<?php
/**
 * class-groups-post-access.php
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
 * Post access restrictions.
 */
class Groups_Post_Access {

	/**
	 * @var string
	 */
	const POSTMETA_PREFIX = 'groups-';

	/**
	 * @var string
	 */
	const READ = 'read';

	/**
	 * @var string
	 */
	const CACHE_GROUP = 'groups';

	/**
	 * 
	 * @var string
	 */
	const CAN_READ_POST = 'can_read_post';

	/**
	 * @deprecated
	 * @var string
	 */
	const READ_POST_CAPABILITY = 'groups_read_post';

	/**
	 * @deprecated
	 * @var string
	 */
	const READ_POST_CAPABILITY_NAME = 'Read Post';

	/**
	 * @deprecated
	 * @var string
	 */
	const READ_POST_CAPABILITIES = 'read_post_capabilities';

	/**
	 * @var string
	 */
	const POST_TYPES = 'post_types';

	/**
	 * @var string
	 */
	const COUNT_POSTS = 'count-posts';

	/**
	 * Work done on activation, currently does nothing.
	 * @see Groups_Controller::activate()
	 */
	public static function activate() {
	}

	/**
	 * Sets up filters to restrict access.
	 */
	public static function init() {
		// post access
		add_filter( 'posts_where', array( __CLASS__, 'posts_where' ), 10, 2 );
		add_filter( 'get_pages', array( __CLASS__, 'get_pages' ), 1 );
		if ( apply_filters( 'groups_filter_the_posts', false ) ) {
			add_filter( 'the_posts', array( __CLASS__, 'the_posts' ), 1, 2 );
		}
		// If we had a get_post filter https://core.trac.wordpress.org/ticket/12955
		// add_filter( 'get_post', ... );
		add_filter( 'wp_get_nav_menu_items', array( __CLASS__, 'wp_get_nav_menu_items' ), 1, 3 );
		// content access
		add_filter( 'get_the_excerpt', array( __CLASS__, 'get_the_excerpt' ), 1 );
		add_filter( 'the_content', array( __CLASS__, 'the_content' ), 1 );
		// edit & delete post
		add_filter( 'map_meta_cap', array( __CLASS__, 'map_meta_cap' ), 10, 4 );
		// @todo these could be interesting to add later ...
		// add_filter( "plugin_row_meta", array( __CLASS__, "plugin_row_meta" ), 1 );
		// add_filter( "posts_join_paged", array( __CLASS__, "posts_join_paged" ), 1 );
		// add_filter( "posts_where_paged", array( __CLASS__, "posts_where_paged" ), 1 );

		add_action( 'groups_deleted_group', array( __CLASS__, 'groups_deleted_group' ) );
		add_filter( 'wp_count_posts', array( __CLASS__, 'wp_count_posts' ), 10, 3 );
		// @todo enable the filter and implement below if needed to correct attachment counts
		// add_filter( 'wp_count_attachments', array( __CLASS__, 'wp_count_attachments' ), 10, 2 );

		// REST API
		$post_types = self::get_handles_post_types();
		if ( !empty( $post_types ) ) {
			foreach( $post_types as $post_type => $handles ) {
				if ( $handles ) {
					add_filter( "rest_prepare_{$post_type}", array( __CLASS__, 'rest_prepare_post' ), 10, 3 );
				}
			}
		}

		// adjacent posts
		add_filter( 'get_previous_post_where', array( __CLASS__, 'get_previous_post_where' ), 10, 5 );
		add_filter( 'get_next_post_where', array( __CLASS__, 'get_next_post_where' ), 10, 5 );
	}

	/**
	 * Replicates the response for invalid post IDs when unauthorized access to a post is requested.
	 * There is no filter in WP_REST_Posts_Controller::get_post() nor in get_post() that we could use (WP 4.8).
	 *
	 * REST API Handbook https://developer.wordpress.org/rest-api/
	 *
	 * For development tests:
	 *
	 * 1. Install https://github.com/WP-API/Basic-Auth
	 * 2. Protect post 1 with group "Test".
	 * 3. Test access denied: $ curl http://example.com/wp-json/wp/v2/posts/1
	 * 4. Test access granted $ curl --user username:password https://example.com/wp-json/wp/v2/posts/1
	 *
	 * On #4 username:password are cleartext, username must belong to group "Test".
	 *
	 * @param array $response
	 * @param WP_Post $post
	 * @param string $request
	 * @return string[]|number[][]
	 */
	public static function rest_prepare_post( $response, $post, $request ) {
		if ( isset( $post->ID ) && !self::user_can_read_post( $post->ID ) ) {
			$response = array(
				'code' => 'rest_post_invalid_id',
				'message' => __( 'Invalid post ID.' ),
				'data' => array( 'status' => 404 )
			);
		}
		return $response;
	}

	/**
	 * Restrict access to edit or delete posts based on the post's access restrictions.
	 * 
	 * @param array $caps
	 * @param string $cap
	 * @param int $user_id
	 * @param array $args
	 * @return array
	 */
	public static function map_meta_cap( $caps, $cap, $user_id, $args ) {
		if ( isset( $args[0] ) ) {
			if ( strpos( $cap, 'edit_' ) === 0 || strpos( $cap, 'delete_' ) === 0 ) {
				if ( $post_type = get_post_type( $args[0] ) ) {

					$edit_post_type   = 'edit_' . $post_type;
					$delete_post_type = 'delete_' . $post_type;
					if ( $post_type_object = get_post_type_object( $post_type ) ) {
						if ( !isset( $post_type_object->capabilities ) ) {
							$post_type_object->capabilities = array();
						}
						$caps_object = get_post_type_capabilities( $post_type_object );
						if ( isset( $caps_object->edit_post ) ) {
							$edit_post_type = $caps_object->edit_post;
						}
						if ( isset( $caps_object->delete_post ) ) {
							$delete_post_type = $caps_object->delete_post;
						}
					}

					if ( $cap === $edit_post_type || $cap === $delete_post_type ) {
						$post_id = null;
						if ( is_numeric( $args[0] ) ) {
							$post_id = $args[0]; 
						} else if ( $args[0] instanceof WP_Post ) {
							$post_id = $args[0]->ID;
						}
						if ( $post_id ) {
							if ( !self::user_can_read_post( $post_id, $user_id ) ) {
								$caps[] = 'do_not_allow';
							}
						}
					}
				}
			}
		}
		return $caps;
	}

	/**
	 * Filters out posts that the user should not be able to access.
	 * 
	 * @param string $where current where conditions
	 * @param WP_Query $query current query
	 * @return string modified $where
	 */
	public static function posts_where( $where, $query ) {

		global $wpdb;

		if ( apply_filters( 'groups_post_access_posts_where_apply', true, $where, $query ) ) {

			$user_id = get_current_user_id();

			// this only applies to logged in users
			if ( _groups_admin_override() ) {
				return $where;
			}

			// Groups admins see everything
			if ( current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
				return $where;
			}

			if ( !apply_filters( 'groups_post_access_posts_where_filter_all', false ) ) {
				$filter = true;
				$post_types = apply_filters(
					'groups_post_access_posts_where_query_get_post_types',
					$query->get( 'post_type', null ),
					$where,
					$query
				);
				if ( 'any' == $post_types ) {
					// we need to filter in this case as it affects any post type
				} else if ( !empty( $post_types ) && is_array( $post_types ) ) {
					// if there is at least one post type we handle, we need to filter
					$handled = 0;
					$handles_post_types = self::get_handles_post_types();
					foreach( $post_types as $post_type ) {
						if ( !isset( $handles_post_types[$post_type] ) || $handles_post_types[$post_type] ) {
							$handled++;
						}
					}
					$filter = $handled > 0;
				} else if ( !empty( $post_types ) && is_string( $post_types ) ) {
					$filter = self::handles_post_type( $post_types );
				} else if ( $query->is_attachment ) {
					$filter = self::handles_post_type( 'attachment' );
				} else if ( $query->is_page ) {
					$filter = self::handles_post_type( 'page' );
				} else {
					$filter = self::handles_post_type( 'post' );
				}
				if ( !$filter ) {
					return $where;
				}
			}

			$handles_post_types = Groups_Post_Access::get_handles_post_types();
			$post_types = array();
			foreach( $handles_post_types as $post_type => $handles ) {
				if ( $handles ) {
					$post_types[] = $post_type;
				}
			}
			if ( count( $post_types ) == 0 ) {
				return $where;
			}
			$post_types_in = "'" . implode( "','", array_map( 'esc_sql', $post_types ) ) . "'";

			// 1. Get all the groups that the user belongs to, including those that are inherited:
			$group_ids = array();
			if ( $user = new Groups_User( $user_id ) ) {
				$group_ids_deep = $user->group_ids_deep;
				if ( is_array( $group_ids_deep ) ) {
					$group_ids = $group_ids_deep;
				}
			}

			if ( count( $group_ids ) > 0 ) {
				$group_ids = "'" . implode( "','", $group_ids ) . "'";
			} else {
				$group_ids = '\'\'';
			}

			// 2. Filter the posts:
			// This allows the user to access posts where the posts are not restricted or where
			// the user belongs to ANY of the groups:
// 			$where .= sprintf(
// 				" AND {$wpdb->posts}.ID IN " .
// 				" ( " .
// 				"   SELECT ID FROM $wpdb->posts WHERE post_type NOT IN (%s) OR ID NOT IN ( SELECT post_id FROM $wpdb->postmeta WHERE {$wpdb->postmeta}.meta_key = '%s' ) " . // posts of a type that is not handled or posts without access restriction
// 				"   UNION ALL " . // we don't care about duplicates here, just make it quick
// 				"   SELECT post_id AS ID FROM $wpdb->postmeta WHERE {$wpdb->postmeta}.meta_key = '%s' AND {$wpdb->postmeta}.meta_value IN (%s) " . // posts that require any group the user belongs to
// 				" ) ",
// 				$post_types_in,
// 				self::POSTMETA_PREFIX . self::READ,
// 				self::POSTMETA_PREFIX . self::READ,
// 				$group_ids
// 			);
			// New faster version - Exclude any post IDs from:
			// posts restricted to groups that the user does not belong to MINUS posts restricted to groups to which the user belongs
			$where .= sprintf(
				" AND {$wpdb->posts}.ID NOT IN ( " .
					"SELECT ID FROM $wpdb->posts WHERE " .
						"post_type IN (%s) AND " .
						"ID IN ( " .
							"SELECT post_id FROM $wpdb->postmeta pm WHERE " .
								"pm.meta_key = '%s' AND pm.meta_value NOT IN (%s) AND " .
								"post_id NOT IN ( SELECT post_id FROM $wpdb->postmeta pm WHERE pm.meta_key = '%s' AND pm.meta_value IN (%s) ) " .
						") " .
				") ",
				$post_types_in,
				esc_sql( self::POSTMETA_PREFIX . self::READ ),
				$group_ids,
				esc_sql( self::POSTMETA_PREFIX . self::READ ),
				$group_ids
			);
		}

		return apply_filters( 'groups_post_access_posts_where', $where, $query );
	}

	/**
	 * Filter pages by access capability.
	 * 
	 * @param array $pages
	 */
	public static function get_pages( $pages ) {
		$result = array();
		if ( apply_filters( 'groups_post_access_get_pages_apply', true, $pages ) ) {
			$user_id = get_current_user_id();
			foreach ( $pages as $page ) {
				if ( self::user_can_read_post( $page->ID, $user_id ) ) {
					$result[] = $page;
				}
			}
		} else {
			$result = $pages;
		}
		return $result;
	}

	/**
	 * Filter posts by access capability.
	 * 
	 * @param array $posts list of posts
	 * @param WP_Query $query
	 */
	public static function the_posts( $posts, &$query ) {
		$result = array();
		if ( apply_filters( 'groups_post_access_the_posts_apply', true, $posts, $query ) ) {
			$user_id = get_current_user_id();
			foreach ( $posts as $post ) {
				if ( self::user_can_read_post( $post->ID, $user_id ) ) {
					$result[] = $post;
				}
			}
		} else {
			$result = $posts;
		}
		return $result;
	}

	/**
	 * Filter menu items by access capability.
	 * 
	 * @todo admin section: this won't inhibit the items being offered to be added, although when they're added they won't show up in the menu
	 * 
	 * @param array $items
	 * @param mixed $menu
	 * @param array $args
	 */
	public static function wp_get_nav_menu_items( $items = null, $menu = null, $args = null ) {
		$result = array();
		if ( apply_filters( 'groups_post_access_wp_get_nav_menu_items_apply', true, $items, $menu, $args ) ) {
			$user_id = get_current_user_id();
			foreach ( $items as $item ) {
				// @todo might want to check $item->object and $item->type first,
				// for example these are 'page' and 'post_type' for a page
				if ( self::user_can_read_post( $item->object_id, $user_id ) ) {
					$result[] = $item;
				}
			}
		} else {
			$result = $items;
		}
		return $result;
	}

	/**
	 * Filter excerpt by access capability.
	 * 
	 * @param string $output
	 * @return $output if access granted, otherwise ''
	 */
	public static function get_the_excerpt( $output ) {
		global $post;
		$result = '';
		if ( apply_filters( 'groups_post_access_get_the_excerpt_apply', true, $output ) ) {
			if ( isset( $post->ID ) ) {
				if ( self::user_can_read_post( $post->ID ) ) {
					$result = $output;
				}
			} else {
				// not a post, don't interfere
				$result = $output;
			}
		} else {
			$result = $output;
		}
		return $result;
	}

	/**
	 * Filter content by access capability.
	 *
	 * @param string $output
	 * @return $output if access granted, otherwise ''
	 */
	public static function the_content( $output ) {
		global $post;
		$result = '';
		if ( apply_filters( 'groups_post_access_the_content_apply', true, $output ) ) {
			if ( isset( $post->ID ) ) {
				if ( self::user_can_read_post( $post->ID ) ) {
					$result = $output;
				}
			} else {
				// not a post, don't interfere
				$result = $output;
			}
		} else {
			$result = $output;
		}
		return $result;
	}

	/**
	 * Hooked on the get_{$adjacent}_post_where filter to remove restricted posts.
	 *
	 * @param string $where
	 * @param boolean $in_same_term
	 * @param array $excluded_terms
	 * @param string $taxonomy
	 * @param WP_Post $post
	 *
	 * @return string $where modified if appropriate
	 */
	public static function get_previous_post_where( $where, $in_same_term, $excluded_terms, $taxonomy, $post ) {
		return self::get_next_post_where( $where, $in_same_term, $excluded_terms, $taxonomy, $post );
	}

	/**
	 * Hooked on the get_{$adjacent}_post_where filter to remove restricted posts.
	 *
	 * @param string $where
	 * @param boolean $in_same_term
	 * @param array $excluded_terms
	 * @param string $taxonomy
	 * @param WP_Post $post
	 *
	 * @return string $where modified if appropriate
	 */
	public static function get_next_post_where( $where, $in_same_term, $excluded_terms, $taxonomy, $post ) {
		if ( !empty( $post ) ) {
			// run it through get_posts with suppress_filters set to false so that our posts_where filter is applied and assures only accessible posts are seen
			$post_ids = get_posts( array( 'post_type' => $post->post_type, 'numberposts' => -1, 'suppress_filters' => false, 'fields' => 'ids' ) );
			if ( is_array( $post_ids ) && count( $post_ids ) > 0 ) {
				$post_ids = array_map( 'intval', $post_ids );
				$condition = ' p.ID IN (' . implode( ',', $post_ids ) . ') ';
				if ( !empty( $where ) ) {
					$where .= ' AND ' . $condition;
				} else {
					$where = ' WHERE ' . $condition;
				}
			}
		}
		return $where;
	}

	/**
	 * Adds an access requirement based on post_id and group_id.
	 * 
	 * (*) Revisions : As of Groups 1.3.13 and at WordPress 3.6.1, as
	 * add_post_meta stores postmeta for the revision's parent, we retrieve
	 * the parent's post ID if it applies and check against that to see if
	 * that capability is already present. This is to avoid duplicating
	 * the already existing postmeta entry (which ocurred in previous
	 * versions).
	 * 
	 * @param array $map must contain 'post_id' (*) and 'group_id'
	 * @return true if the capability could be added to the post, otherwise false
	 */
	public static function create( $map ) {
		extract( $map );
		$result = false;

		if ( isset( $capability ) ) {
			_doing_it_wrong(
				__CLASS__ . '::' . __METHOD__,
				__( 'You should use Groups_Post_Access_Legacy::create() to pass a capability restriction instead.', 'groups' ),
				'2.0.0'
			);
		}

		if ( !empty( $post_id ) && !empty( $group_id ) ) {
			$post_id = Groups_Utility::id( $post_id );
			$group_id = Groups_Utility::id( $group_id );
			if ( Groups_Group::read( $group_id ) ) {
				if ( $revision_parent_id = wp_is_post_revision( $post_id ) ) {
					$post_id = $revision_parent_id;
				}
				if ( !in_array( $group_id, get_post_meta( $post_id, self::POSTMETA_PREFIX . self::READ ) ) ) {
					$result = add_post_meta( $post_id, self::POSTMETA_PREFIX . self::READ, $group_id );
				}
			}
		}
		return $result;
	}

	/**
	 * Returns true if the post requires the user to be a member of the given group(s) to grant access.
	 * 
	 * @param int $post_id ID of the post
	 * @param array $map should provide 'post_id' and 'groups_read'
	 * 
	 * @return true if the group(s) is required, otherwise false
	 */
	public static function read( $post_id, $map = array() ) {
		extract( $map );
		$result = false;
		if ( !empty( $post_id ) ) {
			if ( isset( $groups_read ) ) {
				if ( empty( $groups_read ) ) {
					$groups_read = array();
				} else if ( !is_array( $groups_read ) ) {
					$groups_read = array( $groups_read );
				}
				$group_ids = get_post_meta( $post_id, self::POSTMETA_PREFIX . self::READ );
				if ( $group_ids ) {
					foreach( $groups_read as $group_id ) {
						$result = in_array( $group_id, $group_ids );
						if ( !$result ) {
							break;
						}
					}
				}
			}
		}
		return $result;
	}

	/**
	 * Update the post access restrictions.
	 * 
	 * $map must provide 'post_id' (int) indicating the post's ID and 'groups_read' (int|array of int) holding group IDs that restrict read access.
	 * 
	 * @param array $map
	 * @return array of group ids, false on failure
	 */
	public static function update( $map ) {
		extract( $map );
		$result = false;
		if ( !empty( $post_id ) ) {
			if ( empty( $groups_read ) ) {
				$groups_read = array();
			} else if ( !is_array( $groups_read ) ) {
				$groups_read = array( $groups_read );
			}
			$groups_read = array_map( array( 'Groups_Utility', 'id' ), $groups_read );
			$current_groups_read = get_post_meta( $post_id, self::POSTMETA_PREFIX . self::READ );
			$current_groups_read = array_map( array( 'Groups_Utility', 'id' ), $current_groups_read );
			foreach( $groups_read as $group_id ) {
				if ( !in_array( $group_id, $current_groups_read ) ) {
					add_post_meta( $post_id, self::POSTMETA_PREFIX . self::READ, $group_id );
				}
			}
			foreach( $current_groups_read as $group_id ) {
				if ( !in_array( $group_id, $groups_read ) ) {
					delete_post_meta( $post_id, self::POSTMETA_PREFIX . self::READ, $group_id );
				}
			}
			$result = array_map( array( 'Groups_Utility', 'id' ), get_post_meta( $post_id, self::POSTMETA_PREFIX . self::READ ) );
		}
		return $result;
	}

	/**
	 * Removes a access restrictions from a post.
	 * 
	 * @param int $post_id
	 * @param array $map must provide 'groups_read' holding group IDs to remove from restricting access to the post; if empty, all access restrictions will be removed
	 * @return true on success, otherwise false
	 */
	public static function delete( $post_id, $map = array() ) {
		extract( $map );
		$result = false;
		if ( !empty( $post_id ) ) {
			if ( empty( $groups_read ) ) {
				$groups_read = array();
			} else if ( !is_array( $groups_read ) ) {
				$groups_read = array( $groups_read );
			}
			$groups_read = array_map( array( 'Groups_Utility', 'id' ), $groups_read );
			if ( !empty( $groups_read ) ) {
				foreach( $groups_read as $group_id ) {
					$result = delete_post_meta( $post_id, self::POSTMETA_PREFIX . self::READ, $group_id );
				}
			} else {
				$result = delete_post_meta( $post_id, self::POSTMETA_PREFIX . self::READ );
			}
		}
		return $result;
	}

	/**
	 * Returns a list of capabilities that grant access to the post.
	 * 
	 * @deprecated
	 * @param int $post_id
	 * @return array of string, capabilities
	 */
	public static function get_read_post_capabilities( $post_id ) {
		_doing_it_wrong(
			__CLASS__ . '::' . __METHOD__,
			__( 'This method is deprecated. You should use Groups_Post_Access_Legacy::get_read_post_capabilities() to retrieve the capabilities instead.', 'groups' ),
			'2.0.0'
		);

		require_once( GROUPS_LEGACY_LIB . '/access/class-groups-post-access-legacy.php' );
		return Groups_Post_Access_Legacy::get_read_post_capabilities( $post_id );
	}

	/**
	 * Returns a list of group IDs that grant read access to the post.
	 *
	 * @param int $post_id
	 * @return array of int, group IDs
	 */
	public static function get_read_group_ids( $post_id ) {
		return get_post_meta( $post_id, self::POSTMETA_PREFIX . self::READ );
	}

	/**
	 * Returns true if the user belongs to any of the groups that grant access to the post.
	 * 
	 * @param int $post_id post id
	 * @param int $user_id user id or null for current user 
	 * @return boolean true if user can read the post
	 */
	public static function user_can_read_post( $post_id, $user_id = null ) {

		$result = false;

		if ( !empty( $post_id ) ) {

			if ( $user_id === null ) {
				$user_id = get_current_user_id();
			}

			$cached = Groups_Cache::get( self::CAN_READ_POST . '_' . $user_id . '_' . $post_id, self::CACHE_GROUP );

			if ( $cached !== null ) {
				$result = $cached->value;
				unset( $cached );
			} else {
				// admin override and Groups admins see everything
				if ( _groups_admin_override() || current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
					$result = true;
				} else {
					// can read if post type is not handled
					if ( $post_type = get_post_type( $post_id ) ) {
						if ( !self::handles_post_type( $post_type ) ) {
							$result = true;
						}
					}
					// check if the user can read
					if ( !$result ) {
						$groups_user = new Groups_User( $user_id );
						$group_ids   = self::get_read_group_ids( $post_id );
						if ( empty( $group_ids ) ) {
							$result = true;
						} else {
							$ids = array_intersect( $groups_user->group_ids_deep, $group_ids );
							$result = !empty( $ids );
						}
					}
				}
				$result = apply_filters( 'groups_post_access_user_can_read_post', $result, $post_id, $user_id );
				Groups_Cache::set( self::CAN_READ_POST . '_' . $user_id . '_' . $post_id, $result, self::CACHE_GROUP );
			}
		}
		return $result;
	}

	/**
	 * Hooks into groups_deleted_group to remove existing access restrictions
	 * based on the deleted group.
	 * 
	 * @param int $group_id the ID of the deleted group
	 */
	public static function groups_deleted_group( $group_id ) {
		if ( $group_id ) {
			delete_metadata( 'post', null, self::POSTMETA_PREFIX . self::READ, $group_id, true );
		}
	}

	/**
	 * Hooked on wp_count_posts to correct the post counts.
	 * 
	 * Note: at WP 4.7.4 through WP_Posts_List_Table::prepare_items() which obtains $post_status
	 * independent of the post type, we will come here for any post status, so don't be surprised
	 * to see this executed e.g. on post type 'post' with e.g. 'wc-completed' post status.
	 *
	 * @param object $counts An object containing the current post_type's post counts by status.
	 * @param string $type the post type
	 * @param string $perm The permission to determine if the posts are 'readable' by the current user.
	 */
	public static function wp_count_posts( $counts, $type, $perm ) {
		$user_id = get_current_user_id();
		$cached = Groups_Cache::get( self::COUNT_POSTS . '_' . $type . '_' . $user_id, self::CACHE_GROUP );
		if ( $cached !== null ) {
			$counts = $cached->value;
			unset( $cached );
		} else {
			if ( self::handles_post_type( $type ) ) {
				foreach( $counts as $post_status => $count ) {
					$query_args = array(
						'fields'           => 'ids',
						'post_type'        => $type,
						'post_status'      => $post_status,
						'numberposts'      => -1, // all
						'suppress_filters' => 0, // don't suppres filters as we need to get restrictions taken into account
						'orderby'          => 'none', // Important! Don't waste time here.
						'no_found_rows'    => true, // performance, omit unnecessary SQL_CALC_FOUND_ROWS in query here
						'nopaging'         => true // no paging is needed, in case it would affect performance
					);
					// WooCommerce Orders
					if ( function_exists( 'wc_get_order_statuses' ) && ( $type == 'shop_order' ) ) {
						$wc_order_statuses = array_keys( wc_get_order_statuses() );
						if ( !in_array( $post_status, $wc_order_statuses ) ) {
							// Skip getting the post count for this status as it's
							// not a valid order status and WC would raise a PHP Notice.
							continue;
						}
					}
					// WooCommerce Subscriptions
					if ( function_exists( 'wcs_get_subscription_statuses' ) && ( $type == 'shop_subscription' ) ) {
						$wc_subscription_statuses = array_keys( wcs_get_subscription_statuses() );
						if ( !in_array( $post_status, $wc_subscription_statuses ) ) {
							// Skip as it's not a valid subscription status
							continue;
						}
					}
					$posts = get_posts( $query_args );
					$count = count( $posts );
					unset( $posts );
					$counts->$post_status = $count;
				}
			}
			Groups_Cache::set( self::COUNT_POSTS . '_' . $type . '_' . $user_id, $counts, self::CACHE_GROUP );
		}
		return $counts;
	}

	/**
	 * Would be hooked on wp_count_attachments to correct the counts but it's not actually
	 * being used in the current media library.
	 * 
	 * @param object $counts An object containing the attachment counts by mime type.
	 * @param string $mime_type The mime type pattern used to filter the attachments counted.
	 */
	public static function wp_count_attachments( $counts, $mime_type ) {
		return $counts;
	}

	/**
	 * Returns true if we are supposed to handle the post type, otherwise false.
	 *
	 * @param string $post_type
	 * @return boolean
	 */
	public static function handles_post_type( $post_type ) {
		$post_types = self::get_handles_post_types();
		return isset( $post_types[$post_type] ) && $post_types[$post_type];
	}

	/**
	 * Returns an array of post types indicating for each whether we handle it (true) or not.
	 * The array is indexed by the post type names.
	 *
	 * @return array indexed by post type names, indicating the value true if we handle it, otherwise false
	 */
	public static function get_handles_post_types() {
		$result = array();
		$post_types_option = Groups_Options::get_option( self::POST_TYPES, array() );
		$post_types = get_post_types( array(), 'objects' );
		foreach( $post_types as $post_type => $object ) {
			$public              = isset( $object->public ) ? $object->public : false;
			$exclude_from_search = isset( $object->exclude_from_search ) ? $object->exclude_from_search : false;
			$publicly_queryable  = isset( $object->publicly_queryable ) ? $object->publicly_queryable : false;
			$show_ui             = isset( $object->show_ui ) ? $object->show_ui : false;
			$show_in_nav_menus   = isset( $object->show_in_nav_menus ) ? $object->show_in_nav_menus : false;

			// by default, handle any post type whose public attribute is true
			$managed =
				$public && ( !isset( $post_types_option[$post_type] ) || !isset( $post_types_option[$post_type]['add_meta_box'] ) ) ||
				isset( $post_types_option[$post_type] ) && isset( $post_types_option[$post_type]['add_meta_box'] ) && $post_types_option[$post_type]['add_meta_box'];
			$result[$post_type] = $managed;
		}
		return $result;
	}

	/**
	 * Set which post types we should handle.
	 *
	 * @param array $post_types of post type names mapped to booleans, indicating to handle or not a post type
	 */
	public static function set_handles_post_types( $post_types ) {
		$post_types_option = Groups_Options::get_option( self::POST_TYPES, array() );
		$available_post_types = get_post_types();
		foreach( $available_post_types as $post_type ) {
			$post_types_option[$post_type]['add_meta_box'] = isset( $post_types[$post_type] ) && $post_types[$post_type];
		}
		Groups_Options::update_option( self::POST_TYPES, $post_types_option );
	}
}
Groups_Post_Access::init();
