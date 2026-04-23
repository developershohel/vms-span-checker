<?php

namespace WP_Span_Checker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Logger {
	private $wpdb;
	private $table;

	public function __construct() {
		global $wpdb;
		$this->wpdb  = $wpdb;
		$this->table = $this->wpdb->prefix . 'span_checker_logs';
	}

	public function log( string $type, string $ip, string $domain, string $status, string $message = '' ): bool {
		return (bool) $this->wpdb->insert( $this->table, [
			'type'       => $type,
			'ip'         => sanitize_text_field( $ip ),
			'domain'     => sanitize_text_field( $domain ),
			'status'     => sanitize_text_field( $status ),
			'message'    => sanitize_text_field( $message ),
			'created_at' => current_time( 'mysql' ),
		] );
	}

	public function get_logs( $limit = 50 ): array {
		return $this->wpdb->get_results(
			$this->wpdb->prepare( "SELECT * FROM {$this->table} ORDER BY created_at DESC LIMIT %d", $limit ),
			ARRAY_A
		);
	}
}
