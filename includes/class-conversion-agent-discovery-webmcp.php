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
