<?php
/**
 * WP Span Checker - Email Template Functions
 *
 * Wrapper functions that use the Email_Templates class with customizable settings.
 *
 * @package WP_Span_Checker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get combined email verification (activation link + OTP code).
 *
 * @param string $activation_url Activation URL.
 * @param string $otp            OTP code.
 * @param string $name           User name.
 * @param string $email          User email.
 * @param int    $otp_expires    Minutes until OTP expiry.
 * @param int    $link_expires   Hours until link expiry.
 * @return string
 */
function wsc_email_verification( $activation_url, $otp, $name = '', $email = '', $otp_expires = 10, $link_expires = 24 ) {
	if ( class_exists( '\WP_Span_Checker\Email_Templates' ) ) {
		return \WP_Span_Checker\Email_Templates::generate_email(
			'email_verification',
			array(
				'name'         => $name,
				'email'        => $email,
				'url'          => $activation_url,
				'otp'          => $otp,
				'otp_expires'  => $otp_expires,
				'link_expires' => $link_expires,
				'title'        => __( 'Verify Your Email', 'wp-span-checker' ),
			)
		);
	}
	return wsc_email_fallback_verification( $activation_url, $otp, $name, $email, $otp_expires );
}

/**
 * Legacy: Get OTP verification email (redirects to combined).
 *
 * @param string $otp     OTP code.
 * @param string $name    User name.
 * @param int    $expires Minutes until expiry.
 * @return string
 */
function wsc_email_otp_verification( $otp, $name = '', $expires = 10 ) {
	return wsc_email_verification( '#', $otp, $name, '', $expires, 24 );
}

/**
 * Legacy: Get account activation email (redirects to combined).
 *
 * @param string $activation_url Activation URL.
 * @param string $name           User name.
 * @param int    $expires        Hours until expiry.
 * @return string
 */
function wsc_email_activation( $activation_url, $name = '', $expires = 24 ) {
	return wsc_email_verification( $activation_url, '------', $name, '', 10, $expires );
}

/**
 * Get password reset email using Email_Templates.
 *
 * @param string $reset_url Reset URL.
 * @param string $name      User name.
 * @return string
 */
function wsc_email_password_reset( $reset_url, $name = '' ) {
	if ( class_exists( '\WP_Span_Checker\Email_Templates' ) ) {
		return \WP_Span_Checker\Email_Templates::generate_email(
			'password_reset',
			array(
				'name'  => $name,
				'url'   => $reset_url,
				'title' => __( 'Reset Your Password', 'wp-span-checker' ),
			)
		);
	}
	return wsc_email_fallback_password_reset( $reset_url, $name );
}

/**
 * Get welcome email using Email_Templates.
 *
 * @param string $name      User name.
 * @param string $login_url Login URL.
 * @return string
 */
function wsc_email_welcome( $name = '', $login_url = '' ) {
	$login_url = $login_url ?: wp_login_url();
	
	if ( class_exists( '\WP_Span_Checker\Email_Templates' ) ) {
		return \WP_Span_Checker\Email_Templates::generate_email(
			'welcome',
			array(
				'name'      => $name,
				'login_url' => $login_url,
				'title'     => __( 'Welcome', 'wp-span-checker' ),
			)
		);
	}
	return wsc_email_fallback_welcome( $name, $login_url );
}

/**
 * Get account blocked email using Email_Templates.
 *
 * @param string $name   User name.
 * @param string $reason Block reason.
 * @return string
 */
function wsc_email_account_blocked( $name = '', $reason = '' ) {
	if ( class_exists( '\WP_Span_Checker\Email_Templates' ) ) {
		return \WP_Span_Checker\Email_Templates::generate_email(
			'account_blocked',
			array(
				'name'   => $name,
				'reason' => $reason,
				'title'  => __( 'Account Notice', 'wp-span-checker' ),
			)
		);
	}
	return '';
}

/**
 * Get login alert email using Email_Templates.
 *
 * @param string $name     User name.
 * @param string $ip       IP address.
 * @param string $location Location.
 * @param string $time     Login time.
 * @return string
 */
function wsc_email_login_alert( $name = '', $ip = '', $location = '', $time = '' ) {
	if ( class_exists( '\WP_Span_Checker\Email_Templates' ) ) {
		return \WP_Span_Checker\Email_Templates::generate_email(
			'login_alert',
			array(
				'name'     => $name,
				'ip'       => $ip,
				'location' => $location,
				'time'     => $time ?: current_time( 'F j, Y g:i A' ),
				'title'    => __( 'New Login Alert', 'wp-span-checker' ),
			)
		);
	}
	return '';
}

/**
 * Send HTML email using WordPress.
 *
 * @param string $to      Recipient email.
 * @param string $subject Email subject.
 * @param string $body    Email body (HTML).
 * @return bool
 */
function wsc_send_html_email( $to, $subject, $body ) {
	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
	);

	if ( class_exists( '\WP_Span_Checker\Auth_Forms' ) ) {
		$smtp_settings = \WP_Span_Checker\Auth_Forms::get_smtp_settings();
		if ( ! empty( $smtp_settings['from_name'] ) && ! empty( $smtp_settings['from_email'] ) ) {
			$headers[] = 'From: ' . $smtp_settings['from_name'] . ' <' . $smtp_settings['from_email'] . '>';
		}
	}

	return wp_mail( $to, $subject, $body, $headers );
}

// -----------------------------------------------------------------
// Fallback functions (used only if Email_Templates class is missing)
// -----------------------------------------------------------------

/**
 * Fallback combined verification email template.
 */
function wsc_email_fallback_verification( $url, $otp, $name, $email, $otp_expires ) {
	$greeting  = $name ? sprintf( __( 'Hello %s,', 'wp-span-checker' ), $name ) : __( 'Hello,', 'wp-span-checker' );
	$site_name = get_bloginfo( 'name' );

	return '<!DOCTYPE html><html><body style="font-family: sans-serif; background: #f4f4f5; padding: 40px;">
		<div style="max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden;">
			<div style="background: linear-gradient(135deg, #2563eb, #1d4ed8); padding: 30px; text-align: center;">
				<h1 style="color: #fff; margin: 0;">' . esc_html( $site_name ) . '</h1>
			</div>
			<div style="padding: 40px 30px;">
				<h2 style="margin: 0 0 20px;">' . esc_html( $greeting ) . '</h2>
				<p>' . esc_html__( 'To complete your sign up, we need to verify your email address.', 'wp-span-checker' ) . ( $email ? ' <strong>' . esc_html( $email ) . '</strong>' : '' ) . '</p>

				<p style="text-align: center; margin: 25px 0;"><a href="' . esc_url( $url ) . '" style="background: #2563eb; color: #fff; padding: 14px 28px; border-radius: 6px; text-decoration: none; font-weight: 500; display: inline-block;">' . esc_html__( 'Verify Email Address', 'wp-span-checker' ) . '</a></p>
				<p style="text-align: center; font-size: 12px; color: #6b7280; word-break: break-all;">' . esc_html( $url ) . '</p>

				<div style="text-align: center; margin: 30px 0;">
					<span style="background: #fff; padding: 0 15px; color: #6b7280; font-size: 13px;">' . esc_html__( 'OR VERIFY WITH CODE', 'wp-span-checker' ) . '</span>
					<hr style="border: none; border-top: 1px solid #e5e7eb; margin-top: -10px;">
				</div>

				<div style="background: #f3f4f6; padding: 25px; text-align: center; border-radius: 8px; margin: 20px 0;">
					<span style="font-size: 36px; font-weight: bold; letter-spacing: 10px; color: #2563eb; font-family: monospace;">' . esc_html( $otp ) . '</span>
					<p style="margin: 12px 0 0; color: #6b7280; font-size: 13px;">' . sprintf( esc_html__( 'Code expires in %d minutes', 'wp-span-checker' ), $otp_expires ) . '</p>
				</div>

				<div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px 16px; margin: 20px 0; font-size: 14px; color: #92400e; border-radius: 0 8px 8px 0;">' . esc_html__( 'If you did not create an account, please ignore this email.', 'wp-span-checker' ) . '</div>
			</div>
		</div>
	</body></html>';
}

/**
 * Fallback password reset email template.
 */
function wsc_email_fallback_password_reset( $url, $name ) {
	$greeting  = $name ? sprintf( __( 'Hello %s,', 'wp-span-checker' ), $name ) : __( 'Hello,', 'wp-span-checker' );
	$site_name = get_bloginfo( 'name' );

	return '<!DOCTYPE html><html><body style="font-family: sans-serif; background: #f4f4f5; padding: 40px;">
		<div style="max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden;">
			<div style="background: #2563eb; padding: 30px; text-align: center;">
				<h1 style="color: #fff; margin: 0;">' . esc_html( $site_name ) . '</h1>
			</div>
			<div style="padding: 40px 30px;">
				<h2 style="margin: 0 0 20px;">' . esc_html( $greeting ) . '</h2>
				<p>' . esc_html__( 'Click the button below to reset your password:', 'wp-span-checker' ) . '</p>
				<p style="text-align: center; margin: 30px 0;"><a href="' . esc_url( $url ) . '" style="background: #2563eb; color: #fff; padding: 14px 28px; border-radius: 6px; text-decoration: none; font-weight: 500;">' . esc_html__( 'Reset Password', 'wp-span-checker' ) . '</a></p>
			</div>
		</div>
	</body></html>';
}

/**
 * Fallback welcome email template.
 */
function wsc_email_fallback_welcome( $name, $login_url ) {
	$greeting  = $name ? sprintf( __( 'Welcome %s!', 'wp-span-checker' ), $name ) : __( 'Welcome!', 'wp-span-checker' );
	$site_name = get_bloginfo( 'name' );

	return '<!DOCTYPE html><html><body style="font-family: sans-serif; background: #f4f4f5; padding: 40px;">
		<div style="max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden;">
			<div style="background: #2563eb; padding: 30px; text-align: center;">
				<h1 style="color: #fff; margin: 0;">' . esc_html( $site_name ) . '</h1>
			</div>
			<div style="padding: 40px 30px;">
				<h2 style="margin: 0 0 20px;">' . esc_html( $greeting ) . '</h2>
				<p>' . sprintf( esc_html__( 'Your account has been created on %s.', 'wp-span-checker' ), esc_html( $site_name ) ) . '</p>
				<p style="text-align: center; margin: 30px 0;"><a href="' . esc_url( $login_url ) . '" style="background: #2563eb; color: #fff; padding: 14px 28px; border-radius: 6px; text-decoration: none; font-weight: 500;">' . esc_html__( 'Log In Now', 'wp-span-checker' ) . '</a></p>
			</div>
		</div>
	</body></html>';
}
