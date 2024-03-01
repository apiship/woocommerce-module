<?php
/**
 * File: class-wp-apiship-admin.php
 *
 * @package WP ApiShip
 * @subpackage Administration
 *
 * @since 1.0.0
 */
namespace WP_ApiShip\Admin;

use Exception;
use WP_ApiShip\Options,
	WP_ApiShip\HTTP;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists('WP_ApiShip_Admin') ) :

	class WP_ApiShip_Admin {
		
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
			 * @see wp-admin\includes\class-wp-list-table.php
			 */
			add_filter( 'bulk_actions-edit-' . Options\WP_ApiShip_Options::WC_ORDER_POST_TYPE, array(
				$this,
				'filter__add_actions'
			), 10999 );

			/**
			 * Handle `Print labels` action.
			 */
			add_filter( 'handle_bulk_actions-edit-' . Options\WP_ApiShip_Options::WC_ORDER_POST_TYPE, array(
				$this,
				'filter__handle_actions'
			), 10999, 3 );			
			
			/**
			 * @see wp-admin\admin-header.php
			 */
			add_action( 'admin_notices', array(
				$this,
				'on__bulk_actions_notices'
			) );
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

			if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'ajax-nonce' ) ) {
				die;
			}
			
			$data = json_decode($_REQUEST['wpapiship_action_data']);

			if (!empty($data->success)) {
				$url = $data->url;
				$class = 'notice-success success';
				$defaultMessage = esc_html__('Запрос успешно обработан.', 'apiship');
				foreach ($data->success as $url) {
					$message = $defaultMessage . ' <a target="_blank" href="' . $url . '">' . esc_html__('Скачать файл', 'apiship') . '</a>';
					self::display_notice($message, $class);
				}
			}

			if (!empty($data->errors)) {
				$class = 'notice-error error';
				foreach ($data->errors as $error) {
					self::display_notice($error, $class);
				}
			}
		}

		private static function display_notice($message, $class = 'notice-success success')
		{
			printf( 
				'<div id="wpapiship-message" class="notice ' . $class . '"><p>' . $message . '</p></div>'
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
			
				$line_items_shipping = $order->get_items('shipping');
				
				foreach( $line_items_shipping as $item_id=>$item ) {
					/**
					 * $item is WC_Order_Item_Shipping Object.
					 */
					$shipping_order_item_id = $item_id;
					break;
				}

				$integrator_order = wc_get_order_item_meta( 
					$shipping_order_item_id, 
					Options\WP_ApiShip_Options::INTEGRATOR_ORDER_KEY
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
			
			if ( Options\WP_ApiShip_Options::PRINT_LABELS_ACTION != $doaction and Options\WP_ApiShip_Options::PRINT_WAYBILLS_ACTION != $doaction ) {
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

			if (Options\WP_ApiShip_Options::PRINT_WAYBILLS_ACTION == $doaction) {
				$endpoint = 'orders/waybills';
			} else {
				$endpoint = 'orders/labels';
			}
			
			$response['response'] = HTTP\WP_ApiShip_HTTP::post(
				$endpoint,
				array(
					'headers' 	=> array( 
						'Content-Type' => 'application/json' 
					),
					'body' 	  => wp_json_encode($body),
					'timeout' => 20000,
				)
			);

			$body = json_decode(wp_remote_retrieve_body($response['response']));
			$errors = [];
			$success = [];

			if (wp_remote_retrieve_response_code($response['response']) == HTTP\WP_ApiShip_HTTP::OK) {
				if (!empty($body->failedOrders)) {
					foreach($body->failedOrders as $error) {
						$errors[] = esc_html__('Заказ #') . $error->orderId . ': ' . $error->message;
					}
				}
				if (Options\WP_ApiShip_Options::PRINT_WAYBILLS_ACTION == $doaction) {
					foreach($body->waybillItems as $providerWaybills) {
						$success[] = $providerWaybills->file;
					}
				} else {
					$success[] = $body->url;
				}
			} else {
				$response['success'] = 'error';
				$errors[] = $body->message;	
				if (!empty($body->errors)) {
					foreach($body->errors as $error) {
						$errors[] = esc_html__('Ошибка валидации. Поле ') . $error->field . ': ' . $error->message;
					}
				}		
			}

			$redirect_to = add_query_arg(array(
				'wpapiship_action_data' => wp_json_encode([
					'errors' => $errors,
					'success' => $success
				]),
			), $redirect_to);
			
			return $redirect_to;
		}
		
		/**
		 * Add new actions.
		 *
		 * @since 1.0.0
		 */
		public function filter__add_actions($bulk_actions) {
			$bulk_actions[ Options\WP_ApiShip_Options::PRINT_LABELS_ACTION ] = esc_html__('Печать наклеек','apiship');
			$bulk_actions[ Options\WP_ApiShip_Options::PRINT_WAYBILLS_ACTION ] = esc_html__('Печать акта приема-передачи','apiship');	
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
					'page' => Options\WP_ApiShip_Options::get_wc_settings_page(),
					'tab' => Options\WP_ApiShip_Options::get_wc_settings_plugin_tab(),
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

			$this->labels_log_file = Options\WP_ApiShip_Options::get_labels_file();
			
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
			
			$logs_dir = Options\WP_ApiShip_Options::get_plugin_logs_dir();

			wp_mkdir_p( $logs_dir );
			
			// Protect the folder from reading via URL
			if ( ! file_exists( $logs_dir . '/.htaccess' ) ) {
				$GLOBALS['wp_filesystem']->put_contents($logs_dir . '/.htaccess', 'deny from all');
			}
			if ( ! file_exists( $logs_dir . '/index.php' ) ) {
				$GLOBALS['wp_filesystem']->put_contents($logs_dir . '/index.php', '');
			}			
		}
		
	}
	
endif;
			
# --- EOF