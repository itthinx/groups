<?php
/**
* class-groups-capability.php
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
 * Capability OPM
 */
class Groups_Capability {

	/**
	 * @var string cache group
	 */
	const CACHE_GROUP = 'groups';

	/**
	 * @var string key
	 */
	const READ_BY_CAPABILITY = 'read_by_capability';

	/**
	 * @var string key
	 */
	const READ_CAPABILITY_BY_ID = 'read_capability_by_id';

	/**
	 * @var object persisted capability object
	 *
	 * @access private - do not access this property directly, the visibility will be made private in the future
	 */
	public $capability = null;

	/**
	 * Create by capability id.
	 *
	 * Must have been persisted.
	 *
	 * @param int $capability_id
	 */
	public function __construct( $capability_id ) {
		$this->capability = self::read( $capability_id );
	}

	/**
	 * Provides the object ID.
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->get_capability_id();
	}

	/**
	 * Provides the object ID.
	 *
	 * @return int
	 */
	public function get_capability_id() {
		return $this->capability_id; // @phpstan-ignore property.notFound
	}

	/**
	 * Provides the literal capability.
	 *
	 * @return string
	 */
	public function get_capability() {
		$capability = '';
		if (
			$this->capability !== null &&
			is_object( $this->capability ) &&
			!empty( $this->capability->capability )
		) {
			$capability = $this->capability->capability;
		}
		return $capability;
	}

	/**
	 * Provides the capability's class.
	 *
	 * @return string
	 */
	public function get_class() {
		return $this->class; // @phpstan-ignore property.notFound
	}

	/**
	 * Provides the capability's object.
	 *
	 * @return string
	 */
	public function get_object() {
		return $this->object; // @phpstan-ignore property.notFound
	}

	/**
	 * Provides the capability's name.
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name; // @phpstan-ignore property.notFound
	}

	/**
	 * Provides the capability's description.
	 *
	 * @return string
	 */
	public function get_description() {
		return $this->description; // @phpstan-ignore property.notFound
	}

	/**
	 * Provides the IDs of groups that have the capability.
	 *
	 * @return int[]
	 */
	public function get_group_ids(){
		return $this->group_ids; // @phpstan-ignore property.notFound
	}

	/**
	 * Provides the groups that have the capability.
	 *
	 * @return Groups_Group[]
	 */
	public function get_groups() {
		return $this->groups; // @phpstan-ignore property.notFound
	}

	/**
	 * Retrieve a property by name.
	 *
	 * Possible properties:
	 * - capability_id
	 * - capability
	 * - class
	 * - object
	 * - name
	 * - description
	 *
	 * - group_ids groups that have the capability
	 *
	 * @param string $name property's name
	 *
	 * @return mixed property value, will return null if property does not exist
	 */
	public function __get( $name ) {

		global $wpdb;

		$result = null;
		if ( $this->capability !== null ) {
			switch( $name ) {
				case 'capability_id' :
				case 'capability' :
				case 'class' :
				case 'object' :
				case 'name' :
				case 'description' :
					$result = $this->capability->$name;
					break;
				case 'group_ids' :
					$group_capability_table = _groups_get_tablename( 'group_capability' );
					$rows = $wpdb->get_results( $wpdb->prepare(
						"SELECT group_id FROM $group_capability_table WHERE capability_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						Groups_Utility::id( $this->capability->capability_id )
					) );
					if ( $rows ) {
						$result = array();
						foreach( $rows as $row ) {
							$result[] = $row->group_id;
						}
					}
					break;
				case 'groups' :
					$group_capability_table = _groups_get_tablename( 'group_capability' );
					$rows = $wpdb->get_results( $wpdb->prepare(
						"SELECT group_id FROM $group_capability_table WHERE capability_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						Groups_Utility::id( $this->capability->capability_id )
					) );
					if ( $rows ) {
						$result = array();
						foreach( $rows as $row ) {
							$result[] = new Groups_Group( $row->group_id );
						}
					}
					break;
			}
		}
		return $result;
	}

	/**
	 * Persist a capability.
	 *
	 * Possible keys in $map:
	 *
	 * - "capability" (required) - unique capability label, max 20 characters
	 * - "class" (optional) - class the capability applies to, max 100 chars
	 * - "object" (optional) - identifies object of that class, max 100 chars
	 * - "name" (optional) - name it if you have to
	 * - "description" (optional) - dito
	 *
	 * @param array $map attributes, requires at least: "capability"
	 *
	 * @return int capability_id on success, otherwise false
	 */
	public static function create( $map ) {

		global $wpdb;

		$result = false;

		$capability = isset( $map['capability'] ) ? $map['capability'] : null;
		$class = isset( $map['class'] ) ? $map['class'] : null;
		$object = isset( $map['object'] ) ? $map['object'] : null;
		$name = isset( $map['name'] ) ? $map['name'] : null;
		$description = isset( $map['description'] ) ? $map['description'] : null;

		if ( !empty( $capability ) ) {
			if ( self::read_by_capability( $capability ) === false ) {
				$data = array(
					'capability' => $capability
				);
				$formats = array( '%s' );
				if ( !empty( $class ) ) {
					$data['class'] = $class;
					$formats[] = '%s';
				}
				if ( !empty( $object ) ) {
					$data['object'] = $object;
					$formats[] = '%s';
				}
				if ( !empty( $name ) ) {
					$data['name'] = $name;
					$formats[] = '%s';
				}
				if ( !empty( $description ) ) {
					$data['description'] = $description;
					$formats[] = '%s';
				}
				$capability_table = _groups_get_tablename( 'capability' );
				if ( $wpdb->insert( $capability_table, $data, $formats ) ) {
					if ( $result = $wpdb->get_var( "SELECT LAST_INSERT_ID()" ) ) {
						// read_by_capability above created a cache entry which needs to be reset
						Groups_Cache::delete( self::READ_BY_CAPABILITY . '_' . $capability, self::CACHE_GROUP );
						do_action( 'groups_created_capability', $result );
					}
				}
			}
		}
		return $result;
	}

	/**
	 * Retrieve a capability.
	 *
	 * Use Groups_Capability::read_capability() if you are trying to retrieve a capability by its unique label.
	 *
	 * @see Groups_Capability::read_by_capability()
	 * @param int $capability_id capability's id
	 *
	 * @return object upon success, otherwise false
	 */
	public static function read( $capability_id ) {
		global $wpdb;
		$result = false;
		$cached = Groups_Cache::get( self::READ_CAPABILITY_BY_ID . '_' . $capability_id, self::CACHE_GROUP );
		if ( $cached !== null ) {
			$result = $cached->value;
			unset( $cached );
		} else {
			$capability_table = _groups_get_tablename( 'capability' );
			$capability = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM $capability_table WHERE capability_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				Groups_Utility::id( $capability_id )
			) );
			if ( isset( $capability->capability_id ) ) {
				$result = $capability;
			}
			Groups_Cache::set( self::READ_CAPABILITY_BY_ID . '_' . $capability_id, $result, self::CACHE_GROUP );
		}
		return $result;
	}

	/**
	 * Retrieve a capability by its unique label.
	 *
	 * @param string $capability capability's unique label
	 *
	 * @return object upon success, otherwise false
	 */
	public static function read_by_capability( $capability ) {
		global $wpdb;
		$_capability = $capability;
		$cached = Groups_Cache::get( self::READ_BY_CAPABILITY . '_' . $_capability, self::CACHE_GROUP );
		if ( $cached !== null ) {
			$result = $cached->value;
			unset( $cached );
		} else {
			$result = false;
			$capability_table = _groups_get_tablename( 'capability' );
			$capability = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM $capability_table WHERE capability = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$capability
			) );
			if ( isset( $capability->capability_id ) ) {
				$result = $capability;
			}
			Groups_Cache::set( self::READ_BY_CAPABILITY . '_' . $_capability, $result, self::CACHE_GROUP );
		}
		return $result;
	}

	/**
	 * Update capability.
	 *
	 * @param array $map capability attribute, must contain capability_id
	 *
	 * @return int capability_id on success, otherwise false
	 */
	public static function update( $map ) {

		global $wpdb;

		$result = false;

		$capability_id = isset( $map['capability_id'] ) ? $map['capability_id'] : null;
		$capability = isset( $map['capability'] ) ? $map['capability'] : null;
		$class = isset( $map['class'] ) ? $map['class'] : null;
		$object = isset( $map['object'] ) ? $map['object'] : null;
		$name = isset( $map['name'] ) ? $map['name'] : null;
		$description = isset( $map['description'] ) ? $map['description'] : null;

		if ( $capability_id !== null ) {
			$capability_table = _groups_get_tablename( 'capability' );
			$old_capability = Groups_Capability::read( $capability_id );
			if ( $old_capability ) {
				if ( $capability !== null ) {
					$old_capability_capability = $old_capability->capability;
					$old_capability->capability = $capability;
				}
				if ( $class !== null ) {
					$old_capability->class = $class;
				}
				if ( $object !== null ) {
					$old_capability->object = $object;
				}
				if ( $name !== null ) {
					$old_capability->name = $name;
				}
				if ( $description !== null ) {
					$old_capability->description = $description;
				}
				$rows = $wpdb->query( $wpdb->prepare(
					"UPDATE $capability_table SET capability = %s, class = %s, object = %s, name = %s, description = %s WHERE capability_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$old_capability->capability,
					$old_capability->class,
					$old_capability->object,
					$old_capability->name,
					$old_capability->description,
					Groups_Utility::id( $capability_id )
				) );
				if ( ( $rows !== false ) ) {
					$result = $capability_id;
					if ( !empty( $old_capability ) && !empty( $old_capability->capability ) ) { // @phpstan-ignore empty.variable
						Groups_Cache::delete( self::READ_BY_CAPABILITY . '_' . $old_capability->capability, self::CACHE_GROUP );
					}
					if ( !empty( $old_capability_capability ) ) {
						Groups_Cache::delete( self::READ_BY_CAPABILITY . '_' . $old_capability_capability, self::CACHE_GROUP );
					}
					do_action( 'groups_updated_capability', $result );
				}
			}
		}
		return $result;
	}

	/**
	 * Remove capability and its relations.
	 *
	 * @param int $capability_id
	 *
	 * @return int capability_id if successful, false otherwise
	 */
	public static function delete( $capability_id ) {

		global $wpdb;
		$result = false;

		// avoid nonsense requests
		if ( $capability = Groups_Capability::read( $capability_id ) ) {
			$capability_table = _groups_get_tablename( 'capability' );
			// get rid of it
			if ( $rows = $wpdb->query( $wpdb->prepare(
				"DELETE FROM $capability_table WHERE capability_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				Groups_Utility::id( $capability_id )
			) ) ) {
				$result = $capability_id;
				if ( !empty( $capability->capability ) ) {
					Groups_Cache::delete( self::READ_BY_CAPABILITY . '_' . $capability->capability, self::CACHE_GROUP );
					do_action( 'groups_deleted_capability_capability', $capability->capability );
				}
				do_action( 'groups_deleted_capability', $result );
			}
		}
		return $result;
	}
}
