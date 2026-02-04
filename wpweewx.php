<?php
/**
 * Plugin Name: WPWeeWX
 * Plugin URI:  https://github.com/open-astro/WPWeeWX
 * Description: Display WeeWX JSON weather data with a shortcode.
 * Version:     0.1.1
 * Author:      OpenAstro
 * Text Domain: wpweewx
 * Domain Path: /languages
 *
 * @package WPWeeWX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPWEEWX_VERSION', '0.1.1' );
define( 'WPWEEWX_PLUGIN_FILE', __FILE__ );
define( 'WPWEEWX_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPWEEWX_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once WPWEEWX_PLUGIN_DIR . 'includes/class-wpweewx-settings.php';
require_once WPWEEWX_PLUGIN_DIR . 'includes/class-wpweewx-fetcher.php';
require_once WPWEEWX_PLUGIN_DIR . 'includes/class-wpweewx-parser.php';
require_once WPWEEWX_PLUGIN_DIR . 'includes/class-wpweewx-renderer.php';
require_once WPWEEWX_PLUGIN_DIR . 'includes/class-wpweewx-shortcode.php';
require_once WPWEEWX_PLUGIN_DIR . 'includes/class-wpweewx-admin.php';
require_once WPWEEWX_PLUGIN_DIR . 'includes/class-wpweewx-plugin.php';

WPWeeWX_Plugin::init();
