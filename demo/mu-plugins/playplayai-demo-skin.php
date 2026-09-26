<?php
/**
 * Plugin Name:  Answer-Ready FAQ — demo skin
 * Description:  Presentation layer for the WordPress Playground demo: a dark navy and gold front end, a welcome strip that guides a first-time visitor, a panel that shows the FAQPage schema this page is really publishing, an admin pointer on the Plugins menu, and a small footer.
 * Version:      1.1.0
 * Author:       PlayPlayAI
 * Author URI:   https://playplayai.com
 * License:      GPL-2.0-or-later
 *
 * This file is a must-use plugin written into wp-content/mu-plugins by
 * blueprint.json. It exists only to make the demo look designed and to
 * explain itself to a visitor. It deliberately changes nothing about what
 * the Answer-Ready FAQ block does: no filters on the block's attributes,
 * no changes to its markup, and no changes to the JSON-LD it emits. The
 * schema panel *reads* the block's rendered output and reprints it; it
 * never builds schema of its own.
 *
 * Source of truth for this file lives in the repository at
 * demo/mu-plugins/playplayai-demo-skin.php. After editing it, run
 * `npm run build:blueprint` to copy it into blueprint.json.
 *
 * @package AnswerReadyFAQ\Demo
 */

declare( strict_types=1 );

namespace PlayPlayAI\Demo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const DEMO_PAGE_SLUG     = 'faq-demo';
const HEADLESS_PAGE_SLUG = 'see-it-headless';
const POINTER_ID     = 'ppai_answer_ready_plugins';
const SITE_URL       = 'https://playplayai.com';
const BLOOM_URL      = 'https://bloom.playplayai.com';
const LINKEDIN_URL   = 'https://www.linkedin.com/in/ngenetti';

/**
 * JSON-LD captured from the FAQ block's rendered output.
 *
 * Filled in by capture_rendered_schema() while the block renders, then read
 * by the schema panel. Nothing here is hardcoded — if the block stops
 * emitting schema, this stays empty and the panel says so.
 *
 * @var string
 */
$GLOBALS['ppai_captured_schema'] = '';

/**
 * Is the current request the demo page itself?
 */
function is_demo_page(): bool {
	return is_page( DEMO_PAGE_SLUG ) && is_main_query();
}

/**
 * Mark the front end so the skin's styles have something to hang on.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function body_class( array $classes ): array {
	$classes[] = 'ppai-demo';

	if ( is_page( DEMO_PAGE_SLUG ) ) {
		$classes[] = 'ppai-demo-page';
	}

	if ( is_page( HEADLESS_PAGE_SLUG ) ) {
		$classes[] = 'ppai-headless-page';
	}

	return $classes;
}
add_filter( 'body_class', __NAMESPACE__ . '\body_class' );

/* -------------------------------------------------------------------------
 * Front-end skin
 * ---------------------------------------------------------------------- */

/**
 * Register a style handle with no file behind it, then attach the skin to it.
 *
 * Going through the stylesheet queue (rather than echoing into wp_head)
 * keeps the skin behind core's dependency ordering, so it lands after the
 * theme's global styles and the block's own stylesheet.
 */
function enqueue_skin(): void {
	wp_register_style( 'ppai-demo-skin', false, array(), '1.1.0' );
	wp_enqueue_style( 'ppai-demo-skin' );
	wp_add_inline_style( 'ppai-demo-skin', skin_css() );
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_skin', 100 );

/**
 * The skin itself.
 *
 * Written against core block markup (.wp-site-blocks, .wp-block-site-title,
 * .wp-block-post-title) rather than any one theme's class names, so it holds
 * up whichever default theme ships with "latest" WordPress. Colour is forced
 * where a block theme's global styles would otherwise win; layout is left
 * to the theme.
 *
 * Contrast on the core pairings: #f2f6fd on #0b1526 is about 16:1, and
 * #e9b949 on #0b1526 about 9:1 — both clear of WCAG AA for body text.
 */
function skin_css(): string {
	return <<<'CSS'
:root {
	--ppai-navy: #0b1526;
	--ppai-navy-raised: #132340;
	--ppai-navy-sunken: #081120;
	--ppai-gold: #e9b949;
	--ppai-gold-bright: #f5d183;
	--ppai-ink: #f2f6fd;
	--ppai-ink-muted: #b7c6de;
	--ppai-line: rgba(233, 185, 73, 0.24);
	/* Matches the FAQ block's own card padding. The theme sizes each
	   top-level child to the same content width and the padding sits
	   outside it, so equal padding is what makes the card edges line up. */
	--ppai-card-pad: clamp(1.25rem, 4vw, 2.25rem);
	--ppai-sans: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
	--ppai-mono: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace;
}

html {
	background-color: var(--ppai-navy);
}

/* A block theme does not necessarily set a universal border-box, and the
   UA stylesheet gives <button> border-box while leaving <a> on
   content-box. That splits `width: 100%` two ways: at 390px the stacked
   link-buttons came out 42px wider than the real <button> beside them.
   Scoped to the buttons on purpose — they are the only thing here with an
   explicit width. Applying it to the cards instead made them 74px
   narrower than the FAQ block's own card, which the theme sizes the same
   way, so the two no longer lined up. */
body.ppai-demo .ppai-btn {
	box-sizing: border-box;
}

body.ppai-demo {
	background-color: var(--ppai-navy) !important;
	color: var(--ppai-ink) !important;
	font-family: var(--ppai-sans);
	font-size: 1.0625rem;
	line-height: 1.7;
	-webkit-font-smoothing: antialiased;
	text-rendering: optimizeLegibility;
}

/* A block theme paints its own backgrounds through global styles; clear
   them so the navy shows through, then repaint only what we mean to. */
body.ppai-demo .wp-site-blocks,
body.ppai-demo .wp-block-template-part,
body.ppai-demo main,
body.ppai-demo header,
body.ppai-demo > footer,
body.ppai-demo .wp-block-post-content,
body.ppai-demo .entry-content {
	background-color: transparent !important;
	color: var(--ppai-ink) !important;
}

body.ppai-demo h1,
body.ppai-demo h2,
body.ppai-demo h3,
body.ppai-demo h4 {
	color: #ffffff !important;
	font-weight: 650;
	line-height: 1.25;
	letter-spacing: -0.01em;
}

body.ppai-demo p,
body.ppai-demo li {
	color: var(--ppai-ink) !important;
}

body.ppai-demo a {
	color: var(--ppai-gold) !important;
	text-decoration-thickness: 1px;
	text-underline-offset: 0.2em;
}

body.ppai-demo a:hover,
body.ppai-demo a:focus-visible {
	color: var(--ppai-gold-bright) !important;
}

body.ppai-demo :focus-visible {
	outline: 2px solid var(--ppai-gold-bright);
	outline-offset: 2px;
}

/* Site identity ------------------------------------------------------- */

body.ppai-demo header {
	border-bottom: 1px solid var(--ppai-line);
}

body.ppai-demo .wp-block-site-title a {
	color: #ffffff !important;
	font-weight: 650;
	letter-spacing: -0.015em;
	text-decoration: none;
}

body.ppai-demo .wp-block-site-tagline {
	color: var(--ppai-gold) !important;
	font-size: 0.9375rem;
	letter-spacing: 0.02em;
}

body.ppai-demo .wp-block-post-title {
	font-size: clamp(1.875rem, 5.5vw, 2.75rem);
	margin-bottom: 0.5em;
}

/* The FAQ block ------------------------------------------------------- */

/* The block draws its dividers and its disclosure arrow from currentColor
   on purpose, so setting colour here is all the skinning it needs. */
body.ppai-demo .answer-ready-faq {
	background-color: var(--ppai-navy-raised);
	border: 1px solid var(--ppai-line);
	border-radius: 16px;
	padding: var(--ppai-card-pad);
	margin-block: 2.5rem;
	box-shadow: 0 18px 40px -28px rgba(0, 0, 0, 0.9);
}

body.ppai-demo .answer-ready-faq__heading {
	font-size: clamp(1.375rem, 4vw, 1.75rem);
	margin-top: 0;
	margin-bottom: 1.25rem !important;
}

body.ppai-demo .answer-ready-faq__question {
	color: var(--ppai-gold-bright) !important;
	font-size: 1.0625rem;
	padding-block: 1.125em;
}

body.ppai-demo .answer-ready-faq__answer {
	color: var(--ppai-ink-muted) !important;
	padding-bottom: 1.5em;
	max-width: 68ch;
}

body.ppai-demo .answer-ready-faq__answer p,
body.ppai-demo .answer-ready-faq__answer li {
	color: var(--ppai-ink-muted) !important;
}

/* Welcome strip ------------------------------------------------------- */

.ppai-welcome {
	background: linear-gradient(135deg, #14264a 0%, #0d1a30 100%);
	border: 1px solid var(--ppai-line);
	border-radius: 16px;
	padding: var(--ppai-card-pad);
	margin-block: 0 2.5rem;
}

.ppai-welcome__eyebrow {
	color: var(--ppai-gold) !important;
	font-size: 0.75rem;
	font-weight: 700;
	letter-spacing: 0.14em;
	text-transform: uppercase;
	margin: 0 0 0.625rem !important;
}

.ppai-welcome__lede {
	color: #ffffff !important;
	font-size: clamp(1.0625rem, 3.2vw, 1.25rem);
	font-weight: 500;
	line-height: 1.5;
	margin: 0 0 1.375rem !important;
	max-width: 44ch;
}

.ppai-welcome__actions {
	display: flex;
	flex-wrap: wrap;
	gap: 0.75rem;
}

/* Scoped to body.ppai-demo so these out-specify the blanket link colour
   above: a solid gold button needs dark text, and a bare `.ppai-btn--primary`
   rule loses to `body.ppai-demo a` even with !important on both. */
body.ppai-demo .ppai-btn {
	appearance: none;
	border: 1px solid var(--ppai-gold);
	border-radius: 999px;
	cursor: pointer;
	display: inline-block;
	font-family: inherit;
	font-size: 0.9375rem;
	font-weight: 600;
	line-height: 1.2;
	padding: 0.8125rem 1.25rem;
	text-align: center;
	text-decoration: none !important;
	transition: transform 0.12s ease, background-color 0.12s ease;
}

body.ppai-demo .ppai-btn--primary {
	background-color: var(--ppai-gold);
	color: #0b1526 !important;
}

body.ppai-demo .ppai-btn--primary:hover,
body.ppai-demo .ppai-btn--primary:focus-visible {
	background-color: var(--ppai-gold-bright);
	color: #0b1526 !important;
}

body.ppai-demo .ppai-btn--ghost {
	background-color: transparent;
	color: var(--ppai-gold) !important;
}

body.ppai-demo .ppai-btn--ghost:hover,
body.ppai-demo .ppai-btn--ghost:focus-visible {
	background-color: rgba(233, 185, 73, 0.14);
	color: var(--ppai-gold-bright) !important;
}

body.ppai-demo .ppai-btn:active {
	transform: translateY(1px);
}

@media (prefers-reduced-motion: reduce) {
	body.ppai-demo .ppai-btn {
		transition: none;
	}
	body.ppai-demo .ppai-btn:active {
		transform: none;
	}
}

.ppai-welcome__note {
	color: var(--ppai-ink-muted) !important;
	font-size: 0.9375rem;
	line-height: 1.6;
	margin: 1.125rem 0 0 !important;
	max-width: 56ch;
}

/* The headless page --------------------------------------------------- */

.ppai-hl__lede {
	color: #ffffff !important;
	font-size: clamp(1.0625rem, 3.2vw, 1.25rem);
	font-weight: 500;
	line-height: 1.55;
	margin: 0 0 2rem !important;
	max-width: 52ch;
}

.ppai-hl__count,
.ppai-hl__status {
	color: var(--ppai-gold) !important;
	font-size: 0.9375rem;
	margin: 0 0 1.25rem !important;
}

.ppai-hl__status--error {
	color: #ffb4a8 !important;
}

.ppai-hl__list {
	background-color: var(--ppai-navy-raised);
	border: 1px solid var(--ppai-line);
	border-radius: 16px;
	padding: var(--ppai-card-pad);
}

.ppai-hl__item + .ppai-hl__item {
	border-top: 1px solid var(--ppai-line);
}

/* A real button, not a <details>: on this page the front end owns the
   interaction, and that is the difference the page exists to show. */
body.ppai-demo .ppai-hl__question {
	appearance: none;
	background: none;
	border: 0;
	color: var(--ppai-gold-bright) !important;
	cursor: pointer;
	display: flex;
	align-items: baseline;
	gap: 0.625em;
	font-family: inherit;
	font-size: 1.0625rem;
	font-weight: 600;
	line-height: 1.5;
	padding: 1.125em 0.25em;
	text-align: left;
	width: 100%;
}

body.ppai-demo .ppai-hl__question:hover {
	color: #ffffff !important;
}

body.ppai-demo .ppai-hl__question:focus-visible {
	border-radius: 4px;
	outline: 2px solid var(--ppai-gold-bright);
	outline-offset: -2px;
}

.ppai-hl__marker {
	border-right: 2px solid currentColor;
	border-bottom: 2px solid currentColor;
	display: inline-block;
	flex: 0 0 auto;
	height: 0.5em;
	transform: rotate(-45deg);
	transition: transform 0.15s ease;
	width: 0.5em;
}

.ppai-hl__question[aria-expanded="true"] .ppai-hl__marker {
	transform: rotate(45deg);
}

@media (prefers-reduced-motion: reduce) {
	.ppai-hl__marker {
		transition: none;
	}
}

.ppai-hl__answer {
	color: var(--ppai-ink-muted) !important;
	max-width: 68ch;
	padding: 0 0.25em 1.5em;
}

.ppai-hl__answer p {
	color: var(--ppai-ink-muted) !important;
	margin-top: 0;
}

.ppai-hl__data {
	background-color: var(--ppai-navy-raised);
	border: 1px solid var(--ppai-line);
	border-radius: 16px;
	margin-block: 2.5rem;
	padding: var(--ppai-card-pad);
}

.ppai-hl__data > summary {
	color: var(--ppai-gold-bright) !important;
	cursor: pointer;
	font-size: 1.0625rem;
	font-weight: 650;
}

.ppai-hl__data > summary:focus-visible {
	border-radius: 4px;
	outline: 2px solid var(--ppai-gold-bright);
	outline-offset: 3px;
}

.ppai-hl__data-note {
	color: var(--ppai-ink-muted) !important;
	margin: 1rem 0 1.25rem !important;
	max-width: 62ch;
}

.ppai-hl__back {
	margin-top: 2.5rem !important;
}

/* Schema panel -------------------------------------------------------- */

.ppai-schema {
	background-color: var(--ppai-navy-raised);
	border: 1px solid var(--ppai-line);
	border-radius: 16px;
	margin-block: 2.5rem;
	padding: var(--ppai-card-pad);
}

.ppai-schema > summary {
	color: var(--ppai-gold-bright) !important;
	cursor: pointer;
	font-size: 1.0625rem;
	font-weight: 650;
	list-style-position: outside;
}

.ppai-schema > summary:focus-visible {
	border-radius: 4px;
	outline: 2px solid var(--ppai-gold-bright);
	outline-offset: 3px;
}

.ppai-schema__note {
	color: var(--ppai-ink-muted) !important;
	margin: 1rem 0 1.25rem !important;
	max-width: 62ch;
}

.ppai-schema__code {
	background-color: var(--ppai-navy-sunken);
	border: 1px solid rgba(255, 255, 255, 0.08);
	border-radius: 10px;
	color: #d8e4f7;
	font-family: var(--ppai-mono);
	font-size: 0.8125rem;
	line-height: 1.6;
	margin: 0;
	max-height: 26rem;
	overflow: auto;
	padding: 1rem 1.125rem;
	tab-size: 2;
	/* pre-wrap, not pre: the point of this panel is that someone can read
	   the schema, and a horizontal scrollbar hides the answer text — most
	   of all at 390px. Indentation still comes through. */
	white-space: pre-wrap;
	overflow-wrap: anywhere;
}

/* Demo footer --------------------------------------------------------- */

/* A block theme's default footer pattern ships a nav block pointing at
   pages this one-page demo doesn't have — eight links that all 404. Hide
   it and let the demo footer below carry the site identity instead. */
body.ppai-demo footer.wp-block-template-part {
	display: none !important;
}

.ppai-footer {
	border-top: 1px solid var(--ppai-line);
	margin-top: 3rem;
	padding: 1.75rem 1.25rem 2.5rem;
	text-align: center;
}

.ppai-footer__brand {
	color: #ffffff !important;
	font-size: 1rem;
	font-weight: 600;
	margin: 0 0 1rem !important;
}

.ppai-footer__brand span {
	color: var(--ppai-gold) !important;
	font-weight: 500;
}

.ppai-footer__links {
	color: var(--ppai-ink-muted) !important;
	display: flex;
	flex-wrap: wrap;
	gap: 0.5rem 1.25rem;
	justify-content: center;
	margin: 0 auto;
	max-width: 48rem;
	font-size: 0.9375rem;
}

.ppai-footer__note {
	color: var(--ppai-ink-muted) !important;
	font-size: 0.8125rem;
	margin: 0.875rem 0 0 !important;
	opacity: 0.85;
}

/* Small screens ------------------------------------------------------- */

@media (max-width: 480px) {
	body.ppai-demo {
		font-size: 1rem;
	}

	.ppai-welcome__actions {
		flex-direction: column;
	}

	body.ppai-demo .ppai-btn {
		display: block;
		width: 100%;
	}

	.ppai-schema__code {
		font-size: 0.75rem;
		padding: 0.875rem;
	}

	.ppai-footer__brand {
	color: #ffffff !important;
	font-size: 1rem;
	font-weight: 600;
	margin: 0 0 1rem !important;
}

.ppai-footer__brand span {
	color: var(--ppai-gold) !important;
	font-weight: 500;
}

.ppai-footer__links {
		flex-direction: column;
		gap: 0.625rem;
	}
}
CSS;
}

/* -------------------------------------------------------------------------
 * Read the schema the block actually rendered
 * ---------------------------------------------------------------------- */

/**
 * Keep a copy of the JSON-LD the FAQ block just emitted.
 *
 * This is a read-only observer: the block's HTML is returned untouched. We
 * only lift the JSON-LD out of it so the panel below can show the visitor
 * the real thing instead of a sample that might disagree with the page.
 *
 * @param string $block_content Rendered block HTML.
 * @param array  $block         Parsed block.
 * @return string Unchanged $block_content.
 */
function capture_rendered_schema( string $block_content, array $block ): string {
	if ( 'answer-ready/faq' !== ( $block['blockName'] ?? '' ) ) {
		return $block_content;
	}

	if ( preg_match( '~<script\s+type="application/ld\+json">\s*(.*?)\s*</script>~s', $block_content, $matches ) ) {
		$GLOBALS['ppai_captured_schema'] = $matches[1];
	}

	return $block_content;
}
add_filter( 'render_block', __NAMESPACE__ . '\capture_rendered_schema', 10, 2 );

/**
 * Pretty-print the captured JSON-LD for display.
 *
 * Decoding and re-encoding proves the page is publishing valid JSON — if it
 * would not parse, we show the raw output rather than pretend otherwise.
 */
function pretty_schema(): string {
	$raw = trim( (string) $GLOBALS['ppai_captured_schema'] );

	if ( '' === $raw ) {
		return '';
	}

	$decoded = json_decode( $raw, true );

	if ( null === $decoded || JSON_ERROR_NONE !== json_last_error() ) {
		return $raw;
	}

	return (string) wp_json_encode( $decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
}

/* -------------------------------------------------------------------------
 * Welcome strip and schema panel
 * ---------------------------------------------------------------------- */

/**
 * Link to the demo page in the block editor.
 */
function editor_url(): string {
	$page = get_page_by_path( DEMO_PAGE_SLUG );

	if ( ! $page instanceof \WP_Post ) {
		return admin_url( 'edit.php?post_type=page' );
	}

	return admin_url( 'post.php?post=' . $page->ID . '&action=edit' );
}

/**
 * Link to the headless page.
 */
function headless_url(): string {
	$page = get_page_by_path( HEADLESS_PAGE_SLUG );

	return $page instanceof \WP_Post ? (string) get_permalink( $page ) : home_url( '/' . HEADLESS_PAGE_SLUG . '/' );
}

/**
 * The welcome strip: where you are, and four things worth clicking.
 */
function welcome_strip(): string {
	$buttons = array(
		sprintf(
			'<a class="ppai-btn ppai-btn--primary" href="%s">%s</a>',
			esc_url( editor_url() ),
			esc_html__( 'See the block in the editor', 'answer-ready-faq' )
		),
		sprintf(
			'<a class="ppai-btn ppai-btn--ghost" href="%s">%s</a>',
			esc_url( admin_url( 'plugins.php' ) ),
			esc_html__( 'See the plugin', 'answer-ready-faq' )
		),
		sprintf(
			'<button class="ppai-btn ppai-btn--ghost" type="button" data-ppai-open-schema aria-controls="ppai-schema">%s</button>',
			esc_html__( 'See the schema it writes', 'answer-ready-faq' )
		),
		sprintf(
			'<a class="ppai-btn ppai-btn--ghost" href="%s">%s</a>',
			esc_url( headless_url() ),
			esc_html__( 'See it headless', 'answer-ready-faq' )
		),
	);

	return sprintf(
		'<aside class="ppai-welcome"><p class="ppai-welcome__eyebrow">%s</p><p class="ppai-welcome__lede">%s</p><div class="ppai-welcome__actions">%s</div><p class="ppai-welcome__note">%s</p></aside>',
		esc_html__( 'Live demo', 'answer-ready-faq' ),
		esc_html__( "You're inside a real WordPress site, running in your browser. Nothing to install.", 'answer-ready-faq' ),
		implode( '', $buttons ),
		esc_html__( 'Here WordPress only stores the answers. A separate React front end asks for them and draws the page itself.', 'answer-ready-faq' )
	);
}

/**
 * The schema panel: the page's own JSON-LD, pretty-printed.
 */
function schema_panel(): string {
	$json = pretty_schema();

	$body = '' === $json
		? '<p class="ppai-schema__note">' . esc_html__( 'This page is not publishing FAQ schema right now. The block has a switch for that, so you can turn it off when another plugin already handles it.', 'answer-ready-faq' ) . '</p>'
		: '<p class="ppai-schema__note">'
			. esc_html__( 'Schema is a hidden, tidy copy of your FAQ written for machines. Search engines and AI answer engines read it so they can quote you correctly. The block builds it from the same answers you see above, every time the page loads — so the two can never disagree.', 'answer-ready-faq' )
			. '</p><pre class="ppai-schema__code" tabindex="0"><code>' . esc_html( $json ) . '</code></pre>';

	return sprintf(
		'<details class="ppai-schema" id="ppai-schema"><summary>%s</summary>%s</details>',
		esc_html__( 'See the schema it writes', 'answer-ready-faq' ),
		$body
	);
}

/**
 * Wrap the demo page's content in the strip and the panel.
 *
 * Priority 20 runs after core's do_blocks() (priority 9), so the FAQ block
 * has already rendered and its schema has already been captured.
 *
 * @param string $content Post content.
 * @return string
 */
function frame_demo_content( string $content ): string {
	if ( ! is_demo_page() || ! in_the_loop() ) {
		return $content;
	}

	return welcome_strip() . $content . schema_panel();
}
add_filter( 'the_content', __NAMESPACE__ . '\frame_demo_content', 20 );

/* -------------------------------------------------------------------------
 * "See it headless" — the same answers, drawn by a React front end
 * ---------------------------------------------------------------------- */

/**
 * Is this the headless page?
 */
function is_headless_page(): bool {
	return is_page( HEADLESS_PAGE_SLUG ) && is_main_query();
}

/**
 * Put the mount point and the explanation on the headless page.
 *
 * Note what is *not* here: no FAQ block, no accordion markup, no JSON-LD.
 * The page ships an empty div. Everything a visitor ends up reading is
 * fetched from the REST endpoint and drawn by React in the browser, which
 * is the entire point of the page.
 *
 * @param string $content Post content.
 * @return string
 */
function frame_headless_content( string $content ): string {
	if ( ! is_headless_page() || ! in_the_loop() ) {
		return $content;
	}

	$intro = sprintf(
		'<p class="ppai-hl__lede">%s</p>',
		esc_html__( 'Here WordPress only stores the answers. A separate React front end asks for them and draws the page itself.', 'answer-ready-faq' )
	);

	// Rendered before React mounts, and replaced the moment it does. A
	// visitor on a slow connection sees a sentence rather than a blank box.
	$mount = sprintf(
		'<div id="ppai-headless-root" class="ppai-hl"><p class="ppai-hl__status">%s</p></div>',
		esc_html__( 'Asking WordPress for the answers…', 'answer-ready-faq' )
	);

	$back = sprintf(
		'<p class="ppai-hl__back"><a href="%s">%s</a></p>',
		esc_url( get_permalink( get_page_by_path( DEMO_PAGE_SLUG ) ) ),
		esc_html__( 'Back to the demo page', 'answer-ready-faq' )
	);

	return $intro . $content . $mount . $back;
}
add_filter( 'the_content', __NAMESPACE__ . '\frame_headless_content', 20 );

/**
 * Hand the React app its endpoint and load it.
 *
 * `wp-element` is WordPress's own bundled React, so the app adds no
 * dependency and ships no bundle of its own. It is written with
 * createElement rather than JSX for the same reason: JSX would need a build
 * step, and a build artifact cannot be read in a pull request the way this
 * can.
 */
function enqueue_headless_app(): void {
	if ( ! is_page( HEADLESS_PAGE_SLUG ) ) {
		return;
	}

	$demo_page = get_page_by_path( DEMO_PAGE_SLUG );

	if ( ! $demo_page instanceof \WP_Post ) {
		return;
	}

	wp_register_script( 'ppai-headless', false, array( 'wp-element' ), '1.2.0', true );
	wp_enqueue_script( 'ppai-headless' );

	wp_add_inline_script(
		'ppai-headless',
		'window.ppaiHeadless = ' . wp_json_encode(
			array(
				'endpoint' => rest_url( 'answer-ready/v1/faqs/' . $demo_page->ID ),
				'postId'   => $demo_page->ID,
			)
		) . ';',
		'before'
	);

	wp_add_inline_script( 'ppai-headless', headless_app_js(), 'after' );
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_headless_app', 20 );

/**
 * The React app.
 *
 * Deliberately plain: fetch once, hold three states (loading, error, ready),
 * and draw an accordion of its own. The disclosure is a real button with
 * aria-expanded rather than a <details>, because here the front end owns the
 * interaction — which is the difference the page is trying to show.
 */
function headless_app_js(): string {
	return <<<'JS'
( function ( wp, config ) {
	if ( ! wp || ! wp.element || ! config ) {
		return;
	}

	var el = wp.element.createElement;
	var useState = wp.element.useState;
	var useEffect = wp.element.useEffect;

	function Answer( props ) {
		// The endpoint already ran this through the same inline-only kses
		// allowlist the page renders with, so there is nothing left here to
		// escape a second time.
		return el( 'div', {
			className: 'ppai-hl__answer',
			id: props.id,
			role: 'region',
			'aria-labelledby': props.labelledBy,
			dangerouslySetInnerHTML: { __html: props.html }
		} );
	}

	function Item( props ) {
		var questionId = 'ppai-hl-q-' + props.index;
		var answerId = 'ppai-hl-a-' + props.index;

		return el(
			'div',
			{ className: 'ppai-hl__item' },
			el(
				'button',
				{
					type: 'button',
					className: 'ppai-hl__question',
					id: questionId,
					'aria-expanded': props.isOpen ? 'true' : 'false',
					'aria-controls': answerId,
					onClick: props.onToggle
				},
				el( 'span', { className: 'ppai-hl__marker', 'aria-hidden': 'true' } ),
				props.faq.question
			),
			props.isOpen
				? el( Answer, { id: answerId, labelledBy: questionId, html: props.faq.answer } )
				: null
		);
	}

	function DataPanel( props ) {
		return el(
			'details',
			{ className: 'ppai-hl__data' },
			el( 'summary', null, 'Data coming in' ),
			el(
				'p',
				{ className: 'ppai-hl__data-note' },
				'This is the whole reply, exactly as it arrived. The answers above were drawn from it, and so was the schema — same request, one source.'
			),
			el(
				'pre',
				{ className: 'ppai-schema__code', tabIndex: 0 },
				JSON.stringify( props.data, null, 2 )
			)
		);
	}

	function App() {
		var statePair = useState( { status: 'loading' } );
		var state = statePair[ 0 ];
		var setState = statePair[ 1 ];

		var openPair = useState( {} );
		var open = openPair[ 0 ];
		var setOpen = openPair[ 1 ];

		useEffect( function () {
			var cancelled = false;

			window.fetch( config.endpoint, { headers: { Accept: 'application/json' } } )
				.then( function ( response ) {
					if ( ! response.ok ) {
						throw new Error( 'WordPress answered with ' + response.status + '.' );
					}
					return response.json();
				} )
				.then( function ( data ) {
					if ( ! cancelled ) {
						setState( { status: 'ready', data: data } );
					}
				} )
				.catch( function ( error ) {
					if ( ! cancelled ) {
						setState( { status: 'error', message: error.message } );
					}
				} );

			return function () {
				cancelled = true;
			};
		}, [] );

		if ( 'loading' === state.status ) {
			return el( 'p', { className: 'ppai-hl__status' }, 'Asking WordPress for the answers…' );
		}

		if ( 'error' === state.status ) {
			return el(
				'p',
				{ className: 'ppai-hl__status ppai-hl__status--error' },
				'That request did not come back: ' + state.message
			);
		}

		var faqs = state.data.faqs || [];

		if ( ! faqs.length ) {
			return el( 'p', { className: 'ppai-hl__status' }, 'WordPress has no FAQ answers on that page yet.' );
		}

		return el(
			'div',
			null,
			el(
				'p',
				{ className: 'ppai-hl__count' },
				'Fetched ' + faqs.length + ' answers from WordPress. None of this page is a WordPress block.'
			),
			el(
				'div',
				{ className: 'ppai-hl__list' },
				faqs.map( function ( faq, index ) {
					return el( Item, {
						key: index,
						index: index,
						faq: faq,
						isOpen: !! open[ index ],
						onToggle: function () {
							setOpen( function ( previous ) {
								var next = Object.assign( {}, previous );
								next[ index ] = ! next[ index ];
								return next;
							} );
						}
					} );
				} )
			),
			el( DataPanel, { data: state.data } )
		);
	}

	var root = document.getElementById( 'ppai-headless-root' );

	if ( ! root ) {
		return;
	}

	if ( wp.element.createRoot ) {
		wp.element.createRoot( root ).render( el( App ) );
	} else {
		wp.element.render( el( App ), root );
	}
} )( window.wp, window.ppaiHeadless );
JS;
}

/* -------------------------------------------------------------------------
 * Footer
 * ---------------------------------------------------------------------- */

/**
 * A small footer: who built this, and where to find more of it.
 *
 * Outbound links open in a new tab on purpose — the demo lives in an iframe,
 * and navigating it away would throw the visitor's WordPress site out.
 */
function render_footer(): void {
	if ( is_admin() ) {
		return;
	}

	$links = array(
		array( SITE_URL, __( 'playplayai.com', 'answer-ready-faq' ) ),
		array( BLOOM_URL, __( 'Bloom', 'answer-ready-faq' ) ),
		array( LINKEDIN_URL, __( 'LinkedIn', 'answer-ready-faq' ) ),
	);

	$markup = '';

	foreach ( $links as list( $url, $label ) ) {
		$markup .= sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( $url ),
			esc_html( $label )
		);
	}

	// Read back from the site options the blueprint set, so the footer says
	// whatever the site says rather than keeping its own second copy.
	$brand = sprintf(
		'%s <span>%s</span>',
		esc_html( get_bloginfo( 'name' ) ),
		esc_html( get_bloginfo( 'description' ) )
	);

	printf(
		'<footer class="ppai-footer"><p class="ppai-footer__brand">%s</p><div class="ppai-footer__links">%s</div><p class="ppai-footer__note">%s</p></footer>',
		$brand, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from escaped parts above.
		$markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from escaped parts above.
		esc_html__( 'This demo site is temporary — close the tab and it is gone.', 'answer-ready-faq' )
	);
}
add_action( 'wp_footer', __NAMESPACE__ . '\render_footer', 20 );

/* -------------------------------------------------------------------------
 * Front-end behaviour (the one button that needs it)
 * ---------------------------------------------------------------------- */

/**
 * Open the schema panel when the welcome strip's third button is pressed.
 *
 * The FAQ block itself still ships zero front-end JavaScript; this listener
 * belongs to the demo chrome and touches nothing the block renders.
 */
function print_footer_script(): void {
	if ( ! is_page( DEMO_PAGE_SLUG ) ) {
		return;
	}
	?>
	<script>
	document.addEventListener( 'click', function ( event ) {
		var trigger = event.target.closest( '[data-ppai-open-schema]' );
		if ( ! trigger ) {
			return;
		}
		var panel = document.getElementById( 'ppai-schema' );
		if ( ! panel ) {
			return;
		}
		panel.open = true;
		panel.scrollIntoView( { behavior: 'smooth', block: 'start' } );
		var summary = panel.querySelector( 'summary' );
		if ( summary ) {
			summary.focus();
		}
	} );
	</script>
	<?php
}
add_action( 'wp_footer', __NAMESPACE__ . '\print_footer_script', 30 );

/* -------------------------------------------------------------------------
 * Admin pointer on the Plugins menu
 * ---------------------------------------------------------------------- */

/**
 * Point at the Plugins menu once, using core's own wp-pointer bubble.
 *
 * Dismissal goes through core's dismiss-wp-pointer AJAX action, which writes
 * to the dismissed_wp_pointers user meta — the same mechanism WordPress uses
 * for its own pointers, so "show once" is handled for us.
 */
function enqueue_pointer(): void {
	$user_id = get_current_user_id();

	if ( ! $user_id ) {
		return;
	}

	$dismissed = array_filter( explode( ',', (string) get_user_meta( $user_id, 'dismissed_wp_pointers', true ) ) );

	if ( in_array( POINTER_ID, $dismissed, true ) ) {
		return;
	}

	wp_enqueue_style( 'wp-pointer' );
	wp_enqueue_script( 'wp-pointer' );
	add_action( 'admin_print_footer_scripts', __NAMESPACE__ . '\print_pointer_script' );
}
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\enqueue_pointer' );

/**
 * The pointer's markup and open call.
 */
function print_pointer_script(): void {
	$content = '<h3>' . esc_html__( 'Answer-Ready FAQ', 'answer-ready-faq' ) . '</h3>'
		. '<p>' . esc_html__( 'Answer-Ready FAQ is installed here — built by PlayPlayAI.', 'answer-ready-faq' ) . '</p>';
	?>
	<script>
	jQuery( function ( $ ) {
		var target = $( '#menu-plugins' );

		// On a narrow screen the admin menu is collapsed, and a bubble
		// anchored to a hidden item would land in the wrong place.
		if ( ! target.length || ! target.is( ':visible' ) ) {
			return;
		}

		target.pointer( {
			content: <?php echo wp_json_encode( $content ); ?>,
			position: { edge: 'left', align: 'center' },
			pointerClass: 'wp-pointer ppai-pointer',
			close: function () {
				$.post( window.ajaxurl, {
					action: 'dismiss-wp-pointer',
					pointer: <?php echo wp_json_encode( POINTER_ID ); ?>
				} );
			}
		} ).pointer( 'open' );
	} );
	</script>
	<?php
}
