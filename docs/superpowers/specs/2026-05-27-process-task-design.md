# Design Document - ProcessTask (Import)

## Goal
Implement `WPM\Tasks\Import\ProcessTask` to handle the actual insertion/updating of pages during the import process.

## Architecture
- **Namespace:** `WPM\Tasks\Import`
- **Inheritance:** `WPM\Core\TaskBase`
- **Primary Method:** `run()`

## Data Structure
Expected input in `$this->data`:
- `pages`: Array of page objects, each containing:
    - `post`: Array of post data compatible with `wp_insert_post`.
    - `meta`: Array of post meta (key => value).
    - `terms`: Array of terms (taxonomy => array of term data).
- `conflict_mode`: String (`skip`, `overwrite`, `create_new`).

## Logic Flow
1. Initialize `$results = ['created' => 0, 'updated' => 0, 'skipped' => 0]`.
2. Verify `$this->data['pages']` is present.
3. Iterate through each page entry:
    - Extract `post_data`, `meta`, and `terms`.
    - Check for existing post with the same `post_name` (slug) and `post_type`.
    - **Conflict Resolution:**
        - If exists and `conflict_mode == 'skip'`: Increment `skipped` and continue.
        - If exists and `conflict_mode == 'overwrite'`: Set `ID` in `post_data` to the existing post ID.
        - If exists and `conflict_mode == 'create_new'`: Let `wp_insert_post` handle slug uniqueness if we don't pass an ID, or manually append suffix if needed. Actually, `wp_insert_post` will auto-generate a unique slug if `post_name` is taken and we aren't updating.
    - Call `wp_insert_post( $post_data )`.
    - If successful:
        - Update meta: Loop through `meta` and call `update_post_meta`.
        - Update terms: Loop through `terms` (taxonomy => terms) and call `wp_set_object_terms`.
        - Increment `created` or `updated` based on whether an ID was provided/used.
4. Return `['status' => 'done', 'summary' => $results]`.

## Security
- Use `wp_insert_post` which handles most sanitization, but ensures data is cleaned where necessary.
- Verify capabilities if this were a direct request, but as a task it's executed within a session.

## Error Handling
- If `wp_insert_post` returns a `WP_Error`, log it or include in results (though the requirement asks for a summary).
