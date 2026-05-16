<?php
/**
 * Public agent-readiness routes.
 *
 * @package WP_Agentic
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WP_AGENTIC_TESTING' ) ) {
	exit;
}

/**
 * Generates virtual route responses and robots.txt signals.
 */
class WP_Agentic_Routes {
	const QUERY_VAR = 'wp_agentic_route';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'add_rewrite_rules' ) );
		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_serve_route' ), 0 );
		add_action( 'template_redirect', array( 'WP_Agentic_Markdown', 'maybe_serve_markdown' ), 1 );
		add_filter( 'robots_txt', array( __CLASS__, 'filter_robots_txt' ), 20, 2 );
	}

	/**
	 * Add rewrite rules for virtual files.
	 *
	 * @return void
	 */
	public static function add_rewrite_rules() {
		add_rewrite_rule( '^llms\.txt$', 'index.php?' . self::QUERY_VAR . '=llms', 'top' );
		add_rewrite_rule( '^\.well-known/llms\.txt$', 'index.php?' . self::QUERY_VAR . '=well-known-llms', 'top' );
		add_rewrite_rule( '^\.well-known/api-catalog/?$', 'index.php?' . self::QUERY_VAR . '=api-catalog', 'top' );
		add_rewrite_rule( '^\.well-known/agent-skills/index\.json$', 'index.php?' . self::QUERY_VAR . '=agent-skills', 'top' );
	}

	/**
	 * Register query vars.
	 *
	 * @param array<int,string> $vars Query vars.
	 * @return array<int,string>
	 */
	public static function query_vars( $vars ) {
		$vars[] = self::QUERY_VAR;

		return $vars;
	}

	/**
	 * Serve a plugin route when requested.
	 *
	 * @return void
	 */
	public static function maybe_serve_route() {
		if ( ! WP_Agentic_Settings::enabled() ) {
			return;
		}

		$route = get_query_var( self::QUERY_VAR );
		if ( empty( $route ) ) {
			return;
		}

		$settings = WP_Agentic_Settings::get();

		if ( in_array( $route, array( 'llms', 'well-known-llms' ), true ) && ! empty( $settings['enable_llms'] ) ) {
			self::send_text( self::llms_text( $settings ), 'text/plain; charset=UTF-8' );
		}

		if ( 'api-catalog' === $route && ! empty( $settings['enable_api_catalog'] ) ) {
			self::send_json( self::api_catalog( $settings ), 'application/linkset+json; charset=UTF-8' );
		}

		if ( 'agent-skills' === $route && ! empty( $settings['enable_agent_skills'] ) ) {
			self::send_json( self::agent_skills( $settings ), 'application/json; charset=UTF-8' );
		}
	}

	/**
	 * Append Content-Signal to robots.txt.
	 *
	 * @param string $output Robots output.
	 * @param bool   $public Site public flag.
	 * @return string
	 */
	public static function filter_robots_txt( $output, $public ) {
		unset( $public );

		if ( ! WP_Agentic_Settings::enabled() ) {
			return $output;
		}

		$settings = WP_Agentic_Settings::get();
		if ( empty( $settings['enable_content_signals'] ) ) {
			return $output;
		}

		$signal_line = 'Content-Signal: ' . WP_Agentic_Settings::content_signal_value( $settings );
		if ( false !== stripos( $output, 'Content-Signal:' ) ) {
			return $output;
		}

		return rtrim( $output ) . "\n" . $signal_line . "\n";
	}

	/**
	 * Build llms.txt content.
	 *
	 * @param array<string,mixed> $settings Settings.
	 * @return string
	 */
	public static function llms_text( $settings ) {
		$site_url      = self::clean_url( $settings['publisher_url'] ?? self::home_url() );
		$name          = self::clean_text( $settings['publisher_name'] ?? self::site_name() );
		$contact_url   = self::clean_url( $settings['contact_url'] ?? self::home_url( 'contato/' ) );
		$wp_json       = self::home_url( 'wp-json/' );
		$api_catalog   = self::home_url( '.well-known/api-catalog' );
		$agent_skills  = self::home_url( '.well-known/agent-skills/index.json' );
		$sitemap       = self::sitemap_url();
		$search_sample = self::home_url( '?s=marketing' );

		$lines = array(
			'# ' . $name,
			'',
			'> Agent-readable overview for ' . $name . '.',
			'',
			'## Site',
			'- Home: ' . $site_url,
			'- Contact: ' . $contact_url,
			'- Search example: ' . $search_sample,
			'',
			'## Agent resources',
			'- API catalog: ' . $api_catalog,
			'- Agent skills: ' . $agent_skills,
			'- WordPress REST API: ' . $wp_json,
			'- Sitemap: ' . $sitemap,
			'',
			'## Content signals',
			'- Content-Signal: ' . WP_Agentic_Settings::content_signal_value( $settings ),
			'',
			'## Usage',
			'- Public pages support Markdown negotiation with `Accept: text/markdown` when enabled.',
			'- Tools and routes are read-only in v1.',
		);

		return implode( "\n", $lines ) . "\n";
	}

	/**
	 * Build an RFC 9264-style Linkset catalog.
	 *
	 * @param array<string,mixed> $settings Settings.
	 * @return array<string,mixed>
	 */
	public static function api_catalog( $settings ) {
		$linkset = array(
			array(
				'anchor'       => array( self::home_url() ),
				'service-desc' => array(
					array(
						'href'  => self::home_url( 'wp-json/' ),
						'type'  => 'application/json',
						'title' => 'WordPress REST API',
					),
				),
				'service-doc'  => array(
					array(
						'href'  => self::home_url( 'wp-json/' ),
						'type'  => 'application/json',
						'title' => 'REST API index',
					),
				),
				'describedby'  => array(
					array(
						'href'  => self::home_url( 'llms.txt' ),
						'type'  => 'text/plain',
						'title' => 'llms.txt',
					),
					array(
						'href'  => self::home_url( '.well-known/agent-skills/index.json' ),
						'type'  => 'application/json',
						'title' => 'Agent skills',
					),
				),
				'item'         => array(
					array(
						'href'  => self::sitemap_url(),
						'type'  => 'application/xml',
						'title' => 'Sitemap',
					),
				),
			),
		);

		if ( ! empty( $settings['include_graphql_if_active'] ) && self::graphql_available() ) {
			$linkset[0]['service-desc'][] = array(
				'href'  => self::home_url( 'graphql' ),
				'type'  => 'application/graphql-response+json',
				'title' => 'WPGraphQL endpoint',
			);
		}

		return array( 'linkset' => $linkset );
	}

	/**
	 * Build agent skills index.
	 *
	 * @param array<string,mixed> $settings Settings.
	 * @return array<string,mixed>
	 */
	public static function agent_skills( $settings ) {
		$name        = self::clean_text( $settings['publisher_name'] ?? self::site_name() );
		$contact_url = self::clean_url( $settings['contact_url'] ?? self::home_url( 'contato/' ) );

		return array(
			'name'        => $name . ' Agent Skills',
			'version'     => WP_AGENTIC_VERSION,
			'description' => 'Read-only public skills for discovering and reading site content.',
			'publisher'   => array(
				'name' => $name,
				'url'  => self::clean_url( $settings['publisher_url'] ?? self::home_url() ),
			),
			'skills'      => array(
				array(
					'id'          => 'read_site_content',
					'name'        => 'Read site content',
					'description' => 'Read public pages and posts from the site.',
					'type'        => 'read',
					'endpoint'    => self::home_url( 'wp-json/wp/v2/' ),
				),
				array(
					'id'          => 'search_site',
					'name'        => 'Search site',
					'description' => 'Search public WordPress content.',
					'type'        => 'read',
					'endpoint'    => self::home_url( 'wp-json/wp/v2/search' ),
					'parameters'  => array(
						'search' => 'Search query string.',
					),
				),
				array(
					'id'          => 'read_article',
					'name'        => 'Read article',
					'description' => 'Read a public article by URL or WordPress REST API id.',
					'type'        => 'read',
					'endpoint'    => self::home_url( 'wp-json/wp/v2/posts/{id}' ),
				),
				array(
					'id'                 => 'contact_conversion',
					'name'               => 'Contact',
					'description'        => 'Open the public contact page. Submitting forms requires human confirmation.',
					'type'               => 'handoff',
					'endpoint'           => $contact_url,
					'human_confirmation' => true,
				),
			),
		);
	}

	/**
	 * Send text response and exit.
	 *
	 * @param string $body Body.
	 * @param string $content_type Content-Type.
	 * @return never
	 */
	private static function send_text( $body, $content_type ) {
		status_header( 200 );
		header( 'Content-Type: ' . $content_type );
		header( 'X-WP-Agentic: 1' );
		echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Send JSON response and exit.
	 *
	 * @param array<string,mixed> $data Data.
	 * @param string              $content_type Content-Type.
	 * @return never
	 */
	private static function send_json( $data, $content_type ) {
		status_header( 200 );
		header( 'Content-Type: ' . $content_type );
		header( 'X-WP-Agentic: 1' );
		echo wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Check whether WPGraphQL appears active.
	 *
	 * @return bool
	 */
	private static function graphql_available() {
		return class_exists( 'WPGraphQL' ) || function_exists( 'graphql' ) || has_action( 'graphql_register_types' );
	}

	/**
	 * Resolve home URL.
	 *
	 * @param string $path Optional path.
	 * @return string
	 */
	private static function home_url( $path = '' ) {
		if ( function_exists( 'home_url' ) ) {
			return home_url( $path );
		}

		return 'https://example.com/' . ltrim( $path, '/' );
	}

	/**
	 * Resolve the most likely public sitemap URL.
	 *
	 * @return string
	 */
	private static function sitemap_url() {
		if ( defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' ) ) {
			return self::home_url( 'sitemap_index.xml' );
		}

		return self::home_url( 'wp-sitemap.xml' );
	}

	/**
	 * Resolve site name.
	 *
	 * @return string
	 */
	private static function site_name() {
		return function_exists( 'get_bloginfo' ) ? get_bloginfo( 'name' ) : 'WordPress Site';
	}

	/**
	 * Clean URL for generated documents.
	 *
	 * @param mixed $url URL.
	 * @return string
	 */
	private static function clean_url( $url ) {
		$url = (string) $url;

		return function_exists( 'esc_url_raw' ) ? esc_url_raw( $url ) : filter_var( $url, FILTER_SANITIZE_URL );
	}

	/**
	 * Clean text for generated documents.
	 *
	 * @param mixed $text Text.
	 * @return string
	 */
	private static function clean_text( $text ) {
		$text = (string) $text;

		return function_exists( 'sanitize_text_field' ) ? sanitize_text_field( $text ) : trim( strip_tags( $text ) );
	}
}
