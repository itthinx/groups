<?php
/**
 * groups-admin-capabilities.php
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

define( 'GROUPS_CAPABILITIES_PER_PAGE', 10 );
define( 'GROUPS_ADMIN_CAPABILITIES_NONCE_1', 'groups-cap-nonce-1');
define( 'GROUPS_ADMIN_CAPABILITIES_NONCE_2', 'groups-cap-nonce-2');
define( 'GROUPS_ADMIN_CAPABILITIES_ACTION_NONCE', 'groups-cap-action-nonce');
define( 'GROUPS_ADMIN_CAPABILITIES_FILTER_NONCE', 'groups-cap-filter-nonce' );

require_once( GROUPS_CORE_LIB . '/class-groups-pagination.php' );
require_once( GROUPS_ADMIN_LIB . '/groups-admin-capabilities-add.php');
require_once( GROUPS_ADMIN_LIB . '/groups-admin-capabilities-edit.php');
require_once( GROUPS_ADMIN_LIB . '/groups-admin-capabilities-remove.php');

/**
 * Manage capabilities: table of capabilities and add, edit, remove actions.
 */
function groups_admin_capabilities() {

	global $wpdb;

	$output = '';
	$today = date( 'Y-m-d', time() );

	if ( !current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
		wp_die( __( 'Access denied.', 'groups' ) );
	}

	//
	// handle actions
	//
	if ( isset( $_POST['action'] ) ) {
		//  handle action submit - do it
		switch( $_POST['action'] ) {
			case 'add' :
				if ( !( $capability_id = groups_admin_capabilities_add_submit() ) ) {
					return groups_admin_capabilities_add();
				} else {
					$capability = Groups_Capability::read( $capability_id );
					Groups_Admin::add_message( sprintf( __( 'The <em>%s</em> capability has been created.', 'groups' ), stripslashes( wp_filter_nohtml_kses( $capability->capability ) ) ) );
				}
				break;
			case 'edit' :
				if ( !( $capability_id = groups_admin_capabilities_edit_submit() ) ) {
					return groups_admin_capabilities_edit( $_POST['capability-id-field'] );
				} else {
					$capability = Groups_Capability::read( $capability_id );
					Groups_Admin::add_message( sprintf( __( 'The <em>%s</em> capability has been updated.', 'groups' ), stripslashes( wp_filter_nohtml_kses( $capability->capability ) ) ) );
				}
				break;
			case 'remove' :
				if ( $capability_id = groups_admin_capabilities_remove_submit() ) {
					Groups_Admin::add_message( __( 'The capability has been deleted.', 'groups' ) );
				}
				break;
			// bulk actions on groups: capabilities
			case 'groups-action' :
				if ( wp_verify_nonce( $_POST[GROUPS_ADMIN_GROUPS_ACTION_NONCE], 'admin' ) ) {
					$capability_ids = isset( $_POST['capability_ids'] ) ? $_POST['capability_ids'] : null;
					$bulk = isset( $_POST['bulk'] ) ? $_POST['bulk'] : null;
					if ( is_array( $capability_ids ) && ( $bulk !== null ) ) {
						foreach ( $capability_ids as $capability_id ) {
							$bulk_action = isset( $_POST['bulk-action'] ) ? $_POST['bulk-action'] : null;
							switch( $bulk_action ) {
								case 'remove' :
									if ( isset( $_POST['confirm'] ) ) {
										groups_admin_capabilities_bulk_remove_submit();
									} else {
										return groups_admin_capabilities_bulk_remove();
									}
									break;
							}
							break;
						}
					}
				}
				break;
		}
	} else if ( isset ( $_GET['action'] ) ) {
		// handle action request - show form
		switch( $_GET['action'] ) {
			case 'add' :
				return groups_admin_capabilities_add();
				break;
			case 'edit' :
				if ( isset( $_GET['capability_id'] ) ) {
					return groups_admin_capabilities_edit( $_GET['capability_id'] );
				}
				break;
			case 'remove' :
				if ( isset( $_GET['capability_id'] ) ) {
					return groups_admin_capabilities_remove( $_GET['capability_id'] );
				}
				break;
			case 'refresh' :
				if ( check_admin_referer( 'refresh' ) ) {
					$n = Groups_WordPress::refresh_capabilities();
					if ( $n > 0 ) {
						$output .= '<div class="updated fade"><p>' . sprintf( _n( 'One capability has been added.', '%d capabilities have been added.', $n, 'groups' ), $n ) . '</p></div>';
					} else {
						$output .= '<div class="updated fade"><p>' . __( 'No new capabilities have been found.', 'groups' ) .  '</p></div>';
					}
				} else {
					wp_die( __( 'A Duck!', 'groups' ) );
				}
				break;
		}
	}

	//
	// capabilities table
	//
	if (
		isset( $_POST['clear_filters'] ) ||
		isset( $_POST['capability_id'] ) ||
		isset( $_POST['capability'] )
	) {
		if ( !wp_verify_nonce( $_POST[GROUPS_ADMIN_CAPABILITIES_FILTER_NONCE], 'admin' ) ) {
			wp_die( __( 'Access denied.', 'groups' ) );
		}
	}

	// filters
	$capability_id = Groups_Options::get_user_option( 'capabilities_capability_id', null );
	$capability	= Groups_Options::get_user_option( 'capabilities_capability', null );

	if ( isset( $_POST['clear_filters'] ) ) {
		Groups_Options::delete_user_option( 'capabilities_capability_id' );
		Groups_Options::delete_user_option( 'capabilities_capability' );
		$capability_id = null;
		$capability = null;
	} else if ( isset( $_POST['submitted'] ) ) {
		// filter by name
		if ( !empty( $_POST['capability'] ) ) {
			$capability = $_POST['capability'];
			Groups_Options::update_user_option( 'capabilities_capability', $capability );
		}
		// filter by capability id
		if ( !empty( $_POST['capability_id'] ) ) {
			$capability_id = intval( $_POST['capability_id'] );
			Groups_Options::update_user_option( 'capabilities_capability_id', $capability_id );
		} else if ( isset( $_POST['capability_id'] ) ) { // empty && isset => '' => all
			$capability_id = null;
			Groups_Options::delete_user_option( 'capabilities_capability_id' );
		}
	}

	if ( isset( $_POST['row_count'] ) ) {
		if ( !wp_verify_nonce( $_POST[GROUPS_ADMIN_CAPABILITIES_NONCE_1], 'admin' ) ) {
			wp_die( __( 'Access denied.', 'groups' ) );
		}
	}

	if ( isset( $_POST['paged'] ) ) {
		if ( !wp_verify_nonce( $_POST[GROUPS_ADMIN_CAPABILITIES_NONCE_2], 'admin' ) ) {
			wp_die( __( 'Access denied.', 'groups' ) );
		}
	}

	$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	$current_url = remove_query_arg( 'paged', $current_url );
	$current_url = remove_query_arg( 'action', $current_url );
	$current_url = remove_query_arg( 'capability_id', $current_url );

	$capability_table = _groups_get_tablename( 'capability' );

	$output .=
		'<div class="manage-capabilities wrap">' .
		'<h1>' .
		__( 'Capabilities', 'groups' ) .
		// add capability
		sprintf(
			'<a title="%s" class="add page-title-action" href="%s">',
			esc_attr( __( 'Click to add a new capability', 'groups' ) ),
			esc_url( $current_url . '&action=add' )
		) .
		sprintf(
			'<img class="icon" alt="%s" src="%s" />',
			esc_attr( __( 'Add', 'groups' ) ),
			esc_url( GROUPS_PLUGIN_URL . 'images/add.png' )
		) .
		sprintf(
			'<span class="label">%s</span>',
			stripslashes( wp_filter_nohtml_kses( __( 'New Capability', 'groups' ) ) )
		) .
		'</a>' .
		// refresh capabilities
		sprintf(
			'<a title="%s" class="refresh page-title-action" href="%s">',
			esc_attr( __( 'Click to refresh capabilities', 'groups' ) ),
			esc_url( wp_nonce_url( $current_url . '&action=refresh', 'refresh' ) )
		) .
		sprintf(
			'<img class="icon" alt="%s" src="%s" />',
			esc_attr( __( 'Refresh', 'groups' ) ),
			esc_url( GROUPS_PLUGIN_URL . 'images/refresh.png' )
		) .
		sprintf(
			'<span class="label">%s</span>',
			stripslashes( wp_filter_nohtml_kses( __( 'Refresh', 'groups' ) ) )
		) .
		'</a>' .
		'</h1>';

	$output .= Groups_Admin::render_messages();

	$row_count = isset( $_POST['row_count'] ) ? intval( $_POST['row_count'] ) : 0;

	if ($row_count <= 0) {
		$row_count = Groups_Options::get_user_option( 'capabilities_per_page', GROUPS_CAPABILITIES_PER_PAGE );
	} else {
		Groups_Options::update_user_option('capabilities_per_page', $row_count );
	}
	$offset = isset( $_GET['offset'] ) ? intval( $_GET['offset'] ) : 0;
	if ( $offset < 0 ) {
		$offset = 0;
	}
	$paged = isset( $_REQUEST['paged'] ) ? intval( $_REQUEST['paged'] ) : 0;
	if ( $paged < 0 ) {
		$paged = 0;
	}

	$orderby = isset( $_GET['orderby'] ) ? $_GET['orderby'] : null;
	switch ( $orderby ) {
		case 'capability_id' :
		case 'capability' :
		case 'description' :
			break;
		default:
			$orderby = 'name';
	}

	$order = isset( $_GET['order'] ) ? $_GET['order'] : null;
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
	if ( $capability_id ) {
		$filters[] = " $capability_table.capability_id = %d ";
		$filter_params[] = $capability_id;
	}
	if ( $capability ) {
		$filters[] = " $capability_table.capability LIKE '%%%s%%' ";
		$filter_params[] = $capability;
	}

	if ( !empty( $filters ) ) {
		$filters = " WHERE " . implode( " AND ", $filters );
	} else {
		$filters = '';
	}

	$count_query = $wpdb->prepare( "SELECT COUNT(*) FROM $capability_table $filters", $filter_params );
	$count  = $wpdb->get_var( $count_query );
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
		"SELECT * FROM $capability_table
		$filters
		ORDER BY $orderby $order
		LIMIT $row_count OFFSET $offset",
		$filter_params
	);

	$results = $wpdb->get_results( $query, OBJECT );

	$column_display_names = array(
		'capability_id' => __( 'ID', 'groups' ),
		'capability'	=> __( 'Capability', 'groups' ),
		'description'   => __( 'Description', 'groups' )
	);

	$output .= '<div class="capabilities-overview">';

	$output .=
		'<div class="filters">' .
			'<form id="setfilters" action="" method="post">' .
				'<fieldset>' .
				'<legend>' . __( 'Filters', 'groups' ) . '</legend>' .
				'<label class="capability-id-filter">' .
				__( 'Capability ID', 'groups' ) . ' ' .
				'<input class="capability-id-filter" name="capability_id" type="text" value="' . esc_attr( $capability_id ) . '"/>' .
				'</label>' . ' ' .
				'<label class="capability-filter">' .
				__( 'Capability', 'groups' ) . ' ' .
				'<input class="capability-filter" name="capability" type="text" value="' . $capability . '"/>' .
				'</label>' . ' ' .
				wp_nonce_field( 'admin', GROUPS_ADMIN_CAPABILITIES_FILTER_NONCE, true, false ) .
				'<input class="button" type="submit" value="' . __( 'Apply', 'groups' ) . '"/>' . ' ' .
				'<input class="button" type="submit" name="clear_filters" value="' . __( 'Clear', 'groups' ) . '"/>' .
				'<input type="hidden" value="submitted" name="submitted"/>' .
				'</fieldset>' .
			'</form>' .
		'</div>';

	if ( $paginate ) {
		require_once( GROUPS_CORE_LIB . '/class-groups-pagination.php' );
		$pagination = new Groups_Pagination( $count, null, $row_count );
		$output .= '<form id="posts-filter" method="post" action="">';
		$output .= '<div>';
		$output .= wp_nonce_field( 'admin', GROUPS_ADMIN_CAPABILITIES_NONCE_2, true, false );
		$output .= '</div>';
		$output .= '<div class="tablenav top">';
		$output .= $pagination->pagination( 'top' );
		$output .= '</div>';
		$output .= '</form>';
	}

	$output .= '<div class="page-options right">';
	$output .= '<form id="setrowcount" action="" method="post">';
	$output .= '<div>';
	$output .= '<label for="row_count">' . __( 'Results per page', 'groups' ) . '</label>';
	$output .= '<input name="row_count" type="text" size="2" value="' . esc_attr( $row_count ) .'" />';
	$output .= wp_nonce_field( 'admin', GROUPS_ADMIN_CAPABILITIES_NONCE_1, true, false );
	$output .= '<input class="button" type="submit" value="' . __( 'Apply', 'groups' ) . '"/>';
	$output .= '</div>';
	$output .= '</form>';
	$output .= '</div>';

	$output .= '<form id="groups-action" method="post" action="">';

	$output .= '<div class="tablenav top">';
	$output .= '<div class="capabilities-bulk-container">';
	$output .= '<div class="alignleft actions">';
	$output .= '<select name="bulk-action">';
	$output .= '<option selected="selected" value="-1">' . esc_html( __( 'Bulk Actions', 'groups' ) ) . '</option>';
	$output .= '<option value="remove">' . esc_html( __( 'Remove', 'groups' ) ) . '</option>';
	$output .= '</select>';
	$output .= '<input class="button" type="submit" name="bulk" value="' . esc_attr( __( "Apply", 'groups' ) ) . '"/>';
	$output .= '</div>';
	$output .= '</div>';
	$output .= '</div>';
	$output .= wp_nonce_field( 'admin', GROUPS_ADMIN_GROUPS_ACTION_NONCE, true, false );
	$output .= '<input type="hidden" name="action" value="groups-action"/>';

	$output .= '<table id="" class="wp-list-table widefat fixed" cellspacing="0">';
	$output .= '<thead>';
	$output .= '<tr>';

	$output .= '<th id="cb" class="manage-column column-cb check-column" scope="col"><input type="checkbox"></th>';

	foreach ( $column_display_names as $key => $column_display_name ) {
		$options = array(
			'orderby' => $key,
			'order' => $switch_order
		);
		$class = $key;
		if ( !in_array($key, array( 'capabilities', 'edit', 'remove' ) ) ) {
			if ( strcmp( $key, $orderby ) == 0 ) {
				$lorder = strtolower( $order );
				$class = "$key manage-column sorted $lorder";
			} else {
				$class = "$key manage-column sortable";
			}
			$column_display_name =
				sprintf(
					'<a href="%s"><span>%s</span><span class="sorting-indicator"></span></a>',
					esc_url( add_query_arg( $options, $current_url ) ),
					esc_html( $column_display_name )
				);
		} else {
			$column_display_name = esc_html( $column_display_name );
		}
		$output .= sprintf(
			'<th scope="col" class="%s">%s</th>',
			esc_attr( $class ),
			$column_display_name
		);
	}

	$output .= '</tr>';
	$output .= '</thead>';
	$output .= '<tbody>';

	if ( count( $results ) > 0 ) {
		for ( $i = 0; $i < count( $results ); $i++ ) {
			$result = $results[$i];

			// Construct the "edit" URL.
			$edit_url = add_query_arg(
				array(
					'capability_id' => intval( $result->capability_id ),
					'action' => 'edit',
					'paged' => $paged
				),
				$current_url
			);
			// Construct the "delete" URL.
			$delete_url = add_query_arg(
				array(
					'capability_id' => intval( $result->capability_id ),
					'action' => 'remove',
					'paged' => $paged
				),
				$current_url
			);

			// Construct row actions for this group.
			$row_actions =
				'<div class="row-actions">' .
				'<span class="edit">' .
				'<a href="' . esc_url( $edit_url ) . '">' .
				'<img src="' . GROUPS_PLUGIN_URL . 'images/edit.png"/>' .
				__( 'Edit', 'groups' ) .
				'</a>';
			if ( $result->capability !== Groups_Post_Access::READ_POST_CAPABILITY ) {
				$row_actions .=
					' | '.
					'</span>' .
					'<span class="remove trash">' .
					'<a href="' . esc_url( $delete_url ) . '" class="submitdelete">' .
					'<img src="' . GROUPS_PLUGIN_URL . 'images/remove.png"/>' .
					__( 'Remove', 'groups' ) .
					'</a>' .
					'</span>';
			}
			$row_actions .= '</div>'; // .row-actions

			$output .= '<tr class="' . ( $i % 2 == 0 ? 'even' : 'odd' ) . '">';

			$output .= '<th class="check-column">';
			$output .= '<input type="checkbox" value="' . esc_attr( $result->capability_id ) . '" name="capability_ids[]"/>';
			$output .= '</th>';

			$output .= '<td class="capability-id">';
			$output .= $result->capability_id;
			$output .= '</td>';
			$output .= '<td class="capability">';
			$output .= sprintf( '<a href="%s">%s</a>', esc_url( $edit_url ), stripslashes( wp_filter_nohtml_kses( $result->capability ) ) );
			$output .= $row_actions;
			$output .= '</td>';
			$output .= '<td class="description">';
			$output .= stripslashes( wp_filter_nohtml_kses( $result->description ) );
			$output .= '</td>';

			$output .= '</tr>';
		}
	} else {
		$output .= '<tr><td colspan="3">' . __( 'There are no results.', 'groups' ) . '</td></tr>';
	}

	$output .= '</tbody>';
	$output .= '</table>';

	$output .= Groups_UIE::render_add_titles( '.capabilities-overview table td' );

	 $output .= '</form>'; // #groups-action

	if ( $paginate ) {
	  require_once( GROUPS_CORE_LIB . '/class-groups-pagination.php' );
		$pagination = new Groups_Pagination($count, null, $row_count);
		$output .= '<div class="tablenav bottom">';
		$output .= $pagination->pagination( 'bottom' );
		$output .= '</div>';
	}

	$output .= '</div>'; // .capabilities-overview
	$output .= '</div>'; // .manage-capabilities

	echo $output;
} // function groups_admin_capabilities()
