<?php

namespace WP_Span_Checker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class API_Settings {
	private $wpdb;
	private $table;

	public function __construct() {
		global $wpdb;
		$this->wpdb  = $wpdb;
		$this->table = $this->wpdb->prefix . 'span_checker_api_keys';
	}

	public function get_all(): array {
		return $this->wpdb->get_results( "SELECT * FROM {$this->table} ORDER BY id DESC", ARRAY_A );
	}

	public function add_key( array $data ): bool {
		$data = array_map( 'sanitize_text_field', $data );

		return (bool) $this->wpdb->insert( $this->table, $data );
	}

	public function update_status( int $id, string $status ): bool {
		return (bool) $this->wpdb->update( $this->table, [ 'status' => $status ], [ 'id' => $id ] );
	}

	public function delete_key( int $id ): bool {
		return (bool) $this->wpdb->delete( $this->table, [ 'id' => $id ] );
	}
}
