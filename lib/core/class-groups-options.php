<?php
/**
 * class-groups-options.php
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
 * Groups options handler
 */
class Groups_Options {

	/**
	 * Groups plugin option key.
	 *
	 * @var string
	 */
	const option_key = 'groups_options';

	/**
	 * General option index.
	 *
	 * @var string
	 */
	const general = 'general';

	/**
	 * @var Groups_Lock
	 *
	 * @since 4.0.0
	 */
	private static $lock = null;

	/**
	 * Registers Groups options (not autoloaded).
	 */
	public static function init() {
		$options = get_option( self::option_key );
		if ( $options === false ) {
			$options = array( self::general => array() );
			add_option( self::option_key, $options, '', false );
		}
	}

	/**
	 * Returns the current Groups options and initializes them
	 * through init() if needed.
	 *
	 * @return array Groups options
	 */
	private static function get_options() {
		$options = get_option( self::option_key );
		if ( $options === false ) {
			self::init();
			$options = get_option( self::option_key );
		}
		return $options;
	}

	/**
	 * Soft lock for general option access and modification.
	 *
	 * @since 4.0.0
	 */
	public static function lock() {
		if ( self::$lock === null ) {
			try {
				self::$lock = new Groups_Lock( 'groups-options' );
				self::$lock->writer();
			} catch ( Groups_Lock_Exception $lex ) {
				self::$lock = null;
			}
		}
	}

	/**
	 * Release soft lock for general option access.
	 *
	 * @since 4.0.0
	 */
	public static function release() {
		if ( self::$lock !== null ) {
			self::$lock->release();
		}
	}

	/**
	 * Returns the value of a general setting.
	 *
	 * @param string $option the option id
	 * @param mixed $default default value to retrieve if option is not set
	 *
	 * @return mixed option value, $default if set or null
	 */
	public static function get_option( $option, $default = null ) {
		$options = self::get_options();
		$value = isset( $options[self::general][$option] ) ? $options[self::general][$option] : null;
		if ( $value === null ) {
			$value = $default;
		}
		return $value;
	}


	/**
	 * Returns the value of a user setting.
	 *
	 * @param string $option the option id
	 * @param mixed $default default value to retrieve if option is not set
	 * @param int $user_id retrieve option for this user, defaults to null for current user
	 *
	 * @return mixed option value, $default if set or null
	 */
	public static function get_user_option( $option, $default = null, $user_id = null ) {
		if ( $user_id === null ) {
			$current_user = wp_get_current_user();
			if ( !empty( $current_user ) ) { // @phpstan-ignore empty.variable
				$user_id = $current_user->ID;
			}
		}
		$value = null;
		if ( $user_id !== null ) {
			$options = self::get_options();
			$value = isset( $options[$user_id][$option] ) ? $options[$user_id][$option] : null;
		}
		if ( $value === null ) {
			$value = $default;
		}
		return $value;
	}

	/**
	 * Updates a general setting.
	 *
	 * @param string $option the option's id
	 * @param mixed $new_value the new value
	 */
	public static function update_option( $option, $new_value ) {
		$options = self::get_options();
		$options[self::general][$option] = $new_value;
		wp_cache_delete( self::option_key, 'options' );
		if ( update_option( self::option_key, $options ) ) {
			do_action( 'groups_updated_option', $option, $new_value );
		}
	}

	/**
	 * Updates a user setting.
	 *
	 * @param string $option the option's id
	 * @param mixed $new_value the new value
	 * @param int $user_id update option for this user, defaults to null for current user
	 */
	public static function update_user_option( $option, $new_value, $user_id = null ) {

		if ( $user_id === null ) {
			$current_user = wp_get_current_user();
			if ( !empty( $current_user ) ) { // @phpstan-ignore empty.variable
				$user_id = $current_user->ID;
			}
		}

		if ( $user_id !== null ) {
			$options = self::get_options();
			$options[$user_id][$option] = $new_value;
			wp_cache_delete( self::option_key, 'options' );
			if ( update_option( self::option_key, $options ) ) {
				do_action( 'groups_updated_user_option', $option, $new_value, $user_id );
			}
		}
	}

	/**
	 * Deletes a general setting.
	 *
	 * @param string $option the option's id
	 */
	public static function delete_option( $option ) {
		$options = self::get_options();
		if ( isset( $options[self::general][$option] ) ) {
			unset( $options[self::general][$option] );
			wp_cache_delete( self::option_key, 'options' );
			if ( update_option( self::option_key, $options ) ) {
				do_action( 'groups_deleted_option', $option );
			}
		}
	}

	/**
	 * Deletes a user setting.
	 *
	 * @param string $option the option's id
	 * @param int $user_id delete option for this user, defaults to null for current user
	 */
	public static function delete_user_option( $option, $user_id = null ) {

		if ( $user_id === null ) {
			$current_user = wp_get_current_user();
			if ( !empty( $current_user ) ) { // @phpstan-ignore empty.variable
				$user_id = $current_user->ID;
			}
		}

		if ( $user_id !== null ) {
			$options = self::get_options();
			if ( isset( $options[$user_id][$option] ) ) {
				unset( $options[$user_id][$option] );
				wp_cache_delete( self::option_key, 'options' );
				if ( update_option( self::option_key, $options ) ) {
					do_action( 'groups_deleted_user_option', $user_id );
				}
			}
		}
	}

	/**
	 * Deletes all settings - this includes user and general options.
	 */
	public static function flush_options() {
		wp_cache_delete( self::option_key, 'options' );
		if ( delete_option( self::option_key ) ) {
			do_action( 'groups_flushed_options' );
		}
	}
}
