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
 * Renders the content of the Add-Ons section.
 */
function groups_admin_add_ons() {

	echo '<div class="groups-admin-add-ons">';

	echo '<h1>';
	echo __( 'Add-Ons', GROUPS_PLUGIN_DOMAIN );
	echo '</h1>';

	echo '<h2>';
	echo __( 'Recommended extensions for Groups', GROUPS_PLUGIN_DOMAIN );
	echo '</h2>';

	$entries = array(
		'groups-file-access' => array(
			'title'   => 'Groups File Access',
			'content' => 'Groups File Access is a WordPress plugin that allows to provide file download links for authorized users. Access to files is restricted to users by their group membership.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/groups-file-access.png',
			'url'     => 'http://www.itthinx.com/shop/groups-file-access/',
			'index'   => 100
		),
		'groups-forums' => array(
			'title'   => 'Groups Forums',
			'content' => 'Groups Forums provides a powerful and yet light-weight forum system for WordPress sites.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/groups-forums.png',
			'url'     => 'http://www.itthinx.com/shop/groups-forums/',
			'index'   => 100
		),
		'groups-gravity-forms' => array(
			'title'   => 'Groups Gravity Forms',
			'content' => 'This extension integrates Groups with Gravity Forms. It allows to add users to groups automatically, based on form submissions.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/groups-gravity-forms.png',
			'url'     => 'http://www.itthinx.com/shop/groups-gravity-forms/',
			'index'   => 100
		),
		'groups-import-export' => array(
			'title'   => 'Groups Import Export',
			'content' => 'This is an extension for Groups, providing import and export facilities. Users can be imported and assigned to groups in bulk from a text file. Users can be exported in bulk, including all users or users that belong to specific groups.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/groups-import-export.png',
			'url'     => 'http://www.itthinx.com/shop/groups-import-export/',
			'index'   => 100
		),
		'groups-newsletters' => array(
			'title'   => 'Groups Newsletter',
			'content' => 'Newsletter Campaigns for Subscribers and Groups. Groups Newsletters helps you to communicate efficiently, providing targeted information to groups of recipients through automated campaigns.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/groups-newsletters.png',
			'url'     => 'http://www.itthinx.com/shop/groups-newsletters/',
			'index'   => 100
		),
		'groups-paypal' => array(
			'title'   => 'Groups PayPal',
			'content' => 'Sell memberships and subscriptions with Groups and PayPal.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/groups-paypal.png',
			'url'     => 'http://www.itthinx.com/shop/groups-paypal/',
			'index'   => 10
		),
		'groups-restrict-categories' => array(
			'title'   => 'Groups Restrict Categories',
			'content' => 'Access restrictions for categories and tags, also supporting custom post types and taxonomies.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/groups-restrict-categories.png',
			'url'     => 'http://www.itthinx.com/shop/groups-restrict-categories/',
			'index'   => 10
		),
		'groups-restrict-comments-pro' => array(
			'title'   => 'Groups Restrict Comments Pro',
			'content' => 'This extension allows to restrict who can post or read comments based on a user’s group membership.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/groups-restrict-comments-pro.png',
			'url'     => 'http://www.itthinx.com/shop/groups-restrict-comments-pro/',
			'index'   => 100
		),
		'groups-woocommerce' => array(
			'title'   => 'Groups WooCommerce',
			'content' => 'This extension allows you to sell memberships with WooCommerce.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/groups-woocommerce.png',
			'url'     => 'http://www.itthinx.com/shop/groups-woocommerce/',
			'index'   => 20
		),
		'widgets-control-pro' => array(
			'title'   => 'Widgets Control Pro',
			'content' => 'An advanced Widget toolbox that adds visibility management and helps to control where widgets are shown efficiently. Show or hide widgets based on a user’s group membership.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/widgets-control-pro.png',
			'url'     => 'http://www.itthinx.com/shop/widgets-control-pro/',
			'index'   => 20
		),
		'woocommerce-group-coupons' => array(
			'title'   => 'WooCommerce Group Coupons',
			'content' => 'This extension allows to limit the validity of coupons based on groups and roles.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/woocommerce-group-coupons.png',
			'url'     => 'http://www.itthinx.com/shop/woocommerce-group-coupons/',
			'index'   => 100
		),
		'woocommerce-groups-newsletters' => array(
			'title'   => 'WooCommerce Groups Newsletters',
			'content' => 'The WooCommerce Groups Newsletters extension lets customers subscribe to newsletters at checkout.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/woocommerce-groups-newsletters.png',
			'url'     => 'http://www.itthinx.com/shop/woocommerce-groups-newsletters/',
			'index'   => 100
		),
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

	echo '<h2>';
	echo __( 'Recommended plugins by itthinx', GROUPS_PLUGIN_DOMAIN );
	echo '</h2>';

	$entries = array(
		'affiliates-pro' => array(
			'title'   => 'Affiliates Pro',
			'content' => 'Boost Sales with Affiliate Marketing for your WordPress site.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/affiliates-pro.png',
			'url'     => 'http://www.itthinx.com/shop/affiliates-pro/',
			'index'   => 100
		),
		'affiliates-enterprise' => array(
			'title'   => 'Affiliates Enterprise',
			'content' => 'Affiliates Enterprise provides an affiliate management system for sellers, shops and developers, who want to boost sales with their own affiliate program. Features affiliate campaigns, tracking pixels and multiple tiers.',
			'image'   => GROUPS_PLUGIN_URL . 'images/add-ons/affiliates-enterprise.png',
			'url'     => 'http://www.itthinx.com/shop/affiliates-enterprise/',
			'index'   => 100
		),
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

	echo '</div>'; // .groups-admin-add-ons

	Groups_Help::footer();
}

function groups_admin_add_ons_sort( $e1, $e2 ) {
	$i1 = isset( $e1['index'] ) ? $e1['index'] : 0;
	$i2 = isset( $e2['index'] ) ? $e2['index'] : 0;
	$t1 = isset( $e1['title'] ) ? $e1['title'] : '';
	$t2 = isset( $e2['title'] ) ? $e2['title'] : '';
	
	return $i1 - $i2 + strnatcmp( $t1, $t2 );
}
