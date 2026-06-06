<div class="wrap openwa-sessions-page">
	<h1><?php esc_html_e( 'WhatsApp Sessions', 'openwa-whatsapp-gateway' ); ?></h1>

	<div class="openwa-session-controls">
		<button type="button" class="button button-primary openwa-create-session">
			<?php esc_html_e( 'Create New Session', 'openwa-whatsapp-gateway' ); ?>
		</button>
		<button type="button" class="button openwa-start-session">
			<?php esc_html_e( 'Start Session', 'openwa-whatsapp-gateway' ); ?>
		</button>
		<button type="button" class="button openwa-stop-session">
			<?php esc_html_e( 'Stop Session', 'openwa-whatsapp-gateway' ); ?>
		</button>
		<button type="button" class="button openwa-delete-session">
			<?php esc_html_e( 'Delete Session', 'openwa-whatsapp-gateway' ); ?>
		</button>
		<button type="button" class="button openwa-refresh-session">
			<?php esc_html_e( 'Refresh Status', 'openwa-whatsapp-gateway' ); ?>
		</button>
	</div>

	<div class="openwa-session-status-box">
		<h3><?php esc_html_e( 'Session Status', 'openwa-whatsapp-gateway' ); ?></h3>
		<div id="openwa-session-info">
			<p class="openwa-session-loading"><?php esc_html_e( 'Loading session info...', 'openwa-whatsapp-gateway' ); ?></p>
		</div>
	</div>

	<div class="openwa-qr-box" id="openwa-qr-box" style="display:none;">
		<h3><?php esc_html_e( 'Scan QR Code', 'openwa-whatsapp-gateway' ); ?></h3>
		<p><?php esc_html_e( 'Open WhatsApp on your phone, tap Menu/Three dots > Linked Devices > Link a Device, and scan this QR code.', 'openwa-whatsapp-gateway' ); ?></p>
		<div id="openwa-qr-image"></div>
		<p class="description"><?php esc_html_e( 'The QR code refreshes automatically. Keep this page open while scanning.', 'openwa-whatsapp-gateway' ); ?></p>
	</div>

	<div class="openwa-message-box" id="openwa-session-message"></div>
</div>
