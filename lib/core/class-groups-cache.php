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
	 * @var string
	 */
	const CACHE_GROUP = 'groups';

	/**
	 * Retrieve an entry from cache.
	 * 
	 * @param string $key
	 * @param string $group
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
	 * @return true if successful, otherwise false
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
	 * @return true if successful, otherwise false
	 */
	public static function delete( $key, $group = self::CACHE_GROUP ) {
		return wp_cache_delete( $key, $group );
	}
}
