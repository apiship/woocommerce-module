<?php
/**
 * File: order-metabox-content.php
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

$line_items_shipping = $this->order->get_items( 'shipping' );
foreach ( $line_items_shipping as $item_id=>$item ) {
	/**
	 * $item is WC_Order_Item_Shipping Object.
	 */
	$shipping_order_item_id = $item_id;
}

/**
 * @see woocommerce\includes\wc-order-item-functions.php
 */
$id = wc_get_order_item_meta( 
	$shipping_order_item_id, #$item->get_id(), 
	Options\WP_ApiShip_Options::INTEGRATOR_ORDER_KEY
);

$formatted_meta_data = $item->get_formatted_meta_data( '' );

$tools_class = 'hidden';
// $tools_class = '';
if ( WP_ApiShip\WP_ApiShip_Core::is_godmode() ) {
	$tools_class = '';
}

/**
 * @see meta_data in wp-apiship\includes\class-wp-apiship-shipping-method.php
 */
$meta_data = array();
foreach( $formatted_meta_data as $key=>$meta ) {
	$meta_data[$meta->key] = $meta;
}
unset( $formatted_meta_data );

/**
 * Icon URL.
 * @test for no image provider.
 * $icon_url = WP_ApiShip\WP_ApiShip_Core::get_provider_icon_url('zabberi');
 */
$icon_url = $this->get_provider_icon_url($meta_data['tariffProviderKey']->value);

/**
 * Enabled keys.
 */
$enabled_meta_keys = array(
	// 'tariffId' 	 => esc_html__('ID тарифа','wp-apiship'),
	'tariffName' => esc_html__('Название тарифа','wp-apiship'),
	'daysMin' 	 => esc_html__('Минимальный срок доставки, дней','wp-apiship'),
	'daysMax' 	 => esc_html__('Максимальный срок доставки, дней','wp-apiship'),
	'places'	 => '',
);

/**
 * Pickup date.
 */
$pickup_date = false;
if ( $this->integrator_order_exists() ) {

	/**
	 * Pickup Date.
	 * Will be set via JS.
	 */
	$pickup_date = '';
	
} else {
	
	/**
	 * May be one day ahead?
	 * $pickup_date = date('Y-m-d', strtotime('+1 day'));
	 * Now let's get order created date.
	 */
	// $pickup_date = $order->get_date_created();
	$pickup_date = $this->order->get_date_created();
	$pickup_date = $pickup_date->format('Y-m-d');
}

/**
 * Contact name.
 */
// $contact_name = $this->get_contact_name();

/**
 * City of your store.
 */
$store_city = $this->get_store_city();

/**
 * City of your warehouse.
 */
$warehouse_city = $this->get_warehouse_city();

/**
 * Point in for store.
 */
$point_in_store_city_address = $this->get_point_in_address('store');

/**
 * Point in for warehouse.
 */
$point_in_warehouse_city_address = $this->get_point_in_address('warehouse');

/**
 * To get price @see woocommerce\includes\admin\meta-boxes\views\html-order-shipping.php
 */
$_price = ' - ' . wp_kses_post( wc_price( $item->get_total(), array( 'currency' => $this->order->get_currency() ) ) );
$_refunded = -1 * $this->order->get_total_refunded_for_item( $item_id, 'shipping' );
if ( $_refunded ) {
	$_price .= wp_kses_post( '<small class="refunded">' . wc_price( $_refunded, array( 'currency' => $this->order->get_currency() ) ) . '</small>' );
}

/**
 * Shipping methods.
 */
$shipping_methods = $this->get_shipping_methods();

/**
 * Is delivery to point.
 */
$tariff = json_decode($meta_data['tariff']->value);
$isDeliveryToPoint = false;
if (isset($tariff->isDeliveryToPoint)) {	
	$isDeliveryToPoint = $tariff->isDeliveryToPoint;
}

?>
<div class="order-shipping-message-wrapper" data-order_item_id="<?php echo esc_attr($shipping_order_item_id); ?>">
	<div class="content"></div>
</div>
<div class="order-shipping-wrapper order-hidden" data-order_item_id="<?php echo esc_attr($shipping_order_item_id); ?>">
	<div class="shipping--box left-sidebar">
		<div class="card provider-card provider-<?php echo $meta_data['tariffProviderKey']->value; ?>" data-provider-key="<?php echo $meta_data['tariffProviderKey']->value; ?>">
			<div class="card--item card--logo">
				<img class="logo" src="<?php echo $icon_url; ?>" />
			</div>
			<div class="card--item card--name"><?= $this->get_provider_name($meta_data['tariffProviderKey']->value) ?></div>
			<div class="card--item card--description"></div>
			<?php if ( $store_city && $point_in_store_city_address ) { ?>
				<div class="card--item card--point-in-id store">
					<hr />
					<div class="caption">
						<?php esc_html_e('Пункт приёма заказов','wp-apiship'); ?>
						<?php esc_html_e('из магазина','wp-apiship'); ?>
						<br />
						<?php esc_html_e('г.','wp-apiship'); ?>
						<?php echo $store_city; ?>
					</div>
					<div class="address">
						<?php echo $point_in_store_city_address; ?>
					</div>					
				</div>
			<?php } else { ?>
				<div class="card--item card--point-in-id store">
					<hr />
					<div class="caption">
						<?php esc_html_e('Пункт приёма заказов','wp-apiship'); ?>
						<?php esc_html_e('из магазина','wp-apiship'); ?>
						<br />
						<?php esc_html_e('г.','wp-apiship'); ?>
						<?php echo $store_city; ?>
					</div>
					<div class="address">
						---&nbsp;<?php esc_html_e('не установлен по умолчанию','wp-apiship'); ?>&nbsp;---
					</div>					
				</div>			
			<?php } ?>
			<?php if ( $warehouse_city && $point_in_warehouse_city_address ) { ?>
				<div class="card--item card--point-in-id warehouse">
					<hr />
					<div class="caption">
						<?php esc_html_e('Пункт приёма заказов','wp-apiship'); ?>
						<?php esc_html_e('со склада','wp-apiship'); ?>
						<br />						
						<?php esc_html_e('г.','wp-apiship'); ?>
						<?php echo $warehouse_city; ?>
					</div>
					<div class="address">
						<?php echo $point_in_warehouse_city_address; ?>
					</div>					
				</div>
			<?php } ?>				
		</div><!-- .provider-card --><?php
		if ( $this->integrator_order_exists() ) { ?>
			<div class="card integrator-order-label-card">
				<div class="card--item card--label">
					<span class="card-caption"><?php esc_html_e('Наклейка','wp-apiship'); ?></span>
				</div>			
				<div class="card--item label-exists hidden">
					<img class="" src="<?php $this->the_order_label_image(); ?>" />
					<button class="button button-secondary label-download" onclick="return false;" data-url="">
						<?php esc_html_e('Открыть','wp-apiship'); ?>
						<?php // esc_html_e('Скачать','wp-apiship'); ?>
					</button>
					<!-- <iframe id="iframe-label-download" name="iframe-label-download" frameborder="1" width="1" height="1"></iframe>-->

				</div>	
				<div class="card--item label-not-exists hidden">
					<div class="label-caption"><?php esc_html_e('Наклейка не доступна для скачивания','wp-apiship'); ?></div>
					<div class="label-message"></div>
				</div>
			</div><!-- .integrator-order-label-card --><?php
		} ?>	
	</div><!-- .shipping--box -->
	<div class="shipping--box display-meta">
		<!-- Tariff Section -->
		<div class="meta--item meta-key">&nbsp;</div>
		<div class="meta--item meta-title">
			<h3><?php esc_html_e('Тариф','wp-apiship'); 
			if (!$this->integrator_order_exists()) { ?>
				<a href="#" onclick="return false;" class="edit-data edit-tariff" data-open="0"></a>
			<?php } ?></h3>
		</div>

		<div class="meta-key edit-tariff-section hidden"></div>
		<div class="meta-value edit-tariff-section hidden meta--item meta-value" style="max-width: 90%;">
			
			<button id="save_admin_method" class="button button-secondary" onclick="return false;">
				<?php esc_html_e('Сохранить', 'wp-apiship'); ?>
			</button>

			<div class="meta--item meta-key hidden point-out-save-message" id="updateAdminTariffMessage">Данные успешно сохранены</div>

			<p style="padding-bottom: 20px;"><b><?php esc_html_e('Выберите новый тариф. Выбор ПВЗ и тарифа для способов доставки до ПВЗ будет доступен после сохранения в разделе "Пункт выдачи заказа".', 'wp-apiship'); ?></b></p>

			<?php 

			foreach ($shipping_methods as $method) {
				$method_id = $method['id'];
				$method_title = $method['label'];
				$price = $method['cost'];
				$tariff = json_decode($method['meta_data']['tariff']);
				$is_delivery_to_point = $tariff->isDeliveryToPoint;
				$tariffProviderKey = $method['meta_data']['tariffProviderKey'];

				$price_text = ', цена: ';
				if ($is_delivery_to_point === true) {
					$price_text = ', цена от ';
				}

				echo '<div style="margin-bottom: 10px;"><label>';
				echo '<input type="radio" name="selected_shipping_method" value="' . esc_attr($method_id) . '"';

				// if ($method_id === $selected_shipping) {
				// 	echo ' checked';
				// }

				echo ' data-order-id="' . $post->ID . '"';
				echo ' data-cost="' . $price . '"';
				echo ' data-meta-data="' . htmlspecialchars(json_encode($method['meta_data'])) . '"';
				echo ' data-method-title="' . $method_title . '"';

				echo '>';
				echo $method_title . $price_text . wc_price($price);
				echo '</label></div>';
			}

			?>
			
			<div style="height: 20px;"></div>
		</div>

		<div class="meta--item meta-key">
			<?php esc_html_e('Полное название',''); ?>:
		</div>
		<div class="meta--item meta-value">
			<?php echo esc_html( $item->get_name() ? $item->get_name() : __( 'Shipping', 'woocommerce' ) ) . $_price; ?>
			<a href="#" onclick="return false;" class="edit-data edit-price"></a>
		</div>
		<!-- Pickup type -->
		<div class="meta--item meta-key">
			<?php esc_html_e('Тип забора груза','wp-apiship'); ?>:
		</div>
		<div class="meta--item meta-value">
			<span class="pickup-type"><?php $this->the_pickup_type_text(); ?></span>
			<input type="text" id="pickup-type" 
				name="pickup-type" 
				class="wpapiship-transmitting-field field-to-control hidden" 
				data-request-id="pickupType" 
				value="<?php echo $this->the_pickup_type(); ?>" />
		</div>
		<!-- Delivery type -->
		<div class="meta--item meta-key">
			<?php esc_html_e('Тип доставки','wp-apiship'); ?>:
		</div>
		<div class="meta--item meta-value">
			<span class="delivery-type"><?php $this->the_delivery_type_text(); ?></span>
			<input type="text" id="delivery-type" 
				name="delivery-type" 
				class="wpapiship-transmitting-field field-to-control hidden" 
				data-request-id="deliveryType" 
				value="<?php echo $this->the_delivery_type(); ?>" />
		</div>

		<div class="meta-key edit-price-section hidden"></div>
		<div class="meta-value edit-price-section hidden meta--item meta-value" style="max-width: 90%;">
		
			<div style="display: flex; gap: 15px; flex-wrap: wrap; margin-top: 10px;">
				<div>
					<input type="text" id="edit_price_input" placeholder="Введите новую цену">
				</div>

				<button id="save_price" class="button button-secondary" onclick="return false;">
					<?php esc_html_e('Сохранить', 'wp-apiship'); ?>
				</button>
			</div>

			<div class="meta--item meta-key hidden point-out-save-message" id="updatePriceMessage">Данные успешно сохранены</div>
		</div>

		<?php
		foreach ( $meta_data as $_key=>$_item ) : 
			if ( array_key_exists( $_key, $enabled_meta_keys ) ) {
				switch($_key) :
					case 'daysMin' :
						if ( $_item->value == $meta_data['daysMax']->value ) {	?>
							<div class="meta--item meta-key"><?php esc_html_e('Cрок доставки, дней','wp-apiship'); ?>:</div>
							<div class="meta--item meta-value"><?php echo $_item->value; ?></div><?php 										
						} else {	?>
							<div class="meta--item meta-key"><?php echo $enabled_meta_keys[$_key]; ?>:</div>
							<div class="meta--item meta-value"><?php echo $_item->value; ?></div><?php 								
						}
						break;
					case 'daysMax' :
						if ( $_item->value == $meta_data['daysMin']->value ) {
							// Do nothing.
						} else {	?>
							<div class="meta--item meta-key"><?php echo $enabled_meta_keys[$_key]; ?>:</div>
							<div class="meta--item meta-value"><?php echo $_item->value; ?></div><?php 								
						}					
						break;
					case 'places' : 
						if ( ! empty($_item->value) ) { 
							$places = json_decode($_item->value); ?>
							<!-- Dimensions and Weight unit section -->
							<div class="meta--item meta-key">&nbsp;</div>
							<div class="meta--item meta-title">
								<h3><?php esc_html_e('Размеры и вес места','wp-apiship'); 
									if ( ! $this->integrator_order_exists() ) { ?>
										<a href="#" onclick="return false;" class="edit-data edit-dimensions"></a><?php
									} ?>	
								</h3>
							</div>
							<!-- length -->
							<div class="meta--item meta-key meta-length"><?php esc_html_e('Длина','wp-apiship'); ?>:</div>
							<div class="meta--item meta-value">
								<input type="text" 
									name="wpapiship-places_0_length" 
									id="wpapiship-places_0_length" 
									size="5" 
									value="<?php echo $this->get_place_dimension('length', 0); ?>" 
									placeholder="<?php echo $places[0]->length; ?>" 
									data-init-value="<?php echo $places[0]->length; ?>" 
									disabled="disabled" 
									class="wpapiship-transmitting-field wpapiship-editable-field disabled" 
									data-request-id="custom-length"
									data-place-order="0" 
									data-dimension="length" />
								<?php echo Options\WP_ApiShip_Options::DIMENSIONS_UNIT; ?>.
							</div>
							<!-- width -->
							<div class="meta--item meta-key meta-width"><?php esc_html_e('Ширина','wp-apiship'); ?>:</div>
							<div class="meta--item meta-value">
								<input type="text" 
									name="wpapiship-places_0_width" 
									id="wpapiship-places_0_width" 
									size="5" 
									value="<?php echo $this->get_place_dimension('width', 0); ?>" 
									placeholder="<?php echo $places[0]->width; ?>" 
									data-init-value="<?php echo $places[0]->width; ?>" 
									disabled="disabled" 
									class="wpapiship-transmitting-field wpapiship-editable-field disabled" 
									data-request-id="custom-width" 
									data-place-order="0"									
									data-dimension="width" />
								<?php echo Options\WP_ApiShip_Options::DIMENSIONS_UNIT; ?>.
							</div>
							<!-- height -->
							<div class="meta--item meta-key meta-height"><?php esc_html_e('Высота','wp-apiship'); ?>:</div>
							<div class="meta--item meta-value">
								<input type="text" 
									name="wpapiship-places_0_height" 
									id="wpapiship-places_0_height" 
									size="5" 
									value="<?php echo $this->get_place_dimension('height', 0); ?>" 
									placeholder="<?php echo $places[0]->height; ?>" 
									data-init-value="<?php echo $places[0]->height; ?>" 
									disabled="disabled" 
									class="wpapiship-transmitting-field wpapiship-editable-field disabled" 
									data-request-id="custom-height" 
									data-place-order="0" 
									data-dimension="height" />		
								<?php echo Options\WP_ApiShip_Options::DIMENSIONS_UNIT; ?>.
							</div>
							<!-- weight -->
							<div class="meta--item meta-key"><?php esc_html_e('Вес','wp-apiship'); ?>:</div>
							<div class="meta--item meta-value">
								<input type="text" 
									name="wpapiship-places_0_weight" 
									id="wpapiship-places_0_weight" 
									size="5" 
									value="<?php echo $places[0]->weight; ?>" 
									placeholder="<?php echo $places[0]->weight; ?>" 
									data-init-value="<?php echo $places[0]->weight; ?>" 
									disabled="disabled" 
									class="wpapiship-transmitting-field wpapiship-editable-field disabled" 
									data-request-id="custom-weight" 
									data-place-order="0" 
									data-dimension="weight" />		
								<?php echo Options\WP_ApiShip_Options::WEIGHT_UNIT; ?>.
							</div>								
							<?php 	
						}
						break;
					default: ?>
						<div class="meta--item meta-key"><?php echo $enabled_meta_keys[$_key]; ?>:</div>
						<div class="meta--item meta-value"><?php echo $_item->value; ?></div><?php 					
				endswitch;
			}
		endforeach; ?>
		<!-- Order title -->
		<div class="meta--item meta-key">&nbsp;</div>
		<div class="meta--item meta-title"><h3><?php esc_html_e('Заказ ApiShip','wp-apiship'); ?></h3></div>			
		<!-- Order ID-->
		<div class="meta--item meta-key meta-order-id meta-caption"><?php esc_html_e('Номер заказа','wp-apiship'); ?>:</div>
		<?php
		if ( $this->integrator_order_exists() ) { ?>
			<div class="meta--item meta-value">
				<input type="text" 
					class="integrator-order-id" 
					disabled="disabled" 
					value="<?php echo $this->get_integrator_order_id(); ?>" />
			</div><?php
		} else {	?>		
			<div class="meta--item meta-value">
				<input type="text"
					class="integrator-order-id" 
					disabled="disabled" 
					value="<?php esc_html_e('не создан','wp-apiship'); ?>" />
				<span class="integrator-order-warning dashicons dashicons-warning hidden" title=""></span>	
			</div><?php
		} 	?>	
		<!-- Provider number (tracking-number) -->
		<div class="meta--item meta-key meta-order-id meta-caption"><?php esc_html_e('Трек-номер','wp-apiship'); ?>:</div>
		<?php
		if ( $this->integrator_order_exists() ) { ?>
			<div class="meta--item meta-value">
				<input type="text" 
					class="integrator-order-id" 
					disabled="disabled" 
					value="<?php echo $meta_data['providerNumber']->value; ?>" />
			</div><?php
		} else {	?>		
			<div class="meta--item meta-value">
				<input type="text"
					class="integrator-order-id" 
					disabled="disabled" 
					value="<?php esc_html_e('не создан','wp-apiship'); ?>" />
				<span class="integrator-order-warning dashicons dashicons-warning hidden" title=""></span>	
			</div><?php
		} 	?>	
		<!-- Pickup date -->
		<?php $field = Options\WP_ApiShip_Options::get_metabox_field('pickupDate'); ?>
		<div class="meta--item meta-key meta-pickup-date meta-caption"><?php echo $field['caption']; ?>:</div>
		<div class="meta--item meta-value">
			<input type="<?php echo $field['type']; ?>" 
				value="<?php echo $pickup_date; ?>" 
				name="<?php echo $field['name']; ?>" 
				id="<?php echo $field['id']; ?>" 
				class="wpapiship-transmitting-field" 
				data-request-id="<?php echo $field['requestID']; ?>" />
		</div>
		<!-- Sender Title -->
		<div class="meta--item meta-key">&nbsp;</div>
		<div class="meta--item meta-title"><?php $this->the_sender_title(); ?></div>					
		<!-- Sender: contact name -->
		<?php $field = Options\WP_ApiShip_Options::get_metabox_field('contactName'); ?>
		<div class="meta--item meta-key meta-contact-name meta-caption"><?php echo $field['caption']; ?>:</div>
		<div class="meta--item meta-value">
			<input type="<?php echo $field['type']; ?>" 
				value="<?php echo $this->get_contact_name(); ?>" 
				name="<?php echo $field['name']; ?>" 
				id="<?php echo $field['id']; ?>" 
				placeholder="<?php echo $field['placeholder']; ?>" 
				data-init-value="<?php echo $field['placeholder']; ?>" 
				size="<?php echo $field['size']; ?>" 
				class="wpapiship-transmitting-field" 
				data-request-id="<?php echo $field['requestID']; ?>" />
		</div>
		<!-- Sender: contact phone -->
		<?php $field = Options\WP_ApiShip_Options::get_metabox_field('phone'); ?>
		<div class="meta--item meta-key meta-contact-name meta-caption"><?php echo $field['caption']; ?>:</div>
		<div class="meta--item meta-value">
			<input type="<?php echo $field['type']; ?>" 
				value="<?php echo $this->get_phone(); ?>" 
				name="<?php echo $field['name']; ?>" 
				id="<?php echo $field['id']; ?>" 
				placeholder="<?php echo $field['placeholder']; ?>" 
				data-init-value="<?php echo $field['placeholder']; ?>" 
				size="<?php echo $field['size']; ?>" 
				class="wpapiship-transmitting-field" 
				data-request-id="<?php echo $field['requestID']; ?>" />
		</div>		
		<!-- Sender: address -->
		<div class="meta--item meta-key meta-caption"><?php esc_html_e('Адрес','wp-apiship'); ?>:</div>
		<div class="meta--item meta-value meta-value-sender-address">
			<span><?php $this->the_sender_address(); ?></span>
		</div>
		<!-- Divider -->
		<div class="meta--item meta-key">&nbsp;</div>
		<div class="meta--item meta-value meta-divider"><hr /></div>
		<!-- Shipping point in: Title -->
		<div class="meta--item meta-key">&nbsp;</div>
		<div class="meta--item meta-title">
			<?php esc_html_e('Пункт приёма заказа','wp-apiship'); ?><?php
			if ( ! $this->integrator_order_exists() ) { ?>
				<a href="#" onclick="return false;" class="edit-data edit-point-in"></a>
				<a href="#" onclick="return false;" class="delete-data delete-point-in"></a><?php
			} ?>	
		</div>
		<!-- Shipping point in: ID -->
		<?php $field = Options\WP_ApiShip_Options::get_metabox_field('pointInId'); ?>		
		<div class="meta--item meta-key meta-caption"><?php echo $field['caption']; ?>:</div>
		<div class="meta--item meta-value">
			<input type="<?php echo $field['type']; ?>" 
				value="<?php echo $this->get_order_point_in_id(); ?>" 
				name="<?php echo $field['name']; ?>" 
				id="<?php echo $field['id']; ?>" 
				size="<?php echo $field['size']; ?>" 
				class="meta-value-point-in-id wpapiship-transmitting-field" 
				disabled="disabled" 
				data-request-id="<?php echo $field['requestID']; ?>" />		
		</div>		
		<!-- Shipping point in: Address -->
		<?php $field = Options\WP_ApiShip_Options::get_metabox_field('pointInAddress'); ?>			
		<div class="meta--item meta-key meta-caption"><?php echo $field['caption']; ?>:</div>
		<div class="meta--item meta-value">
			<input type="<?php echo $field['type']; ?>" 
				value="<?php echo $this->get_order_point_in_address(); ?>" 
				name="<?php echo $field['name']; ?>" 
				id="<?php echo $field['id']; ?>" 
				class="meta-value-point-in-address wpapiship-transmitting-field" 
				disabled="disabled" 
				data-request-id="<?php echo $field['requestID']; ?>" />		
		</div><?php
		if ( ! $this->integrator_order_exists() ) : ?>
			<!-- Shipping point in: Change address -->
			<div class="meta--item meta-key meta-caption meta-key-point-in-new-address hidden"><?php esc_html_e('Изменить пункт приёма','wp-apiship'); ?>:</div>
			<div class="meta--item meta-value meta-value-point-in-new-address hidden"><?php 
				$point_in_select_template = $this->get_template('point-in-select.php');
				$point_in_select_action = 'initial';
				require($point_in_select_template); ?>
			</div><?php
		endif; ?>		
		<!-- Divider -->
		<div class="meta--item meta-key">&nbsp;</div>
		<div class="meta--item meta-value meta-divider"><hr /></div>
		<!-- Shipping point out: Title -->		
		<div class="meta--item meta-key">&nbsp;</div>
		<div class="meta--item meta-title">
			<?php 
			esc_html_e('Пункт выдачи заказа','wp-apiship');
			if (!$this->integrator_order_exists() and $isDeliveryToPoint === true) { ?>
				<a href="#" onclick="return false;" class="edit-data edit-point-out"></a>
				<a href="#" onclick="return false;" class="delete-data delete-point-out"></a><?php
			} ?>			
		</div>		
		<!-- Shipping point out: ID -->
		<?php $field = Options\WP_ApiShip_Options::get_metabox_field('pointOutId'); ?>		
		<div class="meta--item meta-key meta-caption"><?php echo $field['caption']; ?>:</div>
		<div class="meta--item meta-value">
			<input type="<?php echo $field['type']; ?>" 
				value="<?php echo $this->get_order_point_out_id(); ?>" 
				name="<?php echo $field['name']; ?>" 
				id="<?php echo $field['id']; ?>" 
				size="<?php echo $field['size']; ?>" 
				class="meta-value-point-out-id wpapiship-transmitting-field" 
				disabled="disabled" 
				data-request-id="<?php echo $field['requestID']; ?>" />		
		</div>	
		<!-- Shipping point out: Address -->
		<?php $field = Options\WP_ApiShip_Options::get_metabox_field('pointOutAddress'); ?>			
		<div class="meta--item meta-key meta-caption"><?php echo $field['caption']; ?>:</div>
		<div class="meta--item meta-value">
			<input type="<?php echo $field['type']; ?>" 
				value="<?php echo $this->get_order_point_out_address(); ?>" 
				name="<?php echo $field['name']; ?>" 
				id="<?php echo $field['id']; ?>" 
				class="meta-value-point-out-address wpapiship-transmitting-field" 
				disabled="disabled" 
				data-request-id="<?php echo $field['requestID']; ?>" />	
			<div class="meta--item meta-key hidden point-out-save-message" id="pointOutSaveMessage">Данные успешно сохранены</div>	
		</div>
		<!-- Divider -->
		<div class="meta--item meta-key">&nbsp;</div>
		<div class="meta--item meta-value meta-divider"><hr /></div>			
		<!-- Action buttons section -->
		<!-- Action buttons: Title -->
		<div class="meta--item meta-key">&nbsp;</div>
		<div class="meta--item meta-title"><?php esc_html_e('Действия','wp-apiship'); ?></div>		
		<div class="meta--item meta-key">&nbsp;</div>		
		<?php
		if ( $this->integrator_order_exists() ) { ?>
			<div class="meta--item meta-value action-buttons wpapiship-buttons">
				<button class="button button-secondary tools-orders <?php echo $tools_class; ?>" onclick="return false;">
					<?php esc_html_e('Инструменты','wp-apiship'); ?>
				</button>
				
				<div class="wpapiship-action-button-wrapper">
					<button class="button button-secondary wpapiship-action-button wpapiship-cancel-order" onclick="return false;">
						<?php esc_html_e('Отменить заказ','wp-apiship'); ?>
					</button>
					<span class="wpapiship-confirmation-bar wpapiship-action-confirmation wpapiship-cancel-order-confirmation hidden">
						<span class="message"><?php esc_html_e('Отменить?','wp-apiship'); ?></span> 
						<button onclick="return false;" href="#" class="confirmation-button yes">
							<?php esc_html_e('Да','wp-apiship'); ?>
						</button>
						<button onclick="return false;" href="#" class="confirmation-button no">
							<?php esc_html_e('Нет','wp-apiship'); ?>
						</button>
					</span>	
				</div>
				
				<div class="wpapiship-action-button-wrapper">
					<button class="button button-secondary wpapiship-action-button wpapiship-delete-order" onclick="return false;">
						<?php esc_html_e('Удалить из ApiShip','wp-apiship'); ?>
					</button>
					<span class="wpapiship-confirmation-bar wpapiship-action-confirmation wpapiship-delete-order-confirmation hidden">
						<span class="message"><?php esc_html_e('Удалить?','wp-apiship'); ?></span> 
						<button onclick="return false;" href="#" class="confirmation-button yes">
							<?php esc_html_e('Да','wp-apiship'); ?>
						</button>
						<button onclick="return false;" href="#" class="confirmation-button no">
							<?php esc_html_e('Нет','wp-apiship'); ?>
						</button>
					</span>	
				</div>
			</div><?php		
		} else { ?>
			<div class="meta--item meta-value action-buttons">
				<button class="button button-secondary validate-orders" onclick="return false;">
					<?php esc_html_e('Валидация','wp-apiship'); ?>
				</button>
				<button class="button button-secondary post-orders" onclick="return false;">
					<?php esc_html_e('Создать заказ','wp-apiship'); ?>
				</button>
				<button class="button button-secondary tools-orders <?php echo $tools_class; ?>" onclick="return false;">
					<?php esc_html_e('Инструменты','wp-apiship'); ?>
				</button>
			</div><?php
		} ?>		
	</div><!-- .shipping--box -->
</div><!-- .order-shipping-wrapper -->

<div style="display: none;" id="adminTariffList"><?= htmlspecialchars_decode($meta_data['tariffList']->value) ?></div>
<div style="display: none;" id="adminTariff"><?= htmlspecialchars_decode($meta_data['tariff']->value) ?></div>

<!-- Shipping point out: Map -->
<div class="meta--item meta-key wpapiship-ymap-row">&nbsp;</div>
<div class="meta--item meta-value wpapiship-ymap-row">
	<!-- yandex map -->
	<div id="wpapiship-ymap" class="hidden"></div>	
</div>	

<!-- order actions -->
<div class="wpapiship-order-action-wrapper">
	<div class="wpapiship-viewer-section hidden">
		<div class="extra-buttons">
			<span class="close-viewer-button">
				<button onclick="return false;" class="button button-primary wpapiship-close-viewer" 
					data-order-id="<?php echo $this->order->get_id(); ?>">
					<?php echo esc_html__('Закрыть', 'wp-apiship'); ?>
				</button>
			</span>
		</div>	
		<div class="wpapiship-log-viewer">
			<pre></pre>
		</div>
	</div>
</div><!-- .wpapiship-order-action-wrapper -->
<?php if ( $this->integrator_order_exists() ) { ?>
	<div class="wpapiship-order-status-history">
		<div class="meta--item meta-title"><h3><?= esc_html__('История статусов', 'wp-apiship'); ?></h3></div>
		<table class="wp-list-table widefat fixed striped table-view-list">
			<thead>
				<tr>
					<th><?php echo esc_html__('Статус ApiShip', 'wp-apiship'); ?></th>
					<th><?php echo esc_html__('Статус СД', 'wp-apiship'); ?></th>
					<th><?php echo esc_html__('Описание', 'wp-apiship'); ?></th>
					<th><?php echo esc_html__('Получен', 'wp-apiship'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($this->get_order_statuses() as $row) { ?>
					<tr>
						<td><?= $row->name ?></td>
						<td><?= $row->providerName ?></td>
						<td><?= $row->providerDescription ?></td>
						<td><?= (new DateTime($row->created))->format('Y-m-d H:i:s') ?></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>
<?php } ?>
<?php
# --- EOF