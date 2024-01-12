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

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

require_once GROUPS_ACCESS_LIB . '/class-groups-access-meta-boxes.php';

/**
 * In charge of registering, controlling and rendering Groups' blocks.
 */
class Groups_Blocks {

	/**
	 * Adds handlers to register blocks, REST and block categories.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'groups_blocks_block_init' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'groups_rest' ) );
		// @since 2.14.0 check for this function which is available since WordPress 5.8.0; as of then, the block_categories filter is deprecated and block_categories_all should be used instead
		if ( function_exists( 'get_default_block_categories' ) ) {
			add_filter( 'block_categories_all', array( __CLASS__, 'block_categories_all' ), 10, 2 );
		} else {
			add_filter( 'block_categories', array( __CLASS__, 'groups_block_categories' ), 10, 2 );
		}
	}

	/**
	 * Create the REST API endpoints.
	 */
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

	/**
	 * Callback function to retrieve existing groups.
	 *
	 * @return array
	 */
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
			foreach ( $groups as $group ) {
				$groups_options[] = array(
					'value' => $group->group_id,
					'label' => $group->name ? stripslashes( wp_filter_nohtml_kses( $group->name ) ) : '',
				);
			}
		} else {
			$groups_options = esc_html__( 'You cannot set any access restrictions.', 'groups' );
		}
		return $groups_options;
	}

	/**
	 * Register the Groups block category.
	 *
	 * @since 2.14.0
	 *
	 * @param array $block_categories
	 * @param WP_Block_Editor_Context $block_editor_context
	 *
	 * @return array
	 */
	public static function block_categories_all( $block_categories, $block_editor_context ) {
		$block_categories = array_merge(
			$block_categories,
			array(
				array(
					'slug' => 'groups',
					'title' => 'Groups' // do NOT translate
				)
			)
		);
		return $block_categories;
	}

	/**
	 * Adds a new block category for 'groups' in the block editor.
	 *
	 * @deprecated as of 2.14.0 with WordPress 5.8.0 using the block_categories_all filter instead
	 *
	 * @param array $categories Array of block categories.
	 * @param WP_Post $post Post being loaded.
	 *
	 * @return array
	 */
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

	/**
	 * Registers our blocks.
	 */
	public static function groups_blocks_block_init() {
		// Skip block registration if Gutenberg is not enabled/merged.
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		$asset_file = include GROUPS_BLOCKS_LIB . '/build/index.asset.php';

		$editor_dependencies = array_merge(
			$asset_file['dependencies'],
			array()
		);

		// @todo if 'wp-edit-widgets' or 'wp-customize-widgets' script then don't use wp-editor ... so ?
		// Scripts.
		wp_register_script(
			'groups_blocks-block-js', // Handle.
			GROUPS_PLUGIN_URL . 'lib/blocks/build/index.js',
			$editor_dependencies,
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
			GROUPS_PLUGIN_URL . 'lib/blocks/build/index.css',
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

	/**
	 * Rendering callback for our Groups Member block.
	 *
	 * @param array $attributes
	 * @param string $content
	 *
	 * @return string
	 */
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
				if ( $groups_user->is_member( $current_group->group_id ) ) {
					$show_content = true;
					break;
				}
			}
		}

		if ( $show_content ) {
			$output = '<div class="groups-member-block-content">' . $content . '</div>';
		}

		return $output;
	}

	/**
	 * Rendering callback for our Groups Non-member block.
	 *
	 * @param array $attributes
	 * @param string $content
	 *
	 * @return string
	 */
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
				if ( $groups_user->is_member( $current_group->group_id ) ) {
					$show_content = false;
					break;
				}
			}
		}

		if ( $show_content ) {
			$output = '<div class="groups-non-member-block-content">' . $content . '</div>';
		}

		return $output;
	}

}

Groups_Blocks::init();
