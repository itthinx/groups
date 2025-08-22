<?php
/**
 * class-groups-user.php
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

require_once GROUPS_CORE_LIB . '/interface-i-capable.php';
require_once GROUPS_CORE_LIB . '/class-groups-capability.php';

/**
 * User OPM.
 */
class Groups_User implements I_Capable {

	/**
	 * @var string cache group key
	 */
	const CACHE_GROUP = 'groups';

	/**
	 * @var string cache key prefix
	 */
	const CAPABILITIES = 'capabilities';

	/**
	 * @var string cache key prefix
	 */
	const CAPABILITIES_BASE = 'capabilities_base';

	/**
	 * @var string cache key prefix
	 *
	 * @since 3.6.0
	 */
	const CAPABILITIES_DEEP = 'capabilities_deep';

	/**
	 * @var string cache key prefix
	 */
	const CAPABILITY_IDS = 'capability_ids';

	/**
	 * @var string cache key prefix
	 */
	const CAPABILITY_IDS_BASE = 'capability_ids_base';

	/**
	 * @var string cache key prefix
	 */
	const GROUP_IDS = 'group_ids';

	/**
	 * @var string cache key prefix
	 */
	const GROUP_IDS_BASE = 'group_ids_base';

	/**
	 * @var string cache key prefix
	 */
	const GROUPS = 'groups';

	/**
	 * @var string cache key prefix
	 */
	const GROUPS_BASE = 'groups_base';

	/**
	 * User object.
	 *
	 * @access private - Use $this->get_user() instead as this property will be made private in a future release of Groups
	 *
	 * @var WP_User
	 */
	public $user = null;

	/**
	 * Hook cache clearers to actions that can modify the capabilities.
	 */
	public static function init() {
		add_action( 'groups_created_user_group', array( __CLASS__, 'clear_cache' ) );
		add_action( 'groups_updated_user_group', array( __CLASS__, 'clear_cache' ) );
		add_action( 'groups_deleted_user_group', array( __CLASS__, 'clear_cache' ) );
		add_action( 'groups_created_user_capability', array( __CLASS__, 'clear_cache' ) );
		add_action( 'groups_updated_user_capability', array( __CLASS__, 'clear_cache' ) );
		add_action( 'groups_deleted_user_capability', array( __CLASS__, 'clear_cache' ) );
		add_action( 'groups_created_group_capability', array( __CLASS__, 'clear_cache_for_group' ) );
		add_action( 'groups_updated_group_capability', array( __CLASS__, 'clear_cache_for_group' ) );
		add_action( 'groups_deleted_group_capability', array( __CLASS__, 'clear_cache_for_group' ) );
	}

	/**
	 * Clear cache objects for the user.
	 *
	 * @param int $user_id
	 */
	public static function clear_cache( $user_id ) {
		// be lazy, clear the entries so they are rebuilt when requested
		Groups_Cache::delete( self::CAPABILITIES . $user_id, self::CACHE_GROUP );
		Groups_Cache::delete( self::CAPABILITIES_BASE . $user_id, self::CACHE_GROUP );
		Groups_Cache::delete( self::CAPABILITIES_DEEP . $user_id, self::CACHE_GROUP );
		Groups_Cache::delete( self::CAPABILITY_IDS . $user_id, self::CACHE_GROUP );
		Groups_Cache::delete( self::CAPABILITY_IDS_BASE . $user_id, self::CACHE_GROUP );
		Groups_Cache::delete( self::GROUP_IDS . $user_id, self::CACHE_GROUP );
		Groups_Cache::delete( self::GROUP_IDS_BASE . $user_id, self::CACHE_GROUP );
		Groups_Cache::delete( self::GROUPS . $user_id, self::CACHE_GROUP );
		Groups_Cache::delete( self::GROUPS_BASE . $user_id, self::CACHE_GROUP );
	}

	/**
	 * Clear cache objects for all users in the group.
	 *
	 * @param int $group_id
	 */
	public static function clear_cache_for_group( $group_id ) {
		global $wpdb;
		if ( $group = Groups_Group::read( $group_id ) ) {
			// not using $group->users, as we don't need a lot of user objects created here
			$user_group_table = _groups_get_tablename( 'user_group' );
			$users = $wpdb->get_results( $wpdb->prepare(
				"SELECT ID FROM $wpdb->users LEFT JOIN $user_group_table ON $wpdb->users.ID = $user_group_table.user_id WHERE $user_group_table.group_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				Groups_Utility::id( $group_id )
			) );
			if ( $users ) {
				foreach( $users as $user ) {
					self::clear_cache( $user->ID );
				}
			}
		}
	}

	/**
	 * Convenience method equivalent to $this->is_member( $group_id ) without having to instantiate a Groups_User object outside first.
	 *
	 * @since 2.20.0
	 *
	 * @param int|\WP_User $user user ID or user object
	 * @param int $group_id group ID
	 *
	 * @return boolean
	 */
	public static function user_is_member( $user, $group_id ) {
		$is_member = false;
		$user_id = null;
		if ( is_numeric( $user ) ) {
			$user_id = max( 0, intval( $user ) );
		} else if ( $user instanceof \WP_User ) {
			$user_id = $user->ID;
		}
		if ( $user_id !== null ) {
			$groups_user = new Groups_User( $user_id );
			$is_member = $groups_user->is_member( $group_id );
		}
		return $is_member;
	}

	/**
	 * Whether the current user has the capability.
	 *
	 * @since 3.0.0
	 *
	 * @param string $capability capability name
	 * @param mixed ...$args optional parameters, typically an object ID
	 *
	 * @return boolean
	 */
	public static function current_user_can( $capability, ...$args ) {
		//
		// The global $current_user is determined in the privately scoped function _wp_get_current_user() defined in wp-includes/user.php.
		// The only call to that function is made in wp_get_current_user(), defined in wp-includes/pluggable.php.
		//
		// A call to current_user_can() defined in wp-includes/capabilities.php which is using wp_get_current_user()
		// which is defined in wp-includes/pluggable.php. The latter is simply wrapping a call to _wp_get_current_user().
		// The wp-includes/pluggable.php is loaded after all plugins have been loaded in a loop in wp-settings.php, whereas
		// wp-includes/user.php is loaded before. So if a plugin makes use of current_user_can() during the loop in wp-settings.php
		// which loads all plugins, the function wp_get_current_user() is not yet defined and a call to current_user_can() ends
		// up creating an error. The function wp_get_current_user() can be defined by a plugin in which case the native
		// WordPress version is not used. Thus we should not simply load wp-includes/pluggable.php in the "plugin-loading-loop"
		// as that would void the ability of overriding the wp_get_current_user() function via a plugin as the standard is loaded
		// before that loop ends loading all plugins.
		//
		// So if wp_get_current_user() is not yet defined, the global $current_user is not yet determined.
		// Because of that, a call to current_user_can() should assume the logged out or anonymous user.
		// This is the correct way to handle the situation when wp_get_current_user() is not defined yet,
		// and we SHOULD NOT load wp-includes/pluggable.php.
		//
		$user = 0;
		if ( function_exists( 'wp_get_current_user' ) ) {
			$user = wp_get_current_user();
		}
		return self::user_can( $user, $capability, ...$args );
	}

	/**
	 * Whether the given user has the capability.
	 *
	 * @since 3.0.0
	 *
	 * @param int|WP_User $user user ID or object
	 * @param string $capability capability name
	 * @param mixed ...$args optional parameters, typically an object ID
	 *
	 * @return boolean
	 */
	public static function user_can( $user, $capability, ...$args ) {
		// If $user is not an object, user_can() will call get_userdata() which is defined in wp-includes/pluggable.php.
		if ( function_exists( 'user_can' ) && function_exists( 'get_userdata' ) ) {
			return user_can( $user, $capability, ...$args );
		}
		// So we will have just the same problem as with current_user_can() unless we avoid getting functions involved which are not yet defined.
		// user_can() calls $user->has_cap() to produce the result, same here:
		if ( !( $user instanceof WP_User ) ) {
			$user = intval( $user );
			$user = new WP_User( $user );
			if ( $user === 0 ) {
				$user->init( new stdClass() );
			}
		}
		if ( $user instanceof WP_User ) {
			return $user->has_cap( $capability, ...$args );
		}
		return false;
	}

	/**
	 * Create, if $user_id = 0 an anonymous user is assumed.
	 *
	 * @param int $user_id
	 */
	public function __construct( $user_id = null ) {
		if ( $user_id !== null ) {
			if ( Groups_Utility::id( $user_id ) ) {
				$this->user = get_user_by( 'id', $user_id );
				if ( !$this->user ) {
					$this->user = new WP_User( 0 );
				}
			} else {
				$this->user = new WP_User( 0 );
			}
		}
	}

	/**
	 * Provide the related WP_User object.
	 *
	 * @return WP_User
	 */
	public function get_user() {
		return $this->user;
	}

	/**
	 * Set the related WP_User object.
	 *
	 * @param WP_User $user the user object
	 */
	public function set_user( $user ) {
		if ( $user instanceof WP_User ) {
			$this->user = $user;
		}
	}

	/**
	 * Provide the ID of the related WP_User object.
	 *
	 * @return int|null
	 */
	public function get_user_id() {
		$user_id = null;
		if ( $this->user !== null && $this->user instanceof WP_User ) {
			$user_id = $this->user->ID;
		}
		return $user_id;
	}

	/**
	 * Provides the capabilities of the object.
	 *
	 * @return Groups_Capability[]
	 */
	public function get_capabilities() {
		return $this->capabilities; // @phpstan-ignore property.notFound
	}

	/**
	 * Provides the IDs of the capabilities of this object.
	 *
	 * @return int[]
	 */
	public function get_capability_ids() {
		return $this->capability_ids; // @phpstan-ignore property.notFound
	}

	/**
	 * Provides the capabilities of the object, those of the user's roles and those of the object's groups, including from ancestor groups.
	 *
	 * @return Groups_Capability[]
	 */
	public function get_capabilities_deep() {
		return $this->capabilities_deep; // @phpstan-ignore property.notFound
	}

	/**
	 * Provides the IDs of the capabilities of object, those of the user's roles and those of the object's groups, including from ancestor groups.
	 *
	 * @return int[]
	 */
	public function get_capability_ids_deep() {
		return $this->capability_ids_deep; // @phpstan-ignore property.notFound
	}

	/**
	 * Provides the groups this object relates to.
	 *
	 * @return Groups_Group[]
	 */
	public function get_groups() {
		return $this->groups; // @phpstan-ignore property.notFound
	}

	/**
	 * Provides the groups that this object relates to and includes their ancestors.
	 *
	 * @return Groups_Group[]
	 */
	public function get_groups_deep() {
		return $this->groups_deep; // @phpstan-ignore property.notFound
	}

	/**
	 * Provides the IDs of the groups that this object relates to.
	 *
	 * @return int[]
	 */
	public function get_group_ids() {
		return $this->group_ids; // @phpstan-ignore property.notFound
	}

	/**
	 * Provide the IDs of the groups that this object relates to and from ancestor groups.
	 */
	public function get_group_ids_deep() {
		return $this->group_ids_deep; // @phpstan-ignore property.notFound
	}

	/**
	 * Retrieve a user property.
	 *
	 * Must be "capabilities", "groups" or a property of the WP_User class.
	 *
	 * @param string $name property's name
	 */
	public function __get( $name ) {

		global $wpdb;
		$result = null;

		if ( $this->user !== null ) {

			switch ( $name ) {

				case 'capability_ids' :
					$cached = Groups_Cache::get( self::CAPABILITY_IDS_BASE . $this->user->ID, self::CACHE_GROUP );
					if ( $cached !== null ) {
						$result = $cached->value;
						unset( $cached );
					} else {
						$user_capability_table = _groups_get_tablename( 'user_capability' );
						$rows = $wpdb->get_results( $wpdb->prepare(
							"SELECT capability_id FROM $user_capability_table WHERE user_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
							Groups_Utility::id( $this->user->ID )
						) );
						if ( $rows ) {
							$result = array();
							foreach ( $rows as $row ) {
								$result[] = $row->capability_id;
							}
						}
						Groups_Cache::set( self::CAPABILITY_IDS_BASE . $this->user->ID, $result, self::CACHE_GROUP );
					}
					break;

				case 'capability_ids_deep' :
					if ( $this->user !== null ) {
						$cached = Groups_Cache::get( self::CAPABILITY_IDS . $this->user->ID, self::CACHE_GROUP );
						if ( $cached !== null ) {
							$capability_ids = $cached->value;
							unset( $cached );
						} else {
							$this->init_cache( $capability_ids );
						}
						$result = $capability_ids;
					}
					break;

				case 'group_ids' :
					$cached = Groups_Cache::get( self::GROUP_IDS_BASE . $this->user->ID, self::CACHE_GROUP );
					if ( $cached !== null ) {
						$result = $cached->value;
						unset( $cached );
					} else {
						$user_group_table = _groups_get_tablename( 'user_group' );
						$rows = $wpdb->get_results( $wpdb->prepare(
							"SELECT group_id FROM $user_group_table WHERE user_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
							Groups_Utility::id( $this->user->ID )
						) );
						if ( $rows ) {
							$result = array();
							foreach( $rows as $row ) {
								$result[] = $row->group_id;
							}
						}
						Groups_Cache::set( self::GROUP_IDS_BASE . $this->user->ID, $result, self::CACHE_GROUP );
					}
					break;

				case 'group_ids_deep' :
					if ( $this->user !== null ) {
						$cached = Groups_Cache::get( self::GROUP_IDS . $this->user->ID, self::CACHE_GROUP );
						if ( $cached !== null ) {
							$group_ids = $cached->value;
							unset( $cached );
						} else {
							$this->init_cache( $capability_ids, $capabilities, $group_ids );
						}
						$result = $group_ids;
					}
					break;

				case 'capabilities' :
					$cached = Groups_Cache::get( self::CAPABILITIES_BASE . $this->user->ID, self::CACHE_GROUP );
					if ( $cached !== null ) {
						$result = $cached->value;
						unset( $cached );
					} else {
						$user_capability_table = _groups_get_tablename( 'user_capability' );
						$rows = $wpdb->get_results( $wpdb->prepare(
							"SELECT capability_id FROM $user_capability_table WHERE user_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
							Groups_Utility::id( $this->user->ID )
						) );
						if ( $rows ) {
							$result = array();
							foreach ( $rows as $row ) {
								$result[] = new Groups_Capability( $row->capability_id );
							}
						}
						Groups_Cache::set( self::CAPABILITIES_BASE . $this->user->ID, $result, self::CACHE_GROUP );
					}
					break;

				case 'capabilities_deep' :
					if ( $this->user !== null ) {
						// @since 3.6. provide cached objects
						$cached = Groups_Cache::get( self::CAPABILITIES_DEEP . $this->user->ID, self::CACHE_GROUP );
						if ( $cached !== null ) {
							$result = $cached->value;
							unset( $cached );
						} else {
							$cached = Groups_Cache::get( self::CAPABILITIES . $this->user->ID, self::CACHE_GROUP );
							if ( $cached !== null ) {
								$capabilities = $cached->value;
								unset( $cached );
							} else {
								$this->init_cache( $capability_ids, $capabilities );
							}
							// @since 3.6.0 provide expected return type Groups_Capability[]
							$result = array();
							foreach ( $capabilities as $capability ) {
								$capobj = Groups_Capability::read_by_capability( $capability );
								if ( $capobj ) {
									$result[] = new Groups_Capability( $capobj->capability_id );
								}
							}
							Groups_Cache::set( self::CAPABILITIES_DEEP . $this->user->ID, $result, self::CACHE_GROUP );
						}
					}
					break;

				case 'groups' :
					$cached = Groups_Cache::get( self::GROUPS_BASE . $this->user->ID, self::CACHE_GROUP );
					if ( $cached !== null ) {
						$result = $cached->value;
						unset( $cached );
					} else {
						$user_group_table = _groups_get_tablename( 'user_group' );
						$rows = $wpdb->get_results( $wpdb->prepare(
							"SELECT group_id FROM $user_group_table WHERE user_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
							Groups_Utility::id( $this->user->ID )
						) );
						if ( $rows ) {
							$result = array();
							foreach( $rows as $row ) {
								$result[] = new Groups_Group( $row->group_id );
							}
						}
						Groups_Cache::set( self::GROUPS_BASE . $this->user->ID, $result, self::CACHE_GROUP );
					}
					break;

				case 'groups_deep' :
					$cached = Groups_Cache::get( self::GROUPS . $this->user->ID, self::CACHE_GROUP );
					if ( $cached !== null ) {
						$result = $cached->value;
						unset( $cached );
					} else {
						$result = array();
						foreach( $this->group_ids_deep as $group_id ) { // @phpstan-ignore property.notFound
							$result[] = new Groups_Group( $group_id );
						}
						Groups_Cache::set( self::GROUPS . $this->user->ID, $result, self::CACHE_GROUP );
					}
					break;

				default:
					$result = $this->user->$name;
			}
		}
		return $result;
	}

	/**
	 * @see I_Capable::can()
	 */
	public function can( $capability, $object = null, $args = null ) {

		$result = false;

		if ( $this->user !== null ) {
			if ( _groups_admin_override( $this->user->ID ) ) {
				$result = true;
			} else {
				// determine capability id
				$capability_id = null;
				if ( is_numeric( $capability ) ) {
					$capability_id = Groups_Utility::id( $capability );
					$cached = Groups_Cache::get( self::CAPABILITY_IDS . $this->user->ID, self::CACHE_GROUP );
					if ( $cached !== null ) {
						$capability_ids = $cached->value;
						unset( $cached );
					} else {
						$this->init_cache( $capability_ids );
					}
					$result = in_array( $capability_id, $capability_ids );
				} else if ( is_string( $capability ) ) {
					$cached = Groups_Cache::get( self::CAPABILITIES . $this->user->ID, self::CACHE_GROUP );
					if ( $cached !== null ) {
						$capabilities = $cached->value;
						unset( $cached );
					} else {
						$this->init_cache( $capability_ids, $capabilities );
					}
					$result = in_array( $capability, $capabilities );
				}
			}
		}
		/**
		 * Filter whether the user has the capability.
		 *
		 * @since 3.0.0 $object
		 * @since 3.0.0 $args
		 *
		 * @param boolean $result
		 * @param Groups_User $group_user
		 * @param string $capability
		 * @param mixed $object
		 * @param mixed $args
		 *
		 * @return boolean
		 */
		$result = apply_filters_ref_array( 'groups_user_can', array( $result, &$this, $capability, $object, $args ) );
		return $result;
	}

	/**
	 * Returns true if the user belongs to the group.
	 *
	 * @param int $group_id
	 *
	 * @return boolean
	 */
	public function is_member( $group_id ) {
		$result = false;
		if ( $this->user !== null ) {
			if ( isset( $this->user->ID ) ) {
				$user_group = Groups_User_Group::read(
					Groups_Utility::id( $this->user->ID ),
					Groups_Utility::id( $group_id )
				);
				$result = $user_group !== false;
				unset( $user_group );
			}
		}
		/**
		 * Allow to modify the result of whether the user belongs to a given group.
		 *
		 * @since 2.20.0
		 *
		 * @param boolean $result whether the user belongs to the group
		 * @param Groups_User $object this object
		 * @param int $group_id the group ID
		 *
		 * @return boolean $filtered_result
		 */
		$filtered_result = apply_filters( 'groups_user_is_member', $result, $this, $group_id );
		if ( is_bool( $result ) ) {
			$result = $filtered_result;
		}
		return $result;
	}

	/**
	 * Builds the cache entries for user groups and capabilities if needed.
	 * The cache entries are built only if they do not already exist.
	 * If you want them rebuilt, delete them before calling.
	 *
	 * @param array $capability_ids carries the capability ids for the user on return, but only if cache entries have been built; will provide an empty array by default
	 * @param array $capabilities carries the capabilities for the user on return, but only if cache entries have been built; will provide an empty array by default
	 * @param array $group_ids carries the group ids for the user on return, but only if cache entries have been built; will provide an empty array by default
	 */
	private function init_cache( &$capability_ids = null, &$capabilities = null, &$group_ids = null ) {

		global $wpdb;

		$capabilities   = array();
		$capability_ids = array();
		$group_ids      = array();

		if ( ( $this->user !== null ) && ( Groups_Cache::get( self::GROUP_IDS . $this->user->ID, self::CACHE_GROUP ) === null ) ) {
			$group_table            = _groups_get_tablename( 'group' );
			$capability_table       = _groups_get_tablename( 'capability' );
			$group_capability_table = _groups_get_tablename( 'group_capability' );
			$user_group_table       = _groups_get_tablename( 'user_group' );
			$user_capability_table  = _groups_get_tablename( 'user_capability' );

			$limit = $wpdb->get_var( "SELECT COUNT(*) FROM $group_table" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( $limit === null ) {
				$limit = 1;
			}

			// note that limits by blog_id for multisite are
			// enforced when a user is added to a blog
			$user_groups = $wpdb->get_results( $wpdb->prepare(
				"SELECT group_id FROM $user_group_table WHERE user_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				Groups_Utility::id( $this->user->ID )
			) );
			// get all capabilities directly assigned (those granted through
			// groups are added below
			$user_capabilities = $wpdb->get_results( $wpdb->prepare(
				"SELECT c.capability_id, c.capability FROM $user_capability_table uc LEFT JOIN $capability_table c ON c.capability_id = uc.capability_id WHERE user_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				Groups_Utility::id( $this->user->ID )
			) );
			if ( $user_capabilities ) {
				foreach( $user_capabilities as $user_capability ) {
					$capabilities[]   = $user_capability->capability;
					$capability_ids[] = $user_capability->capability_id;
				}
			}

			if ( apply_filters( 'groups_user_add_role_capabilities', true ) ) {
				// Get all capabilities from the WP_User object.
				$role_caps = $this->user->get_role_caps();
				if ( !empty( $role_caps ) && is_array( $role_caps ) ) {
					$caps = array();
					foreach( $role_caps as $role_cap => $has ) {
						if ( $has && !in_array( $role_cap, $capabilities ) ) {
							$caps[] = $role_cap;
						}
					}
					if ( !empty( $caps ) ) {
						// Retrieve the capabilities and only add those that are
						// recognized. Note that this also effectively filters out
						// all roles and that this is desired.
						if ( $role_capabilities = $wpdb->get_results( "SELECT capability_id, capability FROM $capability_table c WHERE capability IN ('" . implode( "','", esc_sql( $caps ) ) . "')" ) ) { // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
							foreach( $role_capabilities as $role_capability ) {
								$capabilities[]   = $role_capability->capability;
								$capability_ids[] = $role_capability->capability_id;
							}
						}
					}
				}
			}

			// Get all groups the user belongs to directly or through
			// inheritance along with their capabilities.
			if ( $user_groups ) {
				foreach( $user_groups as $user_group ) {
					$group_ids[] = Groups_Utility::id( $user_group->group_id );
				}
				if ( count( $group_ids ) > 0 ) {
					$iterations          = 0;
					$old_group_ids_count = 0;
					while( ( $iterations < $limit ) && ( count( $group_ids ) !== $old_group_ids_count ) ) {
						$iterations++;
						$old_group_ids_count = count( $group_ids );
						$id_list = implode( ',', $group_ids );
						$parent_group_ids = $wpdb->get_results(
							"SELECT parent_id FROM $group_table WHERE parent_id IS NOT NULL AND group_id IN ($id_list)" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						);
						if ( $parent_group_ids ) {
							foreach( $parent_group_ids as $parent_group_id ) {
								$parent_group_id = Groups_Utility::id( $parent_group_id->parent_id );
								if ( !in_array( $parent_group_id, $group_ids ) ) {
									$group_ids[] = $parent_group_id;
								}
							}
						}
					}
					$id_list = implode( ',', $group_ids );
					$rows = $wpdb->get_results(
						"SELECT $group_capability_table.capability_id, $capability_table.capability FROM $group_capability_table LEFT JOIN $capability_table ON $group_capability_table.capability_id = $capability_table.capability_id WHERE group_id IN ($id_list)" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					);
					if ( count( $rows ) > 0 ) {
						foreach ( $rows as $row ) {
							if ( !in_array( $row->capability_id, $capability_ids ) ) {
								$capabilities[]   = $row->capability;
								$capability_ids[] = $row->capability_id;
							}
						}
					}

				}
			}
			Groups_Cache::set( self::CAPABILITIES . $this->user->ID, $capabilities, self::CACHE_GROUP );
			Groups_Cache::set( self::CAPABILITY_IDS . $this->user->ID, $capability_ids, self::CACHE_GROUP );
			Groups_Cache::set( self::GROUP_IDS . $this->user->ID, $group_ids, self::CACHE_GROUP );
		}
	}

}
Groups_User::init();
