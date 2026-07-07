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

$faqs = array_values(
	array_filter(
		(array) ( $attributes['faqs'] ?? array() ),
		static function ( $faq ): bool {
			return is_array( $faq )
				&& '' !== trim( wp_strip_all_tags( (string) ( $faq['question'] ?? '' ) ) )
				&& '' !== trim( wp_strip_all_tags( (string) ( $faq['answer'] ?? '' ) ) );
		}
	)
);

// Nothing publishable? Render nothing — no empty wrappers, no empty schema.
if ( empty( $faqs ) ) {
	return;
}

$heading     = (string) ( $attributes['heading'] ?? '' );
$emit_schema = (bool) ( $attributes['emitSchema'] ?? true );

/**
 * Inline elements an answer may contain. RichText in the editor is
 * already constrained to simple formats; this is the server-side
 * enforcement of the same contract.
 */
$allowed_inline = array(
	'a'      => array(
		'href'   => true,
		'rel'    => true,
		'target' => true,
	),
	'strong' => array(),
	'em'     => array(),
	'code'   => array(),
	'br'     => array(),
);

// Build the FAQPage graph (plain-text answers, per Google guidelines).
$schema = array(
	'@context'   => 'https://schema.org',
	'@type'      => 'FAQPage',
	'mainEntity' => array_map(
		static function ( array $faq ): array {
			return array(
				'@type'          => 'Question',
				'name'           => wp_strip_all_tags( (string) $faq['question'] ),
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => wp_strip_all_tags( (string) $faq['answer'] ),
				),
			);
		},
		$faqs
	),
);

$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'answer-ready-faq' ) );
?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by get_block_wrapper_attributes(). ?>>
	<?php if ( '' !== trim( wp_strip_all_tags( $heading ) ) ) : ?>
		<h2 class="answer-ready-faq__heading"><?php echo esc_html( wp_strip_all_tags( $heading ) ); ?></h2>
	<?php endif; ?>

	<?php foreach ( $faqs as $faq ) : ?>
		<details class="answer-ready-faq__item">
			<summary class="answer-ready-faq__question">
				<?php echo esc_html( wp_strip_all_tags( (string) $faq['question'] ) ); ?>
			</summary>
			<div class="answer-ready-faq__answer">
				<?php echo wp_kses( (string) $faq['answer'], $allowed_inline ); ?>
			</div>
		</details>
	<?php endforeach; ?>

	<?php if ( $emit_schema ) : ?>
		<script type="application/ld+json">
			<?php
			// wp_json_encode handles encoding; JSON_UNESCAPED_SLASHES keeps URLs readable.
			echo wp_json_encode( $schema, JSON_UNESCAPED_SLASHES ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-LD payload, encoded by wp_json_encode().
			?>
		</script>
	<?php endif; ?>
</section>
