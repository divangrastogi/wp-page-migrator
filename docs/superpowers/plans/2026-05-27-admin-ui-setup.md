# Admin UI Setup Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Set up the React build environment and the Admin Menu registration for the WP Page Migrator plugin.

**Architecture:** Use `@wordpress/scripts` for React compilation and a PHP class to handle menu registration and script enqueuing.

**Tech Stack:** WordPress, React, PHP 7.4+.

---

### Task 1: Initialize package.json

**Files:**
- Create: `package.json`

- [ ] **Step 1: Create package.json with @wordpress/scripts**
```json
{
  "name": "wp-page-migrator",
  "version": "1.0.0",
  "description": "Export and import specific pages with Elementor and ACF data.",
  "main": "index.js",
  "scripts": {
    "build": "wp-scripts build",
    "format": "wp-scripts format",
    "lint:css": "wp-scripts lint-style",
    "lint:js": "wp-scripts lint-js",
    "packages-update": "wp-scripts packages-update",
    "start": "wp-scripts start"
  },
  "devDependencies": {
    "@wordpress/scripts": "^27.0.0"
  }
}
```

---

### Task 2: Create React Entry Point

**Files:**
- Create: `src/index.js`

- [ ] **Step 1: Create src/index.js**
```javascript
import { render } from '@wordpress/element';

const App = () => {
    return (
        <div className="wpm-admin-wrap">
            <h1>WP Page Migrator</h1>
            <p>Welcome to the Page Migrator tool. Select an action below to get started.</p>
        </div>
    );
};

window.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('wpm-admin-root');
    if (root) {
        render(<App />, root);
    }
});
```

---

### Task 3: Create Admin Menu Class

**Files:**
- Create: `admin/class-admin-menu.php`

- [ ] **Step 1: Create admin/ directory**
- [ ] **Step 2: Create admin/class-admin-menu.php**
```php
<?php
/**
 * Admin Menu Class
 *
 * @package WPM\Admin
 */

namespace WPM\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AdminMenu handles the registration of the plugin's admin menu and assets.
 */
class AdminMenu {

	/**
	 * Initialize the class.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Add the menu page under Tools.
	 */
	public function add_menu_page() {
		add_management_page(
			__( 'Page Migrator', 'wp-page-migrator' ),
			__( 'Page Migrator', 'wp-page-migrator' ),
			'manage_options',
			'wp-page-migrator',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Render the admin page root element.
	 */
	public function render_page() {
		echo '<div id="wpm-admin-root"></div>';
	}

	/**
	 * Enqueue compiled React assets.
	 */
	public function enqueue_assets( $hook ) {
		if ( 'tools_page_wp-page-migrator' !== $hook ) {
			return;
		}

		$asset_file = WPM_PATH . 'build/index.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$assets = require $asset_file;

		wp_enqueue_script(
			'wpm-admin-js',
			WPM_URL . 'build/index.js',
			$assets['dependencies'],
			$assets['version'],
			true
		);

		wp_enqueue_style(
			'wpm-admin-css',
			WPM_URL . 'build/index.css',
			array(),
			$assets['version']
		);
	}
}
```

---

### Task 4: Update Autoloader

**Files:**
- Modify: `includes/Autoloader.php`

- [ ] **Step 1: Update autoload method to support admin/ and class- naming convention**
```php
        public static function autoload( $class ) {
                $prefix = 'WPM\\';
                $len = strlen( $prefix );
                if ( strncmp( $prefix, $class, $len ) !== 0 ) {
                        return;
                }

                $relative_class = substr( $class, $len );

                // Try standard PSR-4 in includes/
                $file = __DIR__ . '/' . str_replace( '\\', '/', $relative_class ) . '.php';
                if ( file_exists( $file ) ) {
                        require $file;
                        return;
                }

                // Try admin/ directory with class- prefix for WPM\Admin namespace
                if ( strpos( $relative_class, 'Admin\\' ) === 0 ) {
                        $admin_class = substr( $relative_class, 6 );
                        $filename = 'class-' . strtolower( str_replace( '_', '-', $admin_class ) ) . '.php';
                        $file = dirname( __DIR__ ) . '/admin/' . $filename;
                        if ( file_exists( $file ) ) {
                                require $file;
                                return;
                        }
                }
        }
```

---

### Task 5: Initialize Admin Menu in Plugin

**Files:**
- Modify: `wp-page-migrator.php`

- [ ] **Step 1: Instantiate and init AdminMenu**
```php
function wpm_initialize() {
        // ... (existing code)
        
        // Initialize Admin Menu.
        if ( is_admin() ) {
                $admin_menu = new \WPM\Admin\AdminMenu();
                $admin_menu->init();
        }
}
```
