<?php
/**
 * interface-i-capable.php
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
 * Capable interface OPM.
 */
interface I_Capable {

	/**
	 * Finds out if I have the given capability.
	 *
	 * @since 3.0.0 the optional $object parameter has been added (experimental)
	 * @since 3.0.0 the optional $type parameter has been added (experimental)
	 *
	 * @param string|int $capability capability or capability id
	 * @param mixed|null $object (experimental, declaration might change)
	 * @param mixed|null $args (experimental, declaration might change)
	 *
	 * @return true if I can, otherwise false
	 */
	public function can( $capability, $object = null, $args = null );
}
