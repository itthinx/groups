<?php
/**
 * constants.php
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
 * @since groups 1.0.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @var string GROUPS_DEFAULT_VERSION plugin version dummy
 */
define( 'GROUPS_DEFAULT_VERSION', '1.0.0' );

/**
 * Do NOT remove this constant.
 *
 * @var string GROUPS_PLUGIN_DOMAIN plugin domain
 */
define( 'GROUPS_PLUGIN_DOMAIN', 'groups' );

/**
 * @var string GROUPS_PLUGIN_DIR plugin directory on the server
 */
define( 'GROUPS_PLUGIN_DIR', GROUPS_CORE_DIR );

/**
 * @var string GROUPS_PLUGIN_URL plugin url
 */
define( 'GROUPS_PLUGIN_URL', trailingslashit( GROUPS_CORE_URL ) );

/**
 * @var string GROUPS_TP groups table prefix
 */
define( 'GROUPS_TP', 'groups_' );

// administrative capabilities

/**
 * @var string GROUPS_ACCESS_GROUPS grants access to the groups section
 */
define( 'GROUPS_ACCESS_GROUPS', 'groups_access' );

/**
 * @var string GROUPS_ADMINISTER_GROUPS grants CRUD for groups (CRUD)
 */
define( 'GROUPS_ADMINISTER_GROUPS', 'groups_admin_groups');

/**
 * @var string GROUPS_ADMINISTER_OPTIONS grants to administer plugin options
 */
define( 'GROUPS_ADMINISTER_OPTIONS', 'groups_admin_options');

/**
 * @var string GROUPS_RESTRICT_ACCESS grants permission to restrict access on posts etc.
 */
define( 'GROUPS_RESTRICT_ACCESS', 'groups_restrict_access' );

/**
 * @var string GROUPS_ADMIN_GROUPS_NONCE admin nonce
 */
define( 'GROUPS_ADMIN_GROUPS_NONCE', 'groups-admin-nonce' );

/**
 * @var string GROUPS_ADMINISTRATOR_ACCESS_OVERRIDE admin override option
 *
 * @deprecated since 2.1.1
 */
define( 'GROUPS_ADMINISTRATOR_ACCESS_OVERRIDE', 'groups-admin-override' );

/**
 * @var string GROUPS_ADMINISTRATOR_ACCESS_OVERRIDE_DEFAULT admin override option default setting
 *
 * @deprecated since 2.1.1
 */
define( 'GROUPS_ADMINISTRATOR_ACCESS_OVERRIDE_DEFAULT', false );

/**
 * @var string GROUPS_READ_POST_CAPABILITIES read post capabilities option
 */
define( 'GROUPS_READ_POST_CAPABILITIES', 'groups-read-post-capabilities' );

/**
 * Tree view option
 *
 * @var string GROUPS_SHOW_TREE_VIEW
 */
define( 'GROUPS_SHOW_TREE_VIEW', 'groups-show-tree-view' );

/**
* Tree view option default.
*
* @var boolean GROUPS_SHOW_TREE_VIEW_DEFAULT
*/
define( 'GROUPS_SHOW_TREE_VIEW_DEFAULT', false );

/**
 * Option to show groups info in the user profile.
 *
 * @var string GROUPS_SHOW_IN_USER_PROFILE
 */
define( 'GROUPS_SHOW_IN_USER_PROFILE', 'groups-show-in-user-profile' );

/**
 * Default for showing groups in user profiles.
 *
 * @var boolean GROUPS_SHOW_IN_USER_PROFILE_DEFAULT
 */
define( 'GROUPS_SHOW_IN_USER_PROFILE_DEFAULT', true );

/**
 * Whether legacy functions should be supported.
 *
 * @var string GROUPS_LEGACY_ENABLE
 */
define( 'GROUPS_LEGACY_ENABLE', 'groups-legacy-enable' );

/**
 * Default value for legacy support.
 *
 * @var boolean GROUPS_LEGACY_ENABLE_DEFAULT
 */
define( 'GROUPS_LEGACY_ENABLE_DEFAULT', false );
