<?php
/**
 * class-groups-access-meta-boxes.php
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
 * Adds meta boxes to edit screens.
 * 
 * @link http://codex.wordpress.org/Function_Reference/add_meta_box
 */
class Groups_Access_Meta_Boxes {

	const NONCE          = 'groups-meta-box-nonce';
	const SET_CAPABILITY = 'set-capability';
	const READ_ACCESS    = 'read-access';
	const CAPABILITY     = 'capability';
	const SHOW_GROUPS    = 'access-meta-box-show-groups';

	/**
	 * Sets up an init hook where actions and filters are added.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'wp_init' ) );
		add_action( 'admin_init', array(__CLASS__,'admin_init' ) );
	}

	/**
	 * Hooks for capabilities meta box and saving options.
	 */
	public static function wp_init() {
		if ( current_user_can( GROUPS_ACCESS_GROUPS ) ) {
			require_once GROUPS_VIEWS_LIB . '/class-groups-uie.php';

			add_action( 'add_meta_boxes', array( __CLASS__, "add_meta_boxes" ), 10, 2 );
			add_action( 'save_post', array( __CLASS__, "save_post" ), 10, 2 );
			add_filter( 'wp_insert_post_empty_content', array( __CLASS__, 'wp_insert_post_empty_content' ), 10, 2 );

			add_action( 'attachment_fields_to_edit', array( __CLASS__, 'attachment_fields_to_edit' ), 10, 2 );
			add_action( 'attachment_fields_to_save', array( __CLASS__, 'attachment_fields_to_save' ), 10, 2 );
		}
	}

	/**
	 * Hooked on admin_init to register our action on admin_enqueue_scripts.
	 */
	public static function admin_init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
	}

	/**
	 * Hooked on admin_enqueue_scripts to timely enqueue resources required
	 * on the media upload / attachment popup.
	 */
	public static function admin_enqueue_scripts() {
		global $pagenow;
		if ( $pagenow == 'upload.php' ) {
			Groups_UIE::enqueue( 'select' );
		}
		
	}

	/**
	 * Triggered by init() to add capability meta box.
	 */
	public static function add_meta_boxes( $post_type, $post = null ) {
		global $wp_version;
		$post_type_object = get_post_type_object( $post_type );
		if ( $post_type_object && $post_type != 'attachment' ) {
			$post_types_option = Groups_Options::get_option( Groups_Post_Access::POST_TYPES, array() );
			if ( !isset( $post_types_option[$post_type]['add_meta_box'] ) || $post_types_option[$post_type]['add_meta_box'] ) {
				if ( $wp_version < 3.3 ) {
					$post_types = get_post_types();
					foreach ( $post_types as $post_type ) {
						add_meta_box(
							"groups-access",
							__( "Access restrictions", GROUPS_PLUGIN_DOMAIN ),
							array( __CLASS__, "capability" ),
							$post_type,
							"side",
							"high"
						);
					}
				} else {
					add_meta_box(
						"groups-access",
						__( "Access restrictions", GROUPS_PLUGIN_DOMAIN ),
						array( __CLASS__, "capability" ),
						null,
						"side",
						"high"
					);
				}

				Groups_UIE::enqueue( 'select' );

				if ( current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
					if ( $screen = get_current_screen() ) {
						$screen->add_help_tab( array(
							'id'      => 'groups-access',
							'title'   => __( 'Access restrictions', GROUPS_PLUGIN_DOMAIN ),
							'content' =>
								'<p>' .
								'<strong>' . __( 'Access restrictions', GROUPS_PLUGIN_DOMAIN ) . '</strong>' .
								'</p>' .
								'<p>' .
								__( 'Use the <em>Access restrictions</em> box to limit the visibility of posts, pages and other post types.', GROUPS_PLUGIN_DOMAIN ) .
								'</p>' .
								'<p>' .
								__( 'You can select one or more capabilities that are enabled for access restriction.', GROUPS_PLUGIN_DOMAIN ) .
								' ' .
								__( 'Note that you must be a member of a group that has such a capability assigned.', GROUPS_PLUGIN_DOMAIN ) .
								'</p>' .
								'<p>' .
								'<strong>' . __( 'Example:', GROUPS_PLUGIN_DOMAIN ) . '</strong>' . 
								'</p>' .
								__( 'Let\'s assume that you want to limit the visibility of a post to members of the <em>Premium</em> group.', GROUPS_PLUGIN_DOMAIN ) .
								'<p>' .
								'<strong>' . __( 'The quick way:', GROUPS_PLUGIN_DOMAIN ) . '</strong>' .
								' ' .
								__( 'Using the quick-create field', GROUPS_PLUGIN_DOMAIN ) .
								'</p>' .
								__( 'Enter <em>Premium</em> in the quick-create field located in the Access restrictions panel and save or update the post (or hit Enter).', GROUPS_PLUGIN_DOMAIN ) .
								'<p>' .
								'<p>' .
								__( 'Using the quick-create field, you can create a new group and capability. The capability will be assigned to the group and enabled to enforce read access. Group names are case-sensitive, the name of the capability is the lower-case version of the name of the group. If the group already exists, a new capability is created and assigned to the existing group. If the capability already exists, it will be assigned to the group. If both already exist, the capability is enabled to enforce read access. In order to be able to use the capability, your user account will be assigned to the group.', GROUPS_PLUGIN_DOMAIN ) .
								'</p>' .
								'<em>' . __( 'The manual way:', GROUPS_PLUGIN_DOMAIN ) . '</em>' .
								' ' .
								__( 'Adding the group and capability manually and enabling it for access restriction', GROUPS_PLUGIN_DOMAIN ) .
								'</p>' .
								'<p>' .
								__( 'Try the quick-create field first. Unless you need a more complex setup, there is no reason to go this way instead.', GROUPS_PLUGIN_DOMAIN ) .
								'</p>' .
								'<ol>' .
								'<li>' . __( 'Go to <strong>Groups > Groups</strong> and add the <em>Premium</em> group.', GROUPS_PLUGIN_DOMAIN ) . '</li>' .
								'<li>' . __( 'Go to <strong>Groups > Capabilities</strong> and add the <em>premium</em> capability.', GROUPS_PLUGIN_DOMAIN ) . '</li>' .
								'<li>' . __( 'Go to <strong>Groups > Groups</strong> and assign the <em>premium</em> capability to the <em>Premium</em> group.', GROUPS_PLUGIN_DOMAIN ) . '</li>' .
								'<li>' . __( 'Go to <strong>Groups > Options</strong> and enable the <em>premium</em> capability to restrict access.', GROUPS_PLUGIN_DOMAIN ) . '</li>' .
								'<li>' . __( 'Become a member of the <em>Premium</em> group - this is required so you can choose the <em>premium</em> capability to restrict access to a post.', GROUPS_PLUGIN_DOMAIN ) . '</li>' .
								'<li>' . __( 'Edit the post for which you want to restrict access and choose<sup>*</sup> the <em>premium</em> capability.', GROUPS_PLUGIN_DOMAIN ) . '</li>' . 
								'</ol>' .
								'<p>' .
								__( '<sup>*</sup> For each capability, the groups that have the capability assigned are shown within parenthesis. You can choose a capability by typing part of the group\'s or the capability\'s name.', GROUPS_PLUGIN_DOMAIN ) .
								'</p>'
						) );
					}
				}
			}
		}
	}

	/**
	 * Render meta box for capabilities.
	 * 
	 * @see do_meta_boxes()
	 * 
	 * @param Object $object
	 * @param Object $box
	 */
	public static function capability( $object = null, $box = null ) {

		$output = "";

		$show_groups = Groups_Options::get_user_option( self::SHOW_GROUPS, true );

		$post_id = isset( $object->ID ) ? $object->ID : null;
		$post_type = isset( $object->post_type ) ? $object->post_type : null;
		$post_singular_name = __( "Post", GROUPS_PLUGIN_DOMAIN );
		if ( $post_type !== null ) {
			$post_type_object = get_post_type_object( $post_type );
			$labels = isset( $post_type_object->labels ) ? $post_type_object->labels : null;
			if ( $labels !== null ) {
				if ( isset( $labels->singular_name ) )  {
					$post_singular_name = __( $labels->singular_name );
				}
			}
		}

		$output .= wp_nonce_field( self::SET_CAPABILITY, self::NONCE, true, false );

		if ( self::user_can_restrict() ) {
			$user = new Groups_User( get_current_user_id() );
			$output .= __( "Enforce read access", GROUPS_PLUGIN_DOMAIN );

			$read_caps = get_post_meta( $post_id, Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ_POST_CAPABILITY );
			$valid_read_caps = Groups_Options::get_option( Groups_Post_Access::READ_POST_CAPABILITIES, array( Groups_Post_Access::READ_POST_CAPABILITY ) );
			$output .= '<div class="select-capability-container">';
			$output .= sprintf(
				'<select class="select capability" name="%s" multiple="multiple" placeholder="%s" data-placeholder="%s" title="%s">',
				self::CAPABILITY . '[]',
				__( 'Type and choose &hellip;', GROUPS_PLUGIN_DOMAIN),
				__( 'Type and choose &hellip;', GROUPS_PLUGIN_DOMAIN),
				__( 'Choose one or more capabilities to restrict access. Groups that grant access through the capabilities are shown in parenthesis. If no capabilities are available yet, you can use the quick-create box to create a group and capability enabled for access restriction on the fly.', GROUPS_PLUGIN_DOMAIN )
			);
			$output .= '<option value=""></option>';
			foreach( $valid_read_caps as $valid_read_cap ) {
				if ( $capability = Groups_Capability::read_by_capability( $valid_read_cap ) ) {
					if ( $user->can( $capability->capability ) ) {
						$c = new Groups_Capability( $capability->capability_id );
						$groups = $c->groups;
						$group_names = array();
						if ( !empty( $groups ) ) {
							foreach( $groups as $group ) {
								$group_names[] = $group->name;
							}
						}
						if ( count( $group_names ) > 0 ) {
							$label_title = sprintf(
								_n(
									'Members of the %1$s group can access this %2$s through this capability.',
									'Members of the %1$s groups can access this %2$s through this capability.',
									count( $group_names ),
									GROUPS_PLUGIN_DOMAIN
								),
								wp_filter_nohtml_kses( implode( ',', $group_names ) ),
								$post_singular_name
							);
						} else {
							$label_title = __( 'No groups grant access through this capability. To grant access to group members using this capability, you should assign it to a group and enable the capability for access restriction.', GROUPS_PLUGIN_DOMAIN );
						}
						$output .= sprintf( '<option value="%s" %s>', esc_attr( $capability->capability_id ), in_array( $capability->capability, $read_caps ) ? ' selected="selected" ' : '' );
						$output .= wp_filter_nohtml_kses( $capability->capability );
						if ( $show_groups ) {
							if ( count( $group_names ) > 0 ) {
								$output .= ' ';
								$output .= '(' . wp_filter_nohtml_kses( implode( ', ', $group_names ) ) . ')';
							}
						}
						$output .= '</option>';
					}
				}
			}
			$output .= '</select>';
			
			$output .= Groups_UIE::render_select( '.select.capability' );
// 			$output .= '<script type="text/javascript">';
// 			$output .= 'if (typeof jQuery !== "undefined"){';
// 			if ( self::WHICH_SELECT == 'chosen' ) {
// 				$output .= 'jQuery(".select.capability").chosen({width:"100%",search_contains:true});';
// 			} else {
// 				$output .= 'jQuery(".select.capability").selectize({plugins: ["remove_button"]});';
// 			}
// 			$output .= '}';
// 			$output .= '</script>';
// 			$output .= '<style type="text/css">';
// 			$output .= '.select-capability-container input[type="text"] { min-height: 2em; }';
// 			$output .= '</style>';
			$output .= '</div>';

			$output .= '<p class="description">';
			$output .= sprintf( __( "Only groups or users that have one of the selected capabilities are allowed to read this %s.", GROUPS_PLUGIN_DOMAIN ), $post_singular_name );
			$output .= '</p>';

			$output .= '<p class="description">';
			$output .= sprintf( '<label title="%s">', __( 'Click to toggle the display of groups that grant the capabilities.', GROUPS_PLUGIN_DOMAIN ) );
			$output .= sprintf( '<input id="access-show-groups" type="checkbox" name="%s" %s />', esc_attr( self::SHOW_GROUPS ), $show_groups ? ' checked="checked" ' : '' );
			$output .= ' ';
			$output .= __( 'Show groups', GROUPS_PLUGIN_DOMAIN );
			$output .= '</label>';
			$output .= '</p>';
			$output .= '<script type="text/javascript">';
			$output .= 'if (typeof jQuery !== "undefined"){';
			$output .= !$show_groups ? 'jQuery("span.groups.description").hide();' : '';
			$output .= 'jQuery("#access-show-groups").click(function(){';
			$output .= 'jQuery("span.groups.description").toggle();';
			$output .= '});';
			$output .= '}';
			$output .= '</script>';
		} else {
			$output .= '<p class="description">';
			$output .= sprintf( __( 'You cannot set any access restrictions.', GROUPS_PLUGIN_DOMAIN ), $post_singular_name );
			$style = 'cursor:help;vertical-align:middle;';
			if ( current_user_can( GROUPS_ADMINISTER_OPTIONS ) ) {
				$style = 'cursor:pointer;vertical-align:middle;';
				$output .= sprintf( '<a href="%s">', esc_url( admin_url( 'admin.php?page=groups-admin-options' ) ) );
			}
			$output .= sprintf( '<img style="%s" alt="?" title="%s" src="%s" />', $style, esc_attr( __( 'You must be in a group that has at least one capability enabled to enforce read access.', GROUPS_PLUGIN_DOMAIN ) ), esc_attr( GROUPS_PLUGIN_URL . 'images/help.png' ) );
			if ( current_user_can( GROUPS_ADMINISTER_OPTIONS ) ) {
				$output .= '</a>';
			}
			$output .= '</p>';
		}

		// quick-create
		if ( current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
			$style = 'cursor:help;vertical-align:middle;';
			$output .= '<div class="quick-create-group-capability" style="margin:4px 0">';
			$output .= '<label>';
			$output .= sprintf( '<input style="width:100%%;margin-right:-20px;" id="quick-group-capability" name="quick-group-capability" class="quick-group-capability" type="text" value="" placeholder="%s"/>', __( 'Quick-create group &amp; capability', GROUPS_PLUGIN_DOMAIN ) );
			$output .= sprintf(
				'<img id="quick-create-help-icon" style="%s" alt="?" title="%s" src="%s" />',
				$style,
				esc_attr( __( 'You can create a new group and capability here. The capability will be assigned to the group and enabled to enforce read access. Group names are case-sensitive, the name of the capability is the lower-case version of the name of the group. If the group already exists, a new capability is created and assigned to the existing group. If the capability already exists, it will be assigned to the group. If both already exist, the capability is enabled to enforce read access. In order to be able to use the capability, your user account will be assigned to the group.', GROUPS_PLUGIN_DOMAIN ) ),
				esc_attr( GROUPS_PLUGIN_URL . 'images/help.png' )
			);
			$output .= '</label>';
			$output .= '</div>';
			$output .= '<script type="text/javascript">';
			$output .= 'if (typeof jQuery !== "undefined"){';
			$output .= 'jQuery("#quick-create-help-icon").click(function(){';
			$output .= 'jQuery("#contextual-help-link").click();';
			$output .= '});';
			$output .= '}';
			$output .= '</script>';
		}

		echo $output;
	}

	/**
	 * Invokes our save_post() if the post content is considered empty.
	 * This is required because even on an empty post, we want to allow to
	 * quick-create group and category as well as assign capabilities.
	 * At WordPress 3.6.1, this is the only way we can achieve that, because
	 * the save_post action is not invoked if the post content is considered
	 * empty.
	 * 
	 * @param boolean $maybe_empty
	 * @param array $postarr
	 * @return boolean
	 */
	public static function wp_insert_post_empty_content( $maybe_empty, $postarr ) {

		// Only consider invoking save_post() here, if the post content is
		// considered to be empty at this stage. This is so we don't end up
		// having save_post() invoked twice when the post is not empty.
		if ( $maybe_empty ) {
			$post_id = !empty( $postarr['ID'] ) ? $postarr['ID'] : !empty( $postarr['post_ID'] ) ? $postarr['post_ID'] : null;
			if ( $post_id ) {
				self::save_post( $post_id );
			}
		}

		return $maybe_empty;
	}

	/**
	 * Save capability options.
	 * 
	 * @param int $post_id
	 * @param mixed $post post data (not used here)
	 */
	public static function save_post( $post_id = null, $post = null ) {
		if ( ( defined( "DOING_AUTOSAVE" ) && DOING_AUTOSAVE ) ) {
		} else {
			$post_type = get_post_type( $post_id );
			$post_type_object = get_post_type_object( $post_type );
			if ( $post_type_object && $post_type != 'attachment' ) {
				$post_types_option = Groups_Options::get_option( Groups_Post_Access::POST_TYPES, array() );
				if ( !isset( $post_types_option[$post_type]['add_meta_box'] ) || $post_types_option[$post_type]['add_meta_box'] ) {
					if ( isset( $_POST[self::NONCE] ) && wp_verify_nonce( $_POST[self::NONCE], self::SET_CAPABILITY ) ) {
						$post_type = isset( $_POST["post_type"] ) ? $_POST["post_type"] : null;
						if ( $post_type !== null ) {
							// See http://codex.wordpress.org/Function_Reference/current_user_can 20130119 WP 3.5
							// "... Some capability checks (like 'edit_post' or 'delete_page') require this [the post ID] be provided."
							// If the post ID is not provided, it will throw:
							// PHP Notice:  Undefined offset: 0 in /var/www/groups-forums/wp-includes/capabilities.php on line 1067 
							$edit_post_type = 'edit_' . $post_type;
							if ( $post_type_object = get_post_type_object( $post_type ) ) {
								if ( !isset( $post_type_object->capabilities ) ) {
									// get_post_type_capabilities() (WP 3.8) will throw a warning
									// when trying to merge the missing property otherwise. It's either a
									// bug or the function's documentation should make it clear that you
									// have to provide that.
									$post_type_object->capabilities = array();
								}
								$caps_object = get_post_type_capabilities( $post_type_object );
								if ( isset( $caps_object->edit_post ) ) {
									$edit_post_type = $caps_object->edit_post;
								}
							}

							if ( current_user_can( $edit_post_type, $post_id ) ) {
								// quick-create ?
								if ( current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
									if ( !empty( $_POST['quick-group-capability'] ) ) {
										$creator_id = get_current_user_id();
										$datetime	= date( 'Y-m-d H:i:s', time() );
										$name		= ucfirst( strtolower( trim( $_POST['quick-group-capability'] ) ) );
										if ( strlen( $name ) > 0 ) {
											// create or obtain the group
											if ( $group = Groups_Group::read_by_name( $name ) ) {
											} else {
												if ( $group_id = Groups_Group::create( compact( 'creator_id', 'datetime', 'name' ) ) ) {
													$group = Groups_Group::read( $group_id );
												}
											}
											// create or obtain the capability
											$name = strtolower( $name );
											if ( $capability = Groups_Capability::read_by_capability( $name ) ) {
											} else {
												if ( $capability_id = Groups_Capability::create( array( 'capability' => $name ) ) ) {
													$capability = Groups_Capability::read( $capability_id );
												}
											}
											if ( $group && $capability ) {
												// add the capability to the group
												if ( !Groups_Group_Capability::read( $group->group_id, $capability->capability_id ) ) {
													Groups_Group_Capability::create(
														array(
															'group_id' => $group->group_id,
															'capability_id' => $capability->capability_id
														)
													);
												}
												// enable the capability for access restriction
												$valid_read_caps = Groups_Options::get_option( Groups_Post_Access::READ_POST_CAPABILITIES, array( Groups_Post_Access::READ_POST_CAPABILITY ) );
												if ( !in_array( $capability->capability, $valid_read_caps ) ) {
													$valid_read_caps[] = $capability->capability;
												}
												Groups_Options::update_option( Groups_Post_Access::READ_POST_CAPABILITIES, $valid_read_caps );
												// add the current user to the group
												Groups_User_Group::create(
													array(
														'user_id' => get_current_user_id(),
														'group_id' => $group->group_id
													)
												);
												// put the capability ID in $_POST[self::CAPABILITY] so it is treated below
												if ( empty( $_POST[self::CAPABILITY] ) ) {
													$_POST[self::CAPABILITY] = array();
												}
												if ( !in_array( $capability->capability_id, $_POST[self::CAPABILITY] ) ) {
													$_POST[self::CAPABILITY][] = $capability->capability_id;
												}
											}
										}
									}
								}
								// set
								if ( self::user_can_restrict() ) {
									$valid_read_caps = self::get_valid_read_caps_for_user();
									foreach( $valid_read_caps as $valid_read_cap ) {
										if ( $capability = Groups_Capability::read_by_capability( $valid_read_cap ) ) {
											if ( !empty( $_POST[self::CAPABILITY] ) && is_array( $_POST[self::CAPABILITY] ) && in_array( $capability->capability_id, $_POST[self::CAPABILITY] ) ) {
												Groups_Post_Access::create( array(
													'post_id' => $post_id,
													'capability' => $capability->capability
												) );
											} else {
												Groups_Post_Access::delete( $post_id, $capability->capability );
											}
										}
									}
								}
								// show groups
								Groups_Options::update_user_option( self::SHOW_GROUPS, !empty( $_POST[self::SHOW_GROUPS] ) );
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Enqueue scripts and styles.
	 */
	private static function enqueue() {
		global $groups_version;
		if ( self::WHICH_SELECT == 'chosen' ) {
			if ( !wp_script_is( 'chosen' ) ) {
				wp_enqueue_script( 'chosen', GROUPS_PLUGIN_URL . 'js/chosen/chosen.jquery.min.js', array( 'jquery' ), $groups_version, false );
			}
			if ( !wp_style_is( 'chosen' ) ) {
				wp_enqueue_style( 'chosen', GROUPS_PLUGIN_URL . 'css/chosen/chosen.min.css', array(), $groups_version );
			}
		} else {
			if ( !wp_script_is( 'selectize' ) ) {
				wp_enqueue_script( 'selectize', GROUPS_PLUGIN_URL . 'js/selectize/selectize.min.js', array( 'jquery' ), $groups_version, false );
			}
			if ( !wp_style_is( 'selectize' ) ) {
				wp_enqueue_style( 'selectize', GROUPS_PLUGIN_URL . 'css/selectize/selectize.bootstrap2.css', array(), $groups_version );
			}
		}
	}

	/**
	 * Render capabilities box for attachment post type (Media).
	 * @param array $form_fields
	 * @param object $post
	 * @return array
	 */
	public static function attachment_fields_to_edit( $form_fields, $post ) {

		Groups_UIE::enqueue( 'select' );

		$post_types_option = Groups_Options::get_option( Groups_Post_Access::POST_TYPES, array() );
		if ( !isset( $post_types_option['attachment']['add_meta_box'] ) || $post_types_option['attachment']['add_meta_box'] ) {
			if ( self::user_can_restrict() ) {
				$user = new Groups_User( get_current_user_id() );
				$output = "";
				$post_singular_name = __( 'Media', GROUPS_PLUGIN_DOMAIN );

				$output .= __( "Enforce read access", GROUPS_PLUGIN_DOMAIN );
				$read_caps = get_post_meta( $post->ID, Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ_POST_CAPABILITY );
				$valid_read_caps = self::get_valid_read_caps_for_user();

				// On attachments edited within the 'Insert Media' popup, the update is triggered too soon and we end up with only the last capability selected.
				// This occurs when using normal checkboxes as well as the select below (Chosen and Selectize tested).
				// With checkboxes it's even more confusing, it's actually better to have it using a select as below,
				// because the visual feedback corresponds with what is assigned.
				// See http://wordpress.org/support/topic/multiple-access-restrictions-for-media-items-are-not-saved-in-grid-view
				// and https://core.trac.wordpress.org/ticket/28053 - this is an issue with multiple value fields and should
				// be fixed within WordPress.

// 				$output .= '<div style="padding:0 1em;margin:1em 0;border:1px solid #ccc;border-radius:4px;">';
// 				$output .= '<ul>';
// 				foreach( $valid_read_caps as $valid_read_cap ) {
// 					if ( $capability = Groups_Capability::read_by_capability( $valid_read_cap ) ) {
// 						$checked = in_array( $capability->capability, $read_caps ) ? ' checked="checked" ' : '';
// 						$output .= '<li>';
// 						$output .= '<label>';
// 						$output .= '<input name="attachments[' . $post->ID . '][' . self::CAPABILITY . '][]" ' . $checked . ' type="checkbox" value="' . esc_attr( $capability->capability_id ) . '" />';
// 						$output .= wp_filter_nohtml_kses( $capability->capability );
// 						$output .= '</label>';
// 						$output .= '</li>';
// 					}
// 				}
// 				$output .= '</ul>';
// 				$output .= '</div>';

				$show_groups = Groups_Options::get_user_option( self::SHOW_GROUPS, true );
				$output .= '<div class="select-capability-container">';
				$select_id = 'attachments-' . $post->ID . '-' . self::CAPABILITY;
				$output .= sprintf(
					'<select id="%s" class="select capability" name="%s" multiple="multiple" data-placeholder="%s" title="%s">',
					$select_id,
					'attachments[' . $post->ID . '][' . self::CAPABILITY . '][]',
					__( 'Type and choose &hellip;', GROUPS_PLUGIN_DOMAIN),
					__( 'Choose one or more capabilities to restrict access. Groups that grant access through the capabilities are shown in parenthesis. If no capabilities are available yet, you can use the quick-create box to create a group and capability enabled for access restriction on the fly.', GROUPS_PLUGIN_DOMAIN )
				);
				$output .= '<option value=""></option>';
				foreach( $valid_read_caps as $valid_read_cap ) {
					if ( $capability = Groups_Capability::read_by_capability( $valid_read_cap ) ) {
						if ( $user->can( $capability->capability ) ) {
							$c = new Groups_Capability( $capability->capability_id );
							$groups = $c->groups;
							$group_names = array();
							if ( !empty( $groups ) ) {
								foreach( $groups as $group ) {
									$group_names[] = $group->name;
								}
							}
							if ( count( $group_names ) > 0 ) {
								$label_title = sprintf(
									_n(
										'Members of the %1$s group can access this %2$s through this capability.',
										'Members of the %1$s groups can access this %2$s through this capability.',
										count( $group_names ),
										GROUPS_PLUGIN_DOMAIN
									),
									wp_filter_nohtml_kses( implode( ',', $group_names ) ),
									$post_singular_name
								);
							} else {
								$label_title = __( 'No groups grant access through this capability. To grant access to group members using this capability, you should assign it to a group and enable the capability for access restriction.', GROUPS_PLUGIN_DOMAIN );
							}
							$output .= sprintf( '<option value="%s" %s>', esc_attr( $capability->capability_id ), in_array( $capability->capability, $read_caps ) ? ' selected="selected" ' : '' );
							$output .= wp_filter_nohtml_kses( $capability->capability );
							if ( $show_groups ) {
								if ( count( $group_names ) > 0 ) {
									$output .= ' ';
									$output .= '(' . wp_filter_nohtml_kses( implode( ', ', $group_names ) ) . ')';
								}
							}
							$output .= '</option>';
						}
					}
				}
				$output .= '</select>';

				$output .= Groups_UIE::render_select( '#'.$select_id );

				$output .= '</div>';

				$output .= '<p class="description">';
				$output .= sprintf( __( "Only groups or users that have one of the selected capabilities are allowed to read this %s.", GROUPS_PLUGIN_DOMAIN ), $post_singular_name );
				$output .= '</p>';

				$form_fields['groups_access'] = array(
					'label' => __( 'Access restrictions', GROUPS_PLUGIN_DOMAIN ),
					'input' => 'html',
					'html' => $output
				);
			}
		}
		return $form_fields;
	}

	/**
	 * Save capabilities for attachment post type (Media).
	 * When multiple attachments are saved, this is called once for each.
	 * @param array $post post data
	 * @param array $attachment attachment field data
	 * @return array
	 */
	public static function attachment_fields_to_save( $post, $attachment ) {
		$post_types_option = Groups_Options::get_option( Groups_Post_Access::POST_TYPES, array() );
		if ( !isset( $post_types_option['attachment']['add_meta_box'] ) || $post_types_option['attachment']['add_meta_box'] ) {
			// if we're here, we assume the user is allowed to edit attachments,
			// but we still need to check if the user can restrict access
			if ( self::user_can_restrict() ) {
				$post_id = null;
				if ( isset( $post['ID'] ) ) {
					$post_id = $post['ID'];
				} else if ( isset( $post['post_ID'] ) ) {
					$post_id = $post['post_ID'];
				}
				if ( $post_id !== null ) {
					$valid_read_caps = self::get_valid_read_caps_for_user();
					foreach( $valid_read_caps as $valid_read_cap ) {
						if ( $capability = Groups_Capability::read_by_capability( $valid_read_cap ) ) {
							if ( !empty( $attachment[self::CAPABILITY] ) && is_array( $attachment[self::CAPABILITY] ) && in_array( $capability->capability_id, $attachment[self::CAPABILITY] ) ) {
								Groups_Post_Access::create( array(
									'post_id' => $post_id,
									'capability' => $capability->capability
								) );
							} else {
								Groups_Post_Access::delete( $post_id, $capability->capability );
							}
						}
					}
				}
			}
		}
		return $post;
	}

	/**
	 * Returns true if the current user has at least one of the capabilities
	 * that can be used to restrict access to posts.
	 * @return boolean
	 */
	public static function user_can_restrict() {
		$has_read_cap = false;
		$user = new Groups_User( get_current_user_id() );
		$valid_read_caps = Groups_Options::get_option( Groups_Post_Access::READ_POST_CAPABILITIES, array( Groups_Post_Access::READ_POST_CAPABILITY ) );
		foreach( $valid_read_caps as $valid_read_cap ) {
			if ( $capability = Groups_Capability::read_by_capability( $valid_read_cap ) ) {
				if ( $user->can( $capability->capability_id ) ) {
					$has_read_cap = true;
					break;
				}
			}
		}
		return $has_read_cap;
	}

	/**
	 * @return array of valid read capabilities for the current or given user
	 */
	public static function get_valid_read_caps_for_user( $user_id = null ) {
		$result = array();
		$user = new Groups_User( $user_id === null ? get_current_user_id() : $user_id );
		$valid_read_caps = Groups_Options::get_option( Groups_Post_Access::READ_POST_CAPABILITIES, array( Groups_Post_Access::READ_POST_CAPABILITY ) );
		foreach( $valid_read_caps as $valid_read_cap ) {
			if ( $capability = Groups_Capability::read_by_capability( $valid_read_cap ) ) {
				if ( $user->can( $capability->capability ) ) {
					$result[] = $valid_read_cap;
				}
			}
		}
		return $result;
	}
} 
Groups_Access_Meta_Boxes::init();
