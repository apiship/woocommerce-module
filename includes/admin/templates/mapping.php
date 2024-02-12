<tr valign="top">
    <th scope="row" class="titledesc wp-apiship-mapping-title">
        <label><?php echo esc_html('Сопоставление статусов между ApiShip и WooCommerce.'); ?></label>
    </th>
    <td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
        <div class="wp-apiship-mapping-container">
            <?php foreach($setting_rows as $key => $row) { ?>
                <div class="wp-apiship-mapping-row">
                    <div class="wp-apiship-mapping-inner-status-col">
                        <input type="hidden" name="<?php echo $value_id . '[' . $key . '][is_active_status]'; ?>" value="0">  
                        <input class="wp-apiship-mapping-checkbox wp-apiship-status-active" type="checkbox" <?php if (boolval($row['is_active_status']) === true) { echo 'checked'; } ?> name="<?php echo $value_id . '[' . $key . '][is_active_status]'; ?>" value="1">
                        <span class="wp-apiship-mapping-checkbox-label"><?php echo esc_html__($row['title']) ?></span>
                    </div>
                    <div class="wp-apiship-mapping-wc-status-col wp-apiship-mapping-config-col">
                        <select class="wp-apiship-mapping-select" name="<?php echo $value_id . '[' . $key . '][selected_status]'; ?>">
                            <option disabled><?php echo esc_html__('-- Выберите статус --'); ?></option>
                            <?php foreach($statuses as $slug => $status) { ?>
                                <option <?php if ($slug === $row['selected_status']) { echo 'selected'; } ?> value="<?php echo $slug ?>"><?php echo $status ?></option>
                            <?php } ?>
                        </select>    
                    </div>
                </div>
            <?php } ?>
            
            <div class="wp-apiship-mapping-desc">
                <p><?php echo esc_html__('Слева нахоятся внутренние статусы ApiShip. Для активации статуса, нажмите на галочку, затем задайте соответствие из списка своих статусов.') ?></p>
                <p><?php echo esc_html__('Соответствия статусов между СД и ApiShip можно увидеть на странице: ')?><a href="/"><?php echo esc_html__('соответствие статусов') ?></a>.</p>
            </div>

        </div>
    </td>
</tr>