<?php
/**
 * Plugin Name: Hoppr
 * Plugin URI: https://cloudnineweb.co
 * Description: Create and manage 301/302 redirects with comprehensive analytics tracking and QR code generation capabilities.
 * Version: 1.0.0
 * Author: Cloud Nine Web
 * Author URI: https://cloudnineweb.co
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hoppr
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.6
 * Requires PHP: 7.4
 * Network: false
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('HOPPR_VERSION')) {
    define('HOPPR_VERSION', '1.0.0');
}

if (!defined('HOPPR_PLUGIN_FILE')) {
    define('HOPPR_PLUGIN_FILE', __FILE__);
}

if (!defined('HOPPR_PLUGIN_DIR')) {
    define('HOPPR_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

if (!defined('HOPPR_PLUGIN_URL')) {
    define('HOPPR_PLUGIN_URL', plugin_dir_url(__FILE__));
}

if (!defined('HOPPR_PLUGIN_BASENAME')) {
    define('HOPPR_PLUGIN_BASENAME', plugin_basename(__FILE__));
}

if (!class_exists('Hoppr')) {
    require_once HOPPR_PLUGIN_DIR . 'includes/class-hoppr.php';
}

function hoppr_init() {
    return Hoppr::get_instance();
}

add_action('plugins_loaded', 'hoppr_init');

register_activation_hook(__FILE__, array('Hoppr', 'activate'));
register_deactivation_hook(__FILE__, array('Hoppr', 'deactivate'));
register_uninstall_hook(__FILE__, array('Hoppr', 'uninstall'));