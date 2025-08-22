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
	 * @since 2.20.0
	 *
	 * @var \WP_Block block for which to filter
	 */
	private static $filter_get_terms_block = null;

	/**
	 * @since 2.20.0
	 *
	 * @var array widget for which to filter
	 */
	private static $filter_get_terms_widget = null;

	/**
	 * Work done on activation, currently does nothing.
	 *
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

		// These could be interesting to add later ...
		// add_filter( "plugin_row_meta", array( __CLASS__, "plugin_row_meta" ), 1 );
		// add_filter( "posts_join_paged", array( __CLASS__, "posts_join_paged" ), 1 );
		// add_filter( "posts_where_paged", array( __CLASS__, "posts_where_paged" ), 1 );

		add_action( 'groups_deleted_group', array( __CLASS__, 'groups_deleted_group' ) );
		add_filter( 'wp_count_posts', array( __CLASS__, 'wp_count_posts' ), 10, 3 );

		// Enable the filter and implement below if needed to correct attachment counts ...
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
		add_action( 'save_post', array( __CLASS__, 'save_post' ), PHP_INT_MAX );
		add_filter( 'attachment_fields_to_save', array( __CLASS__, 'attachment_fields_to_save' ), PHP_INT_MAX, 2 );

		// @since 2.20.0
		add_filter( 'render_block', array( __CLASS__, 'render_block' ), 10, 3 );
		// @since 2.20.0
		add_filter( 'block_core_navigation_render_inner_blocks', array( __CLASS__, 'block_core_navigation_render_inner_blocks' ) );

		// @since 2.20.0 adds our get_terms filter for Categories blocks:
		add_filter( 'pre_render_block', array( __CLASS__, 'pre_render_block' ), 10, 3 );
		// @since 2.20.0 adds our get_terms filter for Categories widgets rendered as list:
		add_filter( 'widget_categories_args', array( __CLASS__, 'widget_categories_args' ), 10, 2 );
		// @since 2.20.0 adds our get_terms filter for Categories widgets rendered as dropdown:
		add_filter( 'widget_categories_dropdown_args', array( __CLASS__, 'widget_categories_dropdown_args' ), 10, 2 );
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
	 *
	 * @return string[]|number[][]
	 */
	public static function rest_prepare_post( $response, $post, $request ) {
		if ( isset( $post->ID ) && !self::user_can_read_post( $post->ID ) ) {
			$response = array(
				'code' => 'rest_post_invalid_id',
				'message' => __( 'Invalid post ID.' ), // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
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
	 *
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
							$post_type_object->capabilities = array(); // @phpstan-ignore property.notFound
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
	 *
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
			if ( Groups_User::current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
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

				// If post_types is empty and we have a term page (AKA taxonomy archive),
				// try to retrieve the post type from the taxonomy:
				if ( empty( $post_types ) ) {
					if ( $query->is_tax() ) {
						$queried_object = $query->get_queried_object();
						if ( $queried_object instanceof WP_Term ) {
							$taxonomy = get_taxonomy( $queried_object->taxonomy );
							if ( $taxonomy !== false ) {
								if ( property_exists( $taxonomy, 'object_type' ) ) { // object_type property since WP 4.7.0
									$post_types = $taxonomy->object_type;
								}
							}
						}
					}
				}

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
				$group_ids_deep = $user->get_group_ids_deep();
				if ( is_array( $group_ids_deep ) ) {
					$group_ids = $group_ids_deep;
				}
			}
			if ( count( $group_ids ) > 0 ) {
				$group_ids = implode( ',', array_map( 'intval', $group_ids ) );
			} else {
				$group_ids = '0';
			}

			// 2. Filter the posts:
			// This allows the user to access posts where the posts are not restricted or where
			// the user belongs to ANY of the groups:
			// $where .= sprintf(
			// 	" AND {$wpdb->posts}.ID IN " .
			// 	" ( " .
			// 	"   SELECT ID FROM $wpdb->posts WHERE post_type NOT IN (%s) OR ID NOT IN ( SELECT post_id FROM $wpdb->postmeta WHERE {$wpdb->postmeta}.meta_key = '%s' ) " . // posts of a type that is not handled or posts without access restriction
			// 	"   UNION ALL " . // we don't care about duplicates here, just make it quick
			// 	"   SELECT post_id AS ID FROM $wpdb->postmeta WHERE {$wpdb->postmeta}.meta_key = '%s' AND {$wpdb->postmeta}.meta_value IN (%s) " . // posts that require any group the user belongs to
			// 	" ) ",
			// 	$post_types_in,
			// 	self::POSTMETA_PREFIX . self::READ,
			// 	self::POSTMETA_PREFIX . self::READ,
			// 	$group_ids
			// );
			// New faster version - Exclude any post IDs from:
			// posts restricted to groups that the user does not belong to MINUS posts restricted to groups to which the user belongs
			$groups_table = _groups_get_tablename( 'group' );
			$where .= sprintf(
				" AND {$wpdb->posts}.ID NOT IN ( " .
					"SELECT ID FROM $wpdb->posts WHERE " .
						"post_type IN (%s) AND " .
						"ID IN ( " .
							"SELECT post_id FROM $wpdb->postmeta pm WHERE " .
								"pm.meta_key = '%s' AND " .
								"pm.meta_value NOT IN (%s) AND " .
								"pm.meta_value IN ( SELECT group_id FROM $groups_table ) AND " . // @since 2.18.0 also check for group ID value integrity
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
	 *
	 * @return array
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
	 *
	 * @return array
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
	 * Notes for the admin section:
	 * 1. With themes that provide sidebars and widgets, protected items are visible and can be chosen to be added to a menu. But once the menu is saved, those items do not appear, providing a somewhat confusing user experience.
	 * 2. With themes that support full site editing (like Twenty Twenty-Four which gets rid of sidebars and widgets), protected items are not offered to whom is editing a Navigation block. However, protected items that were added by someone who could access them, will be visible.
	 *
	 * @param array $items
	 * @param mixed $menu
	 * @param array $args
	 *
	 * @return array
	 */
	public static function wp_get_nav_menu_items( $items = null, $menu = null, $args = null ) {
		$result = array();
		if ( apply_filters( 'groups_post_access_wp_get_nav_menu_items_apply', true, $items, $menu, $args ) ) {
			$user_id = get_current_user_id();
			foreach ( $items as $item ) {
				// Check whether the menu item is for some post type, otherwise it's for something else which we don't control.
				if ( is_object( $item ) && isset( $item->type ) && $item->type === 'post_type' ) {
					if ( self::user_can_read_post( $item->object_id, $user_id ) ) {
						$result[] = $item;
					}
				} else {
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
	 *
	 * @return string $output if access granted, otherwise ''
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
	 *
	 * @return string $output if access granted, otherwise ''
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
		if (
			!empty( $post ) && // @phpstan-ignore empty.variable
			self::handles_post_type( $post->post_type )
		) {
			$cache_group = self::CACHE_GROUP . '_' . $post->post_type;

			$group_ids = array();
			$user_id = get_current_user_id();
			if ( $user_id ) {
				$groups_user = new Groups_User( $user_id );
				$group_ids = $groups_user->get_group_ids_deep();
				if ( is_array( $group_ids ) ) {
					sort( $group_ids );
					$cache_group .= '_' . implode( '_', $group_ids );
				}
			}

			// remember the cache group for purging
			$stored_cache_groups = Groups_Options::get_option( 'eligible_post_ids_cache_groups', array() );
			if ( !in_array( $cache_group, $stored_cache_groups ) ) {
				$stored_cache_groups[] = $cache_group;
				Groups_Options::update_option( 'eligible_post_ids_cache_groups', $stored_cache_groups );
			}

			$post_ids = array( -1 );
			$cached = Groups_Cache::get( 'eligible_post_ids', $cache_group );
			if ( $cached === null ) {
				// run it through get_posts with suppress_filters set to false so that our posts_where filter is applied and assures only accessible posts are seen
				$post_ids = get_posts( array( 'post_type' => $post->post_type, 'numberposts' => -1, 'suppress_filters' => false, 'fields' => 'ids' ) );
				if ( is_array( $post_ids ) && count( $post_ids ) > 0 ) {
					foreach ( $post_ids as $i => $post_id ) {
						$post_ids[$i] = intval( $post_id );
					}
				} else {
					$post_ids = array( -1 );
				}
				Groups_Cache::set( 'eligible_post_ids', $post_ids, $cache_group );
			} else {
				$post_ids = $cached->value;
			}

			if ( is_array( $post_ids ) && count( $post_ids ) > 0 ) {
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
	 * Clears cached eligible post IDs.
	 *
	 * @since 2.17.0
	 *
	 * @param int $post_id
	 */
	public static function save_post( $post_id ) {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) ) {
		} else {
			$post_type = get_post_type( $post_id );
			if ( self::handles_post_type( $post_type ) ) {
				self::purge_eligible_post_ids_cached( $post_type );
				self::purge_count_posts_cached( $post_type );
			}
		}
	}

	/**
	 * Clear cached eligible post IDs for the 'attachment' post type (the save_post action is not triggered for those).
	 *
	 * @since 2.17.0
	 *
	 * @param array $post
	 * @param array $attachment
	 *
	 * @return array
	 */
	public static function attachment_fields_to_save( $post, $attachment ) {
		if ( self::handles_post_type( 'attachment' ) ) {
			$post_id = null;
			if ( isset( $post['ID'] ) ) {
				$post_id = $post['ID'];
			} else if ( isset( $post['post_ID'] ) ) {
				$post_id = $post['post_ID'];
			}
			if ( $post_id !== null ) {
				self::save_post( $post_id );
			}
		}
		return $post;
	}

	/**
	 * Deletes all stored eligible post IDs cached for the given post type, or all post types (by default).
	 *
	 * @since 2.17.0
	 *
	 * @param string|null $post_type
	 */
	public static function purge_eligible_post_ids_cached( $post_type = null ) {
		$changed = false;
		$stored_cache_groups = Groups_Options::get_option( 'eligible_post_ids_cache_groups', array() );
		foreach ( $stored_cache_groups as $cache_group ) {
			if ( $post_type === null || strpos( $cache_group, $post_type ) !== false ) {
				Groups_Cache::delete( 'eligible_post_ids' , $cache_group );
				$stored_cache_groups = array_diff( $stored_cache_groups, array( $cache_group ) );
				$changed = true;
			}
		}
		if ( $changed ) {
			if ( count( $stored_cache_groups ) > 0 ) {
				Groups_Options::update_option( 'eligible_post_ids_cache_groups', $stored_cache_groups );
			} else {
				Groups_Options::delete_option( 'eligible_post_ids_cache_groups' );
			}
		}
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
	 *
	 * @return true if the access requirement could be added to the post, otherwise false
	 */
	public static function create( $map ) {

		$result = false;

		$capability = isset( $map['capability'] ) ? $map['capability'] : null;
		$post_id = isset( $map['post_id'] ) ? $map['post_id'] : null;
		$group_id = isset( $map['group_id'] ) ? $map['group_id'] : null;

		if ( $capability !== null ) {
			_doing_it_wrong(
				__CLASS__ . '::' . __METHOD__,
				__( 'You should use Groups_Post_Access_Legacy::create() to pass a capability restriction instead.', 'groups' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
				$stored_group_ids = self::get_read_group_ids( $post_id );
				if ( !in_array( $group_id, $stored_group_ids ) ) {
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
	 * @param array $map should provide one or more group IDs via 'groups_read'
	 *
	 * @return boolean true if the group(s) is required, otherwise false
	 */
	public static function read( $post_id, $map = array() ) {

		$result = false;

		$groups_read = isset( $map['groups_read'] ) ? $map['groups_read'] : null;

		if ( !empty( $post_id ) ) {
			if ( $groups_read !== null ) {
				if ( empty( $groups_read ) ) {
					$groups_read = array();
				} else if ( !is_array( $groups_read ) ) {
					$groups_read = array( $groups_read );
				}
				$group_ids = self::get_read_group_ids( $post_id );
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
	 *
	 * @return array of group ids, false on failure
	 */
	public static function update( $map ) {

		$result = false;

		$post_id = isset( $map['post_id'] ) ? $map['post_id'] : null;
		$groups_read = isset( $map['groups_read'] ) ? $map['groups_read'] : null;

		if ( !empty( $post_id ) ) {
			if ( empty( $groups_read ) ) {
				$groups_read = array();
			} else if ( !is_array( $groups_read ) ) {
				$groups_read = array( $groups_read );
			}
			$groups_read = array_map( array( 'Groups_Utility', 'id' ), $groups_read );
			$current_groups_read = self::get_read_group_ids( $post_id );
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
			$stored_group_ids = self::get_read_group_ids( $post_id );
			$result = array_map( array( 'Groups_Utility', 'id' ), $stored_group_ids );
		}
		return $result;
	}

	/**
	 * Removes access restrictions from a post.
	 *
	 * @param int $post_id
	 * @param array $map must provide 'groups_read' holding group IDs to remove from restricting access to the post; if empty, all access restrictions will be removed
	 *
	 * @return boolean true on success, otherwise false
	 */
	public static function delete( $post_id, $map = array() ) {

		$result = false;

		$groups_read = isset( $map['groups_read'] ) ? $map['groups_read'] : null;

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
	 *
	 * @param int $post_id
	 *
	 * @return array of string, capabilities
	 */
	public static function get_read_post_capabilities( $post_id ) {
		_doing_it_wrong(
			__CLASS__ . '::' . __METHOD__,
			__( 'This method is deprecated. You should use Groups_Post_Access_Legacy::get_read_post_capabilities() to retrieve the capabilities instead.', 'groups' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'2.0.0'
		);

		require_once GROUPS_LEGACY_LIB . '/access/class-groups-post-access-legacy.php';
		return Groups_Post_Access_Legacy::get_read_post_capabilities( $post_id );
	}

	/**
	 * Returns a list of group IDs that grant read access to the post.
	 *
	 * @param int $post_id
	 *
	 * @return array of int, group IDs
	 */
	public static function get_read_group_ids( $post_id ) {
		$result = array();
		$group_ids = get_post_meta( $post_id, self::POSTMETA_PREFIX . self::READ );
		if ( is_array( $group_ids ) ) {
			foreach ( $group_ids as $group_id ) {
				// @since 2.18.0 discard invalid group IDs
				if ( !empty( $group_id ) && Groups_Group::exists( $group_id ) ) {
					$result[] = intval( $group_id );
				}
			}
		}
		return $result;
	}

	/**
	 * Returns true if the user belongs to any of the groups that grant access to the post.
	 *
	 * @param int $post_id post id
	 * @param int $user_id user id or null for current user
	 *
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
				if ( _groups_admin_override() || Groups_User::current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
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
							$ids = array_intersect( $groups_user->get_group_ids_deep(), $group_ids );
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
	 * Counts in cache are purged when posts are saved using the purge_count_posts_cached() method.
	 *
	 * @param object $counts An object containing the current post_type's post counts by status.
	 * @param string $type the post type
	 * @param string $perm The permission to determine if the posts are 'readable' by the current user.
	 *
	 * @return object
	 *
	 * @see Groups_Post_Access::purge_count_posts_cached()
	 */
	public static function wp_count_posts( $counts, $type, $perm ) {
		// @since 3.3.1 remove temporarily to avoid potential infinite recursion https://github.com/itthinx/groups/pull/160
		remove_filter( 'wp_count_posts', array( __CLASS__, 'wp_count_posts' ), 10 );
		if ( !empty( $type ) && is_string( $type ) && self::handles_post_type( $type ) ) {
			$sub_group = Groups_Cache::get_group( '' );
			// @since 2.20.0 cached per post type gathering counts per subgroup
			$cached = Groups_Cache::get( self::COUNT_POSTS . '_' . $type, self::CACHE_GROUP );
			if ( $cached === null ) {
				$type_counts = array();
			} else {
				$type_counts = $cached->value;
			}
			if ( isset( $type_counts[$sub_group] ) ) {
				$counts = $type_counts[$sub_group];
			} else {
				foreach( $counts as $post_status => $count ) {
					$query_args = array(
						'fields'           => 'ids',
						'post_type'        => $type,
						'post_status'      => $post_status,
						'numberposts'      => -1, // all
						'suppress_filters' => false, // don't suppress filters as we need to get restrictions taken into account
						'orderby'          => 'none', // Important! Don't waste time here.
						'no_found_rows'    => true, // performance, omit unnecessary SQL_CALC_FOUND_ROWS in query here
						'nopaging'         => true // no paging is needed, get all corresponding posts
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
				$type_counts[$sub_group] = $counts;
				Groups_Cache::set( self::COUNT_POSTS . '_' . $type, $type_counts, self::CACHE_GROUP );
			}
		}
		add_filter( 'wp_count_posts', array( __CLASS__, 'wp_count_posts' ), 10, 3 ); // @since 3.3.1 reestablish filter for next use
		return $counts;
	}

	/**
	 * Purge the cached post counts for the post type.
	 *
	 * @param string $type post type
	 *
	 * @since 2.20.0
	 *
	 * @see Groups_Post_Access::wp_count_posts()
	 */
	private static function purge_count_posts_cached( $type ) {
		if ( !empty( $type ) && is_string( $type ) && self::handles_post_type( $type ) ) {
			$hyper_group = Groups_Cache::get_group( '' );
			$cached = Groups_Cache::get( self::COUNT_POSTS . '_' . $type, self::CACHE_GROUP );
			if ( $cached !== null ) {
				$type_counts = $cached->value;
				unset( $type_counts[$hyper_group] );
				Groups_Cache::set( self::COUNT_POSTS . '_' . $type, $type_counts, self::CACHE_GROUP );
			}
		}
	}

	/**
	 * Would be hooked on wp_count_attachments to correct the counts but it's not actually
	 * being used in the current media library.
	 *
	 * @param object $counts An object containing the attachment counts by mime type.
	 * @param string $mime_type The mime type pattern used to filter the attachments counted.
	 *
	 * @return object
	 */
	public static function wp_count_attachments( $counts, $mime_type ) {
		return $counts;
	}

	/**
	 * Returns true if we are supposed to handle the post type, otherwise false.
	 *
	 * @param string $post_type
	 *
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

	/**
	 * Filter the block content of core/navigation-link and core/navigation-submenu blocks.
	 *
	 * This is necessary as these blocks would render their content although the corresponding post is protected.
	 *
	 * @since 2.20.0
	 *
	 * @param string $block_content
	 * @param array $parsed_block
	 * @param \WP_Block $block
	 *
	 * @return string
	 */
	public static function render_block( $block_content, $parsed_block, $block ) {
		if ( !is_admin() ) {
			/**
			 * Whether to process this block.
			 *
			 * @param boolean $filter whether to filter the block
			 * @param string $block_content the block's content
			 * @param array $parsed_block the parsed block
			 * @param \WP_Block $block the block
			 *
			 * @return boolean whether to filter
			 */
			if ( apply_filters( 'groups_post_access_filter_render_block', true, $block_content, $parsed_block, $block ) ) {
				$is_valid = true;
				if ( 'core/navigation-link' === $block->name || 'core/navigation-submenu' === $block->name ) {
					if ( $block->attributes && isset( $block->attributes['kind'] ) && 'post-type' === $block->attributes['kind'] && isset( $block->attributes['id'] ) ) {
						$post_id = $block->attributes['id'];
						if ( !self::user_can_read_post( $post_id ) ) {
							$is_valid = false;
						}
					}
				}
				if ( !$is_valid ) {
					$block_content = '';
				}
			}
		}
		return $block_content;
	}

	/**
	 * Short-circuits render_block() and WP_Block->render().
	 *
	 * For core/navigation-link and core/navigation-submenu blocks:
	 * - This will be overridden for dynamic blocks, so the render_block filter is necessary instead.
	 *
	 * For core/categories blocks:
	 * - We use this hook to add our get_terms filter.
	 *
	 * @see Groups_Post_Access::render_block()
	 *
	 * @since 2.20.0
	 *
	 * @param string|null $pre_render
	 * @param array $parsed_block
	 * @param \WP_Block|null $parent_block
	 *
	 * @return string|null
	 */
	public static function pre_render_block( $pre_render, $parsed_block, $parent_block ) {
		if ( !is_admin() ) {
			/**
			 * Whether to process this block pre rendering.
			 *
			 * @param boolean $filter whether to filter
			 * @param string $pre_render block content
			 * @param array $parsed_block the parsed block
			 * @param \WP_Block $parent_block the parent block
			 *
			 * @return boolean whether to filter
			 */
			if ( apply_filters( 'groups_post_access_filter_pre_render_block', true, $pre_render, $parsed_block, $parent_block ) ) {
				$block = new \WP_Block( $parsed_block );
				// $is_valid = true;
				// if ( 'core/navigation-link' === $block->name || 'core/navigation-submenu' === $block->name ) {
				// 	if ( $block->attributes && isset( $block->attributes['kind'] ) && 'post-type' === $block->attributes['kind'] && isset( $block->attributes['id'] ) ) {
				// 		$post_id = $block->attributes['id'];
				// 		if ( !self::user_can_read_post( $post_id ) ) {
				// 			$is_valid = false;
				// 		}
				// 	}
				// }
				// if ( !$is_valid ) {
				// 	$pre_render = '';
				// }

				// Detect a categories block and add our filter to update the counts
				if ( 'core/categories' === $block->name ) {
					self::$filter_get_terms_block = $block;
					add_filter( 'get_terms', array( __CLASS__, 'get_terms' ), 10, 4 );
				}
			}
		}
		return $pre_render;
	}

	/**
	 * Filter inner navigation blocks.
	 *
	 * This will not handle the inner blocks of core/navigation-submenu blocks, so the filter on render_block implemented here in this class is necessary.
	 *
	 * @since 2.20.0
	 *
	 * @param \WP_Block_List $inner_blocks
	 *
	 * @return \WP_Block_List
	 */
	public static function block_core_navigation_render_inner_blocks( $inner_blocks ) {
		if ( !is_admin() ) {
			/**
			 * Whether to filter the inner blocks.
			 *
			 * @param boolean $filter whether to filter
			 * @param \WP_Block_List $inner_blocks the inner blocks
			 *
			 * @return \WP_Block_List the inner blocks
			 */
			if ( apply_filters( 'groups_post_access_filter_block_core_navigation_render_inner_blocks', true, $inner_blocks ) ) {
				$valid_inner_blocks = array();
				/**
				 * @var \WP_Block[] $blocks
				 */
				$blocks = iterator_to_array( $inner_blocks );
				foreach ( $blocks as $block ) {
					$is_valid = true;
					// @see block_core_navigation_from_block_get_post_ids( $block )
					if ( 'core/navigation-link' === $block->name || 'core/navigation-submenu' === $block->name ) {
						if ( $block->attributes && isset( $block->attributes['kind'] ) && 'post-type' === $block->attributes['kind'] && isset( $block->attributes['id'] ) ) {
							$post_id = $block->attributes['id'];
							if ( !self::user_can_read_post( $post_id ) ) {
								$is_valid = false;
							}
						}
					}
					if ( $is_valid ) {
						$valid_inner_blocks[] = $block;
					}
				}
				$inner_blocks = new WP_Block_List( $valid_inner_blocks );
			}
		}
		return $inner_blocks;
	}

	/**
	 * Hooks into the filter to activate our get_terms filter.
	 *
	 * @since 2.20.0
	 *
	 * @param array $cat_args
	 * @param array $instance
	 *
	 * @return array
	 */
	public static function widget_categories_args( $cat_args, $instance ) {
		if ( !is_admin() ) {
			/**
			 * Whether to filter get_terms.
			 *
			 * @param boolean $filter whether to filter
			 * @param array $cat_args category parameters
			 * @param array $instance instance details
			 *
			 * @return array
			 */
			if ( apply_filters( 'groups_post_access_filter_widget_categories_args', true, $cat_args, $instance ) ) {
				self::$filter_get_terms_widget = $instance;
				add_filter( 'get_terms', array( __CLASS__, 'get_terms' ), 10, 4 );
			}
		}
		return $cat_args;
	}

	/**
	 * Hooks into the filter to activate our get_terms filter.
	 *
	 * @since 2.20.0
	 *
	 * @param array $cat_args
	 * @param array $instance
	 *
	 * @return array
	 */
	public static function widget_categories_dropdown_args( $cat_args, $instance ) {
		if ( !is_admin() ) {
			/**
			 * Whether to filter get_terms.
			 *
			 * @param boolean $filter whether to filter
			 * @param array $cat_args category parameters
			 * @param array $instance instance details
			 *
			 * @return array
			 */
			if ( apply_filters( 'groups_post_access_filter_widget_categories_dropdown_args', true, $cat_args, $instance ) ) {
				self::$filter_get_terms_widget = $instance;
				add_filter( 'get_terms', array( __CLASS__, 'get_terms' ), 10, 4 );
			}
		}
		return $cat_args;
	}

	/**
	 * Filter get_terms to adjust counts.
	 *
	 * @since 2.20.0
	 *
	 * @param array $terms
	 * @param array|null $taxonomies
	 * @param array $query_vars
	 * @param \WP_Term_Query $term_query
	 *
	 * @return array
	 */
	public static function get_terms( $terms, $taxonomies, $query_vars, $term_query ) {

		// act only once per add_filter we do in the specific cases we cover
		remove_filter( 'get_terms', array( __CLASS__, 'get_terms' ), 10 );

		if ( !is_admin() ) {
			/**
			 * Whether to filter get_terms.
			 *
			 * @param boolean $filter whether to filter
			 * @param array $terms
			 * @param array|null $taxonomies
			 * @param array $query_vars
			 * @param \WP_Term_Query $term_query
			 *
			 * @return array
			 */
			if ( apply_filters( 'groups_post_access_filter_get_terms', true, $terms, $taxonomies, $query_vars, $term_query ) ) {
				foreach ( $terms as $term ) {
					$query_args = array(
						'cat'              => $term->term_id, // this category term
						'fields'           => 'ids',
						'post_type'        => 'post',
						'post_status'      => 'publish',
						'numberposts'      => -1, // all
						'suppress_filters' => false, // apply restrictions
						'orderby'          => 'none', // performance
						'no_found_rows'    => true, // performance
						'nopaging'         => true // all
					);
					$post_ids = get_posts( $query_args );
					$term->count = count( $post_ids );
				}
				_pad_term_counts( $terms, $taxonomies[0] );
				$remove_empty = false;
				if ( self::$filter_get_terms_block !== null ) {
					if (
						is_object( self::$filter_get_terms_block ) &&
						// will mislead returning false because of dynamic properties : property_exists( self::$filter_get_terms_block, 'attributes' ) &&
						is_array( self::$filter_get_terms_block->attributes ) &&
						array_key_exists( 'showEmpty', self::$filter_get_terms_block->attributes ) &&
						!self::$filter_get_terms_block->attributes['showEmpty']
					) {
						$remove_empty = true;
					}
				}
				if ( self::$filter_get_terms_widget !== null ) {
					// There is no option to hide empty categories for the widget, so we always remove them.
					// Otherwise the condition would be like ...
					// if ( is_array( self::$filter_get_terms_widget ) && array_key_exists( 'show_empty', self::$filter_get_terms_widget ) && !self::$filter_get_terms_widget['show_empty'] )
					// There seems to be a bug in WP_Widget_Categories with showing the counts. Even though the "Show post counts" option was checked, the post counts are not shown. Tested with WP 6.4.2 and Classic Widgets 0.3.
					$remove_empty = true;
				}
				if ( $remove_empty ) {
					$_terms = array();
					foreach ( $terms as $term ) {
						if ( $term->count > 0 ) {
							$_terms[] = $term;
						}
					}
					$terms = $_terms;
				}
			}
		}

		// void context
		self::$filter_get_terms_block = null;
		self::$filter_get_terms_widget = null;

		return $terms;
	}
}
Groups_Post_Access::init();
