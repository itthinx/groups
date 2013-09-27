<?php
/**
 * groups-admin-capabilities-bulk.php
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
function groups_admin_capabilities_bulk_remove() {
	
	global $wpdb;
	
	if ( !current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
		wp_die( __( 'Access denied.', GROUPS_PLUGIN_DOMAIN ) );
	}
	
	$capability_ids = isset( $_POST['capability_ids'] ) ? $_POST['capability_ids'] : null;
	
	if ( ! $capability_ids ) {
		wp_die( __( 'No such capabilities.', GROUPS_PLUGIN_DOMAIN ) );
	}
	
	$capabilities = array();
	foreach ( $capability_ids as $capability_id ) {
		$capability = Groups_Capability::read( intval( $capability_id ) );
		if ( $capability )
			$capabilities[] = $capability;
	}
	
	$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	$current_url = remove_query_arg( 'action', $current_url );
	$current_url = remove_query_arg( 'capability_id', $current_url );
	
	$output =
		'<div class="manage-capabilities">' .
		'<div>' .
			'<h2>' .
				__( 'Remove capabilities', GROUPS_PLUGIN_DOMAIN ) .
			'</h2>' .
		'</div>';
	
	$output .= '<form id="capabilities-action" method="post" action="">';
	$output .= '<div class="capability remove">';
	
	foreach ( $capabilities as $capability ) {
		$output .= 	'<input id="capability_ids" name="capability_ids[]" type="hidden" value="' . esc_attr( intval( $capability->capability_id ) ) . '"/>' .
					'<ul>' .
					'<li>' . sprintf( __( 'Capability Name : %s', GROUPS_PLUGIN_DOMAIN ), wp_filter_nohtml_kses( $capability->capability ) ) . '</li>' .
					'</ul> ';		
	}	
	$output .= '<input class="button" type="submit" name="bulk" value="' . __( "Remove", GROUPS_PLUGIN_DOMAIN ) . '"/>';
	$output .= '<a class="cancel" href="' . $current_url . '">' . __( 'Cancel', GROUPS_PLUGIN_DOMAIN ) . '</a>';

	$output .= '<input type="hidden" name="action" value="groups-action"/>';
	$output .= '<input type="hidden" name="bulk-action" value="remove"/>';
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
function groups_admin_capabilities_bulk_remove_submit() {
	
	global $wpdb;
	
	$result = false;
	
	if ( !current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
		wp_die( __( 'Access denied.', GROUPS_PLUGIN_DOMAIN ) );
	}
	
	if ( !wp_verify_nonce( $_POST[GROUPS_ADMIN_GROUPS_ACTION_NONCE], 'admin' ) ) {
		wp_die( __( 'Access denied.', GROUPS_PLUGIN_DOMAIN ) );
	}
	
	$capability_ids = isset( $_POST['capability_ids'] ) ? $_POST['capability_ids'] : null;
	if ( $capability_ids ) {
		foreach ( $capability_ids as $capability_id ) {
			$capability = Groups_Capability::read( $capability_id );
			if ( $capability ) {
				if ( $capability->capability !== Groups_Post_Access::READ_POST_CAPABILITY ) {
					$thisresult = Groups_Capability::delete( $capability_id );
					$result = $result && $thisresult;						
				}
			}		
		}
	}
	
	return $result;
} 
?>