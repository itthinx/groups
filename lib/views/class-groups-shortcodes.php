<?php
/**
 * class-groups-shortcodes.php
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
 * Shortcode handlers
 */
class Groups_Shortcodes {

	/**
	 * Adds shortcodes.
	 */
	public static function init() {
		// login
		add_shortcode( 'groups_login', array( __CLASS__, 'groups_login' ) );
		// logout
		add_shortcode( 'groups_logout', array( __CLASS__, 'groups_logout' ) );
		// group info
		add_shortcode( 'groups_group_info', array( __CLASS__, 'groups_group_info' ) );
		// user groups
		add_shortcode( 'groups_user_groups', array( __CLASS__, 'groups_user_groups' ) );
		// groups
		add_shortcode( 'groups_groups',  array( __CLASS__, 'groups_groups' ) );
		// join a group
		add_shortcode( 'groups_join',  array( __CLASS__, 'groups_join' ) );
		// leave a group
		add_shortcode( 'groups_leave',  array( __CLASS__, 'groups_leave' ) );
	}

	/**
	 * Renders the Groups login form.
	 * 
	 * The user is redirected to the current page after login by default.
	 * The user can be redirected to a specific URL after login by
	 * indicating the <code>redirect</code> attribute.
	 *
	 * @param array $atts
	 * @param string $content
	 * @return string the rendered form or empty
	 */
	public static function groups_login( $atts, $content = null ) {
		$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		extract(
			shortcode_atts(
				array(
					'redirect'        => $current_url,
					'show_logout'     => 'no'
				),
				$atts
			)
		);
		$redirect    = trim( $redirect );
		$show_logout = trim( strtolower( $show_logout ) );
		$output      = '';
		if ( !is_user_logged_in() ) {
			$output .= wp_login_form(
				array(
					'echo'     => false,
					'redirect' => $redirect
				)
			);
		} else {
			if ( $show_logout == 'yes' ) {
				$output .= self::groups_logout(
					array(
						'redirect' => $redirect
					)
				);
			}
		}
		return $output;
	}

	/**
	 * Renders the Groups logout link.
	 * 
	 * The link is rendered if the user is logged in.
	 * The user is redirected to the current page after logout by default.
	 * The user can be redirected to a specific URL after logout by
	 * indicating the <code>redirect</code> attribute.
	 *
	 * @param array $atts
	 * @param string $content not used
	 * @return string logout link, is empty if not logged in
	 */
	public static function groups_logout( $atts, $content = null ) {
		$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		extract(
			shortcode_atts(
				array(
					'redirect' => $current_url
				),
				$atts
			)
		);
		$redirect = trim( $redirect );
		$output   = '';
		if ( is_user_logged_in() ) {
			$output .= sprintf( '<a href="%s">', esc_url( wp_logout_url( $redirect ) ) );
			$output .= __( 'Log out', GROUPS_PLUGIN_DOMAIN );
			$output .= '</a>';
		}
		return $output;
	}

	/**
	 * Renders information about a group.
	 * Attributes:
	 * - "group"  : group name or id
	 * - "show"   : what to show, can be "name", "description", "count"
	 * - "format" :
	 * - "single" : used with show="count", single form, defaults to '1'
	 * - "plural" : used with show="count", plural form, defaults to '%d', must contain %d to show number
	 * 
	 * @param array $atts attributes
	 * @param string $content content to render
	 * @return rendered information
	 */
	public static function groups_group_info( $atts, $content = null ) {
		global $wpdb;
		$output = "";
		$options = shortcode_atts(
			array(
				'group' => '',
				'show' => '',
				'format' => '',
				'single' => '1',
				'plural' => '%d'
			),
			$atts
		);
		$group = trim( $options['group'] );
		$current_group = Groups_Group::read( $group );
		if ( !$current_group ) {
			$current_group = Groups_Group::read_by_name( $group );
		}
		if ( $current_group ) {
			switch( $options['show'] ) {
				case 'name' :
					$output .= wp_filter_nohtml_kses( $current_group->name );
					break;
				case 'description' :
					$output .= wp_filter_nohtml_kses( $current_group->description );
					break;
				case 'count' :
					$user_group_table = _groups_get_tablename( "user_group" );
					$count = $wpdb->get_var( $wpdb->prepare(
						"SELECT COUNT(*) FROM $user_group_table WHERE group_id = %d",
						Groups_Utility::id( $current_group->group_id )
					) );
					if ( $count === null ) {
						$count = 0;
					} else {
						$count = intval( $count );
					}
					$output .= _n( $options['single'], sprintf( $options['plural'], $count ), $count, GROUPS_PLUGIN_DOMAIN );
					break;
				// @todo experimental - could use pagination, sorting, link to profile, ...
				case 'users' :
					$user_group_table = _groups_get_tablename( "user_group" );
					$users = $wpdb->get_results( $wpdb->prepare(
						"SELECT * FROM $wpdb->users LEFT JOIN $user_group_table ON $wpdb->users.ID = $user_group_table.user_id WHERE $user_group_table.group_id = %d",
						Groups_Utility::id( $current_group->group_id )
					) );
					if ( $users ) {
						$output .= '<ul>';
						foreach( $users as $user ) {
							$output .= '<li>' . wp_filter_nohtml_kses( $user->user_login ) . '</li>';
						}
						$output .= '</ul>';
					}

					break;
			}
		}
		return $output;
	}

	/**
	 * Renders the current or a specific user's groups.
	 * Attributes:
	 * - "user_id" OR "user_login" OR "user_email" to identify the user, if none given assumes the current user
	 * - "format" : one of "list" "div" "ul" or "ol" - "list" and "ul" are equivalent
	 * - "list_class" : defaults to "groups"
	 * - "item_class" : defaults to "name"
	 * - "order_by"   : defaults to "name", also accepts "group_id"
	 * - "order"      : default to "ASC", also accepts "asc", "desc" and "DESC"
	 * - "leavebutton": Set to true/1/whatever to add a "leave group"-button next to each listed group
	 * 
	 * @param array $atts attributes
	 * @param string $content not used
	 * @return rendered groups for current user
	 */
	public static function groups_user_groups( $atts, $content = null ) {
		$output = "";
		$options = shortcode_atts(
			array(
				'user_id' => null,
				'user_login' => null,
				'user_email' => null,
				'format' => 'list',
				'list_class' => 'groups',
				'item_class' => 'name',
				'order_by' => 'name',
				'order' => 'ASC',
				'group' => null,
				'leavebutton' => null,
				'submit_text'       => __( 'Leave the %s group', GROUPS_PLUGIN_DOMAIN ),
				'exclude_group' => null
			),
			$atts
		);
		$user_id = null;
		if ( $options['user_id'] !== null ) {
			if ( $user = get_user_by( 'id', $options['user_id'] ) ) {
				$user_id = $user->ID;
			}
		} else if ( $options['user_id'] !== null ) {
			if ( $user = get_user_by( 'login', $options['user_login'] ) ) {
				$user_id = $user->ID;
			}
		} else if ( $options['user_email'] !== null ) {
			if ( $user = get_user_by( 'email', $options['user_login'] ) ) {
				$user_id = $user->ID;
			}
		}
		if ( $user_id === null ) {
			$user_id = get_current_user_id();
		}
		if ( $user_id !== null ) {
			$user = new Groups_User( $user_id );
			$groups = $user->groups;

			if ( !empty( $groups ) ) {
			// group attr
				if ( $options['group'] !== null ) {
					$groups = array();
					$groups_incl = explode( ",", $options['group'] );
					foreach ( $groups_incl as $group_incl ) {
						$group = trim( $group_incl );
						$current_group = Groups_Group::read( $group );
						if ( !$current_group ) {
							$current_group = Groups_Group::read_by_name( $group );
						}
						if ( $current_group ) {
							if ( Groups_User_Group::read( $user_id, $current_group->group_id ) ) {
								$groups[] = $current_group;
							}
						}
					}
				}
				// exclude_group attr
				if ( $options['exclude_group'] !== null ) {
					$groups_excl = explode( ",", $options['exclude_group'] );
					foreach ( $groups_excl as $key => $group_excl ) {
						$group = trim( $group_excl );
						$current_group = Groups_Group::read( $group );
						if ( !$current_group ) {
							$current_group = Groups_Group::read_by_name( $group );
						}
						if ( $current_group ) {
							$groups_excl[$key] = $current_group->group_id;
						} else {
							unset( $groups_excl[$key] );
						}
					}
					foreach ( $groups as $key => $group ) {
						if ( in_array( $group->group_id, $groups_excl ) ) {
							unset( $groups[$key] );
						}
					}
				}
				switch( $options['order_by'] ) {
					case 'group_id' :
						usort( $groups, array( __CLASS__, 'sort_id' ) );
						break;
					default :
						usort( $groups, array( __CLASS__, 'sort_name' ) );
				}
				switch( $options['order'] ) {
					case 'desc' :
					case 'DESC' :
						$groups = array_reverse( $groups );
						break;
				}

				switch( $options['format'] ) {
					case 'list' :
					case 'ul' :
						$output .= '<ul class="' . esc_attr( $options['list_class'] ) . '">';
						break;
					case 'ol' :
						$output .= '<ol class="' . esc_attr( $options['list_class'] ) . '">';
						break;
					default :
						$output .= '<div class="' . esc_attr( $options['list_class'] ) . '">';
				}


				// check if we're asked to leave a group
                                $submitted     = false;
                                $invalid_nonce = false;

				$specific_nonce = 'nonce_leave-' . $_POST['group_id'];
				$nonce_action = 'groups_action';

                                if ( !empty( $_POST['groups_action'] ) && $_POST['groups_action'] == 'leave' ) {
                                        $submitted = true;
                                        if ( !wp_verify_nonce( $_POST[$specific_nonce], $nonce_action ) ) {
                                                $invalid_nonce = true;
                                        }
                                }
                                if ( $submitted && !$invalid_nonce ) {
					$users_groups_ids[] = $_POST['group_id'];
				}

				// check if we're asked to join a group
                                $submitted     = false;
                                $invalid_nonce = false;

                                $specific_nonce = 'nonce_join-' . $_POST['group_id'];
                                $nonce_action = 'groups_action';

                                if ( !empty( $_POST['groups_action'] ) && $_POST['groups_action'] == 'join' ) {
                                        $submitted = true;
                                        if ( !wp_verify_nonce( $_POST[$specific_nonce], $nonce_action ) ) {
                                                $invalid_nonce = true;
                                        }
                                }
                                if ( $submitted && !$invalid_nonce ) {
					$groups[] = Groups_Group::read( $_POST['group_id'] );
                                }

				// Loop through the groups
				foreach( $groups as $group ) {
   						if (in_array($group->group_id,$users_groups_ids))
	                                                continue; // skip to next group in complete array if user left this group now

					if ($options['leavebutton'] ) {
	                                	// Build the leave-button
						$submit_text = sprintf( $options['submit_text'], wp_filter_nohtml_kses( $group->name ) );
                				$nonce_action = 'groups_action';
                				$nonce        = 'nonce_leave-' . $group->group_id;
                                        	$leavebutton = '<div class="groups-join">';
                                        	$leavebutton .= '<form action="#" method="post">';
                                        	$leavebutton .= '<input type="hidden" name="groups_action" value="leave" />';
                                        	$leavebutton .= '<input type="hidden" name="group_id" value="' . esc_attr( $group->group_id ) . '" />';
                                        	$leavebutton .= '<input type="submit" value="' . $submit_text . '" />';
                                        	$leavebutton .=  wp_nonce_field( $nonce_action, $nonce, true, false );
						$leavebutton .= " -- NONCE: nonce_action: " . $nonce_action . ",  nonce: " . $nonce . "<br>";
                                        	$leavebutton .= '</form>';
                                        	$leavebutton .= '</div>';
					}

					switch( $options['format'] ) {
						case 'list' :
						case 'ul' :
						case 'ol' :
							$output .= '<li class="' . esc_attr( $options['item_class'] ) . '">' . $group->name . $leavebutton . '</li>';
							break;
						default :
							$output .= '<div class="' . esc_attr( $options['item_class'] ) . '">' . $group->name . $leavebutton . '</div>';
					}
				}
				switch( $options['format'] ) {
					case 'list' :
					case 'ul' :
						$output .= '</ul>';
						break;
					case 'ol' :
						$output .= '</ol>';
						break;
					default :
						$output .= '</div>';
				}
			}
		}
		return $output;
	}

	/**
	 * Group comparison by group_id.
	 *
	 * @param Groups_Group $a
	 * @param Groups_Group $b
	 * @return int
	 */
	public static function sort_id( $a, $b ) {
		return $a->group_id - $b->group_id;
	}

	/**
	 * Group comparison by name.
	 * 
	 * @param Groups_Group $a
	 * @param Groups_Group $b
	 * @return int
	 */
	public static function sort_name( $a, $b ) {
		return strcmp( $a->name, $b->name );
	}

	/**
	 * Renders a list of the site's groups.
	 * Attributes:
	 * - "format" : one of "list" "div" "ul" or "ol" - "list" and "ul" are equivalent
	 * - "list_class" : defaults to "groups"
	 * - "item_class" : defaults to "name"
	 * - "order_by"   : defaults to "name", also accepts "group_id"
	 * - "order"      : default to "ASC", also accepts "asc", "desc" and "DESC"
	 * - "joinbutton" : set to true/1/whatever to add a "join group" button next to each group
	 * - "exclude_excisting" : set to true/1/whatever to not list groups the logged in user is already part of (most useful together with joinbutton)
	 *
	 * @param array $atts attributes
	 * @param string $content not used
	 * @return rendered groups
	 */
	public static function groups_groups( $atts, $content = null ) {
		global $wpdb;
		$output = "";
		$options = shortcode_atts(
			array(
				'format' => 'list',
				'list_class' => 'groups',
				'item_class' => 'name',
				'order_by' => 'name',
				'order' => 'ASC',
				'joinbutton' => null,
				'submit_text'       => __( 'Join the %s group', GROUPS_PLUGIN_DOMAIN ),
				'exclude_excisting' => null
			),
			$atts
		);
		switch( $options['order_by'] ) {
			case 'group_id' :
			case 'name' :
				$order_by = $options['order_by'];
				break;
			default :
				$order_by = 'name';
		}
		switch( $options['order'] ) {
			case 'asc' :
			case 'ASC' :
			case 'desc' :
			case 'DESC' :
				$order = strtoupper( $options['order'] );
				break;
			default :
				$order = 'ASC';
		}
		$group_table = _groups_get_tablename( "group" );
		if ( $groups = $wpdb->get_results(
			"SELECT group_id FROM $group_table ORDER BY $order_by $order"
		) ) {
			switch( $options['format'] ) {
				case 'list' :
				case 'ul' :
					$output .= '<ul class="' . esc_attr( $options['list_class'] ) . '">';
					break;
				case 'ol' :
					$output .= '<ol class="' . esc_attr( $options['list_class'] ) . '">';
					break;
				default :
					$output .= '<div class="' . esc_attr( $options['list_class'] ) . '">';
			}

			// Check if we're asked to skip groups the user is already member of
			if ($options['exclude_excisting']) {

				// Load users groups into an array
				$user_id = get_current_user_id();
	                        $user = new Groups_User( $user_id );
                        	$users_groups = $user->groups;

				// Build array with only group ids of users groups (probably a better way to do this)
				$users_groups_ids;
				foreach ($users_groups as $gruppe) {
					$users_groups_ids[] = $gruppe->group_id;
				}
			}

			// Check if we were  asked to join a group. If so, omit that group from the listing
                                $submitted     = false;
                                $invalid_nonce = false;

				$specific_nonce = 'nonce_join-' . $_POST['group_id'];
				$nonce_action = 'groups_action';

                                if ( !empty( $_POST['groups_action'] ) && $_POST['groups_action'] == 'join' ) {
                                        $submitted = true;
                                        if ( !wp_verify_nonce( $_POST[$specific_nonce], $nonce_action ) ) {
                                                $invalid_nonce = true;
                                        }
                                }
                                if ( $submitted && !$invalid_nonce ) {
					$users_groups_ids[] = $_POST['group_id'];
				}

			// Check if we're asked to leave a group. If so, include that group in this list
                                $submitted     = false;
                                $invalid_nonce = false;

                                $specific_nonce = 'nonce_leave-' . $_POST['group_id'];
                                $nonce_action = 'groups_action';

                                if ( !empty( $_POST['groups_action'] ) && $_POST['groups_action'] == 'leave' ) {
                                        $submitted = true;
                                        if ( !wp_verify_nonce( $_POST[$specific_nonce], $nonce_action ) ) {
                                                $invalid_nonce = true;
                                        }
                                }
                                if ( $submitted && !$invalid_nonce ) {
                                        $groups[] = Groups_Group::read( $_POST['group_id'] );
                                }

			foreach( $groups as $group ) {
				$group = new Groups_Group( $group->group_id );

				// Skip this group if it's in the exclude list
				// (Building the "id only" array above makes this process easier - but there's probably an even better way to do this)
				if ($options['exclude_excisting']) {
					if (in_array($group->group_id,$users_groups_ids))
						continue; // skip to next group in complete array if user is already member
				}

				// Build the join-button if we're asked to
				if ($options['joinbutton']) {
                                                $nonce_action = 'groups_action';
                                                $nonce        = 'nonce_join-' . $group->group_id;
					$submit_text = sprintf( $options['submit_text'], wp_filter_nohtml_kses( $group->name ) );
                                        $leavebutton = '<div class="groups-join">';
                                        $leavebutton .= '<form action="#" method="post">';
                                        $leavebutton .= '<input type="hidden" name="groups_action" value="join" />';
                                        $leavebutton .= '<input type="hidden" name="group_id" value="' . esc_attr( $group->group_id ) . '" />';
                                        $leavebutton .= '<input type="submit" value="' . $submit_text . '" />';
                                        $leavebutton .=  wp_nonce_field( $nonce_action, $nonce, true, false );
                                        $leavebutton .= '</form>';
                                        $leavebutton .= '</div>';
					}
				// End joinbutton

				switch( $options['format'] ) {
					case 'list' :
					case 'ul' :
					case 'ol' :
						$output .= '<li class="' . esc_attr( $options['item_class'] ) . '">' . $group->name . $leavebutton . '</li>';
						break;
					default :
						$output .= '<div class="' . esc_attr( $options['item_class'] ) . '">' . $group->name . $leavebutton . '</div>';
				}
			}
			switch( $options['format'] ) {
				case 'list' :
				case 'ul' :
					$output .= '</ul>';
					break;
				case 'ol' :
					$output .= '</ol>';
					break;
				default :
					$output .= '</div>';
			}
		}
		return $output;
	}

	/**
	 * Renders a form that lets a user join a group.
	 * * Attributes:
	 * - "group" : (required) group name or id
	 * 
	 * @param array $atts attributes
	 * @param string $content not used
	 */
	public static function groups_join( $atts, $content = null ) {

		$options = shortcode_atts(
			array(
				'group'             => '',
				'display_message'   => true,
				'display_is_member' => false,
				'submit_text'       => __( 'Join the %s group', GROUPS_PLUGIN_DOMAIN )
			),
			$atts
		);
		extract( $options );

                $output       = "";
                $nonce_action = 'groups_action';
                        if ( $_POST['group_id'] ) {
                                $nonce        = 'nonce_join-' . $_POST['group_id']; }
                        else {
                                $nonce        = 'nonce_join-' . $options['group']; }

		if ( $display_message === 'false' ) {
			$display_message = false;
		}
		if ( $display_is_member === 'true' ) {
			$display_is_member = true;
		}

		if ( $_POST['group_id'] ) {
			$group = $_POST['group_id'];
		}
		else {
			$group = trim( $options['group'] );
		}

		$current_group = Groups_Group::read( $group );
		if ( !$current_group ) {
			$current_group = Groups_Group::read_by_name( $group );
		}
		if ( $current_group ) {
			if ( $user_id = get_current_user_id() ) {
				$submitted     = false;
				$invalid_nonce = false;
				if ( !empty( $_POST['groups_action'] ) && $_POST['groups_action'] == 'join' ) {
					$submitted = true;
					if ( !wp_verify_nonce( $_POST[$nonce], $nonce_action ) ) {
						$invalid_nonce = true;
					}
				}
				if ( $submitted && !$invalid_nonce ) {
					// add user to group
					if ( isset( $_POST['group_id'] ) ) {
						$join_group = Groups_Group::read( $_POST['group_id'] );
						Groups_User_Group::create(
							array(
								'group_id' => $join_group->group_id,
								'user_id' => $user_id
							)
						);
					}
				}
				if ( !Groups_User_Group::read( $user_id, $current_group->group_id ) ) {
					$submit_text = sprintf( $options['submit_text'], wp_filter_nohtml_kses( $current_group->name ) );
					$output .= '<div class="groups-join">';
					$output .= '<form action="#" method="post">';
					$output .= '<input type="hidden" name="groups_action" value="join" />';
					$output .= '<input type="hidden" name="group_id" value="' . esc_attr( $current_group->group_id ) . '" />';
					$output .= '<input type="submit" value="' . $submit_text . '" />';
					$output .=  wp_nonce_field( $nonce_action, $nonce, true, false );
					$output .= '</form>';
					$output .= '</div>';
				} else if ( $display_message ) {
					if ( $submitted && !$invalid_nonce && isset( $join_group ) && $join_group->group_id === $current_group->group_id ) {
						$output .= '<div class="groups-join joined">';
						$output .= sprintf( __( 'You have joined the %s group.', GROUPS_PLUGIN_DOMAIN ), wp_filter_nohtml_kses( $join_group->name ) );
						$output .= '</div>';
					}
					else if ( $display_is_member && isset( $current_group ) && $current_group !== false ) {
						$output .= '<div class="groups-join member">';
						$output .= sprintf( __( 'You are a member of the %s group.', GROUPS_PLUGIN_DOMAIN ), wp_filter_nohtml_kses( $current_group->name ) );
						$output .= '</div>';
					}
				}
			}
		}
		return $output;
	}

	/**
	 * Renders a form that lets a user leave a group.
	 * * Attributes:
	 * - "group" : (required) group name or id
	 *
	 * @param array $atts attributes
	 * @param string $content not used
	 */
	public static function groups_leave( $atts, $content = null ) {

		$options = shortcode_atts(
			array(
				'group'           => '',
				'display_message' => true,
				'submit_text'     => __( 'Leave the %s group', GROUPS_PLUGIN_DOMAIN ),
			),
			$atts
		);
		extract( $options );

		$output       = "";
		$nonce_action = 'groups_action';
			if ( $_POST['group_id'] ) {
				$nonce        = 'nonce_leave-' . $_POST['group_id']; }
			else {
				$nonce        = 'nonce_leave-' . $options['group']; }

		if ( $display_message === 'false' ) {
			$display_message = false;
		}

		$group = trim( $options['group'] );
		$current_group = Groups_Group::read( $group );
		if ( !$current_group ) {
			$current_group = Groups_Group::read_by_name( $group );
		}
		if ( $current_group ) {
			if ( $user_id = get_current_user_id() ) {
				$submitted     = false;
				$invalid_nonce = false;
				if ( !empty( $_POST['groups_action'] ) && $_POST['groups_action'] == 'leave' ) {
					$submitted = true;
					if ( !wp_verify_nonce( $_POST[$nonce], $nonce_action ) ) {
						$invalid_nonce = true;
					}
				}
				if ( $submitted && !$invalid_nonce ) {
					// remove user from group
					if ( isset( $_POST['group_id'] ) ) {
						$leave_group = Groups_Group::read( $_POST['group_id'] );
						Groups_User_Group::delete( $user_id, $leave_group->group_id );
					}
				}
				if ( Groups_User_Group::read( $user_id, $current_group->group_id ) ) {
					$submit_text = sprintf( $options['submit_text'], wp_filter_nohtml_kses( $current_group->name ) );
					$output .= '<div class="groups-join">';
					$output .= '<form action="#" method="post">';
					$output .= '<input type="hidden" name="groups_action" value="leave" />';
					$output .= '<input type="hidden" name="group_id" value="' . esc_attr( $current_group->group_id ) . '" />';
					$output .= '<input type="submit" value="' . $submit_text . '" />';
					$output .=  wp_nonce_field( $nonce_action, $nonce, true, false );
					$output .= '</form>';
					$output .= '</div>';
				} else if ( $display_message ) {
					if ( $submitted && !$invalid_nonce && isset( $leave_group ) && $leave_group->group_id === $current_group->group_id ) {
						$output .= '<div class="groups-join left">';
						$output .= sprintf( __( 'You have left the %s group.', GROUPS_PLUGIN_DOMAIN ), wp_filter_nohtml_kses( $leave_group->name ) );
						$output .= '</div>';
					}
				}
			}
		}
		return $output;
	}
}
Groups_Shortcodes::init();
