<?php
/**
 * File: class-wp-apiship-core.php
 *
 * @package WP ApiShip
 *
 * @since 1.0.0
 */
namespace WP_ApiShip;

use stdClass;
use WP_ApiShip\HTTP\WP_ApiShip_HTTP;
use WP_ApiShip\Options,
	WP_ApiShip\HTTP;
use WP_ApiShip\Options\WP_ApiShip_Options;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists('WP_ApiShip_Core') ) :

	class WP_ApiShip_Core {
		
		/**
		 * Instance.
		 */
		protected static $instance = null;		

		/**
		 *
		 * @var array
		 */	
		protected static $official_docs = array();
	
		/**
		 *
		 */	
		protected static $shipping_method_meta = null;
	
		/**
		 *
		 */	
		protected static $shipping_order_item_id = null;
	
		/**
		 * Current WC order on post.php page.
		 */		
		protected static $wc_order = null;

		/**
		 * Keeps the `__FILE__` of the plugin's loader.
		 *
		 * @var string
		 */
		protected static $PLUGIN_FILE;

		/**
		 * The filesystem path of the directory that contains the plugin.
		 *
		 * @var string
		 */
		protected static $PLUGIN_DIR_PATH;
		
		/**
		 * The filesystem path of the directory that contains the API classes.
		 *
		 * @var string
		 */	
		protected static $PLUGIN_DIR_PATH_API;
		
		/**
		 * The filesystem path of the directory that contains the plugin templates.
		 *
		 * @var string
		 */		
		protected static $PLUGIN_DIR_PATH_TEMPLATES;

		/**
		 * The URL path of the directory that contains the plugin.
		 *
		 * @var string
		 */
		protected static $PLUGIN_DIR_URL;

		/**
		 * The name of a plugin extracted from its filename.
		 *
		 * @var string
		 */
		protected static $PLUGIN_BASENAME;

		/**
		 * The URL path of the directory that contains the images.
		 *
		 * @var string
		 */		
		protected static $PLUGIN_DIR_IMAGE_URL;

		/**
		 * @var bool $_SCRIPT_DEBUG Internal representation of the define('SCRIPT_DEBUG')
		 */
		protected static $_SCRIPT_DEBUG = false;

		/**
		 * @var string $_SCRIPT_SUFFIX Whether to use minimized or full versions of JS.
		 */
        protected static $_SCRIPT_SUFFIX = '.min'; # '';

		/**
		 * List of the providers.
		 */
		public static array $providersList = [];

		/**
		 * Get instance.
		 *
		 * @param string $path_to_loader
		 *
		 * @return WP_ApiShip
		 */
		public static function get_instance( $path_to_loader = '' ) {
			
			if ( null === self::$instance ) {
				self::$instance = new self( $path_to_loader );
			}

			return self::$instance;
		}
		
		/**
		 * Constructor.
		 */
		public function __construct( $path_to_loader ) {

			if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
				self::$_SCRIPT_DEBUG  = true;
				self::$_SCRIPT_SUFFIX = '';
			}
			
			self::$PLUGIN_FILE     = $path_to_loader;
			self::$PLUGIN_DIR_PATH = plugin_dir_path( self::$PLUGIN_FILE );
			self::$PLUGIN_DIR_URL  = plugin_dir_url( self::$PLUGIN_FILE );
			self::$PLUGIN_BASENAME = plugin_basename( self::$PLUGIN_FILE );	
			self::$PLUGIN_DIR_IMAGE_URL 	 = self::$PLUGIN_DIR_URL . 'assets/images/';	
			self::$PLUGIN_DIR_PATH_API       = self::$PLUGIN_DIR_PATH . 'includes/api/';
			self::$PLUGIN_DIR_PATH_TEMPLATES = self::$PLUGIN_DIR_PATH . 'includes/admin/templates/';
	
			add_action( 'wp_ajax_' . self::get_class_name() . '_process_ajax', array(
				__CLASS__,
				'on__process_ajax'
			) );
	
			add_action( 'wp_ajax_nopriv_' . self::get_class_name() . '_process_ajax', array(
				__CLASS__,
				'on__process_ajax'
			) );	
	
			if ( is_admin() ) {
				
				add_action( 'admin_enqueue_scripts', array( __CLASS__, 'on__admin_scripts' ), 6 );
				
				add_action( 'admin_enqueue_scripts', array( __CLASS__, 'on__shop_order_admin_scripts' ), 5 );
				
				add_action( 'admin_enqueue_scripts', array( __CLASS__, 'on__admin_styles' ) );
				
				/**
				 * We need to use only one shipping method `Any "ApiShip integrator"`.
				 * @see `Enable for shipping methods` field on Payments tab
				 * https://site/wp-admin/admin.php?page=wc-settings&tab=checkout&section=cod
				 *
				 * @see woocommerce\includes\class-wc-shipping-zone.php
				 */
				add_filter( 
					'woocommerce_shipping_zone_shipping_methods', 
					array( 
						__CLASS__, 
						'filter__wc_zone_shipping_methods' 
					), 
					5, 4 
				);
				
				/**
				 * @see woocommerce\includes\admin\meta-boxes\views\html-order-items.php
				 * @todo delete
				 */
				
				/*
				add_action( 
					'woocommerce_order_item_add_action_buttons',
					array( 
						__CLASS__, 
						'on__wc_action_button' 
					), 
					5, 1
				);
				// */
				
				/**
				 * @see woocommerce\includes\admin\meta-boxes\views\html-order-shipping.php
				 */
				add_action(
					'woocommerce_after_order_itemmeta',
					array( 
						__CLASS__, 
						'on__wc_after_order_itemmeta' 
					), 
					5, 3
				);					
				
				/**
				 * @see woocommerce\includes\admin\meta-boxes\views\html-order-item-meta.php
				 */
				add_filter(
					'woocommerce_hidden_order_itemmeta',
					array( 
						__CLASS__, 
						'filter__wc_hidden_order_itemmeta' 
					), 
					5, 1				
				);
	
				/**
				 * @see woocommerce\includes\admin\settings\class-wc-settings-general.php
				 */
				add_filter(
					'woocommerce_general_settings',
					array( 
						__CLASS__, 
						'filter__wc_general_settings' 
					), 
					5, 1				
				);	
				
				/**
				 * Add plugin custom column.
				 */
				add_filter( 'manage_edit-' . Options\WP_ApiShip_Options::WC_ORDER_POST_TYPE . '_columns',
					array( 
						__CLASS__,
						'filter__add_column' 
					), 
					20
				);

				/**
				 * Manage plugin custom column.
				 */	
				add_filter( 'manage_' . Options\WP_ApiShip_Options::WC_ORDER_POST_TYPE . '_posts_custom_column',
					array( 
						__CLASS__,
						'filter__manage_column' 
					),
					20, 2
				);

			} else {
				
				/**
				 * @scope front
				 */
	
				/**
				 * Enqueue styles for frontend.
				 */	
				add_action( 'wp_enqueue_scripts', array( __CLASS__, 'on__styles' ) );
				
				/**
				 * Add map wrapper to footer.
				 */
				add_action( 'wp_footer', array( __CLASS__, 'on__wp_footer' ), 1000 );
				
				add_filter( 'option_woocommerce_shipping_debug_mode',
					array( 
						__CLASS__,				
						'filter__woocommerce_shipping_debug_mode'
					),
					5, 2
				);
				
				/**
				 * Enqueue scripts for frontend.
				 */
				add_action( 'wp_enqueue_scripts', 
					array( 
						__CLASS__, 
						'on__enqueue_scripts' 
					) 
				);
				
				/**
				 * Add element with data after shipping rate.
				 *
				 * @see woocommerce\templates\cart\cart-shipping.php
				 */
				add_action( 'woocommerce_after_shipping_rate',
					array( 
						__CLASS__,
						'on__wc_after_shipping_rate' ),
					5, 3 
				);

				/**
				 * Add new point out id field.
				 *
				 * @see woocommerce\includes\class-wc-checkout.php
				 */
				add_filter( 'woocommerce_checkout_fields', 
					array( 
						__CLASS__, 
						'filter__woocommerce_checkout_fields' 
					)
				);
	
				/**
				 * @see woocommerce\includes\class-wc-checkout.php
				 */
				add_action( 'woocommerce_checkout_update_order_meta', 
					array( 
						__CLASS__, 
						'on__wc_checkout_update_order_meta' 
					),
					5, 2 
				);

				/**
				 * @see woocommerce\includes\wc-core-functions.php
				 */
				// add_filter( 'woocommerce_locate_template', array( __CLASS__, 'filter__wc_locate_template' ), 5, 3 );				

			}
		}

		/**
		 * Add new point out id field.
		 *
		 * @see woocommerce\includes\class-wc-checkout.php
		 *
		 * @scope front
		 *
		 * @since 1.0.0
		 */
		public static function filter__woocommerce_checkout_fields( $fields ) {
			
			$class = array('form-row-wide', 'wpapiship-checkout-row', 'wpapiship-checkout-row-hidden');
				
			// // ? todo: Why god mode?
			// if ( defined('WP_APISHIP_GODMODE') && WP_APISHIP_GODMODE ) {
			// 	// Do nothing;
			// } else {
			// 	$class[] = 'wpapiship-checkout-row-hidden';
			// }
			
			$fields['order'][ Options\WP_ApiShip_Options::POST_SHIPPING_TO_POINT_OUT_META ] = array(
				'type'		=> 'text',
				'label'     => esc_html__('Пункт выдачи заказа','wp-apiship'),
				'required'  => false,
				'class'     => $class,
			);			
			
			/**
			 * Add Address field and select Point Out button via JS.
			 */
			
			return $fields;
		}

		public static function get_point_display_mode(): int
		{
			$default_point_display_mode = WP_ApiShip_Options::DEFAULT_POINT_OUT_DISPLAY_MODE;

			$point_display_mode = WP_ApiShip_Options::get_option('point_out_display_mode', $default_point_display_mode);
			$point_display_mode = intval($point_display_mode);

			return $point_display_mode;
		}

		/**
		 * Add element with data after shipping rate.
		 *
		 * @scope front
		 *
		 * @since 1.0.0
		 */	
		public static function on__wc_after_shipping_rate( $method, $index ) {
			
			if (!is_checkout() and !is_cart()) {
				return;
			}

			$meta = $method->get_meta_data();
			
			/**
			 * @see `add_rate` function in wp-apiship\includes\class-wp-apiship-shipping-method.php
			 */
			if ( empty( $meta[ Options\WP_ApiShip_Options::TARIFF_DATA_KEY ] ) ) {
				return;
			}
		
			$tariff = json_decode($meta[ Options\WP_ApiShip_Options::TARIFF_DATA_KEY ]);
			$providerKey = $meta['tariffProviderKey'];

			self::$providersList[$providerKey][] = $tariff;

			$elem = '';

			$buttonText = __('Выбрать ПВЗ', 'wp-apiship');
			$labelTariff = $tariff->tariffId;
			$pointOutId = '0';
			$pointName = '';
			$pointAddress = '';
			$tariffSelected = '0';

			if (isset($tariff->isCached) and $tariff->isCached === true) {
				$buttonText = __('Сменить ПВЗ', 'wp-apiship');
				$pointOutId = $tariff->cachedData->point_id;
				$pointName = $tariff->cachedData->name;
				$pointAddress = $tariff->cachedData->address;
				if (isset($tariff->isSelected) and $tariff->isSelected === true) {
					$tariffSelected = '1';
				}
			}

			$tariffList = json_decode($meta['tariffList']);
			$points = $tariff->pointIds;
			$point_display_mode = self::get_point_display_mode();

			if (!empty($tariffList)) {
				foreach($tariffList as $listTariff) {
					$points = array_merge($points, $listTariff->pointIds);
				}
			}

			$points = array_unique($points);

			if ( in_array( Options\WP_ApiShip_Options::DELIVERY_TO_POINT_OUT, $tariff->deliveryTypes ) ) {
				$elem  = '<a href="#" onclick="return false;" ';
				$elem .= 'class="wpapiship-delivery-to-point wpapiship-map-start" ';
				$elem .= 'data-value="' . $method->get_id() . '" ';
				$elem .= 'data-tariff-id="' . $labelTariff . '" ';
				$elem .= 'data-point-out-id="' . $pointOutId . '" ';
				$elem .= 'data-point-out-name="' . $pointName . '" ';
				$elem .= 'data-point-out-address="' . $pointAddress . '" ';
				$elem .= 'data-tariff-selected="' . $tariffSelected . '" ';
				$elem .= 'data-delivery-type="'  .implode(',', $tariff->deliveryTypes) . '" ';
				$elem .= 'data-points-list="'  .implode(',', $points) . '" ';
				$elem .= 'data-tariff-list="'  . htmlspecialchars($meta['tariffList']) . '" ';
				$elem .= 'data-display-mode="'  . $point_display_mode . '" ';
				$elem .= 'data-provider-key="' . $providerKey . '">';
				$elem .= ' (' . $buttonText . ')';
				$elem .= '</a>';
			}
			echo $elem;
		}

		/**
		 * Get selected point out data.
		 *
		 * @since 1.4.0
		 */
		public static function getSelectedPointData($tariff_id, $method_id)
		{
			if (isset($_COOKIE['wp_apiship_selected_point_out_data'])) {
				$selectedPointData = self::decodeSelectedPointData($_COOKIE['wp_apiship_selected_point_out_data']);
				$tariffKey = 't' . $tariff_id . '|' . $method_id;;
				if (isset($selectedPointData->$tariffKey)) {
					$selectedPointData->$tariffKey->is_selected = false;
					if (isset($selectedPointData->selected_tariff_id) and intval($selectedPointData->selected_tariff_id) === intval($tariff_id)) {
						$selectedPointData->$tariffKey->is_selected = true;
					}
					return $selectedPointData->$tariffKey;
				}
			}
			return false;
		}

		/**
		 * Decode selected point out data.
		 *
		 * @since 1.4.0
		 */
		protected static function decodeSelectedPointData(string $data)
		{
			return json_decode(stripcslashes($data));
		}
		
		/**
		 * Save selected point out data.
		 *
		 * @since 1.4.0
		 */
		protected static function saveSelectedPointData(object $data)
		{
			// $_COOKIE['wp_apiship_selected_point_out_data'] = json_encode($data);
			setcookie('wp_apiship_selected_point_out_data', json_encode($data), time() + 3600 * 24, '/');
		}

		/**
		 * Convert string JSON to array and save meta.
		 *
		 * @since 1.0.0
		 */
		public static function on__wc_checkout_update_order_meta( $order_id, $data ) {
			
			if ( ! isset( $data[ Options\WP_ApiShip_Options::POST_SHIPPING_TO_POINT_OUT_META ] ) ) {
				return;
			}

			$obj = json_decode( $data[ Options\WP_ApiShip_Options::POST_SHIPPING_TO_POINT_OUT_META ] );
			
			if ( is_object( $obj ) ) {

				// @todo may be use $array = (array) $object;
				$point_out = array(
					'address' => $obj->address,
					'id' 	  => $obj->id,
				);
				
				update_post_meta( 
					$order_id,
					Options\WP_ApiShip_Options::POST_SHIPPING_TO_POINT_OUT_META,
					$point_out
				);
				
			}
			
			unset($obj);
		}

		/**
		 * Get templates path.
		 *
		 * @since 1.0.0
		 */	
		public static function get_templates_path() {
			return self::$PLUGIN_DIR_PATH_TEMPLATES;
		}
		
		/**
		 * Filter `woocommerce_shipping_debug_mode` option.
		 *
		 * @scope front
		 *
		 * @since 1.0.0
		 */	
		public static function filter__woocommerce_shipping_debug_mode( $value, $option ) {
		
			/**
			 * 
			 * @see woocommerce\includes\wc-conditional-functions.php
				// $checkout_page_id = get_option( 'woocommerce_checkout_page_id' );
			 */
			if (!is_checkout() and !is_cart()) {
				return $value;
			}
		
			if ( empty( $_POST ) ) {
				return $value;
			}
			
			$checked_fields = array(
				'payment_method',
				'shipping_method',
			);
			
			$_run = true;
			foreach( $checked_fields as $field ) {
				if ( empty( $_POST[$field] ) ) {
					$_run = false;
					break;
				}
			}
			
			if ( $_run ) {
				return 'yes';
			}
			
			return $value;
		}
		
		/**
		 * Add warehouse settings to WC general tab.
		 *
		 * @since 1.0.0
		 */	
		public static function filter__wc_general_settings( $settings ) {
			
			/**
			 * Which option we insert after?
			 */
			$insert_after = 'woocommerce_store_postcode';			
	
			$found = false;
			$i = 0;
			foreach ( $settings as $key=>$field ) {
				if ( $field['id'] == $insert_after ) {
					$found = true;
					break;
				}
				$i++;
			}

			if ( ! $found ) {
				return $settings;
			}

			$apiship_tab_url = add_query_arg( 
				array(
					'page' => Options\WP_ApiShip_Options::get_wc_settings_page(),
					'tab'  => Options\WP_ApiShip_Options::get_wc_settings_plugin_tab(),
				),
				admin_url( 'admin.php' ) 
			);

			$description = sprintf(
				esc_html__('Use of warehouse address instead of store address.', 'wp-apiship') . ' ' .
				esc_html__('You can set it on %1sApiShip%2s tab.', 'wp-apiship'),
				'<a href="'.$apiship_tab_url.'">',
				'</a>'
			);

			/**
			 * New setting.
			 */
			$new_setting = array(
				'title' 	=> esc_html__('Warehouse address', 'wp-apiship'),
				'desc' 		=> $description,
				'id' 		=> 'wp_apiship_warehouse_address_use',
				'default' 	=> 'no',
				'type' 		=> 'checkbox',
				#'desc_tip' 	=> 'Tip',	
			);
		
			$new_settings = array_merge(
				array_slice( $settings, 0, $i + 1 ),
				array( $i + 1 => $new_setting ),
				array_slice( $settings, $i + 1 )
			);
			
			return $new_settings;
		}
		
		/**
		 * Let's hide own meta. We need customize the output in new line.
		 * @see `on__wc_after_order_itemmeta` function
		 * @see `post.php` edit page.
		 *
		 * @scope admin
		 *
		 * @since 1.0.0
		 */		
		public static function filter__wc_hidden_order_itemmeta($meta) {

			// @todo
			// if ( self::is_godmode(false) || self::is_godmode(true) ) {
			if ( 0 ) {
				// do nothing.
			} else {
				
				/**
				 * @see meta_data `calculate_shipping` function in wp-apiship\includes\class-wp-apiship-shipping-method.php
				 */
				// $meta[] = Options\WP_ApiShip_Options::INTEGRATOR_ORDER_KEY;
				// $meta[] = 'places';
			}
			
			return $meta;
		}
		
		/**
		 * Add own shipping items.
		 *
		 * @since 1.0.0
		 */	
		public static function on__wc_after_order_itemmeta( $item_id, $item, $_ ) {

			if ( ! self::is_shipping_integrator() ) {
				return;
			}

			if ( 'shipping' !== $item->get_type() ) {
				return;
			}
			
			/**
			 * Let's set $shipping_order_item_id.
			 */
			self::$shipping_order_item_id = $item->get_id();
		}
	
		/**
		 * Add action button to bottom of order.
		 * 
		 * @todo delete
		 *
		 * @since 1.0.0
		 */
	    /*		
		public static function on__wc_action_button( $order ) {

			if ( ! self::is_shipping_integrator() ) {
				return;
			}
			
			/**
			 * Let's set $shipping_order_item_id.
			 *
			 * @todo maybe best place to do it will be found.
			 */
			// $shipping_order_item_id = self::$shipping_order_item_id;
			
			// if ( (int) $shipping_order_item_id > 0 ) {
				// @todo delete after testing.
				/**
				 * @todo after testing delete wp-apiship\includes\admin\templates\order-action-buttons.php 
				 */
				// require_once( self::$PLUGIN_DIR_PATH_TEMPLATES . 'order-action-buttons.php' );
				// require_once( self::$PLUGIN_DIR_PATH_TEMPLATES . 'order-action-view.php' );
			// }
		// }	
		// */
		
		/**
		 * Description see in constructor above.
		 *
		 * @scope admin
		 *
		 * @since 1.0.0
		 */
		public static function filter__wc_zone_shipping_methods( $methods, $raw_methods, $allowed_classes, $instance ) {
		
			if ( empty( $methods ) ) {
				return $methods;
			}

			if ( Options\WP_ApiShip_Options::get_wc_settings_checkout_tab() != self::safe_get('tab') ) {
				return $methods;
			}

			$unneeded_key = false;

			foreach( $methods as $key=>$method ) {

				if ( $method->id === Options\WP_ApiShip_Options::SHIPPING_METHOD_ID ) {
					$unneeded_key = $key;
					break;
				}
			}
			
			if ( $unneeded_key ) {
				unset( $methods[$unneeded_key] );
			}
			
			return $methods;
		}
		
		/**
		 * Process Ajax actions.
		 *
		 * @scope both
		 *
		 * @since 1.0.0
		 */
		public static function on__process_ajax() {
			
			$request = $_POST['request'];

			$response = array();	
			$response['success'] = 'ok';
			
			switch ( $request['action'] ) :
				case 'saveSelectedPointIn':
					
					$opts = Options\WP_ApiShip_Options::get_options();
					
					$providers = array();
					
					if ( ! empty( $opts['providers'] ) ) {
						$providers = $opts['providers'];
					}
					
					if ( empty( $opts['providers'][ $request['providerKey'] ] ) ) {
						$providers[ $request['providerKey'] ] = array();
					} else {
						$providers[ $request['providerKey'] ] = $opts['providers'][ $request['providerKey'] ];
					}

					$point_type = '';
					if ( ! empty( $request['pointType'] ) ) {
						$point_type = $request['pointType'];
					}
					
					$point_id = '';
					if ( ! empty( $request['pointId'] ) ) {
						$point_id = $request['pointId'];
					}
					
					$point_address = '';
					if ( ! empty( $request['pointAddress'] ) ) {
						$point_address = $request['pointAddress'];
					}					
					
					if ( ! empty($point_type) && ! empty($point_id) ) {
						$providers[ $request['providerKey'] ]['pointInId'][$point_type]['pointId'] 		= $point_id;
						$providers[ $request['providerKey'] ]['pointInId'][$point_type]['pointAddress'] = $point_address;
						$providers[ $request['providerKey'] ]['pointInId'][$point_type]['timestamp'] 	= time();
					}
					
					Options\WP_ApiShip_Options::update_option('providers', $providers);
					
					break;
				case 'deleteSelectedPointIn':
					
					$provider_key = false;
					if ( ! empty( $request['providerKey'] ) ) {
						$provider_key = $request['providerKey'];
					}					

					$opts = Options\WP_ApiShip_Options::get_options();	
					
					if ( ! empty( $opts['providers'] ) ) {
						$providers = $opts['providers']; 
					}

					$point_type = false;
					if ( ! empty( $request['pointType'] ) ) {
						$point_type = $request['pointType'];
					}
					
					if ( $provider_key && ! empty( $providers[$provider_key]['pointInId'][$point_type] ) && $point_type ) {
						
						unset( $providers[$provider_key]['pointInId'][$point_type] );
						Options\WP_ApiShip_Options::update_option('providers', $providers);
					
						$html = self::get_select_html( 
							'reset',
							array(
								'type'	=> $point_type,
								'point'	=> array()
							) 
						);
						
						$response['response'][$point_type]['html'] = json_encode($html);					
						
					} else {	
						$response['success'] = 'error';
					}
			
					break;
				case 'updatePointInData':
				
					// @debug
					// output $request to log
					// break;
				
					if ( ! empty($request['postOrderID']) && (int)  $request['postOrderID'] > 0 && is_array($request['data']) ) {
					
						if ( update_post_meta( 
								$request['postOrderID'],
								Options\WP_ApiShip_Options::POST_SHIPPING_TO_POINT_IN_META,
								$request['data']
							)
						) {
							// Do nothing.
						} else {
							$response['success'] = 'error';
						}				
					} else {
						$response['success'] = 'error';
					}				
				
					break;
				case 'deletePointInData':

					if ( ! empty($request['postOrderID']) && (int)  $request['postOrderID'] > 0 ) {
					
						if ( update_post_meta( 
								$request['postOrderID'],
								Options\WP_ApiShip_Options::POST_SHIPPING_TO_POINT_IN_META,
								''
							)
						) {
							// Do nothing.
						} else {
							$response['success'] = 'error';
						}				
					} else {
						$response['success'] = 'error';
					}				
				
					break;
				case 'deletePointOutData':
					
					if ( ! empty($request['postOrderID']) && (int)  $request['postOrderID'] > 0 ) {
					
						if ( update_post_meta( 
								$request['postOrderID'],
								Options\WP_ApiShip_Options::POST_SHIPPING_TO_POINT_OUT_META,
								''
							)
						) {
							// Do nothing.
						} else {
							$response['success'] = 'error';
						}				
					} else {
						$response['success'] = 'error';
					}
					
					break;
				case 'updatePointOutData':
				
					if ( ! empty($request['postOrderID']) && (int)  $request['postOrderID'] > 0 && is_array($request['data']) ) {
					
						if ( update_post_meta( 
								$request['postOrderID'],
								Options\WP_ApiShip_Options::POST_SHIPPING_TO_POINT_OUT_META,
								$request['data']
							)
						) {
							// Do nothing.
						} else {
							$response['success'] = 'error';
						}
					} else {
						$response['success'] = 'error';
					}
				
					break;
				case 'getCardSelectHtml':
						
					$provider_key = false;
					if ( ! empty( $request['providerKey'] ) ) {
						$provider_key = $request['providerKey'];
					}
					
					$opts = Options\WP_ApiShip_Options::get_options();	
					
					if ( $provider_key && ! empty( $opts['providers'] && ! empty( $opts['providers'][$provider_key] ) ) ) {
					
						$point_in_id = $opts['providers'][$provider_key]['pointInId'];
				
						foreach( Options\WP_ApiShip_Options::get_owner_point_types() as $point_type ) {
					
							if ( empty( $point_in_id[$point_type]) ) {
								$response['response'][$point_type]['html'] = false;
								continue;
							}
					
							$html = self::get_select_html( 
								'getCardSelectHtml', 
								array(
									'type'	=> $point_type,
									'point'	=> array(
										'id' 	  => isset($point_in_id[$point_type]['pointId']) ? $point_in_id[$point_type]['pointId'] : '',
										'address' => isset($point_in_id[$point_type]['pointAddress']) ? $point_in_id[$point_type]['pointAddress'] : '',
									)
								) 
							);
							$response['response'][$point_type]['html'] = $html;
						}
						
					} else {	
						$response['success'] = 'error';
					}
					
					break;
				case 'getListPointsOut':
					
					/**
					 * Examples:
					 *
					 * availableOperation - (integer) Тип операции (1 - прием, 2 - выдача, 3 - прием и выдача).
					 *
					 * $response['response'] = HTTP\WP_ApiShip_HTTP::get('lists/points?filter=city=Москва;providerKey=cdek;availableOperation=[2,3]');
					 *
					 * To get more info @see 
					 * 	https://api.apiship.ru/doc/#/lists/getListPoints
					 * 	https://docs.apiship.ru/docs/api/query-filter/
					 */

					$country_code = Options\WP_ApiShip_Options::get_wc_option(
						'woocommerce_default_country', 
						Options\WP_ApiShip_Options::WС_DEFAULT_COUNTRY,
						false
					);

					$endpoint = 'lists/points?limit=500&filter=';
	
					if ( ! empty($request['city']) ) {
						$endpoint = $endpoint . 'city=' . $request['city'];
					}
					
					if ( ! empty($request['providerKey']) ) {
						$endpoint = $endpoint . ';providerKey=' . $request['providerKey'];
					}		
					
					if ( ! empty($request['availableOperation']) ) {
						$endpoint = $endpoint . ';availableOperation=' . $request['availableOperation'];
					}

					/**
					 * cod - Cash on delevery.
					 */
					if ( ! empty($request['cod']) ) {
						$endpoint = $endpoint . ';cod=' . $request['cod'];
					}
					
					$response['response'] = HTTP\WP_ApiShip_HTTP::get($endpoint);

					if ( wp_remote_retrieve_response_code($response['response']) == HTTP\WP_ApiShip_HTTP::OK ) {
						if (isset($request['tariffPointsList'])) {
							$tariffPointsList = explode(',', $request['tariffPointsList']);
						}

						$newBody = new stdClass();
						$newBody->rows = [];

						$body = json_decode($response['response']['body']);
						foreach($body->rows as $key => $row) {
							if (in_array($row->id, $tariffPointsList)) {
								$row->providerName = WP_ApiShip_Options::get_provider_name($row->providerKey);
								$newBody->rows[] = $row;
							}
						}

						$newBody->meta = [
							'offset' => 0,
							'limit' => 500,
							'total' => count($newBody->rows)
						];

						$response['response']['body'] = json_encode($newBody);

					} else {
						$response['success'] = 'error';						
					}
					break;
				case 'getListsPoints':

					$type = 'store';
					if ( ! empty( $request['optionType'] ) ) {
						/**
						 * Maybe 'warehouse'.
						 */
						$type = $request['optionType'];
					}
					
					$use_warehouse_address = Options\WP_ApiShip_Options::is_warehouse_address_use();
					
					if ($type === 'pickup') {
						if ($use_warehouse_address === true) {
							$city = Options\WP_ApiShip_Options::get_wc_option( 'wp_apiship_warehouse_city', false, false );
						} else {
							$city = Options\WP_ApiShip_Options::get_wc_option( 'woocommerce_store_city', false, false );
						}
					} else if ( $type === 'store' or $type === 'pickup' ) {
						$city = Options\WP_ApiShip_Options::get_wc_option( 'woocommerce_store_city', false, false );
					} else {
						$city = Options\WP_ApiShip_Options::get_wc_option( 'wp_apiship_warehouse_city', false, false );
					}

					if ( $city ) {
						
						/**
						 * @todo To create and use class `WP_ApiShip_List_Points`.
						 * @see https://api.apiship.ru/doc/#/lists/getListPoints
						 */
						 
						/**
						 * Examples:
						 *
						 * availableOperation - (integer) Тип операции (1 - прием, 2 - выдача, 3 - прием и выдача).
						 *
						 * $response['response'] = HTTP\WP_ApiShip_HTTP::get('lists/points?filter=city=Москва;providerKey=cdek;availableOperation=[1,3]');
						 *
						 * To get more info @see https://docs.apiship.ru/docs/api/query-filter/
						 */

						$endpoint = 'lists/points?limit=100&filter=city={{city}}';
						
						$endpoint = str_replace( '{{city}}', $city, $endpoint );
						
						if ( ! empty($request['providerKey']) ) {
							$endpoint = $endpoint . ';providerKey=' . $request['providerKey'];
						}		
						
						if ( ! empty($request['availableOperation']) ) {
							$endpoint = $endpoint . ';availableOperation=' . $request['availableOperation'];
						}		
						
						$response['response'] = HTTP\WP_ApiShip_HTTP::get($endpoint);

						if ( wp_remote_retrieve_response_code($response['response']) == HTTP\WP_ApiShip_HTTP::OK ) {
						
							$body = json_decode( wp_remote_retrieve_body($response['response']) );
							
							$points_in = array();
							
							foreach( $body->rows as $key=>$point_in ) {
								$points_in[$key]['address'] = $point_in->address;
								$points_in[$key]['id'] 		= $point_in->id;
							}
							
							/**
							 * @see wp-apiship\includes\admin\templates\point-in-select.php
							 * for example of select 
							 */
							$html = self::get_select_html( 
								'getListsPoints', 
								array(
									'type' 		=> $type,
									'points_in' => $points_in
								)
							);
							
							$response['response']['customHtml'] = json_encode($html);

						} else {
							$response['success'] = 'error';
						}
					} else {	
						$response['success'] = 'error';
					}					

					break;
				case 'saveSelectedPickupTypes':
					$new_list = array();
					if (isset( $request['selectedTypes'])) {
						$new_list = $request['selectedTypes'];
					}

					$opts = Options\WP_ApiShip_Options::get_options();
					
					$providers = array();
					
					if (!empty( $opts['providers'] ) ) {
						$providers = $opts['providers'];
					}
					
					if (empty($opts['providers'][ $request['providerKey']])) {
						$providers[$request['providerKey']] = array();
					} else {
						$providers[$request['providerKey']] = $opts['providers'][$request['providerKey']];
					}

					$providers[$request['providerKey']]['pickup_types'] = $new_list;
					
					Options\WP_ApiShip_Options::update_option('providers', $providers);
					break;

				case 'connectionCheck':
				
					$response['response'] = HTTP\WP_ApiShip_HTTP::get('connections?offset=0&limit=1');

					if ( wp_remote_retrieve_response_code($response['response']) != HTTP\WP_ApiShip_HTTP::OK ) {
						$response['success'] = 'error';
					}	
					
					break;				
				case 'getToken':
					$_response = HTTP\WP_ApiShip_HTTP::get_token(true);
					if ( $_response['response']['code'] != HTTP\WP_ApiShip_HTTP::OK ) {
						$response['success'] = 'error';
					}
					$response['response']= $_response;
					break;				
				case 'getTokenTest':
				
					$response['response']= HTTP\WP_ApiShip_HTTP::get_token(true);
				
					if ( wp_remote_retrieve_response_code($response['response']) != HTTP\WP_ApiShip_HTTP::OK ) {
						$response['success'] = 'error';
					}
					
					break;				
				case 'getProvider':
					// $response['response'] = HTTP\WP_ApiShip_HTTP::get('connections/cdek');
					break;
				case 'getProviders':
					$rows = self::get_providers_data(false, true, false);
					if (!empty($rows)) {
						$body = new stdClass();
						$body->rows = $rows;
						
						$body->meta = [
							'offset' => 0,
							'limit' => 99,
							'total' => count($rows)
						];
						$response['response']['body'] = json_encode($body);
					} else {
						$response['success'] = 'error';
					}	
					break;		
				case 'getTariffs':

					$suffix = '';
					if ( ! empty( $request['suffix'] ) ) {
						$suffix = $request['suffix'];
					}

					/**
					 * Examples:
					 *
					 * $response['response'] = HTTP\WP_ApiShip_HTTP::get('lists/tariffs?filter=providerKey%3Db2cpl&limit=100');
					 * $response['response'] = HTTP\WP_ApiShip_HTTP::get('lists/tariffs?limit=9999');
					 */

					$endpoint = 'lists/tariffs?limit=100';

					if ( ! empty($request['providerKey']) ) {
						$endpoint = $endpoint . '&filter=providerKey%3D' . $request['providerKey'] . '';
					}	

					$response['response'] = HTTP\WP_ApiShip_HTTP::get($endpoint);
					
					if ( wp_remote_retrieve_response_code($response['response']) != HTTP\WP_ApiShip_HTTP::OK ) {
						$response['success'] = 'error';
					}
					
					break;				
				case 'getProviderConnections':
			 
					/**
					 * Examples:
					 *
					 * - http://api.dev.apiship.ru/v1/connections/?filter={"providerKey":"boxberry"}&limit=100
					 */			 
					
					$endpoint = 'connections';

					if ( ! empty($request['providerKey']) ) {
						$endpoint = $endpoint . '?filter={"providerKey":"' . $request['providerKey'] . '"}&limit=100';
					}
					
					$response['response'] = HTTP\WP_ApiShip_HTTP::get($endpoint);

					if ( wp_remote_retrieve_response_code($response['response']) != HTTP\WP_ApiShip_HTTP::OK ) {
						$response['success'] = 'error';
					}
					
					break;				
				case 'getConnections':
			
					/**
					 * Examples:
					 *
					 * - connections
					 * - connections?filter={"providerKey":"boxberry"}&limit=3
					 * - connections?filter={"providerKey":"ozon"}&limit=100
					 */
			 
					$endpoint = 'connections';

					if ( ! empty($request['providerKey']) ) {
						$endpoint = $endpoint . '?filter={"providerKey":"' . $request['providerKey'] . '"}&limit=100';
					}
					
					$response['response'] = HTTP\WP_ApiShip_HTTP::get($endpoint);

					if ( wp_remote_retrieve_response_code($response['response']) == HTTP\WP_ApiShip_HTTP::OK ) {
						
						$body = json_decode( wp_remote_retrieve_body($response['response']) );

						if ( empty( $body->rows ) ) {
							
							/**
							 * Get new connection.
							 */
							$new_connection = self::get_new_connection($request);
							
							$response['response'] = HTTP\WP_ApiShip_HTTP::post(
								'connections',
								array(
									'headers' 	=> array( 
										'Content-Type' => 'application/json' 
									),
									'body' 	  => json_encode($new_connection),
									'timeout' => 20000,
								)
							);
							
							if ( wp_remote_retrieve_response_code($response['response']) == HTTP\WP_ApiShip_HTTP::OK ) {
								
								$endpoint = 'connections';

								if ( ! empty($request['providerKey']) ) {
									$endpoint = $endpoint . '?filter={"providerKey":"' . $request['providerKey'] . '"}&limit=100';
								}
								
								$response['response'] = HTTP\WP_ApiShip_HTTP::get($endpoint);

								if ( wp_remote_retrieve_response_code($response['response'])!= HTTP\WP_ApiShip_HTTP::OK ) {
									$response['success'] = 'error';
								}
								
							} else {
								$response['success'] = 'error';
							}
						}
						
					} else {
						$response['success'] = 'error';
					}
					
					break;	
				case 'cancelIntegratorOrder':

					if ( isset($request['integratorOrder']) && (int) $request['integratorOrder'] > 0 ) {

						$response['response'] = HTTP\WP_ApiShip_HTTP::get(
							'orders/' . $request['integratorOrder'] . '/cancel'
						);	
						
						if ( wp_remote_retrieve_response_code($response['response']) !== HTTP\WP_ApiShip_HTTP::OK ) {
							$response['success'] = 'error';
						}						
						
					} else {
						$response['success'] = 'error';
					}					
					break;			
				case 'deleteIntegratorOrder':

					if ( isset($request['integratorOrder']) && (int) $request['integratorOrder'] > 0 ) {

						/**
						 * Examples:
						 *
						 * DELETE http://api.dev.apiship.ru/v1/orders/{{integratorOrder}}
						 * https://api.apiship.ru/doc/#/orders/deleteOrder
						 */

						$response['response'] = HTTP\WP_ApiShip_HTTP::delete(
							'orders/'.$request['integratorOrder'],
							array(
								'headers' 	=> array( 
									'Content-Type' => 'application/json' 
								)
							)
						);	
						
						if ( wp_remote_retrieve_response_code($response['response']) == HTTP\WP_ApiShip_HTTP::OK ) {
							if ( isset($request['shippingOrderItemId']) && (int) $request['shippingOrderItemId'] > 0 ) {
								/**
								 * @see woocommerce\includes\wc-order-item-functions.php
								 */
								wc_update_order_item_meta( 
									$request['shippingOrderItemId'], 
									Options\WP_ApiShip_Options::INTEGRATOR_ORDER_KEY, 
									Options\WP_ApiShip_Options::INTEGRATOR_ORDER_INIT_VALUE
								);
							}
						} else {
							$response['success'] = 'error';
						}						
						
					} else {
						$response['success'] = 'error';
					}					
					break;
				case 'getPoint':

					if ( isset($request['providerKey']) && isset($request['pointId']) && (int) $request['pointId'] > 0 ) {
						
						/**
						 * Examples:
						 *
						 * GET http://api.dev.apiship.ru/v1/lists/points?filter=providerKey=cse;id=49155
						 */
						 
						$endpoint = 'lists/points?filter=providerKey={{key}};id={{id}}';
						$endpoint = str_replace( 
							array( '{{key}}', '{{id}}' ), 
							array( $request['providerKey'], $request['pointId'] ), 
							$endpoint 
						);
						
						$response['response'] = HTTP\WP_ApiShip_HTTP::get( $endpoint );
						
						if ( wp_remote_retrieve_response_code($response['response']) != HTTP\WP_ApiShip_HTTP::OK ) {
							$response['success'] = 'error';
						}						
						
					} else {
						$response['success'] = 'error';
					}
					break;
				case 'getIntegratorOrder':
				
					if ( isset($request['integratorOrder']) && (int) $request['integratorOrder'] > 0 ) {
						
						/**
						 * Examples:
						 *
						 * GET http://api.dev.apiship.ru/v1/orders/{{integratorOrder}}
						 */
						
						$response['response'] = HTTP\WP_ApiShip_HTTP::get( 'orders/'.$request['integratorOrder'] );
						
						if ( wp_remote_retrieve_response_code($response['response']) != HTTP\WP_ApiShip_HTTP::OK ) {
							$response['success'] = 'error';
						}						
						
					} else {
						$response['success'] = 'error';
					}
					break;
				case 'getOrderStatusByClientNumber':
				
					if ( isset($request['postOrderID']) && (int) $request['postOrderID'] > 0 ) {
						
						/**
						 * Examples:
						 *						
						 * GET http://api.dev.apiship.ru/v1/orders/status?clientNumber={{postOrderID}}
						 * @see https://api.apiship.ru/doc/#/statuses/getOrderStatusByClientNumber
						 */
						
						$response['response'] = HTTP\WP_ApiShip_HTTP::get( 
							'orders/status?clientNumber=' . $request['postOrderID']
						);
						
						if ( wp_remote_retrieve_response_code($response['response']) != HTTP\WP_ApiShip_HTTP::OK ) {
							$response['success'] = 'error';
						}						
						
					} else {
						$response['success'] = 'error';
					}						
					break;		
				case 'getOrderStatus':

					if ( isset($request['integratorOrder']) && (int) $request['integratorOrder'] > 0 ) {
						
						/**
						 * Examples:
						 *
						 * GET http://api.dev.apiship.ru/v1/orders/{{integratorOrder}}/status
						 * @see https://api.apiship.ru/doc/#/statuses/getOrderStatus
						 */
						 
						$response['response'] = HTTP\WP_ApiShip_HTTP::get( 
							'orders/'.$request['integratorOrder'].'/status'
						);
						
						if ( wp_remote_retrieve_response_code($response['response']) != HTTP\WP_ApiShip_HTTP::OK ) {
							$response['success'] = 'error';
						}						
						
					} else {
						$response['success'] = 'error';
					}				
					break;
				case 'getOrderLabel':
					
					if ( isset($request['integratorOrder']) && (int) $request['integratorOrder'] > 0 ) {

						/**
						 * Examples:
						 *
						 * POST http://api.dev.apiship.ru/v1/orders/labels
						 * @see https://api.apiship.ru/doc/#/orderDocs/getLabels
						 */
						
						$body_request = array(
							'orderIds' => array($request['integratorOrder']),
							'format'   => 'pdf',
						);
						
						$response['response'] = HTTP\WP_ApiShip_HTTP::post( 
							'orders/labels',
							array(
								'headers' 	=> array( 
									'Content-Type' => 'application/json' 
								),
								'body' 	  => json_encode($body_request),
								'timeout' => 20000,
							)							
						);
						
						if ( wp_remote_retrieve_response_code($response['response']) != HTTP\WP_ApiShip_HTTP::OK ) {
							$response['success'] = 'error';
						}						
						
					} else {
						$response['success'] = 'error';
					}				
					break;					
					
					break;
				case 'getCalculation':
					
					// @todo obsolete.
					
					$body_request = self::get_calculator_request();
					
					$response['response'] = HTTP\WP_ApiShip_HTTP::post(
						'calculator',
						array(
							'headers' 	=> array( 
								'Content-Type' => 'application/json' 
							),
							'body' 	  => json_encode($request),
							'timeout' => 20000,
						)
					);
					break;					
				case 'validateOrder':
				
					if ( (int) $request['postOrderID'] > 0 ) {

						$body_request = self::get_orders_request($request);

						/**
						 * Examples:
						 *
						 * POST http://api.dev.apiship.ru/v1/orders/validate
						 * @see https://api.apiship.ru/doc/#/orders/addOrder
						 */

						$response['response'] = HTTP\WP_ApiShip_HTTP::post(
							'orders/validate',
							array(
								'headers' 	=> array( 
									'Content-Type' => 'application/json' 
								),
								'body' => json_encode($body_request)
							)
						);
					
						if ( wp_remote_retrieve_response_code($response['response']) == HTTP\WP_ApiShip_HTTP::OK ) {
							// Do nothing.
						} else {						
							$response['success'] = 'error';
						}
						
					}
					break;	
				case 'postIntegratorOrder':

					/**
					 * Add new order in ApiShip.
					 */
					if ( (int) $request['postOrderID'] > 0 ) {
						
						$body_request = self::get_orders_request($request);
						
						/**
						 * Examples:
						 *
						 * POST http://api.dev.apiship.ru/v1/orders/
						 * @see https://api.apiship.ru/doc/#/orders/addOrder
						 */
						 
						$response['response'] = HTTP\WP_ApiShip_HTTP::post(
							'orders/sync',
							array(
								'headers' 	=> array( 
									'Content-Type' => 'application/json' 
								),
								'body' => json_encode($body_request),
							)
						);
					
						if ( wp_remote_retrieve_response_code($response['response']) == HTTP\WP_ApiShip_HTTP::OK ) {
							
							$body = json_decode( wp_remote_retrieve_body($response['response']) );
						
							if ( is_object($body) ) {

								$integrator_order_id = (int) $body->orderId;
								$providerNumber = $body->providerNumber;

								if ( $integrator_order_id > 0 && (int) $request['shippingOrderItemId'] > 0 ) {
									/**
									 * @see woocommerce\includes\wc-order-item-functions.php
									 */				
									wc_update_order_item_meta( 
										$request['shippingOrderItemId'], 
										Options\WP_ApiShip_Options::INTEGRATOR_ORDER_KEY, 
										$integrator_order_id
									);
										
									wc_update_order_item_meta( 
										$request['shippingOrderItemId'], 
										Options\WP_ApiShip_Options::PROVIDER_NUMBER_KEY, 
										$providerNumber
									);
								}
							}
						} else {						
							$response['success'] = 'error';
						}
					}
					
					break;				
				case 'saveOrderContactName':
					
					if ( (int) $request['postOrderID'] > 0 && ! empty( $request['field'] ) ) {
						
						$field = $request['field'];

						if ( empty( $field['value'] ) ) {
							$response['success'] = 'error';
						} else {
						
							Options\WP_ApiShip_Options::update_order_meta(
								$request['postOrderID'],
								Options\WP_ApiShip_Options::POST_ORDER_CONTACT_NAME_META,
								$field['value']
							);
							
						}
						
					} else {
						$response['success'] = 'error';
					}
					
					break;
				case 'saveOrderPhone':
					
					if ( (int) $request['postOrderID'] > 0 && ! empty( $request['field'] ) ) {
						
						$field = $request['field'];

						if ( empty( $field['value'] ) ) {
							$response['success'] = 'error';
						} else {
						
							Options\WP_ApiShip_Options::update_order_meta(
								$request['postOrderID'],
								Options\WP_ApiShip_Options::POST_ORDER_PHONE_META,
								$field['value']
							);
							
						}
						
					} else {
						$response['success'] = 'error';
					}
					
					break;						
				case 'saveOrderCustomPlaces':
						
					if ( (int) $request['postOrderID'] > 0 && ! empty( $request['field'] ) ) {
						
						$field = $request['field'];
						
						$places = Options\WP_ApiShip_Options::get_places( $request['postOrderID'] );
						
						if ( ! $places ) {
							$places = array();
						}
						
						if ( ! isset( $places[ $field['placeOrder'] ] ) ) {
							$places[ $field['placeOrder'] ] = array();
						}

						$places[ $field['placeOrder'] ][ $field['dimension'] ] = $field['value'];

						Options\WP_ApiShip_Options::update_places(
							$request['postOrderID'],
							$places
						);	
						
					} else {
						$response['success'] = 'error';
					}
				
					break;				
				case 'getSenderAddressString':
				
					if ( isset( $request['order']['sender'] ) ) {
						
						$sender = $request['order']['sender'];
						
						$address = array();
						
						if ( ! empty($sender['postIndex']) ) {
							$address[] = $sender['postIndex'];
						}

						if ( ! empty($sender['city']) ) {
							$address[] = $sender['city'] . ',';
						}	
	
						if ( ! empty($sender['street']) ) {
							$address[] = 'ул.' . $sender['street'];
						}

						if ( ! empty($sender['house']) ) {
							$address[] = 'д.' . $sender['house'] . ',';
						}	

						if ( ! empty($sender['block']) ) {
							$address[] = $sender['block'] . ',';
						}	

						if ( ! empty($sender['office']) ) {
							$address[] = $sender['office'];
						}
	
						$response['response'] = implode(' ', $address);
						
					} else {
						$response['success'] = 'error';
					}
				
					break;		
					
				case 'saveClientSelectedPoint':
					
					$pointData = (object) [
						'address' => addslashes($request['address']),
						'name' => addslashes($request['name']),
						'point_id' => addslashes($request['id']),
						'tariff_id' => addslashes($request['tariff_id']),
						'method_id' => addslashes($request['method_id']),
					];
					$pointTariffKey = 't' . $request['tariff_id'] . '|' . $request['method_id'];

					$allData = (object) [];
					if (isset($_COOKIE['wp_apiship_selected_point_out_data']) and !empty($_COOKIE['wp_apiship_selected_point_out_data'])) {
						$allData = self::decodeSelectedPointData($_COOKIE['wp_apiship_selected_point_out_data']);
					}

					$allData->$pointTariffKey = $pointData;
					$allData->selected_tariff_id = $request['tariff_id'];
					$allData->selected_method_id = $request['method_id'];

					self::saveSelectedPointData($allData);
				
					break;	

				case 'saveClientSelectedTariff':
				
					$allData = (object) [];
					if (isset($_COOKIE['wp_apiship_selected_point_out_data']) and !empty($_COOKIE['wp_apiship_selected_point_out_data'])) {
						$allData = self::decodeSelectedPointData($_COOKIE['wp_apiship_selected_point_out_data']);
					}

					$allData->selected_tariff_id = $request['tariff_id'];

					self::saveSelectedPointData($allData);
				
					break;	
					
				default:
					break;
			endswitch;			
		
			$response['request'] = $request;
			if ( ! empty($response['success']) && $response['success'] == 'error' ) {
				wp_send_json_error( $response );
			}
			wp_send_json_success( $response );			
		}

		/**
		 * Get HTML for point in.
		 *
		 * @see 
		 *
		 * @since 1.0.0
		 */
		protected static function get_select_html( $point_in_select_action = 'getListsPoints', $attrs = array() ) {
	
			ob_start();
			require( self::$PLUGIN_DIR_PATH_TEMPLATES . 'point-in-select.php' );
			$html = ob_get_clean();
			
			return $html;
		}
		
		/**
		 * Create new connection for Delivery Service.
		 *
		 * @see https://api.apiship.ru/doc/#/connections/createConnection 
		 *
		 * @since 1.0.0
		 */
		protected static function get_new_connection($request) {
	
			if ( ! class_exists( 'WP_ApiShip_Connection' ) ) {
				require_once( self::$PLUGIN_DIR_PATH_API . 'class-wp-apiship-connection.php' );
			}
			
			$connection = new \WP_ApiShip_Connection($request);
			
			return $connection->get();
		}
		
		/**
		 * Calculator request.
		 *
		 * @since 1.0.0
		 */
		protected static function get_calculator_request() {
			
			if ( ! class_exists( 'WP_ApiShip_Calculator_Request' ) ) {
				require_once( self::$PLUGIN_DIR_PATH . 'includes/api/class-wp-apiship-calculator-request.php' );
			}
			$request = new \WP_ApiShip_Calculator_Request();
			
			return $request->get_request();
		}
	
		/**
		 * Orders request.
		 *
		 * @since 1.0.0
		 */
		protected static function get_orders_request( $request ) {
			
			if ( ! class_exists( 'WP_ApiShip_Orders_Request' ) ) {
				require_once( self::$PLUGIN_DIR_PATH . 'includes/api/class-wp-apiship-orders-request.php' );
			}
			
			$orders_request = new \WP_ApiShip_Orders_Request($request);
			
			return $orders_request->get_request();
		}
	
		/**
		 *
		 * @todo delete
		 * Get orders status.
		 * 
		 * @see https://api.apiship.ru/doc/#/statuses/getOrderStatus
		 * Example: http://api.dev.apiship.ru/v1/orders/{{id}}/status
		 *
		 * @since 1.0.0
		 */
		/* 
		protected static function get_orders_status( $integrator_order_id ) {
			
			if ( (int) $integrator_order_id > 0 ) {
				$response = HTTP\WP_ApiShip_HTTP::get( 'orders/'.$integrator_order_id.'/status' );	
				
			}
			
			return $response;
		}
		// */
		
		/**
		 * Get class name without namespace.
		 *
		 * @since 1.0.0
		 */
		protected static function get_class_name() {
			return substr(strrchr(__CLASS__, "\\"), 1);   
		}

		/**
		 * Add modal window.
		 *
		 * @scope front
		 *
		 * @since 1.0.0
		 */
		public static function on__wp_footer(): void
		{
			if (!is_checkout() and !is_cart()) {
				return;
			}

			$providers = WP_ApiShip_Options::PROVIDERS_LIST;

			foreach ($providers as $key => $provider) {
				if (!isset(self::$providersList[$provider['key']])) {
					unset($providers[$key]);
				}
			}

			$vars = [
				'providers' => $providers,
				'point_display_mode' => self::get_point_display_mode()
			];

			self::load_template('checkout-modal', $vars);
		}

		/**
		 * Include template by path.
		 *
		 * @since 1.5.0
		 */
		public static function load_template(string $name, array $vars = []): void
		{
			extract($vars);
			include __DIR__ . "/templates/$name.php";
		}
		
		/**
		 * Register admin styles.
		 *
		 * @scope admin
		 *
		 * @since 1.0.0		 
		 */	
		public static function on__admin_styles( $hook ) {
			
			global $post;
			
			if ( 'post.php' === $hook ) {
				
				if ( 
					isset($post->post_type) && 
					Options\WP_ApiShip_Options::WC_ORDER_POST_TYPE == $post->post_type )
				{
					// do nothing.
				} else {
					return;
				}
						
				if ( is_null( self::$wc_order ) ) {
					self::$wc_order = wc_get_order( $post->ID );
				}

				if ( ! self::is_shipping_integrator(self::$wc_order) ) {
					return;
				}						
						
			} else {
			
				/**
				 * Settings page.
				 */

				if (Options\WP_ApiShip_Options::get_wc_settings_page_hook() != $hook) {
					return;
				}

				if (Options\WP_ApiShip_Options::get_wc_settings_plugin_tab() != self::safe_get('tab')) {
					return;
				}
				
			}
			
			wp_register_style(
				'wp-apiship',
				self::$PLUGIN_DIR_URL . 'assets/css/wpapiship-admin' . self::SCRIPT_SUFFIX() . '.css',
				array(),
				WP_APISHIP_VERSION,
				'all'
			);
			wp_enqueue_style('wp-apiship');
		}

		/**
		 * Get providers data
		 *
		 * @since 1.4.0
		 */
		public static function get_providers_data(bool $useProviderKey = true, bool $update = false, bool $getAll = false)
		{
			$response = HTTP\WP_ApiShip_HTTP::get('lists/providers');
			$data = [];

			if (wp_remote_retrieve_response_code($response) == HTTP\WP_ApiShip_HTTP::OK) {

				$body = json_decode( wp_remote_retrieve_body($response) );
				$selected_providers = Options\WP_ApiShip_Options::get_selected_providers();

				if ($update === true) {
					$updated_selected_providers = [];
					$connectionsResponse = HTTP\WP_ApiShip_HTTP::get('connections');
					if (wp_remote_retrieve_response_code($connectionsResponse) == HTTP\WP_ApiShip_HTTP::OK) {
						$conntectionsBody = json_decode(wp_remote_retrieve_body($connectionsResponse));
						foreach($conntectionsBody->rows as $key => $connection) {
							$updated_selected_providers[] = $connection->providerKey;
						}
						$selected_providers = $updated_selected_providers;
						WP_ApiShip_Options::update_option('selected_providers', $updated_selected_providers);
					}
				}

				foreach( $body->rows as $key => $provider ) {
					$provider->selected = false;
					$provider->data = false;
					if (in_array( $provider->key, $selected_providers )) {
						$provider->selected = true;
					}
					if ( in_array( $provider->key, $selected_providers ) or $getAll === true ) {
						$providers = Options\WP_ApiShip_Options::get_option( 'providers', false, false); 
						if ( ! empty( $providers[ $provider->key ] )) {
							$provider->data = $providers[ $provider->key ];
						}
						if ($useProviderKey === true) {
							$data[$provider->key] = $provider;
						} else {
							$data[] = $provider;
						}
					}
				}
				return $data;
			}
			return array();
		}
		
		/**
		 * Register scripts for WC order page.
		 *
		 * @scope admin
		 *
		 * @since 1.0.0
		 */
		public static function on__shop_order_admin_scripts( $hook ) {
			
			global $post, $pagenow;
			
			if ( isset($post->post_type) && Options\WP_ApiShip_Options::WC_ORDER_POST_TYPE == $post->post_type ) {

				if ( is_null( self::$wc_order ) ) {
					self::$wc_order = wc_get_order( $post->ID );
				}

				if ( ! self::is_shipping_integrator(self::$wc_order) ) {
					return;
				}
	
				$i18n = array();
				$i18n['orderStatus']  	= esc_html__('Получение статуса заказа по номеру заказа в системе клиента (GET)', 'wp-apiship');
				$i18n['orderInfo']    	= esc_html__('Получение информации по заказу (GET)', 'wp-apiship');
				$i18n['orderCancel']  	= esc_html__('Отмена заказа (GET)', 'wp-apiship');
				$i18n['orderDelete']  	= esc_html__('Удаление заказа (DELETE)','wp-apiship');	
				$i18n['Error'] 	      	= esc_html__('Error','wp-apiship');
				$i18n['error'] 	      	= esc_html__('Error','wp-apiship');
				$i18n['parsingError'] 	= esc_html__('Parsing error.', 'wp-apiship');
				$i18n['orderExists']  	= esc_html__('В системе ApiShip существует заказ для #{{id}}','wp-apiship');
				$i18n['incorrectToken'] = esc_html__('Некорректный ключ безопасности','wp-apiship');
				$i18n['labelNotExists']	= esc_html__('Ярлык не доступен для скачивания','wp-apiship');
				$i18n['connections']	= esc_html__('Соединений','wp-apiship');
				$i18n['select']			= esc_html__('выбрать','wp-apiship');
				$i18n['Select']			= esc_html__('Выбрать','wp-apiship');
				$i18n['postamat']		= esc_html__('постамат','wp-apiship');
				$i18n['Postamat']		= esc_html__('Постамат','wp-apiship');
				$i18n['notYMap']		= esc_html__('Яндекс карты не загружены','wp-apiship');

				$providersSectionUrl = add_query_arg( 
					array(
						'page' 	  => Options\WP_ApiShip_Options::get_wc_settings_page(),
						'tab'  	  => Options\WP_ApiShip_Options::get_wc_settings_plugin_tab(),
						'section' => Options\WP_ApiShip_Options::get_plugin_providers_section(),
					),
					admin_url('admin.php') 
				);
				
				$wc_shipping = array(
					'_shipping_city' => self::$wc_order->get_shipping_city(),
				);
				
				$data = array(
					'pagenow' 		 	  	   => $pagenow,
					'providersSectionUrl'  	   => $providersSectionUrl,
					'post_type' 		  	   => Options\WP_ApiShip_Options::WC_ORDER_POST_TYPE,
					'post_id'			  	   => $post->ID,
					'shippingMethodMeta'  	   => self::get_shipping_method_meta(self::$wc_order),
					'shippingOrderItemId' 	   => self::$shipping_order_item_id ? self::$shipping_order_item_id : null,
					'INTEGRATOR_ORDER_KEY' 	   => Options\WP_ApiShip_Options::INTEGRATOR_ORDER_KEY,
					'INTEGRATOR_ORDER_INIT_VALUE'    => Options\WP_ApiShip_Options::INTEGRATOR_ORDER_INIT_VALUE,
					'metaboxFields' 				 => Options\WP_ApiShip_Options::get_metabox_fields(),
					'integratorOrderWarningSelector' => '.integrator-order-warning',
					'senderAddressStringSelector'	 => '.meta-value-sender-address',
					'messageWrapperSelector'		 => '.order-shipping-message-wrapper',
					'messageContentWrapperSelector'	 => '.order-shipping-message-wrapper .content',
					'orderShippingWrapperSelector'	 => '.order-shipping-wrapper',
					'transmittingFieldSelector'		 => '.wpapiship-transmitting-field',
					'orderHiddenClass'		 		 => 'order-hidden',
					'wcShipping'		 		 	 => $wc_shipping,
				);
	
				$providerCard = require_once( self::$PLUGIN_DIR_PATH_TEMPLATES . 'provider-card.php' );
				$data['providerCardHtml'] 		= $providerCard;
				$data['providerCardsSelector']  = '#order_shipping_line_items .thumb';
				$data['providerIconURL'] 		= Options\WP_ApiShip_Options::get_icon_url();
				
				$data['connections'] = array();
				$data['providerKey'] = '';
				
				$data['currentPointOutData'] = Options\WP_ApiShip_Options::get_order_meta( 
					self::$wc_order->get_id(), 
					Options\WP_ApiShip_Options::POST_SHIPPING_TO_POINT_OUT_META,
					false 
				);
				//
				$data['deliveryTypeSelector'] = '#wpapiship-order-metabox .delivery-type';
				$data['deliveryType'] 		  = Options\WP_ApiShip_Options::get_delivery_types();
				//
				$data['ymapSelector'] = '#wpapiship-ymap';
				//
				$data['wcCountryCode'] = Options\WP_ApiShip_Options::get_wc_option(
					'woocommerce_default_country', 
					Options\WP_ApiShip_Options::WС_DEFAULT_COUNTRY,
					false
				);
				
				/**
				 * Register Yandex map for admin (WC order page).
				 */
				wp_register_script( 
					'yandexMaps',
					self::get_yandex_map_url(), 
					'', 
					Options\WP_ApiShip_Options::YANDEX_MAP_VERSION,
					true 
				);
				wp_enqueue_script('yandexMaps');
				
				/**
				 * Register admin script.
				 */				
				wp_register_script(
					'wpapiship-admin',
					self::$PLUGIN_DIR_URL . 'assets/js/wpapiship-admin' . self::SCRIPT_SUFFIX() . '.js',
					array('jquery'),
					WP_APISHIP_VERSION,
					true
				);
				wp_enqueue_script('wpapiship-admin');
				wp_localize_script(
					'wpapiship-admin',
					'WPApiShipAdmin',
					array(
						'version' 		=> WP_APISHIP_VERSION,
						'process_ajax' 	=> self::get_class_name() . '_process_ajax',
						'ajaxurl' 		=> admin_url('admin-ajax.php'),
						'i18n' 			=> $i18n,
						'data' 			=> $data,
					)
				);

				/**
				 * Register map.
				 */
				wp_register_script(
					'wpapiship-admin-map',
					self::$PLUGIN_DIR_URL . 'assets/js/wpapiship-admin-map' . self::SCRIPT_SUFFIX() . '.js',
					array('jquery','wpapiship-admin'),
					WP_APISHIP_VERSION,
					true
				);
				wp_enqueue_script('wpapiship-admin-map');
				wp_localize_script(
					'wpapiship-admin-map',
					'WPApiShipAdminMap',
					array(
						'yandexMapsVersion' => '2.1',
					)
				);
			}
		}
		
		/**
		 * Register scripts for settings.
		 *
		 * @scope admin
		 *
		 * @since 1.0.0
		 */
		public static function on__admin_scripts( $hook ) {
			
			global $current_section;
			
			$current_tab = self::safe_get('tab');
			
			$enabled_tabs = array(
				Options\WP_ApiShip_Options::get_wc_settings_plugin_tab(),
				Options\WP_ApiShip_Options::get_wc_settings_shipping_tab(),
			);
			
			if (Options\WP_ApiShip_Options::get_wc_settings_page_hook() != $hook) {
				return;
			}

			if (!in_array($current_tab, $enabled_tabs)) {
				return;
			}
				
			$i18n = array();
			$i18n['connSuccessful'] = esc_html__('Connection successful.', 'wp-apiship');
			$i18n['connFailed'] 	= esc_html__('Connection failed.', 'wp-apiship');
			$i18n['connError'] 	    = esc_html__('Error', 'wp-apiship');
			$i18n['Error'] 	    	= esc_html__('Error', 'wp-apiship');
			
			$data = array();
			$data['wcSettingsPage'] = Options\WP_ApiShip_Options::get_wc_settings_page();
			$data['section'] 		= $current_section == '' ? 'general' : $current_section;
			$data['tab'] 		  	= $current_tab;
			$data['pluginTab'] 		= Options\WP_ApiShip_Options::get_wc_settings_plugin_tab();
			$data['shippingTab'] 	= Options\WP_ApiShip_Options::get_wc_settings_shipping_tab();
			
			// @todo Open single provider card.
			$selected_provider = self::safe_get('provider');
			if ( empty($selected_provider) ) {
				$data['selectedProvider'] = false;
			} else {
				$data['selectedProvider'] = $selected_provider; 
			}
			$data['selectedProviders'] = Options\WP_ApiShip_Options::get_selected_providers(); 
			
			switch( $current_tab ) :
				case Options\WP_ApiShip_Options::get_wc_settings_plugin_tab() :
	
					if ( $current_section == 'providers' ) {
						$providerCard = require_once( self::$PLUGIN_DIR_PATH_TEMPLATES . 'provider-card.php' );
						$data['providerCardHtml'] 				  = $providerCard;
						$data['providerMessageSelector']  		  = '.provider-message';
						$data['providerCardsSelector']  		  = '.provider-cards';
						$data['selectedProviderCardsSelector'] 	  = '.provider-cards.selected';
						$data['notSelectedProviderCardsSelector'] = '.provider-cards.not-selected';
						$data['providerCardSelectSelector']		  = '.provider-select-label';
						$data['providersFormSelector']   		  = '#wp_apiship_providers_form';
						$data['providerCardSelector']   		  = '#wp_apiship_providers_form .provider-card';
						$data['providerCardProcessedClass']		  = 'processed';
						$data['selectedProviderCardClass']	  	  = 'provider-selected';
						$data['notSelectedProviderCardClass']	  = 'provider-not-selected';
						$data['pointInSelectWrapperSelector']	  = '.point-in-select-wrapper';
						$data['providerCardPickupSelect']		  = '.provider-card-pickup-select';
						$data['providerIconURL'] 				  = Options\WP_ApiShip_Options::get_icon_url();
						$data['ownerPointTypes']				  = Options\WP_ApiShip_Options::get_owner_point_types();
					}			
		
					$data['imageURL'] = self::$PLUGIN_DIR_IMAGE_URL;
					$data['providerPlaceholder'] = Options\WP_ApiShip_Options::PROVIDER_PLACEHOLDER;
					$data['noImageProviders'] 	 = Options\WP_ApiShip_Options::get_no_image_providers();
				
					break;	
				case Options\WP_ApiShip_Options::get_wc_settings_shipping_tab() :
					$data['instanceID'] = self::safe_get('instance_id');
					break;	
				default:
					//
			endswitch;
			
			$data['nameTitle'] = esc_html__('Наименование','wp-apiship');

			wp_register_script(
				'wpapiship-admin',
				self::$PLUGIN_DIR_URL . 'assets/js/wpapiship-admin' . self::SCRIPT_SUFFIX() . '.js',
				array('jquery'),
				WP_APISHIP_VERSION,
				true
			);
			wp_enqueue_script( 'wpapiship-admin' );
			wp_localize_script(
				'wpapiship-admin',
				'WPApiShipAdmin',
				array(
					'version' 		=> WP_APISHIP_VERSION,
					'process_ajax' 	=> self::get_class_name() . '_process_ajax',
					'ajaxurl' 		=> admin_url( 'admin-ajax.php' ),
					'i18n' 			=> $i18n,
					'data' 			=> $data,
				)
			);	
		}
		
		/**
		 * Register scripts.
		 *
		 * @scope front
		 *
		 * @since 1.0.0
		 */		
		public static function on__enqueue_scripts( $hook ) {

			if (!is_checkout() and !is_cart()) {
				return;
			}

			$i18n = array();
			$i18n['postamat'] = esc_html__('постамат', 'wp-apiship');
			$i18n['select']	  = esc_html__('выбрать','wp-apiship');
			$i18n['Select']	  = esc_html__('Выбрать','wp-apiship');
			$i18n['closeButtonCaption']  = esc_html__('Закрыть', 'wp-apiship');
			$i18n['selectedPointButtonText']  = esc_html__('Сменить ПВЗ', 'wp-apiship');

			$map_start 					= 'wpapiship-map-start';
			$map_start_selector 		= '.wpapiship-map-start';
			$map_start_wrapper			= 'wpapiship-select-point-out';
			$map_start_wrapper_selector	= '#wpapiship-select-point-out';

			$data = array(
				'checkoutPageID' 				 => wc_get_page_id('checkout'),
				'checkoutBlockSelector' 		 => '.woocommerce-checkout-payment',
				'cartBlockSelector' 		 	 => '.woocommerce-cart-form',
				'checkoutSelectPointOutSelector' => '.wpapiship-select-point-out',
				//
				'shippingMethodSelector' 		=> '.shipping_method',
				'checkedShippingMethodSelector' => '.shipping_method:checked',
				//
				'pointOutField'  		 		 => '_wpapiship_shipping_to_point_out',				
				'pointOutFieldSelector'  		 => '#_wpapiship_shipping_to_point_out',
				'pointOutFieldWrapper'   		 => '_wpapiship_shipping_to_point_out_field',
				'pointOutFieldWrapperSelector'   => '#_wpapiship_shipping_to_point_out_field',
				//
				'selectPointOut' 		 => 'wpapiship-select-point-out',
				'selectPointOutSelector' => '.wpapiship-select-point-out',
				//
				'mapStart'		   		  => $map_start,
				'mapStartSelector' 		  => $map_start_selector,
				'mapStartWrapper' 		  => $map_start_wrapper,
				'mapStartWrapperSelector' => $map_start_wrapper_selector,
				//
				'checkoutRow' 		  => 'wpapiship-checkout-row',
				'checkoutRowSelector' => '.wpapiship-checkout-row'
			);
			
			/**
			 * Register Yandex map for frontend.
			 */
			wp_register_script( 
				'yandexMaps',
				self::get_yandex_map_url(), 
				'', 
				Options\WP_ApiShip_Options::YANDEX_MAP_VERSION,
				true 
			);
			wp_enqueue_script('yandexMaps');
			
			/**
			 * Register script for frontend.
			 */
			wp_register_script(
				'wpapiship',
				self::$PLUGIN_DIR_URL . 'assets/js/wpapiship' . self::SCRIPT_SUFFIX() . '.js',
				array('jquery'),
				WP_APISHIP_VERSION,
				true
			);
			wp_enqueue_script('wpapiship');
			wp_localize_script(
				'wpapiship',
				'WPApiShip',
				array(
					'version' => WP_APISHIP_VERSION,
					'data' 	  => $data,
					'i18n' 	  => $i18n,				
				)
			);
			
			/**
			 * Register map for frontend.
			 */		
			$data = array(
				'checkoutMapSelector' => '#wpapiship-checkout-ymap',
				'checkoutMapWrapper'  => '.wpapiship-checkout-modal',
				//
				'checkoutBodyOverlay' 		  => 'wpapiship-overlay',
				'checkoutBodyOverlaySelector' => '.wpapiship-overlay',
				//
				'shippingMethodSelector' 		=> '.shipping_method',
				'checkedShippingMethodSelector' => '.shipping_method:checked',
				//
				'pointOutSelectSelector' 		 => '#wpapiship-checkout-ymap .point-out-select',
				'pointOutFieldSelector'  		 => '#_wpapiship_shipping_to_point_out',
				'pointOutFieldWrapper'   		 => '_wpapiship_shipping_to_point_out_field',
				'pointOutFieldWrapperSelector'   => '#_wpapiship_shipping_to_point_out_field',
				//
				'mapStartSelector' 		  => $map_start_selector,
				'mapStartWrapperSelector' => $map_start_wrapper_selector,
				//
				'pointOutAddress'	  		=> 'wpapiship-point-out-address',
				'pointOutAddressSelector'	=> '#wpapiship-point-out-address',
				'pointOutAddressButton'	    => '.wpapiship-delivery-to-point',
				//
				'deliveryTypes' => array(
					'byCourier'  => Options\WP_ApiShip_Options::DELIVERY_BY_COURIER,
					'toPointOut' => Options\WP_ApiShip_Options::DELIVERY_TO_POINT_OUT,
				),
				//
				'mapProviderSelect' => '#wpapiship_provider_select'
			);
			
			wp_register_script(
				'wpapiship-map',
				self::$PLUGIN_DIR_URL . 'assets/js/wpapiship-map' . self::SCRIPT_SUFFIX() . '.js',
				array('jquery'),
				WP_APISHIP_VERSION,
				true
			);
			wp_enqueue_script('wpapiship-map');
			wp_localize_script(
				'wpapiship-map',
				'WPApiShipMap',
				array(
					'yandexMapsVersion' => Options\WP_ApiShip_Options::YANDEX_MAP_VERSION,
					'process_ajax' 	    => self::get_class_name() . '_process_ajax',
					'ajaxurl' 		    => admin_url('admin-ajax.php'),					
					'data' 			    => $data,
				)
			);			
		}			
		
		/**
		 * Register styles.
		 *
		 * @scope front
		 *
		 * @since 1.0.0		 
		 */	
		public static function on__styles( $hook ) {
			
			if (!is_checkout() and !is_cart()) {
				return;
			}
			
			wp_register_style(
				'wp-apiship',
				self::$PLUGIN_DIR_URL . 'assets/css/wpapiship' . self::SCRIPT_SUFFIX() . '.css',
				array(),
				WP_APISHIP_VERSION,
				'all'
			);
			wp_enqueue_style('wp-apiship');			
		}
		
		
		/**
		 * Get admin URL.
		 *
		 * @since 1.0.0
		 */
		public static function get_admin_url( $args = '' ) {
			
			if ( is_string($args) && ! empty($args) && false !== strpos($args, '.php') ) {
				return admin_url( $args );
			}
			
			if ( is_array( $args ) ) {
				return add_query_arg( 
					$args,
					admin_url( 'admin.php' ) 
				);
			}

			return admin_url( 'admin.php' );
		}
		
		/**
		 * Get link to offical doc.
		 *
		 * @since 1.0.0
		 */	
		public static function get_doc( $key = false ) {
			
			if ( ! $key ) {
				return '';
			}
			
			if ( empty( self::$official_docs ) ) {
				self::set_docs();
			}
			
			if ( ! empty( self::$official_docs[$key] ) ) {
				return self::$official_docs[$key];
			}

			return '';
		}
		
		/**
		 * Set links to offical doc.
		 *
		 * @since 1.0.0
		 */	
		protected static function set_docs() {
			self::$official_docs['token'] = 'https://docs.apiship.ru/en/docs/api/get-token/';			
		}
		
		/**
		 * Get var from $_GET safely.
		 *
		 * @since 1.0.0
		 */		
		public static function safe_get($key) {
			
			$value = '';

			if ( isset( $_GET[ $key ] ) ) { // Input var okay.
				$get_key = $_GET[ $key ]; // Input var okay; sanitization okay.

				if ( is_scalar( $get_key ) ) {
					$value = sanitize_text_field( $get_key );
				}
			}

			return $value;			
		}
		
		/**
		 * @see description in wp-apiship\woocommerce\cart\cart-shipping.php
		 *
		 * @since 1.0.0
		 */		
		public static function filter__wc_locate_template( $template, $template_name, $template_path ) {

			global $woocommerce;

			$_template = $template;

			if ( ! $template_path ) {
				$template_path = $woocommerce->template_url;
			}
			
			$plugin_path = self::$PLUGIN_DIR_PATH . '/woocommerce/';

			$template = locate_template(
				array(

					$template_path . $template_name,
					$template_name,
				)
			);

			// Modification: Get the template from this plugin, if it exists
			if ( ! $template && file_exists( $plugin_path . $template_name ) ) {
				$template = $plugin_path . $template_name;
			}

			// Use default template.
			if ( ! $template ) {
				$template = $_template;
			}
			// Return what we found.
			return $template;
		}
		
		/**
		 * Check that we are using our integrator in the WC order.
		 *
		 * @param WC_Order $wc_order
		 *
		 * @since 1.0.0
		 *
		 * @return boolean
		 */
		public static function is_shipping_integrator($wc_order = null) {
			
			global $post;
			
			if ( is_null($wc_order) || ! $wc_order ) {
				
				if ( ! isset( $post->ID ) ) {
					return false;
				}

				if ( $post->post_type !== Options\WP_ApiShip_Options::WC_ORDER_POST_TYPE ) {
					return false;
				}
				
				$wc_order = wc_get_order( $post->ID );
			}

			$meta = self::get_shipping_method_meta($wc_order);

			if ( is_null($meta) ) {
				return false;
			}
			
			if ( 
				! empty( $meta['integrator'] ) && 
				Options\WP_ApiShip_Options::INTEGRATOR == $meta['integrator']->value ) 
			{
				return true;
			}
			
			return false;
		}

		/**
		 * Get integrator order.
		 *
		 * @since 1.0.0
		 *
		 * @return array
		 */
		public static function get_integrator_order( $key = false ) {
			
			if ( is_null( self::$shipping_method_meta ) ) {
				self::get_shipping_method_meta();
			}
	
			if ( ! empty( self::$shipping_method_meta['integratorOrder'] ) ) {
				
				if ( $key ) {
					return self::$shipping_method_meta['integratorOrder']->$key;
				} else {
					return self::$shipping_method_meta['integratorOrder'];
				}
			}
			
			return null;
		}
		
		/**
		 * Get all shipping method meta.
		 *
		 * @since 1.0.0
		 * 
		 * @param WC_Order $wc_order
		 *
		 * @return array
		 */
		protected static function get_shipping_method_meta($wc_order = null) {

			if ( is_null( self::$shipping_method_meta ) ) {

				if ( is_null( $wc_order ) ) {
					/**
					 * @todo check out $order in `on__wc_action_button` function for correctness.
					 */
					return null;
				}

				self::set_shipping_method_meta($wc_order);
			}
			
			return self::$shipping_method_meta;
		}
		
		/**
		 * Set shipping method meta.
		 *
		 * @since 1.0.0
		 * 
		 * @param WC_Order $wc_order
		 *
		 * @return none
		 */		
		protected static function set_shipping_method_meta($wc_order) {

			$shipping_methods = $wc_order->get_shipping_methods();
				
			foreach( $shipping_methods as $method ) {
				/**
				 * @see woocommerce\includes\class-wc-order-item.php
				 */
				$meta_data = $method->get_formatted_meta_data('');
			}
			
			self::$shipping_method_meta = array();
			foreach ( $meta_data as $_meta_id=>$_meta ) {
				self::$shipping_method_meta[ $_meta->key ] = $_meta;
			}			
		}
		
		/**
		 * Add column to `edit.php?post_type=shop_order` page.
		 *
		 * @since 1.0.0
		 */
		public static function filter__add_column( $posts_columns ) {
			
			/**
			 * Which column we insert after?
			 */
			$insert_after = 'order_status';

			$i = 0;
			foreach ( $posts_columns as $key => $value ) {
				if ( $key == $insert_after ) {
					break;
				}
				$i++;
			}

			$title = esc_html__('ApiShip Order','wp-apiship');
			
			$posts_columns =
				array_slice( $posts_columns, 0, $i + 1 ) + array( 'wpapiship_order' => $title ) + array_slice( $posts_columns, $i + 1 );

			return $posts_columns;
		}
		
		/**
		 * @since 1.0.0
		 */
		public static function filter__manage_column( $column_name, $wc_order_id ) {

			if ( 'wpapiship_order' == $column_name ) {
				
				$wc_order = wc_get_order( $wc_order_id );
				$shipping_methods = $wc_order->get_shipping_methods();

				foreach( $shipping_methods as $method ) {
					
					/**
					 * @see woocommerce\includes\class-wc-order-item.php
					 */
					$meta_data = $method->get_formatted_meta_data('');

					$provider_key = '';

					foreach( $meta_data as $meta ) {
						
						if ( 'tariffProviderKey' === $meta->key ) {
							$provider_key = $meta->value;
						}
						
						if ( Options\WP_ApiShip_Options::INTEGRATOR_ORDER_KEY === $meta->key ) {
							
							if ( isset($meta->value) && (int) $meta->value > 0 ) {
								echo '#' . $meta->value . '&nbsp СД: ' . Options\WP_ApiShip_Options::get_provider_name($provider_key) . '';
							}
							break 2;
						}
					}
				}
			}
		}

		/**
		 * Get provider icon URL.
		 *
		 * @since 1.0.0
		 *
		 * @return string
		 */		
		public static function get_provider_icon_url( $provider = false ) {
			
			if ( ! $provider || in_array( $provider, Options\WP_ApiShip_Options::get_no_image_providers() ) ) {
				return self::$PLUGIN_DIR_IMAGE_URL . Options\WP_ApiShip_Options::PROVIDER_PLACEHOLDER;
			}
			
			return Options\WP_ApiShip_Options::get_icon_url($provider.'.svg');
		}
	
		/**
		 * Get $PLUGIN_DIR_IMAGE_URL constant.
		 *
		 * @since 1.0.0
		 */
		public static function get_plugin_dir_image_url() {
			return self::$PLUGIN_DIR_IMAGE_URL;
		}

		/**
		 * Get Yandex map URL with API key.
		 *
		 * @since 1.0.0
		 */		
		protected static function get_yandex_map_url() {
			
			$api_key = Options\WP_ApiShip_Options::get_wc_option(
				'wp_apiship_yandexmap_key', 
				'',
				false
			);
			
			$api_key = trim($api_key);
			
			if ( empty($api_key) ) {
				$api_key = Options\WP_ApiShip_Options::YANDEX_MAP_DEFAULT_KEY;
			};

			return Options\WP_ApiShip_Options::YANDEX_MAP_URL . '&apikey=' . $api_key;
		}
	
		/**
		 * Check out the god mode.
		 *
		 * @since 1.0.0
		 *
		 * @return boolean
		 */
		public static function is_godmode( $full_mode = true ) {
			
			if ( defined( 'WP_APISHIP_GODMODE' ) ) {  # && WP_APISHIP_GODMODE 
				
				if ( $full_mode ) {
					if ( 'yes' === Options\WP_ApiShip_Options::get_option('god_mode') ) {
						return true;
					}
				} else {
					return true;
				}
			}
			
			return false;
		}
		
		/**
		 * Check out if we should bypass shipping cache.
		 *
		 * @since 1.1.0
		 *
		 * @return boolean
		 */		
		public static function is_bypass_shipping_cache() {
				
			if ( defined('WP_APISHIP_SHIPPING_CACHE') ) {
				
				if ( WP_APISHIP_SHIPPING_CACHE  ) {
					return false;
				} else {
					return true;
				}
				
			}
			
			$shipping_debug_mode = Options\WP_ApiShip_Options::get_wc_option(
				Options\WP_ApiShip_Options::get_wc_shipping_debug_mode_key(), 
				'no',
				false
			);
			
			if ( 'yes' === $shipping_debug_mode ) {
				return true;
			}
			
			return false;
		}		

		public static function get_order_statuses($orderId)
		{
			$response = WP_ApiShip_HTTP::get("orders/$orderId/statusHistory");
			
			if (wp_remote_retrieve_response_code($response) !== HTTP\WP_ApiShip_HTTP::OK) {
				return [];
			}	

			return json_decode(wp_remote_retrieve_body($response))->rows;
		}
	
		/**
		 * Return $_SCRIPT_SUFFIX.
		 *
		 * @since 1.0.0
		 *
		 * @return string
		 */
		public static function SCRIPT_SUFFIX() {
			return self::$_SCRIPT_SUFFIX;
		}
	
		/**
		 * Logger.
		 *
		 * @since 1.0.0
		 */
		public static function __log( $message = '', $extra = '' ) {
			
			if ( empty( $message ) ) {
				return;
			}

			if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				if ( is_string( $message ) ) {
					error_log( print_r( $extra.' LOG :: '.$message, true ) );	
				} else {
					error_log( print_r( $extra.' LOG :: ', true ) );	
					error_log( print_r( $message, true ) );	
				}
			}
			
		}		
	}
	
endif;

# --- EOF