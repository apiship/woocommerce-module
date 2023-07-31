<?php
/**
 * File: connection-card.php
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
ob_start();
?>
<div class="connection-card {{providerClass}}" data-provider-key="{{providerKey}}">
	<div class="card--item card--logo">
		<img class="logo {{classes}}" src="{{logoURL}}" />
	</div>
	<div class="card--item card--name"></div>
	<div class="card--item card--key"></div>
	<div class="card--item card--description"></div>
</div>			
<?php
return ob_get_clean();

# --- EOF