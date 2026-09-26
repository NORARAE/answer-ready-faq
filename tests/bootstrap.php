<?php
/**
 * Test bootstrap.
 *
 * The data layer was written to lean on almost nothing from WordPress —
 * wp_strip_all_tags() and wp_kses() and no more — so these tests run against
 * plain PHP with those two stubbed, and need no WordPress install, no
 * database, and no wp-env. That keeps them fast enough to run on every save.
 *
 * The stubs are deliberately thin. Nothing here re-implements kses: the test
 * that cares about sanitising asserts which allowlist we hand to wp_kses,
 * not what WordPress then does with it, because the second is WordPress's
 * job to get right and ours to call correctly.
 *
 * @package AnswerReadyFAQ
 */

declare( strict_types=1 );

/**
 * Calls recorded by the wp_kses() stub, so a test can assert on them.
 *
 * @var array<int, array{content: string, allowed: array}>
 */
$GLOBALS['wp_kses_calls'] = array();

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	/**
	 * Stub of wp_strip_all_tags().
	 *
	 * @param string $text           Text to strip.
	 * @param bool   $remove_breaks  Whether to collapse whitespace.
	 * @return string
	 */
	function wp_strip_all_tags( $text, $remove_breaks = false ): string {
		$text = (string) $text;
		$text = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $text );
		$text = strip_tags( (string) $text );

		if ( $remove_breaks ) {
			$text = preg_replace( '/[\r\n\t ]+/', ' ', (string) $text );
		}

		return trim( (string) $text );
	}
}

if ( ! function_exists( 'wp_kses' ) ) {
	/**
	 * Recording stub of wp_kses().
	 *
	 * @param string $content Content to filter.
	 * @param array  $allowed Allowed HTML.
	 * @return string
	 */
	function wp_kses( $content, $allowed ): string {
		$GLOBALS['wp_kses_calls'][] = array(
			'content' => (string) $content,
			'allowed' => $allowed,
		);

		$tags = '';
		foreach ( array_keys( (array) $allowed ) as $tag ) {
			$tags .= '<' . $tag . '>';
		}

		return strip_tags( (string) $content, $tags );
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * Stub of wp_json_encode().
	 *
	 * @param mixed $data    Data to encode.
	 * @param int   $options JSON options.
	 * @param int   $depth   Max depth.
	 * @return string|false
	 */
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		return json_encode( $data, (int) $options, (int) $depth );
	}
}

require_once __DIR__ . '/../includes/faq-data.php';
