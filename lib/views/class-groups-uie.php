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
	 * @param string $extension choices: chosen, selectize
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
					case 'chosen' :
						if ( !wp_script_is( 'chosen' ) ) {
							wp_enqueue_script( 'chosen', GROUPS_PLUGIN_URL . 'js/chosen/chosen.jquery.min.js', array( 'jquery' ), $groups_version, false );
						}
						if ( !wp_style_is( 'chosen' ) ) {
							wp_enqueue_style( 'chosen', GROUPS_PLUGIN_URL . 'css/chosen/chosen.min.css', array(), $groups_version );
						}
						break;
					case 'selectize' :
						if ( !wp_script_is( 'selectize' ) ) {
							wp_enqueue_script( 'selectize', GROUPS_PLUGIN_URL . 'js/selectize/selectize.min.js', array( 'jquery' ), $groups_version, false );
						}
						if ( !wp_style_is( 'selectize' ) ) {
							wp_enqueue_style( 'selectize', GROUPS_PLUGIN_URL . 'css/selectize/selectize.bootstrap2.css', array(), $groups_version );
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
	 * @return string HTML
	 */
	public static function render_select( $selector = 'select.groups-uie', $script = true, $on_document_ready = true ) {
		$output = '';
		if ( $script ) {
			$output .= '<script type="text/javascript">';
			$output .= 'if (typeof jQuery !== "undefined"){';
			if ( $on_document_ready ) {
				$output .= 'jQuery("document").ready(function(){';
			}
			switch( self::$select ) {
				case 'chosen' :
					$output .= sprintf( 'jQuery("%s").chosen({width:"100%%",search_contains:true});', $selector );
					break;
				case 'selectize' :
					$output .= sprintf( 'jQuery("%s").selectize({plugins: ["remove_button"]});', $selector );
					break;
			}
			if ( $on_document_ready ) {
				$output .= '});';
			}
			$output .= '}'; // typeof jQuery
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
