<?php
/**
 * File: class-wp-apiship-order-places.php
 *
 * @package WP ApiShip
 *
 * @since 1.0.0
 */

use WP_ApiShip\Options;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists('WP_ApiShip_Order_Places') ) :

	/**
	 * To get info about validate Order 
	 * @see https://api.apiship.ru/doc/#/orders/validateOrder
	 */
	class WP_ApiShip_Order_Places {

		/**
		 * Высота единицы товара в сантиметрах.
		 *
		 * example: 45
		 *
		 * @var int
		 */		 
		protected $height = 0;

		/**
		 * Длина единицы товара в сантиметрах.
		 *
		 * example: 30
		 *
		 * @var int
		 */		
		protected $length = 0;

		/**
		 * Ширина единицы товара в сантиметрах.
		 *
		 * example: 20
		 *
		 * @var int
		 */			
		protected $width = 0;

		/**
		 * Вес единицы товара в граммах.
		 *
		 * example: 20
		 *
		 * @var int
		 */
		protected $weight = 0;

		/**
		 * Номер места в информационной системе клиента.
		 *
		 * example: 123421931239
		 *
		 * @var string
		 */		
		protected $placeNumber = '';
		
		/**
		 * Штрихкод места.
		 *
		 * example: 800028197737
		 *
		 * @var string
		 */			
		protected $barcode = '';

		/**
		 * Массив товаров.
		 *
		 * @var array
		 */		
		protected $items = array();
		
		/**
		 * Constructor.
		 */		
		public function __construct($request, WC_Order $wc_order) {
	
			/**
			 * Cash on delivery (наложенный платеж).
			 *
			 * @see constructor in wp-apiship\includes\api\class-wp-apiship-order-cost.php
			 */
			$cod_cost = true;
			$payment_method = $wc_order->get_payment_method();
			
			if ( 
				in_array(
					$payment_method, 
					array(
						Options\WP_ApiShip_Options::WC_PAYMENT_DIRECT_BANK_TRANSFER
					) 
				)
			) {
				$cod_cost = false;
			}
	
			/**
			 * Get products.
			 */
			$wc_order_items = $wc_order->get_items( 
				array('line_item')  
			);
			
			foreach( $wc_order_items as $wc_order_item ) {
				$this->items[] = $this->get_item( $wc_order_item, $cod_cost, $wc_order );
			}

			$sizes = $this->getPlacesSizes($this->items);

			$this->length = $sizes['length'];
			$this->width = $sizes['width'];
			$this->height = $sizes['height'];
			$this->weight = $sizes['weight'];
			
			/**
			 * Set custom dimensions if they exist.
			 */
			$properties = array( 
				'length', 
				'width', 
				'height',
				'weight'
			);
			
			foreach( $properties as $property ) {
				if ( isset( $request['custom-'.$property] ) && (int) $request['custom-'.$property] > 0 ) {
					$this->$property = intval($request['custom-'.$property]);
				}
				
			}
		}
		
		/**
		 * Get places sizes.
		 *
		 * @since 1.6.0
		 *
		 * return array
		 */
		public function getPlacesSizes(): array
		{
			$sizes = [
				'weight' => 0,
				'height' => 0,
				'length' => 0,
				'width'  => 0,
			];

			foreach (($this->items ?? []) as $orderPlace) {
				$dims = [$orderPlace->height ?: 0, $orderPlace->width ?: 0, $orderPlace->length ?: 0];
				sort($dims);
				$sizes['height'] += $dims[0];
				$sizes['length'] = max($sizes['length'], $dims[1]);
				$sizes['width'] = max($sizes['width'], $dims[2]);
				$sizes['weight'] += $orderPlace->weight ?: 0;
			}

			return $sizes;
		}

		/**
		 * Get single item.
		 *
		 * @since 1.0.0
		 *
		 * return object
		 */
		protected function get_item($wc_order_item, $cod_cost, $wc_order) {

			$product = $wc_order_item->get_product();
			
			$item = new stdClass();

			/**
			 * Длина единицы товара в сантиметрах.
			 *
			 * example: 30
			 *
			 * number($float)
			 */	
			$item->length = $this->get_dimension( 'length', $product );

			/**
			 * Ширина единицы товара в сантиметрах.
			 *
			 * example: 20
			 *
			 * number($float)
			 */
			$item->width = $this->get_dimension( 'width', $product );

			/**
			 * Высота единицы товара в сантиметрах.
			 *
			 * example: 45
			 *
			 * number($float)
			 */			
			$item->height = $this->get_dimension( 'height', $product );
			
			/**
			 * Вес единицы товара в граммах.
			 *
			 * example: 45
			 *
			 * number($float)
			 */
			$item->weight = $this->get_weight($product);
			
			/**
			 * Артикул товара.
			 *
			 * example: '1189.0'
			 *
			 * string
			 */			
			$item->articul = $product->get_sku();
			
			/**
			 * Код маркировки (UTF-8).
			 *
			 * example: 010290000046994521AK-rO?H!hC2(M\u001D91003A\u001D92cYTu3sTj82KJR3+6hVtQyAfa5Zf6Q2alfJEnwe2RIv4GAWVy2GUptk7P1NYxRsIgsTJi+Wgg+K3dncPELDJ9Ag==
			 *
			 * string
			 */			
			$item->markCode = '';
			
			/**
			 * Наименование товара.
			 *
			 * example: Товар 1
			 *
			 * string
			 */
			$item->description = $product->get_name();

			/**
			 * Кол-во товара. Если указан markCode, то кол-во не может быть > 1.
			 *
			 * example: 2
			 *
			 * integer($int32)
			 */			
			$item->quantity = $wc_order_item->get_quantity();

			/**
			 * Заполняется только при частичной доставке и показывает сколько единиц товара выкуплено.
			 *
			 * example: 2
			 *
			 * integer($int32)
			 */				
			// $item->quantityDelivered = $wc_order_item->get_quantity();

			/**
			 * Оценочная стоимость единицы товара в рублях.
			 *
			 * example: 50
			 *
			 * number($float)
			 * 
			 * @see `get_item_subtotal` in woocommerce\includes\abstracts\abstract-wc-order.php
			 */
			$item->assessedCost = $wc_order->get_item_subtotal( $wc_order_item, false, true );

			/**
			 * Стоимость единицы товара с учетом НДС в рублях.
			 *
			 * example: 30
			 *
			 * number
			 * 
			 * @see `get_item_subtotal` in woocommerce\includes\abstracts\abstract-wc-order.php
			 */
			$item->cost = 0; 
			if ( $cod_cost ) { 
				$item->cost = $wc_order->get_item_subtotal( $wc_order_item, false, true );
			}
			
			/**
			 * Процентная ставка НДС:
			 *
			 * '-1'  - Без НДС
			 *	'0'  - НДС 0%
			 *	'10' - НДС 10%
			 *	'20' - НДС 20%
			 * 
			 * default: -1
			 *
			 * number
			 */	
			// $item->costVat = -1;
			
			/**
			 * Штрихкод на товаре.
			 *
			 * example: 1234567890123
			 *
			 * string
			 */				
			// $item->barcode = '';

			/**
			 * Наименование компании поставщика / продавца товара.
			 *
			 * maxLength: 255
			 * example: ООО "Тест"
			 *
			 * string
			 */
			// $item->companyName = '';
			
			/**
			 * ИНН поставщика / продавца товара.
			 *
			 * maxLength: 12
			 * pattern: ^([0-9]{10}|[0-9]{12})$
			 * example: 1234567890
			 *
			 * string
			 */			
			// $item->companyInn = '';

			return $item;	
		}
	
		/**
		 * Get places.
		 *
		 * return array
		 */	
		public function get_places() {
			
			$places = array(
				'height' 	  => $this->height,
				'length' 	  => $this->length * 1,
				'width'  	  => $this->width * 1,
				'weight' 	  => $this->weight,
				#'placeNumber' => $this->placeNumber,
				#'barcode'	  => $this->barcode,
				'items'		  => $this->items,	
			);
			
			return $places;
		}

		/**
		 * Get dimension in cm.
		 *
		 * @see `get_dimension` function in wp-apiship\includes\api\class-wp-apiship-calculator-request.php
		 * 
		 * @since 1.0.0
		 */
		protected function get_dimension( $dimension, $product ) {

			$value = 0;
			
			switch( $dimension ) :
				case 'height' :
					$option 	   = 'wp_apiship_height';
					$value 		   = $product->get_height();
					$default_value = Options\WP_ApiShip_Options::ITEM_HEIGHT;
					break;
				case 'width' :
					$option 	   = 'wp_apiship_width';
					$value 		   = $product->get_width();
					$default_value = Options\WP_ApiShip_Options::ITEM_WIDTH;
					break;
				case 'length' :
					$option 	   = 'wp_apiship_length';
					$value 		   = $product->get_length();
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
		 * @see `get_weight` function in wp-apiship\includes\api\class-wp-apiship-calculator-request.php
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
			
			return $weight * 1;
		}		
	}
	
endif;

# --- EOF