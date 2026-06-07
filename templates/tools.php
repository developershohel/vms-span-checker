<?php
/**
 * Manual Web Risk / VirusTotal tests and activity log console.
 *
 * @package VMS_Elements_Form_Guard
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 */

use VMS_Elements_Form_Guard\Logger;
use VMS_Elements_Form_Guard\Services\Google_Webrisk;
use VMS_Elements_Form_Guard\Services\VirusTotal;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$logger       = new Logger();
$test_results = array();
$ip           = function_exists( 'vms_elements_form_guard_get_user_ip' ) ? vms_elements_form_guard_get_user_ip() : '';

// phpcs:disable WordPress.Security.NonceVerification.Missing
if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] ) {
	if ( ! isset( $_POST['vefg_tools_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vefg_tools_nonce'] ) ), 'vefg_tools_manual_test' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'vms-elements-form-guard' ) );
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to run tools.', 'vms-elements-form-guard' ) );
	}

	$raw_email = isset( $_POST['vefg_tools_email'] ) ? sanitize_email( wp_unslash( $_POST['vefg_tools_email'] ) ) : '';
	$do_wr     = ! empty( $_POST['vefg_tools_webrisk'] );
	$do_vt     = ! empty( $_POST['vefg_tools_virustotal'] );

	if ( ! is_email( $raw_email ) ) {
		$test_results[] = array(
			'ok'      => false,
			'title'   => __( 'Invalid email', 'vms-elements-form-guard' ),
			'detail'  => __( 'Enter a valid email address to extract the domain for API checks.', 'vms-elements-form-guard' ),
			'service' => '',
		);
		$logger->log( 'manual_tool', $ip, '', 'failed', __( 'Invalid email in manual test form.', 'vms-elements-form-guard' ) );
	} else {
		$email_domain = strtolower( substr( strrchr( $raw_email, '@' ), 1 ) );

		if ( $do_wr ) {
			$wr     = ( new Google_Webrisk() )->check_url( 'https://' . $email_domain );
			$ok     = ! empty( $wr['status'] );
			$logger->log( 'manual_webrisk', $ip, $email_domain, $ok ? 'success' : 'failed', (string) ( $wr['message'] ?? '' ) );
			$test_results[] = array(
				'ok'      => $ok,
				'title'   => __( 'Google Web Risk', 'vms-elements-form-guard' ),
				'detail'  => (string) ( $wr['message'] ?? '' ),
				'service' => 'webrisk',
			);
		}

		if ( $do_vt ) {
			$vt = ( new VirusTotal() )->check_domain( $email_domain );
			$ok = ! empty( $vt['status'] );
			$logger->log( 'manual_virustotal', $ip, $email_domain, $ok ? 'success' : 'failed', (string) ( $vt['message'] ?? '' ) );
			$test_results[] = array(
				'ok'      => $ok,
				'title'   => __( 'VirusTotal', 'vms-elements-form-guard' ),
				'detail'  => (string) ( $vt['message'] ?? '' ),
				'service' => 'virustotal',
			);
		}

		if ( ! $do_wr && ! $do_vt ) {
			$test_results[] = array(
				'ok'      => false,
				'title'   => __( 'Nothing selected', 'vms-elements-form-guard' ),
				'detail'  => __( 'Choose at least one API to run.', 'vms-elements-form-guard' ),
				'service' => '',
			);
		}
	}
}
// phpcs:enable WordPress.Security.NonceVerification.Missing

$log_rows = $logger->get_logs( 50 );
?>


<div class="wrap vefg-admin vefg-tools-layout">
	<?php
	vms_elements_form_guard_admin_page_header(
		__( 'Tools & log', 'vms-elements-form-guard' ),
		__( 'Run Web Risk and VirusTotal against an email’s domain, and review the latest 50 entries: successful and failed sign-ins, registration/domain checks, form validation, and manual tests.', 'vms-elements-form-guard' )
	);
	?>

	<div class="vefg-tools-card">
		<h2 style="margin-top:0;"><?php esc_html_e( 'Manual API test', 'vms-elements-form-guard' ); ?></h2>
		<form method="post" class="vefg-tools-form">
			<?php wp_nonce_field( 'vefg_tools_manual_test', 'vefg_tools_nonce' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="vefg_tools_email"><?php esc_html_e( 'Email address', 'vms-elements-form-guard' ); ?></label></th>
					<td>
						<input name="vefg_tools_email" id="vefg_tools_email" type="email" class="regular-text" required
							placeholder="user@example.com"
							value="<?php echo isset( $_POST['vefg_tools_email'] ) ? esc_attr( sanitize_email( wp_unslash( $_POST['vefg_tools_email'] ) ) ) : ''; ?>">
						<p class="description"><?php esc_html_e( 'The domain after @ is sent to the selected APIs (same as live form validation).', 'vms-elements-form-guard' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Run', 'vms-elements-form-guard' ); ?></th>
					<td>
						<label style="display:block;margin-bottom:8px;">
							<input type="checkbox" name="vefg_tools_webrisk" value="1" checked>
							<?php esc_html_e( 'Google Web Risk (https://domain)', 'vms-elements-form-guard' ); ?>
						</label>
						<label style="display:block;">
							<input type="checkbox" name="vefg_tools_virustotal" value="1" checked>
							<?php esc_html_e( 'VirusTotal domain report', 'vms-elements-form-guard' ); ?>
						</label>
					</td>
				</tr>
			</table>
			<p class="submit">
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Run tests', 'vms-elements-form-guard' ); ?></button>
			</p>
		</form>

		<?php if ( ! empty( $test_results ) ) : ?>
			<h3><?php esc_html_e( 'Results', 'vms-elements-form-guard' ); ?></h3>
			<?php foreach ( $test_results as $row ) : ?>
				<div class="vefg-tools-result <?php echo ! empty( $row['ok'] ) ? 'is-ok' : 'is-fail'; ?>">
					<strong><?php echo esc_html( $row['title'] ); ?></strong>
					<span><?php echo esc_html( $row['detail'] ); ?></span>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>

		<p class="vefg-tools-hint">
			<?php esc_html_e( 'API keys and thresholds are configured under API Settings.', 'vms-elements-form-guard' ); ?>
		</p>
	</div>

	<div class="vefg-tools-card vefg-tools-console-wrap">
		<h2><?php esc_html_e( 'Activity log (last 50)', 'vms-elements-form-guard' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Includes wp-login success/failure, registration guard, front-end domain checks, and manual tests from this screen.', 'vms-elements-form-guard' ); ?></p>
		<?php if ( empty( $log_rows ) ) : ?>
			<p><?php esc_html_e( 'No log entries yet.', 'vms-elements-form-guard' ); ?></p>
		<?php else : ?>
			<div class="vefg-tools-console" role="log" aria-label="<?php esc_attr_e( 'Activity log', 'vms-elements-form-guard' ); ?>">
				<?php
				foreach ( $log_rows as $row ) {
					$row_status = isset( $row['status'] ) ? (string) $row['status'] : '';
					$cls        = ( 'success' === $row_status ) ? 'vefg-log--success' : ( ( 'failed' === $row_status ) ? 'vefg-log--failed' : '' );
					$time       = isset( $row['created_at'] ) ? mysql2date( 'Y-m-d H:i:s', $row['created_at'], true ) : '';
					$row_type   = isset( $row['type'] ) ? (string) $row['type'] : '';
					$dom    = isset( $row['domain'] ) ? (string) $row['domain'] : '';
					$msg    = isset( $row['message'] ) ? (string) $row['message'] : '';
					$ip_disp = isset( $row['ip'] ) ? (string) $row['ip'] : '';

					$domain_bit = $dom !== '' ? $dom : '—';
					$ip_bit     = $ip_disp !== '' ? ' ip=' . $ip_disp : '';

					printf(
						'<div class="vefg-log-line %1$s"><span class="vefg-log-time">[%2$s]</span><span class="vefg-log-type">%3$s</span><span class="vefg-log-domain">%4$s</span><span class="vefg-log-status">%5$s</span>%6$s<span class="vefg-log-msg"> — %7$s</span></div>',
						esc_attr( $cls ),
						esc_html( $time ),
						esc_html( $row_type ),
						esc_html( $domain_bit ),
						esc_html( $row_status ),
						esc_html( $ip_bit ),
						esc_html( $msg )
					);
				}
				?>
			</div>
		<?php endif; ?>
	</div>
</div>
