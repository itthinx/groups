<?php
/**
 * groups-admin-groups.php
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

// phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

// admin defines
define( 'GROUPS_GROUPS_PER_PAGE', 10 );
define( 'GROUPS_ADMIN_GROUPS_NONCE_1', 'groups-nonce-1');
define( 'GROUPS_ADMIN_GROUPS_NONCE_2', 'groups-nonce-2');
define( 'GROUPS_ADMIN_GROUPS_ACTION_NONCE', 'groups-action-nonce');
define( 'GROUPS_ADMIN_GROUPS_FILTER_NONCE', 'groups-filter-nonce' );

require_once GROUPS_CORE_LIB . '/class-groups-pagination.php';
require_once GROUPS_ADMIN_LIB . '/groups-admin-groups-add.php';
require_once GROUPS_ADMIN_LIB . '/groups-admin-groups-edit.php';
require_once GROUPS_ADMIN_LIB . '/groups-admin-groups-remove.php';

/**
 * Manage Groups: table of groups and add, edit, remove actions.
 */
function groups_admin_groups() {

	global $wpdb;

	$output = '';
	// $today = date( 'Y-m-d', time() ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date

	if ( !Groups_User::current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
		wp_die( esc_html__( 'Access denied.', 'groups' ) );
	}

	//
	// handle actions
	//
	if ( isset( $_POST['action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		//  handle action submit - do it
		switch ( groups_sanitize_post( 'action' ) ) {
			case 'add' :
				if ( !( $group_id = groups_admin_groups_add_submit() ) ) {
					return groups_admin_groups_add();
				} else {
					$group = Groups_Group::read( $group_id );
					Groups_Admin::add_message( sprintf(
						/* translators: group name */
						__( 'The <em>%s</em> group has been created.', 'groups' ),
						$group->name ? stripslashes( wp_filter_nohtml_kses( $group->name ) ) : ''
					) );
				}
				break;
			case 'edit' :
				if ( !( $group_id = groups_admin_groups_edit_submit() ) ) {
					return groups_admin_groups_edit( groups_sanitize_post( 'group-id-field' ) );
				} else {
					$group = Groups_Group::read( $group_id );
					Groups_Admin::add_message( sprintf(
						/* translators: group name */
						__( 'The <em>%s</em> group has been updated.', 'groups' ),
						$group->name ? stripslashes( wp_filter_nohtml_kses( $group->name ) ) : ''
					) );
				}
				break;
			case 'remove' :
				if ( $group_id = groups_admin_groups_remove_submit() ) {
					Groups_Admin::add_message( __( 'The group has been deleted.', 'groups' ) );
				}
				break;
			// bulk actions on groups: add capabilities, remove capabilities, remove groups
			case 'groups-action' :
				if ( groups_verify_post_nonce( GROUPS_ADMIN_GROUPS_ACTION_NONCE, 'admin' ) ) {
					$group_ids = groups_sanitize_post( 'group_ids' );
					$bulk_action = groups_sanitize_post( 'bulk-action' );
					if ( is_array( $group_ids ) && ( $bulk_action !== null ) ) {
						foreach ( $group_ids as $group_id ) {
							switch ( $bulk_action ) {
								case 'add-capability' :
									$capabilities_id = groups_sanitize_post( 'capability_id' );
									if ( is_array( $capabilities_id ) ) {
										foreach ( $capabilities_id as $capability_id ) {
											Groups_Group_Capability::create( array( 'group_id' => $group_id, 'capability_id' => $capability_id ) );
										}
									}
									break;
								case 'remove-capability' :
									$capabilities_id = groups_sanitize_post( 'capability_id' );
									if ( is_array( $capabilities_id ) ) {
										foreach ( $capabilities_id as $capability_id ) {
											Groups_Group_Capability::delete( $group_id, $capability_id );
										}
									}
									break;
								case 'remove-group' :
									$bulk_confirm = isset( $_POST['confirm'] ) ? true : false; // phpcs:ignore WordPress.Security.NonceVerification.Missing
									if ( $bulk_confirm ) {
										groups_admin_groups_bulk_remove_submit();
									} else {
										return groups_admin_groups_bulk_remove();
									}
									break;
								default:
									if ( has_action( 'groups_admin_groups_handle_bulk_action' ) ) {
										/**
										 * Handle the requested bulk action.
										 *
										 * @param string $bulk_action the requested bulk action
										 * @param string|int $group_id the requested group ID
										 */
										do_action( 'groups_admin_groups_handle_bulk_action', $bulk_action, $group_id );
									}
							}
						}
					}
				}
				break;
			default:
				if ( has_filter( 'groups_admin_groups_handle_action_submit' ) ) {
					/**
					 * Handle a requested action after $_POST.
					 *
					 * @since 3.7.0
					 *
					 * @param boolean $handle whether to handle the posted action
					 * @param string $action the requested action
					 *
					 * @return boolean whether the posted data was accepted and action was taken
					 */
					if ( apply_filters( 'groups_admin_groups_handle_action_submit', false, groups_sanitize_post( 'action' ) ) ) {
						/**
						 * Fires after the posted data for an action was accepted.
						 *
						 * Should produce output to provide feedback to the user.
						 *
						 * @since 3.7.0
						 *
						 * @param string $action the requested action
						 */
						do_action( 'groups_admin_groups_handle_action_confirm', groups_sanitize_post( 'action' ) );
					} else {
						/**
						 * Fires after the posted data for an action was rejected.
						 *
						 * Should produce output to provide feedback to the user.
						 *
						 * @since 3.7.0
						 *
						 * @param string $action the requested action
						 */
						do_action( 'groups_admin_groups_handle_action_reject', groups_sanitize_post( 'action' ) );
						return;
					}
				}
		}
	} else if ( isset( $_GET['action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
		// handle action request - show form
		switch ( groups_sanitize_get( 'action' ) ) {
			case 'add' :
				return groups_admin_groups_add();
				break;
			case 'edit' :
				if ( isset( $_GET['group_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
					return groups_admin_groups_edit( groups_sanitize_get( 'group_id' ) );
				}
				break;
			case 'remove' :
				if ( isset( $_GET['group_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
					return groups_admin_groups_remove( groups_sanitize_get( 'group_id' ) );
				}
				break;
			default:
				if ( isset( $_GET['group_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
					if ( has_action( 'groups_admin_groups_handle_action' ) ) {
						/**
						 * Handle the requested action and produce the corresponding output.
						 *
						 * @param string $action the requested action
						 * @param string|int $group_id the requested group ID
						 */
						do_action( 'groups_admin_groups_handle_action', groups_sanitize_get( 'action' ), groups_sanitize_get( 'group_id' ) );
						return;
					}
				}
		}
	}

	//
	// group table
	//
	if (
		isset( $_POST['clear_filters'] ) || // phpcs:ignore WordPress.Security.NonceVerification.Missing
		isset( $_POST['group_id'] ) || // phpcs:ignore WordPress.Security.NonceVerification.Missing
		isset( $_POST['group_name'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
	) {
		if ( !groups_verify_post_nonce( GROUPS_ADMIN_GROUPS_FILTER_NONCE, 'admin' ) ) {
			wp_die( esc_html__( 'Access denied.', 'groups' ) );
		}
	}

	// filters
	$group_id   = Groups_Options::get_user_option( 'groups_group_id', null );
	$group_name = Groups_Options::get_user_option( 'groups_group_name', null );

	if ( isset( $_POST['clear_filters'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		Groups_Options::delete_user_option( 'groups_group_id' );
		Groups_Options::delete_user_option( 'groups_group_name' );
		$group_id = null;
		$group_name = null;
	} else if ( isset( $_POST['submitted'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		// filter by name
		if ( !empty( $_POST['group_name'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$group_name = groups_sanitize_post( 'group_name' );
			Groups_Options::update_user_option( 'groups_group_name', $group_name );
		}
		// filter by group id
		if ( !empty( $_POST['group_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$group_id = intval( groups_sanitize_post( 'group_id' ) );
			Groups_Options::update_user_option( 'groups_group_id', $group_id );
		} else if ( isset( $_POST['group_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			// empty && isset => '' => all
			$group_id = null;
			Groups_Options::delete_user_option( 'groups_group_id' );
		}
	}

	if ( isset( $_POST['row_count'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( !groups_verify_post_nonce( GROUPS_ADMIN_GROUPS_NONCE_1, 'admin' ) ) {
			wp_die( esc_html__( 'Access denied.', 'groups' ) );
		}
	}

	if ( isset( $_POST['paged'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( !groups_verify_post_nonce( GROUPS_ADMIN_GROUPS_NONCE_2, 'admin' ) ) {
			wp_die( esc_html__( 'Access denied.', 'groups' ) );
		}
	}

	$current_url = groups_get_current_url();
	$current_url = remove_query_arg( 'paged', $current_url );
	$current_url = remove_query_arg( 'action', $current_url );
	$current_url = remove_query_arg( 'group_id', $current_url );

	$group_table = _groups_get_tablename( 'group' );

	$output .=
		'<div class="manage-groups wrap">' .
		'<h1>' .
		_x( 'Groups', 'page-title', 'groups' ) .
		sprintf(
			'<a title="%s" class="add page-title-action" href="%s">',
			esc_attr__( 'Click to add a new group', 'groups' ),
			esc_url( $current_url . '&action=add' )
		) .
		sprintf(
			'<img class="icon" alt="%s" src="%s" />',
			esc_attr__( 'Add', 'groups' ),
			esc_url( GROUPS_PLUGIN_URL . 'images/add.png' )
		) .
		sprintf(
			'<span class="label">%s</span>',
			esc_html__( 'New Group', 'groups' )
		) .
		'</a>' .
		'</h1>';

	$output .= Groups_Admin::render_messages();

	$row_count = intval( groups_sanitize_post( 'row_count' ) ?? 0 );

	if ($row_count <= 0) {
		$row_count = Groups_Options::get_user_option( 'groups_per_page', GROUPS_GROUPS_PER_PAGE );
	} else {
		Groups_Options::update_user_option('groups_per_page', $row_count );
	}
	$offset = intval( groups_sanitize_get( 'offset' ) ?? 0 );
	if ( $offset < 0 ) {
		$offset = 0;
	}
	$paged = intval( groups_sanitize_request( 'paged' ) ?? 0 );
	if ( $paged < 0 ) {
		$paged = 0;
	}

	$orderby = groups_sanitize_get( 'orderby' );
	switch ( $orderby ) {
		case 'group_id' :
		case 'name' :
		case 'description' :
			break;
		default:
			$orderby = 'name';
	}

	$order = groups_sanitize_get( 'order' );
	switch ( $order ) {
		case 'asc' :
		case 'ASC' :
			$switch_order = 'DESC';
			break;
		case 'desc' :
		case 'DESC' :
			$switch_order = 'ASC';
			break;
		default:
			$order = 'ASC';
			$switch_order = 'DESC';
	}

	$filters = array( " 1=%d " );
	$filter_params = array( 1 );
	if ( $group_id ) {
		$filters[] = " $group_table.group_id = %d ";
		$filter_params[] = $group_id;
	}
	if ( $group_name ) {
		$filters[] = " $group_table.name LIKE %s ";
		$filter_params[] = '%' . $wpdb->esc_like( $group_name ) . '%';
	}

	if ( !empty( $filters ) ) { // @phpstan-ignore empty.variable
		$filters = " WHERE " . implode( " AND ", $filters );
	} else {
		$filters = '';
	}

	$count_query = $wpdb->prepare( "SELECT COUNT(*) FROM $group_table $filters", $filter_params ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
	$count  = $wpdb->get_var( $count_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	if ( $count > $row_count ) {
		$paginate = true;
	} else {
		$paginate = false;
	}
	$pages = ceil ( $count / $row_count );
	if ( $paged > $pages ) {
		$paged = $pages;
	}
	if ( $paged != 0 ) {
		$offset = ( $paged - 1 ) * $row_count;
	}

	$query = $wpdb->prepare(
		// nosemgrep: audit.php.wp.security.sqli.input-in-sinks
		"SELECT * FROM $group_table $filters ORDER BY $orderby $order LIMIT $row_count OFFSET $offset", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$filter_params
	);

	/**
	 * Allows to modify the query for the groups table.
	 *
	 * @since 3.7.0
	 *
	 * @param string $query the query
	 *
	 * @return string
	 */
	$query = apply_filters( 'groups_admin_groups_query', $query );

	// nosemgrep: audit.php.wp.security.sqli.input-in-sinks
	$results = $wpdb->get_results( $query, OBJECT ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	/**
	 * Allows to modify the results for the groups table.
	 *
	 * @since 3.7.0
	 *
	 * @param object[] $results result to show
	 *
	 * @return object[]
	 */
	$results = apply_filters( 'groups_admin_groups_results', $results );

	$columns = array(
		'group_id'     => array( 'label' => __( 'ID', 'groups' ), 'sortable' => true ),
		'name'         => array( 'label' => __( 'Group', 'groups' ), 'sortable' => true ),
		'description'  => array( 'label' => __( 'Description', 'groups' ), 'sortable' => true ),
		'capabilities' => array( 'label' => __( 'Capabilities', 'groups' ), 'sortable' => false )
	);

	/**
	 * Allows to modify the columns of the groups table.
	 *
	 * @since 3.7.0
	 *
	 * @param array $columns maps column keys to column details; keys must be alphanumeric allowing also for underscores '_' and dashes '-', columns with invalid keys are removed; 'checkbox' is a reserved column key and must not be used
	 *
	 * @return array
	 */
	$columns = apply_filters( 'groups_admin_groups_columns', $columns );
	unset( $columns['checkbox'] );
	foreach ( $columns as $key => $column ) {
		if ( preg_replace( '/[^a-zA-Z0-9_-]/', '', $key ) !== $key ) {
			unset( $columns[$key] );
		}
	}

	$column_count = count( $columns ) + 1;

	$output .= '<div class="groups-overview">';

	$filters_html = '<div class="filters">';
	$filters_html .= '<form id="setfilters" action="" method="post">';
	$filters_html .= '<fieldset>';
	$filters_html .= '<legend>' . esc_html__( 'Filters', 'groups' ) . '</legend>';
	$filters_html .= '<label class="group-id-filter">' . esc_html__( 'Group ID', 'groups' ) . ' ';
	$filters_html .= '<input class="group-id-filter" name="group_id" type="text" value="' . esc_attr( $group_id ) . '"/>';
	$filters_html .= '</label>' . ' ';
	$filters_html .= '<label class="group-name-filter">' . esc_html__( 'Group Name', 'groups' ) . ' ';
	$filters_html .= '<input class="group-name-filter" name="group_name" type="text" value="' . esc_attr( stripslashes( $group_name !== null ? $group_name : '' ) ) . '"/>';
	$filters_html .= '</label>' . ' ';
	/**
	 * Allows to add markup after the standard filter fields of the groups table.
	 *
	 * @since 3.7.0
	 *
	 * @param string $markup additional markup
	 *
	 * @return string
	 */
	$filters_html .= apply_filters( 'groups_admin_groups_filters_fields_epilogue', '' );
	$filters_html .= wp_nonce_field( 'admin', GROUPS_ADMIN_GROUPS_FILTER_NONCE, true, false );
	$filters_html .= '<input class="button" type="submit" value="' . esc_attr__( 'Apply', 'groups' ) . '"/>' . ' ';
	$filters_html .= '<input class="button" type="submit" name="clear_filters" value="' . esc_attr__( 'Clear', 'groups' ) . '"/>';
	$filters_html .= '<input type="hidden" value="submitted" name="submitted"/>';
	$filters_html .= '</fieldset>';
	$filters_html .= '</form>';
	$filters_html .= '</div>'; // .filters

	/**
	 * Allows to process the HTML of the filters section of the groups table.
	 *
	 * @since 3.7.0
	 *
	 * @param string $filters_html markup
	 *
	 * @return string
	 */
	$output .= apply_filters( 'groups_admin_groups_filters_html', $filters_html );

	if ( $paginate ) {
		require_once GROUPS_CORE_LIB . '/class-groups-pagination.php';
		$pagination = new Groups_Pagination( $count, null, $row_count );
		$output .= '<form id="posts-filter" method="post" action="">';
		$output .= '<div>';
		$output .= wp_nonce_field( 'admin', GROUPS_ADMIN_GROUPS_NONCE_2, true, false );
		$output .= '</div>';
		$output .= '<div class="tablenav top">';
		$output .= $pagination->pagination( 'top' );
		$output .= '</div>';
		$output .= '</form>';
	}

	$output .= '<div class="page-options right">';
	$output .= '<form id="setrowcount" action="" method="post">';
	$output .= '<div>';
	$output .= '<label for="row_count">' . esc_html__( 'Results per page', 'groups' ) . '</label>';
	$output .= '<input name="row_count" type="text" size="2" value="' . esc_attr( $row_count ) .'" />';
	$output .= wp_nonce_field( 'admin', GROUPS_ADMIN_GROUPS_NONCE_1, true, false );
	$output .= '<input class="button" type="submit" value="' . esc_attr__( 'Apply', 'groups' ) . '"/>';
	$output .= '</div>';
	$output .= '</form>';
	$output .= '</div>';

	$capability_table = _groups_get_tablename( "capability" );
	// $group_capability_table = _groups_get_tablename( "group_capability" );

	// capabilities select
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$capabilities = $wpdb->get_results( "SELECT * FROM $capability_table ORDER BY capability" );
	$capabilities_select = sprintf(
		'<select class="select capability" name="capability_id[]" multiple="multiple" placeholder="%s" data-placeholder="%s">',
		esc_attr__( 'Capabilities &hellip;', 'groups' ),
		esc_attr__( 'Capabilities &hellip;', 'groups' )
	);
	foreach ( $capabilities as $capability ) {
		$capabilities_select .= sprintf(
			'<option value="%s">%s</option>',
			esc_attr( $capability->capability_id ),
			$capability->capability ? stripslashes( wp_filter_nohtml_kses( $capability->capability ) ) : ''
		);
	}
	$capabilities_select .= '</select>';
	$capabilities_select .= Groups_UIE::render_select( '.select.capability' );

	$output .= '<form id="groups-action" method="post" action="">';

	$output .= '<div class="tablenav top">';

	$bulk_html = '<div class="groups-bulk-container">';
	$bulk_html .= '<div class="capabilities-select-container">';
	$bulk_html .= $capabilities_select;
	$bulk_html .= wp_nonce_field( 'admin', GROUPS_ADMIN_GROUPS_ACTION_NONCE, true, false );
	$bulk_html .= '</div>';
	$bulk_html .= '<select class="bulk-action" name="bulk-action">';
	$bulk_html .= '<option selected="selected" value="-1">' . esc_html__( 'Bulk Actions', 'groups' ) . '</option>';
	$bulk_html .= '<option value="remove-group">' . esc_html__( 'Remove group', 'groups' ) . '</option>';
	$bulk_html .= '<option value="add-capability">' . esc_html__( 'Add capability', 'groups' ) . '</option>';
	$bulk_html .= '<option value="remove-capability">' . esc_html__( 'Remove capability', 'groups' ) . '</option>';
	$bulk_html .= '</select>';
	/**
	 * Allows to add markup after the standard bulk actions fields of the groups table.
	 *
	 * @since 3.7.0
	 *
	 * @param string $markup additional markup
	 *
	 * @return string
	 */
	$filters_html .= apply_filters( 'groups_admin_groups_bulk_actions_fields_epilogue', '' );
	$bulk_html .= sprintf( '<input class="button" type="submit" name="bulk" value="%s" />', esc_attr__( 'Apply', 'groups' ) );
	$bulk_html .= '<input type="hidden" name="action" value="groups-action"/>';
	$bulk_html .= '</div>';
	$bulk_html .= '</div>';

	/**
	 * Allows to process the HTML of the bulk actions section of the groups table.
	 *
	 * @since 3.7.0
	 *
	 * @param string $bulk_html markup
	 *
	 * @return string
	 */
	$output .= apply_filters( 'groups_admin_groups_bulk_actions_html', $bulk_html );

	$output .= '<table id="" class="wp-list-table widefat fixed" cellspacing="0">';
	$output .= '<thead>';
	$output .= '<tr>';

	$output .= '<th id="cb" class="manage-column column-cb check-column" scope="col"><input type="checkbox"></th>';

	foreach ( $columns as $key => $column ) {
		$options = array(
			'orderby' => $key,
			'order'   => $switch_order
		);
		$class = $key;
		if ( isset( $column['sortable'] ) && $column['sortable'] ) {
			if ( strcmp( $key, $orderby ) == 0 ) {
				$lorder = strtolower( $order );
				$class = "$key manage-column sorted $lorder";
			} else {
				$class = "$key manage-column sortable";
			}
			$heading =
				sprintf(
					'<a href="%s"><span>%s</span><span class="sorting-indicator"></span></a>',
					esc_url( add_query_arg( $options, $current_url ) ),
					esc_html( $column['label'] )
				);
		} else {
			$heading = esc_html( $column['label'] );
		}
		$output .= sprintf(
			'<th scope="col" class="%s">%s</th>',
			esc_attr( $class ),
			$heading
		);
	}

	$output .= '</tr>';
	$output .= '</thead>';
	$output .= '<tbody>';

	if ( count( $results ) > 0 ) {
		for ( $i = 0; $i < count( $results ); $i++ ) {

			$result = $results[$i];

			/**
			 * @var Groups_Group
			 */
			$group = new Groups_Group( $result->group_id );

			// Construct the "edit" URL.
			$edit_url = add_query_arg(
				array(
					'group_id' => intval( $result->group_id ),
					'action' => 'edit',
					'paged' => $paged
				),
				$current_url
			);

			// Construct the "delete" URL.
			$delete_url = add_query_arg(
				array(
					'group_id' => intval( $result->group_id ),
					'action' => 'remove',
					'paged' => $paged
				),
				$current_url
			);

			$users_url = add_query_arg(
				array( 'filter_group_ids[0]' => intval( $result->group_id ) ),
				admin_url( 'users.php' )
			);

			// Construct row actions for this group.
			$row_actions = array(
				'edit' => sprintf( '<a href="%s"><img src="%s"/>&nbsp;%s</a>', esc_url( $edit_url ), esc_url( GROUPS_PLUGIN_URL . 'images/edit.png' ), esc_html__( 'Edit', 'groups' ) )
			);
			if ( $result->name !== Groups_Registered::REGISTERED_GROUP_NAME ) {
				$row_actions['remove trash'] = sprintf( '<a href="%s" class="submitdelete"><img src="%s"/>&nbsp;%s</a>', esc_url( $delete_url ), esc_url( GROUPS_PLUGIN_URL . 'images/remove.png' ), esc_html__( 'Remove', 'groups' ) );
			}

			/**
			 * Allows to alter the row actions for a group in the groups table.
			 *
			 * @since 3.7.0
			 *
			 * @param array $row_actions row actions as HTML
			 * @param int $group_id ID of the group
			 *
			 * @return array
			 */
			$row_actions = apply_filters( 'groups_admin_groups_row_actions', $row_actions, intval( $result->group_id ) );

			$n = 1;
			$row_actions_html = '<div class="row-actions">';
			foreach ( $row_actions as $row_action_key => $row_action ) {
				$row_actions_html .= sprintf( '<span class="%s">', esc_attr( $row_action_key ) );
				$row_actions_html .= $row_action;
				$row_actions_html .= '</span>';
				if ( $n < count( $row_actions ) ) {
					$row_actions_html .= '&emsp;|&emsp;';
				}
				$n++;
			}
			$row_actions_html .= '</div>'; // .row-actions

			/**
			 * Allows to process the HTML of the row actions for a group in the groups table.
			 *
			 * @since 3.7.0
			 *
			 * @param string $row_actions_html markup
			 * @param int $group_id ID of the group
			 *
			 * @return string
			 */
			$row_actions_html = apply_filters( 'groups_admin_groups_row_actions_html', $row_actions_html, intval( $result->group_id ) );

			$output .= '<tr class="' . ( $i % 2 == 0 ? 'even' : 'odd' ) . '">';

			$columns = array( 'checkbox' => array() ) + $columns;
			foreach ( $columns as $key => $column ) {
				switch ( $key ) {
					case 'checkbox':
						$output .= '<th class="check-column">';
						$output .= '<input type="checkbox" value="' . esc_attr( $result->group_id ) . '" name="group_ids[]"/>';
						$output .= '</th>';
						break;
					case 'group_id':
						$output .= '<td class="group-id">';
						$output .= $result->group_id;
						$output .= '</td>';
						break;
					case 'name':
						$output .= '<td class="group-name">';
						$output .= sprintf(
							'<a href="%s">%s</a>',
							esc_url( $edit_url ),
							$result->name ? stripslashes( wp_filter_nohtml_kses( $result->name ) ) : ''
						);
						$output .= ' ';
						$user_ids = $group->get_user_ids();
						$user_count = is_array( $user_ids ) ? count( $user_ids ) : 0; // guard against null when there are no users
						$output .= sprintf(
							'(<a href="%s">%s</a>)',
							esc_url( $users_url ),
							$user_count
						);
						$output .= $row_actions_html;
						$output .= '</td>';
						break;
					case 'description':
						$output .= '<td class="group-description">';
						$output .= $result->description ? stripslashes( wp_filter_nohtml_kses( $result->description ) ) : '';
						$output .= '</td>';
						break;
					case 'capabilities':
						$output .= '<td class="capabilities">';
						$group_capabilities = $group->get_capabilities();
						$group_capabilities_deep = $group->get_capabilities_deep();
						usort( $group_capabilities_deep, array( 'Groups_Utility', 'cmp' ) );
						if ( count( $group_capabilities_deep ) > 0 ) {
							$output .= '<ul>';
							foreach ( $group_capabilities_deep as $group_capability ) {
								$output .= '<li>';
								$class = '';
								if ( empty( $group_capabilities ) || !in_array( $group_capability, $group_capabilities ) ) {
									$class = 'inherited';
								}
								$output .= sprintf( '<span class="%s">', $class );
								$output .= stripslashes( wp_filter_nohtml_kses( $group_capability->get_capability() ) );
								$output .= '</span>';
								$output .= '</li>';
							}
							$output .= '</ul>';
						} else {
							$output .= esc_html__( 'This group has no capabilities.', 'groups' );
						}
						$output .= '</td>';
						break;
					default:
						$output .= sprintf( '<td class="custom-column %s">', esc_attr( $key ) );
						/**
						 * Provide the row's output for the column identified by $key for the group given by its ID.
						 *
						 * @param string $content column content
						 * @param string $key the column key
						 * @param int $group_id the group's ID
						 *
						 * @return string content HTML
						 */
						$output .= apply_filters( 'groups_admin_groups_column_content', '', $key, $group->get_group_id() );
						$output .= '</td>'; // .custom-column ...
				}
			}
			$output .= '</tr>';
		}
	} else {
		$output .= '<tr>';
		$output .= sprintf( '<td colspan="%d">', esc_attr( $column_count ) );
		$output .= esc_html__( 'There are no results.', 'groups' );
		$output .= '</td>';
		$output .= '</tr>';
	}

	$output .= '</tbody>';
	$output .= '</table>';

	$output .= Groups_UIE::render_add_titles( '.groups-overview table td' );

	$output .= '</form>'; // #groups-action

	if ( $paginate ) {
		require_once GROUPS_CORE_LIB . '/class-groups-pagination.php';
		$pagination = new Groups_Pagination($count, null, $row_count);
		$output .= '<div class="tablenav bottom">';
		$output .= $pagination->pagination( 'bottom' );
		$output .= '</div>';
	}

	$output .= '</div>'; // .groups-overview
	$output .= '</div>'; // .manage-groups

	echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
} // function groups_admin_groups()
