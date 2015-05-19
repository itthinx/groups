<?php
/**
 * class-groups-help.php
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
 * @since groups 1.0.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Help renderer.
 */
class Groups_Help {
	
	/**
	 * Help setup.
	 */
	public static function init() {
		add_action( 'groups_admin_menu', array( __CLASS__, 'groups_admin_menu' ) );
		// @todo temporary fix for GFA <= 1.0.11 on localized installations - can be removed when all are updated
		if ( defined( 'GFA_VIEWS_LIB' ) ) {
			if ( file_exists( GFA_VIEWS_LIB . '/class-gfa-help.php' ) ) {
				include_once( GFA_VIEWS_LIB . '/class-gfa-help.php' );
			}
		}
	}
	
	/**
	 * Adds contextual help to Groups admin screens.
	 * 
	 * @param array $pages admin pages
	 */
	public static function groups_admin_menu( $pages ) {
		foreach ( $pages as $page ) {
			add_action( 'load-' . $page, array( __CLASS__, 'contextual_help' ) );
		}
	}
	
	/**
	 * Adds help to an admin screen.
	 */
	public static function contextual_help() {
		if ( $screen = get_current_screen() ) {
			$show_groups_help = false;
			$help_title = __( 'Groups', GROUPS_PLUGIN_DOMAIN );
			$screen_id = $screen->base;
			// The prefix of the $screen_id is translated, use only the suffix
			// to identify a screen:
			$ids = array(
				'groups-admin' => __( 'Groups', GROUPS_PLUGIN_DOMAIN ),
				'groups-admin-groups' => __( 'Groups', GROUPS_PLUGIN_DOMAIN ),
				'groups-admin-options' => __( 'Options', GROUPS_PLUGIN_DOMAIN ),
				'groups-admin-capabilities' => __( 'Capabilities', GROUPS_PLUGIN_DOMAIN ),
			);
			foreach ( $ids as $id => $title ) {
				$i = strpos( $screen_id, $id );
				if ( $i !== false ) {
					if ( $i + strlen( $id ) == strlen( $screen_id ) ) {
						$screen_id = $id;
						$show_groups_help = true;
						$help_title = $title;
						break;
					}
				}
			}
			if ( $show_groups_help ) {
				$help = '<h3><a href="http://www.itthinx.com/plugins/groups" target="_blank">'. $help_title .'</a></h3>';
				$help .= '<p>';
				$help .= __( 'The complete documentation is available on the <a href="http://docs.itthinx.com/document/groups">Documentation</a> pages for Groups.', GROUPS_PLUGIN_DOMAIN );
				$help .= '</p>';
				switch ( $screen_id ) {
					case 'groups-admin' :
					case 'groups-admin-groups':
						$help .= '<p>' . __( 'Here you can <strong>add</strong>, <strong>edit</strong> and <strong>remove</strong> groups.', GROUPS_PLUGIN_DOMAIN ) . '</p>';
						break;
					case 'groups-admin-options' :
					case 'groups-admin-capabilities' :
						break;
				}

				$screen->add_help_tab(
					array(
						'id'      => $screen_id,
						'title'   => $help_title,
						'content' => $help
					)
				);
			}
		}
	}

	/**
	 * Returns or renders the footer.
	 * @param boolean $render
	 */
	public static function footer( $render = true ) {
		$footer =
			'<div class="groups-footer">' .
			__( 'Thank you for using <a href="http://www.itthinx.com/plugins/groups" target="_blank">Groups</a> by <a href="http://www.itthinx.com" target="_blank">itthinx</a>.', GROUPS_PLUGIN_DOMAIN ) .
			'</div>';
		$footer = apply_filters( 'groups_footer', $footer );
		if ( $render ) {
			echo $footer;
		} else {
			return $footer;
		}
	}
}
Groups_Help::init();
