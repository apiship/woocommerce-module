<?php
/**
 * File: order-aggregator-order.php
 *
 * @package WP ApiShip
 * @subpackage Templates
 *
 * @since 1.0.0
 */

use WP_ApiShip\Options;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} 

/**
 * To get all meta.
 * $meta_data = $item->get_formatted_meta_data( '' );
 */

/**
 * @see woocommerce\includes\wc-order-item-functions.php
 */
$id = wc_get_order_item_meta( 
	$item->get_id(), 
	Options\WP_ApiShip_Options::INTEGRATOR_ORDER_KEY
);

$integrator_order_id = false;
if ( (int) $id > 0 ) {
	$integrator_order_id = $id;
}

$order_id_html = '';
$order_message_html = '';
if ( $integrator_order_id  ) {
	$order_id_html = '<span class="wpapiship-integrator-order-id">'.$integrator_order_id.'</span>';
	$response = self::get_orders_status($integrator_order_id);
	$message = esc_html__('Заказ создан', 'wp-apiship');	
} else {
	$order_id_html = '<span class="wpapiship-integrator-order-id no-value">'.Options\WP_ApiShip_Options::INTEGRATOR_ORDER_INIT_VALUE.'</span>';;
	$message = esc_html__('Заказ не создан', 'wp-apiship');	
}
$wpapiship_debug_class = '';
if ( self::is_godmode(true) ) {
	$wpapiship_debug_class = 'wpapiship-debug wpapiship-open-meta';
}
?>
<div class="view">
	<table cellspacing="0" class="display_meta">
		<tr>
			<th class="<?php echo $wpapiship_debug_class; ?>"><?php esc_html_e('Заказ в системе ApiShip', 'wp-apiship'); ?>:</th>
			<td>
				<div id="wpapiship-integrator-line-items"  
					class="wpapiship-integrator-line-items" 
					data-shipping-order-item-id="<?php echo self::$shipping_order_item_id; ?>">
					<div class="integrator-line--item integrator-order-id">
						<?php echo $integrator_order_id; ?>
					</div>				
					<div class="integrator-line--item integrator-order-status">
						<?php echo $message; ?>
					</div>
					<div class="integrator-line--item">
						<span title="Валидация данных перед созданием заказа" 
							class="validate-orders dashicons dashicons-code-standards <?php if($integrator_order_id){ echo 'hidden';} else { echo '';} ?>">
						</span>
						<span 
							title="Создать заказ в системе <?php echo Options\WP_ApiShip_Options::BRAND; ?>"  
							class="post-orders dashicons dashicons-welcome-add-page <?php if($integrator_order_id){ echo 'hidden';} else { echo '';} ?>">
						</span>
						<span title="Получить информацию по заказу" 
							class="view-orders dashicons dashicons-search <?php if($integrator_order_id){ echo '';} else { echo 'hidden';} ?>">
						</span>
						<span title="Получить статус заказа" 
							class="status-orders dashicons dashicons-external <?php if($integrator_order_id){ echo '';} else { echo 'hidden';} ?>">
						</span>
						<span title="Tools" 
							class="tools-orders dashicons dashicons-admin-tools <?php if( self::is_godmode() ){ echo '';} else { echo 'hidden';} ?>">
						</span>
						<span title="Удалить заказ" 
							class="delete-orders dashicons dashicons-trash <?php if($integrator_order_id){ echo '';} else { echo 'hidden';} ?>">
						</span>						
					</div>
					<div class="integrator-line--item">
						<span class="delete-orders-confirmation hidden">
							<span class="message">Удалить?</span> 
							<a onclick="return false;" href="#" class="confirmation-yes">Да</a>
							<a onclick="return false;" href="#" class="confirmation-no">Нет</a>
						</span>	
					</div>
				</div><!-- .wpapiship-integrator-line-items -->
			</td>
		</tr>
	</table>
</div><?php

# --- EOF