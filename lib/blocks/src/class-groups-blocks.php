<?php
 /**
  * class-groups-blocks.php
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

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once GROUPS_ACCESS_LIB . '/class-groups-access-meta-boxes.php';

class Groups_Blocks {


	public static function init() {
		add_action( 'init', array( __CLASS__, 'groups_blocks_block_init' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'groups_rest' ) );
		add_filter( 'block_categories', array( __CLASS__, 'groups_block_categories' ), 10, 2 );
	}

	// Create the REST API endpoints.
	public static function groups_rest() {
		register_rest_route(
			// namespace - TODO version portion, change when merging to Groups plugin.
			'groups/groups-blocks',
			// resource path
			'/groups',
			array(
				// Get the list of existing groups.
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'get_groups' ),
					// Restrict access for the endpoint only to users that can administrate groups restrictions.
					'permission_callback' => function () {
						return Groups_Access_Meta_Boxes::user_can_restrict();
					},
				),
			)
		);
	}

	// Callback function to retrieve existing groups.
	public static function get_groups() {
		$groups_options = array();

		if ( Groups_Access_Meta_Boxes::user_can_restrict() ) {
			$include = Groups_Access_Meta_Boxes::get_user_can_restrict_group_ids();
			$groups  = Groups_Group::get_groups(
				array(
					'order_by' => 'name',
					'order'    => 'ASC',
					'include'  => $include,
				)
			);
			foreach ( $groups as $key => $group ) {
					$groups_options[] = array(
						'value' => $group->group_id,
						'label' => $group->name,
					);
			}
		} else {
			$groups_options = __( 'You cannot set any access restrictions.', 'groups' );
		}

		return $groups_options;
	}

	// Add a new block category for 'groups' in the block editor
	public static function groups_block_categories( $categories, $post ) {
		$categories = array_merge(
			$categories,
			array(
				array(
					'slug'  => 'groups',
					'title' => 'Groups',
				),
			)
		);
		return $categories;
	}

	public static function groups_blocks_block_init() {
		// Skip block registration if Gutenberg is not enabled/merged.
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}
		// Scripts.
		wp_register_script(
			'groups_blocks-block-js', // Handle.
			GROUPS_PLUGIN_URL . 'lib/blocks/dist/blocks.build.js',
			array(
				'wp-blocks',
				'wp-i18n',
				'wp-element',
				'wp-editor',
			),
			GROUPS_CORE_VERSION
		);

		wp_set_script_translations(
			'groups_blocks-block-js',
			'groups'
		);

		// Frontend Styles - currently none required.
		// wp_register_style(
		// 	'groups_blocks-style-css', // Handle.
		//	GROUPS_PLUGIN_URL . 'lib/blocks/dist/blocks.style.build.css',
		//	array(), // Dependency to include the CSS after it.
		//	GROUPS_CORE_VERSION
		// );

		// Editor Styles.
		wp_register_style(
			'groups_blocks-block-editor-css', // Handle.
			GROUPS_PLUGIN_URL . 'lib/blocks/dist/blocks.editor.build.css',
			array( 'wp-edit-blocks' ), // Dependency to include the CSS after it.
			GROUPS_CORE_VERSION
		);
		register_block_type(
			'groups/groups-member',
			array(
				'editor_script'   => 'groups_blocks-block-js',
				'editor_style'    => 'groups_blocks-block-editor-css',
				'style'           => 'groups_blocks-style-css',
				'render_callback' => array( __CLASS__, 'groups_member_render_content' ),
			)
		);
		register_block_type(
			'groups/groups-non-member',
			array(
				'editor_script'   => 'groups_blocks-block-js',
				'editor_style'    => 'groups_blocks-block-editor-css',
				'style'           => 'groups_blocks-style-css',
				'render_callback' => array( __CLASS__, 'groups_non_member_render_content' ),
			)
		);
	}

	public static function groups_member_render_content( $attributes, $content ) {

		$output          = '';
		$show_content    = false;
		$selected_groups = array();

		if ( isset( $attributes['groups_select'] ) ) {
			$decoded_groups = json_decode( $attributes['groups_select'] );
			if ( ! empty( $decoded_groups ) ) {
				foreach ( $decoded_groups as $group ) {
					$selected_groups[] = $group->value;
				}
			}
		}

		$groups_user = new Groups_User( get_current_user_id() );
		foreach ( $selected_groups as $group ) {
			$current_group = Groups_Group::read( $group );
			if ( ! $current_group ) {
				$current_group = Groups_Group::read_by_name( $group );
			}

			if ( $current_group ) {
				if ( Groups_User_Group::read( $groups_user->user->ID, $current_group->group_id ) ) {
					$show_content = true;
					break;
				}
			}
		}
		if ( $show_content ) {
			$output = '<div>' . $content . '</div>';
		}

		return $output;
	}

	public static function groups_non_member_render_content( $attributes, $content ) {

		$output          = '';
		$show_content    = true;
		$selected_groups = array();

		if ( isset( $attributes['groups_select'] ) ) {
			$decoded_groups = json_decode( $attributes['groups_select'] );
			if ( ! empty( $decoded_groups ) ) {
				foreach ( $decoded_groups as $group ) {
					$selected_groups[] = $group->value;
				}
			}
		}

		$groups_user = new Groups_User( get_current_user_id() );
		foreach ( $selected_groups as $group ) {
			$current_group = Groups_Group::read( $group );
			if ( ! $current_group ) {
				$current_group = Groups_Group::read_by_name( $group );
			}

			if ( $current_group ) {
				if ( Groups_User_Group::read( $groups_user->user->ID, $current_group->group_id ) ) {
					$show_content = false;
					break;
				}
			}
		}
		if ( $show_content ) {
			$output = '<div>' . $content . '</div>';
		}

		return $output;
	}

}

Groups_Blocks::init();
