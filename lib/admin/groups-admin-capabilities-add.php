<?php
/**
 * groups-admin-capabilities-add.php
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
 * Show add capability form.
 */
function groups_admin_capabilities_add() {

	if ( !Groups_User::current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
		wp_die( esc_html__( 'Access denied.', 'groups' ) );
	}

	$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	$current_url = remove_query_arg( 'paged', $current_url );
	$current_url = remove_query_arg( 'action', $current_url );
	$current_url = remove_query_arg( 'capability_id', $current_url );

	$capability  = isset( $_POST['capability-field'] ) ? $_POST['capability-field'] : '';
	$description = isset( $_POST['description-field'] ) ? $_POST['description-field'] : '';

	$output = '<div class="manage-capabilities wrap">';
	$output .= '<h1>';
	$output .= esc_html__( 'Add a new capability', 'groups' );
	$output .= '</h1>';
	$output .= Groups_Admin::render_messages();
	$output .= '<form id="add-capability" action="' . esc_url( $current_url ) . '" method="post">';
	$output .= '<div class="capability new">';

	$output .= '<div class="field">';
	$output .= sprintf( '<label for="capability-field" class="field-label first required">%s</label>', esc_html__( 'Capability', 'groups' ) );
	$output .= sprintf(
		'<input id="name-field" name="capability-field" class="capability-field" type="text" value="%s"/>',
		esc_attr( stripslashes( $capability ) )
	);
	$output .= '</div>';

	$output .= '<div class="field">';
	$output .= sprintf( '<label for="description-field" class="field-label description-field">%s</label>', esc_html__( 'Description', 'groups' ) );
	$output .= sprintf(
		'<textarea id="description-field" name="description-field" rows="5" cols="45">%s</textarea>',
		stripslashes( wp_filter_nohtml_kses( $description ) )
	);
	$output .= '</div>';

	$output .= '<div class="field">';
	$output .= wp_nonce_field( 'capabilities-add', GROUPS_ADMIN_GROUPS_NONCE, true, false );
	$output .= sprintf( '<input class="button button-primary" type="submit" value="%s"/>', esc_attr__( 'Add', 'groups' ) );
	$output .= '<input type="hidden" value="add" name="action"/>';
	$output .= sprintf( '<a class="cancel button" href="%s">%s</a>', esc_url( $current_url ), esc_html__( 'Cancel', 'groups' ) );
	$output .= '</div>';
	$output .= '</div>'; // .capability.new
	$output .= '</form>';
	$output .= '</div>'; // .manage-capabilities

	echo $output;

} // function groups_admin_capabilities_add

/**
 * Handle add capability form submission.
 *
 * @return int new capability's id or false if unsuccessful
 */
function groups_admin_capabilities_add_submit() {

	if ( !Groups_User::current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
		wp_die( esc_html__( 'Access denied.', 'groups' ) );
	}

	if ( !wp_verify_nonce( $_POST[GROUPS_ADMIN_GROUPS_NONCE], 'capabilities-add' ) ) {
		wp_die( esc_html__( 'Access denied.', 'groups' ) );
	}

	$capability  = isset( $_POST['capability-field'] ) ? $_POST['capability-field'] : null;
	$description = isset( $_POST['description-field'] ) ? $_POST['description-field'] : '';

	$capability_id = Groups_Capability::create( compact( "capability", "description" ) );
	if ( !$capability_id ) {
		if ( empty( $capability ) ) {
			Groups_Admin::add_message( __( 'The <em>Capability</em> must not be empty.', 'groups' ), 'error' );
		} else if ( Groups_Capability::read_by_capability( $capability ) ) {
			Groups_Admin::add_message( sprintf( __( 'The <em>%s</em> capability already exists.', 'groups' ), stripslashes( wp_filter_nohtml_kses( ( $capability ) ) ) ), 'error' );
		}
	}
	return $capability_id;
} // function groups_admin_capabilities_add_submit
