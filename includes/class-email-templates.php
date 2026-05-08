<?php
/**
 * Email Templates - Customizable email templates with design controls.
 *
 * @package WP_Span_Checker
 */

namespace WP_Span_Checker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Email Templates handler.
 */
class Email_Templates {

	/**
	 * Option key for email template settings.
	 */
	const OPTION_KEY = 'wsc_email_template_settings';

	/**
	 * Available email types.
	 *
	 * @var array
	 */
	private static $email_types = array(
		'email_verification' => 'Email Verification',
		'password_reset'     => 'Password Reset',
		'welcome'            => 'Welcome Email',
		'account_blocked'    => 'Account Blocked',
		'login_alert'        => 'Login Alert',
	);

	/**
	 * Constructor - register hooks.
	 */
	public function __construct() {
		add_action( 'wp_ajax_wsc_save_email_template_settings', array( $this, 'ajax_save_settings' ) );
		add_action( 'wp_ajax_wsc_preview_email_template', array( $this, 'ajax_preview_template' ) );
		add_action( 'wp_ajax_wsc_send_test_email', array( $this, 'ajax_send_test_email' ) );
	}

	/**
	 * Get default settings.
	 *
	 * @return array
	 */
	public static function get_defaults() {
		return array(
			// Colors
			'header_bg_color'      => '#2563eb',
			'header_text_color'    => '#ffffff',
			'body_bg_color'        => '#f4f4f5',
			'content_bg_color'     => '#ffffff',
			'text_color'           => '#374151',
			'heading_color'        => '#1f2937',
			'link_color'           => '#2563eb',
			'button_bg_color'      => '#2563eb',
			'button_text_color'    => '#ffffff',
			'button_hover_color'   => '#1d4ed8',
			'footer_bg_color'      => '#f9fafb',
			'footer_text_color'    => '#6b7280',
			'border_color'         => '#e5e7eb',
			'otp_bg_color'         => '#f3f4f6',
			'otp_text_color'       => '#2563eb',
			'warning_bg_color'     => '#fef3c7',
			'warning_text_color'   => '#92400e',
			'info_bg_color'        => '#eff6ff',
			'info_text_color'      => '#1e40af',
			// Layout
			'container_width'      => '600',
			'border_radius'        => '8',
			'content_padding'      => '40',
			// Branding
			'show_logo'            => true,
			'logo_url'             => '',
			'logo_width'           => '120',
			'company_name'         => '',
			'company_address'      => '',
			// Footer
			'show_social_links'    => false,
			'facebook_url'         => '',
			'twitter_url'          => '',
			'instagram_url'        => '',
			'linkedin_url'         => '',
		);
	}

	/**
	 * Get current settings.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$saved = get_option( self::OPTION_KEY, array() );
		return wp_parse_args( $saved, self::get_defaults() );
	}

	/**
	 * Get email types.
	 *
	 * @return array
	 */
	public static function get_email_types() {
		return self::$email_types;
	}

	/**
	 * Generate email HTML.
	 *
	 * @param string $type    Email type.
	 * @param array  $data    Email data.
	 * @param array  $settings Optional settings override.
	 * @return string
	 */
	public static function generate_email( $type, $data = array(), $settings = null ) {
		if ( null === $settings ) {
			$settings = self::get_settings();
		}

		$site_name = ! empty( $settings['company_name'] ) ? $settings['company_name'] : get_bloginfo( 'name' );
		$site_url  = home_url();

		// Get logo
		$logo_html = '';
		if ( ! empty( $settings['show_logo'] ) ) {
			$logo_url = $settings['logo_url'];
			if ( empty( $logo_url ) && has_custom_logo() ) {
				$logo_id  = get_theme_mod( 'custom_logo' );
				$logo_url = wp_get_attachment_image_url( $logo_id, 'medium' );
			}
			if ( $logo_url ) {
				$logo_width = absint( $settings['logo_width'] ) ?: 120;
				$logo_html  = '<img src="' . esc_url( $logo_url ) . '" alt="' . esc_attr( $site_name ) . '" style="max-width: ' . $logo_width . 'px; height: auto; margin-bottom: 15px;">';
			}
		}

		// Social links
		$social_html = '';
		if ( ! empty( $settings['show_social_links'] ) ) {
			$socials = array();
			if ( ! empty( $settings['facebook_url'] ) ) {
				$socials[] = '<a href="' . esc_url( $settings['facebook_url'] ) . '" style="color: ' . esc_attr( $settings['link_color'] ) . '; text-decoration: none; margin: 0 8px;">Facebook</a>';
			}
			if ( ! empty( $settings['twitter_url'] ) ) {
				$socials[] = '<a href="' . esc_url( $settings['twitter_url'] ) . '" style="color: ' . esc_attr( $settings['link_color'] ) . '; text-decoration: none; margin: 0 8px;">Twitter</a>';
			}
			if ( ! empty( $settings['instagram_url'] ) ) {
				$socials[] = '<a href="' . esc_url( $settings['instagram_url'] ) . '" style="color: ' . esc_attr( $settings['link_color'] ) . '; text-decoration: none; margin: 0 8px;">Instagram</a>';
			}
			if ( ! empty( $settings['linkedin_url'] ) ) {
				$socials[] = '<a href="' . esc_url( $settings['linkedin_url'] ) . '" style="color: ' . esc_attr( $settings['link_color'] ) . '; text-decoration: none; margin: 0 8px;">LinkedIn</a>';
			}
			if ( ! empty( $socials ) ) {
				$social_html = '<p style="margin: 15px 0 0;">' . implode( ' | ', $socials ) . '</p>';
			}
		}

		// Generate content based on type
		$content = self::get_email_content( $type, $data, $settings );

		// Build full email
		$html = '<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>' . esc_html( $data['subject'] ?? $site_name ) . '</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen, Ubuntu, sans-serif; background-color: ' . esc_attr( $settings['body_bg_color'] ) . ';">
	<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: ' . esc_attr( $settings['body_bg_color'] ) . ';">
		<tr>
			<td align="center" style="padding: 40px 20px;">
				<table role="presentation" width="' . absint( $settings['container_width'] ) . '" cellspacing="0" cellpadding="0" style="max-width: ' . absint( $settings['container_width'] ) . 'px; width: 100%;">
					<!-- Header -->
					<tr>
						<td align="center" style="background: linear-gradient(135deg, ' . esc_attr( $settings['header_bg_color'] ) . ', ' . esc_attr( $settings['button_hover_color'] ) . '); padding: 30px; border-radius: ' . absint( $settings['border_radius'] ) . 'px ' . absint( $settings['border_radius'] ) . 'px 0 0;">
							' . $logo_html . '
							<h1 style="color: ' . esc_attr( $settings['header_text_color'] ) . '; margin: 0; font-size: 24px; font-weight: 600;">' . esc_html( $data['title'] ?? $site_name ) . '</h1>
						</td>
					</tr>
					<!-- Content -->
					<tr>
						<td style="background: ' . esc_attr( $settings['content_bg_color'] ) . '; padding: ' . absint( $settings['content_padding'] ) . 'px; border-left: 1px solid ' . esc_attr( $settings['border_color'] ) . '; border-right: 1px solid ' . esc_attr( $settings['border_color'] ) . ';">
							' . $content . '
						</td>
					</tr>
					<!-- Footer -->
					<tr>
						<td align="center" style="background: ' . esc_attr( $settings['footer_bg_color'] ) . '; padding: 25px 30px; border: 1px solid ' . esc_attr( $settings['border_color'] ) . '; border-top: none; border-radius: 0 0 ' . absint( $settings['border_radius'] ) . 'px ' . absint( $settings['border_radius'] ) . 'px;">
							<p style="margin: 0; font-size: 13px; color: ' . esc_attr( $settings['footer_text_color'] ) . ';">&copy; ' . gmdate( 'Y' ) . ' ' . esc_html( $site_name ) . '</p>
							' . ( ! empty( $settings['company_address'] ) ? '<p style="margin: 5px 0 0; font-size: 12px; color: ' . esc_attr( $settings['footer_text_color'] ) . ';">' . esc_html( $settings['company_address'] ) . '</p>' : '' ) . '
							<p style="margin: 10px 0 0;"><a href="' . esc_url( $site_url ) . '" style="color: ' . esc_attr( $settings['link_color'] ) . '; text-decoration: none; font-size: 13px;">' . esc_html( $site_url ) . '</a></p>
							' . $social_html . '
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</body>
</html>';

		return $html;
	}

	/**
	 * Get email content by type.
	 *
	 * @param string $type     Email type.
	 * @param array  $data     Email data.
	 * @param array  $settings Settings.
	 * @return string
	 */
	private static function get_email_content( $type, $data, $settings ) {
		$name       = $data['name'] ?? '';
		$greeting   = $name ? sprintf( __( 'Hello %s,', 'wp-span-checker' ), $name ) : __( 'Hello,', 'wp-span-checker' );
		$btn_style  = 'display: inline-block; background: ' . esc_attr( $settings['button_bg_color'] ) . '; color: ' . esc_attr( $settings['button_text_color'] ) . ' !important; text-decoration: none; padding: 14px 28px; border-radius: ' . absint( $settings['border_radius'] ) . 'px; font-weight: 500; font-size: 16px;';
		$link_style = 'color: ' . esc_attr( $settings['link_color'] ) . '; word-break: break-all;';

		switch ( $type ) {
			case 'email_verification':
				$otp         = $data['otp'] ?? '123456';
				$url         = $data['url'] ?? '#';
				$otp_expires = $data['otp_expires'] ?? 10;
				$link_expires = $data['link_expires'] ?? 24;
				$email       = $data['email'] ?? '';
				return '
					<h2 style="color: ' . esc_attr( $settings['heading_color'] ) . '; margin: 0 0 20px; font-size: 20px;">' . esc_html( $greeting ) . '</h2>
					<p style="color: ' . esc_attr( $settings['text_color'] ) . '; margin: 0 0 16px; line-height: 1.6;">' . esc_html__( 'Thank you for registering! To complete your sign up, we need to verify your email address.', 'wp-span-checker' ) . ( $email ? ' <strong>' . esc_html( $email ) . '</strong>' : '' ) . '</p>

					<!-- Activation Link -->
					<p style="text-align: center; margin: 25px 0 15px;"><a href="' . esc_url( $url ) . '" style="' . $btn_style . '">' . esc_html__( 'Verify Email Address', 'wp-span-checker' ) . '</a></p>
					<p style="font-size: 13px; color: ' . esc_attr( $settings['footer_text_color'] ) . '; text-align: center; margin: 0 0 20px;">' . esc_html__( 'Button not working? Copy and paste this link:', 'wp-span-checker' ) . '<br><a href="' . esc_url( $url ) . '" style="' . $link_style . ' font-size: 12px;">' . esc_html( $url ) . '</a></p>

					<!-- Divider -->
					<div style="text-align: center; margin: 30px 0;">
						<span style="background: ' . esc_attr( $settings['content_bg_color'] ) . '; padding: 0 15px; color: ' . esc_attr( $settings['footer_text_color'] ) . '; font-size: 13px; position: relative;">
							' . esc_html__( 'OR VERIFY WITH CODE', 'wp-span-checker' ) . '
						</span>
						<hr style="border: none; border-top: 1px solid ' . esc_attr( $settings['border_color'] ) . '; margin-top: -10px;">
					</div>

					<!-- OTP Code -->
					<p style="color: ' . esc_attr( $settings['text_color'] ) . '; margin: 0 0 10px; text-align: center; line-height: 1.6;">' . esc_html__( 'Enter this verification code to verify your email:', 'wp-span-checker' ) . '</p>
					<div style="background: ' . esc_attr( $settings['otp_bg_color'] ) . '; border-radius: ' . absint( $settings['border_radius'] ) . 'px; padding: 25px; text-align: center; margin: 15px 0 25px;">
						<div style="font-size: 36px; font-weight: 700; letter-spacing: 10px; color: ' . esc_attr( $settings['otp_text_color'] ) . '; font-family: monospace;">' . esc_html( $otp ) . '</div>
						<p style="font-size: 13px; color: ' . esc_attr( $settings['footer_text_color'] ) . '; margin: 12px 0 0;">' . sprintf( esc_html__( 'Code expires in %d minutes', 'wp-span-checker' ), $otp_expires ) . '</p>
					</div>

					<div style="background: ' . esc_attr( $settings['warning_bg_color'] ) . '; border-left: 4px solid #f59e0b; padding: 12px 16px; margin: 20px 0; font-size: 14px; color: ' . esc_attr( $settings['warning_text_color'] ) . '; border-radius: 0 ' . absint( $settings['border_radius'] ) . 'px ' . absint( $settings['border_radius'] ) . 'px 0;">' . esc_html__( 'If you did not create an account, please ignore this email.', 'wp-span-checker' ) . '</div>';

			case 'otp_verification':
			case 'email_activation':
				return self::get_email_content( 'email_verification', $data, $settings );

			case 'password_reset':
				$url = $data['url'] ?? '#';
				return '
					<h2 style="color: ' . esc_attr( $settings['heading_color'] ) . '; margin: 0 0 20px; font-size: 20px;">' . esc_html( $greeting ) . '</h2>
					<p style="color: ' . esc_attr( $settings['text_color'] ) . '; margin: 0 0 16px; line-height: 1.6;">' . esc_html__( 'We received a request to reset your password. Click the button below to create a new password:', 'wp-span-checker' ) . '</p>
					<p style="text-align: center; margin: 30px 0;"><a href="' . esc_url( $url ) . '" style="' . $btn_style . '">' . esc_html__( 'Reset Password', 'wp-span-checker' ) . '</a></p>
					<p style="color: ' . esc_attr( $settings['text_color'] ) . '; margin: 0 0 10px; line-height: 1.6;">' . esc_html__( 'Or copy and paste this link into your browser:', 'wp-span-checker' ) . '</p>
					<p><a href="' . esc_url( $url ) . '" style="' . $link_style . '">' . esc_html( $url ) . '</a></p>
					<div style="background: ' . esc_attr( $settings['warning_bg_color'] ) . '; border-left: 4px solid #f59e0b; padding: 12px 16px; margin: 20px 0; font-size: 14px; color: ' . esc_attr( $settings['warning_text_color'] ) . '; border-radius: 0 ' . absint( $settings['border_radius'] ) . 'px ' . absint( $settings['border_radius'] ) . 'px 0;">' . esc_html__( 'If you did not request a password reset, please ignore this email.', 'wp-span-checker' ) . '</div>';

			case 'welcome':
				$login_url = $data['login_url'] ?? wp_login_url();
				return '
					<h2 style="color: ' . esc_attr( $settings['heading_color'] ) . '; margin: 0 0 20px; font-size: 20px;">' . esc_html( $greeting ) . '</h2>
					<p style="color: ' . esc_attr( $settings['text_color'] ) . '; margin: 0 0 16px; line-height: 1.6;">' . sprintf( esc_html__( 'Your account has been successfully created on %s.', 'wp-span-checker' ), '<strong>' . esc_html( get_bloginfo( 'name' ) ) . '</strong>' ) . '</p>
					<p style="color: ' . esc_attr( $settings['text_color'] ) . '; margin: 0 0 16px; line-height: 1.6;">' . esc_html__( 'You can now log in to access your account:', 'wp-span-checker' ) . '</p>
					<p style="text-align: center; margin: 30px 0;"><a href="' . esc_url( $login_url ) . '" style="' . $btn_style . '">' . esc_html__( 'Log In Now', 'wp-span-checker' ) . '</a></p>
					<div style="background: ' . esc_attr( $settings['info_bg_color'] ) . '; border-left: 4px solid ' . esc_attr( $settings['link_color'] ) . '; padding: 12px 16px; margin: 20px 0; font-size: 14px; color: ' . esc_attr( $settings['info_text_color'] ) . '; border-radius: 0 ' . absint( $settings['border_radius'] ) . 'px ' . absint( $settings['border_radius'] ) . 'px 0;">' . esc_html__( 'Need help? Contact our support team anytime.', 'wp-span-checker' ) . '</div>';

			case 'account_blocked':
				$reason = $data['reason'] ?? __( 'Multiple policy violations', 'wp-span-checker' );
				return '
					<h2 style="color: ' . esc_attr( $settings['heading_color'] ) . '; margin: 0 0 20px; font-size: 20px;">' . esc_html( $greeting ) . '</h2>
					<p style="color: ' . esc_attr( $settings['text_color'] ) . '; margin: 0 0 16px; line-height: 1.6;">' . esc_html__( 'Your account has been temporarily blocked due to security concerns.', 'wp-span-checker' ) . '</p>
					<div style="background: #fef2f2; border-left: 4px solid #dc2626; padding: 12px 16px; margin: 20px 0; border-radius: 0 ' . absint( $settings['border_radius'] ) . 'px ' . absint( $settings['border_radius'] ) . 'px 0;">
						<p style="font-size: 14px; color: #991b1b; margin: 0;"><strong>' . esc_html__( 'Reason:', 'wp-span-checker' ) . '</strong> ' . esc_html( $reason ) . '</p>
					</div>
					<p style="color: ' . esc_attr( $settings['text_color'] ) . '; margin: 0 0 16px; line-height: 1.6;">' . esc_html__( 'If you believe this is an error, please contact our support team.', 'wp-span-checker' ) . '</p>';

			case 'login_alert':
				$ip       = $data['ip'] ?? '0.0.0.0';
				$location = $data['location'] ?? __( 'Unknown', 'wp-span-checker' );
				$time     = $data['time'] ?? current_time( 'mysql' );
				return '
					<h2 style="color: ' . esc_attr( $settings['heading_color'] ) . '; margin: 0 0 20px; font-size: 20px;">' . esc_html( $greeting ) . '</h2>
					<p style="color: ' . esc_attr( $settings['text_color'] ) . '; margin: 0 0 16px; line-height: 1.6;">' . esc_html__( 'A new login to your account was detected:', 'wp-span-checker' ) . '</p>
					<div style="background: ' . esc_attr( $settings['otp_bg_color'] ) . '; border-radius: ' . absint( $settings['border_radius'] ) . 'px; padding: 20px; margin: 20px 0;">
						<table style="width: 100%; font-size: 14px; color: ' . esc_attr( $settings['text_color'] ) . ';">
							<tr><td style="padding: 8px 0;"><strong>' . esc_html__( 'IP Address:', 'wp-span-checker' ) . '</strong></td><td>' . esc_html( $ip ) . '</td></tr>
							<tr><td style="padding: 8px 0;"><strong>' . esc_html__( 'Location:', 'wp-span-checker' ) . '</strong></td><td>' . esc_html( $location ) . '</td></tr>
							<tr><td style="padding: 8px 0;"><strong>' . esc_html__( 'Time:', 'wp-span-checker' ) . '</strong></td><td>' . esc_html( $time ) . '</td></tr>
						</table>
					</div>
					<div style="background: ' . esc_attr( $settings['warning_bg_color'] ) . '; border-left: 4px solid #f59e0b; padding: 12px 16px; margin: 20px 0; font-size: 14px; color: ' . esc_attr( $settings['warning_text_color'] ) . '; border-radius: 0 ' . absint( $settings['border_radius'] ) . 'px ' . absint( $settings['border_radius'] ) . 'px 0;">' . esc_html__( 'If this was not you, please change your password immediately.', 'wp-span-checker' ) . '</div>';

			default:
				return '<p style="color: ' . esc_attr( $settings['text_color'] ) . ';">' . esc_html( $data['message'] ?? '' ) . '</p>';
		}
	}

	/**
	 * AJAX: Save settings.
	 */
	public function ajax_save_settings() {
		check_ajax_referer( 'wp_span_checker_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-span-checker' ) ) );
		}

		$settings = array(
			'header_bg_color'      => sanitize_hex_color( wp_unslash( $_POST['header_bg_color'] ?? '' ) ) ?: '#2563eb',
			'header_text_color'    => sanitize_hex_color( wp_unslash( $_POST['header_text_color'] ?? '' ) ) ?: '#ffffff',
			'body_bg_color'        => sanitize_hex_color( wp_unslash( $_POST['body_bg_color'] ?? '' ) ) ?: '#f4f4f5',
			'content_bg_color'     => sanitize_hex_color( wp_unslash( $_POST['content_bg_color'] ?? '' ) ) ?: '#ffffff',
			'text_color'           => sanitize_hex_color( wp_unslash( $_POST['text_color'] ?? '' ) ) ?: '#374151',
			'heading_color'        => sanitize_hex_color( wp_unslash( $_POST['heading_color'] ?? '' ) ) ?: '#1f2937',
			'link_color'           => sanitize_hex_color( wp_unslash( $_POST['link_color'] ?? '' ) ) ?: '#2563eb',
			'button_bg_color'      => sanitize_hex_color( wp_unslash( $_POST['button_bg_color'] ?? '' ) ) ?: '#2563eb',
			'button_text_color'    => sanitize_hex_color( wp_unslash( $_POST['button_text_color'] ?? '' ) ) ?: '#ffffff',
			'button_hover_color'   => sanitize_hex_color( wp_unslash( $_POST['button_hover_color'] ?? '' ) ) ?: '#1d4ed8',
			'footer_bg_color'      => sanitize_hex_color( wp_unslash( $_POST['footer_bg_color'] ?? '' ) ) ?: '#f9fafb',
			'footer_text_color'    => sanitize_hex_color( wp_unslash( $_POST['footer_text_color'] ?? '' ) ) ?: '#6b7280',
			'border_color'         => sanitize_hex_color( wp_unslash( $_POST['border_color'] ?? '' ) ) ?: '#e5e7eb',
			'otp_bg_color'         => sanitize_hex_color( wp_unslash( $_POST['otp_bg_color'] ?? '' ) ) ?: '#f3f4f6',
			'otp_text_color'       => sanitize_hex_color( wp_unslash( $_POST['otp_text_color'] ?? '' ) ) ?: '#2563eb',
			'warning_bg_color'     => sanitize_hex_color( wp_unslash( $_POST['warning_bg_color'] ?? '' ) ) ?: '#fef3c7',
			'warning_text_color'   => sanitize_hex_color( wp_unslash( $_POST['warning_text_color'] ?? '' ) ) ?: '#92400e',
			'info_bg_color'        => sanitize_hex_color( wp_unslash( $_POST['info_bg_color'] ?? '' ) ) ?: '#eff6ff',
			'info_text_color'      => sanitize_hex_color( wp_unslash( $_POST['info_text_color'] ?? '' ) ) ?: '#1e40af',
			'container_width'      => absint( $_POST['container_width'] ?? 600 ),
			'border_radius'        => absint( $_POST['border_radius'] ?? 8 ),
			'content_padding'      => absint( $_POST['content_padding'] ?? 40 ),
			'show_logo'            => ! empty( $_POST['show_logo'] ),
			'logo_url'             => esc_url_raw( wp_unslash( $_POST['logo_url'] ?? '' ) ),
			'logo_width'           => absint( $_POST['logo_width'] ?? 120 ),
			'company_name'         => sanitize_text_field( wp_unslash( $_POST['company_name'] ?? '' ) ),
			'company_address'      => sanitize_text_field( wp_unslash( $_POST['company_address'] ?? '' ) ),
			'show_social_links'    => ! empty( $_POST['show_social_links'] ),
			'facebook_url'         => esc_url_raw( wp_unslash( $_POST['facebook_url'] ?? '' ) ),
			'twitter_url'          => esc_url_raw( wp_unslash( $_POST['twitter_url'] ?? '' ) ),
			'instagram_url'        => esc_url_raw( wp_unslash( $_POST['instagram_url'] ?? '' ) ),
			'linkedin_url'         => esc_url_raw( wp_unslash( $_POST['linkedin_url'] ?? '' ) ),
		);

		update_option( self::OPTION_KEY, $settings );

		wp_send_json_success( array( 'message' => __( 'Email template settings saved.', 'wp-span-checker' ) ) );
	}

	/**
	 * AJAX: Preview template.
	 */
	public function ajax_preview_template() {
		check_ajax_referer( 'wp_span_checker_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-span-checker' ) ) );
		}

		$type = sanitize_text_field( wp_unslash( $_POST['email_type'] ?? 'otp_verification' ) );

		// Build settings from POST data for live preview
		$settings = array(
			'header_bg_color'      => sanitize_hex_color( wp_unslash( $_POST['header_bg_color'] ?? '' ) ) ?: '#2563eb',
			'header_text_color'    => sanitize_hex_color( wp_unslash( $_POST['header_text_color'] ?? '' ) ) ?: '#ffffff',
			'body_bg_color'        => sanitize_hex_color( wp_unslash( $_POST['body_bg_color'] ?? '' ) ) ?: '#f4f4f5',
			'content_bg_color'     => sanitize_hex_color( wp_unslash( $_POST['content_bg_color'] ?? '' ) ) ?: '#ffffff',
			'text_color'           => sanitize_hex_color( wp_unslash( $_POST['text_color'] ?? '' ) ) ?: '#374151',
			'heading_color'        => sanitize_hex_color( wp_unslash( $_POST['heading_color'] ?? '' ) ) ?: '#1f2937',
			'link_color'           => sanitize_hex_color( wp_unslash( $_POST['link_color'] ?? '' ) ) ?: '#2563eb',
			'button_bg_color'      => sanitize_hex_color( wp_unslash( $_POST['button_bg_color'] ?? '' ) ) ?: '#2563eb',
			'button_text_color'    => sanitize_hex_color( wp_unslash( $_POST['button_text_color'] ?? '' ) ) ?: '#ffffff',
			'button_hover_color'   => sanitize_hex_color( wp_unslash( $_POST['button_hover_color'] ?? '' ) ) ?: '#1d4ed8',
			'footer_bg_color'      => sanitize_hex_color( wp_unslash( $_POST['footer_bg_color'] ?? '' ) ) ?: '#f9fafb',
			'footer_text_color'    => sanitize_hex_color( wp_unslash( $_POST['footer_text_color'] ?? '' ) ) ?: '#6b7280',
			'border_color'         => sanitize_hex_color( wp_unslash( $_POST['border_color'] ?? '' ) ) ?: '#e5e7eb',
			'otp_bg_color'         => sanitize_hex_color( wp_unslash( $_POST['otp_bg_color'] ?? '' ) ) ?: '#f3f4f6',
			'otp_text_color'       => sanitize_hex_color( wp_unslash( $_POST['otp_text_color'] ?? '' ) ) ?: '#2563eb',
			'warning_bg_color'     => sanitize_hex_color( wp_unslash( $_POST['warning_bg_color'] ?? '' ) ) ?: '#fef3c7',
			'warning_text_color'   => sanitize_hex_color( wp_unslash( $_POST['warning_text_color'] ?? '' ) ) ?: '#92400e',
			'info_bg_color'        => sanitize_hex_color( wp_unslash( $_POST['info_bg_color'] ?? '' ) ) ?: '#eff6ff',
			'info_text_color'      => sanitize_hex_color( wp_unslash( $_POST['info_text_color'] ?? '' ) ) ?: '#1e40af',
			'container_width'      => absint( $_POST['container_width'] ?? 600 ),
			'border_radius'        => absint( $_POST['border_radius'] ?? 8 ),
			'content_padding'      => absint( $_POST['content_padding'] ?? 40 ),
			'show_logo'            => ! empty( $_POST['show_logo'] ),
			'logo_url'             => esc_url_raw( wp_unslash( $_POST['logo_url'] ?? '' ) ),
			'logo_width'           => absint( $_POST['logo_width'] ?? 120 ),
			'company_name'         => sanitize_text_field( wp_unslash( $_POST['company_name'] ?? '' ) ),
			'company_address'      => sanitize_text_field( wp_unslash( $_POST['company_address'] ?? '' ) ),
			'show_social_links'    => ! empty( $_POST['show_social_links'] ),
			'facebook_url'         => esc_url_raw( wp_unslash( $_POST['facebook_url'] ?? '' ) ),
			'twitter_url'          => esc_url_raw( wp_unslash( $_POST['twitter_url'] ?? '' ) ),
			'instagram_url'        => esc_url_raw( wp_unslash( $_POST['instagram_url'] ?? '' ) ),
			'linkedin_url'         => esc_url_raw( wp_unslash( $_POST['linkedin_url'] ?? '' ) ),
		);

		// Sample data for preview
		$data = array(
			'name'         => 'John Doe',
			'email'        => 'john.doe@example.com',
			'title'        => self::$email_types[ $type ] ?? __( 'Email Preview', 'wp-span-checker' ),
			'otp'          => '847592',
			'otp_expires'  => 10,
			'link_expires' => 24,
			'expires'      => 10,
			'url'          => home_url( '/activate/?key=abc123def456' ),
			'login_url'    => wp_login_url(),
			'reason'       => __( 'Multiple spam attempts detected', 'wp-span-checker' ),
			'ip'           => '192.168.1.1',
			'location'     => 'New York, USA',
			'time'         => current_time( 'F j, Y g:i A' ),
		);

		$html = self::generate_email( $type, $data, $settings );

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * AJAX: Send test email.
	 */
	public function ajax_send_test_email() {
		check_ajax_referer( 'wp_span_checker_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-span-checker' ) ) );
		}

		$to   = sanitize_email( wp_unslash( $_POST['test_email'] ?? '' ) );
		$type = sanitize_text_field( wp_unslash( $_POST['email_type'] ?? 'otp_verification' ) );

		if ( empty( $to ) || ! is_email( $to ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'wp-span-checker' ) ) );
		}

		$data = array(
			'name'      => __( 'Test User', 'wp-span-checker' ),
			'title'     => self::$email_types[ $type ] ?? __( 'Test Email', 'wp-span-checker' ),
			'subject'   => sprintf( __( '[%s] %s', 'wp-span-checker' ), get_bloginfo( 'name' ), self::$email_types[ $type ] ?? 'Test' ),
			'otp'       => '123456',
			'expires'   => 10,
			'url'       => home_url( '/test-link/' ),
			'login_url' => wp_login_url(),
			'reason'    => __( 'This is a test notification', 'wp-span-checker' ),
			'ip'        => '127.0.0.1',
			'location'  => __( 'Test Location', 'wp-span-checker' ),
			'time'      => current_time( 'F j, Y g:i A' ),
		);

		$html    = self::generate_email( $type, $data );
		$subject = $data['subject'];

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		$sent    = wp_mail( $to, $subject, $html, $headers );

		if ( $sent ) {
			wp_send_json_success( array( 'message' => __( 'Test email sent successfully!', 'wp-span-checker' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to send test email. Check SMTP settings.', 'wp-span-checker' ) ) );
		}
	}
}
