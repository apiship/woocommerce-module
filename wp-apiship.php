<?php
/**
 *  Plugin Name: WP ApiShip for WooCommerce
 *  Plugin URI: 
 *  Description: The plugin allows you to automatically calculate the shipping cost from various providers.
 *  Version: 1.8.0
 *  Author: 
 *  Author URI: https://apiship.ru/
 *  Text Domain: apiship
 *  Domain Path: /languages
 *  License: GPLv3
 *  Requires at least: 6.0
 *  Requires PHP: 7.4
 *  Requires Plugins: woocommerce
 *  WC requires at least: 8.0
 *  WC tested up to: 10.9
 */

namespace ApiShip;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.

define('APISHIP_VERSION', '1.8.0');

if (!defined('APISHIP_SHIPPING_CACHE')) {
	// Константа могла быть определена в wp-config.php под старым именем.
	define('APISHIP_SHIPPING_CACHE', defined('WP_APISHIP_SHIPPING_CACHE') ? WP_APISHIP_SHIPPING_CACHE : false);
}

define('APISHIP_PLUGIN_BASE', plugin_basename(__FILE__));

// Activator settings.
define('APISHIP_ACTIVATOR_LIMIT', 25);
define('APISHIP_ACTIVATOR_WRITE_LOG', false);

/**
 * С WordPress 6.7 переводы должны загружаться не раньше хука `init`,
 * иначе ядро пишет notice `_load_textdomain_just_in_time`.
 */
add_action('init', function() {
	load_plugin_textdomain( 'apiship', false, dirname( plugin_basename(__FILE__) ) . '/languages' );
});

// Declare compatibility with WooCommerce features.
add_action('before_woocommerce_init', function() {
	if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
		// High-Performance Order Storage (HPOS).
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
		/**
		 * Блочные корзина и чекаут не поддерживаются: выбор ПВЗ и запись меты
		 * заказа работают через хуки классического чекаута
		 * (`woocommerce_after_shipping_rate`, `woocommerce_checkout_update_order_meta`).
		 * Честно объявляем несовместимость, чтобы WooCommerce предупредил
		 * владельца магазина — на странице чекаута нужен шорткод [woocommerce_checkout].
		 */
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, false);
	}
});

/**
 * Активация/деактивация регистрируются на верхнем уровне файла:
 * хук `activate_{plugin}` срабатывает сразу после подключения файла плагина,
 * когда `plugins_loaded` в этом запросе уже отработал.
 */
require_once __DIR__ . '/includes/class-wp-apiship-activator.php';

register_activation_hook(__FILE__, function(){
	ApiShip_Activator::activate();
});

register_deactivation_hook(__FILE__, function(){
	ApiShip_Activator::deactivate();
});

/**
 * Инициализация выполняется на `plugins_loaded`, а не при подключении файла:
 * порядок загрузки плагинов определяется алфавитным порядком их каталогов,
 * поэтому при слаге, идущем до `woocommerce/` (например `apiship/`), классы
 * WooCommerce на момент подключения этого файла ещё не существуют.
 */
add_action('plugins_loaded', __NAMESPACE__ . '\apiship_bootstrap', 20);

function apiship_bootstrap() {

	if ( ! class_exists('WooCommerce') ) {

		if ( is_admin() ) {
			add_action('admin_notices', function () {
				printf(
					'<div class="notice notice-error"><p>%s</p></div>',
					esc_html__('WP ApiShip needs WooCommerce to run. Please, install and active WooCommerce plugin.', 'apiship')
				);
			});
		}

		return;
	}

	require __DIR__ . '/includes/class-wp-apiship-hpos-compatibility.php';
	require __DIR__ . '/includes/class-wp-apiship-hpos-migration.php';
	require __DIR__ . '/includes/class-wp-apiship-hpos-test.php';

	require __DIR__ . '/includes/class-wp-apiship-options.php';
	Options\ApiShip_Options::get_instance();

	require __DIR__ . '/includes/class-wp-apiship-core.php';
	ApiShip_Core::get_instance( __FILE__ );

	require __DIR__ . '/includes/class-wp-apiship-http.php';
	HTTP\ApiShip_HTTP::get_instance();

	require __DIR__ . '/includes/class-wp-apiship-shipping.php';
	new \ApiShip_Shipping( __FILE__ );

	require __DIR__ . '/includes/class-wp-apiship-cron.php';
	new ApiShip_Cron();

	// Initialize HPOS migration
	ApiShip_HPOS_Migration::init();

	/** Load activator core. */
	new ApiShip_Activator();

	if ( is_admin() ) {
		require __DIR__ . '/includes/admin/class-wp-apiship-admin.php';
		new Admin\ApiShip_Admin( __FILE__ );
		require __DIR__ . '/includes/admin/class-wp-apiship-admin-tab.php';
		new Admin\ApiShip_Admin_Tab();
		require __DIR__ . '/includes/admin/class-wp-apiship-meta-boxes.php';
		new Admin\ApiShip_Meta_Boxes( __FILE__  );
		require __DIR__ . '/includes/admin/class-wp-apiship-mapping.php';
		new Admin\ApiShip_Mapping();
	}
}

# --- EOF