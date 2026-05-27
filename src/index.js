/**
 * WordPress dependencies
 */
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './style.scss';
import App from './App';

/**
 * Initialize the application
 */
window.addEventListener( 'DOMContentLoaded', () => {
	const root = document.getElementById( 'wpm-admin-root' );
	if ( root ) {
		render( <App />, root );
	}
} );
