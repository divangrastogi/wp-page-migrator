<?php
// File: wp-content/plugins/wp-page-migrator/includes/Tasks/Import/MediaSideloadTask.php
// Namespace: WPM\Tasks\Import
// Hooks: N/A (called by TaskManager)

namespace WPM\Tasks\Import;

use WPM\Core\TaskBase;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MediaSideloadTask class.
 *
 * Handles sideloading media files from the exported package.
 *
 * @package WPM\Tasks\Import
 */
class MediaSideloadTask extends TaskBase {

	/**
	 * Run the media sideload task.
	 *
	 * Processes media files in batches and imports them into the media library.
	 *
	 * @return array Result of the task execution.
	 */
	public function run(): array {
		$batch_size      = isset( $this->data['media_batch_size'] ) ? absint( $this->data['media_batch_size'] ) : 5;
		$media_files     = isset( $this->data['media_files'] ) ? (array) $this->data['media_files'] : [];
		$processed_count = isset( $this->data['media_processed'] ) ? absint( $this->data['media_processed'] ) : 0;
		$media_id_map    = isset( $this->data['media_id_map'] ) ? (array) $this->data['media_id_map'] : [];
		$media_dir       = isset( $this->data['media_dir'] ) ? $this->data['media_dir'] : '';

		if ( empty( $media_files ) || $processed_count >= count( $media_files ) ) {
			return [
				'status' => 'done',
				'data'   => $this->data,
				'log'    => [ __( 'Media sideloading complete.', 'wp-page-migrator' ) ]
			];
		}

		// Required for media_handle_sideload and related functions.
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$slice = array_slice( $media_files, $processed_count, $batch_size );
		$new_logs = [];

		foreach ( $slice as $item ) {
			$old_id   = isset( $item['old_id'] ) ? absint( $item['old_id'] ) : 0;
			$filename = isset( $item['file'] ) ? sanitize_text_field( $item['file'] ) : '';

			if ( ! $old_id || ! $filename ) {
				$processed_count++;
				continue;
			}

			// Path to the file in the extracted package.
			$file_path = $media_dir . DIRECTORY_SEPARATOR . $filename;

			if ( ! file_exists( $file_path ) ) {
				$processed_count++;
				continue;
			}

			// Copy the file to a temporary location.
			// media_handle_sideload() expects a file it can move/process.
			$tmp_file = wp_tempnam( $file_path );
			
			if ( ! $tmp_file ) {
				$processed_count++;
				continue;
			}

			if ( ! copy( $file_path, $tmp_file ) ) {
				@unlink( $tmp_file );
				$processed_count++;
				continue;
			}

			$file_array = [
				'name'     => basename( $file_path ),
				'tmp_name' => $tmp_file,
			];

			// Sideload the file into the media library.
			// Use 0 for post_id to keep it unattached initially.
			$new_id = media_handle_sideload( $file_array, 0 );

			if ( ! is_wp_error( $new_id ) ) {
				$media_id_map[ $old_id ] = (int) $new_id;
			}

			// If media_handle_sideload succeeded, it moved the file.
			// If it failed, we should clean up the temp file if it still exists.
			if ( file_exists( $tmp_file ) ) {
				@unlink( $tmp_file );
			}

			$processed_count++;
		}

		// Update state.
		$this->data['media_processed'] = $processed_count;
		$this->data['media_id_map']    = $media_id_map;

		$status = ( $processed_count >= count( $media_files ) ) ? 'done' : 'continue';

		/* ── BACKEND CONTRACT FULFILLED ─────────────────────
		   Task     : MediaSideloadTask
		   Action   : run()
		   Batch    : Processing up to $batch_size files
		   Input    : media_files, media_processed, media_id_map, upload_dir
		   Output   : { status: 'continue'|'done', data: array }
		   Details  : Imports physical files into Media Library, updates ID map for replacement.
		   ─────────────────────────────────────────────── */

		return [
			'status' => $status,
			'data'   => $this->data,
		];
	}
}
