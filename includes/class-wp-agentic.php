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
	/**
	 * Boot the plugin.
	 *
	 * @return void
	 */
	public static function init() {
		WP_Agentic_Routes::init();
		WP_Agentic_Admin::init();
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
	}

	/**
	 * Deactivation routine.
	 *
	 * @return void
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}
