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
	echo $extensions_box;

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

	echo "<$h2>";
	echo esc_html__( 'Recommended extensions for Groups', 'groups' );
	echo "</$h2>";

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
		'groups-restrict-comments-pro' => array(
			'title'   => 'Groups Restrict Comments Pro',
			'content' => 'This extension allows to restrict who can post or read comments based on a user’s group membership.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/groups-restrict-comments-pro.png',
			'url'     => 'https://www.itthinx.com/shop/groups-restrict-comments-pro/',
			'index'   => 100
		),
		'groups-woocommerce' => array(
			'title'   => 'Groups WooCommerce',
			'content' => 'This extension allows you to sell memberships with WooCommerce.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/groups-woocommerce.png',
			'url'     => 'https://www.itthinx.com/shop/groups-woocommerce/',
			'index'   => 30
		),
		'widgets-control-pro' => array(
			'title'   => 'Widgets Control Pro',
			'content' => 'An advanced Widget toolbox that adds visibility management and helps to control where widgets are shown efficiently. Show or hide widgets based on a user’s group membership.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/widgets-control-pro.png',
			'url'     => 'https://www.itthinx.com/shop/widgets-control-pro/',
			'index'   => 100
		),
		'woocommerce-group-coupons' => array(
			'title'   => 'WooCommerce Group Coupons',
			'content' => 'This extension allows to limit the validity of coupons based on groups and roles.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/woocommerce-group-coupons.png',
			'url'     => 'https://www.itthinx.com/shop/woocommerce-group-coupons/',
			'index'   => 40
		)
	);
	usort( $entries, 'groups_admin_add_ons_sort' );

	echo '<ul class="add-ons">';
	foreach( $entries as $key => $entry ) {
		echo '<li class="add-on">';
		echo sprintf( '<a href="%s">', $entry['url'] );
		echo '<h3>';
		echo sprintf( '<img src="%s"/>', $entry['image'] );
		echo $entry['title'];
		echo '</h3>';
		echo '<p>';
		echo $entry['content'];
		echo '</p>';
		echo '</a>';
		echo '</li>'; // .add-on
	}
	echo '</ul>'; // .add-ons

	echo "<$h2>";
	echo esc_html__( 'Recommended plugins by itthinx', 'groups' );
	echo "</$h2>";

	$entries = array(
		'affiliates-pro' => array(
			'title'   => 'Affiliates Pro',
			'content' => 'Boost Sales with Affiliate Marketing for your WordPress site.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/affiliates-pro.png',
			'url'     => 'https://www.itthinx.com/shop/affiliates-pro/',
			'index'   => 40
		),
		'affiliates-enterprise' => array(
			'title'   => 'Affiliates Enterprise',
			'content' => 'Affiliates Enterprise provides an affiliate management system for sellers, shops and developers, who want to boost sales with their own affiliate program. Features affiliate campaigns, tracking pixels and multiple tiers.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/affiliates-enterprise.png',
			'url'     => 'https://www.itthinx.com/shop/affiliates-enterprise/',
			'index'   => 50
		),
		'itthinx-mail-queue' => array(
			'title'   => 'Itthinx Mail Queue',
			'content' => 'Features a fully automated SMTP email queue that substantially improves the way emails are sent out from your site. Prioritize sending by origin, eliminate delays for your visitors and balance your resources.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/itthinx-mail-queue.png',
			'url'     => 'https://www.itthinx.com/shop/itthinx-mail-queue/',
			'index'   => 60
		),
		'woocommerce-product-search' => array(
			'title'   => 'WooCommerce Product Search',
			'content' => 'The essential extension for every WooCommerce Store! The perfect Search Engine for your store helps your customers to find and buy the right products quickly.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/woocommerce-product-search.png',
			'url'     => 'https://www.itthinx.com/shop/woocommerce-product-search/',
			'index'   => 10
		),
		'woocommerce-sales-analysis' => array(
			'title'   => 'Sales Analysis for WooCommerce',
			'content' => 'Sales Analysis for Managers and Marketers. Get in-depth views on fundamental Business Intelligence. Focused on sales and net revenue trends, regional analysis, product market, and customer trends.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/woocommerce-sales-analysis.png',
			'url'     => 'https://www.itthinx.com/shop/woocommerce-sales-analysis/',
			'index'   => 20
		),
		'woocommerce-volume-discount-coupons' => array(
			'title'   => 'Volume Discount Coupons for WooCommerce',
			'content' => 'Increase your sales by giving customers coupons and automatic discounts based on the quantities purchased.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/woocommerce-volume-discount-coupons.png',
			'url'     => 'https://www.itthinx.com/shop/woocommerce-volume-discount-coupons/',
			'index'   => 30
		)
	);
	usort( $entries, 'groups_admin_add_ons_sort' );

	echo '<ul class="add-ons">';
	foreach( $entries as $key => $entry ) {
		echo '<li class="add-on">';
		echo sprintf( '<a href="%s">', $entry['url'] );
		echo '<h3>';
		echo sprintf( '<img src="%s"/>', $entry['image'] );
		echo $entry['title'];
		echo '</h3>';
		echo '<p>';
		echo $entry['content'];
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
