<?php
/**
 * Registration guard — block fake signups using the same domain pipeline as forms.
 *
 * @package WP_Span_Checker
 */

use WP_Span_Checker\Registration_Guard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cfg = Registration_Guard::get();

// phpcs:disable WordPress.Security.NonceVerification.Missing
if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] ) {
	if ( ! isset( $_POST['wsc_registration_guard_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wsc_registration_guard_nonce'] ) ), 'wsc_registration_guard_save' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'wp-span-checker' ) );
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to save these settings.', 'wp-span-checker' ) );
	}

	Registration_Guard::update(
		array(
			'enabled'              => ! empty( $_POST['rg_enabled'] ),
			'use_webrisk'          => ! empty( $_POST['rg_webrisk'] ),
			'use_virustotal'       => ! empty( $_POST['rg_virustotal'] ),
			'require_mx'           => ! empty( $_POST['rg_require_mx'] ),
			'mx_allow_a_fallback'  => ! empty( $_POST['rg_mx_a_fallback'] ),
			'skip_https_check'     => ! empty( $_POST['rg_skip_https'] ),
		)
	);
	$cfg = Registration_Guard::get();
	echo '<div class="updated"><p>' . esc_html__( 'Registration guard saved.', 'wp-span-checker' ) . '</p></div>';
}
// phpcs:enable WordPress.Security.NonceVerification.Missing

$can_register = get_option( 'users_can_register' );
?>

<div class="wrap wsc-admin">
	<?php
	wp_span_checker_admin_page_header(
		__( 'Registration guard', 'wp-span-checker' ),
		__( 'Validate the email domain on WordPress (and WooCommerce) registration: disposable list, optional MX deliverability check, optional Google Web Risk and VirusTotal (thresholds under API Settings). HTTPS check is off by default so email-only domains still pass.', 'wp-span-checker' )
	);
	?>

	<?php if ( ! $can_register ) : ?>
		<div class="notice notice-warning"><p><?php esc_html_e( 'WordPress “Anyone can register” is currently disabled under Settings → General. This guard only runs when registration is allowed (or when WooCommerce creates accounts).', 'wp-span-checker' ); ?></p></div>
	<?php endif; ?>

	<div class="wsc-card">
		<form method="post">
			<?php wp_nonce_field( 'wsc_registration_guard_save', 'wsc_registration_guard_nonce' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable registration guard', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'rg_enabled',
								'checked'     => ! empty( $cfg['enabled'] ),
								'description' => __( 'Run validation when a new user registers.', 'wp-span-checker' ),
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Google Web Risk', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'rg_webrisk',
								'checked'     => ! empty( $cfg['use_webrisk'] ),
								'description' => __( 'Check https://domain against Web Risk (requires API key under API Settings).', 'wp-span-checker' ),
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'VirusTotal', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'rg_virustotal',
								'checked'     => ! empty( $cfg['use_virustotal'] ),
								'description' => __( 'Check domain reputation (requires API keys and thresholds under API Settings).', 'wp-span-checker' ),
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Require MX / mail DNS', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'rg_require_mx',
								'checked'     => ! empty( $cfg['require_mx'] ),
								'description' => __( 'Domain must have MX records (or see fallback below).', 'wp-span-checker' ),
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Allow A-record fallback', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'rg_mx_a_fallback',
								'checked'     => ! empty( $cfg['mx_allow_a_fallback'] ),
								'description' => __( 'If no MX exists, accept domains that have an A record (some small hosts).', 'wp-span-checker' ),
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Skip HTTPS check', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'rg_skip_https',
								'checked'     => ! empty( $cfg['skip_https_check'] ),
								'description' => __( 'Recommended for registration: many valid mail domains do not serve a public HTTPS site on the bare hostname.', 'wp-span-checker' ),
							)
						);
						?>
					</td>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save settings', 'wp-span-checker' ); ?>">
			</p>
		</form>
	</div>
</div>
