<div class="wrap">
	<h1><?php esc_html_e( 'Message Templates', 'openwa-whatsapp-gateway' ); ?></h1>
	<p><?php esc_html_e( 'Customize WhatsApp messages sent for each event. Use shortcodes like {order_id}, {customer_name}, {order_total}, etc.', 'openwa-whatsapp-gateway' ); ?></p>

	<div class="notice notice-info inline" style="margin-bottom:12px;">
		<p><strong><?php esc_html_e( 'Available shortcodes:', 'openwa-whatsapp-gateway' ); ?></strong></p>
		<p style="margin:2px 0;line-height:1.8;">
			<code>{order_id}</code> <code>{order_status}</code> <code>{order_total}</code>
			<code>{subtotal}</code> <code>{shipping_total}</code> <code>{discount_total}</code>
			<code>{order_date}</code> <code>{order_time}</code>
			<code>{customer_name}</code> <code>{customer_first_name}</code>
			<code>{customer_email}</code> <code>{customer_phone}</code>
			<code>{customer_address}</code> <code>{shipping_address}</code>
			<code>{payment_method}</code> <code>{shipping_method}</code>
			<code>{items}</code> <code>{items_detail}</code>
			<code>{order_note}</code>
			<code>{site_name}</code> <code>{site_url}</code>
		</p>
	</div>

	<form method="post">
		<?php wp_nonce_field( 'openwa_save_templates' ); ?>
		
		<table class="wp-list-table widefat fixed striped openwa-templates-table">
			<thead>
				<tr>
					<th class="check-column"><input type="checkbox" id="openwa-toggle-all" /></th>
					<th><?php esc_html_e( 'Event', 'openwa-whatsapp-gateway' ); ?></th>
					<th><?php esc_html_e( 'Message Template', 'openwa-whatsapp-gateway' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $templates as $key => $template ) : ?>
				<tr>
					<td>
						<input type="checkbox" name="templates[<?php echo esc_attr( $key ); ?>][enabled]"
							value="1" <?php checked( ! empty( $template['enabled'] ) ); ?> />
					</td>
					<td class="openwa-template-key">
						<strong><?php echo esc_html( $template['name'] ); ?></strong>
						<br><code><?php echo esc_html( $key ); ?></code>
					</td>
					<td>
						<textarea name="templates[<?php echo esc_attr( $key ); ?>][message]"
							class="widefat openwa-template-message" rows="3"><?php echo esc_textarea( $template['message'] ?? '' ); ?></textarea>
						<input type="hidden" name="templates[<?php echo esc_attr( $key ); ?>][name]"
							value="<?php echo esc_attr( $template['name'] ); ?>" />
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<p class="submit">
			<button type="submit" name="save_templates" class="button button-primary">
				<?php esc_html_e( 'Save Templates', 'openwa-whatsapp-gateway' ); ?>
			</button>
		</p>
	</form>
</div>
