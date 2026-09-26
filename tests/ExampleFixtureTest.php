<?php
/**
 * Keeps the Next.js example's saved response honest.
 *
 * examples/headless-nextjs/test/fixture.json is a copy of a real endpoint
 * response, and the example's smoke test builds against it. A saved copy
 * rots: change the payload shape in includes/rest.php and the example would
 * keep passing against a contract the plugin no longer honours.
 *
 * So this rebuilds the fixture's content from the demo FAQ in blueprint.json,
 * using the plugin's own data layer, and fails if they disagree. The chain it
 * holds together is plugin code → demo content → the example's fixture.
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
final class ExampleFixtureTest extends TestCase {

	private const FIXTURE_PATH   = __DIR__ . '/../examples/headless-nextjs/test/fixture.json';
	private const BLUEPRINT_PATH = __DIR__ . '/../blueprint.json';

	/**
	 * The saved response the example builds against.
	 *
	 * @return array<string, mixed>
	 */
	private function fixture(): array {
		$this->assertFileExists( self::FIXTURE_PATH, 'The example has no saved endpoint response.' );

		$decoded = json_decode( (string) file_get_contents( self::FIXTURE_PATH ), true );

		$this->assertIsArray( $decoded, 'The saved response is not valid JSON.' );

		return $decoded;
	}

	/**
	 * The demo FAQ attributes, lifted from the block comment the blueprint
	 * writes into the demo page.
	 *
	 * @return array<int, mixed>
	 */
	private function demo_faq_attributes(): array {
		$blueprint = json_decode( (string) file_get_contents( self::BLUEPRINT_PATH ), true );

		$this->assertIsArray( $blueprint, 'blueprint.json is not valid JSON.' );

		$code = '';
		foreach ( $blueprint['steps'] as $step ) {
			if ( 'runPHP' === ( $step['step'] ?? '' ) && str_contains( (string) ( $step['code'] ?? '' ), 'wp_insert_post' ) ) {
				$code = (string) $step['code'];
				break;
			}
		}

		$this->assertNotSame( '', $code, 'No page-creation step found in blueprint.json.' );
		$this->assertSame(
			1,
			preg_match( '~<!-- wp:answer-ready/faq (\{.*?\}) /-->~s', $code, $matches ),
			'No Answer-Ready FAQ block found in the demo page content.'
		);

		$attributes = json_decode( $matches[1], true );

		$this->assertIsArray( $attributes, 'The block attributes are not valid JSON.' );

		return $attributes['faqs'] ?? array();
	}

	public function test_fixture_faqs_match_the_demo_content(): void {
		$faqs     = Data\normalize_faqs( $this->demo_faq_attributes() );
		$expected = array_map(
			static function ( array $faq ): array {
				return array(
					'question' => Data\plain_text( $faq['question'] ),
					'answer'   => Data\sanitize_answer( $faq['answer'] ),
				);
			},
			$faqs
		);

		$this->assertSame(
			$expected,
			$this->fixture()['faqs'],
			'The example is built against answers the plugin no longer produces. Re-save the fixture.'
		);
	}

	/**
	 * The example republishes this graph verbatim into its <head>, so if it
	 * goes stale the example starts publishing structured data that no
	 * WordPress site would actually emit.
	 */
	public function test_fixture_schema_matches_the_builder(): void {
		$expected = Data\build_schema( Data\normalize_faqs( $this->demo_faq_attributes() ) );

		$this->assertSame(
			$expected,
			$this->fixture()['schema'],
			'The example is built against a FAQPage graph the plugin no longer builds. Re-save the fixture.'
		);
	}

	public function test_fixture_carries_every_key_the_payload_promises(): void {
		$this->assertSame(
			array( 'postId', 'title', 'url', 'modified', 'count', 'faqs', 'schema' ),
			array_keys( $this->fixture() ),
			'The saved response no longer has the shape includes/rest.php returns.'
		);
	}

	public function test_fixture_count_agrees_with_its_own_faqs(): void {
		$fixture = $this->fixture();

		$this->assertSame( count( $fixture['faqs'] ), $fixture['count'] );
		$this->assertSame( count( $fixture['faqs'] ), count( $fixture['schema']['mainEntity'] ) );
	}
}
