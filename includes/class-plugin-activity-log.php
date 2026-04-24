<?php
/**
 * Records wp-login events into {@see Logger} for the Tools console.
 *
 * @package WP_Span_Checker
 */

namespace WP_Span_Checker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hooks wp_login / wp_login_failed.
 */
class Plugin_Activity_Log {

	public function __construct() {
		add_action( 'wp_login', array( $this, 'on_wp_login' ), 10, 2 );
		add_action( 'wp_login_failed', array( $this, 'on_wp_login_failed' ) );
	}

	/**
	 * @param string   $user_login .
	 * @param \WP_User $user       .
	 */
	public function on_wp_login( $user_login, $user ): void {
		unset( $user );
		$ip = function_exists( 'wp_span_checker_get_user_ip' ) ? wp_span_checker_get_user_ip() : '';
		( new Logger() )->log(
			'wp_login',
			$ip,
			'',
			'success',
			sprintf(
				/* translators: %s: user login (not password). */
				__( 'Signed in: %s', 'wp-span-checker' ),
				sanitize_user( (string) $user_login, true )
			)
		);
	}

	/**
	 * @param string $username Attempted username or email.
	 */
	public function on_wp_login_failed( $username ): void {
		$ip = function_exists( 'wp_span_checker_get_user_ip' ) ? wp_span_checker_get_user_ip() : '';
		( new Logger() )->log(
			'wp_login_failed',
			$ip,
			'',
			'failed',
			sprintf(
				/* translators: %s: attempted username (not password). */
				__( 'Failed sign-in: %s', 'wp-span-checker' ),
				sanitize_user( (string) $username, true )
			)
		);
	}
}
