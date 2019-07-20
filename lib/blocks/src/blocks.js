/**
 * blocks.js
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
 * @author Denitsa Slavcheva
 * @package groups
 * @since groups 2.8.0
 */

import './blocks/groups-member/block.js';
import './blocks/groups-non-member/block.js';

// Change the 'groups' category icon in the block editor.
wp.blocks.updateCategory(
	'groups',
	{
		icon : wp.element.createElement(
			'svg',
			{
				width  : 20,
				height : 20,
				viewBox: '0 0 20 20',
				fill: '#7392cf'

			},
			wp.element.createElement( 'path',
				{
					d:"M 5.14,2.85 C 5.11,2.85 5.09,2.87 5.09,2.90 5.09,2.93 5.11,2.96 5.14,2.96 5.17,2.96 5.19,2.93 5.19,2.90 5.19,2.87 5.17,2.85 5.14,2.85 Z M 1.53,1.64 C 1.49,1.64 1.45,1.68 1.45,1.72 1.45,1.76 1.49,1.80 1.53,1.80 1.58,1.80 1.61,1.76 1.61,1.72 1.61,1.68 1.58,1.64 1.53,1.64 Z M 4.17,1.24 C 4.09,1.24 4.03,1.30 4.03,1.37 4.03,1.44 4.09,1.50 4.17,1.50 4.24,1.50 4.30,1.44 4.30,1.37 4.30,1.30 4.24,1.24 4.17,1.24 Z M 16.67,16.32 C 16.55,16.32 16.45,16.41 16.45,16.53 16.45,16.65 16.55,16.74 16.67,16.74 16.79,16.74 16.88,16.65 16.88,16.53 16.88,16.41 16.79,16.32 16.67,16.32 Z M 3.97,4.68 C 3.78,4.68 3.63,4.83 3.63,5.02 3.63,5.21 3.78,5.37 3.97,5.37 4.16,5.37 4.32,5.21 4.32,5.02 4.32,4.83 4.16,4.68 3.97,4.68 Z M 13.28,8.84 C 12.97,8.84 12.72,9.09 12.72,9.40 12.72,9.71 12.97,9.96 13.28,9.96 13.59,9.96 13.84,9.71 13.84,9.40 13.84,9.09 13.59,8.84 13.28,8.84 Z M 16.63,11.50 C 16.13,11.50 15.73,11.90 15.73,12.40 15.73,12.90 16.13,13.30 16.63,13.30 17.13,13.30 17.53,12.90 17.53,12.40 17.53,11.90 17.13,11.50 16.63,11.50 Z M 8.57,1.55 C 7.26,1.55 6.21,2.60 6.21,3.91 6.21,5.21 7.26,6.27 8.57,6.27 9.87,6.27 10.93,5.21 10.93,3.91 10.93,2.60 9.87,1.55 8.57,1.55 Z M 15.30,1.07 C 13.19,1.07 11.48,2.78 11.48,4.89 11.48,7.00 13.19,8.71 15.30,8.71 17.41,8.71 19.12,7.00 19.12,4.89 19.12,2.78 17.41,1.07 15.30,1.07 Z M 7.25,6.76 C 3.84,6.76 1.07,9.52 1.07,12.94 1.07,16.35 3.84,19.12 7.25,19.12 10.66,19.12 13.43,16.35 13.43,12.94 13.43,9.52 10.66,6.76 7.25,6.76 Z" }
		)
	)
	}
);
