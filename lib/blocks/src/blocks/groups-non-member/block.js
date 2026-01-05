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
 * @author Karim Rahimpur
 * @author Denitsa Slavcheva
 * @author itthinx
 * @package groups
 * @since groups 2.8.0
 */

import Select from 'react-select';
import { useSelect } from '@wordpress/data';
import { registerBlockType, createBlock } from '@wordpress/blocks';
import { __, _x, _n } from '@wordpress/i18n';
import { InspectorControls, InnerBlocks } from '@wordpress/block-editor';
import { PanelBody, PanelRow, Spinner } from '@wordpress/components';
import { useBlockProps } from '@wordpress/block-editor';
import { store } from '../store'; // IMPORTANT! This MUST be imported for select().receiveGroups()

// Import the stylesheet for the Groups Non-member block to use in the block editor
import './editor.scss';

// Define the icon for the Groups Non-member block
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

// Register the Groups Non-member block
//
// https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/#registerblocktype
//
registerBlockType(
	'groups/groups-non-member',
	{
		apiVersion: 3,
		title: __( 'Groups Non-member','groups' ), // block title
		description: __( 'Hide content from group members', 'groups' ), // block description
		icon: nonMemberIcon, // block icon
		category: 'groups', // block category
		keywords: [
			__( 'access', 'groups' ),
			__( 'block', 'groups' ),
			__( 'blocks', 'groups' ),
			__( 'content', 'groups' ),
			__( 'control', 'groups' ),
			__( 'from', 'groups' ),
			__( 'group', 'groups' ),
			__( 'groups', 'groups' ),
			__( 'hide', 'groups' ),
			__( 'member', 'groups' ),
			__( 'members', 'groups' ),
			__( 'membership', 'groups' ),
			__( 'memberships', 'groups' ),
			__( 'only', 'groups' ),
			__( 'protect', 'groups' ),
			__( 'restrict', 'groups' ),
			__( 'show', 'groups' ),
			__( 'to', 'groups' ),
			__( 'visibility', 'groups' ),
			__( 'visible', 'groups' ),
			__( 'user', 'groups' ),
		],
		attributes: {
			groups_select: {
				type: 'string',
				default: null,
			},
		},
		transforms: {
			to: [
				{
					type: 'block',
					blocks: [ 'groups/groups-member' ],
					transform: ( attributes, innerBlocks ) => {
						return createBlock(
							'groups/groups-member',
							attributes,
							innerBlocks
						);
					},
				},
			],
		},

		// renders the block's editor view and its controls
		edit: ( props ) => {
			const blockProps = useBlockProps();
			const groups_select = props.attributes.groups_select;
			const groups = useSelect(
				( select ) => {
					return select( 'groups/groups-blocks' ).receiveGroups();
				}
			);
			const handleGroupsChange = ( groups_select ) => props.setAttributes(
				{ groups_select: JSON.stringify( groups_select ) }
			);
			let selectedGroups = [];
			if ( null !== groups_select ) {
				try {
					selectedGroups = JSON.parse( groups_select );
				} catch ( error ) {
				}
			}
			const panelContent = Array.isArray( groups ) ?
				(
					groups.length > 0 ?
					<Select
						className = 'groups-non-member-block-inspector-select'
						name      = 'block-groups-non-member'
						value     = { selectedGroups }
						onChange  = { handleGroupsChange }
						options   = { groups }
						isClearable
						isMulti   = 'true'
					/> :
					<p>{ __( 'You cannot set any access restrictions.', 'groups' ) }</p>
				) :
				<div>
					<p>
						<Spinner/> { __( 'Loading', 'groups' ) } &hellip;
					</p>
				</div>;

			return (
				<>
					<InspectorControls>
						<PanelBody title={ __( 'Select Groups', 'groups' ) } className="block-inspector">
							<PanelRow>
								<label htmlFor="block-groups-non-member" className="groups-inspector__label">
									{ __( 'Content will be shown to users that are not members of these groups:', 'groups' ) }
								</label>
							</PanelRow>
							<PanelRow>
							{ panelContent }
							</PanelRow>
						</PanelBody>
					</InspectorControls>
					<div {...blockProps}>
						<div className="groups-non-member-block-editor-inside">
							<InnerBlocks/>
						</div>
					</div>
				</>
			);
		},

		// renders the block's frontend view
		save: ( props ) => {
			return (
				<div>
					<InnerBlocks.Content/>
				</div>
			);
		},

		// defines a preview of the block
		example: {
			innerBlocks: [
				{
					name: 'core/paragraph',
					attributes: {
						content: 'Lorem ipsum verba habebant, sed verba significationem non habebant. Sententia autem nihil curabat utrum verba eam haberent necne.',
					},
				},
			],
		},
	}
);
