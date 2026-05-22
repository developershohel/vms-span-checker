<?php
/**
 * Shared helpers for spam check components.
 *
 * @package VMS_Span_Checker
 */

namespace VMS_Span_Checker\Spam;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stateless utilities (no I/O except IP read).
 */
final class Spam_Check_Helpers {

	public static function visitor_ip(): string {
		if ( function_exists( 'vms_span_checker_get_user_ip' ) ) {
			return vms_span_checker_get_user_ip();
		}
		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) ) : '';
	}

	public static function str_len( string $s ): int {
		return function_exists( 'mb_strlen' ) ? mb_strlen( $s ) : strlen( $s );
	}

	/**
	 * @return string Lowercase domain or empty.
	 */
	public static function email_domain( string $email ): string {
		$email = strtolower( trim( $email ) );
		$pos   = strrpos( $email, '@' );
		if ( false === $pos ) {
			return '';
		}
		$d = substr( $email, $pos + 1 );
		return sanitize_text_field( $d );
	}

	/**
	 * @return array<int, string> Lowercase trimmed non-empty lines.
	 */
	public static function lines_to_array( string $raw ): array {
		$out = array();
		foreach ( preg_split( '/\R/u', $raw ) as $line ) {
			$line = strtolower( trim( (string) $line ) );
			if ( $line !== '' && strpos( $line, '#' ) !== 0 ) {
				$out[] = $line;
			}
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * Count http(s) URLs and HTML anchor hrefs.
	 */
	public static function count_links_in_text( string $text ): int {
		$n = 0;
		if ( preg_match_all( '#https?://[^\s<>"\']+#i', $text, $m ) ) {
			$n += count( $m[0] );
		}
		if ( preg_match_all( '/<a\s[^>]*\bhref\s*=\s*([\'"])https?:/i', $text, $m2 ) ) {
			$n += count( $m2[0] );
		}
		return $n;
	}
}
