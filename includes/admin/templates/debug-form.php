<?php
/**
 * File: debug-form.php
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

global $wpdb;

// $options = $wpdb->get_results($wpdb->prepare( "SELECT * FROM $wpdb->options WHERE 1=1 AND option_name LIKE '%wp_apiship_%'", '*' ), ARRAY_A);

$options = [];

?>
<tr valign="top">
	<th scope="row" class="titledesc">
		<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php //echo '$tooltip_html'; // WPCS: XSS ok. ?></label>
	</th>
	<td class="forminp" id="wp_apiship_debug_form">
		<table id="debug-table">
			<tbody>
				<tr>
					<th>option_id</th>
					<th>option_name</th>
					<th>option_value</th>
					<th>autoload</th>
				</tr>
				<?php
				foreach( $options as $option ) { ?>
					<tr class="data">
						<td class="option_id"><?php echo $option['option_id']; ?></td>
						<td class="option_name"><?php echo $option['option_name']; ?></td>
						<td class="option_value"><?php echo $option['option_value']; ?></td>
						<td class="option_autoload"><?php echo $option['autoload']; ?></td>
					</tr><?php
				} ?>
			<tbody>
		</table>
	</td>
</tr>
