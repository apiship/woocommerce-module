<?php
/**
 *  Plugin Name: WP ApiShip for WooCommerce
 *  Plugin URI: 
 *  Description: The plugin allows you to automatically calculate the shipping cost from various providers.
 *  Version: 1.5.0-dev
 *  Author: 
 *  Author URI: https://apiship.ru/
 *  Text Domain: wp-apiship
 *  Domain Path: /languages
 *  License: GPLv3
 *  WC requires at least: 6.3
 *  WC tested up to: 6.5
 */

namespace WP_ApiShip;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define('WP_APISHIP_VERSION', '1.5.0-dev');
define('WP_APISHIP_SHIPPING_CACHE', false);
define('WP_APISHIP_PLUGIN_BASE', plugin_basename(__FILE__));
define('WP_APISHIP_ACTIVATOR_LIMIT', 50);
define('WP_APISHIP_ACTIVATOR_WRITE_LOG', false);

add_action('plugins_loaded', function() {
	load_plugin_textdomain( 'wp-apiship', false, dirname( plugin_basename(__FILE__) ) . '/languages' );
});

if ( ! function_exists('is_plugin_active' ) ) {
	include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

if ( is_plugin_active('woocommerce/woocommerce.php') ) {

	require 'includes/class-wp-apiship-options.php';
	Options\WP_ApiShip_Options::get_instance();
	
	require 'includes/class-wp-apiship-core.php';
	WP_ApiShip_Core::get_instance( __FILE__ );

	require 'includes/class-wp-apiship-http.php';
	HTTP\WP_ApiShip_HTTP::get_instance();
	
	require 'includes/class-wp-apiship-shipping.php';
	new \WP_ApiShip_Shipping( __FILE__ );
	
	require 'includes/class-wp-apiship-cron.php';
	new WP_ApiShip_Cron();

	/** Include activator core. */
	require_once __DIR__ . '/includes/class-wp-apiship-activator.php';
	
	/** Activation actions. */
	(function(){
		/** Load activator core */
		new WP_ApiShip_Activator();
			
		/** Register activation hook */
		register_activation_hook(__FILE__, function(){
			WP_ApiShip_Activator::activate();
		});
	
		/** Register deactivation hook */
		register_deactivation_hook(__FILE__, function(){
			WP_ApiShip_Activator::deactivate();
		});
	})();

	if ( is_admin() ) {
		require 'includes/admin/class-wp-apiship-admin.php';
		new Admin\WP_ApiShip_Admin( __FILE__ );
		require 'includes/admin/class-wp-apiship-admin-tab.php';
		new Admin\WP_ApiShip_Admin_Tab();
		require 'includes/admin/class-wp-apiship-meta-boxes.php';
		new Admin\WP_ApiShip_Meta_Boxes( __FILE__  );
		require 'includes/admin/class-wp-apiship-mapping.php';
		new Admin\WP_ApiShip_Mapping();
	}
	
} elseif ( is_admin() ) {

	add_action('admin_notices', function () {
		$message = esc_html__('WP ApiShip needs WooCommerce to run. Please, install and active WooCommerce plugin.', 'wp-apiship');
		printf('<div class="%1$s"><p>%2$s</p></div>', 'notice notice-error', $message);
	});
  
}

# --- EOF