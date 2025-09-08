<?php
/**
 * class-groups-utility.php
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
 * Utility functions.
 */
class Groups_Utility {

	/**
	 * Transient expiration, 1 minute.
	 *
	 * @since 3.0.0
	 *
	 * @var integer
	 */
	const TREE_TRANSIENT_EXPIRATION = 60;

	/**
	 * Data cache during requests to avoid expensive operations.
	 *
	 * @since 3.7.0
	 *
	 * @var array
	 */
	private static $cache = array();

	/**
	 * Checks an id (0 is accepted => anonymous).
	 *
	 * @param string|int $id
	 * @return int|boolean if validated, the id as an int, otherwise false
	 */
	public static function id( $id ) {
		$result = false;
		if ( is_numeric( $id ) ) {
			$id = intval( $id );
			//if ( $id > 0 ) {
			if ( $id >= 0 ) { // 0 => anonymous
				$result = $id;
			}
		}
		return $result;
	}

	/**
	 * Returns an array of blog_ids for current blogs.
	 *
	 * @return array of int with blog ids
	 */
	public static function get_blogs() {
		global $wpdb;
		$result = array();
		if ( is_multisite() ) {
			$blogs = $wpdb->get_results( $wpdb->prepare(
				"SELECT blog_id FROM $wpdb->blogs WHERE site_id = %d AND archived = '0' AND spam = '0' AND deleted = '0' ORDER BY registered DESC",
				$wpdb->siteid
			) );
			if ( is_array( $blogs ) ) {
				foreach( $blogs as $blog ) {
					$result[] = $blog->blog_id;
				}
			}
		} else {
			$result[] = get_current_blog_id();
		}
		return $result;
	}

	/**
	 * Object sort helper, sort by name property.
	 *
	 * @param object $o1
	 * @param object $o2
	 *
	 * @return int
	 */
	private static function namesort( $o1, $o2 ) {
		return strcmp( $o1->name, $o2->name );
	}

	/**
	 * Provide the groups tree.
	 *
	 * @since 3.7.0
	 *
	 * @access private
	 *
	 * @return object[]
	 */
	public static function get_tree() {

		global $wpdb;

		if ( isset( self::$cache['tree'] ) ) {
			return self::$cache['tree'];
		}

		$tree = get_transient( 'groups_utility_tree' );
		if ( is_array( $tree ) ) {
			self::$cache['tree'] = $tree;
			return $tree;
		}

		$group_table = _groups_get_tablename( 'group' );
		// Note that ORDER BY name is not necessary as it is done after obtaining the rows during processing below
		$map = $wpdb->get_results( "SELECT group_id, parent_id, name FROM $group_table" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$prune = array();

		$objects = array();
		foreach ( $map as $entry ) {
			if ( $entry->parent_id !== null ) {
				if ( !array_key_exists( $entry->parent_id, $objects ) ) {
					// create parent entry
					$p = new stdClass();
					$p->name = null; // completed below
					$p->group_id = $entry->parent_id;
					$p->children = array();
					$objects[$p->group_id] = $p;
				}
			}

			if ( !array_key_exists( $entry->group_id, $objects ) ) {
				$o = new stdClass();
				$o->name = $entry->name;
				$o->group_id = $entry->group_id;
				$o->children = array();
				$objects[$o->group_id] = $o;
				if ( $entry->parent_id !== null ) {
					$objects[$entry->parent_id]->children[] = $o;
					uasort( $objects[$entry->parent_id]->children, array( __CLASS__, 'namesort' ) );
				}
			} else {
				// complete name from parent entry created
				if ( $objects[$entry->group_id]->name === null ) {
					$objects[$entry->group_id]->name = $entry->name;
				}
				if ( $entry->parent_id !== null ) {
					$objects[$entry->parent_id]->children[] = $objects[$entry->group_id];
					uasort( $objects[$entry->parent_id]->children, array( __CLASS__, 'namesort' ) );
				}
			}

			if ( $entry->parent_id !== null ) {
				$prune[] = $entry->group_id;
			}
		}

		foreach ( $prune as $group_id ) {
			unset( $objects[$group_id] );
		}

		uasort( $objects, array( __CLASS__, 'namesort' ) );

		set_transient( 'groups_utility_tree', $objects, self::TREE_TRANSIENT_EXPIRATION );

		self::$cache['tree'] = $objects;

		return $objects;
	}

	/**
	 * Render options from tree for select.
	 *
	 * @since 3.7.0
	 *
	 * @param array $tree
	 * @param string $output
	 * @param int $level
	 */
	public static function render_tree_options( &$tree, &$output, $level = 0, $selected = array() ) {
		foreach( $tree as $group_id => $object ) {
			$output .= sprintf(
				'<option class="node" value="%d" %s>',
				esc_attr( $group_id ),
				in_array( $group_id, $selected ) ? 'selected' : ''
			);
			// If specific filtering is done on the group data, we might need to pass it through this call and use the name of the $group object instead:
			// $group = Groups_Group::read( $group_id );
			if ( $level > 0 ) {
				$output .= str_repeat( "&nbsp;", $level ) . "&llcorner;";
			}
			$output .= $object->name ? stripslashes( wp_filter_nohtml_kses( $object->name ) ) : '';
			$output .= '</option>';
			if ( !empty( $object->children ) ) {
				self::render_tree_options( $object->children, $output, $level + 1, $selected );
			}
		}
	}

	/**
	 * Render the group tree to the $output parameter passed by reference.
	 *
	 * @since 3.7.0
	 *
	 * @param array $tree
	 * @param string $output
	 */
	public static function render_tree( &$tree, &$output, $linked = false ) {
		$output .= '<ul class="groups-tree">';
		foreach( $tree as $group_id => $object ) {
			$output .= '<li class="groups-tree-node">';
			// If specific filtering is done on the group data, we might need to pass it through this call and use the name of the $group object instead:
			// $group = Groups_Group::read( $group_id );
			if ( $linked ) {
				$edit_url = add_query_arg(
					array(
						'group_id' => intval( $object->group_id ),
						'action' => 'edit'
					),
					get_admin_url( null, 'admin.php?page=groups-admin' )
				);
				$output .= sprintf( '<a href="%s">', $edit_url );
			}
			$output .= $object->name ? stripslashes( wp_filter_nohtml_kses( $object->name ) ) : '';
			if ( $linked ) {
				$output .= '</a>';
			}
			if ( !empty( $object->children ) ) {
				self::render_tree( $object->children, $output, $linked );
			}
			$output .= '</li>';
		}
		$output .= '</ul>';
	}

	/**
	 * Get the tree hierarchy of groups.
	 *
	 * This method is inefficent. Internal uses replaced by get_tree() as of 3.7.0.
	 *
	 * @deprecated since 3.7.0
	 *
	 * @param array $tree
	 *
	 * @return array
	 */
	public static function get_group_tree( &$tree = null, $linked = false ) {
		global $wpdb;
		$group_table = _groups_get_tablename( 'group' );
		if ( $tree === null ) {
			$tree = array();
			$root_groups = $wpdb->get_results( "SELECT group_id FROM $group_table WHERE parent_id IS NULL ORDER BY name" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( $root_groups ) {
				foreach( $root_groups as $root_group ) {
					$group_id = Groups_Utility::id( $root_group->group_id );
					$tree[$group_id] = array();
				}
			}
			self::get_group_tree( $tree );
			self::$cache['tree'] = $tree;
		} else {
			foreach( $tree as $group_id => $nodes ) {
				$children = $wpdb->get_results( $wpdb->prepare(
					"SELECT group_id FROM $group_table WHERE parent_id = %d ORDER BY name", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					Groups_Utility::id( $group_id )
				) );
				foreach( $children as $child ) {
					$tree[$group_id][$child->group_id] = array();
				}
				self::get_group_tree( $tree[$group_id] );
			}
		}
		return $tree;
	}

	/**
	 * Render options from tree for select.
	 *
	 * @deprecated since 3.7.0. Internal uses replaced by get_tree_options() as of 3.7.0.
	 *
	 * @since 2.19.0
	 *
	 * @param array $tree
	 * @param string $output
	 * @param int $level
	 */
	public static function render_group_tree_options( &$tree, &$output, $level = 0, $selected = array() ) {
		foreach( $tree as $group_id => $nodes ) {
			$output .= sprintf(
				'<option class="node" value="%d" %s>',
				esc_attr( $group_id ),
				in_array( $group_id, $selected ) ? 'selected' : ''
			);
			$group = Groups_Group::read( $group_id );
			if ( $group ) {
				if ( $level > 0 ) {
					$output .= str_repeat( "&nbsp;", $level ) . "&llcorner;";
				}
				$output .= $group->name ? stripslashes( wp_filter_nohtml_kses( $group->name ) ) : '';
			}
			$output .= '</option>';
			if ( !empty( $nodes ) ) {
				self::render_group_tree_options( $nodes, $output, $level + 1, $selected );
			}
		}
	}

	/**
	 * Render the group tree to the $output parameter passed by reference.
	 *
	 * @deprecated since 3.7.0. Internal uses replaced by render_tree() as of 3.7.0.
	 *
	 * @param array $tree
	 * @param string $output
	 */
	public static function render_group_tree( &$tree, &$output, $linked = false ) {
		$output .= '<ul class="groups-tree">';
		foreach( $tree as $group_id => $nodes ) {
			$output .= '<li class="groups-tree-node">';
			$group = Groups_Group::read( $group_id );
			if ( $group ) {
				if ( $linked ) {
					$edit_url = add_query_arg(
						array(
							'group_id' => intval( $group->group_id ),
							'action' => 'edit'
						),
						get_admin_url( null, 'admin.php?page=groups-admin' )
					);
					$output .= sprintf( '<a href="%s">', $edit_url );
				}
				$output .= $group->name ? stripslashes( wp_filter_nohtml_kses( $group->name ) ) : '';
				if ( $linked ) {
					$output .= '</a>';
				}
			}
			if ( !empty( $nodes ) ) {
				self::render_group_tree( $nodes, $output, $linked );
			}
			$output .= '</li>';
		}
		$output .= '</ul>';
	}

	/**
	 * Compares the two object's names, used for groups and
	 * capabilities, i.e. Groups_Group and Groups_Capability can be compared
	 * if both are of the same class. Otherwise this will return 0.
	 *
	 * @param Groups_Group|Groups_Capability $o1
	 * @param Groups_Group|Groups_Capability $o2 must match the class of $o1
	 *
	 * @return number
	 */
	public static function cmp( $o1, $o2 ) {
		$result = 0;
		if ( $o1 instanceof Groups_Group && $o2 instanceof Groups_Group ) {
			$result = strcmp( $o1->get_name(), $o2->get_name() );
		} else if ( $o1 instanceof Groups_Capability && $o2 instanceof Groups_Capability ) {
			$result = strcmp( $o1->get_capability(), $o2->get_capability() );
		}
		return $result;
	}
}
