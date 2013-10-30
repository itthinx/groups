<?php
/**
 * class-groups-admin-posts.php
 *
 * Copyright (c) 2013 "kento" Karim Rahimpur www.itthinx.com
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
 * @since groups 1.4.2
 */

/**
 * Additions to post overview admin screens.
 */
class Groups_Admin_Posts {

	/**
	 * Sets up an admin_init hook where our actions and filters are added.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
	}

	/**
	 * Adds actions and filters to handle filtering by access restriction
	 * capability.
	 */
	public static function admin_init() {
		add_action( 'restrict_manage_posts', array( __CLASS__, 'restrict_manage_posts' ) );
		add_filter( 'parse_query', array( __CLASS__, 'parse_query' ) );
	}

	/**
	 * Renders the access restriction field.
	 */
	public static function restrict_manage_posts() {

		global $pagenow;

		if ( is_admin() ) {

			if ( $pagenow == 'edit.php' ) { // check that we're on the right screen

				$post_type = isset( $_GET['post_type'] ) ? $_GET['post_type'] : 'post';

				if ( false ) { // @todo check that access restriction is enabled for the $post_type

					$output = '';

					// @todo print the access restriction filter field for post types that have it enabled

					// @todo for now, let's use as the field name : Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ_POST_CAPABILITY

					echo $output;
				}

			}
		}

	}

	/**
	 * Query modifier to take the selected access restriction capability into
	 * account.
	 * 
	 * @param WP_Query $query query object passed by reference
	 */
	public static function parse_query( &$query ) {

		global $pagenow;

		if ( is_admin() ) {

			if ( $pagenow == 'edit.php' ) { // check that we're on the right screen

				$post_type = isset( $_GET['post_type'] ) ? $_GET['post_type'] : 'post';

				if ( false ) { // @todo check that access restriction is enabled for the $post_type

					if ( !empty( $_GET[Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ_POST_CAPABILITY] ) ) {

						$capability_id = Groups_Utility::id( $_GET[Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ_POST_CAPABILITY] );

						// modify the $query to take the access restriction filter into account

						$query->query_vars['meta_key'] = Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ_POST_CAPABILITY;
						$query->query_vars['meta_value'] = $capability_id;
					}
				}
			}

		}

	}

}
Groups_Admin_Posts::init();
