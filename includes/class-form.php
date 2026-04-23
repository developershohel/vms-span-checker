<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Form {
	private $wpdb;
	private $table;

	public function __construct() {
		global $wpdb;
		$this->wpdb  = $wpdb;
		$this->table = $this->wpdb->prefix . 'span_checker_forms';
	}

	public function get_settings(): array {
		$settings = $this->wpdb->get_results( "SELECT * FROM {$this->table}", ARRAY_A );

		return $settings ?: [];
	}

	public function update_setting( array $data ): bool {
		$data = array_map( 'sanitize_text_field', $data );

		return (bool) $this->wpdb->replace( $this->table, $data );
	}
}
