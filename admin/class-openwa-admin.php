<?php
defined( 'ABSPATH' ) || exit;

class OpenWA_Admin {

	private static $instance = null;

	public static function init() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
	}

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'wp_ajax_openwa_test_connection', array( $this, 'ajax_test_connection' ) );
		add_action( 'wp_ajax_openwa_send_test_message', array( $this, 'ajax_send_test_message' ) );
		add_action( 'wp_ajax_openwa_create_session', array( $this, 'ajax_create_session' ) );
		add_action( 'wp_ajax_openwa_start_session', array( $this, 'ajax_start_session' ) );
		add_action( 'wp_ajax_openwa_stop_session', array( $this, 'ajax_stop_session' ) );
		add_action( 'wp_ajax_openwa_delete_session', array( $this, 'ajax_delete_session' ) );
		add_action( 'wp_ajax_openwa_get_qr', array( $this, 'ajax_get_qr' ) );
		add_action( 'wp_ajax_openwa_refresh_session', array( $this, 'ajax_refresh_session' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_order_meta_box' ) );
		add_action( 'wp_ajax_openwa_send_order_message', array( $this, 'ajax_send_order_message' ) );
		add_action( 'wp_ajax_openwa_send_invoice', array( $this, 'ajax_send_invoice' ) );
		add_filter( 'woocommerce_order_actions', array( $this, 'register_order_actions' ), 10, 2 );
		add_action( 'woocommerce_order_action_openwa_send_invoice', array( $this, 'handle_order_action_invoice' ) );
		add_action( 'woocommerce_order_action_openwa_resend_notification', array( $this, 'handle_order_action_resend' ) );
	}

	public function register_admin_menu() {
		add_menu_page(
			__( 'OpenWA Gateway', 'openwa-whatsapp-gateway' ),
			__( 'OpenWA', 'openwa-whatsapp-gateway' ),
			'manage_options',
			'openwa',
			array( $this, 'render_dashboard' ),
			'dashicons-format-chat',
			55
		);

		add_action( 'admin_head', array( $this, 'admin_menu_icon' ) );

		add_submenu_page(
			'openwa',
			__( 'Settings', 'openwa-whatsapp-gateway' ),
			__( 'Settings', 'openwa-whatsapp-gateway' ),
			'manage_options',
			'openwa-settings',
			array( $this, 'render_settings' )
		);

		add_submenu_page(
			'openwa',
			__( 'Sessions', 'openwa-whatsapp-gateway' ),
			__( 'Sessions', 'openwa-whatsapp-gateway' ),
			'manage_options',
			'openwa-sessions',
			array( $this, 'render_sessions' )
		);

		add_submenu_page(
			'openwa',
			__( 'Message Templates', 'openwa-whatsapp-gateway' ),
			__( 'Templates', 'openwa-whatsapp-gateway' ),
			'manage_options',
			'openwa-templates',
			array( $this, 'render_templates' )
		);

		add_submenu_page(
			'openwa',
			__( 'Test Message', 'openwa-whatsapp-gateway' ),
			__( 'Test Message', 'openwa-whatsapp-gateway' ),
			'manage_options',
			'openwa-test',
			array( $this, 'render_test' )
		);

		add_submenu_page(
			'openwa',
			__( 'Logs', 'openwa-whatsapp-gateway' ),
			__( 'Logs', 'openwa-whatsapp-gateway' ),
			'manage_options',
			'openwa-logs',
			array( $this, 'render_logs' )
		);
	}

	public function enqueue_admin_assets( $hook ) {
		$is_openwa_page   = strpos( $hook, 'openwa' ) !== false;
		$is_legacy_order  = 'post.php' === $hook;
		$is_hpos_order    = 'woocommerce_page_wc-orders' === $hook;

		if ( ! $is_openwa_page && ! $is_legacy_order && ! $is_hpos_order ) {
			return;
		}

		wp_enqueue_style(
			'openwa-admin',
			OPENWA_PLUGIN_URL . 'admin/css/openwa-admin.css',
			array(),
			OPENWA_VERSION
		);

		wp_enqueue_script(
			'openwa-admin',
			OPENWA_PLUGIN_URL . 'admin/js/openwa-admin.js',
			array( 'jquery' ),
			OPENWA_VERSION,
			true
		);

		wp_localize_script( 'openwa-admin', 'openwaAdmin', array(
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'openwa_admin_nonce' ),
			'i18n'     => array(
				'sending'    => __( 'Sending...', 'openwa-whatsapp-gateway' ),
				'sent'       => __( 'Message sent!', 'openwa-whatsapp-gateway' ),
				'failed'     => __( 'Failed to send.', 'openwa-whatsapp-gateway' ),
				'connecting' => __( 'Testing connection...', 'openwa-whatsapp-gateway' ),
				'connected'  => __( 'Connection successful!', 'openwa-whatsapp-gateway' ),
			),
		) );
	}

	public function register_settings() {
		register_setting( 'openwa_settings_group', 'openwa_settings', array(
			'sanitize_callback' => array( $this, 'sanitize_settings' ),
		) );

		add_settings_section(
			'openwa_connection_section',
			__( 'OpenWA Connection', 'openwa-whatsapp-gateway' ),
			'__return_empty_string',
			'openwa-settings'
		);

		add_settings_field(
			'openwa_base_url',
			__( 'OpenWA Server URL', 'openwa-whatsapp-gateway' ),
			array( $this, 'field_base_url' ),
			'openwa-settings',
			'openwa_connection_section'
		);

		add_settings_field(
			'openwa_api_key',
			__( 'API Key', 'openwa-whatsapp-gateway' ),
			array( $this, 'field_api_key' ),
			'openwa-settings',
			'openwa_connection_section'
		);

		add_settings_section(
			'openwa_invoice_section',
			__( 'Invoice / Document Settings', 'openwa-whatsapp-gateway' ),
			'__return_empty_string',
			'openwa-settings'
		);

		add_settings_field(
			'openwa_site_url',
			__( 'Site URL (for OpenWA)', 'openwa-whatsapp-gateway' ),
			array( $this, 'field_site_url' ),
			'openwa-settings',
			'openwa_invoice_section'
		);

		add_settings_section(
			'openwa_notification_section',
			__( 'Notification Settings', 'openwa-whatsapp-gateway' ),
			'__return_empty_string',
			'openwa-settings'
		);

		add_settings_field(
			'openwa_admin_phone',
			__( 'Admin Phone Number', 'openwa-whatsapp-gateway' ),
			array( $this, 'field_admin_phone' ),
			'openwa-settings',
			'openwa_notification_section'
		);

		add_settings_field(
			'openwa_default_code',
			__( 'Default Country Code', 'openwa-whatsapp-gateway' ),
			array( $this, 'field_default_code' ),
			'openwa-settings',
			'openwa_notification_section'
		);

		add_settings_section(
			'openwa_otp_section',
			__( 'OTP Settings', 'openwa-whatsapp-gateway' ),
			'__return_empty_string',
			'openwa-settings'
		);

		add_settings_field(
			'openwa_enable_otp',
			__( 'Enable OTP Login', 'openwa-whatsapp-gateway' ),
			array( $this, 'field_enable_otp' ),
			'openwa-settings',
			'openwa_otp_section'
		);

		add_settings_section(
			'openwa_digest_section',
			__( 'Daily Digest', 'openwa-whatsapp-gateway' ),
			'__return_empty_string',
			'openwa-settings'
		);

		add_settings_field(
			'openwa_enable_digest',
			__( 'Enable Daily Digest', 'openwa-whatsapp-gateway' ),
			array( $this, 'field_enable_digest' ),
			'openwa-settings',
			'openwa_digest_section'
		);
	}

	public function sanitize_settings( $input ) {
		$output = get_option( 'openwa_settings', array() );

		if ( isset( $input['base_url'] ) ) {
			$output['base_url'] = esc_url_raw( untrailingslashit( $input['base_url'] ), array( 'http', 'https' ) );
		}
		if ( isset( $input['api_key'] ) ) {
			$output['api_key'] = sanitize_text_field( $input['api_key'] );
		}
		if ( isset( $input['session_id'] ) ) {
			$output['session_id'] = sanitize_text_field( $input['session_id'] );
		}
		if ( isset( $input['admin_phone'] ) ) {
			$output['admin_phone'] = preg_replace( '/[^0-9]/', '', $input['admin_phone'] );
		}
		if ( isset( $input['default_code'] ) ) {
			$output['default_code'] = preg_replace( '/[^0-9]/', '', $input['default_code'] );
		}
		if ( isset( $input['site_url'] ) ) {
			$output['site_url'] = esc_url_raw( untrailingslashit( $input['site_url'] ), array( 'http', 'https' ) );
		}
		if ( isset( $input['enable_otp'] ) ) {
			$output['enable_otp'] = 'yes';
		} else {
			$output['enable_otp'] = 'no';
		}
		if ( isset( $input['enable_digest'] ) ) {
			$output['enable_digest'] = 'yes';
		} else {
			$output['enable_digest'] = 'no';
		}

		return $output;
	}

	public function field_base_url() {
		$settings = get_option( 'openwa_settings', array() );
		$value = isset( $settings['base_url'] ) ? $settings['base_url'] : '';
		?>
		<input type="url" name="openwa_settings[base_url]" value="<?php echo esc_attr( $value ); ?>"
			class="regular-text" placeholder="https://your-openwa-server.com" />
		<p class="description">
			<?php esc_html_e( 'The URL where your OpenWA server is running (e.g., https://wa.example.com:2785).', 'openwa-whatsapp-gateway' ); ?>
		</p>
		<?php
	}

	public function field_api_key() {
		$settings = get_option( 'openwa_settings', array() );
		$value = isset( $settings['api_key'] ) ? $settings['api_key'] : '';
		?>
		<input type="password" name="openwa_settings[api_key]" value="<?php echo esc_attr( $value ); ?>"
			class="regular-text" />
		<button type="button" class="button openwa-toggle-key"><?php esc_html_e( 'Show', 'openwa-whatsapp-gateway' ); ?></button>
		<button type="button" class="button openwa-test-connection"><?php esc_html_e( 'Test Connection', 'openwa-whatsapp-gateway' ); ?></button>
		<span class="openwa-connection-status"></span>
		<p class="description">
			<?php esc_html_e( 'Your OpenWA API key starting with owa_k1_.', 'openwa-whatsapp-gateway' ); ?>
		</p>
		<?php
	}

	public function field_admin_phone() {
		$settings = get_option( 'openwa_settings', array() );
		$value = isset( $settings['admin_phone'] ) ? $settings['admin_phone'] : '';
		?>
		<input type="text" name="openwa_settings[admin_phone]" value="<?php echo esc_attr( $value ); ?>"
			class="regular-text" placeholder="628123456789" />
		<p class="description">
			<?php esc_html_e( 'Phone number to receive admin notifications. Include country code without + or 00.', 'openwa-whatsapp-gateway' ); ?>
		</p>
		<?php
	}

	public function field_default_code() {
		$settings = get_option( 'openwa_settings', array() );
		$value = isset( $settings['default_code'] ) ? $settings['default_code'] : '';
		?>
		<input type="text" name="openwa_settings[default_code]" value="<?php echo esc_attr( $value ); ?>"
			class="small-text" placeholder="62" maxlength="5" />
		<p class="description">
			<?php esc_html_e( 'Default country code for phone numbers without one (e.g., 62 for Indonesia). Numbers shorter than 10 digits will get this prefix.', 'openwa-whatsapp-gateway' ); ?>
		</p>
		<?php
	}

	public function field_site_url() {
		$settings = get_option( 'openwa_settings', array() );
		$value = isset( $settings['site_url'] ) ? $settings['site_url'] : '';
		?>
		<input type="url" name="openwa_settings[site_url]" value="<?php echo esc_attr( $value ); ?>"
			class="regular-text" placeholder="http://host.docker.internal:8080" />
		<p class="description">
			<?php esc_html_e( 'Override the PDF URL that OpenWA will fetch (e.g., for Docker setups where OpenWA cannot reach the WordPress site URL directly). Leave empty to use the default WordPress upload URL.', 'openwa-whatsapp-gateway' ); ?>
		</p>
		<p class="description" style="color:#856404;background:#fff3cd;padding:6px 10px;border-radius:4px;margin-top:6px;">
			<strong><?php esc_html_e( 'Local dev with self-signed SSL:', 'openwa-whatsapp-gateway' ); ?></strong>
			<?php esc_html_e( 'OpenWA needs to fetch the PDF via HTTPS but Node.js rejects self-signed certificates. To bypass, run OpenWA with:', 'openwa-whatsapp-gateway' ); ?>
			<code style="background:rgba(0,0,0,0.06);padding:1px 4px;border-radius:2px;">NODE_TLS_REJECT_UNAUTHORIZED=0 npm run dev</code>
		</p>
		<?php
	}

	public function field_enable_otp() {
		$settings = get_option( 'openwa_settings', array() );
		$checked = isset( $settings['enable_otp'] ) && 'yes' === $settings['enable_otp'] ? 'checked' : '';
		?>
		<label>
			<input type="checkbox" name="openwa_settings[enable_otp]" value="yes" <?php echo $checked; ?> />
			<?php esc_html_e( 'Send OTP via WhatsApp for login verification', 'openwa-whatsapp-gateway' ); ?>
		</label>
		<?php
	}

	public function field_enable_digest() {
		$settings = get_option( 'openwa_settings', array() );
		$checked = isset( $settings['enable_digest'] ) && 'yes' === $settings['enable_digest'] ? 'checked' : '';
		?>
		<label>
			<input type="checkbox" name="openwa_settings[enable_digest]" value="yes" <?php echo $checked; ?> />
			<?php esc_html_e( 'Send daily order summary digest to admin', 'openwa-whatsapp-gateway' ); ?>
		</label>
		<?php
	}

	public function admin_menu_icon() {
		$svg = file_get_contents( OPENWA_PLUGIN_DIR . 'assets/logo.svg' );
		if ( ! $svg ) {
			return;
		}
		$base64 = base64_encode( $svg );
		?>
		<style>
		#adminmenu .toplevel_page_openwa .wp-menu-image img {
			display: none;
		}
		#adminmenu .toplevel_page_openwa .wp-menu-image {
			background: url('data:image/svg+xml;base64,<?php echo $base64; ?>') no-repeat center !important;
			background-size: 20px auto !important;
		}
		#adminmenu .toplevel_page_openwa .wp-menu-image::before {
			display: none !important;
		}
		</style>
		<?php
	}

	public function render_dashboard() {
		$session = new OpenWA_Session();
		$status = $session->get_formatted_status();
		if ( ! is_wp_error( $status ) && isset( $status['status'] ) ) {
			$connection_ok = 'ready' === $status['status'];
		} else {
			$connection_ok = false;
		}
		$connected = $connection_ok;
		require OPENWA_PLUGIN_DIR . 'admin/views/dashboard-page.php';
	}

	public function render_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'openwa-whatsapp-gateway' ) );
		}
		require OPENWA_PLUGIN_DIR . 'admin/views/settings-page.php';
	}

	public function render_sessions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'openwa-whatsapp-gateway' ) );
		}
		$session = new OpenWA_Session();
		require OPENWA_PLUGIN_DIR . 'admin/views/sessions-page.php';
	}

	public function render_templates() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'openwa-whatsapp-gateway' ) );
		}

		if ( isset( $_POST['save_templates'] ) && check_admin_referer( 'openwa_save_templates' ) ) {
			$this->save_templates();
		}

		$message = new OpenWA_Message();
		$templates = $message->get_templates();
		$defaults = $this->get_default_templates();
		$templates = wp_parse_args( $templates, $defaults );
		require OPENWA_PLUGIN_DIR . 'admin/views/templates-page.php';
	}

	public function render_test() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'openwa-whatsapp-gateway' ) );
		}
		require OPENWA_PLUGIN_DIR . 'admin/views/test-page.php';
	}

	public function render_logs() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'openwa-whatsapp-gateway' ) );
		}

		if ( isset( $_POST['clear_logs'] ) && check_admin_referer( 'openwa_clear_logs' ) ) {
			OpenWA_Logger::clear_all();
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Logs cleared.', 'openwa-whatsapp-gateway' ) . '</p></div>';
		}

		$level = isset( $_GET['log_level'] ) ? sanitize_text_field( $_GET['log_level'] ) : '';
		$logs = OpenWA_Logger::get_logs( $level, 200, 0 );
		require OPENWA_PLUGIN_DIR . 'admin/views/logs-page.php';
	}

	private function save_templates() {
		$message = new OpenWA_Message();
		$defaults = $this->get_default_templates();

		if ( ! isset( $_POST['templates'] ) || ! is_array( $_POST['templates'] ) ) {
			return;
		}

		foreach ( $_POST['templates'] as $key => $data ) {
			if ( ! isset( $defaults[ $key ] ) ) {
				continue;
			}
			$name = isset( $data['name'] ) ? sanitize_text_field( wp_unslash( $data['name'] ) ) : $defaults[ $key ]['name'];
			$msg  = isset( $data['message'] ) ? sanitize_textarea_field( wp_unslash( $data['message'] ) ) : $defaults[ $key ]['message'];
			$enabled = isset( $data['enabled'] );
			$message->save_template( $key, $name, $msg, $enabled );
		}

		echo '<div class="notice notice-success"><p>' . esc_html__( 'Templates saved.', 'openwa-whatsapp-gateway' ) . '</p></div>';
	}

	public function get_default_templates() {
		return openwa_get_default_templates();
	}

	public function add_order_meta_box() {
		$screen = wc_get_page_screen_id( 'shop-order' );
		add_meta_box(
			'openwa_order_messages',
			__( 'OpenWA WhatsApp', 'openwa-whatsapp-gateway' ),
			array( $this, 'render_order_meta_box' ),
			$screen,
			'side',
			'default'
		);
	}

	public function render_order_meta_box( $post_or_order ) {
		$order = ( $post_or_order instanceof WP_Post )
			? wc_get_order( $post_or_order->ID )
			: $post_or_order;

		if ( ! $order ) {
			echo '<p>' . esc_html__( 'Order not found.', 'openwa-whatsapp-gateway' ) . '</p>';
			return;
		}

		$phone = $order->get_billing_phone();
		$name  = $order->get_billing_first_name();
		$last_sent = $order->get_meta( '_openwa_last_sent' );
		$invoice  = new OpenWA_Invoice();
		$history  = $invoice->get_history( $order );
		wp_nonce_field( 'openwa_order_meta', 'openwa_order_nonce' );

		$session = new OpenWA_Session();
		$status = $session->get_formatted_status();
		$connected = ! is_wp_error( $status ) && isset( $status['status'] ) && 'ready' === $status['status'];
		?>
		<p style="margin:0 0 6px;">
			<span class="openwa-session-status status-<?php echo esc_attr( $connected ? 'ready' : 'inactive' ); ?>" style="font-size:11px;">
				<?php echo $connected ? esc_html__( 'WhatsApp Connected', 'openwa-whatsapp-gateway' ) : esc_html__( 'WhatsApp Not Connected', 'openwa-whatsapp-gateway' ); ?>
			</span>
		</p>

		<p>
			<strong><?php esc_html_e( 'Phone:', 'openwa-whatsapp-gateway' ); ?></strong>
			<?php echo esc_html( $phone ? $phone : __( 'None', 'openwa-whatsapp-gateway' ) ); ?>
			<br>
			<strong><?php esc_html_e( 'Customer:', 'openwa-whatsapp-gateway' ); ?></strong>
			<?php echo esc_html( $name ); ?>
		</p>

		<?php if ( $last_sent ) : ?>
			<p style="font-size:11px;color:#666;">
				<?php esc_html_e( 'Last sent:', 'openwa-whatsapp-gateway' ); ?>
				<?php echo esc_html( $last_sent ); ?>
			</p>
		<?php endif; ?>

		<hr style="margin:8px 0;">

		<p>
			<textarea id="openwa-custom-message" class="widefat" rows="2"
				placeholder="<?php esc_attr_e( 'Custom message...', 'openwa-whatsapp-gateway' ); ?>"></textarea>
		</p>
		<p style="display:flex;gap:4px;flex-wrap:wrap;">
			<button type="button" class="button openwa-send-order-msg"
				data-order-id="<?php echo esc_attr( $order->get_id() ); ?>">
				<?php esc_html_e( 'Send Message', 'openwa-whatsapp-gateway' ); ?>
			</button>
			<button type="button" class="button openwa-send-invoice"
				data-order-id="<?php echo esc_attr( $order->get_id() ); ?>">
				<?php esc_html_e( 'Send Invoice', 'openwa-whatsapp-gateway' ); ?>
			</button>
			<span class="openwa-order-msg-status"></span>
		</p>

		<?php if ( ! empty( $history ) ) : ?>
			<hr style="margin:8px 0;">
			<h4 style="margin:4px 0;font-size:12px;">
				<?php esc_html_e( 'Message History', 'openwa-whatsapp-gateway' ); ?>
			</h4>
			<ul style="margin:0;padding:0;font-size:11px;max-height:150px;overflow-y:auto;">
				<?php foreach ( array_reverse( $history ) as $entry ) : ?>
					<li style="border-bottom:1px solid #eee;padding:3px 0;list-style:none;">
						<strong><?php echo esc_html( $entry['time'] ); ?></strong>
						<br><?php echo esc_html( $entry['type'] ); ?>
						<?php if ( ! empty( $entry['phone'] ) ) : ?>
							&rarr; <?php echo esc_html( $entry['phone'] ); ?>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( ! $connected ) : ?>
			<hr style="margin:8px 0;">
			<p style="font-size:11px;color:#dc3232;">
				<?php esc_html_e( 'WhatsApp session not connected. Go to', 'openwa-whatsapp-gateway' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=openwa-sessions' ) ); ?>">
					<?php esc_html_e( 'Sessions', 'openwa-whatsapp-gateway' ); ?>
				</a>
				<?php esc_html_e( 'to connect.', 'openwa-whatsapp-gateway' ); ?>
			</p>
		<?php endif; ?>
		<?php
	}

	public function ajax_test_connection() {
		check_ajax_referer( 'openwa_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'openwa-whatsapp-gateway' ) ) );
		}

		$api = new OpenWA_API();
		$result = $api->validate_connection();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Connected to OpenWA server successfully!', 'openwa-whatsapp-gateway' ) ) );
	}

	public function ajax_send_test_message() {
		check_ajax_referer( 'openwa_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'openwa-whatsapp-gateway' ) ) );
		}

		$phone = isset( $_POST['phone'] ) ? sanitize_text_field( $_POST['phone'] ) : '';
		$text  = isset( $_POST['message'] ) ? sanitize_textarea_field( $_POST['message'] ) : '';

		if ( empty( $phone ) || empty( $text ) ) {
			wp_send_json_error( array( 'message' => __( 'Phone and message are required.', 'openwa-whatsapp-gateway' ) ) );
		}

		$message = new OpenWA_Message();
		$result = $message->send( $message->format_chat_id( $phone ), $text );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Test message sent successfully!', 'openwa-whatsapp-gateway' ) ) );
	}

	public function ajax_create_session() {
		check_ajax_referer( 'openwa_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'openwa-whatsapp-gateway' ) ) );
		}

		$name = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : 'wp-store-' . uniqid();
		$session = new OpenWA_Session();
		$result = $session->create( $name );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'message'   => __( 'Session created!', 'openwa-whatsapp-gateway' ),
			'session'   => $result,
		) );
	}

	public function ajax_start_session() {
		check_ajax_referer( 'openwa_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'openwa-whatsapp-gateway' ) ) );
		}

		$session = new OpenWA_Session();
		$result = $session->start();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Session starting...', 'openwa-whatsapp-gateway' ) ) );
	}

	public function ajax_stop_session() {
		check_ajax_referer( 'openwa_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'openwa-whatsapp-gateway' ) ) );
		}

		$session = new OpenWA_Session();
		$result = $session->stop();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Session stopped.', 'openwa-whatsapp-gateway' ) ) );
	}

	public function ajax_delete_session() {
		check_ajax_referer( 'openwa_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'openwa-whatsapp-gateway' ) ) );
		}

		$session = new OpenWA_Session();
		$result = $session->delete();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Session deleted.', 'openwa-whatsapp-gateway' ) ) );
	}

	public function ajax_get_qr() {
		check_ajax_referer( 'openwa_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'openwa-whatsapp-gateway' ) ) );
		}

		$session = new OpenWA_Session();
		$qr = $session->get_qr();

		if ( is_wp_error( $qr ) ) {
			wp_send_json_error( array( 'message' => $qr->get_error_message() ) );
		}

		wp_send_json_success( array( 'qr' => $qr, 'message' => __( 'Scan QR with WhatsApp.', 'openwa-whatsapp-gateway' ) ) );
	}

	public function ajax_refresh_session() {
		check_ajax_referer( 'openwa_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'openwa-whatsapp-gateway' ) ) );
		}

		$session = new OpenWA_Session();
		$status = $session->get_formatted_status();

		wp_send_json_success( $status );
	}

	public function ajax_send_order_message() {
		check_ajax_referer( 'openwa_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'openwa-whatsapp-gateway' ) ) );
		}

		$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
		$text     = isset( $_POST['message'] ) ? sanitize_textarea_field( $_POST['message'] ) : '';

		if ( ! $order_id || empty( $text ) ) {
			wp_send_json_error( array( 'message' => __( 'Order ID and message are required.', 'openwa-whatsapp-gateway' ) ) );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_send_json_error( array( 'message' => __( 'Order not found.', 'openwa-whatsapp-gateway' ) ) );
		}

		$phone = $order->get_billing_phone();
		if ( empty( $phone ) ) {
			wp_send_json_error( array( 'message' => __( 'Customer has no phone number.', 'openwa-whatsapp-gateway' ) ) );
		}

		$message = new OpenWA_Message();
		$phone = $message->normalize_phone( $phone );
		if ( empty( $phone ) ) {
			wp_send_json_error( array( 'message' => __( 'Could not normalize phone number. Check the default country code setting.', 'openwa-whatsapp-gateway' ) ) );
		}

		$data = array(
			'order_id'      => $order->get_order_number(),
			'customer_name'  => $order->get_billing_first_name(),
			'order_total'    => html_entity_decode( wp_strip_all_tags( $order->get_formatted_order_total() ), ENT_QUOTES, 'UTF-8' ),
			'site_name'      => get_bloginfo( 'name' ),
		);
		$parsed = $message->parse_template( $text, $data );
		$result = $message->send( $message->format_chat_id( $phone ), $parsed );

		if ( is_wp_error( $result ) ) {
			OpenWA_Logger::error( 'Manual order message failed', array(
				'order_id' => $order_id,
				'phone'    => $phone,
				'error'    => $result->get_error_message(),
			) );
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		OpenWA_Logger::info( 'Manual order message sent', array(
			'order_id' => $order_id,
			'phone'    => $phone,
		) );

		wp_send_json_success( array( 'message' => __( 'Message sent to customer.', 'openwa-whatsapp-gateway' ) ) );
	}

	public function ajax_send_invoice() {
		check_ajax_referer( 'openwa_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'openwa-whatsapp-gateway' ) ) );
		}

		$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
		if ( ! $order_id ) {
			wp_send_json_error( array( 'message' => __( 'Order ID required.', 'openwa-whatsapp-gateway' ) ) );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_send_json_error( array( 'message' => __( 'Order not found.', 'openwa-whatsapp-gateway' ) ) );
		}

		$invoice = new OpenWA_Invoice();
		$result = $invoice->send_invoice( $order );

		if ( is_wp_error( $result ) ) {
			OpenWA_Logger::error( 'Invoice send failed', array(
				'order_id' => $order_id,
				'error'    => $result->get_error_message(),
			) );
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		OpenWA_Logger::info( 'Invoice sent via WhatsApp', array( 'order_id' => $order_id ) );
		wp_send_json_success( array( 'message' => __( 'Invoice sent via WhatsApp.', 'openwa-whatsapp-gateway' ) ) );
	}

	public function register_order_actions( $actions, $order ) {
		$phone = $order ? $order->get_billing_phone() : '';
		if ( ! empty( $phone ) ) {
			$actions['openwa_send_invoice'] = __( 'Send Invoice via WhatsApp', 'openwa-whatsapp-gateway' );
			$actions['openwa_resend_notification'] = __( 'Resend WhatsApp Notification', 'openwa-whatsapp-gateway' );
		}
		return $actions;
	}

	public function handle_order_action_invoice( $order ) {
		if ( ! $order ) {
			return;
		}

		$invoice = new OpenWA_Invoice();
		$result = $invoice->send_invoice( $order );

		if ( is_wp_error( $result ) ) {
			$order->add_order_note( sprintf(
				/* translators: %s: error message */
				__( 'OpenWA: Invoice WhatsApp send failed - %s', 'openwa-whatsapp-gateway' ),
				$result->get_error_message()
			) );
		} else {
			$order->add_order_note( __( 'OpenWA: Invoice sent via WhatsApp.', 'openwa-whatsapp-gateway' ) );
		}

		$order->save();
	}

	public function handle_order_action_resend( $order ) {
		if ( ! $order ) {
			return;
		}

		$status = $order->get_status();
		$template_key = 'order_' . $status;

		$msg = new OpenWA_Message();
		$template = $msg->get_template( $template_key );
		if ( ! $template || empty( $template['enabled'] ) ) {
			$order->add_order_note( sprintf(
				/* translators: %s: template key */
				__( 'OpenWA: No enabled template found for status %s.', 'openwa-whatsapp-gateway' ),
				$status
			) );
			$order->save();
			return;
		}

		$data = array(
			'order_id'       => $order->get_order_number(),
			'order_status'   => wc_get_order_status_name( $status ),
			'order_total'    => html_entity_decode( wp_strip_all_tags( $order->get_formatted_order_total() ), ENT_QUOTES, 'UTF-8' ),
			'customer_name'  => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
			'customer_email' => $order->get_billing_email(),
			'customer_phone' => $order->get_billing_phone(),
			'payment_method' => $order->get_payment_method_title(),
			'site_name'      => get_bloginfo( 'name' ),
		);

		$notifier = new OpenWA_Notifier();
		$phone = $order->get_billing_phone();
		$notifier->try_send_public( $phone, $template_key, $data, 'customer' );

		$order->add_order_note( sprintf(
			/* translators: %s: template key */
			__( 'OpenWA: Notification resent via WhatsApp (%s).', 'openwa-whatsapp-gateway' ),
			$template_key
		) );
		$order->save();
	}
}
