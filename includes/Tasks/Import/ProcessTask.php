<?php
/**
 * ProcessTask Class
 *
 * @package WPM\Tasks\Import
 */

namespace WPM\Tasks\Import;

use WPM\Core\TaskBase;
use WPM\Handlers\URLRewriter;

// File: wp-content/plugins/wp-page-migrator/includes/Tasks/Import/ProcessTask.php
// Hooks: Inherits from TaskBase.
// DB: Writes to wp_posts, wp_postmeta, wp_term_relationships.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ProcessTask
 *
 * Handles the insertion and update of pages during import.
 */
class ProcessTask extends TaskBase {

	use ProcessTaskHelpers;

	/**
	 * Run the task.
	 *
	 * Iterates through provided pages and inserts/updates them.
	 *
	 * @return array {
	 *     @type string $status  'done', 'continue' or 'error'.
	 *     @type array  $summary Count of created, updated, skipped.
	 * }
	 */
	public function run(): array {
		$batch_size      = isset( $this->data['post_batch_size'] ) ? absint( $this->data['post_batch_size'] ) : 10;
		$processed_count = isset( $this->data['post_processed'] ) ? absint( $this->data['post_processed'] ) : 0;
		$pages           = isset( $this->data['pages'] ) ? (array) $this->data['pages'] : array();
		$conflict_mode   = isset( $this->data['conflict_mode'] ) ? $this->data['conflict_mode'] : 'skip';
		$old_url         = isset( $this->data['old_url'] ) ? $this->data['old_url'] : '';
		$new_url         = isset( $this->data['new_url'] ) ? $this->data['new_url'] : '';
		$media_id_map    = isset( $this->data['media_id_map'] ) ? (array) $this->data['media_id_map'] : array();

		$summary = isset( $this->data['summary'] ) ? $this->data['summary'] : array(
			'created' => 0,
			'updated' => 0,
			'skipped' => 0,
			'errors'  => 0,
		);

		$total_pages = count( $pages );

		if ( empty( $pages ) || $processed_count >= $total_pages ) {
			return array(
				'status'  => 'done',
				'data'    => $this->data,
				'summary' => $summary,
			);
		}

		$slice = array_slice( $pages, $processed_count, $batch_size );

		foreach ( $slice as $page_data ) {
			if ( ! isset( $page_data['post'] ) ) {
				$summary['errors']++;
				$processed_count++;
				continue;
			}

			$post_data = (array) $page_data['post'];
			$meta      = isset( $page_data['meta'] ) ? (array) $page_data['meta'] : array();
			$terms     = isset( $page_data['terms'] ) ? (array) $page_data['terms'] : array();

			// Rewrite URL and Media IDs in post content and excerpt.
			if ( ! empty( $old_url ) && ! empty( $new_url ) ) {
				if ( isset( $post_data['post_content'] ) ) {
					$post_data['post_content'] = URLRewriter::rewrite( $post_data['post_content'], $old_url, $new_url, $media_id_map );
				}
				if ( isset( $post_data['post_excerpt'] ) ) {
					$post_data['post_excerpt'] = URLRewriter::rewrite( $post_data['post_excerpt'], $old_url, $new_url, $media_id_map );
				}
			}

			$existing_post_id = $this->get_existing_post_id( $post_data['post_name'], $post_data['post_type'] );

			if ( $existing_post_id ) {
				if ( 'skip' === $conflict_mode ) {
					$summary['skipped']++;
					$processed_count++;
					continue;
				}
				if ( 'overwrite' === $conflict_mode ) {
					$post_data['ID'] = $existing_post_id;
				}
				if ( 'create_new' === $conflict_mode ) {
					unset( $post_data['ID'] );
				}
			} else {
				unset( $post_data['ID'] );
			}

			$post_data['post_author'] = get_current_user_id();
			$post_id                  = wp_insert_post( $post_data, true );

			if ( is_wp_error( $post_id ) ) {
				$summary['errors']++;
				$processed_count++;
				error_log( 'WPM ProcessTask Error: ' . $post_id->get_error_message() );
				continue;
			}

			if ( $existing_post_id && 'overwrite' === $conflict_mode ) {
				$summary['updated']++;
			} else {
				$summary['created']++;
			}

			$this->update_meta( $post_id, $meta, $old_url, $new_url, $media_id_map );
			$this->update_terms( $post_id, $terms, $old_url, $new_url, $media_id_map );
			$processed_count++;
		}

		$this->data['post_processed'] = $processed_count;
		$this->data['summary']        = $summary;

		$status   = ( $processed_count >= $total_pages ) ? 'done' : 'continue';
		$progress = $total_pages > 0 ? round( ( $processed_count / $total_pages ) * 100 ) : 100;

		/* ── BACKEND CONTRACT FULFILLED ─────────────────────
		   Task     : ProcessTask (Import)
		   Action   : run()
		   Input    : post_batch_size, post_processed, pages, conflict_mode, old_url, new_url, media_id_map
		   Output   : { status: string, summary: array, progress: int }
		   Details  : Batch processes pages, rewrites URLs/Media IDs, and inserts/updates posts.
		   ─────────────────────────────────────────────── */

		return array(
			'status'   => $status,
			'data'     => $this->data,
			'summary'  => $summary,
			'progress' => (int) $progress,
		);
	}
}
