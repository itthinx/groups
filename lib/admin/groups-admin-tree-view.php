<?php
/**
 * groups-admin-tree-view.php
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
 * Tree view : a simple tree view
 */
function groups_admin_tree_view() {

	$output = '';

	if ( !Groups_User::current_user_can( GROUPS_ACCESS_GROUPS ) ) {
		wp_die( esc_html__( 'Access denied.', 'groups' ) );
	}

	$output .= '<div class="groups-tree-view">';
	$output .= '<h1>';
	$output .= esc_html__( 'Tree of Groups', 'groups' );
	$output .= '</h1>';

	$tree = Groups_Utility::get_group_tree();
	$tree_output = '';
	$linked = Groups_User::current_user_can( GROUPS_ADMINISTER_GROUPS );
	Groups_Utility::render_group_tree( $tree, $tree_output, $linked );
	$output .= $tree_output;

	$output .= '</div>'; // .groups-tree-view

	echo $output;
} // function groups_admin_tree_view()
