<?php
/**
 * PageDataTask Class
 *
 * @package WPM\Tasks\Export
 */

namespace WPM\Tasks\Export;

use WPM\Core\TaskBase;

// File: wp-content/plugins/wp-page-migrator/includes/Tasks/Export/PageDataTask.php
// Hooks: Inherits from TaskBase.
// DB: Reads from wp_posts, wp_postmeta, wp_terms, wp_term_relationships.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PageDataTask
 *
 * Collects post data, meta, and terms for specified pages to prepare for export.
 */
class PageDataTask extends TaskBase {

	/**
	 * Run the task.
	 *
	 * Iterates through provided page IDs and collects all relevant data.
	 *
	 * @return array {
	 *     @type string $status 'done' or 'error'.
	 *     @type array  $data   The collected export data.
	 * }
	 */
	public function run(): array {
		$page_ids = isset( $this->data['page_ids'] ) ? (array) $this->data['page_ids'] : array();

		if ( empty( $page_ids ) ) {
			return array(
				'status'  => 'error',
				'message' => 'No page IDs provided for export.',
			);
		}

		$collected_data = array();

		foreach ( $page_ids as $page_id ) {
			$page_id = absint( $page_id );
			if ( 0 === $page_id ) {
				continue;
			}

			$post = get_post( $page_id );

			if ( ! $post ) {
				continue;
			}

			// 1. Post Record.
			$post_data = $post->to_array();

			// 2. All Post Meta.
			$meta = get_post_meta( $page_id );

			// 3. Taxonomy Terms.
			$terms      = array();
			$taxonomies = get_object_taxonomies( $post->post_type );

			foreach ( $taxonomies as $taxonomy ) {
				$object_terms = wp_get_object_terms( $page_id, $taxonomy, array( 'fields' => 'all' ) );

				if ( ! is_wp_error( $object_terms ) && ! empty( $object_terms ) ) {
					$terms[ $taxonomy ] = array();
					foreach ( $object_terms as $term ) {
						$terms[ $taxonomy ][] = array(
							'name'        => $term->name,
							'slug'        => $term->slug,
							'description' => $term->description,
							'parent'      => $term->parent,
							'taxonomy'    => $term->taxonomy,
						);
					}
				}
			}

			// 4. Media Mapping (Prepare for Task 5).
			$media_ids = $this->extract_media_ids( $post );

			$collected_data[] = array(
				'post'      => $post_data,
				'meta'      => $meta,
				'terms'     => $terms,
				'media_ids' => $media_ids,
			);
		}

		/* ── BACKEND CONTRACT FULFILLED ─────────────────────
		   Task     : PageDataTask (Export)
		   Action   : run()
		   Input    : array $this->data['page_ids']
		   Output   : { status: string, data: array }
		   Details  : Collects post, meta, terms, and media IDs for migration.
		   ─────────────────────────────────────────────── */

		return array(
			'status' => 'done',
			'data'   => $collected_data,
		);
	}

	/**
	 * Extract media IDs from post thumbnail and content.
	 *
	 * @param \WP_Post $post The post object.
	 * @return array Unique list of media IDs.
	 */
	private function extract_media_ids( $post ): array {
		$media_ids = array();

		// Featured Image.
		$thumbnail_id = get_post_thumbnail_id( $post->ID );
		if ( $thumbnail_id ) {
			$media_ids[] = absint( $thumbnail_id );
		}

		// Images in content - look for wp-image-{ID} class.
		preg_match_all( '/wp-image-([0-9]+)/i', $post->post_content, $matches );
		if ( ! empty( $matches[1] ) ) {
			foreach ( $matches[1] as $match ) {
				$media_ids[] = absint( $match );
			}
		}

		return array_unique( $media_ids );
	}
}
