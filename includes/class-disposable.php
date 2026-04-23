<?php

namespace WP_Span_Checker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Disposable {
	private $wpdb;
	private $table;

	public function __construct() {
		global $wpdb;
		$this->wpdb  = $wpdb;
		$this->table = $this->wpdb->prefix . 'span_disposable_domains';
	}

	public function get_all(): array {
		return $this->wpdb->get_results( "SELECT * FROM {$this->table} ORDER BY domain ASC", ARRAY_A );
	}

	public function add_domain( string $domain ): bool {
		return (bool) $this->wpdb->insert( $this->table, [ 'domain' => sanitize_text_field( $domain ) ] );
	}

	public function delete_domain( int $id ): bool {
		return (bool) $this->wpdb->delete( $this->table, [ 'id' => $id ] );
	}

	public function count(): int {
		return (int) $this->wpdb->get_var( "SELECT COUNT(*) FROM {$this->table}" );
	}
}
