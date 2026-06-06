<?php
defined( 'ABSPATH' ) || exit;

class OpenWA_API {

	private $base_url;
	private $api_key;

	public function __construct() {
		$settings = get_option( 'openwa_settings', array() );
		$this->base_url = isset( $settings['base_url'] ) ? untrailingslashit( $settings['base_url'] ) : '';
		$this->api_key  = isset( $settings['api_key'] ) ? $settings['api_key'] : '';
	}

	public function is_configured() {
		return ! empty( $this->base_url ) && ! empty( $this->api_key );
	}

	public function get_base_url() {
		return $this->base_url;
	}

	private function get_headers() {
		return array(
			'X-API-Key'    => $this->api_key,
			'Content-Type' => 'application/json',
		);
	}

	public function request( $method, $endpoint, $body = null ) {
		if ( ! $this->is_configured() ) {
			return new WP_Error( 'not_configured', __( 'OpenWA is not configured.', 'openwa-whatsapp-gateway' ) );
		}

		$url = $this->base_url . '/api' . $endpoint;
		$args = array(
			'method'  => strtoupper( $method ),
			'headers' => $this->get_headers(),
			'timeout' => 30,
		);

		if ( null !== $body ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			OpenWA_Logger::error( 'API request failed', array(
				'endpoint' => $endpoint,
				'error'    => $response->get_error_message(),
			) );
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$data = json_decode( $response_body, true );

		if ( $status_code >= 400 ) {
			$error_message = __( 'Unknown error', 'openwa-whatsapp-gateway' );

			if ( isset( $data['error']['message'] ) ) {
				$error_message = $data['error']['message'];
			} elseif ( isset( $data['message'] ) ) {
				$error_message = $data['message'];
			} elseif ( isset( $data['error'] ) && is_string( $data['error'] ) ) {
				$error_message = $data['error'];
			}

			OpenWA_Logger::error( 'API returned error', array(
				'endpoint' => $endpoint,
				'status'   => $status_code,
				'message'  => $error_message,
				'response' => mb_substr( $response_body, 0, 500 ),
			) );
			return new WP_Error( 'api_error', $error_message );
		}

		return $data;
	}

	public function validate_connection() {
		$result = $this->request( 'GET', '/health' );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return isset( $result['status'] ) && 'ok' === $result['status'];
	}

	public function get_sessions() {
		return $this->request( 'GET', '/sessions' );
	}

	public function create_session( $name, $config = array() ) {
		$body = array( 'name' => sanitize_text_field( $name ) );
		if ( ! empty( $config ) ) {
			$body['config'] = $config;
		}
		return $this->request( 'POST', '/sessions', $body );
	}

	public function get_session( $session_id ) {
		return $this->request( 'GET', '/sessions/' . $session_id );
	}

	public function start_session( $session_id ) {
		return $this->request( 'POST', '/sessions/' . $session_id . '/start' );
	}

	public function stop_session( $session_id ) {
		return $this->request( 'POST', '/sessions/' . $session_id . '/stop' );
	}

	public function delete_session( $session_id ) {
		return $this->request( 'DELETE', '/sessions/' . $session_id );
	}

	public function get_session_qr( $session_id ) {
		return $this->request( 'GET', '/sessions/' . $session_id . '/qr' );
	}

	public function send_text_message( $session_id, $chat_id, $text ) {
		$body = array(
			'chatId' => $chat_id,
			'text'   => mb_substr( $text, 0, 4096 ),
		);
		return $this->request( 'POST', '/sessions/' . $session_id . '/messages/send-text', $body );
	}

	public function send_bulk_messages( $session_id, $messages, $options = array() ) {
		$body = array(
			'messages' => $messages,
		);
		if ( ! empty( $options ) ) {
			$body['options'] = $options;
		}
		return $this->request( 'POST', '/sessions/' . $session_id . '/messages/send-bulk', $body );
	}

	public function check_number_exists( $session_id, $number ) {
		return $this->request( 'GET', '/sessions/' . $session_id . '/contacts/check/' . $number );
	}
}
