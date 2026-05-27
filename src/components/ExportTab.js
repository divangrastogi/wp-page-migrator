/**
 * WordPress dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import {
	Button,
	Spinner,
	SearchControl,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import api from '../api';

/**
 * ExportTab Component - Final Debugged Version
 */
const ExportTab = () => {
	const [ pages, setPages ] = useState( [] );
	const [ selectedPages, setSelectedPages ] = useState( [] );
	const [ searchTerm, setSearchTerm ] = useState( '' );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ isExporting, setIsExporting ] = useState( false );
	const [ progress, setProgress ] = useState( 0 );
	const [ message, setMessage ] = useState( null );
	const [ downloadUrl, setDownloadUrl ] = useState( null );
	const [ error, setError ] = useState( null );

	// Fetch pages on mount
	useEffect( () => {
		api.getPages( { per_page: 100 } )
			.then( ( data ) => {
				setPages( Array.isArray( data ) ? data : [] );
				setIsLoading( false );
			} )
			.catch( ( err ) => {
				setError( __( 'Failed to fetch pages.', 'wp-page-migrator' ) );
				setIsLoading( false );
			} );
	}, [] );

	const togglePageSelection = ( pageId ) => {
		setSelectedPages( ( prev ) => {
			if ( prev.includes( pageId ) ) {
				return prev.filter( ( id ) => id !== pageId );
			}
			return [ ...prev, pageId ];
		} );
	};

	const handleExport = async () => {
		if ( selectedPages.length === 0 ) {
			setError( __( 'Please select at least one page to export.', 'wp-page-migrator' ) );
			return;
		}

		setIsExporting( true );
		setError( null );
		setMessage( null );
		setProgress( 0 );
		setDownloadUrl( null );

		try {
			const { session_id } = await api.startExport( selectedPages );
			pollExport( session_id );
		} catch ( err ) {
			setError( __( 'Failed to start export.', 'wp-page-migrator' ) );
			setIsExporting( false );
		}
	};

	const pollExport = async ( sessionId ) => {
		try {
			const response = await api.processExport( sessionId );
			
			const currentProgress = parseInt( response.progress || 0, 10 );
			setProgress( currentProgress );

			if ( response.status === 'completed' ) {
				setIsExporting( false );
				setMessage( __( 'Export completed successfully!', 'wp-page-migrator' ) );

				const data = response.data || {};
				const zipResult = data.ZipTask || data;
				
				if ( zipResult && zipResult.url ) {
					setDownloadUrl( zipResult.url );
				}
			} else if ( response.status === 'processing' ) {
				setTimeout( () => pollExport( sessionId ), 1000 );
			}
		} catch ( err ) {
			console.error( 'Export error:', err );
			setError( __( 'Error during export processing.', 'wp-page-migrator' ) );
			setIsExporting( false );
		}
	};

	const filteredPages = Array.isArray( pages ) ? pages.filter( ( page ) => {
		if ( ! page ) return false;
		const pageTitle = page.title || '';
		const titleStr = typeof pageTitle === 'string' ? pageTitle : ( pageTitle.rendered || '' );
		return titleStr.toLowerCase().includes( ( searchTerm || '' ).toLowerCase() );
	} ) : [];

	let currentStep = 1;
	if ( isExporting ) currentStep = 2;
	if ( message ) currentStep = 3;

	if ( isLoading ) {
		return (
			<div className="wpm-loading" style={ { padding: '50px', textAlign: 'center' } }>
				<Spinner />
				<p>{ __( 'Loading pages...', 'wp-page-migrator' ) }</p>
			</div>
		);
	}

	return (
		<div className="wpm-tab-content">
			<div className="wpm-stepper">
				<div className={ `wpm-step ${ currentStep >= 1 ? 'is-active' : '' } ${ currentStep > 1 ? 'is-completed' : '' }` }>
					<div className="wpm-step-circle">{ currentStep > 1 ? '✓' : '1' }</div>
					<div className="wpm-step-label">{ __( 'Select', 'wp-page-migrator' ) }</div>
				</div>
				<div className={ `wpm-step ${ currentStep >= 2 ? 'is-active' : '' } ${ currentStep > 2 ? 'is-completed' : '' }` }>
					<div className="wpm-step-circle">{ currentStep > 2 ? '✓' : '2' }</div>
					<div className="wpm-step-label">{ __( 'Migrate', 'wp-page-migrator' ) }</div>
				</div>
				<div className={ `wpm-step ${ currentStep >= 3 ? 'is-active' : '' }` }>
					<div className="wpm-step-circle">3</div>
					<div className="wpm-step-label">{ __( 'Complete', 'wp-page-migrator' ) }</div>
				</div>
			</div>

			{ error && (
				<div className="wpm-error-notice" style={ { padding: '15px', background: '#fee2e2', color: '#991b1b', borderRadius: '8px', marginBottom: '20px' } }>
					{ error }
				</div>
			) }

			{ message && (
				<div className="wpm-success-notice" style={ { padding: '20px', background: '#dcfce7', color: '#166534', borderRadius: '12px', marginBottom: '20px', display: 'flex', alignItems: 'center', justifyContent: 'space-between' } }>
					<span>{ message }</span>
					{ downloadUrl && (
						<Button variant="primary" href={ downloadUrl } download target="_blank">
							{ __( 'Download Export Package', 'wp-page-migrator' ) }
						</Button>
					) }
				</div>
			) }

			<div className="wpm-card">
				<div className="wpm-card-header">
					<div>
						<h3>{ __( 'Select Pages to Export', 'wp-page-migrator' ) }</h3>
						<p>{ __( 'Choose the pages you want to migrate to another site.', 'wp-page-migrator' ) }</p>
					</div>
					<div className="wpm-header-actions">
						<div className="wpm-search-wrapper">
							<span className="wpm-search-icon">
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
									<circle cx="11" cy="11" r="8" /><line x1="21" y1="21" x2="16.65" y2="16.65" />
								</svg>
							</span>
							<SearchControl
								label={ __( 'Search pages', 'wp-page-migrator' ) }
								hideLabelFromVision={ true }
								value={ searchTerm || '' }
								onChange={ ( val ) => setSearchTerm( val || '' ) }
								placeholder={ __( 'Search pages...', 'wp-page-migrator' ) }
							/>
						</div>
					</div>
				</div>

				<div className="wpm-card-body">
					{ isExporting ? (
						<div className="wpm-export-progress" style={ { padding: '40px', textAlign: 'center' } }>
							<div style={ { width: '100%', height: '10px', background: '#e2e8f0', borderRadius: '5px', overflow: 'hidden' } }>
								<div style={ { width: `${ progress || 0 }%`, height: '100%', background: '#4f46e5', transition: 'width 0.3s ease' } }></div>
							</div>
							<p style={ { marginTop: '20px', fontWeight: '500' } }>
								{ __( 'Processing export...', 'wp-page-migrator' ) } { progress || 0 }%
							</p>
						</div>
					) : (
						<>
							<div className="wpm-list-header">
								<div className="wpm-col-check">
									<input
										type="checkbox"
										checked={ selectedPages.length > 0 && selectedPages.length === filteredPages.length }
										onChange={ ( e ) => {
											if ( e.target.checked ) {
												setSelectedPages( filteredPages.map( ( p ) => p.id ) );
											} else {
												setSelectedPages( [] );
											}
										} }
									/>
								</div>
								<div className="wpm-col-title">{ __( 'Page Title', 'wp-page-migrator' ) }</div>
								<div className="wpm-col-status">{ __( 'Status', 'wp-page-migrator' ) }</div>
								<div className="wpm-col-date">{ __( 'Date', 'wp-page-migrator' ) }</div>
							</div>

							<div className="wpm-page-list" style={ { maxHeight: '400px', overflowY: 'auto' } }>
								{ filteredPages.length === 0 ? (
									<div style={ { padding: '40px', textAlign: 'center' } } className="wpm-text-muted">
										{ __( 'No pages found.', 'wp-page-migrator' ) }
									</div>
								) : (
									filteredPages.map( ( page ) => (
										<div
											key={ page.id }
											className={ `wpm-list-item ${ selectedPages.includes( page.id ) ? 'is-selected' : '' }` }
											onClick={ () => togglePageSelection( page.id ) }
										>
											<div className="wpm-col-check">
												<div className="wpm-item-checkbox">
													<input
														type="checkbox"
														checked={ selectedPages.includes( page.id ) }
														readOnly
													/>
												</div>
											</div>
											<div className="wpm-col-title">
												<div className="wpm-item-title">{ page.title }</div>
											</div>
											<div className="wpm-col-status">
												<div className="wpm-item-status">
													<span className={ `wpm-badge ${ page.status === 'publish' ? 'is-published' : '' }` }>
														{ page.status }
													</span>
												</div>
											</div>
											<div className="wpm-col-date">
												<div className="wpm-item-date">
													{ new Date( page.date ).toLocaleDateString() }
												</div>
											</div>
										</div>
									) )
								) }
							</div>
						</>
					) }
				</div>

				<div className="wpm-card-footer">
					<div style={ { marginRight: 'auto' } }>
						{ selectedPages.length > 0 && ! isExporting && (
							<span className="wpm-text-muted">
								{ sprintf( __( '%d pages selected', 'wp-page-migrator' ), selectedPages.length ) }
							</span>
						) }
					</div>
					<Button
						variant="primary"
						onClick={ handleExport }
						disabled={ selectedPages.length === 0 || isExporting }
						className="wpm-button-large"
					>
						{ isExporting ? __( 'Exporting...', 'wp-page-migrator' ) : __( 'Export Selected', 'wp-page-migrator' ) }
					</Button>
				</div>
			</div>
		</div>
	);
};

export default ExportTab;
