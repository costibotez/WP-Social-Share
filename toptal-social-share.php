<?php
declare(strict_types=1);
/*
 * Plugin Name: TopTal Social Share
 * Description: Add various social networking share buttons to your website, including; Facebook, Twitter, Pinterest, LinkedIn and WhatsApp(mobile).
 * Author: Botez Costin
 * Version: 1.1.0
 * Requires PHP: 7.4
 * Author URI: https://nomad-developer.co.uk/
 * Text Domain: toptal-ss
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

define( 'TOPTAL_SS_VERSION', '1.1.0' );
define( 'TOPTAL_SS_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );
define( 'TOPTAL_SS_PLUGIN_PATH', plugin_basename( __FILE__ ) );
define( 'TOPTAL_SS_PLUGIN_DIR_ASSETS_URL', plugin_dir_url( __FILE__ ) . 'assets/' );

require_once __DIR__ . '/includes/class-toptal-ss-networks.php';
require_once __DIR__ . '/includes/class-toptal-ss-options.php';
require_once __DIR__ . '/includes/class-toptal-ss-assets.php';
require_once __DIR__ . '/includes/class-toptal-ss-settings.php';
require_once __DIR__ . '/includes/class-toptal-ss-renderer.php';
require_once __DIR__ . '/includes/class-toptal-ss-ajax.php';

register_activation_hook( __FILE__, 'toptal_ss_activation' );
function toptal_ss_activation(): void {
	if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			sprintf(
				/* translators: %s: PHP version */
				__( 'This plugin requires PHP 7.4 or higher. You are running version %s.', 'toptal-ss' ),
				PHP_VERSION
			),
			__( 'Plugin Activation Error', 'toptal-ss' ),
			array( 'back_link' => true )
		);
	}

	TopTal_SS_Options::install_defaults();
}

add_action( 'init', 'toptal_ss_load_textdomain' );
function toptal_ss_load_textdomain(): void {
	load_plugin_textdomain( 'toptal-ss', false, dirname( TOPTAL_SS_PLUGIN_PATH ) . '/languages' );
}

new TopTal_SS_Assets();
new TopTal_SS_Settings();
new TopTal_SS_Renderer();
new TopTal_SS_Ajax();
