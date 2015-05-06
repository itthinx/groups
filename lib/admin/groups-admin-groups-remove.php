<?php
/**
 * groups-admin-groups-remove.php
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
 * @since groups 1.1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shows form to confirm removal of a group.
 * @param int $group_id group id
 */
function groups_admin_groups_remove( $group_id ) {
	
	global $wpdb;
	
	if ( !current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
		wp_die( __( 'Access denied.', GROUPS_PLUGIN_DOMAIN ) );
	}
	
	$group = Groups_Group::read( intval( $group_id ) );
	
	if ( empty( $group ) ) {
		wp_die( __( 'No such group.', GROUPS_PLUGIN_DOMAIN ) );
	}
	
	$group_table = _groups_get_tablename( 'group' );

	$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	$current_url = remove_query_arg( 'action', $current_url );
	$current_url = remove_query_arg( 'group_id', $current_url );
	
	$output =
		'<div class="manage-groups">' .
		'<div>' .
		'<h2>' .
		__( 'Remove a group', GROUPS_PLUGIN_DOMAIN ) .
		'</h2>' .
		'</div>' .
		'<form id="remove-group" action="' . $current_url . '" method="post">' .
		'<div class="group remove">' .
		'<input id="group-id-field" name="group-id-field" type="hidden" value="' . esc_attr( intval( $group->group_id ) ) . '"/>' .
		'<ul>' .
		'<li>' . sprintf( __( 'Group Name : %s', GROUPS_PLUGIN_DOMAIN ), stripslashes( wp_filter_nohtml_kses( $group->name ) ) ) . '</li>' .
		'</ul> ' .
		wp_nonce_field( 'groups-remove', GROUPS_ADMIN_GROUPS_NONCE, true, false ) .
		'<input class="button button-primary" type="submit" value="' . __( 'Remove', GROUPS_PLUGIN_DOMAIN ) . '"/>' .
		'<input type="hidden" value="remove" name="action"/>' .
		'<a class="cancel button" href="' . $current_url . '">' . __( 'Cancel', GROUPS_PLUGIN_DOMAIN ) . '</a>' .
		'</div>' .
		'</div>' . // .group.remove
		'</form>' .
		'</div>'; // .manage-groups
	
	echo $output;
	
	Groups_Help::footer();
} // function groups_admin_groups_remove

/**
 * Handle remove form submission.
 */
function groups_admin_groups_remove_submit() {
	
	global $wpdb;
	
	$result = false;
	
	if ( !current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
		wp_die( __( 'Access denied.', GROUPS_PLUGIN_DOMAIN ) );
	}
	
	if ( !wp_verify_nonce( $_POST[GROUPS_ADMIN_GROUPS_NONCE], 'groups-remove' ) ) {
		wp_die( __( 'Access denied.', GROUPS_PLUGIN_DOMAIN ) );
	}
	
	$group_id = isset( $_POST['group-id-field'] ) ? $_POST['group-id-field'] : null;
	$group = Groups_Group::read( $group_id );
	if ( $group ) {
		if ( $group->name !== Groups_Registered::REGISTERED_GROUP_NAME ) {
			$result = Groups_Group::delete( $group_id );
		}
	}
	return $result;
} // function groups_admin_groups_remove_submit

/**
 * Shows form to confirm bulk-removal of groups.
 */
function groups_admin_groups_bulk_remove() {

	global $wpdb;

	$output = '';

	if ( !current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
		wp_die( __( 'Access denied.', GROUPS_PLUGIN_DOMAIN ) );
	}

	$group_ids = isset( $_POST['group_ids'] ) ? $_POST['group_ids'] : null;

	if ( ! $group_ids ) {
		wp_die( __( 'No such groups.', GROUPS_PLUGIN_DOMAIN ) );
	}

	$groups = array();
	foreach ( $group_ids as $group_id ) {
		$group = Groups_Group::read( intval( $group_id ) );
		if ( $group ) {
			$groups[] = $group;
		}
	}

	$group_table = _groups_get_tablename( 'group' );

	$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	$current_url = remove_query_arg( 'action', $current_url );
	$current_url = remove_query_arg( 'group_id', $current_url );

	$output .= '<div class="manage-groups">';
	$output .= '<div>';
	$output .= '<h2>';
	$output .= __( 'Remove groups', GROUPS_PLUGIN_DOMAIN );
	$output .= '</h2>';
	$output .= '</div>';

	$output .= '<form id="groups-action" method="post" action="">';
	$output .= '<div class="group remove">';

	$output .= '<p>';
	$output .= __( 'Please confirm removal of the following groups. This action cannot be undone.', GROUPS_PLUGIN_DOMAIN );
	$output .= '</p>';

	foreach ( $groups as $group ) {
		$output .= 	'<input id="group_ids" name="group_ids[]" type="hidden" value="' . esc_attr( intval( $group->group_id ) ) . '"/>';
		$output .= '<ul>';
		$output .= '<li>';
		$output .= sprintf( __( '<strong>%s</strong>', GROUPS_PLUGIN_DOMAIN ), wp_filter_nohtml_kses( $group->name ) );
		$output .= '</li>';
		$output .= '</ul>';
	}
	$output .= '<input class="button button-primary" type="submit" name="bulk" value="' . __( "Remove", GROUPS_PLUGIN_DOMAIN ) . '"/>';
	$output .= '<a class="cancel button" href="' . $current_url . '">' . __( 'Cancel', GROUPS_PLUGIN_DOMAIN ) . '</a>';

	$output .= '<input type="hidden" name="action" value="groups-action"/>';
	$output .= '<input type="hidden" name="bulk-action" value="remove-group"/>';
	$output .= '<input type="hidden" name="confirm" value="1"/>';
	$output .= wp_nonce_field( 'admin', GROUPS_ADMIN_GROUPS_ACTION_NONCE, true, false );

	$output .= '</div>';
	$output .= '</form>';
	$output .= '</div>';

	echo $output;

	Groups_Help::footer();
} // function groups_admin_groups_bulk_remove

/**
 * Handle remove form submission.
 * @return array of deleted groups' ids
 */
function groups_admin_groups_bulk_remove_submit() {
	global $wpdb;

	$result = array();
	if ( !current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
		wp_die( __( 'Access denied.', GROUPS_PLUGIN_DOMAIN ) );
	}

	if ( !wp_verify_nonce( $_POST[GROUPS_ADMIN_GROUPS_ACTION_NONCE], 'admin' ) ) {
		wp_die( __( 'Access denied.', GROUPS_PLUGIN_DOMAIN ) );
	}

	$group_ids = isset( $_POST['group_ids'] ) ? $_POST['group_ids'] : null;
	if ( $group_ids ) {
		foreach ( $group_ids as $group_id ) {
			$group = Groups_Group::read( $group_id );
			if ( $group ) {
				if ( $group->name !== Groups_Registered::REGISTERED_GROUP_NAME ) {
					if ( Groups_Group::delete( $group_id ) ) {
						$result[] = $group->group_id;
					}
				}
			}
		}
	}

	return $result;
} // function groups_admin_groups_bulk_remove_submit
