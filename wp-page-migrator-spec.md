# WP Page Migrator — Plugin Build Specification

## Overview

Build a WordPress plugin called **WP Page Migrator** that allows users to select specific pages from a source WordPress site and export them (including Elementor/Gutenberg layout data and ACF custom field values) as a `.zip` package, then import that package into a destination WordPress site.

---

## Plugin Metadata

- **Plugin Name:** WP Page Migrator
- **Text Domain:** `wp-page-migrator`
- **Min WordPress Version:** 6.0
- **Min PHP Version:** 7.4
- **Supports:** Elementor, Gutenberg (core blocks), Advanced Custom Fields (ACF)
- **Required Capability:** `import` (WordPress built-in)

---

## File Structure

```
wp-page-migrator/
├── wp-page-migrator.php
├── includes/
│   ├── class-exporter.php
│   ├── class-importer.php
│   ├── class-media-handler.php
│   ├── class-url-rewriter.php
│   ├── class-preflight.php
│   └── class-logger.php
├── admin/
│   ├── views/
│   │   ├── export-page.php
│   │   └── import-page.php
│   └── class-admin-menu.php
└── assets/
    ├── export.js
    └── import.js
```

---

## Main Plugin File (`wp-page-migrator.php`)

- Standard WordPress plugin header
- Define constants: `WPM_VERSION`, `WPM_PATH`, `WPM_URL`
- Autoload all classes in `includes/` and `admin/`
- Hook `class-admin-menu.php` into `admin_menu`
- Check for ACF and Elementor on `plugins_loaded`, store results in constants `WPM_HAS_ACF` and `WPM_HAS_ELEMENTOR`

---

## Phase 1 — Export

### Admin UI (`admin/views/export-page.php`)

Render a page under **Tools > Page Migrator > Export** with:

- Search/filter input to search pages by title
- A paginated table listing all pages with columns:
  - Checkbox (for selection)
  - Page title (with parent indent for hierarchy)
  - Status (published/draft/etc.)
  - Page template
  - Last modified date
- "Select All" / "Deselect All" toggle
- "Export Selected" button that triggers AJAX export
- Progress indicator during export

### Exporter (`includes/class-exporter.php`)

Class `WPM_Exporter` with a primary method `export(array $page_ids): string` that returns the path to the generated `.zip` file.

**For each page ID, collect:**

```php
$post   = get_post($id);                          // post row
$meta   = get_post_meta($id);                     // ALL postmeta (includes ACF + Elementor)
$terms  = wp_get_object_terms(                    // taxonomy terms
              $id,
              get_object_taxonomies('page')
          );

// If ACF active:
$acf_fields = function_exists('get_field_objects')
    ? get_field_objects($id)
    : [];
```

**Build `pages.json`** — array of objects:

```json
[
  {
    "post": {
      "post_title": "",
      "post_name": "",
      "post_content": "",
      "post_status": "",
      "post_type": "page",
      "post_parent": 0,
      "menu_order": 0,
      "comment_status": "",
      "ping_status": ""
    },
    "meta": {
      "_elementor_data": "...",
      "_elementor_page_settings": "...",
      "_wp_page_template": "...",
      "your_acf_field_key": "value"
    },
    "terms": [],
    "media_map": {
      "123": "123-original-filename.jpg"
    }
  }
]
```

**Build `manifest.json`:**

```json
{
  "plugin_version": "1.0.0",
  "wp_version": "6.5",
  "source_url": "https://source-site.com",
  "export_date": "2024-11-15T10:00:00Z",
  "page_count": 3,
  "has_acf": true,
  "has_elementor": true
}
```

**Build `acf-field-groups.json`** (if ACF active):
- Export all ACF field groups that contain fields used by the exported pages
- Use `acf_get_field_groups()` and `acf_get_fields($group_key)`

**Zip structure:**

```
export-{timestamp}.zip
├── manifest.json
├── pages.json
├── acf-field-groups.json
└── media/
    ├── 123-image.jpg
    └── 456-banner.png
```

Use PHP `ZipArchive` to build the zip. Save to `wp_upload_dir()['basedir'] . '/wpm-exports/'`. Return the full file path.

---

## Phase 2 — Media Handling

### Media Handler (`includes/class-media-handler.php`)

Class `WPM_Media_Handler`.

**On export — `collect(array $page_ids): array`:**

- Scan `_elementor_data` JSON for image URLs and attachment IDs
- Scan all ACF meta values for attachment IDs (use `wp_get_attachment_url()` to confirm)
- For each found attachment:
  - Get the physical file path via `get_attached_file($attachment_id)`
  - Copy to the export `media/` folder
  - Record `old_attachment_id => filename` in the media map

**On import — `upload(array $media_files, array $media_map): array`:**

- For each file in the `media/` folder from the zip:
  - Use `wp_upload_bits()` or `media_handle_sideload()` to upload to the destination media library
  - Return `old_attachment_id => new_attachment_id` map
- This map is used by `WPM_URL_Rewriter` to update references

---

## Phase 3 — Import

### Admin UI (`admin/views/import-page.php`)

Render a page under **Tools > Page Migrator > Import** with:

- File upload input accepting `.zip` files
- "Run Pre-flight Check" button (shows compatibility report before committing)
- Conflict resolution option: radio buttons for **Skip** / **Overwrite** / **Create as new** (for duplicate slugs)
- "Import" button (disabled until pre-flight passes)
- Results table after import: Page title | Status (Created / Updated / Skipped) | Notes

### Pre-flight Check (`includes/class-preflight.php`)

Class `WPM_Preflight` with method `check(string $zip_path): array` returning an array of warnings and errors.

**Checks to perform:**

| Check | Pass condition | Severity |
|---|---|---|
| Plugin version match | Minor version match | Warning |
| WordPress version | Destination >= source | Warning |
| ACF installed | If export has ACF data | Error |
| ACF field groups exist | All exported group keys found | Error |
| Elementor installed | If export has Elementor data | Error |
| Elementor version | Destination >= source | Warning |
| Duplicate slugs | List any `post_name` conflicts | Info |
| Source URL differs | Always true on migration | Info |

Return format:
```php
[
  'errors'   => [ 'ACF field group "group_abc123" not found on destination.' ],
  'warnings' => [ 'Elementor version mismatch: 3.14 → 3.12' ],
  'info'     => [ '2 pages have duplicate slugs.' ],
  'pass'     => false  // true only if no errors
]
```

Block import if `pass === false`.

### Importer (`includes/class-importer.php`)

Class `WPM_Importer` with method `import(string $zip_path, string $conflict_mode): array`.

**Steps:**

1. Extract zip to a temp directory
2. Run `WPM_Preflight::check()` — abort if errors
3. Parse `pages.json`
4. Upload media via `WPM_Media_Handler::upload()`
5. For each page:

```php
// Check for duplicate slug
$existing = get_page_by_path($post_data['post_name']);

if ($existing && $conflict_mode === 'skip') {
    $log[] = ['status' => 'skipped', 'title' => $post_data['post_title']];
    continue;
}

if ($existing && $conflict_mode === 'overwrite') {
    $post_data['ID'] = $existing->ID;
}

// Insert or update
$new_id = wp_insert_post($post_data, true);
if (is_wp_error($new_id)) { /* log error, continue */ }

// Write all meta
foreach ($page['meta'] as $key => $value) {
    $rewritten = WPM_URL_Rewriter::rewrite($value, $old_url, $new_url, $media_id_map);
    update_post_meta($new_id, $key, $rewritten);
}

// Re-attach terms
foreach ($page['terms'] as $term) {
    wp_set_object_terms($new_id, $term['slug'], $term['taxonomy'], true);
}
```

6. After all pages: run post-import tasks (see Phase 4)
7. Return import log array

### URL Rewriter (`includes/class-url-rewriter.php`)

Class `WPM_URL_Rewriter` with static method:

```php
public static function rewrite(
    mixed  $value,
    string $old_url,
    string $new_url,
    array  $media_id_map  // [ old_id => new_id ]
): mixed
```

**Logic:**

```php
// 1. Unserialize if needed
$unserialized = maybe_unserialize($value);

// 2. If it looks like Elementor JSON, decode → replace → re-encode
if (is_string($unserialized) && str_starts_with(trim($unserialized), '[')) {
    $decoded = json_decode($unserialized, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $json_str   = json_encode($decoded);
        $json_str   = str_replace($old_url, $new_url, $json_str);
        // Replace old attachment IDs in JSON
        foreach ($media_id_map as $old_id => $new_id) {
            $json_str = str_replace('"' . $old_id . '"', '"' . $new_id . '"', $json_str);
        }
        return $json_str;
    }
}

// 3. Plain string replacement
if (is_string($unserialized)) {
    return str_replace($old_url, $new_url, $unserialized);
}

// 4. Array: recurse
if (is_array($unserialized)) {
    return array_map(fn($v) => self::rewrite($v, $old_url, $new_url, $media_id_map), $unserialized);
}

return $value;
```

> **Never use raw `unserialize()`.** Always use `maybe_unserialize()`.

---

## Phase 4 — Post-import Tasks

Run these after all pages are imported:

```php
// 1. Regenerate Elementor CSS cache
if (WPM_HAS_ELEMENTOR) {
    \Elementor\Plugin::$instance->files_manager->clear_cache();
}

// 2. Flush ACF field group cache
if (WPM_HAS_ACF) {
    acf_update_setting('l10n_var_export', false);
}

// 3. Flush WordPress rewrite rules
flush_rewrite_rules();
```

---

## Logger (`includes/class-logger.php`)

Class `WPM_Logger`. Stores import results in a transient or custom DB table.

**Log entry structure:**

```php
[
  'page_title'  => 'About Us',
  'post_name'   => 'about-us',
  'status'      => 'created',   // created | updated | skipped | error
  'new_id'      => 42,
  'message'     => '',          // error message if status === error
  'media_count' => 3,
]
```

Display log as a table in the import UI after completion.

---

## AJAX Endpoints

Register via `wp_ajax_{action}` hooks. All require nonce verification and `current_user_can('import')`.

| Action | Handler | Description |
|---|---|---|
| `wpm_export` | `WPM_Exporter::ajax_export()` | Receives page IDs, returns zip download URL |
| `wpm_preflight` | `WPM_Preflight::ajax_check()` | Receives uploaded zip path, returns check results |
| `wpm_import` | `WPM_Importer::ajax_import()` | Receives zip path + conflict mode, runs import |

---

## Security Requirements

- All AJAX handlers: verify nonce with `check_ajax_referer()`
- All AJAX handlers: verify `current_user_can('import')`
- Sanitize all inputs: `sanitize_text_field()`, `intval()`, `absint()`
- Validate uploaded file is a `.zip` using `wp_check_filetype()`
- Store temp exports in a protected directory with an `.htaccess` restricting direct access
- Delete temp zip from server after successful download or after 1 hour (use a WP Cron cleanup job)

---

## Conflict Resolution Modes

| Mode | Behaviour |
|---|---|
| `skip` | If a page with the same `post_name` exists, do not import that page |
| `overwrite` | If a page with the same `post_name` exists, update it in place (preserves ID) |
| `create_new` | Always insert as new post; append `-migrated` suffix to `post_name` if collision |

Default mode: `skip`.

---

## Special Handling Notes

### Gutenberg (core blocks)
- Block markup is stored in `post_content` as HTML comments
- No special handling required — export/import `post_content` as plain text
- URL rewriting still applies (embedded image URLs in block markup)

### ACF Repeater / Group Fields
- Sub-field values are stored as individual postmeta rows: `field_name_0_subfield`
- `get_post_meta($id)` with no key returns ALL meta rows — this captures repeater sub-fields automatically
- No special iteration needed

### ACF Image / File Fields
- These store an attachment ID as the meta value
- The `media_id_map` in `WPM_URL_Rewriter` handles remapping old → new attachment IDs

### Elementor Data
- Stored in `_elementor_data` postmeta as a JSON string
- Also check `_elementor_page_settings` for per-page settings
- After import, `files_manager->clear_cache()` regenerates the per-page CSS file

### Parent–Child Pages
- Export the full `post_parent` ID
- On import, attempt to find the parent by `post_name` on the destination
- If parent not found and not in the current export batch: set `post_parent` to `0` and log a warning

---

## Build Order (Recommended Milestones)

| Milestone | Scope | Est. Effort |
|---|---|---|
| M1 | Export UI + Data Collector + zip output (no media) | 2–3 days |
| M2 | Basic Import: read zip, write posts + meta, plain URL replace | 2–3 days |
| M3 | Media collection, upload, and ID remapping | 2 days |
| M4 | ACF field group export/import + pre-flight checks | 1–2 days |
| M5 | Conflict resolution UI, import log, Elementor CSS regen, security hardening | 2 days |

---

## Out of Scope (v1.0)

- Custom post types (pages only)
- Multisite support
- WooCommerce product pages
- Scheduled / recurring migrations
- REST API endpoint (admin AJAX only)
