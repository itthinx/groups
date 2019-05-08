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
 * Additions to post overview admin screens:
 * - Filter posts by group.
 * - Apply bulk actions to add or remove group access restrictions.
 */
class Groups_Admin_Posts {

	/**
	 * Field name
	 * @var string
	 */
	const GROUPS_READ = 'groups-read';

	const NOT_RESTRICTED = '#not-restricted#';
	const RESTRICTED     = '#restricted#';

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
// 			add_filter( 'parse_query', array( __CLASS__, 'parse_query' ) );

			add_filter( 'posts_where', array( __CLASS__, 'posts_where' ), 10, 2 );
			add_filter( 'posts_join', array( __CLASS__, 'posts_join' ), 10, 2 );
			add_filter( 'posts_orderby', array( __CLASS__, 'posts_orderby' ), 10, 2 );

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
				echo '.groups-groups-container { display: inline-block; line-height: 24px; padding-bottom: 1em; vertical-align: top; margin-left: 4px; margin-right: 4px; }';
				echo '.groups-groups-container .groups-select-container { display: inline-block; vertical-align: top; }';
				echo '.groups-groups-container .groups-select-container select, .groups-bulk-container select.groups-action { float: none; margin-right: 4px; vertical-align: top; }';
				echo '.groups-groups-container .selectize-control { min-width: 128px; }';
				echo '.groups-groups-container .selectize-control, .groups-bulk-container select.groups-action { margin-right: 4px; vertical-align: top; }';
				echo '.groups-groups-container .selectize-input { font-size: inherit; line-height: 18px; padding: 1px 2px 2px 2px; vertical-align: middle; }';
				echo '.groups-groups-container .selectize-input input[type="text"] { font-size: inherit; vertical-align: middle; }';
				echo '.groups-groups-container input.button { margin-top: 1px; vertical-align: top; }';
				echo '.inline-edit-row fieldset .capabilities-bulk-container label span.title { min-width: 5em; padding: 2px 1em; width: auto; }';
				echo '.tablenav .actions { overflow: visible; }'; // this is important so that the selectize options aren't hidden
				echo '.wp-list-table td { overflow: visible; }'; // idem for bulk actions
				echo 'label.groups-read-terms {vertical-align: middle; line-height: 28px; margin-right: 4px; }'; // Terms checkbox
				echo 'th.column-groups, th.column-groups-read { width:10%; }';
				echo '</style>';
			}
		}
	}

	/**
	 * Renders the groups access restriction filter field.
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
					$output .= '<div class="groups-groups-container">';
					$output .= sprintf(
						'<select class="select group" name="%s[]" multiple="multiple" placeholder="%s" data-placeholder="%s">',
						esc_attr( Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ ),
						esc_attr( __( 'Groups &hellip;', 'groups' ) ) ,
						esc_attr( __( 'Groups &hellip;', 'groups' ) )
					);

					$previous_selected = array();
					if ( !empty( $_GET[Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ] ) ) {
						$previous_selected = $_GET[Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ];
						if ( !is_array( $previous_selected ) ) {
							$previous_selected = array();
						}
					}
					$output .= sprintf(
						'<option value="%s" %s >%s</option>', self::NOT_RESTRICTED,
						esc_attr( in_array( self::NOT_RESTRICTED, $previous_selected ) ? ' selected="selected" ' : '' ),
						esc_attr( __( '(none)', 'groups' ) )
					);
					$output .= sprintf(
						'<option value="%s" %s >%s</option>', self::RESTRICTED,
						esc_attr( in_array( self::RESTRICTED, $previous_selected ) ? ' selected="selected" ' : '' ),
						esc_attr( __( '(any)', 'groups' ) )
					);

					$groups = Groups_Group::get_groups( array( 'order_by' => 'name', 'order' => 'ASC' ) );
					foreach( $groups as $group ) {
						$selected = in_array( $group->group_id, $previous_selected ) ? ' selected="selected" ' : '';
						$output .= sprintf( '<option value="%s" %s >%s</option>', esc_attr( $group->group_id ), esc_attr( $selected ), wp_filter_nohtml_kses( $group->name ) );
					}
					$output .= '</select>';
					$output .= '</div>';
					$output .= Groups_UIE::render_select( '.select.group' );

					if (
						function_exists( 'get_term_meta' ) && // >= WordPress 4.4.0 as we query the termmeta table
						class_exists( 'Groups_Restrict_Categories' ) &&
						method_exists( 'Groups_Restrict_Categories', 'get_controlled_taxonomies' ) &&
						method_exists( 'Groups_Restrict_Categories', 'get_term_read_groups' ) // >= Groups Restrict Categories 2.0.0, the method isn't used here but it wouldn't make any sense to query unless we're >= 2.0.0
					) {
						$output .= sprintf( '<label class="groups-read-terms" title="%s">', esc_attr( __( 'Also look for groups related to terms', 'groups' ) ) );
						$output .= sprintf( '<input type="checkbox" name="groups-read-terms" value="1" %s />', empty( $_GET['groups-read-terms'] ) ? '' : ' checked="checked" ' );
						$output .= __( 'Terms', 'groups' );
						$output .= '</label>';
					}
					echo $output;
				}

			}
		}

	}

	/**
	 * Bulk-edit access restriction groups.
	 * 
	 * @param string $column_name
	 * @param string $post_type
	 */
	public static function bulk_edit_custom_box( $column_name, $post_type ) {

		global $pagenow, $wpdb;

		if ( $column_name == self::GROUPS_READ ) {
			if ( $pagenow == 'edit.php' ) { // check that we're on the right screen

				$post_type = isset( $_GET['post_type'] ) ? $_GET['post_type'] : 'post';
				$post_types_option = Groups_Options::get_option( Groups_Post_Access::POST_TYPES, array() );

				if ( !isset( $post_types_option[$post_type]['add_meta_box'] ) || $post_types_option[$post_type]['add_meta_box'] ) {

					$output = '<fieldset class="inline-edit-col-right">';
					$output .= '<div class="bulk-edit-groups" style="padding:0 0.5em;">';

					// capability/access restriction bulk actions added through extra_tablenav()
					$output .= '<div id="group-bulk-actions" class="groups-bulk-container" style="display:inline">';

					$output .= '<label style="display:inline;">';
					$output .= '<span class="title">';
					$output .= _x( 'Groups', 'Bulk edit field label', 'groups' );
					$output .= '</span>';
					$output .= '<select class="groups-action" name="groups-action">';
					$output .= '<option selected="selected" value="-1">' . __( '&mdash; No Change &mdash;', 'groups' ) . '</option>';
					$output .= '<option value="add-group">' . __( 'Add restriction', 'groups' ) . '</option>';
					$output .= '<option value="remove-group">' . __( 'Remove restriction', 'groups' ) . '</option>';
					$output .= '</select>';
					$output .= '</label>';

					$user    = new Groups_User( get_current_user_id() );
					$include = Groups_Access_Meta_Boxes::get_user_can_restrict_group_ids( get_current_user_id() );
					$groups  = Groups_Group::get_groups( array( 'order_by' => 'name', 'order' => 'ASC', 'include' => $include ) );

					$output .= '<div class="groups-groups-container">';
					$output .= sprintf(
						'<select class="select bulk-group" name="%s[]" multiple="multiple" placeholder="%s" data-placeholder="%s">',
						esc_attr( Groups_Post_Access::POSTMETA_PREFIX . 'bulk-' . Groups_Post_Access::READ ),
						esc_attr( __( 'Choose access restriction groups &hellip;', 'groups' ) ) ,
						esc_attr( __( 'Choose access restriction groups &hellip;', 'groups' ) )
					);

					foreach( $groups as $group ) {
						$output .= sprintf( '<option value="%s" >%s</option>', esc_attr( $group->group_id ), wp_filter_nohtml_kses( $group->name ) );
					}
					$output .= '</select>';
					$output .= '</div>'; // .groups-groups-container
					$output .= Groups_UIE::render_select( '.select.bulk-group' );

					$output .= '</div>'; // .groups-bulk-container

					$output .= '</div>'; // .bulk-edit-groups
					$output .= '</fieldset>'; // .inline-edit-col-right

					$output .= wp_nonce_field( 'post-group', 'bulk-post-group-nonce', true, false );

					echo $output;
				}
			}
		}
	}

	/**
	 * Handles access restriction group modifications from bulk-editing.
	 * This is called once for each post that is included in bulk-editing.
	 * The fields that are handled here are rendered through the
	 * bulk_edit_custom_box() method in this class.
	 * 
	 * @param int $post_id
	 */
	public static function save_post( $post_id ) {
		if ( isset( $_REQUEST['groups-action'] ) ) {
			if ( wp_verify_nonce( $_REQUEST['bulk-post-group-nonce'], 'post-group' ) ) {
				$field = Groups_Post_Access::POSTMETA_PREFIX . 'bulk-' . Groups_Post_Access::READ;
				if ( !empty( $_REQUEST[$field] ) && is_array( $_REQUEST[$field] ) ) {
					if ( Groups_Access_Meta_Boxes::user_can_restrict() ) {
						$include = Groups_Access_Meta_Boxes::get_user_can_restrict_group_ids();
						$groups  = Groups_Group::get_groups( array( 'order_by' => 'name', 'order' => 'ASC', 'include' => $include ) );
						$group_ids = array();
						foreach( $groups as $group ) {
							$group_ids[] = $group->group_id;
						}
						foreach( $_REQUEST[$field] as $group_id ) {
							if ( $group = Groups_Group::read( $group_id ) ) {
								if ( in_array( $group->group_id, $group_ids ) ) {
									switch( $_REQUEST['groups-action'] ) {
										case 'add-group' :
											Groups_Post_Access::create( array(
												'post_id' => $post_id,
												'group_id' => $group->group_id
											) );
											break;
										case 'remove-group' :
											Groups_Post_Access::delete( $post_id, array( 'groups_read' => $group->group_id ) );
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
	 * Query modifier to take the selected access restriction groups into
	 * account.
	 *
	 * @deprecated not used
	 * @param WP_Query $query query object passed by reference
	 */
	public static function parse_query( &$query ) {

		global $pagenow;

		if ( is_admin() ) {

			if ( $pagenow == 'edit.php' ) { // check that we're on the right screen

				$post_type = isset( $_GET['post_type'] ) ? $_GET['post_type'] : 'post';
				$post_types_option = Groups_Options::get_option( Groups_Post_Access::POST_TYPES, array() );

				if ( !isset( $post_types_option[$post_type]['add_meta_box'] ) || $post_types_option[$post_type]['add_meta_box'] ) {

					if ( !empty( $_GET[Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ] ) &&
						is_array( $_GET[Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ] )
					) {

						$include_unrestricted = false;
						if ( in_array( self::NOT_RESTRICTED, $_GET[Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ] ) ) {
							$include_unrestricted = true;
						}

						$group_ids = array();
						foreach ( $_GET[Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ] as $group_id ) {
							if ( Groups_Group::read( $group_id ) ) {
								$group_ids[] = $group_id;
							}
						}

						if ( !empty( $group_ids ) ) {
							if ( $include_unrestricted ) {
								// meta_query does not handle a conjunction
								// on the same meta field correctly
								// (at least not up to WordPress 3.7.1)
// 								$query->query_vars['meta_query'] = array (
// 									'relation' => 'OR',
// 									array (
// 										'key' => Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ,
// 										'value' => $group_ids,
// 										'compare' => 'IN'
// 									),
// 									array (
// 										'key' => Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ,
// 										'compare' => 'NOT EXISTS'
// 									)
// 								);
								// we'll limit it to show just unrestricted entries
								// until the above is solved
								$query->query_vars['meta_query'] = array (
									array (
										'key'     => Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ,
										'compare' => 'NOT EXISTS'
									)
								);
							} else {
								$query->query_vars['meta_query'] = array (
									array (
										'key'     => Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ,
										'value'   => $group_ids,
										'compare' => 'IN'
									)
								);
							}
						} else if ( $include_unrestricted ) {
							$query->query_vars['meta_query'] = array (
								array (
									'key'     => Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ,
									'compare' => 'NOT EXISTS'
								)
							);
						}
					}
				}
			}

		}

	}

	/**
	 * Filters out posts by group. This is used when you choose groups on the post admin screen so that
	 * only those posts who are restricted by groups are shown.
	 * 
	 * @param string $where
	 * @param WP_Query $query
	 * @return string
	 */
	public static function posts_where( $where, $query ) {

		global $wpdb;

		if ( self::extend_for_filter_groups_read( $query ) ) {

			$post_in = array();
			$term_in = array();

			$filter_terms = false;
			if (
				!empty( $_GET['groups-read-terms'] ) &&
				function_exists( 'get_term_meta' ) && // >= WordPress 4.4.0 as we query the termmeta table
				class_exists( 'Groups_Restrict_Categories' ) &&
				method_exists( 'Groups_Restrict_Categories', 'get_controlled_taxonomies' ) &&
				method_exists( 'Groups_Restrict_Categories', 'get_term_read_groups' ) // >= Groups Restrict Categories 2.0.0, the method isn't used here but it wouldn't make any sense to query unless we're >= 2.0.0
			) {
				$filter_terms = true;
			}

			if ( in_array( self::NOT_RESTRICTED, $_GET[Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ] ) ) {
				$condition =
					"SELECT ID post_id FROM $wpdb->posts " .
					"WHERE ID NOT IN (" .
					"SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'groups-read'";
				if ( $filter_terms ) {
					$condition .=
						" UNION ALL " .
						"SELECT p.ID post_id FROM $wpdb->posts p " .
						"LEFT JOIN $wpdb->term_relationships tr ON p.ID = tr.object_id " .
						"LEFT JOIN $wpdb->term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id " .
						"LEFT JOIN $wpdb->termmeta tm ON tt.term_id = tm.term_id " .
						"WHERE tm.meta_key = 'groups-read'";
				}
				$condition .= ")";
				$post_in[] = $condition;
			}

			if ( in_array( self::RESTRICTED, $_GET[Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ] ) ) {
				$condition = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'groups-read'";
				if ( $filter_terms ) {
					$condition .=
						" UNION ALL " .
						"SELECT p.ID post_id FROM $wpdb->posts p " .
						"LEFT JOIN $wpdb->term_relationships tr ON p.ID = tr.object_id " .
						"LEFT JOIN $wpdb->term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id " .
						"LEFT JOIN $wpdb->termmeta tm ON tt.term_id = tm.term_id " .
						"WHERE tm.meta_key = 'groups-read'";
				}
				$post_in[] = $condition;
			}

			$group_ids = array();
			foreach ( $_GET[Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ] as $group_id ) {
				if ( $group_id = Groups_Utility::id( $group_id ) ) {
					if ( Groups_Group::read( $group_id ) ) {
						$group_ids[] = $group_id;
					}
				}
			}

			if ( !empty( $group_ids ) ) {
				$groups = ' ( ' . implode( ',', esc_sql( $group_ids ) ) . ' ) ';
				$condition =
					"SELECT post_id FROM $wpdb->postmeta " .
					"WHERE meta_key = 'groups-read' AND meta_value IN $groups";
				if ( $filter_terms ) {
					$condition .=
					" UNION ALL " .
					"SELECT p.ID post_id FROM $wpdb->posts p " .
					"LEFT JOIN $wpdb->term_relationships tr ON p.ID = tr.object_id " .
					"LEFT JOIN $wpdb->term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id " .
					"LEFT JOIN $wpdb->termmeta tm ON tt.term_id = tm.term_id " .
					"WHERE tm.meta_key = 'groups-read' AND tm.meta_value IN $groups";
				}
				$post_in[] = $condition;
			}

			if ( count( $post_in ) > 0 ) {
				if (
					!empty( $_GET['groups-read-terms'] ) &&
					function_exists( 'get_term_meta' ) && // >= WordPress 4.4.0 as we query the termmeta table
					class_exists( 'Groups_Restrict_Categories' ) &&
					method_exists( 'Groups_Restrict_Categories', 'get_controlled_taxonomies' ) &&
					method_exists( 'Groups_Restrict_Categories', 'get_term_read_groups' ) // >= Groups Restrict Categories 2.0.0, the method isn't used here but it wouldn't make any sense to query unless we're >= 2.0.0
				) {
					$post_in = array_merge( $post_in, $term_in );
				}
				$id_in = implode( ' UNION ALL ', $post_in );
				$where .= " AND $wpdb->posts.ID IN ( $id_in ) ";
			}

		}

		return $where;
	}

	/**
	 * Adds to the join to allow advanced sorting by group on the admin back end for post tables.
	 *
	 * @param string $join
	 * @param WP_Query $query
	 */
	public static function posts_join( $join, $query ) {
		global $wpdb;
		if ( self::extend_for_orderby_groups_read( $query ) ) {
			$group_table = _groups_get_tablename( 'group' );
			if ( function_exists( 'get_term_meta' ) ) { // >= WordPress 4.4.0 as we query the termmeta table
				$join .= "
					LEFT JOIN (
						SELECT p.ID post_id, GROUP_CONCAT(DISTINCT groups_read.group_name ORDER BY groups_read.group_name) groups
						FROM $wpdb->posts p
						LEFT JOIN (
							SELECT post_id, g.name group_name
							FROM $wpdb->postmeta pm
							LEFT JOIN $group_table g ON pm.meta_value = g.group_id
							WHERE pm.meta_key = 'groups-read'
								UNION ALL
							SELECT p.ID post_id, g.name group_name
							FROM $wpdb->posts p
							LEFT JOIN $wpdb->term_relationships tr ON p.ID = tr.object_id
							LEFT JOIN $wpdb->term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
							LEFT JOIN $wpdb->termmeta tm ON tt.term_id = tm.term_id
							LEFT JOIN $group_table g ON tm.meta_value = g.group_id
							WHERE tm.meta_key = 'groups-read'
						) as groups_read ON p.ID = groups_read.post_id
						GROUP BY p.ID
					) groups_tmp ON $wpdb->posts.ID = groups_tmp.post_id
					";
			} else {
				$join .= "
					LEFT JOIN (
						SELECT p.ID post_id, GROUP_CONCAT(DISTINCT groups_read.group_name ORDER BY groups_read.group_name) groups
						FROM $wpdb->posts p
						LEFT JOIN (
						SELECT post_id, g.name group_name
						FROM $wpdb->postmeta pm
						LEFT JOIN $group_table g ON pm.meta_value = g.group_id
						WHERE pm.meta_key = 'groups-read'
						) as groups_read ON p.ID = groups_read.post_id
						GROUP BY p.ID
					) groups_tmp ON $wpdb->posts.ID = groups_tmp.post_id
					";
			}
		}
		return $join;
	}

	/**
	 * Extend the orderby clause to sort by groups related to the post and its terms.
	 *
	 * @param $string $orderby
	 * @param WP_Query $query
	 * @return string
	 */
	public static function posts_orderby( $orderby, $query ) {
		if ( self::extend_for_orderby_groups_read( $query ) ) {
			switch( $query->get( 'order' ) ) {
				case 'desc' :
				case 'DESC' :
					$order = 'DESC';
					break;
				default :
					$order = 'ASC';
			}
			$prefix = ' groups_tmp.groups ' . $order;
			if ( !empty( $orderby ) ) {
				$prefix .= ' , ';
			}
			$orderby = $prefix . $orderby;
		}
		return $orderby;
	}

	/**
	 * Check if we should apply our posts_join and posts_orderby filters. Used in those.
	 *
	 * @param WP_Query $query
	 * @return boolean
	 */
	private static function extend_for_orderby_groups_read( &$query ) {
		$result = false;
		if ( is_admin() ) {
			// check if query is for a post type we handle
			$post_types = $query->get( 'post_type' );
			if ( !is_array( $post_types ) ) {
				$post_types = array( $post_types );
			}
			foreach( $post_types as $post_type ) {
				$post_types_option = Groups_Options::get_option( Groups_Post_Access::POST_TYPES, array() );
				if (
					!isset( $post_types_option[$post_type]['add_meta_box'] ) ||
					$post_types_option[$post_type]['add_meta_box']
				) {
					// only act on post etc. screens
					$screen = get_current_screen();
					if (
						!empty( $screen ) &&
						!empty( $screen->id ) &&
						( $screen->id == 'edit-' . $post_type )
						) {
							if ( $query->get( 'orderby' ) == self::GROUPS_READ ) {
								$result = true;
								break;
							}
						}
				}
			}
		}
		return $result;
	}

	/**
	 * Check if we should apply our posts_where filter. Used in it.
	 *
	 * @param WP_Query $query
	 * @return boolean
	 */
	private static function extend_for_filter_groups_read( &$query ) {
		$result = false;
		if ( is_admin() ) {
			// check if query is for a post type we handle
			$post_types = $query->get( 'post_type' );
			$post_types_option = Groups_Options::get_option( Groups_Post_Access::POST_TYPES, array() );
			if ( !is_array( $post_types ) ) {
				$post_types = array( $post_types );
			}
			foreach( $post_types as $post_type ) {
				if (
					!isset( $post_types_option[$post_type]['add_meta_box'] ) ||
					$post_types_option[$post_type]['add_meta_box']
				) {
					// only act on post etc. screens
					$screen = get_current_screen();
					if (
						!empty( $screen ) &&
						!empty( $screen->id ) &&
						( $screen->id == 'edit-' . $post_type )
					) {
						if (
							!empty( $_GET[Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ] ) &&
							is_array( $_GET[Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ] )
						) {
							$result = true;
							break;
						}
					}
				}
			}
		}
		return $result;
	}
}
Groups_Admin_Posts::init();
