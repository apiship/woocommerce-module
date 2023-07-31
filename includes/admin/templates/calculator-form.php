<?php
/**
 * File: calculator-form.php
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
<textarea name="calculate-response" id="calculate-response" class="" rows="10" cols="150"></textarea>
<br />
<button name="calculate" id="get-calculation" class="button-primary woocommerce-save-button" type="button" value="Save changes">
	Calculate
</button>

<?php
# --- EOF