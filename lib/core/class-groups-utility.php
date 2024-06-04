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
	 * Get the tree hierarchy of groups.
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
			$root_groups = $wpdb->get_results( "SELECT group_id FROM $group_table WHERE parent_id IS NULL ORDER BY name" );
			if ( $root_groups ) {
				foreach( $root_groups as $root_group ) {
					$group_id = Groups_Utility::id( $root_group->group_id );
					$tree[$group_id] = array();
				}
			}
			self::get_group_tree( $tree );
		} else {
			foreach( $tree as $group_id => $nodes ) {
				$children = $wpdb->get_results( $wpdb->prepare(
					"SELECT group_id FROM $group_table WHERE parent_id = %d ORDER BY name",
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
