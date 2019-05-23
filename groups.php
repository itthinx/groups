<?php
/**
 * groups.php
 *
 * Copyright (c) 2011-2019 "kento" Karim Rahimpur www.itthinx.com
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
 *
 * Plugin Name: Groups
 * Plugin URI: http://www.itthinx.com/plugins/groups
 * Description: Groups provides group-based user membership management, group-based capabilities and content access control.
 * Version: 2.7.1
 * Author: itthinx
 * Author URI: http://www.itthinx.com
 * Donate-Link: http://www.itthinx.com
 * Text Domain: groups
 * Domain Path: /languages
 * License: GPLv3
 */
if ( !defined( 'ABSPATH' ) ) {
	exit;
}
define( 'GROUPS_CORE_VERSION', '2.7.1' );
define( 'GROUPS_FILE', __FILE__ );
if ( !defined( 'GROUPS_CORE_DIR' ) ) {
	define( 'GROUPS_CORE_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
}
if ( !defined( 'GROUPS_CORE_LIB' ) ) {
	define( 'GROUPS_CORE_LIB', GROUPS_CORE_DIR . '/lib/core' );
}
if ( !defined( 'GROUPS_ACCESS_LIB' ) ) {
	define( 'GROUPS_ACCESS_LIB', GROUPS_CORE_DIR . '/lib/access' );
}
if ( !defined( 'GROUPS_ADMIN_LIB' ) ) {
	define( 'GROUPS_ADMIN_LIB', GROUPS_CORE_DIR . '/lib/admin' );
}
if ( !defined( 'GROUPS_AUTO_LIB' ) ) {
	define( 'GROUPS_AUTO_LIB', GROUPS_CORE_DIR . '/lib/auto' );
}
if ( !defined( 'GROUPS_VIEWS_LIB' ) ) {
	define( 'GROUPS_VIEWS_LIB', GROUPS_CORE_DIR . '/lib/views' );
}
if ( !defined( 'GROUPS_WP_LIB' ) ) {
	define( 'GROUPS_WP_LIB', GROUPS_CORE_DIR . '/lib/wp' );
}
if ( !defined( 'GROUPS_EXTRA_LIB' ) ) {
	define( 'GROUPS_EXTRA_LIB', GROUPS_CORE_DIR . '/lib/extra' );
}
if ( !defined( 'GROUPS_LEGACY_LIB' ) ) {
	define( 'GROUPS_LEGACY_LIB', GROUPS_CORE_DIR . '/legacy' );
}
if ( !defined( 'GROUPS_CORE_URL' ) ) {
	define( 'GROUPS_CORE_URL', plugins_url( 'groups' ) );
}
require_once( GROUPS_CORE_LIB . '/constants.php' );
require_once( GROUPS_CORE_LIB . '/wp-init.php');
