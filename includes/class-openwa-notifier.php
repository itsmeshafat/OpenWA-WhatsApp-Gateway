<?php
defined( 'ABSPATH' ) || exit;

class OpenWA_Notifier {

	private static $instance = null;
	private $message;
	private $api;

	public static function init() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
	}

	public function __construct() {
		$this->message = new OpenWA_Message();
		$this->api     = new OpenWA_API();

		$statuses = array( 'pending', 'processing', 'completed', 'on-hold', 'cancelled', 'refunded', 'failed' );
		foreach ( $statuses as $status ) {
			add_action( 'woocommerce_order_status_' . $status, array( $this, 'handle_order_status_change' ), 10, 2 );
		}

		add_action( 'woocommerce_new_order', array( $this, 'handle_new_order_admin' ), 10, 2 );

		add_action( 'woocommerce_created_customer', array( $this, 'handle_customer_registration' ), 10, 3 );

		add_action( 'openwa_daily_digest', array( $this, 'maybe_send_daily_digest' ) );
	}

	public function handle_order_status_change( $order_id, $order ) {
		if ( is_numeric( $order_id ) ) {
			$order = wc_get_order( $order_id );
		}
		if ( ! $order ) {
			return;
		}

		$order_id_val = $order->get_id();
		$status = $order->get_status();
		$template_key = 'order_' . $status;

		$template = $this->message->get_template( $template_key );
		$template_enabled = $template && ! empty( $template['enabled'] );

		if ( ! $template_enabled ) {
			OpenWA_Logger::info( 'Skipping notification - template disabled or missing', array(
				'order_id' => $order_id_val,
				'template' => $template_key,
			) );
		}

		$admin_phone_raw    = $this->get_admin_notification_phone();
		$customer_phone_raw = $order->get_billing_phone();
		$data               = $this->build_order_data( $order );

		$invoice_template = $this->message->get_template( 'invoice' );
		$attach_invoice   = $invoice_template && ! empty( $invoice_template['enabled'] );

		if ( $template_enabled ) {
			// Admin: always send plain text
			if ( ! empty( $admin_phone_raw ) ) {
				OpenWA_Logger::info( 'Queue admin notification', array(
					'order_id' => $order_id_val,
					'template' => $template_key,
					'raw_phone' => $admin_phone_raw,
				) );
				$this->try_send( $admin_phone_raw, $template_key, $data, 'admin' );
			} else {
				OpenWA_Logger::info( 'No admin phone configured', array(
					'order_id' => $order_id_val,
					'template' => $template_key,
				) );
			}

			// Customer: send PDF + caption if invoice is enabled, otherwise plain text
			if ( ! empty( $customer_phone_raw ) ) {
				OpenWA_Logger::info( 'Queue customer notification', array(
					'order_id'  => $order_id_val,
					'template'  => $template_key,
					'raw_phone' => $customer_phone_raw,
					'attach_invoice' => $attach_invoice,
				) );

				if ( $attach_invoice ) {
					$this->try_send_with_invoice( $customer_phone_raw, $template_key, $data, $order );
				} else {
					$this->try_send( $customer_phone_raw, $template_key, $data, 'customer' );
				}
			} else {
				OpenWA_Logger::info( 'Customer has no billing phone', array(
					'order_id' => $order_id_val,
					'template' => $template_key,
				) );
			}
		}
	}

	public function handle_new_order_admin( $order_id, $order ) {
		if ( is_numeric( $order_id ) ) {
			$order = wc_get_order( $order_id );
		}
		if ( ! $order ) {
			return;
		}

		$template = $this->message->get_template( 'new_order_admin' );
		if ( ! $template || empty( $template['enabled'] ) ) {
			return;
		}

		$admin_phone_raw = $this->get_admin_notification_phone();
		if ( empty( $admin_phone_raw ) ) {
			return;
		}

		$data = $this->build_order_data( $order );
		$this->try_send( $admin_phone_raw, 'new_order_admin', $data, 'admin' );
	}

	public function handle_customer_registration( $customer_id, $new_customer_data, $password_generated ) {
		$template = $this->message->get_template( 'customer_registered' );
		if ( ! $template || empty( $template['enabled'] ) ) {
			return;
		}

		$phone = get_user_meta( $customer_id, 'billing_phone', true );
		if ( empty( $phone ) ) {
			return;
		}

		$user = get_userdata( $customer_id );
		$data = array(
			'customer_name'  => $user ? $user->display_name : '',
			'customer_email' => $user ? $user->user_email : '',
		);

		$this->try_send( $phone, 'customer_registered', $data, 'customer' );
	}

	public function try_send_public( $phone, $template_key, $data, $recipient_type ) {
		$this->try_send( $phone, $template_key, $data, $recipient_type );
	}

	private function try_send( $phone, $template_key, $data, $recipient_type ) {
		$phone = $this->message->normalize_phone( $phone );
		if ( empty( $phone ) ) {
			OpenWA_Logger::warning( 'Skipped notification - empty phone', array(
				'template'  => $template_key,
				'recipient' => $recipient_type,
			) );
			return;
		}

		$result = $this->message->send_notification( $phone, $template_key, $data );

		if ( is_wp_error( $result ) ) {
			OpenWA_Logger::error( 'Notification failed', array(
				'template'  => $template_key,
				'recipient' => $recipient_type,
				'phone'     => $phone,
				'error'     => $result->get_error_message(),
			) );
		} else {
			OpenWA_Logger::info( 'Notification sent', array(
				'template'  => $template_key,
				'recipient' => $recipient_type,
				'phone'     => $phone,
			) );
		}
	}

	private function try_send_with_invoice( $phone, $template_key, $data, $order ) {
		$phone = $this->message->normalize_phone( $phone );
		if ( empty( $phone ) ) {
			OpenWA_Logger::warning( 'Skipped invoice notification - empty phone', array(
				'template' => $template_key,
			) );
			return;
		}

		$template = $this->message->get_template( $template_key );
		if ( ! $template ) {
			$this->try_send( $phone, $template_key, $data, 'customer' );
			return;
		}

		$caption = $this->message->parse_template( $template['message'], $data );

		$invoice = new OpenWA_Invoice();
		$pdf     = $invoice->get_invoice_pdf_data( $order );

		if ( ! $pdf ) {
			// PDF generation failed, send plain text instead
			OpenWA_Logger::warning( 'PDF generation failed for invoice-attached notification, sending text', array(
				'order_id' => $order->get_id(),
			) );
			$this->try_send( $phone, $template_key, $data, 'customer' );
			return;
		}

		$settings = get_option( 'openwa_settings', array() );
		$session_id = isset( $settings['session_id'] ) ? $settings['session_id'] : '';
		if ( empty( $session_id ) ) {
			OpenWA_Logger::error( 'No session configured for invoice attachment', array(
				'order_id' => $order->get_id(),
			) );
			return;
		}

		$chat_id = $this->message->format_chat_id( $phone );
		$result = $invoice->send_document( $session_id, $chat_id, $pdf, $caption );

		if ( is_wp_error( $result ) ) {
			OpenWA_Logger::warning( 'Invoice PDF send failed in notification, falling back to text', array(
				'order_id'  => $order->get_id(),
				'template'  => $template_key,
				'error'     => $result->get_error_message(),
			) );
			// Fall back to plain text
			$this->try_send( $phone, $template_key, $data, 'customer' );
			return;
		}

		OpenWA_Logger::info( 'Notification sent with invoice PDF', array(
			'template'  => $template_key,
			'phone'     => $phone,
			'order_id'  => $order->get_id(),
			'pdf_size'  => $pdf['size'],
		) );
	}

	private function build_order_data( $order ) {
		$items       = array();
		$items_detailed = array();
		foreach ( $order->get_items() as $item ) {
			$product   = $item->get_product();
			$sku       = $product ? $product->get_sku() : '';
			$line_total = html_entity_decode( wp_strip_all_tags( wc_price( $item->get_total(), array( 'currency' => $order->get_currency() ) ) ), ENT_QUOTES, 'UTF-8' );

			$items[] = $item->get_name() . ' x' . $item->get_quantity();

			$line = $item->get_name();
			if ( $sku ) {
				$line = '[' . $sku . '] ' . $line;
			}
			$line .= ' x' . $item->get_quantity() . ' = ' . $line_total;
			$items_detailed[] = $line;
		}

		$total   = html_entity_decode( wp_strip_all_tags( $order->get_formatted_order_total() ), ENT_QUOTES, 'UTF-8' );
		$subtotal = html_entity_decode( wp_strip_all_tags( wc_price( $order->get_subtotal(), array( 'currency' => $order->get_currency() ) ) ), ENT_QUOTES, 'UTF-8' );
		$shipping_total = '';
		if ( $order->get_shipping_total() > 0 ) {
			$shipping_total = html_entity_decode( wp_strip_all_tags( wc_price( $order->get_shipping_total(), array( 'currency' => $order->get_currency() ) ) ), ENT_QUOTES, 'UTF-8' );
		}
		$discount_total = '';
		if ( $order->get_discount_total() > 0 ) {
			$discount_total = html_entity_decode( wp_strip_all_tags( wc_price( $order->get_discount_total(), array( 'currency' => $order->get_currency() ) ) ), ENT_QUOTES, 'UTF-8' );
		}
		$tax_total = '';
		if ( $order->get_total_tax() > 0 ) {
			$tax_total = html_entity_decode( wp_strip_all_tags( wc_price( $order->get_total_tax(), array( 'currency' => $order->get_currency() ) ) ), ENT_QUOTES, 'UTF-8' );
		}

		$customer_address = html_entity_decode( wp_strip_all_tags( $order->get_formatted_billing_address() ), ENT_QUOTES, 'UTF-8' );
		$shipping_address = html_entity_decode( wp_strip_all_tags( $order->get_formatted_shipping_address() ), ENT_QUOTES, 'UTF-8' );
		$order_note = $order->get_customer_note();

		$order_total_raw = $order->get_total();

		return array(
			'order_id'          => $order->get_order_number(),
			'order_status'      => wc_get_order_status_name( $order->get_status() ),
			'order_total'       => $total,
			'order_total_raw'   => $order_total_raw,
			'order_currency'    => $order->get_currency(),
			'order_date'        => $order->get_date_created()->format( get_option( 'date_format' ) ),
			'order_time'        => $order->get_date_created()->format( get_option( 'time_format' ) ),
			'customer_name'     => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
			'customer_first_name' => $order->get_billing_first_name(),
			'customer_email'    => $order->get_billing_email(),
			'customer_phone'    => $order->get_billing_phone(),
			'customer_address'  => $customer_address,
			'shipping_address'  => $shipping_address,
			'payment_method'    => $order->get_payment_method_title(),
			'shipping_method'   => $order->get_shipping_method(),
			'shipping_total'    => $shipping_total,
			'discount_total'    => $discount_total,
			'tax_total'         => $tax_total,
			'subtotal'          => $subtotal,
			'items'             => implode( ', ', $items ),
			'items_detail'      => implode( "\n", $items_detailed ),
			'order_note'        => $order_note,
			'site_name'         => get_bloginfo( 'name' ),
			'site_url'          => home_url(),
		);
	}

	private function get_admin_notification_phone() {
		$settings = get_option( 'openwa_settings', array() );
		return isset( $settings['admin_phone'] ) ? $settings['admin_phone'] : '';
	}

	public function maybe_send_daily_digest() {
		$settings = get_option( 'openwa_settings', array() );
		if ( empty( $settings['enable_digest'] ) || 'yes' !== $settings['enable_digest'] ) {
			return;
		}

		$admin_phone = $this->get_admin_notification_phone();
		if ( empty( $admin_phone ) ) {
			return;
		}

		global $wpdb;
		$today = current_time( 'Y-m-d' );

		$total_orders = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}posts WHERE post_type = 'shop_order' AND post_date >= %s",
			$today
		) );

		$total_revenue = $wpdb->get_var( $wpdb->prepare(
			"SELECT SUM(meta_value) FROM {$wpdb->prefix}postmeta pm
			INNER JOIN {$wpdb->prefix}posts p ON pm.post_id = p.ID
			WHERE p.post_type = 'shop_order' AND p.post_date >= %s AND pm.meta_key = '_order_total'",
			$today
		) );

		$data = array(
			'site_name'    => get_bloginfo( 'name' ),
			'date'         => date_i18n( get_option( 'date_format' ) ),
			'total_orders' => (int) $total_orders,
			'total_revenue' => wp_strip_all_tags( wc_price( (float) $total_revenue ) ),
		);

		$this->try_send( $admin_phone, 'daily_digest', $data, 'admin' );
	}
}
