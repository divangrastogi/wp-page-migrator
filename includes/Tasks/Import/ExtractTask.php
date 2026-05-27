<?php
/**
 * ExtractTask Class for WPM Import.
 *
 * @package WPM\Tasks\Import
 */

namespace WPM\Tasks\Import;

use WPM\Core\TaskBase;
use ZipArchive;
use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ExtractTask
 *
 * Extracts the migration ZIP and loads the manifest and pages data.
 */
class ExtractTask extends TaskBase {

	/**
	 * Run the task.
	 *
	 * @return array
	 * @throws Exception If ZipArchive fails.
	 */
	public function run(): array {
		$zip_path = isset( $this->data['zip_path'] ) ? $this->data['zip_path'] : '';

		if ( ! file_exists( $zip_path ) ) {
			throw new Exception( 'ZIP file not found at ' . $zip_path );
		}

		$upload_dir = wp_upload_dir();
		$extract_to = $upload_dir['basedir'] . '/wpm-imports/extract/' . $this->session_id;

		if ( ! file_exists( $extract_to ) ) {
			wp_mkdir_p( $extract_to );
		}

		$zip = new ZipArchive();
		if ( true !== $zip->open( $zip_path ) ) {
			throw new Exception( 'Could not open ZIP file.' );
		}

		$zip->extractTo( $extract_to );
		$zip->close();

		// Load pages.json.
		$pages_file = $extract_to . '/pages.json';
		if ( ! file_exists( $pages_file ) ) {
			throw new Exception( 'Invalid package: pages.json missing.' );
		}

		$pages = json_decode( file_get_contents( $pages_file ), true );

		// Load manifest.json.
		$manifest_file = $extract_to . '/manifest.json';
		$manifest = file_exists( $manifest_file ) ? json_decode( file_get_contents( $manifest_file ), true ) : [];

		// Load media mapping.
		$media_json = $extract_to . '/media.json';
		$media_map = file_exists( $media_json ) ? json_decode( file_get_contents( $media_json ), true ) : [];
		
		$media_dir = $extract_to . '/media';
		$media_files = [];
		if ( is_array( $media_map ) ) {
			foreach ( $media_map as $old_id => $filename ) {
				$media_files[] = [
					'old_id' => $old_id,
					'file'   => $filename,
					'path'   => $media_dir . '/' . $filename
				];
			}
		}

		return [
			'status'      => 'done',
			'data'        => [
				'extract_path' => $extract_to,
				'media_dir'    => $media_dir,
				'pages'        => $pages,
				'manifest'     => $manifest,
				'media_files'  => $media_files,
				'old_url'      => isset( $manifest['site_url'] ) ? $manifest['site_url'] : '',
				'new_url'      => get_site_url()
			],
			'log' => [ __( 'Package extracted successfully.', 'wp-page-migrator' ) ]
		];
	}
}
