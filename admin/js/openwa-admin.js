(function($) {
	'use strict';

	var openwa = {
		init: function() {
			this.bindEvents();
			this.loadSessionInfo();
		},

		bindEvents: function() {
			$( '.openwa-toggle-key' ).on( 'click', this.toggleApiKey );
			$( '.openwa-test-connection' ).on( 'click', this.testConnection );
			$( '.openwa-send-test' ).on( 'click', this.sendTestMessage );
			$( '.openwa-create-session' ).on( 'click', this.createSession );
			$( '.openwa-start-session' ).on( 'click', this.startSession );
			$( '.openwa-stop-session' ).on( 'click', this.stopSession );
			$( '.openwa-delete-session' ).on( 'click', this.deleteSession );
			$( '.openwa-refresh-session' ).on( 'click', this.refreshSessionInfo );
			$( '.openwa-send-order-msg' ).on( 'click', this.sendOrderMessage );
			$( '.openwa-insert-sc' ).on( 'click', this.insertShortcode );
			$( '#openwa-toggle-all' ).on( 'change', this.toggleAllTemplates );
		},

		toggleApiKey: function(e) {
			e.preventDefault();
			var $input = $(this).prev( 'input' );
			if ( $input.attr( 'type' ) === 'password' ) {
				$input.attr( 'type', 'text' );
				$(this).text( openwaAdmin.i18n.sending );
			} else {
				$input.attr( 'type', 'password' );
				$(this).text( 'Show' );
			}
		},

		testConnection: function() {
			var $btn = $(this);
			var $status = $( '.openwa-connection-status' );

			$btn.prop( 'disabled', true );
			$status.text( openwaAdmin.i18n.connecting ).css( 'color', '#666' );

			$.post( openwaAdmin.ajaxUrl, {
				action: 'openwa_test_connection',
				nonce: openwaAdmin.nonce
			}, function( response ) {
				if ( response.success ) {
					$status.text( openwaAdmin.i18n.connected ).css( 'color', '#46b450' );
				} else {
					$status.text( response.data.message ).css( 'color', '#dc3232' );
				}
			} ).fail( function() {
				$status.text( openwaAdmin.i18n.failed ).css( 'color', '#dc3232' );
			} ).always( function() {
				$btn.prop( 'disabled', false );
			} );
		},

		sendTestMessage: function() {
			var $btn = $(this);
			var $result = $( '#openwa-test-result' );
			var phone = $( '#openwa-test-phone' ).val();
			var message = $( '#openwa-test-message' ).val();

			if ( ! phone || ! message ) {
				$result.text( 'Phone and message are required.' ).css( 'color', '#dc3232' );
				return;
			}

			$btn.prop( 'disabled', true );
			$result.text( openwaAdmin.i18n.sending ).css( 'color', '#666' );

			$.post( openwaAdmin.ajaxUrl, {
				action: 'openwa_send_test_message',
				nonce: openwaAdmin.nonce,
				phone: phone,
				message: message
			}, function( response ) {
				if ( response.success ) {
					$result.text( openwaAdmin.i18n.sent ).css( 'color', '#46b450' );
				} else {
					$result.text( response.data.message ).css( 'color', '#dc3232' );
				}
			} ).fail( function() {
				$result.text( openwaAdmin.i18n.failed ).css( 'color', '#dc3232' );
			} ).always( function() {
				$btn.prop( 'disabled', false );
			} );
		},

		createSession: function() {
			var $btn = $(this);
			var $msg = $( '#openwa-session-message' );

			$btn.prop( 'disabled', true );

			$.post( openwaAdmin.ajaxUrl, {
				action: 'openwa_create_session',
				nonce: openwaAdmin.nonce
			}, function( response ) {
				if ( response.success ) {
					openwa.showMessage( 'Session created. Starting...', 'success' );
					openwa.autoStartSession();
				} else {
					openwa.showMessage( response.data.message, 'error' );
				}
			} ).fail( function() {
				openwa.showMessage( 'Failed to create session.', 'error' );
			} ).always( function() {
				$btn.prop( 'disabled', false );
			} );
		},

		autoStartSession: function() {
			setTimeout( function() {
				$.post( openwaAdmin.ajaxUrl, {
					action: 'openwa_start_session',
					nonce: openwaAdmin.nonce
				}, function( response ) {
					if ( response.success ) {
						openwa.showMessage( 'Session starting. Fetching QR code...', 'success' );
						setTimeout( openwa.fetchQR, 3000 );
					} else {
						openwa.showMessage( response.data.message, 'error' );
					}
				} );
			}, 1000 );
		},

		startSession: function() {
			var $btn = $(this);

			$btn.prop( 'disabled', true );

			$.post( openwaAdmin.ajaxUrl, {
				action: 'openwa_start_session',
				nonce: openwaAdmin.nonce
			}, function( response ) {
				if ( response.success ) {
					openwa.showMessage( 'Session starting...', 'success' );
					setTimeout( openwa.fetchQR, 3000 );
				} else {
					openwa.showMessage( response.data.message, 'error' );
				}
			} ).fail( function() {
				openwa.showMessage( 'Failed to start session.', 'error' );
			} ).always( function() {
				$btn.prop( 'disabled', false );
			} );
		},

		fetchQR: function() {
			$.post( openwaAdmin.ajaxUrl, {
				action: 'openwa_get_qr',
				nonce: openwaAdmin.nonce
			}, function( response ) {
				if ( response.success && response.data.qr ) {
					$( '#openwa-qr-box' ).show();
					$( '#openwa-qr-image' ).html(
						'<img src="' + response.data.qr + '" alt="QR Code" />'
					);
					openwa.showMessage( 'QR Code ready! Scan with WhatsApp.', 'success' );

					openwa.pollUntilReady();
				} else {
					openwa.showMessage( 'QR not ready yet. Retrying...', 'warning' );
					setTimeout( openwa.fetchQR, 3000 );
				}
			} ).fail( function() {
				openwa.showMessage( 'Failed to fetch QR code.', 'error' );
			} );
		},

		pollUntilReady: function() {
			var attempts = 0;
			var maxAttempts = 20;
			var poll = function() {
				if ( attempts >= maxAttempts ) {
					openwa.showMessage( 'Session not connected yet. Refresh manually.', 'warning' );
					return;
				}
				attempts++;

				$.post( openwaAdmin.ajaxUrl, {
					action: 'openwa_refresh_session',
					nonce: openwaAdmin.nonce
				}, function( response ) {
					if ( response.success && response.data.status === 'ready' ) {
						$( '#openwa-qr-box' ).hide();
						openwa.updateSessionInfo( response.data );
						openwa.showMessage( 'WhatsApp connected!', 'success' );
					} else if ( response.success && response.data.status === 'qr_ready' ) {
						setTimeout( poll, 3000 );
					} else {
						setTimeout( poll, 3000 );
					}
				} ).fail( function() {
					setTimeout( poll, 5000 );
				} );
			};
			setTimeout( poll, 5000 );
		},

		stopSession: function() {
			var $btn = $(this);
			$btn.prop( 'disabled', true );

			$.post( openwaAdmin.ajaxUrl, {
				action: 'openwa_stop_session',
				nonce: openwaAdmin.nonce
			}, function( response ) {
				if ( response.success ) {
					openwa.showMessage( 'Session stopped.', 'success' );
					openwa.loadSessionInfo();
					$( '#openwa-qr-box' ).hide();
				} else {
					openwa.showMessage( response.data.message, 'error' );
				}
			} ).fail( function() {
				openwa.showMessage( 'Failed to stop session.', 'error' );
			} ).always( function() {
				$btn.prop( 'disabled', false );
			} );
		},

		deleteSession: function() {
			if ( ! confirm( 'Are you sure you want to delete this session?' ) ) {
				return;
			}

			var $btn = $(this);
			$btn.prop( 'disabled', true );

			$.post( openwaAdmin.ajaxUrl, {
				action: 'openwa_delete_session',
				nonce: openwaAdmin.nonce
			}, function( response ) {
				if ( response.success ) {
					openwa.showMessage( 'Session deleted.', 'success' );
					openwa.loadSessionInfo();
					$( '#openwa-qr-box' ).hide();
				} else {
					openwa.showMessage( response.data.message, 'error' );
				}
			} ).fail( function() {
				openwa.showMessage( 'Failed to delete session.', 'error' );
			} ).always( function() {
				$btn.prop( 'disabled', false );
			} );
		},

		refreshSessionInfo: function() {
			openwa.loadSessionInfo();
		},

		loadSessionInfo: function() {
			$( '#openwa-session-info' ).html(
				'<p class="openwa-session-loading">Loading session info...</p>'
			);

			$.post( openwaAdmin.ajaxUrl, {
				action: 'openwa_refresh_session',
				nonce: openwaAdmin.nonce
			}, function( response ) {
				if ( response.success ) {
					openwa.updateSessionInfo( response.data );
				} else {
					$( '#openwa-session-info' ).html(
						'<p class="error">' + response.data.message + '</p>'
					);
				}
			} ).fail( function() {
				$( '#openwa-session-info' ).html(
					'<p class="error">Failed to load session info.</p>'
				);
			} );
		},

		updateSessionInfo: function( data ) {
			var html = '';

			if ( data.status === 'inactive' || data.status === 'no_session' ) {
				html = '<p>No WhatsApp session configured. Click "Create New Session" to begin.</p>';
			} else if ( data.status === 'error' ) {
				html = '<p class="error">' + data.message + '</p>';
			} else {
				html += '<p><span class="openwa-session-status status-' + data.status + '">' +
					data.message + '</span></p>';
				if ( data.phone ) {
					html += '<p><strong>Phone:</strong> ' + data.phone + '</p>';
				}
				if ( data.pushName ) {
					html += '<p><strong>Name:</strong> ' + data.pushName + '</p>';
				}
				if ( data.status === 'qr_ready' ) {
					openwa.fetchQR();
				}
			}

			$( '#openwa-session-info' ).html( html );
		},

		sendOrderMessage: function() {
			var $btn = $(this);
			var $status = $( '.openwa-order-msg-status' );
			var orderId = $btn.data( 'order-id' );
			var message = $( '#openwa-custom-message' ).val();

			if ( ! message ) {
				$status.text( 'Please enter a message.' ).css( 'color', '#dc3232' );
				return;
			}

			$btn.prop( 'disabled', true );
			$status.text( openwaAdmin.i18n.sending ).css( 'color', '#666' );

			$.post( openwaAdmin.ajaxUrl, {
				action: 'openwa_send_order_message',
				nonce: openwaAdmin.nonce,
				order_id: orderId,
				message: message
			}, function( response ) {
				if ( response.success ) {
					$status.text( openwaAdmin.i18n.sent ).css( 'color', '#46b450' );
				} else {
					$status.text( response.data.message ).css( 'color', '#dc3232' );
				}
			} ).fail( function() {
				$status.text( openwaAdmin.i18n.failed ).css( 'color', '#dc3232' );
			} ).always( function() {
				$btn.prop( 'disabled', false );
			} );
		},

		insertShortcode: function(e) {
			e.preventDefault();
			var $textarea = $(this).closest( 'td' ).find( 'textarea' );
			var sc = $(this).data( 'sc' );
			var cursor = $textarea.prop( 'selectionStart' );
			var val = $textarea.val();
			var before = val.substring( 0, cursor );
			var after = val.substring( cursor );
			$textarea.val( before + '{' + sc + '}' + after );
			$textarea.focus();
			$textarea.prop( 'selectionStart', cursor + sc.length + 2 );
			$textarea.prop( 'selectionEnd', cursor + sc.length + 2 );
		},

		toggleAllTemplates: function() {
			$( '.openwa-templates-table input[type="checkbox"]' ).prop(
				'checked',
				$(this).prop( 'checked' )
			);
		},

		showMessage: function( text, type ) {
			var $msg = $( '#openwa-session-message' );
			$msg.removeClass( 'success error warning' )
				.addClass( type )
				.text( text )
				.show();
		}
	};

	$(document).ready(function() {
		openwa.init();
	});

})(jQuery);

(function($) {
	'use strict';

	$(document).on( 'click', '.openwa-send-invoice', function() {
		var $btn = $(this);
		var $status = $( '.openwa-order-msg-status' );
		var orderId = $btn.data( 'order-id' );

		$btn.prop( 'disabled', true );
		$status.text( 'Sending invoice...' ).css( 'color', '#666' );

		$.post( openwaAdmin.ajaxUrl, {
			action: 'openwa_send_invoice',
			nonce: openwaAdmin.nonce,
			order_id: orderId
		}, function( response ) {
			if ( response.success ) {
				$status.text( 'Invoice sent!' ).css( 'color', '#46b450' );
			} else {
				$status.text( response.data.message ).css( 'color', '#dc3232' );
			}
		} ).fail( function() {
			$status.text( 'Failed to send invoice.' ).css( 'color', '#dc3232' );
		} ).always( function() {
			$btn.prop( 'disabled', false );
		} );
	} );

	$(document).on( 'click', '.openwa-send-order-msg', function() {
		var $btn = $(this);
		var $status = $( '.openwa-order-msg-status' );
		var orderId = $btn.data( 'order-id' );
		var message = $( '#openwa-custom-message' ).val();

		if ( ! message ) {
			$status.text( 'Please enter a message.' ).css( 'color', '#dc3232' );
			return;
		}

		$btn.prop( 'disabled', true );
		$status.text( openwaAdmin.i18n.sending ).css( 'color', '#666' );

		$.post( openwaAdmin.ajaxUrl, {
			action: 'openwa_send_order_message',
			nonce: openwaAdmin.nonce,
			order_id: orderId,
			message: message
		}, function( response ) {
			if ( response.success ) {
				$status.text( openwaAdmin.i18n.sent ).css( 'color', '#46b450' );
			} else {
				$status.text( response.data.message ).css( 'color', '#dc3232' );
			}
		} ).fail( function() {
			$status.text( openwaAdmin.i18n.failed ).css( 'color', '#dc3232' );
		} ).always( function() {
			$btn.prop( 'disabled', false );
		} );
	} );

})(jQuery);
