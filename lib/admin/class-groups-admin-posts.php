<?php
/**
 * class-groups-admin-posts.php
 *
 * Copyright (c) 2013 "kento" Karim Rahimpur www.itthinx.com
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
 * @since groups 1.4.2
 */

/**
 * Additions to post overview admin screens.
 */
class Groups_Admin_Posts {

	const NOT_RESTRICTED = "#";
	
	/**
	 * Sets up an admin_init hook where our actions and filters are added.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
	}

	/**
	 * Adds actions and filters to handle filtering by access restriction
	 * capability.
	 */
	public static function admin_init() {
		global $pagenow;
		
		if ( $pagenow == 'edit.php' ) {
			Groups_UIE::enqueue( 'select' );
		}
		
		add_action( 'restrict_manage_posts', array( __CLASS__, 'restrict_manage_posts' ) );
		add_filter( 'parse_query', array( __CLASS__, 'parse_query' ) );
	}

	/**
	 * Renders the access restriction field.
	 */
	public static function restrict_manage_posts() {

		global $pagenow, $wpdb;

		if ( is_admin() ) {

			if ( $pagenow == 'edit.php' ) { // check that we're on the right screen

				$post_type = isset( $_GET['post_type'] ) ? $_GET['post_type'] : 'post';

				$post_types_option = Groups_Options::get_option( Groups_Post_Access::POST_TYPES, array() );
				
				if ( !isset( $post_types_option[$post_type]['add_meta_box'] ) || $post_types_option[$post_type]['add_meta_box'] ) {
				
					$output = '';

					$capability_table = _groups_get_tablename( "capability" );
					
					// capabilities select
					$capabilities = $wpdb->get_results( "SELECT * FROM $capability_table ORDER BY capability" );
					$output .= sprintf(
							'<select class="select capability" name="%s[]" multiple="multiple" placeholder="%s" data-placeholder="%s">',
							esc_attr( Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ_POST_CAPABILITY ),
							esc_attr( __( 'Capabilities &hellip;', GROUPS_PLUGIN_DOMAIN ) ) ,
							esc_attr( __( 'Capabilities &hellip;', GROUPS_PLUGIN_DOMAIN ) )
					);
					$output .= sprintf( '<option value="%s">%s</option>', self::NOT_RESTRICTED, esc_attr( __( "( Not restricted )", GROUPS_PLUGIN_DOMAIN ) ) );
					foreach( $capabilities as $capability ) {
						$output .= sprintf( '<option value="%s">%s</option>', esc_attr( $capability->capability ), wp_filter_nohtml_kses( $capability->capability ) );
					}
					$output .= '</select>';
					$output .= Groups_UIE::render_select( '.select.capability' );

					echo $output;
				}

			}
		}

	}

	/**
	 * Query modifier to take the selected access restriction capability into
	 * account.
	 * 
	 * @param WP_Query $query query object passed by reference
	 */
	public static function parse_query( &$query ) {

		global $pagenow;

		if ( is_admin() ) {

			if ( $pagenow == 'edit.php' ) { // check that we're on the right screen

				$post_type = isset( $_GET['post_type'] ) ? $_GET['post_type'] : 'post';

				$post_types_option = Groups_Options::get_option( Groups_Post_Access::POST_TYPES, array() );
				
				if ( !isset( $post_types_option[$post_type]['add_meta_box'] ) || $post_types_option[$post_type]['add_meta_box'] ) {
				
					if ( !empty( $_GET[Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ_POST_CAPABILITY] ) ) {

						$include_unrestricted = false;
						if ( in_array( self::NOT_RESTRICTED, $_GET[Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ_POST_CAPABILITY] ) ) {
							$include_unrestricted = true;
						} 
						$capabilities = array();
						
						foreach ( $_GET[Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ_POST_CAPABILITY] as $capability ) {
							if ( Groups_Capability::read_by_capability( $capability ) ) {
								$capabilities[] = $capability;
							}
						}
						if ( $include_unrestricted || !empty( $capabilities ) ) {
							if ( $include_unrestricted ) {
								$query->query_vars['meta_query'] = array (
									'relation' => 'OR',
									array (
											'key' => Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ_POST_CAPABILITY,
											'compare' => 'NOT EXISTS'
									),
									array (
											'key' => Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ_POST_CAPABILITY,
											'value' => $capabilities,
											'compare' => 'IN'
									)
								);
							} else {
								$query->query_vars['meta_query'] = array (
									array (
											'key' => Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ_POST_CAPABILITY,
											'value' => $capabilities,
											'compare' => 'IN'
									)
								);
							}
						}
					}
				}
			}

		}

	}

}
Groups_Admin_Posts::init();
