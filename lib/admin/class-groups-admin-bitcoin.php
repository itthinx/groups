<?php
/**
 * class-groups-admin-bitcoin.php
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
 * @since 4.0.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bitcoin
 */
class Groups_Admin_Bitcoin {

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
