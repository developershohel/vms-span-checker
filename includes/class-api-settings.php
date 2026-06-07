<?php
/**
 * API key storage for plugin-managed integrations.
 *
 * Queries target the plugin-owned `{$wpdb->prefix}vms_elements_form_guard_api_keys`
 * custom table; the identifier is hardcoded.
 *
 * @package VMS_Elements_Form_Guard
 *
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 * phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
 */

namespace VMS_Elements_Form_Guard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class API_Settings {
	private $wpdb;
	private $table;

	public function __construct() {
		global $wpdb;
		$this->wpdb  = $wpdb;
		$this->table = $this->wpdb->prefix . 'vms_elements_form_guard_api_keys';
	}

	public function get_all(): array {
		return $this->wpdb->get_results( "SELECT * FROM {$this->table} ORDER BY id DESC", ARRAY_A );
	}

	public function add_key( array $data ): bool {
		$data = array_map( 'sanitize_text_field', $data );

		return (bool) $this->wpdb->insert( $this->table, $data );
	}

	public function update_status( int $id, string $status ): bool {
		return (bool) $this->wpdb->update( $this->table, array( 'status' => $status ), array( 'id' => $id ) );
	}

	public function delete_key( int $id ): bool {
		return (bool) $this->wpdb->delete( $this->table, array( 'id' => $id ) );
	}
}
