<?php
/**
 * File: class-wp-apiship-hpos-compatibility.php
 *
 * @package WP ApiShip
 *
 * @since 1.5.0
 */

namespace ApiShip;

use Automattic\WooCommerce\Utilities\OrderUtil;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( __NAMESPACE__ . '\ApiShip_HPOS_Compatibility' ) ) :

	/**
	 * Class for HPOS (High-Performance Order Storage) compatibility.
	 *
	 * @since 1.5.0
	 */
	class ApiShip_HPOS_Compatibility {

		/**
		 * Check if HPOS is enabled.
		 *
		 * @return bool
		 */
		public static function is_hpos_enabled() {
			return class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && OrderUtil::custom_orders_table_usage_is_enabled();
		}

		/**
		 * Get order meta data.
		 *
		 * @param int|WC_Order $order Order ID or order object.
		 * @param string $meta_key Meta key.
		 * @param bool $single Whether to return single value or array.
		 * @param mixed $default Default value if meta not found.
		 *
		 * @return mixed
		 */
		public static function get_order_meta( $order, $meta_key, $single = true, $default = null ) {
			if ( is_numeric( $order ) ) {
				$order = wc_get_order( $order );
			}

			if ( ! $order instanceof \WC_Order ) {
				return $default;
			}

			if ( self::is_hpos_enabled() ) {
				$value = $order->get_meta( $meta_key, $single );

				if ( ! $single && is_array( $value ) ) {
					/**
					 * Приводим к тому же виду, что и get_post_meta():
					 * массив значений, а не массив объектов WC_Meta_Data.
					 */
					$value = array_map(
						function( $meta ) {
							return $meta instanceof \WC_Meta_Data ? $meta->value : $meta;
						},
						$value
					);
				}
			} else {
				$value = get_post_meta( $order->get_id(), $meta_key, $single );
			}

			/**
			 * Значение отсутствует, а не равно '0'/0/false.
			 */
			if ( $value === '' || $value === null || $value === array() || $value === false ) {
				return $default;
			}

			return $value;
		}

		/**
		 * Update order meta data.
		 *
		 * @param int|WC_Order $order Order ID or order object.
		 * @param string $meta_key Meta key.
		 * @param mixed $meta_value Meta value.
		 *
		 * @return bool
		 */
		public static function update_order_meta( $order, $meta_key, $meta_value ) {
			if ( is_numeric( $order ) ) {
				$order = wc_get_order( $order );
			}

			if ( ! $order instanceof \WC_Order ) {
				return false;
			}

			if ( self::is_hpos_enabled() ) {
				$order->update_meta_data( $meta_key, $meta_value );
				$order->save();
				return true;
			}

			$result = update_post_meta( $order->get_id(), $meta_key, $meta_value );

			if ( false !== $result ) {
				return true;
			}

			/**
			 * update_post_meta() возвращает false и когда значение не изменилось —
			 * для вызывающего кода это успех, а не ошибка.
			 */
			return get_post_meta( $order->get_id(), $meta_key, true ) == $meta_value;
		}

		/**
		 * Add order meta data.
		 *
		 * @param int|WC_Order $order Order ID or order object.
		 * @param string $meta_key Meta key.
		 * @param mixed $meta_value Meta value.
		 * @param bool $unique Whether meta key should be unique.
		 *
		 * @return bool|int
		 */
		public static function add_order_meta( $order, $meta_key, $meta_value, $unique = false ) {
			if ( is_numeric( $order ) ) {
				$order = wc_get_order( $order );
			}

			if ( ! $order instanceof \WC_Order ) {
				return false;
			}

			if ( self::is_hpos_enabled() ) {
				if ( $unique && $order->meta_exists( $meta_key ) ) {
					return false;
				}
				$order->add_meta_data( $meta_key, $meta_value );
				$order->save();
				return true;
			} else {
				return add_post_meta( $order->get_id(), $meta_key, $meta_value, $unique );
			}
		}

		/**
		 * Delete order meta data.
		 *
		 * @param int|WC_Order $order Order ID or order object.
		 * @param string $meta_key Meta key.
		 * @param mixed $meta_value Meta value (optional).
		 *
		 * @return bool
		 */
		public static function delete_order_meta( $order, $meta_key, $meta_value = '' ) {
			if ( is_numeric( $order ) ) {
				$order = wc_get_order( $order );
			}

			if ( ! $order instanceof \WC_Order ) {
				return false;
			}

			if ( self::is_hpos_enabled() ) {
				$order->delete_meta_data( $meta_key );
				$order->save();
				return true;
			} else {
				return delete_post_meta( $order->get_id(), $meta_key, $meta_value );
			}
		}

		/**
		 * Get orders by meta query.
		 *
		 * @param array $meta_query Meta query array.
		 * @param array $args Additional arguments.
		 *
		 * @return array
		 */
		public static function get_orders_by_meta( $meta_query, $args = array() ) {
			$default_args = array(
				'limit' => -1,
				'return' => 'ids',
			);

			$args = wp_parse_args( $args, $default_args );

			if ( self::is_hpos_enabled() ) {
				$args['meta_query'] = $meta_query;
				return wc_get_orders( $args );
			} else {
				$query_args = array(
					'post_type' => 'shop_order',
					'post_status' => array_keys( wc_get_order_statuses() ),
					'meta_query' => $meta_query,
					'fields' => 'ids',
					'posts_per_page' => $args['limit'],
				);

				if ( isset( $args['status'] ) ) {
					$query_args['post_status'] = $args['status'];
				}

				$query = new \WP_Query( $query_args );
				return $query->posts;
			}
		}

		/**
		 * Get the order screen ID.
		 *
		 * @return string
		 */
		public static function get_order_screen_id() {
			if ( self::is_hpos_enabled() ) {
				return wc_get_page_screen_id( 'shop-order' );
			} else {
				return 'shop_order';
			}
		}

		/**
		 * Get the order post type.
		 *
		 * @return string
		 */
		public static function get_order_post_type() {
			/**
			 * Тип записи заказа остаётся `shop_order` в обоих режимах хранения;
			 * различается только идентификатор экрана — см. get_order_screen_id().
			 */
			return 'shop_order';
		}

		/**
		 * Get bulk actions hook name.
		 *
		 * @return string
		 */
		public static function get_bulk_actions_hook() {
			if ( self::is_hpos_enabled() ) {
				return 'bulk_actions-woocommerce_page_wc-orders';
			} else {
				return 'bulk_actions-edit-shop_order';
			}
		}

		/**
		 * Get handle bulk actions hook name.
		 *
		 * @return string
		 */
		public static function get_handle_bulk_actions_hook() {
			if ( self::is_hpos_enabled() ) {
				return 'handle_bulk_actions-woocommerce_page_wc-orders';
			} else {
				return 'handle_bulk_actions-edit-shop_order';
			}
		}

		/**
		 * Check if current screen is order edit screen.
		 *
		 * @return bool
		 */
		public static function is_order_edit_screen() {
			$screen = get_current_screen();
			
			if ( ! $screen ) {
				return false;
			}

			if ( self::is_hpos_enabled() ) {
				return $screen->id === 'woocommerce_page_wc-orders' && isset( $_GET['action'] ) && $_GET['action'] === 'edit';
			} else {
				return $screen->id === 'shop_order';
			}
		}

		/**
		 * Check if current screen is orders list screen.
		 *
		 * @return bool
		 */
		public static function is_orders_list_screen() {
			$screen = get_current_screen();
			
			if ( ! $screen ) {
				return false;
			}

			if ( self::is_hpos_enabled() ) {
				return $screen->id === 'woocommerce_page_wc-orders' && ( ! isset( $_GET['action'] ) || $_GET['action'] !== 'edit' );
			} else {
				return $screen->id === 'edit-shop_order';
			}
		}
	}

endif;