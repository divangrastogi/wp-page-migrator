<?php
// File: wp-content/plugins/wp-page-migrator/includes/Core/TaskManager.php
// Namespace: WPM\Core

namespace WPM\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TaskManager class.
 *
 * Manages task session state using WordPress Transients.
 *
 * @package WPM\Core
 */
class TaskManager {

	/**
	 * Transient prefix.
	 *
	 * @var string
	 */
	private $prefix = 'wpm_session_';

	/**
	 * Retrieve session data.
	 *
	 * @param string $id The session ID.
	 * @return mixed Session data or false if not found.
	 */
	public function get_session( $id ) {
		return get_transient( $this->prefix . sanitize_key( $id ) );
	}

	/**
	 * Update session data.
	 *
	 * @param string $id   The session ID.
	 * @param mixed  $data The data to store.
	 * @param int    $ttl  Time to live in seconds. Default is 1 hour.
	 * @return bool True if set, false otherwise.
	 */
	public function update_session( $id, $data, $ttl = HOUR_IN_SECONDS ) {
		return set_transient( $this->prefix . sanitize_key( $id ), $data, absint( $ttl ) );
	}

	/**
	 * Delete session data.
	 *
	 * @param string $id The session ID.
	 * @return bool True if deleted, false otherwise.
	 */
	public function delete_session( $id ) {
		return delete_transient( $this->prefix . sanitize_key( $id ) );
	}
}
