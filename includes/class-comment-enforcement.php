<?php
/**
 * Shared strike storage and blocked-comment redirect for Comment Guard and Product Review Guard.
 *
 * Queries target the plugin-owned
 * `{$wpdb->prefix}vms_elements_form_guard_comment_enforcement` custom table; identifiers
 * are hardcoded and values pass through `$wpdb->prepare()` or insert helpers.
 *
 * @package VMS_Elements_Form_Guard
 *
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 * phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
 * phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter
 */

namespace VMS_Elements_Form_Guard;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enforcement helpers (table: vms_elements_form_guard_comment_enforcement).
 */
final class Comment_Enforcement {

	/**
	 * Visitor IP for enforcement rows.
	 */
	public static function get_ip(): string {
		if ( function_exists( 'vms_elements_form_guard_get_user_ip' ) ) {
			return vms_elements_form_guard_get_user_ip();
		}
		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) ) : '';
	}

	/**
	 * @param array<string, mixed> $commentdata Core comment payload.
	 * @return array{key:string,label:string}
	 */
	public static function get_actor( array $commentdata ): array {
		$uid = isset( $commentdata['user_id'] ) ? (int) $commentdata['user_id'] : 0;
		if ( $uid > 0 ) {
			return array(
				'key'   => 'u:' . $uid,
				'label' => 'user:' . $uid,
			);
		}

		$email = isset( $commentdata['comment_author_email'] ) ? strtolower( trim( (string) $commentdata['comment_author_email'] ) ) : '';
		$ip    = self::get_ip();

		$key = substr( hash( 'sha256', $ip . '|' . $email ), 0, 64 );

		return array(
			'key'   => 'g:' . $key,
			'label' => '' !== $email ? $email : $ip,
		);
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public static function get_row( string $actor_key ): ?array {
		global $wpdb;
		$table = $wpdb->prefix . 'vms_elements_form_guard_comment_enforcement';
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE actor_key = %s", $actor_key ),
			ARRAY_A
		);
		return is_array( $row ) ? $row : null;
	}

	/**
	 * @param array{key:string,label:string} $actor  .
	 * @param array<string, mixed>           $config AI_Span_Config row.
	 * @param string                         $source Strike source (comment, product_review).
	 */
	public static function register_strike( array $actor, string $reason, array $config, string $source = 'comment' ): void {
		global $wpdb;
		$table       = $wpdb->prefix . 'vms_elements_form_guard_comment_enforcement';
		$max_comment = (int) ( $config['comment_max_strikes'] ?? 5 );
		$ban_enabled = ! empty( $config['comment_site_ban_enabled'] );
		$ban_at      = (int) ( $config['comment_site_ban_strikes'] ?? 10 );
		$now         = current_time( 'mysql' );
		$reason      = function_exists( 'mb_substr' ) ? mb_substr( $reason, 0, 500 ) : substr( $reason, 0, 500 );
		$ip          = self::get_ip();
		$existing    = self::get_row( $actor['key'] );

		if ( $existing ) {
			$strikes     = (int) $existing['strikes'] + 1;
			$was_blocked = (int) $existing['blocked'];
			$site_banned = (int) ( $existing['site_banned'] ?? 0 );
			$blocked     = ( $strikes >= $max_comment ) ? 1 : $was_blocked;
			if ( $ban_enabled && $strikes >= $ban_at ) {
				$site_banned = 1;
			}
			$blocked_at = (string) ( $existing['blocked_at'] ?? '' );
			if ( $blocked && ! $was_blocked ) {
				$blocked_at = $now;
			}
			$wpdb->update(
				$table,
				array(
					'strikes'        => $strikes,
					'blocked'        => $blocked,
					'site_banned'    => $site_banned,
					'last_ip'        => $ip,
					'blocked_at'     => $blocked_at,
					'last_strike_at' => $now,
					'last_reason'    => $reason,
					'actor_label'    => $actor['label'],
					'strike_source'  => $source,
				),
				array( 'actor_key' => $actor['key'] ),
				array( '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' ),
				array( '%s' )
			);
			return;
		}

		$strikes     = 1;
		$blocked     = ( $strikes >= $max_comment ) ? 1 : 0;
		$site_banned = ( $ban_enabled && $strikes >= $ban_at ) ? 1 : 0;
		$row         = array(
			'actor_key'      => $actor['key'],
			'actor_label'    => $actor['label'],
			'strikes'        => $strikes,
			'blocked'        => $blocked,
			'site_banned'    => $site_banned,
			'last_ip'        => $ip,
			'last_strike_at' => $now,
			'last_reason'    => $reason,
			'strike_source'  => $source,
		);
		if ( $blocked ) {
			$row['blocked_at'] = $now;
		}
		$wpdb->insert( $table, $row );
	}

	/**
	 * Redirect to the post with a transient-backed notice (same mechanism as Comment Guard).
	 *
	 * @param array<string, mixed> $commentdata .
	 * @param WP_Error             $error       .
	 * @param string               $fragment    Hash fragment (e.g. #respond or #reviews).
	 * @param string|null          $notice_title Optional alert title (default: Comment blocked).
	 */
	public static function redirect_with_notice( array $commentdata, WP_Error $error, string $fragment = '#respond', ?string $notice_title = null ): void {
		$post_id = isset( $commentdata['comment_post_ID'] ) ? (int) $commentdata['comment_post_ID'] : 0;
		$url     = ( $post_id > 0 ) ? get_permalink( $post_id ) : home_url( '/' );
		if ( ! is_string( $url ) || '' === $url ) {
			$url = home_url( '/' );
		}

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Established hook name; renaming would break BC for existing filter consumers.
		$fragment = (string) apply_filters( 'vefg_comment_block_redirect_fragment', $fragment, $commentdata, $error );

		$token = function_exists( 'wp_generate_password' )
			? wp_generate_password( 16, false, false )
			: bin2hex( random_bytes( 8 ) );
		$title = ( null !== $notice_title && '' !== trim( $notice_title ) )
			? $notice_title
			: __( 'Comment blocked', 'vms-elements-form-guard' );

		set_transient(
			'vefg_cerr_' . $token,
			array(
				'title'   => $title,
				'message' => $error->get_error_message(),
			),
			5 * MINUTE_IN_SECONDS
		);

		$target = add_query_arg( 'vefg_comment_err', $token, $url );
		if ( '' !== $fragment && '#' !== $fragment[0] ) {
			$fragment = '#' . $fragment;
		}
		$target .= $fragment;

		wp_safe_redirect( $target );
		exit;
	}
}
