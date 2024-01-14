<?php
/**
 * File: class-wp-apiship-meta-boxes.php
 *
 * @package WP ApiShip
 * @subpackage Administration
 *
 * @since 1.0.0
 */
namespace WP_ApiShip\Admin;

use WP_ApiShip\HTTP\WP_ApiShip_HTTP;
use WP_ApiShip\Options,
	WP_ApiShip;
use WP_ApiShip\WP_ApiShip_Core;
use WP_ApiShip_Shipping_Method;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists('WP_ApiShip_Meta_Boxes') ) :

	class WP_ApiShip_Meta_Boxes {

		/**
		 * Metabox nonce key.
		 *
		 * @var string		 
		 */
		protected $wpapiship_box_nonce = 'wpapiship_box_nonce';

		/**
		 * Metabox action key.
		 *
		 * @var string
		 */		
		protected $wpapiship_box_action = 'wpapiship_box_action';
		
		/**
		 * Current WC Order.
		 */
		protected $order = null;

		/**
		 * Current WC Order meta data.
		 */		
		protected $meta_data = null;
		
		/**
		 * The point to receiving shipment.
		 *
		 * @var array
		 */
		protected $point_in = null;

		/**
		 * The point to issuing shipment.
		 *
		 * @var array
		 */		
		protected $point_out = null;

		/**
		 * Shipment places of store owner.
		 *
		 * @example 'store', 'warehouse'.
		 *
		 * @var array
		 */
		protected $shipment_places = array();
		
		/**
		 * Current tariff.
		 *
		 * @var object
		 */		
		public $tariff = null;

		/**
		 * Integrator order ID for current WC order.
		 *
		 * @var string | int
		 */
		protected $integrator_order_id = null;

		/**
		 * Admin shipping methods.
		 *
		 * @since 1.6.0
		 */
		public ?array $shipping_methods = null;
		
		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct( $path_to_loader ) {
			
			$this->shipment_places = Options\WP_ApiShip_Options::get_owner_point_types();
			
			/**
			 * The `Custom selected` place may be set in each order.
			 * It will be init later.
			 */
			$this->shipment_places[] = 'customSelected';
			
			add_action( 'add_meta_boxes', array( $this, 'on__add_meta_boxes' ), 10, 2 );			
			add_action( 'save_post', array( $this, 'on__save' ) );

			$this->set_tariff();
		}
		
		/**
		 * Save the meta when the post is saved.
		 *
		 * @param int $post_id The ID of the post being saved.
		 */
		public function on__save( $post_id ) {
	 
			/**
			 * We need to verify this came from the our screen and with proper authorization,
			 * because save_post can be triggered at other times.
			 */
	 
			// Check if our nonce is set.
			if ( ! isset( $_POST[ $this->wpapiship_box_nonce ] ) ) {
				return $post_id;
			}
	 
			$nonce = $_POST[ $this->wpapiship_box_nonce ];
	 
			// Verify that the nonce is valid.
			if ( ! wp_verify_nonce( $nonce, $this->wpapiship_box_action ) ) {
				return $post_id;
			}
	 
			/**
			 * If this is an autosave, our form has not been submitted,
			 * so we don't want to do anything.
			 */
			if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
				return $post_id;
			}
		}
		
		/**
		 * Add WP ApiShip meta boxes.
		 *
		 * @since 1.0.0
		 */
		public function on__add_meta_boxes( $post_type, $post ) {
	
			/**
			 * Limit meta box to certain post types.
			 */
			$post_types = array( 
				Options\WP_ApiShip_Options::WC_ORDER_POST_TYPE
			);
	 
			if ( ! in_array( $post_type, $post_types ) ) {
				return;
			}

			$this->order = $this->wc_get_order($post);	

			if ( ! WP_ApiShip\WP_ApiShip_Core::is_shipping_integrator( $this->order ) ) {
				return;
			}

			$line_items_shipping = $this->order->get_items('shipping');
			foreach ( $line_items_shipping as $item_id=>$item ) {
				/**
				 * $item is WC_Order_Item_Shipping Object.
				 */
				$shipping_order_item_id = $item_id;
			}

			/**
			 * @see woocommerce\includes\wc-order-item-functions.php
			 */
			$id = wc_get_order_item_meta( 
				$shipping_order_item_id, 
				Options\WP_ApiShip_Options::INTEGRATOR_ORDER_KEY
			);

			$this->integrator_order_id = false;
			if ( (int) $id > 0 ) {
				$this->integrator_order_id = $id;
			}

			$formatted_meta_data = $item->get_formatted_meta_data( '' );

			/**
			 * @see meta_data in wp-apiship\includes\class-wp-apiship-shipping-method.php
			 */
			$meta_data = array();
			foreach( $formatted_meta_data as $key=>$meta ) {
				$meta_data[$meta->key] = $meta;
			}
			
			$this->meta_data = $meta_data;
			
			unset( $formatted_meta_data );
			unset( $meta_data );

			add_meta_box(
                'wpapiship-order-metabox',
                esc_html__( 'ApiShip', 'wp-apiship' ),
                array( $this, 'render_content' ),
                Options\WP_ApiShip_Options::WC_ORDER_POST_TYPE,
                'advanced',
                'high'
            );
		}
					
		/**
		 * Admin shipping methods.
		 * 
		 * @since 1.6.0
		 */
		public function get_shipping_methods()
		{
			if (isset($this->shipping_methods)) {
				return $this->shipping_methods;
			}

			global $wpdb;

			$method_key = Options\WP_ApiShip_Options::SHIPPING_METHOD_ID;

			$instance_id = $wpdb->get_var( $wpdb->prepare( "SELECT instance_id FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE method_id = %s", $method_key ) );

			$instance_id = intval($instance_id);

			$order = $this->order;

			$shipping_method = new WP_ApiShip_Shipping_Method($instance_id, true);

			$items = $order->get_items();

			$package = array(
				'destination' => array(
					'country' => $order->get_shipping_country(),
					'state'    => $order->get_shipping_state(),
					'postcode' => $order->get_shipping_postcode(),
					'city'     => $order->get_shipping_city(),
				),
				'contents'    => array(),
				'user'        => array(
					'ID' => $order->get_customer_id(),
				),
				'address'     => $order->get_address(),
			);

			foreach ( $items as $item_id => $item ) {
				$product_id    = wc_get_order_item_meta( $item->get_id(), '_product_id', true );
				$variation_id  = wc_get_order_item_meta( $item->get_id(), '_variation_id', true );
				$quantity      = $item->get_quantity();
				$line_total    = wc_get_order_item_meta( $item->get_id(), '_line_total', true );
				$line_tax      = wc_get_order_item_meta( $item->get_id(), '_line_tax', true );

				$package['contents'][] = array(
					'product_id'  => $product_id,
					'variation_id'=> $variation_id,
					'quantity'    => $quantity,
					'line_total'  => $line_total,
					'line_tax'    => $line_tax,
				);
			}

			$shipping_method->calculate_shipping( $package );

			$this->shipping_methods = $shipping_method->admin_rates;

			return $this->shipping_methods;
		}
		
	    /**
		 * Save admin shipping method.
		 *
		 * @since 1.6.0
		 */
		public function save_admin_shipping()
		{
			
		}

	    /**
		 * Render Meta Box content.
		 *
		 * @since 1.0.0
		 *
		 * @param WP_Post $post The post object.
		 */
		public function render_content( $post ) {

			/**
			 * Add an nonce field so we can check for it later.
			 */
			wp_nonce_field( 
				$this->wpapiship_box_action, 
				$this->wpapiship_box_nonce
			);

			if ( $this->is_use_warehouse_address() ) {
				$point_in_select_type = 'warehouse';
			} else {
				$point_in_select_type = 'store';
			}
			
			ob_start();
			require_once( 'templates/order-metabox-content.php' );
			echo ob_get_clean();
		}

	    /**
		 * Get WC order.
		 *
		 * @since 1.0.0
		 *
		 * @param WP_Post $post The post object.
		 */		
		protected function wc_get_order($post) {
			if ( is_null( $this->order ) ) {
				$this->order = wc_get_order( $post->ID );
			}
			return $this->order;	
		}

		/**
		 * Get dimension of place.
		 *
		 * @since 1.0.0
		 */
		protected function get_place_dimension( $dimension = false, $order = false ) {
			
			if ( ! $dimension || false === $order ) {
				return false;
			}
			
			$places = Options\WP_ApiShip_Options::get_places( $this->order->get_id() );
			
			if ( empty( $places[$order] ) ) {
				return '';
			}

			if ( empty( $places[$order][$dimension] ) ) {
				return '';
			}
			
			return $places[$order][$dimension];
		}
		
		/**
		 * Get Sender Title.
		 *
		 * @since 1.0.0
		 */		
		public function get_sender_title() {
			
			$title = esc_html__('Отправитель','wp-apiship');
	
			if ( $this->integrator_order_exists() ) {
				
				/**
				 * Don't set shipment place.
				 */
			
			} else {
	
				$warehouse_address_use = Options\WP_ApiShip_Options::get_wc_option( 
					'wp_apiship_warehouse_address_use', 
					'no', 
					false 
				);

				if ( $warehouse_address_use === 'no' ) {
					$title .= ' (' . esc_html__('магазин','wp-apiship') . ')';
				} else {
					$title .= ' (' . esc_html__('склад','wp-apiship') . ')';
				}
			}				
			
			return $title;
		}

		/**
		 * Output Sender Title.
		 *
		 * @since 1.0.0
		 */	
		public function the_sender_title() {
			echo $this->get_sender_title();
		}	
	
	    /**
		 * Get sender address as a string.
		 *
		 * @since 1.0.0
		 *
		 * @return string.
		 */
		public function get_sender_address() {
			
			$warehouse_address_use = Options\WP_ApiShip_Options::get_wc_option( 
				'wp_apiship_warehouse_address_use', 
				'no', 
				false 
			);
			
			$address = array();
			
			if ( $warehouse_address_use === 'no' ) {
				
				/**
				 * Store address.
				 */
				$address['strongOpen'] = '<strong>'; 				
				$address['post_index'] = Options\WP_ApiShip_Options::get_wc_option( 'woocommerce_store_postcode', '', false );
				$address['city'] = Options\WP_ApiShip_Options::get_wc_option( 'woocommerce_store_city', '', false );
		
				$address['address'] = Options\WP_ApiShip_Options::get_wc_option( 
					'woocommerce_store_address', 
					'', 
					false 
				);	
				// $address['address'] = get_option( 'woocommerce_store_address' );
		
				$address['address_2'] = Options\WP_ApiShip_Options::get_wc_option( 
					'woocommerce_store_address_2', 
					'', 
					false 
				);
				$address['strongClose'] = '</strong>'; 				
				
			} else {

				/**
				 * Warehouse address.
				 */			
				$address['strongOpen'] = '<strong>'; 
				$address['post_index'] = Options\WP_ApiShip_Options::get_wc_option( 'wp_apiship_warehouse_index', '', false );
				$address['city'] = Options\WP_ApiShip_Options::get_wc_option( 'wp_apiship_warehouse_city', '', false );	

				$address['address'] = Options\WP_ApiShip_Options::get_wc_option( 
					'wp_apiship_warehouse_address', 
					'', 
					false 
				);	
		
				$address['address_2'] = Options\WP_ApiShip_Options::get_wc_option( 
					'wp_apiship_warehouse_address_2', 
					'', 
					false 
				);
				$address['strongClose'] = '</strong>'; 
			}			
			
			return implode(' ', $address);
		}
		
		/**
		 * Output Sender Address.
		 *
		 * @since 1.0.0
		 */
		public function the_sender_address() {
			echo $this->get_sender_address();
		}

		/**
		 * Get provider icon url.
		 *
		 * @test with no image provider.
		 * $icon_url = WP_ApiShip\WP_ApiShip_Core::get_provider_icon_url('zabberi');
		 *
		 * @since 1.0.0
		 */	
		public function get_provider_icon_url($provider_key) {
			return WP_ApiShip\WP_ApiShip_Core::get_provider_icon_url($provider_key);
		}

		/**
		 * Get store city.
		 *
		 * @since 1.0.0
		 */		
		public function get_store_city() {
			return Options\WP_ApiShip_Options::get_wc_option( 'woocommerce_store_city', false, false );
		}
	
		/**
		 * Get warehouse city.
		 *
		 * @since 1.0.0
		 */		
		public function get_warehouse_city() {
			return Options\WP_ApiShip_Options::get_wc_option( 'wp_apiship_warehouse_city', false, false );
		}

		/**
		 * Get order contact name.
		 *
		 * @since 1.0.0
		 */		
		public function get_contact_name() {
			return Options\WP_ApiShip_Options::get_order_meta(
				$this->order->get_id(),
				Options\WP_ApiShip_Options::POST_ORDER_CONTACT_NAME_META, 
				''
			);
		}

		/**
		 * Get order phone.
		 *
		 * @since 1.0.0 
		 */		
		public function get_phone() {
			return Options\WP_ApiShip_Options::get_order_meta(
				$this->order->get_id(),
				Options\WP_ApiShip_Options::POST_ORDER_PHONE_META, 
				''
			);
		}

		/**
		 * Get provider name.
		 * 
		 * @since 1.4.0
		 */	
		public function get_provider_name($provider_key) {
			return Options\WP_ApiShip_Options::get_provider_name($provider_key);
		}

		/**
		 * Get statuses.
		 * 
		 * @since 1.4.0
		 */	
		public function get_order_statuses() {
			return WP_ApiShip_Core::get_order_statuses($this->integrator_order_id);
		}

		/**
		 * Set points in data. 
		 *
		 * @since 1.0.0
		 */		
		protected function set_point_in() {

			/**
			 * Init.
			 */
			$this->point_in = [];
			
			/**
			 * Providers.
			 */
			$providers = Options\WP_ApiShip_Options::get_option( 
				'providers', 
				false, 
				false 
			);
			
			foreach( $this->shipment_places as $place ) {
				if ( ! empty( $providers[ $this->get_provider_key() ]['pointInId'][$place] ) ) {
					$this->point_in[$place] = $providers[ $this->get_provider_key() ]['pointInId'][$place];
				}
			}
			
			$custom_selected = Options\WP_ApiShip_Options::get_order_meta( 
				$this->order->get_id(),
				Options\WP_ApiShip_Options::POST_SHIPPING_TO_POINT_IN_META,
				false
			);
			
			if ( $custom_selected ) {
				$this->point_in['customSelected'] = $custom_selected;
			}
		}
		
		/**
		 * Get point in data. 
		 *
		 * @since 1.0.0
		 */	
		protected function get_point_in($shipment_place = 'store', $key = false) {
			
			if ( ! in_array( $shipment_place, $this->shipment_places ) ) {
				return false;
			}
		
			if ( is_null($this->point_in) ) {	
				$this->set_point_in();
			}
			
			if ( empty($this->point_in) ) {
				return false;
			}
			
			if ( 'customSelected' == $shipment_place )  {
				$_key = $key;
			} else {
				switch ($key):
					case 'address':
						$_key = 'pointAddress';
						break;
					case 'id':
					case 'ID':
						$_key = 'pointId';
						break;
					default:
						$_key = $key;
						break;
				endswitch;			
			}
				
			if ( array_key_exists( $shipment_place, $this->point_in ) ) {
				if ( $_key ) {
					return $this->point_in[$shipment_place][$_key];
				} else {
					return $this->point_in[$shipment_place];
				}
			}
			
			return false;
		}
	
		/**
		 * Get point in ID.
		 *
		 * @since 1.0.0
		 */
		public function get_point_in_id($shipment_place = 'store') {
			return $this->get_point_in( $shipment_place, 'id' );
		}
	
		/**
		 * Get point in address.
		 *
		 * @since 1.0.0
		 */
		public function get_point_in_address($shipment_place = 'store') {
			return $this->get_point_in( $shipment_place, 'address' );
		}
	
		/**
		 * Get point in ID for current order.
		 *
		 * @since 1.0.0
		 */		
		public function get_order_point_in_id() {
			
			if ( $this->integrator_order_exists() ) {
				/**
				 * Will be set via JS.
				 */
				return '';
			}

			$custom_selected = Options\WP_ApiShip_Options::get_order_meta( 
				$this->order->get_id(),
				Options\WP_ApiShip_Options::POST_SHIPPING_TO_POINT_IN_META,
				false
			);
			
			if ( $custom_selected ) {
				return $this->get_point_in( 'customSelected', 'id' );	
			}

			if ( $this->is_use_pickup_point() ) {
				return $this->get_point_in( 'pickup', 'id' );	
			}

			if ( $this->is_use_warehouse_address() ) {
				return $this->get_point_in( 'warehouse', 'id' );	
			}
			
			return $this->get_point_in( 'store', 'id' );	
		}	
	
		/**
		 * Get point in address for current order.
		 *
		 * @since 1.0.0
		 */	
		public function get_order_point_in_address() {
	
			if ( $this->integrator_order_exists() ) {
				/**
				 * Will be set via JS.
				 */				
				return '';
			}

			$custom_selected = Options\WP_ApiShip_Options::get_order_meta( 
				$this->order->get_id(),
				Options\WP_ApiShip_Options::POST_SHIPPING_TO_POINT_IN_META,
				false
			);
			
			if ( $custom_selected ) {
				return $this->get_point_in( 'customSelected', 'address' );	
			}

			if ( $this->is_use_pickup_point() ) {
				return $this->get_point_in( 'pickup', 'address' );	
			}

			if ( $this->is_use_warehouse_address() ) {
				return $this->get_point_in( 'warehouse', 'address' );	
			}
			
			return $this->get_point_in( 'store', 'address' );
		}
	
		/**
		 * Get point out ID for current order.
		 *
		 * @since 1.0.0
		 */		
		public function get_order_point_out_id() {
			
			// if ( $this->integrator_order_exists() ) {
				/**
				 * Will be set via JS. @todo delete after testing
				 */
				// return '';
			// }
			
			return $this->get_order_point_out('id');
		}	
	
		/**
		 * Get point out Address for current order.
		 *
		 * @since 1.0.0
		 */		
		public function get_order_point_out_address() {
			
			// if ( $this->integrator_order_exists() ) {
				/**
				 * Will be set via JS. @todo delete after testing
				 */
				// return '';
			// }
			
			return $this->get_order_point_out('address');
		}
		
		/**
		 * Get point out address for current order.
		 *
		 * @since 1.0.0
		 */		
		public function get_order_point_out( $key ) {
			
			if ( is_null($this->point_out) ) {
				$this->set_point_out();
			}
			
			if ( false === $this->point_out ) {
				return '';
			}
			
			if ( ! empty($this->point_out[$key]) ) {
				return $this->point_out[$key];
			}
				
			return '';
		}			

		/**
		 * Set point out data. 
		 *
		 * @since 1.0.0
		 */		
		protected function set_point_out() {
			$this->point_out = Options\WP_ApiShip_Options::get_order_meta( 
				$this->order->get_id(),
				Options\WP_ApiShip_Options::POST_SHIPPING_TO_POINT_OUT_META,
				false
			);
		}			
	
		/**
		 * Get current provider key.
		 *
		 * @since 1.0.0
		 */		
		protected function get_provider_key() {
			return $this->meta_data['tariffProviderKey']->value;
		}

		/**
		 * Check address we are using.
		 *
		 * @since 1.0.0
		 */		
		protected function is_use_warehouse_address() {
			
			$warehouse_address_use = Options\WP_ApiShip_Options::get_wc_option( 
				'wp_apiship_warehouse_address_use', 
				'no', 
				false 
			);
			
			if ( $warehouse_address_use == 'no' ) {
				return false;
			}
			
			return true;
		}

		/**
		 * Check pickup point we are using.
		 *
		 * @since 1.4.0
		 */		
		protected function is_use_pickup_point() {
			
			$line_items_shipping = $this->order->get_items('shipping');
			foreach ( $line_items_shipping as $item_id=>$item ) {
				/**
				 * $item is WC_Order_Item_Shipping Object.
				 */
				$shipping_order_item_id = $item_id;
			}

			$id = wc_get_order_item_meta( 
				$shipping_order_item_id, 
				'pointInId'
			);

			if ( (int) $id > 0 ) {
				return true;
			}
			return false;
		}
	
		/**
		 * Get template.
		 *
		 * @since 1.0.0
		 */	
		public function get_template($template) {
			return $this->get_templates_path() . $template;
		}
		
		/**
		 * Get plugin templates path.
		 *
		 * @since 1.0.0
		 */
		public function get_templates_path() {
			return WP_ApiShip\WP_ApiShip_Core::get_templates_path();
		}
		
		/**
		 * Check if an integrator order exists.
		 *
		 * @since 1.0.0
		 */		
		public function integrator_order_exists() {

			if ( $this->integrator_order_id ) {
				return true;
			}
			
			return false;
		}
		
		/**
		 * Get integrator order ID.
		 *
		 * @since 1.0.0
		 */		
		public function get_integrator_order_id() {
			return $this->integrator_order_id;
		}

		/**
		 * Get integrator order label image.
		 *
		 * @since 1.0.0
		 */
		public function get_order_label_image() {
			return WP_ApiShip\WP_ApiShip_Core::get_plugin_dir_image_url() . Options\WP_ApiShip_Options::LABEL_PLACEHOLDER;
		}

		/**
		 * Output integrator order label image.
		 *
		 * @since 1.0.0
		 */	
		public function the_order_label_image() {
			echo $this->get_order_label_image();
		}

		/**
		 * Set current tariff.
		 *
		 * @since 1.0.0
		 */
		protected function set_tariff() {
			if ( isset( $this->meta_data[ Options\WP_ApiShip_Options::TARIFF_DATA_KEY ] ) && ! empty( $this->meta_data[ Options\WP_ApiShip_Options::TARIFF_DATA_KEY ] ) ) {
				$this->tariff = json_decode($this->meta_data[ Options\WP_ApiShip_Options::TARIFF_DATA_KEY ]->value);
			}
		}
		
		/**
		 * Get current tariff.
		 *
		 * @since 1.0.0
		 */
		protected function get_tariff() {
			if ( is_null( $this->tariff ) ) {
				$this->set_tariff();
			}
			return $this->tariff;
		}		
	
		/**
		 * Get tariff delivery type.
		 *
		 * @since 1.0.0
		 */	
		public function get_delivery_type() {
			
			if ( empty($this->get_tariff()->deliveryTypes) ) {
				return false;
			}
			
			return $this->get_tariff()->deliveryTypes;
		}
		
		/**
		 * Output tariff delivery type.
		 *
		 * @since 1.0.0
		 */		
		public function the_delivery_type() {

			/**
			 * Delivery type value by default.
			 * @see wp-apiship\includes\api\class-wp-apiship-order.php
			 */					
			$delivery_type = '1';
			
			if ( is_array( $this->get_delivery_type() ) ) {
				echo implode( ',', $this->get_delivery_type() );
			} else {
				echo $delivery_type;
			}
		}
		
		/**
		 * Output tariff delivery type text.
		 *
		 * @since 1.0.0
		 *
		 * @param $type number|false Delivery type.
		 */
		public function the_delivery_type_text() {
		
			$text = array();
			
			foreach( $this->get_delivery_type() as $type ) {
				$text[] = Options\WP_ApiShip_Options::get_delivery_type_text($type);
			}

			echo implode( '; ', $text );
		}
		
		/**
		 * Get tariff pickup type.
		 *
		 * @since 1.0.0
		 */	
		public function get_pickup_type() {
			return $this->get_tariff()->pickupTypes;
		}

		/**
		 * Output tariff pickup type.
		 *
		 * @since 1.0.0
		 */		
		public function the_pickup_type() {

			/**
			 * Pickup type value by default.
			 * @see wp-apiship\includes\api\class-wp-apiship-order.php
			 */					
			$pickup_type = '1';
			
			if ( is_array( $this->get_pickup_type() ) ) {
				echo implode( ',', $this->get_pickup_type() );
			} else {
				echo $pickup_type;
			}
		}
		
		/**
		 * Output tariff pickup type text.
		 *
		 * @since 1.0.0
		 *
		 * @param $type number|false Delivery type.
		 */
		public function the_pickup_type_text() {
		
			$text = array();
			
			foreach( $this->get_pickup_type() as $type ) {
			// foreach( array(0=>1,1=>2) as $type ) { // @debug
				$text[] = Options\WP_ApiShip_Options::get_pickup_type_text($type);
			}

			echo implode( '; ', $text );
		}		
		
	}
	
endif;

# --- EOF