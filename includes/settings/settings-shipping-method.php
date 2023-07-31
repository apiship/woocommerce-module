<?php
/**
 * File: settings-shipping-method.php
 *
 * @package WP ApiShip
 * @subpackage Administration
 *
 * @since 1.0.0
 */
 
$settings = array(
	'title' => array(
		'title'       => esc_html__('Название метода', 'wp-apiship'),
		'type'        => 'text',
		'default'     => esc_html__('ApiShip интегратор', 'wp-apiship'),
	),
);	

return $settings;

# --- EOF