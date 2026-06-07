<?php
/**
 * Disposable domain repository.
 *
 * All queries target the plugin-owned `{$wpdb->prefix}vms_elements_form_guard_disposable_domains`
 * custom table; identifiers are hardcoded and values pass through
 * `$wpdb->prepare()` / insert / delete helpers.
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

class Disposable {
	private $wpdb;
	private $table;

	public function __construct() {
		global $wpdb;
		$this->wpdb  = $wpdb;
		$this->table = $this->wpdb->prefix . 'vms_elements_form_guard_disposable_domains';
	}

	public function get_all(): array {
		return $this->wpdb->get_results( "SELECT * FROM {$this->table} ORDER BY domain ASC", ARRAY_A );
	}

	public function add_domain( string $domain ): bool {
		return (bool) $this->wpdb->insert( $this->table, array( 'domain' => sanitize_text_field( $domain ) ) );
	}

	public function delete_domain( int $id ): bool {
		return (bool) $this->wpdb->delete( $this->table, array( 'id' => $id ) );
	}

	public function count(): int {
		return (int) $this->wpdb->get_var( "SELECT COUNT(*) FROM {$this->table}" );
	}

	/**
	 * True if the email host (or a parent host) exists in the disposable list.
	 *
	 * @param string $domain Lowercase domain from the email address.
	 */
	public function email_domain_is_disposable( string $domain ): bool {
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
