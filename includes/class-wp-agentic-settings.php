<?php
/**
 * Settings model and sanitization.
 *
 * @package WP_Agentic
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WP_AGENTIC_TESTING' ) ) {
	exit;
}

/**
 * Stores defaults and sanitizes persisted settings.
 */
class WP_Agentic_Settings {
	const OPTION_NAME = 'wp_agentic_settings';

	/**
	 * Default settings.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults() {
		$site_name = self::wp_value( 'get_bloginfo', array( 'name' ), 'WordPress Site' );
		$home_url  = self::wp_value( 'home_url', array( '/' ), 'https://example.com/' );

		return array(
			'enabled'                   => 1,
			'enable_content_signals'    => 1,
			'enable_llms'               => 1,
			'enable_api_catalog'        => 1,
			'enable_agent_skills'       => 1,
			'enable_markdown'           => 1,
			'publisher_name'            => $site_name,
			'publisher_url'             => $home_url,
			'contact_url'               => self::trailingslash( $home_url ) . 'contato/',
			'content_signal_ai_train'   => 'yes',
			'content_signal_search'     => 'yes',
			'content_signal_ai_input'   => 'yes',
			'include_graphql_if_active' => 1,
		);
	}

	/**
	 * Current settings merged with defaults.
	 *
	 * @return array<string,mixed>
	 */
	public static function get() {
		$settings = function_exists( 'get_option' ) ? get_option( self::OPTION_NAME, array() ) : array();

		return wp_parse_args( is_array( $settings ) ? $settings : array(), self::defaults() );
	}

	/**
	 * Is the whole plugin enabled?
	 *
	 * @return bool
	 */
	public static function enabled() {
		$settings = self::get();

		return ! empty( $settings['enabled'] );
	}

	/**
	 * Sanitize settings from wp-admin.
	 *
	 * @param array<string,mixed> $input Raw input.
	 * @return array<string,mixed>
	 */
	public static function sanitize( $input ) {
		$defaults = self::defaults();
		$input    = is_array( $input ) ? $input : array();
		$output   = $defaults;

		$booleans = array(
			'enabled',
			'enable_content_signals',
			'enable_llms',
			'enable_api_catalog',
			'enable_agent_skills',
			'enable_markdown',
			'include_graphql_if_active',
		);

		foreach ( $booleans as $key ) {
			$output[ $key ] = empty( $input[ $key ] ) ? 0 : 1;
		}

		$output['publisher_name'] = self::sanitize_text( $input['publisher_name'] ?? $defaults['publisher_name'] );
		$output['publisher_url']  = self::sanitize_url( $input['publisher_url'] ?? $defaults['publisher_url'] );
		$output['contact_url']    = self::sanitize_url( $input['contact_url'] ?? $defaults['contact_url'] );

		foreach ( array( 'content_signal_ai_train', 'content_signal_search', 'content_signal_ai_input' ) as $key ) {
			$value          = isset( $input[ $key ] ) ? (string) $input[ $key ] : 'yes';
			$output[ $key ] = in_array( $value, array( 'yes', 'no' ), true ) ? $value : 'yes';
		}

		return $output;
	}

	/**
	 * Build the Content-Signal header value.
	 *
	 * @param array<string,mixed>|null $settings Settings override.
	 * @return string
	 */
	public static function content_signal_value( $settings = null ) {
		$settings = is_array( $settings ) ? $settings : self::get();

		return sprintf(
			'ai-train=%s, search=%s, ai-input=%s',
			self::yes_no( $settings['content_signal_ai_train'] ?? 'yes' ),
			self::yes_no( $settings['content_signal_search'] ?? 'yes' ),
			self::yes_no( $settings['content_signal_ai_input'] ?? 'yes' )
		);
	}

	/**
	 * Normalize yes/no values.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	private static function yes_no( $value ) {
		return 'no' === $value ? 'no' : 'yes';
	}

	/**
	 * WordPress-compatible text sanitization with fallback for tests.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	private static function sanitize_text( $value ) {
		$value = (string) $value;

		return function_exists( 'sanitize_text_field' ) ? sanitize_text_field( $value ) : trim( strip_tags( $value ) );
	}

	/**
	 * WordPress-compatible URL sanitization with fallback for tests.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	private static function sanitize_url( $value ) {
		$value = (string) $value;

		return function_exists( 'esc_url_raw' ) ? esc_url_raw( $value ) : filter_var( $value, FILTER_SANITIZE_URL );
	}

	/**
	 * Call a WordPress function when available.
	 *
	 * @param string       $function Function name.
	 * @param array<mixed> $args Arguments.
	 * @param mixed        $fallback Fallback value.
	 * @return mixed
	 */
	private static function wp_value( $function, $args, $fallback ) {
		return function_exists( $function ) ? call_user_func_array( $function, $args ) : $fallback;
	}

	/**
	 * Add a trailing slash.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	private static function trailingslash( $url ) {
		return function_exists( 'trailingslashit' ) ? trailingslashit( $url ) : rtrim( $url, '/' ) . '/';
	}
}
