<?php
/**
 * ProcessTask Helpers Trait
 *
 * @package WPM\Tasks\Import
 */

namespace WPM\Tasks\Import;

use WPM\Handlers\URLRewriter;
use WPM\Handlers\DatabaseHandler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trait ProcessTaskHelpers
 *
 * Provides helper methods for post, meta, and term processing during import.
 */
trait ProcessTaskHelpers {

	/**
	 * Find existing post ID by slug and type.
	 *
	 * @param string $slug Post slug.
	 * @param string $type Post type.
	 * @return int|null Post ID or null.
	 */
	private function get_existing_post_id( $slug, $type ) {
		global $wpdb;

		// Use DatabaseHandler as requested for non-standard or even standard table checks if desired.
		if ( ! DatabaseHandler::table_exists( $wpdb->posts ) ) {
			return null;
		}

		$id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_type = %s AND post_status != 'trash' LIMIT 1",
				$slug,
				$type
			)
		);
		return $id ? (int) $id : null;
	}

	/**
	 * Update post meta.
	 *
	 * @param int    $post_id      Post ID.
	 * @param array  $meta         Meta data (key => value or key => [values]).
	 * @param string $old_url      Old site URL.
	 * @param string $new_url      New site URL.
	 * @param array  $media_id_map Media ID map.
	 */
	private function update_meta( $post_id, $meta, $old_url, $new_url, $media_id_map ) {
		foreach ( $meta as $key => $values ) {
			// Rewrite URL and Media IDs in meta values.
			$values = URLRewriter::rewrite( $values, $old_url, $new_url, $media_id_map );

			if ( is_array( $values ) ) {
				delete_post_meta( $post_id, $key );
				foreach ( $values as $value ) {
					add_post_meta( $post_id, $key, maybe_unserialize( $value ) );
				}
			} else {
				update_post_meta( $post_id, $key, maybe_unserialize( $values ) );
			}
		}
	}

	/**
	 * Update post terms.
	 *
	 * @param int    $post_id      Post ID.
	 * @param array  $terms        Terms data (taxonomy => terms).
	 * @param string $old_url      Old site URL.
	 * @param string $new_url      New site URL.
	 * @param array  $media_id_map Media ID map.
	 */
	private function update_terms( $post_id, $terms, $old_url, $new_url, $media_id_map ) {
		foreach ( $terms as $taxonomy => $term_list ) {
			$slugs = array();
			foreach ( $term_list as $term_data ) {
				if ( ! taxonomy_exists( $taxonomy ) ) {
					continue;
				}

				// Rewrite term description.
				$description = isset( $term_data['description'] ) ? $term_data['description'] : '';
				if ( ! empty( $description ) ) {
					$description = URLRewriter::rewrite( $description, $old_url, $new_url, $media_id_map );
				}

				// Check if term exists, if not create it.
				$term = get_term_by( 'slug', $term_data['slug'], $taxonomy );
				if ( ! $term ) {
					$new_term = wp_insert_term(
						$term_data['name'],
						$taxonomy,
						array(
							'slug'        => $term_data['slug'],
							'description' => $description,
							'parent'      => $term_data['parent'],
						)
					);
					if ( ! is_wp_error( $new_term ) ) {
						$slugs[] = $term_data['slug'];
					}
				} else {
					$slugs[] = $term->slug;
				}
			}

			if ( ! empty( $slugs ) ) {
				wp_set_object_terms( $post_id, $slugs, $taxonomy );
			}
		}
	}
}
