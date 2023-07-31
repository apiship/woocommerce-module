<?php
/**
 * File: provider-card.php
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

$opts = Options\WP_ApiShip_Options::get_options();

ob_start();
?>
<div class="provider-card {{providerClass}}" data-provider-key="{{providerKey}}">
	<div class="card--item card--label">
		<span class="provider-select-label provider-selected dashicons dashicons-yes-alt {{selected-hidden}}"></span>
	</div>
	<div class="card--item card--logo">
		<img class="logo {{classes}}" src="{{logoURL}}" />
	</div>
	<div class="card--item card--name"></div>
	<div class="card--item card--description"></div>
	<div class="card--item card--point-in-id pickup-type">
		<div class="caption">
			<?php esc_html_e('Способ отгрузки заказов','wp-apiship'); ?>
		</div>
		<div style="margin-top: 5px;"> 
			<label class="provider-card-pickup-label">
				<input {{pickuptype1}} type="checkbox" class="provider-card-pickup-select pickupType1" name="pickup_types[]" value="1"> <?php esc_html_e('Курьер службы доставки приезжает на мой склад за заказами', 'wp-apiship'); ?>
			</label>
			<label class="wpapiship-pickup-label">
				<input {{pickuptype2}} type="checkbox" class="provider-card-pickup-select pickupType2" name="pickup_types[]" value="2"> <?php esc_html_e('Я сам привожу заказы в пункт приема службы доставки', 'wp-apiship'); ?>
			</label>
		</div>
		<div class="card--item pickup-point hidden">
			<div class="caption">
				<?php esc_html_e('Пункт приёма заказов в СД','wp-apiship'); ?>
			</div><?php 
			$point_in_select_type = 'pickup';
			require('point-in-select.php'); ?>
		</div>
	</div>
</div>			
<?php
return ob_get_clean();

# --- EOF