<?php
/**
 * groups-admin-groups-bulk.php
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
 * @author Antonio Blanco
 * @package groups
 * @since groups 1.1.0
 */

/**
 * Shows form to confirm removal bulk groups
 */
function groups_admin_groups_bulk_remove() {
	
	global $wpdb;
	
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
		if ( $group )
			$groups[] = $group;
	}
	
	
	$group_table = _groups_get_tablename( 'group' );

	$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	$current_url = remove_query_arg( 'action', $current_url );
	$current_url = remove_query_arg( 'group_id', $current_url );
	
	$output =
		'<div class="manage-groups">' .
		'<div>' .
			'<h2>' .
				__( 'Remove groups', GROUPS_PLUGIN_DOMAIN ) .
			'</h2>' .
		'</div>';
	
	$output .= '<form id="groups-action" method="post" action="">';
	$output .= '<div class="group remove">';
	
	foreach ( $groups as $group ) {
		$output .= 	'<input id="group_ids" name="group_ids[]" type="hidden" value="' . esc_attr( intval( $group->group_id ) ) . '"/>' .
					'<ul>' .
					'<li>' . sprintf( __( 'Group Name : %s', GROUPS_PLUGIN_DOMAIN ), wp_filter_nohtml_kses( $group->name ) ) . '</li>' .
					'</ul> ';		
	}	
	$output .= '<input class="button" type="submit" name="bulk" value="' . __( "Remove", GROUPS_PLUGIN_DOMAIN ) . '"/>';
	$output .= '<a class="cancel" href="' . $current_url . '">' . __( 'Cancel', GROUPS_PLUGIN_DOMAIN ) . '</a>';

	$output .= '<input type="hidden" name="action" value="groups-action"/>';
	$output .= '<input type="hidden" name="bulk-action" value="remove-group"/>';
	$output .= '<input type="hidden" name="confirm" value="1"/>' .
			wp_nonce_field( 'admin', GROUPS_ADMIN_GROUPS_ACTION_NONCE, true, false );
	
	$output .= '</div>' .
				'</form>' .
				'</div>'; 
	
	echo $output;
	
	Groups_Help::footer();
} 

/**
 * Handle remove form submission.
 */
function groups_admin_groups_bulk_remove_submit() {
	
	global $wpdb;
	
	$result = false;
	
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
					$thisresult = Groups_Group::delete( $group_id );
					$result = $result && $thisresult;
				}
			}
		}
	}
	
	return $result;
} 
?>