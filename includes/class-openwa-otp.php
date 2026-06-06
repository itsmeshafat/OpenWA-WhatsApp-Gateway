<?php
defined( 'ABSPATH' ) || exit;

class OpenWA_OTP {

	private static $otp_length = 6;
	private static $otp_expiry = 300;

	public static function init() {
		$settings = get_option( 'openwa_settings', array() );
		if ( isset( $settings['enable_otp'] ) && 'yes' === $settings['enable_otp'] ) {
			add_filter( 'authenticate', array( __CLASS__, 'handle_otp_login' ), 30, 3 );
			add_action( 'login_form', array( __CLASS__, 'add_otp_field' ) );
			add_action( 'wp_ajax_nopriv_openwa_send_otp', array( __CLASS__, 'ajax_send_otp' ) );
			add_action( 'wp_ajax_openwa_send_otp', array( __CLASS__, 'ajax_send_otp' ) );
			add_action( 'wp_ajax_nopriv_openwa_verify_otp', array( __CLASS__, 'ajax_verify_otp' ) );
			add_action( 'wp_ajax_openwa_verify_otp', array( __CLASS__, 'ajax_verify_otp' ) );
		}
	}

	public static function generate_otp() {
		return str_pad( (string) wp_rand( 0, pow( 10, self::$otp_length ) - 1 ), self::$otp_length, '0', STR_PAD_LEFT );
	}

	public static function send_otp( $phone, $otp ) {
		$message = new OpenWA_Message();
		$phone = $message->normalize_phone( $phone );
		if ( empty( $phone ) ) {
			return new WP_Error( 'invalid_phone', __( 'Invalid phone number for OTP.', 'openwa-whatsapp-gateway' ) );
		}
		$template = $message->get_template( 'otp' );
		if ( ! $template || empty( $template['enabled'] ) ) {
			$text = sprintf(
				/* translators: %s: OTP code */
				__( 'Your OTP is: %s. It expires in 5 minutes. Do not share this code.', 'openwa-whatsapp-gateway' ),
				$otp
			);
			return $message->send( $message->format_chat_id( $phone ), $text );
		}
		return $message->send_notification( $phone, 'otp', array( 'otp' => $otp ) );
	}

	public static function handle_otp_login( $user, $username, $password ) {
		if ( is_wp_error( $user ) || null === $user ) {
			return $user;
		}

		$otp_enabled = get_user_meta( $user->ID, 'openwa_otp_enabled', true );
		if ( 'yes' !== $otp_enabled ) {
			return $user;
		}

		if ( isset( $_POST['openwa_otp_code'] ) ) {
			$submitted_otp = sanitize_text_field( $_POST['openwa_otp_code'] );
			$stored_otp = get_transient( 'openwa_otp_' . $user->ID );

			if ( $submitted_otp === $stored_otp ) {
				delete_transient( 'openwa_otp_' . $user->ID );
				return $user;
			}
			return new WP_Error( 'invalid_otp', __( 'Invalid or expired OTP code.', 'openwa-whatsapp-gateway' ) );
		}

		$phone = get_user_meta( $user->ID, 'billing_phone', true );
		if ( empty( $phone ) ) {
			return new WP_Error( 'no_phone', __( 'No phone number on file. Contact support.', 'openwa-whatsapp-gateway' ) );
		}

		$otp = self::generate_otp();
		set_transient( 'openwa_otp_' . $user->ID, $otp, self::$otp_expiry );
		self::send_otp( $phone, $otp );

		return new WP_Error( 'otp_sent', __( 'An OTP has been sent to your phone. Please enter it below.', 'openwa-whatsapp-gateway' ) );
	}

	public static function add_otp_field() {
		?>
		<p>
			<label for="openwa_otp_code">
				<?php esc_html_e( 'OTP Code', 'openwa-whatsapp-gateway' ); ?><br>
				<input type="text" name="openwa_otp_code" id="openwa_otp_code" class="input" size="20" autocomplete="off" />
			</label>
		</p>
		<?php
	}

	public static function ajax_send_otp() {
		check_ajax_referer( 'openwa_otp_nonce', 'nonce' );

		$phone = isset( $_POST['phone'] ) ? sanitize_text_field( $_POST['phone'] ) : '';
		if ( empty( $phone ) ) {
			wp_send_json_error( array( 'message' => __( 'Phone number is required.', 'openwa-whatsapp-gateway' ) ) );
		}

		$otp = self::generate_otp();
		$phone_hash = wp_hash( $phone );
		set_transient( 'openwa_otp_' . $phone_hash, $otp, self::$otp_expiry );
		$result = self::send_otp( $phone, $otp );

		if ( is_wp_error( $result ) ) {
			OpenWA_Logger::error( 'OTP send failed', array( 'error' => $result->get_error_message() ) );
			wp_send_json_error( array( 'message' => __( 'Failed to send OTP. Please try again.', 'openwa-whatsapp-gateway' ) ) );
		}

		wp_send_json_success( array( 'message' => __( 'OTP sent successfully.', 'openwa-whatsapp-gateway' ) ) );
	}

	public static function ajax_verify_otp() {
		check_ajax_referer( 'openwa_otp_nonce', 'nonce' );

		$phone = isset( $_POST['phone'] ) ? sanitize_text_field( $_POST['phone'] ) : '';
		$otp   = isset( $_POST['otp'] ) ? sanitize_text_field( $_POST['otp'] ) : '';

		if ( empty( $phone ) || empty( $otp ) ) {
			wp_send_json_error( array( 'message' => __( 'Phone and OTP are required.', 'openwa-whatsapp-gateway' ) ) );
		}

		$phone_hash = wp_hash( $phone );
		$stored_otp = get_transient( 'openwa_otp_' . $phone_hash );

		if ( $otp === $stored_otp ) {
			delete_transient( 'openwa_otp_' . $phone_hash );
			WC()->session->set( 'openwa_phone_verified', $phone );
			wp_send_json_success( array( 'message' => __( 'Phone verified successfully.', 'openwa-whatsapp-gateway' ) ) );
		}

		wp_send_json_error( array( 'message' => __( 'Invalid or expired OTP.', 'openwa-whatsapp-gateway' ) ) );
	}
}
