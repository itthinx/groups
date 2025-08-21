<?php
/**
 * class-groups-cache-object.php
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
 * Cache entry encapsulation.
 *
 * @property string $key
 * @property mixed $value
 */
class Groups_Cache_Object {

	/**
	 * Cache key.
	 *
	 * @var string
	 */
	private $key = null;

	/**
	 * Cached value.
	 *
	 * @var mixed
	 */
	private $value = null;

	/**
	 * Create a cache entry object that holds a value for the given key.
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function __construct( $key, $value ) {
		if ( is_string( $key ) ) {
			$this->key = $key;
		}
		$this->value = $value;
	}

	/**
	 * Getter implementation for key and value properties.
	 *
	 * @param string $name
	 *
	 * @return mixed property value or null
	 */
	public function __get( $name ) {
		$result = null;
		switch ( $name ) {
			case 'key' :
			case 'value' :
				$result = $this->$name;
				break;
		}
		return $result;
	}

	/**
	 * Setter for key and value properties.
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set( $name, $value ) {
		switch( $name ) {
			case 'key' :
				if ( is_string( $value ) ) {
					$this->key = $value;
				}
				break;
			case 'value' :
				$this->value = $value;
				break;
		}
	}

	/**
	 * Provide the key of this object.
	 *
	 * @since 3.6.0
	 *
	 * @return string
	 */
	public function get_key() {
		return $this->key;
	}

	/**
	 * Set the key of this object.
	 *
	 * @since 3.6.0
	 *
	 * @param string $key
	 */
	public function set_key( $key ) {
		if ( is_string( $key ) ) {
			$this->key = $key;
		}
	}

	/**
	 * Provide the value of this object.
	 *
	 * @since 3.6.0
	 *
	 * @return mixed
	 */
	public function get_value() {
		return $this->value;
	}

	/**
	 * Set the value of this object.
	 *
	 * @since 3.6.0
	 *
	 * @param mixed $value
	 */
	public function set_value( $value ) {
		$this->value = $value;
	}
}
