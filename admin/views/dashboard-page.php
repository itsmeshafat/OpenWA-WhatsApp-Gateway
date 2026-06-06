<div class="wrap openwa-dashboard">
	<h1><?php esc_html_e( 'OpenWA - WhatsApp Gateway', 'openwa-whatsapp-gateway' ); ?></h1>

	<div class="openwa-status-cards">
		<div class="openwa-card">
			<h3><?php esc_html_e( 'Connection Status', 'openwa-whatsapp-gateway' ); ?></h3>
			<div class="openwa-status-indicator <?php echo $connection_ok ? 'connected' : 'disconnected'; ?>">
				<span class="dashicons <?php echo $connection_ok ? 'dashicons-yes-alt' : 'dashicons-no-alt'; ?>"></span>
				<?php echo $connection_ok ? esc_html__( 'Connected', 'openwa-whatsapp-gateway' ) : esc_html__( 'Disconnected', 'openwa-whatsapp-gateway' ); ?>
			</div>
			<?php if ( ! $connected ) : ?>
				<p class="description">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=openwa-settings' ) ); ?>">
						<?php esc_html_e( 'Configure connection settings', 'openwa-whatsapp-gateway' ); ?>
					</a>
				</p>
			<?php endif; ?>
		</div>

		<div class="openwa-card">
			<h3><?php esc_html_e( 'WhatsApp Session', 'openwa-whatsapp-gateway' ); ?></h3>
			<?php if ( is_wp_error( $status ) ) : ?>
				<p class="error"><?php echo esc_html( $status->get_error_message() ); ?></p>
			<?php elseif ( isset( $status['status'] ) && 'no_session' === $status['status'] ) : ?>
				<p><?php esc_html_e( 'No session configured.', 'openwa-whatsapp-gateway' ); ?></p>
				<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=openwa-sessions' ) ); ?>" class="button">
					<?php esc_html_e( 'Create Session', 'openwa-whatsapp-gateway' ); ?>
				</a></p>
			<?php else : ?>
				<p>
					<span class="openwa-session-status status-<?php echo esc_attr( $status['status'] ?? 'unknown' ); ?>">
						<?php echo esc_html( $status['message'] ?? $status['status'] ?? __( 'Unknown', 'openwa-whatsapp-gateway' ) ); ?>
					</span>
				</p>
				<?php if ( ! empty( $status['phone'] ) ) : ?>
					<p><strong><?php esc_html_e( 'Phone:', 'openwa-whatsapp-gateway' ); ?></strong> <?php echo esc_html( $status['phone'] ); ?></p>
				<?php endif; ?>
			<?php endif; ?>
		</div>

		<div class="openwa-card">
			<h3><?php esc_html_e( 'Quick Actions', 'openwa-whatsapp-gateway' ); ?></h3>
			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=openwa-sessions' ) ); ?>" class="button">
					<?php esc_html_e( 'Manage Session', 'openwa-whatsapp-gateway' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=openwa-templates' ) ); ?>" class="button">
					<?php esc_html_e( 'Message Templates', 'openwa-whatsapp-gateway' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=openwa-test' ) ); ?>" class="button">
					<?php esc_html_e( 'Send Test', 'openwa-whatsapp-gateway' ); ?>
				</a>
			</p>
		</div>
	</div>

	<div class="openwa-card openwa-shortcodes-ref">
		<h3><?php esc_html_e( 'Available Template Shortcodes', 'openwa-whatsapp-gateway' ); ?></h3>
		<table class="widefat striped">
			<thead><tr><th><?php esc_html_e( 'Shortcode', 'openwa-whatsapp-gateway' ); ?></th><th><?php esc_html_e( 'Description', 'openwa-whatsapp-gateway' ); ?></th></tr></thead>
			<tbody>
				<tr><td><code>{order_id}</code></td><td><?php esc_html_e( 'Order number', 'openwa-whatsapp-gateway' ); ?></td></tr>
				<tr><td><code>{order_status}</code></td><td><?php esc_html_e( 'Order status name', 'openwa-whatsapp-gateway' ); ?></td></tr>
				<tr><td><code>{order_total}</code></td><td><?php esc_html_e( 'Formatted order total', 'openwa-whatsapp-gateway' ); ?></td></tr>
				<tr><td><code>{customer_name}</code></td><td><?php esc_html_e( 'Customer full name', 'openwa-whatsapp-gateway' ); ?></td></tr>
				<tr><td><code>{customer_email}</code></td><td><?php esc_html_e( 'Customer email', 'openwa-whatsapp-gateway' ); ?></td></tr>
				<tr><td><code>{customer_phone}</code></td><td><?php esc_html_e( 'Customer phone', 'openwa-whatsapp-gateway' ); ?></td></tr>
				<tr><td><code>{payment_method}</code></td><td><?php esc_html_e( 'Payment method title', 'openwa-whatsapp-gateway' ); ?></td></tr>
				<tr><td><code>{items}</code></td><td><?php esc_html_e( 'Order items list', 'openwa-whatsapp-gateway' ); ?></td></tr>
				<tr><td><code>{site_name}</code></td><td><?php esc_html_e( 'Site name', 'openwa-whatsapp-gateway' ); ?></td></tr>
				<tr><td><code>{otp}</code></td><td><?php esc_html_e( 'OTP code (OTP template only)', 'openwa-whatsapp-gateway' ); ?></td></tr>
			</tbody>
		</table>
	</div>
</div>
