<?php
/**
 * Main plugin bootstrap.
 *
 * @package WP_Agentic
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coordinates public routes, Markdown negotiation, and admin UI.
 */
class WP_Agentic {
	const VERSION_OPTION = 'wp_agentic_version';

	/**
	 * Boot the plugin.
	 *
	 * @return void
	 */
	public static function init() {
		WP_Agentic_Routes::init();
		WP_Agentic_REST::init();
		WP_Agentic_WebMCP::init();
		WP_Agentic_Admin::init();
		add_action( 'init', array( __CLASS__, 'maybe_upgrade' ), 20 );
		add_action( 'upgrader_process_complete', array( __CLASS__, 'maybe_flush_after_plugin_update' ), 10, 2 );
	}

	/**
	 * Activation routine.
	 *
	 * @return void
	 */
	public static function activate() {
		if ( false === get_option( WP_Agentic_Settings::OPTION_NAME, false ) ) {
			add_option( WP_Agentic_Settings::OPTION_NAME, WP_Agentic_Settings::defaults() );
		}

		WP_Agentic_Routes::add_rewrite_rules();
		flush_rewrite_rules();
		update_option( self::VERSION_OPTION, WP_AGENTIC_VERSION );
	}

	/**
	 * Deactivation routine.
	 *
	 * @return void
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Run lightweight upgrade tasks after plugin updates.
	 *
	 * @return void
	 */
	public static function maybe_upgrade() {
		$stored_version = get_option( self::VERSION_OPTION, '' );
		if ( WP_AGENTIC_VERSION === $stored_version ) {
			return;
		}

		WP_Agentic_Routes::add_rewrite_rules();
		flush_rewrite_rules();
		update_option( self::VERSION_OPTION, WP_AGENTIC_VERSION );
	}

	/**
	 * Flush rewrites after WordPress updates this plugin through the upgrader.
	 *
	 * @param WP_Upgrader $upgrader Upgrader instance.
	 * @param array       $options Upgrade options.
	 * @return void
	 */
	public static function maybe_flush_after_plugin_update( $upgrader, $options ) {
		unset( $upgrader );

		if ( empty( $options['type'] ) || 'plugin' !== $options['type'] ) {
			return;
		}

		$plugins = isset( $options['plugins'] ) && is_array( $options['plugins'] ) ? $options['plugins'] : array();
		if ( ! empty( $options['plugin'] ) ) {
			$plugins[] = $options['plugin'];
		}

		if ( ! in_array( plugin_basename( WP_AGENTIC_FILE ), $plugins, true ) ) {
			return;
		}

		WP_Agentic_Routes::add_rewrite_rules();
		flush_rewrite_rules();
		update_option( self::VERSION_OPTION, WP_AGENTIC_VERSION );
	}
}
