# WP Page Migrator — Import Engine Design Specification
**Date:** 2026-05-27
**Status:** Draft / Approved

## 1. Overview
The Import Engine is responsible for extracting, validating, and migrating data from a WPM ZIP package into the destination WordPress site. It prioritizes reliability through user-configurable batch sizes and a "Flexible Import" mode that handles missing dependencies gracefully.

## 2. Technical Components

### 2.1 `WPM\Tasks\Import\UploadTask`
- Handles the initial file upload via the REST API.
- Moves the ZIP to `wp-content/uploads/wpm-imports/temp/`.
- Verifies the ZIP structure (presence of `manifest.json`).

### 2.2 `WPM\Tasks\Import\PreflightTask`
- Extracts `manifest.json`.
- Compares source vs. destination:
    - WP Version.
    - Plugin Status (ACF, Elementor).
    - Page Slugs (identifies collisions).
- Returns a JSON report for the React UI.

### 2.3 `WPM\Tasks\Import\MediaSideloadTask`
- Processes a "batch" of media files from the ZIP.
- Batch size is determined by the `media_batch_size` parameter from the UI.
- Uses `media_handle_sideload()` for standard WP integration.
- Updates the `media_id_map` in the `TaskManager` session.

### 2.4 `WPM\Tasks\Import\ProcessTask` (Updated)
- Now supports `offset` and `limit` to handle page insertion in batches.
- Uses `WPM\Handlers\URLRewriter` for data normalization.
- Skips operations targeting non-existent tables via `WPM\Handlers\DatabaseHandler`.

## 3. UI Components (React)

### 3.1 `ImportTab`
- Root component for the Import view.
- Manages the state machine: `UPLOAD` -> `PREFLIGHT` -> `CONFIG` -> `PROCESSING` -> `COMPLETE`.

### 3.2 `ImportConfig`
- Displays the Pre-flight report.
- Provides inputs for:
    - **Conflict Mode:** Skip, Overwrite, Create New.
    - **Media Batch Size:** 1 - 20 (Default: 5).
    - **Post Batch Size:** 1 - 50 (Default: 10).

### 3.3 `ImportProgress`
- Orchestrates the sequential calls to `/import/process`.
- Displays a multi-stage progress bar and a "Live Log" of imported pages.

## 4. REST API Extensions

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/import/upload` | Uploads and registers the ZIP. |
| `POST` | `/import/preflight` | Runs compatibility checks. |
| `POST` | `/import/process` | Executes the next batch of the current task. |

## 5. Reliability & Security
- **Chunked Sideloading:** Avoids memory and timeout issues.
- **Table Integrity:** `DatabaseHandler` verifies table existence before execution.
- **Cleanup:** Temporary extraction folders are deleted immediately after completion or failure.
