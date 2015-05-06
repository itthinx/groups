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

require_once( GROUPS_CORE_LIB . "/interface-i-capable.php" );

/**
 * Group OPM.
 */
class Groups_Group implements I_Capable {

	const CACHE_GROUP  = 'groups';
	const READ_BY_NAME = 'read_by_name';

	/**
	 * @var Object Persisted group.
	 */
	var $group = null;
		
	/**
	 * Create by group id.
	 * Must have been persisted.
	 * @param int $group_id
	 */
	public function __construct( $group_id ) {
		$this->group = self::read( $group_id );
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
	 * @return property value, will return null if property does not exist
	 */
	public function __get( $name ) {
		global $wpdb;
		$result = null;
		if ( $this->group !== null ) {
			switch( $name ) {
				case "group_id" :
				case "parent_id" :
				case "creator_id" :
				case "datetime" :
				case "name" :
				case "description" :
					$result = $this->group->$name;
					break;
				case "capabilities" :
					$group_capability_table = _groups_get_tablename( "group_capability" );
					$rows = $wpdb->get_results( $wpdb->prepare(
						"SELECT capability_id FROM $group_capability_table WHERE group_id = %d",
						Groups_Utility::id( $this->group->group_id )
					) );
					if ( $rows ) {
						$result = array();
						foreach ( $rows as $row ) {
							$result[] = new Groups_Capability( $row->capability_id );
						}
					}
					break;
				case 'capabilities_deep' :
					$capability_ids = $this->capability_ids_deep;
					$result = array();
					foreach( $capability_ids as $capability_id ) {
						$result[] = new Groups_Capability( $capability_id );
					}
					break;
				case 'capability_ids_deep' :
					$capability_ids = array();
					$group_table = _groups_get_tablename( "group" );
					$group_capability_table = _groups_get_tablename( "group_capability" );
					// Find this group's and all its parent groups' capabilities.
					$group_ids  = array( Groups_Utility::id( $this->group->group_id ) );
					$iterations = 0;
					$old_group_ids_count = 0;
					$all_groups = $wpdb->get_var( "SELECT COUNT(*) FROM $group_table" );
					while( ( $iterations < $all_groups ) && ( count( $group_ids ) !== $old_group_ids_count ) ) {
						$iterations++;
						$old_group_ids_count = count( $group_ids );
						$id_list = implode( ",", $group_ids );
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
					if ( count( $group_ids ) > 0 ) {
						$id_list = implode( ",", $group_ids );
						$rows = $wpdb->get_results(
							"SELECT DISTINCT capability_id FROM $group_capability_table WHERE group_id IN ($id_list)"
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
					$user_group_table = _groups_get_tablename( "user_group" );
					$users = $wpdb->get_results( $wpdb->prepare(
						"SELECT ID FROM $wpdb->users LEFT JOIN $user_group_table ON $wpdb->users.ID = $user_group_table.user_id WHERE $user_group_table.group_id = %d",
						Groups_Utility::id( $this->group->group_id )
					) );
					if ( $users ) {
						$result = array();
						foreach( $users as $user ) {
							$result[] = new Groups_User( $user->ID );
						}
					}
					break;
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
		
		if ( $this->group !== null ) {
			
			$group_table = _groups_get_tablename( "group" );
			$capability_table = _groups_get_tablename( "capability" );
			$group_capability_table = _groups_get_tablename( "group_capability" );
			
			// determine capability id 
			$capability_id = null;
			if ( is_numeric( $capability ) ) {
				$capability_id = Groups_Utility::id( $capability );
			} else if ( is_string( $capability ) ) {
				$capability_id = $wpdb->get_var( $wpdb->prepare(
					"SELECT capability_id FROM $capability_table WHERE capability = %s",
					$capability
				) );
			}
			
			if ( $capability_id !== null ) {
				// check if the group itself can
				$result = ( Groups_Group_Capability::read( $this->group->group_id, $capability_id ) !== false );
				if ( !$result ) {
					// find all parent groups and include in the group's
					// upward hierarchy to see if any of these can
					$group_ids		   = array( $this->group->group_id );
					$iterations		  = 0;
					$old_group_ids_count = 0;
					$all_groups = $wpdb->get_var( "SELECT COUNT(*) FROM $group_table" );
					while( ( $iterations < $all_groups ) && ( count( $group_ids ) !== $old_group_ids_count ) ) {
						$iterations++;
						$old_group_ids_count = count( $group_ids );
						$id_list = implode( ",", $group_ids );
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
					if ( count( $group_ids ) > 0 ) {
						$id_list = implode( ",", $group_ids );
						$rows = $wpdb->get_results( $wpdb->prepare(
							"SELECT capability_id FROM $group_capability_table WHERE capability_id = %d AND group_id IN ($id_list)",
							Groups_Utility::id( $capability_id )
						) );
						
						if ( count( $rows ) > 0 ) {
							$result = true;
						}
					}
				}
			}
		}
		$result = apply_filters_ref_array( "groups_group_can", array( $result, &$this, $capability ) );
		return $result;
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
	 * @return group_id on success, otherwise false
	 */
	public static function create( $map ) {
		global $wpdb;
		extract( $map );
		$result = false;
		$error = false;
		
		if ( !empty( $name ) ) {
			
			$group_table = _groups_get_tablename( "group" );
			
			$data = array( 'name' => $name );
			$formats = array( '%s' );
			if ( !isset( $creator_id ) ) {
				$creator_id = get_current_user_id();
			}
			if ( isset( $creator_id ) ) {
				$data['creator_id'] = Groups_Utility::id( $creator_id );
				$formats[] = '%d';
			}
			if ( !isset( $datetime ) ) {
				$datetime = date( 'Y-m-d H:i:s', time() );
			}
			if ( isset( $datetime ) ) {
				$data['datetime'] = $datetime;
				$formats[] = '%s';
			}
			if ( !empty( $description ) ) {
				$data['description'] = $description;
				$formats[] = '%s';
			}
			if ( !empty( $parent_id ) ) {
				// only allow to set an existing parent group (that is from the same blog)
				$parent_group_id = $wpdb->get_var( $wpdb->prepare(
					"SELECT group_id FROM $group_table WHERE group_id = %d",
					Groups_Utility::id( $parent_id )
				) );
				if ( $parent_group_id === $parent_id ) {
					$data['parent_id'] = Groups_Utility::id( $parent_id );
					$formats[] = '%d';
				} else {
					$error = true;
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
						wp_cache_delete( self::READ_BY_NAME . '_' . $name, self::CACHE_GROUP );
						do_action( "groups_created_group", $result );
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
	 * @return object upon success, otherwise false
	 */
	public static function read( $group_id ) {
		global $wpdb;
		$result = false;
		
		$group_table = _groups_get_tablename( 'group' );
		$group = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $group_table WHERE group_id = %d",
			Groups_Utility::id( $group_id )
		) );
		if ( isset( $group->group_id ) ) {
			$result = $group;
		}
		return $result;
	}
	
	/**
	 * Retrieve a group by name.
	 *
	 * @param string $name the group's name
	 * @return object upon success, otherwise false
	 */
	public static function read_by_name( $name ) {
		global $wpdb;
		$found = false;
		$result = wp_cache_get( self::READ_BY_NAME . '_' . $name, self::CACHE_GROUP, false, $found );
		if ( $found === false ) {
			$result = false;
			$group_table = _groups_get_tablename( 'group' );
			$group = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM $group_table WHERE name = %s",
				$name
			) );
			if ( isset( $group->group_id ) ) {
				$result = $group;
			}
			wp_cache_set( self::READ_BY_NAME . '_' . $name, $result, self::CACHE_GROUP );
		}
		return $result;
	}
	
	/**
	 * Update group.
	 * 
	 * @param array $map group attribute, must contain group_id
	 * @return group_id on success, otherwise false
	 */
	public static function update( $map ) {
		
		global $wpdb;
		extract( $map );
		$result = false;
		
		if ( isset( $group_id ) && !empty( $name ) ) {
			$old_group = Groups_Group::read( $group_id );
			$group_table = _groups_get_tablename( 'group' );
			if ( !isset( $description ) || ( $description === null ) ) {
				$description = '';
			}
			$wpdb->query( $wpdb->prepare(
				"UPDATE $group_table SET name = %s, description = %s WHERE group_id = %d",
				$name,
				$description,
				Groups_Utility::id( $group_id )
			) );
			if ( empty( $parent_id ) ) {
				$wpdb->query( $wpdb->prepare(
					"UPDATE $group_table SET parent_id = NULL WHERE group_id = %d",
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
				$groups = $wpdb->get_var( "SELECT COUNT(*) FROM $group_table" );
				if ( $groups !== null ) {
					$group_ids		   = array();
					$group_ids[]		 = Groups_Utility::id( $group_id );
					$iterations		  = 0;
					$old_group_ids_count = 0;
					while( ( $iterations < $groups ) && ( count( $group_ids ) > 0 ) && ( count( $group_ids ) !== $old_group_ids_count ) ) {
						
						$iterations++;
						$old_group_ids_count = count( $group_ids );
						
						$id_list	 = implode( ",", $group_ids );
						// We can trust ourselves here, no need to use prepare()
						// but careful if this query is modified!
						$successor_group_ids = $wpdb->get_results(
							"SELECT group_id FROM $group_table WHERE parent_id IS NOT NULL AND parent_id IN ($id_list)"
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
							"UPDATE $group_table SET parent_id = %d WHERE group_id = %d",
							Groups_Utility::id( $parent_id),
							Groups_Utility::id( $group_id )
						) );
					}
				}
			}
			$result = $group_id;
			if ( !empty( $name ) ) {
				wp_cache_delete( self::READ_BY_NAME . '_' . $name, self::CACHE_GROUP );
			}
			if ( !empty( $old_group ) && !empty( $old_group->name ) ) {
				wp_cache_delete( self::READ_BY_NAME . '_' . $old_group->name, self::CACHE_GROUP );
			}
			do_action( "groups_updated_group", $result );
		}
		return $result;
	}
	
	/**
	 * Remove group and its relations.
	 * 
	 * @param int $group_id
	 * @return group_id if successful, false otherwise
	 */
	public static function delete( $group_id ) {

		global $wpdb;
		$result = false;
		
		if ( $group = self::read( $group_id ) ) {
			
			// delete group-capabilities
			$group_capability_table = _groups_get_tablename( 'group_capability' );
			$wpdb->query( $wpdb->prepare(
				"DELETE FROM $group_capability_table WHERE group_id = %d",
				Groups_Utility::id( $group->group_id )
			) );
			
			// delete group-users
			$user_group_table = _groups_get_tablename( 'user_group' );
			$wpdb->query( $wpdb->prepare(
				"DELETE FROM $user_group_table WHERE group_id = %d",
				$group->group_id
			) );
			
			// set parent_id to null where this group is parent
			$group_table = _groups_get_tablename( 'group' );
			$wpdb->query( $wpdb->prepare(
				"UPDATE $group_table SET parent_id = NULL WHERE parent_id = %d",
				$group->group_id
			) );
			
			// delete group
			if ( $wpdb->query( $wpdb->prepare(
				"DELETE FROM $group_table WHERE group_id = %d",
				$group->group_id
			) ) ) {
				$result = $group->group_id;
				if ( !empty( $group->name ) ) {
					wp_cache_delete( self::READ_BY_NAME . '_' . $group->name, self::CACHE_GROUP );
				}
				do_action( "groups_deleted_group", $result );
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
	 * @return array of int with group IDs
	 * 
	 * @since groups 1.4.9
	 */
	public static function get_groups( $args = array() ) {
		global $wpdb;

		extract( $args );

		if ( !isset( $fields ) ) {
			$fields = "*";
		} else {
			$array_fields = explode( ',', sanitize_text_field( $fields ) );
			$fields = "";
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

		if ( !isset( $order_by ) ) {
			$order_by = "";
		} else {
			$order_by = sanitize_text_field( $order_by );
			switch( trim( $field ) ) {
				case 'group_id' :
				case 'parent_id' :
				case 'creator_id' :
				case 'datetime' :
				case 'name' :
				case 'description' :
					$order = '';
					if ( !isset( $order ) || ( !( $order == 'ASC' ) && !( $order == 'DESC' ) ) ) {
						$order = 'DESC';
					}
					$order_by = $wpdb->prepare( " ORDER BY %s $order ", array( $order_by ) );
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
		if ( count( $include ) > 0 ) {
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
		if ( count( $include_by_name ) > 0 ) {
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
		if ( count( $exclude ) > 0 ) {
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
		if ( count( $exclude_by_name ) > 0 ) {
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
		$groups = $wpdb->get_results( "SELECT $fields FROM $groups_table $where $order_by" );

		return $groups;
	}
}
