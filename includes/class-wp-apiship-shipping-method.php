<?php
/**
 * File: class-wp-apiship-shipping-method.php
 *
 * @package WP ApiShip
 *
 * @since 1.0.0
 */

use	WP_ApiShip\Options,
	WP_ApiShip\HTTP,
	WP_ApiShip\Options\WP_ApiShip_Options,
	WP_ApiShip\WP_ApiShip_Core;

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
		
		public $admin_rates = [];

		/**
		 * Constructor.
		 *
		 * @param int $instance_id id.
		 */
		public function __construct($instance_id = 0, $is_admin = false) {
			
			$this->id                 = Options\WP_ApiShip_Options::SHIPPING_METHOD_ID;
			$this->instance_id        = absint( $instance_id );
			$this->method_title       = esc_html__('ApiShip integrator', 'apiship');
			$this->method_description = esc_html__('Select tariffs from various providers.', 'apiship');
			$this->supports           = array(
				'shipping-zones',
				'instance-settings',
			);
			$this->is_admin = $is_admin;
			// $this->selectedPointData = (object) array();
			// if (isset($_COOKIE['selectedPointData'])) {
			// 	$this->selectedPointData = json_decode(stripcslashes($_COOKIE['selectedPointData']));
			// }

			if ($this->is_admin === false) {
				$this->init();
			}
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

			/**
			 * Remove action for sanitize rate label.
			 * Important for using html inside label data.
			 *
			 * @since 1.4.0
			 */
			remove_action('woocommerce_shipping_rate_label', 'sanitize_text_field');
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

			$is_admin = $this->is_admin;
			
			if ( ! class_exists( 'WP_ApiShip_Calculator_Request' ) ) {
				include_once dirname( __FILE__ ) . '/api/class-wp-apiship-calculator-request.php';
			}

			if (isset($_POST['security']) and ! wp_verify_nonce($_POST['security'], 'update-shipping-method')) {
				// die;
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

			$request_hash = 'wp_apiship_cache_' . md5( wp_json_encode($request) );			

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
						'body' 	  => wp_json_encode($request),
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

			if ($is_admin === false) {
				$point_display_mode = intval(Options\WP_ApiShip_Options::get_option('point_out_display_mode', Options\WP_ApiShip_Options::DEFAULT_POINT_OUT_DISPLAY_MODE));
			} else {
				$point_display_mode = 2;
			}

			$providers_data = WP_ApiShip_Core::get_providers_data(true, false, false, true);

			$tariffsToRates = [];
			$selectedExists = false;
			$selectedProviderKey = null;
			$selectedMethodId = null;

			foreach( $delivery_types as $delivery_type ) {
				$is_delivery_to_point = false;
				if ($delivery_type === Options\WP_ApiShip_Options::DELIVERY_TO_POINT) {
					$is_delivery_to_point = true;
				}

				foreach($response_body->$delivery_type as $tariffGroupKey => $tariff_group) {
					
					$pickup_types = [];
					$providerData = $providers_data[$tariff_group->providerKey]->data;

					if (!empty($providerData['pickup_types'])) {
						$pickup_types = $providerData['pickup_types'];
					}

					foreach($tariff_group->tariffs as $tariff) {
						$tariff->methodId = $this->get_rate_id($tariff_group->providerKey . ':' . $tariff->tariffId);
						$tariff->isDeliveryToPoint = $is_delivery_to_point;
						$tariff->providerKey = $tariff_group->providerKey;
						$tariff->providerName = WP_ApiShip_Options::get_provider_name($tariff_group->providerKey);
						$tariff->deliveryType = $delivery_type;
						$tariff->tariffGroupKey = $tariffGroupKey;
						$tariff->isSelected = false;
						$tariff->isCached = false;
						$tariff->pointInId = null;
						$tariff->cachedData = (object) [];

						if ($tariff->isDeliveryToPoint === true) {
							/**
							 * Filter by pickup types and set pointInId.
							 *
							 * @since 1.4.0
							 */
							if (!empty($pickup_types) and count($pickup_types) === 1) {
								$pickupTypeKey = $pickup_types[0];
								if (!in_array($pickupTypeKey, $tariff->pickupTypes)) {
									continue;
								}
								if (intval($pickupTypeKey) === 2 and !empty($providerData['pointInId']['pickup'])) {
									$tariff->pointInId = $providerData['pointInId']['pickup']['pointId'];
								}
							}

							/**
							 * Set selected point data.
							 *
							 * @since 1.5.0
							 */		
							$selectedData = WP_ApiShip_Core::getSelectedPointData($tariff->tariffId, $tariff->methodId);
							if (is_object($selectedData)) {
								$tariff->isCached = true;
								$tariff->cachedData = $selectedData;
												
								$tariff->pointName = $selectedData->name;
								$tariff->pointAddress = $selectedData->address;
								$tariff->isSelected = $selectedData->is_selected;

								if ($selectedData->is_selected === true) {
									$selectedExists = true;
									$selectedMethodId = $tariff->methodId;
									$selectedProviderKey = $tariff->providerKey;
								}
							}
						}

						$tariffsToRates[] = $tariff;
					}
				}		
			}

			$existedRates = [];

			foreach($tariffsToRates as $tariff) {

				$currentDeliveryType = $tariff->deliveryType;
				$currentTariffGroupKey = $tariff->tariffGroupKey;
				$tariffGroup = $response_body->$currentDeliveryType[$currentTariffGroupKey];

				/**
				 * Create tariffs list.
				 *
				 * @since 1.5.0
				 */
				$tariffList = [];

				/**
				 * Filter methods by point display mode.
				 *
				 * @since 1.5.0
				 */
				if ($tariff->isDeliveryToPoint === true) {
					if ($point_display_mode === 3) {
						if ($selectedExists === true and $tariff->methodId !== $selectedMethodId or !empty($existedRates)) {
							continue;
						}
					}

					if ($point_display_mode === 2) {
						if ($selectedExists === true and $tariff->methodId !== $selectedMethodId and $tariff->providerKey === $selectedProviderKey or !empty($existedRates[$tariff->providerKey])) {
							continue;
						}
					}

					if ($point_display_mode !== 1) {
						$tariffList = self::get_tariff_list($point_display_mode, $providers_data, $response_body, $tariff->deliveryType, $tariffGroup);

						if ($tariff->isCached === false) {
							foreach($tariffList as $innerTariff) {
								if ($innerTariff->deliveryCost < $tariff->deliveryCost) {
									$tariff->deliveryCost = $innerTariff->deliveryCost;
								}
								if ($innerTariff->daysMin < $tariff->daysMin) {
									$tariff->daysMin = $innerTariff->daysMin;
								}
							}
						}
					}
				}
				
				/**
				 * Get label data by user template.
				 *
				 * @since 1.4.0
				 */
				$label = $this->get_label_data($tariff, $providers_data, $tariff->isDeliveryToPoint, $point_display_mode);
				
				/**
				 * Set meta data array
				 */
				$meta_data = array(
					'tariffId' 	 	 	=> $tariff->tariffId,
					'tariffName'	 	=> $tariff->tariffName,
					'tariffProviderKey' => $tariff->providerKey,
					'daysMin' 			=> $tariff->daysMin,
					'daysMax' 			=> $tariff->daysMax,
					'integrator' 		=> Options\WP_ApiShip_Options::INTEGRATOR,
					'integratorOrder'	=> Options\WP_ApiShip_Options::INTEGRATOR_ORDER_INIT_VALUE,
					'tariffList'		=> wp_json_encode($tariffList),
					'tariff' 			=> $this->get_tariff_data($tariff),
					'methodId' 			=> $tariff->methodId,
					'places'			=> wp_json_encode($request['places']),
					'pointInId'			=> $tariff->pointInId,
					#'radioHidden' 	 	=> false, // @see Options\WP_ApiShip_Options::is_dropdown_selector()
				);

				$rate_args = array(
					'id'        => $tariff->methodId,
					'label'     => $label,
					'cost'      => $tariff->deliveryCost,
					'package'   => $package,
					'meta_data' => $meta_data
				);
				
				if ($is_admin === false) {
					/**
					 * @see `add_rate` method in woocommerce\includes\abstracts\abstract-wc-shipping-method.php
					 */ 
					$this->add_rate($rate_args);
				} else {
					$this->admin_rates[] = $rate_args;
				}

				if ($tariff->isDeliveryToPoint === true) {
					$existedRates[$tariff->providerKey][] = $tariff->tariffId;
				}
			}
		}

		protected function get_tariff_list($point_display_mode, $providers_data, $response_body, $delivery_type, $tariff_group)
		{
			$tariffList = [];

			if ($point_display_mode === 2) {

				$pickup_types = [];
				$providerData = $providers_data[$tariff_group->providerKey]->data;

				if (!empty($providerData['pickup_types'])) {
					$pickup_types = $providerData['pickup_types'];
				}

				foreach($tariff_group->tariffs as $tariff_to_list) {
					$c_rate_id = $this->get_rate_id($tariff_group->providerKey . ':' . $tariff_to_list->tariffId);
					$tariff_to_list->methodId = $c_rate_id;
					$tariff_to_list->providerKey = $tariff_group->providerKey;
					$tariff_to_list->providerName = WP_ApiShip_Options::get_provider_name($tariff_group->providerKey);

					if (!empty($pickup_types) and count($pickup_types) === 1) {
						$pickupTypeKey = $pickup_types[0];
						if (!in_array($pickupTypeKey, $tariff_to_list->pickupTypes)) {
							continue;
						}
					}

					$tariffList[] = $tariff_to_list;
				}
			} else if ($point_display_mode === 3) {
				foreach($response_body->$delivery_type as $tariff_to_list_group) {
					
					$pickup_types = [];
					$providerData = $providers_data[$tariff_to_list_group->providerKey]->data;

					if (!empty($providerData['pickup_types'])) {
						$pickup_types = $providerData['pickup_types'];
					}

					foreach($tariff_to_list_group->tariffs as $tariff_to_list) {

						$c_rate_id = $this->get_rate_id($tariff_to_list_group->providerKey . ':' . $tariff_to_list->tariffId);
						$tariff_to_list->methodId = $c_rate_id;
						$tariff_to_list->providerKey = $tariff_to_list_group->providerKey;
						$tariff_to_list->providerName = WP_ApiShip_Options::get_provider_name($tariff_to_list_group->providerKey);
						
						if (!empty($pickup_types) and count($pickup_types) === 1) {
							$pickupTypeKey = $pickup_types[0];
							if (!in_array($pickupTypeKey, $tariff_to_list->pickupTypes)) {
								continue;
							}
						}

						$tariffList[] = $tariff_to_list;
					}
				}
			}
			return $tariffList;
		}

		/**
		 * Get label data by delivery points template settings.
		 *
		 * @since 1.4.0
		 */
		protected function get_label_data($tariff, $providers_data, $is_delivery_to_point = true, $point_display_mode = 1)
		{
			$template = Options\WP_ApiShip_Options::get_wc_option( 'points_template', Options\WP_ApiShip_Options::DEFAULT_POINTS_TEMPLATE, null); 
			
			$deliveryTypes = Options\WP_ApiShip_Options::get_delivery_types();
			$deliveryTypeKey = $tariff->deliveryTypes[0];
			$type = $deliveryTypes[$deliveryTypeKey];

			if (!empty($providers_data) && !empty($providers_data[$tariff->providerKey])) {
				$name = $providers_data[$tariff->providerKey]->name;
			} else {
				$name = $tariff->providerKey;
			}
      
			$pointName = '';
			if (isset($tariff->pointName)) {
			  $pointName = $tariff->pointName;
			}
			
			$pointAddress = '';
			if (isset($tariff->pointAddress)) {
			  $pointAddress = $tariff->pointAddress;
			}
			
			$tariffName = '';
			if (isset($tariff->tariffName)) {
			  $tariffName = $tariff->tariffName;
			}
			
			$isCached = false;
			if (isset($tariff->isCached)) {
			  $isCached = $tariff->isCached;
			}

			$variables = [
				'type' => $type,
				'company' => $name,
				'tariff' => $tariffName,
				'name' => "<span class='pointName'>$pointName</span>",
				'address' => "<span class='pointAddress'>$pointAddress</span>",
				'time' => $this->get_label_suffix($tariff, $is_delivery_to_point, $point_display_mode)
			];

			if ($is_delivery_to_point === true and $isCached === false) {
				if ($point_display_mode === 2) {
					$variables['tariff'] = '';
				} else if ($point_display_mode === 3) {
					$variables['tariff'] = '';
					$variables['company'] = '';
				}
			}

			if ($this->is_admin === true) {
				$variables['name'] = '';
				$variables['address'] = '';
			}

			foreach ($variables as $key => $value) {
				$templateVar = '%' . $key;
				if (stripos($template, $templateVar) !== false) {
					$template = str_replace($templateVar, $value, $template);
				}
			}

			if ($this->is_admin === false and $is_delivery_to_point === true and $point_display_mode !== 1 and $isCached === false) {
				$template .= ', цена от';
			}

			return $template;
		}
		
		/**
		 * Get tariff data.
		 *
		 * @since 1.0.0
		 */
		protected function get_tariff_data($tariff) {
			return wp_json_encode($tariff);
		}
		
		/**
		 * Get label suffix.
		 *
		 * @since 1.0.0
		 */
		protected function get_label_suffix($tariff, $is_delivery_to_point = true, $point_display_mode = 1) {
			
			$label_suffix = '';
			
			if ($is_delivery_to_point === true and $point_display_mode !== 1 and $tariff->isCached === false) {
				
				switch($tariff->daysMax) {
					case 1 :
						$label_suffix = 'срок от '.$tariff->daysMin.' дня';
					default:
						$label_suffix = 'срок от '.$tariff->daysMin.' дней';
						break;
				}

			} else if ( $tariff->daysMin == $tariff->daysMax ) {
				
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