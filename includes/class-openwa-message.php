<?php
defined( 'ABSPATH' ) || exit;

class OpenWA_Message {

	private $api;

	public function __construct() {
		$this->api = new OpenWA_API();
	}

	public function send( $chat_id, $text, $session_id = '' ) {
		if ( empty( $session_id ) ) {
			$settings = get_option( 'openwa_settings', array() );
			$session_id = isset( $settings['session_id'] ) ? $settings['session_id'] : '';
		}
		if ( empty( $session_id ) ) {
			return new WP_Error( 'no_session', __( 'No WhatsApp session configured.', 'openwa-whatsapp-gateway' ) );
		}
		return $this->api->send_text_message( $session_id, $chat_id, $text );
	}

	public function send_notification( $phone, $template_id, $data = array() ) {
		$raw_phone = $phone;
		$phone = $this->normalize_phone( $phone );
		if ( ! $phone ) {
			OpenWA_Logger::error( 'Invalid phone for notification', array(
				'template'    => $template_id,
				'raw_phone'   => $raw_phone,
			) );
			return new WP_Error( 'invalid_phone', __( 'Invalid phone number.', 'openwa-whatsapp-gateway' ) );
		}

		$template = $this->get_template( $template_id );
		if ( ! $template || empty( $template['enabled'] ) ) {
			return new WP_Error( 'template_disabled', __( 'Message template not found or disabled.', 'openwa-whatsapp-gateway' ) );
		}
		$message = $this->parse_template( $template['message'], $data );
		$chat_id = $this->format_chat_id( $phone );
		OpenWA_Logger::info( 'Sending notification', array(
			'template'   => $template_id,
			'raw_phone'  => $raw_phone,
			'normalized' => $phone,
			'chat_id'    => $chat_id,
		) );
		return $this->send( $chat_id, $message );
	}

	public function get_templates() {
		$saved = get_option( 'openwa_message_templates', array() );
		return $saved;
	}

	public function get_template( $key ) {
		$templates = $this->get_templates();
		return isset( $templates[ $key ] ) ? $templates[ $key ] : false;
	}

	public function save_template( $key, $name, $message, $enabled = true ) {
		$templates = get_option( 'openwa_message_templates', array() );
		$templates[ $key ] = array(
			'name'    => sanitize_text_field( $name ),
			'message' => $message,
			'enabled' => (bool) $enabled,
		);
		update_option( 'openwa_message_templates', $templates );
	}

	public function parse_template( $template, $data ) {
		$search = array();
		$replace = array();
		foreach ( $data as $key => $value ) {
			$search[] = '{' . $key . '}';
			$replace[] = $value;
		}
		return str_replace( $search, $replace, $template );
	}

	public function normalize_phone( $phone ) {
		$original = $phone;
		$phone = trim( $phone );
		$phone = preg_replace( '/[^0-9+]/', '', $phone );
		if ( empty( $phone ) ) {
			return '';
		}

		$settings = get_option( 'openwa_settings', array() );
		$default_code = isset( $settings['default_code'] ) ? trim( $settings['default_code'] ) : '';
		$default_code = preg_replace( '/[^0-9]/', '', $default_code );

		if ( 0 === strpos( $phone, '+' ) ) {
			$phone = ltrim( $phone, '+' );
			if ( ! empty( $default_code ) && 0 !== strpos( $phone, $default_code ) ) {
				OpenWA_Logger::warning( 'Phone with + prefix does not match default country code', array(
					'raw'          => $original,
					'cleaned'      => $phone,
					'default_code' => $default_code,
				) );
			}
			return '+' . $phone;
		}

		if ( 0 === strpos( $phone, '00' ) ) {
			$phone = substr( $phone, 2 );
			if ( ! empty( $default_code ) && 0 !== strpos( $phone, $default_code ) ) {
				OpenWA_Logger::warning( 'Phone with 00 prefix does not match default country code', array(
					'raw'          => $original,
					'cleaned'      => $phone,
					'default_code' => $default_code,
				) );
			}
			return '+' . $phone;
		}

		if ( ! empty( $default_code ) ) {
			if ( 0 === strpos( $phone, $default_code ) ) {
				return '+' . $phone;
			}

			if ( 0 === strpos( $phone, '0' ) ) {
				$phone = substr( $phone, 1 );
				return '+' . $default_code . $phone;
			}

			if ( strlen( $phone ) < 10 ) {
				return '+' . $default_code . $phone;
			}

			OpenWA_Logger::warning( 'Phone longer than 10 digits without known prefix, assuming full number', array(
				'raw'          => $original,
				'cleaned'      => $phone,
				'default_code' => $default_code,
			) );
		} else {
			if ( 0 === strpos( $phone, '0' ) ) {
				OpenWA_Logger::warning( 'Phone starts with 0 but no default country code set', array(
					'raw'     => $original,
					'cleaned' => $phone,
				) );
			}
		}

		return '+' . $phone;
	}

	public function format_chat_id( $phone ) {
		$phone = ltrim( $phone, '+' );
		$phone = preg_replace( '/[^0-9]/', '', $phone );
		if ( empty( $phone ) ) {
			return '';
		}
		return $phone . '@c.us';
	}
}
