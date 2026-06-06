<?php
defined( 'ABSPATH' ) || exit;

class OpenWA_Session {

	private $api;

	public function __construct() {
		$this->api = new OpenWA_API();
	}

	public function get_status() {
		$session_id = $this->get_active_session_id();
		if ( ! $session_id ) {
			return array( 'status' => 'no_session' );
		}
		return $this->api->get_session( $session_id );
	}

	public function get_active_session_id() {
		$settings = get_option( 'openwa_settings', array() );
		return isset( $settings['session_id'] ) ? $settings['session_id'] : '';
	}

	public function get_sessions_list() {
		return $this->api->get_sessions();
	}

	public function create( $name, $config = array() ) {
		$result = $this->api->create_session( $name, $config );
		if ( ! is_wp_error( $result ) && isset( $result['id'] ) ) {
			$settings = get_option( 'openwa_settings', array() );
			$settings['session_id'] = $result['id'];
			update_option( 'openwa_settings', $settings );
		}
		return $result;
	}

	public function start() {
		$session_id = $this->get_active_session_id();
		if ( ! $session_id ) {
			return new WP_Error( 'no_session', __( 'No session configured.', 'openwa-whatsapp-gateway' ) );
		}
		return $this->api->start_session( $session_id );
	}

	public function stop() {
		$session_id = $this->get_active_session_id();
		if ( ! $session_id ) {
			return new WP_Error( 'no_session', __( 'No session configured.', 'openwa-whatsapp-gateway' ) );
		}
		return $this->api->stop_session( $session_id );
	}

	public function delete() {
		$session_id = $this->get_active_session_id();
		if ( ! $session_id ) {
			return new WP_Error( 'no_session', __( 'No session configured.', 'openwa-whatsapp-gateway' ) );
		}
		$result = $this->api->delete_session( $session_id );
		if ( ! is_wp_error( $result ) ) {
			$settings = get_option( 'openwa_settings', array() );
			unset( $settings['session_id'] );
			update_option( 'openwa_settings', $settings );
		}
		return $result;
	}

	public function get_qr() {
		$session_id = $this->get_active_session_id();
		if ( ! $session_id ) {
			return new WP_Error( 'no_session', __( 'No session configured.', 'openwa-whatsapp-gateway' ) );
		}
		$result = $this->api->get_session_qr( $session_id );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return isset( $result['qrCode'] ) ? $result['qrCode'] : '';
	}

	public function get_formatted_status() {
		$info = $this->get_status();
		if ( is_wp_error( $info ) ) {
			return array(
				'status'  => 'error',
				'message' => $info->get_error_message(),
			);
		}
		if ( isset( $info['status'] ) && 'no_session' === $info['status'] ) {
			return array(
				'status'  => 'inactive',
				'message' => __( 'No WhatsApp session configured.', 'openwa-whatsapp-gateway' ),
			);
		}
		return array(
			'status'       => isset( $info['status'] ) ? $info['status'] : 'unknown',
			'phone'        => isset( $info['phone'] ) ? $info['phone'] : '',
			'pushName'     => isset( $info['pushName'] ) ? $info['pushName'] : '',
			'message'      => $this->status_label( $info['status'] ?? 'unknown' ),
		);
	}

	private function status_label( $status ) {
		$labels = array(
			'created'        => __( 'Created', 'openwa-whatsapp-gateway' ),
			'initializing'   => __( 'Initializing...', 'openwa-whatsapp-gateway' ),
			'qr_ready'       => __( 'QR Code Ready - Scan with WhatsApp', 'openwa-whatsapp-gateway' ),
			'authenticating' => __( 'Authenticating...', 'openwa-whatsapp-gateway' ),
			'ready'          => __( 'Connected & Ready', 'openwa-whatsapp-gateway' ),
			'disconnected'   => __( 'Disconnected', 'openwa-whatsapp-gateway' ),
			'failed'         => __( 'Failed', 'openwa-whatsapp-gateway' ),
		);
		return isset( $labels[ $status ] ) ? $labels[ $status ] : ucfirst( $status );
	}
}
