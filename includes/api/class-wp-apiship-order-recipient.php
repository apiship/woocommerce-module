<?php
/**
 * File: class-wp-apiship-order-recipient.php
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

if ( ! class_exists('WP_ApiShip_Order_Recipient') ) :

	class WP_ApiShip_Order_Recipient {

		protected $countryCode = Options\WP_ApiShip_Options::WС_DEFAULT_COUNTRY;
		protected $postIndex = ''; 	 #'105062';
		protected $region = ''; #'Москва';
		protected $area = '';
		protected $city = ''; #'Москва';
		protected $cityGuid = ''; #'0c5b2444-70a0-4932-980c-b4dc0d3f02b5';
		protected $street = ''; #'Машкова';
		protected $house = ''; #'21';
		protected $block = '';
		protected $office = '';
		protected $lat = 0.0000; #55.755297,
		protected $lng = 0.0000; #37.653810,
		protected $addressString = ''; #'г Москва, ул Машкова, д 21';
		protected $companyName = ''; #'ООО "Тест"';
		protected $companyInn = ''; #'1234567890';
		protected $contactName = ''; #'Иванов Иван Иванович';
		protected $phone = ''; #'79250001115';
		protected $email = ''; #'test@test.com';
		protected $comment = '';
		
		/**
		 * Constructor.
		 */		
		public function __construct($request, WC_Order $wc_order) {

			$this->countryCode 	 = $wc_order->get_shipping_country();
			$this->postIndex	 = $wc_order->get_shipping_postcode();
			$this->region		 = $wc_order->get_shipping_city();
			$this->area			 = '';
			$this->city			 = $wc_order->get_shipping_city();
			$this->cityGuid		 = '';
			$this->street		 = $wc_order->get_shipping_address_1() . ' ' . $wc_order->get_shipping_address_2();
			$this->house		 = '';
			$this->block		 = '';
			$this->office		 = '';
			$this->lat			 = 0.0000; #55.755297,
			$this->lng			 = 0.0000; #37.653810,
			// $this->addressString = '';
			$this->companyName	 = '';
			$this->companyInn 	 = '';
			$this->contactName	 = $this->get_contact_name($wc_order);
			$this->phone		 = $wc_order->get_billing_phone();
			$this->email		 = $wc_order->get_billing_email();
			$this->comment	 	 = '';
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

			$address_string[] = $this->street;
			
			return implode( ' ', $address_string );
		}

		/**
		 * Get contact name.
		 *
		 * @since 1.0.0
		 */	
		protected function get_contact_name(WC_Order $wc_order) {
			return $wc_order->get_shipping_first_name() . ' ' . $wc_order->get_shipping_last_name();
		}
		
		/**
		 * Get recipient data.
		 *
		 * @since 1.0.0
		 */
		public function get_recipient() {
			
			$recipient = array(
				'countryCode'	=> $this->countryCode,
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
				#'email'			=> $this->email,
				'comment'		=> $this->comment,		
			);

			if ( ! empty( $this->area ) ) {
				$recipient['area'] = $this->area;
			}

			if ( ! empty( $this->cityGuid ) ) {
				$recipient['cityGuid'] = $this->cityGuid;
			}				

			if ( ! empty( $this->street ) ) {
				$recipient['street'] = $this->street;
			}	

			if ( ! empty( $this->house ) ) {
				$recipient['house'] = $this->house;
			}
	
			if ( ! empty( $this->block ) ) {
				$recipient['block'] = $this->block;
			}
	
			if ( ! empty( $this->office ) ) {
				$recipient['office'] = $this->office;
			}
	
			if ( (int) $this->lat != 0 ) {
				$recipient['lat'] = $this->lat;
			}
			
			if ( (int) $this->lng != 0 ) {
				$recipient['lng'] = $this->lng;
			}
			
			if ( ! empty( $this->companyName ) ) {
				$recipient['companyName'] = $this->companyName;
			}
	
			if ( ! empty( $this->companyInn ) ) {
				$recipient['companyInn'] = $this->companyInn;
			}
			
			if ( ! empty( $this->email ) ) {
				$recipient['email'] = $this->email;
			}	
	
			return $recipient;
		}
	}
	
endif;

# --- EOF