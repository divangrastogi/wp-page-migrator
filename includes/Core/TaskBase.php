<?php
// File: wp-content/plugins/wp-page-migrator/includes/Core/TaskBase.php
// Namespace: WPM\Core

namespace WPM\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract TaskBase class.
 *
 * Base class for all background/asynchronous tasks.
 *
 * @package WPM\Core
 */
abstract class TaskBase {

	/**
	 * The session ID.
	 *
	 * @var string
	 */
	protected $session_id;

	/**
	 * The task data.
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * Constructor.
	 *
	 * @param string $session_id The unique session ID.
	 * @param array  $data       Data associated with the task.
	 */
	public function __construct( $session_id, array $data ) {
		$this->session_id = $session_id;
		$this->data       = $data;
	}

	/**
	 * Execute the task.
	 *
	 * @return array Result of the task execution.
	 */
	abstract public function run(): array;
}
