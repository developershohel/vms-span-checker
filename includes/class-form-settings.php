<?php

namespace WP_Span_Checker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Form Guard persistence: `span_checker_form_settings` table.
 */
class Form_Settings {
	private $wpdb;
	private $post;
	private $table;

	public function __construct() {
		global $wpdb, $post;
		$this->wpdb  = $wpdb;
		$this->post  = $post;
		$this->table = $this->wpdb->prefix . 'span_checker_form_settings';
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
