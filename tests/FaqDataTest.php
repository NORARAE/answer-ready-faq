<?php
/**
 * Tests for the shared FAQ data layer.
 *
 * @package AnswerReadyFAQ
 */

declare( strict_types=1 );

namespace AnswerReadyFAQ\Tests;

use PHPUnit\Framework\TestCase;
use AnswerReadyFAQ\Data;

/**
 * @covers \AnswerReadyFAQ\Data
 */
final class FaqDataTest extends TestCase {

	/**
	 * A representative set of pairs, as the editor would save them.
	 *
	 * @return array<int, array{question: string, answer: string}>
	 */
	private function sample(): array {
		return array(
			array(
				'question' => 'What is answer engine optimization?',
				'answer'   => 'Structuring content so AI systems can <strong>find</strong> and cite it.',
			),
			array(
				'question' => 'Does FAQ schema still matter?',
				'answer'   => 'Yes — see <a href="https://schema.org/FAQPage">the spec</a>.',
			),
		);
	}

	protected function setUp(): void {
		$GLOBALS['wp_kses_calls'] = array();
	}

	public function test_normalize_keeps_complete_pairs_in_order(): void {
		$faqs = Data\normalize_faqs( $this->sample() );

		$this->assertCount( 2, $faqs );
		$this->assertSame( 'What is answer engine optimization?', $faqs[0]['question'] );
		$this->assertSame( 'Does FAQ schema still matter?', $faqs[1]['question'] );
	}

	/**
	 * A pair needs both halves to mean anything, in the accordion or in the
	 * graph. Half a pair is dropped rather than published as a Question with
	 * an empty Answer.
	 *
	 * @dataProvider provide_unpublishable_rows
	 *
	 * @param mixed  $row  Row that should not survive.
	 * @param string $why  Assertion message.
	 */
	public function test_normalize_drops_unpublishable_rows( $row, string $why ): void {
		$this->assertSame( array(), Data\normalize_faqs( array( $row ) ), $why );
	}

	/**
	 * @return array<string, array{0: mixed, 1: string}>
	 */
	public static function provide_unpublishable_rows(): array {
		return array(
			'missing answer'      => array( array( 'question' => 'Q?' ), 'a question with no answer' ),
			'missing question'    => array( array( 'answer' => 'A.' ), 'an answer with no question' ),
			'whitespace only'     => array( array( 'question' => "  \n ", 'answer' => 'A.' ), 'whitespace is not a question' ),
			'markup with no text' => array( array( 'question' => '<em></em>', 'answer' => 'A.' ), 'empty markup is not a question' ),
			'not an array'        => array( 'just a string', 'a scalar row' ),
			'null'                => array( null, 'a null row' ),
		);
	}

	public function test_normalize_tolerates_junk_input(): void {
		$this->assertSame( array(), Data\normalize_faqs( null ) );
		$this->assertSame( array(), Data\normalize_faqs( 'nonsense' ) );
		$this->assertSame( array(), Data\normalize_faqs( array() ) );
	}

	public function test_schema_has_the_shape_google_documents(): void {
		$schema = Data\build_schema( Data\normalize_faqs( $this->sample() ) );

		$this->assertSame( 'https://schema.org', $schema['@context'] );
		$this->assertSame( 'FAQPage', $schema['@type'] );
		$this->assertCount( 2, $schema['mainEntity'] );
		$this->assertSame( 'Question', $schema['mainEntity'][0]['@type'] );
		$this->assertSame( 'Answer', $schema['mainEntity'][0]['acceptedAnswer']['@type'] );
	}

	/**
	 * Google's structured-data guidance wants plain text in the graph, so the
	 * inline formatting an answer is allowed to carry must not reach it.
	 */
	public function test_schema_answers_are_flattened_to_plain_text(): void {
		$schema = Data\build_schema( Data\normalize_faqs( $this->sample() ) );
		$text   = $schema['mainEntity'][0]['acceptedAnswer']['text'];

		$this->assertStringNotContainsString( '<strong>', $text );
		$this->assertStringNotContainsString( '<', $text );
		$this->assertSame( 'Structuring content so AI systems can find and cite it.', $text );
	}

	public function test_empty_input_yields_an_empty_graph_not_a_broken_one(): void {
		$schema = Data\build_schema( array() );

		$this->assertSame( 'FAQPage', $schema['@type'] );
		$this->assertSame( array(), $schema['mainEntity'] );
	}

	/**
	 * The editor constrains formatting for UX; the server enforces it. This
	 * asserts the allowlist we hand to wp_kses, not what kses does with it.
	 */
	public function test_sanitize_answer_passes_the_inline_only_allowlist(): void {
		Data\sanitize_answer( '<script>alert(1)</script><strong>ok</strong>' );

		$this->assertCount( 1, $GLOBALS['wp_kses_calls'] );

		$allowed = $GLOBALS['wp_kses_calls'][0]['allowed'];

		$this->assertSame( array( 'a', 'strong', 'em', 'code', 'br' ), array_keys( $allowed ) );
		$this->assertArrayNotHasKey( 'script', $allowed );
		$this->assertArrayNotHasKey( 'div', $allowed );
		$this->assertSame( array( 'href' => true, 'rel' => true, 'target' => true ), $allowed['a'] );
	}

	public function test_plain_text_trims_and_strips(): void {
		$this->assertSame( 'Hello', Data\plain_text( '  <em>Hello</em> ' ) );
		$this->assertSame( '', Data\plain_text( '   ' ) );
	}
}
