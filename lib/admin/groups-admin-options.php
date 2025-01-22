<?php
/**
 * groups-admin-options.php
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
 * @var string options form nonce name
 */
define( 'GROUPS_ADMIN_OPTIONS_NONCE', 'groups-admin-nonce' );

/**
 * @var int 14 days in seconds
 */
define( 'GROUPS_SHOW_EXTENSIONS_BOX_INTERVAL', 1209600 );

/**
 * Options admin screen.
 */
function groups_admin_options() {

	global $wp_roles, $groups_version;

	if ( !Groups_User::current_user_can( GROUPS_ADMINISTER_OPTIONS ) ) {
		wp_die( esc_html__( 'Access denied.', 'groups' ) );
	}

	$is_sitewide_plugin = false;
	if ( is_multisite() ) {
		$active_sitewide_plugins = get_site_option( 'active_sitewide_plugins', array() );
		$active_sitewide_plugins = array_keys( $active_sitewide_plugins );
		$is_sitewide_plugin = in_array( 'groups/groups.php', $active_sitewide_plugins );
	}

	$caps = array(
		GROUPS_ACCESS_GROUPS	  => __( 'Access Groups', 'groups' ),
		GROUPS_ADMINISTER_GROUPS  => __( 'Administer Groups', 'groups' ),
		GROUPS_ADMINISTER_OPTIONS => __( 'Administer Groups plugin options', 'groups' ),
		GROUPS_RESTRICT_ACCESS    => __( 'Restrict Access', 'groups' )
	);

	$previous_legacy_enable =  Groups_Options::get_option( GROUPS_LEGACY_ENABLE, GROUPS_LEGACY_ENABLE_DEFAULT );

	//
	// handle options form submission
	//
	if ( isset( $_POST['submit'] ) ) {
		if ( wp_verify_nonce( $_POST[GROUPS_ADMIN_OPTIONS_NONCE], 'admin' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			$post_types = get_post_types();
			$selected_post_types = !empty( $_POST['add_meta_boxes'] ) && is_array( $_POST['add_meta_boxes'] ) ? $_POST['add_meta_boxes'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			foreach( $post_types as $post_type ) {
				$handle_post_types[$post_type] = in_array( $post_type, $selected_post_types );
			}
			Groups_Post_Access::set_handles_post_types( $handle_post_types );

			// tree view
			if ( !empty( $_POST[GROUPS_SHOW_TREE_VIEW] ) ) {
				Groups_Options::update_option( GROUPS_SHOW_TREE_VIEW, true );
			} else {
				Groups_Options::update_option( GROUPS_SHOW_TREE_VIEW, false );
			}

			// show in user profiles
			Groups_Options::update_option( GROUPS_SHOW_IN_USER_PROFILE, !empty( $_POST[GROUPS_SHOW_IN_USER_PROFILE] ) );

			// roles & capabilities
			$rolenames = $wp_roles->get_names();
			foreach ( $rolenames as $rolekey => $rolename ) {
				$role = $wp_roles->get_role( $rolekey );
				foreach ( $caps as $capkey => $capname ) {
					$role_cap_id = $rolekey.'-'.$capkey;
					if ( !empty($_POST[$role_cap_id] ) ) {
						$role->add_cap( $capkey );
					} else {
						$role->remove_cap( $capkey );
					}
				}
			}
			Groups_Controller::assure_capabilities();

			if ( !$is_sitewide_plugin ) {
				// delete data
				if ( !empty( $_POST['delete-data'] ) ) {
					Groups_Options::update_option( 'groups_delete_data', true );
				} else {
					Groups_Options::update_option( 'groups_delete_data', false );
				}
			}

			// legacy enable ?
			if ( !empty( $_POST[GROUPS_LEGACY_ENABLE] ) ) {
				Groups_Options::update_option( GROUPS_LEGACY_ENABLE, true );
			} else {
				Groups_Options::update_option( GROUPS_LEGACY_ENABLE, false );
			}

			Groups_Admin::add_message( __( 'Options saved.', 'groups' ) );
		}
	}

	echo '<div class="groups-options wrap">';

	echo
		'<h1>' .
		esc_html__( 'Groups Options', 'groups' ) .
		'</h1>';

	echo Groups_Admin::render_messages(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	$show_tree_view = Groups_Options::get_option( GROUPS_SHOW_TREE_VIEW, GROUPS_SHOW_TREE_VIEW_DEFAULT );
	$show_in_user_profile = Groups_Options::get_option( GROUPS_SHOW_IN_USER_PROFILE, GROUPS_SHOW_IN_USER_PROFILE_DEFAULT );

	$rolenames = $wp_roles->get_names();
	$caps_table = '<table class="groups-permissions">';
	$caps_table .= '<thead>';
	$caps_table .= '<tr>';
	$caps_table .= '<td class="role">';
	$caps_table .= esc_html__( 'Role', 'groups' );
	$caps_table .= '</td>';
	foreach ( $caps as $cap ) {
		$caps_table .= '<td class="cap">';
		$caps_table .= esc_html( $cap );
		$caps_table .= '</td>';
	}

	$caps_table .= '</tr>';
	$caps_table .= '</thead>';
	$caps_table .= '<tbody>';
	foreach ( $rolenames as $rolekey => $rolename ) {
		$role = $wp_roles->get_role( $rolekey );
		$caps_table .= '<tr>';
		$caps_table .= '<td>';
		$caps_table .= esc_html( translate_user_role( $rolename ) );
		$caps_table .= '</td>';
		foreach ( $caps as $capkey => $capname ) {

			if ( $role->has_cap( $capkey ) ) {
				$checked = ' checked="checked" ';
			} else {
				$checked = '';
			}

			$caps_table .= '<td class="checkbox">';
			$role_cap_id = $rolekey.'-'.$capkey;
			$caps_table .= '<input type="checkbox" name="' . esc_attr( $role_cap_id ) . '" id="' . esc_attr( $role_cap_id ) . '" ' . $checked . '/>';
			$caps_table .= '</td>';
		}
		$caps_table .= '</tr>';
	}
	$caps_table .= '</tbody>';
	$caps_table .= '</table>';

	$delete_data = Groups_Options::get_option( 'groups_delete_data', false );

	if ( isset( $_GET['dismiss-groups-extensions-box'] ) && isset( $_GET['groups-extensions-box-nonce'] ) && wp_verify_nonce( $_GET['groups-extensions-box-nonce'], 'dismiss-box' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		Groups_Options::update_user_option( 'show-extensions-box', time() );
	}
	$extensions_box = '';
	$show_extensions_box = Groups_Options::get_user_option( 'show-extensions-box', 0 );
	if ( ( time() - $show_extensions_box ) > GROUPS_SHOW_EXTENSIONS_BOX_INTERVAL ) {
		$dismiss_url = wp_nonce_url( add_query_arg( 'dismiss-groups-extensions-box', '1', admin_url( 'admin.php?page=groups-admin-options' ) ), 'dismiss-box', 'groups-extensions-box-nonce' );
		$extensions_box = '<div id="groups-extensions-box">';
		$extensions_box .= sprintf( '<a title="%s" class="close" href="%s"></a>', esc_attr_x( 'Dismiss', 'title of dismiss notice link', 'groups' ), esc_url( $dismiss_url ) );
		$extensions_box .= '<h3>';
		$extensions_box .= esc_html__( 'Your support matters!', 'groups' );
		$extensions_box .= '</h3>';
		$extensions_box .= '<p>';
		$extensions_box .= sprintf(
		/* translators: 1: opening tag 2: closing tag */
			esc_html__( 'Enhanced functionality is available via official %1$sExtensions%2$s for Groups.', 'groups' ),
			'<a href="https://www.itthinx.com/shop/">',
			'</a>'
		);
		$extensions_box .= '</p>';
		$extensions_box .= '<p>';
		$extensions_box .= esc_html__( 'By getting an official extension, you fund the work that is necessary to maintain and improve Groups.', 'groups' );
		$extensions_box .= '</p>';
		$extensions_box .= '</div>';
	}

	//
	// print the options form
	//
	echo
		'<form action="" name="options" method="post">' .
		'<div>' .

		'<p>' .
		'<input class="button button-primary" type="submit" name="submit" value="' . esc_attr__( 'Save', 'groups' ) . '"/>' .
		$extensions_box . // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		'</p>';

	if ( _groups_admin_override() ) {
		echo
			'<h2 style="color:red">' .
			esc_html__( 'Administrator Access Override', 'groups' ) .
			'</h2>' .
			'<p>' .
			esc_html__( 'Administrators override all access permissions derived from Groups capabilities.', 'groups' ) .
			'</p>' .
			'<p>' .
			wp_kses_post( __( 'To disable, do not define the constant <code>GROUPS_ADMINISTRATOR_OVERRIDE</code> or set it to <code>false</code>.', 'groups' ) ) .
			'</p>' .
			'<p>' .
			wp_kses_post( __( 'Enabling this on production sites is <strong>not</strong> recommended.', 'groups' ) ) .
			'</p>';
	}

	echo '<h2>';
	echo esc_html__( 'Access restricions', 'groups' );
	echo '</h2>';

	echo '<h3>';
	echo esc_html__( 'Post types', 'groups' );
	echo '</h3>';

	echo '<p class="description">';
	echo esc_html__( 'Show access restrictions for these post types.', 'groups' ); // @todo change wording to '...handles access...' ?
	echo '</p>';

	$post_type_objects = get_post_types( array(), 'objects' );
	uasort( $post_type_objects, 'groups_admin_options_compare_post_types' );

	echo '<ul>';
	foreach( $post_type_objects as $post_type => $post_type_object ) {
		echo '<li>';
		echo '<label>';
		$label = $post_type;
		$labels = isset( $post_type_object->labels ) ? $post_type_object->labels : null;
		if ( ( $labels !== null ) && isset( $labels->singular_name ) ) {
			$label = $labels->singular_name; // this is already translated
		}
		$checked = Groups_Post_Access::handles_post_type( $post_type ) ? ' checked="checked" ' : '';
		echo '<input name="add_meta_boxes[]" type="checkbox" value="' . esc_attr( $post_type ) . '" ' . $checked . '/>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$is_public = isset( $post_type_object->public ) && $post_type_object->public;
		echo $is_public ? '<strong>' : '';
		echo esc_html( $label );
		echo $is_public ? '</strong>' : '';
		if ( $post_type != $label ) {
			echo ' ';
			echo '<code><small>';
			echo esc_html( $post_type );
			echo '</small></code>';
		}
		echo '</label>';
		echo '</li>';
	}
	echo '<ul>';
	echo '<p class="description">';
	esc_html_e( 'This determines for which post types access restriction settings are offered.', 'groups' );
	echo ' ';
	esc_html_e( 'Disabling this setting for a post type also disables existing access restrictions on individual posts of that type.', 'groups' );
	echo ' ';
	esc_html_e( 'Some post types shown may not offer access restrictions even though they appear enabled here.', 'groups' );
	echo '</p>';

	echo
		'<h2>' . esc_html__( 'User profiles', 'groups' ) . '</h2>' .
		'<p>' .
		'<label>' .
		'<input name="' . GROUPS_SHOW_IN_USER_PROFILE . '" type="checkbox" ' . ( $show_in_user_profile ? 'checked="checked"' : '' ) . '/>' . // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		esc_html__( 'Show groups in user profiles.', 'groups' ) .
		'</label>' .
		'</p>';

	echo
		'<h2>' . esc_html__( 'Tree view', 'groups' ) . '</h2>' .
		'<p>' .
		'<label>' .
		'<input name="' . GROUPS_SHOW_TREE_VIEW . '" type="checkbox" ' . ( $show_tree_view ? 'checked="checked"' : '' ) . '/>' . // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		esc_html__( 'Show the Groups tree view.', 'groups' ) .
		'</label>' .
		'</p>';

	echo
		'<h2>' . esc_html__( 'Permissions', 'groups' ) . '</h2>' .
		'<p>' . esc_html__( 'These permissions apply to Groups management. They do not apply to access permissions derived from Groups capabilities.', 'groups' ) . '</p>' .
		$caps_table . // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		'<p class="description">' .
		esc_html__( 'A minimum set of permissions will be preserved.', 'groups' ) .
		'<br/>' .
		esc_html__( 'If you lock yourself out, please ask an administrator to help.', 'groups' ) .
		'</p>';
	if ( !$is_sitewide_plugin ) {
		echo
			'<h2>' . esc_html__( 'Deactivation and data persistence', 'groups' ) . '</h2>' .
			'<p>' .
			'<label>' .
			'<input name="delete-data" type="checkbox" ' . ( $delete_data ? 'checked="checked"' : '' ) . '/>' .
			esc_html__( 'Delete all Groups plugin data on deactivation', 'groups' ) .
			'</label>' .
			'</p>' .
			'<p class="description warning">' .
			esc_html__( 'CAUTION: If this option is active while the plugin is deactivated, ALL plugin settings and data will be DELETED. If you are going to use this option, now would be a good time to make a backup. By enabling this option you agree to be solely responsible for any loss of data or any other consequences thereof.', 'groups' ) .
			'</p>';
	}

	$groups_legacy_enable = Groups_Options::get_option( GROUPS_LEGACY_ENABLE, GROUPS_LEGACY_ENABLE_DEFAULT );
	if (
		defined( 'GROUPS_SHOW_LEGACY_SETTINGS' ) && GROUPS_SHOW_LEGACY_SETTINGS === true || $groups_legacy_enable
	) {
		echo '<h2>' . esc_html__( 'Legacy Settings', 'groups' ) . '</h2>';
		echo '<p>' .
			'<label>' .
			'<input name="' . esc_attr( GROUPS_LEGACY_ENABLE ) . '" type="checkbox" ' . ( $groups_legacy_enable ? 'checked="checked"' : '' ) . '/>' . // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html__( 'Enable legacy access control based on capabilities.', 'groups' ) .
			'</label>' .
			'</p>';
		if ( $groups_legacy_enable ) {
			require_once GROUPS_LEGACY_LIB . '/admin/groups-admin-options-legacy.php';
			do_action( 'groups_admin_options_legacy', $groups_legacy_enable !== $previous_legacy_enable );
		}

		$legacy_enabled = Groups_Options::get_option( GROUPS_LEGACY_ENABLE );
		echo '<h3>';
		/* translators: version number */
		printf( esc_html__( 'Switching to Groups %s', 'groups' ), esc_html( $groups_version ) );
		echo '</h3>';
		echo '<p>';
		/* translators: version number */
		printf( esc_html__( 'Groups %s features a simpler model for access restrictions based on groups instead of capabilities used in Groups 1.x.', 'groups' ), esc_html( $groups_version ) );
		echo ' ';
		esc_html_e( 'To put it simple, previously you would have used capabilities to restrict access to posts and now you simply use groups.', 'groups' );
		echo ' ';
		esc_html_e( 'To make it easier to transition to the new model for those who migrate from a previous version, we have included legacy access control based on capabilities.', 'groups' );
		echo '</p>';
		echo '<div class="indent">';
		echo '<p>';
		esc_html_e( 'The following is only of interest if you have upgraded from Groups 1.x:', 'groups' );
		echo '<br/>';
		if ( $legacy_enabled ) {
			esc_html_e( 'You are running the system with legacy access control based on capabilities enabled.', 'groups' );
			echo ' ';
			esc_html_e( 'This means that if you had access restrictions in place that were based on capabilities, your entries will still be protected.', 'groups' );
		} else {
			esc_html_e( 'You are running the system with legacy access control based on capabilities disabled.', 'groups' );
			echo ' ';
			esc_html_e( 'This could be important!', 'groups' );
			echo ' ';
			esc_html_e( 'If you had any access restrictions in place based on capabilities, the entries will now be unprotected, unless you enable legacy access restrictions or place appropriate access restrictions based on groups on the desired entries.', 'groups' );
		}
		echo '</p>';
		echo '<p>';
		esc_html_e( 'If you would like to switch to access restrictions based on groups (recommended) instead of capabilities, you can easily do so by setting the appropriate groups on your protected posts, pages and other entries to restrict access.', 'groups' );
		echo ' ';
		esc_html_e( 'Once you have adjusted your access restrictions based on groups, you can disable legacy access control.', 'groups' );
		echo ' ';
		echo wp_kses_post( __( 'Please refer to the <a target="_blank" href="https://docs.itthinx.com/document/groups/">Documentation</a> for details on how to switch to and use the new access restrictions.', 'groups' ) );
		echo '</p>';
		echo '</div>'; // .indent
	}

	echo
		'<p>' .
		wp_nonce_field( 'admin', GROUPS_ADMIN_OPTIONS_NONCE, true, false ) . // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		'<input class="button button-primary" type="submit" name="submit" value="' . esc_attr__( 'Save', 'groups' ) . '"/>' .
		'</p>' .
		'</div>' .
		'</form>';

	echo '</div>'; // .groups-options
}

/**
 * Network administration options.
 */
function groups_network_admin_options() {

	if ( !Groups_User::current_user_can( GROUPS_ADMINISTER_OPTIONS ) ) {
		wp_die( esc_html__( 'Access denied.', 'groups' ) );
	}

	echo
		'<div>' .
		'<h1>' .
		esc_html__( 'Groups network options', 'groups' ) .
		'</h1>' .
		'</div>';

	// handle options form submission
	if ( isset( $_POST['submit'] ) ) {
		if ( wp_verify_nonce( $_POST[GROUPS_ADMIN_OPTIONS_NONCE], 'admin' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			// delete data
			if ( !empty( $_POST['delete-data'] ) ) {
				Groups_Options::update_option( 'groups_network_delete_data', true );
			} else {
				Groups_Options::update_option( 'groups_network_delete_data', false );
			}
		}
	}

	$delete_data = Groups_Options::get_option( 'groups_network_delete_data', false );

	// options form
	echo
		'<form action="" name="options" method="post">' .
		'<div>' .
		'<h2>' . esc_html__( 'Network deactivation and data persistence', 'groups' ) . '</h2>' .
		'<p>' .
		'<label>' .
		'<input name="delete-data" type="checkbox" ' . ( $delete_data ? 'checked="checked"' : '' ) . '/>' .
		' ' .
		esc_html__( 'Delete all Groups plugin data for ALL sites on network deactivation', 'groups' ) .
		'</label>' .
		'</p>' .
		'<p class="description warning">' .
		wp_kses_post( __( 'CAUTION: If this option is active while the plugin is deactivated, ALL plugin settings and data will be DELETED for <strong>all sites</strong>. If you are going to use this option, now would be a good time to make a backup. By enabling this option you agree to be solely responsible for any loss of data or any other consequences thereof.', 'groups' ) ) .
		'</p>' .
		'<p>' .
		wp_nonce_field( 'admin', GROUPS_ADMIN_OPTIONS_NONCE, true, false ) . // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		'<input class="button button-primary" type="submit" name="submit" value="' . esc_attr__( 'Save', 'groups' ) . '"/>' .
		'</p>' .
		'</div>' .
		'</form>';
}

/**
 * Compare two post types, considering those that have $public and/or $show_ui true as coming first.
 *
 * @param object $o1
 * @param object $o2
 *
 * @return int
 */
function groups_admin_options_compare_post_types( $o1, $o2 ) {
	$name_1 = isset( $o1->name ) ? $o1->name : '';
	$name_2 = isset( $o2->name ) ? $o2->name : '';
	$public_1 = isset( $o1->public ) && $o1->public;
	$public_2 = isset( $o2->public ) && $o2->public;
	$show_ui_1 = isset( $o1->show_ui ) && $o1->show_ui;
	$show_ui_2 = isset( $o2->show_ui ) && $o2->show_ui;
	$n1 = 0;
	$n2 = 0;
	if ( $public_1 ) {
		$n1--;
	}
	if ( $show_ui_1 ) {
		$n1--;
	}
	if ( $public_2 ) {
		$n2--;
	}
	if ( $show_ui_2 ) {
		$n2--;
	}
	return ( $n1 - $n2 ) * 10 + strcmp( $name_1, $name_2 );
}
