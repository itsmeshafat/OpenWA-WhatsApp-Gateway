<div class="wrap">
	<h1><?php esc_html_e( 'OpenWA Settings', 'openwa-whatsapp-gateway' ); ?></h1>

	<form method="post" action="options.php">
		<?php
		settings_fields( 'openwa_settings_group' );
		do_settings_sections( 'openwa-settings' );
		submit_button();
		?>
	</form>

	<hr>

	<h2><?php esc_html_e( 'How to Get Started', 'openwa-whatsapp-gateway' ); ?></h2>
	<ol>
		<li>
			<strong><?php esc_html_e( 'Set up OpenWA Server', 'openwa-whatsapp-gateway' ); ?></strong>:
			<?php esc_html_e( 'Install and run OpenWA on your server. See', 'openwa-whatsapp-gateway' ); ?>
			<a href="https://github.com/rmyndharis/OpenWA" target="_blank"><?php esc_html_e( 'OpenWA on GitHub', 'openwa-whatsapp-gateway' ); ?></a>.
		</li>
		<li>
			<strong><?php esc_html_e( 'Get an API Key', 'openwa-whatsapp-gateway' ); ?></strong>:
			<?php esc_html_e( 'Create an API key in OpenWA dashboard with at least operator role.', 'openwa-whatsapp-gateway' ); ?>
		</li>
		<li>
			<strong><?php esc_html_e( 'Configure Connection', 'openwa-whatsapp-gateway' ); ?></strong>:
			<?php esc_html_e( 'Enter your OpenWA server URL and API key above, then click Test Connection.', 'openwa-whatsapp-gateway' ); ?>
		</li>
		<li>
			<strong><?php esc_html_e( 'Create a WhatsApp Session', 'openwa-whatsapp-gateway' ); ?></strong>:
			<?php esc_html_e( 'Go to the Sessions page to create and connect a WhatsApp session by scanning the QR code.', 'openwa-whatsapp-gateway' ); ?>
		</li>
		<li>
			<strong><?php esc_html_e( 'Set Up Templates', 'openwa-whatsapp-gateway' ); ?></strong>:
			<?php esc_html_e( 'Customize message templates for each order status.', 'openwa-whatsapp-gateway' ); ?>
		</li>
	</ol>
</div>
