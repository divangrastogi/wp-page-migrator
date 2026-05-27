# WP Page Migrator Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a modern, task-runner based WordPress plugin for page migration with a React UI.

**Architecture:** A decoupled system using a `TaskManager` to orchestrate discrete PHP `Task` classes. The frontend is a React SPA communicating via the WordPress REST API, supporting chunked/batch processing for high reliability.

**Tech Stack:** PHP 7.4+, WordPress REST API, React, `@wordpress/components`, `@wordpress/scripts`, `ZipArchive`.

---

## Phase 1: Foundation & Scaffold

### Task 1: Plugin Entry & Autoloader

**Files:**
- Create: `wp-page-migrator.php`
- Create: `includes/Autoloader.php`

- [ ] **Step 1: Create the main plugin file**
```php
<?php
/**
 * Plugin Name: WP Page Migrator
 * Text Domain: wp-page-migrator
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WPM_VERSION', '1.0.0' );
define( 'WPM_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPM_URL', plugin_dir_url( __FILE__ ) );

require_once WPM_PATH . 'includes/Autoloader.php';
\WPM\Autoloader::register();

add_action( 'plugins_loaded', function() {
    define( 'WPM_HAS_ACF', class_exists( 'ACF' ) );
    define( 'WPM_HAS_ELEMENTOR', did_action( 'elementor/loaded' ) );
} );
```

- [ ] **Step 2: Create simple PSR-4 autoloader**
```php
<?php
namespace WPM;

class Autoloader {
    public static function register() {
        spl_autoload_register( function ( $class ) {
            if ( strpos( $class, 'WPM\\' ) !== 0 ) return;
            $file = WPM_PATH . 'includes/' . str_replace( '\\', '/', substr( $class, 4 ) ) . '.php';
            if ( file_exists( $file ) ) require_once $file;
        } );
    }
}
```

- [ ] **Step 3: Commit**
```bash
git add wp-page-migrator.php includes/Autoloader.php
git commit -m "feat: initial plugin scaffold and autoloader"
```

---

## Phase 2: Task-Runner Engine

### Task 2: Core Task System

**Files:**
- Create: `includes/Core/TaskBase.php`
- Create: `includes/Core/TaskManager.php`

- [ ] **Step 1: Create abstract TaskBase**
```php
<?php
namespace WPM\Core;

abstract class TaskBase {
    protected $session_id;
    protected $data;

    public function __construct( $session_id, $data = [] ) {
        $this->session_id = $session_id;
        $this->data = $data;
    }

    abstract public function run(): array;
}
```

- [ ] **Step 2: Create TaskManager**
```php
<?php
namespace WPM\Core;

class TaskManager {
    public function get_session( $id ) {
        return get_transient( 'wpm_session_' . $id );
    }

    public function update_session( $id, $data ) {
        set_transient( 'wpm_session_' . $id, $data, HOUR_IN_SECONDS );
    }
}
```

- [ ] **Step 3: Commit**
```bash
git add includes/Core/TaskBase.php includes/Core/TaskManager.php
git commit -m "feat: implement core TaskRunner classes"
```

---

## Phase 3: REST API & Admin UI

### Task 3: REST API Controllers

**Files:**
- Create: `includes/API/RESTController.php`

- [ ] **Step 1: Register migration endpoints**
```php
<?php
namespace WPM\API;

class RESTController extends \WP_REST_Controller {
    public function register_routes() {
        register_rest_route( 'wpm/v1', '/pages', [
            'methods' => 'GET',
            'callback' => [ $this, 'get_pages' ],
            'permission_callback' => [ $this, 'check_permission' ],
        ]);
    }

    public function check_permission() {
        return current_user_can( 'import' );
    }

    public function get_pages( $request ) {
        $pages = get_pages();
        return rest_ensure_response( $pages );
    }
}
```

- [ ] **Step 2: Commit**
```bash
git add includes/API/RESTController.php
git commit -m "feat: register initial REST API endpoints"
```

---

## Phase 4: Export Implementation

### Task 4: Export Metadata Task

**Files:**
- Create: `includes/Tasks/Export/PageDataTask.php`

- [ ] **Step 1: Implement data collection logic**
```php
<?php
namespace WPM\Tasks\Export;
use WPM\Core\TaskBase;

class PageDataTask extends TaskBase {
    public function run(): array {
        $page_ids = $this->data['page_ids'];
        $export_data = [];
        foreach ( $page_ids as $id ) {
            $export_data[] = [
                'post' => get_post( $id ),
                'meta' => get_post_meta( $id )
            ];
        }
        return [ 'status' => 'done', 'data' => $export_data ];
    }
}
```

- [ ] **Step 2: Commit**
```bash
git add includes/Tasks/Export/PageDataTask.php
git commit -m "feat: implement PageDataTask for metadata collection"
```

---

## Phase 5: Media & URL Handling

### Task 5: URL Rewriter Handler

**Files:**
- Create: `includes/Handlers/URLRewriter.php`

- [ ] **Step 1: Implement rewrite logic**
```php
<?php
namespace WPM\Handlers;

class URLRewriter {
    public static function rewrite( $value, $old_url, $new_url, $media_map = [] ) {
        if ( is_string( $value ) ) {
            $value = str_replace( $old_url, $new_url, $value );
            foreach ( $media_map as $old_id => $new_id ) {
                $value = str_replace( '"' . $old_id . '"', '"' . $new_id . '"', $value );
            }
        }
        return $value;
    }
}
```

- [ ] **Step 2: Commit**
```bash
git add includes/Handlers/URLRewriter.php
git commit -m "feat: add URLRewriter utility"
```

---

## Phase 6: Frontend Build

### Task 6: React Setup

**Files:**
- Create: `package.json`
- Create: `src/index.js`

- [ ] **Step 1: Initialize package.json**
```json
{
  "name": "wp-page-migrator",
  "scripts": {
    "start": "wp-scripts start",
    "build": "wp-scripts build"
  },
  "devDependencies": {
    "@wordpress/scripts": "^27.0.0"
  }
}
```

- [ ] **Step 2: Commit**
```bash
git add package.json
git commit -m "chore: setup wordpress/scripts build pipeline"
```

---

## Phase 7: Zip & Media Finalization

### Task 7: Zip Finalization Task

**Files:**
- Create: `includes/Tasks/Export/ZipTask.php`

- [ ] **Step 1: Implement ZipArchive logic**
```php
<?php
namespace WPM\Tasks\Export;
use WPM\Core\TaskBase;

class ZipTask extends TaskBase {
    public function run(): array {
        $zip = new \ZipArchive();
        $filename = 'wpm-export-' . time() . '.zip';
        $path = wp_upload_dir()['basedir'] . '/' . $filename;
        if ( $zip->open( $path, \ZipArchive::CREATE ) === TRUE ) {
            $zip->addFromString( 'manifest.json', json_encode( $this->data['manifest'] ) );
            $zip->addFromString( 'pages.json', json_encode( $this->data['pages'] ) );
            $zip->close();
        }
        return [ 'status' => 'done', 'url' => wp_upload_dir()['baseurl'] . '/' . $filename ];
    }
}
```

- [ ] **Step 2: Commit**
```bash
git add includes/Tasks/Export/ZipTask.php
git commit -m "feat: add ZipTask for archive creation"
```

---

## Phase 8: Basic Import Engine

### Task 8: Import Process Task

**Files:**
- Create: `includes/Tasks/Import/ProcessTask.php`

- [ ] **Step 1: Implement post insertion logic**
```php
<?php
namespace WPM\Tasks\Import;
use WPM\Core\TaskBase;

class ProcessTask extends TaskBase {
    public function run(): array {
        $pages = $this->data['pages'];
        foreach ( $pages as $page ) {
            $post_data = (array) $page['post'];
            unset( $post_data['ID'] );
            wp_insert_post( $post_data );
        }
        return [ 'status' => 'done' ];
    }
}
```

- [ ] **Step 2: Commit**
```bash
git add includes/Tasks/Import/ProcessTask.php
git commit -m "feat: implement basic Import ProcessTask"
```

