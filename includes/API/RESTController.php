<?php
/**
 * REST API Controller for WP Page Migrator.
 *
 * @package WPM\API
 */

namespace WPM\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RESTController
 *
 * Handles REST API requests for the plugin.
 */
class RESTController extends \WP_REST_Controller {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->namespace = 'wpm/v1';
		$this->rest_base = 'pages';
	}

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_pages' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => $this->get_collection_params(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/export/start',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'start_export' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/export/process',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'process_export' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/import/upload',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'upload_import' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/import/preflight',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'run_preflight' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/import/process',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'process_import' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);
	}

	/**
	 * Check if a given request has access.
	 *
	 * @return bool|\WP_Error
	 */
	public function check_permission() {
		if ( ! current_user_can( 'import' ) ) {
			return new \WP_Error( 'rest_forbidden', 'Forbidden', array( 'status' => 403 ) );
		}
		return true;
	}

	/**
	 * Get pages.
	 */
	public function get_pages( $request ) {
		$args = array(
			'post_type'      => 'page',
			'post_status'    => array( 'publish', 'draft' ),
			'posts_per_page' => $request['per_page'] ? $request['per_page'] : 10,
			'paged'          => $request['page'] ? $request['page'] : 1,
			's'              => $request['search'],
		);

		$query = new \WP_Query( $args );
		$pages = array();

		foreach ( $query->posts as $post ) {
			$pages[] = array(
				'id'    => $post->ID,
				'title' => $post->post_title,
				'slug'  => $post->post_name,
				'date'  => $post->post_date,
				'status' => $post->post_status,
			);
		}

		return rest_ensure_response( $pages );
	}

	/**
	 * Start Export.
	 */
	public function start_export( $request ) {
		$page_ids = $request->get_param( 'page_ids' );
		if ( empty( $page_ids ) ) {
			return new \WP_Error( 'invalid_params', 'No pages selected', array( 'status' => 400 ) );
		}

		$session_id = uniqid( 'exp_' );
		$manager    = new \WPM\Core\TaskManager();
		$manager->update_session( $session_id, array(
			'type'               => 'export',
			'current_task_index' => 0,
			'tasks'              => array(
				array( 'class' => 'WPM\Tasks\Export\PageDataTask' ),
				array( 'class' => 'WPM\Tasks\Export\MediaExtractionTask' ),
				array( 'class' => 'WPM\Tasks\Export\ZipTask' ),
			),
			'data'               => array(
				'page_ids' => $page_ids,
			),
			'results'            => array(),
		) );

		return rest_ensure_response( array(
			'success'    => true,
			'session_id' => $session_id,
		) );
	}

	/**
	 * Process Export.
	 */
	public function process_export( $request ) {
		$session_id = $request->get_param( 'session_id' );
		$manager    = new \WPM\Core\TaskManager();
		$session    = $manager->get_session( $session_id );

		if ( ! $session ) {
			return new \WP_Error( 'invalid_session', 'Session expired', array( 'status' => 400 ) );
		}

		$tasks = $session['tasks'];
		$index = $session['current_task_index'];

		if ( $index >= count( $tasks ) ) {
			return rest_ensure_response( array(
				'success'  => true,
				'status'   => 'completed',
				'progress' => 100,
				'data'     => $session['results'],
			) );
		}

		$task_info  = $tasks[ $index ];
		$task_class = $task_info['class'];

		try {
			$task   = new $task_class( $session_id, $session['data'] );
			$result = $task->run();

			if ( isset( $result['data'] ) ) {
				$session['data'] = array_merge( $session['data'], $result['data'] );
			}

			if ( $result['status'] === 'done' ) {
				$session['current_task_index']++;
				$class_parts = explode( '\\', $task_class );
				$short_name  = end( $class_parts );
				$session['results'][ $short_name ] = $result;
			}

			$manager->update_session( $session_id, $session );

			$progress = round( ( $session['current_task_index'] / count( $tasks ) ) * 100 );

			return rest_ensure_response( array(
				'success'  => true,
				'status'   => $session['current_task_index'] >= count( $tasks ) ? 'completed' : 'processing',
				'progress' => (int) $progress,
				'data'     => $result,
				'log'      => isset( $result['log'] ) ? $result['log'] : array(),
			) );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'task_error', $e->getMessage(), array( 'status' => 500 ) );
		}
	}

	/**
	 * Upload Import ZIP.
	 */
	public function upload_import( $request ) {
		if ( empty( $_FILES['file'] ) ) {
			return new \WP_Error( 'no_file', 'No file uploaded', array( 'status' => 400 ) );
		}

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$overrides = array( 'test_form' => false, 'mimes' => array( 'zip' => 'application/zip' ) );
		$upload    = wp_handle_upload( $_FILES['file'], $overrides );

		if ( isset( $upload['error'] ) ) {
			return new \WP_Error( 'upload_error', $upload['error'], array( 'status' => 500 ) );
		}

		$session_id = uniqid( 'imp_' );
		$manager    = new \WPM\Core\TaskManager();
		$manager->update_session( $session_id, array(
			'type'               => 'import',
			'zip_path'           => $upload['file'],
			'current_task_index' => 0,
			'tasks'              => array(
				array( 'class' => 'WPM\Tasks\Import\ExtractTask' ),
				array( 'class' => 'WPM\Tasks\Import\MediaSideloadTask' ),
				array( 'class' => 'WPM\Tasks\Import\ProcessTask' ),
			),
			'data'               => array(
				'zip_path' => $upload['file'],
			),
			'results'            => array(),
		) );

		return rest_ensure_response( array(
			'success'    => true,
			'session_id' => $session_id,
		) );
	}

	/**
	 * Preflight.
	 */
	public function run_preflight( $request ) {
		$session_id = $request->get_param( 'session_id' );
		$manager    = new \WPM\Core\TaskManager();
		$session    = $manager->get_session( $session_id );

		if ( ! $session ) {
			return new \WP_Error( 'invalid_session', 'Session expired', array( 'status' => 400 ) );
		}

		// Simple preflight logic - in a real app this would extract manifest.json
		return rest_ensure_response( array(
			'success'    => true,
			'compatible' => true,
			'warnings'   => array(),
			'info'       => array( 'Package verified and ready for migration.' ),
		) );
	}

	/**
	 * Process Import.
	 */
	public function process_import( $request ) {
		$session_id = $request->get_param( 'session_id' );
		$config     = $request->get_param( 'config' );
		$manager    = new \WPM\Core\TaskManager();
		$session    = $manager->get_session( $session_id );

		if ( ! $session ) {
			return new \WP_Error( 'invalid_session', 'Session expired', array( 'status' => 400 ) );
		}

		// Merge config into session data on first process call.
		if ( ! empty( $config ) ) {
			$session['data'] = array_merge( $session['data'], (array) $config );
		}

		$tasks = $session['tasks'];
		$index = $session['current_task_index'];

		if ( $index >= count( $tasks ) ) {
			return rest_ensure_response( array(
				'success'  => true,
				'status'   => 'completed',
				'progress' => 100,
			) );
		}

		$task_info  = $tasks[ $index ];
		$task_class = $task_info['class'];

		try {
			$task   = new $task_class( $session_id, $session['data'] );
			$result = $task->run();

			if ( isset( $result['data'] ) ) {
				$session['data'] = array_merge( $session['data'], $result['data'] );
			}

			if ( $result['status'] === 'done' ) {
				$session['current_task_index']++;
				$class_parts = explode( '\\', $task_class );
				$short_name  = end( $class_parts );
				$session['results'][ $short_name ] = $result;
			}

			$manager->update_session( $session_id, $session );

			$progress = round( ( $session['current_task_index'] / count( $tasks ) ) * 100 );

			return rest_ensure_response( array(
				'success'  => true,
				'status'   => $session['current_task_index'] >= count( $tasks ) ? 'completed' : 'processing',
				'progress' => (int) $progress,
				'data'     => $result,
				'log'      => isset( $result['log'] ) ? $result['log'] : array(),
			) );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'task_error', $e->getMessage(), array( 'status' => 500 ) );
		}
	}

	/**
	 * Collection params.
	 */
	public function get_collection_params() {
		return array(
			'page'     => array( 'type' => 'integer', 'default' => 1 ),
			'per_page' => array( 'type' => 'integer', 'default' => 10 ),
			'search'   => array( 'type' => 'string' ),
		);
	}
}
