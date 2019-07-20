/**
 * groups-member/block.js
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

import Select from 'react-select';
import classnames from 'classnames';

// Import CSS.
import './editor.scss';

const
{
	apiFetch
} = wp;
const
{
	__
} = wp.i18n; // Import __() from wp.i18n.
const
{
	registerStore,
	withSelect
} = wp.data; // Import registerStore, withSelect from wp.data.
const
{
	registerBlockType
} = wp.blocks; // Import registerBlockType() from wp.blocks.
const
{
	InspectorControls,
	InnerBlocks
} = wp.editor; // Import InspectorControls, InnerBlocks from wp.editor.
const
{
	PanelBody,
	PanelRow,
	Spinner
} = wp.components; // Import PanelBody, SelectControl from wp.components.

// Default state - no groups selected.
const DEFAULT_STATE = {
	groups:
	{},
};
/**
 * Actions object. Actions are payloads of information that send data from the application to the store. Plain JavaScript objects.
 */
const actions = {
	// Action creator for the action called when settng a group by choosing from the options list.
	setGroups( groups )
	{
		return {
			type: 'SET_GROUPS',
			groups,
		};
	},

	receiveGroups( path )
	{
		return {
			type: 'RECEIVE_GROUPS',
			path,
		};
	},
};

/**
 * Store
 */
const store = registerStore(
	'groups/groups-blocks',
	{
		// The reducer is a pure function that takes the previous state and an action, and returns the next state.
		reducer( state = DEFAULT_STATE, action )
		{
			switch ( action.type )
			{
			case 'SET_GROUPS':
				return {
					// To keep the reducer pure, state should not be mutated.
					// Use object state operator to copy enumerabale properties into a new object instead of Object.assign().
					...state,
					groups: action.groups,
				};
			}
			// Return the previous state - for when there's an unknown action.
			return state;
		},

		actions,
		// A selector accepts state and optional arguments and returns some value from state.
		selectors:
		{
			receiveGroups( state )
			{
				const
				{
					groups
				} = state;
				return groups;
			},
		},
		// Defines the execution flow behavior associated with a specific action type.
		controls:
		{
			RECEIVE_GROUPS( action )
			{
				return apiFetch(
				{
					path: action.path
				} );
			},
		},
		// Side-effects for a selector. Used with data from an extrnal source.
		resolvers:
		{
			* receiveGroups( state )
			{
				const groups = yield actions.receiveGroups( '/groups/groups-blocks/groups/' );
				return actions.setGroups( groups );
			},
		},
	}
);


// SVG for the block icon.
const memberIcon = wp.element.createElement( 'svg',
	{
		width: 24,
		height: 24,
		viewBox: '0 0 24 24'
	},
	wp.element.createElement( 'path',
	{
		d: "M 14.42,9.58 C 13.75,8.91 12.95,8.57 12.00,8.57 11.05,8.57 10.25,8.91 9.58,9.58 8.91,10.25 8.57,11.05 8.57,12.00 8.57,12.95 8.91,13.75 9.58,14.42 10.25,15.09 11.05,15.43 12.00,15.43 12.95,15.43 13.75,15.09 14.42,14.42 15.09,13.75 15.43,12.95 15.43,12.00 15.43,11.05 15.09,10.25 14.42,9.58 Z M 15.66,5.69 C 16.77,6.34 17.66,7.23 18.31,8.34 18.96,9.46 19.29,10.68 19.29,12.00 19.29,13.32 18.96,14.54 18.31,15.66 17.66,16.77 16.77,17.66 15.66,18.31 14.54,18.96 13.32,19.29 12.00,19.29 10.68,19.29 9.46,18.96 8.34,18.31 7.23,17.66 6.34,16.77 5.69,15.66 5.04,14.54 4.71,13.32 4.71,12.00 4.71,10.68 5.04,9.46 5.69,8.34 6.34,7.23 7.23,6.34 8.34,5.69 9.46,5.04 10.68,4.71 12.00,4.71 13.32,4.71 14.54,5.04 15.66,5.69 Z M 20.91,6.84 C 19.99,5.26 18.74,4.01 17.16,3.09 15.59,2.17 13.87,1.71 12.00,1.71 10.13,1.71 8.41,2.17 6.84,3.09 5.26,4.01 4.01,5.26 3.09,6.84 2.17,8.41 1.71,10.13 1.71,12.00 1.71,13.87 2.17,15.59 3.09,17.16 4.01,18.74 5.26,19.99 6.84,20.91 8.41,21.83 10.13,22.29 12.00,22.29 13.87,22.29 15.59,21.83 17.16,20.91 18.74,19.99 19.99,18.74 20.91,17.16 21.83,15.59 22.29,13.87 22.29,12.00 22.29,10.13 21.83,8.41 20.91,6.84 Z "
	} )
);

/**
 * Register: Groups Member Gutenberg Block.
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
	'groups/groups-member',
	{
		// Block name. Block names must be string that contains a namespace prefix. Example: my-plugin/my-custom-block.
		title: __( 'Groups Member Block', 'groups' ), // Block title.
		description: __( 'Show content for group members', 'groups' ),
		icon: memberIcon, // Block icon from Dashicons → https://developer.wordpress.org/resource/dashicons/.
		category: 'groups', // Block category — Group blocks together based on common traits E.g. common, formatting, layout widgets, embed.
		keywords: [ __( 'groups', 'groups' ), __( 'access', 'groups' ), __( 'members', 'groups' ) ],
		attributes:
		{
			groups_select:
			{
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
		 * @link https://wordpress.org/gutenberg/handbook/block-api/block-edit-save/
		 */

		// Use withSelect to inject state-derived props into a component.
		edit: withSelect(
			( select ) =>
			{
				return {
					// Uses select() to return an object of the store's selectors. Pre-bound to pass the current state automatically.
					groups: select( 'groups/groups-blocks' ).receiveGroups(),
				};
			}
		)

		(
			props =>
			{
				const
				{
					attributes:
					{
						groups_select
					},
					groups,
					className,
					setAttributes,
					isSelected
				} = props;
				const handleGroupsChange = ( groups_select ) => setAttributes(
				{
					groups_select: JSON.stringify( groups_select )
				} );
				let selectedGroups = [];
				if ( null !== groups_select )
				{
					selectedGroups = JSON.parse( groups_select );
				}
				// Show if the data is not loaded yet.
				if ( !groups.length )
				{
					return ( <
						p className = {
							className
						} >
						<
						Spinner / >
						{
							__( 'Loading...', 'groups' )
						} <
						/ p >
					);
				}

				return [ <
					InspectorControls >
					<
					PanelBody title = {
						__( 'Select Groups', 'groups' )
					}
					className = "block-inspector" >
					<
					PanelRow >
					<
					label htmlFor = "block-groups"
					className = "groups-inspector__label" >
					{
						__( 'Content will be shown to users that are members of these groups:', 'groups' )
					} <
					/ label > <
					/ PanelRow > <
					PanelRow >
					<
					Select
					className = "groups-inspector__control"
					name = 'block-groups'
					value = {
						selectedGroups
					}
					onChange = {
						handleGroupsChange
					}
					options = {
						groups
					}
					isClearable
					isMulti = 'true' /
					>
					<
					/ PanelRow > <
					/ PanelBody > <
					/ InspectorControls > , <
					div className = {
						isSelected ? ( classnames( className ) + '__selected' ) : props.className
					} >
					<
					div className = {
						classnames( className ) + '__inner-block'
					} >
					<
					InnerBlocks / >
					<
					/ div > <
					/ div >
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
		save: props =>
		{
			return ( <
				div >
				<
				InnerBlocks.Content / >
				<
				/ div >
			);
		},
	}
);
