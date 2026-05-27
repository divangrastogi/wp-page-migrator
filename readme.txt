=== WP Page Migrator ===
Contributors: Logelite
Tags: migration, pages, elementor, acf, export, import
Requires at least: 6.0
Tested up to: 6.5
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A modern, high-reliability WordPress plugin for migrating pages between sites with a React-powered UI.

== Description ==

WP Page Migrator allows you to select specific pages from your WordPress site and export them, along with their metadata (ACF) and layouts (Elementor/Gutenberg), into a portable ZIP package.

The plugin uses a unique Task-Runner architecture that processes data in batches, ensuring that migrations never fail due to server timeouts or memory limits, even on restricted hosting environments.

== Installation ==

1. Upload the `wp-page-migrator` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to Tools -> Page Migrator to begin.

== Screenshots ==

1. The Modern SaaS Export Dashboard.
2. The Import Wizard with file dropzone.
3. Real-time progress tracking.

== Changelog ==

= 1.0.0 =
* Initial release.
* Modern React UI with Wizard-style flow.
* Batch processing for Export/Import.
* Media sideloading and URL remapping.
