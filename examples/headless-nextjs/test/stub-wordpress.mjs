/**
 * Stands in for a WordPress site.
 *
 * Serves `test/fixture.json` — a saved copy of a real response from
 * GET /wp-json/answer-ready/v1/faqs/4 — on the one route this example
 * calls. Keeps the smoke test hermetic: no WordPress, no network.
 *
 * The fixture is not free-floating sample data. tests/ExampleFixtureTest.php
 * in the plugin rebuilds it from the demo content using the plugin's own
 * data layer and fails if the two disagree, so a change to the response
 * shape cannot quietly leave this example testing yesterday's contract.
 */

import { createServer } from 'node:http';
import { readFileSync } from 'node:fs';

const body = readFileSync( new URL( './fixture.json', import.meta.url ), 'utf8' );
const ROUTE = '/wp-json/answer-ready/v1/faqs/4';

export function startStub( port = 8899 ) {
	const server = createServer( ( request, response ) => {
		if ( request.url === ROUTE ) {
			response.writeHead( 200, {
				'Content-Type': 'application/json; charset=UTF-8',
				'Cache-Control': 'public, max-age=300, s-maxage=3600',
			} );
			response.end( body );
			return;
		}

		response.writeHead( 404, { 'Content-Type': 'application/json' } );
		response.end( JSON.stringify( { code: 'answer_ready_faq_not_found' } ) );
	} );

	return new Promise( ( resolve ) => server.listen( port, () => resolve( server ) ) );
}

export const fixture = JSON.parse( body );

// Allow `node test/stub-wordpress.mjs` for poking at it by hand.
if ( import.meta.url === `file://${ process.argv[ 1 ] }` ) {
	startStub().then( () => console.log( `stub WordPress on http://localhost:8899${ ROUTE }` ) );
}
