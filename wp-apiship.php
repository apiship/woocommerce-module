<?php
/**
 *  Plugin Name: WP ApiShip for WooCommerce
 *  Plugin URI: 
 *  Description: The plugin allows you to automatically calculate the shipping cost from various providers.
 *  Version: 1.7.1
 *  Author: 
 *  Author URI: https://apiship.ru/
 *  Text Domain: wp-apiship
 *  Domain Path: /languages
 *  License: GPLv3
 *  WC requires at least: 8.0
 *  WC tested up to: 9.0
 */

namespace WP_ApiShip;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.

define('WP_APISHIP_VERSION', '1.7.1');

if (!defined('WP_APISHIP_SHIPPING_CACHE')) {
	define('WP_APISHIP_SHIPPING_CACHE', false);
}

define('WP_APISHIP_PLUGIN_BASE', plugin_basename(__FILE__));

// Activator settings.
define('WP_APISHIP_ACTIVATOR_LIMIT', 25);
define('WP_APISHIP_ACTIVATOR_WRITE_LOG', false);

add_action('plugins_loaded', function() {
	load_plugin_textdomain( 'wp-apiship', false, dirname( plugin_basename(__FILE__) ) . '/languages' );
});

// Declare compatibility with WooCommerce High-Performance Order Storage (HPOS)
add_action('before_woocommerce_init', function() {
	if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
	}
});

/**
 * Активация/деактивация регистрируются на верхнем уровне файла:
 * хук `activate_{plugin}` срабатывает сразу после подключения файла плагина,
 * когда `plugins_loaded` в этом запросе уже отработал.
 */
require_once __DIR__ . '/includes/class-wp-apiship-activator.php';

register_activation_hook(__FILE__, function(){
	WP_ApiShip_Activator::activate();
});

register_deactivation_hook(__FILE__, function(){
	WP_ApiShip_Activator::deactivate();
});

/**
 * Инициализация выполняется на `plugins_loaded`, а не при подключении файла:
 * порядок загрузки плагинов определяется алфавитным порядком их каталогов,
 * поэтому при слаге, идущем до `woocommerce/` (например `apiship/`), классы
 * WooCommerce на момент подключения этого файла ещё не существуют.
 */
add_action('plugins_loaded', __NAMESPACE__ . '\wp_apiship_bootstrap', 20);

function wp_apiship_bootstrap() {

	if ( ! class_exists('WooCommerce') ) {

		if ( is_admin() ) {
			add_action('admin_notices', function () {
				$message = esc_html__('WP ApiShip needs WooCommerce to run. Please, install and active WooCommerce plugin.', 'wp-apiship');
				printf('<div class="%1$s"><p>%2$s</p></div>', 'notice notice-error', $message);
			});
		}

		return;
	}

	require __DIR__ . '/includes/class-wp-apiship-hpos-compatibility.php';
	require __DIR__ . '/includes/class-wp-apiship-hpos-migration.php';
	require __DIR__ . '/includes/class-wp-apiship-hpos-test.php';

	require __DIR__ . '/includes/class-wp-apiship-options.php';
	Options\WP_ApiShip_Options::get_instance();

	require __DIR__ . '/includes/class-wp-apiship-core.php';
	WP_ApiShip_Core::get_instance( __FILE__ );

	require __DIR__ . '/includes/class-wp-apiship-http.php';
	HTTP\WP_ApiShip_HTTP::get_instance();

	require __DIR__ . '/includes/class-wp-apiship-shipping.php';
	new \WP_ApiShip_Shipping( __FILE__ );

	require __DIR__ . '/includes/class-wp-apiship-cron.php';
	new WP_ApiShip_Cron();

	// Initialize HPOS migration
	WP_ApiShip_HPOS_Migration::init();

	/** Load activator core. */
	new WP_ApiShip_Activator();

	if ( is_admin() ) {
		require __DIR__ . '/includes/admin/class-wp-apiship-admin.php';
		new Admin\WP_ApiShip_Admin( __FILE__ );
		require __DIR__ . '/includes/admin/class-wp-apiship-admin-tab.php';
		new Admin\WP_ApiShip_Admin_Tab();
		require __DIR__ . '/includes/admin/class-wp-apiship-meta-boxes.php';
		new Admin\WP_ApiShip_Meta_Boxes( __FILE__  );
		require __DIR__ . '/includes/admin/class-wp-apiship-mapping.php';
		new Admin\WP_ApiShip_Mapping();
	}
}

# --- EOF