// File: wp-content/plugins/wp-page-migrator/src/components/ImportTab.js
// Dependencies: @wordpress/components, @wordpress/element, @wordpress/i18n, ./FileUploader

/**
 * WordPress dependencies
 */
import { Notice } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import FileUploader from './FileUploader';
import ImportConfig from './ImportConfig';
import ImportProgress from './ImportProgress';

/**
 * Constants for Import Steps
 */
const STEPS = {
	UPLOAD: 'UPLOAD',
	CONFIG: 'CONFIG',
	PROCESSING: 'PROCESSING',
	COMPLETE: 'COMPLETE',
};

/**
 * ImportTab Component
 *
 * Manages the import state machine and UI.
 */
const ImportTab = () => {
	const [ step, setStep ] = useState( STEPS.UPLOAD );
	const [ sessionData, setSessionData ] = useState( null );
	const [ config, setConfig ] = useState( null );

	/**
	 * Handle successful file upload.
	 *
	 * @param {Object} data Response data from /import/upload.
	 */
	const handleUploadSuccess = ( data ) => {
		setSessionData( data );
		setStep( STEPS.CONFIG );
	};

	/**
	 * Handle starting the import process.
	 *
	 * @param {Object} importConfig The configuration for the import.
	 */
	const handleStartImport = ( importConfig ) => {
		setConfig( importConfig );
		setStep( STEPS.PROCESSING );
	};

	/**
	 * Handle import completion.
	 */
	const handleImportComplete = () => {
		setStep( STEPS.COMPLETE );
	};

	/**
	 * Render the current step component.
	 */
	const renderStep = () => {
		switch ( step ) {
			case STEPS.UPLOAD:
				return <FileUploader onUploadSuccess={ handleUploadSuccess } />;

			case STEPS.CONFIG:
				return (
					<ImportConfig
						sessionData={ sessionData }
						onStart={ handleStartImport }
					/>
				);

			case STEPS.PROCESSING:
				return (
					<ImportProgress
						sessionId={ sessionData?.session_id }
						config={ config }
						onComplete={ handleImportComplete }
					/>
				);

			case STEPS.COMPLETE:
				return (
					<Notice status="success" isDismissible={ false }>
						{ __( 'Import complete!', 'wp-page-migrator' ) }
					</Notice>
				);

			default:
				return null;
		}
	};

	return (
		<div className="wpm-import-tab">
			<div className="wpm-stepper">
				<div
					className={ `wpm-step ${
						step === STEPS.UPLOAD ? 'is-active' : ''
					} ${
						[
							STEPS.CONFIG,
							STEPS.PROCESSING,
							STEPS.COMPLETE,
						].includes( step )
							? 'is-completed'
							: ''
					}` }
				>
					<div className="wpm-step-circle">1</div>
					<div className="wpm-step-label">
						{ __( 'Upload', 'wp-page-migrator' ) }
					</div>
				</div>
				<div
					className={ `wpm-step ${
						step === STEPS.CONFIG ? 'is-active' : ''
					} ${
						[ STEPS.PROCESSING, STEPS.COMPLETE ].includes( step )
							? 'is-completed'
							: ''
					}` }
				>
					<div className="wpm-step-circle">2</div>
					<div className="wpm-step-label">
						{ __( 'Configure', 'wp-page-migrator' ) }
					</div>
				</div>
				<div
					className={ `wpm-step ${
						step === STEPS.PROCESSING ? 'is-active' : ''
					} ${ step === STEPS.COMPLETE ? 'is-completed' : '' }` }
				>
					<div className="wpm-step-circle">3</div>
					<div className="wpm-step-label">
						{ __( 'Migrate', 'wp-page-migrator' ) }
					</div>
				</div>
			</div>

			<div className="wpm-card">
				<div className="wpm-card-header">
					<div>
						<h3>
							{ step === STEPS.UPLOAD &&
								__(
									'Step 1: Upload Migration File',
									'wp-page-migrator'
								) }
							{ step === STEPS.CONFIG &&
								__(
									'Step 2: Configure Import',
									'wp-page-migrator'
								) }
							{ step === STEPS.PROCESSING &&
								__(
									'Step 3: Processing Import',
									'wp-page-migrator'
								) }
							{ step === STEPS.COMPLETE &&
								__( 'Import Successful', 'wp-page-migrator' ) }
						</h3>
						<p>
							{ step === STEPS.UPLOAD &&
								__(
									'Select the ZIP file containing your exported pages.',
									'wp-page-migrator'
								) }
							{ step === STEPS.CONFIG &&
								__(
									'Review and adjust how your pages will be imported.',
									'wp-page-migrator'
								) }
							{ step === STEPS.PROCESSING &&
								__(
									'Please wait while we migrate your content.',
									'wp-page-migrator'
								) }
							{ step === STEPS.COMPLETE &&
								__(
									'Content migration has finished successfully.',
									'wp-page-migrator'
								) }
						</p>
					</div>
				</div>
				<div style={ { padding: '24px' } }>{ renderStep() }</div>
			</div>

			{ step === STEPS.UPLOAD && (
				<div className="wpm-import-notice-wrap">
					<Notice status="warning" isDismissible={ false }>
						{ __(
							'Importing pages will overwrite existing pages with the same slug. Please ensure you have a backup of your site before proceeding.',
							'wp-page-migrator'
						) }
					</Notice>
				</div>
			) }
		</div>
	);
};

export default ImportTab;
