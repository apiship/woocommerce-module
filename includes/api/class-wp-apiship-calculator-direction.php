<?php
/**
 * File: class-wp-apiship-calculator-direction.php
 *
 * @package WP ApiShip
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_ApiShip\Options;

if ( ! class_exists('WP_ApiShip_Calculator_Direction') ) :

	class WP_ApiShip_Calculator_Direction {

		/**
		 * Код страны в соответствии с ISO 3166-1 alpha-2.
		 *
		 * @since 1.0.0
		 * @var string
		 */		
		protected $countryCode;
		
		/**
		 * Почтовый индекс.
		 *
		 * @since 1.0.0
		 * @var string
		 */				
		protected $index;

		/**
		 * Полный почтовый адрес. Если заполнен, то считается приоритетным, если не указан cityGuid.
		 *
		 * @since 1.0.0
		 * @var string
		 */			
		protected $addressString;
	
		/**
		 * Регион/Край/Область.
		 *
		 * @since 1.0.0
		 * @var string
		 */	
		protected $region;

		/**
		 * Название города (обязательно если не заполнен cityGuid).
		 *
		 * @since 1.0.0
		 * @var string
		 */		
		protected $city;

		/**
		 * ФИАС код города\поселения в базе fias.nalog.ru (обязательно, если не заполнен city).
		 *
		 * @since 1.0.0
		 * @var string
		 */			
		protected $cityGuid;
		
		/**
		 * Широта. Обязательно указывайте, если это доставка через такси, например, Яндекс.Доставка, Gett и д.р.
		 *
		 * @since 1.0.0
		 * @var float
		 */			
		protected $lat;

		/**
		 * Долгота. Обязательно указывайте, если это доставка через такси, например, Яндекс.Доставка, Gett и д.
		 *
		 * @since 1.0.0
		 * @var float
		 */			
		protected $lng;

		/**
		 * Constructor.
		 */			
		public function __construct( $args = array() ) {

			$defaults = array(
				'countryCode' => Options\WP_ApiShip_Options::get_wc_option( 
					'woocommerce_default_country', 
					Options\WP_ApiShip_Options::WС_DEFAULT_COUNTRY, 
					false 
				),
				'index' 		=> '',
				'addressString' => '',
				'region'		=> '',
				'city'			=> '',
				'cityGuid'		=> '',
				'lat'			=> 0.00,
				'lng'			=> 0.00,
			);
 
			$args = wp_parse_args( $args, $defaults );
			
			$this->countryCode 	 = $args['countryCode'];
			$this->index	   	 = $args['index'];
			$this->addressString = $args['addressString'];
			$this->region 		 = $args['region'];
			$this->city 		 = $args['city'];
			$this->cityGuid		 = $args['cityGuid'];
			$this->lat		 	 = $args['lat'];
			$this->lng		 	 = $args['lng'];
		}
	
		/**
		 * Return address.
		 *
		 * @since 1.0.0
		 */
		public function get() {
			
			$direction = array(
				'countryCode' 	=> $this->countryCode,
				'index' 	  	=> $this->index,
				'addressString' => $this->addressString,
				'region' 		=> $this->region,
				'city' 			=> $this->city,
				'cityGuid' 		=> $this->cityGuid,
				'lat' 			=> $this->lat,
				'lng' 			=> $this->lng,
			);
			
			if ( empty( $direction['cityGuid'] ) ) {
				unset( $direction['cityGuid'] );
			}

			if ( empty( $direction['lat'] ) || (int) $direction['lat'] == 0 ) {
				unset( $direction['lat'] );
			}
			
			if ( empty( $direction['lng'] ) || (int) $direction['lng'] == 0 ) {
				unset( $direction['lng'] );
			}
			
			return $direction;
		}
	}

endif;

# --- EOF