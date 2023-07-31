<?php
/**
 * File: order-action-buttons.php
 *
 * @package WP ApiShip
 * @subpackage Templates
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} 

$integrator_order = self::get_integrator_order('value');

$disable_delete_orders_class = 'hidden';
if ( (int) $integrator_order > 0 ) {
	$disable_delete_orders_class = '';
}
?>
<div class="wpapiship-order-action-wrapper">
	<div class="wpapiship-buttons-section"><?php
		if ( (int) $integrator_order > 0 ) { ?>
			<!--
			<span class="view-orders-status">
				<?php //$status = esc_html__( 'Заказ %1s%2s%3s создан', 'wp-apiship' ); ?>
				<?php //echo sprintf( $status, '<strong>', $integrator_order, '</strong>' ); ?>
			</span>-->
			<!--
			<span class="view-orders-button">
				<button class="button button-primary view-orders" 
					data-connect-id="" 
					data-order-id="<?php // echo $order->get_id(); ?>">
					<?php // echo esc_html__( 'Просмотреть', 'wp-apiship' ); ?>
				</button>
			</span>--><?php
		} else { ?>
			<!--
			<button class="button button-primary post-orders" 
				data-connect-id="" 
				data-order-id="<?php // echo $order->get_id(); ?>">
				<?php // echo esc_html__( 'Отправить заказ', 'wp-apiship' ); ?>
			</button>--><?php
		} ?>
	</div>	
	<div class="wpapiship-viewer-section hidden">
		<div class="extra-buttons">
			<span class="close-viewer-button">
				<button onclick="return false;" class="button button-primary wpapiship-close-viewer" 
					data-order-id="<?php echo $order->get_id(); ?>">
					<?php echo esc_html__('Закрыть', 'wp-apiship'); ?>
				</button>
			</span>
			<!--
			<span class="delete-orders-button <?php // echo $disable_delete_orders_class; ?>">
				<button onclick="return false;" 
					class="button button-primary delete-orders" 
					data-order-id="<?php // echo $order->get_id(); ?>" 
					data-shipping_order_item_id="<?php // echo $shipping_order_item_id; ?>"> 
					<?php // echo esc_html__( 'Удалить заказ', 'wp-apiship' ); ?>
				</button>
			</span>-->
		</div>	
		<div class="wpapiship-log-viewer">
			<pre></pre>
		</div>	
	</div>	
	<!--
	<div class="wpapiship-reload-section hidden">
		<button onclick="return false;" class="button button-primary wpapiship-reload-page">
			<?php // echo esc_html__( 'Перезагрузить', 'wp-apiship' ); ?>
		</button>	
	</div>-->
</div>
<?php

# --- EOF