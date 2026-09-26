import { getFaqPage } from './wordpress';
import './globals.css';

export const metadata = {
	title: 'Answer-Ready FAQ — headless example',
	description: 'A WordPress FAQ rendered by Next.js from a read-only REST endpoint.',
};

export default async function RootLayout( { children } ) {
	const { data } = await getFaqPage();

	return (
		<html lang="en">
			<head>
				{ /* The FAQPage graph WordPress built, published unchanged.
				     This example never assembles schema of its own — if it
				     did, the headless front end and the WordPress page could
				     start saying different things, which is the exact
				     problem the block exists to prevent. */ }
				{ data?.schema && (
					<script
						type="application/ld+json"
						dangerouslySetInnerHTML={ {
							__html: JSON.stringify( data.schema ),
						} }
					/>
				) }
			</head>
			<body>{ children }</body>
		</html>
	);
}
