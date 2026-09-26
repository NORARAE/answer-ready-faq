<?php
/**
 * The FAQ data layer: one normaliser, one sanitiser, one schema builder.
 *
 * This file exists so that the block's rendered output and the REST
 * endpoint cannot disagree. Both call the same three functions on the same
 * attributes, so there is no second implementation to fall out of step —
 * which is the whole promise the block makes about content and schema.
 *
 * The functions here deliberately depend on very little: wp_strip_all_tags()
 * and wp_kses() for escaping, and nothing else. That keeps them testable
 * without a WordPress install (see tests/).
 *
 * @package AnswerReadyFAQ
 */

declare( strict_types=1 );

namespace AnswerReadyFAQ\Data;

/**
 * Inline elements an answer may contain.
 *
 * RichText in the editor is already constrained to these formats; this is
 * the server-side enforcement of the same contract, applied identically to
 * rendered HTML and to API output.
 *
 * @return array<string, array<string, bool>>
 */
function answer_allowed_html(): array {
	return array(
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
}

/**
 * Drop anything unpublishable and return a clean, ordered list.
 *
 * A pair needs both a question and an answer to mean anything, in the
 * accordion or in the schema graph, so a half-filled row is dropped rather
 * than rendered empty or published as a Question with no Answer.
 *
 * @param mixed $raw Raw `faqs` attribute — trusted to be nothing in particular.
 * @return array<int, array{question: string, answer: string}>
 */
function normalize_faqs( $raw ): array {
	$faqs = array();

	foreach ( (array) $raw as $faq ) {
		if ( ! is_array( $faq ) ) {
			continue;
		}

		$question = (string) ( $faq['question'] ?? '' );
		$answer   = (string) ( $faq['answer'] ?? '' );

		if ( '' === trim( wp_strip_all_tags( $question ) ) ) {
			continue;
		}

		if ( '' === trim( wp_strip_all_tags( $answer ) ) ) {
			continue;
		}

		$faqs[] = array(
			'question' => $question,
			'answer'   => $answer,
		);
	}

	return $faqs;
}

/**
 * Reduce an answer to the inline formatting the block actually supports.
 *
 * @param string $answer Answer as authored.
 * @return string Answer with only allowed inline elements left standing.
 */
function sanitize_answer( string $answer ): string {
	return wp_kses( $answer, answer_allowed_html() );
}

/**
 * Reduce a value to plain text, as Google's structured-data guidance expects.
 *
 * @param string $value Value to flatten.
 * @return string
 */
function plain_text( string $value ): string {
	return trim( wp_strip_all_tags( $value ) );
}

/**
 * Build the schema.org FAQPage graph.
 *
 * The single source of the JSON-LD, called by the block's render callback
 * and by the REST endpoint alike. Answers are flattened to plain text per
 * Google's guidelines; the graph is never assembled by string concatenation.
 *
 * @param array<int, array{question: string, answer: string}> $faqs Normalised pairs.
 * @return array<string, mixed> FAQPage graph, ready for wp_json_encode().
 */
function build_schema( array $faqs ): array {
	return array(
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => array_map(
			static function ( array $faq ): array {
				return array(
					'@type'          => 'Question',
					'name'           => plain_text( $faq['question'] ),
					'acceptedAnswer' => array(
						'@type' => 'Answer',
						'text'  => plain_text( $faq['answer'] ),
					),
				);
			},
			$faqs
		),
	);
}

/**
 * Collect every FAQ pair a post publishes, in the order they appear.
 *
 * Walks the parsed block tree rather than reading a meta field, because the
 * block attributes are the source of truth — the same place the renderer
 * reads from. Inner blocks are walked too, so a FAQ block nested inside a
 * Group or Columns is found.
 *
 * @param \WP_Post $post Post to read.
 * @return array<int, array{question: string, answer: string}>
 */
function faqs_from_post( \WP_Post $post ): array {
	$collected = array();

	$walk = static function ( array $blocks ) use ( &$walk, &$collected ): void {
		foreach ( $blocks as $block ) {
			if ( 'answer-ready/faq' === ( $block['blockName'] ?? '' ) ) {
				$collected = array_merge(
					$collected,
					normalize_faqs( $block['attrs']['faqs'] ?? array() )
				);
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				$walk( $block['innerBlocks'] );
			}
		}
	};

	$walk( parse_blocks( $post->post_content ) );

	return $collected;
}
