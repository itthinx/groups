<?php
/**
 * class-groups-admin-custom-posts.php
 *
 * Copyright (c) 2012 "kento" Karim Rahimpur www.itthinx.com
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
 * @author Antonio Blanco
 * @author Karim Rahimpur
 * @package groups
 * @since groups 1.4.2
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Post column extensions.
 */
class Groups_Admin_Post_Columns {

	/**
	 * Groups column header id.
	 * @var string
	 */
	const GROUPS = 'groups-read';

	/**
	 * Field name.
	 * @var string
	 */
	const GROUPS_READ = 'groups-read';

	const CACHE_GROUP    = 'groups';
	const EDIT_TERM_LINK = 'edit-term-link';

	/**
	 * Adds an admin_init action.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
	}

	/**
	 * Adds the filters and actions only for users who have the right
	 * Groups permissions and for the post types that have access
	 * restrictions enabled.
	 */
	public static function admin_init() {
		if ( current_user_can( GROUPS_ACCESS_GROUPS ) ) {
			$post_types = get_post_types( array( 'public' => true ) );
			$post_types_option = Groups_Options::get_option( Groups_Post_Access::POST_TYPES, array() );
			foreach ( $post_types as $post_type ) {
				if ( !isset( $post_types_option[$post_type]['add_meta_box'] ) || $post_types_option[$post_type]['add_meta_box'] ) {
					if ( ( $post_type == 'attachment' ) ) {
						// filters to display the media's access restriction groups
						add_filter( 'manage_media_columns', array( __CLASS__, 'columns' ) );
						// args: string $column_name, int $media_id
						add_action( 'manage_media_custom_column', array( __CLASS__, 'custom_column' ), 10, 2 );
						// make the groups column sortable
						add_filter( 'manage_upload_sortable_columns', array( __CLASS__, 'manage_edit_post_sortable_columns' ) );
					} else {
						// filters to display the posts' access restriction groups
						add_filter( 'manage_' . $post_type . '_posts_columns', array( __CLASS__, 'columns' ) );
						// args: string $column_name, int $post_id
						add_action( 'manage_' . $post_type . '_posts_custom_column', array( __CLASS__, 'custom_column' ), 10, 2 );
						// make the groups column sortable
						add_filter( 'manage_edit-' . $post_type . '_sortable_columns', array( __CLASS__, 'manage_edit_post_sortable_columns' ) );
					}
				}
			}
		}
	}

	/**
	 * Adds a new column to the post type's table showing the access
	 * restriction groups.
	 * 
	 * @param array $column_headers
	 * @return array column headers
	 */
	public static function columns( $column_headers ) {
		$column_headers[self::GROUPS] = sprintf(
			'<span title="%s">%s</span>',
			esc_attr( __( 'One or more groups granting access to entries.', 'groups' ) ),
			esc_html( _x( 'Groups', 'Column header', 'groups' ) )
		);
		return $column_headers;
	}

	/**
	 * Renders custom column content.
	 * 
	 * @param string $column_name
	 * @param int $post_id
	 * @return string custom column content
	 */
	public static function custom_column( $column_name, $post_id ) {
		$output = '';
		switch ( $column_name ) {
			case self::GROUPS :
				$entries = array();
				$groups_read = get_post_meta( $post_id, Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ );
				if ( count( $groups_read ) > 0 ) {
					$groups = Groups_Group::get_groups( array( 'order_by' => 'name', 'order' => 'ASC', 'include' => $groups_read ) );
					if ( ( count( $groups ) > 0 ) ) {
						foreach( $groups as $group ) {
							$entries[] = wp_strip_all_tags( $group->name );
						}
					}
				}
				if (
					function_exists( 'get_term_meta' ) && // >= WordPress 4.4
					class_exists( 'Groups_Restrict_Categories' ) &&
					method_exists( 'Groups_Restrict_Categories', 'get_controlled_taxonomies' ) &&
					method_exists( 'Groups_Restrict_Categories', 'get_term_read_groups' ) // >= Groups Restrict Categories 2.0.0
				) {
					$taxonomies = Groups_Restrict_Categories::get_controlled_taxonomies();
					if ( count( $taxonomies ) > 0 ) {
						$terms = wp_get_object_terms( $post_id, $taxonomies );
						if ( !( $terms instanceof WP_Error ) ) {
							foreach( $terms as $term ) {
								if ( in_array( $term->taxonomy, $taxonomies ) ) {
									$term_group_ids = Groups_Restrict_Categories::get_term_read_groups( $term->term_id );
									if ( count( $term_group_ids ) > 0 ) {

										if ( !empty( $term_group_ids ) ) {
											$edit_term_link = self::get_edit_term_link( $term->term_id, $term->taxonomy );
											$taxonomy_label = '';
											if ( $taxonomy = get_taxonomy( $term->taxonomy ) ) {
												$taxonomy_label = isset( $taxonomy->label ) ? __( $taxonomy->label ) : '';
												$labels = isset( $taxonomy->labels ) ? $taxonomy->labels : null;
												if ( $labels !== null ) {
													if ( isset( $labels->singular_name ) )  {
														$taxonomy_label = __( $labels->singular_name );
													}
												}
											}
											$term_taxonomy_title = !empty( $term->name ) ? $term->name : '';
											$term_taxonomy_title.= !empty( $taxonomy_label ) ? ' ' . $taxonomy_label : '';
											foreach( $term_group_ids as $group_id ) {
												if ( $group = Groups_Group::read( $group_id ) ) {
													$entries[] = sprintf(
														'%s <a href="%s" title="%s" style="cursor: help">%s</a>',
														esc_html( $group->name ),
														esc_url( $edit_term_link ),
														esc_attr( $term_taxonomy_title),
														esc_html( $term->name )
													);
												}
											}
										}
									}
								}
							}
						}
					}
				}
				if ( !empty( $entries ) ) {
					sort( $entries );
					$output .= '<ul>';
					foreach( $entries as $entry ) {
						$output .= '<li>';
						$output .= $entry; // entries are already escaped for output
						$output .= '</li>';
					}
					$output .= '</ul>';
				}
				break;
		}
		echo $output;
	}

	/**
	 * Helper to reduce query redundancy due to usage of current_user_can() in get_edit_term_link() etc.
	 *
	 * @param int $term_id
	 * @param string $taxonomy
	 * @return string or null if edit link could not be retrieved
	 */
	private static function get_edit_term_link( $term_id, $taxonomy ) {
		$result = null;
		$user_id = get_current_user_id();
		$cached = Groups_Cache::get( self::EDIT_TERM_LINK . '_' . $term_id . '_' . $user_id, self::CACHE_GROUP );
		if ( $cached !== null ) {
			$result = $cached->value;
			unset( $cached );
		} else {
			$result = get_edit_term_link( $term_id, $taxonomy );
			Groups_Cache::set( self::EDIT_TERM_LINK . '_' . $term_id . '_' . $user_id, $result, self::CACHE_GROUP );
		}
		return $result;
	}

	/**
	 * Groups column is sortable.
	 * 
	 * Sorting depends on the filters Groups_Admin_Posts::posts_join() and Groups_Admin_Posts::posts_orderby()
	 * which add the relevant group information and sort by group name.
	 * 
	 * @see Groups_Admin_Posts::posts_join()
	 * @see Groups_Admin_Posts::posts_orderby()
	 * @param array $sortable_columns
	 * @return array
	 */
	public static function manage_edit_post_sortable_columns( $sortable_columns ) {
		$sortable_columns[self::GROUPS] = self::GROUPS;
		return $sortable_columns;
	}

}
Groups_Admin_Post_Columns::init();
