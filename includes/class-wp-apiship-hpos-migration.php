<?php
/**
 * File: class-wp-apiship-hpos-migration.php
 *
 * @package WP ApiShip
 *
 * @since 1.5.0
 */

namespace WP_ApiShip;

use Automattic\WooCommerce\Utilities\OrderUtil;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( __NAMESPACE__ . '\WP_ApiShip_HPOS_Migration' ) ) :

	/**
	 * Class for HPOS migration tasks.
	 *
	 * @since 1.5.0
	 */
	class WP_ApiShip_HPOS_Migration {

		/**
		 * Migration option key.
		 */
		const MIGRATION_OPTION_KEY = 'wp_apiship_hpos_migration_completed';

		/**
		 * Batch size for migration.
		 */
		const BATCH_SIZE = 50;

		/**
		 * Initialize migration hooks.
		 */
		public static function init() {
			add_action( 'admin_init', array( __CLASS__, 'maybe_run_migration' ) );
			add_action( 'wp_ajax_wp_apiship_hpos_migration', array( __CLASS__, 'ajax_migration' ) );
		}

		/**
		 * Check if migration is needed and run it.
		 */
		public static function maybe_run_migration() {
			if ( ! self::is_migration_needed() ) {
				return;
			}

			if ( ! WP_ApiShip_HPOS_Compatibility::is_hpos_enabled() ) {
				return;
			}

			// Add admin notice for migration
			add_action( 'admin_notices', array( __CLASS__, 'migration_notice' ) );
		}

		/**
		 * Check if migration is needed.
		 *
		 * @return bool
		 */
		public static function is_migration_needed() {
			return ! get_option( self::MIGRATION_OPTION_KEY, false );
		}

		/**
		 * Display migration notice.
		 */
		public static function migration_notice() {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				return;
			}

			?>
			<div class="notice notice-info wp-apiship-migration-notice">
				<p>
					<?php esc_html_e( 'WP ApiShip: Требуется миграция данных для поддержки высокопроизводительного хранилища заказов WooCommerce.', 'wp-apiship' ); ?>
				</p>
				<p>
					<button type="button" class="button button-primary" id="wp-apiship-start-migration">
						<?php esc_html_e( 'Начать миграцию', 'wp-apiship' ); ?>
					</button>
					<span class="spinner" style="float: none; margin: 0 10px;"></span>
					<span class="wp-apiship-migration-status"></span>
				</p>
			</div>
			<script>
			jQuery(document).ready(function($) {
				$('#wp-apiship-start-migration').on('click', function() {
					var $button = $(this);
					var $spinner = $('.wp-apiship-migration-notice .spinner');
					var $status = $('.wp-apiship-migration-status');
					
					$button.prop('disabled', true);
					$spinner.addClass('is-active');
        $status.text('<?php echo esc_js( __( 'Начинается миграция...', 'wp-apiship' ) ); ?>');
					
					wpApiShipRunMigration(0, $status, $spinner, $button);
				});
				
				function wpApiShipRunMigration(offset, $status, $spinner, $button) {
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'wp_apiship_hpos_migration',
							offset: offset,
							nonce: '<?php echo wp_create_nonce( 'wp_apiship_hpos_migration' ); ?>'
						},
						success: function(response) {
							if (response.success) {
								if (response.data.completed) {
                            $status.text('<?php echo esc_js( __( 'Миграция завершена успешно!', 'wp-apiship' ) ); ?>');
									$spinner.removeClass('is-active');
									setTimeout(function() {
										$('.wp-apiship-migration-notice').fadeOut();
									}, 2000);
								} else {
                                    $status.text('<?php echo esc_js( __( 'Обработано заказов:', 'wp-apiship' ) ); ?> ' + response.data.processed);
									wpApiShipRunMigration(response.data.next_offset, $status, $spinner, $button);
								}
							} else {
                                $status.text('<?php echo esc_js( __( 'Ошибка миграции:', 'wp-apiship' ) ); ?> ' + response.data);
								$spinner.removeClass('is-active');
								$button.prop('disabled', false);
							}
						},
						error: function() {
                            $status.text('<?php echo esc_js( __( 'Произошла ошибка при миграции', 'wp-apiship' ) ); ?>');
							$spinner.removeClass('is-active');
							$button.prop('disabled', false);
						}
					});
				}
			});
			</script>
			<?php
		}

		/**
		 * Handle AJAX migration request.
		 */
		public static function ajax_migration() {
        if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wp_apiship_hpos_migration' ) ) {
            wp_send_json_error( __( 'Неверный nonce', 'wp-apiship' ) );
        }

			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_send_json_error( __( 'Недостаточно прав', 'wp-apiship' ) );
			}

			$offset = intval( $_POST['offset'] );
			$result = self::migrate_batch( $offset );

			wp_send_json_success( $result );
		}

		/**
		 * Migrate a batch of orders.
		 *
		 * @param int $offset Starting offset.
		 * @return array Migration result.
		 */
		public static function migrate_batch( $offset = 0 ) {
			global $wpdb;

			// Get orders with ApiShip meta data from post meta
			$meta_keys = array(
				Options\WP_ApiShip_Options::POST_SHIPPING_TO_POINT_IN_META,
				Options\WP_ApiShip_Options::POST_SHIPPING_TO_POINT_OUT_META,
				Options\WP_ApiShip_Options::ORDER_PLACES_META,
				Options\WP_ApiShip_Options::INTEGRATOR_ORDER_KEY,
				Options\WP_ApiShip_Options::PROVIDER_NUMBER_KEY,
				Options\WP_ApiShip_Options::TARIFF_DATA_KEY,
			);

			$meta_keys_placeholder = implode( ',', array_fill( 0, count( $meta_keys ), '%s' ) );

			$query = $wpdb->prepare(
				"SELECT DISTINCT p.ID 
				FROM {$wpdb->posts} p 
				INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
				WHERE p.post_type = 'shop_order' 
				AND pm.meta_key IN ({$meta_keys_placeholder})
				ORDER BY p.ID ASC 
				LIMIT %d OFFSET %d",
				array_merge( $meta_keys, array( self::BATCH_SIZE, $offset ) )
			);

			$order_ids = $wpdb->get_col( $query );

			if ( empty( $order_ids ) ) {
				// Migration completed
				update_option( self::MIGRATION_OPTION_KEY, time() );
				return array(
					'completed' => true,
					'processed' => 0,
				);
			}

			$processed = 0;
			foreach ( $order_ids as $order_id ) {
				if ( self::migrate_single_order( $order_id ) ) {
					$processed++;
				}
			}

			return array(
				'completed' => false,
				'processed' => $offset + $processed,
				'next_offset' => $offset + self::BATCH_SIZE,
			);
		}

		/**
		 * Migrate single order data.
		 *
		 * @param int $order_id Order ID.
		 * @return bool Success status.
		 */
		public static function migrate_single_order( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				return false;
			}

			$meta_keys = array(
				Options\WP_ApiShip_Options::POST_SHIPPING_TO_POINT_IN_META,
				Options\WP_ApiShip_Options::POST_SHIPPING_TO_POINT_OUT_META,
				Options\WP_ApiShip_Options::ORDER_PLACES_META,
				Options\WP_ApiShip_Options::INTEGRATOR_ORDER_KEY,
				Options\WP_ApiShip_Options::PROVIDER_NUMBER_KEY,
				Options\WP_ApiShip_Options::TARIFF_DATA_KEY,
			);

			$migrated = false;

			foreach ( $meta_keys as $meta_key ) {
				$value = get_post_meta( $order_id, $meta_key, true );
				
				if ( ! empty( $value ) ) {
					// Check if meta already exists in HPOS
					$existing_value = $order->get_meta( $meta_key, true );
					
					if ( empty( $existing_value ) ) {
						$order->update_meta_data( $meta_key, $value );
						$migrated = true;
					}
				}
			}

			if ( $migrated ) {
				$order->save();
			}

			return true;
		}

		/**
		 * Force run migration manually.
		 */
		public static function force_migration() {
			delete_option( self::MIGRATION_OPTION_KEY );
			
			$offset = 0;
			do {
				$result = self::migrate_batch( $offset );
				$offset = isset( $result['next_offset'] ) ? $result['next_offset'] : 0;
			} while ( ! $result['completed'] );

			return true;
		}

		/**
		 * Check migration status.
		 *
		 * @return array Migration status info.
		 */
		public static function get_migration_status() {
			$completed = get_option( self::MIGRATION_OPTION_KEY, false );
			
			return array(
				'completed' => (bool) $completed,
				'completed_at' => $completed ? date( 'Y-m-d H:i:s', $completed ) : null,
				'hpos_enabled' => WP_ApiShip_HPOS_Compatibility::is_hpos_enabled(),
			);
		}
	}

endif;