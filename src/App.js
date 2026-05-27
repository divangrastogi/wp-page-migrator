/**
 * WordPress dependencies
 */
import { TabPanel } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ExportTab from './components/ExportTab';
import ImportTab from './components/ImportTab';

/**
 * Main App component for WP Page Migrator
 */
const App = () => {
	const tabs = [
		{
			name: 'export',
			title: __( 'Export', 'wp-page-migrator' ),
			className: 'wpm-tab-export',
		},
		{
			name: 'import',
			title: __( 'Import', 'wp-page-migrator' ),
			className: 'wpm-tab-import',
		},
	];

	const onSelect = ( tabName ) => {
		console.log( 'Selected tab:', tabName );
	};

	return (
		<div className="wpm-admin-wrap">
			<header className="wpm-header">
				<h1>{ __( 'WP Page Migrator', 'wp-page-migrator' ) }</h1>
			</header>

			<div className="wpm-main-content">
				<TabPanel
					className="wpm-tab-panel"
					activeClass="is-active"
					onSelect={ onSelect }
					tabs={ tabs }
				>
					{ ( tab ) => {
						if ( tab.name === 'export' ) {
							return (
								<div className="wpm-tab-content">
									<ExportTab />
								</div>
							);
						}
						if ( tab.name === 'import' ) {
							return (
								<div className="wpm-tab-content">
									<ImportTab />
								</div>
							);
						}
						return null;
					} }
				</TabPanel>
			</div>
		</div>
	);
};

export default App;
