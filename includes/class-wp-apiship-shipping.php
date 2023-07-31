<?php
/**
 * File: class-wp-apiship-shipping.php
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

if ( ! class_exists('WP_ApiShip_Shipping') ) :

	class WP_ApiShip_Shipping {
		
		/**
		 * Available rates.
		 */		
		protected $rates = array();
	
		/**
		 * Constructor.
		 *
		 * @param string $path_to_loader
		 */	
		public function __construct( $path_to_loader ) {
			
			if ( is_admin() ) {
				
				/**
				 * @see woocommerce\includes\admin\settings\class-wc-settings-page.php
				 */
				add_filter( 'woocommerce_get_sections_shipping', array( $this, 'filter__settings_page' ) );

				/**
				 * @see woocommerce\includes\admin\settings\class-wc-settings-page.php
				 */
				add_filter( 'woocommerce_get_settings_shipping', array( $this, 'filter__settings' ), 10, 2 );
			
				/**
				 * @see woocommerce\templates\cart\cart-shipping.php
				 * @see wp-apiship\woocommerce\cart\cart-shipping.php
				 */
				add_action( 'woocommerce_after_shipping_rate', array( $this, 'on__after_shipping_rate' ), 10, 2 );
			
			}
			
			/**
			 * Add new shipping methods.
			 * @see woocommerce\includes\class-wc-shipping.php
			 */

			/**
			 * @see woocommerce\includes\class-wc-shipping.php
			 */			
			add_action( 'woocommerce_shipping_init', array( $this, 'on__init_method' ) );
			
			/**
			 * @see woocommerce\includes\class-wc-shipping.php
			 */
			add_filter( 'woocommerce_shipping_methods', array( $this, 'filter__register_method' ) );			
		}
		
		/**
		 * Add shipping method.
		 */
		public function on__init_method() {
			if ( ! class_exists( 'WP_ApiShip_Shipping_Method' ) ) {
				include_once dirname( __FILE__ ) . '/class-wp-apiship-shipping-method.php';
			}
		}
		
		/**
		 * Register shipping method.
		 *
		 * @param array $methods Shipping methods.
		 *
		 * @return array
		 */
		public function filter__register_method( $methods ) {
			$methods[ Options\WP_ApiShip_Options::SHIPPING_METHOD_ID ] = 'WP_ApiShip_Shipping_Method'; 
			// @see class name in `on__init_method`.
			return $methods;
		}			

		/**
		 * If we need to change the view to dropdown of shipping methods on frontend.
		 *
		 * @since 1.0.0
		 */
		public function on__after_shipping_rate( $method, $index ) { 

			if ( ! $this->is_dropdown_selector() ) {
				return;
			}

			/**
			 * @todo May be use get_packages().
			 */
			/** 
			$packages = WC()->shipping()->get_packages();
			foreach ( $packages as $i => $package ) {
				// output to log
			}
			// */
			
			if ( count( $this->rates ) == $this->get_rates_max() ) {
				return;
			}
			
			// @todo change 'wpapiship'
			if ( array_key_exists( 'provider', $method->meta_data ) && $method->meta_data['provider'] == 'wpapiship' ) { 
				
				$this->rates[] = $method;
				
				if ( count( $this->rates ) == $this->get_rates_max() ) {	?>
					<select size="1" name="wpapiship-shipping-methods" id="wpapiship-shipping-methods">
						<option value="none"><?php esc_html_e('Выберите метод доставки', 'wp-apiship'); ?></option><?php
						foreach( $this->rates as $key=>$rate  ) {	?>
							<option value="rate-<?php echo $key; ?>">
								<?php echo $rate->meta_data['tariffProvider'] . ':' . wc_cart_totals_shipping_method_label( $rate ); ?>
							</option><?php
						} ?>
				   </select><?php
				}				
			}
		}

		/**
		 * Get all sections for this page, register own settings page.
		 *
		 * @since 1.0.0
		 *
		 * @param array $sections Admin sections.
		 *
		 * @return array
		 */		 
		public function filter__settings_page( $sections ) {
			$sections['apiship'] = esc_html__('ApiShip', 'wp-apiship');
			return $sections;
		}
		
		/**
		 * Settings page.
		 *
		 * @param array  $settings Admin settings.
		 * @param string $current_section Current section.
		 *
		 * @return array
		 */
		public function filter__settings( $settings, $current_section ) {
			
			if ( 'apiship' === $current_section ) {

				/**
				 * We are redirect to own settings page.
				 * $settings = include 'admin/templates/shipping-section.php';
				 */
				$url = add_query_arg( 
					array(
						'page' => Options\WP_ApiShip_Options::get_wc_settings_page(),
						'tab'  => Options\WP_ApiShip_Options::get_wc_settings_plugin_tab(),
					),
					admin_url( 'admin.php' ) 
				);
				
				wp_redirect($url);
				exit;
			}
			return $settings;
		}

		/**
		 * Get rates max option.
		 *
		 * @since 1.0.0
		 */
		public function get_rates_max() {
			return Options\WP_ApiShip_Options::get_rates_max();
		}
		
		/**
		 * Get dropdown selector option.
		 *
		 * @since 1.0.0
		 */
		public function is_dropdown_selector() {
			return Options\WP_ApiShip_Options::is_dropdown_selector();
		}		 
	}
	
endif;

# --- EOF