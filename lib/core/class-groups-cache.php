<?php
/**
 * class-groups-cache.php
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
 * @since groups 1.9.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cache service.
 *
 * Uses cache objects to encapsulate cached data.
 *
 * This makes us completely independent from the problems related to
 * incomplete cache implementations that ignore the $found parameter used
 * to disambiguate cache misses with wp_cache_get() when false is retrieved.
 */
class Groups_Cache {

	/**
	 * Default cache group.
	 *
	 * @var string
	 */
	const CACHE_GROUP = 'groups';

	/**
	 * Cache group specializer, use the user's groups.
	 *
	 * @since 2.20.0
	 *
	 * @var int
	 */
	const GROUPS_CACHE_GROUP = 0x01;

	/**
	 * Cache group specializer, use the user's roles.
	 *
	 * @since 2.20.0
	 *
	 * @var int
	 */
	const ROLES_CACHE_GROUP = 0x02;

	/**
	 * Cache group specializer, use the user's ID.
	 *
	 * @since 2.20.0
	 *
	 * @var int
	 */
	const USER_CACHE_GROUP = 0x04;

	/**
	 * Default expiration.
	 *
	 * @since 4.0.0
	 *
	 * @var int
	 */
	const EXPIRES_DEFAULT = 86400;

	/**
	 * @since 4.0.0
	 *
	 * @var boolean
	 */
	private static $debug = false;

	/**
	 * @since 4.0.0
	 *
	 * @var int
	 */
	private static $hit = 0;

	/**
	 * @since 4.0.0
	 *
	 * @var integer
	 */
	private static $count = 0;

	/**
	 * Initialize.
	 */
	public static function init() {
		if ( defined( 'GROUPS_CACHE_DEBUG' ) && GROUPS_CACHE_DEBUG ) {
			self::$debug = true;
		}
	}

	/**
	 * Retrieve an entry from cache.
	 *
	 * @param string $key
	 * @param string $group
	 *
	 * @return Groups_Cache_Object|null returns a cache object on hit, null on cache miss
	 */
	public static function get( $key, $group = self::CACHE_GROUP ) {
		self::$count++;
		$hit = false;
		$found = null;
		/**
		 * @var Groups_Cache_Object|boolean $value
		 */
		$value = wp_cache_get( $key, $group, false, $found );
		if ( !( $value instanceof Groups_Cache_Object ) ) {
			$value = null;
		} else {
			// verify validity (safeguard if cache has not expired entry appropriately)
			if ( $value->has_expired() ) {
				wp_cache_delete( $key, $group );
				$value = null;
			} else {
				self::$hit++;
				$hit = true;
			}
		}
		if ( self::$debug ) {
			$ratio = self::$hit / self::$count;
			Groups_Log::log(
				sprintf(
					'Cache get [%4s] [%s|%s] [%.2f]',
					$hit ? 'hit' : 'miss',
					json_encode( $key ),
					json_encode( $group ),
					$ratio
				)
			);
		}
		return $value;
	}

	/**
	 * Retrieve an entry from cache, extended key.
	 *
	 * @since 4.0.0
	 *
	 * @param string $key
	 * @param string $group
	 *
	 * @return Groups_Cache_Object|NULL
	 */
	public static function get_ext( $key, $group = self::CACHE_GROUP ) {
		$key = self::extend_key( $key );
		return self::get( $key, $group );
	}

	/**
	 * Store an entry in cache.
	 *
	 * @param string $key
	 * @param string $value
	 * @param string $group
	 * @param int|null $expires default expiration applies if not provided
	 *
	 * @return boolean true if successful, otherwise false
	 */
	public static function set( $key, $value, $group = self::CACHE_GROUP, $expires = null ) {
		/**
		 * Filter when cache entry expires.
		 *
		 * @since 4.0.0
		 *
		 * @param int|null $expires
		 * @param string $key
		 * @param mixed $value
		 * @param string $group
		 *
		 * @return int|null
		 */
		$expires = apply_filters( 'groups_cache_set_expires', $expires, $key, $value, $group );
		if ( is_numeric( $expires ) ) {
			$expires = max( 0, intval( $expires ) );
		} else {
			$expires = self::EXPIRES_DEFAULT;
		}
		$object = new Groups_Cache_Object( $key, $value, $expires );
		if ( $expires === null || $expires === 0 ) {
			/**
			 * Indefinite value.
			 *
			 * @since 4.0.0
			 *
			 * @param int $value
			 *
			 * @return int
			 */
			$expires = apply_filters( 'groups_cache_set_expires_indefinite', PHP_INT_MAX );
			$expires = is_numeric( $expires ) ? max( 0, intval( $expires ) ) : PHP_INT_MAX;
		}

		$set = wp_cache_set( $key, $object, $group, $expires );
		if ( self::$debug ) {
			Groups_Log::log(
				sprintf(
					'Cache set [%4s] [%s|%s]',
					$set ? 'ok' : 'fail',
					json_encode( $key ),
					json_encode( $group )
				)
			);
		}
		return $set;
	}

	/**
	 * Store an entry in cache, extended key.
	 *
	 * @param string $key
	 * @param string $value
	 * @param string $group
	 * @param int|null $expires default expiration applies if not provided
	 *
	 * @return boolean true if successful, otherwise false
	 */
	public static function set_ext( $key, $value, $group = self::CACHE_GROUP, $expires = null ) {
		$key = self::extend_key( $key );
		return self::set( $key, $value, $group, $expires );
	}

	/**
	 * Delete a cache entry.
	 *
	 * @param string $key
	 * @param string $group
	 *
	 * @return boolean true if successful, otherwise false
	 */
	public static function delete( $key, $group = self::CACHE_GROUP ) {
		$delete = wp_cache_delete( $key, $group );
		if ( self::$debug ) {
			Groups_Log::log(
				sprintf(
					'Cache del [%4s] [%s|%s]',
					$delete ? 'ok' : 'fail',
					json_encode( $key ),
					json_encode( $group )
				)
			);
		}
		return $delete;
	}

	/**
	 * Delete a cache entry, extended key.
	 *
	 * @param string $key
	 * @param string $group
	 *
	 * @return boolean true if successful, otherwise false
	 */
	public static function delete_ext( $key, $group = self::CACHE_GROUP ) {
		$key = self::extend_key( $key );
		return wp_cache_delete( $key, $group );
	}

	/**
	 * Provide a specialized (groups-roles-user-based) version of the $group key.
	 *
	 * The $flags parameter is self::GROUPS_CACHE_GROUP | self::ROLES_CACHE_GROUP by default,
	 * meaning a user's groups and roles are used to specialize the $group key.
	 *
	 * @since 2.20.0
	 *
	 * @param string $group group key
	 * @param int $flags determines what to take into account, null uses groups and roles by default
	 *
	 * @return string
	 */
	public static function get_group( $group, $flags = null ) {

		if ( $flags === null ) {
			$flags = self::GROUPS_CACHE_GROUP | self::ROLES_CACHE_GROUP;
		}

		$group_ids = array();
		$roles = array();
		$user_id = null;

		if ( function_exists( 'wp_get_current_user' ) ) {
			$user = wp_get_current_user();
			if ( $user->exists() ) {
				if ( $flags & self::GROUPS_CACHE_GROUP ) {
					if ( is_array( $user->roles ) ) {
						$roles = $user->roles;
						sort( $roles );
					}
				}
				if ( $flags & self::ROLES_CACHE_GROUP ) {
					if ( class_exists( '\Groups_User' ) ) {
						$groups_user = new \Groups_User( $user->ID );
						$group_ids = $groups_user->get_group_ids_deep();
						$group_ids = array_map( 'intval', $group_ids );
						sort( $group_ids, SORT_NUMERIC );
					}
				}
				if ( $flags & self::USER_CACHE_GROUP ) {
					$user_id = $user->ID;
				}
			}
		}

		if ( count( $roles ) > 0 ) {
			$group .= '_';
			$group .= implode( '_', $roles );
		}
		if ( count( $group_ids ) > 0 ) {
			$group .= '_';
			$group .= implode( '_', $group_ids );
		}
		if ( $user_id !== null ) {
			$group .= '_';
			$group .= $user_id;
		}

		/**
		 * Apply suffix to extended group.
		 *
		 * @param string $suffix
		 * @param string $group
		 * @param int $flags
		 *
		 * @return string
		 */
		$group_suffix = apply_filters( 'groups_cache_group_suffix', '', $group, $flags );
		if ( !is_string( $group_suffix ) ) {
			$group_suffix = '';
		} else {
			$group_suffix = trim( sanitize_key( $group_suffix ) );
		}
		if ( strlen( $group_suffix ) > 0 ) {
			$group = $group . '_' . $group_suffix;
		}

		return $group;
	}

	/**
	 * Provide a specialized (groups-roles-user-based) version of the $key.
	 *
	 * The $flags parameter is self::GROUPS_CACHE_GROUP | self::ROLES_CACHE_GROUP by default,
	 * meaning a user's groups and roles are used to specialize the $key.
	 *
	 * @since 4.0.0
	 *
	 * @param string $key group key
	 * @param int $flags determines what to take into account, null uses groups and roles by default
	 *
	 * @return string
	 */
	public static function extend_key( $key, $flags = null ) {
		if ( $flags === null ) {
			$flags = self::GROUPS_CACHE_GROUP | self::ROLES_CACHE_GROUP;
		}

		$group_ids = array();
		$roles = array();
		$user_id = null;

		if ( function_exists( 'wp_get_current_user' ) ) {
			$user = wp_get_current_user();
			if ( $user->exists() ) {
				if ( $flags & self::GROUPS_CACHE_GROUP ) {
					if ( is_array( $user->roles ) ) {
						$roles = $user->roles;
						sort( $roles );
					}
				}
				if ( $flags & self::ROLES_CACHE_GROUP ) {
					if ( class_exists( '\Groups_User' ) ) {
						$groups_user = new \Groups_User( $user->ID );
						$group_ids = $groups_user->get_group_ids_deep();
						$group_ids = array_map( 'intval', $group_ids );
						sort( $group_ids, SORT_NUMERIC );
					}
				}
				if ( $flags & self::USER_CACHE_GROUP ) {
					$user_id = $user->ID;
				}
			}
		}

		if ( count( $roles ) > 0 ) {
			$key .= '_';
			$key .= implode( '_', $roles );
		}
		if ( count( $group_ids ) > 0 ) {
			$key .= '_';
			$key .= implode( '_', $group_ids );
		}
		if ( $user_id !== null ) {
			$key .= '_';
			$key .= $user_id;
		}

		/**
		 * Apply suffix to extended key.
		 *
		 * @param string $suffix
		 * @param string $key
		 * @param int $flags
		 *
		 * @return string
		 */
		$suffix = apply_filters( 'groups_cache_extend_key_suffix', '', $key, $flags );
		if ( !is_string( $suffix ) ) {
			$suffix = '';
		} else {
			$suffix = trim( sanitize_key( $suffix ) );
		}
		if ( strlen( $suffix ) > 0 ) {
			$key = $key . '_' . $suffix;
		}

		return $key;
	}
}

Groups_Cache::init();
