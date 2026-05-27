<?php
/**
 * File: wp-content/plugins/wp-page-migrator/includes/Handlers/DatabaseHandler.php
 * Hooks: None (Utility class)
 */

namespace WPM\Handlers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DatabaseHandler
 *
 * Handles database-related operations for migration.
 *
 * @package WPM\Handlers
 */
class DatabaseHandler {

	/**
	 * Checks if a database table exists.
	 *
	 * @param string $table_name The table name to check.
	 *
	 * @return bool True if the table exists, false otherwise.
	 */
	public static function table_exists( $table_name ) {
		global $wpdb;

		// Use SHOW TABLES LIKE to check for existence.
		// %s is used for the table name, and we use $wpdb->esc_like to ensure it's treated as a literal.
		$query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) );

		$result = $wpdb->get_var( $query );

		return $result === $table_name;
	}
}
