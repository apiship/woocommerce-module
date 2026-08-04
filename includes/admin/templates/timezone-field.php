<?php
/**
 * File: timezone-field.php
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

/**
 * @see wp-admin\options-general.php
 */
$current_offset = get_option( 'gmt_offset' );
$tzstring   	= get_option( 'timezone_string' );

$check_zone_info = true;

// Remove old Etc mappings. Fallback to gmt_offset.
if ( false !== strpos( $tzstring, 'Etc/GMT' ) ) {
	$tzstring = '';
}

if ( empty( $tzstring ) ) { // Create a UTC+- zone if no timezone string exists.
	$check_zone_info = false;
	if ( 0 == $current_offset ) {
		$tzstring = 'UTC+0';
	} elseif ( $current_offset < 0 ) {
		$tzstring = 'UTC' . $current_offset;
	} else {
		$tzstring = 'UTC+' . $current_offset;
	}
}

if ( ! isset( $value['desc_tip'] ) ) {
	$value['desc_tip'] = false;
}
				
$tooltip_html 	= wc_help_tip( $value['desc_tip'] );
$local_time_tip = esc_html__('Местное время', 'apiship');
$description 	= '<p class="description">' . wp_kses_post( $value['desc'] ) . '</p>';

$date = new DateTime('now', new DateTimeZone( wp_timezone_string() ) );

?>
<tr valign="top">
	<th scope="row" class="titledesc">
		<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo wp_kses( $tooltip_html, array( 'span' => array( 'class' => true, 'tabindex' => true, 'aria-label' => true, 'data-tip' => true ) ) ); ?></label>
	</th>
	<td class="forminp forminp-timezone" id="timezone">
		<input type="text" id="timezone" name="timezone" value="<?php echo esc_attr( $tzstring ); ?>" disabled />
		<span class="local-time-tip"><?php echo esc_html( $local_time_tip . ': ' . $date->format('Y-m-d H:i') ); ?></span>
		<?php echo wp_kses_post( $description ); ?>
	</td>
</tr>
<?php
# --- EOF