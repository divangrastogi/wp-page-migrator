# Changelog — WP Page Migrator

## [1.0.0] - 2026-05-27
### Added
- Initial plugin structure.
- PSR-4 Autoloader in `includes/Autoloader.php`.
- Main plugin file `wp-page-migrator.php` with core constants.
- Dependency check for ACF and Elementor on `plugins_loaded`.
- REST API implementation with `\WPM\API\RESTController`.
- Endpoint `GET wpm/v1/pages` for listing pages.
- `\WPM\Handlers\URLRewriter` for handling URL rewriting and media ID remapping in serialized/JSON data.

## [1.0.0-task-6] - 2026-05-27
### Added
- Created `package.json` with `@wordpress/scripts` for React builds.
- Created `src/index.js` as the entry point for the React-based Admin UI.
- Created `admin/class-admin-menu.php` to register the "Page Migrator" tool page and enqueue assets.
- Updated `includes/Autoloader.php` to support the `admin/` directory and WordPress class naming conventions.
- Initialized the Admin Menu in `wp-page-migrator.php`.

## [1.0.0-tasks-7-8] - 2026-05-27
### Added
- Created `\WPM\Tasks\Export\ZipTask` for ZIP compression (Task 7).
- Created `\WPM\Tasks\Import\ProcessTask` for page insertion and update during import (Task 8).

## [1.0.0-ui-task-2] - 2026-05-27
### Added
- Rebuilt `ExportTab.js` with a modern SaaS layout.
- Implemented `wpm-stepper` for export process tracking.
- Added card-based page selection with search filtering.
- Added status badges (Published/Draft) for pages in the export list.
- Updated `RESTController` to provide page status and include draft pages.

## [1.0.0-ui-task-4] - 2026-05-27
### Added
- Created `src/components/ImportTab.js` scaffold.
- Integrated `ImportTab` into the main `App.js` component.
- Added "coming soon" notice for import functionality.
