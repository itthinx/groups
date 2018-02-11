<?php
/**
 * class-groups-admin-notice.php
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
 * @since 2.2.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Notices
 */
class Groups_Admin_Notice {

	/**
	 * Time mark.
	 * 
	 * @var string
	 */
	const INIT_TIME = 'groups-init-time';

	/**
	 * Used to store user meta and hide the notice asking to review.
	 * 
	 * @var string
	 */
	const HIDE_REVIEW_NOTICE = 'groups-hide-review-notice';

	/**
	 * Used to check next time.
	 *
	 * @var string
	 */
	const REMIND_LATER_NOTICE = 'groups-remind-later-notice';

	/**
	 * The number of seconds in seven days, since init date to show the notice.
	 * 
	 * @var int
	 */
	const SHOW_LAPSE = 604800;

	/**
	 * The number of seconds in one day, used to show notice later again.
	 *
	 * @var int
	 */
	const REMIND_LAPSE = 86400;

	/**
	 * Adds actions.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__,'admin_init' ) );
	}

	/**
	 * Hooked on the admin_init action.
	 */
	public static function admin_init() {
		if ( current_user_can( 'activate_plugins' ) ) {
			$user_id = get_current_user_id();
			if ( !empty( $_GET[self::HIDE_REVIEW_NOTICE] ) && wp_verify_nonce( $_GET['groups_notice'], 'hide' ) ) {
				add_user_meta( $user_id, self::HIDE_REVIEW_NOTICE, true );
			}
			if ( !empty( $_GET[self::REMIND_LATER_NOTICE] ) && wp_verify_nonce( $_GET['groups_notice'], 'later' ) ) {
				update_user_meta( $user_id, self::REMIND_LATER_NOTICE, time() + self::REMIND_LAPSE );
			}
			$hide_review_notice = get_user_meta( $user_id, self::HIDE_REVIEW_NOTICE, true );
			if ( empty( $hide_review_notice ) ) {
				$d = time() - self::get_init_time();
				if ( $d >= self::SHOW_LAPSE ) {
					$remind_later_notice = get_user_meta( $user_id, self::REMIND_LATER_NOTICE, true );
					if ( empty( $remind_later_notice ) || ( time() > $remind_later_notice ) ) {
						add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
					}
				}
			}
		}
	}

	/**
	 * Initializes if necessary and returns the init time.
	 */
	public static function get_init_time() {
		$init_time = get_site_option( self::INIT_TIME, null );
		if ( $init_time === null ) {
			$init_time = time();
			add_site_option( self::INIT_TIME, $init_time );
		}
		return $init_time;
	}

	/**
	 * Adds the admin notice.
	 */
	public static function admin_notices() {

		$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$hide_url    = wp_nonce_url( add_query_arg( self::HIDE_REVIEW_NOTICE, true, $current_url ), 'hide', 'groups_notice' );
		$remind_url  = wp_nonce_url( add_query_arg( self::REMIND_LATER_NOTICE, true, $current_url ), 'later', 'groups_notice' );

		$output = '';

		$output .= '<style type="text/css">';
		$output .= 'div.groups-rating {';
		$output .= sprintf( 'background: url(%s) #fff no-repeat 8px 8px;', GROUPS_PLUGIN_URL . '/images/groups-256x256.png' );
		$output .= 'padding-left: 76px ! important;';
		$output .= 'background-size: 64px 64px;';
		$output .= '}';
		$output .= '</style>';

		$output .= '<div class="updated groups-rating">';
		$output .= '<p>';
		$output .= __( 'Many thanks for using <strong>Groups</strong>!', 'groups' );
		$output .= ' ';
		$output .= __( 'Could you please spare a minute and give it a review over at WordPress.org?', 'groups' );
		$output .= ' ';
		$output .= sprintf(
			'<a style="color:inherit;white-space:nowrap;" href="%s">%s</a>',
			esc_url( $hide_url ),
			esc_html( __( 'I have already done that.', 'groups' ) )
		);
		$output .= '</p>';
		$output .= '<p>';
		$output .= sprintf(
			'<a class="button button-primary" href="%s" target="_blank">%s</a>',
			esc_url( 'http://wordpress.org/support/view/plugin-reviews/groups?filter=5#postform' ),
			__( 'Yes, here we go!', 'groups' )
		);
		$output .= '&emsp;';
		$output .= sprintf(
			'<a class="button" href="%s">%s</a>',
			esc_url( $remind_url ),
			esc_html( __( 'Remind me later', 'groups' ) )
		);

		$output .= '</p>';
		$output .= '<p>';
		$output .= sprintf(
			__( 'You can also follow <a href="%s">@itthinx</a> on Twitter or visit <a href="%s" target="_blank">itthinx.com</a> to check out other free and premium plugins we provide.', 'groups' ),
			esc_url( 'https://twitter.com/itthinx' ),
			esc_url( 'https://www.itthinx.com' )
		);
		$output .= '</p>';
		$output .= '</div>';

		echo $output;
	}
}
Groups_Admin_Notice::init();
