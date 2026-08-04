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

$options_query = $wpdb->prepare(
	"SELECT * FROM {$wpdb->options} WHERE option_name LIKE %s",
	'%' . $wpdb->esc_like( 'wp_apiship_' ) . '%'
);
$options = $wpdb->get_results( $options_query, ARRAY_A );

/**
 * Значения, которые нельзя показывать открытым текстом.
 */
$masked_options = array( 'wp_apiship_token' );
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
				foreach( $options as $option ) {

					$option_value = in_array( $option['option_name'], $masked_options, true ) && $option['option_value'] !== ''
						? str_repeat( '*', 12 )
						: $option['option_value'];
					?>
					<tr class="data">
						<td class="option_id"><?php echo esc_html( $option['option_id'] ); ?></td>
						<td class="option_name"><?php echo esc_html( $option['option_name'] ); ?></td>
						<td class="option_value"><?php echo esc_html( $option_value ); ?></td>
						<td class="option_autoload"><?php echo esc_html( $option['autoload'] ); ?></td>
					</tr><?php
				} ?>
			<tbody>
		</table>
	</td>
</tr>
<?php