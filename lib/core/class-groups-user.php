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

	const CACHE_GROUP         = 'groups';
	const CAPABILITIES        = 'capabilities';
	const CAPABILITIES_BASE   = 'capabilities_base';
	const CAPABILITY_IDS      = 'capability_ids';
	const CAPABILITY_IDS_BASE = 'capability_ids_base';
	const GROUP_IDS           = 'group_ids';
	const GROUP_IDS_BASE      = 'group_ids_base';
	const GROUPS              = 'groups';
	const GROUPS_BASE         = 'groups_base';

	/**
	 * User object.
	 *
	 * @var WP_User
	 */
	var $user = null;

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
	 * @param int $user_id
	 */
	public static function clear_cache( $user_id ) {
		// be lazy, clear the entries so they are rebuilt when requested
		Groups_Cache::delete( self::CAPABILITIES . $user_id, self::CACHE_GROUP );
		Groups_Cache::delete( self::CAPABILITIES_BASE . $user_id, self::CACHE_GROUP );
		Groups_Cache::delete( self::CAPABILITY_IDS . $user_id, self::CACHE_GROUP );
		Groups_Cache::delete( self::CAPABILITY_IDS_BASE . $user_id, self::CACHE_GROUP );
		Groups_Cache::delete( self::GROUP_IDS . $user_id, self::CACHE_GROUP );
		Groups_Cache::delete( self::GROUP_IDS_BASE . $user_id, self::CACHE_GROUP );
		Groups_Cache::delete( self::GROUPS . $user_id, self::CACHE_GROUP );
		Groups_Cache::delete( self::GROUPS_BASE . $user_id, self::CACHE_GROUP );
	}

	/**
	 * Clear cache objects for all users in the group.
	 * @param int $group_id
	 */
	public static function clear_cache_for_group( $group_id ) {
		global $wpdb;
		if ( $group = Groups_Group::read( $group_id ) ) {
			// not using $group->users, as we don't need a lot of user objects created here
			$user_group_table = _groups_get_tablename( 'user_group' );
			$users = $wpdb->get_results( $wpdb->prepare(
				"SELECT ID FROM $wpdb->users LEFT JOIN $user_group_table ON $wpdb->users.ID = $user_group_table.user_id WHERE $user_group_table.group_id = %d",
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
	 * Retrieve a user property.
	 * Must be "capabilities", "groups" or a property of the WP_User class.
	 * @param string $name property's name
	 */
	public function __get( $name ) {

		global $wpdb;
		$result = null;

		if ( $this->user !== null ) {

			/**
			 * Filter the user properties.
			 * @param WP_User
			 * @param string
			 */
			$null = apply_filters( 'groups_user_get_' . $name, null, $this->user, $name );
			if ( null !== $null ) {
				return $null;
			}

			switch ( $name ) {

				case 'capability_ids' :
					$cached = Groups_Cache::get( self::CAPABILITY_IDS_BASE . $this->user->ID, self::CACHE_GROUP );
					if ( $cached !== null ) {
						$result = $cached->value;
						unset( $cached );
					} else {
						$user_capability_table = _groups_get_tablename( 'user_capability' );
						$rows = $wpdb->get_results( $wpdb->prepare(
							"SELECT capability_id FROM $user_capability_table WHERE user_id = %d",
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
							"SELECT group_id FROM $user_group_table WHERE user_id = %d",
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
							"SELECT capability_id FROM $user_capability_table WHERE user_id = %d",
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
						$cached = Groups_Cache::get( self::CAPABILITIES . $this->user->ID, self::CACHE_GROUP );
						if ( $cached !== null ) {
							$capabilities = $cached->value;
							unset( $cached );
						} else {
							$this->init_cache( $capability_ids, $capabilities );
						}
						$result = $capabilities;
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
							"SELECT group_id FROM $user_group_table WHERE user_id = %d",
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
						foreach( $this->group_ids_deep as $group_id ) {
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
	 * (non-PHPdoc)
	 * @see I_Capable::can()
	 */
	public function can( $capability ) {

		global $wpdb;
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
		$result = apply_filters_ref_array( 'groups_user_can', array( $result, &$this, $capability ) );
		return $result;
	}

	/**
	 * Returns true if the user belongs to the group.
	 *
	 * @param int $group_id
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

			$limit = $wpdb->get_var( "SELECT COUNT(*) FROM $group_table" );
			if ( $limit === null ) {
				$limit = 1;
			}

			// note that limits by blog_id for multisite are
			// enforced when a user is added to a blog
			$user_groups = $wpdb->get_results( $wpdb->prepare(
				"SELECT group_id FROM $user_group_table WHERE user_id = %d",
				Groups_Utility::id( $this->user->ID )
			) );
			// get all capabilities directly assigned (those granted through
			// groups are added below
			$user_capabilities = $wpdb->get_results( $wpdb->prepare(
				"SELECT c.capability_id, c.capability FROM $user_capability_table uc LEFT JOIN $capability_table c ON c.capability_id = uc.capability_id WHERE user_id = %d",
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
							$caps[] = "'" . $role_cap . "'";
						}
					}
					if ( !empty( $caps ) ) {
						// Retrieve the capabilities and only add those that are
						// recognized. Note that this also effectively filters out
						// all roles and that this is desired.
						if ( $role_capabilities = $wpdb->get_results( "SELECT capability_id, capability FROM $capability_table c WHERE capability IN (" . implode( ',', $caps ) . ")" ) ) {
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
							"SELECT parent_id FROM $group_table WHERE parent_id IS NOT NULL AND group_id IN ($id_list)"
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
						"SELECT $group_capability_table.capability_id, $capability_table.capability FROM $group_capability_table LEFT JOIN $capability_table ON $group_capability_table.capability_id = $capability_table.capability_id WHERE group_id IN ($id_list)"
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
