<?php
/**
 * class-groups-wpbakery.php
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
 * @since groups 4.8.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPBakery Page Builder compatibility.
 */
class Groups_WPBakery {

	/**
	 * Register actions and filters.
	 *
	 * Note that we are already past the init action at this point.
	 */
	public static function boot() {
		add_filter( 'groups_shortcodes_validate_contents', array( __CLASS__, 'groups_shortcodes_validate_contents' ), 10, 4 );
	}

	/**
	 * Shortcode validation based on decoded content.
	 *
	 * Decodes WPBakery [vc_raw_html] / [vc_raw_js] blocks found in the raw
	 * content and appends their decoded text, so Groups_Shortcodes::validate()
	 * can find the literal [groups_member ...] / [groups_non_member ...] /
	 * [groups_can ...] / [groups_can_not ...] occurrences they contain.
	 *
	 * @see https://wordpress.org/support/topic/shortcode-validation-fails-in-widgets-after-v4-7-0-update-cve-2026-77203-fix/#post-19026100
	 *
	 * @param string $contents Contents considered for shortcode validation.
	 * @param string $tag Shortcode tag being validated.
	 * @param array $atts Shortcode attributes.
	 * @param string $content Shortcode content.
	 *
	 * @return string
	 */
	public static function groups_shortcodes_validate_contents( $contents, $tag, $atts, $content ) {
		$matches = null;
		if ( preg_match_all( '/\[vc_raw_(?:html|js)(?:\s[^\]]*)?\](.*?)\[\/vc_raw_(?:html|js)\]/s', $contents, $matches ) ) {
			foreach ( $matches[1] as $encoded ) {
				$decoded = rawurldecode( base64_decode( trim( $encoded ) ) );
				if ( is_string( $decoded ) && $decoded !== '' ) {
					$contents .= ' ' . $decoded;
				}
			}
		}
		return $contents;
	}

}

Groups_WPBakery::boot();
