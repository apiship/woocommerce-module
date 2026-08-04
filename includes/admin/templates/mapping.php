<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<tr valign="top">
    <th scope="row" class="titledesc wp-apiship-mapping-title">
        <label><?php esc_html_e('Сопоставление статусов между ApiShip и WooCommerce.', 'apiship'); ?></label>
    </th>
    <td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
        <div class="wp-apiship-mapping-container">
            <?php foreach($setting_rows as $key => $row) { ?>
                <div class="wp-apiship-mapping-row">
                    <div class="wp-apiship-mapping-inner-status-col">
                        <input type="hidden" name="<?php echo esc_attr( $value_id . '[' . $key . '][is_active_status]' ); ?>" value="0">
                        <input class="wp-apiship-mapping-checkbox wp-apiship-status-active" type="checkbox" <?php if (boolval($row['is_active_status']) === true) { echo 'checked'; } ?> name="<?php echo esc_attr( $value_id . '[' . $key . '][is_active_status]' ); ?>" value="1">
                        <span class="wp-apiship-mapping-checkbox-label"><?php echo esc_html( $row['title'] ); ?></span>
                    </div>
                    <div class="wp-apiship-mapping-wc-status-col wp-apiship-mapping-config-col">
                        <select class="wp-apiship-mapping-select" name="<?php echo esc_attr( $value_id . '[' . $key . '][selected_status]' ); ?>">
                            <option disabled><?php esc_html_e('-- Выберите статус --', 'apiship'); ?></option>
                            <?php foreach($statuses as $slug => $status) { ?>
                                <option <?php if ($slug === $row['selected_status']) { echo 'selected'; } ?> value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $status ); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
            <?php } ?>

            <div class="wp-apiship-mapping-desc">
                <p><?php esc_html_e('Слева находятся внутренние статусы ApiShip. Для активации статуса, нажмите на галочку, затем задайте соответствие из списка своих статусов.', 'apiship'); ?></p>
                <p><?php esc_html_e('Соответствия статусов между СД и ApiShip можно увидеть на странице: ', 'apiship'); ?><a href="/"><?php esc_html_e('соответствие статусов', 'apiship'); ?></a>.</p>
            </div>

        </div>
    </td>
</tr>
