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

// phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.WP.AlternativeFunctions.rand_rand, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

/**
 * Shortcode handlers
 */
class Groups_Shortcodes {

	/**
	 * Maximum amount of time to accept hashes for join and leave requests.
	 *
	 * @var int
	 */
	const MAX_TIME_DELTA = 3600;

	/**
	 * Hashed content map.
	 *
	 * @since 3.11.0
	 *
	 * @var array
	 */
	private static $map = array();

	/**
	 * During preprocessing.
	 *
	 * @since 3.11.0
	 *
	 * @var boolean
	 */
	private static $preprocessing = false;

	/**
	 * Shortcode queue.
	 *
	 * @since 4.7.0
	 *
	 * @var array
	 */
	private static $shortcode_queue = array();

	/**
	 * Widgets contents.
	 *
	 * @since 4.7.1
	 *
	 * @var array
	 */
	private static $widgets_contents = array();

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
		add_shortcode( 'groups_groups', array( __CLASS__, 'groups_groups' ) );
		// join a group
		add_shortcode( 'groups_join', array( __CLASS__, 'groups_join' ) );
		// leave a group
		add_shortcode( 'groups_leave', array( __CLASS__, 'groups_leave' ) );
		// @since 3.11.0 content preprocessing
		add_filter( 'pre_render_block', array( __CLASS__, 'pre_render_block' ), 0, 3 );
		// @since 3.11.0 map processing
		add_filter( 'render_block', array( __CLASS__, 'render_block' ), 0, 3 );
		// @since 4.7.0 shortcode queue ops
		add_filter( 'pre_do_shortcode_tag', array( __CLASS__, 'pre_do_shortcode_tag' ), PHP_INT_MAX, 4 );
		add_filter( 'do_shortcode_tag', array( __CLASS__, 'do_shortcode_tag' ), PHP_INT_MAX, 4 );
		// @since 4.7.1 shortcodes in widgets
		add_filter( 'widget_display_callback', array( __CLASS__, 'widget_display_callback' ), PHP_INT_MAX, 3 );
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
	 *
	 * @return string the rendered form or empty
	 */
	public static function groups_login( $atts, $content = null ) {

		if ( !self::validate( 'groups_login', $atts, $content ) ) {
			return '';
		}

		$current_url = groups_get_current_url();
		$atts = shortcode_atts(
			array(
				'redirect'    => $current_url,
				'show_logout' => 'no'
			),
			$atts
		);
		$redirect    = isset( $atts['redirect'] ) ? trim( $atts['redirect'] ) : $current_url;
		$show_logout = isset( $atts['show_logout'] ) ? trim( strtolower( $atts['show_logout'] ) ) : 'no';
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
		return $output; // nosemgrep audit.php.wp.security.sqli.shortcode-attr, audit.php.wp.security.xss.shortcode-attr
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
	 *
	 * @return string logout link, is empty if not logged in
	 */
	public static function groups_logout( $atts, $content = null ) {

		if ( !self::validate( 'groups_logout', $atts, $content ) ) {
			return '';
		}

		$current_url = groups_get_current_url();
		$atts = shortcode_atts(
			array(
				'redirect' => $current_url
			),
			$atts
		);
		$redirect = isset( $atts['redirect'] ) ? trim( $atts['redirect'] ) : $current_url;
		$output   = '';
		if ( is_user_logged_in() ) {
			$output .= sprintf( '<a href="%s">', esc_url( wp_logout_url( $redirect ) ) );
			$output .= esc_html__( 'Log out', 'groups' );
			$output .= '</a>';
		}
		return $output;
	}

	/**
	 * Renders information about a group.
	 *
	 * Attributes:
	 * - "group"  : group name or id
	 * - "show"   : what to show, can be "name", "description", "count"
	 * - "format" :
	 * - "single" : used with show="count", single form, defaults to '1'
	 * - "plural" : used with show="count", plural form, defaults to '%d', must contain %d to show number
	 *
	 * @param array $atts attributes
	 * @param string $content content to render
	 *
	 * @return string rendered information
	 */
	public static function groups_group_info( $atts, $content = null ) {

		global $wpdb;

		if ( !self::validate( 'groups_group_info', $atts, $content ) ) {
			return '';
		}

		$output = '';
		$options = shortcode_atts(
			array(
				'group'  => '',
				'show'   => '',
				'format' => '',
				'none'   => '0',
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
			switch ( $options['show'] ) {
				case 'name' :
					$output .= wp_filter_nohtml_kses( $current_group->name );
					break;
				case 'description' :
					$output .= wp_filter_nohtml_kses( $current_group->description );
					break;
				case 'count' :
					$user_group_table = _groups_get_tablename( 'user_group' );
					$count = $wpdb->get_var( $wpdb->prepare(
						"SELECT COUNT(*) FROM $user_group_table WHERE group_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						Groups_Utility::id( $current_group->group_id )
					) );
					if ( $count === null ) {
						$count = 0;
					} else {
						$count = intval( $count );
					}
					switch ( $count ) {
						case 0:
							$output .= wp_kses_post( $options['none'] );
							break;
						case 1:
							$output .= wp_kses_post( $options['single'] );
							break;
						default:
							$output .= wp_kses_post( sprintf( $options['plural'], $count ) );
					}
					break;
				case 'users' :
					// Renders a basic user list, do not extend. For more detailed information,
					// create a separate shortcode that could use pagination, sorting, link to profile, ...
					$user_group_table = _groups_get_tablename( 'user_group' );
					$users = $wpdb->get_results( $wpdb->prepare(
						"SELECT * FROM $wpdb->users LEFT JOIN $user_group_table ON $wpdb->users.ID = $user_group_table.user_id WHERE $user_group_table.group_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						Groups_Utility::id( $current_group->group_id )
					) );
					if ( $users ) {
						$output .= '<ul>';
						foreach ( $users as $user ) {
							$display_name = !empty( $user->display_name ) ? $user->display_name : $user->user_login;
							$output .= '<li>' . wp_filter_nohtml_kses( $display_name ) . '</li>';
						}
						$output .= '</ul>';
					}
					break;
			}
		}
		return $output; // nosemgrep audit.php.wp.security.sqli.shortcode-attr, audit.php.wp.security.xss.shortcode-attr
	}

	/**
	 * Renders the current or a specific user's groups.
	 *
	 * Attributes:
	 * - "user_id" OR "user_login" OR "user_email" to identify the user, if none given assumes the current user
	 * - "format" : one of "list" "div" "ul" or "ol" - "list" and "ul" are equivalent
	 * - "list_class" : defaults to "groups"
	 * - "item_class" : defaults to "name"
	 * - "order_by"   : defaults to "name", also accepts "group_id"
	 * - "order"      : default to "ASC", also accepts "asc", "desc" and "DESC"
	 *
	 * @param array $atts attributes
	 * @param string $content not used
	 *
	 * @return string rendered groups for current user
	 */
	public static function groups_user_groups( $atts, $content = null ) {

		if ( !self::validate( 'groups_user_groups', $atts, $content ) ) {
			return '';
		}

		$output = '';
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
			$groups = $user->get_groups();

			if ( !empty( $groups ) ) {
				// group attr
				if ( $options['group'] !== null ) {
					$groups = array();
					$groups_incl = explode( ',', $options['group'] );
					foreach ( $groups_incl as $group_incl ) {
						$group = trim( $group_incl );
						$current_group = Groups_Group::read( $group );
						if ( !$current_group ) {
							$current_group = Groups_Group::read_by_name( $group );
						}
						if ( $current_group ) {
							if ( Groups_User::user_is_member( $user_id, $current_group->group_id ) ) {
								$groups[] = $current_group;
							}
						}
					}
				}
				// exclude_group attr
				if ( $options['exclude_group'] !== null ) {
					$groups_excl = explode( ',', $options['exclude_group'] );
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
				switch ( $options['order_by'] ) {
					case 'group_id' :
						usort( $groups, array( __CLASS__, 'sort_id' ) );
						break;
					default :
						usort( $groups, array( __CLASS__, 'sort_name' ) );
				}
				switch ( $options['order'] ) {
					case 'desc' :
					case 'DESC' :
						$groups = array_reverse( $groups );
						break;
				}

				switch ( $options['format'] ) {
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
				foreach ( $groups as $group ) {
					switch ( $options['format'] ) {
						case 'list' :
						case 'ul' :
						case 'ol' :
							// @todo mixed assignments done above, unify to Groups_Group objects only
							$name = $group instanceof Groups_Group ? $group->get_name() : $group->name;
							$output .= '<li class="' . esc_attr( $options['item_class'] ) . '">' . stripslashes( esc_html( $name ) ) . '</li>';
							break;
						default :
							// @todo mixed assignments done above, unify to Groups_Group objects only
							$name = $group instanceof Groups_Group ? $group->get_name() : $group->name;
							$output .= '<div class="' . esc_attr( $options['item_class'] ) . '">' . stripslashes( esc_html( $name ) ) . '</div>';
					}
				}
				switch ( $options['format'] ) {
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
	 *
	 * @return int
	 */
	public static function sort_id( $a, $b ) {
		return $a->get_id() - $b->get_id();
	}

	/**
	 * Group comparison by name.
	 *
	 * @param Groups_Group $a
	 * @param Groups_Group $b
	 *
	 * @return int
	 */
	public static function sort_name( $a, $b ) {
		return strcmp( $a->get_name(), $b->get_name() );
	}

	/**
	 * Renders a list of the site's groups.
	 *
	 * Attributes:
	 * - "format" : one of "list" "div" "ul" or "ol" - "list" and "ul" are equivalent
	 * - "list_class" : defaults to "groups"
	 * - "item_class" : defaults to "name"
	 * - "order_by"   : defaults to "name", also accepts "group_id"
	 * - "order"      : default to "ASC", also accepts "asc", "desc" and "DESC"
	 *
	 * @param array $atts attributes
	 * @param string $content not used
	 *
	 * @return string rendered groups
	 */
	public static function groups_groups( $atts, $content = null ) {

		global $wpdb;

		if ( !self::validate( 'groups_groups', $atts, $content ) ) {
			return '';
		}

		$output = '';
		$options = shortcode_atts(
			array(
				'format' => 'list',
				'list_class' => 'groups',
				'item_class' => 'name',
				'order_by' => 'name',
				'order' => 'ASC'
			),
			$atts
		);
		switch ( $options['order_by'] ) {
			case 'group_id' :
			case 'name' :
				$order_by = $options['order_by'];
				break;
			default :
				$order_by = 'name';
		}
		switch ( $options['order'] ) {
			case 'asc' :
			case 'ASC' :
			case 'desc' :
			case 'DESC' :
				$order = strtoupper( $options['order'] );
				break;
			default :
				$order = 'ASC';
		}
		$group_table = _groups_get_tablename( 'group' );
		// nosemgrep: audit.php.wp.security.sqli.shortcode-attr
		$groups = $wpdb->get_results( "SELECT group_id FROM $group_table ORDER BY $order_by $order" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( is_array( $groups ) && count( $groups ) > 0 ) {
			switch ( $options['format'] ) {
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
			foreach ( $groups as $group ) {
				$group = new Groups_Group( $group->group_id );
				switch ( $options['format'] ) {
					case 'list' :
					case 'ul' :
					case 'ol' :
						$output .= '<li class="' . esc_attr( $options['item_class'] ) . '">' . stripslashes( esc_html( $group->get_name() ) ) . '</li>';
						break;
					default :
						$output .= '<div class="' . esc_attr( $options['item_class'] ) . '">' . stripslashes( esc_html( $group->get_name() ) ) . '</div>';
				}
			}
			switch ( $options['format'] ) {
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
	 *
	 * Attributes:
	 *
	 * - "group" : (required) group name or id
	 * - "class" : (optional) container class to add
	 * - "display_message" : (optional) whether to display the message
	 * - "display_is_member" : (optional) whether to display the message that a user is a member
	 * - "redirect" : (optional) whether to redirect after accepted submission
	 * - "submit_class" : (optional) submit HTML element class to add
	 * - "submit_text" : (optional) submit HTML element text to use
	 *
	 * @param array $atts attributes
	 * @param string $content not used
	 *
	 * @return string
	 */
	public static function groups_join( $atts, $content = null ) {

		global $groups_join_data_init, $post;

		if ( !self::validate( 'groups_join', $atts, $content ) ) {
			return '';
		}

		$nonce_action = 'groups_action';
		$nonce        = 'nonce_join';
		$output       = '';

		$options = shortcode_atts(
			array(
				'class'             => '',
				'group'             => '',
				'display_message'   => true,
				'display_is_member' => false,
				'redirect'          => true,
				'submit_class'      => '',
				/* translators: group name */
				'submit_text'       => esc_html__( 'Join the %s group', 'groups' )
			),
			$atts
		);

		$display_message = is_string( $options['display_message'] ) ? strtolower( $options['display_message'] ) : $options['display_message'];
		$display_is_member = is_string( $options['display_is_member'] ) ? strtolower( $options['display_is_member'] ) : $options['display_is_member'];
		$redirect = is_string( $options['redirect'] ) ? strtolower( $options['redirect'] ) : $options['redirect'];
		$submit_text = $options['submit_text'];
		$display_message = in_array( $display_message, array( 'true', 'yes', true ) );
		$display_is_member = in_array( $display_is_member, array( 'true', 'yes', true ) );

		if ( !is_bool( $redirect ) ) {
			switch ( $redirect ) {
				case 'true':
				case 'yes':
					$redirect = true;
					break;
				case 'false':
				case 'no':
					$redirect = false;
					break;
				default:
					if ( is_string( $redirect ) ) {
						$redirect = trim( $redirect );
						if ( strlen( $redirect ) === 0 ) {
							$redirect = true;
						}
					} else {
						$redirect = true;
					}
			}
		}

		$class = trim( $options['class'] );
		$submit_class = trim( $options['submit_class'] );
		$group = trim( $options['group'] );
		$current_group = Groups_Group::read( $group );
		if ( !$current_group ) {
			$current_group = Groups_Group::read_by_name( $group );
		}
		// bail out if no valid group
		if ( !$current_group ) {
			return '';
		}

		// @since 3.11.0 Restrict the functionality to authors with appropriate permission
		$author_can_restrict_group_ids = array();
		$author_id = isset( $post ) && !empty( $post->post_author ) ? $post->post_author : get_the_author_meta( 'ID' );
		$author_id = is_numeric( $author_id ) ? intval( $author_id ) : null;
		if ( $author_id !== null ) {
			$author = new Groups_User( $author_id );
			if ( $author->can( GROUPS_RESTRICT_ACCESS ) ) {
				if ( $author->can( GROUPS_ADMINISTER_GROUPS ) ) {
					$author_can_restrict_group_ids = Groups_Group::get_group_ids();
				} else {
					$author_can_restrict_group_ids = $author->get_group_ids_deep();
				}
			}
		}
		if ( !in_array( $current_group->group_id, $author_can_restrict_group_ids ) ) {
			return '';
		}

		if ( $current_group ) {
			if ( $user_id = get_current_user_id() ) {
				$joined        = false;
				$submitted     = false;
				$invalid_nonce = false;
				if ( groups_sanitize_post( 'groups_action' ) === 'join' ) {
					$submitted = true;
					// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					if ( !groups_verify_post_nonce( $nonce, $nonce_action ) ) { // nosemgrep: scanner.php.wp.security.csrf.nonce-check-not-dying
						$invalid_nonce = true;
					}
				}
				if ( $submitted && !$invalid_nonce ) {
					// add user to group
					if ( isset( $_POST['groups-join-data'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
						$hash = trim( groups_sanitize_post( 'groups-join-data' ) );
						$groups_join_data = get_user_meta( $user_id, 'groups-join-data', true );
						if ( is_array( $groups_join_data ) && isset( $groups_join_data[$hash] ) ) {
							if ( isset( $groups_join_data[$hash]['group_id'] ) && isset( $groups_join_data[$hash]['time'] ) ) {
								$group_id = $groups_join_data[$hash]['group_id'];
								$dt = time() - $groups_join_data[$hash]['time'];
								if ( $dt < apply_filters( 'groups_join_submit_max_time_delta', self::MAX_TIME_DELTA ) ) {
									$joined = Groups_User_Group::create(
										array(
											'group_id' => $group_id,
											'user_id' => $user_id
										)
									);
									if ( $joined ) {
										/**
										 * Whether to redirect after submit and successful addition to group.
										 *
										 * @since 3.6.0
										 *
										 * @param boolean|string $redirect true to redirect to current ULR, string to redirect to specific URL, false not to redirect
										 * @param array $atts shortcode attributes
										 * @param array $options evaluated shortcode options
										 *
										 * @return boolean|string
										 */
										if ( apply_filters( 'groups_join_submit_redirect', $redirect, $atts, $options ) !== false ) {
											self::maybe_redirect( $redirect );
										}
									}
								}
							}
						}
					}
				}
				if ( !Groups_User::user_is_member( $user_id, $current_group->group_id ) ) {
					if ( !isset( $groups_join_data_init ) ) {
						$groups_join_data_init = true;
						delete_user_meta( $user_id, 'groups-join-data' );
					}
					$data = array(
						'user_id'  => $user_id,
						'group_id' => $current_group->group_id,
						'time'     => time(),
						'salt'     => rand( 0, PHP_INT_MAX )
					);
					$hash = hash( 'sha256', json_encode( $data ) );
					$groups_join_data = get_user_meta( $user_id, 'groups-join-data', true );
					if ( !is_array( $groups_join_data ) ) {
						$groups_join_data = array();
					}
					$groups_join_data[$hash] = $data;
					update_user_meta( $user_id, 'groups-join-data', $groups_join_data );

					$submit_text = sprintf( $options['submit_text'], stripslashes( wp_filter_nohtml_kses( $current_group->name ) ) );
					$output .= sprintf(
						'<div class="groups-join%s">',
						strlen( $class ) > 0 ? ' ' . esc_attr( $class ) : '',
					);
					$output .= '<form action="#" method="post">';
					$output .= '<input type="hidden" name="groups_action" value="join" />';
					$output .= '<input type="hidden" name="groups-join-data" value="' . esc_attr( $hash ) . '" />';
					$output .= sprintf(
						'<input class="groups-join-submit%s" type="submit" value="%s" />',
						strlen( $submit_class ) > 0 ? ' ' . esc_attr( $submit_class ) : '',
						esc_attr( $submit_text )
					);
					$output .= wp_nonce_field( $nonce_action, $nonce, true, false );
					$output .= '</form>';
					$output .= '</div>';
				} else if ( $display_message ) {
					if ( $joined ) {
						$output .= '<div class="groups-join joined">';
						/* translators: group name */
						$output .= sprintf( esc_html__( 'You have joined the %s group.', 'groups' ), stripslashes( wp_filter_nohtml_kses( $current_group->name ) ) );
						$output .= '</div>';
					} else if ( $display_is_member && $current_group !== false ) {
						$output .= '<div class="groups-join member">';
						/* translators: group name */
						$output .= sprintf( esc_html__( 'You are a member of the %s group.', 'groups' ), stripslashes( wp_filter_nohtml_kses( $current_group->name ) ) );
						$output .= '</div>';
					}
				}
			}
		}

		if ( self::$preprocessing ) {
			// surround content with hashmarks
			// <!-- groups:{hash} -->{content}<!-- /groups:{hash} -->
			$hash = md5( $output );
			$prefix = sprintf( '<!-- groups:%s -->', $hash );
			$suffix = sprintf( '<!-- /groups:%s -->', $hash );
			self::$map[$hash] = array(
				'prefix' => $prefix,
				'suffix' => $suffix,
				'content' => $output
			);

			$output = sprintf(
				'%s%s%s',
				$prefix,
				$output,
				$suffix
			);
		}

		return $output;
	}

	/**
	 * Renders a form that lets a user leave a group.
	 *
	 * Attributes:
	 *
	 * - "group" : (required) group name or id
	 * - "class" : (optional) container class to add
	 * - "display_message" : (optional) whether to display the message
	 * - "redirect" : (optional) whether to redirect after accepted submission
	 * - "submit_class" : (optional) submit HTML element class to add
	 * - "submit_text" : (optional) submit HTML element text to use
	 *
	 * @param array $atts attributes
	 * @param string $content not used
	 *
	 * @return string
	 */
	public static function groups_leave( $atts, $content = null ) {

		global $groups_leave_data_init, $post;

		if ( !self::validate( 'groups_leave', $atts, $content ) ) {
			return '';
		}

		$nonce_action = 'groups_action';
		$nonce        = 'nonce_leave';
		$output       = '';

		$options = shortcode_atts(
			array(
				'class'           => '',
				'group'           => '',
				'display_message' => true,
				'redirect'        => true,
				'submit_class'    => '',
				/* translators: group name */
				'submit_text'     => esc_html__( 'Leave the %s group', 'groups' ),
			),
			$atts
		);

		$display_message = is_string( $options['display_message'] ) ? strtolower( $options['display_message'] ) : $options['display_message'];
		$redirect = is_string( $options['redirect'] ) ? strtolower( $options['redirect'] ) : $options['redirect'];
		$submit_text = $options['submit_text'];

		$display_message = in_array( $display_message, array( 'true', 'yes', true ) );

		if ( !is_bool( $redirect ) ) {
			switch ( $redirect ) {
				case 'true':
				case 'yes':
					$redirect = true;
					break;
				case 'false':
				case 'no':
					$redirect = false;
					break;
				default:
					if ( is_string( $redirect ) ) {
						$redirect = trim( $redirect );
						if ( strlen( $redirect ) === 0 ) {
							$redirect = true;
						}
					} else {
						$redirect = true;
					}
			}
		}

		$class = trim( $options['class'] );
		$submit_class = trim( $options['submit_class'] );
		$group = trim( $options['group'] );
		$current_group = Groups_Group::read( $group );
		if ( !$current_group ) {
			$current_group = Groups_Group::read_by_name( $group );
		}
		// bail out if no valid group
		if ( !$current_group ) {
			return '';
		}

		// @since 3.11.0 Restrict the functionality to authors with appropriate permission
		$author_can_restrict_group_ids = array();
		$author_id = isset( $post ) && !empty( $post->post_author ) ? $post->post_author : get_the_author_meta( 'ID' );
		$author_id = is_numeric( $author_id ) ? intval( $author_id ) : null;
		if ( $author_id !== null ) {
			$author = new Groups_User( $author_id );
			if ( $author->can( GROUPS_RESTRICT_ACCESS ) ) {
				if ( $author->can( GROUPS_ADMINISTER_GROUPS ) ) {
					$author_can_restrict_group_ids = Groups_Group::get_group_ids();
				} else {
					$author_can_restrict_group_ids = $author->get_group_ids_deep();
				}
			}
		}
		if ( !in_array( $current_group->group_id, $author_can_restrict_group_ids ) ) {
			return '';
		}

		if ( $current_group ) {
			if ( $user_id = get_current_user_id() ) {
				$left          = false;
				$submitted     = false;
				$invalid_nonce = false;
				if ( groups_sanitize_post( 'groups_action' ) === 'leave' ) {
					$submitted = true;
					// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					if ( !groups_verify_post_nonce( $nonce, $nonce_action ) ) { // nosemgrep: scanner.php.wp.security.csrf.nonce-check-not-dying
						$invalid_nonce = true;
					}
				}
				if ( $submitted && !$invalid_nonce ) {
					// remove user from group
					if ( isset( $_POST['groups-leave-data'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
						$hash = trim( groups_sanitize_post( 'groups-leave-data' ) );
						$groups_leave_data = get_user_meta( $user_id, 'groups-leave-data', true );
						if ( is_array( $groups_leave_data ) && isset( $groups_leave_data[$hash] ) ) {
							if ( isset( $groups_leave_data[$hash]['group_id'] ) && isset( $groups_leave_data[$hash]['time'] ) ) {
								$group_id = $groups_leave_data[$hash]['group_id'];
								$dt = time() - $groups_leave_data[$hash]['time'];
								if ( $dt < apply_filters( 'groups_leave_submit_max_time_delta', self::MAX_TIME_DELTA ) ) {
									$left = Groups_User_Group::delete( $user_id, $group_id );
									if ( $left ) {
										/**
										 * Whether to redirect after acceptedsubmit and successful removal from group.
										 *
										 * @since 3.6.0
										 *
										 * @param boolean|string $redirect true to redirect to current ULR, string to redirect to specific URL, false not to redirect
										 * @param array $atts shortcode attributes
										 * @param array $options evaluated shortcode options
										 *
										 * @return boolean|string
										 */
										if ( apply_filters( 'groups_leave_submit_redirect', $redirect, $atts, $options ) !== false ) {
											self::maybe_redirect( $redirect );
										}
									}
								}
							}
						}
					}
				}
				if ( Groups_User::user_is_member( $user_id, $current_group->group_id ) ) {
					if ( !isset( $groups_leave_data_init ) ) {
						$groups_leave_data_init = true;
						delete_user_meta( $user_id, 'groups-leave-data' );
					}
					$data = array(
						'user_id'  => $user_id,
						'group_id' => $current_group->group_id,
						'time'     => time(),
						'salt'     => rand( 0, PHP_INT_MAX )
					);
					$hash = hash( 'sha256', json_encode( $data ) );
					$groups_leave_data = get_user_meta( $user_id, 'groups-leave-data', true );
					if ( !is_array( $groups_leave_data ) ) {
						$groups_leave_data = array();
					}
					$groups_leave_data[$hash] = $data;
					update_user_meta( $user_id, 'groups-leave-data', $groups_leave_data );

					$submit_text = sprintf( $options['submit_text'], stripslashes( wp_filter_nohtml_kses( $current_group->name ) ) );
					$output .= sprintf(
						'<div class="groups-leave%s">',
						strlen( $class ) > 0 ? ' ' . esc_attr( $class ) : '',
					);
					$output .= '<form action="#" method="post">';
					$output .= '<input type="hidden" name="groups_action" value="leave" />';
					$output .= '<input type="hidden" name="groups-leave-data" value="' . esc_attr( $hash ) . '" />';
					$output .= sprintf(
						'<input class="groups-leave-submit%s" type="submit" value="%s" />',
						strlen( $submit_class ) > 0 ? ' ' . esc_attr( $submit_class ) : '',
						esc_attr( $submit_text )
					);
					$output .= wp_nonce_field( $nonce_action, $nonce, true, false );
					$output .= '</form>';
					$output .= '</div>';
				} else if ( $display_message ) {
					if ( $left ) {
						$output .= '<div class="groups-leave left">';
						/* translators: group name */
						$output .= sprintf( esc_html__( 'You have left the %s group.', 'groups' ), stripslashes( wp_filter_nohtml_kses( $current_group->name ) ) );
						$output .= '</div>';
					}
				}
			}
		}

		if ( self::$preprocessing ) {
			// surround content with hashmarks
			// <!-- groups:{hash} -->{content}<!-- /groups:{hash} -->
			$hash = md5( $output );
			$prefix = sprintf( '<!-- groups:%s -->', $hash );
			$suffix = sprintf( '<!-- /groups:%s -->', $hash );
			self::$map[$hash] = array(
				'prefix' => $prefix,
				'suffix' => $suffix,
				'content' => $output
			);

			$output = sprintf(
				'%s%s%s',
				$prefix,
				$output,
				$suffix
			);
		}

		return $output;
	}

	/**
	 * Try to redirect.
	 *
	 * No redirect will happen if $redirect is false.
	 *
	 * A redirect to the current URL is attempted if $redirect is an empty string.
	 *
	 * Relative paths will try to redirect to the path off the home URL and other URL components present.
	 *
	 * @since 3.6.0
	 *
	 * @param boolean|string $redirect
	 */
	private static function maybe_redirect( $redirect ) {

		// Don't redirect
		if ( is_bool( $redirect ) && !$redirect ) {
			return;
		}

		// Use the current URL if no specific URL is provided
		if ( is_string( $redirect ) && trim( $redirect ) !== '' ) {
			$redirect_url = trim( $redirect );
		} else {
			$redirect_url = groups_get_current_url();
		}

		// Try to handle a relative URL, determine missing parts
		$parts = wp_parse_url( $redirect_url );
		if ( !isset( $parts['scheme'] ) ) {
			$parts['scheme'] = is_ssl() ? 'https' : 'http';
		}
		if ( !isset( $parts['host'] ) ) {
			$parts['host'] = wp_parse_url( home_url(), PHP_URL_HOST );
		}
		if ( !isset( $parts['path'] ) ) {
			$parts['path'] = wp_parse_url( home_url(), PHP_URL_PATH );
		} else {
			$home_path = wp_parse_url( home_url(), PHP_URL_PATH );
			if ( strpos( $parts['path'], $home_path ) !== 0 ) {
				$parts['path'] = trailingslashit( $home_path ) . ltrim( $parts['path'], '/\\' );
			}
		}
		// Put the absolute URL together
		$url = $parts['scheme'] . ':';
		if ( !empty( $parts['user'] ) && !empty( $parts['password'] ) ) {
			$url .= $parts['user'] . ':' . $parts['password'] . '@';
		}
		$url .= '//' . $parts['host'];
		if ( !empty( $parts['path'] ) ) {
			$url .= $parts['path'];
		}
		if ( !empty( $parts['query'] ) ) {
			$url .= '?' . $parts['query'];
		}
		if ( !empty( $parts['fragment'] ) ) {
			$url .= '#' . $parts['fragment'];
		}
		$redirect_url = $url;

		// validate the URL and restrict to allowed hosts, uses the allowed_redirect_hosts filter restricting to the domain of the current site
		$redirect_url = wp_validate_redirect( $redirect_url ); // default fallback is ''
		if ( is_string( $redirect_url ) ) {
			$redirect_url = trim( $redirect_url );
		}
		if ( $redirect_url !== null && $redirect_url !== false && $redirect_url !== '' ) {
			if ( wp_redirect( $redirect_url ) ) { // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
				exit;
			}
		}
	}

	/**
	 * Determine which blocks to preprocess.
	 *
	 * @since 3.11.0
	 *
	 * @return array
	 */
	public static function get_preprocess_blocks() {
		$blocks = apply_filters(
			'groups_shortcodes_preprocess_blocks',
			array(
				'core/latest-posts'
			)
		);
		if ( !is_array( $blocks ) ) {
			$blocks = array();
		}
		return $blocks;
	}

	/**
	 * Content preprocessing.
	 *
	 * @since 3.11.0
	 *
	 * @param string|null $pre_render
	 * @param array $parsed_block
	 * @param WP_Block|null $parent_block
	 *
	 * @return string|null
	 */
	public static function pre_render_block( $pre_render, $parsed_block, $parent_block ) {
		if ( in_array( $parsed_block['blockName'], self::get_preprocess_blocks() ) ) {
			// start preprocessing
			self::$preprocessing = true;
			add_filter( 'the_posts', array( __CLASS__, 'preprocess_the_posts' ), 10, 2 );
		}
		return $pre_render;
	}

	/**
	 * Map processing.
	 *
	 * @since 3.11.0
	 *
	 * @param string $block_content
	 * @param array $parsed_block
	 * @param WP_Block $block
	 *
	 * @return string
	 */
	public static function render_block( $block_content, $parsed_block, $block ) {
		// Remove hashmarks leaving the content within.
		if ( in_array( $parsed_block['blockName'], self::get_preprocess_blocks() ) ) {
			// stop preprocessing
			remove_filter( 'the_posts', array( __CLASS__, 'preprocess_the_posts' ), 10 );
			self::$preprocessing = false;
			foreach ( self::$map as $hash => $data ) {
				$prefix  = $data['prefix'] ?? '';
				$suffix  = $data['suffix'] ?? '';
				$content = $data['content'] ?? '';
				$start   = $prefix !== '' ? strpos( $block_content, $prefix ) : false;
				$end     = $suffix !== '' ? strpos( $block_content, $suffix ) : false;
				if ( $start !== false && $end !== false ) {
					$block_content = substr( $block_content, 0, $start ) . $content . substr( $block_content, $end + strlen( $suffix ) );
				}
			}
		}
		return $block_content;
	}

	/**
	 * Preprocess posts.
	 *
	 * @since 3.11.0
	 *
	 * @param WP_Post[] $posts
	 * @param WP_Query $query
	 *
	 * @return WP_Post[]
	 */
	public static function preprocess_the_posts( $posts, $query ) {
		global $shortcode_tags, $post;
		if ( !empty( $shortcode_tags ) ) {
			// remember the global post object
			$original_post = $post;
			// remember the global registered shortcodes
			$original_shortcode_tags = $shortcode_tags;
			// limit processing to these shortcodes
			$do_shortcode_tags = array();
			if ( isset( $shortcode_tags['groups_join'] ) ) {
				$do_shortcode_tags['groups_join'] = $shortcode_tags['groups_join'];
			}
			if ( isset( $shortcode_tags['groups_leave'] ) ) {
				$do_shortcode_tags['groups_leave'] = $shortcode_tags['groups_leave'];
			}
			$shortcode_tags = $do_shortcode_tags;
			// preprocess content for each post
			$processed_posts = array();
			while ( !empty( $posts ) ) {
				// set the global $post to process within do_shortcode()
				$post = array_shift( $posts );
				$post->post_excerpt = do_shortcode( $post->post_excerpt );
				$post->post_content = do_shortcode( $post->post_content );
				array_push( $processed_posts, $post );
			}
			// modified posts to return
			$posts = $processed_posts;
			// restore the global registered shortcodes
			$shortcode_tags = $original_shortcode_tags;
			// restore the global post
			$post = $original_post;
		}
		return $posts;
	}

	/**
	 * Validate shortcode.
	 *
	 * @since 4.7.0
	 *
	 * @see do_shortcode()
	 * @see do_shortcode_tag()
	 *
	 * @param string $tag shortcode tag
	 * @param array $atts shortcode attributes
	 * @param string $content shortcode content
	 *
	 * @return boolean
	 */
	public static function validate( $tag, $atts, $content ) {

		global $post;

		// allow direct calls to shortcode processing functions
		if ( !self::is_processing( $tag, $atts ) ) {
			return true;
		}

		$valid =
			isset( $post ) && !empty( $post->ID ) && ( !empty( $post->post_excerpt ) || !empty( $post->post_content ) ) ||
			!empty( self::$widgets_contents );

		if ( $valid ) {
			$valid = ! (
				defined( 'REST_API_REQUEST' ) && REST_API_REQUEST ||
				defined( 'REST_REQUEST' ) && REST_REQUEST ||
				defined( 'WP_CLI' ) && WP_CLI ||
				defined( 'WPCOM_CLI_SCRIPT' ) && WPCOM_CLI_SCRIPT ||
				is_admin() ||
				is_feed() ||
				wp_doing_ajax() || // supersedes DOING_AJAX
				wp_is_json_request() ||
				wp_is_jsonp_request()
			);
		}

		if ( $valid ) {
			$valid = false;

			// @since 4.7.1 also consider the excerpt and widgets
			$contents = ( $post->post_excerpt ?? '' ) . ( $post->post_content ?? '' );
			if ( !empty( self::$widgets_contents ) ) {
				$contents .= implode( ' ', self::$widgets_contents );
			}
			/**
			 * Allow to filter the contents considered for shortcode validation.
			 *
			 * @since 4.7.1
			 *
			 * @param string $contents contents considered
			 * @param string $tag shortcode tag
			 * @param array $atts shortcode attributes
			 * @param string $content shortcode content
			 *
			 * @return string
			 */
			$contents = apply_filters( 'groups_shortcodes_validate_contents', $contents, $tag, $atts, $content );

			if ( !empty( $contents ) && has_shortcode( $contents, $tag ) ) {
				$matches = null;
				preg_match_all( '@\[([^<>&/\[\]\x00-\x20=]++)@', $contents, $matches );
				if ( !empty( $matches[1] ) && is_array( $matches[1] ) && count( $matches[1] ) > 0 ) {
					$tags = array_intersect( array( $tag ), $matches[1] );
					if ( !empty( $tags ) ) {
						// - does not support nested shortcodes
						// - Groups shortcodes do not support nesting
						$pattern = '@' . get_shortcode_regex( $tags ) . '@';
						$m = null;
						preg_match_all( $pattern, $contents, $m );
						// shortcode arguments list
						if ( isset( $m[3] ) && is_array( $m[3] ) && count( $m[3] ) > 0 ) {
							$found = false;
							foreach ( $m[3] as $args ) {
								if ( is_string( $args ) ) {
									$args = trim( $args );
									// decode in case unicode escape sequences used for quotes surrounding attributes need to be decoded for comparison
									$atts_decoded = json_decode( '"' . $args . '"' );
									$attributes = shortcode_parse_atts( $atts_decoded ?? $args );
									$match = true;
									foreach ( $attributes as $key => $value ) {
										if ( !array_key_exists( $key, $atts ) || $atts[$key] !== $value ) {
											$match = false;
											break;
										}
									}
									if ( $match ) {
										$found = true;
										break;
									}
								}
							}
							if ( $found ) {
								$valid = true;
							}
						}
					}
				}
			}
		}

		return $valid;
	}

	/**
	 * Use return value short-circuit to signal shortcode instance processing.
	 *
	 * @since 4.7.0
	 *
	 * @param boolean|string $output short-circuit value
	 * @param string $tag shortcode tag/name
	 * @param array $attr shortcode attributes
	 * @param array $m regex match
	 *
	 * @return boolean|string
	 */
	public static function pre_do_shortcode_tag( $output, $tag, $attr, $m ) {
		$hash = md5( json_encode( $attr ) );
		self::$shortcode_queue[] = array( 'tag' => $tag, 'hash' => $hash );
		return $output;
	}

	/**
	 * Use output filter to signal shortcode processed.
	 *
	 * @since 4.7.0
	 *
	 * @param string $output shortcode output
	 * @param string $tag shortcode tag/name
	 * @param array $attr shortcode attributes
	 * @param array $m regex match
	 *
	 * @return string
	 */
	public static function do_shortcode_tag( $output, $tag, $attr, $m ) {
		$queue = array();
		for ( $i = count( self::$shortcode_queue ) - 1; $i >= 0; $i-- ) {
			if ( $tag === self::$shortcode_queue[$i]['tag'] ) {
				$hash = md5( json_encode( $attr ) );
				if ( $hash !== self::$shortcode_queue[$i]['hash'] ) {
					array_unshift( $queue, self::$shortcode_queue[$i] );
				}
			}
		}
		self::$shortcode_queue = $queue;
		return $output;
	}

	/**
	 * Use callback filter to gather shortcodes in widgets.
	 *
	 * @since 4.7.1
	 *
	 * @param array $instance widget instance settings
	 * @param WP_Widget $widget widget object
	 * @param array $args widget arguments
	 *
	 * @return array
	 */
	public static function widget_display_callback( $instance, $widget, $args ) {
		if ( !empty( $instance ) && is_array( $instance ) ) {
			if ( is_object( $widget ) ) {
				if ( $widget instanceof WP_Widget_Text ) {
					if ( isset( $instance['text'] ) && is_string( $instance['text'] ) ) {
						self::$widgets_contents[] = $instance['text'];
					}
				} else if ( $widget instanceof WP_Widget_Block ) {
					if ( isset( $instance['content'] ) && is_string( $instance['content'] ) ) {
						self::$widgets_contents[] = $instance['content'];
					}
				} else {
					/**
					 * Allow widgets to provide content that should be checked during shortcode validation.
					 *
					 * @since 4.7.1
					 *
					 * @param string|null $content widget content to check for shortcodes
					 * @param array $instance widget instance settings
					 * @param WP_Widget $widget widget object
					 * @param array $args widget arguments
					 *
					 * @return string|null string content of the widget or null if widget's content should not be considered
					 */
					$content = apply_filters( 'groups_shortcodes_widget_display_callback_widget_content', null, $instance, $widget, $args );
					if ( is_string( $content ) ) {
						self::$widgets_contents[] = $content;
					}
				}
			}
		}
		return $instance;
	}

	/**
	 * Is processing shortcode tag with given attributes.
	 *
	 * @since 4.7.0
	 *
	 * @param string $tag
	 * @param array $atts
	 *
	 * @return boolean
	 */
	private static function is_processing( $tag, $atts ) {
		$result = false;
		for ( $i = 0; $i < count( self::$shortcode_queue ); $i++ ) {
			if ( $tag === self::$shortcode_queue[$i]['tag'] ) {
				$hash = md5( json_encode( $atts ) );
				if ( $hash === self::$shortcode_queue[$i]['hash'] ) {
					$result = true;
				}
			}
		}
		return $result;
	}
}

Groups_Shortcodes::init();
