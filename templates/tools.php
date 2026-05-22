<?php
/**
 * Manual Web Risk / VirusTotal tests and activity log console.
 *
 * @package VMS_Span_Checker
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 */

use VMS_Span_Checker\Logger;
use VMS_Span_Checker\Services\GoogleWebRisk;
use VMS_Span_Checker\Services\VirusTotal;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$logger       = new Logger();
$test_results = array();
$ip           = function_exists( 'vms_span_checker_get_user_ip' ) ? vms_span_checker_get_user_ip() : '';

// phpcs:disable WordPress.Security.NonceVerification.Missing
if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] ) {
	if ( ! isset( $_POST['wsc_tools_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wsc_tools_nonce'] ) ), 'wsc_tools_manual_test' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'vms-span-checker' ) );
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to run tools.', 'vms-span-checker' ) );
	}

	$raw_email = isset( $_POST['wsc_tools_email'] ) ? sanitize_email( wp_unslash( $_POST['wsc_tools_email'] ) ) : '';
	$do_wr     = ! empty( $_POST['wsc_tools_webrisk'] );
	$do_vt     = ! empty( $_POST['wsc_tools_virustotal'] );

	if ( ! is_email( $raw_email ) ) {
		$test_results[] = array(
			'ok'      => false,
			'title'   => __( 'Invalid email', 'vms-span-checker' ),
			'detail'  => __( 'Enter a valid email address to extract the domain for API checks.', 'vms-span-checker' ),
			'service' => '',
		);
		$logger->log( 'manual_tool', $ip, '', 'failed', __( 'Invalid email in manual test form.', 'vms-span-checker' ) );
	} else {
		$domain = strtolower( substr( strrchr( $raw_email, '@' ), 1 ) );

		if ( $do_wr ) {
			$wr     = ( new GoogleWebRisk() )->check_url( 'https://' . $domain );
			$ok     = ! empty( $wr['status'] );
			$logger->log( 'manual_webrisk', $ip, $domain, $ok ? 'success' : 'failed', (string) ( $wr['message'] ?? '' ) );
			$test_results[] = array(
				'ok'      => $ok,
				'title'   => __( 'Google Web Risk', 'vms-span-checker' ),
				'detail'  => (string) ( $wr['message'] ?? '' ),
				'service' => 'webrisk',
			);
		}

		if ( $do_vt ) {
			$vt = ( new VirusTotal() )->check_domain( $domain );
			$ok = ! empty( $vt['status'] );
			$logger->log( 'manual_virustotal', $ip, $domain, $ok ? 'success' : 'failed', (string) ( $vt['message'] ?? '' ) );
			$test_results[] = array(
				'ok'      => $ok,
				'title'   => __( 'VirusTotal', 'vms-span-checker' ),
				'detail'  => (string) ( $vt['message'] ?? '' ),
				'service' => 'virustotal',
			);
		}

		if ( ! $do_wr && ! $do_vt ) {
			$test_results[] = array(
				'ok'      => false,
				'title'   => __( 'Nothing selected', 'vms-span-checker' ),
				'detail'  => __( 'Choose at least one API to run.', 'vms-span-checker' ),
				'service' => '',
			);
		}
	}
}
// phpcs:enable WordPress.Security.NonceVerification.Missing

$log_rows = $logger->get_logs( 50 );
?>

<style>
	.wsc-tools-layout { display: grid; gap: 24px; max-width: 1100px; }
	.wsc-tools-card { background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
	.wsc-tools-console-wrap h2 { margin-top: 0; }
	.wsc-tools-console {
		background: #0c0c0c;
		color: #d4d4d4;
		font-family: ui-monospace, "Cascadia Code", "Source Code Pro", Menlo, Consolas, monospace;
		font-size: 12px;
		line-height: 1.55;
		padding: 14px 16px;
		border-radius: 6px;
		max-height: 480px;
		overflow: auto;
		border: 1px solid #333;
		box-shadow: inset 0 0 0 1px rgba(255,255,255,.04);
	}
	.wsc-tools-console .wsc-log-line { margin: 0 0 6px; white-space: pre-wrap; word-break: break-word; }
	.wsc-tools-console .wsc-log-line:last-child { margin-bottom: 0; }
	.wsc-tools-console .wsc-log-time { color: #858585; margin-right: 8px; }
	.wsc-tools-console .wsc-log-type { color: #dcdcaa; margin-right: 8px; }
	.wsc-tools-console .wsc-log-domain { color: #9cdcfe; margin-right: 8px; }
	.wsc-tools-console .wsc-log--success .wsc-log-status { color: #89d185; }
	.wsc-tools-console .wsc-log--failed .wsc-log-status { color: #f48771; }
	.wsc-tools-console .wsc-log-msg { color: #ce9178; }
	.wsc-tools-result { padding: 10px 14px; border-left: 4px solid #646970; margin-bottom: 10px; background: #f6f7f7; }
	.wsc-tools-result.is-ok { border-left-color: #00a32a; }
	.wsc-tools-result.is-fail { border-left-color: #d63638; }
	.wsc-tools-result strong { display: block; margin-bottom: 4px; }
	.wsc-tools-hint { color: #646970; font-size: 13px; margin: 8px 0 0; }
</style>

<div class="wrap wsc-admin wsc-tools-layout">
	<?php
	vms_span_checker_admin_page_header(
		__( 'Tools & log', 'vms-span-checker' ),
		__( 'Run Web Risk and VirusTotal against an email’s domain, and review the latest 50 entries: successful and failed sign-ins, registration/domain checks, form validation, and manual tests.', 'vms-span-checker' )
	);
	?>

	<div class="wsc-tools-card">
		<h2 style="margin-top:0;"><?php esc_html_e( 'Manual API test', 'vms-span-checker' ); ?></h2>
		<form method="post" class="wsc-tools-form">
			<?php wp_nonce_field( 'wsc_tools_manual_test', 'wsc_tools_nonce' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="wsc_tools_email"><?php esc_html_e( 'Email address', 'vms-span-checker' ); ?></label></th>
					<td>
						<input name="wsc_tools_email" id="wsc_tools_email" type="email" class="regular-text" required
							placeholder="user@example.com"
							value="<?php echo isset( $_POST['wsc_tools_email'] ) ? esc_attr( sanitize_email( wp_unslash( $_POST['wsc_tools_email'] ) ) ) : ''; ?>">
						<p class="description"><?php esc_html_e( 'The domain after @ is sent to the selected APIs (same as live form validation).', 'vms-span-checker' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Run', 'vms-span-checker' ); ?></th>
					<td>
						<label style="display:block;margin-bottom:8px;">
							<input type="checkbox" name="wsc_tools_webrisk" value="1" checked>
							<?php esc_html_e( 'Google Web Risk (https://domain)', 'vms-span-checker' ); ?>
						</label>
						<label style="display:block;">
							<input type="checkbox" name="wsc_tools_virustotal" value="1" checked>
							<?php esc_html_e( 'VirusTotal domain report', 'vms-span-checker' ); ?>
						</label>
					</td>
				</tr>
			</table>
			<p class="submit">
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Run tests', 'vms-span-checker' ); ?></button>
			</p>
		</form>

		<?php if ( ! empty( $test_results ) ) : ?>
			<h3><?php esc_html_e( 'Results', 'vms-span-checker' ); ?></h3>
			<?php foreach ( $test_results as $row ) : ?>
				<div class="wsc-tools-result <?php echo ! empty( $row['ok'] ) ? 'is-ok' : 'is-fail'; ?>">
					<strong><?php echo esc_html( $row['title'] ); ?></strong>
					<span><?php echo esc_html( $row['detail'] ); ?></span>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>

		<p class="wsc-tools-hint">
			<?php esc_html_e( 'API keys and thresholds are configured under API Settings.', 'vms-span-checker' ); ?>
		</p>
	</div>

	<div class="wsc-tools-card wsc-tools-console-wrap">
		<h2><?php esc_html_e( 'Activity log (last 50)', 'vms-span-checker' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Includes wp-login success/failure, registration guard, front-end domain checks, and manual tests from this screen.', 'vms-span-checker' ); ?></p>
		<?php if ( empty( $log_rows ) ) : ?>
			<p><?php esc_html_e( 'No log entries yet.', 'vms-span-checker' ); ?></p>
		<?php else : ?>
			<div class="wsc-tools-console" role="log" aria-label="<?php esc_attr_e( 'Activity log', 'vms-span-checker' ); ?>">
				<?php
				foreach ( $log_rows as $row ) {
					$status = isset( $row['status'] ) ? (string) $row['status'] : '';
					$cls    = ( 'success' === $status ) ? 'wsc-log--success' : ( ( 'failed' === $status ) ? 'wsc-log--failed' : '' );
					$time   = isset( $row['created_at'] ) ? mysql2date( 'Y-m-d H:i:s', $row['created_at'], true ) : '';
					$type   = isset( $row['type'] ) ? (string) $row['type'] : '';
					$dom    = isset( $row['domain'] ) ? (string) $row['domain'] : '';
					$msg    = isset( $row['message'] ) ? (string) $row['message'] : '';
					$ip_disp = isset( $row['ip'] ) ? (string) $row['ip'] : '';

					$domain_bit = $dom !== '' ? $dom : '—';
					$ip_bit     = $ip_disp !== '' ? ' ip=' . $ip_disp : '';

					printf(
						'<div class="wsc-log-line %1$s"><span class="wsc-log-time">[%2$s]</span><span class="wsc-log-type">%3$s</span><span class="wsc-log-domain">%4$s</span><span class="wsc-log-status">%5$s</span>%6$s<span class="wsc-log-msg"> — %7$s</span></div>',
						esc_attr( $cls ),
						esc_html( $time ),
						esc_html( $type ),
						esc_html( $domain_bit ),
						esc_html( $status ),
						esc_html( $ip_bit ),
						esc_html( $msg )
					);
				}
				?>
			</div>
		<?php endif; ?>
	</div>
</div>
