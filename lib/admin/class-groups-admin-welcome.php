<?php
/**
 * class-groups-admin-welcome.php
 *
 * Copyright (c) 2017 "kento" Karim Rahimpur www.itthinx.com
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
 * @since groups 2.0.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Groups admin welcome and update screen.
 */
class Groups_Admin_Welcome {

	/**
	 * Adds actions to admin_menu, admin_head and admin_init.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_head', array( __CLASS__, 'admin_head' ) );
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'plugin_row_meta' ), 10, 2 );
		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
	}

	/**
	 * Adds the welcome screen to the dashboard menu.
	 */
	public static function admin_menu() {
		add_dashboard_page(
			__( 'Welcome to Groups', 'groups' ),
			__( 'Welcome to Groups', 'groups' ),
			'manage_options',
			'groups-welcome',
			array( __CLASS__, 'groups_welcome' )
		);
	}

	/**
	 * Removes the welcome screen from the dashboard menu.
	 */
	public static function admin_head() {
		remove_submenu_page( 'index.php', 'groups-welcome' );
	}

	/**
	 * Output admin notices.
	 *
	 * @since 3.3.1
	 */
	public static function admin_notices() {
		$is_updated_legacy = get_transient( 'groups_plugin_updated_legacy' );
		if ( $is_updated_legacy ) {
			$message = '<p>';
			$message .= '<strong>';
			$message .= esc_html__( 'Important', 'groups' );
			$message .= '</strong>';
			$message .= ' ';
			$message .= esc_html__( 'It seems that you have updated from Groups 1.x where access restrictions were based on capabilities.', 'groups' );
			$message .= '</p>';
			$message .= '<p>';
			$message .= sprintf(
				esc_html__( 'Please make sure to read the %1$sMigration Guide%2$s.', 'groups' ),
				'<a href="https://docs.itthinx.com/document/groups/migration-guide/">',
				'</a>'
			);
			$message .= '</p>';
			wp_admin_notice(
				$message,
				array(
					'type'               => 'info',
					'dismissible'        => true,
					'additional_classes' => array( 'inline', 'notice-alt' ),
					'attributes'         => array( 'data-slug' => 'plugin-slug' )
				)
			);
		}
	}

	/**
	 * Checks if the welcome screen should be shown and redirected to.
	 */
	public static function admin_init() {
		global $groups_version;
		if (
			Groups_User::current_user_can( GROUPS_ACCESS_GROUPS ) &&
			isset( $_GET['groups-welcome-dismiss'] ) &&
			isset( $_GET['_groups_welcome_nonce'] )
		) {
			if ( wp_verify_nonce( $_GET['_groups_welcome_nonce'], 'groups_welcome_dismiss' ) ) {
				Groups_Options::update_user_option( 'groups-welcome-dismiss', $groups_version );
			}
		}
		$groups_welcome_dismiss = Groups_Options::get_user_option( 'groups-welcome-dismiss', '' );
		if ( version_compare( $groups_version, $groups_welcome_dismiss ) > 0 ) {
			// @see Groups_Controller::activate()
			if ( get_transient( 'groups_plugin_activated' ) ) {
				$doing_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;
				$doing_cron = defined( 'DOING_CRON' ) && DOING_CRON;
				// we'll delete the transients in the welcome screen handler
				if (
					!$doing_ajax &&
					!$doing_cron &&
					( empty( $_GET['page'] ) || $_GET['page'] !== 'groups-welcome' ) &&
					!is_network_admin() &&
					Groups_User::current_user_can( GROUPS_ACCESS_GROUPS ) &&
					apply_filters( 'groups_welcome_show', true )
				) {
					wp_safe_redirect( admin_url( 'index.php?page=groups-welcome' ) );
					exit;
				} else {
					// @since 3.3.1 remove the transient as we don't want to trigger a redirect in any other case than direct activation of the Groups plugin
					delete_transient( 'groups_plugin_activated' );
				}
			}
		}
	}

	/**
	 * Adds an entry leading to the welcome screen.
	 *
	 * @param array $links
	 * @param string $file plugin file basename
	 * @return array
	 */
	public static function plugin_row_meta( $links, $file ) {
		if ( $file == plugin_basename( GROUPS_FILE ) ) {
			$row_meta = array(
				'welcome' => sprintf(
					'<a href="%s" title="%s">%s</a>',
					esc_url( admin_url( 'index.php?page=groups-welcome' ) ),
					esc_attr__( 'View the Welcome screen for this version of Groups', 'groups' ),
					esc_html__( 'Welcome', 'groups' )
				)
			);
			return array_merge( $links, $row_meta );
		}
		return (array) $links;
	}

	/**
	 * Renders the welcome screen.
	 */
	public static function groups_welcome() {

		global $groups_version;

		wp_enqueue_style( 'groups_admin' );

		delete_transient( 'groups_plugin_activated' );
		// As of Groups 3.3.1 this transient does not trigger a redirect to the welcome screen.
		// Instead, an admin notice pointing to the migration guide is displayed for as long as the transient has not expired.
		// We still delete the transient here as we are displaying the welcome screen, and the notice will be visible on top of it.
		delete_transient( 'groups_plugin_updated_legacy' );

		echo '<div class="groups-welcome-panel">';
		echo '<div class="groups-welcome-panel-content">';

		echo '<div class="groups-welcome-panel-description">';

		printf( '<img class="groups-welcome-icon" width="64" height="64" src="%s"/>', esc_attr( GROUPS_PLUGIN_URL . 'images/groups-256x256.png' ) );

		echo '<h1>';
		printf( esc_html__( 'Welcome to Groups %s', 'groups' ), esc_html( $groups_version ) );
		echo '</h1>';

		printf(
			'<a class="notice-dismiss" href="%s" title="%s" aria-label="%s"></a>',
			esc_url( wp_nonce_url( add_query_arg( 'groups-welcome-dismiss', '1', admin_url() ), 'groups_welcome_dismiss', '_groups_welcome_nonce' ) ),
			esc_attr__( 'Dismiss', 'groups' ),
			esc_html__( 'Dismiss', 'groups' )
		);

		echo '<p class="headline">';
		esc_html_e( 'Thanks for using Groups! We have made it even easier to protect your content and hope you like it :)', 'groups' );
		echo '</p>';

		echo '<h2>';
		esc_html_e( "What's New?", 'groups' );
		echo '</h2>';

		echo '<h3>';
		esc_html_e( 'Protect Content Easily', 'groups' );
		echo '</h3>';
		echo '<p>';
		esc_html_e( 'We have made it even easier to protect your content!', 'groups' );
		echo ' ';
		esc_html_e( 'Now you can protect your posts, pages and any other custom post type like products or events by simply assigning them to one or more groups.', 'groups' );
		echo '</p>';

		echo '<h3>';
		esc_html_e( 'Efficient User Interface', 'groups' );
		echo '</h3>';
		echo '<p>';
		esc_html_e( 'Manage groups and users with a minimal footprint on the administrative screens.', 'groups' );
		echo '</p>';

		echo '<h3>';
		esc_html_e( 'Documentation', 'groups' );
		echo '</h3>';
		echo '<p>';
		printf(
			/* translators: 1: opening tag, 2: closing tag */
			esc_html__( 'Whether you are new to Groups or have been using it before, please make sure to visit the %1$sDocumentation%2$s pages to know more about how to use it.', 'groups' ),
			'<a target="_blank" href="https://docs.itthinx.com/document/groups/">',
			'</a>'
		);
		echo '</p>';

		echo '</div>'; // .groups-welcome-panel-description

		echo '<h2>';
		esc_html_e( 'Add-Ons', 'groups' );
		echo '</h2>';
		echo '<p>';
		esc_html_e( 'Perfect complements to memberships and access control with Groups.', 'groups' );
		echo '</p>';
		echo '<div class="groups-admin-add-ons">';
		groups_admin_add_ons_content( array( 'offset' => 1 ) );
		echo '</div>'; // .groups-admin-add-ons

		echo '</div>'; // .groups-welcome-panel-content
		echo '</div>'; // .groups-welcome-panel
	}
}
Groups_Admin_Welcome::init();
