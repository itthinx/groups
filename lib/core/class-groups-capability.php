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

	const CACHE_GROUP        = 'groups';
	const READ_BY_CAPABILITY = 'read_by_capability';

	/**
	 * @var persisted capability object
	 */
	var $capability = null;
	
	/**
	 * Create by capability id.
	 * Must have been persisted.
	 * @param int $capability_id
	 */
	public function __construct( $capability_id ) {
		$this->capability = self::read( $capability_id );
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
	 * @return property value, will return null if property does not exist
	 */
	public function __get( $name ) {

		global $wpdb;

		$result = null;
		if ( $this->capability !== null ) {
			switch( $name ) {
				case "capability_id" :
				case "capability" :
				case "class" :
				case "object" :
				case "name" :
				case "description" :
					$result = $this->capability->$name;
					break;
				case 'group_ids' :
					$group_capability_table = _groups_get_tablename( "group_capability" );
					$rows = $wpdb->get_results( $wpdb->prepare(
						"SELECT group_id FROM $group_capability_table WHERE capability_id = %d",
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
					$group_capability_table = _groups_get_tablename( "group_capability" );
					$rows = $wpdb->get_results( $wpdb->prepare(
						"SELECT group_id FROM $group_capability_table WHERE capability_id = %d",
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
	 * @return capability_id on success, otherwise false
	 */
	public static function create( $map ) {
		
		global $wpdb;
		extract( $map );
		$result = false;
		
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
						wp_cache_delete( self::READ_BY_CAPABILITY . '_' . $capability, self::CACHE_GROUP );
						do_action( "groups_created_capability", $result );
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
	 * @return object upon success, otherwise false
	 */
	public static function read( $capability_id ) {
		global $wpdb;
		$result = false;
		$capability_table = _groups_get_tablename( 'capability' );
		$capability = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $capability_table WHERE capability_id = %d",
			Groups_Utility::id( $capability_id )
		) );
		if ( isset( $capability->capability_id ) ) {
			$result = $capability;
		}
		return $result;
	}

	/**
	 * Retrieve a capability by its unique label.
	 * 
	 * @param string $capability capability's unique label
	 * @return object upon success, otherwise false
	 */
	public static function read_by_capability( $capability ) {
		global $wpdb;
		$_capability = $capability;
		$found = false;
		$result = wp_cache_get( self::READ_BY_CAPABILITY . '_' . $_capability, self::CACHE_GROUP, false, $found );
		if ( $found === false ) {
			$result = false;
			$capability_table = _groups_get_tablename( 'capability' );
			$capability = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM $capability_table WHERE capability = %s",
				$capability
			) );
			if ( isset( $capability->capability_id ) ) {
				$result = $capability;
			}
			wp_cache_set( self::READ_BY_CAPABILITY . '_' . $_capability, $result, self::CACHE_GROUP );
		}
		return $result;
	}

	/**
	 * Update capability.
	 * 
	 * @param array $map capability attribute, must contain capability_id
	 * @return capability_id on success, otherwise false
	 */
	public static function update( $map ) {
		
		global $wpdb;
		extract( $map );
		$result = false;
		
		if ( isset( $capability_id ) && !empty( $capability ) ) {
			$capability_table = _groups_get_tablename( 'capability' );
			$old_capability = Groups_Capability::read( $capability_id );
			if ( $old_capability ) {
				if ( isset( $capability ) ) {
					$old_capability_capability = $old_capability->capability;
					$old_capability->capability = $capability;
				}
				if ( isset( $class ) ) {
					$old_capability->class = $class;
				}
				if ( isset( $object ) ) {
					$old_capability->object = $object;
				}
				if ( isset( $name ) ) {
					$old_name = $old_capability->name;
					$old_capability->name = $name;
				}
				if ( isset( $description ) ) {
					$old_capability->description = $description;
				}
				$rows = $wpdb->query( $wpdb->prepare(
					"UPDATE $capability_table SET capability = %s, class = %s, object = %s, name = %s, description = %s WHERE capability_id = %d",
					$old_capability->capability,
					$old_capability->class,
					$old_capability->object,
					$old_capability->name,
					$old_capability->description,
					Groups_Utility::id( $capability_id )
				) );
				if ( ( $rows !== false ) ) {
					$result = $capability_id;
					if ( !empty( $old_capability ) && !empty( $old_capability->capability ) ) {
						wp_cache_delete( self::READ_BY_CAPABILITY . '_' . $old_capability->capability, self::CACHE_GROUP );
					}
					if ( !empty( $old_capability_capability ) ) {
						wp_cache_delete( self::READ_BY_CAPABILITY . '_' . $old_capability_capability, self::CACHE_GROUP );
					}
					do_action( "groups_updated_capability", $result );
				}
			}
		}
		return $result;
	}
	
	/**
	 * Remove capability and its relations.
	 * 
	 * @param int $capability_id
	 * @return capability_id if successful, false otherwise
	 */
	public static function delete( $capability_id ) {

		global $wpdb;
		$result = false;
		
		// avoid nonsense requests
		if ( $capability = Groups_Capability::read( $capability_id ) ) {
			$capability_table = _groups_get_tablename( 'capability' );
			// get rid of it
			if ( $rows = $wpdb->query( $wpdb->prepare(
				"DELETE FROM $capability_table WHERE capability_id = %d",
				Groups_Utility::id( $capability_id )
			) ) ) {
				$result = $capability_id;
				if ( !empty( $capability->capability ) ) {
					wp_cache_delete( self::READ_BY_CAPABILITY . '_' . $capability->capability, self::CACHE_GROUP );
					do_action( 'groups_deleted_capability_capability', $capability->capability );
				}
				do_action( "groups_deleted_capability", $result );
			}
		}
		return $result;
	}
}