<?php
/**
 * File: docs-form.php
 *
 * @package WP ApiShip
 * @subpackage Templates
 *
 * @since 1.0.0
 */

use ApiShip\Options,
	ApiShip\HTTP;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$labels_file = Options\ApiShip_Options::get_labels_file();

$message = array();

if ( file_exists( $labels_file ) ) {
	
	$data = file_get_contents( $labels_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- локальный файл журнала плагина.

	$data = json_decode($data);

	$response = $data->response;
	$timestamp = isset( $data->timestamp ) ? $data->timestamp : false;

	/**
	 * @todo using `$request_body` in the next versions.
	 */
	// $request_body = $data->request_body;

	if ( isset($response->response->code) && isset($response->body) ) {

		if ( $response->response->code == HTTP\ApiShip_HTTP::OK ) {

			if ( $timestamp ) {
				$message[] = esc_html__('Дата получения наклеек: ', 'apiship') . esc_html( gmdate( 'd.m.Y', $timestamp ) );
				$message[] = '<br />';
			}
			
			$body = json_decode( $response->body );
			
			if ( ! empty( $body->url ) ) {
				$message[] = esc_html__('Ссылка для скачивания наклеек: ', 'apiship') . '<a href="'.esc_url($body->url).'" target="_blank">'.esc_html($body->url).'</a>';
			}
			
			if ( ! empty( $body->failedOrders ) ) {
				
				$message[] = ''; 
				$message[] = '<h3>'.esc_html__('Заказы без наклеек','apiship').'</h3>'; 
				
				$message[] = '<ul>'; 
				foreach( $body->failedOrders as $order_data ) {
					$message[] = '<li>'.esc_html($order_data->orderId) . ': '.esc_html($order_data->message).'</li>';
				}
				$message[] = '</ul>'; 
			}
		}
		
	} else {
		$message[] = esc_html__('Ошибка чтения файла', 'apiship').' <strong>'.esc_html($labels_file).'</strong>';
	}
	
} else {
	$message[] = esc_html__('Файл наклеек не найден:', 'apiship').'&nbsp;<strong>'.esc_html($labels_file).'</strong>';
}

echo wp_kses(
	implode( "\n", $message ),
	array(
		'a'      => array( 'href' => true, 'target' => true ),
		'ul'     => array(),
		'li'     => array(),
		'h3'     => array(),
		'br'     => array(),
		'strong' => array(),
	)
);
			
# --- EOF