<?php
/**
 * File: shipping-section.php
 *
 * Settings for shipping method.
 *
 * @package WP ApiShip
 */

use WP_ApiShip\Options;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// ApiShip
$settings = array(
	# Main title.
	array(
		'title' => esc_html__('ApiShip', 'apiship'),
		'type'  => 'title',
		'id'    => 'wp_apiship_section_title',
	),
	array(
		'type'  => 'sectionend',
		'id'    => 'wp_apiship_section_title',
	),
	# Warehouse address.
	array(
		'title' => esc_html__('Адрес склада', 'apiship'),
		'type'  => 'title',
		'id'    => 'wp_apiship_section_warehouse_address',
	),	
	# Using warehouse address.
	array(
		'name' 		=> '',
		'type' 		=> 'checkbox',
		'class' 	=> 'wp-apiship-admin-tab-field',
		'desc' 		=>  'Использовать адрес склада вместо адреса магазина при расчёте стоимости доставки',
		'desc_tip' 	=> '',
		'id' 		=> 'wp_apiship_warehouse_address_use',
		#'save' 		=> true
	),	
	array(
		'title' 	=> esc_html__('Адрес', 'apiship'),
		#'desc'  	=> esc_html__('', 'wp-apiship' ),
		'type'  	=> 'text',
		'id'    	=> 'wp_apiship_warehouse_address',
	),	
	array(
		'title' 	=> esc_html__('Город', 'apiship'),
		#'desc'  	=> esc_html__('', 'wp-apiship' ),
		'type'  	=> 'text',
		'id'    	=> 'wp_apiship_warehouse_city',
	),	
	array(
		'title' 	=> esc_html__('Страна/Регион', 'apiship'),
		#'desc'  	=> esc_html__('', 'wp-apiship' ),
		'type'  	=> 'text',
		'id'    	=> 'wp_apiship_warehouse_country',
		'placeholder' => Options\WP_ApiShip_Options::WС_DEFAULT_COUNTRY
	),
	array(
		'title' 	=> esc_html__('Почтовый индекс', 'apiship'),
		#'desc'  	=> esc_html__('', 'wp-apiship' ),
		'type'  	=> 'text',
		'id'    	=> 'wp_apiship_warehouse_index',
	),	
	array(
		'type'  => 'sectionend',
		'id'    => 'wp_apiship_section_warehouse_address',
	),	
	# Dimensions of product.
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
		'type'  => 'sectionend',
		'id'   	=> 'wp_apiship_section_dimensions',
	),
	# Weight of product.
	array(
		'title' => esc_html__('Weight of product by default', 'apiship'),
		'type'  => 'title',
		'id'    => 'wp_apiship_section_weight',
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
		'id'   	=> 'wp_apiship_section_weight',
	),	
);

return $settings;

# --- EOF