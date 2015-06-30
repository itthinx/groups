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

// admin defines
define( 'GROUPS_GROUPS_PER_PAGE', 10 );
define( 'GROUPS_ADMIN_GROUPS_NONCE_1', 'groups-nonce-1');
define( 'GROUPS_ADMIN_GROUPS_NONCE_2', 'groups-nonce-2');
define( 'GROUPS_ADMIN_GROUPS_ACTION_NONCE', 'groups-action-nonce');
define( 'GROUPS_ADMIN_GROUPS_FILTER_NONCE', 'groups-filter-nonce' );

require_once( GROUPS_CORE_LIB . '/class-groups-pagination.php' );
require_once( GROUPS_ADMIN_LIB . '/groups-admin-groups-add.php');
require_once( GROUPS_ADMIN_LIB . '/groups-admin-groups-edit.php');
require_once( GROUPS_ADMIN_LIB . '/groups-admin-groups-remove.php');

/**
 * Manage Groups: table of groups and add, edit, remove actions.
 */
function groups_admin_groups() {

	global $wpdb;

	$output = '';
	$today = date( 'Y-m-d', time() );

	if ( !current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
		wp_die( __( 'Access denied.', GROUPS_PLUGIN_DOMAIN ) );
	}

	//
	// handle actions
	//
	if ( isset( $_POST['action'] ) ) {
		//  handle action submit - do it
		switch( $_POST['action'] ) {
			case 'add' :
				if ( !( $group_id = groups_admin_groups_add_submit() ) ) {
					return groups_admin_groups_add();
				} else {
					$group = Groups_Group::read( $group_id );
					Groups_Admin::add_message( sprintf( __( "The <em>%s</em> group has been created.", GROUPS_PLUGIN_DOMAIN ), stripslashes( wp_filter_nohtml_kses( $group->name ) ) ) );
				}
				break;
			case 'edit' :
				if ( !( $group_id = groups_admin_groups_edit_submit() ) ) {
					return groups_admin_groups_edit( $_POST['group-id-field'] );
				} else {
					$group = Groups_Group::read( $group_id );
					Groups_Admin::add_message( sprintf( __( 'The <em>%s</em> group has been updated.', GROUPS_PLUGIN_DOMAIN ), stripslashes( wp_filter_nohtml_kses( $group->name ) ) ) );
				}
				break;
			case 'remove' :
				if ( $group_id = groups_admin_groups_remove_submit() ) {
					Groups_Admin::add_message( __( 'The group has been deleted.', GROUPS_PLUGIN_DOMAIN ) );
				}
				break;
			// bulk actions on groups: add capabilities, remove capabilities, remove groups
			case 'groups-action' :
				if ( wp_verify_nonce( $_POST[GROUPS_ADMIN_GROUPS_ACTION_NONCE], 'admin' ) ) {
					$group_ids = isset( $_POST['group_ids'] ) ? $_POST['group_ids'] : null;
					$bulk_action = null;
					if ( isset( $_POST['bulk'] ) ) {
						$bulk_action = $_POST['bulk-action'];
					}
					if ( is_array( $group_ids ) && ( $bulk_action !== null ) ) {
						foreach ( $group_ids as $group_id ) {
							switch ( $bulk_action ) {
								case 'add-capability' :
									$capabilities_id = isset( $_POST['capability_id'] ) ? $_POST['capability_id'] : null;
									if ( $capabilities_id !== null ) {
										foreach ( $capabilities_id as $capability_id ) {
											Groups_Group_Capability::create( array( 'group_id' => $group_id, 'capability_id' => $capability_id ) );
										}
									}
									break;
								case 'remove-capability' :
									$capabilities_id = isset( $_POST['capability_id'] ) ? $_POST['capability_id'] : null;
									if ( $capabilities_id !== null ) {
										foreach ( $capabilities_id as $capability_id ) {
											Groups_Group_Capability::delete( $group_id, $capability_id );
										}
									}
									break;
								case 'remove-group' :
									$bulk_confirm = isset( $_POST['confirm'] ) ? true : false;
									if ( $bulk_confirm ) {
										groups_admin_groups_bulk_remove_submit();
									} else {
										return groups_admin_groups_bulk_remove();
									}
									break;
							}
						}
					}
				}
				break;
		}
	} else if ( isset ( $_GET['action'] ) ) {
		// handle action request - show form
		switch( $_GET['action'] ) {
			case 'add' :
				return groups_admin_groups_add();
				break;
			case 'edit' :
				if ( isset( $_GET['group_id'] ) ) {
					return groups_admin_groups_edit( $_GET['group_id'] );
				}
				break;
			case 'remove' :
				if ( isset( $_GET['group_id'] ) ) {
					return groups_admin_groups_remove( $_GET['group_id'] );
				}
				break;
		}
	}

	//
	// group table
	//
	if (
		isset( $_POST['clear_filters'] ) ||
		isset( $_POST['group_id'] ) ||
		isset( $_POST['group_name'] )
	) {
		if ( !wp_verify_nonce( $_POST[GROUPS_ADMIN_GROUPS_FILTER_NONCE], 'admin' ) ) {
			wp_die( __( 'Access denied.', GROUPS_PLUGIN_DOMAIN ) );
		}
	}

	// filters
	$group_id		 = Groups_Options::get_user_option( 'groups_group_id', null );
	$group_name	   = Groups_Options::get_user_option( 'groups_group_name', null );

	if ( isset( $_POST['clear_filters'] ) ) {
		Groups_Options::delete_user_option( 'groups_group_id' );
		Groups_Options::delete_user_option( 'groups_group_name' );
		$group_id = null;
		$group_name = null;
	} else if ( isset( $_POST['submitted'] ) ) {
		// filter by name
		if ( !empty( $_POST['group_name'] ) ) {
			$group_name = $_POST['group_name'];
			Groups_Options::update_user_option( 'groups_group_name', $group_name );
		}
		// filter by group id
		if ( !empty( $_POST['group_id'] ) ) {
			$group_id = intval( $_POST['group_id'] );
			Groups_Options::update_user_option( 'groups_group_id', $group_id );
		} else if ( isset( $_POST['group_id'] ) ) { // empty && isset => '' => all
			$group_id = null;
			Groups_Options::delete_user_option( 'groups_group_id' );
		}
	}

	if ( isset( $_POST['row_count'] ) ) {
		if ( !wp_verify_nonce( $_POST[GROUPS_ADMIN_GROUPS_NONCE_1], 'admin' ) ) {
			wp_die( __( 'Access denied.', GROUPS_PLUGIN_DOMAIN ) );
		}
	}

	if ( isset( $_POST['paged'] ) ) {
		if ( !wp_verify_nonce( $_POST[GROUPS_ADMIN_GROUPS_NONCE_2], 'admin' ) ) {
			wp_die( __( 'Access denied.', GROUPS_PLUGIN_DOMAIN ) );
		}
	}

	$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	$current_url = remove_query_arg( 'paged', $current_url );
	$current_url = remove_query_arg( 'action', $current_url );
	$current_url = remove_query_arg( 'group_id', $current_url );

	$group_table = _groups_get_tablename( 'group' );

	$output .=
		'<div class="manage-groups">' .
		'<div>' .
		'<h2>' .
		_x( 'Groups', 'page-title', GROUPS_PLUGIN_DOMAIN ) .
		'</h2>' .
		'</div>';

	$output .= Groups_Admin::render_messages();

	$output .=
		'<div class="manage">' .
		"<a title='" . __( 'Click to add a new group', GROUPS_PLUGIN_DOMAIN ) . "' class='add button' href='" . esc_url( $current_url ) . "&action=add'><img class='icon' alt='" . __( 'Add', GROUPS_PLUGIN_DOMAIN) . "' src='". GROUPS_PLUGIN_URL ."images/add.png'/><span class='label'>" . __( 'New Group', GROUPS_PLUGIN_DOMAIN) . "</span></a>" .
		'</div>';

	$row_count = isset( $_POST['row_count'] ) ? intval( $_POST['row_count'] ) : 0;

	if ($row_count <= 0) {
		$row_count = Groups_Options::get_user_option( 'groups_per_page', GROUPS_GROUPS_PER_PAGE );
	} else {
		Groups_Options::update_user_option('groups_per_page', $row_count );
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
		case 'group_id' :
		case 'name' :
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
	if ( $group_id ) {
		$filters[] = " $group_table.group_id = %d ";
		$filter_params[] = $group_id;
	}
	if ( $group_name ) {
		$filters[] = " $group_table.name LIKE '%%%s%%' ";
		$filter_params[] = $group_name;
	}

	if ( !empty( $filters ) ) {
		$filters = " WHERE " . implode( " AND ", $filters );
	} else {
		$filters = '';
	}

	$count_query = $wpdb->prepare( "SELECT COUNT(*) FROM $group_table $filters", $filter_params );
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
		"SELECT * FROM $group_table
		$filters
		ORDER BY $orderby $order
		LIMIT $row_count OFFSET $offset",
		$filter_params
	);

	$results = $wpdb->get_results( $query, OBJECT );

	$column_display_names = array(
		'group_id'	 => __( 'Id', GROUPS_PLUGIN_DOMAIN ),
		'name'		 => __( 'Group', GROUPS_PLUGIN_DOMAIN ),
		'description'  => __( 'Description', GROUPS_PLUGIN_DOMAIN ),
		'capabilities' => __( 'Capabilities', GROUPS_PLUGIN_DOMAIN ),
		'edit'		 => __( 'Edit', GROUPS_PLUGIN_DOMAIN ),
		'remove'	   => __( 'Remove', GROUPS_PLUGIN_DOMAIN )
	);

	$output .= '<div class="groups-overview">';

	$output .=
		'<div class="filters">' .
			'<label class="description" for="setfilters">' . __( 'Filters', GROUPS_PLUGIN_DOMAIN ) . '</label>' .
			'<form id="setfilters" action="" method="post">' .
				'<p>' .
				'<label class="group-id-filter" for="group_id">' . __( 'Group Id', GROUPS_PLUGIN_DOMAIN ) . '</label>' .
				'<input class="group-id-filter" name="group_id" type="text" value="' . esc_attr( $group_id ) . '"/>' .
				'<label class="group-name-filter" for="group_name">' . __( 'Group Name', GROUPS_PLUGIN_DOMAIN ) . '</label>' .
				'<input class="group-name-filter" name="group_name" type="text" value="' . $group_name . '"/>' .
				'</p>' .
				'<p>' .
				wp_nonce_field( 'admin', GROUPS_ADMIN_GROUPS_FILTER_NONCE, true, false ) .
				'<input class="button" type="submit" value="' . __( 'Apply', GROUPS_PLUGIN_DOMAIN ) . '"/>' .
				'<input class="button" type="submit" name="clear_filters" value="' . __( 'Clear', GROUPS_PLUGIN_DOMAIN ) . '"/>' .
				'<input type="hidden" value="submitted" name="submitted"/>' .
				'</p>' .
			'</form>' .
		'</div>';

	if ( $paginate ) {
	  require_once( GROUPS_CORE_LIB . '/class-groups-pagination.php' );
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
	$output .= '<label for="row_count">' . __('Results per page', GROUPS_PLUGIN_DOMAIN ) . '</label>';
	$output .= '<input name="row_count" type="text" size="2" value="' . esc_attr( $row_count ) .'" />';
	$output .= wp_nonce_field( 'admin', GROUPS_ADMIN_GROUPS_NONCE_1, true, false );
	$output .= '<input class="button" type="submit" value="' . __( 'Apply', GROUPS_PLUGIN_DOMAIN ) . '"/>';
	$output .= '</div>';
	$output .= '</form>';
	$output .= '</div>';

	$capability_table = _groups_get_tablename( "capability" );
	$group_capability_table = _groups_get_tablename( "group_capability" );

	// capabilities select
	$capabilities = $wpdb->get_results( "SELECT * FROM $capability_table ORDER BY capability" );
	$capabilities_select = sprintf(
		'<select class="select capability" name="capability_id[]" multiple="multiple" placeholder="%s" data-placeholder="%s">',
		esc_attr( __( 'Capabilities &hellip;', GROUPS_PLUGIN_DOMAIN ) ) ,
		esc_attr( __( 'Capabilities &hellip;', GROUPS_PLUGIN_DOMAIN ) )
	);
	foreach( $capabilities as $capability ) {
		$capabilities_select .= sprintf( '<option value="%s">%s</option>', esc_attr( $capability->capability_id ), wp_filter_nohtml_kses( $capability->capability ) );
	}
	$capabilities_select .= '</select>';
	$capabilities_select .= Groups_UIE::render_select( '.select.capability' );

	$output .= '<form id="groups-action" method="post" action="">';

	$output .= '<div class="tablenav top">';

	$output .= '<div class="groups-bulk-container">';
	$output .= '<div class="capabilities-select-container">';
	$output .= $capabilities_select;
	$output .= wp_nonce_field( 'admin', GROUPS_ADMIN_GROUPS_ACTION_NONCE, true, false );
	$output .= '</div>';
	$output .= '<select class="bulk-action" name="bulk-action">';
	$output .= '<option selected="selected" value="-1">' . __( 'Bulk Actions', GROUPS_PLUGIN_DOMAIN ) . '</option>';
	$output .= '<option value="remove-group">' . __( 'Remove group', GROUPS_PLUGIN_DOMAIN ) . '</option>';
	$output .= '<option value="add-capability">' . __( 'Add capability', GROUPS_PLUGIN_DOMAIN ) . '</option>';
	$output .= '<option value="remove-capability">' . __( 'Remove capability', GROUPS_PLUGIN_DOMAIN ) . '</option>';
	$output .= '</select>';
	$output .= sprintf( '<input class="button" type="submit" name="bulk" value="%s" />', __( 'Apply', GROUPS_PLUGIN_DOMAIN ) );
	$output .= '<input type="hidden" name="action" value="groups-action"/>';
	$output .= '</div>';
	$output .= '</div>';

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
			$column_display_name = '<a href="' . esc_url( add_query_arg( $options, $current_url ) ) . '"><span>' . $column_display_name . '</span><span class="sorting-indicator"></span></a>';
		}
		$output .= "<th scope='col' class='$class'>$column_display_name</th>";
	}

	$output .= '</tr>';
	$output .= '</thead>';
	$output .= '<tbody>';

	if ( count( $results ) > 0 ) {
		for ( $i = 0; $i < count( $results ); $i++ ) {

			$result = $results[$i];

			$output .= '<tr class="' . ( $i % 2 == 0 ? 'even' : 'odd' ) . '">';

			$output .= '<th class="check-column">';
			$output .= '<input type="checkbox" value="' . esc_attr( $result->group_id ) . '" name="group_ids[]"/>';
			$output .= '</th>';

			$output .= "<td class='group-id'>";
			$output .= $result->group_id;
			$output .= "</td>";
			$output .= "<td class='group-name'>" . stripslashes( wp_filter_nohtml_kses( $result->name ) ) . "</td>";
			$output .= "<td class='group-description'>" . stripslashes( wp_filter_nohtml_kses( $result->description ) ) . "</td>";

			$output .= '<td class="capabilities">';

			$group = new Groups_Group( $result->group_id );
			$group_capabilities = $group->capabilities;
			$group_capabilities_deep = $group->capabilities_deep;
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
					if ( isset( $group_capability->capability ) && isset( $group_capability->capability->capability ) ) {
						$output .= wp_filter_nohtml_kses( $group_capability->capability->capability );
					}
					$output .= '</span>';
					$output .= '</li>';
				}
				$output .= '</ul>';
			} else {
				$output .= __( 'This group has no capabilities.', GROUPS_PLUGIN_DOMAIN );
			}
			$output .= '</td>';

			$output .= "<td class='edit'>";
			$output .= "<a href='" . esc_url( add_query_arg( 'paged', $paged, $current_url ) ) . "&action=edit&group_id=" . $result->group_id . "' alt='" . __( 'Edit', GROUPS_PLUGIN_DOMAIN) . "'><img src='". GROUPS_PLUGIN_URL ."images/edit.png'/></a>";
			$output .= "</td>";

			$output .= "<td class='remove'>";
			if ( $result->name !== Groups_Registered::REGISTERED_GROUP_NAME ) {
				$output .= "<a href='" . esc_url( $current_url ) . "&action=remove&group_id=" . $result->group_id . "' alt='" . __( 'Remove', GROUPS_PLUGIN_DOMAIN) . "'><img src='". GROUPS_PLUGIN_URL ."images/remove.png'/></a>";
			}
			$output .= "</td>";

			$output .= '</tr>';
		}
	} else {
		$output .= '<tr><td colspan="7">' . __( 'There are no results.', GROUPS_PLUGIN_DOMAIN ) . '</td></tr>';
	}

	$output .= '</tbody>';
	$output .= '</table>';

	$output .= Groups_UIE::render_add_titles( '.groups-overview table td' );

	$output .= '</form>'; // #groups-action

	if ( $paginate ) {
	  require_once( GROUPS_CORE_LIB . '/class-groups-pagination.php' );
		$pagination = new Groups_Pagination($count, null, $row_count);
		$output .= '<div class="tablenav bottom">';
		$output .= $pagination->pagination( 'bottom' );
		$output .= '</div>';
	}

	$output .= '</div>'; // .groups-overview
	$output .= '</div>'; // .manage-groups

	echo $output;
	Groups_Help::footer();
} // function groups_admin_groups()
