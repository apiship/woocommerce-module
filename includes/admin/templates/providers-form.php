<?php
/**
 * File: providers-form.php
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
<tr valign="top" style="display:none;">
	<th scope="row" class="titledesc">&nbsp;</th>
	<td>
		<div class="provider-message"></div>
	</td>	
</tr>
<tr valign="top">
	<th scope="row" class="titledesc">
		<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php //echo '$tooltip_html'; // WPCS: XSS ok. ?></label>
	</th>
	<td class="forminp hidden" id="wp_apiship_providers_form">
		<h2><?php esc_html_e('Выбранные службы доставки','wp-apiship'); ?></h2>
		<div class="provider-cards selected"></div>
	</td>
</tr>
