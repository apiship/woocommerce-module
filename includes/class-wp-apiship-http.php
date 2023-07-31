<?php
/**
 * File: class-wp-apiship-http.php
 *
 * @package WP ApiShip
 * @subpackage HTTP
 *
 * @since 1.0.0
 */
namespace WP_ApiShip\HTTP;

use WP_ApiShip,
	WP_ApiShip\Options;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists('WP_ApiShip_HTTP', false) ) :

	class WP_ApiShip_HTTP {
		
		/**
		 * Aliases for HTTP response codes.
		 */
		const OK                     	= 200;		 
		const BAD_REQUEST               = 400;
		const UNAUTHORIZED              = 401;
		const PAYMENT_REQUIRED          = 402;
		const FORBIDDEN                 = 403;
		const NOT_FOUND                 = 404;
		
		const INTERNAL_SERVER_ERROR     = 500;
		const NOT_IMPLEMENTED           = 501;
		const BAD_GATEWAY               = 502;
		const SERVICE_UNAVAILABLE       = 503;
		const GATEWAY_TIMEOUT           = 504;
		
		/**
		 * Instance.
		 */
		protected static $instance = null;
		
		/**
		 * Test api url.
		 */
		protected static $test_api_url = 'http://api.dev.apiship.ru/';
		
		/**
		 * Api url.
		 */		
		protected static $api_url = 'https://api.apiship.ru/';
	
		/**
		 * Namespace.
		 */	
		protected static $namespace = 'v1';

		/**
		 * Token.
		 */		
		protected static $token = null;	

		/**
		 * How long the connection should stay open in milliseconds.
		 */
        protected static $timeout = 20000;	

		/**
		 * Get instance.
		 *
		 * @return WP_ApiShip_HTTP
		 */
		public static function get_instance() {
			
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
		
		/**
		 * Constructor.
		 */
		public function __construct() {}

		/**
		 * Get API URL.
		 *
		 * @since 1.0.0
		 */	
		public static function get_api_url() {
			
			if ( WP_ApiShip\WP_ApiShip_Core::is_godmode(true) ) {
				return self::$test_api_url;
			}

			return self::$api_url;
		}

		/**
		 * Get remote URL.
		 *
		 * @since 1.0.0
		 */	
		protected static function get_remote_url( $endpoint = '' ) {
			return self::get_api_url() . self::$namespace . '/' . $endpoint;
		}

		/**
		 * Method POST.
		 *
		 * @since 1.0.0
		 */		
		public static function post( $endpoint = '', $args = array() ) {
			
			if ( $args === false ) {
				$parsed_args = array();
			} else {
				$defaults = array( 
					'headers' => self::get_headers($args),
					'timeout' => self::get_timeout(),
				);
				if ( ! empty( $args['headers'] ) ) {
					unset( $args['headers'] );
				}
				$parsed_args = wp_parse_args( $args, $defaults );
			}

			return wp_remote_post( 
				self::get_remote_url( $endpoint ),
				$parsed_args
			);				
		}
		
		/**
		 * Method GET.
		 *
		 * @since 1.0.0
		 */		
		public static function get( $endpoint = '', $args = array() ) {
			
			if ( $args === false ) {
				$parsed_args = array();
			} else {
				$defaults = array( 
					'headers' => self::get_headers($args),
					'timeout' => self::get_timeout(),
				);
				if ( ! empty( $args['headers'] ) ) {
					unset( $args['headers'] );
				}				
				$parsed_args = wp_parse_args( $args, $defaults );
			}	

			return wp_remote_get( 
				self::get_remote_url( $endpoint ),
				$parsed_args			
			);			
		}
		
		/**
		 * Method DELETE.
		 *
		 * @since 1.0.0
		 */		
		public static function delete( $endpoint = '', $args = array() ) {
			
			if ( $args === false ) {
				$parsed_args = array();
			} else {
				$defaults = array( 
					'method'  => 'DELETE',
					'headers' => self::get_headers($args),
					'timeout' => self::get_timeout(),
				);
				if ( ! empty( $args['headers'] ) ) {
					unset( $args['headers'] );
				}				
				$parsed_args = wp_parse_args( $args, $defaults );
			}				

			return wp_remote_request( 
				self::get_remote_url( $endpoint ),
				$parsed_args			
			);
		}

		/**
		 * Get timeout.
		 *
		 * @since 1.0.0
		 */		
		public static function get_timeout() {
			return self::$timeout;
		}
		
		/**
		 * Get headers.
		 *
		 * @since 1.0.0
		 */
		public static function get_headers($args = null) {
			
			$default = array(
				'Accept' => 'application/json',
				'Authorization' => self::get_token(),
				'platform' => Options\WP_ApiShip_Options::PLATFORM
			);
			
			if ( is_null($args) || empty( $args['headers'] ) ) {
				$headers = $default;
			} else {
			
				$headers = array_merge(
					$default,
					$args['headers']
				);
			}
			
			return $headers;
		}

		/**
		 * Get token.
		 *
		 * @since 1.0.0
		 */
		public static function get_token()
		{
			return Options\WP_ApiShip_Options::get_option('token');
		}		
	}
	
endif;

# --- EOF