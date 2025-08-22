<?php
/**
 * class-groups-group.php
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

/**
 * Group OPM.
 */
class Groups_Group implements I_Capable {

	/**
	 * @var string cache group
	 */
	const CACHE_GROUP = 'groups';

	/**
	 * @var string key
	 */
	const READ_GROUP_BY_ID = 'read_group_by_id';

	/**
	 * @var string key
	 */
	const READ_BY_NAME = 'read_by_name';

	/**
	 * @var Object Persisted group.
	 *
	 * @access private - do not access this property directly, the visibility will be made private in the future
	 */
	public $group = null;

	/**
	 * Create by group id.
	 *
	 * Must have been persisted.
	 *
	 * @param int $group_id
	 */
	public function __construct( $group_id ) {
		$this->group = self::read( $group_id );
	}

	/**
	 * Provides the object ID.
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->get_group_id();
	}

	/**
	 * Provides the object ID.
	 *
	 * @return int
	 */
	public function get_group_id() {
		return $this->group_id; // @phpstan-ignore property.notFound
	}

	/**
	 * Provides the parent group's ID.
	 *
	 * @return int
	 */
	public function get_parent_id() {
		return $this->parent_id; // @phpstan-ignore property.notFound
	}

	/**
	 * Provides the creator's ID.
	 *
	 * @return int
	 */
	public function get_creator_id() {
		return $this->creator_id; // @phpstan-ignore property.notFound
	}

	/**
	 * Provides the date and time of creation.
	 *
	 * @return string
	 */
	public function get_datetime() {
		return $this->datetime; // @phpstan-ignore property.notFound
	}

	/**
	 * Provides the group's name.
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name; // @phpstan-ignore property.notFound
	}

	/**
	 * Provides the group's description.
	 *
	 * @return string
	 */
	public function get_description() {
		return $this->description; // @phpstan-ignore property.notFound
	}

	/**
	 * Provides the capabilities of the group.
	 *
	 * @return Groups_Capability[]
	 */
	public function get_capabilities() {
		return $this->capabilities; // @phpstan-ignore property.notFound
	}

	/**
	 * Provides the IDs of the capabilities of this group.
	 *
	 * @return int[]
	 */
	public function get_capability_ids() {
		return $this->capability_ids; // @phpstan-ignore property.notFound
	}

	/**
	 * Provides the capabilities of the group and of all its ancestors.
	 *
	 * @return Groups_Capability[]
	 */
	public function get_capabilities_deep() {
		return $this->capabilities_deep; // @phpstan-ignore property.notFound
	}

	/**
	 * Provides the IDs of the capabilities of this group and of all its ancestors.
	 *
	 * @return int[]
	 */
	public function get_capability_ids_deep() {
		return $this->capability_ids_deep; // @phpstan-ignore property.notFound
	}

	/**
	 * Provides the members of the group.
	 *
	 * @return Groups_User[]
	 */
	public function get_users() {
		return $this->users; // @phpstan-ignore property.notFound
	}

	/**
	 * Provides the user IDs of the members of this group.
	 *
	 * @return int[]
	 */
	public function get_user_ids() {
		return $this->user_ids; // @phpstan-ignore property.notFound
	}

	/**
	 * Retrieve a property by name.
	 *
	 * Possible properties:
	 * - group_id
	 * - parent_id
	 * - creator_id
	 * - datetime
	 * - name
	 * - description
	 * - capabilities, returns an array of Groups_Capability
	 * - users, returns an array of Groups_User
	 *
	 * @param string $name property's name
	 *
	 * @return mixed property value, will return null if property does not exist
	 */
	public function __get( $name ) {
		global $wpdb;
		$result = null;
		if ( $this->group !== null ) {
			switch( $name ) {
				case 'group_id' :
				case 'parent_id' :
				case 'creator_id' :
				case 'datetime' :
				case 'name' :
				case 'description' :
					$result = $this->group->$name;
					break;
				case 'capability_ids' :
					$result = array();
					$group_capability_table = _groups_get_tablename( 'group_capability' );
					$rows = $wpdb->get_results( $wpdb->prepare(
						"SELECT capability_id FROM $group_capability_table WHERE group_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						Groups_Utility::id( $this->group->group_id )
					) );
					if ( $rows ) {
						foreach ( $rows as $row ) {
							$result[] = $row->capability_id;
						}
					}
					break;
				case 'capabilities' :
					$result = array();
					$capability_ids = $this->capability_ids; // @phpstan-ignore property.notFound
					foreach ( $capability_ids as $capability_id ) {
						$result[] = new Groups_Capability( $capability_id );
					}
					break;
				case 'capabilities_deep' :
					$result = array();
					$capability_ids = $this->capability_ids_deep; // @phpstan-ignore property.notFound
					foreach( $capability_ids as $capability_id ) {
						$result[] = new Groups_Capability( $capability_id );
					}
					break;
				case 'capability_ids_deep' :
					$capability_ids = array();
					$group_table = _groups_get_tablename( 'group' );
					$group_capability_table = _groups_get_tablename( "group_capability" );
					// Find this group's and all its parent groups' capabilities.
					$group_ids  = array( Groups_Utility::id( $this->group->group_id ) );
					$iterations = 0;
					$old_group_ids_count = 0;
					$all_groups = $wpdb->get_var( "SELECT COUNT(*) FROM $group_table" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					while( ( $iterations < $all_groups ) && ( count( $group_ids ) !== $old_group_ids_count ) ) {
						$iterations++;
						$old_group_ids_count = count( $group_ids );
						$id_list = implode( ',', $group_ids );
						$parent_group_ids = $wpdb->get_results(
							"SELECT parent_id FROM $group_table WHERE parent_id IS NOT NULL AND group_id IN ($id_list)" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
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
					if ( count( $group_ids ) > 0 ) {
						$id_list = implode( ',', $group_ids );
						$rows = $wpdb->get_results(
							"SELECT DISTINCT capability_id FROM $group_capability_table WHERE group_id IN ($id_list)" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						);
						if ( $rows ) {
							foreach ( $rows as $row ) {
								$capability_ids[] = $row->capability_id;
							}
						}
					}
					$result = $capability_ids;
					break;
				case 'users' :
					$result = array();
					$user_group_table = _groups_get_tablename( 'user_group' );
					$users = $wpdb->get_results( $wpdb->prepare(
						"SELECT $wpdb->users.* FROM $wpdb->users LEFT JOIN $user_group_table ON $wpdb->users.ID = $user_group_table.user_id WHERE $user_group_table.group_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						Groups_Utility::id( $this->group->group_id )
					) );
					if ( $users ) {
						foreach( $users as $user ) {
							$groups_user = new Groups_User();
							$groups_user->set_user( new WP_User( $user ) );
							$result[] = $groups_user;
						}
					}
					break;
				case 'user_ids' :
					$result = array();
					$user_group_table = _groups_get_tablename( 'user_group' );
					$user_ids = $wpdb->get_results( $wpdb->prepare(
						"SELECT $wpdb->users.ID FROM $wpdb->users LEFT JOIN $user_group_table ON $wpdb->users.ID = $user_group_table.user_id WHERE $user_group_table.group_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						Groups_Utility::id( $this->group->group_id )
					) );
					if ( $user_ids ) {
						foreach( $user_ids as $user_id ) {
							$result[] = $user_id->ID;
						}
					}
					break;
			}
		}
		return $result;
	}

	/**
	 * @see I_Capable::can()
	 */
	public function can( $capability, $object = null, $args = null ) {

		global $wpdb;
		$result = false;

		if ( $this->group !== null ) {

			$group_table = _groups_get_tablename( 'group' );
			$capability_table = _groups_get_tablename( 'capability' );
			$group_capability_table = _groups_get_tablename( 'group_capability' );

			// determine capability id
			$capability_id = null;
			if ( is_numeric( $capability ) ) {
				$capability_id = Groups_Utility::id( $capability );
			} else if ( is_string( $capability ) ) {
				$capability_id = $wpdb->get_var( $wpdb->prepare(
					"SELECT capability_id FROM $capability_table WHERE capability = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$capability
				) );
			}

			if ( $capability_id !== null ) {
				// check if the group itself can
				$result = is_object( $this->group ) ? ( Groups_Group_Capability::read( $this->group->group_id, $capability_id ) !== false ) : null;
				if ( !$result ) {
					// find all parent groups and include in the group's
					// upward hierarchy to see if any of these can
					$group_ids = is_object( $this->group ) ? array( $this->group->group_id ) : array();
					$iterations = 0;
					$old_group_ids_count = 0;
					$all_groups = $wpdb->get_var( "SELECT COUNT(*) FROM $group_table" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					while( ( $iterations < $all_groups ) && ( count( $group_ids ) !== $old_group_ids_count ) ) {
						$iterations++;
						$old_group_ids_count = count( $group_ids );
						$id_list = implode( ',', $group_ids );
						$parent_group_ids = $wpdb->get_results(
							"SELECT parent_id FROM $group_table WHERE parent_id IS NOT NULL AND group_id IN ($id_list)" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
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
					if ( count( $group_ids ) > 0 ) {
						$id_list = implode( ',', $group_ids );
						$rows = $wpdb->get_results( $wpdb->prepare(
							"SELECT capability_id FROM $group_capability_table WHERE capability_id = %d AND group_id IN ($id_list)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
							Groups_Utility::id( $capability_id )
						) );

						if ( count( $rows ) > 0 ) {
							$result = true;
						}
					}
				}
			}
		}
		/**
		 * Filter whether the group has the capability.
		 *
		 * @since 3.0.0 $object
		 * @since 3.0.0 $args
		 *
		 * @param boolean $result
		 * @param Groups_Group $group
		 * @param string $capability
		 * @param mixed $object
		 * @param mixed $args
		 *
		 * @return boolean
		 */
		$result = apply_filters_ref_array( 'groups_group_can', array( $result, &$this, $capability, $object, $args ) );
		return $result;
	}

	/**
	 * Check if a group with the given ID exists.
	 *
	 * @since 2.18.0
	 *
	 * @param int $group_id
	 *
	 * @return boolean
	 */
	public static function exists( $group_id ) {
		$exists = false;
		if ( !empty( $group_id ) && is_numeric( $group_id ) ) {
			$group_id = Groups_Utility::id( $group_id );
			if ( $group_id !== false ) {
				$exists = self::read( $group_id ) !== false;
			}
		}
		return $exists;
	}

	/**
	 * Persist a group.
	 *
	 * Parameters:
	 * - name (required) - the group's name
	 * - creator_id (optional) - defaults to the current user's id
	 * - datetime (optional) - defaults to now
	 * - description (optional)
	 * - parent_id (optional)
	 *
	 * @param array $map attributes
	 *
	 * @return int group_id on success, otherwise false
	 */
	public static function create( $map ) {

		global $wpdb;

		$result = false;
		$error = false;

		$name = isset( $map['name'] ) ? $map['name'] : null;
		$creator_id = isset( $map['creator_id'] ) ? $map['creator_id'] : null;
		$datetime = isset( $map['datetime'] ) ? $map['datetime'] : null;
		$description = isset( $map['description'] ) ? $map['description'] : null;
		$parent_id = isset( $map['parent_id'] ) ? $map['parent_id'] : null;

		if ( !empty( $name ) ) {

			$group_table = _groups_get_tablename( 'group' );

			$data = array( 'name' => $name );
			$formats = array( '%s' );
			if ( $creator_id === null ) {
				$creator_id = get_current_user_id();
			}
			if ( $creator_id !== null ) {
				$data['creator_id'] = Groups_Utility::id( $creator_id );
				$formats[] = '%d';
			}
			if ( $datetime === null ) {
				$datetime = date( 'Y-m-d H:i:s', time() ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			}
			if ( !empty( $datetime ) ) {
				$data['datetime'] = $datetime;
				$formats[] = '%s';
			}
			if ( !empty( $description ) ) {
				$data['description'] = $description;
				$formats[] = '%s';
			}
			if ( !empty( $parent_id ) ) {
				$parent_id = Groups_Utility::id( $parent_id );
				if ( $parent_id !== false ) {
					// only allow to set an existing parent group (that is from the same blog)
					$parent_group_id = $wpdb->get_var(
						$wpdb->prepare(
							"SELECT group_id FROM $group_table WHERE group_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
							Groups_Utility::id( $parent_id )
						)
					);
					if ( $parent_group_id !== null ) {
						$parent_group_id = intval( $parent_group_id );
					}
					if (
						$parent_group_id !== null &&
						$parent_group_id === $parent_id
					) {
						$data['parent_id'] = $parent_id;
						$formats[] = '%d';
					} else {
						$error = true;
					}
				}
			}
			// no duplicate names
			$duplicate = Groups_Group::read_by_name( $name );
			if ( $duplicate ) {
				$error = true;
			}
			if ( !$error ) {
				if ( $wpdb->insert( $group_table, $data, $formats ) ) {
					if ( $result = $wpdb->get_var( "SELECT LAST_INSERT_ID()" ) ) {
						// must clear cache for this name in case it has been requested previously as it now exists
						Groups_Cache::delete( self::READ_BY_NAME . '_' . $name, self::CACHE_GROUP );
						do_action( 'groups_created_group', $result );
					}
				}
			}
		}
		return $result;
	}

	/**
	 * Retrieve a group.
	 *
	 * @param int $group_id group's id
	 *
	 * @return object upon success, otherwise false
	 */
	public static function read( $group_id ) {
		global $wpdb;
		$result = false;
		$cached = Groups_Cache::get( self::READ_GROUP_BY_ID . '_' . $group_id, self::CACHE_GROUP );
		if ( $cached !== null ) {
			$result = $cached->value;
			unset( $cached );
		} else {
			$group_table = _groups_get_tablename( 'group' );
			$group = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM $group_table WHERE group_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				Groups_Utility::id( $group_id )
			) );
			if ( isset( $group->group_id ) ) {
				$result = $group;
			}
			Groups_Cache::set( self::READ_GROUP_BY_ID . '_' . $group_id, $result, self::CACHE_GROUP );
		}
		return $result;
	}

	/**
	 * Retrieve a group by name.
	 *
	 * @param string $name the group's name
	 *
	 * @return object upon success, otherwise false
	 */
	public static function read_by_name( $name ) {
		global $wpdb;
		$cached = Groups_Cache::get( self::READ_BY_NAME . '_' . $name, self::CACHE_GROUP );
		if ( $cached !== null ) {
			$result = $cached->value;
			unset( $cached );
		} else {
			$result = false;
			$group_table = _groups_get_tablename( 'group' );
			$query = $wpdb->prepare(
				"SELECT * FROM $group_table WHERE name = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$name
			);
			$group = $wpdb->get_row( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( isset( $group->group_id ) ) {
				$result = $group;
			}
			Groups_Cache::set( self::READ_BY_NAME . '_' . $name, $result, self::CACHE_GROUP );
		}
		return $result;
	}

	/**
	 * Update group.
	 *
	 * @param array $map group attribute, must contain group_id
	 *
	 * @return int group_id on success, otherwise false
	 */
	public static function update( $map ) {

		global $wpdb;

		$result = false;

		$group_id = isset( $map['group_id'] ) ? $map['group_id'] : null;
		$name = isset( $map['name'] ) ? $map['name'] : null;
		$description = isset( $map['description'] ) ? $map['description'] : null;
		$parent_id = isset( $map['parent_id'] ) ? $map['parent_id'] : null;

		if ( isset( $group_id ) && !empty( $name ) ) {
			$old_group = Groups_Group::read( $group_id );
			$group_table = _groups_get_tablename( 'group' );
			if ( !isset( $description ) || ( $description === null ) ) {
				$description = '';
			}
			$wpdb->query( $wpdb->prepare(
				"UPDATE $group_table SET name = %s, description = %s WHERE group_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$name,
				$description,
				Groups_Utility::id( $group_id )
			) );
			if ( empty( $parent_id ) ) {
				$wpdb->query( $wpdb->prepare(
					"UPDATE $group_table SET parent_id = NULL WHERE group_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					Groups_Utility::id( $group_id )
				) );
			} else {
				// Prohibit circular dependencies:
				// This group cannot have a parent that is its successor
				// at any level in its successor hierarchy.
				// S(g)  : successor of group g
				// S*(g) : successors of group g, any level deep
				// P(g)  : parent of g
				// ---
				// It must hold: !( P(g) in S*(g) )

				// Find all successors of this group
				$groups = $wpdb->get_var( "SELECT COUNT(*) FROM $group_table" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				if ( $groups !== null ) {
					$group_ids   = array();
					$group_ids[] = Groups_Utility::id( $group_id );
					$iterations  = 0;
					$old_group_ids_count = 0;
					while( ( $iterations < $groups ) && ( count( $group_ids ) > 0 ) && ( count( $group_ids ) !== $old_group_ids_count ) ) {

						$iterations++;
						$old_group_ids_count = count( $group_ids );

						$id_list = implode( ',', $group_ids );
						// We can trust ourselves here, no need to use prepare()
						// but careful if this query is modified!
						$successor_group_ids = $wpdb->get_results(
							"SELECT group_id FROM $group_table WHERE parent_id IS NOT NULL AND parent_id IN ($id_list)" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						);
						if ( $successor_group_ids ) {
							foreach( $successor_group_ids as $successor_group_id ) {
								$successor_group_id = Groups_Utility::id( $successor_group_id->group_id );
								if ( !in_array( $successor_group_id, $group_ids ) ) {
									$group_ids[] = $successor_group_id;
								}
							}
						}
					}
					// only add if condition holds
					if ( !in_array( Groups_Utility::id( $parent_id ), $group_ids ) ) {
						$wpdb->query( $wpdb->prepare(
							"UPDATE $group_table SET parent_id = %d WHERE group_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
							Groups_Utility::id( $parent_id),
							Groups_Utility::id( $group_id )
						) );
					}
				}
			}
			$result = $group_id;
			if ( !empty( $group_id ) ) {
				Groups_Cache::delete( self::READ_GROUP_BY_ID . '_' . $group_id, self::CACHE_GROUP );
			}
			if ( !empty( $name ) ) { // @phpstan-ignore empty.variable
				Groups_Cache::delete( self::READ_BY_NAME . '_' . $name, self::CACHE_GROUP );
			}
			if ( !empty( $old_group ) && !empty( $old_group->name ) ) { // @phpstan-ignore empty.variable
				Groups_Cache::delete( self::READ_BY_NAME . '_' . $old_group->name, self::CACHE_GROUP );
			}
			do_action( 'groups_updated_group', $result );
		}
		return $result;
	}

	/**
	 * Remove group and its relations.
	 *
	 * @param int $group_id
	 *
	 * @return int group_id if successful, false otherwise
	 */
	public static function delete( $group_id ) {

		global $wpdb;
		$result = false;

		if ( $group = self::read( $group_id ) ) {

			// delete group-capabilities
			$group_capability_table = _groups_get_tablename( 'group_capability' );
			$wpdb->query( $wpdb->prepare(
				"DELETE FROM $group_capability_table WHERE group_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				Groups_Utility::id( $group->group_id )
			) );

			// delete group-users
			$user_group_table = _groups_get_tablename( 'user_group' );
			$wpdb->query( $wpdb->prepare(
				"DELETE FROM $user_group_table WHERE group_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$group->group_id
			) );

			// set parent_id to null where this group is parent
			$group_table = _groups_get_tablename( 'group' );
			$wpdb->query( $wpdb->prepare(
				"UPDATE $group_table SET parent_id = NULL WHERE parent_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$group->group_id
			) );

			// delete group
			if ( $wpdb->query( $wpdb->prepare(
				"DELETE FROM $group_table WHERE group_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$group->group_id
			) ) ) {
				$result = $group->group_id;
				if ( !empty( $group->group_id ) ) {
					Groups_Cache::delete( self::READ_GROUP_BY_ID . '_' . $group_id, self::CACHE_GROUP );
				}
				if ( !empty( $group->name ) ) {
					Groups_Cache::delete( self::READ_BY_NAME . '_' . $group->name, self::CACHE_GROUP );
				}
				do_action( 'groups_deleted_group', $result );
			}
		}
		return $result;
	}

	/**
	 * Returns an array of group IDs.
	 *
	 * If no arguments are passed, IDs for all existing groups are returned.
	 *
	 * @param array $args
	 * - ['order_by'] string a Groups_Group property
	 * - ['order'] string ASC or DESC. Only applied if 'order_by' is set.
	 * - ['parent_id'] int retrieve groups whose parent is indicated by this ID
	 * - ['include'] array|string with one or more IDs of groups to include, separated by comma
	 * - ['include_by_name'] array|string with one ore more group names of groups to include, separated by comma
	 * - ['exclude'] array|string with one or more IDs of groups to exclude, separated by comma
	 * - ['exclude_by_name'] array|string with one ore more group names of groups to exclude, separated by comma
	 *
	 * @return array of int with group IDs
	 *
	 * @since groups 1.4.9
	 */
	public static function get_group_ids( $args = array() ) {
		$result = array();
		$args['fields'] = 'group_id';
		$groups = self::get_groups( $args );
		if ( sizeof( $groups ) > 0 ) {
			foreach ( $groups as $group ) {
				$result[] = $group->group_id;
			}
		}
		return $result;
	}

	/**
	 * Returns an array of database results by querying the group table.
	 *
	 * @param Array $args
	 * - ['fields'] string with fields to get separated by comma. If empty then get all fields.
	 * - ['order_by'] string a Groups_Group property
	 * - ['order'] string ASC or DESC. Only applied if 'order_by' is set.
	 * - ['parent_id'] int retrieve groups whose parent is indicated by this ID
	 * - ['include'] array|string with one or more IDs of groups to include, separated by comma
	 * - ['include_by_name'] array|string with one ore more group names of groups to include, separated by comma
	 * - ['exclude'] array|string with one or more IDs of groups to exclude, separated by comma
	 * - ['exclude_by_name'] array|string with one ore more group names of groups to exclude, separated by comma
	 *
	 * @return array of object with query rows
	 *
	 * @since groups 1.4.9
	 */
	public static function get_groups( $args = array() ) {
		global $wpdb;

		$fields = isset( $args['fields'] ) ? $args['fields'] : null;
		$order = isset( $args['order'] ) ? $args['order'] : null;
		$order_by = isset( $args['order_by'] ) ? $args['order_by'] : null;
		$parent_id = isset( $args['parent_id'] ) ? $args['parent_id'] : null;
		$include = isset( $args['include'] ) ? $args['include'] : null;
		$include_by_name = isset( $args['include_by_name'] ) ? $args['include_by_name'] : null;
		$exclude = isset( $args['exclude'] ) ? $args['exclude'] : null;
		$exclude_by_name = isset( $args['exclude_by_name'] ) ? $args['exclude_by_name'] : null;

		if ( !isset( $fields ) ) {
			$fields = '*';
		} else {
			$array_fields = explode( ',', sanitize_text_field( $fields ) );
			$fields = '';
			foreach ( $array_fields as $field ) {
				switch( trim( $field ) ) {
					case 'group_id' :
					case 'parent_id' :
					case 'creator_id' :
					case 'datetime' :
					case 'name' :
					case 'description' :
						$fields .= ',' . trim( $field );
						break;
				}
			}
			if ( strlen( $fields ) > 0 ) {
				$fields = substr( $fields, 1 );
			}
		}

		if ( !isset( $order ) ) {
			$order = '';
		} else {
			$order = strtoupper( sanitize_text_field( trim( $order ) ) );
			switch( $order ) {
				case 'ASC' :
				case 'DESC' :
					break;
				default :
					$order = 'ASC';
			}
		}

		if ( !isset( $order_by ) ) {
			$order_by = '';
		} else {
			$order_by = sanitize_text_field( $order_by );
			switch( trim( $order_by ) ) {
				case 'group_id' :
				case 'parent_id' :
				case 'creator_id' :
				case 'datetime' :
				case 'name' :
				case 'description' :
					$order_by = " ORDER BY $order_by $order "; // Watch out! This is unescaped but safe within this switch.
					break;
				default :
					$order_by = '';
					break;
			}
		}

		$where = '';
		if ( isset( $parent_id ) ) {
			$parent_id = sanitize_text_field( $parent_id );
			if ( is_numeric ( $parent_id ) ) {
				$where .= $wpdb->prepare( " WHERE parent_id=%s ", array( $parent_id ) );
			}
		}

		//
		// include by group ID
		//
		$where_include = '';
		$include       = !empty( $include ) ? $include : null;
		if ( !empty( $include ) && !is_array( $include ) && is_string( $include ) ) {
			$include = explode( ',', $include );
		}
		if ( $include !== null && count( $include ) > 0 ) {
			$include = implode( ',', array_map( 'intval', array_map( 'trim', $include ) ) );
			if ( strlen( $include ) > 0 ) {
				$where_include = " group_id IN ($include) ";
			}
		}

		//
		// include by group name
		//
		$where_include_by_name = '';
		$include_by_name       = !empty( $include_by_name ) ? $include_by_name : null;
		if ( !empty( $include_by_name ) && !is_array( $include_by_name ) && is_string( $include_by_name ) ) {
			$include_by_name = explode( ',', $include_by_name );
		}
		if ( $include_by_name !== null && count( $include_by_name ) > 0 ) {
			$include_by_name = "'" . implode( "','", array_map( 'esc_sql', array_map( 'trim', $include_by_name ) ) ) . "'";
			if ( strlen( $include_by_name ) > 0 ) {
				$where_include_by_name = " name IN ($include_by_name) ";
			}
		}

		// adding includes ...
		if ( ( $where_include !== '' ) || ( $where_include_by_name !== '' ) ) {
			if ( $where == '' ) {
				$where .= " WHERE ";
			} else {
				$where .= " AND ";
			}
		}
		if ( ( $where_include !== "" ) && ( $where_include_by_name !== "" ) ) {
			$where .= "(";
		}
		if ( $where_include !== "" ) {
			$where .= $where_include;
		}
		if ( ( $where_include !== "" ) && ( $where_include_by_name !== "" ) ) {
			$where .= " OR ";
		}
		if ( $where_include_by_name !== "" ) {
			$where .= $where_include_by_name;
		}
		if ( ( $where_include !== "" ) && ( $where_include_by_name !== "" ) ) {
			$where .= ")";
		}

		//
		// exclude
		//
		$exclude = !empty( $exclude ) ? $exclude : null;
		if ( !empty( $exclude ) && !is_array( $exclude ) && is_string( $exclude ) ) {
			$exclude = explode( ',', $exclude );
		}
		if ( $exclude !== null && count( $exclude ) > 0 ) {
			$exclude = implode( ',', array_map( 'intval', array_map( 'trim', $exclude ) ) );
			if ( strlen( $exclude ) > 0 ) {
				if ( empty( $where ) ) {
					$where = " WHERE group_id NOT IN ($exclude) ";
				} else {
					$where .= " AND group_id NOT IN ($exclude) ";
				}
			}
		}

		//
		// exclude by group name
		//
		$exclude_by_name = !empty( $exclude_by_name ) ? $exclude_by_name : null;
		if ( !empty( $exclude_by_name ) && !is_array( $exclude_by_name ) && is_string( $exclude_by_name ) ) {
			$exclude_by_name = explode( ',', $exclude_by_name );
		}
		if ( $exclude_by_name !== null && count( $exclude_by_name ) > 0 ) {
			$exclude_by_name = "'" . implode( "','", array_map( 'esc_sql', array_map( 'trim', $exclude_by_name ) ) ) . "'";
			if ( strlen( $exclude_by_name ) > 0 ) {
				if ( empty( $where ) ) {
					$where = " WHERE name NOT IN ($exclude_by_name) ";
				} else {
					$where .= " AND name NOT IN ($exclude_by_name) ";
				}
			}
		}

		$groups_table = _groups_get_tablename( 'group' );
		$groups = $wpdb->get_results( "SELECT $fields FROM $groups_table $where $order_by" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $groups;
	}
}
