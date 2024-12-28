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
 *
 * @param int $capability_id capability id
 */
function groups_admin_capabilities_remove( $capability_id ) {

	if ( !Groups_User::current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
		wp_die( esc_html__( 'Access denied.', 'groups' ) );
	}

	$capability = new Groups_Capability( $capability_id );

	if ( empty( $capability ) ) {
		wp_die( esc_html__( 'No such capability.', 'groups' ) );
	}

	$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$current_url = remove_query_arg( 'action', $current_url );
	$current_url = remove_query_arg( 'capability_id', $current_url );

	$output = '<div class="manage-capabilities wrap">';
	$output .= '<h1>';
	$output .= esc_html__( 'Remove a capability', 'groups' );
	$output .= '</h1>';
	$output .= sprintf( '<form id="remove-capability" action="%s" method="post">', esc_url( $current_url ) );
	$output .= '<div class="capability remove">';
	$output .= sprintf( '<input id="capability-id-field" name="capability-id-field" type="hidden" value="%s"/>', esc_attr( intval( $capability->get_capability_id() ) ) );
	$output .= '<ul>';
	$output .= '<li>';
	$output .= sprintf( '%s : %s', esc_html__( 'Capability', 'groups' ), stripslashes( wp_filter_nohtml_kses( $capability->get_capability() ) ) );
	$output .= '</li>';
	$output .= '</ul> ';
	$output .= wp_nonce_field( 'capabilities-remove', GROUPS_ADMIN_GROUPS_NONCE, true, false );
	$output .= sprintf( '<input class="button button-primary" type="submit" value="%s"/>', esc_attr__( 'Remove', 'groups' ) );
	$output .= '<input type="hidden" value="remove" name="action"/>';
	$output .= sprintf( '<a class="cancel button" href="%s">%s</a>', esc_url( $current_url ), esc_html__( 'Cancel', 'groups' ) );
	$output .= '</div>';
	$output .= '</div>'; // .capability.remove
	$output .= '</form>';
	$output .= '</div>'; // .manage-capabilities

	echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
} // function groups_admin_capabilities_remove

/**
 * Handle remove form submission.
 *
 * @return int|boolean ID of the deleted capability or false on failure
 */
function groups_admin_capabilities_remove_submit() {

	$result = false;

	if ( !Groups_User::current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
		wp_die( esc_html__( 'Access denied.', 'groups' ) );
	}

	if ( !wp_verify_nonce( $_POST[GROUPS_ADMIN_GROUPS_NONCE], 'capabilities-remove' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		wp_die( esc_html__( 'Access denied.', 'groups' ) );
	}

	$capability_id = isset( $_POST['capability-id-field'] ) ? $_POST['capability-id-field'] : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
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

	$output = '';

	if ( !Groups_User::current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
		wp_die( esc_html__( 'Access denied.', 'groups' ) );
	}

	$capability_ids = isset( $_POST['capability_ids'] ) ? $_POST['capability_ids'] : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	if ( $capability_ids === null || !is_array( $capability_ids ) ) {
		wp_die( esc_html__( 'No such capabilities.', 'groups' ) );
	}

	$capabilities = array();
	foreach ( $capability_ids as $capability_id ) {
		$capability = Groups_Capability::read( intval( $capability_id ) );
		if ( $capability ) {
			$capabilities[] = $capability;
		}
	}

	$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$current_url = remove_query_arg( 'action', $current_url );
	$current_url = remove_query_arg( 'capability_id', $current_url );

	$output .= '<div class="manage-capabilities wrap">';
	$output .= '<h1>';
	$output .= esc_html__( 'Remove capabilities', 'groups' );
	$output .= '</h1>';

	$output .= '<form id="capabilities-action" method="post" action="">';
	$output .= '<div class="capability remove">';
	$output .= '<p>';
	$output .= esc_html__( 'Please confirm to remove the following capabilities. This action cannot be undone.', 'groups' );
	$output .= '</p>';
	$output .= '<ul class="groups-capability-bulk-remove">';
	foreach ( $capabilities as $capability ) {
		$output .= sprintf( '<input id="capability_ids" name="capability_ids[]" type="hidden" value="%s"/>', esc_attr( intval( $capability->capability_id ) ) );
		$output .= '<li>';
		$output .= sprintf( '<strong>%s</strong>', stripslashes( wp_filter_nohtml_kses( $capability->capability ) ) );
		$output .= '</li>';
	}
	$output .= '</ul>';
	$output .= sprintf( '<input class="button button-primary" type="submit" name="bulk" value="%s"/>', esc_attr__( 'Remove', 'groups' ) );
	$output .= sprintf( '<a class="cancel button" href="%s">%s</a>', esc_url( $current_url ), esc_html__( 'Cancel', 'groups' ) );

	$output .= '<input type="hidden" name="action" value="groups-action"/>';
	$output .= '<input type="hidden" name="bulk-action" value="remove"/>';
	$output .= '<input type="hidden" name="confirm" value="1"/>';
	$output .= wp_nonce_field( 'admin', GROUPS_ADMIN_GROUPS_ACTION_NONCE, true, false );

	$output .= '</div>';
	$output .= '</form>';
	$output .= '</div>';

	echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
} // function groups_admin_capabilities_bulk_remove

/**
 * Handle remove form submission.
 *
 * @return array of deleted capabilities' ids
 */
function groups_admin_capabilities_bulk_remove_submit() {

	$result = array();

	if ( !Groups_User::current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
		wp_die( esc_html__( 'Access denied.', 'groups' ) );
	}

	if ( !wp_verify_nonce( $_POST[GROUPS_ADMIN_GROUPS_ACTION_NONCE], 'admin' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		wp_die( esc_html__( 'Access denied.', 'groups' ) );
	}

	$capability_ids = isset( $_POST['capability_ids'] ) ? $_POST['capability_ids'] : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	if ( $capability_ids !== null && is_array( $capability_ids ) ) {
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
