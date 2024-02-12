<?php
/**
 * File: docs-form.php
 *
 * @package WP ApiShip
 * @subpackage Templates
 *
 * @since 1.0.0
 */

use WP_ApiShip\Options,
	WP_ApiShip\HTTP;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$labels_file = Options\WP_ApiShip_Options::get_labels_file();

$message = array();

// if ( file_exists( $labels_file ) ) {
	
// 	$handle = fopen($labels_file, "r");
// 	$data = fread($handle, filesize($labels_file));
// 	fclose($handle);

// 	$data = json_decode($data);

// 	$response = $data->response;
// 	$timestamp = isset( $data->timestamp ) ? $data->timestamp : false;

// 	/**
// 	 * @todo using `$request_body` in the next versions.
// 	 */
// 	// $request_body = $data->request_body;

// 	if ( isset($response->response->code) && isset($response->body) ) {

// 		if ( $response->response->code == HTTP\WP_ApiShip_HTTP::OK ) {

// 			if ( $timestamp ) {
// 				$message[] = esc_html('Дата получения наклеек: ','apiship') . date( 'd.m.Y', $timestamp );
// 				$message[] = '<br />';
// 			}
			
// 			$body = json_decode( $response->body );
			
// 			if ( ! empty( $body->url ) ) {
// 				$message[] = 'Ссылка для скачивания наклеек: <a href="'.$body->url.'" target="_blank">'.$body->url.'</a>';
// 			}
			
// 			if ( ! empty( $body->failedOrders ) ) {
				
// 				$message[] = ''; 
// 				$message[] = '<h3>'.esc_html__('Заказы без наклеек','apiship').'</h3>'; 
				
// 				$message[] = '<ul>'; 
// 				foreach( $body->failedOrders as $order_data ) {
// 					$message[] = '<li>'.$order_data->orderId . ': '.$order_data->message.'</li>';
// 				}
// 				$message[] = '</ul>'; 
// 			}
// 		}
		
// 	} else {
// 		$message[] = 'Ошибка чтения файла <strong>'.$labels_file.'</strong>';
// 	}
	
// } else {
// 	$message[] = 'Файл наклеек не найден:&nbsp;<strong>'.$labels_file.'</strong>';
// }

echo implode( "\n", $message );
			
# --- EOF