<?php
/**
 * PSR-4 Autoloader for WP Page Migrator
 *
 * @package WPM
 */

namespace WPM;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Autoloader class to handle PSR-4 mapping.
 */
class Autoloader {

	/**
	 * Registers the autoloader.
	 *
	 * @return void
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Autoload callback.
	 *
	 * @param string $class The fully-qualified class name.
	 * @return void
	 */
	public static function autoload( $class ) {
		$prefix = 'WPM\\';
		$len    = strlen( $prefix );
		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}

		$relative_class = substr( $class, $len );

		// Try standard PSR-4 in includes/.
		$file = __DIR__ . '/' . str_replace( '\\', '/', $relative_class ) . '.php';
		if ( file_exists( $file ) ) {
			require $file;
			return;
		}

		// Try admin/ directory with class- prefix for WPM\Admin namespace.
		if ( strpos( $relative_class, 'Admin\\' ) === 0 ) {
			$admin_class = substr( $relative_class, 6 );
			// Convert PascalCase to kebab-case (e.g., AdminMenu -> admin-menu).
			$kebab_name = strtolower( preg_replace( '/([a-z])([A-Z])/', '$1-$2', $admin_class ) );
			$filename   = 'class-' . str_replace( '_', '-', $kebab_name ) . '.php';
			$file       = dirname( __DIR__ ) . '/admin/' . $filename;
			if ( file_exists( $file ) ) {
				require $file;
				return;
			}
		}
	}
}
