/**
 * WP ApiShipAdminMap for WooCommerce.
 * Interface JS functions
 *
 * @since 1.0.0
 *
 * @scope admin
 *
 * @package WP ApiShip for WooCommerce
 */
/*jslint browser: true*/
/*global jQuery, console, WPApiShipAdmin, WPApiShipAdminMap*/
(function($) {
	"use strict";

	if ('undefined' === typeof WPApiShipAdmin) {
		return;
	}
	
	if ('undefined' === typeof WPApiShipAdminMap) {
		return;
	}
	
	var mapApi = {
		parseBool: function(b){return !(/^(false|0)$/i).test(b) && !!b;},
		listPointsOut: null,
		map: null,
		tariffList: null,
		pointsMode: 2,
		toCity: null,
		providerKey: null,
		requestKeyEnabled: false,
		existsYmaps: function(){
			if ('undefined' === typeof ymaps) {
				return false;
			}
			return true;
		},
		isMapExists: function(){
			if ( mapApi.map !== null && 'object' === typeof mapApi.map ) {
				return true;
			}
			return false;
		},		
		getBalloonContent: function(pointOut) {
			var content = '<div class="point-out-balloon-content">';
			content 	 += 	'<div class="provider-key">Служба доставки: ' + pointOut.providerName;
			content 	 += 	'</div>';			
			content 	 += 	'<div class="point-out-address">'+pointOut.streetType+'.'+pointOut.street+', д.'+pointOut.house;
			if ( pointOut.availableOperation == '2' ) {
				content += ' ('+WPApiShipAdmin.__('postamat')+')';
			}			
			content 	 += 	'</div>';
			content 	 += 	'<br />';

			
			content 	 += 	'<div style="margin-bottom: 10px;">';
			content 	 += 		'<div>Выбор тарифа</div>';
			content 	 += 		'<select class="wpapiship-select-map-tariff">';

			let selectedTariff = null;
			
			mapApi.tariffList.forEach(element => {

				let days = element.daysMin;
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

				let jsonTariff = JSON.stringify(element);

				jsonTariff = jsonTariff.replace(/&/g, "&amp;")
					.replace(/</g, "&lt;")
					.replace(/>/g, "&gt;")
					.replace(/"/g, "&quot;")
					.replace(/'/g, "&#039;");
					
				if (selectedTariff === null) {
					selectedTariff = jsonTariff;
				}

				content  	+= 			'<option data-method-id="' + element.methodId + '" data-provider-key="' + element.providerKey + '" value="' + element.tariffId + '" data-tariff="' + jsonTariff + '">';
				content 	+= 				element.providerName + ', ';
				content  	+= 				element.tariffName + ', ';
				content  	+= 				days + ' дн., ';
				content  	+= 				element.deliveryCost?.toFixed(2) + ' руб.';
				content  	+= 			'</option>';
			});	

			content 	 += 		'</select>';
			content 	 += 	'</div>';

			content 	 += 	'<div class="description">';
			content 	 += 		pointOut.description;
			content 	 += 	'</div>';
			content 	 += 	'<br />';
			content 	 += 	'<div class="point-out-select-wrapper">';
			content 	 += 		'<a href="#" data-tariff="' + selectedTariff + '" data-id="'+pointOut.id+'" data-address="'+pointOut.address+'" onclick="return false;" class="point-out-select button button-secondary">'+WPApiShipAdmin.__('Select')+'</a>';
			content 	 += 	'</div>';			
			content 	 += '</div>';
			return content;
		},
		getCaption: function(pointOut) {
			var caption = pointOut.street+', д.'+pointOut.house;
			if ( pointOut.availableOperation == '2' ) {
				caption += '(постамат)';
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
							iconColor: '#0000ff', //'#0095b6'
						}
					)
				);
			
			// Set tariff.
			$(document).on('change', '.wpapiship-select-map-tariff', function(evnt){
				$('.point-out-select').attr('data-tariff', $(this).find('option:selected').attr('data-tariff'));
			});	
		},
		newMap: function() {
			mapApi.map = new ymaps.Map(
				'wpapiship-ymap',
				{
					center: [55.753909, 37.620938],
					zoom: 12,
					controls: []
				}, 
				// {
				// 	searchControlProvider: 'yandex#search'
				// }
			);			
		},	
		mapInit: function() {
			
			if ( null === mapApi.listPointsOut ) {
				return;
			}

			var currentPointOut = WPApiShipAdmin.getParam('currentPointOutData');
			var currentPointOutData = false;
			var pointOutInDefaultCountry = null;
			
			var checkData = pointOut => {
				if ( mapApi.parseBool(currentPointOut) ) {
					if ( currentPointOut.id === pointOut.id ) {
						currentPointOutData = pointOut;
					}
				}
			}
			
			mapApi.listPointsOut.rows.map(
				function(pointOut, idx){
					if ( pointOutInDefaultCountry === null ) {
						if ( pointOut.countryCode == WPApiShipAdmin.getParam('wcCountryCode') ) {
							pointOutInDefaultCountry = pointOut;
						}
					}
					checkData(pointOut);
					if ( mapApi.map === null ) {
						mapApi.newMap();
					}
					mapApi.placePointOutToMap(pointOut);
				}
			);

			if ( currentPointOutData ) {
				mapApi.map.setCenter([currentPointOutData.lat, currentPointOutData.lng])
			} else {
				mapApi.map.setCenter([pointOutInDefaultCountry.lat, pointOutInDefaultCountry.lng])
			}
		},
		startMap: function() {
		
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
	
				ymaps.ready(mapApi.mapInit);
			}
			
			mapApi.tariffList = JSON.parse($('#adminTariffList').html());
			mapApi.tariffPointsList = JSON.parse($('#adminTariff').html()).pointIds;

			var shipping = WPApiShipAdmin.getParam('wcShipping');
			
			var request = {
				action: 'getListPointsOut',
				city: shipping['_shipping_city'],
				tariffPointsList: mapApi.getTariffPointsList(),
				availableOperation: "[2,3]",
				cod: mapApi.getCod(),
				doneCallback: donePointsOutCallback,
			}

			request.providerKey = WPApiShipAdmin.getShippingMethodMeta('tariffProviderKey','value');

			WPApiShipAdmin.ajax(request);		
		},
		getCod: function(){
			if (mapApi.getPaymentMethod() == 'cod') {
				return '1';
			}
			return '0';
		},
		getPaymentMethod: function(){
			console.log($('#_payment_method').val());
			return $('#_payment_method').val();
		},
		getTariffPointsList: function(){
			return mapApi.tariffPointsList;
		},
		warning: function() {
			$( WPApiShipAdmin.getParam('ymapSelector') ).text('').text(WPApiShipAdmin.__('notYMap')).addClass('not-ymap').toggleClass('hidden');			
		},
		attachListeners: function() {
			
			$(document).on('click', '.point-out-select', function(evnt){
				// Call custom trigger to select point out.
				$(document).trigger('selectPointOut',[$(this)] );

				// Close map.
				$(WPApiShipAdmin.getParam('ymapSelector')).toggleClass('hidden');
				
				// Display message.
				$('#pointOutSaveMessage').toggleClass('hidden');

				// Hide message.
				setTimeout(function(){
					// $('#pointOutSaveMessage').toggleClass('hidden');
					location.reload();
				}, 2000);
			});
			
			// Edit data in ApiShip metabox.
			$('#wpapiship-order-metabox .edit-data').on('click', function(evnt){
				var $t = $(this);
				if ( $t.hasClass('edit-point-out') ) {
					if ( ! mapApi.existsYmaps() ) {
						mapApi.warning();
						return;
					}
					if ( ! mapApi.isMapExists() ) {
						mapApi.startMap();
					}				
					
					$( WPApiShipAdmin.getParam('ymapSelector') ).toggleClass('hidden');
				}
			});				
		},
		start: function() {
			if ( WPApiShipAdmin.getParam('pagenow') == 'post.php' && WPApiShipAdmin.getParam('post_type') == 'shop_order' ) {
				mapApi.attachListeners();
			}			
		},			
	}
	
	WPApiShipAdminMap = $.extend({}, WPApiShipAdminMap, mapApi);
	WPApiShipAdminMap.start();
	
})(jQuery);