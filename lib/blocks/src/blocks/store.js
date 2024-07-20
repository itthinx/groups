/**
 * store.js
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
 * @author itthinx
 * @package groups
 * @since groups 3.2.1
 */

import { registerStore, withSelect } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

/**
 * Default state - no groups selected.
 */
const DEFAULT_STATE = {
	groups: {},
};

/**
 * Actions object.
 * Actions are payloads of information that send data from the application to the store.
 * Plain JavaScript objects.
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
export const store = registerStore(
	'groups/groups-blocks',
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
				const groups = yield actions.receiveGroups( '/groups/groups-blocks/groups/' );
				return actions.setGroups( groups );
			},
		},
	}
);
