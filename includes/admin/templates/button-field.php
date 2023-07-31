<?php
/**
 * File: button-field.php
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
<tr valign="top">
	<th scope="row" class="titledesc">
		<label></label>
	</th>
	<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>" id="<?php echo esc_html( $value['wrapper_id'] ); ?>">
		<button onclick="return false;" 
			name="<?php echo esc_html( $value['button_name'] ); ?>" 
			class="<?php echo esc_html( $value['class'] ); ?>" 
			value="<?php echo esc_html( $value['button_name'] ); ?>">
			<?php echo esc_html( $value['caption'] ); ?>
		</button> 
		<span class="message"><?php echo ''; ?></span>
	</td>
</tr>
<?php

# --- EOF