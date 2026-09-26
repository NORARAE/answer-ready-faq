<?php
/**
 * Read-only REST endpoint: GET /wp-json/answer-ready/v1/faqs/<post_id>
 *
 * Returns a post's FAQ pairs together with the FAQPage JSON-LD the page
 * itself publishes. Both come from AnswerReadyFAQ\Data, so the API answer
 * and the rendered page are the same content by construction — a headless
 * front end and a crawler looking at the HTML cannot be told two different
 * things.
 *
 * The endpoint is public because the data is public: it only ever reads
 * posts that are already published and already readable without a login.
 *
 * @package AnswerReadyFAQ
 */

declare( strict_types=1 );

namespace AnswerReadyFAQ\REST;

use AnswerReadyFAQ\Data;

const NAMESPACE_V1 = 'answer-ready/v1';

/**
 * How long a response may be reused, in seconds.
 *
 * The cache key carries the post's modified time, so an edit produces a new
 * key rather than a stale hit; this window only bounds how long an orphaned
 * entry sits in storage.
 */
const CACHE_TTL = 12 * HOUR_IN_SECONDS;

/**
 * Register the route.
 *
 * @return void
 */
function register_routes(): void {
	register_rest_route(
		NAMESPACE_V1,
		'/faqs/(?P<id>[\d]+)',
		array(
			'methods'             => 'GET',
			'callback'            => __NAMESPACE__ . '\get_faqs',
			// Public: the callback refuses anything that is not already
			// published and publicly viewable, so there is nothing here a
			// visitor could not read from the page itself.
			'permission_callback' => '__return_true',
			'args'                => array(
				'id' => array(
					'description'       => __( 'ID of the post or page to read FAQs from.', 'answer-ready-faq' ),
					'type'              => 'integer',
					'required'          => true,
					'sanitize_callback' => 'absint',
					'validate_callback' => static function ( $value ): bool {
						return absint( $value ) > 0;
					},
				),
			),
		)
	);
}
add_action( 'rest_api_init', __NAMESPACE__ . '\register_routes' );

/**
 * Is this post one an anonymous visitor could already read?
 *
 * Drafts, pending posts, private posts, revisions, password-protected posts
 * and non-public post types are all refused — the endpoint must not become a
 * way to read something the site has not published.
 *
 * @param \WP_Post|null $post Post to check.
 * @return bool
 */
function is_publicly_readable( ?\WP_Post $post ): bool {
	if ( ! $post instanceof \WP_Post ) {
		return false;
	}

	if ( 'publish' !== $post->post_status ) {
		return false;
	}

	if ( '' !== (string) $post->post_password ) {
		return false;
	}

	$post_type = get_post_type_object( $post->post_type );

	return $post_type instanceof \WP_Post_Type && $post_type->public;
}

/**
 * Handle the request.
 *
 * @param \WP_REST_Request $request Incoming request.
 * @return \WP_REST_Response|\WP_Error
 */
function get_faqs( \WP_REST_Request $request ) {
	$post_id = absint( $request['id'] );
	$post    = get_post( $post_id );

	if ( ! is_publicly_readable( $post ) ) {
		// One shape of answer for "no such post" and "not yours to read", so
		// the endpoint cannot be used to probe for unpublished content.
		return new \WP_Error(
			'answer_ready_faq_not_found',
			__( 'No published post with that ID.', 'answer-ready-faq' ),
			array( 'status' => 404 )
		);
	}

	$cache_key = sprintf( 'arf_faqs_%d_%s', $post_id, $post->post_modified_gmt );
	$payload   = get_transient( $cache_key );

	if ( false === $payload ) {
		$payload = build_payload( $post );
		set_transient( $cache_key, $payload, CACHE_TTL );
	}

	$response = rest_ensure_response( $payload );

	// Public and cacheable: this is published content, and a headless front
	// end or a CDN in front of it should not have to ask twice.
	$response->header( 'Cache-Control', 'public, max-age=300, s-maxage=3600' );
	$response->header( 'Last-Modified', mysql2date( 'D, d M Y H:i:s', $post->post_modified_gmt, false ) . ' GMT' );

	return $response;
}

/**
 * Assemble the response body for a post.
 *
 * @param \WP_Post $post Post to read.
 * @return array<string, mixed>
 */
function build_payload( \WP_Post $post ): array {
	$faqs = Data\faqs_from_post( $post );

	return array(
		'postId'   => $post->ID,
		'title'    => Data\plain_text( get_the_title( $post ) ),
		'url'      => (string) get_permalink( $post ),
		'modified' => mysql2date( 'c', $post->post_modified_gmt, false ),
		'count'    => count( $faqs ),
		'faqs'     => array_map(
			static function ( array $faq ): array {
				return array(
					'question' => Data\plain_text( $faq['question'] ),
					// The same inline-only allowlist the page renders with,
					// so a headless client can print this without having to
					// trust or re-sanitise it.
					'answer'   => Data\sanitize_answer( $faq['answer'] ),
				);
			},
			$faqs
		),
		// Byte-for-byte the graph the page publishes: same builder, same input.
		'schema'   => Data\build_schema( $faqs ),
	);
}
