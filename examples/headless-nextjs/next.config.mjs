import { dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

/**
 * The plugin repo has its own package-lock.json one directory up, so Next
 * guesses the wrong workspace root and warns about it on every build. This
 * example is self-contained; pin the root to it.
 */
export default {
	outputFileTracingRoot: dirname( fileURLToPath( import.meta.url ) ),
};
