<?php
/**
 * Third-party API credentials.
 *
 * @package WP_Span_Checker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$google_defaults     = array(
	'secret_key' => '',
	'client_id'  => '',
	'api_key'    => '',
);
$virustotal_defaults = array(
	'keys'            => array(),
	'max_malicious'   => 0,
	'max_suspicious'  => -1,
);

$google_config     = get_option( 'wsc-google-config', $google_defaults );
$virustotal_config = get_option( 'wsc-virustotal-config', $virustotal_defaults );

if ( ! is_array( $google_config ) ) {
	$google_config = $google_defaults;
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
			'secret_key' => isset( $_POST['secret_key'] ) ? sanitize_text_field( wp_unslash( $_POST['secret_key'] ) ) : '',
			'client_id'  => isset( $_POST['client_id'] ) ? sanitize_text_field( wp_unslash( $_POST['client_id'] ) ) : '',
			'api_key'    => isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '',
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
}
// phpcs:enable WordPress.Security.NonceVerification.Missing
?>

<div class="wrap wsc-admin">
	<?php
	wp_span_checker_admin_page_header(
		__( 'API Settings', 'wp-span-checker' ),
		__( 'Connect Google Web Risk and VirusTotal for optional reputation checks on mapped forms.', 'wp-span-checker' )
	);
	?>

	<div class="wsc-card wsc-api-section">
		<h2 class="wsc-card__title"><?php esc_html_e( 'Google Web Risk', 'wp-span-checker' ); ?></h2>
		<form method="post">
			<?php wp_nonce_field( 'wsc_api_action', 'wsc_api_nonce' ); ?>
			<input type="hidden" name="google_config" value="1">
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="secret_key"><?php esc_html_e( 'Secret key', 'wp-span-checker' ); ?></label></th>
					<td><input type="text" name="secret_key" id="secret_key" value="<?php echo esc_attr( $google_config['secret_key'] ); ?>" class="regular-text" required></td>
				</tr>
				<tr>
					<th scope="row"><label for="client_id"><?php esc_html_e( 'Client ID', 'wp-span-checker' ); ?></label></th>
					<td><input type="text" name="client_id" id="client_id" value="<?php echo esc_attr( $google_config['client_id'] ); ?>" class="regular-text" required></td>
				</tr>
				<tr>
					<th scope="row"><label for="api_key"><?php esc_html_e( 'API key', 'wp-span-checker' ); ?></label></th>
					<td><input type="text" name="api_key" id="api_key" value="<?php echo esc_attr( $google_config['api_key'] ); ?>" class="regular-text" required></td>
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
