<?php
/**
 * File: subsubsub-section.php
 *
 *  ApiShip tab's sections.
 *
 * @package WP ApiShip
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<ul class="subsubsub">
  <?php foreach ($sections as $id => $label) : ?>
	<li>
		<a href="<?php echo esc_url( admin_url('admin.php?page=wc-settings&tab=' . $this->id . '&section=' . $id) ); ?>"
			class="<?php echo ($current_section == $id ? 'current' : ''); ?>"><?php echo esc_html( $label ); ?>
		</a>
	</li> | 
  <?php endforeach; ?>
</ul>
<br class="clear" />
<?php

# --- EOF