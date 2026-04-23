<?php
/**
 * WordPress registration: validate signup email domain (MX, reputation APIs, disposable list).
 *
 * @package WP_Span_Checker
 */

namespace WP_Span_Checker;

use WP_Error;
use WP_Span_Checker\Services\Domain_Validator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hooks core registration; optional WooCommerce registration.
 */
class Registration_Guard {

	public const OPTION_KEY = 'wsc-registration-guard';

	/**
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'enabled'             => false,
			'use_webrisk'         => true,
			'use_virustotal'      => true,
			'require_mx'          => true,
			'mx_allow_a_fallback' => true,
			'skip_https_check'    => true,
		);
	}

	public function __construct() {
		add_filter( 'registration_errors', array( $this, 'filter_registration_errors' ), 10, 3 );
		if ( class_exists( 'WooCommerce' ) ) {
			add_filter( 'woocommerce_registration_errors', array( $this, 'filter_wc_registration_errors' ), 10, 3 );
		}
	}

	/**
	 * @param \WP_Error $errors .
	 * @param string    $sanitized_user_login .
	 * @param string    $user_email .
	 * @return \WP_Error
	 */
	public function filter_registration_errors( $errors, $sanitized_user_login, $user_email ) {
		return $this->apply_guard( $errors, (string) $user_email );
	}

	/**
	 * @param \WP_Error $errors .
	 * @param string    $username .
	 * @param string    $email .
	 * @return \WP_Error
	 */
	public function filter_wc_registration_errors( $errors, $username, $email ) {
		return $this->apply_guard( $errors, (string) $email );
	}

	/**
	 * @param \WP_Error $errors .
	 * @param string    $user_email .
	 * @return \WP_Error
	 */
	private function apply_guard( $errors, string $user_email ) {
		if ( ! ( $errors instanceof WP_Error ) ) {
			$errors = new WP_Error();
		}

		$cfg = self::get();
		if ( empty( $cfg['enabled'] ) || $user_email === '' ) {
			return $errors;
		}

		$ip        = function_exists( 'wp_span_checker_get_user_ip' ) ? wp_span_checker_get_user_ip() : '';
		$validator = new Domain_Validator();
		$settings  = array(
			'is_webrisk'          => ! empty( $cfg['use_webrisk'] ),
			'is_virustotal'       => ! empty( $cfg['use_virustotal'] ),
			'require_mx'          => ! empty( $cfg['require_mx'] ),
			'mx_allow_a_fallback' => ! empty( $cfg['mx_allow_a_fallback'] ),
			'skip_https'          => ! empty( $cfg['skip_https_check'] ),
		);

		$result = $validator->validate_email( $user_email, 'registration', $ip, $settings );
		if ( empty( $result['status'] ) ) {
			$errors->add( 'wsc_registration_email', $result['message'] );
		}

		return $errors;
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get(): array {
		$raw = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $raw ) ) {
			$raw = array();
		}
		return array_merge( self::defaults(), $raw );
	}

	/**
	 * @param array<string, mixed> $data .
	 */
	public static function update( array $data ): void {
		$d = self::defaults();
		$merged = array_merge( $d, self::get(), $data );
		$merged['enabled']              = ! empty( $merged['enabled'] );
		$merged['use_webrisk']          = ! empty( $merged['use_webrisk'] );
		$merged['use_virustotal']       = ! empty( $merged['use_virustotal'] );
		$merged['require_mx']           = ! empty( $merged['require_mx'] );
		$merged['mx_allow_a_fallback']  = ! empty( $merged['mx_allow_a_fallback'] );
		$merged['skip_https_check']     = ! empty( $merged['skip_https_check'] );
		update_option( self::OPTION_KEY, array_merge( $d, $merged ), false );
	}
}
