// File: wp-content/plugins/wp-page-migrator/src/components/FileUploader.js
// Dependencies: @wordpress/components, @wordpress/element, @wordpress/i18n, ../api

/**
 * WordPress dependencies
 */
import {
	Button,
	FormFileUpload,
	Spinner,
	Notice,
	Icon,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { upload } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import api from '../api';

/**
 * FileUploader Component
 *
 * Handles the selection and upload of the migration ZIP file.
 *
 * @param {Object} props
 * @param {Function} props.onUploadSuccess Callback when upload completes.
 */
const FileUploader = ( { onUploadSuccess } ) => {
	const [ isUploading, setIsUploading ] = useState( false );
	const [ isDragging, setIsDragging ] = useState( false );
	const [ error, setError ] = useState( null );

	const handleFileChange = async ( event ) => {
		const file = event.target.files[ 0 ];
		if ( ! file ) {
			return;
		}

		// Validate file type (basic)
		if (
			file.type !== 'application/zip' &&
			! file.name.endsWith( '.zip' )
		) {
			setError(
				__( 'Please select a valid ZIP file.', 'wp-page-migrator' )
			);
			return;
		}

		setIsUploading( true );
		setError( null );

		try {
			const response = await api.uploadFile( file );

			if ( response && response.session_id ) {
				onUploadSuccess( response );
			} else {
				throw new Error(
					__( 'Invalid response from server.', 'wp-page-migrator' )
				);
			}
		} catch ( err ) {
			setError(
				err.message ||
					__( 'An error occurred during upload.', 'wp-page-migrator' )
			);
		} finally {
			setIsUploading( false );
		}
	};

	const onDragOver = ( e ) => {
		e.preventDefault();
		setIsDragging( true );
	};

	const onDragLeave = () => {
		setIsDragging( false );
	};

	const onDrop = () => {
		setIsDragging( false );
	};

	return (
		<div className="wpm-file-uploader">
			{ error && (
				<Notice
					status="error"
					isDismissible={ true }
					onDismiss={ () => setError( null ) }
					style={ { marginBottom: '20px' } }
				>
					{ error }
				</Notice>
			) }

			<FormFileUpload
				accept=".zip"
				onChange={ handleFileChange }
				disabled={ isUploading }
				render={ ( { openFileDialog } ) => (
					<div
						className={ `wpm-file-dropzone ${
							isDragging ? 'is-dragging' : ''
						} ${ isUploading ? 'is-uploading' : '' }` }
						onClick={ ! isUploading ? openFileDialog : undefined }
						onDragOver={ onDragOver }
						onDragLeave={ onDragLeave }
						onDrop={ onDrop }
					>
						<div className="wpm-upload-icon">
							{ isUploading ? (
								<Spinner />
							) : (
								<Icon icon={ upload } size={ 48 } />
							) }
						</div>
						<h4>
							{ isUploading
								? __(
										'Uploading Migration File...',
										'wp-page-migrator'
								  )
								: __(
										'Click or drag ZIP file here',
										'wp-page-migrator'
								  ) }
						</h4>
						<p>
							{ __(
								'Only .zip files exported from WP Page Migrator are supported.',
								'wp-page-migrator'
							) }
						</p>

						{ ! isUploading && (
							<div style={ { marginTop: '24px' } }>
								<Button variant="secondary">
									{ __( 'Select File', 'wp-page-migrator' ) }
								</Button>
							</div>
						) }
					</div>
				) }
			/>
		</div>
	);
};

export default FileUploader;
