/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * API Service for WP Page Migrator
 */
const api = {
	/**
	 * Get all pages from the site.
	 *
	 * @param {Object} params Optional query parameters.
	 * @returns {Promise}
	 */
	getPages( params = {} ) {
		const queryParams = new URLSearchParams( params ).toString();
		const path = `/wpm/v1/pages${ queryParams ? '?' + queryParams : '' }`;

		return apiFetch( {
			path: path,
			method: 'GET',
		} );
	},

	/**
	 * Start the export process for selected pages.
	 *
	 * @param {Array} pageIds Array of page IDs to export.
	 * @returns {Promise}
	 */
	startExport( pageIds ) {
		return apiFetch( {
			path: '/wpm/v1/export/start',
			method: 'POST',
			data: {
				page_ids: pageIds,
			},
		} );
	},

	/**
	 * Process an export session.
	 *
	 * @param {string} sessionId The session ID to process.
	 * @returns {Promise}
	 */
	processExport( sessionId ) {
		return apiFetch( {
			path: '/wpm/v1/export/process',
			method: 'POST',
			data: {
				session_id: sessionId,
			},
		} );
	},

	/**
	 * Upload a migration ZIP file.
	 *
	 * @param {File} file The ZIP file to upload.
	 * @returns {Promise}
	 */
	uploadFile( file ) {
		const formData = new FormData();
		formData.append( 'file', file );

		return apiFetch( {
			path: '/wpm/v1/import/upload',
			method: 'POST',
			body: formData,
		} );
	},

	/**
	 * Import pages from a file.
	 *
	 * @param {File} file The file to import.
	 * @returns {Promise}
	 */
	importPages( file ) {
		const formData = new FormData();
		formData.append( 'import_file', file );

		return apiFetch( {
			path: '/wpm/v1/import',
			method: 'POST',
			body: formData,
			// apiFetch handles multipart/form-data if body is FormData
		} );
	},

	/**
	 * Process an import session.
	 *
	 * @param {string} sessionId The session ID to process.
	 * @param {Object} config Import configuration options.
	 * @returns {Promise}
	 */
	processImport( sessionId, config = {} ) {
		return apiFetch( {
			path: '/wpm/v1/import/process',
			method: 'POST',
			data: {
				session_id: sessionId,
				config: config,
			},
		} );
	},
};

export default api;
