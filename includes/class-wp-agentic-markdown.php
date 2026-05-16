<?php
/**
 * Markdown content negotiation.
 *
 * @package WP_Agentic
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WP_AGENTIC_TESTING' ) ) {
	exit;
}

/**
 * Serves public WordPress content as Markdown when requested.
 */
class WP_Agentic_Markdown {
	/**
	 * Maybe serve the current request as Markdown.
	 *
	 * @return void
	 */
	public static function maybe_serve_markdown() {
		if ( ! WP_Agentic_Settings::enabled() ) {
			return;
		}

		$settings = WP_Agentic_Settings::get();
		if ( empty( $settings['enable_markdown'] ) || ! self::current_request_accepts_markdown() || ! self::is_allowed_request() ) {
			return;
		}

		$markdown = self::markdown_for_current_query();
		if ( '' === trim( $markdown ) ) {
			return;
		}

		self::send_markdown( $markdown );
	}

	/**
	 * Parse an Accept header for text/markdown support.
	 *
	 * @param string $accept Accept header.
	 * @return bool
	 */
	public static function accepts_markdown( $accept ) {
		if ( '' === trim( $accept ) ) {
			return false;
		}

		$parts = explode( ',', strtolower( $accept ) );
		foreach ( $parts as $part ) {
			$media = trim( explode( ';', $part )[0] );
			if ( 'text/markdown' === $media ) {
				return false === strpos( $part, 'q=0' );
			}
		}

		return false;
	}

	/**
	 * Convert HTML into conservative Markdown.
	 *
	 * @param string $html HTML.
	 * @return string
	 */
	public static function html_to_markdown( $html ) {
		$html = preg_replace( '#<(script|style|noscript)\b[^>]*>.*?</\1>#is', '', $html );
		$html = preg_replace( '#<br\s*/?>#i', "\n", $html );
		$html = preg_replace( '#</p>#i', "\n\n", $html );
		$html = preg_replace( '#<h1[^>]*>(.*?)</h1>#is', "\n# $1\n\n", $html );
		$html = preg_replace( '#<h2[^>]*>(.*?)</h2>#is', "\n## $1\n\n", $html );
		$html = preg_replace( '#<h3[^>]*>(.*?)</h3>#is', "\n### $1\n\n", $html );
		$html = preg_replace( '#<h4[^>]*>(.*?)</h4>#is', "\n#### $1\n\n", $html );
		$html = preg_replace( '#<li[^>]*>(.*?)</li>#is', "\n- $1", $html );
		$html = preg_replace_callback(
			'#<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)</a>#is',
			function ( $matches ) {
				$text = trim( wp_strip_all_tags( $matches[2] ) );
				$url  = trim( html_entity_decode( $matches[1], ENT_QUOTES, 'UTF-8' ) );

				if ( '' === $text || '' === $url ) {
					return $text;
				}

				return '[' . $text . '](' . $url . ')';
			},
			$html
		);

		$text = wp_strip_all_tags( $html );
		$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
		$text = preg_replace( "/[ \t]+\n/", "\n", $text );
		$text = preg_replace( "/\n{3,}/", "\n\n", $text );

		return trim( $text ) . "\n";
	}

	/**
	 * Estimate tokens for diagnostics.
	 *
	 * @param string $markdown Markdown.
	 * @return int
	 */
	public static function estimate_tokens( $markdown ) {
		$words = preg_split( '/\s+/', trim( $markdown ) );
		$count = is_array( $words ) && '' !== trim( $markdown ) ? count( $words ) : 0;

		return (int) ceil( $count * 1.33 );
	}

	/**
	 * Check current Accept header.
	 *
	 * @return bool
	 */
	private static function current_request_accepts_markdown() {
		$accept = isset( $_SERVER['HTTP_ACCEPT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT'] ) ) : '';

		return self::accepts_markdown( $accept );
	}

	/**
	 * Guard routes and methods that must stay untouched.
	 *
	 * @return bool
	 */
	private static function is_allowed_request() {
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET';
		if ( ! in_array( $method, array( 'GET', 'HEAD' ), true ) ) {
			return false;
		}

		if ( is_admin() || wp_doing_ajax() || is_feed() || is_robots() || is_trackback() || is_embed() ) {
			return false;
		}

		$uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		if ( preg_match( '#/(wp-admin|wp-login\.php|wp-json|xmlrpc\.php|wp-content|wp-includes|\.well-known)/#i', $uri ) ) {
			return false;
		}

		if ( preg_match( '#/(robots\.txt|llms\.txt)$#i', parse_url( $uri, PHP_URL_PATH ) ?: '' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Generate Markdown for the current query.
	 *
	 * @return string
	 */
	private static function markdown_for_current_query() {
		if ( is_singular() ) {
			return self::singular_markdown();
		}

		return self::archive_markdown();
	}

	/**
	 * Markdown for a single public post/page.
	 *
	 * @return string
	 */
	private static function singular_markdown() {
		$post = get_post();
		if ( ! $post || 'publish' !== get_post_status( $post ) ) {
			return '';
		}

		setup_postdata( $post );
		$title   = get_the_title( $post );
		$url     = get_permalink( $post );
		$content = apply_filters( 'the_content', $post->post_content );
		wp_reset_postdata();

		$markdown = '# ' . self::plain_text( $title ) . "\n\n";
		$markdown .= 'Source: ' . esc_url_raw( $url ) . "\n\n";
		$markdown .= self::html_to_markdown( $content );

		return $markdown;
	}

	/**
	 * Markdown for home, archives, and searches.
	 *
	 * @return string
	 */
	private static function archive_markdown() {
		global $wp_query;

		$title = self::archive_title();
		$items = array();

		if ( $wp_query && ! empty( $wp_query->posts ) ) {
			foreach ( $wp_query->posts as $post ) {
				if ( 'publish' !== get_post_status( $post ) ) {
					continue;
				}

				$excerpt = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( $post->post_content ), 40 );
				$items[] = '- [' . self::plain_text( get_the_title( $post ) ) . '](' . esc_url_raw( get_permalink( $post ) ) . '): ' . self::plain_text( $excerpt );
			}
		}

		$markdown = '# ' . self::plain_text( $title ) . "\n\n";
		$markdown .= 'Source: ' . esc_url_raw( home_url( add_query_arg( null, null ) ) ) . "\n\n";
		$markdown .= empty( $items ) ? "No public content found.\n" : implode( "\n", $items ) . "\n";

		return $markdown;
	}

	/**
	 * Human title for the current archive view.
	 *
	 * @return string
	 */
	private static function archive_title() {
		if ( is_search() ) {
			return 'Search results for ' . get_search_query();
		}

		if ( is_archive() ) {
			return get_the_archive_title();
		}

		return get_bloginfo( 'name' );
	}

	/**
	 * Send Markdown and exit.
	 *
	 * @param string $markdown Markdown.
	 * @return never
	 */
	private static function send_markdown( $markdown ) {
		if ( function_exists( 'do_action' ) ) {
			do_action( 'litespeed_control_set_nocache', 'wp-agentic-markdown' );
		}

		nocache_headers();
		status_header( 200 );
		header( 'Content-Type: text/markdown; charset=UTF-8' );
		header( 'Vary: Accept', false );
		header( 'X-Markdown-Tokens: ' . self::estimate_tokens( $markdown ) );
		header( 'X-WP-Agentic: 1' );
		echo $markdown; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Plain text helper.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	private static function plain_text( $text ) {
		return trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( html_entity_decode( (string) $text, ENT_QUOTES, 'UTF-8' ) ) ) );
	}
}
