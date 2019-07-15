/**
 * BLOCK: groups-shortcodes
 */

// import React Select2.
import Select from 'react-select';
import classnames from 'classnames';

// Import CSS.
//import './style.scss';
import './editor.scss';

const { apiFetch }                       = wp;
const { __ }                             = wp.i18n; // Import __() from wp.i18n.
const { registerStore, withSelect }      = wp.data; // Import registerStore, withSelect from wp.data.
const { registerBlockType }              = wp.blocks; // Import registerBlockType() from wp.blocks.
const { InspectorControls, InnerBlocks } = wp.editor; // Import InspectorControls, InnerBlocks from wp.editor.
const { PanelBody, PanelRow, Spinner }   = wp.components; // Import PanelBody, SelectControl from wp.components.

// Default state - no groups selected.
const DEFAULT_STATE = {
	groups: {},
};
/**
 * Actions object. Actions are payloads of information that send data from the application to the store. Plain JavaScript objects.
 */
const actions = {
	// Action creator for the action called when settng a group by choosing from the options list.
	setGroups( groups ) {
		return {
			type: 'SET_GROUPS',
			groups,
		};
	},

	receiveGroups( path ) {
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
	'groups/groups-shortcodes',
	{
		// The reducer is a pure function that takes the previous state and an action, and returns the next state.
		reducer( state = DEFAULT_STATE, action ) {
			switch ( action.type ) {
				case 'SET_GROUPS':
					return {
						// To keep the reducer pure, state should not be mutated.
						// Use object state operator to copy enumerabale properties into a new object instead of Object.assign().
						...state,
						groups: action.groups,
					};
				// case 'CREATE_GROUP':
				// 	return {
				// 		...state,
				// 		groups: [...state.groups, action.newGroup],
				// 	};
			}
			// Return the previous state - for when there's an unknown action.
			return state;
		},

		actions,
		// A selector accepts state and optional arguments and returns some value from state.
		selectors: {
			receiveGroups( state ) {
				const { groups } = state;
				return groups;
			},
		},
		// Defines the execution flow behavior associated with a specific action type.
		controls: {
			RECEIVE_GROUPS( action ) {
				return apiFetch( { path: action.path } );
			},
		},
		// Side-effects for a selector. Used with data from an extrnal source.
		resolvers: {
			* receiveGroups( state ) {
				const groups = yield actions.receiveGroups( '/groups/groups-shortcodes/groups/' );
				return actions.setGroups( groups );
			},
		},
	}
);

// Change the 'groups' category icon in the block editor.
wp.blocks.updateCategory(
	'groups',
	{
		icon : wp.element.createElement(
			'img',
			{
				src    : groups_shortcodes_block.icon,
				width  : 20,
				height : 20
			}
		)
	}
);

// SVG for the block icon.
const memberIcon = wp.element.createElement('svg',
	{
		width: 1792,
		height: 1792,
		viewBox: '0 0 1792 1792'
	},
	wp.element.createElement( 'path',
		{
			d:"M1152 896q0 106-75 181t-181 75-181-75-75-181 75-181 181-75 181 75 75 181zm-256-544q-148 0-273 73t-198 198-73 273 73 273 198 198 273 73 273-73 198-198 73-273-73-273-198-198-273-73zm768 544q0 209-103 385.5t-279.5 279.5-385.5 103-385.5-103-279.5-279.5-103-385.5 103-385.5 279.5-279.5 385.5-103 385.5 103 279.5 279.5 103 385.5z"
		}
	)
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
		title: __( 'Groups Member Block','groups' ), // Block title.
		description: __( 'Restrict content for members of particular groups', 'groups' ),
		icon: memberIcon, // Block icon from Dashicons → https://developer.wordpress.org/resource/dashicons/.
		category: 'groups', // Block category — Group blocks together based on common traits E.g. common, formatting, layout widgets, embed.
		keywords: [__( 'groups' ),	],
		attributes: {
			groups_select: {
				type:    'string',
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
		( select ) => {
			return {
				// Uses select() to return an object of the store's selectors. Pre-bound to pass the current state automatically.
				groups: select( 'groups/groups-shortcodes' ).receiveGroups(),
			};
		}
	)

	(
		props => {
			const { attributes: { groups_select }, groups, className, setAttributes, isSelected } = props;
			const handleGroupsChange = ( groups_select ) => setAttributes( { groups_select: JSON.stringify( groups_select ) } );
			let selectedGroups = [];
			if ( null !== groups_select ) {
				selectedGroups = JSON.parse( groups_select );
			}
      // Show if the data is not loaded yet.
			if ( ! groups.length ) {
				return (
				< p className = {className} >
				< Spinner / >
				{ __( 'Loading...', 'groups' ) }
				< / p >
				);
			}

			return [
			< InspectorControls >
			< PanelBody title = { __( 'Select Groups', 'groups' ) } className = "block-inspector" >
			< PanelRow >
			< label htmlFor   = "block-groups" className = "groups-inspector__label" >
					{ __( 'Content will be shown to users that are members of these groups:', 'groups' ) }
			< / label >
			< / PanelRow >
			< PanelRow >
				< Select
					className = "groups-inspector__control"
					name      = 'block-groups'
					value     = { selectedGroups }
					onChange  = { handleGroupsChange }
					options = { groups }
					isClearable
					isMulti = 'true'
				 / >
			< / PanelRow >
			< / PanelBody >
			< / InspectorControls > ,
			< div className = { isSelected ? ( classnames( className ) + '__selected' ) : props.className } >
			< div className = { classnames( className ) + '__inner-block' } >
			< InnerBlocks / >
			< / div >
			< / div >
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
			< div >
			< InnerBlocks.Content / >
			< / div >
		);
	},
}
);
