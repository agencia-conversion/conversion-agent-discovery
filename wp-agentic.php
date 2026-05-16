<?php
/**
 * Plugin Name: WP Agentic
 * Plugin URI: https://www.conversion.com.br/
 * Description: Agent readiness for WordPress: Markdown negotiation, llms.txt, API catalog, agent skills, and AI content signals.
 * Version: 0.1.0
 * Requires at least: 5.9
 * Requires PHP: 7.4
 * Author: Conversion
 * Author URI: https://www.conversion.com.br/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-agentic
 *
 * @package WP_Agentic
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WP_AGENTIC_VERSION', '0.1.0' );
define( 'WP_AGENTIC_FILE', __FILE__ );
define( 'WP_AGENTIC_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP_AGENTIC_URL', plugin_dir_url( __FILE__ ) );

require_once WP_AGENTIC_PATH . 'includes/class-wp-agentic-settings.php';
require_once WP_AGENTIC_PATH . 'includes/class-wp-agentic-markdown.php';
require_once WP_AGENTIC_PATH . 'includes/class-wp-agentic-routes.php';
require_once WP_AGENTIC_PATH . 'admin/class-wp-agentic-admin.php';
require_once WP_AGENTIC_PATH . 'includes/class-wp-agentic.php';

register_activation_hook( __FILE__, array( 'WP_Agentic', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WP_Agentic', 'deactivate' ) );

WP_Agentic::init();
