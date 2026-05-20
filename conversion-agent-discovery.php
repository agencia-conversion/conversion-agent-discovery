<?php
/**
 * Plugin Name: Conversion Agent Discovery
 * Plugin URI: https://github.com/agencia-conversion/conversion-agent-discovery
 * Description: Read-only agent discovery surfaces for WordPress: Markdown negotiation, llms.txt, API catalog, agent skills, WebMCP, and content signals.
 * Version: 0.1.10
 * Requires at least: 5.9
 * Requires PHP: 7.4
 * Author: Conversion
 * Author URI: https://conversion.ag/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: conversion-agent-discovery
 * Domain Path: /languages
 *
 * @package Conversion_Agent_Discovery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CONVERSION_AGENT_DISCOVERY_VERSION', '0.1.10' );
define( 'CONVERSION_AGENT_DISCOVERY_FILE', __FILE__ );
define( 'CONVERSION_AGENT_DISCOVERY_PATH', plugin_dir_path( __FILE__ ) );
define( 'CONVERSION_AGENT_DISCOVERY_URL', plugin_dir_url( __FILE__ ) );

require_once CONVERSION_AGENT_DISCOVERY_PATH . 'includes/class-conversion-agent-discovery-settings.php';
require_once CONVERSION_AGENT_DISCOVERY_PATH . 'includes/class-conversion-agent-discovery-markdown.php';
require_once CONVERSION_AGENT_DISCOVERY_PATH . 'includes/class-conversion-agent-discovery-rest.php';
require_once CONVERSION_AGENT_DISCOVERY_PATH . 'includes/class-conversion-agent-discovery-routes.php';
require_once CONVERSION_AGENT_DISCOVERY_PATH . 'includes/class-conversion-agent-discovery-webmcp.php';
require_once CONVERSION_AGENT_DISCOVERY_PATH . 'admin/class-conversion-agent-discovery-admin.php';
require_once CONVERSION_AGENT_DISCOVERY_PATH . 'includes/class-conversion-agent-discovery.php';

register_activation_hook( __FILE__, array( 'Conversion_Agent_Discovery', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Conversion_Agent_Discovery', 'deactivate' ) );

Conversion_Agent_Discovery::init();
