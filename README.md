# WP Page Migrator

A modern, high-reliability WordPress plugin for migrating pages between sites. Built with a React-powered "10/10 SaaS" UI and a robust PHP Task-Runner engine.

## 🌟 Key Features

*   **Chunked Processing:** Prevents server timeouts by processing large page collections and media libraries in small, manageable batches.
*   **Modern SaaS UI:** A premium, centered dashboard built with React and the WordPress Design System.
*   **Flexible Import:** Import pages even if dependencies like Elementor or ACF are missing.
*   **Media Sideloading:** Automatically downloads and re-associates media files from the export package.
*   **URL & ID Remapping:** Deep-rewriting of post content and metadata (including Elementor JSON) to match the destination environment.

## 🏗️ Architecture

This plugin follows a **Task-Runner** pattern:
1.  **Frontend (React):** Orchestrates the migration steps via the WordPress REST API.
2.  **Backend (PHP):** Executes discrete `Task` objects (Extract, Media, Post Process, Zip).
3.  **State Management:** Uses transients to track migration sessions, allowing for resume-on-failure behavior.

## 🚀 Getting Started

### Prerequisites
*   WordPress 6.0+
*   PHP 7.4+
*   Node.js & npm (for building UI assets)

### Installation
1.  Upload the `wp-page-migrator` folder to your `/wp-content/plugins/` directory.
2.  Run the build command:
    ```bash
    npm install && npm run build
    ```
3.  Activate the plugin through the 'Plugins' menu in WordPress.
4.  Navigate to **Tools > Page Migrator** to start exporting or importing.

## 📂 Internal Documentation
Detailed technical records are stored in the `/docs/superpowers/` directory:
- `ARCHITECTURE.md`: High-level system design.
- `CHANGELOG.md`: Version history and UI updates.
- `context/MEMORIES.md`: Task status and implementation logic.
- `specs/`: Original design specifications.
- `plans/`: Step-by-step implementation plans.

## 📜 License
GPLv2 or later.
