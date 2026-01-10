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
		// @since 3.1.0 make sure the class and method exists, in case script load order and action triggers conflict
		if ( class_exists( 'Groups_User' ) && method_exists( 'Groups_User', 'current_user_can' ) ) {
			if ( Groups_User::current_user_can( 'activate_plugins' ) ) {
				$user_id = get_current_user_id();
				if ( !empty( $_GET[self::HIDE_REVIEW_NOTICE] ) && wp_verify_nonce( $_GET['groups_notice'], 'hide' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					add_user_meta( $user_id, self::HIDE_REVIEW_NOTICE, true );
				}
				if ( !empty( $_GET[self::REMIND_LATER_NOTICE] ) && wp_verify_nonce( $_GET['groups_notice'], 'later' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
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
	}

	/**
	 * Initializes if necessary and returns the init time.
	 *
	 * @return int
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

		$current_url = groups_get_current_url();
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
		$output .= wp_kses_post( __( 'Many thanks for using <strong>Groups</strong>!', 'groups' ) );
		$output .= ' ';
		$output .= esc_html__( 'Could you please spare a minute and give it a review over at WordPress.org?', 'groups' );
		$output .= ' ';
		$output .= sprintf(
			'<a title="%s" style="color:inherit;white-space:nowrap;cursor:help;opacity:0.5;" href="%s">%s</a>',
			esc_attr__( 'I have already done that or do not want to submit a review.', 'groups' ),
			esc_url( $hide_url ),
			esc_html__( 'Dismiss', 'groups' )
		);
		$output .= '</p>';
		$output .= '<p>';
		$output .= sprintf(
			'<a title="%s" class="button button-primary" href="%s" target="_blank">%s</a>',
			esc_attr__( 'I want to submit a review right now!', 'groups' ),
			esc_url( 'https://wordpress.org/support/view/plugin-reviews/groups?filter=5#postform' ),
			esc_html__( 'Yes, here we go!', 'groups' )
		);
		$output .= '&emsp;';
		$output .= sprintf(
			'<a title="%s" class="button" href="%s">%s</a>',
			esc_attr__( 'I want to submit a review later, remind me!', 'groups' ),
			esc_url( $remind_url ),
			esc_html__( 'Remind me later', 'groups' )
		);

		$output .= '</p>';
		$output .= '<p>';
		$output .= sprintf(
			/* translators: 1: link, 2: link */
			__( 'Follow %1$s and visit %2$s to stay tuned for free and premium tools.', 'groups' ),
			'<a href="https://x.com/itthinx" target="_blank">@itthinx</a>',
			'<a href="https://www.itthinx.com" target="_blank">itthinx.com</a>'
		);
		$output .= '</p>';
		$output .= '</div>';

		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Provide the Bitcoin box markup.
	 *
	 * @return string
	 */
	public static function get_groups_bitcoin_box( $params = null ) {
		$type = '';
		$where = '';
		if ( is_array( $params ) ) {
			if ( isset( $params['type'] ) ) {
				switch ( $params['type'] ) {
					case 'vertical':
					case 'horizontal':
						$type = $params['type'];
						break;
				}
			}
			if ( isset( $params['where'] ) ) {
				switch ( $params['where'] ) {
					case 'options':
					case 'add-ons':
						$where = $params['where'];
						break;
				}
			}
		}
		$bitcoin_address = 'bc1q7klf9ge8gvtl4h4qlh6c73tgk0cdehfv5vcq0g';
		$bitcoin_box = sprintf( '<div id="groups-bitcoin-box" class="groups-bitcoin-box %s %s">', esc_attr( $type ), esc_attr( $where ) );
		$bitcoin_box .= '<div class="groups-bitcoin-box-inside">';
		$bitcoin_box .= '<div class="groups-bitcoin-cta">';
		$bitcoin_box .= esc_html__( 'Contribute Bitcoin to Groups', 'groups' );
		$bitcoin_box .= '</div>'; // .groups-bitcoin-cta
		$bitcoin_box .= '<div class="groups-bitcoin-image">';
		$bitcoin_box .= sprintf( '<img class="groups-bitcoin-address-image" src="%s"/>', esc_url( GROUPS_PLUGIN_URL .'images/groups-bitcoin.png' ) );
		$bitcoin_box .= '</div>'; // .groups-bitcoin-image
		$bitcoin_box .= '<div class="groups-bitcoin-description">';
		$bitcoin_box .= sprintf(
			/* translators: HTML */
			esc_html__( 'Support the developers and contribute Bitcoin to %s', 'groups' ),
			'<span class="groups-bitcoin-address">' . esc_html( $bitcoin_address ) . '</span>' .
			'&ensp;' .
			'<span class="dashicons dashicons-admin-page pseudo-copy-icon" style="display:none"></span>'
		);
		$bitcoin_box .= '</div>'; // .groups-bitcoin-description
		$bitcoin_box .= '</div>'; // .groups-bitcoin-box-inside
		$bitcoin_box .= '</div>'; // .groups-bitcoin-box
		$bitcoin_box .= '<script type="text/javascript">';
		$bitcoin_box .= 'if ( typeof groups_bitcoin_box_address === "undefined" ) {';
		$bitcoin_box .= 	'function groups_bitcoin_box_address( event ) {';
		$bitcoin_box .= 		'if ( navigator.clipboard && window.isSecureContext ) {';
		$bitcoin_box .= 			'try {';
		$bitcoin_box .= 				'navigator.clipboard.writeText("' . esc_html( $bitcoin_address ) .'");';
		$bitcoin_box .= 				'jQuery( event.target ).closest( ".groups-bitcoin-box-inside" ).fadeOut( 100 ).fadeIn( 100 );';
		$bitcoin_box .= 			'} catch (error) {';
		$bitcoin_box .= 			'}';
		$bitcoin_box .= 		'} else if ( document.queryCommandEnabled && document.queryCommandEnabled( "copy" ) ) {';
		$bitcoin_box .= 			'let tmp = document.createElement("textarea");';
		$bitcoin_box .= 			sprintf( 'tmp.value="%s";', $bitcoin_address );
		$bitcoin_box .= 			'tmp.setAttribute("style","display:none");';
		$bitcoin_box .= 			'tmp.select();';
		$bitcoin_box .= 			'try {';
		$bitcoin_box .= 				'document.execCommand( "copy" );';
		$bitcoin_box .= 				'jQuery( event.target ).closest( ".groups-bitcoin-box-inside" ).fadeOut( 100 ).fadeIn( 100 );';
		$bitcoin_box .= 			'} catch ( error ) {';
		$bitcoin_box .= 			'} finally {';
		$bitcoin_box .= 				'tmp.remove();';
		$bitcoin_box .= 			'}';
		$bitcoin_box .= 		'}';
		$bitcoin_box .= 	'}';
		$bitcoin_box .= 	'document.addEventListener( "DOMContentLoaded", function() {';
		$bitcoin_box .= 		'if ( typeof jQuery !== "undefined" ) {';
		$bitcoin_box .= 			'if ( navigator.clipboard && window.isSecureContext || document.queryCommandEnabled && document.queryCommandEnabled( "copy" ) ) {';
		$bitcoin_box .= 				'jQuery( ".groups-bitcoin-box" ).click( groups_bitcoin_box_address );';
		$bitcoin_box .= 				'jQuery( ".groups-bitcoin-box" ).css( "cursor", "pointer" );';
		$bitcoin_box .= 				'jQuery( ".groups-bitcoin-box .pseudo-copy-icon" ).css( "display", "inline" );';
		$bitcoin_box .= 			'} else {';
		$bitcoin_box .= 				'jQuery( ".groups-bitcoin-box .dashicons.dashicons-admin-page" ).remove();';
		$bitcoin_box .= 			'}';
		$bitcoin_box .= 		'}';
		$bitcoin_box .= 	'} );'; // DOMContentLoaded
		$bitcoin_box .= '}'; // typeof
		$bitcoin_box .= '</script>';
		return $bitcoin_box;
	}
}
Groups_Admin_Notice::init();
