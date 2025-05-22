<?php
/**
 * File: class-wp-apiship-hpos-test.php
 *
 * @package WP ApiShip
 *
 * @since 1.5.0
 */

namespace WP_ApiShip;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( __NAMESPACE__ . '\WP_ApiShip_HPOS_Test' ) ) :

	/**
	 * Class for testing HPOS compatibility.
	 *
	 * @since 1.5.0
	 */
	class WP_ApiShip_HPOS_Test {

		/**
		 * Run all HPOS compatibility tests.
		 *
		 * @return array Test results.
		 */
		public static function run_tests() {
			$results = array();
			
			$results['hpos_enabled'] = self::test_hpos_enabled();
			$results['order_meta_operations'] = self::test_order_meta_operations();
			$results['screen_detection'] = self::test_screen_detection();
			$results['hooks_compatibility'] = self::test_hooks_compatibility();
			$results['migration_status'] = self::test_migration_status();
			
			return $results;
		}

		/**
		 * Test if HPOS is enabled and detected correctly.
		 *
		 * @return array
		 */
		public static function test_hpos_enabled() {
			$result = array(
				'test' => 'HPOS Detection',
				'passed' => false,
				'message' => '',
				'data' => array()
			);

			try {
				$hpos_enabled = WP_ApiShip_HPOS_Compatibility::is_hpos_enabled();
				$wc_hpos_enabled = class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && 
								   \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
				
				$result['data']['plugin_detection'] = $hpos_enabled;
				$result['data']['wc_detection'] = $wc_hpos_enabled;
				$result['data']['compatibility_class_exists'] = class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' );
				
				if ( $hpos_enabled === $wc_hpos_enabled ) {
					$result['passed'] = true;
					$result['message'] = $hpos_enabled ? 'HPOS is enabled and detected correctly' : 'HPOS is disabled and detected correctly';
				} else {
					$result['message'] = 'HPOS detection mismatch between plugin and WooCommerce';
				}
				
			} catch ( Exception $e ) {
				$result['message'] = 'Error testing HPOS detection: ' . $e->getMessage();
			}

			return $result;
		}

		/**
		 * Test order meta operations.
		 *
		 * @return array
		 */
		public static function test_order_meta_operations() {
			$result = array(
				'test' => 'Order Meta Operations',
				'passed' => false,
				'message' => '',
				'data' => array()
			);

			try {
				// Get a test order
				$orders = wc_get_orders( array( 'limit' => 1 ) );
				
				if ( empty( $orders ) ) {
					$result['message'] = 'No orders found for testing';
					return $result;
				}

				$order = $orders[0];
				$test_meta_key = '_wp_apiship_test_meta';
				$test_meta_value = 'test_value_' . time();

				// Test update meta
				$update_result = WP_ApiShip_HPOS_Compatibility::update_order_meta( $order, $test_meta_key, $test_meta_value );
				
				// Test get meta
				$retrieved_value = WP_ApiShip_HPOS_Compatibility::get_order_meta( $order, $test_meta_key );
				
				// Test delete meta
				$delete_result = WP_ApiShip_HPOS_Compatibility::delete_order_meta( $order, $test_meta_key );
				
				$result['data']['order_id'] = $order->get_id();
				$result['data']['update_result'] = $update_result;
				$result['data']['retrieved_value'] = $retrieved_value;
				$result['data']['values_match'] = ( $retrieved_value === $test_meta_value );
				$result['data']['delete_result'] = $delete_result;
				
				if ( $update_result && $retrieved_value === $test_meta_value && $delete_result ) {
					$result['passed'] = true;
					$result['message'] = 'Order meta operations working correctly';
				} else {
					$result['message'] = 'Order meta operations failed';
				}
				
			} catch ( Exception $e ) {
				$result['message'] = 'Error testing order meta operations: ' . $e->getMessage();
			}

			return $result;
		}

		/**
		 * Test screen detection methods.
		 *
		 * @return array
		 */
		public static function test_screen_detection() {
			$result = array(
				'test' => 'Screen Detection',
				'passed' => true,
				'message' => 'Screen detection methods available',
				'data' => array()
			);

			try {
				$result['data']['order_screen_id'] = WP_ApiShip_HPOS_Compatibility::get_order_screen_id();
				$result['data']['order_post_type'] = WP_ApiShip_HPOS_Compatibility::get_order_post_type();
				$result['data']['bulk_actions_hook'] = WP_ApiShip_HPOS_Compatibility::get_bulk_actions_hook();
				$result['data']['handle_bulk_actions_hook'] = WP_ApiShip_HPOS_Compatibility::get_handle_bulk_actions_hook();
				$result['data']['is_order_edit_screen'] = WP_ApiShip_HPOS_Compatibility::is_order_edit_screen();
				$result['data']['is_orders_list_screen'] = WP_ApiShip_HPOS_Compatibility::is_orders_list_screen();
				
			} catch ( Exception $e ) {
				$result['passed'] = false;
				$result['message'] = 'Error testing screen detection: ' . $e->getMessage();
			}

			return $result;
		}

		/**
		 * Test hooks compatibility.
		 *
		 * @return array
		 */
		public static function test_hooks_compatibility() {
			$result = array(
				'test' => 'Hooks Compatibility',
				'passed' => false,
				'message' => '',
				'data' => array()
			);

			try {
				$expected_hooks = array(
					'bulk_actions' => Options\WP_ApiShip_Options::get_bulk_actions_hook(),
					'handle_bulk_actions' => Options\WP_ApiShip_Options::get_handle_bulk_actions_hook(),
				);

				$hpos_enabled = WP_ApiShip_HPOS_Compatibility::is_hpos_enabled();
				
				if ( $hpos_enabled ) {
					$expected_bulk = 'bulk_actions-woocommerce_page_wc-orders';
					$expected_handle = 'handle_bulk_actions-woocommerce_page_wc-orders';
				} else {
					$expected_bulk = 'bulk_actions-edit-shop_order';
					$expected_handle = 'handle_bulk_actions-edit-shop_order';
				}

				$result['data']['hpos_enabled'] = $hpos_enabled;
				$result['data']['bulk_actions_hook'] = $expected_hooks['bulk_actions'];
				$result['data']['handle_bulk_actions_hook'] = $expected_hooks['handle_bulk_actions'];
				$result['data']['expected_bulk'] = $expected_bulk;
				$result['data']['expected_handle'] = $expected_handle;
				
				if ( $expected_hooks['bulk_actions'] === $expected_bulk && 
					 $expected_hooks['handle_bulk_actions'] === $expected_handle ) {
					$result['passed'] = true;
					$result['message'] = 'Hooks compatibility working correctly';
				} else {
					$result['message'] = 'Hooks compatibility failed - unexpected hook names';
				}
				
			} catch ( Exception $e ) {
				$result['message'] = 'Error testing hooks compatibility: ' . $e->getMessage();
			}

			return $result;
		}

		/**
		 * Test migration status.
		 *
		 * @return array
		 */
		public static function test_migration_status() {
			$result = array(
				'test' => 'Migration Status',
				'passed' => true,
				'message' => 'Migration status available',
				'data' => array()
			);

			try {
				$migration_status = WP_ApiShip_HPOS_Migration::get_migration_status();
				$result['data'] = $migration_status;
				
				if ( isset( $migration_status['completed'] ) && isset( $migration_status['hpos_enabled'] ) ) {
					$result['passed'] = true;
					$result['message'] = 'Migration status working correctly';
				} else {
					$result['passed'] = false;
					$result['message'] = 'Migration status data incomplete';
				}
				
			} catch ( Exception $e ) {
				$result['passed'] = false;
				$result['message'] = 'Error testing migration status: ' . $e->getMessage();
			}

			return $result;
		}

		/**
		 * Generate test report.
		 *
		 * @return string
		 */
		public static function generate_report() {
			$tests = self::run_tests();
			$total_tests = count( $tests );
			$passed_tests = 0;
			
			$report = "WP ApiShip HPOS Compatibility Test Report\n";
			$report .= "========================================\n\n";
			
			foreach ( $tests as $test ) {
				if ( $test['passed'] ) {
					$passed_tests++;
					$status = "✓ PASSED";
				} else {
					$status = "✗ FAILED";
				}
				
				$report .= sprintf( "Test: %s - %s\n", $test['test'], $status );
				$report .= sprintf( "Message: %s\n", $test['message'] );
				
				if ( ! empty( $test['data'] ) ) {
					$report .= "Data:\n";
					foreach ( $test['data'] as $key => $value ) {
						$report .= sprintf( "  %s: %s\n", $key, is_bool( $value ) ? ( $value ? 'true' : 'false' ) : $value );
					}
				}
				
				$report .= "\n";
			}
			
			$report .= sprintf( "Summary: %d/%d tests passed\n", $passed_tests, $total_tests );
			
			if ( $passed_tests === $total_tests ) {
				$report .= "🎉 All tests passed! HPOS compatibility is working correctly.\n";
			} else {
				$report .= "⚠️  Some tests failed. Please review the implementation.\n";
			}
			
			return $report;
		}

		/**
		 * Output test report to admin.
		 */
		public static function admin_test_page() {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
			}

			echo '<div class="wrap">';
			echo '<h1>WP ApiShip HPOS Compatibility Test</h1>';
			echo '<pre style="background: #f1f1f1; padding: 20px; border: 1px solid #ccc; white-space: pre-wrap;">';
			echo esc_html( self::generate_report() );
			echo '</pre>';
			echo '</div>';
		}
	}

endif;