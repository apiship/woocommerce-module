/**
 * WP ApiShip for WooCommerce.
 * Interface JS functions
 *
 * @since 1.0.0
 *
 * @package WP ApiShip for WooCommerce
 */
/*jslint browser: true*/
/*global jQuery, console, WPApiShip*/

(function($) {
	"use strict";

	if ('undefined' === typeof WPApiShip) {
			return;
	}
	
	var api = {
		parseBool: function(b){return !(/^(false|0)$/i).test(b) && !!b;},
		__: function(key){
			if ( 'undefined' === typeof WPApiShip.i18n[key] ) {
				return key;
			}
			return WPApiShip.i18n[key];
		},		
		getParam: function(param){
			param = param || null;
			if ( null === param ) {
				return null;
			}
			if ( 'undefined' !== typeof WPApiShip[param] ) { 
				return WPApiShip[param];
			}
			if ( 'undefined' !== typeof WPApiShip.data[param] ) { 
				return WPApiShip.data[param];
			}
			return null;
		},
		isStart: function() {
			var checkout = $(api.getParam('checkoutBlockSelector'));
			var cart = $(api.getParam('cartBlockSelector'));
			if (checkout.length != 1 && cart.length != 1) {
				return false;
			}
			return true;	
		},
		getCheckedMethod: function() {
			var checkedMethod = $(api.getParam('checkedShippingMethodSelector'));
			if ( checkedMethod.length != 1 ) {
				return false;
			}
			var value = checkedMethod.val();
			if ( 'string' === typeof value && value.length > 0 ){
				var selector = $('a[data-value="'+value+'"]');
				if ( selector.length > 1 ) {
					selector.removeClass('hidden');
					
				}
			}
		},
		start: function() {
			setTimeout(function(){
				if ( ! api.isStart() ) {
					return;
				}
				api.attachListeners();
			},700);
		},
		attachListeners: function(){
			$(document).on('change', 'input[name="payment_method"]', function(evnt){
				// @see woocommerce\assets\js\frontend\checkout.js
				jQuery(document.body).trigger('update_checkout',{update_shipping_method:true});
			});
			
			// Custom event `selectPointOut` selecting new point out.
			$(document).on('selectPointOut', function(evnt, elem){
				var id = elem.data('id'),
					address = elem.data('address'),
					value = {};
						
				if ( 'undefined' !== typeof address ) {
					value.address = address;
				}
						
				if ( 'undefined' !== typeof id ) {
					value.id = id;
				}

				$( api.getParam('pointOutFieldSelector') ).val( JSON.stringify(value) );
			});
		}
	}

	WPApiShip = $.extend({}, WPApiShip, api);
	WPApiShip.start();	
})(jQuery);