<?php
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Property\Selector;

/**
 * class-groups-uie.php
 *
 * Copyright (c) "kento" Karim Rahimpur www.itthinx.com
 *
 * This code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt.
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This header and all notices must be kept intact.
 *
 * @author Karim Rahimpur
 * @package groups
 * @since groups 1.3.14
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * User Interface Extensions.
 *
 * This class may yet be subject to changes in method signatures. External
 * dependency is not advised until the private access restriction is removed.
 *
 * @access private
 */
class Groups_UIE {

	/**
	 * Extension used for select.
	 *
	 * @var string
	 */
	private static $select = 'tom-select';

	/**
	 * Setup.
	 */
	public static function init() {
	}

	/**
	 * Extension chooser - determines what UI extension is used for an element.
	 *
	 * @param string $element choices: select
	 * @param string $extension choices: tom-select, selectize
	 */
	public static function set_extension( $element, $extension ) {
		switch ( $element ) {
			case 'select' :
				self::$select = sanitize_key( $extension );
				break;
		}
	}

	/**
	 * Tell which select is being used.
	 *
	 * @since 4.0.0
	 *
	 * @return mixed
	 */
	public static function which_select() {
		/**
		 * Allow to filter the select to use.
		 *
		 * @since 4.0.0
		 *
		 * @param string $select which select
		 *
		 * @return string
		 */
		return sanitize_key( apply_filters( 'groups_uie_which_select', self::$select ) ?? '' );
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public static function enqueue( $element = null ) {
		global $groups_version;
		switch ( $element ) {
			case 'select' :
				switch ( self::which_select() ) {
					case 'selectize':
						if ( !wp_script_is( 'groups-selectize' ) ) {
							wp_enqueue_script( 'groups-selectize', GROUPS_PLUGIN_URL . 'js/selectize/selectize.min.js', array( 'jquery' ), $groups_version, false );
						}
						if ( !wp_style_is( 'groups-selectize' ) ) {
							wp_enqueue_style( 'groups-selectize', GROUPS_PLUGIN_URL . 'css/selectize/selectize.css', array(), $groups_version );
						}
						if ( !wp_style_is( 'groups-uie' ) ) {
							wp_enqueue_style( 'groups-uie', GROUPS_PLUGIN_URL . 'css/groups-uie.css', array(), $groups_version );
						}
						break;
					default:
						if ( !wp_script_is( 'groups-tom-select' ) ) {
							// Tom Select does not require jQuery but we add it as a dependency for remnant code that assumes its presence.
							wp_enqueue_script( 'groups-tom-select', GROUPS_PLUGIN_URL . 'js/tom-select/tom-select.complete.min.js', array( 'jquery' ), $groups_version, false );
						}
						if ( !wp_style_is( 'groups-tom-select' ) ) {
							wp_enqueue_style( 'groups-tom-select', GROUPS_PLUGIN_URL . 'css/tom-select/tom-select.groups.css', array(), $groups_version );
						}
						if ( !wp_style_is( 'groups-uie' ) ) {
							wp_enqueue_style( 'groups-uie', GROUPS_PLUGIN_URL . 'css/groups-uie.css', array(), $groups_version );
						}
						break;
				}
				break;
		}
	}

	/**
	 * Render select script and style.
	 *
	 * @param string $selector identifying the select, default: select.groups-uie
	 * @param boolean $script render the script, default: true
	 * @param boolean $on_document_ready whether to trigger on document ready, default: true
	 * @param boolean $create allow to create items, default: false (only with selectize)
	 *
	 * @return string HTML
	 */
	public static function render_select( $selector = 'select.groups-uie', $script = true, $on_document_ready = true, $create = false ) {
		$output = '';
		if ( $script ) {

			$call_output = '';
			switch ( self::$select ) {
				case 'selectize':
					$call_output .= 'if ( typeof jQuery !== "undefined" && typeof jQuery.fn.selectize === "function" ) {';
					$call_output .= sprintf(
						'jQuery("%s").selectize({%splugins: ["remove_button"],wrapperClass:"groups-selectize"});',
						$selector,
						$create ? 'create:true,' : ''
					);
					$call_output .= '}';
					break;
				default:
					/**
					 * Allows to determine the maximum number of options displayed in the dropdown.
					 *
					 * Provide a number > 0, or null for no limit.
					 * Defaults to null for unlimited options displayed.
					 * This limits the number of options that are displayed, but not the total available options.
					 *
					 * @since 4.1.0
					 *
					 * @param int|null $max_options
					 */
					$max_options = apply_filters( 'groups_uie_render_select_display_limit', null );
					if ( is_numeric( $max_options ) ) {
						$max_options = max( 1, intval( $max_options ) );
					} else {
						$max_options = null;
					}
					$call_output .= 'if ( typeof jQuery !== "undefined" && typeof tomSelect === "function" ) {';
					$call_output .= sprintf(
						'tomSelect("%s",{%splugins: ["remove_button","clear_button"],maxOptions:%s,wrapperClass:"groups-ts-wrapper",controlClass:"groups-ts-control",dropdownClass:"groups-ts-dropdown",dropdownContentClass:"groups-ts-dropdown-content"});',
						$selector,
						$create ? 'create:true,' : '',
						$max_options === null ? 'null' : $max_options
					);
					$call_output .= '}';
					break;
			}

			// Our selectize options will be hidden unless the block editor's components panel allows to overflow.
			$output .= '<style type="text/css">';
			$output .= '.components-panel { overflow: visible !important; }';
			$output .= '</style>';
			// Act immediately if DOMContentLoaded was already dispatched, otherwise defer to handler.
			$output .= '<script type="text/javascript">';
			$output .= 'if ( document.readyState === "complete" || document.readyState === "interactive" ) {';
			$output .= $call_output;
			$output .= '}';
			if ( $on_document_ready ) {
				$output .= ' else {';
				$output .= 'document.addEventListener( "DOMContentLoaded", function() {';
				$output .= $call_output;
				$output .= '} );'; // document....
				$output .= '}'; // else
			}
			$output .= '</script>';
		}
		return $output;
	}

	/**
	 * Render the title attribute for elements matching the selector.
	 *
	 * @param string $selector
	 *
	 * @return string
	 */
	public static function render_add_titles( $selector ) {
		$output = '<script type="text/javascript">';
		$output .= 'if ( typeof jQuery !== "undefined" ) {';
		$output .= sprintf( 'jQuery("%s").each(', $selector );
		$output .= 'function(){';
		$output .= 'var title = jQuery(this).html().replace( /(<\/[^>]+>)/igm , "$1 ");';
		$output .= 'jQuery(this).attr("title", this.innerText || jQuery(jQuery.parseHTML(title)).text().replace(/\s+/igm, " ") );';
		$output .= '}';
		$output .= ');';
		$output .= '}';
		$output .= '</script>';
		return $output;
	}
}
Groups_UIE::init();
