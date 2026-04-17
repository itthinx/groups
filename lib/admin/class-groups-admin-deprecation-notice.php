<?php
/**
 * class-groups-admin-deprecation-notice.php
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
 * @author itthinx
 * @package groups
 * @since 4.2.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Deprecation Notices.
 */
class Groups_Admin_Deprecation_Notice {

	/**
	 * @var int
	 */
	const PRIORITY = 10000;

	/**
	 * @var int
	 */
	const PERIOD = 432000;

	/**
	 * Adds actions.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__,'admin_init' ), self::PRIORITY );
		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ), self::PRIORITY );
		if ( is_multisite() ) {
			add_action( 'network_admin_notices', array( __CLASS__, 'admin_notices' ), self::PRIORITY );
		}
	}

	/**
	 * Hooked on the admin_init action.
	 */
	public static function admin_init() {
		if ( class_exists( 'Groups_User' ) && method_exists( 'Groups_User', 'current_user_can' ) ) {
			if ( Groups_User::current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
				if ( isset( $_GET['groups-deprecation-notice-dismiss'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					if ( groups_verify_get_nonce( 'groups-deprecation-notice-nonce', 'groups-deprecation-notice' ) ) {
						set_transient('groups-deprecation-notice-timestamp', time(), self::PERIOD );
						$current_url = groups_get_current_url();
						$current_url = remove_query_arg( array( 'groups-deprecation-notice-dismiss', 'groups-deprecation-notice-nonce' ), $current_url );
						wp_safe_redirect( $current_url );
						exit;
					}
				}
			}
		}
	}

	/**
	 * Hooked on the admin_notices and network_admin_notices actions.
	 */
	public static function admin_notices() {
		// make sure the class and method exists, in case script load order and action triggers conflict
		if ( class_exists( 'Groups_User' ) && method_exists( 'Groups_User', 'current_user_can' ) ) {
			if ( Groups_User::current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
				// check if legacy enabled, otherwise skip notice
				$groups_legacy_enable = Groups_Options::get_option( GROUPS_LEGACY_ENABLE, GROUPS_LEGACY_ENABLE_DEFAULT );
				if ( $groups_legacy_enable ) {
					$show = true;
					$timestamp = get_transient( 'groups-deprecation-notice-timestamp' );
					if ( is_numeric( $timestamp ) ) {
						$dt = time() - intval( $timestamp );
						if ( $dt < self::PERIOD ) {
							$show = false;
						}
					}
					if ( $show ) {
						echo '<div class="error">';
						echo '<h2>';
						echo esc_html__( 'Groups', 'groups' );
						echo sprintf(
							'<a style="text-decoration:none; color:inherit; text-align:right; float:right;" href="%s" title="%s" aria-label="%s"><span class="dashicons dashicons-dismiss"></span></a>',
							esc_url( wp_nonce_url( add_query_arg( 'groups-deprecation-notice-dismiss', '1', admin_url() ), 'groups-deprecation-notice', 'groups-deprecation-notice-nonce' ) ),
							esc_attr__( 'Remind me later', 'groups' ),
							esc_html__( 'Remind me later', 'groups' )
						);
						echo '</h2>';
						echo '<p>';
						echo '<strong>';
						echo esc_html__( 'Legacy access control based on capabilities is enabled.', 'groups' );
						echo ' ';
						echo esc_html__( 'Support for this deprecated feature will soon be removed.', 'groups' );
						echo '</strong>';
						echo '</p>';
						echo '<p>';
						echo esc_html__( 'It is important to switch to access restrictions based on groups, before support for legacy access control based on capabilities is completely removed.', 'groups' );
						echo '</p>';
						echo '<p>';
						echo sprintf(
							/* translators: documentation pages link */
							esc_html__( 'Please refer to the %s for details on how to switch to and use the new access restrictions.', 'groups' ),
							sprintf( '<a target="_blank" href="https://docs.itthinx.com/document/groups/">%s</a>', esc_html__( 'Documentation', 'groups' ) )
						);
						echo '</p>';
						echo '</div>';
					}
				}
			}
		}
	}

}

Groups_Admin_Deprecation_Notice::init();
