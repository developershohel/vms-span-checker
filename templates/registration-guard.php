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
			'enabled'                    => ! empty( $_POST['rg_enabled'] ),
			'use_webrisk'                => ! empty( $_POST['rg_webrisk'] ),
			'use_virustotal'             => ! empty( $_POST['rg_virustotal'] ),
			'require_dns_live'           => ! empty( $_POST['rg_require_dns_live'] ),
			'require_mx'                 => ! empty( $_POST['rg_require_mx'] ),
			'mx_allow_a_fallback'        => ! empty( $_POST['rg_mx_a_fallback'] ),
			'skip_https_check'           => ! empty( $_POST['rg_skip_https'] ),
			'rate_limit_enabled'         => ! empty( $_POST['rg_rate_limit_enabled'] ),
			'rate_limit_max_burst'       => isset( $_POST['rg_rate_burst'] ) ? (int) $_POST['rg_rate_burst'] : 5,
			'rate_limit_lockout_seconds' => isset( $_POST['rg_rate_lockout_sec'] ) ? (int) $_POST['rg_rate_lockout_sec'] : 18000,
			'rate_limit_max_per_day'     => isset( $_POST['rg_rate_per_day'] ) ? (int) $_POST['rg_rate_per_day'] : 10,
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
		__( 'Runs before WordPress creates the account: DNS “live” check, MX (if enabled), disposable list, then Google Web Risk and optionally VirusTotal. Failed attempts are counted per IP with lockout and a daily cap; reference IDs help match server logs.', 'wp-span-checker' )
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
								'description' => __( 'Google threat-list check (malware/phishing/unwanted software). If clean, it means Google has not flagged this domain URL.', 'wp-span-checker' ),
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
								'description' => __( 'Multi-engine reputation check. A domain can pass Web Risk but still fail here if your malicious/suspicious thresholds are exceeded.', 'wp-span-checker' ),
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Require DNS “live” domain', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'rg_require_dns_live',
								'checked'     => ! empty( $cfg['require_dns_live'] ),
								'description' => __( 'Hostname must have at least one of MX, A, AAAA, NS, or SOA in public DNS (catches dead or typo domains before MX-specific rules).', 'wp-span-checker' ),
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
				<tr>
					<th scope="row"><?php esc_html_e( 'Rate limit failed signups', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'rg_rate_limit_enabled',
								'checked'     => ! empty( $cfg['rate_limit_enabled'] ),
								'description' => __( 'Count failed registration attempts by IP. Lockout and daily cap apply to the validation step (after DNS/MX/disposable, including API failures).', 'wp-span-checker' ),
							)
						);
						?>
						<p class="description" style="margin-top:10px;">
							<label for="rg_rate_burst"><?php esc_html_e( 'Max failures before lockout', 'wp-span-checker' ); ?></label><br>
							<input name="rg_rate_burst" id="rg_rate_burst" type="number" min="1" max="100" class="small-text" value="<?php echo esc_attr( (string) (int) $cfg['rate_limit_max_burst'] ); ?>">
						</p>
						<p class="description">
							<label for="rg_rate_lockout_sec"><?php esc_html_e( 'Lockout duration (seconds)', 'wp-span-checker' ); ?></label><br>
							<input name="rg_rate_lockout_sec" id="rg_rate_lockout_sec" type="number" min="60" class="small-text" value="<?php echo esc_attr( (string) (int) $cfg['rate_limit_lockout_seconds'] ); ?>">
							<?php esc_html_e( '(default 18000 ≈ 5 hours)', 'wp-span-checker' ); ?>
						</p>
						<p class="description">
							<label for="rg_rate_per_day"><?php esc_html_e( 'Max failures per calendar day (site timezone)', 'wp-span-checker' ); ?></label><br>
							<input name="rg_rate_per_day" id="rg_rate_per_day" type="number" min="1" max="1000" class="small-text" value="<?php echo esc_attr( (string) (int) $cfg['rate_limit_max_per_day'] ); ?>">
						</p>
					</td>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save settings', 'wp-span-checker' ); ?>">
			</p>
		</form>
	</div>
</div>
