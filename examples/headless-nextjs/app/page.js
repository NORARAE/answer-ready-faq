import { endpoint, getFaqPage } from './wordpress';

export default async function Home() {
	const { data, error } = await getFaqPage();

	if ( error ) {
		return (
			<main className="wrap">
				<h1>Not connected yet</h1>
				<p className="error">{ error }</p>
				<p className="muted">
					Copy <code>.env.example</code> to <code>.env.local</code>, point it at a
					WordPress site running the Answer-Ready FAQ plugin, and reload.
				</p>
			</main>
		);
	}

	return (
		<main className="wrap">
			<p className="eyebrow">Next.js · App Router</p>
			<h1>{ data.title }</h1>
			<p className="muted">
				This page is Next.js. The questions below came from WordPress as JSON, over
				one request to <code className="break">{ endpoint() }</code>. Nothing here is
				a WordPress theme or a WordPress block.
			</p>

			{ /* Native disclosure elements, so this example ships no client
			     JavaScript at all: it is a server component start to finish. */ }
			<div className="faqs">
				{ data.faqs.map( ( faq, index ) => (
					<details key={ index }>
						<summary>{ faq.question }</summary>
						{ /* Already run through WordPress's inline-only kses
						     allowlist by the endpoint, so there is nothing
						     left to sanitise a second time here. */ }
						<div
							className="answer"
							dangerouslySetInnerHTML={ { __html: faq.answer } }
						/>
					</details>
				) ) }
			</div>

			<p className="muted small">
				The <code>FAQPage</code> JSON-LD in this page&rsquo;s <code>&lt;head&gt;</code> is
				the graph WordPress generated from these same answers. View source to read it.
			</p>
		</main>
	);
}
