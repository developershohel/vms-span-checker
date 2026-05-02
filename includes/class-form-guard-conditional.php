<?php
/**
 * Form Guard: field-type conditional validation (email vs text vs textarea vs other).
 *
 * Irrelevant stored toggles are ignored so switching field type in the admin cannot
 * leave “ghost” Web Risk / username / textarea checks active on the wrong control.
 *
 * @package WP_Span_Checker
 */

namespace WP_Span_Checker;

use Exception;
use WP_Span_Checker\Services\AI_Span_Completion;
use WP_Span_Checker\Services\Domain_Validator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalize config and run validation steps in field-type order.
 */
class Form_Guard_Conditional {

	/**
	 * Drop toggles that do not apply to this HTML field type.
	 *
	 * @param string               $field_type text|textarea|username|email|url|tel|number|password.
	 * @param array<string, mixed> $field      Raw JSON row for one field.
	 * @return array<string, mixed>
	 */
	public static function normalize_field_config( string $field_type, array $field ): array {
		$f = $field;

		if ( ! in_array( $field_type, array( 'email', 'url' ), true ) ) {
			$f['is_webrisk']    = 0;
			$f['is_virustotal'] = 0;
		}

		if ( 'username' !== $field_type ) {
			$f['check_username_exists'] = 0;
		}

		if ( 'text' !== $field_type ) {
			unset( $f['text_allow_urls'] );
		}

		if ( 'textarea' !== $field_type ) {
			$f['textarea_ai_spam'] = 0;
			unset( $f['textarea_allow_links'] );
		}

		return $f;
	}

	/**
	 * Full AJAX validation for one mapped field (returns payload for wp_send_json_success).
	 *
	 * @param string               $field_type  Field type key.
	 * @param array<string, mixed> $field       Field row (will be normalized internally).
	 * @param string               $value       Raw submitted value.
	 * @param array<string, mixed> $mapping_row Parent mapping DB row.
	 * @return array{status:bool,message:string}
	 */
	public static function validate_field_value( string $field_type, array $field, string $value, array $mapping_row ): array {
		$field   = self::normalize_field_config( $field_type, $field );
		$trimmed = trim( $value );

		if ( ! empty( $field['isRequired'] ) && '0' !== (string) $field['isRequired'] && '' === $trimmed ) {
			return array(
				'status'  => false,
				'message' => __( 'This field is required.', 'wp-span-checker' ),
			);
		}

		if ( '' === $trimmed && ( empty( $field['isRequired'] ) || '0' === (string) $field['isRequired'] ) ) {
			return array( 'status' => true, 'message' => '' );
		}

		// Regex applies to any type when configured.
		$regex = isset( $field['regex'] ) ? trim( (string) $field['regex'] ) : '';
		if ( $regex !== '' && ! wp_span_checker_form_guard_preg_match_safe( $regex, $trimmed ) ) {
			return array(
				'status'  => false,
				'message' => __( 'This value does not match the required pattern.', 'wp-span-checker' ),
			);
		}

		switch ( $field_type ) {
			case 'text':
				return self::guard_text_field( $field, $trimmed );
			case 'username':
				return self::guard_username_field( $field, $trimmed );
			case 'textarea':
				return self::guard_textarea_field( $field, $trimmed );
			case 'email':
			case 'url':
				return self::guard_email_or_url_field( $field_type, $field, $trimmed, $mapping_row );
			default:
				return self::guard_generic_field( $field_type, $field, $trimmed );
		}
	}

	/**
	 * @param array<string, mixed> $field .
	 */
	private static function guard_text_field( array $field, string $trimmed ): array {
		$allow_urls = ! isset( $field['text_allow_urls'] ) || '0' !== (string) $field['text_allow_urls'];
		if ( ! $allow_urls ) {
			$urls = wp_span_checker_form_guard_extract_urls( $trimmed );
			if ( ! empty( $urls ) ) {
				return array(
					'status'  => false,
					'message' => __( 'URLs are not allowed in this field.', 'wp-span-checker' ),
				);
			}
		}

		return array( 'status' => true, 'message' => '' );
	}

	/**
	 * Username control (single-line input mapped by ID/class).
	 *
	 * @param array<string, mixed> $field .
	 */
	private static function guard_username_field( array $field, string $trimmed ): array {
		if ( ! empty( $field['check_username_exists'] ) && '0' !== (string) $field['check_username_exists'] ) {
			$u = sanitize_user( $trimmed, true );
			if ( $u && username_exists( $u ) ) {
				return array(
					'status'  => false,
					'message' => __( 'This username is already registered.', 'wp-span-checker' ),
				);
			}
		}

		return array( 'status' => true, 'message' => '' );
	}

	/**
	 * @param array<string, mixed> $field .
	 */
	private static function guard_textarea_field( array $field, string $trimmed ): array {
		$allow_links = ! isset( $field['textarea_allow_links'] ) || '0' !== (string) $field['textarea_allow_links'];
		if ( ! $allow_links ) {
			$urls = wp_span_checker_form_guard_extract_urls( $trimmed );
			if ( ! empty( $urls ) ) {
				return array(
					'status'  => false,
					'message' => __( 'Links are not allowed in this field.', 'wp-span-checker' ),
				);
			}
		}

		if ( ! empty( $field['textarea_ai_spam'] ) && '0' !== (string) $field['textarea_ai_spam'] ) {
			$system = 'You screen contact-form messages for spam, phishing, and scams. Reply ONLY with compact JSON: {"status":"ok"|"spam","message":"short reason"}. ok means legitimate human message.';
			$chunk  = function_exists( 'mb_substr' ) ? mb_substr( $trimmed, 0, 4000 ) : substr( $trimmed, 0, 4000 );
			$ai_res = AI_Span_Completion::complete( $system, "Message:\n" . $chunk );
			if ( is_wp_error( $ai_res ) ) {
				return array( 'status' => false, 'message' => $ai_res->get_error_message() );
			}
			$verdict = AI_Span_Completion::parse_json_verdict( $ai_res );
			if ( is_wp_error( $verdict ) ) {
				return array( 'status' => false, 'message' => $verdict->get_error_message() );
			}
			if ( 'spam' === $verdict['status'] ) {
				return array(
					'status'  => false,
					'message' => '' !== $verdict['message']
						? $verdict['message']
						: __( 'This message looks like spam.', 'wp-span-checker' ),
				);
			}
		}

		return array( 'status' => true, 'message' => '' );
	}

	/**
	 * @param array<string, mixed> $field .
	 * @param array<string, mixed> $mapping_row .
	 */
	private static function guard_email_or_url_field( string $field_type, array $field, string $trimmed, array $mapping_row ): array {
		$needs_domain_api = ! empty( $field['isValidate'] ) && '0' !== (string) $field['isValidate'];
		if ( ! $needs_domain_api ) {
			return array( 'status' => true, 'message' => '' );
		}

		$flags   = wp_span_checker_form_guard_field_api_flags( $field, $mapping_row );
		$api_row = array(
			'is_webrisk'    => $flags['is_webrisk'],
			'is_virustotal' => $flags['is_virustotal'],
		);
		$ip        = wp_span_checker_get_user_ip();
		$validator = new Domain_Validator();

		if ( 'email' === $field_type ) {
			$email = sanitize_email( $trimmed );
			if ( ! is_email( $email ) ) {
				return array(
					'status'  => false,
					'message' => __( 'Email address is invalid.', 'wp-span-checker' ),
				);
			}
			$host = '';
			$at   = strpos( $email, '@' );
			if ( false !== $at ) {
				$host = strtolower( substr( $email, $at + 1 ) );
			}
			if ( '' === $host ) {
				return array(
					'status'  => false,
					'message' => __( 'Email address is invalid.', 'wp-span-checker' ),
				);
			}
			try {
				$result = $validator->validate_domain( $host, 'email', $ip, $api_row );

				return array(
					'status'  => ! empty( $result['status'] ),
					'message' => $result['message'] ?? '',
				);
			} catch ( Exception $e ) {
				return array( 'status' => false, 'message' => $e->getMessage() );
			}
		}

		// url.
		$host = wp_span_checker_normalize_domain_input( $trimmed );
		if ( '' === $host ) {
			return array(
				'status'  => false,
				'message' => __( 'URL not valid.', 'wp-span-checker' ),
			);
		}
		try {
			$result = $validator->validate_domain( $host, 'url', $ip, $api_row );

			return array(
				'status'  => ! empty( $result['status'] ),
				'message' => $result['message'] ?? '',
			);
		} catch ( Exception $e ) {
			return array( 'status' => false, 'message' => $e->getMessage() );
		}
	}

	/**
	 * tel / number / password: regex + required only (already handled).
	 *
	 * @param array<string, mixed> $field Unused for now.
	 */
	private static function guard_generic_field( string $field_type, array $field, string $trimmed ): array {
		return array( 'status' => true, 'message' => '' );
	}
}
