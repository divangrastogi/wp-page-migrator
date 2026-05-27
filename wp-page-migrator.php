<?php
/**
 * Plugin Name:       WP Page Migrator
 * Plugin URI:        https://example.com/wp-page-migrator
 * Description:       Export and import specific pages with Elementor and ACF data.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Senior Developer
 * Author URI:        https://example.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-page-migrator
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define Constants.
define( 'WPM_VERSION', '1.0.0' );
define( 'WPM_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPM_URL', plugin_dir_url( __FILE__ ) );

/**
 * Initialize Autoloader.
 */
require_once WPM_PATH . 'includes/Autoloader.php';
\WPM\Autoloader::register();

/**
 * Plugin Initialization and Dependency Check.
 */
add_action( 'plugins_loaded', 'wpm_initialize' );

/**
 * Initialize the plugin and check for dependencies.
 */
function wpm_initialize() {
	// Check for ACF.
	if ( ! defined( 'WPM_HAS_ACF' ) ) {
		define( 'WPM_HAS_ACF', class_exists( 'ACF' ) );
	}

	// Check for Elementor.
	if ( ! defined( 'WPM_HAS_ELEMENTOR' ) ) {
	        define( 'WPM_HAS_ELEMENTOR', class_exists( '\Elementor\Plugin' ) );
	}

	// Initialize Admin Menu.
	if ( is_admin() ) {
	        $admin_menu = new \WPM\Admin\AdminMenu();
	        $admin_menu->init();
	}

	// Future: Initialize main plugin logic here.
}

/**
 * Register REST API routes.
 */
add_action( 'rest_api_init', function () {
	$controller = new \WPM\API\RESTController();
	$controller->register_routes();
} );
