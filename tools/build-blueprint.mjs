#!/usr/bin/env node
/**
 * Inline the demo mu-plugin into blueprint.json.
 *
 * The Playground blueprint writes the demo skin into wp-content/mu-plugins
 * with a `writeFile` step, which means the PHP has to live inside a JSON
 * string. Editing PHP in that form is miserable and unreviewable, so the
 * real file lives at demo/mu-plugins/playplayai-demo-skin.php and this
 * script copies it into the blueprint.
 *
 * Inlining (rather than pointing `writeFile` at a raw GitHub URL) keeps the
 * demo self-contained: one fetch of blueprint.json and Playground has
 * everything it needs, with no second request that could fail or be cached
 * stale while someone is watching.
 *
 * Usage:
 *   node tools/build-blueprint.mjs          # write blueprint.json
 *   node tools/build-blueprint.mjs --check  # fail if it is out of date
 */

import { readFileSync, writeFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve( dirname( fileURLToPath( import.meta.url ) ), '..' );
const blueprintPath = resolve( root, 'blueprint.json' );
const muPluginPath = resolve( root, 'demo/mu-plugins/playplayai-demo-skin.php' );
const targetPath = '/wordpress/wp-content/mu-plugins/playplayai-demo-skin.php';

const blueprint = JSON.parse( readFileSync( blueprintPath, 'utf8' ) );
const muPlugin = readFileSync( muPluginPath, 'utf8' );

const step = blueprint.steps.find(
	( candidate ) => candidate.step === 'writeFile' && candidate.path === targetPath
);

if ( ! step ) {
	console.error( `No writeFile step in blueprint.json targeting ${ targetPath }` );
	process.exit( 1 );
}

const check = process.argv.includes( '--check' );

if ( check ) {
	if ( step.data === muPlugin ) {
		console.log( 'blueprint.json is up to date.' );
		process.exit( 0 );
	}
	console.error(
		'blueprint.json is out of date with demo/mu-plugins/playplayai-demo-skin.php.\n' +
			'Run `npm run build:blueprint` and commit the result.'
	);
	process.exit( 1 );
}

step.data = muPlugin;
writeFileSync( blueprintPath, `${ JSON.stringify( blueprint, null, 2 ) }\n` );
console.log(
	`Inlined ${ muPlugin.length } bytes of demo/mu-plugins/playplayai-demo-skin.php into blueprint.json.`
);
