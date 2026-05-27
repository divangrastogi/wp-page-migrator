# Architecture — WP Page Migrator

## Namespace
The plugin uses the `WPM` namespace root.

## Autoloading
A custom PSR-4 autoloader is implemented in `includes/Autoloader.php`.
- Prefix: `WPM\`
- Base Directory: `includes/`
- Example: `\WPM\Exporter` -> `includes/Exporter.php`

## Constants
- `WPM_VERSION`: Current plugin version.
- `WPM_PATH`: Absolute path to the plugin directory.
- `WPM_URL`: URL to the plugin directory.
- `WPM_HAS_ACF`: Boolean, true if ACF is active.
- `WPM_HAS_ELEMENTOR`: Boolean, true if Elementor is active.

### Admin UI
- **Location:** `admin/class-admin-menu.php`
- **Frontend Entry:** `src/index.js`
- **Build System:** `@wordpress/scripts`
- **Root Element:** `#wpm-admin-root`
- **Menu Location:** Tools > Page Migrator

### REST API
The plugin exposes a REST API under the `wpm/v1` namespace.
- **Controller:** `\WPM\API\RESTController`
- **Endpoints:**
    - `GET /pages`: Retrieve a list of pages with basic metadata. Requires `import` capability.

## Handlers
- **`\WPM\Handlers\URLRewriter`**: Utility class for rewriting URLs and remapping media attachment IDs in strings, arrays, serialized data, and Elementor JSON data.

## Task System
The plugin uses a task-based architecture for background processes like export and import.
- **Base Class:** `\WPM\Core\TaskBase`
- **Manager:** `\WPM\Core\TaskManager`
- **Tasks:**
    - **Export:**
        - `\WPM\Tasks\Export\PageDataTask`: Collects post data, meta, and terms.
        - `\WPM\Tasks\Export\ZipTask`: Compresses data into a ZIP file.
    - **Import:**
        - `\WPM\Tasks\Import\ProcessTask`: Handles post insertion, updates, and conflict resolution.

## Hooks
- `plugins_loaded`: Used to initialize dependency checks and the main plugin logic.
