<?php
/**
 * groups-admin-groups-edit.php
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

/**
 * Show edit group form.
 * @param int $group_id group id
 */
function groups_admin_groups_edit( $group_id ) {
	
	global $wpdb;
	
	if ( !current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
		wp_die( __( 'Access denied.', GROUPS_PLUGIN_DOMAIN ) );
	}
	
	$group = Groups_Group::read( intval( $group_id ) );
	
	if ( empty( $group ) ) {
		wp_die( __( 'No such group.', GROUPS_PLUGIN_DOMAIN ) );
	}
	
	$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	$current_url = remove_query_arg( 'action', $current_url );
	$current_url = remove_query_arg( 'group_id', $current_url );
	
	$name		= isset( $_POST['name-field'] ) ? $_POST['name-field'] : $group->name;
	$description = isset( $_POST['description-field'] ) ? $_POST['description-field'] : $group->description;
	$parent_id   = isset( $_POST['parent-id-field'] ) ? $_POST['parent-id-field'] : $group->parent_id;
	
	$group_table = _groups_get_tablename( 'group' );
	$parent_select = '<select name="parent-id-field">';
	$parent_select .= '<option value="">--</option>';
	$groups = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $group_table WHERE group_id != %d", $group->group_id ) );
	foreach ( $groups as $g ) {
		$selected = ( $g->group_id == $group->parent_id ? ' selected="selected" ' : '' );
		$parent_select .= '<option ' . $selected . 'value="' . esc_attr( $g->group_id ) . '">' . wp_filter_nohtml_kses( $g->name ) . '</option>';
	}
	$parent_select .= '</select>';
	
	$name_readonly = ( $name !== Groups_Registered::REGISTERED_GROUP_NAME ) ? "" : ' readonly="readonly" ';
	
	$output =
		'<div class="manage-groups">' .
		'<div>' .
			'<h2>' .
				__( 'Edit a group', GROUPS_PLUGIN_DOMAIN ) .
			'</h2>' .
		'</div>' .
	
		'<form id="edit-group" action="' . $current_url . '" method="post">' .
		'<div class="group edit">' .
		'<input id="group-id-field" name="group-id-field" type="hidden" value="' . esc_attr( intval( $group_id ) ) . '"/>' .
		
		'<div class="field">' .
		'<label for="name-field" class="field-label first required">' .__( 'Name', GROUPS_PLUGIN_DOMAIN ) . '</label>' .
		'<input ' . $name_readonly . ' id="name-field" name="name-field" class="namefield" type="text" value="' . esc_attr( $name ) . '"/>' .
		'</div>' .
		
		'<div class="field">' .
		'<label for="parent-id-field" class="field-label">' .__( 'Parent', GROUPS_PLUGIN_DOMAIN ) . '</label>' .
		$parent_select .
		'</div>' .
	
		'<div class="field">' .
		'<label for="description-field" class="field-label description-field">' .__( 'Description', GROUPS_PLUGIN_DOMAIN ) . '</label>' .
		'<textarea id="description-field" name="description-field" rows="5" cols="45">' . wp_filter_nohtml_kses( $description ) . '</textarea>' .
		'</div>' .
		
		
		'<div class="field">' .
		'<label for="description-field" class="field-label description-field">' .__( 'Capabilities', GROUPS_PLUGIN_DOMAIN ) . '</label>' .  
		'<span class="description">' . __('These capabilities will be assigned to the group.', GROUPS_PLUGIN_DOMAIN). '</span>';
	
		$capability_table = _groups_get_tablename( "capability" );
		$group_capability_table = _groups_get_tablename( "group_capability" );
		
		$group_capabilities = $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM $capability_table WHERE capability_id IN ( SELECT capability_id FROM $group_capability_table WHERE group_id = %d )",
				Groups_Utility::id( $group_id )
		) );
		$group_capabilities_array = array();
		if (count($group_capabilities)>0) {
			foreach ($group_capabilities as $group_capability) {
				$group_capabilities_array[] = $group_capability->capability_id;
			}
		}
		
		$capabilities = $wpdb->get_results( "SELECT * FROM $capability_table ORDER BY capability" );
		
		$output .= '<div class="select-capability-container" style="width:62%;">';
		$output .= sprintf( '<select class="select capability" name="%s" multiple="multiple">', 'capability_ids[]' );
		foreach( $capabilities as $capability ) {
			$selected = in_array( $capability->capability_id, $group_capabilities_array ) ? ' selected="selected" ' : '';
			$output .= sprintf( '<option value="%s" %s>%s</option>', esc_attr( $capability->capability_id ), $selected, wp_filter_nohtml_kses( $capability->capability ) );
		}
		$output .= '</select>';
		$output .= '</div>';
		
		$output .= Groups_UIE::render_select( '.select.capability' );
		
		$output .= '</div>';		
		
		$output .= '<div class="field">' .
		wp_nonce_field( 'groups-edit', GROUPS_ADMIN_GROUPS_NONCE, true, false ) .
		'<input class="button" type="submit" value="' . __( 'Save', GROUPS_PLUGIN_DOMAIN ) . '"/>' .
		'<input type="hidden" value="edit" name="action"/>' .
		'<a class="cancel" href="' . $current_url . '">' . __( 'Cancel', GROUPS_PLUGIN_DOMAIN ) . '</a>' .
		'</div>' .
		'</div>' . // .group.edit
		'</form>' .
		'</div>'; // .manage-groups
	
		echo $output;
	
	Groups_Help::footer();
} // function groups_admin_groups_edit

/**
 * Handle edit form submission.
 */
function groups_admin_groups_edit_submit() {
	global $wpdb;
	
	if ( !current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
		wp_die( __( 'Access denied.', GROUPS_PLUGIN_DOMAIN ) );
	}
	
	if ( !wp_verify_nonce( $_POST[GROUPS_ADMIN_GROUPS_NONCE],  'groups-edit' ) ) {
		wp_die( __( 'Access denied.', GROUPS_PLUGIN_DOMAIN ) );
	}
	
	$group_id = isset( $_POST['group-id-field'] ) ? $_POST['group-id-field'] : null;
	$group = Groups_Group::read( $group_id );
	if ( $group ) {
		$group_id	= $group->group_id;
		if ( $group->name !== Groups_Registered::REGISTERED_GROUP_NAME ) {
			$name		= isset( $_POST['name-field'] ) ? $_POST['name-field'] : null;
		} else {
			$name = Groups_Registered::REGISTERED_GROUP_NAME;
		}
		$parent_id   = isset( $_POST['parent-id-field'] ) ? $_POST['parent-id-field'] : null;
		$description = isset( $_POST['description-field'] ) ? $_POST['description-field'] : '';
		$group_id = Groups_Group::update( compact( "group_id", "name", "parent_id", "description" ) );
		
		if ( $group_id ) {
			$capability_table = _groups_get_tablename( "capability" );
			$group_capability_table = _groups_get_tablename( "group_capability" );
			
			$group_capabilities = $wpdb->get_results( $wpdb->prepare(
					"SELECT * FROM $capability_table WHERE capability_id IN ( SELECT capability_id FROM $group_capability_table WHERE group_id = %d )",
					Groups_Utility::id( $group_id )
			) );
			$group_capabilities_array = array();
			foreach ($group_capabilities as $group_capability) {
				$group_capabilities_array[] = $group_capability->capability_id;
			}
			
			$read_caps = array();
			if ( isset( $_POST['capability_ids'] ) ) {
				$read_caps = $_POST['capability_ids'];
			}
			// delete
			foreach( $group_capabilities_array as $group_cap ) {
				if ( !in_array($group_cap, $read_caps) ) {
					Groups_Group_Capability::delete( $group_id, $group_cap );
				}
			}
			// add
			foreach( $read_caps as $read_cap ) {
				if ( !in_array($read_cap, $group_capabilities_array) ) {
					Groups_Group_Capability::create( array( 'group_id' => $group_id, 'capability_id' => $read_cap ) );
				}
			}
		}
		return $group_id;		
	} else {
		return false;
	}
	
} // function groups_admin_groups_edit_submit
