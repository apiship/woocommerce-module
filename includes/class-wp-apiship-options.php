<?php
/**
 * File: class-wp-apiship-options.php
 *
 * @package WP ApiShip
 * @subpackage Options
 *
 * @since 1.0.0
 */
namespace WP_ApiShip\Options;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists('WP_ApiShip_Options') ) :

	class WP_ApiShip_Options {

		/**
		 * Plugin constants.
		 */
		const PLATFORM 		  = 'wordpress';
		const BRAND	  		  = 'ApiShip';
		const INTEGRATOR	  = 'WPApiShip';
		const RATES_MAX		  = 15;
		const ICON_URL		  = 'https://storage.apiship.ru/icons/providers/svg/';
		const PROVIDER_PLACEHOLDER = 'provider-placeholder.jpg';
		const LABEL_PLACEHOLDER    = 'label-placeholder.jpg';
		const PLUGIN_OPTIONS  	   = 'PLUGIN_OPTIONS';
		const SHIPPING_METHOD_ID   = 'wpapiship_shipping';
		const INTEGRATOR_ORDER_KEY = 'integratorOrder';
		const PROVIDER_NUMBER_KEY  = 'providerNumber';
		const INTEGRATOR_ORDER_INIT_VALUE = '-'; // Don't make this constant empty.
		const TARIFF_DATA_KEY 		 = 'tariff';
		const PRINT_LABELS_ACTION 	 = 'wpapiship_print-labels';
		const PRINT_WAYBILLS_ACTION  = 'wpapiship_print-waybills';
		const LOGS_DIR_BASENAME   	 = 'wpapiship-logs';
		const LABELS_FILE_BASENAME   = 'wpapiship-labels.log';
		const YANDEX_MAP_URL 		 = 'https://api-maps.yandex.ru/2.1/?lang=ru_RU';
		const YANDEX_MAP_DEFAULT_KEY = '5ea541c2-8d1c-459d-81dc-fb77a608ecd3';
		const YANDEX_MAP_VERSION	 = '2.1';
				
		/**
		 * Delivery point template
		 * 
		 * @since 1.4.0
		 */
		const DEFAULT_POINTS_TEMPLATE	 = '%type %company %name %tariff %address %time';
			
		/**
		 * Default point out display mode
		 * 
		 * @since 1.5.0
		 */
		const DEFAULT_POINT_OUT_DISPLAY_MODE = 1;
		
		/**
		 * Default mapping settings.
		 * 
		 * @since 1.4.0
		 */
		const APISHIP_MAPPING_SETTINGS = [
			'uploading' => [
				'title' => 'Загрузка информации в систему перевозчика',
				'is_active_status' => false,
				'selected_status' => 'none',
			],
			'uploaded' => [
				'title' => 'Информация успешно загружена в систему перевозчика',
				'is_active_status' => false,
				'selected_status' => 'none',
			],	
			'onPointIn' => [
				'title' => 'Принят на склад в пункте отправления',
				'is_active_status' => false,
				'selected_status' => 'none',
			],
			'onWay' => [
				'title' => 'В пути',
				'is_active_status' => false,
				'selected_status' => 'none',
			],
			'onPointOut' => [
				'title' => 'Прибыл на склад в пункте назначения',
				'is_active_status' => false,
				'selected_status' => 'none',
			],
			'readyForRecipient' => [
				'title' => 'Готов к выдаче в пункте назначения',
				'is_active_status' => false,
				'selected_status' => 'none',
			],
			'delivering' => [
				'title' => 'Передан на доставку в пункте назначения',
				'is_active_status' => true,
				'selected_status' => 'wc-processing',
			],
			'returnedFromDelivery' => [
				'title' => 'Возвращен с доставки',
				'is_active_status' => false,
				'selected_status' => 'none',
			],
			'partialReturn' => [
				'title' => 'Частичный возврат',
				'is_active_status' => false,
				'selected_status' => 'none',
			],
			'delivered' => [
				'title' => 'Доставлен получателю',
				'is_active_status' => true,
				'selected_status' => 'wc-completed',
			],

			'returnReady' => [
				'title' => 'Подготовлен возврат',
				'is_active_status' => false,
				'selected_status' => 'none',
			],
			'returning' => [
				'title' => 'Возвращается отправителю',
				'is_active_status' => false,
				'selected_status' => 'none',
			],
			'returned' => [
				'title' => 'Возвращен отправителю',
				'is_active_status' => false,
				'selected_status' => 'none',
			],

			'uploadingError' => [
				'title' => 'Ошибка передачи информации в систему перевозчика',
				'is_active_status' => false,
				'selected_status' => 'none',
			],
			'deliveryCanceled' => [
				'title' => 'Доставка отменена',
				'is_active_status' => true,
				'selected_status' => 'wc-cancelled',
			],
			'problem' => [
				'title' => 'Возникла проблема',
				'is_active_status' => false,
				'selected_status' => 'none',
			],
			'lost' => [
				'title' => 'Утерян',
				'is_active_status' => false,
				'selected_status' => 'none',
			],
			'unknown' => [
				'title' => 'Неизвестный статус',
				'is_active_status' => false,
				'selected_status' => 'none',
			],
			'notApplicable' => [
				'title' => 'N/A',
				'is_active_status' => false,
				'selected_status' => 'none',
			],
		];

		/**
		 * Providers list.
		 */
		const PROVIDERS_LIST = [
			"accordpost" => [
				"key" => "accordpost",
				"name" => "Accordpost",
				"description" => null
			],
			"axilog" => [
				"key" => "axilog",
				"name" => "Axilog",
				"description" => null
			],
			"azexpress" => [
				"key" => "azexpress",
				"name" => "AZ.Express",
				"description" => null
			],
			"b2cpl" => [
				"key" => "b2cpl",
				"name" => "B2Cpl",
				"description" => "Курьерская служба доставки для Интернет-магазинов по России"
			],
			"boxberry" => [
				"key" => "boxberry",
				"name" => "Boxberry",
				"description" => "Boxberry – удобная и быстрая доставка посылок"
			],
			"cdek" => [
				"key" => "cdek",
				"name" => "СДЭК",
				"description" => "Экспресс доставка грузов по России и миру"
			],
			"checkbox" => [
				"key" => "checkbox",
				"name" => "Checkbox",
				"description" => null
			],
			"cityex" => [
				"key" => "cityex",
				"name" => "CityExpress",
				"description" => null
			],
			"courierist" => [
				"key" => "courierist",
				"name" => "Курьерист",
				"description" => "Курьерская служба доставки в день заказа"
			],
			"cse" => [
				"key" => "cse",
				"name" => "CSE",
				"description" => "КурьерСервисЭкспресс"
			],
			"d-club" => [
				"key" => "d-club",
				"name" => "Dostavka-Club",
				"description" => "Курьерская служба доставка для  интернет магазинов в Москве и Санкт-Петербурге"
			],
			"dalli" => [
				"key" => "dalli",
				"name" => "Dalli-Service",
				"description" => null
			],
			"dellin" => [
				"key" => "dellin",
				"name" => "Деловые Линии",
				"description" => null
			],
			"dostavista" => [
				"key" => "dostavista",
				"name" => "Dostavista",
				"description" => "Dostavista"
			],
			"dpd" => [
				"key" => "dpd",
				"name" => "DPD",
				"description" => null
			],
			"drhl" => [
				"key" => "drhl",
				"name" => "DRH logistic",
				"description" => null
			],
			"e-kit" => [
				"key" => "e-kit",
				"name" => "Е-КИТ",
				"description" => "Логистика для интернет магазинов. Перевозки с наложенным платежом. Услуга Посылка."
			],
			"easyway" => [
				"key" => "easyway",
				"name" => "ПЭК Easyway",
				"description" => null
			],
			"ebulky" => [
				"key" => "ebulky",
				"name" => "EASY BULKY",
				"description" => null
			],
			"ecomlog" => [
				"key" => "ecomlog",
				"name" => "E-COMLOG",
				"description" => null
			],
			"exmail" => [
				"key" => "exmail",
				"name" => "ExMail",
				"description" => "Служба доставки ExMail"
			],
			"guru" => [
				"key" => "guru",
				"name" => "Dostavka.GURU",
				"description" => null
			],
			"halva" => [
				"key" => "halva",
				"name" => "Халва",
				"description" => null
			],
			"integral" => [
				"key" => "integral",
				"name" => "Интеграл",
				"description" => ""
			],
			"kazpost" => [
				"key" => "kazpost",
				"name" => "Почта Казахстана",
				"description" => null
			],
			"kgt" => [
				"key" => "kgt",
				"name" => "КГТ",
				"description" => ""
			],
			"logsis" => [
				"key" => "logsis",
				"name" => "Logsis",
				"description" => null
			],
			"lpost" => [
				"key" => "lpost",
				"name" => "Л-Пост",
				"description" => "Лабиринт-Пост"
			],
			"major" => [
				"key" => "major",
				"name" => "Major Express",
				"description" => null
			],
			"marlinzet" => [
				"key" => "marlinzet",
				"name" => "Marlinzet",
				"description" => "Marlinzet"
			],
			"omnic" => [
				"key" => "omnic",
				"name" => "Omnic",
				"description" => null
			],
			"pecom" => [
				"key" => "pecom",
				"name" => "ПЭК",
				"description" => null
			],
			"podorojnik" => [
				"key" => "podorojnik",
				"name" => "Подорожник",
				"description" => "Грузовые перевозки по Москве и области"
			],
			"pony" => [
				"key" => "pony",
				"name" => "PONY EXPRESS",
				"description" => null
			],
			"rayber" => [
				"key" => "rayber",
				"name" => "Райбер",
				"description" => ""
			],
			"redexpress" => [
				"key" => "redexpress",
				"name" => "RedExpress",
				"description" => null
			],
			"rudostavka" => [
				"key" => "rudostavka",
				"name" => "РУ-ДОСТАВКА",
				"description" => null
			],
			"runcrm" => [
				"key" => "runcrm",
				"name" => "runCRM",
				"description" => "Программа для курьерской службы и приложение для курьеров"
			],
			"rupost" => [
				"key" => "rupost",
				"name" => "Почта России",
				"description" => null
			],
			"sberlog" => [
				"key" => "sberlog",
				"name" => "Сберлогистика",
				"description" => null
			],
			"strizh" => [
				"key" => "strizh",
				"name" => "СТРИЖ",
				"description" => null
			],
			"td" => [
				"key" => "td",
				"name" => "TopDelivery",
				"description" => null
			],
			"vozovoz" => [
				"key" => "vozovoz",
				"name" => "Возовоз",
				"description" => null
			],
			"x5" => [
				"key" => "x5",
				"name" => "5Post",
				"description" => "X5 OMNI"
			],
			"yataxi" => [
				"key" => "yataxi",
				"name" => "Яндекс.Доставка",
				"description" => null
			]
		];
		
		/**
		 * Woocommerce constants.
		 */
		const WC_ORDER_POST_TYPE = 'shop_order';
		const WС_DEFAULT_COUNTRY = 'RU';
		const WC_OPTIONS 	  	 = 'WC_OPTIONS';
		const WC_PAYMENT_DIRECT_BANK_TRANSFER = 'bacs';
		const WC_SHIPPING_DEBUG_MODE_KEY	  = 'woocommerce_shipping_debug_mode';

		/**
		 * Post order meta.
		 */
		const ORDER_PLACES_META 			  = '_wpapiship_order_places';
		const POST_SHIPPING_TO_POINT_IN_META  = '_wpapiship_shipping_to_point_in';	
		const POST_SHIPPING_TO_POINT_OUT_META = '_wpapiship_shipping_to_point_out';	
		const POST_ORDER_CONTACT_NAME_META 	  = '_wpapiship_order_contact_name';	
		const POST_ORDER_PHONE_META 	  	  = '_wpapiship_order_phone';	
		
		/**
		 * @see CalculatorRequest -> CalculatorPlace https://api.apiship.ru/doc/#/calculator/getCalculator
		 */
		const ITEM_LENGTH 	= 10;   // in cm.
		const ITEM_WIDTH 	= 10;	// in cm.
		const ITEM_HEIGHT 	= 10;	// in cm.
		const ITEM_WEIGHT 	= 20;	// in g.
		const DIMENSIONS_UNIT = 'см';	// in cm.
		const WEIGHT_UNIT  	  = 'гр';	// in g.

		/**
		 * @see availableOperation https://api.apiship.ru/doc/#/lists/getListPoints
		 */
		const POINT_AVAILABLE_OPERATION_RECEIVING = '1';
		const POINT_AVAILABLE_OPERATION_ISSUING   = '2';
		const POINT_AVAILABLE_OPERATION_BOTH      = '3';
		
		/**
		 * @see POST /orders https://api.apiship.ru/doc/#/orders/addOrder
		 */
		const DELIVERY_TO_DOOR  = 'deliveryToDoor';  // 1
		const DELIVERY_TO_POINT = 'deliveryToPoint'; // 2
	
		const DELIVERY_BY_COURIER 	= 1;
		const DELIVERY_TO_POINT_OUT = 2;
		
		/**
		 * Instance.
		 */
		protected static $instance = null;		

		/**
		 * Plugin options key.
		 */	
		protected static $options_key = '_wp_apiship_options';
		
		/**
		 * Plugin options.
		 */		
		protected static $options = null;

		/**
		 * Prefix for WC similar options.
		 */			
		protected static $wc_option_prefix = 'wp_apiship_';

		/**
		 * WC and WC similar options.
		 * @see `get_wc_option` function's description.
		 */
		protected static $wc_options = array();
		
		/**
		 * Woocommerce settings page key.
		 */
		protected static $wc_settings_page = 'wc-settings';
		
		/**
		 * Woocommerce page hook.
		 */
		protected static $wc_settings_page_hook = '';
		
		/**
		 * Plugin tab key.
		 */
		protected static $wc_settings_plugin_tab = 'wp-apiship-tab';

		/**
		 * Woocommerce shipping tab key.
		 */
		protected static $wc_settings_shipping_tab = 'shipping';

		/**
		 * Woocommerce checkout tab key.
		 */
		protected static $wc_settings_checkout_tab = 'checkout';

		/**
		 * Plugin providers section key.
		 */		
		protected static $plugin_providers_section_key = 'providers';
		
		/**
		 * Plugin docs section key.
		 */		
		protected static $plugin_docs_section_key = 'docs';
		
		/**
		 * Provider's list with no logo/image.
		 */
		protected static $no_image_providers = array();
		
		/**
		 * Array of fields for ApiShip metabox.
		 */
		protected static $metabox_fields = array();
		
		/**
		 * Owner point types.
		 */		
		protected static $owner_point_types = array('store', 'warehouse', 'pickup');

		/**
		 * Get instance.
		 *
		 * @param string $path_to_loader
		 *
		 * @return WP_ApiShip_Options
		 */
		public static function get_instance( $path_to_loader = '' ) {
			
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
		
		/**
		 * Constructor.
		 */
		public function __construct() {

			self::$wc_settings_page_hook = 'woocommerce_page_'.self::$wc_settings_page;
			
			/**
			 * We need to get `woocommerce_shipping_debug_mode` option here. @todo
			 * 
			 * @since 1.1.0
			 */
			self::$wc_options[ self::get_wc_shipping_debug_mode_key() ] = get_option( self::get_wc_shipping_debug_mode_key(), 'no' );
		}

		/**
		 * Get delivery types text.
		 *
		 * @since 1.0.0
		 */	
		public static function get_delivery_types() {
			return array(
				self::DELIVERY_BY_COURIER 	=> esc_html__('Доставка курьером','apiship'),
				self::DELIVERY_TO_POINT_OUT => esc_html__('Доставка на ПВЗ','apiship'),
			);
		}
		
		/**
		 * Get delivery type text by (number) type.
		 *
		 * @since 1.0.0
		 */	
		public static function get_delivery_type_text($type) {
			
			$delivery_types = self::get_delivery_types();
			
			if ( isset( $delivery_types[$type] ) ) {
				return $delivery_types[$type];
			}
			
			return '';
		}

		/**
		 * Get pickup types text.
		 *
		 * @since 1.0.0
		 */			
		public static function get_pickup_types() {
			return array(
				1 => esc_html__('Отгрузка груза курьером','apiship'),
				2 => esc_html__('Отгрузка груза на ПВЗ','apiship'),
			);			
		}
		
		/**
		 * Get pickup type text by (number) type.
		 *
		 * @since 1.0.0
		 */	
		public static function get_pickup_type_text($type) {
			
			$pickup_types = self::get_pickup_types();
			
			if ( isset( $pickup_types[$type] ) ) {
				return $pickup_types[$type];
			}
			
			return '';
		}		
		
		/**
		 * @todo rewrite for using in options.
		 */			
		public static function get_rates_max() {
			return self::RATES_MAX;
		}

		/**
		 * Get icon URL.
		 *
		 * @since 1.0.0
		 */			
		public static function get_icon_url( $icon = '' ) {
			return self::ICON_URL . $icon;
		}
	
		/**
		 * Get provider's list with no logo/image.
		 *
		 * @since 1.0.0
		 *
		 * @return array
		 */	
		public static function get_no_image_providers() {
			return array(
				'jde',
				'courierexe',
				'd-club',
				'drhl',
				'ebulky',
				'knd',
				'mxp',
				'pecom',
				'rudostavka',
				'shustrim',
				'smart',
				'today',
				'viehali',
				'zabberi',
			);			
		}

		/**
		 * Get selected providers.
		 *
		 * @since 1.0.0
		 *
		 * @return array
		 */
		public static function get_selected_providers() {

			$opts = self::get_options();
			
			if ( array_key_exists( 'selected_providers', $opts ) ) {
				$selected_providers = $opts['selected_providers'];
			} else {
				// @todo array as CONST.
				$selected_providers = array('b2cpl', 'x5', 'dpd', 'cdek', 'boxberry');
			}
			
			return $selected_providers;
		}
	
		/**
		 * Get owner point types.
		 *
		 * @since 1.0.0
		 *
		 * @return array
		 */	
		public static function get_owner_point_types() {
			return self::$owner_point_types;
		}

		/**
		 * Check if client use warehouse address.
		 *
		 * @since 1.4.0
		 *
		 * @return bool
		 */	
		public static function is_warehouse_address_use(): bool
		{
			$warehouse_address_use = self::get_wc_option( 
				'wp_apiship_warehouse_address_use', 
				'no', 
				false 
			);
		
			if ( $warehouse_address_use == 'yes' ) {
				return true;
			} else {
				return false;
			}
		}
		
		/**
		 * Set metabox fields.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */			
		public static function set_metabox_fields() {

			$warehouse_address_use = self::get_wc_option( 
				'wp_apiship_warehouse_address_use', 
				'no', 
				false 
			);
		
			if ( $warehouse_address_use == 'yes' ) {
				
				$contact_name = self::get_wc_option(
					'wp_apiship_warehouse_contact_name',
					'',
					false 
				);
				
				$phone = self::get_wc_option(
					'wp_apiship_warehouse_phone',
					'',
					false 
				);
			} else {
				
				$contact_name = self::get_wc_option(
					'wp_apiship_store_contact_name',
					'',
					false 
				);
				
				$phone = self::get_wc_option(
					'wp_apiship_store_phone',
					'',
					false 
				);				
			}
			
			self::$metabox_fields = array(
				'pickupDate' => array( 
					'requestID'	  => 'pickupDate',
					'caption'     => esc_html__('Дата передачи груза в службу доставки','apiship'),
					'type' 	      => 'date',
					'name' 	      => 'wpapiship-pickup-date',
					'id'   	   	  => 'wpapiship-pickup-date',
					'selector' 	  => '#wpapiship-pickup-date',
					'class'	   	  => 'WP_ApiShip_Order',
					'placeholder' => '',
				),
				'contactName' => array( 
					'requestID'	  => 'contactName',
					'caption' 	  => esc_html__('ФИО контактного лица','apiship'),
					'type' 	   	  => 'text',
					'name' 	   	  => 'wpapiship-contact-name',
					'id'   	   	  => 'wpapiship-contact-name',
					'selector' 	  => '#wpapiship-contact-name',
					'class'		  => 'WP_ApiShip_Order_Sender',
					'placeholder' => $contact_name,
					'size'		  => 50
				),
				'phone' => array( 
					'requestID'	  => 'phone',
					'caption' 	  => esc_html__('Телефон','apiship'),
					'type' 	   	  => 'text',
					'name' 	   	  => 'wpapiship-phone',
					'id'   	   	  => 'wpapiship-phone',
					'selector' 	  => '#wpapiship-phone',
					'class'		  => 'WP_ApiShip_Order_Sender',
					'placeholder' => $phone,
					'size'		  => 50
				),				
				'pointInId' => array( 
					'requestID'	  => 'pointInId',
					'caption' 	  => esc_html__('ID пункта приёма','apiship'),
					'type' 	   	  => 'text',
					'name' 	   	  => 'wpapiship-point-in-id',
					'id'   	   	  => 'wpapiship-point-in-id',
					'selector' 	  => '#wpapiship-point-in-id',
					'class'		  => 'WP_ApiShip_Order',
					'size'		  => 10
				),
				'pointInAddress' => array( 
					'requestID'	  => 'pointInAddress',
					'caption' 	  => esc_html__('Адрес','apiship'),
					'type' 	   	  => 'text',
					'name' 	   	  => 'wpapiship-point-in-address',
					'id'   	   	  => 'wpapiship-point-in-address',
					'selector' 	  => '#wpapiship-point-in-address',
					#'class'		  => 'none',
				),
				'pointOutId' => array( 
					'requestID'	  => 'pointOutId',
					'caption' 	  => esc_html__('ID пункта выдачи','apiship'),
					'type' 	   	  => 'text',
					'name' 	   	  => 'wpapiship-point-out-id',
					'id'   	   	  => 'wpapiship-point-out-id',
					'selector' 	  => '#wpapiship-point-out-id',
					'class'		  => 'WP_ApiShip_Order',
					'size'		  => 10
				),
				'pointOutAddress' => array( 
					'requestID'	  => 'pointOutAddress',
					'caption' 	  => esc_html__('Адрес','apiship'),
					'type' 	   	  => 'text',
					'name' 	   	  => 'wpapiship-point-out-address',
					'id'   	   	  => 'wpapiship-point-out-address',
					'selector' 	  => '#wpapiship-point-out-address',
					#'class'		  => 'none',
				),				
			);			
		}

		/**
		 * Get metabox fields.
		 *
		 * @since 1.0.0
		 */
		public static function get_metabox_fields() {
			
			if ( empty( self::$metabox_fields ) ) {
				self::set_metabox_fields();
			}
			
			return self::$metabox_fields;
		}
		
		/**
		 * Get metabox field.
		 *
		 * @since 1.0.0
		 *
		 * @return array|boolean
		 */			
		public static function get_metabox_field( $field, $attr = 'id' ) {
			
			if ( empty( self::$metabox_fields ) ) {
				self::set_metabox_fields();
			}
			
			if ( empty( self::$metabox_fields[$field] ) || empty( self::$metabox_fields[$field][$attr] ) ) {
				return false; 
			}
			
			return self::$metabox_fields[$field];
		}
		
		/**
		 * Return true if we are using dropdown rate selector.
		 *
		 * @since 1.0.0
		 *
		 * @return boolean
		 */		
		public static function is_dropdown_selector() {
			return false;
		}
		
		/**
		 * Return true if `wp_apiship_use_selected_providers` option was set to `yes`.
		 *
		 * @since 1.1.0
		 *
		 * @return boolean
		 */		
		public static function is_use_selected_providers() {
			if ( 'no' === self::get_wc_option( 'wp_apiship_use_selected_providers', 'no', false ) ) {
				return false;
			}
			return true;
		}		
	
		/**
		 * Get places.
		 *
		 * @since 1.0.0
		 */	
		public static function get_places( $order = false ) {
			
			if ( ! $order ) {
				return null;
			}
			
			return self::get_order_meta( $order, self::ORDER_PLACES_META, false );
		}		

		/**
		 * Get places.
		 *
		 * @since 1.0.0
		 */	
		public static function update_places( $order = false, $places = false ) {
			
			if ( ! $order ) {
				return null;
			}
			
			return self::update_order_meta( $order, self::ORDER_PLACES_META, $places );
		}

		/**
		 * Update order meta.
		 *
		 * @since 1.0.0
		 */
		public static function update_order_meta( $order = false, $meta = false, $value = null ) {
			
			if ( ! $order || ! $meta ) {
				return false;
			}
			
			return update_post_meta( $order, $meta, $value );
		}			

		/**
		 * Get order meta.
		 *
		 * @since 1.0.0
		 */	
		public static function get_order_meta( $order = false, $meta = false, $default_value = null ) {
			
			if ( ! $order || ! $meta ) {
				return null;
			}
			
			$value = get_post_meta( $order, $meta, true );
			
			if ( empty($value) ) {
				return $default_value;
			}
			
			return get_post_meta( $order, $meta, true );
		}	
	
		/**
		 * Update plugin option.
		 *
		 * @since 1.0.0
		 */		
		public static function update_option( $option, $value = false ) {
			
			$opts = self::get_options();
			
			$opts[$option] = $value;
			
			update_option(
				self::get_options_key(),
				$opts,
				false
			);
			
			self::$options = null;
			self::get_options();
			
			// @todo Test!
			// output `self::$options` to log
			
		}
		
		/**
		 * Get option.
		 *
		 * @since 1.0.0
		 */	
		public static function get_option( $key = false, $default_value = null, $type = self::WC_OPTIONS ) {
	
			if ( ! $key ) {
				return null;	
			}
	
			if ( $type == self::WC_OPTIONS ) {
				
				return self::get_wc_option($key, $default_value, self::$wc_option_prefix);
				
			} else {
			
				$opts = self::get_options();
				
				if ( isset( $opts[$key] ) ) {
					return $opts[$key];
				}
			}
			
			return null;
		}

		/**
		 * Get Woocommerce option by key.
		 *
		 * If we need WPApiShip option, but saved by WC, then we should use `false` as $prefix value.
		 *
		 * @since 1.0.0
		 */			
		public static function get_wc_option( $key, $default_value = null, $prefix = null ) {
			
			if ( is_null( $prefix ) ) {
				$key = self::$wc_option_prefix . $key;
			} else if ( false === $prefix ) {
				// Don't modify key.
			} else {
				$key = $prefix . $key;
			}
			
			if ( isset( self::$wc_options[$key] ) ) {
				return self::$wc_options[$key];
			}

			self::$wc_options[$key] = get_option( $key, $default_value );
			
			return self::$wc_options[$key];
		}
		
		/**
		 * Get plugin options.
		 *
		 * @since 1.0.0
		 */	
		public static function get_options() {
			
			if ( is_null( self::$options ) ) {
				
				self::$options = get_option( self::get_options_key(), false );
				
				if ( ! self::$options ) {
					self::$options = array();
					if ( ! add_option( self::get_options_key(), self::$options, '', false ) ) {
						//	@todo	
					}
				}
			}
			
			return self::$options;
		}
	
		/**
		 * Get options key.
		 *
		 * @since 1.0.0
		 */	
		public static function get_options_key() {
			return self::$options_key;
		}
	
		/**
		 * Get plugin providers section name.
		 *
		 * @since 1.0.0
		 */	
		public static function get_plugin_providers_section() {
			return self::$plugin_providers_section_key;
		}
		
		/**
		 * Get plugin docs section name.
		 *
		 * @since 1.0.0
		 */	
		public static function get_plugin_docs_section() {
			return self::$plugin_docs_section_key;
		}
		
		/**
		 * Get plugin settings tab name.
		 *
		 * @since 1.0.0
		 */
		public static function get_wc_settings_plugin_tab() {
			return self::$wc_settings_plugin_tab;
		}

		/**
		 * Get checkout tab name.
		 *
		 * @since 1.0.0
		 */
		public static function get_wc_settings_checkout_tab() {
			return self::$wc_settings_checkout_tab;
		}

		/**
		 * Get shipping tab name.
		 *
		 * @since 1.0.0
		 */
		public static function get_wc_settings_shipping_tab() {
			return self::$wc_settings_shipping_tab;
		}
		
		/**
		 * Get WC settings page slug.
		 *
		 * @since 1.0.0
		 */
		public static function get_wc_settings_page() {
			return self::$wc_settings_page;
		}
		
		/**
		 * Get WC debug mode key.
		 *
		 * @since 1.1.0
		 */
		public static function get_wc_shipping_debug_mode_key() {
			return self::WC_SHIPPING_DEBUG_MODE_KEY;
		}
	
		/**
		 * Get WC settings page hook.
		 *
		 * @since 1.0.0
		 */
		public static function get_wc_settings_page_hook() {
			return self::$wc_settings_page_hook;
		}		

		/**
		 * Get plugin logs dir.
		 *
		 * @since 1.0.0
		 */
		public static function get_plugin_logs_dir() {
			$upload_dir = wp_upload_dir();
			return $upload_dir['basedir'] . '/' . self::LOGS_DIR_BASENAME;
		}

		/**
		 * Get labels log file.
		 *
		 * @since 1.0.0
		 */		
		public static function get_labels_file() {
			return self::get_plugin_logs_dir() . '/' . self::LABELS_FILE_BASENAME;
		}
	
		/**
		 * Get siteurl option.
		 *
		 * @since 1.0.0
		 */
		public static function get_siteurl() {
			
			$siteurl = get_option('siteurl');
			
			$siteurl = str_replace( 
				array('https://', 'http://'),
				'',
				$siteurl
			);
			
			return $siteurl;
		}

		public static function get_provider_name($provider_key)
		{
			$list = get_option('wp_apiship_providers_list', self::PROVIDERS_LIST);
			if (array_key_exists($provider_key, $list)) {
				return $list[$provider_key]['name'];
			}
			return $provider_key;
		}
	}
	
endif;	

# --- EOF