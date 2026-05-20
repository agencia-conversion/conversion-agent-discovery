<?php
/**
 * Public conversion-agent-discovery routes.
 *
 * @package Conversion_Agent_Discovery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates virtual route responses and robots.txt signals.
 */
class Conversion_Agent_Discovery_Routes {
	const QUERY_VAR = 'conversion_agent_discovery_route';
	const SKILL_VAR = 'conversion_agent_discovery_skill';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'add_rewrite_rules' ) );
		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_serve_route' ), 0 );
		add_action( 'template_redirect', array( 'Conversion_Agent_Discovery_Markdown', 'maybe_serve_markdown' ), 1 );
		add_action( 'send_headers', array( __CLASS__, 'send_link_headers' ) );
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
		add_rewrite_rule( '^\.well-known/agent-skills/discovery-0\.2\.schema\.json$', 'index.php?' . self::QUERY_VAR . '=agent-skills-schema', 'top' );
		add_rewrite_rule( '^\.well-known/agent-skills/([a-z0-9-]+)/SKILL\.md$', 'index.php?' . self::QUERY_VAR . '=agent-skill&' . self::SKILL_VAR . '=$matches[1]', 'top' );
	}

	/**
	 * Register query vars.
	 *
	 * @param array<int,string> $vars Query vars.
	 * @return array<int,string>
	 */
	public static function query_vars( $vars ) {
		$vars[] = self::QUERY_VAR;
		$vars[] = self::SKILL_VAR;

		return $vars;
	}

	/**
	 * Serve a plugin route when requested.
	 *
	 * @return void
	 */
	public static function maybe_serve_route() {
		if ( ! Conversion_Agent_Discovery_Settings::enabled() ) {
			return;
		}

		$route = get_query_var( self::QUERY_VAR );
		if ( empty( $route ) ) {
			return;
		}

		$settings = Conversion_Agent_Discovery_Settings::get();

		if ( in_array( $route, array( 'llms', 'well-known-llms' ), true ) && ! empty( $settings['enable_llms'] ) ) {
			self::send_text( self::llms_text( $settings ), 'text/plain; charset=UTF-8' );
		}

		if ( 'api-catalog' === $route && ! empty( $settings['enable_api_catalog'] ) ) {
			self::send_json( self::api_catalog( $settings ), 'application/linkset+json; charset=UTF-8' );
		}

		if ( 'agent-skills' === $route && ! empty( $settings['enable_agent_skills'] ) ) {
			self::send_json( self::agent_skills( $settings ), 'application/json; charset=UTF-8' );
		}

		if ( 'agent-skills-schema' === $route && ! empty( $settings['enable_agent_skills'] ) ) {
			self::send_json( self::agent_skills_schema(), 'application/schema+json; charset=UTF-8' );
		}

		if ( 'agent-skill' === $route && ! empty( $settings['enable_agent_skills'] ) ) {
			$skill = sanitize_key( get_query_var( self::SKILL_VAR ) );
			$body  = self::agent_skill_markdown( $skill, $settings );
			if ( '' !== $body ) {
				self::send_text( $body, 'text/markdown; charset=UTF-8' );
			}
		}
	}

	/**
	 * Advertise agent resources through explicit Link headers.
	 *
	 * @return void
	 */
	public static function send_link_headers() {
		if ( is_admin() || ! Conversion_Agent_Discovery_Settings::enabled() ) {
			return;
		}

		$settings = Conversion_Agent_Discovery_Settings::get();
		if ( empty( $settings['enable_api_catalog'] ) && empty( $settings['enable_llms'] ) && empty( $settings['enable_agent_skills'] ) ) {
			return;
		}

		if ( ! empty( $settings['enable_api_catalog'] ) ) {
			header( 'Link: <' . self::home_url( '.well-known/api-catalog' ) . '>; rel="api-catalog"; type="application/linkset+json"', false );
		}

		if ( ! empty( $settings['enable_llms'] ) ) {
			header( 'Link: <' . self::home_url( 'llms.txt' ) . '>; rel="describedby"; type="text/plain"', false );
		}

		if ( ! empty( $settings['enable_agent_skills'] ) ) {
			header( 'Link: <' . self::home_url( '.well-known/agent-skills/index.json' ) . '>; rel="describedby"; type="application/json"', false );
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

		if ( ! Conversion_Agent_Discovery_Settings::enabled() ) {
			return $output;
		}

		$settings = Conversion_Agent_Discovery_Settings::get();
		if ( empty( $settings['enable_content_signals'] ) ) {
			return $output;
		}

		$signal_line = 'Content-Signal: ' . Conversion_Agent_Discovery_Settings::content_signal_value( $settings );
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
		$wp_json       = self::rest_url();
		$api_catalog   = self::home_url( '.well-known/api-catalog' );
		$agent_skills  = self::home_url( '.well-known/agent-skills/index.json' );
		$skills_schema = self::home_url( '.well-known/agent-skills/discovery-0.2.schema.json' );
		$sitemap       = self::sitemap_url();
		$search_sample = self::home_url( '?s=marketing' );

		$lines = array(
			'# ' . $name,
			'',
			'> ' . sprintf(
				/* translators: %s: site name. */
				__( 'Agent-readable overview for %s.', 'conversion-agent-discovery' ),
				$name
			),
			'',
			'## ' . __( 'Site', 'conversion-agent-discovery' ),
			'- ' . __( 'Home', 'conversion-agent-discovery' ) . ': ' . $site_url,
			'- ' . __( 'Contact', 'conversion-agent-discovery' ) . ': ' . $contact_url,
			'- ' . __( 'Search example', 'conversion-agent-discovery' ) . ': ' . $search_sample,
			'',
			'## ' . __( 'Agent resources', 'conversion-agent-discovery' ),
			'- ' . __( 'API catalog', 'conversion-agent-discovery' ) . ': ' . $api_catalog,
			'- ' . __( 'Agent skills', 'conversion-agent-discovery' ) . ': ' . $agent_skills,
			'- ' . __( 'Agent skills schema', 'conversion-agent-discovery' ) . ': ' . $skills_schema,
			'- WordPress REST API: ' . $wp_json,
			'- ' . __( 'Sitemap', 'conversion-agent-discovery' ) . ': ' . $sitemap,
			'',
			'## ' . __( 'Content signals', 'conversion-agent-discovery' ),
			'- Content-Signal: ' . Conversion_Agent_Discovery_Settings::content_signal_value( $settings ),
			'',
			'## ' . __( 'Usage', 'conversion-agent-discovery' ),
			'- ' . __( 'Public pages support Markdown negotiation with `Accept: text/markdown` when enabled.', 'conversion-agent-discovery' ),
			'- ' . __( 'Tools and routes are read-only in v1.', 'conversion-agent-discovery' ),
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
						'href'  => self::rest_url(),
						'type'  => 'application/json',
						'title' => __( 'WordPress REST API', 'conversion-agent-discovery' ),
					),
					array(
						'href'  => self::rest_url( 'conversion-agent-discovery/v1/' ),
						'type'  => 'application/json',
						'title' => __( 'Conversion Agent Discovery read-only REST API', 'conversion-agent-discovery' ),
					),
				),
				'service-doc'  => array(
					array(
						'href'  => self::rest_url(),
						'type'  => 'application/json',
						'title' => __( 'REST API index', 'conversion-agent-discovery' ),
					),
					array(
						'href'  => self::rest_url( 'conversion-agent-discovery/v1/context' ),
						'type'  => 'application/json',
						'title' => __( 'Conversion Agent Discovery site context', 'conversion-agent-discovery' ),
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
						'title' => __( 'Agent skills', 'conversion-agent-discovery' ),
					),
					array(
						'href'  => self::home_url( '.well-known/agent-skills/discovery-0.2.schema.json' ),
						'type'  => 'application/schema+json',
						'title' => __( 'Agent skills local schema', 'conversion-agent-discovery' ),
					),
				),
				'item'         => array(
					array(
						'href'  => self::sitemap_url(),
						'type'  => 'application/xml',
						'title' => __( 'Sitemap', 'conversion-agent-discovery' ),
					),
				),
			),
		);

		if ( ! empty( $settings['include_graphql_if_active'] ) && self::graphql_available() ) {
			$linkset[0]['service-desc'][] = array(
				'href'  => self::home_url( 'graphql' ),
				'type'  => 'application/graphql-response+json',
				'title' => __( 'WPGraphQL endpoint', 'conversion-agent-discovery' ),
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
		$name   = self::clean_text( $settings['publisher_name'] ?? self::site_name() );
		$skills = array();

		foreach ( self::agent_skill_definitions( $settings ) as $skill ) {
			$body     = self::agent_skill_markdown( $skill['name'], $settings );
			$skills[] = array(
				'name'        => $skill['name'],
				'id'          => $skill['id'],
				'type'        => 'skill-md',
				'description' => $skill['description'],
				'url'         => self::home_url( '.well-known/agent-skills/' . $skill['name'] . '/SKILL.md' ),
				'digest'      => 'sha256:' . hash( 'sha256', $body ),
				'version'     => CONVERSION_AGENT_DISCOVERY_VERSION,
				'endpoint'    => $skill['endpoint'],
				'read_only'   => true,
			);
		}

		return array(
			'$schema'    => self::home_url( '.well-known/agent-skills/discovery-0.2.schema.json' ),
			'name'        => sprintf(
				/* translators: %s: site name. */
				__( '%s Agent Skills', 'conversion-agent-discovery' ),
				$name
			),
			'version'     => CONVERSION_AGENT_DISCOVERY_VERSION,
			'description' => __( 'Read-only public skills for discovering and reading site content.', 'conversion-agent-discovery' ),
			'publisher'   => array(
				'name' => $name,
				'url'  => self::clean_url( $settings['publisher_url'] ?? self::home_url() ),
			),
			'skills'      => $skills,
		);
	}

	/**
	 * Build a local schema document for the generated Agent Skills index.
	 *
	 * @return array<string,mixed>
	 */
	public static function agent_skills_schema() {
		return array(
			'title'                => __( 'Agent Skills Discovery 0.2', 'conversion-agent-discovery' ),
			'description'          => __( 'Local schema for the Agent Skills discovery document generated by Conversion Agent Discovery.', 'conversion-agent-discovery' ),
			'type'                 => 'object',
			'required'             => array( '$schema', 'name', 'version', 'description', 'publisher', 'skills' ),
			'additionalProperties' => true,
			'properties'           => array(
				'$schema'    => array(
					'type'        => 'string',
					'description' => __( 'Canonical URL for this local schema document.', 'conversion-agent-discovery' ),
				),
				'name'       => array(
					'type' => 'string',
				),
				'version'    => array(
					'type' => 'string',
				),
				'description' => array(
					'type' => 'string',
				),
				'publisher'  => array(
					'type'                 => 'object',
					'required'             => array( 'name', 'url' ),
					'additionalProperties' => true,
					'properties'           => array(
						'name' => array(
							'type' => 'string',
						),
						'url'  => array(
							'type' => 'string',
						),
					),
				),
				'skills'     => array(
					'type'  => 'array',
					'items' => array(
						'type'                 => 'object',
						'required'             => array( 'name', 'type', 'description', 'url', 'digest' ),
						'additionalProperties' => true,
						'properties'           => array(
							'name'        => array(
								'type' => 'string',
							),
							'id'          => array(
								'type' => 'string',
							),
							'type'        => array(
								'type' => 'string',
							),
							'description' => array(
								'type' => 'string',
							),
							'url'         => array(
								'type' => 'string',
							),
							'digest'      => array(
								'type' => 'string',
							),
							'version'     => array(
								'type' => 'string',
							),
							'endpoint'    => array(
								'type' => 'string',
							),
							'read_only'   => array(
								'type' => 'boolean',
							),
						),
					),
				),
			),
		);
	}

	/**
	 * Build one virtual SKILL.md artifact.
	 *
	 * @param string              $skill_name Skill identifier.
	 * @param array<string,mixed> $settings Settings.
	 * @return string
	 */
	public static function agent_skill_markdown( $skill_name, $settings ) {
		$definitions = self::agent_skill_definitions( $settings );
		if ( empty( $definitions[ $skill_name ] ) ) {
			return '';
		}

		$skill = $definitions[ $skill_name ];
		$name  = self::clean_text( $settings['publisher_name'] ?? self::site_name() );

		$lines = array(
			'---',
			'name: ' . $skill['name'],
			'description: "' . self::yaml_escape( $skill['description'] ) . '"',
			'---',
			'',
			'# ' . $skill['title'],
			'',
			sprintf(
				/* translators: %s: site name. */
				__( 'Use this skill when an agent needs to work with public content from %s.', 'conversion-agent-discovery' ),
				$name
			),
			'',
			'## ' . __( 'Behavior', 'conversion-agent-discovery' ),
			'- ' . __( 'This skill is read-only.', 'conversion-agent-discovery' ),
			'- ' . __( 'Use only public, published content.', 'conversion-agent-discovery' ),
			'- ' . __( 'Do not submit forms, authenticate, edit content, or perform transactions.', 'conversion-agent-discovery' ),
			'',
			'## Endpoint',
			'- ' . $skill['endpoint'],
			'',
			'## ' . __( 'Input', 'conversion-agent-discovery' ),
			$skill['input'],
			'',
			'## ' . __( 'Output', 'conversion-agent-discovery' ),
			$skill['output'],
		);

		return implode( "\n", $lines ) . "\n";
	}

	/**
	 * Read-only skill definitions.
	 *
	 * @param array<string,mixed> $settings Settings.
	 * @return array<string,array<string,string>>
	 */
	private static function agent_skill_definitions( $settings ) {
		$contact_url = self::clean_url( $settings['contact_url'] ?? self::home_url( 'contato/' ) );

		return array(
			'read-site-content' => array(
				'name'        => 'read-site-content',
				'id'          => 'read_site_content',
				'title'       => __( 'Read Site Content', 'conversion-agent-discovery' ),
				'description' => __( 'Read public pages and posts from the site as clean Markdown.', 'conversion-agent-discovery' ),
				'endpoint'    => self::rest_url( 'conversion-agent-discovery/v1/content' ),
				'input'       => '- ' . __( 'Provide `id`, `url`, or `slug` for a public post or page.', 'conversion-agent-discovery' ),
				'output'      => '- ' . __( 'Returns title, URL, excerpt, dates, and Markdown content.', 'conversion-agent-discovery' ),
			),
			'search-site'       => array(
				'name'        => 'search-site',
				'id'          => 'search_site',
				'title'       => __( 'Search Site', 'conversion-agent-discovery' ),
				'description' => __( 'Search public WordPress content by query string.', 'conversion-agent-discovery' ),
				'endpoint'    => self::rest_url( 'conversion-agent-discovery/v1/search' ),
				'input'       => '- ' . __( 'Provide `query` and optional `per_page`.', 'conversion-agent-discovery' ),
				'output'      => '- ' . __( 'Returns matching public content summaries.', 'conversion-agent-discovery' ),
			),
			'read-article'      => array(
				'name'        => 'read-article',
				'id'          => 'read_article',
				'title'       => __( 'Read Article', 'conversion-agent-discovery' ),
				'description' => __( 'Read a public article by URL, slug, or WordPress ID.', 'conversion-agent-discovery' ),
				'endpoint'    => self::rest_url( 'conversion-agent-discovery/v1/content' ),
				'input'       => '- ' . __( 'Provide article `id`, `url`, or `slug`.', 'conversion-agent-discovery' ),
				'output'      => '- ' . __( 'Returns article metadata and Markdown body.', 'conversion-agent-discovery' ),
			),
			'list-recent-posts' => array(
				'name'        => 'list-recent-posts',
				'id'          => 'list_recent_posts',
				'title'       => __( 'List Recent Posts', 'conversion-agent-discovery' ),
				'description' => __( 'List recent public posts and pages exposed by the site.', 'conversion-agent-discovery' ),
				'endpoint'    => self::rest_url( 'conversion-agent-discovery/v1/recent' ),
				'input'       => '- ' . __( 'Optionally provide `per_page`, capped at 20.', 'conversion-agent-discovery' ),
				'output'      => '- ' . __( 'Returns recent public content summaries.', 'conversion-agent-discovery' ),
			),
			'contact-conversion' => array(
				'name'        => 'contact-conversion',
				'id'          => 'contact_conversion',
				'title'       => __( 'Contact Conversion', 'conversion-agent-discovery' ),
				'description' => __( 'Open the public contact page without submitting forms automatically.', 'conversion-agent-discovery' ),
				'endpoint'    => $contact_url,
				'input'       => '- ' . __( 'No automated form submission is supported.', 'conversion-agent-discovery' ),
				'output'      => '- ' . __( 'Returns the public contact URL and requires human confirmation for any form action.', 'conversion-agent-discovery' ),
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
		header( 'X-Conversion-Agent-Discovery: 1' );
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
		header( 'X-Conversion-Agent-Discovery: 1' );
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
	 * Resolve REST URL.
	 *
	 * @param string $path Optional REST path.
	 * @return string
	 */
	private static function rest_url( $path = '' ) {
		if ( function_exists( 'rest_url' ) ) {
			return rest_url( $path );
		}

		return 'https://example.com/wp-json/' . ltrim( $path, '/' );
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

		return function_exists( 'sanitize_text_field' ) ? sanitize_text_field( $text ) : self::fallback_strip_tags( $text );
	}

	/**
	 * Strip HTML tags when WordPress is unavailable in isolated tests.
	 *
	 * @param string $text Raw text.
	 * @return string
	 */
	private static function fallback_strip_tags( $text ) {
		$text = preg_replace( '#<(script|style)\b[^>]*>.*?</\1>#is', '', (string) $text );
		$text = preg_replace( '#<[^>]*>#', '', (string) $text );

		return trim( (string) $text );
	}

	/**
	 * Escape a scalar for double-quoted YAML.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	private static function yaml_escape( $text ) {
		return str_replace( array( '\\', '"' ), array( '\\\\', '\"' ), self::clean_text( $text ) );
	}
}
