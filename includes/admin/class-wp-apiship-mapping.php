<?php
/**
 * File: class-wp-apiship-mapping.php
 *
 * @package WP ApiShip
 * @subpackage Mapping
 *
 * @since 1.4.0
 */
namespace WP_ApiShip\Admin;

use WC_Admin_Settings;
use WP_ApiShip\Options\WP_ApiShip_Options;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('WP_ApiShip_Mapping')) :

	/**
	 * Status mapping.
	 */
	class WP_ApiShip_Mapping
	{
		/**
		 * Constructor.
		 */
		public function __construct()
		{
			add_action('woocommerce_admin_field_apiship_mapping', [$this, 'output_option']);
			add_filter('woocommerce_admin_settings_sanitize_option_apiship_mapping', [$this, 'sanitize_option'], 10, 3);
		}
				
		/**
		 * Handle output of new settings type.
		 */
		function output_option($value)
		{
			$defaults = WP_ApiShip_Options::APISHIP_MAPPING_SETTINGS;
			$config = WC_Admin_Settings::get_option($value['id']);
			$settings = $defaults;

			if (!empty($config)) {
				foreach($defaults as $statusKey => $statusRow) {
					if (isset($config[$statusKey])) {
						$settings[$statusKey] = wp_parse_args($config[$statusKey], $statusRow);
					}
				}
			}

			$params = [
				'value' => $value,
				'value_id' => $value['id'],
				'statuses' => wc_get_order_statuses(),
				'setting_rows' => $settings,
			];
			extract($params);

			include __DIR__ . '/templates/mapping.php';
		}

		/**
		 * Sanitize data for new settings type.
		 */
		function sanitize_option($value, $option, $raw_value)
		{
			$value = array_filter(array_map('wc_clean', (array) $raw_value));
			return $value;
			// return serialize($value);
		}
	}
	
endif;
			
# --- EOF