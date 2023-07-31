<?php
/**
 * File: class-wp-apiship-order.php
 *
 * @package WP ApiShip
 *
 * @since 1.0.0
 */

// use WP_ApiShip\Options;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists('WP_ApiShip_Order') ) :

	class WP_ApiShip_Order {
		
		/**
		 * Номер заказа в системе службы доставки. Если СД выдает диапазон номеров заказа.
		 *
		 * maxLength: 100
		 * example  : 11e51r6
		 *
		 * @var string
		 */
		protected $providerNumber = '';
		
		/**
		 * Дополнительный номер заказа в системе службы доставки.
		 * maxLength: 100
		 * example  : 21309812039812
		 *
		 * @var string
		 */		
		protected $additionalProviderNumber = '';
		
		/**
		 * Номер заказа в системе клиента.
		 *
		 * maxLength: 100
		 * example  : 6532-TR-23
		 *
		 * @var string
		 */			
		protected $clientNumber = '';
		
		/**
		 * Штрих-код.
		 *
		 * maxLength: 100
		 * example  : 123456
		 *
		 * @var string
		 */		
		protected $barcode = '';
		
		/**
		 * Комментарий.
		 *
		 * example: 123456
		 *
		 * @var string
		 */			
		protected $description = '';
		
		/**
		 * Код службы доставки.
		 *
		 * example: cdek
		 *
		 * @var string
		 */		
		protected $providerKey = '';
		
		/**
		 * ID подключения вашей компании к СД.
		 *
		 * example: 11102
		 *
		 * @var string
		 */
		protected $providerConnectId = null;

		/**
		 * Тип забора груза. 1 - отгрузка груза курьером; 2 - отгрузка груза на ПВЗ;
		 *
		 * example: 1
		 *
		 * @var integer
		 */
		protected $pickupType = 1;
		
		/**
		 * Тип доставки. 1 - доставка курьером; 2 - доставка на ПВЗ;
		 *
		 * example: 1
		 *
		 * @var integer
		 */		
		protected $deliveryType = 1;
		
		/**
		 * Тариф службы доставки по которому осуществляется доставка.
		 *
		 * example: 16
		 *
		 * @var integer
		 */		
		protected $tariffId = 0;

		/**
		 * ID точки приема заказа.
		 *
		 * example: 333
		 *
		 * @var integer
		 */		
		protected $pointInId = 0;
		
		/**
		 * ID точки выдачи заказа.
		 *
		 * example: 407
		 *
		 * @var integer
		 */			
		protected $pointOutId = 0;
		
		/**
		 * Предполагаемая дата передачи груза в службу доставки.
		 *
		 * example: 2022-03-15
		 *
		 * @var string($date)
		 */				
		protected $pickupDate = null;

		/**
		 * Начальное время забора груза.
		 *
		 * example: 09:00
		 * pattern: ^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$
		 *
		 * @var string
		 */		
		protected $pickupTimeStart = null;
		
		/**
		 * Конечное время забора груза.
		 *
		 * example: 18:00
		 * pattern: ^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$
		 *
		 * @var string
		 */		
		protected $pickupTimeEnd = null;
		
		/**
		 * Дата доставки.
		 *
		 * example: 2022-03-16
		 *
		 * @var string($date)
		 */			
		protected $deliveryDate = null;
		
		protected $deliveryTimeStart = null;
		
		protected $deliveryTimeEnd = null;
		
		/**
		 * Высота единицы товара в сантиметрах.
		 *
		 * example: 45
		 * 
		 * number($float)
		 */
		protected $height = 0;
		
		/**
		 * Длина единицы товара в сантиметрах.
		 *
		 * example: 30
		 * 
		 * number($float)
		 */
		protected $length = 0;
		
		/**
		 * Ширина единицы товара в сантиметрах.
		 *
		 * example: 20
		 * 
		 * number($float)
		 */
		protected $width  = 0;
		
		/**
		 * Вес единицы товара в граммах.
		 *
		 * example: 20
		 * 
		 * number($float)
		 */
		protected $weight = 0;
		
		/**
		 * Constructor.
		 */		
		public function __construct($request, WP_ApiShip_Orders_Request $orders_request) {
			
			/**
			 * Set shipping meta.
			 */
			$shipping_meta = $orders_request->get_shipping_meta();

			/**
			 * Set weight.
			 */
			$this->weight = $orders_request->get_places_weight();

			/**
			 * Set providerKey.
			 */			
			if ( isset($shipping_meta['tariffProviderKey']) ) {
				$this->providerKey = $shipping_meta['tariffProviderKey']->value;
			}

			/**
			 * Set pickupType.
			 */
			if ( ! empty( $request['pickupType'] ) ) {
				$this->pickupType = $this->get_pickup_type($request);
			}
	
			/**
			 * Set deliveryType.
			 */
			if ( ! empty( $request['deliveryType'] ) ) {
				$this->deliveryType = (int) $request['deliveryType'];
			}			 
			
			/**
			 * Set tariffId.
			 */
			$this->tariffId = $shipping_meta['tariffId']->value;

			/**
			 * Set providerConnectId.
			 */
			if ( ! empty( $request['providerConnectId'] ) ) {
				$this->providerConnectId = (int) $request['providerConnectId'];
			}
	
			/**
			 * Set pointInId.
			 */	
			if ($shipping_meta['pointInId']->value != 0) {
				$this->pointInId = (int) $shipping_meta['pointInId']->value;
			} else if (!empty( $request['pointInId'])) {
				$this->pointInId = (int) $request['pointInId'];
			}

			/**
			 * Set pointOutId.
			 */		
			if ( ! empty( $request['pointOutId'] ) ) {
				$this->pointOutId = (int) $request['pointOutId'];
			}

			/**
			 * Set clientNumber.
			 */			
			$this->clientNumber = $this->get_client_number($request, $shipping_meta);

			/**
			 * Set pickupDate.
			 */	
			$this->pickupDate = $this->get_pickup_date($request);
		
			/**
			 * Set deliveryDate
			 */			
			// $this->deliveryDate = $this->get_delivery_date();		

			/**
			 * Set description.
			 */
			$this->description = $this->get_description($request);
		}	 
		
		/**
		 * Get order.
		 *
		 * @since 1.0.0
		 */
		public function get_order() {
			
			$order = array(
				#'providerNumber' 		   => $this->providerNumber,
				#'additionalProviderNumber' => $this->additionalProviderNumber,
				'clientNumber' 			   => $this->clientNumber,
				#'barcode'				   => $this->barcode,
				'description'			   => $this->description,
				'providerKey'			   => $this->providerKey,
				'providerConnectId'		   => $this->providerConnectId,
				'pickupType'			   => $this->pickupType,
				'deliveryType'			   => $this->deliveryType,
				'tariffId'				   => $this->tariffId,
				#'height'				   => $this->height,
				#'length'				   => $this->length,
				#'width'				   => $this->width,
				'weight'				   => $this->weight,
			);

			if ( (int) $this->pointInId > 0 ) {
				$order['pointInId'] = $this->pointInId;
			}
	
			if ( (int) $this->pointOutId > 0 ) {
				$order['pointOutId'] = $this->pointOutId;
			}	

			/**
			 * Pickup date&time.
			 */
			if ( ! is_null( $this->pickupDate ) ) {
				$order['pickupDate'] = $this->pickupDate->format('Y-m-d');
			}
			
			if ( ! is_null( $this->pickupTimeStart ) ) {
				$order['pickupTimeStart'] = $this->pickupTimeStart;
			}
	
			if ( ! is_null( $this->pickupTimeEnd ) ) {
				$order['pickupTimeEnd'] = $this->pickupTimeEnd;
			}	

			/**
			 * Delivery date&time.
			 */
			if ( ! is_null( $this->deliveryDate ) ) {
				$order['deliveryDate'] = $this->deliveryDate->format('Y-m-d');
				// $order['deliveryDate'] = $this->deliveryDate;
			}

			if ( ! is_null( $this->deliveryTimeStart ) ) {
				$order['deliveryTimeStart'] = $this->deliveryTimeStart;
			}
			
			if ( ! is_null( $this->deliveryTimeEnd ) ) {
				$order['deliveryTimeEnd'] = $this->deliveryTimeEnd;
			}
			
			return $order;
		}

		/**
		 * Get description.
		 *
		 * @since 1.0.0
		 */
		protected function get_description($request) {

			$desc = '';

			if ( isset( $request['postOrderID'] ) && (int) $request['postOrderID'] > 0 ) {

				/**
				 * To get more info about notes:
				 * @see woocommerce\includes\admin\meta-boxes\class-wc-meta-box-order-notes.php
				 * @see woocommerce\includes\admin\meta-boxes\views\html-order-notes.php
				 */

				$args = array(
					'order_id' => $request['postOrderID'],
				);

				$notes = wc_get_order_notes( $args );
				
				if ( ! empty($notes) ) {
					foreach( $notes as $note ) {
						if ( $note->added_by != 'system' ) {
							$desc = $note->content;
							break;
						}
					}
				}
				
			}
			
			return $desc;
		}
		
		/**
		 * Generate clientNumber.
		 *
		 * @since 1.0.0
		 */
		protected function get_client_number($request, $meta) {
			return $request['postOrderID'];
		}

		/**
		 * Get pickupType.
		 *
		 * @since 1.0.0
		 */		
		protected function get_pickup_type($request) {
			
			$pickup_types = explode(',', $request['pickupType']);
			$pickup_type  = false;
			
			if ( count($pickup_types) == 2 ) {
				/**
				 * We have 2 pickup types: 1 - отгрузка груза курьером; 2 - отгрузка груза на ПВЗ;
				 * @see pickupType https://api.apiship.ru/doc/#/orders/validateOrder
				 * so, let's set pickup type as $pickup_types[1]. 
				 *
				 * @todo may add preset option for this one.
				 */
				$pickup_type = $pickup_types[1];
			
			} elseif ( count($pickup_types) == 1 ) {
				
				$pickup_type = $pickup_types[0];
			
			}
				
			return $pickup_type;
		}
		
		/**
		 * Get pickupDate.
		 *
		 * @see https://www.php.net/manual/ru/class.datetime.php
		 * @since 1.0.0
		 *
		 * @return DateTime|NULL object if the date is set or null if there is no date.
		 */
		protected function get_pickup_date($request) {
			
			if ( isset($request['pickupDate']) ) {
				
				/**
				 * We get pickupDate as string, so let's typecasting to DateTime object.
				 */
				$date = new DateTime( $request['pickupDate'] );
				
			} else {
			
				/**
				 * pickupDate set to now.
				 */
				$date = new DateTime('now', new DateTimeZone( wp_timezone_string() ) );
				
				// $date->add(new DateInterval('P1D'));
				$date->modify('+1 day');
			}
			
			return $date;
		}	

		/**
		 * Get deliveryDate.
		 *
		 * @see https://www.php.net/manual/ru/class.datetime.php
		 * @since 1.0.0
		 */		
		protected function get_delivery_date() {

			/**
			 * deliveryDate set to pickupDate + 1 day.
			 */			
			if ( is_null( $this->pickupDate ) ) {
				$this->pickupDate = $this->get_pickup_date();
			}
			$date = $this->pickupDate;
			// $date->add(new DateInterval('P1D'));
			$date->modify('+1 day');
			
			return $date;
		}
	}
  
endif;

# --- EOF