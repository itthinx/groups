<?php
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
	 * Extension used for select
	 * @var string
	 */
	private static $select = 'selectize';

	/**
	 * Setup.
	 */
	public static function init() {
	}

	/**
	 * Extension chooser - determines what UI extension is used for an element.
	 * 
	 * @param string $element choices: select
	 * @param string $extension choices: selectize
	 */
	public static function set_extension( $element, $extension ) {
		switch( $element ) {
			case 'select' :
				self::$select = $extension;
				break;
		}
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public static function enqueue( $element = null ) {
		global $groups_version;
		switch( $element ) {
			case 'select' :
				switch ( self::$select ) {
					case 'selectize' :
						if ( !wp_script_is( 'selectize' ) ) {
							wp_enqueue_script( 'selectize', GROUPS_PLUGIN_URL . 'js/selectize/selectize.min.js', array( 'jquery' ), $groups_version, false );
						}
						if ( !wp_style_is( 'selectize' ) ) {
							wp_enqueue_style( 'selectize', GROUPS_PLUGIN_URL . 'css/selectize/selectize.bootstrap2.css', array(), $groups_version );
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
	 * @param string $selector identifying the select, default: select.groups-uie
	 * @param boolean $script render the script, default: true
	 * @param boolean $on_document_ready whether to trigger on document ready, default: true
	 * @param boolean $create allow to create items, default: false (only with selectize)
	 * @return string HTML
	 */
	public static function render_select( $selector = 'select.groups-uie', $script = true, $on_document_ready = true, $create = false ) {
		$output = '';
		if ( $script ) {

			$call_output = '';
			if ( self::$select === 'selectize' ) {
				$call_output .= 'if ( typeof jQuery !== "undefined" ) {';
				$call_output .= sprintf(
					'jQuery("%s").selectize({%splugins: ["remove_button"]});',
					$selector,
					$create ? 'create:true,' : ''
				);
				$call_output .= '}';
			}

			// Our selectize options will be hidden unless the block editor's components panel allows to overflow.
			$output .= '<style type="text/css">';
			$output .= '.components-panel { overflow: visible!important; }';
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
