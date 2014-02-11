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

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

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

			add_action( 'bulk_edit_custom_box', array( __CLASS__, 'bulk_edit_custom_box' ), 10, 2);
			add_action( 'save_post', array( __CLASS__, 'save_post' ) );
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
				echo '.groups-capabilities-container { display: inline-block; line-height: 24px; padding-bottom: 1em; vertical-align: top; margin-left: 4px; margin-right: 4px; }';
				echo '.groups-capabilities-container .groups-select-container { display: inline-block; vertical-align: top; }';
				echo '.groups-capabilities-container .groups-select-container select, .groups-bulk-container select.groups-action { float: none; margin-right: 4px; vertical-align: top; }';
				echo '.groups-capabilities-container .selectize-control { min-width: 128px; }';
				echo '.groups-capabilities-container .selectize-control, .groups-bulk-container select.groups-action { margin-right: 4px; vertical-align: top; }';
				echo '.groups-capabilities-container .selectize-input { font-size: inherit; line-height: 18px; padding: 1px 2px 2px 2px; vertical-align: middle; }';
				echo '.groups-capabilities-container .selectize-input input[type="text"] { font-size: inherit; vertical-align: middle; }';
				echo '.groups-capabilities-container input.button { margin-top: 1px; vertical-align: top; }';
				echo '.inline-edit-row fieldset .capabilities-bulk-container label span.title { min-width: 5em; padding: 2px 1em; width: auto; }';
				echo '.tablenav .actions { overflow: visible; }'; // this is important so that the selectize options aren't hidden
				echo '.wp-list-table td { overflow: visible; }'; // idem for bulk actions
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
	 * Bulk-edit access restriction capabilities.
	 * 
	 * @param string $column_name
	 * @param string $post_type
	 */
	public static function bulk_edit_custom_box( $column_name, $post_type ) {

		global $pagenow, $wpdb;

		if ( $column_name == 'capabilities' ) {

			if ( $pagenow == 'edit.php' ) { // check that we're on the right screen

				$post_type = isset( $_GET['post_type'] ) ? $_GET['post_type'] : 'post';
				$post_types_option = Groups_Options::get_option( Groups_Post_Access::POST_TYPES, array() );

				if ( !isset( $post_types_option[$post_type]['add_meta_box'] ) || $post_types_option[$post_type]['add_meta_box'] ) {

					$output = '<fieldset class="inline-edit-col-right">';
					$output .= '<div class="bulk-edit-groups">';

					// capability/access restriction bulk actions added through extra_tablenav()
					$output .= '<div id="capability-bulk-actions" class="capabilities-bulk-container" style="display:inline">';

					$output .= '<label style="display:inline;">';
					$output .= '<span class="title">';
					$output .= __( 'Access Restrictions', GROUPS_PLUGIN_DOMAIN );
					$output .= '</span>';
					$output .= '<select class="capabilities-action" name="capabilities-action">';
					$output .= '<option selected="selected" value="-1">' . __( '&mdash; No Change &mdash;', GROUPS_PLUGIN_DOMAIN ) . '</option>';
					$output .= '<option value="add-capability">' . __( 'Add restriction', GROUPS_PLUGIN_DOMAIN ) . '</option>';
					$output .= '<option value="remove-capability">' . __( 'Remove restriction', GROUPS_PLUGIN_DOMAIN ) . '</option>';
					$output .= '</select>';
					$output .= '</label>';

					$output .= '<div class="groups-capabilities-container">';
					$valid_read_caps = Groups_Access_Meta_Boxes::get_valid_read_caps_for_user();
					$output .= sprintf(
						'<select class="select bulk-capability" name="%s[]" multiple="multiple" placeholder="%s" data-placeholder="%s">',
						esc_attr( Groups_Post_Access::POSTMETA_PREFIX . 'bulk-' . Groups_Post_Access::READ_POST_CAPABILITY ),
						esc_attr( __( 'Choose access restrictions &hellip;', GROUPS_PLUGIN_DOMAIN ) ) ,
						esc_attr( __( 'Choose access restrictions &hellip;', GROUPS_PLUGIN_DOMAIN ) )
					);

					foreach( $valid_read_caps as $capability ) {
						$output .= sprintf( '<option value="%s" >%s</option>', esc_attr( $capability ), wp_filter_nohtml_kses( $capability ) );
					}
					$output .= '</select>';
					$output .= '</div>'; // .groups-capabilities-container
					$output .= Groups_UIE::render_select( '.select.bulk-capability' );

					$output .= '</div>'; // .capabilities-bulk-container

					$output .= '</div>'; // .bulk-edit-groups
					$output .= '</fieldset>'; // .inline-edit-col-right

					$output .= wp_nonce_field( 'post-capability', 'bulk-post-capability-nonce', true, false );

					echo $output;
				}
			}
		}
	}

	/**
	 * Handles access restriction capability modifications from bulk-editing.
	 * This is called once for each post that is included in bulk-editing.
	 * The fields that are handled here are rendered through the
	 * bulk_edit_custom_box() method in this class.
	 * 
	 * @param int $post_id
	 */
	public static function save_post( $post_id ) {
		if ( isset( $_REQUEST['capabilities-action'] ) ) {
			if ( wp_verify_nonce( $_REQUEST['bulk-post-capability-nonce'], 'post-capability' ) ) {
				$field = Groups_Post_Access::POSTMETA_PREFIX . 'bulk-' . Groups_Post_Access::READ_POST_CAPABILITY;
				if ( !empty( $_REQUEST[$field] ) && is_array( $_REQUEST[$field] ) ) {
					if ( Groups_Access_Meta_Boxes::user_can_restrict() ) {
						$valid_read_caps = Groups_Access_Meta_Boxes::get_valid_read_caps_for_user();
						foreach( $_REQUEST[$field] as $capability_name ) {
							if ( $capability = Groups_Capability::read_by_capability( $capability_name ) ) {
								if ( in_array( $capability->capability, $valid_read_caps ) ) {
									switch( $_REQUEST['capabilities-action'] ) {
										case 'add-capability' :
											Groups_Post_Access::create( array(
												'post_id' => $post_id,
												'capability' => $capability->capability
											) );
											break;
										case 'remove-capability' :
											Groups_Post_Access::delete( $post_id, $capability->capability );
											break;
									}
								}
							}
						}
					}
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
