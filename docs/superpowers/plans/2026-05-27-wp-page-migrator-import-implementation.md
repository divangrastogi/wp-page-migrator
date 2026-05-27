# WP Page Migrator Import Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement a high-reliability Import Engine with chunked media sideloading, flexible dependency handling, and user-configurable batch sizes.

**Architecture:** A multi-stage import workflow (Upload -> Preflight -> Config -> Execute) managed by a React UI and orchestrated by a Task-Runner on the backend. It uses a "Flexible Import" strategy to handle missing plugins (ACF/Elementor) safely.

**Tech Stack:** PHP 7.4+, WordPress REST API, React, `@wordpress/components`, `ZipArchive`.

---

## Phase 1: Backend Utilities & API

### Task 1: Database Handler Utility

**Files:**
- Create: `includes/Handlers/DatabaseHandler.php`

- [ ] **Step 1: Implement table existence check**
```php
<?php
namespace WPM\Handlers;

class DatabaseHandler {
    public static function table_exists( $table_name ) {
        global $wpdb;
        return $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $wpdb->prefix . $table_name ) ) === $wpdb->prefix . $table_name;
    }
}
```

- [ ] **Step 2: Update Memory**
Update `docs/superpowers/context/MEMORIES.md` to note the new utility.

---

### Task 2: REST API Import Extensions

**Files:**
- Modify: `includes/API/RESTController.php`

- [ ] **Step 1: Register Import endpoints**
```php
// Add to register_routes()
register_rest_route( 'wpm/v1', '/import/upload', [
    'methods' => 'POST',
    'callback' => [ $this, 'upload_import' ],
    'permission_callback' => [ $this, 'check_permission' ],
]);

register_rest_route( 'wpm/v1', '/import/preflight', [
    'methods' => 'POST',
    'callback' => [ $this, 'run_preflight' ],
    'permission_callback' => [ $this, 'check_permission' ],
]);

register_rest_route( 'wpm/v1', '/import/process', [
    'methods' => 'POST',
    'callback' => [ $this, 'process_import' ],
    'permission_callback' => [ $this, 'check_permission' ],
]);
```

- [ ] **Step 2: Implement upload_import placeholder**
```php
public function upload_import( $request ) {
    $files = $request->get_file_params();
    if ( empty( $files['file'] ) ) return new \WP_Error( 'no_file', 'No file uploaded', ['status' => 400] );
    
    // Logic to move file to wpm-imports/temp/
    // Return session_id
}
```

---

## Phase 2: Import Tasks

### Task 3: Media Sideload Task

**Files:**
- Create: `includes/Tasks/Import/MediaSideloadTask.php`

- [ ] **Step 1: Implement chunked media import**
```php
<?php
namespace WPM\Tasks\Import;
use WPM\Core\TaskBase;

class MediaSideloadTask extends TaskBase {
    public function run(): array {
        $batch_size = $this->data['media_batch_size'] ?? 5;
        $processed = $this->data['media_processed'] ?? 0;
        $media_files = $this->data['media_files'] ?? []; // List from ZIP extraction
        
        $current_batch = array_slice( $media_files, $processed, $batch_size );
        
        foreach ( $current_batch as $file ) {
            // sideload logic using media_handle_sideload()
            // update media_id_map in session
        }
        
        $new_processed = $processed + count( $current_batch );
        $done = $new_processed >= count( $media_files );
        
        return [
            'status' => $done ? 'done' : 'continue',
            'progress' => count( $media_files ) > 0 ? floor( ( $new_processed / count( $media_files ) ) * 100 ) : 100,
            'data' => [ 'media_processed' => $new_processed ]
        ];
    }
}
```

---

### Task 4: Batch-enabled Process Task

**Files:**
- Modify: `includes/Tasks/Import/ProcessTask.php`

- [ ] **Step 1: Add offset/limit support and table checks**
```php
// Update run() method
$offset = $this->data['post_processed'] ?? 0;
$limit = $this->data['post_batch_size'] ?? 10;
$pages = array_slice( $this->data['pages'], $offset, $limit );

foreach ( $pages as $page ) {
    // Check if table exists for specific meta if needed
    // Use URLRewriter::rewrite() with media_id_map
    // Insert/Update post
}
```

---

## Phase 3: Frontend Implementation

### Task 5: Import UI - State & Upload

**Files:**
- Modify: `src/components/ImportTab.js`
- Create: `src/components/FileUploader.js`

- [ ] **Step 1: Implement State Machine in ImportTab**
```javascript
const [ step, setStep ] = useState( 'UPLOAD' ); // UPLOAD, CONFIG, PROCESSING, COMPLETE
```

- [ ] **Step 2: Build FileUploader with Dropzone/File input**

---

### Task 6: Import UI - Config & Progress

**Files:**
- Create: `src/components/ImportConfig.js`
- Create: `src/components/ImportProgress.js`

- [ ] **Step 1: Build Config Screen with Batch Size inputs**
- [ ] **Step 2: Build Progress Screen with polling logic**
Poll `/import/process` and update progress bar based on task percentage.
