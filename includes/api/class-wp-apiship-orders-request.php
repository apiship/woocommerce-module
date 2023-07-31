<?php
/**
 * File: class-wp-apiship-orders-request.php
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

if ( ! class_exists('WP_ApiShip_Orders_Request') ) :

	/**
	 * @see https://api.apiship.ru/doc/#/orders/addOrder
	 */
	class WP_ApiShip_Orders_Request {

		protected $order;
		protected $cost;
		protected $sender;
		protected $recipient;
		protected $returnAddress = null;
		protected $places;
		protected $extraParams = null;
		
		/**
		 * Shipping meta data.
		 */
		protected $shipping_meta = array();

		/**
		 * Constructor.
		 */		
		public function __construct( $request = false ) {

			if ( ! $request ) {
				return;
			}
			
			$wc_order_id = (int) $request['postOrderID'];

			if ( ! $wc_order_id ) {
				return;	
			}
			
			/**
			 * WC_Order $wc_order.
			 */
			$wc_order = wc_get_order( $wc_order_id );
			
			$shipping_methods = $wc_order->get_shipping_methods();
			
			/**
			 * @see woocommerce\includes\admin\meta-boxes\views\html-order-items.php
			 * @see woocommerce\includes\admin\meta-boxes\views\html-order-shipping.php
			 * @see woocommerce\includes\admin\meta-boxes\views\html-order-item-meta.php
			 */ 
			foreach( $shipping_methods as $method ) {
				/**
				 * @see woocommerce\includes\class-wc-order-item.php
				 */
				$meta_data = $method->get_formatted_meta_data('');
			}
			
			foreach ( $meta_data as $meta_id=>$meta ) {
				$this->shipping_meta[ $meta->key ] = $meta;
			}
			
			/**
			 * places.
			 */
			if ( ! class_exists('WP_ApiShip_Order_Places') ) {
				require_once('class-wp-apiship-order-places.php');
			}
			$_places = new WP_ApiShip_Order_Places($request, $wc_order);
			$this->places[] = $_places->get_places();

			/**
			 * Main order.
			 */	
			if ( ! class_exists('WP_ApiShip_Order') ) {
				require_once('class-wp-apiship-order.php');
			}	
			$_order = new WP_ApiShip_Order($request, $this);	
			$this->order = $_order->get_order();
			
			/**
			 * sender.
			 */
			if ( ! class_exists('WP_ApiShip_Order_Sender') ) {
				require_once('class-wp-apiship-order-sender.php');
			}			
			$_sender = new WP_ApiShip_Order_Sender($request, $wc_order);
			$this->sender = $_sender->get_sender();
			
			/**
			 * recipient.
			 */
			if ( ! class_exists('WP_ApiShip_Order_Recipient') ) {
				require_once('class-wp-apiship-order-recipient.php');
			}			 
			$_recipient = new WP_ApiShip_Order_Recipient($request, $wc_order);
			$this->recipient = $_recipient->get_recipient();
	
			/**
			 * returnAddress.
			 * @todo
			 */
			/* 
			if ( ! class_exists('WP_ApiShip_Order_ReturnAddress') ) {
				require_once('class-wp-apiship-order-returnaddress.php');
			}			 
			$_returnAddress = new WP_ApiShip_Order_ReturnAddress($request, $wc_order);
			$this->returnAddress = $_returnAddress->get_return_address();
			*/
			
			/**
			 * cost.
			 */
			if ( ! class_exists('WP_ApiShip_Order_Cost') ) {
				require_once('class-wp-apiship-order-cost.php');
			}			 
			$_cost = new WP_ApiShip_Order_Cost($request, $wc_order, $this->places);
			$this->cost = $_cost->get_cost();	
	
			/**
			 * extraParams.
			 * @todo how to get it?
			 */			
			// $_extra_params = new stdClass();
			// $_extra_params->key = 'testParam';
			// $_extra_params->value = 'testValue';
			// $this->extraParams[] = $_extra_params;
		}	
		
		/**
		 * Get request.
		 *
		 * @since 1.0.0
		 */
		public function get_request() {
			
			$request = array(
				#'platform'  	=> $this->platform,
				'order' 		=> $this->order,
				'cost' 			=> $this->cost,
				'sender' 		=> $this->sender,
				'recipient' 	=> $this->recipient,
				#'returnAddress' => $this->returnAddress,
				'places' 		=> $this->places,
				#'extraParams' 	=> $this->extraParams,
			);
			
			if ( ! is_null($this->returnAddress) ) {
				$request['returnAddress'] = $this->returnAddress;
			}

			if ( ! is_null($this->extraParams) ) {
				$request['extraParams'] = $this->extraParams;
			}

			return $request;
		}

		/**
		 * Get shipping meta.
		 *
		 * @since 1.0.0
		 */		
		public function get_shipping_meta() {
			return $this->shipping_meta;
		}
		
		/**
		 * Get places.
		 *
		 * @since 1.0.0
		 */		
		public function get_places() {
			return $this->places;
		}

		/**
		 * Get height.
		 *
		 * @since 1.0.0
		 */
		public function get_places_height() {
			return $this->places[0]['height'];
		}	

		/**
		 * Get length.
		 *
		 * @since 1.0.0
		 */		
		public function get_places_length() {
			return $this->places[0]['length'];
		}

		/**
		 * Get width.
		 *
		 * @since 1.0.0
		 */		
		public function get_places_width() {
			return $this->places[0]['width'];
		}

		/**
		 * Get weight.
		 *
		 * @since 1.0.0
		 */		
		public function get_places_weight() {
			return $this->places[0]['weight'];
		}
	}
	
endif;

# --- EOF