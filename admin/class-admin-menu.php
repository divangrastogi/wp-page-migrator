<?php
/**
 * Admin Menu Class
 *
 * @package WPM\Admin
 */

namespace WPM\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AdminMenu handles the registration of the plugin's admin menu and assets.
 */
class AdminMenu {

	/**
	 * Initialize the class.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Add the menu page under Tools.
	 */
	public function add_menu_page() {
		add_management_page(
			__( 'Page Migrator', 'wp-page-migrator' ),
			__( 'Page Migrator', 'wp-page-migrator' ),
			'manage_options',
			'wp-page-migrator',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Render the admin page root element.
	 */
	public function render_page() {
		echo '<div id="wpm-admin-root"></div>';
	}

	/**
	 * Enqueue compiled React assets.
	 */
	public function enqueue_assets( $hook ) {
		if ( 'tools_page_wp-page-migrator' !== $hook ) {
			return;
		}

		$asset_file = WPM_PATH . 'build/index.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$assets = require $asset_file;

		wp_enqueue_script(
			'wpm-admin-js',
			WPM_URL . 'build/index.js',
			$assets['dependencies'],
			$assets['version'],
			true
		);

		wp_enqueue_style(
			'wpm-admin-css',
			WPM_URL . 'build/style-index.css',
			array(),
			$assets['version']
		);
	}
}
