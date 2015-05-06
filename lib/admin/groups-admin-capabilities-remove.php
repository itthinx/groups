<?php
/**
 * groups-admin-capabilities-remove.php
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

/**
 * Shows form to confirm capability deletion.
 * @param int $capability_id capability id
 */
function groups_admin_capabilities_remove( $capability_id ) {

	global $wpdb;

	if ( !current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
		wp_die( __( 'Access denied.', GROUPS_PLUGIN_DOMAIN ) );
	}

	$capability = Groups_Capability::read( intval( $capability_id ) );

	if ( empty( $capability ) ) {
		wp_die( __( 'No such capability.', GROUPS_PLUGIN_DOMAIN ) );
	}

	$capability_table = _groups_get_tablename( 'capability' );

	$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	$current_url = remove_query_arg( 'action', $current_url );
	$current_url = remove_query_arg( 'capability_id', $current_url );

	$output =
		'<div class="manage-capabilities">' .
		'<div>' .
			'<h2>' .
				__( 'Remove a capability', GROUPS_PLUGIN_DOMAIN ) .
			'</h2>' .
		'</div>' .
		'<form id="remove-capability" action="' . $current_url . '" method="post">' .
		'<div class="capability remove">' .
		'<input id="capability-id-field" name="capability-id-field" type="hidden" value="' . esc_attr( intval( $capability->capability_id ) ) . '"/>' .
		'<ul>' .
		'<li>' . sprintf( __( 'Capability : %s', GROUPS_PLUGIN_DOMAIN ), stripslashes( wp_filter_nohtml_kses( $capability->capability ) ) ) . '</li>' .
		'</ul> ' .
		wp_nonce_field( 'capabilities-remove', GROUPS_ADMIN_GROUPS_NONCE, true, false ) .
		'<input class="button button-primary" type="submit" value="' . __( 'Remove', GROUPS_PLUGIN_DOMAIN ) . '"/>' .
		'<input type="hidden" value="remove" name="action"/>' .
		'<a class="cancel button" href="' . $current_url . '">' . __( 'Cancel', GROUPS_PLUGIN_DOMAIN ) . '</a>' .
		'</div>' .
		'</div>' . // .capability.remove
		'</form>' .
		'</div>'; // .manage-capabilities

	echo $output;

	Groups_Help::footer();
} // function groups_admin_capabilities_remove

/**
 * Handle remove form submission.
 */
function groups_admin_capabilities_remove_submit() {

	global $wpdb;

	$result = false;

	if ( !current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
		wp_die( __( 'Access denied.', GROUPS_PLUGIN_DOMAIN ) );
	}

	if ( !wp_verify_nonce( $_POST[GROUPS_ADMIN_GROUPS_NONCE], 'capabilities-remove' ) ) {
		wp_die( __( 'Access denied.', GROUPS_PLUGIN_DOMAIN ) );
	}

	$capability_id = isset( $_POST['capability-id-field'] ) ? $_POST['capability-id-field'] : null;
	$capability = Groups_Capability::read( $capability_id );
	if ( $capability ) {
		if ( $capability->capability !== Groups_Post_Access::READ_POST_CAPABILITY ) {
			$result = Groups_Capability::delete( $capability_id );
		}
	}
	return $result;
} // function groups_admin_capabilities_remove_submit

/**
 * Shows form to confirm removal bulk capabilities
 */
function groups_admin_capabilities_bulk_remove() {

	global $wpdb;

	$output = '';

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
		if ( $capability ) {
			$capabilities[] = $capability;
		}
	}

	$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	$current_url = remove_query_arg( 'action', $current_url );
	$current_url = remove_query_arg( 'capability_id', $current_url );

	$output .= '<div class="manage-capabilities">';
	$output .= '<div>';
	$output .= '<h2>';
	$output .= __( 'Remove capabilities', GROUPS_PLUGIN_DOMAIN );
	$output .= '</h2>';
	$output .= '</div>';

	$output .= '<form id="capabilities-action" method="post" action="">';
	$output .= '<div class="capability remove">';
	$output .= '<p>';
	$output .= __( 'Please confirm to remove the following capabilities. This action cannot be undone.', GROUPS_PLUGIN_DOMAIN );
	$output .= '</p>';
	foreach ( $capabilities as $capability ) {
		$output .= 	'<input id="capability_ids" name="capability_ids[]" type="hidden" value="' . esc_attr( intval( $capability->capability_id ) ) . '"/>';
		$output .= '<ul>';
		$output .= '<li>';
		$output .= sprintf( __( '<strong>%s</strong>', GROUPS_PLUGIN_DOMAIN ), wp_filter_nohtml_kses( $capability->capability ) );
		$output .= '</li>';
		$output .= '</ul>';
	}
	$output .= '<input class="button button-primary" type="submit" name="bulk" value="' . __( "Remove", GROUPS_PLUGIN_DOMAIN ) . '"/>';
	$output .= '<a class="cancel button" href="' . $current_url . '">' . __( 'Cancel', GROUPS_PLUGIN_DOMAIN ) . '</a>';

	$output .= '<input type="hidden" name="action" value="groups-action"/>';
	$output .= '<input type="hidden" name="bulk-action" value="remove"/>';
	$output .= '<input type="hidden" name="confirm" value="1"/>';
	$output .= wp_nonce_field( 'admin', GROUPS_ADMIN_GROUPS_ACTION_NONCE, true, false );

	$output .= '</div>';
	$output .= '</form>';
	$output .= '</div>';

	echo $output;

	Groups_Help::footer();
} // function groups_admin_capabilities_bulk_remove

/**
 * Handle remove form submission.
 * @return array of deleted capabilities' ids
 */
function groups_admin_capabilities_bulk_remove_submit() {

	global $wpdb;

	$result = array();

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
					if ( Groups_Capability::delete( $capability_id ) ) {
						$result[] = $capability->capability_id;
					}
				}
			}
		}
	}

	return $result;
} // function groups_admin_capabilities_bulk_remove_submit
