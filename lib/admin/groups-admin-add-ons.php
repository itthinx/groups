<?php
/**
 * groups-admin-add-ons.php
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
 * @since groups 1.8.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the heading and content container for the Add-Ons section.
 */
function groups_admin_add_ons() {
	echo '<div class="groups-admin-add-ons wrap">';
	echo '<h1>';
	echo esc_html__( 'Add-Ons', 'groups' );
	echo '</h1>';

	$extensions_box = '<div id="groups-extensions-support">';
	$extensions_box .= '<h2>';
	$extensions_box .= esc_html__( 'Your support matters!', 'groups' );
	$extensions_box .= '</h2>';
	$extensions_box .= '<p>';
	$extensions_box .= esc_html__( 'Enhanced functionality is available via official extensions for Groups.', 'groups' );
	$extensions_box .= '</p>';
	$extensions_box .= '<p>';
	$extensions_box .= esc_html__( 'By getting an official extension, you fund the work that is necessary to maintain and improve Groups.', 'groups' );
	$extensions_box .= '</p>';
	$extensions_box .= '</div>';
	echo $extensions_box; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	groups_admin_add_ons_content();
	echo '</div>'; // .groups-admin-add-ons.wrap
}

/**
 * Renders the content of the Add-Ons section.
 *
 * @param $params array of options (offset is 0 by default and used to adjust heading h2)
 */
function groups_admin_add_ons_content( $params = array( 'offset' => 0 ) ) {

	$d = intval( $params['offset'] );
	$h2 = sprintf( 'h%d', 2+$d );

	echo "<$h2 class='woocommerce'>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo esc_html__( 'Discover our favorite Extensions for Groups and WooCommerce', 'groups' );
	echo "</$h2>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	$entries = array(
		'groups-woocommerce' => array(
			'title'   => 'Groups for WooCommerce',
			'content' => 'Sell Memberships with Groups and WooCommerce – the best Group Membership and Access Control solution for WordPress and WooCommerce.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/woocommerce/groups-woocommerce.png',
			'url'     => 'https://woocommerce.com/products/groups-woocommerce/',
			'index'   => 10
		),
		'woocommerce-group-coupons' => array(
			'title'   => 'Group Coupons',
			'content' => 'Automatically apply and restrict coupon validity for user groups. Offer exclusive, automatic and targeted discounts for your customers.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/woocommerce/group-coupons.png',
			'url'     => 'https://woocommerce.com/products/group-coupons/',
			'index'   => 20
		),
		'woocommerce-product-search' => array(
			'title'   => 'WooCommerce Product Search',
			'content' => 'The perfect Search Engine helps customers to find and buy products quickly – essential for every WooCommerce store.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/woocommerce/woocommerce-product-search.png',
			'url'     => 'https://woocommerce.com/products/woocommerce-product-search/',
			'index'   => 30
		),
		'woocommerce-sales-analysis' => array(
			'title'   => 'Sales Analysis',
			'content' => 'Sales Analysis for WooCommerce offers reporting for Marketers & Managers. Get insights on key metrics, international sales, revenue, product and customer trends.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/woocommerce/sales-analysis.png',
			'url'     => 'https://woocommerce.com/products/sales-analysis-for-woocommerce/',
			'index'   => 40
		),
		'volume-discount-coupons' => array(
			'title'   => 'Volume Discount Coupons',
			'content' => 'Increase your sales by giving customers coupons and automatic discounts based on the quantities purchased.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/woocommerce/volume-discount-coupons.png',
			'url'     => 'https://woocommerce.com/shop/volume-discount-coupons/',
			'index'   => 50
		),
		'restrict-payment-methods' => array(
			'title'   => 'Restrict Payment Methods',
			'content' => 'Limit the use of Payment Methods by Group Memberships, Roles, Countries, and Order Amounts.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/woocommerce/restrict-payment-methods.png',
			'url'     => 'https://woocommerce.com/products/restrict-payment-methods/',
			'index'   => 60
		),
		'woopayments' => array(
			'title'   => 'WooPayments',
			'content' => 'The only payment solution fully integrated to Woo. Accept credit/debit cards and local payment options with no setup or monthly fees.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/woocommerce/woopayments.png',
			'url'     => 'https://woocommerce.com/products/woopayments/',
			'index'   => 70
		),
		'woocommerce-subscriptions' => array(
			'title'   => 'WooCommerce Subscriptions',
			'content' => 'Let customers subscribe to your products or services and pay on a weekly, monthly or annual basis.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/woocommerce/woocommerce-subscriptions.png',
			'url'     => 'https://woocommerce.com/products/woocommerce-subscriptions/',
			'index'   => 80
		),
		'woo' => array(
			'title'   => 'WooCommerce Marketplace',
			'content' => 'Explore more extensions on the WooCommerce Marketplace. Products you can trust, built by the WooCommerce team and trusted partners.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/woocommerce/woo.png',
			'url'     => 'https://woocommerce.com/products/',
			'index'   => 90
		)
	);
	usort( $entries, 'groups_admin_add_ons_sort' );

	echo '<ul class="woocommerce add-ons">';
	foreach( $entries as $key => $entry ) {
		echo '<li class="add-on">';
		echo sprintf( '<a href="%s">', esc_url( $entry['url'] ) );
		echo '<h3>';
		echo sprintf( '<img src="%s"/>', esc_url( $entry['image'] ) );
		echo '<span class="title">';
		echo esc_html( $entry['title'] );
		echo '</span>';
		echo '</h3>';
		echo '<p class="content">';
		echo wp_kses_post( $entry['content'] );
		echo '</p>';
		echo '</a>';
		echo '</li>'; // .add-on
	}
	echo '</ul>'; // .add-ons

	echo "<$h2>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo esc_html__( 'More recommended Extensions for Groups', 'groups' );
	echo "</$h2>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	$entries = array(
		'groups-drip-content' => array(
			'title'   => 'Groups Drip Content',
			'content' => 'Release posts, pages, other post types and embedded content on a schedule. Drip content to members based on user account creation, group memberships or specific dates and times.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/groups-drip-content.png',
			'url'     => 'https://www.itthinx.com/shop/groups-drip-content/',
			'index'   => 10
		),
		'groups-file-access' => array(
			'title'   => 'Groups File Access',
			'content' => 'Groups File Access is a WordPress plugin that allows to provide file download links for authorized users. Access to files is restricted to users by their group membership.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/groups-file-access.png',
			'url'     => 'https://www.itthinx.com/shop/groups-file-access/',
			'index'   => 50
		),
		'groups-forums' => array(
			'title'   => 'Groups Forums',
			'content' => 'Groups Forums provides a powerful and yet light-weight forum system for WordPress sites.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/groups-forums.png',
			'url'     => 'https://www.itthinx.com/shop/groups-forums/',
			'index'   => 100
		),
		'groups-gravity-forms' => array(
			'title'   => 'Groups Gravity Forms',
			'content' => 'This extension integrates Groups with Gravity Forms. It allows to add users to groups automatically, based on form submissions.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/groups-gravity-forms.png',
			'url'     => 'https://www.itthinx.com/shop/groups-gravity-forms/',
			'index'   => 100
		),
		'groups-import-export' => array(
			'title'   => 'Groups Import Export',
			'content' => 'This is an extension for Groups, providing import and export facilities. Users can be imported and assigned to groups in bulk from a text file. Users can be exported in bulk, including all users or users that belong to specific groups.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/groups-import-export.png',
			'url'     => 'https://www.itthinx.com/shop/groups-import-export/',
			'index'   => 100
		),
		'groups-newsletters' => array(
			'title'   => 'Groups Newsletters',
			'content' => 'Newsletter Campaigns for Subscribers and Groups. Groups Newsletters helps you to communicate efficiently, providing targeted information to groups of recipients through automated campaigns. Integrated with WooCommerce, lets customers subscribe to newsletters at checkout.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/groups-newsletters.png',
			'url'     => 'https://www.itthinx.com/shop/groups-newsletters/',
			'index'   => 100
		),
		'groups-restrict-categories' => array(
			'title'   => 'Groups Restrict Categories',
			'content' => 'Access restrictions for categories and tags, also supporting custom post types and taxonomies.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/groups-restrict-categories.png',
			'url'     => 'https://www.itthinx.com/shop/groups-restrict-categories/',
			'index'   => 20
		),
		'widgets-control-pro' => array(
			'title'   => 'Widgets Control Pro',
			'content' => 'An advanced Widget toolbox that adds visibility management and helps to control where widgets are shown efficiently. Show or hide widgets based on a user’s group membership.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/widgets-control-pro.png',
			'url'     => 'https://www.itthinx.com/shop/widgets-control-pro/',
			'index'   => 100
		)
	);
	usort( $entries, 'groups_admin_add_ons_sort' );

	echo '<ul class="groups add-ons">';
	foreach( $entries as $key => $entry ) {
		echo '<li class="add-on">';
		echo sprintf( '<a href="%s">', esc_url( $entry['url'] ) );
		echo '<h3>';
		echo sprintf( '<img src="%s"/>', esc_url( $entry['image'] ) );
		echo '<span class="title">';
		echo esc_html( $entry['title'] );
		echo '</span>';
		echo '</h3>';
		echo '<p>';
		echo wp_kses_post( $entry['content'] );
		echo '</p>';
		echo '</a>';
		echo '</li>'; // .add-on
	}
	echo '</ul>'; // .add-ons

	echo "<$h2>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo esc_html__( 'Other recommended Tools', 'groups' );
	echo "</$h2>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	$entries = array(
		'affiliates-pro' => array(
			'title'   => 'Affiliates Pro',
			'content' => 'Boost Sales with Affiliate Marketing for your WordPress site.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/affiliates-pro.png',
			'url'     => 'https://www.itthinx.com/shop/affiliates-pro/',
			'index'   => 10
		),
		'affiliates-enterprise' => array(
			'title'   => 'Affiliates Enterprise',
			'content' => 'Affiliates Enterprise provides an affiliate management system for sellers, shops and developers, who want to boost sales with their own affiliate program. Features affiliate campaigns, tracking pixels and multiple tiers.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/affiliates-enterprise.png',
			'url'     => 'https://www.itthinx.com/shop/affiliates-enterprise/',
			'index'   => 20
		),
		'itthinx-mail-queue' => array(
			'title'   => 'Itthinx Mail Queue',
			'content' => 'Features a fully automated SMTP email queue that substantially improves the way emails are sent out from your site. Prioritize sending by origin, eliminate delays for your visitors and balance your resources.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/itthinx-mail-queue.png',
			'url'     => 'https://www.itthinx.com/shop/itthinx-mail-queue/',
			'index'   => 30
		)
	);
	usort( $entries, 'groups_admin_add_ons_sort' );

	echo '<ul class="other add-ons">';
	foreach( $entries as $key => $entry ) {
		echo '<li class="add-on">';
		echo sprintf( '<a href="%s">', esc_url( $entry['url'] ) );
		echo '<h3>';
		echo sprintf( '<img src="%s"/>', esc_url( $entry['image'] ) );
		echo '<span class="title">';
		echo esc_html( $entry['title'] );
		echo '</span>';
		echo '</h3>';
		echo '<p>';
		echo wp_kses_post( $entry['content'] );
		echo '</p>';
		echo '</a>';
		echo '</li>'; // .add-on
	}
	echo '</ul>'; // .add-ons
}

function groups_admin_add_ons_sort( $e1, $e2 ) {
	$i1 = isset( $e1['index'] ) ? $e1['index'] : 0;
	$i2 = isset( $e2['index'] ) ? $e2['index'] : 0;
	$t1 = isset( $e1['title'] ) ? $e1['title'] : '';
	$t2 = isset( $e2['title'] ) ? $e2['title'] : '';

	return $i1 - $i2 + strnatcmp( $t1, $t2 );
}
