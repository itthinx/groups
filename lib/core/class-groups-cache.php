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

}
Groups_Cache::init();

// @todo remove
class Groups_Cache_Token_Solution {

	const CACHE_GROUP = 'groups';

	private static $found_is_supported = false;

	const TRUE_TOKEN = 'groups-cache:{true}';

	const FALSE_TOKEN = 'groups-cache:{false}';

	public static function init() {
		$found = time();
		$result = wp_cache_get( 'groups-cache-test' . $found, self::CACHE_GROUP, false, $found );
		if ( $found !== false ) {
			// cache miss disambiguation is not supported
			error_log( __METHOD__ . " " .var_export($found,true). " found is not supported " ); // @todo remove
			self::$found_is_supported = false;
		} else {
			error_log( __METHOD__ . " " .var_export($found,true). " found IS supported" ); // @todo remove
			self::$found_is_supported = true;
		}
	}

	public static function get( $key, $group = self::CACHE_GROUP ) {
		$value = wp_cache_get( $key, $group, false, $found );
		if ( !self::$found_is_supported ) {
			if ( is_string( $value ) ) {
				switch( $value ) {
					case self::TRUE_TOKEN :
						$value = true;
						break;
					case self::FALSE_TOKEN :
						$value = false;
						break;
				}
			}
		}
		return $value;
	}

	public static function set( $key, $value, $group = self::CACHE_GROUP ) {
		if ( !self::$found_is_supported ) {
			if ( $value === true ) {
				$value = self::TRUE_TOKEN;
			} else if ( $value === false ) {
				$value = self::FALSE_TOKEN;
			}
		}
		wp_cache_set( $key, $value, $group );
	}
}
Groups_Cache_Token_Solution::init();
