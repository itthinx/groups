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
	 * Registers actions, filters ...
	 */
	public static function init() {
		add_filter( 'woocommerce_product_is_visible', array( __CLASS__, 'woocommerce_product_is_visible' ), 10, 2 );
		add_filter( 'groups_comment_access_comment_count_where', array( __CLASS__, 'groups_comment_access_comment_count_where'), 10, 2 );
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
	 * @return string
	 */
	public static function groups_comment_access_comment_count_where( $where, $post_id ) {
		if ( defined( 'WC_VERSION' ) ) {
			$where .= " AND comment_type NOT IN ('order_note', 'webhook_delivery') ";
		}
		return $where;
	}
}
add_action( 'init', array( 'Groups_Extra', 'init' ) );
