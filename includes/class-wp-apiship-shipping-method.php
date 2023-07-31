<?php
/**
 * File: class-wp-apiship-shipping-method.php
 *
 * @package WP ApiShip
 *
 * @since 1.0.0
 */

use	WP_ApiShip\Options,
	WP_ApiShip\HTTP;
use WP_ApiShip\WP_ApiShip_Core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_ApiShip_Shipping_Method.
 */
if ( ! class_exists('WP_ApiShip_Shipping_Method') ) :

	/**
	 * @see parent class in woocommerce\includes\abstracts\abstract-wc-shipping-method.php
	 */
	class WP_ApiShip_Shipping_Method extends WC_Shipping_Method {
		
		/**
		 * Constructor.
		 *
		 * @param int $instance_id id.
		 */
		public function __construct( $instance_id = 0 ) {
			
			$this->id                 = Options\WP_ApiShip_Options::SHIPPING_METHOD_ID;
			$this->instance_id        = absint( $instance_id );
			$this->method_title       = esc_html__('ApiShip integrator', 'wp-apiship');
			$this->method_description = esc_html__('Select tariffs from various providers.', 'wp-apiship');
			$this->supports           = array(
				'shipping-zones',
				'instance-settings',
			);
			// $this->selectedPointData = (object) array();
			// if (isset($_COOKIE['selectedPointData'])) {
			// 	$this->selectedPointData = json_decode(stripcslashes($_COOKIE['selectedPointData']));
			// }
			$this->init();
		}
		
		/**
		 * Init variables.
		 *
		 * @since 1.0.0
		 */
		public function init() {
			
			$this->instance_form_fields = include 'settings/settings-shipping-method.php';

			foreach ( $this->instance_form_fields as $key => $settings ) {
				$this->{$key} = $this->get_option( $key );
			}

			add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
		}	

		/**
		 * Decode response body.
		 *
		 * @since 1.0.0
		 */
		public function body_decode( $body ) {
			
			$response = json_decode( $body );
			
			if ( is_null( $response ) ) {
				/**
				 * json cannot be decoded or the encoded data is deeper than the nesting limit.
				 */
				throw new Exception('JSON cannot be decoded or the encoded data is deeper than the nesting limit.');
			}
			
			return $response;	
		}

		/**
		 * Called to calculate shipping rates. Rates can be added using the add_rate() method.
		 *
		 * @since 1.0.0
		 *
		 * @param array $package Package array.
		 */
		public function calculate_shipping( $package = array() ) {
			
			if ( ! class_exists( 'WP_ApiShip_Calculator_Request' ) ) {
				include_once dirname( __FILE__ ) . '/api/class-wp-apiship-calculator-request.php';
			}

			$calc = new \WP_ApiShip_Calculator_Request( $package, $_POST );

			$request = $calc->get_request();
			
			/**
			 * We need send the request timeout in seconds.
			 * @see `http_request_timeout` filter in wp-includes\class-http.php
			 */
			$timeout = (int) $request['timeout'];
			if (  $timeout < 1000 ) {
				$timeout = 1;
			} else {
				$timeout = $timeout / 1000;
			}

			$request_hash = 'wp_apiship_cache_' . md5( json_encode($request) );			

			/**
			 * Init $response.
			 */			
			$response = false;
			
			/**
			 * Init $response_body.
			 *
			 * @since 1.3.0
			 */
			$response_body = false;
			
			if ( WP_ApiShip\WP_ApiShip_Core::is_bypass_shipping_cache() ) {
				/**
				 * Not response from cache in this case.
				 */
			} else {
				$response = get_transient($request_hash);
			}
			
			if ( ! $response ) {

				/**
				 * @see https://api.apiship.ru/doc/#/calculator/getCalculator
				 */				
				$response = HTTP\WP_ApiShip_HTTP::post(
					'calculator',
					array(
						'headers' => array(
							'Content-Type' => 'application/json'
						),
						'body' 	  => json_encode($request),
						'timeout' => $timeout,
					)
				);
		
				if ( is_wp_error( $response ) ) {
					WP_ApiShip\WP_ApiShip_Core::__log( $response, __CLASS__ .'::'. __FUNCTION__ );
					return;
				}		

				if ( ! is_array( $response ) || ! is_array( $response['response'] ) ) {
					return;
				}
	
				if ( wp_remote_retrieve_response_code($response) == HTTP\WP_ApiShip_HTTP::OK ) {
					
					try {
						$response_body = $this->body_decode( wp_remote_retrieve_body($response) );
					} catch ( Exception $e ) {
						WP_ApiShip\WP_ApiShip_Core::__log( $e->getMessage(), __CLASS__ .'::'. __FUNCTION__ );
						return;
					}
				
					/**
					 * Caching.
					 * 
					 * Revising @since 1.1.0
					 */
					if ( WP_ApiShip\WP_ApiShip_Core::is_bypass_shipping_cache() ) {
						/**
						 * Not caching.
						 */
					} else {
						set_transient( $request_hash, $response, DAY_IN_SECONDS );
					}					
				}
			
			} else {
				
				$response_body = $this->body_decode( wp_remote_retrieve_body($response) );
			}
			
			/**
			 * Logging in case empty `$response_body`.
			 *
			 * @since 1.3.0
			 */
			if ( ! $response_body ) {
				WP_ApiShip\WP_ApiShip_Core::__log( 'Not response body.', __CLASS__ .'::'. __FUNCTION__ );
				return;
			}
			
			$delivery_types = array(
				Options\WP_ApiShip_Options::DELIVERY_TO_DOOR,  //  'deliveryToDoor', 
				Options\WP_ApiShip_Options::DELIVERY_TO_POINT, //  'deliveryToPoint'
			);

			$providers_data = WP_ApiShip_Core::get_providers_data(true, false, false);

			foreach( $delivery_types as $delivery_type ) :

				foreach( $response_body->$delivery_type as $tariff_group_key => $tariff_group ) :
					
					$pickup_types = [];
					$providerData = $providers_data[$tariff_group->providerKey]->data;
					if (!empty($providerData['pickup_types'])) {
						$pickup_types = $providerData['pickup_types'];
					}

					foreach( $tariff_group->tariffs as $key => $tariff ) {
			
						/**
						 * Filter by pickup types and set pointInId.
						 *
						 * @since 1.4.0
						 */
						$pointInId = 0;
						if (!empty($pickup_types) and count($pickup_types) === 1) {
							$pickupTypeKey = $pickup_types[0];
							if (!in_array($pickupTypeKey, $tariff->pickupTypes)) {
								continue;
							}
							if (intval($pickupTypeKey) === 2 and !empty($providerData['pointInId']['pickup'])) {
								$pointInId = $providerData['pointInId']['pickup']['pointId'];
							}
						}
						
						/**
						 * Get label data by user template.
						 *
						 * @since 1.4.0
						 */
						$label = $this->get_label_data($tariff, $tariff_group, $providers_data);

						/**
						 * Remove action for sanitize rate label.
						 * Important for using html inside label data.
						 *
						 * @since 1.4.0
						 */
						remove_action('woocommerce_shipping_rate_label', 'sanitize_text_field');

						/**
						 * Set meta data array
						 */
						$meta_data = array(
							'tariffId' 	 	 	=> $tariff->tariffId,
							'tariffName'	 	=> $tariff->tariffName,
							'tariffProviderKey' => $tariff_group->providerKey,
							'daysMin' 			=> $tariff->daysMin,
							'daysMax' 			=> $tariff->daysMax,
							'integrator' 		=> Options\WP_ApiShip_Options::INTEGRATOR,
							'integratorOrder'	=> Options\WP_ApiShip_Options::INTEGRATOR_ORDER_INIT_VALUE,
							'tariff' 			=> $this->get_tariff_data($tariff),
							'places'			=> json_encode($request['places']),
							'pointInId'			=> $pointInId,
							#'radioHidden' 	 	=> false, // @see Options\WP_ApiShip_Options::is_dropdown_selector()
						);

						/**
						 * @see `add_rate` method in woocommerce\includes\abstracts\abstract-wc-shipping-method.php
						 */ 
						$this->add_rate(
							array(
								'id'        => $this->get_rate_id( $tariff_group->providerKey . ':' . $tariff->tariffId ),
								'label'     => $label,
								'cost'      => $tariff->deliveryCost,
								'package'   => $package,
								'meta_data' => $meta_data
							)
						);	
					}
					
				endforeach;			
			endforeach;
		}

		/**
		 * Get label data by delivery points template settings.
		 *
		 * @since 1.4.0
		 */
		protected function get_label_data($tariff, $tariff_group, $providers_data)
		{
			$template = Options\WP_ApiShip_Options::get_wc_option( 'points_template', Options\WP_ApiShip_Options::DEFAULT_POINTS_TEMPLATE, null); 
			
			$deliveryTypes = Options\WP_ApiShip_Options::get_delivery_types();
			$deliveryTypeKey = $tariff->deliveryTypes[0];
			$type = $deliveryTypes[$deliveryTypeKey];

			if (!empty($providers_data) && !empty($providers_data[$tariff_group->providerKey])) {
				$name = $providers_data[$tariff_group->providerKey]->name;
			} else {
				$name = $tariff_group->providerKey;
			}

			$labelTariff = $tariff->tariffId;

			$pointName = '';
			$pointAddress = '';

			$selectedData = WP_ApiShip_Core::getSelectedPointData($labelTariff);
			if (is_object($selectedData)) {
				$pointName = $selectedData->name;
				$pointAddress = $selectedData->address;
			}

			$variables = [
				'type' => $type,
				'company' => $name,
				'tariff' => $tariff->tariffName,
				'name' => "<span class='pointName'>$pointName</span>",
				'address' => "<span class='pointAddress'>$pointAddress</span>",
				'time' => $this->get_label_suffix($tariff)
			];

			foreach ($variables as $key => $value) {
				$templateVar = '%' . $key;
				if (stripos($template, $templateVar) !== false) {
					$template = str_replace($templateVar, $value, $template);
				}
			}

			return $template;
		}
		
		/**
		 * Get tariff data.
		 *
		 * @since 1.0.0
		 */
		protected function get_tariff_data($tariff) {
			return json_encode($tariff);
		}
		
		/**
		 * Get label suffix.
		 *
		 * @since 1.0.0
		 */
		protected function get_label_suffix( $tariff ) {
			
			$label_suffix = '';
			
			if ( $tariff->daysMin == $tariff->daysMax ) {
						
				/**
				 * Deleted ()
				 *
				 * @since 1.4.0
				 */
				switch($tariff->daysMax) {
					case 1 :
						$label_suffix = 'срок '.$tariff->daysMax.' день';
						break;				
					case 2 :
					case 3 :
					case 4 :
						$label_suffix = 'срок '.$tariff->daysMax.' дня';
						break;				
					default:
						$label_suffix = 'срок '.$tariff->daysMax.' дней';
						break;								
				}
				
			} else {
				
				switch($tariff->daysMax) {
					case 2 :
					case 3 :
					case 4 :
						$label_suffix = 'срок '.$tariff->daysMin.'-'.$tariff->daysMax.' дня';
						break;				
					default:
						$label_suffix = 'срок '.$tariff->daysMin.'-'.$tariff->daysMax.' дней';
						break;								
				}
				
			}
			
			return $label_suffix;
		}			
	}
	
endif;	

# --- EOF