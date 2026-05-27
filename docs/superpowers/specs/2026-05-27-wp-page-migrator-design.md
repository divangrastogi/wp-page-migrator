# WP Page Migrator — Technical Design Specification
**Date:** 2026-05-27
**Status:** Draft / Approved

## 1. Overview
A modern WordPress plugin to migrate pages between sites, supporting Elementor, Gutenberg, and ACF. This plugin uses a Task-Runner architecture for reliability and a React-based UI for a seamless developer/user experience.

## 2. Architecture: Task-Runner Engine
The core logic is decoupled into a **Task-Runner** pattern.

### 2.1 `WPM_Task_Manager`
- Responsible for orchestrating the execution of migration tasks.
- Manages "Migration Sessions" stored in a custom table or as Transients.
- Tracks session state: `pending`, `processing`, `completed`, `failed`.

### 2.2 `WPM_Task` Interface / Base Class
Each step of the migration is a self-contained class:
- `collect_meta`: Scans post and meta for data.
- `collect_media`: Identifies and packages media files.
- `rewrite_urls`: Handles URL and ID remapping.
- `package_zip`: Finalizes the export archive.

### 2.3 Batching Strategy
Large datasets (media libraries) are processed in chunks. Each task returns a `progress` percentage and a `status` (`continue` or `done`). The React UI uses these values to drive the progress bar.

## 3. Tech Stack
- **Backend:** PHP 7.4+, WordPress REST API, `ZipArchive`.
- **Frontend:** React (JSX), `@wordpress/components`, `@wordpress/scripts`.
- **Integration:** Hooks for Elementor CSS regeneration and ACF field group syncing.

## 4. REST API Endpoints
All endpoints are prefixed with `/wpm/v1/`.

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/pages` | Searchable/Paginated list of pages. |
| `POST` | `/export/initialize` | Creates a session, returns session ID + task list. |
| `POST` | `/export/process` | Executes the next task in the queue. |
| `POST` | `/import/preflight` | Validates ZIP compatibility. |
| `POST` | `/import/execute` | Runs the import task runner. |

## 5. File Structure (Refined)
```
wp-page-migrator/
├── wp-page-migrator.php         # Entry & Autoloader
├── src/                         # React source
│   ├── export/
│   └── import/
├── includes/
│   ├── Core/
│   │   ├── TaskManager.php
│   │   └── TaskBase.php
│   ├── Tasks/
│   │   ├── Export/
│   │   └── Import/
│   ├── Handlers/
│   │   ├── MediaHandler.php
│   │   └── URLRewriter.php
│   └── API/
│       └── RESTController.php
├── build/                       # Compiled JS/CSS
└── languages/
```

## 6. Security & Safety
- **Nonce Verification:** Mandatory for all REST requests.
- **Capability Check:** Only users with `import` capability.
- **Data Integrity:** `wpdb->prepare()` for all queries.
- **Cleanup:** WP Cron job to delete exports older than 24 hours.
