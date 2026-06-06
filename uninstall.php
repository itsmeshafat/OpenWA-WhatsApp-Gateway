<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

delete_option( 'openwa_settings' );
delete_option( 'openwa_message_templates' );
delete_option( 'openwa_logs_db_version' );

wp_clear_scheduled_hook( 'openwa_cleanup_logs' );
wp_clear_scheduled_hook( 'openwa_health_check' );
wp_clear_scheduled_hook( 'openwa_daily_digest' );

$table_name = $wpdb->prefix . 'openwa_logs';
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );

$wpdb->query(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_openwa_otp_%' OR option_name LIKE '_transient_timeout_openwa_otp_%'"
);

$wpdb->query(
	"DELETE FROM {$wpdb->usermeta} WHERE meta_key = 'openwa_otp_enabled'"
);
