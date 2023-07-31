<?php
/**
 * File: class-wp-apiship-connection.php
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

if ( ! class_exists('WP_ApiShip_Connection') ) :

	class WP_ApiShip_Connection {

		/**
		 * Код службы доставки.
		 *
		 * example: 100
		 *
		 * @var string
		 */
		protected $providerKey = '';
		
		/**
		 * Название подключения.
		 *
		 * example: "Основное подключение к службе доставки"
		 *
		 * @var string
		 */		
		protected $name = '';		

		/**
		 * Своя для компании процентная ставка страховки (%) - перекрывает общую для СД.
		 *
		 * @var number
		 */		
		protected $insuranceRate = 0;
	
		/**
		 * Своя для компании процентная ставка кассового обслуживания (%) - перекрывает общую для СД.
		 *
		 * @var number
		 */		
		protected $cashServiceRate = 0;
		
		/**
		 * Параметры соединения.
		 *
		 * @var object
		 */		
		protected $connectParams = null; // ??		
		
		/**
		 * Тип соединения.
		 *
		 * example:
		 * 	0 - никогда не использовать базовое подключение ApiShip;
		 * 	1 - подключение будет использовать базовое подключение ApiShip, т.е. передавать connectParams не нужно;
		 *  2 - будет использовано базовое подключение, если в процессе калькуляции по текущим параметрам подключения возникнет ошибка;
		 *
		 * @var integer
		 */			
		protected $isUseBaseConnect = 1;		
		
		/**
		 * Constructor.
		 */		
		public function __construct($request) {
			$this->providerKey  = $request['providerKey'];
			$this->name		 	= esc_html__('Новое соединение для', 'wp-apiship').' '.$request['providerKey'];
		}	

		/**
		 * Get new connection.
		 *
		 * @since 1.0.0
		 */
		public function get() {
			
			$connection = array(
				'providerKey' 	   => $this->providerKey,
				'name' 			   => $this->name,
				'insuranceRate'    => $this->insuranceRate,
				'cashServiceRate'  => $this->cashServiceRate,
				#'connectParams'    => $this->connectParams,
				'isUseBaseConnect' => $this->isUseBaseConnect,		
			);
			
			// @todo ??
			// if ( $this->isUseBaseConnect != 1 ) {
				
				$this->connectParams = array();
				$this->connectParams['additionalProp1'] = 'string';
				$this->connectParams['additionalProp2'] = 'string';
				$this->connectParams['additionalProp3'] = 'string';
				
				/*
				$this->connectParams = new stdClass();
				$this->connectParams->additionalProp1 = 'string';
				$this->connectParams->additionalProp2 = 'string';
				$this->connectParams->additionalProp3 = 'string';
				// */
				$connection['connectParams'] = $this->connectParams;
			// }
			
			return $connection;
		}		
	
	}
	
endif;

# --- EOF