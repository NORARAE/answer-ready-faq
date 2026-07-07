/**
 * Answer-Ready FAQ — block registration.
 *
 * Registration is metadata-driven: block.json declares attributes,
 * supports, and assets; this file only wires up the edit component.
 * The block is dynamic, so there is no save() — the source of truth
 * for front-end output is render.php.
 */
import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import Edit from './edit';
import './style.scss';
import './editor.scss';

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
