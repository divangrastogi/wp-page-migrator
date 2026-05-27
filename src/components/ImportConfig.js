// File: wp-content/plugins/wp-page-migrator/src/components/ImportConfig.js
// Dependencies: @wordpress/components, @wordpress/element, @wordpress/i18n

/**
 * WordPress dependencies
 */
import {
	Button,
	RadioControl,
	TextControl,
	Notice,
	__experimentalText as Text,
	__experimentalSpacer as Spacer,
	Flex,
	FlexItem,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * ImportConfig Component
 *
 * Allows users to configure import options before starting the process.
 *
 * @param {Object} props Component props.
 * @param {Object} props.sessionData Data returned from the upload step.
 * @param {Function} props.onStart Callback to start the import process.
 */
const ImportConfig = ( { sessionData, onStart } ) => {
	const [ conflictMode, setConflictMode ] = useState( 'skip' );
	const [ mediaBatchSize, setMediaBatchSize ] = useState( 5 );
	const [ postBatchSize, setPostBatchSize ] = useState( 10 );

	const preflight = sessionData?.preflight || {};
	const warnings = preflight.warnings || [];
	const info = preflight.info || [];

	const handleStart = () => {
		onStart( {
			conflict_mode: conflictMode,
			media_batch_size: parseInt( mediaBatchSize, 10 ),
			post_batch_size: parseInt( postBatchSize, 10 ),
		} );
	};

	return (
		<div className="wpm-import-config">
			{ warnings.length > 0 && (
				<div className="wpm-config-notice">
					<Notice status="warning" isDismissible={ false }>
						<div className="wpm-notice-title">
							{ __(
								'Potential Issues Found:',
								'wp-page-migrator'
							) }
						</div>
						<ul className="wpm-notice-list">
							{ warnings.map( ( warning, index ) => (
								<li key={ index }>{ warning }</li>
							) ) }
						</ul>
					</Notice>
				</div>
			) }

			{ info.length > 0 && (
				<div className="wpm-config-notice">
					<Notice status="info" isDismissible={ false }>
						<div className="wpm-notice-title">
							{ __( 'Import Summary:', 'wp-page-migrator' ) }
						</div>
						<ul className="wpm-notice-list">
							{ info.map( ( item, index ) => (
								<li key={ index }>{ item }</li>
							) ) }
						</ul>
					</Notice>
				</div>
			) }

			<div className="wpm-config-section">
				<RadioControl
					label={
						<span className="wpm-config-label">
							{ __( 'Conflict Resolution', 'wp-page-migrator' ) }
						</span>
					}
					selected={ conflictMode }
					options={ [
						{
							label: __(
								'Skip - Keep existing pages',
								'wp-page-migrator'
							),
							value: 'skip',
						},
						{
							label: __(
								'Overwrite - Replace existing pages',
								'wp-page-migrator'
							),
							value: 'overwrite',
						},
						{
							label: __(
								'Create New - Keep both versions',
								'wp-page-migrator'
							),
							value: 'create_new',
						},
					] }
					onChange={ ( value ) => setConflictMode( value ) }
				/>
			</div>

			<div className="wpm-config-section">
				<h4 className="wpm-section-title">
					{ __( 'Performance Settings', 'wp-page-migrator' ) }
				</h4>
				<Flex align="flex-start" gap={ 6 }>
					<FlexItem isBlock>
						<TextControl
							label={ __(
								'Media Batch Size',
								'wp-page-migrator'
							) }
							type="number"
							value={ mediaBatchSize }
							min={ 1 }
							max={ 50 }
							onChange={ ( value ) => setMediaBatchSize( value ) }
							help={ __(
								'Images per request.',
								'wp-page-migrator'
							) }
						/>
					</FlexItem>
					<FlexItem isBlock>
						<TextControl
							label={ __(
								'Page Batch Size',
								'wp-page-migrator'
							) }
							type="number"
							value={ postBatchSize }
							min={ 1 }
							max={ 50 }
							onChange={ ( value ) => setPostBatchSize( value ) }
							help={ __(
								'Pages per request.',
								'wp-page-migrator'
							) }
						/>
					</FlexItem>
				</Flex>
			</div>

			<Button
				variant="primary"
				onClick={ handleStart }
				className="wpm-start-button"
			>
				{ __( 'Start Import Process', 'wp-page-migrator' ) }
			</Button>
		</div>
	);
};

export default ImportConfig;
