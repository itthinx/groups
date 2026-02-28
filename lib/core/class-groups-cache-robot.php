<?php
/**
 * class-groups-cache-robot.php
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
 * @since groups 4.0.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cache robot automates cache actions and invalidation.
 */
class Groups_Cache_Robot {

	/**
	 * @var string[]
	 */
	private static $comment_groups = array();

	/**
	 * @var string[]
	 */
	private static $post_groups = array();

	/**
	 * @var string[]
	 */
	private static $user_groups = array();

	/**
	 * @var string[]
	 */
	private static $term_groups = array();

	/**
	 * @var string[]
	 */
	private static $flush_groups = array();

	/**
	 * Register groups and actions.
	 */
	public static function init() {
		// * Currently, all cache groups resolve to 'groups' but as these may change
		//   and others can be added, all applicable should be processed here.
		// * Apparent redundancy is by design. Multiple triggered actions resulting
		//   in flush scheduled will only cause a single group flush to be processed
		//   during shutdown.
		// * If group flush is not supported, full cache is flushed.

		if ( class_exists( 'Groups_Post_Access_Legacy' ) ) {
			self::$post_groups[] = Groups_Post_Access_Legacy::CACHE_GROUP;
			self::$user_groups[] = Groups_Post_Access_Legacy::CACHE_GROUP;
		}

		self::$comment_groups[] = Groups_Comment_Access::CACHE_GROUP;
		self::$post_groups[] = Groups_Comment_Access::CACHE_GROUP;
		self::$user_groups[] = Groups_Comment_Access::CACHE_GROUP;

		self::$post_groups[] = Groups_Post_Access::CACHE_GROUP;
		self::$user_groups[] = Groups_Post_Access::CACHE_GROUP;

		if ( class_exists( 'Groups_Admin_Post_Columns' ) ) {
			self::$term_groups[] = Groups_Admin_Post_Columns::CACHE_GROUP;
			self::$user_groups[] = Groups_Admin_Post_Columns::CACHE_GROUP;
		}

		self::$user_groups[] = Groups_WordPress::CACHE_GROUP;

		/**
		 * Filter comment groups.
		 *
		 * @param string[] $groups
		 *
		 * @return string[]
		 */
		self::$comment_groups = apply_filters( 'groups_cache_robot_comment_groups', self::$comment_groups );

		/**
		 * Filter post groups.
		 *
		 * @param string[] $groups
		 *
		 * @return string[]
		 */
		self::$post_groups = apply_filters( 'groups_cache_robot_post_groups', self::$post_groups );

		/**
		 * Filter term groups.
		 *
		 * @param string[] $groups
		 *
		 * @return string[]
		 */
		self::$term_groups = apply_filters( 'groups_cache_robot_term_groups', self::$term_groups );

		/**
		 * Filter user groups.
		 *
		 * @param string[] $groups
		 *
		 * @return string[]
		 */
		self::$user_groups = apply_filters( 'groups_cache_robot_user_groups', self::$user_groups );

		// comments
		add_action( 'deleted_comment', array( __CLASS__, 'comment' ), 10, 2 );
		add_action( 'trashed_comment', array( __CLASS__, 'comment' ), 10, 2 );
		add_action( 'untrashed_comment', array( __CLASS__, 'comment' ), 10, 2 );
		add_action( 'spammed_comment', array( __CLASS__, 'comment' ), 10, 2 );
		add_action( 'unspammed_comment', array( __CLASS__, 'comment' ), 10, 2 );
		add_action( 'transition_comment_status', array( __CLASS__, 'comment' ), 10, 3 );
		add_action( 'wp_insert_comment', array( __CLASS__, 'comment' ), 10, 2 );
		add_action( 'edit_comment', array( __CLASS__, 'comment' ), 10, 2 );

		// posts
		add_action( 'deleted_post', array( __CLASS__, 'post' ), 10, 2 );
		add_action( 'trashed_post', array( __CLASS__, 'post' ), 10, 2 );
		add_action( 'untrashed_post', array( __CLASS__, 'post' ), 10, 2 );
		add_action( 'save_post', array( __CLASS__, 'post' ), 10, 3 );
		// save_post covers add_action( 'transition_post_status', array( __CLASS__, 'post' ), 10, 3 );

		// users
		add_action( 'wp_update_user', array( __CLASS__, 'user' ), 10, 3 );
		add_action( 'add_user_role', array( __CLASS__, 'user' ), 10, 2 );
		add_action( 'set_user_role', array( __CLASS__, 'user' ), 10, 3 );
		add_action( 'remove_user_role', array( __CLASS__, 'user' ), 10, 2 );
		add_action( 'granted_super_admin', array( __CLASS__, 'user' ), 10 );
		add_action( 'revoked_super_admin', array( __CLASS__, 'user' ), 10 );
		add_action( 'profile_update', array( __CLASS__, 'user' ), 10, 3 );

		// terms
		// created_term is covered by saved_term (they always fire in sequence)
		add_action( 'delete_term', array( __CLASS__, 'term' ), 10, 5 );
		add_action( 'saved_term', array( __CLASS__, 'term' ), 10, 5 );
		add_action( 'set_object_terms', array( __CLASS__, 'term' ), 10, 6 );
		add_action( 'deleted_term_relationships', array( __CLASS__, 'term' ), 10, 3 );

		// actions that affect relationship between user and group
		add_action( 'groups_created_user_group', array( __CLASS__, 'all' ), 10, 2 );
		add_action( 'groups_updated_user_group', array( __CLASS__, 'all' ), 10, 2 );
		add_action( 'groups_deleted_user_group', array( __CLASS__, 'all' ), 10, 2 );

		// actions that affect relationship between capability and group
		add_action( 'groups_created_group_capability', array( __CLASS__, 'all' ), 10, 2 );
		add_action( 'groups_updated_group_capability', array( __CLASS__, 'all' ), 10, 2 );
		add_action( 'groups_deleted_group_capability', array( __CLASS__, 'all' ), 10, 2 );

		// actions that affect relationship between user and capability
		add_action( 'groups_created_user_capability', array( __CLASS__, 'all' ), 10, 2 );
		add_action( 'groups_updated_user_capability', array( __CLASS__, 'all' ), 10, 2 );
		add_action( 'groups_deleted_user_capability', array( __CLASS__, 'all' ), 10, 2 );

		// general options updated
		add_action( 'groups_updated_option', array( __CLASS__, 'all' ), 10, 2 );
		add_action( 'groups_deleted_option', array( __CLASS__, 'all' ) );
		add_action( 'groups_flushed_options', array( __CLASS__, 'all' ), 10, 0 );

		add_action( 'shutdown', array( __CLASS__, 'shutdown' ) );
	}

	/**
	 * Join array elements as set.
	 *
	 * @param array $a
	 * @param array $b
	 *
	 * @return array
	 */
	public static function join( $a, $b ) {
		return array_unique( array_merge( $a, $b ) );
	}

	/**
	 * Schedule comment groups to flush.
	 *
	 * @param mixed ...$args
	 */
	public static function comment( ...$args ) {

		$apply = false;

		$action = current_action();
		if ( $action !== false ) {
			switch ( $action ) {
				case 'deleted_comment':
				case 'trashed_comment':
				case 'untrashed_comment':
				case 'spammed_comment':
				case 'unspammed_comment':
					$apply = self::apply_comment( $args[1] ?? null );
					break;
				case 'wp_insert_comment':
					$apply = self::apply_comment( $args[1] ?? null );
					if ( $apply ) {
						$comment = $args[1] ?? null;
						if ( $comment instanceof WP_Comment ) {
							if ( !$comment->comment_approved ) {
								$apply = false;
							}
						}
					}
					break;
				case 'transition_comment_status':
					$apply = self::apply_comment( $args[2] ?? null );
					break;
				case 'edit_comment':
					$apply = self::apply_comment( $args[0] ?? null );
					break;
			}
		}

		/**
		 * Add comment groups to flush?
		 *
		 * @param boolean $apply
		 * @param string $action
		 * @param array|Traversable $args
		 *
		 * @return boolean
		 */
		$apply = apply_filters( 'groups_cache_robot_flush_comment', true, current_action(), $args );
		if ( $apply ) {
			self::$flush_groups = self::join( self::$flush_groups, self::$comment_groups );
		}
	}

	/**
	 * Apply to comment.
	 *
	 * @param WP_Comment|int $comment
	 *
	 * @return boolean
	 */
	private static function apply_comment( $comment ) {
		$apply = false;
		if ( is_numeric( $comment ) ) {
			$comment = WP_Comment::get_instance( $comment );
		}
		if ( $comment instanceof WP_Comment ) {
			$post_type = get_post_type( $comment->comment_post_ID );
			if ( $post_type ) {
				$apply = Groups_Post_Access::handles_post_type( $post_type );
			}
		}
		return $apply;
	}

	/**
	 * Schedule post groups to flush.
	 *
	 * @param mixed ...$args
	 */
	public static function post( ...$args ) {

		$apply = false;

		$action = current_action();
		if ( $action !== false ) {
			switch ( $action ) {
				case 'save_post':
				case 'deleted_post':
					$post = $args[1] ?? null;
					if ( $post instanceof WP_Post ) {
						if ( Groups_Post_Access::handles_post_type( $post->post_type ) ) {
							$apply = true;
						}
					}
					break;
				case 'trashed_post':
				case 'untrashed_post':
					$post_type = get_post_type( $args[0] ?? -1 );
					if ( is_string( $post_type ) ) {
						if ( Groups_Post_Access::handles_post_type( $post_type ) ) {
							$apply = true;
						}
					}
					break;
			}
		}

		/**
		 * Add post groups to flush?
		 *
		 * @param boolean $apply
		 * @param string $action
		 * @param array|Traversable $args
		 *
		 * @return boolean
		 */
		$apply = apply_filters( 'groups_cache_robot_flush_post', $apply, current_action(), $args );
		if ( $apply ) {
			self::$flush_groups = self::join( self::$flush_groups, self::$post_groups );
		}
	}

	/**
	 * Schedule user groups to flush.
	 *
	 * @param mixed ...$args
	 */
	public static function user( ...$args ) {
		/**
		 * Add user groups to flush?
		 *
		 * @param boolean $apply
		 * @param string $action
		 * @param array|Traversable $args
		 *
		 * @return boolean
		 */
		$apply = apply_filters( 'groups_cache_robot_flush_user', true, current_action(), $args );
		if ( $apply ) {
			self::$flush_groups = self::join( self::$flush_groups, self::$user_groups );
		}
	}

	/**
	 * Schedule term groups to flush.
	 *
	 * @param mixed ...$args
	 */
	public static function term( ...$args ) {

		$apply = false;

		$action = current_action();
		if ( $action !== false ) {
			switch ( $action ) {
				case 'delete_term':
					$apply = self::apply_taxonomy( $args[2] ?? '' );
					break;
				case 'saved_term':
					$apply = self::apply_taxonomy( $args[2] ?? '' );
					break;
				case 'set_object_terms':
					$apply = self::apply_taxonomy( $args[3] ?? '' );
					break;
				case 'deleted_term_relationships':
					$apply = self::apply_taxonomy( $args[2] ?? '' );
					break;
			}
		}

		/**
		 * Add term groups to flush?
		 *
		 * @param boolean $apply
		 * @param string $action
		 * @param array|Traversable $args
		 *
		 * @return boolean
		 */
		$apply = apply_filters( 'groups_cache_robot_flush_term', $apply, current_action(), $args );
		if ( $apply ) {
			self::$flush_groups = self::join( self::$flush_groups, self::$term_groups );
		}
	}

	/**
	 * Apply to taxonomy.
	 *
	 * @param string $taxonomy
	 *
	 * @return boolean
	 */
	private static function apply_taxonomy( $taxonomy ) {
		$apply = false;
		$taxonomy = get_taxonomy( $taxonomy ?? '' );
		if ( $taxonomy instanceof WP_Taxonomy ) {
			$post_types = $taxonomy->object_type ?? array();
			foreach ( $post_types as $post_type ) {
				if ( Groups_Post_Access::handles_post_type( $post_type ) ) {
					$apply = true;
					break;
				}
			}
		}
		return $apply;
	}

	/**
	 * Schedule all groups to flush.
	 */
	public static function all( ...$args ) {
		/**
		 * Add all groups to flush?
		 *
		 * @param boolean $apply
		 * @param string $action
		 * @param array|Traversable $args
		 *
		 * @return boolean
		 */
		$apply = apply_filters( 'groups_cache_robot_flush_all', true, current_action(), $args );
		if ( $apply ) {
			self::$flush_groups = self::join( self::$flush_groups, self::$comment_groups );
			self::$flush_groups = self::join( self::$flush_groups, self::$post_groups );
			self::$flush_groups = self::join( self::$flush_groups, self::$user_groups );
			self::$flush_groups = self::join( self::$flush_groups, self::$term_groups );
		}
	}

	/**
	 * Flush scheduled groups.
	 */
	public static function shutdown() {
		/**
		 * Filter groups to flush.
		 *
		 * @param string[] $groups
		 *
		 * @return string[]
		 */
		self::$flush_groups = apply_filters( 'groups_cache_robot_flush_groups', self::$flush_groups );
		if ( is_array( self::$flush_groups ) && count( self::$flush_groups ) > 0 ) {
			if (
				function_exists( 'wp_cache_supports' ) &&
				wp_cache_supports( 'flush_group' )
			) {
				foreach( self::$flush_groups as $group ) {
					if ( is_string( $group ) ) {
						wp_cache_flush_group( $group );
					}
				}
			} else {
				if ( function_exists( 'wp_cache_flush' ) ) {
					wp_cache_flush();
				}
			}
			self::$flush_groups = array();
		}
	}
}

Groups_Cache_Robot::init();
