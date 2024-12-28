<?php
/**
 * groups-admin-capability-edit.php
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
 * Show edit capability form.
 *
 * @param int $capability_id capability id
 */
function groups_admin_capabilities_edit( $capability_id ) {

	global $wpdb;

	if ( !Groups_User::current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
		wp_die( esc_html__( 'Access denied.', 'groups' ) );
	}

	$capability = Groups_Capability::read( intval( $capability_id ) );

	if ( empty( $capability ) ) {
		wp_die( esc_html__( 'No such capability.', 'groups' ) );
	}

	$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$current_url = remove_query_arg( 'action', $current_url );
	$current_url = remove_query_arg( 'capability_id', $current_url );

	$capability_capability = isset( $_POST['capability-field'] ) ? sanitize_text_field( $_POST['capability-field'] ) : ( $capability->capability !== null ? $capability->capability : '' );
	$description = isset( $_POST['description-field'] ) ? sanitize_textarea_field( $_POST['description-field'] ) : ( $capability->description !==null ? $capability->description : '' );

	$capability_readonly = ( $capability->capability !== Groups_Post_Access::READ_POST_CAPABILITY ) ? "" : ' readonly="readonly" ';

	$output = '<div class="manage-capabilities wrap">';
	$output .= '<h1>';
	$output .= esc_html__( 'Edit a capability', 'groups' );
	$output .= '</h1>';

	$output .= Groups_Admin::render_messages();

	$output .= sprintf( '<form id="edit-capability" action="%s" method="post">', esc_url( $current_url ) );
	$output .= '<div class="capability edit">';
	$output .= sprintf( '<input id="capability-id-field" name="capability-id-field" type="hidden" value="%s"/>', esc_attr( intval( $capability_id ) ) );

	$output .= '<div class="field">';
	$output .= sprintf( '<label for="capability-field" class="field-label first required">%s</label>', esc_html__( 'Capability', 'groups' ) );
	$output .= sprintf(
		'<input %s id="capability-field" name="capability-field" class="capability-field" type="text" value="%s"/>',
		$capability_readonly,
		esc_attr( stripslashes( $capability_capability ) )
	);
	$output .= '</div>';

	$output .= '<div class="field">';
	$output .= sprintf( '<label for="description-field" class="field-label description-field">%s</label>', esc_html__( 'Description', 'groups' ) );
	$output .= sprintf( '<textarea id="description-field" name="description-field" rows="5" cols="45">%s</textarea>', stripslashes( wp_filter_nohtml_kses( $description ) ) );
	$output .= '</div>';

	$output .= '<div class="field">';
	$output .= wp_nonce_field( 'capabilities-edit', GROUPS_ADMIN_GROUPS_NONCE, true, false );
	$output .= sprintf( '<input class="button button-primary" type="submit" value="%s"/>', esc_attr__( 'Save', 'groups' ) );
	$output .= '<input type="hidden" value="edit" name="action"/>';
	$output .= sprintf( '<a class="cancel button" href="%s">%s</a>', esc_url( $current_url ), esc_html__( 'Cancel', 'groups' ) );
	$output .= '</div>';
	$output .= '</div>'; // .capability.edit
	$output .= '</form>';
	$output .= '</div>'; // .manage-capabilities

	echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
} // function groups_admin_capabilities_edit

/**
 * Handle edit form submission.
 *
 * @return int|boolean the capability ID if it was updated, otherwise false
 */
function groups_admin_capabilities_edit_submit() {

	$result = false;

	if ( !Groups_User::current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
		wp_die( esc_html__( 'Access denied.', 'groups' ) );
	}

	if ( !wp_verify_nonce( $_POST[GROUPS_ADMIN_GROUPS_NONCE],  'capabilities-edit' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		wp_die( esc_html__( 'Access denied.', 'groups' ) );
	}

	$capability_id = isset( $_POST['capability-id-field'] ) ? $_POST['capability-id-field'] : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$capability = Groups_Capability::read( $capability_id );
	if ( $capability ) {
		$capability = new Groups_Capability( $capability_id );
		$capability_id = $capability->get_capability_id();
		if ( $capability->get_capability() !== Groups_Post_Access::READ_POST_CAPABILITY ) {
			$capability_field = isset( $_POST['capability-field'] ) ? sanitize_text_field( $_POST['capability-field'] ) : null;
		} else {
			$capability_field = Groups_Post_Access::READ_POST_CAPABILITY;
		}
		if ( !empty( $capability_field ) ) {
			$update = true;
			if ( $other_capability = Groups_Capability::read_by_capability( $capability_field ) ) {
				if ( $other_capability->capability_id != $capability_id ) {
					Groups_Admin::add_message( sprintf( __( 'The <em>%s</em> capability already exists and cannot be assigned to this one.', 'groups' ), stripslashes( wp_filter_nohtml_kses( $other_capability->capability ) ) ), 'error' );
					$update = false;
				}
			}
			if ( $update ) {
				$description = isset( $_POST['description-field'] ) ? sanitize_textarea_field( $_POST['description-field'] ) : '';
				$capability_id = Groups_Capability::update( array( 'capability_id' => $capability_id, 'capability' => $capability_field, 'description' => $description ) );
				if ( $capability_id ) {
					$result = $capability_id;
				} else {
					Groups_Admin::add_message( sprintf( __( 'The <em>%s</em> capability could not be updated.', 'groups' ), stripslashes( wp_filter_nohtml_kses( $capability->get_capability() ) ) ), 'error' );
				}
			}
		} else {
			Groups_Admin::add_message( __( 'The <em>Capability</em> must not be empty.', 'groups' ), 'error' );
		}
	}
	return $result;
} // function groups_admin_capabilities_edit_submit
