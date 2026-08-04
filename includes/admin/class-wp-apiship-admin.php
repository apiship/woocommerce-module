<?php
/**
 * File: class-wp-apiship-admin.php
 *
 * @package WP ApiShip
 * @subpackage Administration
 *
 * @since 1.0.0
 */
namespace ApiShip\Admin;

use Exception;
use ApiShip\Options,
	ApiShip\HTTP;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists('ApiShip_Admin') ) :

	class ApiShip_Admin {
		
		/**
		 * Log file to store order's labels info.
		 */
		protected $labels_log_file = '';
		
		/**
		 * Constructor.
		 */
		public function __construct( $path_to_loader ) {

			add_filter( 'plugin_action_links_' . plugin_basename($path_to_loader), array(
				$this,
				'filter__plugin_action_links'
			) );

			/**
			 * Add `Print labels` menu item.
			 *
			 * Регистрируются оба варианта хуков — для HPOS и для legacy-хранилища:
			 * активен всегда только один экран, поэтому конфликта нет, а выбор
			 * по is_hpos_enabled() здесь был бы преждевременным (WooCommerce
			 * может быть ещё не полностью инициализирован).
			 *
			 * @see wp-admin\includes\class-wp-list-table.php
			 */
			$bulk_actions_hooks = array(
				'bulk_actions-edit-shop_order',
				'bulk_actions-woocommerce_page_wc-orders',
			);

			foreach ( $bulk_actions_hooks as $hook ) {
				add_filter( $hook, array(
					$this,
					'filter__add_actions'
				), 10999 );
			}

			/**
			 * Handle `Print labels` action.
			 */
			$handle_bulk_actions_hooks = array(
				'handle_bulk_actions-edit-shop_order',
				'handle_bulk_actions-woocommerce_page_wc-orders',
			);

			foreach ( $handle_bulk_actions_hooks as $hook ) {
				add_filter( $hook, array(
					$this,
					'filter__handle_actions'
				), 10999, 3 );
			}
			
			/**
			 * @see wp-admin\admin-header.php
			 */
			add_action( 'admin_notices', array(
				$this,
				'on__bulk_actions_notices'
			) );

			// $ajax_callback = function() {
			// 	$method_id = $_POST['method_id'];
			// 	$order_id = $_POST['order_id'];
			
			// 	// Сохраняем выбранный метод доставки в метаданных заказа
			// 	update_post_meta($order_id, '_custom_shipping_method', $method_id);
			
			// 	// Получите объект метода доставки
			// 	$shipping_method = WC()->shipping->get_shipping_for_package(array('chosen_method' => $method_id));
			
			// 	// Проверяем, существует ли объект метода доставки и у него есть метод calculate_shipping
			// 	if ($shipping_method && method_exists($shipping_method, 'calculate_shipping')) {
			// 		// Рассчитайте стоимость доставки с использованием объекта метода доставки
			// 		$order = wc_get_order($order_id);
			// 		$shipping_cost = $shipping_method->calculate_shipping($order);
			// 	} else {
			// 		// Если метод calculate_shipping не доступен, установите стоимость доставки в ноль или другое значение по умолчанию
			// 		$shipping_cost = 0.00;
			// 	}
			
			// 	echo $shipping_cost;
			// 	wp_die();
			// };
			
			// add_action('wp_ajax_calculate_custom_shipping_cost', $ajax_callback);
			// add_action('wp_ajax_nopriv_calculate_custom_shipping_cost', $ajax_callback);

		}
		
		/**
		 * Output notice.
		 *
		 * @since 1.0.0
		 */
		public function on__bulk_actions_notices()
		{
			if (!isset($_REQUEST['wpapiship_action_data'])) {
				return;
			}

			if (!current_user_can('manage_woocommerce')) {
				return;
			}

			$data = json_decode(base64_decode(sanitize_text_field(wp_unslash($_REQUEST['wpapiship_action_data']))));

			if (!is_object($data)) {
				return;
			}

			if (!empty($data->success)) {
				$class = 'notice-success success';
				$defaultMessage = esc_html__('Запрос успешно обработан.', 'apiship');
				foreach ((array) $data->success as $url) {
					$message = $defaultMessage
						. ' <a target="_blank" href="' . esc_url($url) . '">'
						. esc_html__('Скачать файл', 'apiship') . '</a>';
					self::display_notice($message, $class);
				}
			}

			if (!empty($data->errors)) {
				$class = 'notice-error error';
				foreach ((array) $data->errors as $error) {
					self::display_notice(esc_html($error), $class);
				}
			}
		}

		/**
		 * @param string $message Готовая к выводу разметка (уже экранированная).
		 */
		private static function display_notice($message, $class = 'notice-success success')
		{
			printf(
				'<div id="wpapiship-message" class="notice %1$s"><p>%2$s</p></div>',
				esc_attr($class),
				wp_kses_post($message)
			);
		}
		
		/**
		 * Get integrator orders.
		 *
		 * @since 1.0.0
		 */
		public function get_integrator_orders($ids) {
			
			$integrator_orders = false;
			
			foreach( (array) $ids as $id ) {

				$order = wc_get_order($id);

				if ( ! $order ) {
					continue;
				}

				/**
				 * Сбрасывается на каждой итерации: иначе заказ без строки доставки
				 * получил бы номер заказа интегратора от предыдущего заказа.
				 */
				$shipping_order_item_id = false;

				$line_items_shipping = $order->get_items('shipping');

				foreach( $line_items_shipping as $item_id=>$item ) {
					/**
					 * $item is WC_Order_Item_Shipping Object.
					 */
					$shipping_order_item_id = $item_id;
					break;
				}

				if ( ! $shipping_order_item_id ) {
					continue;
				}

				$integrator_order = wc_get_order_item_meta(
					$shipping_order_item_id,
					Options\ApiShip_Options::INTEGRATOR_ORDER_KEY
				);

				if ( (int) $integrator_order > 0 ) {
					$integrator_orders[$id] = $integrator_order;
				}
				
			}
			
			return $integrator_orders;
		}
		
		/**
		 * @since 1.0.0
		 */	
		public function filter__handle_actions($redirect_to, $doaction, $post_ids) {
			
			if ( Options\ApiShip_Options::PRINT_LABELS_ACTION != $doaction and Options\ApiShip_Options::PRINT_WAYBILLS_ACTION != $doaction ) {
				return $redirect_to;
			}

			if ( empty($post_ids) ) {
				return $redirect_to;
			}

			$integrator_order_ids = $this->get_integrator_orders($post_ids);

			if ( empty($integrator_order_ids) ) {
				return $redirect_to;
			}

			$body = array(
				'orderIds' 	=> $integrator_order_ids,
				'format'	=> 'pdf',
			);
			
			$response = array();
			$response['success'] = 'ok';

			if (Options\ApiShip_Options::PRINT_WAYBILLS_ACTION == $doaction) {
				$endpoint = 'orders/waybills';
			} else {
				$endpoint = 'orders/labels';
			}
			
			$response['response'] = HTTP\ApiShip_HTTP::post(
				$endpoint,
				array(
					'headers' 	=> array( 
						'Content-Type' => 'application/json' 
					),
					'body' 	  => wp_json_encode($body),
					'timeout' => 20,
				)
			);

			$errors = [];
			$success = [];

			if (is_wp_error($response['response'])) {

				$response['success'] = 'error';
				$errors[] = $response['response']->get_error_message();

			} else {

				$body = json_decode(wp_remote_retrieve_body($response['response']));

				if (wp_remote_retrieve_response_code($response['response']) == HTTP\ApiShip_HTTP::OK) {
					if (!empty($body->failedOrders)) {
						foreach($body->failedOrders as $error) {
							$errors[] = esc_html__('Заказ #') . $error->orderId . ': ' . $error->message;
						}
					}
					if (Options\ApiShip_Options::PRINT_WAYBILLS_ACTION == $doaction) {
						foreach((array) ($body->waybillItems ?? []) as $providerWaybills) {
							$success[] = $providerWaybills->file;
						}
					} elseif (!empty($body->url)) {
						$success[] = $body->url;
					}
				} else {
					$response['success'] = 'error';
					$errors[] = !empty($body->message)
						? $body->message
						: esc_html__('Не удалось получить ответ от ApiShip', 'apiship');
					if (!empty($body->errors)) {
						foreach($body->errors as $error) {
							$errors[] = esc_html__('Ошибка валидации. Поле ') . $error->field . ': ' . $error->message;
						}
					}
				}
			}

			$redirect_to = add_query_arg(array(
				'wpapiship_action_data' => base64_encode(wp_json_encode([
					'errors' => $errors,
					'success' => $success
				])),
			), $redirect_to);
			
			return $redirect_to;
		}
		
		/**
		 * Add new actions.
		 *
		 * @since 1.0.0
		 */
		public function filter__add_actions($bulk_actions) {
			$bulk_actions[ Options\ApiShip_Options::PRINT_LABELS_ACTION ] = esc_html__('Печать наклеек','apiship');
			$bulk_actions[ Options\ApiShip_Options::PRINT_WAYBILLS_ACTION ] = esc_html__('Печать акта приема-передачи','apiship');	
			return $bulk_actions;
		}
		
		/**
		 * Add action link to plugins.php page.
		 *
		 * @since 1.0.0
		 */
		public function filter__plugin_action_links($links) {
			
			$url = $this->get_wc_settings_plugin_tab_url();
		
			$settings_link = '<a class="" href="' . $url . '">' . esc_html__( 'Settings' ) . '</a>';
			array_unshift( $links, $settings_link );
			return $links;
		}
		
		/**
		 * Get plugin tab URL.
		 *
		 * @since 1.0.0
		 */
		protected function get_wc_settings_plugin_tab_url() {
			
			$url = add_query_arg(
				array(
					'page' => Options\ApiShip_Options::get_wc_settings_page(),
					'tab' => Options\ApiShip_Options::get_wc_settings_plugin_tab(),
				), 
				admin_url( 'admin.php' )
			);
			
			return $url;
		}

		/**
		 * Write log string to file.
		 *
		 * @since 1.0.0
		 */
		protected function _log( $content ) {
			/**
			 * Output labels to file.
			 */
			error_log( $content, 3, $this->labels_log_file );
		}
		
		/**
		 * Init docs log file.
		 *
		 * @since 1.0.0
		 */
		protected function set_log_file() {

			$this->set_logs_dir();	

			$this->labels_log_file = Options\ApiShip_Options::get_labels_file();
			
			if ( file_exists($this->labels_log_file) ) {
				unlink( $this->labels_log_file );
			}
		}

		/**
		 * Init docs log file.
		 *
		 * Note: 'wp-content' can be set to a different path, so we are using the standard WP method.
		 * @todo Check if the folder exists and file is writeable.
		 *
		 * @since 1.0.0
		 */		
		protected function set_logs_dir() {
			
			$logs_dir = Options\ApiShip_Options::get_plugin_logs_dir();

			wp_mkdir_p( $logs_dir );
			
			// Protect the folder from reading via URL
			if ( ! file_exists( $logs_dir . '/.htaccess' ) ) {
				file_put_contents( $logs_dir . '/.htaccess', 'deny from all' );
			}
			if ( ! file_exists( $logs_dir . '/index.php' ) ) {
				file_put_contents( $logs_dir . '/index.php', '' );
			}			
		}
		
	}
	
endif;
			
# --- EOF