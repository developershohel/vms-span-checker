<?php
/**
 * Dashboard statistics and log queries.
 *
 * All queries read aggregated data from the plugin-owned
 * `{$wpdb->prefix}vms_elements_form_guard_logs` custom table. Identifiers are hardcoded
 * and dynamic values are prepared via `$wpdb->prepare()`.
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

/**
 * Aggregates counts for the admin dashboard.
 */
class Dashboard {

	/**
	 * WordPress database object.
	 *
	 * @var \wpdb
	 */
	private $wpdb;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	/**
	 * Summary metrics for VMS Elements Form Guard.
	 *
	 * @return array<string, int|bool>
	 */
	public function get_summary() {
		$logs  = $this->wpdb->prefix . 'vms_elements_form_guard_logs';
		$forms = $this->wpdb->prefix . 'vms_elements_form_guard_form_settings';

		$vt_config = get_option( 'vefg-virustotal-config', array() );
		$vt_keys   = isset( $vt_config['keys'] ) && is_array( $vt_config['keys'] ) ? $vt_config['keys'] : array();
		$vt_count  = count( array_filter( array_map( 'strval', $vt_keys ) ) );

		$google     = get_option( 'vefg-google-config', array() );
		$google_key = isset( $google['api_key'] ) ? (string) $google['api_key'] : '';

		$failed = (int) $this->wpdb->get_var( "SELECT COUNT(*) FROM {$logs} WHERE status = 'failed'" );

		return array(
			'whitelist_count'       => (int) $this->wpdb->get_var( "SELECT COUNT(*) FROM {$this->wpdb->prefix}vms_elements_form_guard_whitelist_domains" ),
			'disposable_count'      => (int) $this->wpdb->get_var( "SELECT COUNT(*) FROM {$this->wpdb->prefix}vms_elements_form_guard_disposable_domains" ),
			'login_attempts'        => (int) $this->wpdb->get_var( "SELECT COUNT(*) FROM {$logs} WHERE type = 'login'" ),
			'registration_attempts' => (int) $this->wpdb->get_var( "SELECT COUNT(*) FROM {$logs} WHERE type = 'registration'" ),
			'spam_logs'             => $failed,
			'failed_validations'    => $failed,
			'total_logs'            => (int) $this->wpdb->get_var( "SELECT COUNT(*) FROM {$logs}" ),
			'success_logs'          => (int) $this->wpdb->get_var( "SELECT COUNT(*) FROM {$logs} WHERE status = 'success'" ),
			'form_mappings'         => (int) $this->wpdb->get_var( "SELECT COUNT(*) FROM {$forms}" ),
			'virustotal_keys'       => $vt_count,
			'google_api_ready'      => ( '' !== trim( $google_key ) ),
		);
	}

	/**
	 * Recent failed validation rows.
	 *
	 * @param int $limit Rows.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_spam_logs( $limit = 20 ) {
		return $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->wpdb->prefix}vms_elements_form_guard_logs WHERE status = %s ORDER BY created_at DESC LIMIT %d",
				'failed',
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Aggregates for dashboard analysis (charts / top lists).
	 *
	 * @param int $top_n Max rows for top-N lists.
	 * @return array{
	 *   events_by_type: array<int, array{type: string, count: int}>,
	 *   top_failed_domains: array<int, array{domain: string, count: int}>,
	 *   top_failed_messages: array<int, array{message: string, count: int}>
	 * }
	 */
	public function get_analysis( $top_n = 5 ) {
		$logs  = $this->wpdb->prefix . 'vms_elements_form_guard_logs';
		$top_n = max( 1, min( 20, (int) $top_n ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name uses trusted prefix only.
		$events_by_type = $this->wpdb->get_results(
			"SELECT type AS event_type, COUNT(*) AS cnt FROM {$logs} GROUP BY type ORDER BY cnt DESC",
			ARRAY_A
		);

		$top_failed_domains = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT domain, COUNT(*) AS cnt FROM {$logs} WHERE status = %s AND domain <> '' GROUP BY domain ORDER BY cnt DESC LIMIT %d",
				'failed',
				$top_n
			),
			ARRAY_A
		);

		$top_failed_messages = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT message, COUNT(*) AS cnt FROM {$logs} WHERE status = %s AND message <> '' GROUP BY message ORDER BY cnt DESC LIMIT %d",
				'failed',
				$top_n
			),
			ARRAY_A
		);

		$by_type = array();
		foreach ( (array) $events_by_type as $row ) {
			$by_type[] = array(
				'type'  => isset( $row['event_type'] ) ? (string) $row['event_type'] : '',
				'count' => isset( $row['cnt'] ) ? (int) $row['cnt'] : 0,
			);
		}

		$domains_out = array();
		foreach ( (array) $top_failed_domains as $row ) {
			$domains_out[] = array(
				'domain' => isset( $row['domain'] ) ? (string) $row['domain'] : '',
				'count'  => isset( $row['cnt'] ) ? (int) $row['cnt'] : 0,
			);
		}

		$messages_out = array();
		foreach ( (array) $top_failed_messages as $row ) {
			$messages_out[] = array(
				'message' => isset( $row['message'] ) ? (string) $row['message'] : '',
				'count'   => isset( $row['cnt'] ) ? (int) $row['cnt'] : 0,
			);
		}

		return array(
			'events_by_type'      => $by_type,
			'top_failed_domains'  => $domains_out,
			'top_failed_messages' => $messages_out,
		);
	}
}
