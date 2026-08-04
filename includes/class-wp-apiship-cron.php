<?php
/**
 * File: class-wp-apiship-cron.php
 *
 * @package WP ApiShip
 * @subpackage Cron
 *
 * @since 1.4.0
 */
namespace ApiShip;

use DateTime;
use Throwable;
use WC_Admin_Settings;
use WC_Order;
use ApiShip\HTTP\ApiShip_HTTP;
use ApiShip\Options\ApiShip_Options;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('ApiShip_Cron')) :

	/**
	 * Cron actions.
	 */
	class ApiShip_Cron
	{
		protected const LOG_ENABLED = false;

		/**
		 * Предел смещения пагинации на стороне API.
		 */
		protected const MAX_STATUS_OFFSET = 5000;

		protected $db;
		protected $db_prefix;
		protected $integratorOrderKey;
		protected $mapping_options;
		protected $last_query;
		protected $timezone;

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
			/**
			 * Ограничение задаётся только внутри этой функции (гайдлайн WP.org
			 * запрещает глобальные безлимитные значения): HTTP-таймаут запроса — 20 сек,
			 * на хостингах с max_execution_time=30 медленный ответ API приводил бы
			 * к фаталу до update_option().
			 */
			set_time_limit(120);

			$response = ApiShip_HTTP::get("lists/providers?limit=999");
			if(wp_remote_retrieve_response_code($response) == ApiShip_HTTP::OK) {
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
			/**
			 * Ограничение задаётся только внутри этой функции (гайдлайн WP.org
			 * запрещает глобальные безлимитные значения): синхронизация статусов
			 * постранично обходит ответ API и может не уложиться в стандартные 30 сек.
			 */
			set_time_limit(300);

			$now = time();
			$query_date = gmdate('Y-m-d\TH:i:s', $this->last_query) . $this->timezone;

			$this->log("Дата: $query_date");

			update_option('wp_apiship_status_api_query_date', $now);

			/**
			 * Дата кодируется: без этого '+' из смещения таймзоны на стороне API
			 * превращается в пробел при urldecode() и смещение теряется.
			 */
			$date_param = rawurlencode($query_date);

			do {
				$response = ApiShip_HTTP::get("/orders/statuses/history/date/$date_param?offset=$offset");

				if (wp_remote_retrieve_response_code($response) != ApiShip_HTTP::OK) {
					$this->log("Неудачный запрос: " . print_r($response, true));
					return;
				}

				$body = json_decode(wp_remote_retrieve_body($response));
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

				/**
				 * Пагинация приходит в объекте `meta`; у старых версий API поля
				 * лежали на верхнем уровне ответа.
				 */
				$meta = isset($body->meta) ? $body->meta : $body;

				if (!isset($meta->total, $meta->offset, $meta->limit) || $meta->limit < 1) {
					return;
				}

				$offset = $meta->offset + $meta->limit;
				$ordersLeft = $meta->total - $offset;

				if ($ordersLeft > 0) {
					$this->log("Остались необработанные заказы ($ordersLeft шт.), повторяем запрос.");
				}

			} while ($ordersLeft > 0 && $offset < self::MAX_STATUS_OFFSET);
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

		/**
		 * Опция wp_apiship_status_api_query_date могла быть записана старой версией
		 * плагина в виде строки даты, а новыми версиями — как unix timestamp.
		 */
		protected static function normalize_timestamp($value)
		{
			if (is_numeric($value)) {
				return (int) $value;
			}
			if (is_string($value) && $value !== '') {
				$parsed = strtotime($value);
				if ($parsed !== false) {
					return $parsed;
				}
			}
			return time() - 3600 * 24;
		}

		protected function set_params()
		{
			global $wpdb;

			$this->db = $wpdb;
			$this->db_prefix = $wpdb->base_prefix;
			
			$this->integratorOrderKey = Options\ApiShip_Options::INTEGRATOR_ORDER_KEY;
			$this->mapping_options = WC_Admin_Settings::get_option(
				'wp_apiship_mapping',
				ApiShip_Options::APISHIP_MAPPING_SETTINGS
			);

			$this->last_query = self::normalize_timestamp(
				get_option('wp_apiship_status_api_query_date', false)
			);

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

				$orderItemMeta = $this->db->get_row(
					$this->db->prepare(
						"SELECT im.order_item_id FROM {$woocommerce_order_itemmeta} im"
						. " LEFT JOIN {$woocommerce_order_items} i ON im.order_item_id = i.order_item_id"
						. " WHERE im.meta_key = %s AND im.meta_value = %d",
						$this->integratorOrderKey,
						(int) $orderId
					),
					OBJECT,
					0
				);

				if (empty($orderItemMeta->order_item_id)) {
					$this->log("Заказ интегратора $orderId не найден в этом магазине");
					return;
				}

				$orderId = wc_get_order_id_by_order_item_id($orderItemMeta->order_item_id);
				$order = wc_get_order($orderId);

				if (!$order) {
					$this->log("Заказ WooCommerce $orderId не найден");
					return;
				}

				if ($order->has_status($wp_status) === false) {
					Options\ApiShip_Options::update_order_meta($order->get_id(), ApiShip_Options::PROVIDER_NUMBER_KEY, $providerNumber);
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
			if (self::LOG_ENABLED === false) {
				return;
			}
			if (!is_scalar($row)) {
				$row = print_r($row, true);
			}
			/**
			 * Логи пишутся через WC_Logger (wp-content/uploads/wc-logs, закрыт
			 * от прямого доступа), а не в каталог плагина внутри веб-корня.
			 */
			if (function_exists('wc_get_logger')) {
				wc_get_logger()->debug($row, array('source' => 'wp-apiship-cron'));
				return;
			}

			error_log('[wp-apiship] ' . $row);
		}
	}
	
endif;
			
# --- EOF