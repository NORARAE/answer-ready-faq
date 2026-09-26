<?php
/**
 * Server-side render for the Answer-Ready FAQ block.
 *
 * Two outputs from one source of truth (the block attributes):
 *
 * 1. Visible markup — a native <details>/<summary> accordion.
 *    Disclosure semantics, keyboard operability, and screen-reader
 *    announcements come from the platform itself, so the block is
 *    accessible with zero JavaScript on the front end.
 *
 * 2. Machine-readable markup — a schema.org FAQPage JSON-LD graph,
 *    built server-side with wp_json_encode() so it always reflects
 *    the current content and is always correctly encoded.
 *
 * Neither the normalising, the escaping, nor the schema graph is written
 * here: all three come from AnswerReadyFAQ\Data, which the REST endpoint
 * reads from as well. One implementation, so the page a visitor sees, the
 * JSON-LD a crawler reads, and the JSON a headless client fetches cannot
 * drift apart.
 *
 * Escaping strategy: questions are plain text (esc_html). Answers come
 * from RichText and may contain inline formatting, so they are passed
 * through wp_kses() with an explicit inline-only allowlist. The JSON-LD
 * variant of each answer is stripped to plain text per Google's
 * structured-data guidelines.
 *
 * @package AnswerReadyFAQ
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner block content (unused; block is leaf-level).
 * @var WP_Block $block      Block instance.
 */

declare( strict_types=1 );

namespace AnswerReadyFAQ;

use AnswerReadyFAQ\Data;

$faqs = Data\normalize_faqs( $attributes['faqs'] ?? array() );

// Nothing publishable? Render nothing — no empty wrappers, no empty schema.
if ( empty( $faqs ) ) {
	return;
}

$heading     = (string) ( $attributes['heading'] ?? '' );
$emit_schema = (bool) ( $attributes['emitSchema'] ?? true );

$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'answer-ready-faq' ) );
?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by get_block_wrapper_attributes(). ?>>
	<?php if ( '' !== Data\plain_text( $heading ) ) : ?>
		<h2 class="answer-ready-faq__heading"><?php echo esc_html( Data\plain_text( $heading ) ); ?></h2>
	<?php endif; ?>

	<?php foreach ( $faqs as $faq ) : ?>
		<details class="answer-ready-faq__item">
			<summary class="answer-ready-faq__question">
				<?php echo esc_html( Data\plain_text( $faq['question'] ) ); ?>
			</summary>
			<div class="answer-ready-faq__answer">
				<?php echo Data\sanitize_answer( $faq['answer'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by sanitize_answer()'s wp_kses() allowlist. ?>
			</div>
		</details>
	<?php endforeach; ?>

	<?php if ( $emit_schema ) : ?>
		<script type="application/ld+json">
			<?php
			// wp_json_encode handles encoding; JSON_UNESCAPED_SLASHES keeps URLs readable.
			echo wp_json_encode( Data\build_schema( $faqs ), JSON_UNESCAPED_SLASHES ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-LD payload, encoded by wp_json_encode().
			?>
		</script>
	<?php endif; ?>
</section>
