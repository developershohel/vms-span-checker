<?php

namespace WP_Span_Checker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Dashboard {
	private $wpdb;

	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	public function get_summary(): array {
		return [
			'whitelist_count'       => (int) $this->wpdb->get_var( "SELECT COUNT(*) FROM {$this->wpdb->prefix}span_checker_whitelist_domains" ),
			'disposable_count'      => (int) $this->wpdb->get_var( "SELECT COUNT(*) FROM {$this->wpdb->prefix}span_checker_disposable_domains" ),
			'login_attempts'        => (int) $this->wpdb->get_var( "SELECT COUNT(*) FROM {$this->wpdb->prefix}span_checker_logs WHERE type='login'" ),
			'registration_attempts' => (int) $this->wpdb->get_var( "SELECT COUNT(*) FROM {$this->wpdb->prefix}wp_span_logs WHERE type='registration'" ),
			'spam_logs'             => (int) $this->wpdb->get_var( "SELECT COUNT(*) FROM {$this->wpdb->prefix}wp_span_logs WHERE status='failed'" ),
		];
	}

	public function get_spam_logs( $limit = 20 ): array {
		return $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->wpdb->prefix}wp_span_logs WHERE status='failed' ORDER BY created_at DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		);
	}
}
