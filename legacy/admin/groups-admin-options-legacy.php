<?php
/**
 * groups-admin-options-legacy.php
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
 * @since groups 2.0.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Legacy options admin screen extension.
 * @param $legacy_switched boolean whether legacy mode setting has been changed during submit
 */
function groups_admin_options_legacy( $legacy_switched ) {

	global $wpdb;

	require_once GROUPS_LEGACY_LIB . '/access/class-groups-post-access-legacy.php';

	//
	// handle legacy options after form submission
	//
	if ( isset( $_POST['submit'] ) && !$legacy_switched ) {
		if ( wp_verify_nonce( $_POST[GROUPS_ADMIN_OPTIONS_NONCE], 'admin' ) ) {
			$valid_read_caps = array( Groups_Post_Access_Legacy::READ_POST_CAPABILITY );
			if ( !empty( $_POST[GROUPS_READ_POST_CAPABILITIES] ) ) {
				$read_caps = $_POST[GROUPS_READ_POST_CAPABILITIES];
				foreach( $read_caps as $read_cap ) {
					if ( $valid_cap = Groups_Capability::read( $read_cap ) ) {
						if ( !in_array( $valid_cap->capability, $valid_read_caps ) ) {
							$valid_read_caps[] = $valid_cap->capability;
						}
					}
				}
			}
			Groups_Options::update_option( Groups_Post_Access_Legacy::READ_POST_CAPABILITIES, $valid_read_caps );
		}
	}

	//
	// render legacy settings
	//
	echo '<h3>' . __( 'Capabilities', 'groups' ) . '</h3>';

	echo '<p class="description">' .
		__( 'Include these capabilities to enforce read access on posts. The selected capabilities will be offered to restrict access to posts.', 'groups' ) .
		'</p>';

	$capability_table = _groups_get_tablename( 'capability' );
	$capabilities = $wpdb->get_results( "SELECT * FROM $capability_table ORDER BY capability" );
	$applicable_read_caps = Groups_Options::get_option( Groups_Post_Access_Legacy::READ_POST_CAPABILITIES, array( Groups_Post_Access_Legacy::READ_POST_CAPABILITY ) );
	echo '<div class="select-capability-container" style="width:62%;">';
	printf( '<select class="select capability" name="%s" multiple="multiple">', GROUPS_READ_POST_CAPABILITIES . '[]' );
	foreach( $capabilities as $capability ) {
		$selected = in_array( $capability->capability, $applicable_read_caps ) ? ' selected="selected" ' : '';
		if ( $capability->capability == Groups_Post_Access_Legacy::READ_POST_CAPABILITY ) {
			$selected .= ' disabled="disabled" ';
		}
		printf( '<option value="%s" %s>%s</option>', esc_attr( $capability->capability_id ), $selected, wp_filter_nohtml_kses( $capability->capability ) );
	}
	echo '</select>';
	echo '</div>'; // .select-capability-container

	echo Groups_UIE::render_select( '.select.capability' );

}

add_action( 'groups_admin_options_legacy', 'groups_admin_options_legacy' );
