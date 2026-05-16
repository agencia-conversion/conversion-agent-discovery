<?php
define( 'WP_AGENTIC_TESTING', true );
define( 'WP_AGENTIC_VERSION', '0.1.0' );

function wp_strip_all_tags( $text ) {
	return strip_tags( $text );
}

function wp_parse_args( $args, $defaults = array() ) {
	return array_merge( $defaults, is_array( $args ) ? $args : array() );
}

function home_url( $path = '' ) {
	return 'https://example.com/' . ltrim( $path, '/' );
}

function get_bloginfo( $show = '' ) {
	return 'Example Site';
}

function esc_url_raw( $url ) {
	return filter_var( $url, FILTER_SANITIZE_URL );
}

function sanitize_text_field( $text ) {
	return trim( strip_tags( (string) $text ) );
}

function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function trailingslashit( $string ) {
	return rtrim( $string, '/' ) . '/';
}

function has_action( $hook ) {
	return false;
}

require_once __DIR__ . '/../includes/class-wp-agentic-settings.php';
require_once __DIR__ . '/../includes/class-wp-agentic-markdown.php';
require_once __DIR__ . '/../includes/class-wp-agentic-routes.php';

$failures = 0;

function assert_true( $condition, $message ) {
	global $failures;
	if ( ! $condition ) {
		$failures++;
		echo "FAIL: {$message}\n";
		return;
	}

	echo "PASS: {$message}\n";
}

$settings = WP_Agentic_Settings::sanitize(
	array(
		'publisher_name'          => '<b>Conversion</b>',
		'publisher_url'           => 'https://www.conversion.com.br/',
		'contact_url'             => 'https://www.conversion.com.br/contato/',
		'content_signal_ai_train' => 'yes',
		'content_signal_search'   => 'yes',
		'content_signal_ai_input' => 'yes',
		'enabled'                 => '1',
	)
);

assert_true( 'Conversion' === $settings['publisher_name'], 'settings sanitize publisher name' );
assert_true( 'ai-train=yes, search=yes, ai-input=yes' === WP_Agentic_Settings::content_signal_value( $settings ), 'content signal value' );

assert_true( WP_Agentic_Markdown::accepts_markdown( 'text/html, text/markdown;q=1' ), 'Accept header detects text/markdown' );
assert_true( ! WP_Agentic_Markdown::accepts_markdown( 'text/html, application/json' ), 'Accept header rejects missing markdown' );
assert_true( ! WP_Agentic_Markdown::accepts_markdown( 'text/markdown;q=0, text/html' ), 'Accept header rejects q=0' );

$markdown = WP_Agentic_Markdown::html_to_markdown( '<h1>Hello</h1><p>Read <a href="https://example.com/x">this</a>.</p><script>alert(1)</script>' );
assert_true( false !== strpos( $markdown, '# Hello' ), 'HTML h1 converts to Markdown heading' );
assert_true( false !== strpos( $markdown, '[this](https://example.com/x)' ), 'HTML link converts to Markdown link' );
assert_true( false === strpos( $markdown, 'alert' ), 'script content removed from Markdown' );

$catalog = WP_Agentic_Routes::api_catalog( $settings );
assert_true( isset( $catalog['linkset'][0]['service-desc'][0]['href'] ), 'API catalog exposes service-desc' );
assert_true( 'https://example.com/wp-json/' === $catalog['linkset'][0]['service-desc'][0]['href'], 'API catalog points to REST API' );

$skills = WP_Agentic_Routes::agent_skills( $settings );
assert_true( 4 === count( $skills['skills'] ), 'agent skills exposes four skills' );
assert_true( 'read_site_content' === $skills['skills'][0]['id'], 'agent skills includes read_site_content' );

$llms = WP_Agentic_Routes::llms_text( $settings );
assert_true( false !== strpos( $llms, '## Agent resources' ), 'llms.txt includes agent resources' );
assert_true( false !== strpos( $llms, 'Content-Signal: ai-train=yes, search=yes, ai-input=yes' ), 'llms.txt includes Content-Signal' );

if ( $failures > 0 ) {
	echo "{$failures} failure(s)\n";
	exit( 1 );
}

echo "All tests passed\n";
