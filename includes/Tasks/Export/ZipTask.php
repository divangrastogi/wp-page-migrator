<?php
/**
 * ZipTask for WPM Export.
 *
 * @package WPM\Tasks\Export
 */

namespace WPM\Tasks\Export;

use WPM\Core\TaskBase;
use ZipArchive;
use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ZipTask
 *
 * Handles the creation of the export ZIP file.
 */
class ZipTask extends TaskBase {

	/**
	 * Run the task.
	 *
	 * Expects $this->data to contain:
	 * - manifest: array|string (content for manifest.json)
	 * - pages: array|string (content for pages.json)
	 * - acf_field_groups: array|string (optional content for acf-field-groups.json)
	 * - media: array (optional, associative array where key is the internal zip path and value is absolute file path)
	 *
	 * @return array {
	 *     @type string $url      The URL to the generated ZIP.
	 *     @type string $filename The name of the ZIP file.
	 *     @type string $path     The absolute path to the ZIP file.
	 * }
	 * @throws Exception If ZipArchive is missing or ZIP creation fails.
	 */
	public function run(): array {
		if ( ! class_exists( 'ZipArchive' ) ) {
			throw new Exception( 'The ZipArchive PHP extension is not installed.' );
		}

		$upload_dir = wp_upload_dir();
		$export_dir = $upload_dir['basedir'] . '/wpm-exports';

		// Ensure export directory exists and is secured.
		if ( ! file_exists( $export_dir ) ) {
			wp_mkdir_p( $export_dir );
			$this->secure_directory( $export_dir );
		}

		$filename  = 'export-' . sanitize_title( $this->session_id ) . '-' . time() . '.zip';
		$file_path = $export_dir . '/' . $filename;

		$zip = new ZipArchive();
		if ( true !== $zip->open( $file_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
			throw new Exception( 'Could not create ZIP file at ' . $file_path );
		}

		// Add manifest.json.
		$manifest = isset( $this->data['manifest'] ) ? $this->data['manifest'] : [];
		$manifest['site_url'] = get_site_url();
		$zip->addFromString( 'manifest.json', $this->format_json( $manifest ) );

		// Add pages.json.
		$pages = isset( $this->data['pages'] ) ? $this->data['pages'] : [];
		$zip->addFromString( 'pages.json', $this->format_json( $pages ) );

		// Add acf-field-groups.json if provided.
		if ( ! empty( $this->data['acf_field_groups'] ) ) {
			$zip->addFromString( 'acf-field-groups.json', $this->format_json( $this->data['acf_field_groups'] ) );
		}

		// Add media files and media.json if provided.
		$media_map = isset( $this->data['media'] ) ? $this->data['media'] : [];
		if ( ! empty( $media_map ) && is_array( $media_map ) ) {
			$export_media_map = [];
			foreach ( $media_map as $old_id => $media_info ) {
				$abs_path = $media_info['path'];
				if ( file_exists( $abs_path ) && is_readable( $abs_path ) ) {
					$zip_name = $media_info['name'];
					$zip->addFile( $abs_path, 'media/' . $zip_name );
					$export_media_map[ $old_id ] = $zip_name;
				}
			}
			$zip->addFromString( 'media.json', $this->format_json( $export_media_map ) );
		}

		$zip->close();

		return [
			'status'   => 'done',
			'url'      => $upload_dir['baseurl'] . '/wpm-exports/' . $filename,
			'filename' => $filename,
			'path'     => $file_path,
		];
	}

	/**
	 * Format data as JSON string if it's not already.
	 *
	 * @param mixed $data Data to format.
	 * @return string
	 */
	private function format_json( $data ): string {
		if ( is_string( $data ) ) {
			return $data;
		}
		return (string) wp_json_encode( $data, JSON_PRETTY_PRINT );
	}

	/**
	 * Secure the export directory by adding an index.php and .htaccess.
	 *
	 * @param string $dir The absolute path to the directory.
	 */
	private function secure_directory( string $dir ): void {
		if ( ! file_exists( $dir . '/index.php' ) ) {
			file_put_contents( $dir . '/index.php', '<?php // Silence is golden' );
		}

		if ( ! file_exists( $dir . '/.htaccess' ) ) {
			file_put_contents( $dir . '/.htaccess', "Options -Indexes\nDeny from all\n<Files *.zip>\n    Allow from all\n</Files>" );
		}
	}
}
