<?php
/**
 * class-groups-log.php
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
 * Logger
 */
class Groups_Log {

	/**
	 * Process ID.
	 *
	 * @var int|string
	 */
	private static $pid = null;

	/**
	 * Hashes.
	 *
	 * @var string[]
	 */
	private static $hashes = array();

	/**
	 * Log message.
	 *
	 * Always logs errors. Warnings and infos are logged with $force or GROUPS_DEBUG.
	 *
	 * @param string $message
	 * @param int|string|null $level 'info' (default), 'warning', 'error'
	 * @param boolean $force
	 */
	public static function log( $message, $level = null, $force = false ) {
		if ( !is_string( $message ) || strlen( $message ) === 0 ) {
			return;
		}
		if ( !is_string( $level ) ) {
			$level = 'info';
		}
		$level = strtolower( $level );
		switch ( $level ) {
			case 'error':
			case 'warning':
			case 'info':
				break;
			default:
				$level = 'info';
		}

		// process or pseudo ID
		if ( self::$pid === null ) {
			if ( function_exists( 'getmypid' ) ) {
				self::$pid = @getmypid();
				if ( self::$pid === false ) {
					self::$pid = null;
				}
			}
			if ( self::$pid === null ) {
				self::$pid = hash( 'crc32', rand( 0, time() ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.rand_rand
			}
		}

		$level_str = strtoupper( $level );
		if (
			$force ||
			defined( 'GROUPS_DEBUG' ) && GROUPS_DEBUG ||
			'ERROR' === $level_str
		) {
			$entry = sprintf(
				'Groups [%7s] [%8s] | %s',
				$level_str,
				self::$pid ?? '?',
				$message,
			);
			$hash = md5( $entry );
			if ( !in_array( $hash, self::$hashes ) ) {
				self::$hashes[] = $hash;
				error_log( $entry ); // phpcs:ignore QITStandard.PHP.DebugCode.DebugFunctionFound, WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
		}
	}
}
