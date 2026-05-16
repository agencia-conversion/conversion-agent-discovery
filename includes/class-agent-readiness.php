<?php
/**
 * Main plugin bootstrap.
 *
 * @package Agent_Readiness
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coordinates public routes, Markdown negotiation, and admin UI.
 */
class Agent_Readiness {
	const VERSION_OPTION = 'wp_agentic_version';
	const FLUSH_OPTION   = 'agent_readiness_needs_rewrite_flush';

	/**
	 * Boot the plugin.
	 *
	 * @return void
	 */
	public static function init() {
		Agent_Readiness_Routes::init();
		Agent_Readiness_REST::init();
		Agent_Readiness_WebMCP::init();
		Agent_Readiness_Admin::init();
		add_action( 'init', array( __CLASS__, 'maybe_upgrade' ), 20 );
		add_action( 'admin_init', array( __CLASS__, 'maybe_flush_rewrite_rules' ) );
		add_action( 'upgrader_process_complete', array( __CLASS__, 'maybe_flush_after_plugin_update' ), 10, 2 );
	}

	/**
	 * Activation routine.
	 *
	 * @return void
	 */
	public static function activate() {
		if ( false === get_option( Agent_Readiness_Settings::OPTION_NAME, false ) ) {
			add_option( Agent_Readiness_Settings::OPTION_NAME, Agent_Readiness_Settings::defaults() );
		}

		Agent_Readiness_Routes::add_rewrite_rules();
		flush_rewrite_rules();
		update_option( self::VERSION_OPTION, AGENT_READINESS_VERSION );
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
		if ( AGENT_READINESS_VERSION === $stored_version ) {
			return;
		}

		update_option( self::VERSION_OPTION, AGENT_READINESS_VERSION );
		update_option( self::FLUSH_OPTION, '1' );
	}

	/**
	 * Flush rewrite rules after all init callbacks have registered their rules.
	 *
	 * @return void
	 */
	public static function maybe_flush_rewrite_rules() {
		if ( '1' !== get_option( self::FLUSH_OPTION, '' ) ) {
			return;
		}

		Agent_Readiness_Routes::add_rewrite_rules();
		flush_rewrite_rules( false );
		delete_option( self::FLUSH_OPTION );
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

		if ( ! in_array( plugin_basename( AGENT_READINESS_FILE ), $plugins, true ) ) {
			return;
		}

		update_option( self::VERSION_OPTION, AGENT_READINESS_VERSION );
		update_option( self::FLUSH_OPTION, '1' );
	}
}
