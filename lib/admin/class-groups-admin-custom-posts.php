<?php
/**
 * class-groups-admin-custom-posts.php
 *
 * Copyright (c) 2012 "kento" Karim Rahimpur www.itthinx.com
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
 * @author Antonio Blanco
 * @package groups
 * @since groups 1.4.2
 */

/**
 * Posts admin integration with Groups.
 */
class Groups_Admin_Custom_Posts {

	const GROUPS = 'groups_custom_post_groups';
	
	/**
	 * Hooks into filters to add the Groups column to the posts table.
	 */
	public static function init() {
		// we hook this on admin_init so that current_user_can() is available
		add_action( 'admin_init', array( __CLASS__, 'setup' ) );
	}

	/**
	 * Adds the filters and actions only for users who have the right
	 * Groups permissions.
	 */
	public static function setup() {
		if ( current_user_can( GROUPS_ACCESS_GROUPS ) ) {
			$post_types = get_post_types( array( 'public' => true ), 'names' );
			$post_types_option = Groups_Options::get_option( Groups_Post_Access::POST_TYPES, array() );
			
			foreach ( $post_types as $post_type ) {
				// Product and product_variation is added as posts.
				if ( ($post_type !== "product_variation") && ($post_type !== "product") && ($post_type !== "post") && ($post_type !== "page") ){
					// !isset it is because new custom posts type can be added without save groups->options
					if ( !isset( $post_types_option[$post_type]['add_meta_box'] ) || $post_types_option[$post_type]['add_meta_box'] ) {
						// filters to display the posts's groups
						add_filter('manage_edit-' . $post_type . '_columns', array( __CLASS__, 'manage_edit_custom_posts_custom_columns' ) );
						// args: string $column_name, int $post_id
						add_action( 'manage_' . $post_type . '_posts_custom_column', array( __CLASS__, 'manage_custom_posts_posts_custom_column' ), 10, 2 );
					}
				}
			}
		}
		if ( current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
			if ( !is_network_admin() ) {
				// styles
				add_action( 'admin_head', array( __CLASS__, 'admin_head' ) );
			}
		}
	}
	
	/**
	 * Adds the groups css style.
	 */
	public static function admin_head() {
	
		global $pagenow;
	
		if ( $pagenow == 'edit.php' ) {
			echo '<style type="text/css">';
			echo '.column-' . self::GROUPS . ' { width: 15%; }';
			echo '</style>';
		}
	}

	/**
	 * Adds a new column to the posts table to show the capabilities
	 * 
	 * @param array $column_headers
	 * @return array column headers
	 */
	public static function manage_edit_custom_posts_custom_columns( $column_headers ) {
		$column_headers[self::GROUPS] = __( 'Capabilities', GROUPS_PLUGIN_DOMAIN );
		return $column_headers;
	}

	/**
	 * Renders custom column content.
	 * 
	 * @param string $column_name
	 * @param int $post_id
	 * @return string custom column content
	 */
	public static function manage_custom_posts_posts_custom_column( $column_name, $post_id ) {
		$output = "";
		switch ( $column_name ) {
			case self::GROUPS :
				$user = new Groups_User( get_current_user_id() );
			
				$read_caps = get_post_meta( $post_id, Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ_POST_CAPABILITY );
				$valid_read_caps = Groups_Options::get_option( Groups_Post_Access::READ_POST_CAPABILITIES, array( Groups_Post_Access::READ_POST_CAPABILITY ) );
				if ( count( $valid_read_caps ) > 0 ) {
					$output = '<ul>';
					foreach( $valid_read_caps as $valid_read_cap ) {
						if ( $capability = Groups_Capability::read_by_capability( $valid_read_cap ) ) {
							if ( in_array( $valid_read_cap, $read_caps ) ) {
								$output .= '<li>';
								$output .= wp_filter_nohtml_kses( $capability->capability );
								$output .= '</li>';
							}
						}
					}
					$output .= '</ul>';
				} else {
					$output = __( '--', GROUPS_PLUGIN_DOMAIN );
				}
				break;
		}	
		echo $output;
	}
}
Groups_Admin_Custom_Posts::init();
