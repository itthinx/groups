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

	const NOT_RESTRICTED = "#not-restricted#";

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
		if ( current_user_can( GROUPS_ACCESS_GROUPS ) ) {
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
			add_action( 'admin_head', array( __CLASS__, 'admin_head' ) );
			add_action( 'restrict_manage_posts', array( __CLASS__, 'restrict_manage_posts' ) );
			add_filter( 'parse_query', array( __CLASS__, 'parse_query' ) );
		}
	}

	/**
	 * Enqueues the select script.
	 */
	public static function admin_enqueue_scripts() {

		global $pagenow;

		if ( $pagenow == 'edit.php' ) {
			$post_type = isset( $_GET['post_type'] ) ? $_GET['post_type'] : 'post';
			$post_types_option = Groups_Options::get_option( Groups_Post_Access::POST_TYPES, array() );
			if ( !isset( $post_types_option[$post_type]['add_meta_box'] ) || $post_types_option[$post_type]['add_meta_box'] ) {
				Groups_UIE::enqueue( 'select' );
			}
		}
	}

	/**
	 * Adds CSS rules to display our access restriction filter coherently.
	 */
	public static function admin_head() {

		global $pagenow;

		if ( $pagenow == 'edit.php' ) {
			$post_type = isset( $_GET['post_type'] ) ? $_GET['post_type'] : 'post';
			$post_types_option = Groups_Options::get_option( Groups_Post_Access::POST_TYPES, array() );
			if ( !isset( $post_types_option[$post_type]['add_meta_box'] ) || $post_types_option[$post_type]['add_meta_box'] ) {
				echo '<style type="text/css">';
				echo '.groups-capabilities-container { display: inline-block; line-height: 24px; padding-bottom: 1em; vertical-align: top; margin-left: 1em; margin-right: 1em; }';
				echo '.groups-capabilities-container .groups-select-container { display: inline-block; vertical-align: top; }';
				echo '.groups-capabilities-container .groups-select-container select, .groups-bulk-container select.groups-action { float: none; margin-right: 4px; vertical-align: top; }';
				echo '.groups-capabilities-container .selectize-control { min-width: 128px; }';
				echo '.groups-capabilities-container .selectize-control, .groups-bulk-container select.groups-action { margin-right: 4px; vertical-align: top; }';
				echo '.groups-capabilities-container .selectize-input { font-size: inherit; line-height: 18px; padding: 1px 2px 2px 2px; vertical-align: middle; }';
				echo '.groups-capabilities-container .selectize-input input[type="text"] { font-size: inherit; vertical-align: middle; }';
				echo '.groups-capabilities-container input.button { margin-top: 1px; vertical-align: top; }';
				echo '.tablenav .actions { overflow: visible; }'; // this is important so that the selectize options aren't hidden
				echo '</style>';
			}
		}
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

					// capabilities select
					$output .= '<div class="groups-capabilities-container">';
					$applicable_read_caps = Groups_Options::get_option( Groups_Post_Access::READ_POST_CAPABILITIES, array( Groups_Post_Access::READ_POST_CAPABILITY ) );
					$output .= sprintf(
						'<select class="select capability" name="%s[]" multiple="multiple" placeholder="%s" data-placeholder="%s">',
						esc_attr( Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ_POST_CAPABILITY ),
						esc_attr( __( 'Access restrictions &hellip;', GROUPS_PLUGIN_DOMAIN ) ) ,
						esc_attr( __( 'Access restrictions &hellip;', GROUPS_PLUGIN_DOMAIN ) )
					);

					$previous_selected = array();
					if ( !empty( $_GET[Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ_POST_CAPABILITY] ) ) {
						$previous_selected = $_GET[Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ_POST_CAPABILITY];
						if ( !is_array( $previous_selected ) ) {
							$previous_selected = array();
						}
					}
					$selected = in_array( self::NOT_RESTRICTED, $previous_selected ) ? ' selected="selected" ' : '';
					$output .= sprintf( '<option value="%s" %s >%s</option>', self::NOT_RESTRICTED, esc_attr( $selected ), esc_attr( __( '(only unrestricted)', GROUPS_PLUGIN_DOMAIN ) ) );

					foreach( $applicable_read_caps as $capability ) {
						$selected = in_array( $capability, $previous_selected ) ? ' selected="selected" ' : '';
						$output .= sprintf( '<option value="%s" %s >%s</option>', esc_attr( $capability ), esc_attr( $selected ), wp_filter_nohtml_kses( $capability ) );
					}
					$output .= '</select>';
					$output .= '</div>';
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

					if ( !empty( $_GET[Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ_POST_CAPABILITY] ) &&
						is_array( $_GET[Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ_POST_CAPABILITY] )
					) {

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

						if ( !empty( $capabilities ) ) {
							if ( $include_unrestricted ) {
								// meta_query does not handle a conjunction
								// on the same meta field correctly
								// (at least not up to WordPress 3.7.1)
// 								$query->query_vars['meta_query'] = array (
// 									'relation' => 'OR',
// 									array (
// 										'key' => Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ_POST_CAPABILITY,
// 										'value' => $capabilities,
// 										'compare' => 'IN'
// 									),
// 									array (
// 										'key' => Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ_POST_CAPABILITY,
// 										'compare' => 'NOT EXISTS'
// 									)
// 								);
								// we'll limit it to show just unrestricted entries
								// until the above is solved
								$query->query_vars['meta_query'] = array (
									array (
										'key'     => Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ_POST_CAPABILITY,
										'compare' => 'NOT EXISTS'
									)
								);
							} else {
								$query->query_vars['meta_query'] = array (
									array (
										'key'     => Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ_POST_CAPABILITY,
										'value'   => $capabilities,
										'compare' => 'IN'
									)
								);
							}
						} else if ( $include_unrestricted ) {
							$query->query_vars['meta_query'] = array (
								array (
									'key'     => Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ_POST_CAPABILITY,
									'compare' => 'NOT EXISTS'
								)
							);
						}
					}
				}
			}

		}

	}

}
Groups_Admin_Posts::init();
