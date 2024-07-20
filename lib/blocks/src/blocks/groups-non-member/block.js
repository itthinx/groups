/**
 * groups-non-member/block.js
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
 * @author itthinx
 * @package groups
 * @since groups 2.8.0
 */

import Select from 'react-select';
import classnames from 'classnames';
import { registerStore, withSelect } from '@wordpress/data';
import { registerBlockType } from '@wordpress/blocks';
import { __, _x, _n, sprintf } from '@wordpress/i18n';
import { InspectorControls, InnerBlocks } from '@wordpress/block-editor';
import { PanelBody, PanelRow, Spinner } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { store } from '../store';

// Import CSS.
import './editor.scss';

/**
 * Icon for the Groups Non-member Block.
 */
const nonMemberIcon = wp.element.createElement(
	'svg',
	{
		width: 24,
		height: 24,
		viewBox: '0 0 24 24'
	},
	wp.element.createElement(
		'path',
		{
			d:"M 15.66,5.69 C 16.77,6.34 17.66,7.23 18.31,8.34 18.96,9.46 19.29,10.68 19.29,12.00 19.29,13.32 18.96,14.54 18.31,15.66 17.66,16.77 16.77,17.66 15.66,18.31 14.54,18.96 13.32,19.29 12.00,19.29 10.68,19.29 9.46,18.96 8.34,18.31 7.23,17.66 6.34,16.77 5.69,15.66 5.04,14.54 4.71,13.32 4.71,12.00 4.71,10.68 5.04,9.46 5.69,8.34 6.34,7.23 7.23,6.34 8.34,5.69 9.46,5.04 10.68,4.71 12.00,4.71 13.32,4.71 14.54,5.04 15.66,5.69 Z M 20.91,6.84 C 19.99,5.26 18.74,4.01 17.16,3.09 15.59,2.17 13.87,1.71 12.00,1.71 10.13,1.71 8.41,2.17 6.84,3.09 5.26,4.01 4.01,5.26 3.09,6.84 2.17,8.41 1.71,10.13 1.71,12.00 1.71,13.87 2.17,15.59 3.09,17.16 4.01,18.74 5.26,19.99 6.84,20.91 8.41,21.83 10.13,22.29 12.00,22.29 13.87,22.29 15.59,21.83 17.16,20.91 18.74,19.99 19.99,18.74 20.91,17.16 21.83,15.59 22.29,13.87 22.29,12.00 22.29,10.13 21.83,8.41 20.91,6.84 Z "
		}
	)
);

/**
 * Register: Groups-Non-member Gutenberg Block.
 *
 * Registers a new block provided a unique name and an object defining its
 * behavior. Once registered, the block is made editor as an option to any
 * editor interface where blocks are implemented.
 *
 * @link https://wordpress.org/gutenberg/handbook/block-api/
 * @param  {string}   name     Block name.
 * @param  {Object}   settings Block settings.
 * @return {?WPBlock}          The block, if it has been successfully
 *                             registered; otherwise `undefined`.
 */
registerBlockType(
	// Block name.
	// Block names must be string that contains a namespace prefix.
	// Example: my-plugin/my-custom-block.
	'groups/groups-non-member',
	{
		title: __( 'Groups Non-member','groups' ), // Block title.
		description: __( 'Hide content from group members', 'groups' ),
		icon: nonMemberIcon, // Block icon from Dashicons → https://developer.wordpress.org/resource/dashicons/.
		category: 'groups', // Block category — Group blocks together based on common traits E.g. common, formatting, layout widgets, embed.
		keywords: [ __( 'groups', 'groups' ), __( 'access', 'groups' ), __( 'members', 'groups' ) ],
		attributes: {
			groups_select: {
				type: 'string',
				default: null
			},
		},

		/**
		 * The edit function describes the structure of the block in the context of the editor.
		 * This represents what the editor will render when the block is used.
		 *
		 * The "edit" property must be a valid function.
		 *
		 * Use withSelect to inject state-derived props into a component.
		 *
		 * @link https://wordpress.org/gutenberg/handbook/block-api/block-edit-save/
		 */
		edit: withSelect(
			( select ) => {
				return {
					// Uses select() to return an object of the store's selectors. Pre-bound to pass the current state automatically.
					groups: select( 'groups/groups-blocks' ).receiveGroups(),
				};
			}
		)

		(
			props => {

				const {
					attributes: { groups_select },
					groups,
					className,
					setAttributes,
					isSelected
				} = props;

				const handleGroupsChange = ( groups_select ) => setAttributes(
					{ groups_select: JSON.stringify( groups_select ) }
				);

				let selectedGroups = [];

				if ( null !== groups_select ) {
					selectedGroups = JSON.parse( groups_select );
				}

				// Show if the data is not loaded yet.
				if ( ! groups.length ) {
					return (
						<p className={className}>
						<Spinner/>
						{ __( 'Loading...', 'groups' ) }
						</p>
					);
				}

				return [
					<InspectorControls>
						<PanelBody title={ __( 'Select Groups', 'groups' ) } className="block-inspector">
							<PanelRow>
								<label htmlFor="block-groups" className="groups-inspector__label">
									{ __( 'Content will be shown to users that are not members of these groups:', 'groups' ) }
								</label>
							</PanelRow>
							<PanelRow>
								<Select
									className = "groups-inspector__control"
									name      = 'block-groups'
									value     = { selectedGroups }
									onChange  = { handleGroupsChange }
									options   = { groups }
									isClearable
									isMulti   = 'true'
								/>
							</PanelRow>
						</PanelBody>
					</InspectorControls>,
					<div className={ isSelected ? ( classnames( className ) + '__selected' ) : props.className }>
						<div className={ classnames( className ) + '__inner-block' }>
							<InnerBlocks/>
						</div>
					</div>
				];
			}
		),

		/**
		 * The save function defines the way in which the different attributes should be combined
		 * into the final markup, which is then serialized by Gutenberg into post_content.
		 *
		 * The "save" property must be specified and must be a valid function.
		 *
		 * @link https://wordpress.org/gutenberg/handbook/block-api/block-edit-save/
		 */
		save: props => {
			return (
				<div>
					<InnerBlocks.Content/>
				</div>
			);
		},
	}
);
