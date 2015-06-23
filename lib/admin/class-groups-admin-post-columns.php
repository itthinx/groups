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

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Post column extensions.
 */
class Groups_Admin_Post_Columns {

	const CAPABILITIES = 'capabilities';

	/**
	 * Adds an admin_init action.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
	}

	/**
	 * Adds the filters and actions only for users who have the right
	 * Groups permissions and for the post types that have access
	 * restrictions enabled.
	 */
	public static function admin_init() {
		if ( current_user_can( GROUPS_ACCESS_GROUPS ) ) {
			$post_types = get_post_types( array( 'public' => true ) );
			$post_types_option = Groups_Options::get_option( Groups_Post_Access::POST_TYPES, array() );
			foreach ( $post_types as $post_type ) {
				if ( !isset( $post_types_option[$post_type]['add_meta_box'] ) || $post_types_option[$post_type]['add_meta_box'] ) {
					if ( ( $post_type == 'attachment' ) ) {
						// filters to display the media's access restriction capabilities
						add_filter( 'manage_media_columns', array( __CLASS__, 'columns' ) );
						// args: string $column_name, int $media_id
						add_action( 'manage_media_custom_column', array( __CLASS__, 'custom_column' ), 10, 2 );
					} else {
						// filters to display the posts' access restriction capabilities
						add_filter( 'manage_' . $post_type . '_posts_columns', array( __CLASS__, 'columns' ) );
						// args: string $column_name, int $post_id
						add_action( 'manage_' . $post_type . '_posts_custom_column', array( __CLASS__, 'custom_column' ), 10, 2 );
					}
				}
			}
		}
	}

	/**
	 * Adds a new column to the post type's table showing the access
	 * restriction capabilities.
	 * 
	 * @param array $column_headers
	 * @return array column headers
	 */
	public static function columns( $column_headers ) {
		$column_headers[self::CAPABILITIES] = sprintf(
			__( '<span title="%s">Access Restrictions</span>', GROUPS_PLUGIN_DOMAIN ),
			esc_attr( __( 'One or more capabilities required to read the entry.', GROUPS_PLUGIN_DOMAIN ) )
		);
		return $column_headers;
	}

	/**
	 * Renders custom column content.
	 * 
	 * @param string $column_name
	 * @param int $post_id
	 * @return string custom column content
	 */
	public static function custom_column( $column_name, $post_id ) {
		$output = '';
		switch ( $column_name ) {
			case self::CAPABILITIES :
				$read_caps = get_post_meta( $post_id, Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ_POST_CAPABILITY );
				$valid_read_caps = Groups_Options::get_option( Groups_Post_Access::READ_POST_CAPABILITIES, array( Groups_Post_Access::READ_POST_CAPABILITY ) );
				if ( count( $valid_read_caps ) > 0 ) {
					sort( $valid_read_caps );
					$output = '<ul>';
					foreach( $valid_read_caps as $valid_read_cap ) {
						if ( $capability = Groups_Capability::read_by_capability( $valid_read_cap ) ) {
							if ( in_array( $valid_read_cap, $read_caps ) ) {
								$output .= '<li>';
								$output .= wp_strip_all_tags( $capability->capability );
								$output .= '</li>';
							}
						}
					}
					$output .= '</ul>';
				} else {
					$output .= '';
				}
				break;
		}
		echo $output;
	}
}
Groups_Admin_Post_Columns::init();
