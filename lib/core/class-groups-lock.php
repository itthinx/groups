<?php
/**
 * class-groups-lock.php
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
 * Lock.
 */
class Groups_Lock {

	/**
	 * @var int
	 */
	const TIMEOUT_DEFAULT = 1000000;

	/**
	 * @var int
	 */
	const SLEEP_DEFAULT = 10000;

	/**
	 * @var int
	 */
	const AUX_TIMEOUT = 2;

	/**
	 * Lockfile path.
	 *
	 * @var string
	 */
	private $path = null;

	/**
	 * Lock file pointer.
	 *
	 * @var resource
	 */
	private $h = null;

	/**
	 * Timeout microseconds.
	 *
	 * @var int
	 */
	private $timeout = self::TIMEOUT_DEFAULT;

	/**
	 * Sleep microseconds.
	 *
	 * @var integer
	 */
	private $sleep = self::SLEEP_DEFAULT;

	/**
	 * Create an instance.
	 *
	 * @throws Groups_Lock_Exception
	 *
	 * @param string $name lock name, generic lock used by default
	 */
	public function __construct( $name = null ) {
		if (
			function_exists( 'fopen' ) &&
			function_exists( 'flock' ) &&
			function_exists( 'fclose' ) &&
			function_exists( 'is_readable' )
		) {

			if ( $name !== null ) {
				$name = trim( sanitize_key( $name ) );
			} else {
				$name = 'groups-lock';
			}
			$blog_id = intval( get_current_blog_id() );
			$path = sprintf(
				'%s%s.%s-%d',
				untrailingslashit( WP_CONTENT_DIR ),
				DIRECTORY_SEPARATOR,
				$name,
				$blog_id
			);
			/**
			 * Modify the lock file path.
			 *
			 * @param string $path
			 * @param string $name
			 * @param int $blog_id
			 *
			 * @return string
			 */
			$path = apply_filters( 'groups_lock_path', $path, $name, $blog_id );

			/**
			 * Modify the lock timeout (microseconds).
			 *
			 * @param int $timeout
			 * @param string $name
			 * @param int $blog_id
			 *
			 * @return int
			 */
			$this->timeout = apply_filters( 'groups_lock_timeout', $this->timeout, $name, $blog_id );
			if ( is_numeric( $this->timeout ) ) {
				$this->timeout = max( 0, intval( $this->timeout ) );
			} else {
				$this->timeout = 0;
			}

			/**
			 * Modify the lock sleep gap (microseconds).
			 *
			 * @param int $sleep
			 * @param string $name
			 * @param int $blog_id
			 *
			 * @return int
			 */
			$this->sleep = apply_filters( 'groups_lock_sleep', $this->sleep, $name, $blog_id );
			if ( is_numeric( $this->sleep ) ) {
				$this->sleep = max( 1, intval( $this->sleep ) );
			} else {
				$this->sleep = self::SLEEP_DEFAULT;
			}

			if ( is_string( $path ) ) {
				$h = @fopen( $path, 'c' );
				if ( $h !== false ) {
					$this->path = $path;
					@fclose( $h );
				} else {
					$error = error_get_last();
					$msg = $error !== null && !empty( $error['message'] ) ? $error['message'] : '';
					if ( !file_exists( $path ) ) {
						$error = sprintf( 'Failed to create lock %s [%s]', $path, $msg );
					} else {
						$error = sprintf( 'Failed to access lock %s [%s]', $path, $msg );
					}
					throw new Groups_Lock_Exception( $error );
				}
			}
			register_shutdown_function( array( $this, 'release' ) );
		} else {
			throw new Groups_Lock_Exception( 'Lock requires fopen, flock, fclose, is_readable' );
		}
	}

	/**
	 * Lock is usable.
	 *
	 * @return boolean
	 */
	public function is_usable() {
		return $this->path !== null && is_readable( $this->path );
	}

	/**
	 * Write lock.
	 *
	 * @throws Groups_Lock_Exception
	 *
	 * @return boolean
	 */
	public function writer() {
		if ( $this->path !== null ) {
			if ( $this->h === null ) {
				$h = @fopen( $this->path, 'r+' );
				if ( $h !== false ) {
					if ( $this->timeout === 0 ) {
						// blocking
						if ( @flock( $h, LOCK_EX ) ) {
							$this->h = $h;
						}
					} else {
						// non-blocking
						if ( function_exists( 'microtime' ) && function_exists( 'usleep' ) ) {
							$t = microtime( true ) * 1000000;
							while ( $this->h === null && ( microtime( true ) * 1000000 - $t < $this->timeout ) ) {
								if ( @flock( $h, LOCK_EX | LOCK_NB ) ) {
									$this->h = $h;
									break;
								} else {
									usleep( $this->sleep );
								}
							}
						} else {
							// fallback if required functions missing
							$t = time();
							while ( $this->h === null && ( time() - $t < self::AUX_TIMEOUT ) ) {
								if ( @flock( $h, LOCK_EX | LOCK_NB ) ) {
									$this->h = $h;
									break;
								} else {
									sleep( 1 );
								}
							}
						}
					}
					if ( $this->h === null ) {
						throw new Groups_Lock_Exception( sprintf( 'Failed to obtain write lock %s', $this->path ) );
					}
				} else {
					throw new Groups_Lock_Exception( sprintf( 'Failed to access write lock %s ', $this->path ) );
				}
			}
		}
		return $this->h !== null;
	}

	/**
	 * Read lock.
	 *
	 * @throws Groups_Lock_Exception
	 *
	 * @return boolean
	 */
	public function reader() {
		if ( $this->path !== null ) {
			if ( $this->h === null ) {
				$h = @fopen( $this->path, 'r' );
				if ( $h !== false ) {
					if ( $this->timeout === 0 ) {
						// blocking
						if ( @flock( $h, LOCK_SH ) ) {
							$this->h = $h;
						}
					} else {
						// non
						if ( function_exists( 'microtime' ) && function_exists( 'usleep' ) ) {
							$t = microtime( true ) * 1000000;
							while ( $this->h === null && ( microtime( true ) * 1000000 - $t < $this->timeout ) ) {
								if ( @flock( $h, LOCK_SH | LOCK_NB ) ) {
									$this->h = $h;
									break;
								} else {
									usleep( $this->sleep );
								}
							}
						} else {
							// fallback
							$t = time();
							while ( $this->h === null && ( time() - $t < self::AUX_TIMEOUT ) ) {
								if ( @flock( $h, LOCK_SH | LOCK_NB ) ) {
									$this->h = $h;
									break;
								} else {
									sleep( 1 );
								}
							}
						}
					}
					if ( $this->h === null ) {
						throw new Groups_Lock_Exception( sprintf( 'Failed to obtain read lock %s', $this->path ) );
					}
				} else {
					throw new Groups_Lock_Exception( sprintf( 'Failed to access read lock %s', $this->path ) );
				}
			}
		}
		return $this->h !== null;
	}

	/**
	 * Release lock.
	 *
	 * @return boolean
	 */
	public function release() {
		$released = false;
		if ( $this->h !== null ) {
			$released = @flock( $this->h, LOCK_UN );
			@fclose( $this->h );
			$this->h = null;
		}
		return $released;
	}
}
