<?php
/**
 * File: class-wp-apiship-order-cost.php
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

if ( ! class_exists('WP_ApiShip_Order_Cost') ) :

	class WP_ApiShip_Order_Cost {
		
		/**
		 * Оценочная стоимость / сумма страховки (в рублях).
		 *
		 * example: 100
		 *
		 * number($float)
		 */
		protected $assessedCost = 0;
		
		/**
		 * Стоимость доставки с учетом НДС (в рублях).
		 *
		 * example: 200
		 *
		 * number($float)
		 */		
		protected $deliveryCost = 0;
		
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
		protected $deliveryCostVat = -1;
		
		/**
		 * Сумма наложенного платежа с учетом НДС (в рублях).
		 *
		 * example: 260
		 * default: 0
		 *
		 * number($float)
		 */		
		protected $codCost = 0;

		/**
		 * Флаг для указания стороны, которая платит за услуги доставки (0-отправитель, 1-получатель).
		 *
		 * example: false
		 *
		 * boolean($boolean)
		 */			
		protected $isDeliveryPayedByRecipient = false;	
		
		/**
		 * Constructor.
		 */		
		public function __construct($request, WC_Order $wc_order, $places) {
	
			/**
			 * Cash on delivery (наложенный платеж).
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
			 * @see `get_codcost` function in wp-apiship\includes\api\class-wp-apiship-calculator-request.php
			 */
			foreach( $places[0]['items'] as $item ) {
				$this->assessedCost = $this->assessedCost + $item->assessedCost * $item->quantity;
				if ( $cod_cost ) {
					$this->codCost = $this->codCost + $item->cost * $item->quantity;
				} else {
					$this->codCost = 0;
				}
			}
			
			/**
			 * Set deliveryCost.
			 */			
			if ( isset($request['deliveryCost']) ) {
				$this->deliveryCost = $request['deliveryCost'];
				if ( $cod_cost ) {
					$this->codCost = $this->codCost + $this->deliveryCost;
				}
			}			
		}	

		/**
		 * Get cost.
		 *
		 * @since 1.0.0
		 */
		public function get_cost() {
			
			$cost = array(
				'assessedCost' 				 => $this->assessedCost,
				'deliveryCost' 				 => $this->deliveryCost,
				#'deliveryCostVat' 			 => $this->deliveryCostVat,
				'codCost' 		  			 => $this->codCost,
				'isDeliveryPayedByRecipient' => $this->isDeliveryPayedByRecipient,				
			);
			
			return $cost;
		}
	}
	
endif;

# --- EOF