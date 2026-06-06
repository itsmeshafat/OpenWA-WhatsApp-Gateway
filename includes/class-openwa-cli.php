<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

class OpenWA_CLI extends WP_CLI_Command {

	private function colorize( $text, $color ) {
		$colors = array(
			'green'  => '%G',
			'red'    => '%R',
			'yellow' => '%Y',
			'cyan'   => '%C',
			'white'  => '%W',
			'bold'   => '%B',
		);
		$tag = isset( $colors[ $color ] ) ? $colors[ $color ] : '%W';
		return \WP_CLI::colorize( $tag . $text . '%n' );
	}

	private function step( $msg ) {
		\WP_CLI::line( $this->colorize( '>>> ' . $msg, 'cyan' ) );
	}

	private function ok( $msg ) {
		\WP_CLI::line( $this->colorize( '  [OK] ' . $msg, 'green' ) );
	}

	private function fail( $msg ) {
		\WP_CLI::line( $this->colorize( '  [FAIL] ' . $msg, 'red' ) );
	}

	private function info( $msg ) {
		\WP_CLI::line( '  [..] ' . $msg );
	}

	/**
	 * Test PDF invoice sending for an order.
	 *
	 * Walks through the entire send_document flow and reports
	 * what URL would be tried and the result at each step.
	 *
	 * ## OPTIONS
	 *
	 * <order_id>
	 * : WooCommerce order ID to test with.
	 *
	 * [--send]
	 * : Actually send the PDF (default: dry-run, shows what would happen).
	 *
	 * [--phone=<phone>]
	 * : Override the recipient phone number.
	 *
	 * [--session=<session_id>]
	 * : Override the WhatsApp session ID.
	 *
	 * ## EXAMPLES
	 *
	 *     # Dry-run (shows URL that would be used)
	 *     wp openwa test-pdf-send 1277
	 *
	 *     # Actually send
	 *     wp openwa test-pdf-send 1277 --send
	 *
	 *     # Send to a specific phone
	 *     wp openwa test-pdf-send 1277 --send --phone=628123456789
	 */
	public function test_pdf_send( $args, $assoc_args ) {
		$order_id = intval( $args[0] );
		$do_send  = ! empty( $assoc_args['send'] );

		\WP_CLI::line( '' );
		\WP_CLI::line( $this->colorize( '=== OpenWA PDF Invoice Send Test ===', 'bold' ) );
		\WP_CLI::line( '' );

		// -- Check plugin configuration
		$this->step( '1. Checking plugin configuration' );
		$settings = get_option( 'openwa_settings', array() );
		$base_url = isset( $settings['base_url'] ) ? $settings['base_url'] : '';
		$api_key  = isset( $settings['api_key'] ) ? $settings['api_key'] : '';

		if ( empty( $base_url ) ) {
			$this->fail( 'OpenWA Server URL not configured' );
			\WP_CLI::line( '  Set it at: WP Admin > OpenWA > Settings' );
			return;
		}
		$this->ok( 'OpenWA Server URL: ' . $base_url );

		if ( empty( $api_key ) ) {
			$this->fail( 'API Key not configured' );
			return;
		}
		$this->ok( 'API Key: ' . substr( $api_key, 0, 8 ) . '...' );

		// -- Check order
		$this->step( '2. Loading order #' . $order_id );
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			$this->fail( 'Order #' . $order_id . ' not found' );
			return;
		}
		$this->ok( 'Order found: #' . $order->get_order_number() . ' (' . $order->get_status() . ')' );

		$phone = $order->get_billing_phone();
		if ( ! empty( $assoc_args['phone'] ) ) {
			$phone = $assoc_args['phone'];
		}
		if ( empty( $phone ) ) {
			$this->fail( 'No billing phone number on order' );
			return;
		}
		$this->ok( 'Customer phone: ' . $phone );

		$session_id = isset( $settings['session_id'] ) ? $settings['session_id'] : '';
		if ( ! empty( $assoc_args['session'] ) ) {
			$session_id = $assoc_args['session'];
		}
		if ( empty( $session_id ) ) {
			$this->fail( 'No WhatsApp session configured' );
			return;
		}
		$this->ok( 'Session ID: ' . $session_id );

		// -- Check PDF plugin
		$this->step( '3. Checking PDF invoice plugin' );
		$invoice = new OpenWA_Invoice();
		if ( ! $invoice->is_pdf_plugin_active() ) {
			$this->fail( 'WooCommerce PDF Invoices plugin not active' );
			$this->info( 'Install: https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/' );
			return;
		}
		$this->ok( 'WooCommerce PDF Invoices plugin is active' );

		// -- Check session status
		$this->step( '4. Checking WhatsApp session status' );
		$session = new OpenWA_Session();
		$status  = $session->get_formatted_status();
		if ( is_wp_error( $status ) ) {
			$this->fail( 'Session error: ' . $status->get_error_message() );
			return;
		}
		$this->ok( 'Session status: ' . $status['status'] );
		if ( $status['status'] !== 'ready' ) {
			$this->fail( 'Session is not ready (status: ' . $status['status'] . ')' );
			return;
		}

		// -- Generate PDF data
		$this->step( '5. Generating PDF invoice' );
		$message = new OpenWA_Message();
		$chat_id = $message->format_chat_id( $phone );
		$pdf = $invoice->get_invoice_pdf_data( $order );

		if ( ! $pdf ) {
			$this->fail( 'Failed to generate PDF data' );
			return;
		}
		$this->ok( 'PDF generated: ' . $pdf['filename'] );
		$this->info( '  Size:      ' . number_format( $pdf['size'] ) . ' bytes' );
		$this->info( '  Filepath:  ' . $pdf['filepath'] );
		$this->info( '  URL:       ' . $pdf['url'] );
		$this->info( '  Base64:    ' . number_format( strlen( $pdf['base64'] ) ) . ' chars' );

		$err_hint = $this->get_error_hint( $pdf );

		// -- Send
		if ( ! $do_send ) {
			\WP_CLI::line( '' );
			\WP_CLI::line( $this->colorize( '--- Dry-run complete. Use --send to actually send. ---', 'yellow' ) );
			if ( $err_hint ) {
				\WP_CLI::line( '' );
				\WP_CLI::line( $this->colorize( $err_hint, 'yellow' ) );
			}
			return;
		}

		$this->step( '6. Sending PDF via URL' );
		$api = new OpenWA_API();
		$caption = sprintf(
			__( 'Invoice #%2$s from %1$s', 'openwa-whatsapp-gateway' ),
			get_bloginfo( 'name' ),
			$order->get_order_number()
		);

		$result = $api->request( 'POST', '/sessions/' . $session_id . '/messages/send-document', array(
			'chatId'   => $chat_id,
			'url'      => $pdf['url'],
			'mimetype' => 'application/pdf',
			'filename' => $pdf['filename'],
			'caption'  => $caption,
		) );

		if ( ! is_wp_error( $result ) ) {
			$this->ok( 'Sent via URL! Message ID: ' . ( $result['messageId'] ?? 'unknown' ) );
			\WP_CLI::line( '' );
			\WP_CLI::line( $this->colorize( '=== Test complete ===', 'bold' ) );
			return;
		}

		$this->fail( 'URL method failed: ' . $result->get_error_message() );

		// -- Try base64 if PDF is small enough
		if ( $pdf['size'] < 70000 ) {
			$this->step( '7. Trying base64 (PDF is within 70KB limit)' );
			$result = $api->request( 'POST', '/sessions/' . $session_id . '/messages/send-document', array(
				'chatId'   => $chat_id,
				'base64'   => $pdf['base64'],
				'mimetype' => 'application/pdf',
				'filename' => $pdf['filename'],
				'caption'  => $caption,
			) );

			if ( ! is_wp_error( $result ) ) {
				$this->ok( 'Sent via base64! Message ID: ' . ( $result['messageId'] ?? 'unknown' ) );
				\WP_CLI::line( '' );
				\WP_CLI::line( $this->colorize( '=== Test complete ===', 'bold' ) );
				return;
			}

			$this->fail( 'Base64 also failed: ' . $result->get_error_message() );
		} else {
			$this->step( '7. Skipping base64 (PDF is ' . number_format( $pdf['size'] ) . ' bytes, limit: 70000)' );
		}

		// -- All methods failed
		$this->fail( 'All send methods failed.' );
		\WP_CLI::line( '' );
		if ( $err_hint ) {
			\WP_CLI::line( $this->colorize( $err_hint, 'yellow' ) );
		}
		\WP_CLI::line( '' );
		\WP_CLI::line( $this->colorize( '=== Test complete ===', 'bold' ) );
	}

	/**
	 * Return a helpful hint string depending on the PDF size / URL scheme.
	 */
	private function get_error_hint( $pdf ) {
		$hints = array();

		if ( strpos( $pdf['url'], 'https://' ) === 0 ) {
			$hints[] = '  - Ensure your SSL certificate is valid and trusted by Node.js.';
			$hints[] = '  - For local development, run OpenWA with: NODE_TLS_REJECT_UNAUTHORIZED=0 npm run dev';
		}

		if ( $pdf['size'] >= 70000 ) {
			$hints[] = '  - PDF is too large (' . number_format( $pdf['size'] ) . ' bytes) for base64 fallback.';
			$hints[] = '  - To enable base64 for larger files, increase the JSON body parser limit';
			$hints[] = '    in OpenWA\'s src/main.ts: app.useBodyParser(\'json\', { limit: \'10mb\' })';
		}

		if ( empty( $hints ) ) {
			return '';
		}

		return $this->colorize( 'Suggestions:', 'yellow' ) . "\n" . implode( "\n", $hints );
	}
}

\WP_CLI::add_command( 'openwa', 'OpenWA_CLI' );
