<?php
/**
 * groups-admin-groups-add.php
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
 * Show add group form.
 */
function groups_admin_groups_add() {

	global $wpdb;

	$output = '';

	if ( !current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
		wp_die( __( 'Access denied.', 'groups' ) );
	}

	$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	$current_url = remove_query_arg( 'paged', $current_url );
	$current_url = remove_query_arg( 'action', $current_url );
	$current_url = remove_query_arg( 'group_id', $current_url );

	$parent_id   = isset( $_POST['parent-id-field'] ) ? $_POST['parent-id-field'] : '';
	$name        = isset( $_POST['name-field'] ) ? $_POST['name-field'] : '';
	$description = isset( $_POST['description-field'] ) ? $_POST['description-field'] : '';

	$group_table = _groups_get_tablename( 'group' );
	$parent_select = '<select name="parent-id-field">';
	$parent_select .= '<option value="">--</option>';
	$groups = $wpdb->get_results( "SELECT * FROM $group_table" );
	foreach ( $groups as $group ) {
		$parent_select .= '<option value="' . esc_attr( $group->group_id ) . '">' . wp_filter_nohtml_kses( $group->name ) . '</option>';
	}
	$parent_select .= '</select>';

	$output .= '<div class="manage-groups wrap">';
	$output .= '<h1>';
	$output .= __( 'Add a new group', 'groups' );
	$output .= '</h1>';

	$output .= Groups_Admin::render_messages();

	$output .= '<form id="add-group" action="' . esc_url( $current_url ) . '" method="post">';
	$output .= '<div class="group new">';

	$output .= '<div class="field">';
	$output .= '<label for="name-field" class="field-label first required">';
	$output .= __( 'Name', 'groups' );
	$output .= '</label>';
	$output .= '<input id="name-field" name="name-field" class="namefield" type="text" value="' . esc_attr( stripslashes( $name ) ) . '"/>';
	$output .= '</div>';

	$output .= '<div class="field">';
	$output .= '<label for="parent-id-field" class="field-label">';
	$output .= __( 'Parent', 'groups' );
	$output .= '</label>';
	$output .= $parent_select;
	$output .= '</div>';

	$output .= '<div class="field">';
	$output .= '<label for="description-field" class="field-label description-field">';
	$output .= __( 'Description', 'groups' );
	$output .= '</label>';
	$output .= '<textarea id="description-field" name="description-field" rows="5" cols="45">';
	$output .= stripslashes( wp_filter_nohtml_kses( $description ) );
	$output .= '</textarea>';
	$output .= '</div>';

	$output .= '<div class="field">';

	$capability_table = _groups_get_tablename( "capability" );
	$capabilities     = $wpdb->get_results( "SELECT * FROM $capability_table ORDER BY capability" );

	$output .= '<div class="select-capability-container" style="width:62%;">';
	$output .= '<label>';
	$output .= __( 'Capabilities', 'groups' );
	$output .= sprintf(
		'<select class="select capability" name="capability_ids[]" multiple="multiple" placeholder="%s">',
		__( 'Choose capabilities &hellip;', 'groups' )
	);
	foreach( $capabilities as $capability ) {
		$output .= sprintf( '<option value="%s">%s</option>', esc_attr( $capability->capability_id ), wp_filter_nohtml_kses( $capability->capability ) );
	}
	$output .= '</select>';
	$output .= '</label>';
	$output .= '</div>';
	$output .= '<p class="description">';
	$output .= __( 'These capabilities will be assigned to the group.', 'groups' );
	$output .= '</p>';

	$output .= Groups_UIE::render_select( '.select.capability' );
	$output .= '</div>';

	$output .= apply_filters( 'groups_admin_groups_add_form_after_fields', '' );

	$output .= '<div class="field">';
	$output .= wp_nonce_field( 'groups-add', GROUPS_ADMIN_GROUPS_NONCE, true, false );
	$output .= '<input class="button button-primary" type="submit" value="' . __( 'Add', 'groups' ) . '"/>';
	$output .= '<input type="hidden" value="add" name="action"/>';
	$output .= '<a class="cancel button" href="' . esc_url( $current_url ) . '">' . __( 'Cancel', 'groups' ) . '</a>';
	$output .= '</div>';
	$output .= '</div>'; // .group.new
	$output .= '</form>';
	$output .= '</div>'; // .manage-groups

	echo $output;
} // function groups_admin_groups_add

/**
 * Handle add group form submission.
 * @return int new group's id or false if unsuccessful
 */
function groups_admin_groups_add_submit() {

	global $wpdb;

	if ( !current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
		wp_die( __( 'Access denied.', 'groups' ) );
	}

	if ( !wp_verify_nonce( $_POST[GROUPS_ADMIN_GROUPS_NONCE], 'groups-add' ) ) {
		wp_die( __( 'Access denied.', 'groups' ) );
	}

	$creator_id  = get_current_user_id();
	$datetime    = date( 'Y-m-d H:i:s', time() );
	$parent_id   = isset( $_POST['parent-id-field'] ) ? $_POST['parent-id-field'] : null;
	$description = isset( $_POST['description-field'] ) ? $_POST['description-field'] : '';
	$name        = isset( $_POST['name-field'] ) ? $_POST['name-field'] : null;

	$group_id = Groups_Group::create( compact( "creator_id", "datetime", "parent_id", "description", "name" ) );
	if ( $group_id ) {
		if ( !empty( $_POST['capability_ids'] ) ) {
			$caps = $_POST['capability_ids'];
			foreach( $caps as $cap ) {
				Groups_Group_Capability::create( array( 'group_id' => $group_id, 'capability_id' => $cap ) );
			}
		}
		do_action( 'groups_admin_groups_add_submit_success', $group_id );
	} else {
		if ( !$name ) {
			Groups_Admin::add_message( __( 'The name must not be empty.', 'groups' ), 'error' );
		} else if ( Groups_Group::read_by_name( $name ) ) {
			Groups_Admin::add_message( sprintf( __( 'The <em>%s</em> group already exists.', 'groups' ), stripslashes( wp_filter_nohtml_kses( ( $name ) ) ) ), 'error' );
		}
	}

	return $group_id;
} // function groups_admin_groups_add_submit
