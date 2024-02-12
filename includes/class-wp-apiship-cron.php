<?php
/**
 * File: class-wp-apiship-cron.php
 *
 * @package WP ApiShip
 * @subpackage Cron
 *
 * @since 1.4.0
 */
namespace WP_ApiShip;

use DateTime;
use Throwable;
use WC_Admin_Settings;
use WC_Order;
use WP_ApiShip\HTTP\WP_ApiShip_HTTP;
use WP_ApiShip\Options\WP_ApiShip_Options;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('WP_ApiShip_Cron')) :

	/**
	 * Cron actions.
	 */
	class WP_ApiShip_Cron
	{
		protected const LOG_ENABLED = false;

		/**
		 * Constructor.
		 */
		public function __construct()
		{
			$this->wp_actions();
			$this->set_params();
		}

		public function providers_callback()
		{
			set_time_limit(0);
			$response = WP_ApiShip_HTTP::get("lists/providers?limit=999");
			if(wp_remote_retrieve_response_code($response) == WP_ApiShip_HTTP::OK) {
				$body = json_decode($response['body']);
				if (empty($body->rows)) {
					return;
				}
				$list = [];
				foreach($body->rows as $row) {
					$list[$row->key] = (array) $row;
				}
				update_option('wp_apiship_providers_list', $list);
			} else {
				$this->log("Неудачный запрос: " . print_r($response, true));
			}
		}

		public function callback($offset = 0)
		{
			set_time_limit(0);

			$now = time();
			$query_date = (new DateTime($this->last_query))->format('Y-m-d\TH:i:s') . $this->timezone;
			
			$this->log("Дата: $query_date");

			update_option('wp_apiship_status_api_query_date', $now);

			$response = WP_ApiShip_HTTP::get("/orders/statuses/history/date/$query_date?offset=$offset");

			if(wp_remote_retrieve_response_code($response) == WP_ApiShip_HTTP::OK) {
				
				$body = json_decode($response['body']);
				$this->log($body);

				if (empty($body->rows)) {
					$this->log('Активных заказов нет');
					return;
				}

				foreach($body->rows as $row) {
					try {
						$this->row_handler($row);
					} catch (Throwable $exception) {
						$this->log($exception);
					}
				}

				$ordersLeft = $body->total - $body->offset;

				if ($ordersLeft > $body->limit) {
					$this->log("Остались необработанные заказы ($ordersLeft шт.), повторяем запрос.");
					$this->callback($body->offset + $body->limit);
				}
			} else {
				$this->log("Неудачный запрос: " . print_r($response, true));
			}
		} 

		protected function wp_actions()
		{
			/**
			 * Set cron schedules.
			 */
			add_filter('cron_schedules', function ($schedules) {
				$schedules['wp_apiship_schedule'] = array(
					'interval' => 600,
					'display'  => 'Once at 10 min'
				);
				$schedules['wp_apiship_schedule_min'] = array(
					'interval' => 60,
					'display'  => 'Once at 1 min'
				);
				$schedules['wp_apiship_schedule_30_sec'] = array(
					'interval' => 30,
					'display'  => 'Once at 30 sec'
				);
				$schedules['wp_apiship_schedule_10_sec'] = array(
					'interval' => 10,
					'display'  => 'Once at 10 sec'
				);
				return $schedules;
			});

			/**
			 * Set cron action.
			 */
			add_action('wp_apiship_cron_hook', function(){
				$this->callback();
			}, 10, 0);

			add_action('wp_apiship_providers_cron_hook', function(){
				$this->providers_callback();
			}, 10, 0);

			/**
			 * Set cron event.
			 */
			if (!wp_next_scheduled('wp_apiship_cron_hook')) {
				wp_schedule_event(time(), 'wp_apiship_schedule', 'wp_apiship_cron_hook');
			}

			if (!wp_next_scheduled('wp_apiship_providers_cron_hook')) {
				wp_schedule_event(time(), 'daily', 'wp_apiship_providers_cron_hook');
			}
		}

		protected function set_params()
		{
			global $wpdb;

			$this->db = $wpdb;
			$this->db_prefix = $wpdb->base_prefix;
			
			$this->integratorOrderKey = Options\WP_ApiShip_Options::INTEGRATOR_ORDER_KEY;
			$this->mapping_options = get_option(
				'wp_apiship_mapping',
				WP_ApiShip_Options::APISHIP_MAPPING_SETTINGS
			);

			$this->last_query = get_option('wp_apiship_status_api_query_date', time() - 3600 * 24);

			$this->timezone = (new DateTime())->format('P');
		}

		protected function row_handler($row)
		{
			$status = $row->statuses[0]->key;
			$orderId = $row->orderInfo->orderId;
			$providerNumber = $row->orderInfo->providerNumber;
			
			if (isset($this->mapping_options[$status]) and boolval($this->mapping_options[$status]['is_active_status']) === true) {
				
				$wp_status = $this->mapping_options[$status]['selected_status'];

				$woocommerce_order_itemmeta = $this->db_prefix . 'woocommerce_order_itemmeta';
				$woocommerce_order_items = $this->db_prefix . 'woocommerce_order_items';

				$orderItemMeta = $this->db->get_row("SELECT $woocommerce_order_itemmeta.order_item_id FROM $woocommerce_order_itemmeta LEFT JOIN $woocommerce_order_items ON $woocommerce_order_itemmeta.order_item_id = $woocommerce_order_items.order_item_id WHERE meta_key = '$this->integratorOrderKey' AND meta_value = $orderId", OBJECT, 0);
				
				$orderId = wc_get_order_id_by_order_item_id($orderItemMeta->order_item_id);
				$order = new WC_Order($orderId);

				if ($order->has_status($wp_status) === false) {
					update_post_meta($order->get_id(), WP_ApiShip_Options::PROVIDER_NUMBER_KEY, $providerNumber);
					$order->update_status($wp_status);
					$this->log("Обновление статуса заказа на $wp_status. orderId" . $order->get_id());
				} else {
					$this->log("Статус заказа уже изменён на $wp_status. orderId" . $order->get_id());
				}
			} else {
				$this->log("Статус $status не требует обновления");
			}
		}

		protected function log($row)
		{
            global $wp_filesystem;

			if (self::LOG_ENABLED === false) {
				return;
			}
			if (!is_scalar($row)) {
				$row = print_r($row, true);
			}

            $content = '';
            if (file_exists(__DIR__ . '/.log')) {
                $content = $wp_filesystem->get_contents(__DIR__ . '/.log');
            }
            $wp_filesystem->put_contents(__DIR__ . '/.log', $content . $row . PHP_EOL);
		}
	}
	
endif;
			
# --- EOF