<?php
/**
 * Third-party API credentials.
 *
 * @package WP_Span_Checker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$google_defaults = array(
	'api_key' => '',
);
$virustotal_defaults = array(
	'keys'            => array(),
	'max_malicious'   => 0,
	'max_suspicious'  => -1,
);
$recaptcha_defaults = array(
	'site_key'   => '',
	'secret_key' => '',
	'version'    => 'v2',
);

$google_raw        = get_option( 'wsc-google-config', $google_defaults );
$virustotal_config = get_option( 'wsc-virustotal-config', $virustotal_defaults );
$recaptcha_config  = get_option( 'wsc-recaptcha-config', $recaptcha_defaults );

if ( ! is_array( $recaptcha_config ) ) {
	$recaptcha_config = $recaptcha_defaults;
}
$recaptcha_config = wp_parse_args( $recaptcha_config, $recaptcha_defaults );

if ( ! is_array( $google_raw ) ) {
	$google_raw = $google_defaults;
}
// Only `api_key` is used (Web Risk REST `uris:search`); strip legacy unused keys from older installs.
$google_config = array(
	'api_key' => isset( $google_raw['api_key'] ) ? (string) $google_raw['api_key'] : '',
);
if ( $google_config !== $google_raw ) {
	update_option( 'wsc-google-config', $google_config );
}
if ( ! is_array( $virustotal_config ) || ! isset( $virustotal_config['keys'] ) || ! is_array( $virustotal_config['keys'] ) ) {
	$virustotal_config = $virustotal_defaults;
}
$virustotal_config = wp_parse_args( $virustotal_config, $virustotal_defaults );

// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified below for POST actions.
if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] ) {

	if ( ! isset( $_POST['wsc_api_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wsc_api_nonce'] ) ), 'wsc_api_action' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'wp-span-checker' ) );
	}

	if ( isset( $_POST['google_config'] ) ) {
		$google_config = array(
			'api_key' => isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '',
		);
		update_option( 'wsc-google-config', $google_config );
		echo '<div class="updated"><p>' . esc_html__( 'Google configuration saved.', 'wp-span-checker' ) . '</p></div>';
	}

	if ( ! empty( $_POST['virustotal_key'] ) ) {
		$new_key = sanitize_text_field( wp_unslash( $_POST['virustotal_key'] ) );
		if ( ! in_array( $new_key, $virustotal_config['keys'], true ) ) {
			$virustotal_config['keys'][] = $new_key;
			update_option( 'wsc-virustotal-config', $virustotal_config );
			echo '<div class="updated"><p>' . esc_html__( 'VirusTotal API key added.', 'wp-span-checker' ) . '</p></div>';
		}
	}

	if ( ! empty( $_POST['delete_vt_key'] ) ) {
		$key_to_delete = sanitize_text_field( wp_unslash( $_POST['delete_vt_key'] ) );
		$virustotal_config['keys'] = array_values(
			array_filter(
				$virustotal_config['keys'],
				static function ( $k ) use ( $key_to_delete ) {
					return $k !== $key_to_delete;
				}
			)
		);
		update_option( 'wsc-virustotal-config', $virustotal_config );
		echo '<div class="updated"><p>' . esc_html__( 'VirusTotal API key removed.', 'wp-span-checker' ) . '</p></div>';
	}

	if ( ! empty( $_POST['virustotal_thresholds_save'] ) ) {
		$virustotal_config['max_malicious'] = isset( $_POST['vt_max_malicious'] ) ? max( 0, absint( $_POST['vt_max_malicious'] ) ) : 0;
		$raw_susp                           = isset( $_POST['vt_max_suspicious'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['vt_max_suspicious'] ) ) ) : '';
		$virustotal_config['max_suspicious'] = ( '' === $raw_susp ) ? -1 : max( 0, (int) $raw_susp );
		update_option( 'wsc-virustotal-config', $virustotal_config );
		echo '<div class="updated"><p>' . esc_html__( 'VirusTotal thresholds saved.', 'wp-span-checker' ) . '</p></div>';
	}

	if ( isset( $_POST['recaptcha_save'] ) ) {
		$recaptcha_config = array(
			'site_key'   => isset( $_POST['recaptcha_site_key'] ) ? sanitize_text_field( wp_unslash( $_POST['recaptcha_site_key'] ) ) : '',
			'secret_key' => isset( $_POST['recaptcha_secret_key'] ) ? sanitize_text_field( wp_unslash( $_POST['recaptcha_secret_key'] ) ) : '',
			'version'    => isset( $_POST['recaptcha_version'] ) && in_array( $_POST['recaptcha_version'], array( 'v2', 'v3' ), true ) ? $_POST['recaptcha_version'] : 'v2',
		);
		update_option( 'wsc-recaptcha-config', $recaptcha_config );
		echo '<div class="updated"><p>' . esc_html__( 'Google reCAPTCHA configuration saved.', 'wp-span-checker' ) . '</p></div>';
	}
}
// phpcs:enable WordPress.Security.NonceVerification.Missing
?>

<div class="wrap wsc-admin">
	<?php
	wp_span_checker_admin_page_header(
		__( 'API Settings', 'wp-span-checker' ),
		__( 'Connect Google Web Risk, VirusTotal, and reCAPTCHA for enhanced form protection.', 'wp-span-checker' )
	);
	?>

	<div class="wsc-card wsc-api-section">
		<h2 class="wsc-card__title"><?php esc_html_e( 'Google reCAPTCHA', 'wp-span-checker' ); ?></h2>
		<p class="description" style="margin-top:0;">
			<?php esc_html_e( 'Add Google reCAPTCHA v2 (checkbox) or v3 (invisible) to protect your forms from bots and spam.', 'wp-span-checker' ); ?>
		</p>
		<form method="post">
			<?php wp_nonce_field( 'wsc_api_action', 'wsc_api_nonce' ); ?>
			<input type="hidden" name="recaptcha_save" value="1">
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="recaptcha_version"><?php esc_html_e( 'reCAPTCHA Version', 'wp-span-checker' ); ?></label></th>
					<td>
						<select name="recaptcha_version" id="recaptcha_version" class="regular-text">
							<option value="v2" <?php selected( $recaptcha_config['version'], 'v2' ); ?>><?php esc_html_e( 'reCAPTCHA v2 (Checkbox)', 'wp-span-checker' ); ?></option>
							<option value="v3" <?php selected( $recaptcha_config['version'], 'v3' ); ?>><?php esc_html_e( 'reCAPTCHA v3 (Invisible)', 'wp-span-checker' ); ?></option>
						</select>
						<p class="description">
							<?php esc_html_e( 'v2: Shows "I\'m not a robot" checkbox. v3: Invisible, scores user behavior.', 'wp-span-checker' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="recaptcha_site_key"><?php esc_html_e( 'Site Key', 'wp-span-checker' ); ?></label></th>
					<td>
						<input type="text" name="recaptcha_site_key" id="recaptcha_site_key" value="<?php echo esc_attr( $recaptcha_config['site_key'] ); ?>" class="regular-text" autocomplete="off">
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="recaptcha_secret_key"><?php esc_html_e( 'Secret Key', 'wp-span-checker' ); ?></label></th>
					<td>
						<input type="password" name="recaptcha_secret_key" id="recaptcha_secret_key" value="<?php echo esc_attr( $recaptcha_config['secret_key'] ); ?>" class="regular-text" autocomplete="off">
						<p class="description">
							<?php
							echo wp_kses(
								sprintf(
									/* translators: %s: URL to Google reCAPTCHA admin */
									__( 'Get your keys from <a href="%s" rel="noopener noreferrer" target="_blank">Google reCAPTCHA Admin Console</a>.', 'wp-span-checker' ),
									esc_url( 'https://www.google.com/recaptcha/admin' )
								),
								array(
									'a' => array(
										'href'   => true,
										'rel'    => true,
										'target' => true,
									),
								)
							);
							?>
						</p>
					</td>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save reCAPTCHA configuration', 'wp-span-checker' ); ?>">
			</p>
		</form>
	</div>

	<div class="wsc-card wsc-api-section">
		<h2 class="wsc-card__title"><?php esc_html_e( 'Google Web Risk', 'wp-span-checker' ); ?></h2>
		<p class="description" style="margin-top:0;">
			<?php
			echo esc_html__(
				'This plugin calls the Web Risk v1 API using a single API key (query parameter). Create a key in Google Cloud Console for the Web Risk API—no OAuth client or secret is required for this lookup.',
				'wp-span-checker'
			);
			?>
		</p>
		<form method="post">
			<?php wp_nonce_field( 'wsc_api_action', 'wsc_api_nonce' ); ?>
			<input type="hidden" name="google_config" value="1">
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="api_key"><?php esc_html_e( 'API key', 'wp-span-checker' ); ?></label></th>
					<td>
						<input type="text" name="api_key" id="api_key" value="<?php echo esc_attr( $google_config['api_key'] ); ?>" class="regular-text" autocomplete="off" required>
						<p class="description">
							<?php
							echo wp_kses(
								sprintf(
									/* translators: %s: URL to Google Web Risk docs */
									__( 'See <a href="%s" rel="noopener noreferrer" target="_blank">Google Web Risk documentation</a> to enable the API and create a key.', 'wp-span-checker' ),
									esc_url( 'https://cloud.google.com/web-risk/docs/quickstart' )
								),
								array(
									'a' => array(
										'href'   => true,
										'rel'    => true,
										'target' => true,
									),
								)
							);
							?>
						</p>
					</td>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Google configuration', 'wp-span-checker' ); ?>">
			</p>
		</form>
	</div>

	<div class="wsc-card wsc-api-section">
		<h2 class="wsc-card__title"><?php esc_html_e( 'VirusTotal API keys', 'wp-span-checker' ); ?></h2>
		<form method="post" class="wsc-card" style="margin-bottom:1.5em;">
			<?php wp_nonce_field( 'wsc_api_action', 'wsc_api_nonce' ); ?>
			<input type="hidden" name="virustotal_thresholds_save" value="1">
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="vt_max_malicious"><?php esc_html_e( 'Max malicious engines', 'wp-span-checker' ); ?></label></th>
					<td>
						<input type="number" name="vt_max_malicious" id="vt_max_malicious" value="<?php echo esc_attr( (string) (int) ( $virustotal_config['max_malicious'] ?? 0 ) ); ?>" min="0" max="100" class="small-text">
						<p class="description"><?php esc_html_e( 'Reject the domain if VirusTotal reports more than this many “malicious” detections. Use 0 for strict (any malicious = block).', 'wp-span-checker' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="vt_max_suspicious"><?php esc_html_e( 'Max suspicious engines', 'wp-span-checker' ); ?></label></th>
					<td>
						<?php
						$vt_susp = isset( $virustotal_config['max_suspicious'] ) ? (int) $virustotal_config['max_suspicious'] : -1;
						$vt_susp_val = $vt_susp >= 0 ? (string) $vt_susp : '';
						?>
						<input type="number" name="vt_max_suspicious" id="vt_max_suspicious" value="<?php echo esc_attr( $vt_susp_val ); ?>" min="0" max="100" class="small-text" placeholder="<?php esc_attr_e( 'off', 'wp-span-checker' ); ?>">
						<p class="description"><?php esc_html_e( 'Leave empty to ignore “suspicious” counts. If set, block when suspicious is greater than this value.', 'wp-span-checker' ); ?></p>
					</td>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save VirusTotal thresholds', 'wp-span-checker' ); ?>">
			</p>
		</form>
		<form method="post" class="wsc-admin__inline-form wsc-api-vt-add">
			<?php wp_nonce_field( 'wsc_api_action', 'wsc_api_nonce' ); ?>
			<input type="text" name="virustotal_key" placeholder="<?php esc_attr_e( 'Enter VirusTotal API key', 'wp-span-checker' ); ?>" class="regular-text" required>
			<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Add key', 'wp-span-checker' ); ?>">
		</form>

		<?php if ( ! empty( $virustotal_config['keys'] ) ) : ?>
			<div class="wsc-admin__table-wrap wsc-api-vt-table">
				<table class="widefat striped">
					<thead>
					<tr>
						<th><?php esc_html_e( 'API key', 'wp-span-checker' ); ?></th>
						<th><?php esc_html_e( 'Action', 'wp-span-checker' ); ?></th>
					</tr>
					</thead>
					<tbody>
					<?php foreach ( $virustotal_config['keys'] as $vt_key_row ) : ?>
						<tr>
							<td><?php echo esc_html( $vt_key_row ); ?></td>
							<td>
								<form method="post" class="wsc-inline-form">
									<?php wp_nonce_field( 'wsc_api_action', 'wsc_api_nonce' ); ?>
									<input type="hidden" name="delete_vt_key" value="<?php echo esc_attr( $vt_key_row ); ?>">
									<input type="submit" class="button button-secondary" value="<?php esc_attr_e( 'Delete', 'wp-span-checker' ); ?>">
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php else : ?>
			<p class="wsc-admin__empty-hint"><?php esc_html_e( 'No VirusTotal API keys have been added yet.', 'wp-span-checker' ); ?></p>
		<?php endif; ?>
	</div>
</div>
