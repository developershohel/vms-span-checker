<?php
/**
 * Validation activity logger.
 *
 * Writes to and reads from the plugin-owned `{$wpdb->prefix}vms_elements_form_guard_logs`
 * custom table; identifiers are hardcoded and values pass through
 * `$wpdb->prepare()` / `$wpdb->insert()`.
 *
 * @package VMS_Elements_Form_Guard
 *
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 * phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
 * phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
 * phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter
 */

namespace VMS_Elements_Form_Guard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Logger {
	private $wpdb;
	private $table;

	public function __construct() {
		global $wpdb;
		$this->wpdb  = $wpdb;
		$this->table = $this->wpdb->prefix . 'vms_elements_form_guard_logs';
	}

	public function log( string $type, string $ip, string $domain, string $status, string $message = '' ): bool {
		return (bool) $this->wpdb->insert(
			$this->table,
			array(
				'type'       => sanitize_text_field( $type ),
				'ip'         => sanitize_text_field( $ip ),
				'domain'     => sanitize_text_field( $domain ),
				'status'     => sanitize_text_field( $status ),
				'message'    => self::sanitize_log_message( $message ),
				'created_at' => current_time( 'mysql' ),
			)
		);
	}

	/**
	 * Strip tags and cap length for the logs `message` column (TEXT).
	 */
	public static function sanitize_log_message( string $message ): string {
		$m = wp_strip_all_tags( $message );
		$m = str_replace( array( "\r\n", "\r" ), "\n", $m );
		if ( strlen( $m ) > 4000 ) {
			$m = substr( $m, 0, 4000 ) . '…';
		}
		return $m;
	}

	public function get_logs( $limit = 50 ): array {
		return $this->wpdb->get_results(
			$this->wpdb->prepare( "SELECT * FROM {$this->table} ORDER BY created_at DESC LIMIT %d", $limit ),
			ARRAY_A
		);
	}
}
