// File: wp-content/plugins/wp-page-migrator/src/components/ImportProgress.js
// Dependencies: @wordpress/components, @wordpress/element, @wordpress/i18n, ../api

/**
 * WordPress dependencies
 */
import {
	ProgressBar,
	Notice,
	__experimentalText as Text,
	__experimentalSpacer as Spacer,
	Flex,
	FlexItem,
} from '@wordpress/components';
import { useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import api from '../api';

/**
 * ImportProgress Component
 *
 * Orchestrates and displays the migration progress.
 *
 * @param {Object} props Component props.
 * @param {string} props.sessionId The session ID of the import.
 * @param {Object} props.config Configuration options for the import.
 * @param {Function} props.onComplete Callback when the import is complete.
 */
const ImportProgress = ( { sessionId, config, onComplete } ) => {
	const [ progress, setProgress ] = useState( 0 );
	const [ status, setStatus ] = useState( 'processing' );
	const [ logs, setLogs ] = useState( [] );
	const [ error, setError ] = useState( null );
	const logEndRef = useRef( null );

	/**
	 * Scroll to the bottom of the log window.
	 */
	const scrollToBottom = () => {
		logEndRef.current?.scrollIntoView( { behavior: 'smooth' } );
	};

	useEffect( () => {
		scrollToBottom();
	}, [ logs ] );

	/**
	 * Process the import step-by-step.
	 */
	const processStep = async () => {
		try {
			const response = await api.processImport( sessionId, config );

			if ( response.success ) {
				const newProgress = parseInt( response.progress || 0, 10 );
				setProgress( newProgress );
				setStatus( response.status );

				if ( response.log && response.log.length > 0 ) {
					setLogs( ( prevLogs ) => [ ...prevLogs, ...response.log ] );
				}

				if ( response.status === 'completed' ) {
					onComplete();
				} else {
					// Continue processing
					setTimeout( () => processStep(), 500 );
				}
			} else {
				setError( response.message || __( 'An error occurred during import.', 'wp-page-migrator' ) );
			}
		} catch ( err ) {
			console.error( 'Import error:', err );
			setError( err.message || __( 'A network error occurred during import.', 'wp-page-migrator' ) );
		}
	};

	useEffect( () => {
		processStep();
	}, [] );

	return (
		<div className="wpm-import-progress">
			{ error ? (
				<Notice status="error" isDismissible={ false }>
					{ error }
				</Notice>
			) : (
				<>
					<div className="wpm-progress-header">
						<Flex justify="space-between" align="center">
							<FlexItem>
								<span className="wpm-progress-status">
									{ progress === 100
										? __(
												'Migration Complete!',
												'wp-page-migrator'
										  )
										: __(
												'Migrating Content...',
												'wp-page-migrator'
										  ) }
								</span>
							</FlexItem>
							<FlexItem>
								<span className="wpm-progress-percentage">
									{ progress }%
								</span>
							</FlexItem>
						</Flex>
						<div className="wpm-progress-bar-wrap">
							<ProgressBar value={ progress } />
						</div>
					</div>

					<div className="wpm-log-header">
						<span className="wpm-log-label">
							{ __( 'Activity Log', 'wp-page-migrator' ) }
						</span>
						<div className="wpm-log-divider"></div>
					</div>

					<div className="wpm-log-window">
						{ logs.length === 0 && (
							<div className="log-entry">
								<span className="log-message">
									{ __(
										'Initializing migration process...',
										'wp-page-migrator'
									) }
								</span>
							</div>
						) }
						{ logs.map( ( log, index ) => (
							<div key={ index } className="log-entry">
								<span className="log-timestamp">
									[
									{ new Date().toLocaleTimeString( [], {
										hour12: false,
										hour: '2-digit',
										minute: '2-digit',
										second: '2-digit',
									} ) }
									]
								</span>
								<span className="log-message">{ log }</span>
							</div>
						) ) }
						<div ref={ logEndRef } />
					</div>
				</>
			) }
		</div>
	);
};

export default ImportProgress;
