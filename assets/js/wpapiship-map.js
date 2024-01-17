/**
 * WP ApiShipMap for WooCommerce.
 * Interface JS functions
 *
 * @since 1.0.0
 *
 * @scope front
 *
 * @package WP ApiShip for WooCommerce
 */
/*jslint browser: true*/
/*global jQuery, console, WPApiShip, WPApiShipMap*/

(function($) {
	"use strict";

	if ('undefined' === typeof WPApiShip) {
		return;
	}
		
	if ('undefined' === typeof WPApiShipMap) {
		return;
	}
	
	if ('undefined' === typeof ymaps) {
		return;
	}	
	
	var mapApi = {
		parseBool: function(b){return !(/^(false|0)$/i).test(b) && !!b;},		
		listPointsOut: null,
		tariffList: null,
		pointsMode: 1,
		methodId: null,
		map: null,
		toCity: null,
		providerKey: null,
		requestKeyEnabled: false,
		isMapExists: function(){
			if ( mapApi.map !== null && 'object' === typeof mapApi.map ) {
				return true;
			}
			return false;
		},
		getToCity: function(){
			return mapApi.toCity;
		},
		setToCity: function(){
			var city = false;
			var cart = $(WPApiShip.getParam('cartBlockSelector'));
			if (cart.length == 1) {
				city = $('input[name="calc_shipping_city"]').val();
			} else {
				var differentCity = $('input[name="ship_to_different_address"]').prop('checked');
				if ( differentCity ) {
					city = $('input[name="shipping_city"]').val();
				} else {
					city = $('input[name="billing_city"]').val();
				}
			}
			mapApi.toCity = city.trim();
		},
		getCod: function(){
			if (mapApi.getPaymentMethod() == 'cod') {
				return '1';
			}
			return '0';
		},
		getPaymentMethod: function(){
			return $('input[name="payment_method"]:checked').val();
		},
		getProviderKey: function(){
			return mapApi.providerKey;
		},
		getTariffPointsList: function(){
			return mapApi.tariffPointsList;
		},
		setProviderKey: function(key){
			if ( false === key || 'string' === typeof key ) {
				mapApi.providerKey = key;
			} else if (mapApi.requestKeyEnabled == false) {
				var val = $(mapApi.getParam('checkedShippingMethodSelector')).val();
				var dataElem = $('.wpapiship-delivery-to-point[data-value="'+val+'"]');
				if ( dataElem.length == 1 ) {
					var deliveryType = dataElem.data('delivery-type');
					if ( mapApi.isDeliveryToPointOut(deliveryType) ) {
						mapApi.providerKey = dataElem.data('provider-key');
					} else {
						mapApi.providerKey = false;
					}
				}
			}
		},		
		setTariffData: function(){
			var val = $(mapApi.getParam('checkedShippingMethodSelector')).val();
			var dataElem = $('.wpapiship-delivery-to-point[data-value="'+val+'"]');
			
			if (dataElem.length == 1) {
				var deliveryType = dataElem.data('delivery-type');
				
				if ( mapApi.isDeliveryToPointOut(deliveryType) ) {
					mapApi.tariffPointsList = dataElem.data('points-list');
					mapApi.tariffList = dataElem.data('tariff-list');
				} else {
					mapApi.tariffPointsList = false;
					mapApi.tariffList = false;
				}

				mapApi.pointsMode = dataElem.data('display-mode');
				mapApi.methodId = val;
			}

		},	
		getListPointsOut: function(){
			return mapApi.listPointsOut;
		},
		getPointOut: function(id){
			id = id || false;
			if ( ! id ) {
				return false;
			}
			var point = false;
			mapApi.getListPointsOut().rows.map(
				(pointOut, idx) => {
					if ( pointOut.id * 1 === id * 1 ) {
						point = pointOut;
					}
				}
			);
			return point;
		},
		getAjaxUrl: function(){
			return WPApiShipMap.ajaxurl;
		},
		getProcessAjax: function(){
			return WPApiShipMap.process_ajax;
		},		
		getMap: function(){
			return mapApi.map;
		},
		getParam: function(param){
			param = param || null;
			if ( null === param ) {
				return WPApiShipMap.data;
			}
			if ( 'undefined' !== typeof WPApiShipMap[param] ) { 
				return WPApiShipMap[param];
			}
			if ( 'undefined' !== typeof WPApiShipMap.data[param] ) { 
				return WPApiShipMap.data[param];
			}
			return null;
		},
		canStart: function() {
			if ( WPApiShip.isStart() ) {
				return true;
			}
			return false;
		},
		getBalloonContent: function(pointOut) {
			var content = '<div class="point-out-balloon-content">';
			
			content  	+= 		'<div class="provider-key">Служба доставки: '+pointOut.providerName;
			content  	+= 		'</div>';

			content 	 += 	'<div class="point-out-address">'+pointOut.streetType+'.'+pointOut.street+', д.'+pointOut.house;
			
			if ( pointOut.availableOperation == '2' ) {
				content  += 	' ('+WPApiShip.__('postamat')+')';
			}		

			content 	 += 	'</div>';
			content 	 += 	'<br />';	

			if (mapApi.pointsMode != 1) {
				content 	 += 	'<div style="margin-bottom: 10px;">';
				content 	 += 		'<div>Выбор тарифа</div>';
				content 	 += 		'<select class="wpapiship-select-map-tariff">';
				
				mapApi.tariffList.forEach(element => {
					let days = element.daysMin;;
					let pointExists = false;
					
					element.pointIds.forEach(pointId => {
						if (pointId == pointOut.id) {
							pointExists = true;
						}
					});

					if (pointExists === false) {
						return;
					}

					if (element.daysMin != element.daysMax) {
						days += '-' + element.daysMax;
					}

					content  	+= 			'<option data-method-id="' + element.methodId + '" data-provider-key="' + element.providerKey + '" value="' + element.tariffId + '">';
					content 	+= 				element.providerName + ', '; // if (mapApi.pointsMode == 3) {}	
					content  	+= 				element.tariffName + ', ';
					content  	+= 				days + ' дн., ';
					content  	+= 				element.deliveryCost?.toFixed(2) + ' руб.';
					content  	+= 			'</option>';
				});	

				content 	 += 		'</select>';
				content 	 += 	'</div>';
			} 

			content 	 += 	'<div class="description">';
			content 	 += 		pointOut.description;
			content 	 += 	'</div>';
			content 	 += 	'<br />';			
			content 	 += 	'<div class="point-out-select-wrapper">';
			content 	 += 		'<a href="#" data-id="'+pointOut.id+'" data-address="'+pointOut.address+'"';
			content 	 += 			' onclick="return false;" class="point-out-select button button-secondary">'+WPApiShip.__('Select');
			content 	 += 		'</a>';			
			content 	 += 	'</div>';			
			content 	 += '</div>';
			return content;
		},
		getCaption: function(pointOut) {
			var caption = pointOut.street+', д.'+pointOut.house;
			if ( pointOut.availableOperation == '2' ) {
				caption += '('+WPApiShip.__('postamat')+')';
			}
			return caption;
		},
		placePointOutToMap: function(pointOut) {
			mapApi.map.geoObjects
				.add(
					new ymaps.Placemark(
						[pointOut.lat, pointOut.lng], 
						{
							balloonContent: mapApi.getBalloonContent(pointOut),
							iconCaption: mapApi.getCaption(pointOut)
						}, 
						{
							preset: 'islands#icon',
							iconColor: '#0000ff',
						}
					)
				)			
		},
		newMap: function(pointOut) {
			mapApi.map = new ymaps.Map(
				'wpapiship-checkout-ymap',
				{
					center: [pointOut.lat, pointOut.lng],
					zoom: 12,
					controls: []
				}
			);
			mapApi.map.controls.add(
				new ymaps.control.Button(WPApiShip.__('closeButtonCaption')),
				{float: 'right'}
			);
			mapApi.map.controls.events.add('click', function(e){
				mapApi.closeMap();
			});
		},		
		mapInit: function() {
			var list = mapApi.getListPointsOut();
			if ( null === list ) {
				return;
			}
			list.rows.map(
				function(pointOut){
					if ( mapApi.map === null ) {
						mapApi.newMap(pointOut);
					}
					mapApi.placePointOutToMap(pointOut);
				}
			); 
		},
		getMapCenter: function(pointOut) {
			var lat = $( mapApi.getParam('pointOutAddressSelector') ).data('lat'),
					lng =	$( mapApi.getParam('pointOutAddressSelector') ).data('lng'),
					center = {};
			
			center.lat = lat*1; 
			if ( 'undefined' === typeof lat || '' === lat ) {
				center.lat = pointOut.lat;
			}
			
			center.lng = lng*1;
			if ( 'undefined' === typeof lng || '' === lng ) {
				center.lng = pointOut.lng;
			}
			return center;
		},
		mapStart: function() {
			var currentCity = mapApi.getToCity();
			mapApi.listPointsOut = null;
			
			var donePointsOutCallback = (response) => {

				if ( 'undefined' === typeof response ) {
					return;
				}
				if ( ! response.success ) {
					return;
				}
			
				try {
					mapApi.listPointsOut = JSON.parse(response.data.response.body);
				} catch (uncaught) {
					console.log('getListPointsOut:: parsing error.');
					return;
				}

				if ( mapApi.isMapExists() ) {
					mapApi.getListPointsOut().rows.map(
						function(pointOut, idx){
							if ( idx === 0 ) {
								mapApi.map.geoObjects.removeAll();
								var center = mapApi.getMapCenter(pointOut);
								// mapApi.map.setCenter([pointOut.lat, pointOut.lng]);
								mapApi.map.setCenter([center.lat, center.lng]);
								// mapApi.map.setZoom(12)
							}
							mapApi.placePointOutToMap(pointOut);
						}
					); 					
				} else {
					ymaps.ready(mapApi.mapInit);
				}					
			}

			var request = {
				action: 'getListPointsOut',
				city: currentCity,
				tariffPointsList: mapApi.getTariffPointsList(),
				availableOperation: "[2,3]",
				cod: mapApi.getCod(),
				doneCallback: donePointsOutCallback,
			}
			if (mapApi.pointsMode != 3 || mapApi.pointsMode == 3 && mapApi.requestKeyEnabled == true) {
				request.providerKey = mapApi.getProviderKey();
			}
			mapApi.ajax(request);	
		},
		ajax: function(request){
			return $.ajax({
				beforeSend:function(){
					if ( typeof mapApi.ajaxBeforeSend === 'function' ) {
						mapApi.ajaxBeforeSend(request);
					}
				},
				type: 'POST',
				url: mapApi.getAjaxUrl(),
				data: {
					action: mapApi.getProcessAjax(), 
					request: request
				},
				dataType: 'json' 
			})
			.done(function(response) {
				if ( typeof request.doneCallback === 'function' ) {
					request.doneCallback(response);
				}
			})
			.fail(function(jqXHR, textStatus){
				// if jqXHR.status == 0, then internet connection is failed.
				if ( typeof request.failCallback === 'function' ) {
					request.failCallback(jqXHR, textStatus);
				}					
			})
			.always(function (jqXHR, textStatus) {
				if ( typeof request.alwaysCallback === 'function' ) {
					request.alwaysCallback(jqXHR, textStatus);
				}				
			});
		},
		openMap: function() {
			mapApi.mapStart();
			$('body').addClass(mapApi.getParam('checkoutBodyOverlay'));
			$(mapApi.getParam('checkoutMapWrapper')).addClass('open');
		},
		closeMap: function() {
			$('body').removeClass(mapApi.getParam('checkoutBodyOverlay'));
			$(mapApi.getParam('checkoutMapWrapper')).removeClass('open');
		},
		showPointOutFields: function() {
			$(WPApiShip.getParam('checkoutRowSelector')).removeClass('hidden');
			let pointOutId = $( mapApi.getParam('checkedShippingMethodSelector') ).parent().find('.wpapiship-map-start').data('point-out-id');
			if (pointOutId == 0) {
				$(mapApi.getParam('pointOutFieldSelector')).val('');
				$(mapApi.getParam('pointOutAddressSelector')).val('').data('id','').data('lat','').data('lng','');
			} else {
				mapApi.initSelectedPoint();
			}
		},
		hidePointOutFields: function() {
			$(WPApiShip.getParam('checkoutRowSelector')).addClass('hidden');					
			$(mapApi.getParam('pointOutFieldSelector')).val('');
			$(mapApi.getParam('pointOutAddressSelector')).val('').data('id','').data('lat','').data('lng','');
		},
		selectProviderCallback: function() {
			$(document).on('change', mapApi.getParam('mapProviderSelect'), function(evnt){
				mapApi.reloadMap($(this).val());
				mapApi.selectProviderCallback();
			});
		},
		reloadMap: function(requestProviderKey = null) {
			// Set selected provider key.
			mapApi.requestKeyEnabled = false;
			if (requestProviderKey != null && requestProviderKey != 'all-providers') {
				mapApi.providerKey = requestProviderKey;
				mapApi.requestKeyEnabled = true;
			}
			
			// Reload the map.
			mapApi.setToCity();
			mapApi.setProviderKey();
			mapApi.setTariffData();
			mapApi.openMap();
		},
		attachListeners: function() {
			
			// Stop event bubbling when clicking on map itself.
			$(document).on('click', mapApi.getParam('checkoutMapSelector'), function(evnt){
				evnt.stopPropagation();
			});
			
			// Close map by clicking outside it.
			$(document).on('click', mapApi.getParam('checkoutBodyOverlaySelector'), function(evnt){
				// Check if click on map options container.
				if (!$(evnt.target).closest('#wpapiship_map_options').length) {
					mapApi.closeMap();
				}
			});

			// Select option from providers list.
			mapApi.selectProviderCallback();

			// Click on Select button in the balloon content box.
			$(document).on('click', mapApi.getParam('pointOutSelectSelector'), function(evnt){
				$(document).trigger('selectPointOut',[$(this)]);
				
				/// update form

				var $t = $(this);
				var id = $t.data('id'), lat, lng, point = false, address = '';
				if ( 'undefined' !== typeof id ) {
					point = mapApi.getPointOut(id);
					if ( point ) {
						lat = point.lat;
						lng = point.lng;
						if ( typeof lat === 'undefined' ) {
							lat = '';
						}
						if ( typeof lng === 'undefined' ) {
							lng = '';
						}						
						address = point.city+', '+point.streetType+'.'+point.street+' '+point.house;							
						$( mapApi.getParam('pointOutAddressSelector') ).data('id',id.toString()).data('lat',lat.toString()).data('lng',lng.toString());
								
						// Set point data.
						$( mapApi.getParam('pointOutAddressSelector') ).val(address);
						$( mapApi.getParam('checkedShippingMethodSelector') ).parent().find('.pointAddress').text(address);
						$( mapApi.getParam('checkedShippingMethodSelector') ).parent().find('.pointName').text(point.name);
						$( mapApi.getParam('checkedShippingMethodSelector') ).parent().find('.wpapiship-map-start').text(' (' + WPApiShip.__('selectedPointButtonText') + ')');

						// Set tariff data.
						if (mapApi.pointsMode != 1) {
							mapApi.tariffId = $('.wpapiship-select-map-tariff').val();
							mapApi.providerKey = $('.wpapiship-select-map-tariff option:selected').data('provider-key');
							mapApi.methodId = $('.wpapiship-select-map-tariff option:selected').data('method-id');
						}

						// Save selected point.
						var request = {
							action: 'saveClientSelectedPoint',
							address: address,
							name: point.name,
							id: id,
							tariff_id: mapApi.tariffId,
							method_id: mapApi.methodId
						}
						mapApi.ajax(request);

						$('body').trigger('update_checkout');
					}
				}	
				mapApi.closeMap();
			});
			
			// Select shipping method (tariff).
			$(document).on('click', mapApi.getParam('shippingMethodSelector'), function(evnt){

				if ($(this).attr('data-is-saved') == 1) {
					$(this).attr('data-is-saved', 0);
					return;
				}

				// Reset #wpapiship_shipping_to_point_out_field field.
				$(mapApi.getParam('pointOutFieldSelector')).val('');

				var val = $(mapApi.getParam('checkedShippingMethodSelector')).val();
				var deliveryType = false;
				var dataElem = $('.wpapiship-delivery-to-point[data-value="'+val+'"]');

				if ( dataElem.length == 1 ) {
					
					deliveryType = dataElem.data('delivery-type');
					
					if ( mapApi.isDeliveryToPointOut(deliveryType) ) {
						mapApi.showPointOutFields();
					} else {
						mapApi.hidePointOutFields();					
					}
				}

				// Save selected tariff.
				let tariff_id = $(this).parent().find('.wpapiship-map-start').data('tariff-id');
				var request = {
					action: 'saveClientSelectedTariff',
					tariff_id: tariff_id,
					method_id: val,
				}
				mapApi.ajax(request);	

			});		
			
			// Some settings and open map.
			$(document).on('click', mapApi.getParam('mapStartSelector'), function(evnt){
				$(this).parent().find('input.shipping_method').prop('checked', true);
				mapApi.setToCity();
				mapApi.setProviderKey();
				mapApi.setTariffData();
				mapApi.openMap();
			});
			
			// Set selected tariff after any shipping block reloading.
			$(document).ready(function(){
				setInterval(function(){
					if ($('.woocommerce .blockUI.blockOverlay').length != 0) {
						let innerInterval = setInterval(function(){
							if ($('.woocommerce .blockUI.blockOverlay').length == 0) {
								$('input.shipping_method').each(function(index){
									let selected = $(this).parent().find('.wpapiship-delivery-to-point').data('tariff-selected');
									if (selected == '1' && $(this).prop('checked') == false) {
										$(this).prop('checked', true);
										mapApi.initSelectedPoint();
										return;
									}
								});
								clearInterval(innerInterval);
							}
						}, 1200);
					}
				}, 1500);
			});
		},
		initSelectedPoint: function(){
			var val = $(mapApi.getParam('checkedShippingMethodSelector')).val();
			var dataElem = $('.wpapiship-delivery-to-point[data-value="'+val+'"]');

			if ( dataElem.length == 1 ) {
				
				if (dataElem.data('point-out-id') != '0') {
					var id = dataElem.data('point-out-id'),
						address = dataElem.data('point-out-address'),
						value = {};
							
					if ( 'undefined' !== typeof address ) {
						value.address = address;
					}
							
					if ( 'undefined' !== typeof id ) {
						value.id = id;
					}

					$( mapApi.getParam('pointOutFieldSelector') ).val( JSON.stringify(value) );
				}
			}
		},
		isDeliveryToPointOut: function(type){
			/**
       		 * now we get type as single number.			
			 * @todo case when type == '1,2'
			 */
			var toPointOut = false; 
			$.each(mapApi.getParam('deliveryTypes'), function(key,_type) {
				if ( _type * 1 === type * 1 && 'toPointOut' === key ) {
					toPointOut = true;
				}
			});
			return toPointOut;
		},
		addButton: function(){
			setTimeout( () => {
				var addressField = '<p class="form-row form-row-wide '+WPApiShip.getParam('checkoutRow')+' hidden" id="wpapiship-point-out-address-wrapper">';
				addressField    += '<label for="'+mapApi.getParam('pointOutAddress')+'">Пункт выдачи заказа </label>';
				addressField    += '<input type="text" id="'+mapApi.getParam('pointOutAddress')+'" disabled="disabled" data-id="" data-lat="" data-lng="" />';
				addressField    += '</p>';
				$(addressField).insertAfter( WPApiShip.getParam('pointOutFieldWrapperSelector') );				
			}, 1000);
		},		
		initialSet: function() {
			var val = $( WPApiShip.getParam('checkedShippingMethodSelector') ).val();
			var dataElem = $('.wpapiship-delivery-to-point[data-value="'+val+'"]');
			if ( dataElem.length == 1 ) {
				var deliveryType = dataElem.data('delivery-type');
				if ( mapApi.isDeliveryToPointOut(deliveryType) ) {
					setTimeout( () => {
						mapApi.setProviderKey();
						mapApi.setTariffData();
						mapApi.showPointOutFields();
					}, 4500);
				}
			}
		},
		start: function() {
			if ( mapApi.canStart() ) {
				mapApi.addButton();
				mapApi.initialSet();
				mapApi.attachListeners();
				mapApi.initSelectedPoint();
			}
		},			
	}
	
	WPApiShipMap = $.extend({}, WPApiShipMap, mapApi);
	WPApiShipMap.start();	
		
})(jQuery);