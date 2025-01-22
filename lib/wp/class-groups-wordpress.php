<?php
/**
 * class-groups-wordpress.php
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
 * WordPress capabilities integration.
 */
class Groups_WordPress {

	/**
	 * Cache group
	 *
	 * @var string
	 */
	const CACHE_GROUP = 'groups';

	/**
	 * Cache key prefix
	 *
	 * @var string
	 */
	const HAS_CAP = 'has_cap';

	/**
	 * Filter priority: groups_user_can
	 *
	 * @var integer
	 *
	 * @since 2.11.0
	 */
	const GROUPS_USER_CAN_FILTER_PRIORITY = 10;

	/**
	 * Filter priority: user_has_cap
	 *
	 * @var int
	 *
	 * @since 2.11.0
	 */
	const USER_HAS_CAP_FILTER_PRIORITY = PHP_INT_MAX;

	/**
	 * Hook into actions to extend user capabilities.
	 *
	 * @todo We might want to keep up with new capabilities when added, so
	 * that others don't have to add these explicitly to Groups when they
	 * add them to WordPress. Currently there's no hook for when a capability
	 * is added and checking this in any other way is too costly.
	 */
	public static function init() {
		// args: boolean $result, Groups_User $groups_user, string $capability
		add_filter( 'groups_user_can', array( __CLASS__, 'groups_user_can' ), self::GROUPS_USER_CAN_FILTER_PRIORITY, 5 );
		add_filter( 'user_has_cap', array( __CLASS__, 'user_has_cap' ), self::USER_HAS_CAP_FILTER_PRIORITY, 4 );
	}

	/**
	 * Whether the given user has the capability.
	 *
	 * Calls Groups_User::user_can() with user_has_cap and groups_user_can filters temporarily removed.
	 *
	 * @since 3.1.0
	 *
	 * @param int|WP_User $user user ID or object
	 * @param string $capability capability name
	 * @param mixed ...$args optional parameters, typically an object ID
	 *
	 * @return boolean
	 */
	private static function unfiltered_user_can( $user, $capability, ...$args ) {
		// We want to check without the capabilities granted to the user via groups.
		// Groups_User::user_can() relies on user_can() or WP_User->has_cap() in the absense of the former,
		// which will cause our user_has_cap filter to act so we have to remove it temporarily.
		// To avoid potential internal conflicts or cicular dependencies, we also remove the groups_user_can filter which
		// relies on this method.
		$result = false;
		$filter_user_has_cap = remove_filter( 'user_has_cap', array( __CLASS__, 'user_has_cap' ), self::USER_HAS_CAP_FILTER_PRIORITY ); // @since 3.1.0
		$filter_groups_user_can = remove_filter( 'groups_user_can', array( __CLASS__, 'groups_user_can' ), self::GROUPS_USER_CAN_FILTER_PRIORITY ); // @since 3.1.0
		$result = Groups_User::user_can( $user, $capability, ...$args );
		if ( $filter_user_has_cap ) {
			add_filter( 'user_has_cap', array( __CLASS__, 'user_has_cap' ), self::USER_HAS_CAP_FILTER_PRIORITY, 4 );
		}
		if ( $filter_groups_user_can ) {
			add_filter( 'groups_user_can', array( __CLASS__, 'groups_user_can' ), self::GROUPS_USER_CAN_FILTER_PRIORITY, 5 );
		}
		return $result;
	}

	/**
	 * Extends Groups user capability with its WP_User capability.
	 *
	 * @param boolean $result
	 * @param Groups_User $groups_user
	 * @param string $capability
	 * @param mixed $object
	 * @param mixed $args
	 *
	 * @return boolean
	 */
	public static function groups_user_can( $result, $groups_user, $capability, $object, $args ) {
		// The intention here is to complement capabilities from groups with those from the user.
		// Our user_has_cap filter extends user capabilities with those from groups, i.e. the reverse complementary.
		// Thus, our user_has_cap filter should not be involved here. Also, we want to avoid any potential circular
		// dependency which could arise from having our groups_user_can fired again while we attend to that action here.
		// To avoid any potential hickups, we also remove the filter while we handle it.
		$filter_groups_user_can = remove_filter( 'groups_user_can', array( __CLASS__, 'groups_user_can' ), self::GROUPS_USER_CAN_FILTER_PRIORITY ); // @since 3.1.0
		if ( !$result ) {
			// Check if the capability exists, otherwise this will
			// produce a deprecation warning "Usage of user levels by plugins
			// and themes is deprecated", not because we actually use a
			// deprecated user level, but because it doesn't exist.
			if ( Groups_Capability::read_by_capability( $capability ) ) {
				if ( $groups_user instanceof Groups_User ) {
					$user_id = $groups_user->get_user_id();
					if ( $user_id !== null ) {
						if ( $object === null ) {
							$result = self::unfiltered_user_can( $user_id, $capability );
						} else {
							// @since 3.0.0
							$object_id = null;
							if ( is_numeric( $object ) ) {
								$object_id = Groups_Utility::id( $object_id );
							} else if ( is_object( $object ) && method_exists( $object, 'get_id' ) ) {
								$object_id = $object->get_id();
							}
							// PHP >= 8.1.0 named arguments can be used after unpacking ...$args
							// With prior PHP versions, the order in which values appear in an $args array
							// determines the order of the parameters passed.
							if ( $object_id !== null ) {
								$result = self::unfiltered_user_can( $user_id, $capability, $object_id, ...$args );
							} else {
								$result = self::unfiltered_user_can( $user_id, $capability, $object, ...$args );
							}
						}
					}
				}
			}
		}
		if ( $filter_groups_user_can ) {
			add_filter( 'groups_user_can', array( __CLASS__, 'groups_user_can' ), self::GROUPS_USER_CAN_FILTER_PRIORITY, 5 ); // @since 3.1.0
		}
		return $result;
	}

	/**
	 * Extend user capabilities with Groups user capabilities.
	 *
	 * @param array $allcaps the capabilities the user has
	 * @param array $caps the requested capabilities
	 * @param array $args capability context which can provide the requested capability as $args[0], the user ID as $args[1] and the related object's ID as $args[2]
	 * @param WP_User $user the user object
	 *
	 * @return array
	 */
	public static function user_has_cap( $allcaps, $caps, $args, $user ) {
		if ( is_array( $caps ) ) {
			$user_id = 0;
			if ( isset( $user->ID ) ) {
				$user_id = intval( $user->ID );
			} else if ( isset( $args[1] ) ) {
				$user_id = intval( $args[1] );
			}
			$hash    = md5( json_encode( $caps ) . json_encode( $args ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
			$cached  = Groups_Cache::get( self::HAS_CAP . '_' . $user_id . '_' . $hash, self::CACHE_GROUP );

			if ( $cached !== null ) {
				$_allcaps = $cached->value;
				unset( $cached );
				// Capabilities that were added after our value was cached must be added and
				// those entries that provide different values must be adopted. This is necessary
				// because other filters which hook into user_has_cap might have added or modified
				// things after we had already cached the values.
				foreach ( $allcaps as $cap => $value ) {
					if (
						!key_exists( $cap, $_allcaps ) ||
						$_allcaps[$cap] !== $value
					) {
						$_allcaps[$cap] = $value;
					}
				}
				$allcaps = $_allcaps;
			} else {
				$groups_user = new Groups_User( $user_id );
				// we need to deactivate this because invoking $groups_user->can()
				// would trigger this same function and we would end up
				// in an infinite loop
				remove_filter( 'user_has_cap', array( __CLASS__, 'user_has_cap' ), self::USER_HAS_CAP_FILTER_PRIORITY );
				foreach( $caps as $cap ) {
					if ( $groups_user->can( $cap ) ) {
						$allcaps[$cap] = true;
					}
				}
				add_filter( 'user_has_cap', array( __CLASS__, 'user_has_cap' ), self::USER_HAS_CAP_FILTER_PRIORITY, 4 );
				Groups_Cache::set( self::HAS_CAP . '_' . $user_id . '_' . $hash, $allcaps, self::CACHE_GROUP );
			}
		}
		$allcaps = apply_filters( 'groups_user_has_cap', $allcaps, $caps, $args, $user );
		return $allcaps;
	}

	/**
	 * Adds WordPress capabilities to Groups capabilities.
	 * Must be called explicitly.
	 *
	 * @see Groups_Controller::activate()
	 */
	public static function activate() {
		self::refresh_capabilities();
	}

	/**
	 * Refreshes Groups capabilities based on WordPress capabilities.
	 *
	 * @return int number of capabilities added
	 */
	public static function refresh_capabilities() {
		global $wp_roles;
		$capabilities = array();
		$count = 0;
		if ( !isset( $wp_roles ) ) {
			// just trigger initialization
			get_role( 'administrator' );
		}
		$roles = $wp_roles->roles;
		if ( is_array( $roles ) ) {
			foreach ( $roles as $rolename => $atts ) {
				if ( isset( $atts['capabilities'] ) && is_array( $atts['capabilities'] ) ) {
					foreach ( $atts['capabilities'] as $capability => $value ) {
						if ( !in_array( $capability, $capabilities ) ) {
							$capabilities[] = $capability;
						}
					}
				}
			}
		}
		foreach ( $capabilities as $capability ) {
			if ( !Groups_Capability::read_by_capability( $capability ) ) {
				Groups_Capability::create( array( 'capability' => $capability ) );
				$count++;
			}
		}
		return $count;
	}
}
Groups_WordPress::init();
