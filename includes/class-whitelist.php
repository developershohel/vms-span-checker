<?php
/**
 * Whitelist domain repository.
 *
 * All queries target the plugin-owned `{$wpdb->prefix}vms_elements_form_guard_whitelist_domains`
 * custom table. Identifiers are hardcoded; values are always passed through
 * `$wpdb->prepare()` or `$wpdb->insert()` / `$wpdb->delete()` helpers.
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

class Whitelist {
	private $wpdb;
	private $table;

	public function __construct() {
		global $wpdb;
		$this->wpdb  = $wpdb;
		$this->table = $this->wpdb->prefix . 'vms_elements_form_guard_whitelist_domains';
	}

	public function get_all( int $page = 1, int $per_page = 50 ): array {
		$offset = ( $page - 1 ) * $per_page;
		return $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table} ORDER BY id ASC LIMIT %d OFFSET %d",
				$per_page,
				$offset
			),
			ARRAY_A
		);
	}

	public function count(): int {
		return (int) $this->wpdb->get_var( "SELECT COUNT(*) FROM {$this->table}" );
	}

	public function total_pages( int $per_page = 50 ): int {
		return (int) ceil( $this->count() / $per_page );
	}

	public function add_domain( string $domain ): bool {
		global $wpdb;
		return (bool) $wpdb->insert( $this->table, array( 'domain' => sanitize_text_field( $domain ) ) );
	}

	public function delete_domain( int $id ): bool {
		global $wpdb;
		return (bool) $wpdb->delete( $this->table, array( 'id' => $id ) );
	}

	/**
	 * Whether the host or a parent host is on the whitelist.
	 *
	 * @param string $domain Lowercase email domain.
	 */
	public function domain_on_list( string $domain ): bool {
		$domain = strtolower( trim( $domain ) );
		if ( $domain === '' ) {
			return false;
		}
		$labels = explode( '.', $domain );
		$n      = count( $labels );
		for ( $i = 0; $i < $n; $i++ ) {
			$candidate = implode( '.', array_slice( $labels, $i ) );
			if ( $candidate === '' ) {
				continue;
			}
			$hit = (int) $this->wpdb->get_var(
				$this->wpdb->prepare( "SELECT COUNT(*) FROM {$this->table} WHERE domain = %s", $candidate )
			);
			if ( $hit > 0 ) {
				return true;
			}
		}
		return false;
	}
}
