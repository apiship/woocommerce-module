<?php
/**
 * File: class-wp-apiship-order-returnaddress.php
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

if ( ! class_exists('WP_ApiShip_Order_ReturnAddress') ) :

	class WP_ApiShip_Order_ReturnAddress {

		/**
		 * If use warehouse address then set value to `yes`.
		 *
		 * @var string
		 */
		protected $warehouse_address_use = null;

		protected $countryCode = Options\WP_ApiShip_Options::WС_DEFAULT_COUNTRY;
		protected $postIndex = ''; #'105062';
		protected $region = ''; #'Москва';
		protected $area = '';
		protected $city = ''; #'Москва';
		protected $cityGuid = ''; #'0c5b2444-70a0-4932-980c-b4dc0d3f02b5';
		protected $street = ''; #'Машкова';
		protected $house = ''; #'21';
		protected $block = '';
		protected $office = '';
		protected $lat = 0.0000; #55.7647252;
		protected $lng = 0.0000; #37.6537218;
		protected $addressString = ''; #'г Москва, ул Машкова, д 21';
		protected $companyName = ''; #'ООО "Тест"';
		protected $companyInn = ''; #'1234567890';
		protected $contactName = ''; #'Иванов Иван Иванович';
		protected $phone = ''; #'79250001115';
		protected $email = ''; # 'test@test.com';
		protected $comment = '';	
	
		/**
		 * Constructor.
		 */		
		public function __construct($request, WC_Order $wc_order) {

			$this->warehouse_address_use = 
				Options\WP_ApiShip_Options::get_wc_option( 'wp_apiship_warehouse_address_use', 'no', false );
			
			if ( $this->warehouse_address_use == 'no' ) {
				
				$country_code 	= Options\WP_ApiShip_Options::get_wc_option( 'woocommerce_default_country', false, false );
				$post_index   	= Options\WP_ApiShip_Options::get_wc_option( 'woocommerce_store_postcode', false, false );
				$city 		  	= Options\WP_ApiShip_Options::get_wc_option( 'woocommerce_store_city', false, false );
				$address_string = Options\WP_ApiShip_Options::get_wc_option( 'woocommerce_store_address', false, false );
			
			} else {
			
				$country_code 	= Options\WP_ApiShip_Options::get_wc_option( 'wp_apiship_warehouse_country', false, false );
				$post_index   	= Options\WP_ApiShip_Options::get_wc_option( 'wp_apiship_warehouse_index', false, false );
				$city 		  	= Options\WP_ApiShip_Options::get_wc_option( 'wp_apiship_warehouse_city', false, false );
				$address_string = Options\WP_ApiShip_Options::get_wc_option( 'wp_apiship_warehouse_address', false, false );				
			
			}
			
			$this->countryCode 	 = $country_code;
			$this->postIndex	 = $post_index;
			$this->region 		 = $city;
			$this->area			 = '';
			$this->city			 = $city;
			$this->cityGuid		 = '';
			$this->street	     = '';
			$this->house		 = '';
			$this->block		 = '';
			$this->office		 = '';
			$this->lat			 = 0.0000;
			$this->lng			 = 0.0000;
			$this->addressString = $address_string;
			$this->companyName	 = '';
			$this->companyInn	 = '';
			$this->contactName	 = $this->get_contact_name($wc_order);
			$this->phone		 = $this->get_phone($wc_order);
			$this->email 		 = '';
			$this->comment		 = '';
		
		}	  	
	
		/**
		 * Get contact name.
		 *
		 * @since 1.0.0
		 */	
		protected function get_contact_name(WC_Order $wc_order) {
			// @todo
			if ( $this->warehouse_address_use == 'no' ) {
				return 'Магазинов Иван Иванович';
			} else {
				return 'Складовский Марий Иванович';
			}	
		}

		/**
		 * Get contact phone.
		 *
		 * @since 1.0.0
		 */	
		protected function get_phone(WC_Order $wc_order) {
			// @todo			
			return '79251234567';
		}

		/**
		 * Get rerurn address data.
		 *
		 * @since 1.0.0
		 */		
		public function get_return_address() {
			
			$address = array(
				'countryCode' 	=> $this->countryCode,
				'postIndex'		=> $this->postIndex,
				'region'		=> $this->region,
				'area'			=> $this->area,
				'city'			=> $this->city,
				'cityGuid'		=> $this->cityGuid,
				'street'		=> $this->street,
				'house'			=> $this->house,
				'block'			=> $this->block,
				'office'		=> $this->office,
				'lat'			=> $this->lat,
				'lng'			=> $this->lng,
				'addressString'	=> $this->addressString,
				'companyName'	=> $this->companyName,
				'companyInn'	=> $this->companyInn,
				'contactName'	=> $this->contactName,
				'phone'			=> $this->phone,
				'email'			=> $this->email,
				'comment'		=> $this->comment,
			);
			
			return $address;
		}
	}
	
endif;

# --- EOF