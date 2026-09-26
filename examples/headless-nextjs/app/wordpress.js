/**
 * The one place this example talks to WordPress.
 *
 * `fetch` is memoised by React for the duration of a single render, so the
 * layout and the page below can both call this and still make exactly one
 * HTTP request — which is how the JSON-LD ends up in <head> and the
 * questions in <body> without asking WordPress twice.
 */

export function endpoint() {
	const base = process.env.WORDPRESS_URL;
	const postId = process.env.WORDPRESS_FAQ_POST_ID;

	if ( ! base || ! postId ) {
		return null;
	}

	return `${ base.replace( /\/$/, '' ) }/wp-json/answer-ready/v1/faqs/${ postId }`;
}

export async function getFaqPage() {
	const url = endpoint();

	if ( ! url ) {
		return { error: 'Set WORDPRESS_URL and WORDPRESS_FAQ_POST_ID in .env.local — see .env.example.' };
	}

	try {
		// The endpoint sends public cache headers; this is the Next.js side
		// of the same bargain. Raise it, lower it, or swap in tag-based
		// revalidation if you publish often.
		const response = await fetch( url, { next: { revalidate: 300 } } );

		if ( ! response.ok ) {
			return { error: `WordPress answered with ${ response.status } for ${ url }` };
		}

		return { data: await response.json() };
	} catch ( cause ) {
		return { error: `Could not reach ${ url } — ${ cause.message }` };
	}
}
