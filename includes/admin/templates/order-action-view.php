<?php
/**
 * File: order-action-view.php
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
?>
<div class="wpapiship-order-action-wrapper">
	<div class="wpapiship-viewer-section hidden">
		<div class="extra-buttons">
			<span class="close-viewer-button">
				<button onclick="return false;" class="button button-primary wpapiship-close-viewer" 
					data-order-id="<?php echo $order->get_id(); ?>">
					<?php echo esc_html__('Закрыть', 'wp-apiship'); ?>
				</button>
			</span>
		</div>	
		<div class="wpapiship-log-viewer">
			<pre></pre>
		</div>	
	</div>	
</div>
<?php

# --- EOF