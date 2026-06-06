<div class="wrap">
	<h1><?php esc_html_e( 'OpenWA Logs', 'openwa-whatsapp-gateway' ); ?></h1>

	<form method="get" class="openwa-logs-filter">
		<input type="hidden" name="page" value="openwa-logs" />
		<label for="log_level"><?php esc_html_e( 'Filter by level:', 'openwa-whatsapp-gateway' ); ?></label>
		<select name="log_level" id="log_level">
			<option value=""><?php esc_html_e( 'All Levels', 'openwa-whatsapp-gateway' ); ?></option>
			<option value="info" <?php selected( $level, 'info' ); ?>><?php esc_html_e( 'Info', 'openwa-whatsapp-gateway' ); ?></option>
			<option value="warning" <?php selected( $level, 'warning' ); ?>><?php esc_html_e( 'Warning', 'openwa-whatsapp-gateway' ); ?></option>
			<option value="error" <?php selected( $level, 'error' ); ?>><?php esc_html_e( 'Error', 'openwa-whatsapp-gateway' ); ?></option>
		</select>
		<button type="submit" class="button"><?php esc_html_e( 'Filter', 'openwa-whatsapp-gateway' ); ?></button>
	</form>

	<form method="post" style="float:right;">
		<?php wp_nonce_field( 'openwa_clear_logs' ); ?>
		<button type="submit" name="clear_logs" class="button"
			onclick="return confirm('<?php esc_attr_e( 'Clear all logs?', 'openwa-whatsapp-gateway' ); ?>');">
			<?php esc_html_e( 'Clear Logs', 'openwa-whatsapp-gateway' ); ?>
		</button>
	</form>

	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Level', 'openwa-whatsapp-gateway' ); ?></th>
				<th><?php esc_html_e( 'Message', 'openwa-whatsapp-gateway' ); ?></th>
				<th><?php esc_html_e( 'Context', 'openwa-whatsapp-gateway' ); ?></th>
				<th><?php esc_html_e( 'Time', 'openwa-whatsapp-gateway' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $logs ) ) : ?>
				<tr><td colspan="4"><?php esc_html_e( 'No log entries found.', 'openwa-whatsapp-gateway' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $logs as $log ) : ?>
					<tr class="openwa-log-<?php echo esc_attr( $log->level ); ?>">
						<td><span class="openwa-log-badge log-<?php echo esc_attr( $log->level ); ?>"><?php echo esc_html( $log->level ); ?></span></td>
						<td><?php echo esc_html( $log->message ); ?></td>
						<td><code><?php echo esc_html( $log->context ? substr( $log->context, 0, 200 ) : '-' ); ?></code></td>
						<td><?php echo esc_html( $log->created_at ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
