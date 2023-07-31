<?php
/**
 * File: class-wp-apiship-order-sender.php
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

if ( ! class_exists('WP_ApiShip_Order_Sender') ) :

	class WP_ApiShip_Order_Sender {

		protected $countryCode 	 = Options\WP_ApiShip_Options::WС_DEFAULT_COUNTRY;
		protected $postIndex 	 = '';
		protected $region 		 = '';
		protected $area 		 = '';
		protected $city 		 = '';
		protected $cityGuid 	 = '';
		protected $street 		 = '';
		protected $house 		 = '';
		protected $block 		 = '';
		protected $office 		 = '';
		protected $lat 			 = 0.0000;
		protected $lng 			 = 0.0000;
		protected $addressString = '';
		protected $companyName 	 = '';
		protected $companyInn 	 = '';
		protected $contactName 	 = '';
		protected $phone 		 = '';
		protected $email 		 = '';
		protected $comment 		 = '';
		protected $brandName 	 = '';

		/**
		 * If use warehouse address then set value to `yes`.
		 *
		 * @var string
		 */
		protected $warehouse_address_use = null;
	
		/**
		 * Constructor.
		 */		
		public function __construct($request, WC_Order $wc_order) {
	
			$this->warehouse_address_use = Options\WP_ApiShip_Options::get_wc_option( 
				'wp_apiship_warehouse_address_use', 
				'no', 
				false 
			);
			
			$wc_default_country = Options\WP_ApiShip_Options::get_wc_option( 
				'woocommerce_default_country', 
				false, 
				false 
			);
			
			if ( $this->warehouse_address_use == 'no' ) {
				
				$country_code 	= $wc_default_country;
				$post_index   	= Options\WP_ApiShip_Options::get_wc_option( 'woocommerce_store_postcode', false, false );
				$city 		  	= Options\WP_ApiShip_Options::get_wc_option( 'woocommerce_store_city', false, false );
				// $address_string = Options\WP_ApiShip_Options::get_wc_option( 'woocommerce_store_address', false, false );
				
			} else {
				
				$country_code = Options\WP_ApiShip_Options::get_wc_option( 
					'wp_apiship_warehouse_country', 
					$wc_default_country, 
					false 
				);
				
				if ( trim($country_code) == '' ) {
					$country_code = $wc_default_country;
				}				
			
				$country_code 	= Options\WP_ApiShip_Options::get_wc_option( 'wp_apiship_warehouse_country', false, false );
				$post_index   	= Options\WP_ApiShip_Options::get_wc_option( 'wp_apiship_warehouse_index', false, false );
				$city 		  	= Options\WP_ApiShip_Options::get_wc_option( 'wp_apiship_warehouse_city', false, false );
				// $address_string = Options\WP_ApiShip_Options::get_wc_option( 'wp_apiship_warehouse_address', false, false );				
			
			}

			$this->countryCode   = $country_code;
			$this->postIndex	 = $post_index;
			$this->region		 = $city;
			$this->area 		 = '';
			$this->city 		 = $city;
			$this->cityGuid		 = '';
			$this->street 		 = '';
			$this->house 		 = '';
			$this->block 		 = '';
			$this->office 		 = '';
			$this->lat			 = 0.0000;
			$this->lng 			 = 0.0000;
			$this->companyName 	 = '';
			$this->companyInn 	 = '';
			$this->contactName 	 = $this->get_contact_name($request, $wc_order);
			$this->phone 		 = $this->get_phone($request, $wc_order);
			$this->email  		 = '';
			$this->comment  	 = '';
			// $this->brandName 	 = Options\WP_ApiShip_Options::BRAND;
			$this->addressString = $this->get_address_string();
		}

		/**
		 * Get address string.
		 *
		 * @since 1.0.0
		 */	
		protected function get_address_string() {
				
			$address_string = array(
				$this->postIndex,
			);
			
			if ( trim($this->region) != trim($this->city) ) {
				$address_string[] = $this->region;
				$address_string[] = $this->city;
			} else {
				$address_string[] = $this->city;
			}
			
			if ( $this->warehouse_address_use == 'no' ) {

				$address_2 = Options\WP_ApiShip_Options::get_wc_option( 
					'woocommerce_store_address_2', 
					'', 
					false 
				);
				
				if ( ! empty($address_2) ) {
					$address_2 = ' '.$address_2;
				}

				$address_string[] = Options\WP_ApiShip_Options::get_wc_option( 
					'woocommerce_store_address', 
					false, 
					false 
				) . $address_2;
			
			} else {
				
				$address_2 = Options\WP_ApiShip_Options::get_wc_option( 
					'wp_apiship_warehouse_address_2', 
					'', 
					false 
				);
				
				if ( ! empty($address_2) ) {
					$address_2 = ' '.$address_2;
				}
				
				$address_string[] = Options\WP_ApiShip_Options::get_wc_option( 
					'wp_apiship_warehouse_address', 
					false, 
					false 
				) . $address_2;
			}
			
			return implode( ' ', $address_string );
		}

		/**
		 * Get contact phone.
		 *
		 * @since 1.0.0
		 */	
		protected function get_phone($request, WC_Order $wc_order) {
			
			if ( ! empty( $request['phone'] ) ) {
				return $request['phone'];
			}
			
			return '';
		}
 
		/**
		 * Get contact name.
		 *
		 * @since 1.0.0
		 */	
		protected function get_contact_name($request, WC_Order $wc_order) {
	
			if ( ! empty( $request['contactName'] ) ) {
				return $request['contactName'];
			}
			
			return '';
		}

		/**
		 * Get sender data.
		 *
		 * @since 1.0.0
		 */		
		public function get_sender() {
	
			$sender = array(
				'countryCode' 	=> $this->countryCode,
				'postIndex'		=> $this->postIndex,
				'region'		=> $this->region,
				#'area'			=> $this->area,
				'city'			=> $this->city,
				#'cityGuid'		=> $this->cityGuid,
				#'street'		=> $this->street,
				#'house'		=> $this->house,
				#'block'		=> $this->block,
				#'office'		=> $this->office,
				#'lat'			=> $this->lat,
				#'lng'			=> $this->lng,
				'addressString'	=> $this->addressString,
				#'companyName'	=> $this->companyName,
				#'companyInn'	=> $this->companyInn,
				'contactName'	=> $this->contactName,
				'phone'			=> $this->phone,
				#'email'		=> $this->email,
				'comment'		=> $this->comment,
				#'brandName'		=> $this->brandName,		
			);
			
			if ( ! empty( $this->area ) ) {
				$sender['area'] = $this->area;
			}

			if ( ! empty( $this->cityGuid ) ) {
				$sender['cityGuid'] = $this->cityGuid;
			}				

			if ( ! empty( $this->street ) ) {
				$sender['street'] = $this->street;
			}	

			if ( ! empty( $this->house ) ) {
				$sender['house'] = $this->house;
			}
	
			if ( ! empty( $this->block ) ) {
				$sender['block'] = $this->block;
			}
	
			if ( ! empty( $this->office ) ) {
				$sender['office'] = $this->office;
			}
	
			if ( (int) $this->lat != 0 ) {
				$sender['lat'] = $this->lat;
			}
			
			if ( (int) $this->lng != 0 ) {
				$sender['lng'] = $this->lng;
			}
			
			if ( ! empty( $this->companyName ) ) {
				$sender['companyName'] = $this->companyName;
			}
	
			if ( ! empty( $this->companyInn ) ) {
				$sender['companyInn'] = $this->companyInn;
			}
			
			if ( ! empty( $this->email ) ) {
				$sender['email'] = $this->email;
			}
			
			return $sender;
		}
	}
	
endif;

# --- EOF