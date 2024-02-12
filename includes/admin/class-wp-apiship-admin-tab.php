<?php
/**
 * File: class-wp-apiship-admin-tab.php
 *
 * @package WP ApiShip
 * @subpackage Administration
 *
 * @since 1.0.0
 */
namespace WP_ApiShip\Admin;

use stdClass;
use WP_ApiShip,
	WC_Admin_Settings,
	WP_ApiShip\Options;
use WP_ApiShip\HTTP\WP_ApiShip_HTTP;
use WP_ApiShip\WP_ApiShip_Core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists('WP_ApiShip_Admin_Tab', false) ) :

	class WP_ApiShip_Admin_Tab {

		/**
		 * Constructor.
		 */
		public function __construct() {

			$this->id = Options\WP_ApiShip_Options::get_wc_settings_plugin_tab();
			$this->label = esc_html__('ApiShip', 'apiship');

			add_filter( 'woocommerce_settings_tabs_array', array($this, 'fiter__add_tab'), 500);
			add_action( 'woocommerce_sections_' . $this->id, array( $this, 'on__output_sections') );
			add_action( 'woocommerce_settings_' . $this->id, array( $this, 'on__output_section') );
			add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'on__save') );
			
			/**
			 * Custom fields.
			 *
			 * @see woocommerce\includes\admin\class-wc-admin-settings.php
			 */
			add_action( 'woocommerce_admin_field_table', array( $this, 'on__wc_field_table') );
			// add_action( 'woocommerce_admin_field_connections', array( $this, 'on__wc_field_connections') );
			add_action( 'woocommerce_admin_field_providers', array( $this, 'on__wc_field_providers') );
			// add_action( 'woocommerce_admin_field_docs', array( $this, 'on__wc_field_docs') );
			add_action( 'woocommerce_admin_field_timezone', array( $this, 'on__wc_field_timezone') );
			add_action( 'woocommerce_admin_field_button', array( $this, 'on__wc_field_button') );
			
			if ( WP_ApiShip\WP_ApiShip_Core::is_godmode(false) ) {
				add_action( 'woocommerce_admin_field_debug', array( $this, 'on__wc_field_debug') );
				// add_action( 'woocommerce_admin_field_calculator', array( $this, 'on__wc_field_calculator') );
			}
		}
		
		/**
		 * @since 1.0.0
		 */
		public function on__wc_field_table($value) {
			ob_start();
			require_once( 'templates/provider-table.php' );
			echo ob_get_clean();			
		}

		/**
		 * @since 1.0.0
		 */	
		public function on__wc_field_timezone($value) {
			ob_start();
			require_once( 'templates/timezone-field.php' );
			echo ob_get_clean();			
		}
	
		/**
		 * @since 1.0.0
		 */	
		public function on__wc_field_button($value) {
			ob_start();
			require( 'templates/button-field.php' );
			echo ob_get_clean();	
		}
	
		/**
		 * Add `providers` form.
		 *
		 * @since 1.0.0
		 */		
		public function on__wc_field_providers($value) {
			ob_start();
			require_once( 'templates/providers-form.php' );
			echo ob_get_clean();
		}

		/**
		 * Add `docs` form.
		 *
		 * @since 1.0.0
		 */		
		public function on__wc_field_docs($value) {
			ob_start();
			require_once( 'templates/docs-form.php' );
			echo ob_get_clean();
		}

		/**
		 * Add `debug` form.
		 *
		 * @since 1.0.0
		 */			
		public function on__wc_field_debug($value) {
			ob_start();
			require_once( 'templates/debug-form.php' );
			echo ob_get_clean();			
		}

		/**
		 * Add `calculator` form.
		 *
		 * @since 1.0.0
		 */				
		public function on__wc_field_calculator($value) {
			ob_start();
			require_once( 'templates/calculator-form.php' );
			echo ob_get_clean();			
		}
		
		/**
		 * Add new tab to Woocommerce navigation.
		 *
		 * @since 1.0.0
		 */
		public function fiter__add_tab($settings_tabs) {
			$settings_tabs[$this->id] = $this->label;
			return $settings_tabs;
		}

		/**
		 * Get sections for ApiShip tab on WC settings page.
		 *
		 * @since 1.0.0
		 */
		public function get_sections(){

			$sections = array(
				'' 			=> esc_html__('General', 'apiship'),
				'providers' => esc_html__('Providers', 'apiship'),
				// 'docs' 		=> esc_html__('Docs', 'apiship'),
			);

			if ( WP_ApiShip\WP_ApiShip_Core::is_godmode(false) ) {
				$sections['debug'] = 'Debug';
			}

			/**
			 * @see woocommerce\includes\admin\settings\class-wc-settings-page.php
			 */
			return apply_filters('woocommerce_get_sections_' . $this->id, $sections);
		}

		/**
		 * Output sections subsubsub.
		 *
		 * @since 1.0.0
		 */
		public function on__output_sections() {
			
			global $current_section;

			$sections = $this->get_sections();

			if (empty($sections) || 1 === sizeof($sections)) {
			  return;
			}
			
			require_once( 'templates/subsubsub-section.php' );
		}

		/**
		 * Get settings.
		 *
		 * @since 1.0.0
		 */
		public function get_settings( $current_section = ' ' ) {

			if ( 'providers' == $current_section ) {
				$settings = $this->get_section_providers();
			} else if ( 'connections' == $current_section ) {
				// $settings = $this->get_section_connections();
			} else if ( 'docs' == $current_section ) {
				$settings = $this->get_section_docs();
			} else if ( 'debug' == $current_section ) {
				$settings = $this->get_section_debug();
			} else if ( 'calculator' == $current_section ) {
				$settings = $this->get_section_calc();
			} else {
				$settings = $this->get_section_general();
			}

			/**
			 * @see woocommerce\includes\admin\settings\class-wc-settings-page.php
			 */
			return apply_filters('woocommerce_get_settings_' . $this->id, $settings, $current_section);
		}
		
		/**
		 * Output section.
		 *
		 * @since 1.0.0
		 */
		public function on__output_section() {

			global $current_section;

			$settings = $this->get_settings($current_section);

			wp_nonce_field('wp_apiship_save_options', 'nonce');

			WC_Admin_Settings::output_fields($settings);
		}
		
		/**
		 * Save options.
		 *
		 * @since 1.0.0
		 */
		public function on__save()
		{
			global $current_section;

			if (!wp_verify_nonce($_POST['nonce'], 'wp_apiship_save_options')) {
				die('Undefined Nonce');
			}
			
			$postData = $_POST;
			$settings = $this->get_settings($current_section);

			if ($current_section === 'general' || $current_section === ' ' || $current_section === '') {
				self::check_api_token($postData['wp_apiship_token']);
			}

			foreach( $settings as $id=>$setting ) {
				if ( isset( $setting['save'] ) && ! $setting['save'] ) {
					unset( $settings[$id] );
				}
			}
			
			WC_Admin_Settings::save_fields($settings, $postData);
		}

		/**
		 * Check client api token
		 *
		 * @since 1.3.0
		 */		
		protected static function check_api_token(string $token)
		{
			$response = WP_ApiShip_HTTP::get(
				'lists/providers',
				array(
					'headers' => [
						'Authorization' => $token,
					],
					'timeout' => 20000
				)
			);
			if ( wp_remote_retrieve_response_code($response) !== WP_ApiShip_HTTP::OK ) {
				WC_Admin_Settings::add_error( esc_html__( 'Ошибка проверки токена, укажите корректный токен', 'wp-apiship' ) );
			}
		}
		
		/**
		 * Get `providers` section.
		 *
		 * @since 1.0.0
		 */		
		protected function get_section_providers() {

			$settings = array(
				'section_title' => array(
					'name' 	=> esc_html__('Список доступных Служб Доставки', 'apiship'),
					'type' 	=> 'title',
					'desc' 	=> '',
					'id' 	=> 'wp_apiship_section_providers'
				),
				'providers_form' => array(
					#'name'	 	  => esc_html__('Кликайте по карточке для получения информации о тарифах и количестве подключений','apiship'),
					'name'	 	  => '',
					'type' 		  => 'providers', # @see action `woocommerce_admin_field_providers`
					'class' 	  => 'wp-apiship-admin-tab-field',
					'desc' 		  => '',
					'desc_tip' 	  => '',
					'id' 		  => 'wp_apiship_providers_form',
					'placeholder' => '',
					'value' 	  => ''		
				),				
				'section_end' => array(
				  'type' => 'sectionend',
				  'id' 	 => 'wp_apiship_section_providers'
				)
			);
			
			return $settings;
		}

		/**
		 * Get `docs` section.
		 *
		 * @since 1.0.0
		 */			
		protected function get_section_docs() {
			
			$settings = array(
				array(
					'name' 	=> esc_html__('Наклейки заказов', 'apiship'),
					'type' 	=> 'title',
					'desc' 	=> '',
					'id' 	=> 'wp_apiship_section_docs'
				),
				array(
					'name'	 	  => '',
					'type' 		  => 'docs', # @see action `woocommerce_admin_field_docs`
					'class' 	  => 'wp-apiship-admin-tab-field',
					'desc' 		  => '',
					'desc_tip' 	  => '',
					'id' 		  => 'wp_apiship_docs_form',
					'placeholder' => '',
					'value' 	  => ''		
				),				
				array(
				  'type' => 'sectionend',
				  'id' 	 => 'wp_apiship_section_docs'
				)
			);
			
			return $settings;			
		}
		
		/**
		 * Get `general` section options.
		 *
		 * @since 1.0.0
		 */
		protected function get_section_general() {
			
			$settings = array(
				/**
				 * Main.
				 */
				array(
					'name' 	=> esc_html__('Настройки подключения к сервису ApiShip','apiship'),
					'type'	=> 'title',
					'desc' 	=> '',
					'id' 	=> 'wp_apiship_section_connection'
				),
				'god_mode' => array(
					'name' 		=> '',
					'type' 		=> 'checkbox',
					'class' 	=> 'wp-apiship-admin-tab-field',
					'desc' 		=>  '<strong>God Mode.</strong>',
					'desc_tip' 	=> '',
					'id' 		=> 'wp_apiship_god_mode',
					'save' 		=> true
				),				
				array(
					'name' 	=> esc_html__('Token', 'apiship'),
					'type' 	=> 'password',
					'class' => 'wp-apiship-admin-tab-field options-field',
					'desc' 	=>  esc_html__('Скопируйте токен из личного кабинета ApiShip (Dashboard - Главная страница)', 'apiship'),
					'desc_tip' 	  => esc_html__('You should get a token for authorization', 'apiship'),
					'id' 		  => 'wp_apiship_token',
					'placeholder' => ''
				),
				array(
				  'type' => 'sectionend',
				  'id' 	 => 'wp_apiship_section_connection'
				),
				/**
				 * Store address.
				 */
				array(
					'name' 	=> esc_html__('Store address', 'apiship'),
					'type'	=> 'title',
					'desc' 	=> '',
					'id' 	=> 'wp_apiship_section_store_address'
				),
				array(
					'title'		=> '',
					'id' 		=> 'wp_apiship_store_fake_checkbox',
					'default' 	=> 'no',
					'class'		=> 'wp-apiship-remove-self',
					'type' 		=> 'checkbox',
					'desc' 		=> sprintf(
						esc_html__('You can set the store address on the Woocommerce %sGeneral%s tab.', 'apiship'),
						'<a href="'.WP_ApiShip\WP_ApiShip_Core::get_admin_url(array('page'=>'wc-settings','tab'=>'general')).'">',
						'</a>'
					),
				),
				array(
					'title' => esc_html__('Contact name', 'apiship'),
					'type'  => 'text',
					'id'    => 'wp_apiship_store_contact_name',
					'desc' 	=> esc_html__("The contact name will be indicated in the sender's address", 'apiship'),
				),
				array(
					'title' => esc_html__('Phone', 'apiship'),
					'type'  => 'text',
					'id'    => 'wp_apiship_store_phone',
					'desc' 	=> '',
				),				
				array(
				  'type' => 'sectionend',
				  'id' 	 => 'wp_apiship_section_store_address'
				),
				/**
				 * Warehouse address.
				 */				
				array(
					'name' 	=> esc_html__('Warehouse address', 'apiship'),
					'type'	=> 'title',
					'desc' 	=> '',
					'id' 	=> 'wp_apiship_section_warehouse_address'
				),
				array(
					'desc' 		=> esc_html__('Use of warehouse address instead of store address.', 'apiship'),
					'id' 		=> 'wp_apiship_warehouse_address_use',
					'default' 	=> 'no',
					'type' 		=> 'checkbox',
				),
				array(
					'title' => esc_html__('Address line 1', 'apiship'),
					'type'  => 'text',
					#'desc'  => esc_html__('This is where your business is located. Tax rates and shipping rates will use this address.', 'apiship'),
					'desc'  => esc_html__('Место где расположен ваш склад. Расчёт стоимости доставки будет производиться учитывая данный адрес.', 'apiship'),
					'id'    => 'wp_apiship_warehouse_address',
				),
				array(
					'title' => esc_html__('Address line 2', 'apiship'),
					'type'  => 'text',
					'id'    => 'wp_apiship_warehouse_address_2',
				),				
				array(
					'title' => esc_html__('City', 'apiship'),
					'type'  => 'text',
					'id'    => 'wp_apiship_warehouse_city',
				),
				array(
					'title'   => esc_html__('Country / State', 'apiship'),
					'desc'    => esc_html__('Устанавливается на странице настроек Woocommerce, вкладка General', 'wp-apiship' ), # WC()->countries->countries['RU'];
					'type'    => 'text',
					'id'      => 'wp_apiship_warehouse_country',
					'default' => Options\WP_ApiShip_Options::get_wc_option(
						'woocommerce_default_country', 
						Options\WP_ApiShip_Options::WС_DEFAULT_COUNTRY,
						false
					),
					'custom_attributes' => array(
						'disabled' => 'disabled'
					),
				),
				array(
					'title' => esc_html__('Postcode / ZIP', 'apiship'),
					'type'  => 'text',
					'id'    => 'wp_apiship_warehouse_index',
				),
				array(
					'title' => esc_html__('Contact name', 'apiship'),
					'type'  => 'text',
					'id'    => 'wp_apiship_warehouse_contact_name',
					'desc' 	=> esc_html__("The contact name will be indicated in the sender's address", 'apiship'),
				),
				array(
					'title' => esc_html__('Phone', 'apiship'),
					'type'  => 'text',
					'id'    => 'wp_apiship_warehouse_phone',
					'desc' 	=> '',
				),				
				array(
				  'type' => 'sectionend',
				  'id' 	 => 'wp_apiship_section_warehouse_address'
				),
				/**
				 * Dimensions and weight of product.
				 */
				array(
					'title' => esc_html__('Dimensions of product by default', 'apiship'),
					'type'  => 'title',
					'id'    => 'wp_apiship_section_dimensions',
				),
				array(
					'title' 	=> esc_html__('Default item length (cm)', 'apiship'),
					'desc'  	=> esc_html__('Default item length if it not specified', 'wp-apiship' ),
					'type'  	=> 'number', #'text',
					'id'    	=> 'wp_apiship_length',
					'default'	=> Options\WP_ApiShip_Options::get_wc_option(
						'wp_apiship_length', 
						Options\WP_ApiShip_Options::ITEM_LENGTH,
						false
					),
					'placeholder' => Options\WP_ApiShip_Options::ITEM_LENGTH
				),
				array(
					'title' 	=> esc_html__('Default item width (cm)', 'apiship'),
					'desc'  	=> esc_html__('Default item width if it not specified', 'wp-apiship' ),
					'type'  	=> 'number',
					'id'    	=> 'wp_apiship_width',
					'default'	=> Options\WP_ApiShip_Options::get_wc_option(
						'wp_apiship_width', 
						Options\WP_ApiShip_Options::ITEM_WIDTH,
						false
					),
					'placeholder' => Options\WP_ApiShip_Options::ITEM_WIDTH
				),
				array(
					'title' 	=> esc_html__('Default item height (cm)', 'apiship'),
					'desc'  	=> esc_html__('Default item height if it not specified', 'wp-apiship' ),
					'type'  	=> 'number',
					'id'    	=> 'wp_apiship_height',
					'default'   => Options\WP_ApiShip_Options::get_wc_option(
						'wp_apiship_height', 
						Options\WP_ApiShip_Options::ITEM_HEIGHT,
						false
					),
					'placeholder' => Options\WP_ApiShip_Options::ITEM_HEIGHT,
					'min' => 1
				),
				array(
					'title' 	=> esc_html__('Default item weight (g)', 'apiship'),
					'desc'  	=> esc_html__('Default item weight if it not specified', 'wp-apiship' ),
					'type'  	=> 'number',
					'id'    	=> 'wp_apiship_weight',
					'default'   => Options\WP_ApiShip_Options::get_wc_option(
						'wp_apiship_weight', 
						Options\WP_ApiShip_Options::ITEM_WEIGHT,
						false
					),
					'placeholder' => Options\WP_ApiShip_Options::ITEM_WEIGHT
				),	
				array(
					'type'  => 'sectionend',
					'id'   	=> 'wp_apiship_section_dimensions',
				),
				/**
				 * Default cargo place params
				 */
				array(
					'title' => esc_html__('Параметры грузоместа (используются, если заполнено)', 'apiship'),
					'type'  => 'title',
					'id'    => 'wp_apiship_section_place',
				),
				array(
					'title' 	=> esc_html__('Длина (см)', 'apiship'),
					'desc'  	=> '',
					'type'  	=> 'number',
					'id'    	=> 'wp_apiship_place_length',
					'default'	=> Options\WP_ApiShip_Options::get_wc_option(
						'wp_apiship_place_length', 
						'',
						false
					),
					'placeholder' => esc_html__('Длина (см)', 'apiship'),
				),
				array(
					'title' 	=> esc_html__('Высота (см)', 'apiship'),
					'desc'  	=> '',
					'type'  	=> 'number',
					'id'    	=> 'wp_apiship_place_height',
					'default'	=> Options\WP_ApiShip_Options::get_wc_option(
						'wp_apiship_place_height', 
						'',
						false
					),
					'placeholder' => esc_html__('Высота (см)', 'apiship'),
				),
				array(
					'title' 	=> esc_html__('Ширина (см)', 'apiship'),
					'desc'  	=> '',
					'type'  	=> 'number', #'text',
					'id'    	=> 'wp_apiship_place_width',
					'default'	=> Options\WP_ApiShip_Options::get_wc_option(
						'wp_apiship_place_width', 
						'',
						false
					),
					'placeholder' => esc_html__('Ширина (см)', 'apiship'),
				),
				array(
					'title' 	=> esc_html__('Вес места (гр)', 'apiship'),
					'desc'  	=> '',
					'type'  	=> 'number',
					'id'    	=> 'wp_apiship_place_weight',
					'default'	=> Options\WP_ApiShip_Options::get_wc_option(
						'wp_apiship_place_weight', 
						'',
						false
					),
					'placeholder' => esc_html__('Вес места (гр)', 'apiship'),
				),
				array(
					'title' 	=> esc_html__('Вес упаковки (гр)', 'apiship'),
					'desc'  	=> '',
					'type'  	=> 'number',
					'id'    	=> 'wp_apiship_place_package_weight',
					'default'	=> Options\WP_ApiShip_Options::get_wc_option(
						'wp_apiship_place_package_weight', 
						'',
						false
					),
					'placeholder' => esc_html__('Вес упаковки (гр)', 'apiship'),
				),
				array(
					'type'  => 'sectionend',
					'id'   	=> 'wp_apiship_section_place',
				),
				/**
				 * Commission.
				 */
				array(
					'name' 	=> esc_html__('Commission', 'apiship'),
					'type'	=> 'title',
					'desc' 	=> '',
					'id' 	=> 'wp_apiship_section_commission_settings'
				),
				array(
					'title' 	=> esc_html__('Commission', 'apiship'),
					'type'  	=> 'radio', # @see woocommerce\includes\admin\class-wc-admin-settings.php
					'id'    	=> 'wp_apiship_include_fees',
					'default'   => 'yes',
					'options'   => array(
						#'yes' => esc_html__('Включать в стоимость доставки комиссию за наложенный платёж и страховку', 'apiship'),
						'yes' => esc_html__('Include COD and insurance fees commission in shipping costs', 'apiship'),
						#'no'  => esc_html__('Комиссия за наложенный платёж и страховку в стоимость не включена', 'apiship'),
						'no'  => esc_html__('Do not include COD and insurance fees commission in shipping costs', 'apiship'),
					),
					'autoload' => true, #this is 3-rd option in `update_option` function.
				),
				array(
					'type'  => 'sectionend',
					'id'   	=> 'wp_apiship_section_commission_settings',
				),				
				/**
				 * Delivery points template settings.
				 * 
				 * @since 1.4.0
				 */
				array(
					'name' 	=> esc_html__('Настройки шаблона пунктов доставки', 'apiship'),
					'type'	=> 'title',
					'desc' 	=> '',
					'id' 	=> 'wp_apiship_section_points_template_setting'
				),
				array(
					'title' 	  => 'Шаблон пунктов модуля доставки',
					'type'  	  => 'textarea',
					'id'    	  => 'wp_apiship_points_template',
					'desc' 	=> implode('<br>', [
						'<strong>%type</strong> - ' . esc_html__('тип доставки', 'apiship'),
						'<strong>%company</strong> - ' . esc_html__('транспортная компания', 'apiship'),
						'<strong>%name</strong> - ' . esc_html__('название ПВЗ', 'apiship'),
						'<strong>%address</strong> - ' . esc_html__('адрес ПВЗ', 'apiship'),
						'<strong>%tariff</strong> - ' . esc_html__('название тарифа', 'apiship'),
						'<strong>%time</strong> - ' . esc_html__('сроки доставки', 'apiship'),
						'<div style="height: 5px;"></div>'
					]),
					'default'	=> Options\WP_ApiShip_Options::get_wc_option(
						'wp_apiship_points_template', 
						Options\WP_ApiShip_Options::DEFAULT_POINTS_TEMPLATE,
						false
					),
					'placeholder' => Options\WP_ApiShip_Options::DEFAULT_POINTS_TEMPLATE,
				),
				array(
					'type'  => 'sectionend',
					'id'   	=> 'wp_apiship_section_points_template_setting',
				),
				/**
				 * Status mapping settings.
				 * 
				 * @since 1.4.0
				 */
				array(
					'name' 	=> esc_html__('Параметры сопоставления статусов', 'apiship'),
					'type'	=> 'title',
					'desc' 	=> '',
					'id' 	=> 'wp_apiship_section_mapping_setting'
				),
				array(
					'type'  	  => 'apiship_mapping',
					'id'    	  => 'wp_apiship_mapping'
				),
				array(
					'type'  => 'sectionend',
					'id'   	=> 'wp_apiship_section_points_template_setting',
				),
				/**
				 * Point out display mode.
				 */
				array(
					'name' 	=> esc_html__('Настройка вывода ПВЗ', 'apiship'),
					'type'	=> 'title',
					'desc' 	=> '',
					'id' 	=> 'wp_apiship_section_point_out_display_settings'
				),
				array(
					'title' 	=> esc_html__('Выберите один из режимов', 'apiship'),
					'type'  	=> 'radio', # @see woocommerce\includes\admin\class-wc-admin-settings.php
					'id'    	=> 'wp_apiship_point_out_display_mode',
					'default'   => '1',
					'options'   => array(
						'1' => esc_html__('Отдельный способ доставки до ПВЗ для каждого тарифа', 'apiship'),
						'2' => esc_html__('Отдельный способ доставки до ПВЗ для каждой СД', 'apiship'),
						'3' => esc_html__('Все ПВЗ на одной карте', 'apiship'),
					),
					'autoload' => true, #this is 3-rd option in `update_option` function.
				),
				array(
					'type'  => 'sectionend',
					'id'   	=> 'wp_apiship_section_point_out_display_settings',
				),	
				/**
				 * Other settings.
				 */
				array(
					'name' 	=> esc_html__('Others', 'apiship'),
					'type'	=> 'title',
					'desc' 	=> '',
					'id' 	=> 'wp_apiship_section_others_settings'
				),
				array(
					'desc' 		=> esc_html__('Using only selected providers', 'apiship'),
					'type' 		=> 'checkbox',
					'id' 		=> 'wp_apiship_use_selected_providers',
					'default' 	=> 'no',
					'desc_tip' 	=> sprintf(
						esc_html__('You can set the list of providers on %sProviders%s section', 'apiship'),
						'<a href="'.WP_ApiShip\WP_ApiShip_Core::get_admin_url(
							array(
								'page'    => Options\WP_ApiShip_Options::get_wc_settings_page(),
								'tab'	  => Options\WP_ApiShip_Options::get_wc_settings_plugin_tab(),
								'section' => Options\WP_ApiShip_Options::get_plugin_providers_section()
							)
						).'">',
						'</a>'
					),
				),				
				array(
					'name' 	=> esc_html__('Timezone', 'apiship'),
					'type' 	=> 'timezone',  # @see `on__wc_field_timezone`.
					'class' => 'wp-apiship-admin-tab-field options-field',
					'desc' 	=>  sprintf(
						esc_html__('Order dispatch date (pickupDate) will be set according to %sTimezone%s', 'apiship'),
						'<a href="'.WP_ApiShip\WP_ApiShip_Core::get_admin_url('options-general.php').'" target="_blank">',
						'</a>'
					),
					'desc_tip' 	  => esc_html__('You can set Timezone on General Settings page', 'apiship'),
					'id' 		  => 'wp_apiship_timezone',
					'placeholder' => '',
					'save' 		  => false		
				),				
				array(
					'title' 	  => esc_html__('Yandex map API key', 'apiship'),
					'type'  	  => 'text',
					'id'    	  => 'wp_apiship_yandexmap_key',
					'desc' 		  => esc_html__('For correct operation, you should set your own Yandex map key.','apiship'),
					'placeholder' => Options\WP_ApiShip_Options::YANDEX_MAP_DEFAULT_KEY,
					'desc_tip' 	  => esc_html__('We provide a default key, but no guarantee of correct operation', 'apiship'),
				),
				array(
					'type'  => 'sectionend',
					'id'   	=> 'wp_apiship_section_others_setting',
				),
			);

			// $god_mode = Options\WP_ApiShip_Options::get_wc_option('wp_apiship_god_mode', 'no', false);
			if ( ! WP_ApiShip\WP_ApiShip_Core::is_godmode(false) ) {
				unset( $settings['god_mode'] );
			}
			
			return $settings;
		}
		
		/**
		 * Get `debug` section.
		 *
		 * @since 1.0.0
		 */
		protected function get_section_debug() {
			
			$settings = array(
				'section_title' => array(
					'name' => 'Debug',
					'type' => 'title',
					'desc' => '',
					'id'   => 'wp_apiship_section_debug'
				),
				'debug' => array(
					'name'	   => '',
					'type'	   => 'debug', # @see `on__wc_field_debug`.
					'class'    => 'wp-apiship-admin-tab-field',
					'desc' 	   =>  '',
					'desc_tip' => '',
					'id' 	   => 'wp_apiship_debug',
				),				
				'section_end' => array(
				  'type' => 'sectionend',
				  'id' 	 => 'wp_apiship_section_debug'
				)
			);
			
			return $settings;
		}

		/**
		 * Get `calculator` section.
		 *
		 * @since 1.0.0
		 */		
		protected function get_section_calc() {
			
			$settings = array(
				'section_title' => array(
					'name' => 'Calculator',
					'type' => 'title',
					'desc' => '',
					'id'   => 'wp_apiship_section_calc'
				),
				'calculator' => array(
					'name' 	   => '',
					'type' 	   => 'calculator', # @see `on__wc_field_calculator`.
					'class'    => 'wp-apiship-admin-tab-field',
					'desc' 	   =>  '',
					'desc_tip' => '',
					'id' 	   => 'wp_apiship_calculator',
				),				
				'section_end' => array(
				  'type' => 'sectionend',
				  'id' 	 => 'wp_apiship_section_calc'
				)
			);
			
			return $settings;			
		}		
	}

endif;

# --- EOF