<?php
/**
 * AI-assisted comment spam checks and strike / block enforcement.
 *
 * @package WP_Span_Checker
 */

namespace WP_Span_Checker;

use WP_Error;
use WP_Span_Checker\Services\AI_Span_Completion;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hooks preprocess_comment + REST comment insert.
 */
class AI_Span_Comments {

	public function __construct() {
		add_filter( 'preprocess_comment', array( $this, 'preprocess_comment' ), 5, 1 );
		add_filter( 'rest_pre_insert_comment', array( $this, 'rest_pre_insert_comment' ), 10, 2 );
	}

	/**
	 * @param array<string, mixed> $commentdata .
	 * @return array<string, mixed>
	 */
	public function preprocess_comment( $commentdata ) {
		$check = $this->evaluate_comment_submission( $commentdata );
		if ( is_wp_error( $check ) ) {
			wp_die(
				esc_html( $check->get_error_message() ),
				esc_html__( 'Comment blocked', 'wp-span-checker' ),
				array( 'response' => 403, 'back_link' => true )
			);
		}
		return $commentdata;
	}

	/**
	 * @param \WP_Comment $prepared_comment .
	 * @param \WP_REST_Request $request .
	 * @return \WP_Comment|WP_Error
	 */
	public function rest_pre_insert_comment( $prepared_comment, $request ) {
		$data = array(
			'comment_post_ID'      => $prepared_comment->comment_post_ID,
			'comment_author'       => $prepared_comment->comment_author,
			'comment_author_email' => $prepared_comment->comment_author_email,
			'comment_author_url'   => isset( $prepared_comment->comment_author_url ) ? $prepared_comment->comment_author_url : '',
			'comment_content'      => $prepared_comment->comment_content,
			'comment_type'         => isset( $prepared_comment->comment_type ) ? $prepared_comment->comment_type : 'comment',
			'user_id'              => $prepared_comment->user_id,
		);
		$check = $this->evaluate_comment_submission( $data );
		if ( is_wp_error( $check ) ) {
			return $check;
		}
		return $prepared_comment;
	}

	/**
	 * @param array<string, mixed> $commentdata .
	 * @return true|WP_Error
	 */
	private function evaluate_comment_submission( array $commentdata ) {
		$c       = AI_Span_Config::get();
		$post_id = isset( $commentdata['comment_post_ID'] ) ? (int) $commentdata['comment_post_ID'] : 0;
		if ( $post_id <= 0 ) {
			return true;
		}

		$actor = $this->get_actor( $commentdata );
		$block = $this->get_enforcement_row( $actor['key'] );
		if ( $block && ! empty( $block['blocked'] ) ) {
			return new WP_Error(
				'wsc_blocked',
				__( 'You are temporarily blocked from commenting on this site due to repeated spam attempts.', 'wp-span-checker' )
			);
		}

		if ( ! empty( $c['comment_antispam_enabled'] ) ) {
			$spam_check = Comment_Spam_Rules::evaluate( $commentdata, $c );
			if ( is_wp_error( $spam_check ) ) {
				if ( ! empty( $c['comment_strike_on_heuristic'] ) ) {
					$this->register_strike( $actor, $spam_check->get_error_message(), $c );
				}
				return $spam_check;
			}
		}

		if ( empty( $c['ai_enabled'] ) ) {
			return true;
		}

		$content = isset( $commentdata['comment_content'] ) ? (string) $commentdata['comment_content'] : '';

		$summary = AI_Span_Summary::get_summary_text( $post_id );
		if ( $summary === null || $summary === '' ) {
			return true;
		}

		$sys = (string) ( $c['system_prompt'] ?? '' );
		if ( $sys === '' ) {
			$sys = AI_Span_Config::default_system_prompt();
		}

		$review_note = ! empty( $c['product_review_filter'] )
			? 'PRODUCT_REVIEW_MODE: yes — allow genuine short reviews.'
			: 'PRODUCT_REVIEW_MODE: no';

		$usr = $review_note . "\n\nPOST_SUMMARY:\n" . $summary . "\n\nCOMMENT_TEXT:\n" . $content;

		$raw = AI_Span_Completion::complete( $sys, $usr );
		if ( is_wp_error( $raw ) ) {
			return true;
		}

		$verdict = AI_Span_Completion::parse_json_verdict( (string) $raw );
		if ( is_wp_error( $verdict ) ) {
			return true;
		}

		if ( 'spam' === $verdict['status'] ) {
			$this->register_strike( $actor, $verdict['message'], $c );
			return new WP_Error(
				'wsc_spam',
				sprintf(
					/* translators: %s: short reason from AI */
					__( 'Comment rejected: %s', 'wp-span-checker' ),
					$verdict['message']
				)
			);
		}

		return true;
	}

	/**
	 * @param array<string, mixed> $commentdata .
	 * @return array{key:string,label:string}
	 */
	private function get_actor( array $commentdata ): array {
		$uid = isset( $commentdata['user_id'] ) ? (int) $commentdata['user_id'] : 0;
		if ( $uid > 0 ) {
			return array(
				'key'   => 'u:' . $uid,
				'label' => 'user:' . $uid,
			);
		}

		$email = isset( $commentdata['comment_author_email'] ) ? strtolower( trim( (string) $commentdata['comment_author_email'] ) ) : '';
		$ip    = $this->get_ip();

		$key = substr( hash( 'sha256', $ip . '|' . $email ), 0, 64 );

		return array(
			'key'   => 'g:' . $key,
			'label' => $email !== '' ? $email : $ip,
		);
	}

	private function get_ip(): string {
		if ( function_exists( 'wp_span_checker_get_user_ip' ) ) {
			return wp_span_checker_get_user_ip();
		}
		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) ) : '';
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private function get_enforcement_row( string $actor_key ): ?array {
		global $wpdb;
		$table = $wpdb->prefix . 'span_checker_comment_enforcement';
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE actor_key = %s", $actor_key ),
			ARRAY_A
		);
		return is_array( $row ) ? $row : null;
	}

	/**
	 * @param array{key:string,label:string} $actor .
	 * @param array<string, mixed>          $c     Config.
	 */
	private function register_strike( array $actor, string $reason, array $c ): void {
		global $wpdb;
		$table   = $wpdb->prefix . 'span_checker_comment_enforcement';
		$max     = (int) ( $c['comment_max_strikes'] ?? 5 );
		$now     = current_time( 'mysql' );
		$reason = function_exists( 'mb_substr' ) ? mb_substr( $reason, 0, 500 ) : substr( $reason, 0, 500 );
		$existing = $this->get_enforcement_row( $actor['key'] );

		if ( $existing ) {
			$strikes      = (int) $existing['strikes'] + 1;
			$was_blocked  = (int) $existing['blocked'];
			$blocked      = $strikes >= $max ? 1 : $was_blocked;
			$blocked_at   = (string) ( $existing['blocked_at'] ?? '' );
			if ( $blocked && ! $was_blocked ) {
				$blocked_at = $now;
			}
			$wpdb->update(
				$table,
				array(
					'strikes'        => $strikes,
					'blocked'        => $blocked,
					'blocked_at'     => $blocked_at,
					'last_strike_at' => $now,
					'last_reason'    => $reason,
					'actor_label'    => $actor['label'],
				),
				array( 'actor_key' => $actor['key'] ),
				array( '%d', '%d', '%s', '%s', '%s', '%s' ),
				array( '%s' )
			);
			return;
		}

		$strikes = 1;
		$blocked = $strikes >= $max ? 1 : 0;
		$row     = array(
			'actor_key'      => $actor['key'],
			'actor_label'    => $actor['label'],
			'strikes'        => $strikes,
			'blocked'        => $blocked,
			'last_strike_at' => $now,
			'last_reason'    => $reason,
		);
		if ( $blocked ) {
			$row['blocked_at'] = $now;
		}
		$wpdb->insert( $table, $row );
	}

	/**
	 * Admin: clear block and optionally strikes.
	 */
	public static function admin_unblock( string $actor_key ): bool {
		global $wpdb;
		$table = $wpdb->prefix . 'span_checker_comment_enforcement';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name uses trusted prefix.
		$result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET blocked = 0, strikes = 0, blocked_at = NULL WHERE actor_key = %s",
				$actor_key
			)
		);
		return false !== $result && '' === $wpdb->last_error;
	}
}
