# ProcessTask (Import) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement `WPM\Tasks\Import\ProcessTask` to handle the actual insertion/updating of pages during the import process.

**Architecture:** This task inherits from `WPM\Core\TaskBase`. It processes an array of page data, handling conflict resolution (`skip`, `overwrite`, `create_new`) and updating post meta and terms.

**Tech Stack:** WordPress, PHP 7.4.

---

### Task 1: Create ProcessTask Class

**Files:**
- Create: `includes/Tasks/Import/ProcessTask.php`

- [ ] **Step 1: Write the implementation**

```php
<?php
/**
 * ProcessTask Class
 *
 * @package WPM\Tasks\Import
 */

namespace WPM\Tasks\Import;

use WPM\Core\TaskBase;

// File: wp-content/plugins/wp-page-migrator/includes/Tasks/Import/ProcessTask.php
// Hooks: Inherits from TaskBase.
// DB: Writes to wp_posts, wp_postmeta, wp_term_relationships.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ProcessTask
 *
 * Handles the insertion and update of pages during import.
 */
class ProcessTask extends TaskBase {

	/**
	 * Run the task.
	 *
	 * Iterates through provided pages and inserts/updates them.
	 *
	 * @return array {
	 *     @type string $status  'done' or 'error'.
	 *     @type array  $summary Count of created, updated, skipped.
	 * }
	 */
	public function run(): array {
		$pages         = isset( $this->data['pages'] ) ? (array) $this->data['pages'] : array();
		$conflict_mode = isset( $this->data['conflict_mode'] ) ? $this->data['conflict_mode'] : 'skip';

		$summary = array(
			'created' => 0,
			'updated' => 0,
			'skipped' => 0,
			'errors'  => 0,
		);

		if ( empty( $pages ) ) {
			return array(
				'status'  => 'done',
				'summary' => $summary,
			);
		}

		foreach ( $pages as $page_data ) {
			if ( ! isset( $page_data['post'] ) ) {
				$summary['errors']++;
				continue;
			}

			$post_data = (array) $page_data['post'];
			$meta      = isset( $page_data['meta'] ) ? (array) $page_data['meta'] : array();
			$terms     = isset( $page_data['terms'] ) ? (array) $page_data['terms'] : array();

			$existing_post_id = $this->get_existing_post_id( $post_data['post_name'], $post_data['post_type'] );

			if ( $existing_post_id ) {
				if ( 'skip' === $conflict_mode ) {
					$summary['skipped']++;
					continue;
				}

				if ( 'overwrite' === $conflict_mode ) {
					$post_data['ID'] = $existing_post_id;
				}

				if ( 'create_new' === $conflict_mode ) {
					// unset ID to ensure new post
					unset( $post_data['ID'] );
					// wp_insert_post will handle slug uniqueness if we don't force post_name, 
					// or we can let it be and it will append suffix.
					// To be safe, we unset post_name so WP generates a unique one based on title,
					// OR we keep it and WP appends -2 etc.
				}
			} else {
				// New post anyway
				unset( $post_data['ID'] );
			}

			// Ensure post_author is current user or 0
			$post_data['post_author'] = get_current_user_id();

			$post_id = wp_insert_post( $post_data, true );

			if ( is_wp_error( $post_id ) ) {
				$summary['errors']++;
				continue;
			}

			if ( $existing_post_id && 'overwrite' === $conflict_mode ) {
				$summary['updated']++;
			} else {
				$summary['created']++;
			}

			// Update Meta
			$this->update_meta( $post_id, $meta );

			// Update Terms
			$this->update_terms( $post_id, $terms );
		}

		/* ── BACKEND CONTRACT FULFILLED ─────────────────────
		   Task     : ProcessTask (Import)
		   Action   : run()
		   Input    : array $this->data['pages'], string $this->data['conflict_mode']
		   Output   : { status: string, summary: array }
		   Details  : Inserts or updates posts, meta, and terms.
		   ─────────────────────────────────────────────── */

		return array(
			'status'  => 'done',
			'summary' => $summary,
		);
	}

	/**
	 * Find existing post ID by slug and type.
	 *
	 * @param string $slug Post slug.
	 * @param string $type Post type.
	 * @return int|null Post ID or null.
	 */
	private function get_existing_post_id( $slug, $type ) {
		global $wpdb;
		$id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_type = %s AND post_status != 'trash' LIMIT 1",
				$slug,
				$type
			)
		);
		return $id ? (int) $id : null;
	}

	/**
	 * Update post meta.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $meta    Meta data (key => value or key => [values]).
	 */
	private function update_meta( $post_id, $meta ) {
		foreach ( $meta as $key => $values ) {
			// PageDataTask returns meta as array of values
			if ( is_array( $values ) ) {
				delete_post_meta( $post_id, $key );
				foreach ( $values as $value ) {
					add_post_meta( $post_id, $key, maybe_unserialize( $value ) );
				}
			} else {
				update_post_meta( $post_id, $key, maybe_unserialize( $values ) );
			}
		}
	}

	/**
	 * Update post terms.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $terms   Terms data (taxonomy => terms).
	 */
	private function update_terms( $post_id, $terms ) {
		foreach ( $terms as $taxonomy => $term_list ) {
			$slugs = array();
			foreach ( $term_list as $term_data ) {
				if ( ! taxonomy_exists( $taxonomy ) ) {
					continue;
				}

				// Check if term exists, if not create it
				$term = get_term_by( 'slug', $term_data['slug'], $taxonomy );
				if ( ! $term ) {
					$new_term = wp_insert_term(
						$term_data['name'],
						$taxonomy,
						array(
							'slug'        => $term_data['slug'],
							'description' => $term_data['description'],
							'parent'      => $term_data['parent'], // Note: this might need mapping if parent ID changed
						)
					);
					if ( ! is_wp_error( $new_term ) ) {
						$slugs[] = $term_data['slug'];
					}
				} else {
					$slugs[] = $term->slug;
				}
			}

			if ( ! empty( $slugs ) ) {
				wp_set_object_terms( $post_id, $slugs, $taxonomy );
			}
		}
	}
}
```

- [ ] **Step 2: Save the file**

### Task 2: Verify Autoloading

**Files:**
- Test: Manual check or small script.

- [ ] **Step 1: Verify the file is in the correct place for the autoloader**

The autoloader maps `WPM\Tasks\Import\ProcessTask` to `includes/Tasks/Import/ProcessTask.php`.

Run:
```php
if ( class_exists( 'WPM\Tasks\Import\ProcessTask' ) ) {
    echo "Class loaded successfully.";
} else {
    echo "Class failed to load.";
}
```

---
