<?php

namespace WP_Span_Checker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Form_Builder {
	private $wpdb;
	private $table;

	public function __construct() {
		global $wpdb;
		$this->wpdb  = $wpdb;
		$this->table = $this->wpdb->prefix . 'span_checker_forms';
	}

	public function get_all(): array {
		return $this->wpdb->get_results( "SELECT * FROM {$this->table} ORDER BY id DESC", ARRAY_A );
	}

	public function add_form( array $data ): bool {
		$data = array_map( 'sanitize_text_field', $data );

		return (bool) $this->wpdb->insert( $this->table, $data );
	}

	public function update_form( int $id, array $data ): bool {
		$data = array_map( 'sanitize_text_field', $data );

		return (bool) $this->wpdb->update( $this->table, $data, [ 'id' => $id ] );
	}

	public function delete_form( int $id ): bool {
		return (bool) $this->wpdb->delete( $this->table, [ 'id' => $id ] );
	}
}
