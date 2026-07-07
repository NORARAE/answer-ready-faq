<?php
/**
 * Plugin Name:       Answer-Ready FAQ Block
 * Plugin URI:        https://github.com/ngenetti/answer-ready-faq
 * Description:       An accessible FAQ accordion block that automatically outputs schema.org FAQPage JSON-LD, making your answers machine-readable for search engines and AI answer engines.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.0
 * Author:            Nora Genetti
 * Author URI:        https://www.linkedin.com/in/ngenetti/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       answer-ready-faq
 *
 * @package AnswerReadyFAQ
 */

declare( strict_types=1 );

namespace AnswerReadyFAQ;

// Abort if this file is called directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const PLUGIN_VERSION = '1.0.0';

/**
 * Register the block using the metadata loaded from block.json.
 *
 * Registration is metadata-driven: block.json is the single source of
 * truth for attributes, supports, and asset handles. The block is
 * dynamic (rendered via render.php) so the FAQPage JSON-LD is always
 * generated from the current attribute state on the server — it can
 * never drift out of sync with the visible content the way statically
 * saved markup can.
 *
 * @return void
 */
function register_block(): void {
	register_block_type( __DIR__ . '/build/faq-block' );
}
add_action( 'init', __NAMESPACE__ . '\register_block' );
