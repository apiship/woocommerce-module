<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wpapiship-checkout-modal">
    <!-- map provider select -->
    <?php if ($point_display_mode === 3) : ?>
        <div id="wpapiship_map_options" class="wpapiship-map-options-container">
            <select id="wpapiship_provider_select" class="wpapiship-map-select" >
                <option value="all-providers"><?php esc_html_e('Все службы доставки', 'apiship'); ?></option>
                <?php foreach ($providers as $provider) : ?>
                    <option value="<?php echo esc_attr( $provider['key'] ); ?>"><?php echo esc_html( $provider['name'] ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>
    <!-- map provider select -->

    <!-- yandex map -->
    <div id="wpapiship-checkout-ymap"></div>
    <!-- yandex map -->
</div>
