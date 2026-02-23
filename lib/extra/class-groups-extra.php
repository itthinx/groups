<?php
/**
 * class-groups-extra.php
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
 * @since groups 2.1.2
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Compatibility actions, filters, etc as needed.
 */
class Groups_Extra {

	/**
	 * Early actions.
	 */
	public static function boot() {
		add_action( 'init', array( __CLASS__, 'init' ) );
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
		add_action( 'before_woocommerce_init', array( __CLASS__, 'before_woocommerce_init' ) );
	}

	/**
	 * Registers actions, filters ...
	 */
	public static function init() {
		add_filter( 'woocommerce_product_is_visible', array( __CLASS__, 'woocommerce_product_is_visible' ), 10, 2 );
		add_filter( 'groups_comment_access_comment_count_where', array( __CLASS__, 'groups_comment_access_comment_count_where'), 10, 2 );
		add_filter( 'groups_post_access_posts_where_query_get_post_types', array( __CLASS__, 'groups_post_access_posts_where_query_get_post_types' ), 10, 3 );
	}

	/**
	 * Add admin filters and actions.
	 *
	 * @since 4.0.0
	 */
	public static function admin_init() {
		add_action( 'current_screen', array( __CLASS__, 'current_screen' ), PHP_INT_MIN );
	}

	/**
	 * Up-sell and cross-sell products are obtained directly by their ids and
	 * no normal filters are executed that would hide them. This filter is used
	 * instead to determine the visibility.
	 *
	 * If at some point we had a get_post filter in WordPress, it could filter these
	 * and we wouldn't need this.
	 *
	 * @param boolean $visible
	 * @param int $product_id
	 *
	 * @return boolean
	 */
	public static function woocommerce_product_is_visible( $visible, $product_id ) {
		if ( $visible ) {
			$visible = Groups_Post_Access::user_can_read_post( $product_id );
		}
		return $visible;
	}

	/**
	 * Take WooCommerce comment types into account.
	 *
	 * @param string $where
	 * @param int $post_id
	 *
	 * @return string
	 */
	public static function groups_comment_access_comment_count_where( $where, $post_id ) {
		if ( defined( 'WC_VERSION' ) ) {
			$where .= " AND comment_type NOT IN ('order_note', 'webhook_delivery') ";
		}
		return $where;
	}

	/**
	 * Checks if the query is a wc_query for product_query; if $post_types is empty, it will assume the product type and return that.
	 *
	 * @param string|array $post_types current query post types
	 * @param string $where the where part of the query
	 * @param WP_Query $query the query
	 *
	 * @return string
	 */
	public static function groups_post_access_posts_where_query_get_post_types( $post_types, $where, $query ) {
		if (
			empty( $post_types ) ||
			is_string( $post_types ) && ( $post_types === '' ) ||
			is_array( $post_types ) && ( count( $post_types ) === 0 )
		) {
			$wc_query = $query->get( 'wc_query', null );
			if ( ! empty( $wc_query ) && $wc_query === 'product_query' ) {
				$post_types = 'product';
			}
		}
		return $post_types;
	}

	/**
	 * Declare WooCommerce feature compatibility.
	 *
	 * @since 3.4.1
	 */
	public static function before_woocommerce_init() {
		// HPOS
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', GROUPS_FILE, true );
		}
	}

	/**
	 * Do not redirect to the add product tasklist if there are no products when filtering.
	 *
	 * @since 4.0.0
	 *
	 * Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\Products's maybe_redirect_to_add_product_tasklist hooks on the current_screen action
	 */
	public static function current_screen() {
		global $wp_filter;

		if ( function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			if ( 'edit' === $screen->base && 'product' === $screen->post_type ) {
				$filtering = false;
				if ( class_exists( 'Groups_Post_Access_Legacy' ) ) {
					$field = Groups_Post_Access_Legacy::POSTMETA_PREFIX . Groups_Post_Access_Legacy::READ_POST_CAPABILITY;
					$filtering = !empty( groups_sanitize_get( $field ) );
				}
				$filtering = $filtering || !empty( groups_sanitize_get( Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ ) );
				$filtering = $filtering || !empty( groups_sanitize_get( 'groups-woocommerce-product-groups' ) );
				if ( $filtering ) {
					foreach ( $wp_filter['current_screen']->callbacks as $priority => $callbacks ) {
						foreach ( $callbacks as $callback => $data ) {
							if ( strpos( $callback, 'maybe_redirect_to_add_product_tasklist' ) !== false ) {
								remove_action( 'current_screen', $callback, $priority );
							}
						}
					}
				}
			}
		}
	}

}
Groups_Extra::boot();
