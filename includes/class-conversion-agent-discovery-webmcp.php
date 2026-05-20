<?php
/**
 * Browser-side WebMCP tool registration.
 *
 * @package Conversion_Agent_Discovery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers read-only tools for browsers that expose navigator.modelContext.
 */
class Conversion_Agent_Discovery_WebMCP {
	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_script' ) );
	}

	/**
	 * Enqueue the WebMCP registration script on public HTML pages.
	 *
	 * @return void
	 */
	public static function enqueue_script() {
		if ( ! Conversion_Agent_Discovery_Settings::enabled() || is_admin() || is_feed() || is_robots() || is_embed() ) {
			return;
		}

		$settings = Conversion_Agent_Discovery_Settings::get();
		if ( empty( $settings['enable_webmcp'] ) ) {
			return;
		}

		$config = array(
			'endpoints' => array(
				'search'  => esc_url_raw( rest_url( 'conversion-agent-discovery/v1/search' ) ),
				'content' => esc_url_raw( rest_url( 'conversion-agent-discovery/v1/content' ) ),
				'recent'  => esc_url_raw( rest_url( 'conversion-agent-discovery/v1/recent' ) ),
				'context' => esc_url_raw( rest_url( 'conversion-agent-discovery/v1/context' ) ),
				'contact' => esc_url_raw( rest_url( 'conversion-agent-discovery/v1/contact' ) ),
			),
			'messages'  => array(
				'request_failed' => __( 'Request failed', 'conversion-agent-discovery' ),
			),
			'tools'     => array(
				array(
					'name'        => 'search_posts',
					'description' => __( 'Search public posts and pages on this WordPress site.', 'conversion-agent-discovery' ),
					'inputSchema' => array(
						'type'       => 'object',
						'properties' => array(
							'query'    => array(
								'type'        => 'string',
								'description' => __( 'Search query.', 'conversion-agent-discovery' ),
							),
							'per_page' => array(
								'type'        => 'integer',
								'minimum'     => 1,
								'maximum'     => 20,
								'description' => __( 'Maximum results to return.', 'conversion-agent-discovery' ),
							),
						),
						'required'   => array( 'query' ),
					),
					'endpoint'    => 'search',
				),
				array(
					'name'        => 'read_post',
					'description' => __( 'Read a public post or page by id, URL, or slug.', 'conversion-agent-discovery' ),
					'inputSchema' => array(
						'type'       => 'object',
						'properties' => array(
							'id'   => array(
								'type'        => 'integer',
								'description' => __( 'WordPress post ID.', 'conversion-agent-discovery' ),
							),
							'url'  => array(
								'type'        => 'string',
								'format'      => 'uri',
								'description' => __( 'Canonical public URL.', 'conversion-agent-discovery' ),
							),
							'slug' => array(
								'type'        => 'string',
								'description' => __( 'Post or page slug.', 'conversion-agent-discovery' ),
							),
							'type' => array(
								'type'        => 'string',
								'description' => __( 'Optional post type, usually post or page.', 'conversion-agent-discovery' ),
							),
						),
					),
					'endpoint'    => 'content',
				),
				array(
					'name'        => 'list_recent_posts',
					'description' => __( 'List recent public posts and pages from this WordPress site.', 'conversion-agent-discovery' ),
					'inputSchema' => array(
						'type'       => 'object',
						'properties' => array(
							'per_page' => array(
								'type'        => 'integer',
								'minimum'     => 1,
								'maximum'     => 20,
								'description' => __( 'Maximum results to return.', 'conversion-agent-discovery' ),
							),
						),
					),
					'endpoint'    => 'recent',
				),
				array(
					'name'        => 'get_site_context',
					'description' => __( 'Get public Conversion Agent Discovery context, discovery URLs, and content policy for this site.', 'conversion-agent-discovery' ),
					'inputSchema' => array(
						'type'       => 'object',
						'properties' => array(),
					),
					'endpoint'    => 'context',
				),
				array(
					'name'        => 'contact_conversion',
					'description' => __( 'Get the public contact URL. This tool does not submit forms.', 'conversion-agent-discovery' ),
					'inputSchema' => array(
						'type'       => 'object',
						'properties' => array(),
					),
					'endpoint'    => 'contact',
				),
			),
		);

		wp_enqueue_script(
			'conversion-agent-discovery-webmcp',
			CONVERSION_AGENT_DISCOVERY_URL . 'assets/webmcp.js',
			array(),
			CONVERSION_AGENT_DISCOVERY_VERSION,
			true
		);

		wp_add_inline_script(
			'conversion-agent-discovery-webmcp',
			'window.ConversionAgentDiscoveryWebMCP = ' . wp_json_encode( $config, JSON_UNESCAPED_SLASHES ) . ';',
			'before'
		);
	}
}
