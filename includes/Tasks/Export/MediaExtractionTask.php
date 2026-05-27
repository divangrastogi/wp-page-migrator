<?php
/**
 * MediaExtractionTask Class for WPM Export.
 *
 * @package WPM\Tasks\Export
 */

namespace WPM\Tasks\Export;

use WPM\Core\TaskBase;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MediaExtractionTask
 *
 * Finds physical paths for all media IDs collected in previous steps.
 */
class MediaExtractionTask extends TaskBase {

	/**
	 * Run the task.
	 *
	 * @return array
	 */
	public function run(): array {
		$pages = isset( $this->data['PageDataTask']['data'] ) ? $this->data['PageDataTask']['data'] : [];
		$all_media_ids = [];

		foreach ( $pages as $page ) {
			if ( ! empty( $page['media_ids'] ) ) {
				$all_media_ids = array_merge( $all_media_ids, $page['media_ids'] );
			}
		}

		$all_media_ids = array_unique( $all_media_ids );
		$media_map = [];

		foreach ( $all_media_ids as $id ) {
			$path = get_attached_file( $id );
			if ( $path && file_exists( $path ) ) {
				$media_map[ $id ] = [
					'id'   => $id,
					'path' => $path,
					'name' => basename( $path )
				];
			}
		}

		return [
			'status' => 'done',
			'data'   => [
				'media' => $media_map
			],
			'log'    => [ sprintf( __( 'Found %d media attachments to export.', 'wp-page-migrator' ), count( $media_map ) ) ]
		];
	}
}
