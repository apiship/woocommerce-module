<div class="wpapiship-checkout-modal">
    <!-- map provider select -->
    <?php if ($point_display_mode === 3) : ?>
        <div id="wpapiship_map_options" class="wpapiship-map-options-container">
            <select id="wpapiship_provider_select" class="wpapiship-map-select" >
                <!-- <option selected disabled><?= __('Служба доставки', 'wp-apiship') ?></option> -->
                <option value="all-providers"><?= __('Все службы доставки', 'wp-apiship') ?></option>
                <?php foreach ($providers as $provider) : ?>
                    <option value="<?= $provider['key'] ?>"><?= $provider['name'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>
    <!-- map provider select -->

    <!-- yandex map -->
    <div id="wpapiship-checkout-ymap"></div>
    <!-- yandex map -->
</div>