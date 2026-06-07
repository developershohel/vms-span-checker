<?php
/**
 * VMS Elements Form Guard - Email Template Functions
 *
 * Builds the hardcoded inline-CSS fallback HTML for each transactional email
 * the free plugin sends, then runs it through the
 * `vms_elements_form_guard_email_template_html` filter so the Pro Email Templates
 * editor can override the markup when active.
 *
 * The `vefg_email_*` and `vefg_send_html_email` helpers are part of the plugin's
 * stable public API; the `vefg_` prefix is the legacy plugin prefix and is kept
 * to preserve backward compatibility with third-party integrations.
 *
 * @package VMS_Elements_Form_Guard
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Run the fallback HTML through the Pro bridge filter so the Email Templates
 * editor (when active) can override it.
 */
function vms_elements_form_guard_filter_email( string $template_key, string $fallback_html, array $vars ): string {
	$out = apply_filters( 'vms_elements_form_guard_email_template_html', $fallback_html, $template_key, $vars );
	return is_string( $out ) ? $out : $fallback_html;
}

/**
 * Combined email verification (activation link + OTP code).
 */
function vefg_email_verification( $activation_url, $otp, $name = '', $email = '', $otp_expires = 10, $link_expires = 24 ) {
	$vars = array(
		'name'         => $name,
		'email'        => $email,
		'url'          => $activation_url,
		'otp'          => $otp,
		'otp_expires'  => $otp_expires,
		'link_expires' => $link_expires,
		'title'        => __( 'Verify Your Email', 'vms-elements-form-guard' ),
	);
	return vms_elements_form_guard_filter_email(
		'email_verification',
		vefg_email_fallback_verification( $activation_url, $otp, $name, $email, $otp_expires ),
		$vars
	);
}

/**
 * Legacy: OTP-only verification (uses combined template with `#` URL).
 */
function vefg_email_otp_verification( $otp, $name = '', $expires = 10 ) {
	return vefg_email_verification( '#', $otp, $name, '', $expires, 24 );
}

/**
 * Legacy: account activation (uses combined template with placeholder OTP).
 */
function vefg_email_activation( $activation_url, $name = '', $expires = 24 ) {
	return vefg_email_verification( $activation_url, '------', $name, '', 10, $expires );
}

/**
 * Password reset email.
 */
function vefg_email_password_reset( $reset_url, $name = '' ) {
	$vars = array(
		'name'  => $name,
		'url'   => $reset_url,
		'title' => __( 'Reset Your Password', 'vms-elements-form-guard' ),
	);
	return vms_elements_form_guard_filter_email(
		'password_reset',
		vefg_email_fallback_password_reset( $reset_url, $name ),
		$vars
	);
}

/**
 * Welcome email.
 */
function vefg_email_welcome( $name = '', $login_url = '' ) {
	$login_url = $login_url ?: wp_login_url();
	$vars      = array(
		'name'      => $name,
		'login_url' => $login_url,
		'title'     => __( 'Welcome', 'vms-elements-form-guard' ),
	);
	return vms_elements_form_guard_filter_email(
		'welcome',
		vefg_email_fallback_welcome( $name, $login_url ),
		$vars
	);
}

/**
 * Account blocked notification.
 */
function vefg_email_account_blocked( $name = '', $reason = '' ) {
	$vars = array(
		'name'   => $name,
		'reason' => $reason,
		'title'  => __( 'Account Notice', 'vms-elements-form-guard' ),
	);
	return vms_elements_form_guard_filter_email(
		'account_blocked',
		vefg_email_fallback_account_blocked( $name, $reason ),
		$vars
	);
}

/**
 * Login alert email.
 */
function vefg_email_login_alert( $name = '', $ip = '', $location = '', $time = '' ) {
	$vars = array(
		'name'     => $name,
		'ip'       => $ip,
		'location' => $location,
		'time'     => $time ?: current_time( 'F j, Y g:i A' ),
		'title'    => __( 'New Login Alert', 'vms-elements-form-guard' ),
	);
	return vms_elements_form_guard_filter_email(
		'login_alert',
		vefg_email_fallback_login_alert( $name, $ip, $location, $vars['time'] ),
		$vars
	);
}

/**
 * Send HTML email through wp_mail with optional SMTP From header.
 *
 * @param string $to      Recipient email.
 * @param string $subject Email subject.
 * @param string $body    Email body (HTML).
 * @return bool
 */
function vefg_send_html_email( $to, $subject, $body ) {
	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
	);

	if ( class_exists( '\VMS_Elements_Form_Guard\Auth_Forms' ) ) {
		$smtp_settings = \VMS_Elements_Form_Guard\Auth_Forms::get_smtp_settings();
		if ( ! empty( $smtp_settings['from_name'] ) && ! empty( $smtp_settings['from_email'] ) ) {
			$headers[] = 'From: ' . $smtp_settings['from_name'] . ' <' . $smtp_settings['from_email'] . '>';
		}
	}

	return wp_mail( $to, $subject, $body, $headers );
}

// -----------------------------------------------------------------
// Hardcoded inline-CSS fallback templates (used when Pro Email
// Templates is not installed / inactive).
// -----------------------------------------------------------------

function vefg_email_fallback_verification( $url, $otp, $name, $email, $otp_expires ) {
	$greeting = $name
		? sprintf( /* translators: %s: recipient name */ __( 'Hello %s,', 'vms-elements-form-guard' ), $name )
		: __( 'Hello,', 'vms-elements-form-guard' );
	$site_name = get_bloginfo( 'name' );

	return '<!DOCTYPE html><html><body style="font-family: sans-serif; background: #f4f4f5; padding: 40px;">
		<div style="max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden;">
			<div style="background: linear-gradient(135deg, #2563eb, #1d4ed8); padding: 30px; text-align: center;">
				<h1 style="color: #fff; margin: 0;">' . esc_html( $site_name ) . '</h1>
			</div>
			<div style="padding: 40px 30px;">
				<h2 style="margin: 0 0 20px;">' . esc_html( $greeting ) . '</h2>
				<p>' . esc_html__( 'To complete your sign up, we need to verify your email address.', 'vms-elements-form-guard' ) . ( $email ? ' <strong>' . esc_html( $email ) . '</strong>' : '' ) . '</p>

				<p style="text-align: center; margin: 25px 0;"><a href="' . esc_url( $url ) . '" style="background: #2563eb; color: #fff; padding: 14px 28px; border-radius: 6px; text-decoration: none; font-weight: 500; display: inline-block;">' . esc_html__( 'Verify Email Address', 'vms-elements-form-guard' ) . '</a></p>
				<p style="text-align: center; font-size: 12px; color: #6b7280; word-break: break-all;">' . esc_html( $url ) . '</p>

				<div style="text-align: center; margin: 30px 0;">
					<span style="background: #fff; padding: 0 15px; color: #6b7280; font-size: 13px;">' . esc_html__( 'OR VERIFY WITH CODE', 'vms-elements-form-guard' ) . '</span>
					<hr style="border: none; border-top: 1px solid #e5e7eb; margin-top: -10px;">
				</div>

				<div style="background: #f3f4f6; padding: 25px; text-align: center; border-radius: 8px; margin: 20px 0;">
					<span style="font-size: 36px; font-weight: bold; letter-spacing: 10px; color: #2563eb; font-family: monospace;">' . esc_html( $otp ) . '</span>
					<p style="margin: 12px 0 0; color: #6b7280; font-size: 13px;">' .
					sprintf(
						/* translators: %d: number of minutes until OTP code expires */
						esc_html__( 'Code expires in %d minutes', 'vms-elements-form-guard' ),
						(int) $otp_expires
					) . '</p>
				</div>

				<div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px 16px; margin: 20px 0; font-size: 14px; color: #92400e; border-radius: 0 8px 8px 0;">' . esc_html__( 'If you did not create an account, please ignore this email.', 'vms-elements-form-guard' ) . '</div>
			</div>
		</div>
	</body></html>';
}

function vefg_email_fallback_password_reset( $url, $name ) {
	$greeting = $name
		? sprintf( /* translators: %s: recipient name */ __( 'Hello %s,', 'vms-elements-form-guard' ), $name )
		: __( 'Hello,', 'vms-elements-form-guard' );
	$site_name = get_bloginfo( 'name' );

	return '<!DOCTYPE html><html><body style="font-family: sans-serif; background: #f4f4f5; padding: 40px;">
		<div style="max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden;">
			<div style="background: #2563eb; padding: 30px; text-align: center;">
				<h1 style="color: #fff; margin: 0;">' . esc_html( $site_name ) . '</h1>
			</div>
			<div style="padding: 40px 30px;">
				<h2 style="margin: 0 0 20px;">' . esc_html( $greeting ) . '</h2>
				<p>' . esc_html__( 'Click the button below to reset your password:', 'vms-elements-form-guard' ) . '</p>
				<p style="text-align: center; margin: 30px 0;"><a href="' . esc_url( $url ) . '" style="background: #2563eb; color: #fff; padding: 14px 28px; border-radius: 6px; text-decoration: none; font-weight: 500;">' . esc_html__( 'Reset Password', 'vms-elements-form-guard' ) . '</a></p>
			</div>
		</div>
	</body></html>';
}

function vefg_email_fallback_welcome( $name, $login_url ) {
	$greeting = $name
		? sprintf( /* translators: %s: recipient name */ __( 'Welcome %s!', 'vms-elements-form-guard' ), $name )
		: __( 'Welcome!', 'vms-elements-form-guard' );
	$site_name = get_bloginfo( 'name' );

	return '<!DOCTYPE html><html><body style="font-family: sans-serif; background: #f4f4f5; padding: 40px;">
		<div style="max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden;">
			<div style="background: #2563eb; padding: 30px; text-align: center;">
				<h1 style="color: #fff; margin: 0;">' . esc_html( $site_name ) . '</h1>
			</div>
			<div style="padding: 40px 30px;">
				<h2 style="margin: 0 0 20px;">' . esc_html( $greeting ) . '</h2>
				<p>' .
				sprintf(
					/* translators: %s: site name */
					esc_html__( 'Your account has been created on %s.', 'vms-elements-form-guard' ),
					esc_html( $site_name )
				) . '</p>
				<p style="text-align: center; margin: 30px 0;"><a href="' . esc_url( $login_url ) . '" style="background: #2563eb; color: #fff; padding: 14px 28px; border-radius: 6px; text-decoration: none; font-weight: 500;">' . esc_html__( 'Log In Now', 'vms-elements-form-guard' ) . '</a></p>
			</div>
		</div>
	</body></html>';
}

function vefg_email_fallback_account_blocked( $name, $reason ) {
	$greeting = $name
		? sprintf( /* translators: %s: recipient name */ __( 'Hello %s,', 'vms-elements-form-guard' ), $name )
		: __( 'Hello,', 'vms-elements-form-guard' );
	$site_name = get_bloginfo( 'name' );

	return '<!DOCTYPE html><html><body style="font-family: sans-serif; background: #f4f4f5; padding: 40px;">
		<div style="max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden;">
			<div style="background: #b91c1c; padding: 30px; text-align: center;">
				<h1 style="color: #fff; margin: 0;">' . esc_html( $site_name ) . '</h1>
			</div>
			<div style="padding: 40px 30px;">
				<h2 style="margin: 0 0 20px;">' . esc_html( $greeting ) . '</h2>
				<p>' . esc_html__( 'Your account has been blocked due to suspicious activity.', 'vms-elements-form-guard' ) . '</p>
				' . ( $reason ? '<div style="background: #fef2f2; border-left: 4px solid #b91c1c; padding: 12px 16px; margin: 20px 0; font-size: 14px; color: #7f1d1d; border-radius: 0 8px 8px 0;">' . esc_html( $reason ) . '</div>' : '' ) . '
				<p>' . esc_html__( 'Contact site support if you think this is a mistake.', 'vms-elements-form-guard' ) . '</p>
			</div>
		</div>
	</body></html>';
}

function vefg_email_fallback_login_alert( $name, $ip, $location, $time ) {
	$greeting = $name
		? sprintf( /* translators: %s: recipient name */ __( 'Hello %s,', 'vms-elements-form-guard' ), $name )
		: __( 'Hello,', 'vms-elements-form-guard' );
	$site_name = get_bloginfo( 'name' );

	return '<!DOCTYPE html><html><body style="font-family: sans-serif; background: #f4f4f5; padding: 40px;">
		<div style="max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden;">
			<div style="background: #2563eb; padding: 30px; text-align: center;">
				<h1 style="color: #fff; margin: 0;">' . esc_html( $site_name ) . '</h1>
			</div>
			<div style="padding: 40px 30px;">
				<h2 style="margin: 0 0 20px;">' . esc_html( $greeting ) . '</h2>
				<p>' . esc_html__( 'A new sign-in was detected on your account.', 'vms-elements-form-guard' ) . '</p>
				<div style="background: #f3f4f6; padding: 18px 20px; border-radius: 8px; margin: 18px 0; font-size: 14px;">
					<p style="margin:6px 0;"><strong>' . esc_html__( 'Time:', 'vms-elements-form-guard' ) . '</strong> ' . esc_html( $time ) . '</p>
					<p style="margin:6px 0;"><strong>' . esc_html__( 'IP:', 'vms-elements-form-guard' ) . '</strong> ' . esc_html( $ip ) . '</p>
					' . ( $location ? '<p style="margin:6px 0;"><strong>' . esc_html__( 'Location:', 'vms-elements-form-guard' ) . '</strong> ' . esc_html( $location ) . '</p>' : '' ) . '
				</div>
				<p style="font-size: 12px; color: #6b7280;">' . esc_html__( 'If this was not you, please change your password immediately.', 'vms-elements-form-guard' ) . '</p>
			</div>
		</div>
	</body></html>';
}
