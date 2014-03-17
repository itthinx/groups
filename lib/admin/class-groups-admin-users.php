<?php
/**
 * class-groups-admin-users.php
 *
 * Copyright (c) 2012 "kento" Karim Rahimpur www.itthinx.com
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
 * Users admin integration with Groups.
 */
class Groups_Admin_Users {

	const GROUPS = 'groups_user_groups';

	/**
	 * Hooks into filters to add the Groups column to the users table.
	 */
	public static function init() {
		// we hook this on admin_init so that current_user_can() is available
		add_action( 'admin_init', array( __CLASS__, 'setup' ) );
	}

	/**
	 * Adds the filters and actions only for users who have the right
	 * Groups permissions.
	 */
	public static function setup() {
		if ( current_user_can( GROUPS_ACCESS_GROUPS ) ) {
			// filters to display the user's groups
			add_filter( 'manage_users_columns', array( __CLASS__, 'manage_users_columns' ) );
			// args: unknown, string $column_name, int $user_id
			add_filter( 'manage_users_custom_column', array( __CLASS__, 'manage_users_custom_column' ), 10, 3 );
		}
		if ( current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
			if ( !is_network_admin() ) {
				// scripts
				add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
				// styles
				add_action( 'admin_head', array( __CLASS__, 'admin_head' ) );
				// allow to add or remove selected users to groups
				add_action( 'load-users.php', array( __CLASS__, 'load_users' ) );
				// add links to filter users by group
				add_filter( 'views_users', array( __CLASS__, 'views_users' ) );
				// modify query to filter users by group
				add_filter( 'pre_user_query', array( __CLASS__, 'pre_user_query' ) );
				// WP_Users_List_Table implements extra_tablenav() where the restrict_manage_users action is invoked.
				// As the extra_tablenav() method does not define a generic extension point, this is
				// the best shot we get at inserting our group actions block (currently we're at WordPress 3.6.1). 
				// We choose to use our own group-actions block instead of re-using the existing bulk-actions,
				// to have a more explicit user interface which makes it clear that these actions
				// are directed at relating users and groups.
				add_action( 'restrict_manage_users', array( __CLASS__, 'restrict_manage_users' ), 0 );
			}
		}
	}

	/**
	 * Modify query to filter users by group.
	 * 
	 * @param WP_User_Query $user_query
	 * @return WP_User_Query
	 */
	public static function pre_user_query( $user_query ) {
		global $pagenow, $wpdb;
		if ( ( $pagenow == 'users.php' ) && empty( $_GET['page'] ) ) {
			if ( isset( $_REQUEST['group'] ) ) {
				$group_id = $_REQUEST['group'];
				if ( Groups_Group::read( $group_id ) ) {
					$group = new Groups_Group( $group_id );
					$users = $group->users;
					$include = array();
					if ( count( $users ) > 0 ) {
						foreach( $users as $user ) {
							$include[] = $user->user->ID;
						}
					} else { // no results
						$include[] = 0;
					}
					$ids = implode( ',', wp_parse_id_list( $include ) );
					$user_query->query_where .= " AND $wpdb->users.ID IN ($ids)";
				}
			}
		}
		return $user_query;
	}

	/**
	 * Enqueue scripts the group-actions.
	 */
	public static function admin_enqueue_scripts() {

		global $pagenow;

		if ( ( $pagenow == 'users.php' ) && empty( $_GET['page'] ) ) {
			Groups_UIE::enqueue( 'select' );
		}
	}

	/**
	 * Adds the group add/remove buttons after the last action box.
	 */
	public static function admin_head() {

		global $pagenow;

		if ( ( $pagenow == 'users.php' ) && empty( $_GET['page'] ) ) {

			// .subsubsub rule added because with views_users() the list can get long
			// icon distinguishes from role links
			echo '<style type="text/css">';
			echo '.subsubsub { white-space: normal; }';
			echo 'a.group { background: url(' . GROUPS_PLUGIN_URL . '/images/groups-grey-8x8.png) transparent no-repeat left center; padding-left: 10px;}';
			echo '</style>';

			// group-actions
			echo '<style type="text/css">';
			echo '.groups-bulk-container { display: inline-block; line-height: 24px; padding-bottom: 1em; vertical-align: top; margin-left: 1em; margin-right: 1em; }';
			echo '.groups-bulk-container .groups-select-container { display: inline-block; vertical-align: top; }';
			echo '.groups-bulk-container .groups-select-container select, .groups-bulk-container select.groups-action { float: none; margin-right: 4px; vertical-align: top; }';
			echo '.groups-bulk-container .selectize-control { min-width: 128px; }';
			echo '.groups-bulk-container .selectize-control, .groups-bulk-container select.groups-action { margin-right: 4px; vertical-align: top; }';
			echo '.groups-bulk-container .selectize-input { font-size: inherit; line-height: 18px; padding: 1px 2px 2px 2px; vertical-align: middle; }';
			echo '.groups-bulk-container .selectize-input input[type="text"] { font-size: inherit; vertical-align: middle; height: 24px; }';
			echo '.groups-bulk-container input.button { margin-top: 1px; vertical-align: top; }';
			echo '.tablenav .actions { overflow: visible; }'; // this is important so that the selectize options aren't hidden
			echo '</style>';
		}
	}

	/**
	 * Renders group actions in the users table's extra_tablenav().
	 */
	public static function restrict_manage_users() {

		global $pagenow, $wpdb;

		$output = '';

		if ( ( $pagenow == 'users.php' ) && empty( $_GET['page'] ) ) {
			$group_table = _groups_get_tablename( "group" );
			// groups select
			$groups_table = _groups_get_tablename( 'group' );
			if ( $groups = $wpdb->get_results( "SELECT * FROM $groups_table ORDER BY name" ) ) {
				$groups_select = sprintf(
					'<select id="user-groups" class="groups" name="group_ids[]" multiple="multiple" placeholder="%s" data-placeholder="%s">',
					esc_attr( __( 'Choose groups &hellip;', GROUPS_PLUGIN_DOMAIN ) ) ,
					esc_attr( __( 'Choose groups &hellip;', GROUPS_PLUGIN_DOMAIN ) )
				);
				foreach( $groups as $group ) {
					$is_member = false;
					$groups_select .= sprintf( '<option value="%d" %s>%s</option>', Groups_Utility::id( $group->group_id ), $is_member ? ' selected="selected" ' : '', wp_filter_nohtml_kses( $group->name ) );
				}
				$groups_select .= '</select>';

			}

			// group bulk actions added through extra_tablenav()
			$box = '<div id="group-bulk-actions" class="groups-bulk-container">';
			$box .= '<div class="groups-select-container">';
			$box .= $groups_select;
			$box .= '</div>';
			$box .= '<select class="groups-action" name="groups-action">';
			$box .= '<option selected="selected" value="-1">' . __( 'Group Actions', GROUPS_PLUGIN_DOMAIN ) . '</option>';
			$box .= '<option value="add-group">' . __( 'Add to group', GROUPS_PLUGIN_DOMAIN ) . '</option>';
			$box .= '<option value="remove-group">' . __( 'Remove from group', GROUPS_PLUGIN_DOMAIN ) . '</option>';
			$box .= '</select>';
			$box .= sprintf( '<input class="button" type="submit" name="groups" value="%s" />', __( 'Apply', GROUPS_PLUGIN_DOMAIN ) );
			$box .= '</div>';
			$box = str_replace( '"', "'", $box );

			$nonce = wp_nonce_field( 'user-group', 'bulk-user-group-nonce', true, false );
			$nonce = str_replace( '"', "'", $nonce );
			$box .= $nonce;

			$box .= '<script type="text/javascript">';
			$box .= 'if ( typeof jQuery !== "undefined" ) {';
			$box .= 'jQuery("document").ready(function(){';
			$box .= 'jQuery(".tablenav.top .alignleft.actions:last").after("<div id=\"groups-bulk-actions-block\" class=\"alignleft actions\"></div>");';
			$box .= 'jQuery("#group-bulk-actions").appendTo(jQuery("#groups-bulk-actions-block"));';
			$box .= '});';
			$box .= '}';
			$box .= '</script>';

			$output .= $box;
			$output .= Groups_UIE::render_select( '#user-groups' );
		}
		echo $output;
	}

	/**
	 * Hooked on filter in class-wp-list-table.php to add links that
	 * filter by group.
	 * @param array $views
	 */
	public static function views_users( $views ) {
		global $pagenow, $wpdb;
		if ( ( $pagenow == 'users.php' ) && empty( $_GET['page'] ) ) {
			$group_table = _groups_get_tablename( "group" );
			$user_group_table = _groups_get_tablename( "user_group" );
			$groups = $wpdb->get_results( "SELECT * FROM $group_table ORDER BY name" );
			foreach( $groups as $group ) {
				$group = new Groups_Group( $group->group_id );
				// Do not use $user_count = count( $group->users ); here,
				// as it creates a lot of unneccessary objects and can lead
				// to out of memory issues on large user bases.
				$user_count = $wpdb->get_var( $wpdb->prepare(
					"SELECT COUNT(user_id) FROM $user_group_table WHERE group_id = %d",
					Groups_Utility::id( $group->group_id ) ) );
				$views[] = sprintf(
					'<a class="group" href="%s" title="%s">%s</a>',
					esc_url( add_query_arg( 'group', $group->group_id, admin_url( 'users.php' ) ) ),
					sprintf( '%s Group', wp_filter_nohtml_kses( $group->name ) ),
					sprintf( '%s <span class="count">(%s)</span>', wp_filter_nohtml_kses( $group->name ), $user_count )
				);
			}
		}
		return $views;
	}

	/**
	 * Adds or removes users to/from groups.
	 */
	public static function load_users() {
		if ( current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
			$users = isset( $_REQUEST['users'] ) ? $_REQUEST['users'] : null;
			$action = null;
			if ( !empty( $_REQUEST['groups'] ) ) {
				if ( $_GET['groups-action'] == "add-group" ) {
					$action = 'add';
				} else if ( $_GET['groups-action'] == "remove-group" ) {
					$action = 'remove';
				}
			}
			if ( $users !== null && $action !== null ) {
				if ( wp_verify_nonce( $_REQUEST['bulk-user-group-nonce'], 'user-group' ) ) {
					foreach( $users as $user_id ) {
						switch ( $action ) {
							case 'add':
								$group_ids = isset( $_GET['group_ids'] ) ? $_GET['group_ids'] : null;
								if ( $group_ids !== null ) {
									foreach ( $group_ids as $group_id ) {
										if ( !Groups_User_Group::read( $user_id, $group_id ) ) {
											Groups_User_Group::create(
												array(
													'user_id' => $user_id,
													'group_id' => $group_id
												)
											);
										}
									}
								}
								break;
							case 'remove':
								$group_ids = isset( $_GET['group_ids'] ) ? $_GET['group_ids'] : null;
								if ( $group_ids !== null ) {
									foreach ( $group_ids as $group_id ) {
										if ( Groups_User_Group::read( $user_id, $group_id ) ) {
											Groups_User_Group::delete( $user_id, $group_id );
										}
									}
								}
								break;
						}
					}
					$referer = wp_get_referer();
					if ( $referer ) {
						$redirect_to = remove_query_arg( array( 'action', 'action2', 'add-to-group', 'bulk-user-group-nonce', 'group_id', 'new_role', 'remove-from-group', 'users' ), $referer );
						wp_redirect( $redirect_to );
						exit;
					}
				}
			}
		}
	}

	/**
	 * Adds a new column to the users table to show the groups that users
	 * belong to.
	 * 
	 * @param array $column_headers
	 * @return array column headers
	 */
	public static function manage_users_columns( $column_headers ) {
		$column_headers[self::GROUPS] = __( 'Groups', GROUPS_PLUGIN_DOMAIN );
		return $column_headers;
	}

	/**
	 * Renders custom column content.
	 * 
	 * @param string $output 
	 * @param string $column_name
	 * @param int $user_id
	 * @return string custom column content
	 */
	public static function manage_users_custom_column( $output, $column_name, $user_id ) {
		switch ( $column_name ) {
			case self::GROUPS :
				$groups_user = new Groups_User( $user_id );
				$groups = $groups_user->groups;
				if ( count( $groups ) > 0 ) {
					usort( $groups, array( __CLASS__, 'by_group_name' ) );
					$output = '<ul>';
					foreach( $groups as $group ) {
						$output .= '<li>';
						$output .= wp_filter_nohtml_kses( $group->name );
						$output .= '</li>';
					}
					$output .= '</ul>';
				} else {
					$output .= __( '--', GROUPS_PLUGIN_DOMAIN );
				}
				break;
		}
		return $output;
	}

	/**
	 * usort helper
	 * @param Groups_Group $o1
	 * @param Groups_Group $o2
	 * @return int strcmp result for group names
	 */
	public static function by_group_name( $o1, $o2 ) {
		return strcmp( $o1->name, $o2->name );
	}
}
Groups_Admin_Users::init();
