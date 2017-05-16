<?php
/**
 * class-groups-comment-access.php
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
 * @since groups 2.2.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Post access restrictions.
 */
class Groups_Comment_Access {

	public static function init() {
		add_filter( 'comments_array', array( __CLASS__, 'comments_array' ), 10, 2 );
		add_filter( 'comment_feed_where', array( __CLASS__, 'comment_feed_where' ), 10, 2 );
		add_filter( 'comments_clauses', array( __CLASS__, 'comments_clauses' ), 10, 2 );
		// the comments_clauses filter is used in WP_Comment_Query::get_comment_ids() before the
		// comments are filtered with the_comments in WP_Comment_Query::get_comments() so we don't need to do this again
		//add_filter( 'the_comments', array( __CLASS__, 'the_comments' ), 10, 2 );
		// @todo add_filter( 'wp_count_comments', 10, 2 ); // see wp-includes/comment.php function wp_count_comments(...)
		add_filter( 'get_comments_number', array( __CLASS__, 'get_comments_number' ), 10, 2 );
	}

	/**
	 * Filter comments on the post if the user can't read the post.
	 *
	 * @param array $comments
	 * @param int $post_id
	 */
	public static function comments_array( $comments, $post_id ) {

		if ( !apply_filters( 'groups_comment_access_comments_array_apply', true, $comments, $post_id ) ) {
			return $comments;
		}

		$result = array();
		if ( Groups_Post_Access::user_can_read_post( $post_id ) ) {
			$result = $comments;
		}

		return $result;
	}

	/**
	 * Remove comments on posts that the user cannot read.
	 *
	 * @param array $comments
	 * @param WP_Comment_Query $comment_query
	 * @return array
	 */
	public static function the_comments( $comments, $comment_query ) {

		if ( !apply_filters( 'groups_comment_access_the_comments_apply', true, $comments, $comment_query ) ) {
			return $comments;
		}

		$_comments = array();
		foreach( $comments as $comment ) {
			if ( isset( $comment->comment_post_ID ) ) {
				if ( Groups_Post_Access::user_can_read_post( $comment->comment_post_ID ) ) {
					$_comments[] = $comment;
				}
			}
		}

		return $_comments;
	}

	/**
	 * Filter feed comments.
	 *
	 * @param string $where
	 * @param WP_Query $query
	 * @return string
	 */
	public static function comment_feed_where( $where, $query ) {

		if ( !apply_filters( 'groups_comment_access_comment_feed_where_apply', true, $where, $query ) ) {
			return $where;
		}

		if ( _groups_admin_override() ) {
			return $where;
		}

		if ( current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
			return $where;
		}

		$where = self::build_where( $where );
		return $where;
	}

	/**
	 * Filter the comments based on post read access restrictions.
	 *
	 * @param array $pieces
	 * @param WP_Comment_Query $comment_query
	 * @return array
	 */
	public static function comments_clauses( $pieces, $comment_query ) {

		if ( !apply_filters( 'groups_comment_access_comments_clauses_apply', true, $pieces, $comment_query ) ) {
			return $pieces;
		}

		if ( _groups_admin_override() ) {
			return $pieces;
		}

		if ( current_user_can( GROUPS_ADMINISTER_GROUPS ) ) {
			return $pieces;
		}

		$where = isset( $pieces['where'] ) ? $pieces['where'] : '';
		$where = self::build_where( $where );
		$pieces['where'] = $where;
		return $pieces;
	}

	/**
	 * Adds conditions to $where to restrict comment access.
	 *
	 * @param unknown $where
	 * @return unknown|string
	 */
	private static function build_where( $where ) {

		global $wpdb;

		$handles_post_types = Groups_Post_Access::get_handles_post_types();
		$post_types = array();
		foreach( $handles_post_types as $post_type => $handles ) {
			if ( $handles ) {
				$post_types[] = $post_type;
			}
		}
		if ( count( $post_types ) == 0 ) {
			return $where;
		}
		$post_types_in = "'" . implode( "','", array_map( 'esc_sql', $post_types ) ) . "'";

		// group_ids : all the groups that the user belongs to, including those that are inherited
		$user_id = get_current_user_id();
		$group_ids = array();
		if ( $user = new Groups_User( $user_id ) ) {
			$group_ids_deep = $user->group_ids_deep;
			if ( is_array( $group_ids_deep ) ) {
				$group_ids = $group_ids_deep;
			}
		}

		if ( count( $group_ids ) > 0 ) {
			$group_ids = "'" . implode( "','", array_map( 'esc_sql', $group_ids ) ) . "'";
		} else {
			$group_ids = '\'\'';
		}

		// only comments from posts that the user can read
		$where .= sprintf(
			" AND {$wpdb->comments}.comment_post_ID NOT IN ( " .
				"SELECT ID FROM $wpdb->posts WHERE " .
					"post_type IN (%s) AND " .
					"ID IN ( " .
						"SELECT post_id FROM $wpdb->postmeta pm WHERE " .
							"pm.meta_key = '%s' AND pm.meta_value NOT IN (%s) AND " .
							"post_id NOT IN ( SELECT post_id FROM $wpdb->postmeta pm WHERE pm.meta_key = '%s' AND pm.meta_value IN (%s) ) " .
					") " .
			") ",
			$post_types_in,
			esc_sql( Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ ),
			$group_ids,
			esc_sql( Groups_Post_Access::POSTMETA_PREFIX . Groups_Post_Access::READ ),
			$group_ids
		);

		return $where;
	}

	/**
	 * Filters the comments number of a post.
	 *
	 * @param int $count
	 * @param int $post_id
	 * @return int number of comments (0 if there are none or the user can't read the post)
	 */
	public static function get_comments_number ( $count, $post_id ) {
		$num_comments = 0;
		if ( Groups_Post_Access::user_can_read_post( $post_id ) ) {
			$num_comments = $count;
		}
		return $num_comments;
	}
}
Groups_Comment_Access::init();
