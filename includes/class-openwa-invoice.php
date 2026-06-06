<?php
defined( 'ABSPATH' ) || exit;

class OpenWA_Invoice {

	private $api;
	private $upload_dir;
	private $upload_url;

	public function __construct() {
		$this->api = new OpenWA_API();
		$upload = wp_upload_dir();
		$this->upload_dir = $upload['basedir'] . '/openwa-invoices';
		$this->upload_url = $upload['baseurl'] . '/openwa-invoices';
		$this->ensure_upload_dir();
	}

	private function ensure_upload_dir() {
		if ( ! file_exists( $this->upload_dir ) ) {
			wp_mkdir_p( $this->upload_dir );
		}
	}

	public function is_pdf_plugin_active() {
		return function_exists( 'wcpdf_get_document' );
	}

	public function get_invoice_pdf_data( $order ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}
		if ( ! $order ) {
			return null;
		}
		if ( ! $this->is_pdf_plugin_active() ) {
			return null;
		}

		$document = wcpdf_get_document( 'invoice', $order, true );
		if ( ! $document ) {
			return null;
		}

		$pdf_data = $document->get_pdf();
		if ( empty( $pdf_data ) ) {
			return null;
		}

		$filename = 'invoice-' . $order->get_order_number() . '.pdf';
		$filepath = $this->upload_dir . '/' . $filename;
		file_put_contents( $filepath, $pdf_data );

		// Remove .htaccess so OpenWA can fetch the file via URL
		$htaccess = $this->upload_dir . '/.htaccess';
		if ( file_exists( $htaccess ) ) {
			unlink( $htaccess );
		}

		// Allow site_url override (e.g., Docker setups where OpenWA
		// cannot reach the WordPress site URL directly)
		$settings = get_option( 'openwa_settings', array() );
		$site_url = ! empty( $settings['site_url'] ) ? untrailingslashit( $settings['site_url'] ) : '';

		if ( $site_url ) {
			$parts = parse_url( $this->upload_url );
			$url   = $site_url . ( isset( $parts['path'] ) ? $parts['path'] : '' ) . '/' . $filename;
		} else {
			$url = $this->upload_url . '/' . $filename;
		}

		OpenWA_Logger::info( 'Invoice PDF generated', array(
			'url'      => $url,
			'filepath' => $filepath,
			'size'     => strlen( $pdf_data ),
		) );

		return array(
			'url'      => $url,
			'base64'   => base64_encode( $pdf_data ),
			'raw'      => $pdf_data,
			'filepath' => $filepath,
			'filename' => $filename,
			'size'     => strlen( $pdf_data ),
		);
	}

	public function send_document( $session_id, $chat_id, $pdf, $caption ) {
		$filename = $pdf['filename'];

		// 1. Try URL — works in production (valid SSL) and local dev
		//    when OpenWA is run with NODE_TLS_REJECT_UNAUTHORIZED=0.
		$result = $this->api->request( 'POST', '/sessions/' . $session_id . '/messages/send-document', array(
			'chatId'   => $chat_id,
			'url'      => $pdf['url'],
			'mimetype' => 'application/pdf',
			'filename' => $filename,
			'caption'  => $caption,
		) );

		if ( ! is_wp_error( $result ) ) {
			return $result;
		}

		OpenWA_Logger::warning( 'PDF URL send failed, trying base64 fallback', array(
			'url'   => $pdf['url'],
			'size'  => $pdf['size'],
			'error' => $result->get_error_message(),
		) );

		// 2. Base64 fallback — NestJS default body-parser JSON limit is
		//    102400 bytes. Base64 adds ~37 % overhead, so raw data <= 70 KB
		//    stays within the limit.
		if ( $pdf['size'] < 70000 ) {
			$result2 = $this->api->request( 'POST', '/sessions/' . $session_id . '/messages/send-document', array(
				'chatId'   => $chat_id,
				'base64'   => $pdf['base64'],
				'mimetype' => 'application/pdf',
				'filename' => $filename,
				'caption'  => $caption,
			) );

			if ( ! is_wp_error( $result2 ) ) {
				return $result2;
			}

			OpenWA_Logger::error( 'Base64 send also failed', array(
				'error' => $result2->get_error_message(),
			) );
		} else {
			OpenWA_Logger::warning( sprintf(
				'PDF too large for base64 fallback (%s bytes raw). Use URL approach — ensure valid SSL cert or run OpenWA with NODE_TLS_REJECT_UNAUTHORIZED=0.',
				number_format( $pdf['size'] )
			) );
		}

		return $result;
	}

	public function get_invoice_text( $order ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}
		if ( ! $order ) {
			return '';
		}

		$total = html_entity_decode( wp_strip_all_tags( $order->get_formatted_order_total() ), ENT_QUOTES, 'UTF-8' );
		$subtotal = html_entity_decode( wp_strip_all_tags( wc_price( $order->get_subtotal(), array( 'currency' => $order->get_currency() ) ) ), ENT_QUOTES, 'UTF-8' );
		$shipping_total = $order->get_shipping_total() > 0
			? html_entity_decode( wp_strip_all_tags( wc_price( $order->get_shipping_total(), array( 'currency' => $order->get_currency() ) ) ), ENT_QUOTES, 'UTF-8' )
			: '';
		$payment_method = $order->get_payment_method_title();
		$billing = html_entity_decode( wp_strip_all_tags( $order->get_formatted_billing_address() ), ENT_QUOTES, 'UTF-8' );

		$lines = array();
		$lines[] = '═══════════════════════════════';
		$lines[] = sprintf( __( 'INVOICE #%s', 'openwa-whatsapp-gateway' ), $order->get_order_number() );
		$lines[] = sprintf( __( 'Date: %s', 'openwa-whatsapp-gateway' ), $order->get_date_created()->date_i18n( get_option( 'date_format' ) ) );
		$lines[] = sprintf( __( 'Status: %s', 'openwa-whatsapp-gateway' ), wc_get_order_status_name( $order->get_status() ) );
		$lines[] = '';
		$lines[] = __( 'Bill To:', 'openwa-whatsapp-gateway' );
		$lines[] = $billing;
		$lines[] = '';
		$lines[] = __( 'Items:', 'openwa-whatsapp-gateway' );

		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			$sku = $product ? $product->get_sku() : '';
			$item_total = html_entity_decode( wp_strip_all_tags( wc_price( $item->get_total(), array( 'currency' => $order->get_currency() ) ) ), ENT_QUOTES, 'UTF-8' );
			$line = sprintf( '%s x%d', $item->get_name(), $item->get_quantity() );
			if ( $sku ) {
				$line = sprintf( '[%s] %s', $sku, $line );
			}
			$lines[] = '  ' . $line . ' — ' . $item_total;
		}

		$lines[] = '───────────────────────────────';
		$lines[] = sprintf( '%s: %s', __( 'Subtotal', 'openwa-whatsapp-gateway' ), $subtotal );
		if ( $shipping_total ) {
			$lines[] = sprintf( '%s: %s', __( 'Shipping', 'openwa-whatsapp-gateway' ), $shipping_total );
		}
		foreach ( $order->get_order_item_totals() as $total_item ) {
			$label = html_entity_decode( wp_strip_all_tags( $total_item['label'] ), ENT_QUOTES, 'UTF-8' );
			if ( 'Subtotal' === $label || 'Shipping' === $label ) {
				continue;
			}
			$value = html_entity_decode( wp_strip_all_tags( $total_item['value'] ), ENT_QUOTES, 'UTF-8' );
			$lines[] = sprintf( '%s: %s', $label, $value );
		}
		if ( $payment_method ) {
			$lines[] = sprintf( '%s: %s', __( 'Payment', 'openwa-whatsapp-gateway' ), $payment_method );
		}
		$lines[] = '═══════════════════════════════';
		$lines[] = sprintf( __( 'View online: %s', 'openwa-whatsapp-gateway' ), $order->get_view_order_url() );

		return implode( "\n", $lines );
	}

	public function send_invoice( $order, $session_id = '' ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}
		if ( ! $order ) {
			return new WP_Error( 'invalid_order', __( 'Order not found.', 'openwa-whatsapp-gateway' ) );
		}

		$phone = $order->get_billing_phone();
		if ( empty( $phone ) ) {
			return new WP_Error( 'no_phone', __( 'Customer has no phone number.', 'openwa-whatsapp-gateway' ) );
		}

		$message = new OpenWA_Message();
		$phone = $message->normalize_phone( $phone );
		if ( empty( $phone ) ) {
			return new WP_Error( 'invalid_phone', __( 'Invalid phone number.', 'openwa-whatsapp-gateway' ) );
		}

		if ( empty( $session_id ) ) {
			$settings = get_option( 'openwa_settings', array() );
			$session_id = isset( $settings['session_id'] ) ? $settings['session_id'] : '';
		}

		if ( empty( $session_id ) ) {
			return new WP_Error( 'no_session', __( 'No WhatsApp session configured.', 'openwa-whatsapp-gateway' ) );
		}

		$chat_id = $message->format_chat_id( $phone );
		$pdf = $this->get_invoice_pdf_data( $order );

		$template = $message->get_template( 'invoice' );
		if ( $template && ! empty( $template['enabled'] ) ) {
			$data = $this->build_invoice_data( $order );
			$caption = $message->parse_template( $template['message'], $data );
		} else {
			$caption = sprintf(
				__( 'Invoice #%2$s from %1$s', 'openwa-whatsapp-gateway' ),
				get_bloginfo( 'name' ),
				$order->get_order_number()
			);
		}

		if ( $pdf ) {
			$result = $this->send_document( $session_id, $chat_id, $pdf, $caption );

			if ( is_wp_error( $result ) ) {
				OpenWA_Logger::warning( 'PDF invoice send failed, falling back to text', array(
					'order_id' => $order->get_id(),
					'error'    => $result->get_error_message(),
				) );
				$text = $this->get_invoice_text( $order );
				$result = $this->api->send_text_message( $session_id, $chat_id, $text );
			}
		} else {
			$text = $this->get_invoice_text( $order );
			$result = $this->api->send_text_message( $session_id, $chat_id, $text );
		}

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$this->log_sent( $order, 'invoice' );

		return $result;
	}

	public function send_invoice_notification( $order, $session_id = '' ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}
		if ( ! $order ) {
			return new WP_Error( 'invalid_order', __( 'Order not found.', 'openwa-whatsapp-gateway' ) );
		}

		$msg = new OpenWA_Message();
		$template = $msg->get_template( 'invoice' );
		if ( ! $template || empty( $template['enabled'] ) ) {
			return false;
		}

		$phone_raw = $order->get_billing_phone();
		$phone = $msg->normalize_phone( $phone_raw );
		if ( empty( $phone ) ) {
			return new WP_Error( 'invalid_phone', __( 'Invalid phone number.', 'openwa-whatsapp-gateway' ) );
		}

		if ( empty( $session_id ) ) {
			$settings = get_option( 'openwa_settings', array() );
			$session_id = isset( $settings['session_id'] ) ? $settings['session_id'] : '';
		}
		if ( empty( $session_id ) ) {
			return new WP_Error( 'no_session', __( 'No WhatsApp session configured.', 'openwa-whatsapp-gateway' ) );
		}

		$chat_id = $msg->format_chat_id( $phone );

		$data = $this->build_invoice_data( $order );
		$caption = $msg->parse_template( $template['message'], $data );
		$pdf = $this->get_invoice_pdf_data( $order );

		if ( $pdf ) {
			$result = $this->send_document( $session_id, $chat_id, $pdf, $caption );

			if ( is_wp_error( $result ) ) {
				OpenWA_Logger::warning( 'Invoice PDF notif failed, falling back to text', array(
					'order_id' => $order->get_id(),
					'error'    => $result->get_error_message(),
				) );
				$text = $caption . "\n\n" . $this->get_invoice_text( $order );
				$result = $this->api->send_text_message( $session_id, $chat_id, $text );
			}
		} else {
			$text = $caption . "\n\n" . $this->get_invoice_text( $order );
			$result = $this->api->send_text_message( $session_id, $chat_id, $text );
		}

		if ( is_wp_error( $result ) ) {
			OpenWA_Logger::error( 'Invoice notification failed', array(
				'order_id' => $order->get_id(),
				'error'    => $result->get_error_message(),
			) );
			return $result;
		}

		$this->log_sent( $order, 'invoice_notification' );
		OpenWA_Logger::info( 'Invoice notification sent', array(
			'order_id'     => $order->get_id(),
			'attached_pdf' => (bool) $pdf,
			'pdf_size'     => $pdf ? $pdf['size'] : 0,
		) );

		return $result;
	}

	private function build_invoice_data( $order ) {
		$total = html_entity_decode( wp_strip_all_tags( $order->get_formatted_order_total() ), ENT_QUOTES, 'UTF-8' );
		$subtotal = html_entity_decode( wp_strip_all_tags( wc_price( $order->get_subtotal(), array( 'currency' => $order->get_currency() ) ) ), ENT_QUOTES, 'UTF-8' );
		$shipping_total = $order->get_shipping_total() > 0
			? html_entity_decode( wp_strip_all_tags( wc_price( $order->get_shipping_total(), array( 'currency' => $order->get_currency() ) ) ), ENT_QUOTES, 'UTF-8' )
			: '';

		$items_detailed = array();
		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			$sku = $product ? $product->get_sku() : '';
			$line_total = html_entity_decode( wp_strip_all_tags( wc_price( $item->get_total(), array( 'currency' => $order->get_currency() ) ) ), ENT_QUOTES, 'UTF-8' );
			$line = $item->get_name();
			if ( $sku ) {
				$line = '[' . $sku . '] ' . $line;
			}
			$line .= ' x' . $item->get_quantity() . ' = ' . $line_total;
			$items_detailed[] = $line;
		}

		return array(
			'order_id'          => $order->get_order_number(),
			'customer_name'     => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
			'customer_first_name' => $order->get_billing_first_name(),
			'customer_email'    => $order->get_billing_email(),
			'customer_phone'    => $order->get_billing_phone(),
			'order_total'       => $total,
			'order_status'      => wc_get_order_status_name( $order->get_status() ),
			'order_date'        => $order->get_date_created()->date_i18n( get_option( 'date_format' ) ),
			'order_time'        => $order->get_date_created()->format( get_option( 'time_format' ) ),
			'subtotal'          => $subtotal,
			'shipping_total'    => $shipping_total,
			'payment_method'    => $order->get_payment_method_title(),
			'shipping_method'   => $order->get_shipping_method(),
			'items_detail'      => implode( "\n", $items_detailed ),
			'items'             => implode( ', ', array_map( function( $item ) {
				return $item->get_name() . ' x' . $item->get_quantity();
			}, $order->get_items() ) ),
			'site_name'         => get_bloginfo( 'name' ),
			'site_url'          => home_url(),
		);
	}

	public function get_history( $order ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}
		if ( ! $order ) {
			return array();
		}
		$history = $order->get_meta( '_openwa_message_history' );
		return is_array( $history ) ? $history : array();
	}

	private function log_sent( $order, $type ) {
		$history = $this->get_history( $order );
		$history[] = array(
			'time'    => current_time( 'mysql' ),
			'type'    => $type,
			'phone'   => $order->get_billing_phone(),
		);
		$order->update_meta_data( '_openwa_message_history', $history );
		$order->update_meta_data( '_openwa_last_sent', current_time( 'mysql' ) );
		$order->save();
	}
}
