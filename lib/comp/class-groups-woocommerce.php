<?php
/**
 * class-groups-woocommerce.php
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
 * Woocommerce capabilities integration.
 */
class Groups_Woocommerce {
	
	/**
	 * Hook into actions to extend user capabilities.
	 */
	public static function init() {
		add_filter( 'woocommerce_product_is_visible', array( __CLASS__, 'woocommerce_product_is_visible' ), 10, 2 );
	}

	/**
	 * Hide the product from the catalog if the current user has not access to it.
	 * @param boolean $visible
	 * @param int $product_id
	 * @return boolean
	 */
	public static function woocommerce_product_is_visible ( $visible, $product_id ) {
		if ( $visible ) {
			$visible = Groups_Post_Access::user_can_read_post( $product_id );
		}
		return $visible;
	}
}
Groups_Woocommerce::init();
