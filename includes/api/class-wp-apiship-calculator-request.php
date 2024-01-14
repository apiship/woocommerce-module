<?php
/**
 * File: class-wp-apiship-calculator-request.php
 *
 * @package WP ApiShip
 *
 * @since 1.0.0
 */

use WP_ApiShip\Options;
use WP_ApiShip\Options\WP_ApiShip_Options;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists('WP_ApiShip_Calculator_Request') ) :

	/**
	 * @see https://api.apiship.ru/doc/#/calculator/getCalculator
	 * @see https://docs.apiship.ru/docs/api/calculator/
	 */
	class WP_ApiShip_Calculator_Request {

		/**
		 * @see CalculatorRequest -> CalculatorPlace https://api.apiship.ru/doc/#/calculator/getCalculator
		 * @see wp-apiship\includes\class-wp-apiship-options.php
		 */
		/* 
		const ITEM_LENGTH;
		const ITEM_WIDTH;
		const ITEM_HEIGHT;
		const ITEM_WEIGHT;
		// */
		
		/**
		 * Address to.
		 *
		 * @since 1.0.0
		 *
		 * @var WP_ApiShip_Calculator_Direction
		 */		
		protected $to;

		/**
		 * Address from.
		 *
		 * @since 1.0.0
		 *
		 * @var WP_ApiShip_Calculator_Direction
		 */		
		protected $from;
		
		/**
		 * Места. Калькуляция многоместных заказов. В случае когда СД не поддерживает многоместную калькуляцию, вес суммируется,
		 * а габариты всех коробок складываются в высоту и берется макс. длина и ширина, то есть коробки ставятся друг на друга
		 * пирамидой и передаются в СД для расчета.
		 *
		 * @var array of objects
		 */
		protected $places = array();
		
		/**
		 * Дата приёма груза (не обязательно, по умолчания берется текущая дата).
		 *
		 * @var string
		 */
		protected $pickupDate;
		
		/**
		 * Типы забора /lists/pickupTypes. Если не переданы, то рассчитываются тарифы по всем типам 
		 * (Забор груза курьером, Сдача груза на ПВЗ). ????
		 * [
		 *		{
		 *			"id": 1,
		 *			"name": "От двери",
		 *			"description": "Забор груза от двери клиента"
		 *		},
		 *		{
		 *			"id": 2,
		 *			"name": "Самозавоз",
		 *			"description": "Клиет сам доставляет груз на пункт приема СД"
		 *		}
		 *	]
		 * @var array of numbers
		 */		
		protected $pickupTypes = array();

		/**
		 * Типы доставки /lists/deliveryTypes. Если не переданы, то рассчитываются тарифы по всем типам.
		 * (Доставка Курьером, Самовывоз из ПВЗ)
		 * [
		 *		{
		 *			"id": 1,
		 *			"name": "До двери",
		 *			"description": "Доставка груза до двери клиента"
		 *		},
		 *		{
		 *			"id": 2,
		 *			"name": "До ПВЗ",
		 *			"description": "Доставка груза до пункта выдачи"
		 *		}
		 *	]	
		 *
		 * @var array of numbers
		 */			
		protected $deliveryTypes = array();
		
		/**
		 * Массив ключей служб доставки. Если не передавать, то рассчитает тарифы всех подключенных к аккаунту служб доставки.
		 *
		 * @var array of numbers
		 */				
		protected $providerKeys = array();

		/**
		 * Оценочная стоимость (в рублях).  // страховка
		 *
		 * @var float
		 */
		protected $assessedCost = 0;
		
		/**
		 * Сумма наложенного платежа.
		 *
		 * @var float
		 */		
		protected $codCost = 0;
		
		/**
		 * Суммировать ли к итоговой стоимости все сборы СД (страховка и комиссия за НП).
		 *
		 * @default: false
		 *
		 * @var boolean
		 */		
		protected $includeFees = false;
		
		/**
		 * Время ожидания ответа (мс.) от провайдера, результаты по провайдерам, которые не успели в указанное время выдаваться не будут.
		 *
		 * @var integer
		 * @default 10000
		 */		
		protected $timeout = 20000;
	
		/**
		 * Пропускает применение правил редактора тарифов. Полезно, если надо проверить корректность применения правил.
		 *
		 * @var boolean
		 */	
		protected $skipTariffRules = false;

		/**
		 * Дополнительные параметры. Например. можно рассчитать DPD по какому-то определенному подключению (договору),
		 * передав dpd.providerConnectId = id из /connections/getListConnections
		 * Передавать как {"<ключ_службы_доставки>.<код_параметра>": "<значение>"}
		 *
		 * @var object
		 */		
		protected $extraParams = array();
		
		/**
		 * Промокод. В редакторе тарифов можно указать промокод, по которому можно изменять тарифы, например, скидку на стоимость доставки.
		 * 
		 * @var string
		 */		
		protected $promoCode = '';
		
		/**
		 * Пользовательское поле. В это поле можно передать, например, название сайта и по нему строить правила в редакторе сайтов.
		 * 
		 * @var string
		 */			
		protected $customCode = '';
	
		/**
		 * If use warehouse address then set value to `yes`.
		 *
		 * @var string
		 */
		protected $warehouse_address_use = null;
		
		/**
		 * Constructor.
		 */		
		public function __construct( $package = array(), $post_data = [] ) {
			
			if ( ! class_exists( 'WP_ApiShip_Calculator_Direction', false  ) ) {
				require_once( 'class-wp-apiship-calculator-direction.php' );
			}
			
			$destination = $package['destination'];
			
			$wc_default_country = Options\WP_ApiShip_Options::get_wc_option( 
				'woocommerce_default_country', 
				Options\WP_ApiShip_Options::WС_DEFAULT_COUNTRY, 
				false 
			);

			$this->warehouse_address_use = Options\WP_ApiShip_Options::get_wc_option( 
				'wp_apiship_warehouse_address_use', 
				'no', 
				false 
			);

			/**
			 * Address to.
			 */			
			$to = new \WP_ApiShip_Calculator_Direction( 
				array(
					'countryCode'	=> ! empty( $destination['country'] )  ? $destination['country']  : $wc_default_country,
					'index' 		=> ! empty( $destination['postcode'] ) ? $destination['postcode'] : '',
					'addressString' => ! empty( $destination['address'] )  ? $destination['address']  : '',
					'region'		=> ! empty( $destination['state'] )    ? $destination['state']    : '',
					'city'			=> ! empty( $destination['city'] ) 	   ? $destination['city']	  : '',
					'lat'			=> 0.0000,
					'lng'			=> 0.0000,
				)
			);
			$this->to = $to->get();
			
			/**
			 * Address from.
			 */
			if ( $this->warehouse_address_use == 'no' ) {			 
			 
				$from = new \WP_ApiShip_Calculator_Direction(
					array(
						'countryCode'	=> $wc_default_country,
						'index'			=> Options\WP_ApiShip_Options::get_wc_option( 'woocommerce_store_postcode', false, false ),
						'addressString' => Options\WP_ApiShip_Options::get_wc_option( 'woocommerce_store_address', false, false ),	
						'region' 		=> Options\WP_ApiShip_Options::get_wc_option( 'woocommerce_store_city', false, false ),
						'city' 			=> Options\WP_ApiShip_Options::get_wc_option( 'woocommerce_store_city', false, false ),
						'lat' 			=> 0.0000,
						'lng' 			=> 0.0000,
					)			
				);
				
			} else {

				$country_code = Options\WP_ApiShip_Options::get_wc_option( 
					'wp_apiship_warehouse_country', 
					$wc_default_country, 
					false 
				);
				
				if ( trim($country_code) == '' ) {
					$country_code = $wc_default_country;
				}
				
				$post_index   = Options\WP_ApiShip_Options::get_wc_option( 'wp_apiship_warehouse_index', false, false );
				$city 		  = Options\WP_ApiShip_Options::get_wc_option( 'wp_apiship_warehouse_city', false, false );
				
				$from = new \WP_ApiShip_Calculator_Direction(
					array(
						'countryCode'	=> $country_code,
						'index'			=> $post_index,
						'region' 		=> $city,
						'city' 			=> $city,
						'addressString' => $this->get_address_string(
							$country_code,
							$post_index,
							$city,
						),	
						'lat' => 0.0000,
						'lng' => 0.0000,
					)			
				);
			}
			
			$this->from = $from->get();
	
			/**
			 * Others params.
			 */
			 
			/** 
			 * For all ТК to use empty array or use some TKs, 
			 * for example: 
			 *  $this->providerKeys  = array();
			 *  $this->providerKeys  = array('cdek','cse','dpd','ozon');
			 *  $this->providerKeys  = array('boxberry', 'cdek', 'b2cpl');
			 */
			$this->providerKeys  = $this->get_provider_keys();
			
			$this->pickupDate 	 = $this->get_pickup_date();
			$this->pickupTypes 	 = array(1,2);
			$this->deliveryTypes = array(1,2);
			$this->places 		 = $this->get_places($package);
			$this->assessedCost	 = $this->get_assessed_cost($package);
			$this->includeFees 	 = $this->get_include_fees();
			$this->extraParams 	 = array();
			$this->customCode 	 = Options\WP_ApiShip_Options::get_siteurl();
			
			$payment_method = isset( $post_data['payment_method'] ) ? $post_data['payment_method'] : '';
			$this->codCost	= $this->get_codcost($payment_method);
		}
		
		/**
		 * Get request.
		 *
		 * @since 1.0.0
		 */
		public function get_request() {
			
			$request = array(
				'to' 				=> $this->to,
				'from' 				=> $this->from,
				'places' 			=> $this->places,
				#'pickupDate' 		=> $this->pickupDate,
				'pickupTypes' 		=> $this->pickupTypes,
				'deliveryTypes' 	=> $this->deliveryTypes,
				'providerKeys' 		=> $this->providerKeys,
				'assessedCost' 		=> $this->assessedCost,
				'codCost' 			=> $this->codCost,
				'includeFees'		=> $this->includeFees,
				'timeout'			=> $this->timeout,
				'skipTariffRules'	=> $this->skipTariffRules,
				'extraParams'		=> $this->extraParams,
				'promoCode'			=> $this->promoCode,
				'customCode'		=> $this->customCode,
			);

			return $request;
		}

		/**
		 * Get provider keys.
		 *
		 * @since 1.1.0
		 */	
		protected function get_provider_keys() {
			
			if ( Options\WP_ApiShip_Options::is_use_selected_providers() ) {
				$selected_providers = Options\WP_ApiShip_Options::get_selected_providers();
				if ( is_array($selected_providers) && ! empty($selected_providers) ) {
					return $selected_providers;
					
				}
				return array();
			}
			
			/**
			 * Return empty array.
			 */
			return array();
		}
		
		/**
		 * Get address string.
		 *
		 * @see function `get_address_string` in wp-apiship\includes\api\class-wp-apiship-order-sender.php
		 *
		 * @since 1.0.0
		 */	
		protected function get_address_string( $country_code, $post_index, $city ) {
				
			$address_string = array();
			
			/*
			if ( trim($this->region) != trim($this->city) ) {
				$address_string[] = $this->region;
				$address_string[] = $this->city;
			} else {
				$address_string[] = $this->city;
			}
			// */
			
			if ( $this->warehouse_address_use == 'no' ) {
				// $address_string[] = Options\WP_ApiShip_Options::get_wc_option( 'woocommerce_store_address', false, false );
			} else {
				$address_string[] = Options\WP_ApiShip_Options::get_wc_option( 'wp_apiship_warehouse_address', false, false );
			}
			
			if ( count($address_string) > 1 ) {
				return implode( ' ', $address_string );
			}
			
			return implode( '', $address_string );
		}

		/**
		 * Get codCost.
		 *
		 * @see `constructor` in wp-apiship\includes\api\class-wp-apiship-order-cost.php
		 *
		 * @since 1.0.0
		 */
		protected function get_codcost($payment_method) {
			
			/**
			 * Cash on delivery (наложенный платеж).
			 */
			$cod = true;
			
			if ( 
				in_array(
					$payment_method, 
					array(
						Options\WP_ApiShip_Options::WC_PAYMENT_DIRECT_BANK_TRANSFER
					) 
				)
			) {
				$cod = false;
			}

			/**
			 * Now we have 1 cargo place (грузоместо).
			 *
			 * @todo add option to handle multiple cargo places.
			 * 
			 */
			foreach( $this->places[0] as $place ) {
				
				if ( $cod ) {
					// $this->codCost = $this->codCost + $place->cost;
					$cod_cost = $this->assessedCost;
				} else {
					$cod_cost = 0;
				}
			}

			return $cod_cost;	
		}
		
		/**
		 * Get includeFees.
		 *
		 * @since 1.0.0
		 */
		protected function get_include_fees() {
			
			$include_fees = Options\WP_ApiShip_Options::get_wc_option( 'wp_apiship_include_fees', 'no', false );

			if ( $include_fees === 'no' ) {
				return false;
			}
			return true;
		}
	
		/**
		 * Get pickupDate.
		 *
		 * @since 1.0.0
		 */
		protected function get_pickup_date() {
			$date = new DateTime('now', new DateTimeZone( wp_timezone_string() ) );
			return $date->format('Y-m-d');
		}

		/**
		 * Get assessedCost.
		 *
		 * @since 1.0.0		 
		 */		
		protected function get_assessed_cost($package) {

			global $woocommerce;  

			if (!$woocommerce->cart) {
				return 0;
			}
			
			/**
			 * @see woocommerce\includes\class-wc-cart.php
			 * 
			 * @return result like `9999.9`
			 */
			return ceil( $woocommerce->cart->get_cart_contents_total() );
		}

		/**
		 * Get default place params if it set
		 */
		protected function get_default_places()
		{
			$length = WP_ApiShip_Options::get_wc_option('wp_apiship_place_length', '', false);	
			$height = WP_ApiShip_Options::get_wc_option('wp_apiship_place_height', '', false);
			$width = WP_ApiShip_Options::get_wc_option('wp_apiship_place_width', '', false);
			
			$placeWeight = WP_ApiShip_Options::get_wc_option('wp_apiship_place_weight', '', false);
			$packWeight = WP_ApiShip_Options::get_wc_option('wp_apiship_place_package_weight', '', false);

			$length = intval($length);
			$height = intval($height);
			$width = intval($width);
			$weight = intval($placeWeight) + intval($packWeight);
			
			if ($length === 0 || $height === 0 || $width === 0 || $weight === 0) {
				return array();
			}

			$defaultPlace = (object) array(
				'length' => $length,
				'height' => $height,
				'width' => $width,
				'weight' => $weight
			);

			return array($defaultPlace);
		}
		
		/**
		 * Get places.
		 *
		 * @since 1.0.0		 
		 */
		protected function get_places($package) {
			
			if ( ! is_array( $package['contents'] ) ) {
				/**
				 * @todo check out empty response.
				 */
				return array();
			}

			/**
			 * Check if default place params are set
			 */
			$defaultPlaces = $this->get_default_places();
			if (!empty($defaultPlaces)) {
				return $defaultPlaces;
			}

			/**
			 * Get weight from woocommerce cart.
			 */
			// global $woocommerce;
			// $weight = ceil( wc_get_weight( $woocommerce->cart->cart_contents_weight, 'g' ) );
			
			$products = array();
			
			foreach( $package['contents'] as $key=>$item ) {

				$product = $item['data'];

				if ( is_object($product) ) {

					$product_id = $product->get_id();
					
					/**
					 * Dimension.
					 */
					$products[ $product_id ] = array(
						'product_id' => $product_id,		
						'length' 	 => $this->get_dimension('length', $product),		
						'width'  	 => $this->get_dimension('width', $product),
						'height' 	 => $this->get_dimension('height', $product),					
						'weight' 	 => $this->get_weight($product),
						'quantity'   => $item['quantity']
					);
				}					
			}
			
			$places = $this->calculate_dimensions($products);
			
			// @debug
			// output $products to log
			// output $places to log
			
			return $places;
		}
		
		/**
		 * Get dimension in cm.
		 *
		 * @see `get_dimension` function in wp-apiship\includes\api\class-wp-apiship-order-places.php
		 *
		 * @since 1.0.0
		 */
		protected function get_dimension( $dimension, $product ) {

			$value = 0;
			
			switch( $dimension ) :
				case 'height' :
					$option = 'wp_apiship_height';
					$value 	= $product->get_height();
					$default_value = Options\WP_ApiShip_Options::ITEM_HEIGHT;
					break;
				case 'width' :
					$option = 'wp_apiship_width';
					$value 	= $product->get_width();
					$default_value = Options\WP_ApiShip_Options::ITEM_WIDTH;
					break;
				case 'length' :
					$option = 'wp_apiship_length';
					$value 	= $product->get_length();
					$default_value = Options\WP_ApiShip_Options::ITEM_LENGTH;				
					break;
			endswitch;				
			
			if ( empty($value) ) {

				$value = Options\WP_ApiShip_Options::get_wc_option(
					$option, 
					$default_value,
					false
				);
				
				if ( empty($value) ) {
					$value = $default_value;
				}
				
			} else {
				
				$wc_dimension_unit = \get_option( 'woocommerce_dimension_unit' );

				/**
				 * Convert dimension to cm.
				 */				
				switch( $wc_dimension_unit ) :
					case 'm' :
						$value = $value * 100;
						break;
					case 'cm' :
						// default value;
						break;
					case 'mm' :
						$value = $value / 10;
						break;
					case 'in' :
						// @todo
						break;
					case 'yd' :
						// @todo			
						break;
				endswitch;				
			}
			
			/**
			 * @since 1.3.0 Using `ceil` to prevent fractional dimensions.
			 */
			return ceil( $value * 1 );
		}

		/**
		 * Get product weight in gramms.
		 *
		 * @see `get_weight` function in wp-apiship\includes\api\class-wp-apiship-order-places.php
		 *
		 * @since 1.0.0
		 */		
		protected function get_weight( $product ) {

			$weight = $product->get_weight();

			if ( empty($weight) ) {
				
				$weight = Options\WP_ApiShip_Options::get_wc_option(
					'wp_apiship_weight', 
					Options\WP_ApiShip_Options::ITEM_WEIGHT,
					false
				);
				
				if ( empty($weight) ) {
					$weight = Options\WP_ApiShip_Options::ITEM_WEIGHT;
				}
				
			} else {
				
				$wc_weight_unit = \get_option( 'woocommerce_weight_unit' );
				
				switch( $wc_weight_unit ) :
					case 'kg' :
						$weight = $weight * 1000;
						break;
					case 'lbs' :
						// @todo
						break;
					case 'oz' :
						// @todo
						break;
				endswitch;
			}
			
			return $weight;
		}
		
		/**
		 * Calculate place dimensions.
		 * 
		 * @since 1.0.0
		 *
		 * @return array
		 */
		protected function calculate_dimensions( $products ) {
			
			$length   = 0;
			$width    = 0;
			$height   = 0;
			$weight   = 0;
			
			foreach( $products as $product ) {
				
				$quantity = $product['quantity'] * 1;	
				
				if ( $quantity < 1 ) {
					// @todo what to do?
				}
				
				/**
				 * Let's get max length.
				 */ 
				if ( (int) $product['length'] > $length ) {
					$length = $product['length'];
				}

				/**
				 * Let's get max width.
				 */ 
				if ( (int) $product['width'] > $width ) {
					$width = $product['width'];
				}

				/**
				 * Let's sum height.
				 */ 
				$height = $height + ($product['height'] * $quantity);

				/**
				 * Let's sum weight.
				 */	
				$weight = $weight + ($product['weight'] * $quantity);
			}
			
			/**
			 * @since 1.3.0 Using `ceil` to prevent fractional dimensions.
			 */
			$place = new stdClass();
			$place->length = ceil($length);
			$place->width  = ceil($width);
			$place->height = ceil($height);
			$place->weight = $weight;			
			
			return array(
				$place
			);			
		}
	}
	
endif;

# --- EOF