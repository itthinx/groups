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
 *
 * @param int $group_id group id
 */
function groups_admin_groups_remove( $group_id ) {

	if ( !Groups_User::current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
		wp_die( esc_html__( 'Access denied.', 'groups' ) );
	}

	$group = Groups_Group::read( intval( $group_id ) );

	if ( empty( $group ) ) { // @phpstan-ignore empty.variable
		wp_die( esc_html__( 'No such group.', 'groups' ) );
	}

	$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$current_url = remove_query_arg( 'action', $current_url );
	$current_url = remove_query_arg( 'group_id', $current_url );

	$output = '<div class="manage-groups wrap">';
	$output .= '<h1>';
	$output .= esc_html__( 'Remove a group', 'groups' );
	$output .= '</h1>';
	$output .= sprintf( '<form id="remove-group" action="%s" method="post">', esc_url( $current_url ) );
	$output .= '<div class="group remove">';
	$output .= sprintf( '<input id="group-id-field" name="group-id-field" type="hidden" value="%s"/>', esc_attr( intval( $group->group_id ) ) );
	$output .= '<ul>';
	$output .= '<li>';
	$output .= sprintf( '%s : <strong>%s</strong> [%d]', esc_html__( 'Group', 'groups' ), stripslashes( wp_filter_nohtml_kses( $group->name ) ), esc_html( $group->group_id ) );
	$output .= '</li>';
	$output .= '</ul> ';
	$output .= wp_nonce_field( 'groups-remove', GROUPS_ADMIN_GROUPS_NONCE, true, false );
	$output .= sprintf( '<input class="button button-primary" type="submit" value="%s"/>', esc_attr__( 'Remove', 'groups' ) );
	$output .= '<input type="hidden" value="remove" name="action"/>';
	$output .= sprintf( '<a class="cancel button" href="%s">%s</a>', esc_url( $current_url ), esc_html__( 'Cancel', 'groups' ) );
	$output .= '</div>'; // .group.remove
	$output .= '</form>';
	$output .= '</div>'; // .manage-groups

	echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
} // function groups_admin_groups_remove

/**
 * Handle remove form submission.
 *
 * @return int|false group ID if successful, otherwise false
 */
function groups_admin_groups_remove_submit() {

	$result = false;

	if ( !Groups_User::current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
		wp_die( esc_html__( 'Access denied.', 'groups' ) );
	}

	if ( !wp_verify_nonce( $_POST[GROUPS_ADMIN_GROUPS_NONCE], 'groups-remove' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		wp_die( esc_html__( 'Access denied.', 'groups' ) );
	}

	$group_id = isset( $_POST['group-id-field'] ) ? $_POST['group-id-field'] : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
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

	$output = '';

	if ( !Groups_User::current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
		wp_die( esc_html__( 'Access denied.', 'groups' ) );
	}

	$group_ids = isset( $_POST['group_ids'] ) ? $_POST['group_ids'] : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	if ( $group_ids === null || !is_array( $group_ids ) ) {
		wp_die( esc_html__( 'No such groups.', 'groups' ) );
	}

	$groups = array();
	foreach ( $group_ids as $group_id ) {
		$group = Groups_Group::read( intval( $group_id ) );
		if ( $group ) {
			$groups[] = $group;
		}
	}

	$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$current_url = remove_query_arg( 'action', $current_url );
	$current_url = remove_query_arg( 'group_id', $current_url );

	$output .= '<div class="manage-groups wrap">';
	$output .= '<h1>';
	$output .= esc_html__( 'Remove groups', 'groups' );
	$output .= '</h1>';

	$output .= '<form id="groups-action" method="post" action="">';
	$output .= '<div class="group remove">';

	$output .= '<p>';
	$output .= esc_html__( 'Please confirm removal of the following groups. This action cannot be undone.', 'groups' );
	$output .= '</p>';

	$output .= '<ul class="groups-group-bulk-remove">';
	foreach ( $groups as $group ) {
		$output .= sprintf( '<input id="group_ids" name="group_ids[]" type="hidden" value="%s"/>', esc_attr( intval( $group->group_id ) ) );
		$output .= '<li>';
		$output .= sprintf( '<strong>%s</strong> [%d]', stripslashes( wp_filter_nohtml_kses( $group->name ) ), esc_html( $group->group_id ) );
		$output .= '</li>';
	}
	$output .= '</ul>';
	$output .= sprintf( '<input class="button button-primary" type="submit" name="bulk" value="%s"/>', esc_attr__( 'Remove', 'groups' ) );
	$output .= sprintf( '<a class="cancel button" href="%s">%s</a>', esc_url( $current_url ), esc_html__( 'Cancel', 'groups' ) );

	$output .= '<input type="hidden" name="action" value="groups-action"/>';
	$output .= '<input type="hidden" name="bulk-action" value="remove-group"/>';
	$output .= '<input type="hidden" name="confirm" value="1"/>';
	$output .= wp_nonce_field( 'admin', GROUPS_ADMIN_GROUPS_ACTION_NONCE, true, false );

	$output .= '</div>';
	$output .= '</form>';
	$output .= '</div>';

	echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
} // function groups_admin_groups_bulk_remove

/**
 * Handle remove form submission.
 *
 * @return array of deleted groups' ids
 */
function groups_admin_groups_bulk_remove_submit() {

	$result = array();
	if ( !Groups_User::current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
		wp_die( esc_html__( 'Access denied.', 'groups' ) );
	}

	if ( !wp_verify_nonce( $_POST[GROUPS_ADMIN_GROUPS_ACTION_NONCE], 'admin' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		wp_die( esc_html__( 'Access denied.', 'groups' ) );
	}

	$group_ids = isset( $_POST['group_ids'] ) ? $_POST['group_ids'] : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	if ( $group_ids !== null && is_array( $group_ids ) ) {
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
