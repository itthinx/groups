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
			// handle our sortable column
			add_filter( 'pre_get_posts', array( __CLASS__, 'pre_get_posts' ) );
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
			'<span title="%s">%s</a>',
			esc_attr( __( 'One or more groups granting access to entries.', GROUPS_PLUGIN_DOMAIN ) ),
			esc_html( __( 'Groups', GROUPS_PLUGIN_DOMAIN ) )
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
				$groups_read = get_post_meta( $post_id, Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ );
				if ( count( $groups_read ) > 0 ) {
					$groups = Groups_Group::get_groups( array( 'order_by' => 'name', 'order' => 'ASC', 'include' => $groups_read ) );
					if ( ( count( $groups ) > 0 ) ) {
						$output = '<ul>';
						foreach( $groups as $group ) {
							$output .= '<li>';
							$output .= wp_strip_all_tags( $group->name );
							$output .= '</li>';
						}
						$output .= '</ul>';
					}
				}
				if (
					class_exists( 'Groups_Restrict_Categories' ) &&
					method_exists( 'Groups_Restrict_Categories', 'get_controlled_taxonomies' ) &&
					method_exists( 'Groups_Restrict_Categories', 'get_term_read_groups' )
				) {
					$taxonomies = Groups_Restrict_Categories::get_controlled_taxonomies();
					$terms = wp_get_post_terms( $post_id );
					foreach( $terms as $term ) {
						if ( in_array( $term->taxonomy, $taxonomies ) ) {
							$term_group_ids = Groups_Restrict_Categories::get_term_read_groups( $term->term_id );
							$edit_term_link = get_edit_term_link( $term->term_id, $term->taxonomy, get_post_type( $post_id ) );
							if ( !empty( $term_group_ids ) ) {
								$output .= '<ul>';
								foreach( $term_group_ids as $group_id ) {
									if ( $group = Groups_Group::read( $group_id ) ) {
										$output .= '<li>';
										$output .= wp_strip_all_tags( $group->name );
										$output .= ' ';
										$output .= sprintf( '<a href="%s">%s</a>', esc_url( $edit_term_link ), esc_html( $term->name ) );
										$output .= '</li>';
									}
								}
								$output .= '</ul>';
							}
						}
					}
				}
				break;
		}
		echo $output;
	}

	/**
	 * Groups column is sortable.
	 * 
	 * @param array $sortable_columns
	 * @return array
	 */
	public static function manage_edit_post_sortable_columns( $sortable_columns ) {
		$sortable_columns[self::GROUPS] = self::GROUPS;
		return $sortable_columns;
	}

	/**
	 * Allows us to modify the query to order by group if requested.
	 * 
	 * @param WP_Query $query is passed by reference - see https://codex.wordpress.org/Plugin_API/Action_Reference/pre_get_posts
	 */
	public static function pre_get_posts( $query ) {
		if ( is_admin() ) {
			// check if query is for a post type we handle
			$post_type = $query->get( 'post_type' );
			$post_types_option = Groups_Options::get_option( Groups_Post_Access::POST_TYPES, array() );
			if ( !isset( $post_types_option[$post_type]['add_meta_box'] ) || $post_types_option[$post_type]['add_meta_box'] ) {
				// only act on post etc. screens
				$screen = get_current_screen();
				if (
					!empty( $screen ) &&
					!empty( $screen->id ) &&
					( $screen->id == 'edit-' . $post_type )
				) {
					if ( $query->get( 'orderby' ) == self::GROUPS_READ ) {
						$query->set( 'orderby', 'meta_value' );
						$meta_query = array(
							'relation' => 'OR',
							array(
								'key' => self::GROUPS_READ,
								'value' => '',
								'compare' => 'NOT EXISTS'
							),
							array(
								'key' => self::GROUPS_READ,
								'value' => array(),
								'compare' => 'NOT IN'
							)
						);
						// don't overwrite an existing meta_query, e.g. when we filter by group
						// see Groups_Admin_Posts::parse_query()
						if ( !empty( $query->get( 'meta_query' ) ) ) {
							$meta_query = array(
								'relation' => 'AND',
								$query->get( 'meta_query' ),
								$meta_query
							);
						}
						$query->set( 'meta_query', $meta_query );
					}
				}
			}
		}
	}
}
Groups_Admin_Post_Columns::init();
