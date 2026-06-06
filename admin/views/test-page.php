<div class="wrap">
	<h1><?php esc_html_e( 'Test WhatsApp Message', 'openwa-whatsapp-gateway' ); ?></h1>

	<div class="openwa-card">
		<table class="form-table">
			<tr>
				<th scope="row"><label for="openwa-test-phone"><?php esc_html_e( 'Phone Number', 'openwa-whatsapp-gateway' ); ?></label></th>
				<td>
					<input type="text" id="openwa-test-phone" class="regular-text"
						placeholder="628123456789" />
					<p class="description"><?php esc_html_e( 'Include country code without + or 00.', 'openwa-whatsapp-gateway' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="openwa-test-message"><?php esc_html_e( 'Message', 'openwa-whatsapp-gateway' ); ?></label></th>
				<td>
					<textarea id="openwa-test-message" class="large-text" rows="5"
						placeholder="<?php esc_attr_e( 'Enter your test message here...', 'openwa-whatsapp-gateway' ); ?>"></textarea>
				</td>
			</tr>
		</table>

		<p>
			<button type="button" class="button button-primary openwa-send-test">
				<?php esc_html_e( 'Send Test Message', 'openwa-whatsapp-gateway' ); ?>
			</button>
			<span id="openwa-test-result"></span>
		</p>
	</div>
</div>
