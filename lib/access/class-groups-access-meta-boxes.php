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

	const CAPABILITY_NONCE = 'groups-meta-box-capability-nonce';
	const SET_CAPABILITY   = 'set-capability';
	const READ_ACCESS      = 'read-access';
	const CAPABILITY       = 'capability';
	const SHOW_GROUPS      = 'access-meta-box-show-groups';

	const NONCE          = 'groups-meta-box-nonce';
	const SET_GROUPS     = 'set-groups';
	const GROUPS_READ    = 'groups-read';
	const READ           = 'read';

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

			add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ), 10, 2 );
			add_action( 'save_post', array( __CLASS__, 'save_post' ), 10, 2 );
			add_filter( 'wp_insert_post_empty_content', array( __CLASS__, 'wp_insert_post_empty_content' ), 10, 2 );

			add_filter( 'attachment_fields_to_edit', array( __CLASS__, 'attachment_fields_to_edit' ), 10, 2 );
			add_filter( 'attachment_fields_to_save', array( __CLASS__, 'attachment_fields_to_save' ), 10, 2 );
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
		if ( isset( $pagenow ) ) {
			switch ( $pagenow ) {
				case 'upload.php' :
				case 'customize.php' :
				case 'edit-tags.php' : // @since 2.7.1 [1]
				case 'term.php' : // @since 2.7.1 [1]
					Groups_UIE::enqueue( 'select' );
					break;
			}
		}
		// [1] For cases when attachments can be added to terms, e.g. WC Product Category Thumbnail.
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

				add_meta_box(
					'groups-permissions',
					_x( 'Groups', 'Meta box title', 'groups' ),
					array( __CLASS__, 'groups' ),
					null,
					'side',
					'high'
				);

				Groups_UIE::enqueue( 'select' );

				if ( self::user_can_restrict() ) {
					if ( $screen = get_current_screen() ) {
						// help tab for group-based access restrictions
						$screen->add_help_tab( array(
							'id'      => 'groups-groups',
							'title'   => _x( 'Groups', 'Help tab title', 'groups' ),
							'content' =>
								'<p>' .
								'<strong>' . _x( 'Groups', 'Help heading', 'groups' ) . '</strong>' .
								'</p>' .
								'<p>' .
								__( 'Use the <em>Groups</em> box to limit the visibility of posts, pages and other post types.', 'groups' ) .
								'</p>' .
								'<p>' .
								__( 'You can select one or more groups to restrict access to its members.', 'groups' ) .
								( !current_user_can( GROUPS_ADMINISTER_GROUPS ) ?
									' ' .
									__( 'Note that you must be a member of a group to use it to restrict access.', 'groups' )
									:
									''
								) .
								'</p>' .
								'<p>' .
								'<strong>' . __( 'Example:', 'groups' ) . '</strong>' . 
								'</p>' .
								__( 'Let\'s assume that you want to limit the visibility of a post to members of the <em>Premium</em> group.', 'groups' ) .
								'<p>' .
								' ' .
								'</p>' .
								__( 'Choose or enter <em>Premium</em> in the <em>Read</em> field located in the <em>Groups</em> box and save or update the post (or hit Enter).', 'groups' ) .
								'<p>' .
								( current_user_can( GROUPS_ADMINISTER_GROUPS ) ?
									'<p>' .
									__( 'In the same field, you can create a new group and restrict access. Group names are case-sensitive. In order to be able to use the new group, your user account will be assigned to it.', 'groups' ) .
									'</p>'
									:
									''
								)
						) );
					}
				}
			}
		}
	}

	/**
	 * Render meta box for groups.
	 *
	 * @see do_meta_boxes()
	 *
	 * @param Object $object
	 * @param Object $box
	 */
	public static function groups( $object = null, $box = null ) {

		$output = '';

		$post_id   = isset( $object->ID ) ? $object->ID : null;
		$post_type = isset( $object->post_type ) ? $object->post_type : null;
		$post_singular_name = __( 'Post', 'groups' );
		if ( $post_type !== null ) {
			$post_type_object = get_post_type_object( $post_type );
			$labels = isset( $post_type_object->labels ) ? $post_type_object->labels : null;
			if ( $labels !== null ) {
				if ( isset( $labels->singular_name ) ) {
					$post_singular_name = __( $labels->singular_name );
				}
			}
		}

		$output .= wp_nonce_field( self::SET_GROUPS, self::NONCE, true, false );

		$output .= apply_filters( 'groups_access_meta_boxes_groups_before_read_groups', '', $object, $box );

		$output .= '<div class="select-read-groups-container">';

		if ( self::user_can_restrict() ) {

			$include     = self::get_user_can_restrict_group_ids();
			$groups      = Groups_Group::get_groups( array( 'order_by' => 'name', 'order' => 'ASC', 'include' => $include ) );
			$groups_read = get_post_meta( $post_id, Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ );

			$read_help = sprintf(
				__( 'You can restrict the visibility of this %1$s to group members. Choose one or more groups that are allowed to read this %2$s. If no groups are chosen, the %3$s is visible to anyone.', 'groups' ),
				$post_singular_name,
				$post_singular_name,
				$post_singular_name
			);
			if ( current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
				$read_help .= ' ' . __( 'You can create a new group by indicating the group\'s name.', 'groups' );
			}

			$output .= sprintf(
				'<label title="%s">',
				esc_attr( $read_help )
			);
			$output .= __( 'Read', 'groups' );
			$output .= ' ';

			$output .= sprintf(
				'<select class="select groups-read" name="%s" multiple="multiple" placeholder="%s" data-placeholder="%s" title="%s">',
				self::GROUPS_READ . '[]',
				esc_attr( __( 'Anyone &hellip;', 'groups' ) ),
				esc_attr( __( 'Anyone &hellip;', 'groups' ) ),
				esc_attr( $read_help )
			);
			$output .= '<option value=""></option>';
			foreach( $groups as $group ) {
				$output .= sprintf( '<option value="%s" %s>', esc_attr( $group->group_id ), in_array( $group->group_id, $groups_read ) ? ' selected="selected" ' : '' );
				$output .= wp_filter_nohtml_kses( $group->name );
				$output .= '</option>';
			}
			$output .= '</select>';
			$output .= '</label>';
			$output .= Groups_UIE::render_select(
				'.select.groups-read',
				true,
				true,
				current_user_can( GROUPS_ADMINISTER_GROUPS )
			);
			$output .= '<p class="description">';
			$output .= sprintf(
				__( 'Restricts the visibility of this %s to members of the chosen groups.', 'groups' ),
				$post_singular_name
			);
			$output .= '</p>';

		} else {
			$output .= '<p class="description">';
			$output .= sprintf( __( 'You cannot set any access restrictions.', 'groups' ), $post_singular_name );
			$style = 'cursor:help;vertical-align:middle;';
			if ( current_user_can( GROUPS_ADMINISTER_OPTIONS ) ) {
				$style = 'cursor:pointer;vertical-align:middle;';
				$output .= sprintf( '<a href="%s">', esc_url( admin_url( 'admin.php?page=groups-admin-options' ) ) );
			}
			$output .= sprintf( '<img style="%s" alt="?" title="%s" src="%s" />', $style, esc_attr( __( 'You need to have permission to set access restrictions.', 'groups' ) ), esc_attr( GROUPS_PLUGIN_URL . 'images/help.png' ) );
			if ( current_user_can( GROUPS_ADMINISTER_OPTIONS ) ) {
				$output .= '</a>';
			}
			$output .= '</p>';
		}

		$output .= '</div>'; // .select-read-groups-container

		$output .= apply_filters( 'groups_access_meta_boxes_groups_after_read_groups', '', $object, $box );

		$output = apply_filters( 'groups_access_meta_boxes_groups', $output, $object, $box );

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
	 * Save the group access restriction.
	 * 
	 * @param int $post_id
	 * @param mixed $post post data (not used here)
	 */
	public static function save_post( $post_id = null, $post = null ) {
		// This is called multiple times and if a new post is created and a new group is requested*,
		// we can end up without the new group being assigned to the post unless we duely check
		// for revision and autosave:
		// (* on the second call, the new group exists and it will bail out on "if ( !( $group = Groups_Group::read_by_name( $name ) ) ) { ...")
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) ) {
		} else {
			$post_type = get_post_type( $post_id );
			$post_type_object = get_post_type_object( $post_type );
			if ( $post_type_object && $post_type != 'attachment' ) {
				$post_types_option = Groups_Options::get_option( Groups_Post_Access::POST_TYPES, array() );
				if ( !isset( $post_types_option[$post_type]['add_meta_box'] ) || $post_types_option[$post_type]['add_meta_box'] ) {

					if ( self::user_can_restrict() ) {
						if ( isset( $_POST[self::NONCE] ) && wp_verify_nonce( $_POST[self::NONCE], self::SET_GROUPS ) ) {
							$post_type = isset( $_POST['post_type'] ) ? $_POST['post_type'] : null;
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
									$include = self::get_user_can_restrict_group_ids();
									$groups  = Groups_Group::get_groups( array( 'order_by' => 'name', 'order' => 'ASC', 'include' => $include ) );
									$user_group_ids_deep = array();
									foreach( $groups as $group ) {
										$user_group_ids_deep[] = $group->group_id;
									}
									$group_ids = array();
									$submitted_group_ids = !empty( $_POST[self::GROUPS_READ] ) && is_array( $_POST[self::GROUPS_READ] ) ? $_POST[self::GROUPS_READ] : array();

									// assign requested groups and create and assign new groups if allowed
									foreach( $submitted_group_ids as $group_id ) {
										if ( is_numeric( $group_id ) ) {
											if ( in_array( $group_id, $user_group_ids_deep ) ) {
												$group_ids[] = $group_id;
											}
										} else {
											if ( current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
												$creator_id = get_current_user_id();
												$datetime   = date( 'Y-m-d H:i:s', time() );
												$name       = ucwords( strtolower( trim( preg_replace( '/\s+/', ' ', $group_id ) ) ) );
												if ( strlen( $name ) > 0 ) {
													if ( !( $group = Groups_Group::read_by_name( $name ) ) ) {
														if ( $group_id = Groups_Group::create( compact( 'creator_id', 'datetime', 'name' ) ) ) {
															if ( Groups_User_Group::create( array( 'user_id' => $creator_id, 'group_id' => $group_id ) ) ) {
																$group_ids[] = $group_id;
															}
														}
													}
												}
											}
										}
									}

									do_action( 'groups_access_meta_boxes_before_groups_read_update', $post_id, $group_ids );
									$update_result = Groups_Post_Access::update( array( 'post_id' => $post_id, 'groups_read' => $group_ids ) );
									do_action( 'groups_access_meta_boxes_after_groups_read_update', $post_id, $group_ids, $update_result );
								}

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
		if ( !wp_script_is( 'selectize' ) ) {
			wp_enqueue_script( 'selectize', GROUPS_PLUGIN_URL . 'js/selectize/selectize.min.js', array( 'jquery' ), $groups_version, false );
		}
		if ( !wp_style_is( 'selectize' ) ) {
			wp_enqueue_style( 'selectize', GROUPS_PLUGIN_URL . 'css/selectize/selectize.bootstrap2.css', array(), $groups_version );
		}
	}

	/**
	 * Render groups box for attachment post type (Media).
	 * 
	 * @param array $form_fields
	 * @param object $post
	 * @return array
	 */
	public static function attachment_fields_to_edit( $form_fields, $post ) {

		Groups_UIE::enqueue( 'select' );

		$post_types_option = Groups_Options::get_option( Groups_Post_Access::POST_TYPES, array() );

		if ( !isset( $post_types_option['attachment']['add_meta_box'] ) || $post_types_option['attachment']['add_meta_box'] ) {

			if ( self::user_can_restrict() ) {

				$include     = self::get_user_can_restrict_group_ids();
				$groups      = Groups_Group::get_groups( array( 'order_by' => 'name', 'order' => 'ASC', 'include' => $include ) );
				$groups_read = get_post_meta( $post->ID, Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ );

				$output = '';
				$post_singular_name = __( 'Media', 'groups' );

				$output .= __( 'Read', 'groups' );

				// On attachments edited within the 'Insert Media' popup, the update is triggered too soon and we end up with only the last capability selected.
				// This occurs when using normal checkboxes as well as the select below (Chosen and Selectize tested).
				// With checkboxes it's even more confusing, it's actually better to have it using a select as below,
				// because the visual feedback corresponds with what is assigned.
				// See http://wordpress.org/support/topic/multiple-access-restrictions-for-media-items-are-not-saved-in-grid-view
				// and https://core.trac.wordpress.org/ticket/28053 - this is an issue with multiple value fields and should
				// be fixed within WordPress.

// 				$output .= '<div style="padding:0 1em;margin:1em 0;border:1px solid #ccc;border-radius:4px;">';
// 				$output .= '<ul>';
// 				foreach( $groups as $group ) {
// 						$checked = in_array( $group->group_id, $groups_read ) ? ' checked="checked" ' : '';
// 						$output .= '<li>';
// 						$output .= '<label>';
// 						$output .= '<input name="attachments[' . $post->ID . '][' . self::GROUPS_READ . '][]" ' . $checked . ' type="checkbox" value="' . esc_attr( $group->group_id ) . '" />';
// 						$output .= wp_filter_nohtml_kses( $group->name );
// 						$output .= '</label>';
// 						$output .= '</li>';
// 				}
// 				$output .= '</ul>';
// 				$output .= '</div>';

				$output .= '<div class="select-groups-container">';
				$select_id = 'attachments-' . $post->ID . '-' . self::GROUPS_READ;
				$output .= sprintf(
					'<select id="%s" class="select groups-read" name="%s" multiple="multiple" placeholder="%s" data-placeholder="%s" title="%s">',
					$select_id,
					'attachments[' . $post->ID . '][' . self::GROUPS_READ . '][]',
					esc_attr( __( 'Anyone &hellip;', 'groups' ) ),
					esc_attr( __( 'Anyone &hellip;', 'groups' ) ),
					__( 'You can restrict the visibility to group members. Choose one or more groups to restrict access. If no groups are chosen, this entry is visible to anyone.', 'groups' ) .
					current_user_can( GROUPS_ADMINISTER_GROUPS ) ? ' ' . __( 'You can create a new group by indicating the group\'s name.', 'groups' ) : ''
				);
				$output .= '<option value=""></option>';
				foreach( $groups as $group ) {
					$output .= sprintf( '<option value="%s" %s>', esc_attr( $group->group_id ), in_array( $group->group_id, $groups_read ) ? ' selected="selected" ' : '' );
					$output .= wp_filter_nohtml_kses( $group->name );
					$output .= '</option>';
				}
				$output .= '</select>';

				$output .= Groups_UIE::render_select( '#'.$select_id, true, true, current_user_can( GROUPS_ADMINISTER_GROUPS ) );

				$output .= '</div>';

				$output .= '<p class="description">';
				$output .= __( 'Restricts the visibility of this entry to members of the chosen groups.', 'groups' );
				$output .= '</p>';

				$output .= '<p class="description">';
				$output .= __( 'The attachment page is restricted to authorized users, but due to technical limitations, the file can still be accessed directly via its URL.', 'groups' );
				$output .= ' ';
				$output .= sprintf( __( 'Please use <a href="%s" target="_blank">Groups File Access</a> for files that require complete protection.', 'groups' ), esc_url( 'http://www.itthinx.com/shop/groups-file-access/' ) );
				$output .= '</p>';

				$form_fields['groups_read'] = array(
					'label' => _x( 'Groups', 'Attachment field label', 'groups' ),
					'input' => 'html',
					'html' => $output
				);
			}
		}
		return $form_fields;
	}

	/**
	 * Save groups for attachment post type (Media).
	 * When multiple attachments are saved, this is called once for each.
	 * 
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
					$include = self::get_user_can_restrict_group_ids();
					$groups  = Groups_Group::get_groups( array( 'order_by' => 'name', 'order' => 'ASC', 'include' => $include ) );
					$group_ids = array();
					if ( !empty( $attachment[self::GROUPS_READ] ) && is_array( $attachment[self::GROUPS_READ] ) ) {
						foreach( $groups as $group ) {
							if ( in_array( $group->group_id, $attachment[self::GROUPS_READ] ) ) {
								$group_ids[] = $group->group_id;
							}
						}
					}
					do_action( 'groups_access_meta_boxes_before_groups_read_update', $post_id, $group_ids );
					$update_result = Groups_Post_Access::update( array( 'post_id' => $post_id, 'groups_read' => $group_ids ) );
					do_action( 'groups_access_meta_boxes_after_groups_read_update', $post_id, $group_ids, $update_result );
				}
			}
		}
		return $post;
	}

	/**
	 * Returns true if the user can restrict access to posts. The current user is
	 * assumed by default unless a user ID is provided.
	 * 
	 * @param int $user_id indicates the desired user, otherwise for the current user
	 * @return boolean
	 */
	public static function user_can_restrict( $user_id = null ) {
		if ( $user_id === null ) {
			$user_id = get_current_user_id();
		}
		$user = new Groups_User( $user_id);
		return $user->can( GROUPS_RESTRICT_ACCESS );
	}

	/**
	 * Returns the group IDs of the groups that the user can use to restrict access.
	 * 
	 * If the user can GROUPS_RESTRICT_ACCESS, the following group IDs are returned:
	 * - If the user can GROUPS_ADMINISTER_GROUPS, this will return the IDs of all groups.
	 * - Otherwise it will return the IDs of all groups that the user belongs to, directly
	 * or indirectly by group inheritance.
	 * 
	 * If the user can not GROUPS_RESTRICT_ACCESS, an empty array is returned.
	 * 
	 * @param int $user_id if provided, retrieve results for the user indicated by user ID, otherwise for the current user
	 * @return array of int with the group IDs
	 */
	public static function get_user_can_restrict_group_ids( $user_id = null ) {
		$group_ids = array();
		if ( $user_id === null ) {
			$user_id = get_current_user_id();
		}
		if ( self::user_can_restrict( $user_id ) ) {
			if ( current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
				$group_ids = Groups_Group::get_group_ids();
			} else {
				$user = new Groups_User( $user_id );
				$group_ids = $user->group_ids_deep;
			}
			if ( !empty( $group_ids ) && is_array( $group_ids ) ) {
				$group_ids = array_map (array( 'Groups_Utility','id'), $group_ids );
			}
		}
		return $group_ids;
	}

	/**
	 * @deprecated
	 * @return array of valid read capabilities for the current or given user
	 */
	public static function get_valid_read_caps_for_user( $user_id = null ) {
		require_once( GROUPS_LEGACY_LIB . '/access/class-groups-access-meta-boxes-legacy.php' );
		return Groups_Access_Meta_Boxes_Legacy::get_valid_read_caps_for_user( $user_id );
	}
} 
Groups_Access_Meta_Boxes::init();
