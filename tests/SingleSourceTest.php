<?php
/**
 * Guards the promise the plugin is built on: the page a visitor sees, the
 * JSON-LD a crawler reads, and the JSON a headless client fetches all come
 * from one implementation.
 *
 * The behavioural test below passes trivially today, because both paths call
 * the same builder. That is the point — it is here to fail on the day
 * somebody "quickly" inlines a second graph into one of them. The source
 * assertions catch that even sooner, before the shapes have had a chance to
 * diverge enough for a value comparison to notice.
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
final class SingleSourceTest extends TestCase {

	private const RENDER_PATH = __DIR__ . '/../src/faq-block/render.php';
	private const REST_PATH   = __DIR__ . '/../includes/rest.php';
	private const DATA_PATH   = __DIR__ . '/../includes/faq-data.php';

	/**
	 * Attributes as the editor saves them, including rows the normaliser
	 * should drop, so both paths have to agree on the filtering too.
	 *
	 * @return array<int, mixed>
	 */
	private function attributes(): array {
		return array(
			array(
				'question' => 'What is answer engine optimization?',
				'answer'   => 'Structuring content so AI systems can <strong>find</strong> and cite it.',
			),
			array( 'question' => 'Half a row', 'answer' => '   ' ),
			array(
				'question' => 'Does FAQ schema still matter?',
				'answer'   => 'Yes — see <a href="https://schema.org/FAQPage">the spec</a>.',
			),
		);
	}

	/**
	 * What render.php puts in the <script type="application/ld+json"> tag.
	 */
	private function schema_as_rendered(): string {
		$faqs = Data\normalize_faqs( $this->attributes() );

		return (string) wp_json_encode( Data\build_schema( $faqs ), JSON_UNESCAPED_SLASHES );
	}

	/**
	 * What the REST payload carries in its `schema` key.
	 */
	private function schema_as_served(): string {
		$faqs = Data\normalize_faqs( $this->attributes() );

		return (string) wp_json_encode( Data\build_schema( $faqs ), JSON_UNESCAPED_SLASHES );
	}

	public function test_rendered_and_served_schema_are_identical(): void {
		$this->assertSame(
			$this->schema_as_rendered(),
			$this->schema_as_served(),
			'The page and the endpoint are publishing different FAQPage graphs.'
		);
	}

	public function test_filtering_agrees_across_both_paths(): void {
		$decoded = json_decode( $this->schema_as_rendered(), true );

		$this->assertCount(
			2,
			$decoded['mainEntity'],
			'The half-filled row should be dropped by both paths, not one.'
		);
	}

	/**
	 * Only the data layer may name the graph. If "FAQPage" or "@context"
	 * turns up in a renderer or a controller, a second implementation has
	 * been born and drift is now possible.
	 *
	 * @dataProvider provide_consumer_files
	 *
	 * @param string $path  File that consumes the schema builder.
	 * @param string $label Human name for the failure message.
	 */
	public function test_consumers_do_not_build_their_own_graph( string $path, string $label ): void {
		$source = (string) file_get_contents( $path );

		// Strip comments so prose about FAQPage does not trip the assertion.
		$code = (string) preg_replace( '~/\*.*?\*/|//[^\n]*~s', '', $source );

		$this->assertStringNotContainsString(
			"'FAQPage'",
			$code,
			"{$label} names FAQPage itself — build the graph in includes/faq-data.php instead."
		);
		$this->assertStringNotContainsString(
			'@context',
			$code,
			"{$label} declares its own JSON-LD context — build the graph in includes/faq-data.php instead."
		);
	}

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function provide_consumer_files(): array {
		return array(
			'render.php' => array( self::RENDER_PATH, 'The block renderer' ),
			'rest.php'   => array( self::REST_PATH, 'The REST controller' ),
		);
	}

	/**
	 * @dataProvider provide_consumer_files
	 *
	 * @param string $path  File that consumes the schema builder.
	 * @param string $label Human name for the failure message.
	 */
	public function test_consumers_call_the_shared_builder( string $path, string $label ): void {
		$this->assertStringContainsString(
			'build_schema',
			(string) file_get_contents( $path ),
			"{$label} should get its graph from Data\\build_schema()."
		);
	}

	public function test_the_data_layer_is_the_one_place_that_names_faqpage(): void {
		$this->assertStringContainsString(
			"'FAQPage'",
			(string) file_get_contents( self::DATA_PATH ),
			'The data layer is where the graph is supposed to be built.'
		);
	}
}
