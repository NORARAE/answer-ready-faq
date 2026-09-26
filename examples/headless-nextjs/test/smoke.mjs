/**
 * Smoke test: does this example still build and render against the
 * endpoint's response?
 *
 * Builds the app for real and serves it, rather than asserting on source,
 * because the failure worth catching is a response shape the page no longer
 * knows how to read — and `getFaqPage()` deliberately swallows fetch errors
 * so a visitor gets a sentence instead of a stack trace. That means a broken
 * build target still *renders*, just with the "Not connected yet" state, so
 * this asserts on the content rather than on the exit code alone.
 *
 * Usage: npm run test:smoke
 */

import { spawn } from 'node:child_process';
import { rm } from 'node:fs/promises';
import { startStub, fixture } from './stub-wordpress.mjs';

const STUB_PORT = 8899;
const APP_PORT = 3111;
const env = {
	...process.env,
	WORDPRESS_URL: `http://localhost:${ STUB_PORT }`,
	WORDPRESS_FAQ_POST_ID: '4',
	NEXT_TELEMETRY_DISABLED: '1',
};

const failures = [];

function check( description, condition ) {
	if ( condition ) {
		console.log( `  ✓ ${ description }` );
	} else {
		console.log( `  ✗ ${ description }` );
		failures.push( description );
	}
}

function run( command, args ) {
	return new Promise( ( resolve, reject ) => {
		const child = spawn( command, args, { env, stdio: 'inherit' } );
		child.on( 'exit', ( code ) =>
			code === 0 ? resolve() : reject( new Error( `${ command } ${ args.join( ' ' ) } exited ${ code }` ) )
		);
	} );
}

async function waitForServer( url, attempts = 60 ) {
	for ( let attempt = 0; attempt < attempts; attempt++ ) {
		try {
			const response = await fetch( url );
			if ( response.ok ) {
				return;
			}
		} catch {
			// Not listening yet.
		}
		await new Promise( ( resolve ) => setTimeout( resolve, 500 ) );
	}
	throw new Error( `${ url } never came up` );
}

// Next caches fetch responses across builds (`revalidate: 300` in
// wordpress.js), so a second run can rebuild from the previous run's copy
// of the response and render content the stub is no longer serving. That
// would let a genuinely broken payload pass. Start from nothing.
await rm( new URL( '../.next', import.meta.url ), { recursive: true, force: true } );

const stub = await startStub( STUB_PORT );
console.log( `\nStub WordPress listening on ${ STUB_PORT }\n` );

let server;

try {
	console.log( '→ next build\n' );
	await run( 'npx', [ 'next', 'build' ] );

	console.log( '\n→ next start\n' );
	server = spawn( 'npx', [ 'next', 'start', '-p', String( APP_PORT ) ], { env, stdio: 'inherit' } );
	await waitForServer( `http://localhost:${ APP_PORT }/` );

	const html = await ( await fetch( `http://localhost:${ APP_PORT }/` ) ).text();
	const jsonLd = html.match( /<script type="application\/ld\+json">(.*?)<\/script>/s );
	const head = html.split( '</head>' )[ 0 ];

	console.log( '\nChecking the rendered page:' );

	const faqs = Array.isArray( fixture.faqs ) ? fixture.faqs : [];

	// Guards the case this test exists for: a response whose shape the page
	// no longer recognises. Rename `faqs` upstream and everything below it
	// silently has nothing to assert on unless we check the shape first.
	check( 'saved response still carries a non-empty `faqs` array', faqs.length > 0 );
	check( 'rendered the connected state, not the error state', ! html.includes( 'Not connected yet' ) );
	check( 'used the page title from the endpoint', html.includes( fixture.title ) );

	for ( const faq of faqs ) {
		check( `rendered: ${ faq.question }`, html.includes( faq.question ) );
	}

	// Independent of the fixture's field names: did the accordion actually
	// draw one row per answer?
	const summaries = ( html.match( /<summary>/g ) || [] ).length;
	check( `drew ${ faqs.length } disclosure rows (found ${ summaries })`, summaries === faqs.length );

	check( 'emitted FAQPage JSON-LD', Boolean( jsonLd ) );
	check( 'put the JSON-LD in <head>', head.includes( 'application/ld+json' ) );

	if ( jsonLd ) {
		let graph = null;
		try {
			graph = JSON.parse( jsonLd[ 1 ] );
		} catch {
			// Left null; the next check reports it.
		}
		check( 'JSON-LD parses', graph !== null );
		check(
			'published WordPress’s graph unchanged',
			JSON.stringify( graph ) === JSON.stringify( fixture.schema )
		);
	}
} finally {
	server?.kill();
	stub.close();
}

if ( failures.length ) {
	console.error( `\n${ failures.length } check(s) failed:\n  - ${ failures.join( '\n  - ' ) }\n` );
	process.exit( 1 );
}

console.log( '\nAll checks passed.\n' );
