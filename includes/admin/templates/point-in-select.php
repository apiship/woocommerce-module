<?php
/**
 * File: point-in-select.php
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

if ( ! isset($point_in_select_action) ) {
	$point_in_select_action = 'initial';
}

switch ($point_in_select_action) :
	case 'getListsPoints':	
		$type 		= $attrs['type'];
		$points_in  = $attrs['points_in'];
		break;				
	case 'getCardSelectHtml':
		$type  = $attrs['type'];
		$point = $attrs['point'];
		break;				
	case 'reset':
		$type = $attrs['type'];
		break;				
	case 'initial':
		$type = 'store';
		if ( isset($point_in_select_type) ) {
			$type = $point_in_select_type;
		}	
		break;
	default:
		break;
endswitch;

if ( 'initial' == $point_in_select_action ) { ?>
	<div class="point-in-select-wrapper <?php echo $type; ?>">
		<select name="point-in-id-<?php echo $type; ?>" class="point-select" data-type="<?php echo $type; ?>">
		  <option class="" value="not-selected" selected><?php esc_html_e('-- не выбрано --','apiship'); ?></option>
		  <option class="point-in-select-option" value="load-points"><?php esc_html_e('Загрузить список','apiship'); ?></option>
		</select>
	</div><?php
} elseif ( 'reset' == $point_in_select_action ) { ?>
	<select name="point-in-id-<?php echo $type; ?>" class="point-select" data-type="<?php echo $type; ?>">
	  <option class="" value="not-selected" selected><?php esc_html_e('-- не выбрано --','apiship'); ?></option>
	  <option class="point-in-select-option" value="load-points"><?php esc_html_e('Загрузить список','apiship'); ?></option>
	</select><?php
} elseif ( 'getListsPoints' == $point_in_select_action ) { ?>
	<select name="point-in-id-<?php echo $type; ?>" class="point-select" data-type="<?php echo $type; ?>"><?php
		if ( count( $points_in ) == 0 ) { ?>
			<option class="" value="not-found"><?php esc_html_e('-- не найдено --','apiship'); ?></option><?php
		} else { ?>
			<option value="select-from-list"><?php esc_html_e('-- выберите из списка --','apiship'); ?></option><?php
			foreach( $points_in as $point ) { ?>
				<option class="point-in-select-option" value="<?php echo $point['id']; ?>"><?php echo $point['address']; ?></option><?php
			} ?>
			<option disabled>-------------------</option><?php
		} ?>
		<option class="point-in-select-option" value="load-points"><?php esc_html_e('Повторить загрузку списка','apiship'); ?></option>
	</select><?php
} elseif ( 'getCardSelectHtml' == $point_in_select_action ) { ?>
	<select name="point-in-id-<?php echo $type; ?>" class="point-select" data-type="<?php echo $type; ?>">
		<option class="point-in-select-option" value="<?php echo $point['id']; ?>" selected><?php echo $point['address']; ?></option>
		<option disabled>-------------------</option>
		<option class="point-in-select-option" value="reset-point"><?php esc_html_e('отменить выбор','apiship'); ?></option>
		<option disabled>-------------------</option>
		<option class="point-in-select-option" value="load-points"><?php esc_html_e('Повторить загрузку списка','apiship'); ?></option>
	</select><?php
}
# --- EOF