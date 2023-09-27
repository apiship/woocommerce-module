<?php
/**
 * File: class-wp-apiship-activator.php
 *
 * @package WP ApiShip
 * @subpackage Activator
 *
 * @since 1.5.0
 */
namespace WP_ApiShip;

use Throwable;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('WP_ApiShip\\WP_ApiShip_Activator')) :

	/**
	 * Plugin activator.
     * 
     * @since 1.5.0
	 */
	class WP_ApiShip_Activator
	{
        public const LIMIT = \WP_APISHIP_ACTIVATOR_LIMIT;

        public const ACTIVATION_HOOK_NAME = 'wp_apiship_activator';
        public const DEACTIVATION_HOOK_NAME = 'wp_apiship_deactivator';

        public const OFFSET_OPTION = 'wp_apiship_activator_offset';
        public const IS_DONE_OPTION = 'wp_apiship_activator_done';
        public const IS_WAIT_ACTION_OPTION = 'wp_apiship_activator_wait_action';
        public const ACTION_OPTION = 'wp_apiship_activator_action';

        public const CRON_SCHEDULE = 'wp_apiship_schedule_30_sec'; # 'wp_apiship_schedule_min';
        public const ORDER_ITEM_TABLE = 'woocommerce_order_items';
        public const ORDER_ITEM_META_TABLE = 'woocommerce_order_itemmeta';

        public const ACTIVATE_INSTANT_MODE = false;
        public const DEACTIVATE_INSTANT_MODE = false;

        public const WRITE_LOG = \WP_APISHIP_ACTIVATOR_WRITE_LOG;
        public const LOG_PATH = __DIR__ . '/../.activator.log';

        public static bool $is_done = true;
        public static bool $is_wait_action = false;
        public static int $offset = 0;
        public static string $current_action = 'activation';

        public function __construct()
        {
            self::$is_done = boolval(get_option(self::IS_DONE_OPTION, self::$is_done));
            self::$is_wait_action = boolval(get_option(self::IS_WAIT_ACTION_OPTION, self::$is_wait_action));
            self::$offset = intval(get_option(self::OFFSET_OPTION, self::$offset));
            self::$current_action = get_option(self::ACTION_OPTION, self::$current_action);

            /** Deactivate plugin after processing all data. */
            if (self::$is_wait_action === true and self::$current_action === 'deactivation') {
                add_action('admin_init', function(){
                    self::write_log("Deactivate plugin" . PHP_EOL);
                    
                    deactivate_plugins(\WP_APISHIP_PLUGIN_BASE, true);

                    update_option(self::IS_WAIT_ACTION_OPTION, 0);

                    add_action('admin_notices', function(){
                        echo '<div class="notice notice-info"><p>Плагин успешно деактивирован</p></div>';
                    });
                });
            }

            /** Call callback. */
            if (isset($_GET['apiship_deactivation'])) {
                $callback = self::get_callback();
                $callback(self::$current_action, self::LIMIT);
            }

            /** Set processing data callback. */
            if (self::$is_done === false) {
                if (self::$current_action === 'deactivation') {
                    add_action('admin_notices', function(){
                        $offset = self::$offset;

                        echo '<div class="notice notice-info"><p>Статус деактивации плагина WP ApiShip for WooCommerce: в процессе.</p><p>Offset: ' . $offset . '</p><p><a href="/wp-admin/plugins.php?apiship_deactivation">Обновить</a></p></div>';
                    });
                }

                $hook = self::ACTIVATION_HOOK_NAME;
                $args = array(self::$current_action, self::LIMIT);

                if (self::$current_action === 'deactivation') {
                    $hook = self::DEACTIVATION_HOOK_NAME;
                }

                /** Set cron event. */

                add_action($hook, self::get_callback(), 10, 2);

                if (!wp_next_scheduled($hook, $args)) {
                    wp_schedule_event(time(), self::CRON_SCHEDULE, $hook, $args);
                }
            }
        }
        
        /**
         * Activate the plugin.
         */
        public static function activate()
        {
            $action = 'activation';

            self::set_options($action);

            if (self::ACTIVATE_INSTANT_MODE === true) {
                $callback = self::get_callback();
                $callback($action, 150);
                $callback($action, 1);
            }
        }

        /**
         * Deactivate the plugin.
         */
        public static function deactivate()
        {
            $action = 'deactivation';

            self::set_options($action);
            
            if (self::DEACTIVATE_INSTANT_MODE === true) {
                $callback = self::get_callback();
                $callback($action, 150);
                $callback($action, 1);
            } else {
                /**
                 * Cancel deactivation.
                 */
                wp_redirect(admin_url('plugins.php'));
                exit;
            }
        }

        /**
         * Set activator options.
         */
        private static function set_options(string $action): void
        {
            self::$is_done = false;
            self::$is_wait_action = false;
            self::$offset = 0;
            self::$current_action = $action;

            update_option(self::IS_DONE_OPTION, 0);
            update_option(self::IS_WAIT_ACTION_OPTION, 0);
            update_option(self::OFFSET_OPTION, 0);
            update_option(self::ACTION_OPTION, $action);
        }

        /**
         * Write log.
         */
        private static function write_log(string $message): void
        {
            if (self::WRITE_LOG === false) {
                return;
            }

            $datetime = date('d.m.Y H:i:s');
            $action = self::$current_action;

            file_put_contents(self::LOG_PATH, "[$datetime] [$action] $message" . PHP_EOL, FILE_APPEND);
        }

        /**
         * Get callback.
         */
        public static function get_callback()
        {
            return function(string $action = 'activation', $limit = 5): void {
                try {
                    global $wpdb;

                    $table          = $wpdb->prefix . self::ORDER_ITEM_TABLE;
                    $meta_table     = $wpdb->prefix . self::ORDER_ITEM_META_TABLE;

                    $offset_option  = self::OFFSET_OPTION;
                    $done_option    = self::IS_DONE_OPTION;

                    $offset         = self::$offset;
                    $is_done        = self::$is_done;
                    
                    self::write_log("Start. OFFSET $offset LIMIT $limit");
                  
                    if ($action === 'activation') {
                        $from_status    = '__apiship_shipping';
                        $to_status      = 'shipping';
                    } else {
                        $from_status    = 'shipping';
                        $to_status      = '__apiship_shipping';
                    }

                    $meta_key = 'integrator';
                    $meta_value = 'WPApiShip';

                    $order_item_ids = $wpdb->get_col(
                        $wpdb->prepare(
                            "SELECT DISTINCT oi.order_item_id
                            FROM $meta_table AS oim
                            INNER JOIN $table AS oi ON oim.order_item_id = oi.order_item_id
                            WHERE oim.meta_key = %s
                            AND oim.meta_value = %s
                            LIMIT %d
                            OFFSET %d",
                            $meta_key,
                            $meta_value,
                            $limit,
                            $offset
                        )
                    );

                    if (boolval($is_done) === true or empty($order_item_ids) or $order_item_ids === null) {
                        self::write_log("[FINISH] All data has been processed" . PHP_EOL);

                        update_option($offset_option, 0);
                        update_option($done_option, 1);

                        if (self::$current_action === 'deactivation') {
                            update_option(self::IS_WAIT_ACTION_OPTION, 1);
                        }

                        return;
                    }
                    else {
                        $count = count($order_item_ids);
                        self::write_log("Data processing. Results count $count");
                        
                        foreach ($order_item_ids as $order_item_id) {
                            
                            $result = $wpdb->update($table, ['order_item_type' => $to_status], ['order_item_id' => $order_item_id]);

                            $response = '[success]';

                            if (!$result) {
                                $response = '[ERROR]';
                            }

                            self::write_log("$response orderItemId$order_item_id. Status update: $from_status to $to_status");
                        }
                    }

                    $new_offset = $offset + $limit;

                    self::$offset = $new_offset;

                    update_option($offset_option, $new_offset);
                }
                catch (Throwable $th) {
                    self::write_log('Uncaught exception:' . PHP_EOL . print_r($th, true));
                }
            };
        }
    }
	
endif;
