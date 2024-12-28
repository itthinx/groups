<?php
/**
 * class-groups-admin-user-profile.php
 *
 * Copyright (c) 2013 "kento" Karim Rahimpur www.itthinx.com
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
 * @since groups 1.3.11
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Show group info on user profile pages and let admins edit group membership.
 */
class Groups_Admin_User_Profile {

	/**
	 * Adds user profile actions.
	 */
	public static function init() {
		add_action( 'user_new_form', array( __CLASS__, 'user_new_form' ) );
		add_action( 'user_register', array( __CLASS__, 'user_register' ) );
		add_action( 'show_user_profile', array( __CLASS__, 'show_user_profile' ) );
		add_action( 'edit_user_profile', array( __CLASS__, 'edit_user_profile' ) );
		add_action( 'personal_options_update', array( __CLASS__, 'personal_options_update' ) );
		add_action( 'edit_user_profile_update', array( __CLASS__, 'edit_user_profile_update' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
	}

	/**
	 * Enqueues the select script on the user-edit and profile screens.
	 */
	public static function admin_enqueue_scripts() {
		$screen = get_current_screen();
		if ( isset( $screen->id ) ) {
			switch( $screen->id ) {
				case 'user' : // creating a new user
				case 'user-edit' :
				case 'profile' :
					require_once GROUPS_VIEWS_LIB . '/class-groups-uie.php';
					Groups_UIE::enqueue( 'select' );
					break;
			}
		}
	}

	/**
	 * Hook for the form to create a new user.
	 *
	 * See wp-admin/user-new.php
	 *
	 * @param string $type form context, expecting 'add-existing-user' (Multisite), or 'add-new-user' (single site and network admin)
	 */
	public static function user_new_form( $type = null ) {
		global $wpdb;
		if ( $type == 'add-new-user' ) {
			if ( Groups_User::current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
				$output = '<h3>' . _x( 'Groups', 'Groups section heading (add user)', 'groups' ) . '</h3>';
				$groups_table = _groups_get_tablename( 'group' );
				/**
				 * Allow to filter the groups.
				 *
				 * @since 2.20.0
				 *
				 * @param array $groups
				 * @param string $type form context
				 *
				 * @return array
				 */
				$groups = apply_filters(
					'groups_admin_user_profile_user_new_form_groups',
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$wpdb->get_results( "SELECT * FROM $groups_table ORDER BY name" ),
					$type
				);
				if ( $groups ) {
					$output .= '<style type="text/css">';
					$output .= '.groups .selectize-input { font-size: inherit; }';
					$output .= '</style>';
					$output .= sprintf(
						'<select id="user-groups" class="groups" name="group_ids[]" multiple="multiple" placeholder="%s" data-placeholder="%s">',
						esc_attr__( 'Choose groups &hellip;', 'groups' ),
						esc_attr__( 'Choose groups &hellip;', 'groups' )
					);
					foreach( $groups as $group ) {
						$output .= sprintf(
							'<option value="%d">%s</option>',
							Groups_Utility::id( $group->group_id ),
							$group->name ? stripslashes( wp_filter_nohtml_kses( $group->name ) ) : ''
						);
					}
					$output .= '</select>';
					$output .= Groups_UIE::render_select( '#user-groups' );
					$output .= '<p class="description">' . esc_html__( 'The user is a member of the chosen groups.', 'groups' ) . '</p>';
				}
				echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}
	}

	/**
	 * Adds the new user to chosen groups when creating a new user account
	 * from the admin side.
	 *
	 * @param int $user_id
	 */
	public static function user_register( $user_id ) {

		global $wpdb;

		if ( is_admin() ) {
			if ( function_exists( 'get_current_screen' ) ) {
				$screen = get_current_screen();
				if ( isset( $screen->id ) && $screen->id === 'user' ) {
					if ( Groups_User::current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
						$groups_table = _groups_get_tablename( 'group' );
						/**
						 * Allow to filter the groups offered.
						 *
						 * @since 2.20.0
						 *
						 * @param array $groups
						 * @param int $user_id
						 *
						 * @return array
						 */
						$groups = apply_filters(
							'groups_admin_user_profile_user_register_groups',
							$wpdb->get_results( "SELECT * FROM $groups_table" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
							$user_id
						);
						if ( $groups ) {
							$user_group_ids = isset( $_POST['group_ids'] ) && is_array( $_POST['group_ids'] ) ? $_POST['group_ids'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
							foreach( $groups as $group ) {
								if ( in_array( $group->group_id, $user_group_ids ) ) {
									// Do NOT use Groups_User::user_is_member( ... ) here, as this must not be filtered:
									if ( !Groups_User_Group::read( $user_id, $group->group_id ) ) {
										Groups_User_Group::create( array( 'user_id' => $user_id, 'group_id' => $group->group_id ) );
									}
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Own profile.
	 *
	 * @param WP_User $user
	 */
	public static function show_user_profile( $user ) {
		if ( Groups_User::current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
			self::edit_user_profile( $user );
		} else {
			$output = '<h3>' . _x( 'Groups', 'Groups section heading (user profile)', 'groups' ) . '</h3>';
			$user = new Groups_User( $user->ID );
			$groups = $user->get_groups();
			if ( is_array( $groups ) ) {
				if ( count( $groups ) > 0 ) {
					usort( $groups, array( __CLASS__, 'by_group_name' ) );
					$output .= '<ul>';
					foreach( $groups as $group ) {
						$output .= '<li>';
						$output .= $group->name ? stripslashes( wp_filter_nohtml_kses( $group->name ) ) : '';
						$output .= '</li>';
					}
					$output .= '</ul>';
				}
			}
			echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Editing a user profile.
	 *
	 * @param WP_User $user
	 */
	public static function edit_user_profile( $user ) {
		global $wpdb;
		if ( Groups_User::current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
			$output = '<h3>' . _x( 'Groups', 'Groups section heading (edit user)', 'groups' ) . '</h3>';
			$user = new Groups_User( $user->ID );
			$groups_table = _groups_get_tablename( 'group' );
			/**
			 * Allow to filter the groups offered.
			 *
			 * @since 2.20.0
			 *
			 * @param array $groups
			 * @param int $user_id
			 *
			 * @return array
			 */
			$groups = apply_filters(
				'groups_admin_user_profile_edit_user_profile_groups',
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->get_results( "SELECT * FROM $groups_table ORDER BY name" ),
				$user->get_user()->ID
			);
			if ( $groups ) {
				$output .= '<style type="text/css">';
				$output .= '.groups .selectize-input { font-size: inherit; }';
				$output .= '</style>';
				$output .= sprintf(
					'<select id="user-groups" class="groups" name="group_ids[]" multiple="multiple" placeholder="%s" data-placeholder="%s">',
					esc_attr__( 'Choose groups &hellip;', 'groups' ),
					esc_attr__( 'Choose groups &hellip;', 'groups' )
				);
				foreach( $groups as $group ) {
					// Do NOT use Groups_User::user_is_member( ... ) here, as this must not be filtered:
					$is_member = Groups_User_Group::read( $user->ID, $group->group_id ) ? true : false;
					$output .= sprintf(
						'<option value="%d" %s>%s</option>',
						Groups_Utility::id( $group->group_id ),
						$is_member ? ' selected="selected" ' : '',
						$group->name ? stripslashes( wp_filter_nohtml_kses( $group->name ) ) : ''
					);
				}
				$output .= '</select>';
				$output .= Groups_UIE::render_select( '#user-groups' );
				$output .= '<p class="description">' . esc_html__( 'The user is a member of the chosen groups.', 'groups' ) . '</p>';
			}
			echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Updates the group membership when a user's own profile is saved - but
	 * for group admins on their own profile page only.
	 *
	 * @param int $user_id
	 *
	 * @see Groups_Admin_User_Profile::edit_user_profile_update()
	 */
	public static function personal_options_update( $user_id ) {
		// We're using the same method as for editing another user's profile,
		// but let's check for group admin here as well.
		if ( Groups_User::current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
			self::edit_user_profile_update( $user_id );
		}
	}

	/**
	 * Updates the group membership.
	 *
	 * @param int $user_id
	 */
	public static function edit_user_profile_update( $user_id ) {
		global $wpdb;
		if ( Groups_User::current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
			$groups_table = _groups_get_tablename( 'group' );
			$groups = apply_filters(
				'groups_admin_user_profile_edit_user_profile_update_groups',
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->get_results( "SELECT * FROM $groups_table" ),
				$user_id
			);
			if ( $groups ) {
				$user_group_ids = isset( $_POST['group_ids'] ) && is_array( $_POST['group_ids'] ) ? $_POST['group_ids'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				foreach( $groups as $group ) {
					if ( in_array( $group->group_id, $user_group_ids ) ) {
						// Do NOT use Groups_User::user_is_member( ... ) here, as this must not be filtered:
						if ( !Groups_User_Group::read( $user_id, $group->group_id ) ) {
							Groups_User_Group::create( array( 'user_id' => $user_id, 'group_id' => $group->group_id ) );
						}
					} else {
						// Do NOT use Groups_User::user_is_member( ... ) here, as this must not be filtered:
						if ( Groups_User_Group::read( $user_id, $group->group_id ) ) {
							Groups_User_Group::delete( $user_id, $group->group_id );
						}
					}
				}
			}
		}
	}

	/**
	 * usort helper
	 *
	 * @param Groups_Group $o1
	 * @param Groups_Group $o2
	 *
	 * @return int strcmp result for group names
	 */
	public static function by_group_name( $o1, $o2 ) {
		return strcmp( $o1->get_name(), $o2->get_name() );
	}

}
Groups_Admin_User_Profile::init();
