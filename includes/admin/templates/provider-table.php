<?php
/**
 * File: provider-table.php
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
		<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo '$tooltip_html'; // WPCS: XSS ok. ?></label>
	</th>
	<td class="forminp" id="provider-table">
		<div class="wc_input_table_wrapper provider-table-wrapper">
			<div class="provider-table--item provider-table-header">
				<div class="header--item">&nbsp;</div>
				<div class="header--item">Имя файла</div>
				<div class="header--item">Тип</div>
				<div class="header--item">Дата</div>
			</div>
			<div class="provider-table--item provider-table-body">
				<div class="body--item"></div>
				<div class="body--item"></div>
				<div class="body--item"></div>
				<div class="body--item"></div>
			</div>			
		</div>
		<div>
			<button id="get-all-providers" onclick="return false;">Get providers</button>
		</div>
	</td>
</tr>
<?php
