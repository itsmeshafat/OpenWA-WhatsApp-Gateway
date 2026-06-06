<?php
defined( 'ABSPATH' ) || exit;

class OpenWA_Logger {

	private static $log_table = 'openwa_logs';
	private static $db_version = '1.0';
	private static $max_logs = 10000;

	public static function init() {
		self::maybe_create_table();
		add_action( 'openwa_cleanup_logs', array( __CLASS__, 'cleanup_old_logs' ) );
		if ( ! wp_next_scheduled( 'openwa_cleanup_logs' ) ) {
			wp_schedule_event( time(), 'daily', 'openwa_cleanup_logs' );
		}
	}

	private static function maybe_create_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . self::$log_table;
		$version_option = 'openwa_logs_db_version';

		if ( get_option( $version_option ) === self::$db_version ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			level VARCHAR(20) NOT NULL DEFAULT 'info',
			message TEXT NOT NULL,
			context LONGTEXT DEFAULT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			INDEX idx_level (level),
			INDEX idx_created (created_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
		update_option( $version_option, self::$db_version );
	}

	public static function info( $message, $context = array() ) {
		self::log( 'info', $message, $context );
	}

	public static function warning( $message, $context = array() ) {
		self::log( 'warning', $message, $context );
	}

	public static function error( $message, $context = array() ) {
		self::log( 'error', $message, $context );
	}

	private static function log( $level, $message, $context ) {
		global $wpdb;
		$table_name = $wpdb->prefix . self::$log_table;

		$wpdb->insert(
			$table_name,
			array(
				'level'   => $level,
				'message' => $message,
				'context' => ! empty( $context ) ? wp_json_encode( $context ) : null,
			),
			array( '%s', '%s', '%s' )
		);
	}

	public static function get_logs( $level = '', $limit = 100, $offset = 0 ) {
		global $wpdb;
		$table_name = $wpdb->prefix . self::$log_table;

		if ( ! empty( $level ) ) {
			$results = $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE level = %s ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$level, $limit, $offset
			) );
		} else {
			$results = $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM {$table_name} ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$limit, $offset
			) );
		}

		return $results;
	}

	public static function cleanup_old_logs() {
		global $wpdb;
		$table_name = $wpdb->prefix . self::$log_table;

		$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
		if ( $count > self::$max_logs ) {
			$wpdb->query( $wpdb->prepare(
				"DELETE FROM {$table_name} ORDER BY created_at ASC LIMIT %d",
				$count - self::$max_logs
			) );
		}

		$wpdb->query(
			"DELETE FROM {$table_name} WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
		);
	}

	public static function clear_all() {
		global $wpdb;
		$table_name = $wpdb->prefix . self::$log_table;
		$wpdb->query( "TRUNCATE TABLE {$table_name}" );
	}
}
