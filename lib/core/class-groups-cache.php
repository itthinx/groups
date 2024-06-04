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
	 * Retrieve an entry from cache.
	 *
	 * @param string $key
	 * @param string $group
	 *
	 * @return Groups_Cache_Object|null returns a cache object on hit, null on cache miss
	 */
	public static function get( $key, $group = self::CACHE_GROUP ) {
		$found = null;
		$value = wp_cache_get( $key, $group, false, $found );
		if ( !( $value instanceof Groups_Cache_Object ) ) {
			$value = null;
		}
		return $value;
	}

	/**
	 * Store an entry in cache.
	 *
	 * @param string $key
	 * @param string $value
	 * @param string $group
	 *
	 * @return boolean true if successful, otherwise false
	 */
	public static function set( $key, $value, $group = self::CACHE_GROUP ) {
		$object = new Groups_Cache_Object( $key, $value );
		return wp_cache_set( $key, $object, $group );
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
		 * Additional specialization if needed by third-party extensions.
		 *
		 * @param string $suffix
		 * @param string $group
		 * @param int $flags
		 *
		 * @var string $group_suffix
		 */
		$group_suffix = apply_filters( 'groups_cache_group_suffix', '', $group, $flags );
		if ( !is_string( $group_suffix ) ) {
			$group_suffix = '';
		} else {
			$group_suffix = trim( $group_suffix );
		}
		if ( strlen( $group_suffix ) > 0 ) {
			$group = $group . '_' . $group_suffix;
		}

		return $group;
	}
}
