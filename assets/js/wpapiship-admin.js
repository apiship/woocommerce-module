/**
 * WP ApiShip for WooCommerce Admin.
 * Interface JS functions
 *
 * @since 1.0.0
 *
 * @package WP ApiShip for WooCommerce
 */
/*jslint browser: true*/
/*global jQuery, console, WPApiShipAdmin*/

(function($) {
	"use strict";
	
	if ('undefined' === typeof WPApiShipAdmin) {
		return;
	}
		
	var api = {
		parseBool: function(b){return !(/^(false|0)$/i).test(b) && !!b;},
		getCurrentSection: function(){
			return WPApiShipAdmin.data.section;
		},
		isCurrentSection: function(section){
			section = section || false;
			if ( section === api.getCurrentSection() ) {
				section = true;
			} else {
				section = false;
			}
			return section;			
		},
		getCurrentTab: function(){
			return WPApiShipAdmin.data.tab;
		},
		isCurrentTab: function(tab){
			tab = tab || false;
			if ( tab === api.getCurrentTab() ) {
				tab = true;
			} else {
				tab = false;
			}
			return tab;
		},
		getAjaxUrl: function(){
			return WPApiShipAdmin.ajaxurl;
		},
		getProcessAjax: function(){
			return WPApiShipAdmin.process_ajax;
		},
		getShippingOrderItemId: function(){
			var id = api.getParam('shippingOrderItemId');
			if ( null === id ) {
				var $el = $('#wpapiship-integrator-line-items');
				if ( $el.length == 1 ) {
					api.setData('shippingOrderItemId', $el.data('shipping-order-item-id'));
					id = api.getParam('shippingOrderItemId');
				} else {
					// Get order item id from WC.
					$el = $('#order_shipping_line_items .shipping');
					if ( $el.length == 1 ) {
						api.setData('shippingOrderItemId', $el.data('order_item_id'));
						id = api.getParam('shippingOrderItemId');						
					}
				}
			}
			return id;
		},
		getMetaboxFields: function(){
			return WPApiShipAdmin.data['metaboxFields'];
		},
		getMetaboxField: function(field, attr){
			field = field || false;
			attr = attr || false;
			if ( ! field ) {
				return api.getMetaboxFields();
			}
			if ( 'boolean' === typeof attr && !attr ) {
				return WPApiShipAdmin.data['metaboxFields'][field];
			}
			return WPApiShipAdmin.data['metaboxFields'][field][attr];
		},
		getParam: function(param){
			param = param || null;
			if ( null === param ) {
				return WPApiShipAdmin.data;
			}
			if ( 'undefined' !== typeof WPApiShipAdmin[param] ) { 
				return WPApiShipAdmin[param];
			}
			if ( 'undefined' !== typeof WPApiShipAdmin.data[param] ) { 
				return WPApiShipAdmin.data[param];
			}
			return null;
		},
		getShippingMethodMeta: function(meta, key, defaultValue){
			meta = meta || null;
			key = key || null;
			defaultValue = defaultValue || null;
			var metas = api.getParam('shippingMethodMeta');
			if ( meta === null ) {
				return metas;
			}
			if ( key === null ) {
				return metas[meta];
			}
			// console.log('meta ::', meta);
			// console.log('key  ::', key);
			if ( null === metas[meta][key] || 'undefined' === typeof metas[meta][key] ) {
				return defaultValue;
			}
			return metas[meta][key];
		},
		__: function(key){
			if ( 'undefined' === typeof WPApiShipAdmin.i18n[key] ) {
				return key;
			}
			return WPApiShipAdmin.i18n[key];
		},
		setData: function(param, value, type){
			type = type || 'array';
			if ( 'undefined' === typeof WPApiShipAdmin.data[param] ) { 
				if ( 'array' === type ) {
					WPApiShipAdmin.data[param] = [];
				}
			}
			WPApiShipAdmin.data[param] = value;	
		},
		setSubmitButton: function(section, status){
			if ( section === 'connections' && 'hide' === status ) {
				$('.submit').addClass('hidden');
			}
			if ( section === 'debug' && 'hide' === status ) {
				$('.submit').addClass('hidden');
			}	
			if ( section === 'docs' && 'hide' === status ) {
				$('.submit').addClass('hidden');
			}			
		},
		getSpaces: function(count){
			var spaces = '';
			if ( 'undefined' === typeof count || count == 0 ) {
				return '';
			}
			for (var i=0; i<count; i++) {
				spaces += ' ';
			}
			return spaces;
		},
		orderReportLoop: 0,
		getOrderReport: function(source){
			if ( source === null ) {
				return 'null';
			}
		
			if ( 'object' === typeof source ) {
				var openBracket, closeBracket;
				
				openBracket = '[';
				if ( undefined === source.push ) {
					// It's object.
					openBracket = '{';
				}
						
				var r = api.getSpaces(1) + openBracket + "\n";
				api.orderReportLoop++;
				Object.keys(source).forEach(function(key) {
					if ( 'object' === typeof source[key] && null !== source[key] ) {
						closeBracket = ']';
						if ( undefined === source[key].push ) {
							// It's object.
							closeBracket = '}';
						}
						r = r+api.getSpaces(api.orderReportLoop)+'"'+key+'"'+':'+api.getSpaces(' ');
						api.orderReportLoop++;
						r = r+api.getOrderReport(source[key]);
						r = r+api.getSpaces(api.orderReportLoop*2)+closeBracket+','+"\n";
						api.orderReportLoop--;
					} else {
						if ( null === source[key] ) {
							r = r+api.getSpaces(api.orderReportLoop*2)+'"'+key+'"'+':null,'+"\n";
						} else {
							r = r+api.getSpaces(api.orderReportLoop*2)+'"'+key+'"'+':"'+source[key]+'",'+"\n";
						}
					}
				});
				api.orderReportLoop--;
				if (api.orderReportLoop == 0) {
					r = r + '}'+"\n";
				}
				return r;
			} else if ( 'string' === typeof source ) {
				return source;
			}
		},
		orderViewer: {
			textArea: $('.wpapiship-log-viewer pre'),
			box: $('.wpapiship-viewer-section'),
			buttonClose: $('.wpapiship-close-viewer'),
			inited: null,
			open() {
				api.orderViewer.init();
				api.orderViewer.box.removeClass('hidden');
				return this;
			},
			close() {
				api.orderViewer.box.addClass('hidden');
				return this;
			},
			toggle(content) {
				content = content || false;
				if ( api.orderViewer.box.hasClass('hidden') ) {
					api.orderViewer.open();
					if ( content ) {
						api.orderViewer.add(content);
					}
				} else {
					api.orderViewer.close();
					if ( '' == content ) {
						api.orderViewer.clear();
					}						
				}
				return this;
			},
			add(content) {
				api.orderViewer.textArea.text(content);
				return this;
			},
			clear(){
				api.orderViewer.textArea.text('');
				return this;
			},
			init(){
				if ( null !== api.orderViewer.inited ) {
					return;
				}
				api.orderViewer.buttonClose.on('click',function(evnt){
					api.orderViewer.clear().close();
					return this;
				});
				api.orderViewer.inited = true;
			}
		},		
		initOrderPage: function() {
			
			if ( api.getParam('pagenow') == 'post.php' && api.getParam('post_type') == 'shop_order' ) {
				// Let's run.
			} else {
				// Do not run.
				return;
			}
	
			var doneCheckCB = response => {
				if ( 'undefined' === typeof response ) {
					return;
				}

				try {
					var body = JSON.parse(response.data.response.body);
				} catch {
					console.log('connectionCheck:: Parsing error.');
					return;
				}
	
				if ( ! response.success ) {
					$(api.getParam('messageContentWrapperSelector')).text( api.__('Error')+': '+body.message );
					return;
				}
				
				$(api.getParam('orderShippingWrapperSelector')).removeClass(api.getParam('orderHiddenClass'));
				setTimeout(function(){
					// api.setProviderData();
					api.setPostNewOrderAction();
					api.setOrderActions();
					api.setStatusOrdersAction();
					api.setViewOrdersAction();
					api.setValidateOrdersAction();
					api.setToolsOrdersAction();
					api.setIntegratorOrder();
					api.attachOrdersListeners();
				}, 500);				
			}

			api.ajax({
				action: 'connectionCheck',
				doneCallback: doneCheckCB,
			});
		},
		setIntegratorOrder: function(){
			// Check out for existing but incorrect ApiShip order.
			var id = api.isCorrectIntegratorOrder();
			if ( id ) {
				api.getIntegratorOrder(id);
			}
		},
		getIntegratorOrder: function(id){
			var doneCB = function(response){
				if ( 'undefined' === typeof response ) {
					return;
				}

				if ( ! response.success ) {
					return;
				}

				try {
					var iOrder = JSON.parse(response.data.response.body);
				} catch {
					console.log('getSenderAddressString: Parsing error.');
					return;
				}
		
				// Disable some fields.
				$( api.getMetaboxField('pickupDate','selector') ).val(iOrder.order.pickupDate).attr('disabled','disabled');
				$( api.getMetaboxField('contactName','selector') ).val(iOrder.sender.contactName).attr('disabled','disabled');
				$( api.getMetaboxField('phone','selector') ).val(iOrder.sender.phone).attr('disabled','disabled');

				// Rewrite pickup type. @todo Remove after testing.
				$( api.getParam('pickupTypeSelector') ).text( 
					// api.getParam('pickupType')[iOrder.order.pickupType]
				);
				// Rewrite delivery type.
				$( api.getParam('deliveryTypeSelector') ).text( 
					// api.getParam('deliveryType')[iOrder.order.deliveryType]
				);
				
				// Set data in WPApiShip metabox.
				if ( iOrder.order.pointInId !== null && iOrder.order.pointInId * 1 > 0 ) {
			
					var getPointDoneCB = response => {
						if ( 'undefined' === typeof response ) {
							return;
						}

						if ( ! response.success ) {
							return;
						}
						
						try {
							var body = JSON.parse(response.data.response.body);
						} catch {
							console.log('getPoint:: Parsing error.');
							return;
						}

						// Set Point in data.
						if ( body.rows.length > 0 && 'undefined' !== typeof body.rows[0] ) {
							$( api.getMetaboxField('pointInId','selector') ).val(body.rows[0]['id']);
							$( api.getMetaboxField('pointInAddress','selector') ).val(body.rows[0]['address']);
						}

					}
					
					var request = {
						action: 'getPoint',
						pointId: iOrder.order.pointInId,
						providerKey: iOrder.order.providerKey,
						doneCallback: getPointDoneCB,
					}
					api.ajax(request);
				}

				// Set Integrator Order Label.
				var getOrderLabelDoneCB = response => {
					if ( 'undefined' === typeof response ) {
						return;
					}

					if ( ! response.success ) {
						return;
					}
					
					try {
						var body = JSON.parse(response.data.response.body);
					} catch {
						console.log('getPoint:: Parsing error.');
						return;
					}

					if ( 'object' === typeof body.failedOrders && null !== body.failedOrders ) {
						$('#wpapiship-order-metabox .label-not-exists').removeClass('hidden');
						$.each(body.failedOrders, function(i,e){
							$('#wpapiship-order-metabox .label-message').text(e.message);
							return false; // @todo multiple orders.
						});
						
					} else {
						$('#wpapiship-order-metabox .label-exists').removeClass('hidden');
						$('#wpapiship-order-metabox .label-download').data('url',body.url);
						
					}
			
				}

				var request = {
					action: 'getOrderLabel',
					integratorOrder: id,
					doneCallback: getOrderLabelDoneCB,
				}
				api.ajax(request);					
				
				// Generate sender address string.
				var addressDoneCB = (response) => {
					if ( 'undefined' === typeof response ) {
						return;
					}
					if ( ! response.success ) {
						return;
					}
					if ( 'string' === typeof response.data.response ) {
						$( api.getParam('senderAddressStringSelector') ).text(response.data.response);
					}
				}
				api.ajax({
					action: 'getSenderAddressString',
					order: iOrder,
					doneCallback: addressDoneCB,
				})
			}
			var request = {
				action: 'getIntegratorOrder',
				postOrderID: api.getParam('post_id'),
				integratorOrder: id,
				doneCallback: doneCB,
			}
			api.ajax(request);					
		},
		integratorOrderExists: function(){
			var integratorOrder = api.getShippingMethodMeta('integratorOrder','value') || false;
			if ( integratorOrder === api.getParam('INTEGRATOR_ORDER_INIT_VALUE') ) {
				return false;
			}
			if ( integratorOrder * 1 > 0 ) {
				return true;
			}			
		},
		isCorrectIntegratorOrder: function(){
			
			var integratorOrder = api.getShippingMethodMeta('integratorOrder','value') || false;
			if ( integratorOrder === api.getParam('INTEGRATOR_ORDER_INIT_VALUE') ) {
				integratorOrder = false;
			}
			if ( integratorOrder ) {
				return integratorOrder;
			}
			
			var warningElem = $( api.getParam('integratorOrderWarningSelector') );
			
			if ( warningElem.length != 1 ) {
				return;
			}
			
			var doneCB = (response) => {
				if ( 'undefined' === typeof response ) {
					return;
				}
				if ( ! response.success ) {
					return;
				}
				
				try {
					var body = JSON.parse(response.data.response.body);
				} catch {
					return;
				}				
				
				if ( body.orderInfo['orderId'] * 1 > 0 ) {
					var title = api.__('orderExists').replace('{{id}}',api.getParam('post_id'));
					warningElem.attr('title',title);
					warningElem.removeClass('hidden');
				}
			}
			
			var request = {
				action: 'getOrderStatusByClientNumber',
				postOrderID: api.getParam('post_id'),
				doneCallback: doneCB,
			}
			api.ajax(request);
			return false;
		},
		setPostNewOrderAction: function(){
			
			/**
			 * Post new order.
			 */
			var beforeCB = (request) => {
				$('.button.post-orders').prop('disabled','disabled');
				api.orderViewer.clear();
			}
			var alwaysCB = (response) => {
				$('.button.post-orders').prop('disabled','');			
			}			
			var doneCB = (response) => {
				if ( 'undefined' === typeof response ) {
					return;
				}					
				try {
					var body = JSON.parse(response.data.response.body);
				} catch {
					return;
				}

				if ( response.success ) {
					api.orderViewer.add('Новый заказ создан: ' + '#' + body.orderId);
					setTimeout(function(){
						location.reload();
					}, 500);
				} else {
					api.orderViewer.add(api.getErrorsReport(body, 'При создании заказа выявлены ошибки:'));
				}
				api.orderViewer.open();
			}
			
			$('.post-orders').on('click', function(evnt){
				evnt.preventDefault();
				
				var providerConnectId;
				var connections = api.getParam('connections');
				if ( connections.length == 0 ) {
					// @todo
				} else {
					providerConnectId = connections[0]['id'];
				}
				var request = {
					action: 'postIntegratorOrder',
					postOrderID: api.getParam('post_id'),
					shippingOrderItemId: api.getShippingOrderItemId(),
					providerConnectId: providerConnectId,
					beforeCallback: beforeCB,
					doneCallback: doneCB,
					alwaysCallback: alwaysCB,
				}
				request = api.getTransmittingFields(request);
				api.ajax(request);		
			});			
		},
		setValidateOrdersAction: function(){
			/**
			 * Validate orders.
			 */	
			var validateBeforeCB = (request) => {
				if ( 'undefined' === typeof request ) {
					return;
				}	
				api.orderViewer.clear().close();
			}
			var validateDoneCB = (response) => {
				if ( 'undefined' === typeof response ) {
					return;
				}
				try {
					var body = JSON.parse(response.data.response.body);
					api.setValidationReport(body);
				} catch (error) {
					console.error(error);
					api.orderViewer.add('Ошибка валидации: ' + error).open();
					return;
				}
			}
			$('.validate-orders').on('click', function(evnt){
				evnt.preventDefault();	
				var request = {
					action: 'validateOrder',
					postOrderID: api.getParam('post_id'),
					beforeCallback: validateBeforeCB,
					doneCallback: validateDoneCB
				}
				request = api.getTransmittingFields(request);
				api.ajax(request);		
			});			
		},
		getTransmittingFields: function(request) {
			$(api.getParam('transmittingFieldSelector')).each(function(i,e){
				var $elem = $(e), val = $elem.val();
				if ( '' == val.trim() ) {
					if ( 'undefined' !== typeof $elem.data('init-value') ) {
						val = $elem.data('init-value');
					}
				}
				request[$elem.data('request-id')] = val;
			});
			
			// Get deliveryCost.
			var tariff = JSON.parse( api.getShippingMethodMeta('tariff','value') );
			request['deliveryCost'] = tariff['deliveryCost'];
			
			return request;
		},
		setViewOrdersAction: function(){
			/**
			 * View orders.
			 */
			var viewDoneCB = (response) => {
				if ( 'undefined' === typeof response ) {
					return;
				}
				try {
					var body = JSON.parse(response.data.response.body);
				} catch {
					api.orderViewer.add('View order: ' + api.__('parsingError')).open();
					return;
				}
				api.orderViewer.add('Информация по заказу:'+"\n"+api.getOrderReport(body)).open();
			}				
			
			$('.view-orders').on('click', function(evnt){
				evnt.preventDefault();	
				var request = {};
				request.action 			 	  = 'getIntegratorOrder';
				request.postOrderID  	  = api.getParam('post_id');
				request.integratorOrder = WPApiShipAdmin.data.shippingMethodMeta['integratorOrder']['value'];
				request.doneCallback 	  = viewDoneCB;
				api.ajax(request);		
			});			
		},
		setStatusOrdersAction: function(){
			/**
			 * Status orders.
			 */
			var statusDoneCB = (response) => {
				if ( 'undefined' === typeof response ) {
					return;
				}					
				try {
					var body = JSON.parse(response.data.response.body);
				} catch {
					api.orderViewer.add('Set status order: ' + api.__('parsingError')).open();
					return;
				}
				api.orderViewer.add('Статус заказа:'+"\n"+api.getOrderReport(body)).open();
			}				 
			$('.status-orders').on('click', function(evnt){
				evnt.preventDefault();	
				var request = {};
				request.action = 'getOrderStatus';
				request.postOrderID  = api.getParam('post_id');
				request.integratorOrder = WPApiShipAdmin.data.shippingMethodMeta['integratorOrder']['value'];
				request.doneCallback 	 = statusDoneCB;
				api.ajax(request);		
			});			
		},
		setToolsOrdersAction: function(){
			/**
			 * Tools for order.
			 */
			var parsedResponse = null;
			
			var orderView = response => {
				
				try {
					var body = JSON.parse(response.data.response.body);
				} catch {
					api.orderViewer.add('Order tool: ' + api.__('parsingError')).open();					
					return;
				}	

				var iOrder = api.getShippingMethodMeta(
					'integratorOrder', 
					'value', 
					api.getParam('INTEGRATOR_ORDER_INIT_VALUE')
				);
		
				var tools = 'order_item_id :: '+api.getShippingOrderItemId()+"\n";
				tools += api.__('orderStatus')+"\n";
				tools += 'http://api.dev.apiship.ru/v1/orders/status?clientNumber='+WPApiShipAdmin.getParam('post_id')+"\n";

				if ( iOrder * 1 > 0 ) {
					
					tools += api.__('orderInfo')+"\n";
					tools += 'http://api.dev.apiship.ru/v1/orders/'+iOrder+"\n";
					tools += api.__('orderCancel')+"\n";
					tools += 'http://api.dev.apiship.ru/v1/orders/'+iOrder+'/cancel'+"\n";
					tools += api.__('orderDelete')+"\n";
					tools += 'http://api.dev.apiship.ru/v1/orders/'+iOrder+"\n";
				
				} else {

					if ( 'undefined' !== typeof body.orderInfo && 'undefined' !== typeof body.orderInfo['orderId'] ) {

						if ( 'string' === typeof parsedResponse.errors['clientNumber'] ) {
							tools += "\n" + parsedResponse.errors['clientNumber']+"\n";
						}
						tools += api.__('orderCancel')+"\n";
						tools += 'http://api.dev.apiship.ru/v1/orders/'+body.orderInfo['orderId']+'/cancel'+"\n";
						tools += api.__('orderDelete')+"\n";
						tools += 'http://api.dev.apiship.ru/v1/orders/'+body.orderInfo['orderId']+"\n";		
					}
				}
				api.orderViewer.add('Tools:'+"\n"+tools).open();
			}
			 
			var doneCB = function(response) {
				if ( 'undefined' === typeof response ) {
					return;
				}
				if ( response.success ) {
					orderView(response);
				}
			}
			
			var validateDoneCB = (response) => {
				if ( 'undefined' === typeof response ) {
					return;
				}
				
				try {
					var body = JSON.parse(response.data.response.body);
				} catch {
					api.orderViewer.add('Validate done callback: ' + api.__('parsingError')).open();
					return;
				}

				parsedResponse = api.parseResponse(body);
				
				if ( 'string' === typeof parsedResponse.errors['clientNumber'] ) {
					var request = {
						action: 'getOrderStatusByClientNumber',
						postOrderID: api.getParam('post_id'),
						doneCallback: doneCB,
					}
					api.ajax(request);
				} else {
					orderView(response);
				}					
			}
			 
			$('.tools-orders').on('click', function(evnt){
				evnt.preventDefault();
				var request = {
					action: 'validateOrder',
					postOrderID: api.getParam('post_id'),
					doneCallback: validateDoneCB,
				}
				api.ajax(request);	
			});				
		},
		parseResponse: function(response){
			var parsed = {
				errors: {}
			};
			if ( 'object' === typeof response.errors && response.errors.length > 0 ) {
				response.errors.map(
						function(error){
							parsed.errors[error.field] = error.message;
						} 
					);	
			}
			return parsed;
		},
		setOrderActions: function(){
			api.buttonAction('.wpapiship-delete-order', 'deleteIntegratorOrder', 'удалён');
			api.buttonAction('.wpapiship-cancel-order', 'cancelIntegratorOrder', 'отменён');			
		},
		buttonAction: function(elemClass, endpoint, textStatus = 'отменён'){
			
			var beforeCB = (request) => {
				if ( 'undefined' === typeof request ) {
					return;
				}				
			}
			
			var doneCB = (response) => {
				if ( 'undefined' === typeof response ) {
					return;
				}

				var body = JSON.parse(response.data.response.body);

				api.orderViewer.open();
				if ( response.success ) {
					api.orderViewer.add('Заказ #' + response.data.request.integratorOrder + ' ' + textStatus);
					setTimeout(function(){
						location.reload();
					}, 1000);
				} else {
					api.orderViewer.add('При совершении действия произошла ошибка: ' + body.message);
				}			
			}
			var confirmationClass = elemClass + '-confirmation';
			var confirmation = {
				_self: $('#wpapiship-order-metabox ' + confirmationClass + '.wpapiship-action-confirmation'),
				_noElement: $('#wpapiship-order-metabox ' + confirmationClass + ' .confirmation-button.no'),
				_yesElement: $('#wpapiship-order-metabox ' + confirmationClass + ' .confirmation-button.yes'),
				hiddenClass: 'hidden',
				inited: null,
				open() {
					api.hideAllConfirmations();
					this._self.removeClass(this.hiddenClass);
					return this;
				},
				isOpened() {
					return !this.isClosed();
				},
				close() {
					this._self.addClass(this.hiddenClass);
					return this;
				},
				isClosed() {
					if ( this._self.hasClass(this.hiddenClass) ) {
						return true;
					}
					return false;
				},
				toggle() {
					if ( this.isClosed() ) {
						this.open();
					} else {
						this.close();
					}
					return this;					
				},
				init() {
					if ( this.inited ) {
						return true;
					}
					if ( this._self.length != 1 ) {
						return false;
					}
					this._noElement.on('click', (evnt) => {
						evnt.preventDefault();
						this.close();
					});
					this._yesElement.on('click', (evnt) => {
						evnt.preventDefault();
						var request = {
							action: endpoint,
							postOrderID: api.getParam('post_id'),
							integratorOrder: api.getShippingMethodMeta(
								'integratorOrder', 
								'value'
							),
							shippingOrderItemId: api.getShippingOrderItemId(),
							beforeCallback: beforeCB,
							doneCallback: doneCB,
						}	
						api.ajax(request);						
						this.close();
					});					
					this.inited = true;
					return true;
				}
			}
			
			if ( ! confirmation.init() ) {
				return;
			}

			$(elemClass).on('click', function(evnt){
				console.log(this);
				evnt.preventDefault();
				confirmation.toggle();
				if ( confirmation.isOpened() ) {
					setTimeout(function(){
						confirmation.close();
					}, 10000);
				}
			});	
		},
		hideAllConfirmations: function(){
			$('#wpapiship-order-metabox .wpapiship-action-confirmation').each(function(index, elem){
				if (!$(elem).hasClass('hidden')) {
					$(elem).addClass('hidden');
				}
			});
		},
			
		// deprecated (set connections count etc.)
		setProviderData: function(){
			var providerKey = api.getShippingMethodMeta('tariffProviderKey', 'value');
			if ( 'undefined' === typeof providerKey ) {
				return;
			}
			
			var doneCB = (response) => {
				if ( 'undefined' === typeof response ) {
					return;
				}					
				try {
					var body = JSON.parse(response.data.response.body);
				} catch {
					api.orderViewer.add('Error: ' + api.__('parsingError')).open();
					return;
				}
	
				var $wrapper = $(api.getParam('providerCardsSelector'));
				var card = api.getParam('providerCardHtml');
				card = card.replace('{{providerKey}}', providerKey);
				card = card.replace('{{providerClass}}', 'provider-'+providerKey);
				card = card.replace(
					'{{logoURL}}', 
					api.getParam('providerIconURL')+providerKey+'.svg'
				);
				card = card.replace('{{classes}}', '');
			
				var $logo = $('.provider-'+providerKey+' .logo');
				$logo.removeClass('image-placeholder');
				
				// $('.provider-'+providerKey+' .card--name').text(provider.name);			

				// Set Data.
				api.setData('providerKey', providerKey);
				api.setData('connections', body.rows);
			};
			var request = {
				action: 'getConnections',
				doneCallback: doneCB,
				providerKey: providerKey,
			}
			api.ajax(request);					
		},
		pointInSelectActions: function(evnt, select) {
			evnt = evnt || false;
			if ( ! evnt ) {
				return;
			}
			
			var request = {};

			if ( evnt.target.value === 'load-points' ) {
				
				request.action 			= 'getListsPoints';
				request.optionName  = select.attr('name');
				request.availableOperation  = '[1,3]'; // 1-receiving, 3-receiving and issuing a shipment.
		
			} else if ( evnt.target.value * 1 > 0 ) {
				
				var sel = evnt.target;
				var value = sel.value;
				var pointInAddress = sel.options[sel.selectedIndex].text;
				var pointInId = evnt.target.value;
				$( api.getMetaboxField('pointInId','selector') ).val(pointInId);
				$( api.getMetaboxField('pointInAddress','selector') ).val(pointInAddress);
				
				var request = {
					action: 'updatePointInData',
					postOrderID: api.getParam('post_id'),
					data: {id:pointInId, address:pointInAddress}
				}
				api.ajax(request);	
				return;

			} else {
				return;
			}
			
			var newAddressField = $('.meta-value-point-in-new-address');
			
			var doneCB = response => {
				if (  'undefined' === typeof response ) {
					return;
				}

				if ( response.success ) {
					var initialRequest = response.data.request;
					try {
						var html = JSON.parse(response.data.response.customHtml);
					} catch { 
						console.log(initialRequest.action+': '+ 'error parsing response body.');
						return 
					}

					if ( 'undefined' !== typeof html ) {
						newAddressField.find('select[name="point-in-id-'+request.pointType+'"]').detach();
						newAddressField.find('.point-in-select-wrapper.'+request.pointType).append(html);
					}
				}					
			}

			request.doneCallback = doneCB;
			request.providerKey  = api.getParam('providerKey');
			request.pointType 	 = select.data('type');
			request.optionType 	 = select.data('type');
			api.ajax(request);
		},
		attachOrdersListeners: function(){
			$('#wpapiship-order-metabox .label-download').on('click', function(evnt){
				var btn = $(this);
				btn.attr('disabled','disabled');
				setTimeout(()=>{
					// btn.removeAttr('disabled');
					btn.prop('disabled',false);
				}, 1500);
				// iframe-label-download
				// console.log( btn.data('url') );
				// $('#iframe-label-download').attr('src', btn.data('url'));
				// window.open(btn.data('url'), 'Download');
				window.open( btn.data('url') );
				// location = btn.data('url');
				
				/*
				var downloadUrl = btn.data('url');
				var downloadFrame = document.createElement("iframe"); 
				downloadFrame.setAttribute('src',downloadUrl);
				downloadFrame.setAttribute('class',"screenReaderText"); 
				document.body.appendChild(downloadFrame); 
				// */
				
			});
			
			// Edit data in ApiShip metabox.
			$('#wpapiship-order-metabox .edit-data').on('click', function(evnt){
				var $t = $(this);
				if ( $t.hasClass('edit-point-in') ) {
					$('.meta-key-point-in-new-address').toggleClass('hidden');
					$('.meta-value-point-in-new-address').toggleClass('hidden');
				} else if ( $t.hasClass('edit-dimensions') ) {
					$('.wpapiship-editable-field').each(function(i,e){
						var elm = $(e);
						if ( elm.hasClass('disabled') ) {
							// elm.removeClass('disabled').removeAttr('disabled');
							elm.removeClass('disabled').prop('disabled',false);
						} else {
							elm.addClass('disabled').attr('disabled','disabled');
						}
					});
				}
			});	

			// Delete data in ApiShip metabox.
			$('#wpapiship-order-metabox .delete-data').on('click', function(evnt){
				var $t = $(this);
				var action = false;
				if ( $t.hasClass('delete-point-in') ) {
					$( api.getMetaboxField('pointInId','selector') ).val('');
					$( api.getMetaboxField('pointInAddress','selector') ).val('');
					$('.meta-key-point-in-new-address').addClass('hidden');
					$('.meta-value-point-in-new-address').addClass('hidden');					
					action = 'deletePointInData';
				} else if ( $t.hasClass('delete-point-out') ) {
					$( api.getMetaboxField('pointOutId','selector') ).val('');
					$( api.getMetaboxField('pointOutAddress','selector') ).val('');
					action = 'deletePointOutData';
				}

				if ( action ) {
					var request = {
						action: action,
						postOrderID: api.getParam('post_id'),
					}
					api.ajax(request);	
				}
			});
			
			// Save contact name.
			$('#wpapiship-order-metabox #wpapiship-contact-name').on('change', function(evnt){
				var field = $(this);
				var request = {
					action: 'saveOrderContactName',
					postOrderID: api.getParam('post_id'),
					field: {
						value: field.val(),
						id: field.attr('id'),
					}
				}
				api.ajax(request);		
			});
			
			// Save phone.
			$('#wpapiship-order-metabox #wpapiship-phone').on('change', function(evnt){
				var field = $(this);
				var request = {
					action: 'saveOrderPhone',
					postOrderID: api.getParam('post_id'),
					field: {
						value: field.val(),
						id: field.attr('id'),
					}
				}
				api.ajax(request);		
			});
			
			// Save custom dimensions.
			$('#wpapiship-order-metabox .wpapiship-editable-field').on('change', function(evnt){
				var field = $(this);
				var val = field.val();
				var initVal = field.data('init-value');
				val = val * 1;
				if ( 'number' === typeof val ) {
					if ( val > 0  ) {
						// ok.
					} else {
						val = initVal;
						// Reset the value so that the placeholder takes effect.
						field.val('');
					}

					var request = {
						action: 'saveOrderCustomPlaces',
						postOrderID: api.getParam('post_id'),
						field: {
							value: val,
							initValue: initVal,
							placeOrder: field.data('place-order'),
							dimension: field.data('dimension'),
						}
					}
					api.ajax(request);						
				}
			});	
	
			// Redirect to Providers section on settings page.
			$('#wpapiship-order-metabox .card--logo').on('click', function(evnt){
				location = api.getParam('providersSectionUrl');
			});
			
			// Debug.
			$('.wpapiship-debug').on('dblclick', function(evnt){
				if ( api.getParam('pagenow') === 'post.php' ) {
					var $t = $(this);
					if ( $t.hasClass('wpapiship-open-meta') ) {
						$('.edit').css({'display':''});
					}
				}
			});
			$('.provider-card .card--key').on('dblclick', function(evnt){
				if ( api.getParam('pagenow') === 'post.php' ) {
					$('#order_shipping_line_items').css({'display':'contents'});
					$('.field-to-control').removeClass('hidden');
				}
			});
			
			// Select new point in.
			$(document).on('change', '.meta-value-point-in-new-address .point-select', function(evnt){
				api.pointInSelectActions(evnt, $(this));
			});	
			
			// Custom event `selectPointOut` selecting new point out.
			$(document).on('selectPointOut', function(evnt, elem){

				var id = elem.data('id'),
						address = elem.data('address');
						
				var action = '';
		
				if ( 'undefined' !== typeof id ) {
					$( api.getMetaboxField('pointOutId','selector') ).val(id);
					action = 'updatePointOut';
				}
				if ( 'undefined' !== typeof address ) {
					$( api.getMetaboxField('pointOutAddress','selector') ).val(address);
					action += 'Data';
				}
				
				if ( action === 'updatePointOutData' ) {
					var request = {
						action: action,
						postOrderID: api.getParam('post_id'),
						data: {id:id, address:address}
					}
					api.ajax(request);	
				}				
				
			});
		},
		initSettingsPage: function(){
			setTimeout(function(){
				api.initTabs();
			}, 500);
		},
		initTabs: function(){
			if ( api.isCurrentTab( api.getParam('pluginTab') ) ) {
				api.initPluginSections();
				api.attachListeners();
			} else if ( api.isCurrentTab( api.getParam('shippingTab') ) ) {
				api.initShippingSections();
				api.attachListeners();
			}
		},
		initShippingSections: function(){
			var section = api.getParam('section');
			if ( 'apiship' == section ) {
				api.initApishipSection();
			}			
		},
		initPluginSections: function(){
			var section = api.getParam('section');
			if ( 'general' == section ) {
				api.initGeneralSection();
			} else if ( 'providers' == section ) {
				api.initProvidersSection();
			} else if ( 'docs' == section ) {
				api.initDocsSection();
			} else if ( 'debug' == section ) {
				api.initDebugSection();
			} else if ( 'calculator' == section ) {
				api.initCalculatorSection();
			}
		},
		initDocsSection: function(){
			api.setSubmitButton('docs', 'hide');			
		},
		initApishipSection: function(){
			$('#wp_apiship_length, #wp_apiship_width, #wp_apiship_height, #wp_apiship_weight').on('change', function(evnt){
				var $t = $(this);
				if ( $t.val() < 0 ) {
					$t.val(0)
				}
			});
		},
		initCalculatorSection: function(){
			api.setSubmitButton('debug', 'hide');
		},
		initDebugSection: function(){
			api.setSubmitButton('debug', 'hide');
		},
		providerCard: {
			wrapperMessage: null,
			wrapperSelected: null,
			wrapperNotSelected: null,
			cardHtml: null,
			inited: null,
			add(provider) {
				this.init();
				this.append(provider);
				return this;
			},
			errorMessage(message) {
				message = message || false;
				if ( ! message ) {
					return;
				}
				this.addMessage(api.__('Error')+': ' + message, 'error');
				return this;
			},
			addMessage(message, type) {
				type = type || 'normal';
				this.init();
				this.wrapperMessage.append('<div class="wpapiship-notice notice-'+type+'">'+message+'</div>');
				this.wrapperMessage.removeClass('hidden');
				this.wrapperMessage.parents('tr').removeClass('hidden').css({'display':''});
				return this
			},
			append(provider) {
				if ( provider.selected ) {
					this.wrapperSelected.append( this._getNewCard(provider) );
					this._setSelectedCardData(provider);
				} else {
					this.wrapperNotSelected.append( this._getNewCard(provider) );
				}
				this._setCardFooter(provider);
				this._removePlaceholder(provider);
				return this;
			},
			_getNewCard(provider) {
				var selectedClass = provider.selected ? ' '+api.getParam('selectedProviderCardClass') : '';
				var card = this.cardHtml;
				card = card.replace('{{providerKey}}', provider.key);
				card = card.replace(
					'{{providerClass}}',
					'provider-' + provider.key + selectedClass
				);
				
				var _logoURL = this._getLogoURL(provider.key);
				card = card.replace('{{logoURL}}', _logoURL);

				if ( this.noImageProviders.includes(provider.key) ) {
					card = card.replace('{{classes}}', 'no-image');
				} else {
					card = card.replace('{{classes}}', '');
				}
				
				if ( provider.selected ) {
					card = card.replace('{{selected-hidden}}','');
					card = card.replace('{{not-selected-hidden}}','hidden');
				} else {
					card = card.replace('{{selected-hidden}}','hidden');
					card = card.replace('{{not-selected-hidden}}','');
				}
				
				let pickupTypes = provider.data.pickup_types;

				let pickuptype1 = '';
				let pickuptype2 = '';

				if ($.inArray('1', pickupTypes) != -1) {
					pickuptype1 = 'checked';
				}
				if ($.inArray('2', pickupTypes) != -1) {
					pickuptype2 = 'checked';
				}

				card = card.replace('{{pickuptype1}}', pickuptype1);
				card = card.replace('{{pickuptype2}}', pickuptype2);

				return card;
			},
			_setCardFooter(provider) {
				$('.provider-'+provider.key+' .card--name').text(api.getParam('nameTitle') + ': '+provider.name);	
				if ( provider.selected ) {
					$('.provider-'+provider.key+' .card--point-in-id').removeClass('hidden');	
				}
				if ($.inArray('2', provider.data.pickup_types) != -1) {
					$('.provider-'+provider.key+' .pickup-point').removeClass('hidden');	
				}
			},
			_removePlaceholder(provider) {
				$('.provider-'+provider.key+' .logo').removeClass('image-placeholder');			
			},
			_getLogoURL(providerKey) {
				var logoURL = api.getParam('providerIconURL')+providerKey+'.svg';
				if ( this.noImageProviders.includes(providerKey) ) {
					logoURL = api.getParam('imageURL')+api.getParam('providerPlaceholder');
				}
				return logoURL;
			},			
			_setCardData($card) {
				
				if ( $card.hasClass( api.getParam('providerCardProcessedClass') ) ) {
					// Card already processed.
					return;
				}

				var providerKey = $card.data('provider-key');
				if ( 'undefined' === typeof providerKey && '' === providerKey ) {
					return;
				}				
				var tariffsDoneCB = response => {
					if ( 'undefined' === typeof response ) {
						return;
					}
					if ( response.success ) {
						try {
							// var body = JSON.parse(response.data.response.body);
							api.orderViewer.add('getTariffs callback: ' + api.__('parsingError')).open();
						} catch {
							api.orderViewer.add('getTariffs callback: ' + api.__('parsingError')).open();
							return;
						}
					}
				}

				var connectDoneCB = response => {
					if ( 'undefined' === typeof response ) {
						return;
					}
					if ( response.success ) {
						try {
							// var body = JSON.parse(response.data.response.body);
							api.orderViewer.add('getProviderConnections callback: ' + api.__('parsingError')).open();
						} catch {
							api.orderViewer.add('getProviderConnections callback: ' + api.__('parsingError')).open();
							return;
						}
					}
				}
			
				// Get tariffs.
				var tariffsRequest = {
					action: 'getTariffs',
					providerKey: providerKey,
					doneCallback: tariffsDoneCB,
				};
				api.ajax(tariffsRequest);
				
				// Get connections.
				var connectRequest = {
					action: 'getProviderConnections',
					providerKey: providerKey,
					doneCallback: connectDoneCB,
				};
				api.ajax(connectRequest);
		
				var cardSelectHtmlDoneCB = response => {
					if ( 'undefined' === typeof response ) {
						return;
					}
					if ( response.success ) {
						$.each(api.getParam('ownerPointTypes'), function(i,pointType){
							if ( response.data.response[pointType]['html'] ) {
								$card.find('select[name="point-in-id-'+pointType+'"]').detach();
								$card.find(api.getParam('pointInSelectWrapperSelector')+'.'+pointType).append(response.data.response[pointType]['html']);
							}
						});
					}					
				}
	
				// Get html for select elements.	
				var request = {
					action: 'getCardSelectHtml',
					providerKey: providerKey,
					doneCallback: cardSelectHtmlDoneCB,
				};
				api.ajax(request);
				
				// Mark card as processed.
				$card.addClass( api.getParam('providerCardProcessedClass') ); 
			},
			_setSelectedCardData(provider) {
				api.providerCard._setCardData( $( api.getParam('providerCardSelector') + '.provider-'+provider.key) );
			},
			_isCardSelected(card) {
				if ( card.hasClass( api.getParam('selectedProviderCardClass') ) ) {
					return true;
				}
				return false;
			},
			_selectActions(evnt, select) {
				evnt = evnt || false;
				if ( ! evnt ) {
					return;
				}				
				if ( evnt.target.value === 'load-points' ) {
					this._getListsPoints(evnt, select);
				} else if ( evnt.target.value === 'reset-point' ) {
					this._resetSelectedPointIn(evnt, select);
				} else if ( evnt.target.value * 1 > 0 ) {
					this._saveSelectedPointIn(evnt, select);
				}
			},
			_resetSelectedPointIn(evnt, select) {

				var card = select.parents(api.getParam('providerCardSelector'));
		
				var doneCB = response => {
					if (  'undefined' === typeof response ) {
						return;
					}
					if ( response.success ) {

						var request = response.data.request;

						try {
							var html = JSON.parse( response.data.response[request.pointType]['html'] );
						} catch { 
							api.providerCard.errorMessage('resetSelectedPoint Parsing success response body.');
							return 
						}

						if ( 'undefined' !== typeof html ) {
							card.find('select[name="point-in-id-'+request.pointType+'"]').detach();
							// card.find('.point-select-wrapper.'+request.pointType).append( html );
							card.find(api.getParam('pointInSelectWrapperSelector')+'.'+request.pointType).append( html );
						}
					}
				}				
				
				var request = {
					// action: 'removeSelectedPoint',
					action: 'deleteSelectedPointIn',
					providerKey: card.data('provider-key'),
					pointType: select.data('type'),
					doneCallback: doneCB,
				}
				api.ajax(request);					
			},
			_saveSelectedPointIn(evnt, select) {
				var card = select.parents(api.getParam('providerCardSelector'));
				var sel = evnt.target;
				var value = sel.value;
				var address = sel.options[sel.selectedIndex].text;
				var request = {
					action: 'saveSelectedPointIn',
					providerKey: card.data('provider-key'),
					pointId: value,
					pointAddress: address,
					pointType: select.data('type'),
				}
				api.ajax(request);					
			},
			_getListsPoints(evnt, select) {
				
				var card = select.parents(api.getParam('providerCardSelector'));
				
				var doneCB = response => {
					if (  'undefined' === typeof response ) {
						return;
					}

					if ( response.success ) {
						try {
							var html = JSON.parse(response.data.response.customHtml);
						} catch { 
							api.providerCard.errorMessage('getListsPoints: Parsing success response body.');
							return 
						}

						var request = response.data.request;
						if ( 'undefined' !== typeof html ) {
							card.find('select[name="point-in-id-'+request.optionType+'"]').detach();
							// card.find('.point-select-wrapper.'+request.optionType).append(html);
							card.find(api.getParam('pointInSelectWrapperSelector')+'.'+request.optionType).append(html);
						}
					}					
				}

				var request = {
					action: 'getListsPoints',
					providerKey: card.data('provider-key'),
					optionName: select.attr('name'),
					optionType: select.data('type'),
					availableOperation: '[1,3]', // 1-receiving, 3-receiving and issuing a shipment.
					doneCallback: doneCB,
				}
				api.ajax(request);							
			},
			_selectPickup(label) {
				label = label || false;
				if ( ! label ) {
					return;
				}
				var card = label.parents(api.getParam('providerCardSelector'));

				var selectedTypes = [];

				$(card).find('.provider-card-pickup-select').each(function(index, element){
					var val = $(element).val();
					if ($(element).prop('checked') === true) {
						selectedTypes.push(val);
						if (val == '2') {
							$(card).find('.pickup-point').removeClass('hidden');
						}
					} else if (val == '2') {
						$(card).find('.pickup-point').addClass('hidden');
					}
				});
				
				var doneCB = (response) => {
					if (  'undefined' === typeof response ) {
						// console.log('err');
						return;
					}
					console.log('success');
				}
				
				var request = {
					action: 'saveSelectedPickupTypes',
					selectedTypes: selectedTypes,
					providerKey: card.data('provider-key'),
					doneCallback: doneCB
				}
				api.ajax(request);	
			},
			init() {
				if ( null !== this.inited ) {
					return;
				}
				this.wrapperMessage 		 = $(api.getParam('providerMessageSelector'));
				this.wrapperSelected 	   = $(api.getParam('selectedProviderCardsSelector'));
				this.wrapperNotSelected  = $(api.getParam('notSelectedProviderCardsSelector'));
				this.cardHtml 				   = api.getParam('providerCardHtml');
				this.noImageProviders 	 = api.getParam('noImageProviders');
				this.imageURL 				   = api.getParam('imageURL');
				this.providerPlaceholder = api.getParam('providerPlaceholder');

				setTimeout( function(){ 
					$( api.getParam('providerCardSelector') ).on('click', function(evnt){
						api.providerCard._setCardData($(this));
					});
					$( api.getParam('providerCardPickupSelect') ).on('change', function(evnt){
						api.providerCard._selectPickup($(this));
					});
					$(document).on('change', '.provider-card .point-select', function(evnt){
						api.providerCard._selectActions(evnt, $(this));
					});						
				}, 1000);
				
				this.inited = true;
			}
		},	
		initProvidersSection: function() {

			api.setSubmitButton('connections', 'hide');
				
			// Get Providers | getProviders.
			var promise = $.when();
			promise.then(function(){
				var request = {
					action:'getProviders'
				}
				return api.ajax(request);
			},function(){
				/* error in promise */
				/* return $.ajax(); */
			}).then(function(response){
				if ( response.success ) {
					try {
						var body = JSON.parse(response.data.response.body);
					} catch { 
						api.providerCard.errorMessage('Parsing success response body.');
						return;
					}
				} else {
					try {
						var body = JSON.parse(response.data.response.body);
						api.providerCard.errorMessage(body.message);
					} catch { 
						api.providerCard.errorMessage('Parsing error response body.');
					}					
					return;
				}

				$(api.getParam('providersFormSelector')).removeClass('hidden');
				// @todo Check out correct loading of providerCardHtml param,
				// WPApiShipAdmin.getParam('providerCardHtml')
				body.rows.map(
					function(provider){
						// if ( provider.selected )
						api.providerCard.add(provider);
					} 
				);					
			});
		},
		initGeneralSection: function() {
			
			$('.options-field-disabled').prop('disabled','disabled');
			
			// Remove unneeded elements.
			$('.wp-apiship-remove-self').remove();
			
			// Check out for negative values.
			$('#wp_apiship_length, #wp_apiship_width, #wp_apiship_height, #wp_apiship_weight').on('change', function(evnt){
				var $t = $(this);
				if ( $t.val() < 0 ) {
					$t.val(0)
				}
			});			

			$(document).ready(function(){
				if ($('.wp-apiship-mapping-container').length !== 0) {
					let callback = function(){
						$('.wp-apiship-mapping-row').each(function(index){
							let isActive = $(this).find('.wp-apiship-status-active').prop('checked');
							$(this).find('.wp-apiship-mapping-config-col').each(function(index){
								if (isActive === true) {
									$(this).removeClass('wp-apiship-mapping-config-disabled');
								} else {
									$(this).addClass('wp-apiship-mapping-config-disabled');
								}
							});
						});
					};
					$('.wp-apiship-status-active').on('change', function(){
						callback();
					});
					callback();
				}
			});
		},
		ajaxBeforeSend: function(request){
			if ( typeof request.beforeCallback === 'function' ) {
				request.beforeCallback(request);
			}			
		},
		ajax: function(request){
			return $.ajax({
				beforeSend:function(){
					if ( typeof api.ajaxBeforeSend === 'function' ) {
						api.ajaxBeforeSend(request);
					}
				},
				type: 'POST',
				url: api.getAjaxUrl(),
				data: {
					action:api.getProcessAjax(), 
					request:request
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
		attachListeners: function() {
			$('#get-providers').on('click',function(evnt){
				api.getProviders();
			});
		},
		getCalculation: function() {
			var beforeCB = () => {
				$('#calculate-response').val('');
			}
			var doneCB = response => {

				if (  'undefined' === typeof response ) {
					return;
				}
		
				if ( ! response.success ) {	
					return;	
				}
				
				try {
					var calculations = JSON.parse(response.data.response.body);
				} catch {
					$('#calculate-response').val('Incorrect response');
					return;
				}
		
				var list = '';	
				if ( 'undefined' !== typeof calculations.deliveryToDoor ) {
					list += 'deliveryToDoor: '+"\n";	
					calculations.deliveryToDoor.map(function(elem){
						list += elem.providerKey + ' - ' + elem.tariffs.length + ' tariffs' + "\n";
					});
				}
				
				list += "------\n";
				if ( 'undefined' !== typeof calculations.deliveryToPoint ) {
					list += 'deliveryToPoint: '+"\n";	
					calculations.deliveryToPoint.map(function(elem){
						list += elem.providerKey + ' - ' + elem.tariffs.length + ' tariffs' + "\n";
					});
				}				
				$('#calculate-response').val(list);
			}			
			var request = {}; 		
			request.action = 'getCalculation';
			request.doneCallback = doneCB;
			request.beforeCallback = beforeCB;
			api.ajax(request);
		},
		setValidationReport: function(body, successText = 'Проверка пройдена успешно') {
			let validationResult = successText;
			if ('undefined' !== typeof body.errors && body.errors.length > 0) {
				validationResult = api.getErrorsReport(body);
			}
			api.orderViewer.add(validationResult).open();
		},
		getErrorsReport: function(body, errorText = 'При проверке выявлены следующие ошибки:') {
			if (!body.errors) {
				if ('undefined' !== typeof body.message) {
					return errorText + "\n" + body.message + "\n" + body.description;
				}
				return errorText + "\nНеизвестная ошибка";
			}
			errorText = errorText + "\n\n";
			body.errors.forEach(function(currentValue, index, arr){
				errorText = errorText + 'Поле ' + currentValue.field + ' - ' + currentValue.message + '\n';
			});
			return errorText;
		},
		getProviders: function() {
			var doneCB = (response) => {
				if (  'undefined' === typeof response ) {
					return;
				}
				var providers = JSON.parse(response.data.response.body);
				var list = '';	
				$.each(providers.rows, function(i,e){
					list = list + e.name + ' | ' +  e.key;
					if ( e.description !== null ) {
						list = list + ' | ' + e.description;
					}
					list += "\n";
				});
				$('#providers').val(list);
			}
			var request = {};
			request.action = 'getProviders';
			request.data = 'Some data';
			request.doneCallback = doneCB;
			api.ajax(request);			
		},
		start: function(){
			api.initSettingsPage();
			api.initOrderPage();
		}
	}
	
	WPApiShipAdmin = $.extend({}, WPApiShipAdmin, api);
	WPApiShipAdmin.start();
	
})(jQuery);