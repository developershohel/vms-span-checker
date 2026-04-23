<?php
/**
 * Post AI summaries for comment context.
 *
 * @package WP_Span_Checker
 */

namespace WP_Span_Checker;

use WP_Span_Checker\Services\AI_Span_Completion;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores summaries in span_checker_ai_post_summary.
 */
class AI_Span_Summary {

	/**
	 * @var int
	 */
	private static $pending_post_id = 0;

	public function __construct() {
		add_action( 'save_post', array( $this, 'on_save_post' ), 99, 3 );
		add_action( 'shutdown', array( $this, 'run_pending_summary' ), 999 );
		add_action( 'wsc_ai_generate_post_summary', array( $this, 'cron_generate' ), 10, 1 );
	}

	/**
	 * Schedule or run summary generation when a supported post type is published/updated.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @param bool     $update  Whether this is an update.
	 */
	public function on_save_post( $post_id, $post, $update ): void { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$post_id = (int) $post_id;
		if ( $post_id <= 0 || ! ( $post instanceof \WP_Post ) ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		$c = AI_Span_Config::get();
		if ( empty( $c['ai_enabled'] ) ) {
			return;
		}

		$types = $c['summary_post_types'] ?? array( 'post' );
		if ( ! in_array( $post->post_type, $types, true ) ) {
			return;
		}

		if ( 'publish' !== $post->post_status && 'future' !== $post->post_status ) {
			return;
		}

		self::$pending_post_id = $post_id;
	}

	/**
	 * Run summary generation once per request after save (WP-Cron also scheduled as fallback).
	 */
	public function run_pending_summary(): void {
		if ( self::$pending_post_id <= 0 ) {
			return;
		}
		$pid = self::$pending_post_id;
		self::$pending_post_id = 0;
		$this->generate_for_post( $pid );
	}

	/**
	 * Cron callback.
	 *
	 * @param int $post_id Post ID.
	 */
	public function cron_generate( $post_id ): void {
		$this->generate_for_post( (int) $post_id );
	}

	/**
	 * Build plain-text post body excerpt and request a short summary from AI.
	 *
	 * @param int $post_id Post ID.
	 * @return bool True on stored success.
	 */
	public function generate_for_post( int $post_id ): bool {
		$post = get_post( $post_id );
		if ( ! $post || ! in_array( $post->post_status, array( 'publish', 'future' ), true ) ) {
			return false;
		}

		$c = AI_Span_Config::get();
		if ( empty( $c['ai_enabled'] ) ) {
			return false;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'span_checker_ai_post_summary';
		$now   = current_time( 'mysql' );

		$title   = wp_strip_all_tags( $post->post_title );
		$content = wp_strip_all_tags( $post->post_content );
		$content = wp_html_excerpt( $content, 12000, '…' );

		$sys = __(
			'You summarize blog posts for spam-detection context. Reply with ONLY valid JSON: {"summary":"2-4 neutral sentences describing what the article is about. No markdown."}',
			'wp-span-checker'
		);
		$usr = "TITLE:\n{$title}\n\nBODY:\n{$content}";

		$raw = AI_Span_Completion::complete( $sys, $usr );
		if ( is_wp_error( $raw ) ) {
			$this->upsert_row(
				$post_id,
				'',
				'failed',
				$raw->get_error_message(),
				$now
			);
			return false;
		}

		$parsed = json_decode( (string) $raw, true );
		$summary = '';
		if ( is_array( $parsed ) && ! empty( $parsed['summary'] ) ) {
			$summary = sanitize_textarea_field( (string) $parsed['summary'] );
		} else {
			$summary = sanitize_textarea_field( (string) $raw );
		}

		if ( $summary === '' ) {
			$this->upsert_row( $post_id, '', 'failed', __( 'Empty summary from AI.', 'wp-span-checker' ), $now );
			return false;
		}

		$this->upsert_row( $post_id, $summary, 'generated', '', $now );
		return true;
	}

	/**
	 * @param string $status pending|generated|failed .
	 */
	private function upsert_row( int $post_id, string $summary, string $status, string $error, string $now ): void {
		global $wpdb;
		$table = $wpdb->prefix . 'span_checker_ai_post_summary';

		$existing = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT id FROM {$table} WHERE post_id = %d", $post_id )
		);

		$row = array(
			'post_id'    => $post_id,
			'summary'    => $summary,
			'status'     => $status,
			'last_error' => $error,
			'updated_at' => $now,
		);

		if ( $existing ) {
			$wpdb->update( $table, $row, array( 'post_id' => $post_id ), array( '%d', '%s', '%s', '%s', '%s' ), array( '%d' ) );
		} else {
			$wpdb->insert( $table, $row, array( '%d', '%s', '%s', '%s', '%s' ) );
		}
	}

	/**
	 * @return string|null Summary text or null.
	 */
	public static function get_summary_text( int $post_id ): ?string {
		global $wpdb;
		$table = $wpdb->prefix . 'span_checker_ai_post_summary';
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT summary, status FROM {$table} WHERE post_id = %d",
				$post_id
			),
			ARRAY_A
		);
		if ( ! $row || 'generated' !== $row['status'] || $row['summary'] === '' ) {
			return null;
		}
		return (string) $row['summary'];
	}
}
