<?php
/**
 * Admin dashboard template — WP Span Checker home.
 *
 * @package WP_Span_Checker
 */

use WP_Span_Checker\AI_Span_Config;
use WP_Span_Checker\Dashboard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dashboard = new Dashboard();
$summary   = $dashboard->get_summary();
$analysis  = $dashboard->get_analysis( 6 );
$spam_logs = $dashboard->get_spam_logs( 25 );

$ai_cfg            = AI_Span_Config::get();
$ai_blocked_guests = 0;
if ( ! empty( $ai_cfg['ai_enabled'] ) ) {
	global $wpdb;
	$ai_blocked_guests = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}span_checker_comment_enforcement WHERE blocked = 1" );
}

$pass_rate = 0;
if ( ! empty( $summary['total_logs'] ) ) {
	$pass_rate = (int) round( 100 * ( (int) $summary['success_logs'] / (int) $summary['total_logs'] ) );
}

$max_event_type = 1;
foreach ( (array) $analysis['events_by_type'] as $et_row ) {
	$max_event_type = max( $max_event_type, (int) $et_row['count'] );
}

$max_domain_hits = 1;
foreach ( (array) $analysis['top_failed_domains'] as $d_row ) {
	$max_domain_hits = max( $max_domain_hits, (int) $d_row['count'] );
}

$max_message_hits = 1;
foreach ( (array) $analysis['top_failed_messages'] as $m_row ) {
	$max_message_hits = max( $max_message_hits, (int) $m_row['count'] );
}

$insights = array();
if ( 0 === (int) $summary['form_mappings'] ) {
	$insights[] = __( 'No form mappings yet—connect a form under Form Settings to start logging checks.', 'wp-span-checker' );
}
if ( 0 === (int) $summary['total_logs'] ) {
	$insights[] = __( 'No validation events recorded yet. Traffic will appear here once forms are mapped and submitted.', 'wp-span-checker' );
} else {
	$failed_n  = (int) $summary['failed_validations'];
	$success_n = (int) $summary['success_logs'];
	if ( $failed_n > 0 && $success_n > 0 && $failed_n > ( $success_n * 2 ) ) {
		$insights[] = __( 'Blocks are outpacing passes—review disposable rules and the top failing domains below.', 'wp-span-checker' );
	} elseif ( $pass_rate >= 90 ) {
		$insights[] = __( 'Strong pass rate. Monitor API quotas and keep disposable lists current.', 'wp-span-checker' );
	}
}
if ( empty( $insights ) ) {
	$insights[] = __( 'Dashboard is live—use the breakdowns below to spot patterns in validation traffic.', 'wp-span-checker' );
}

$google_on = ! empty( $summary['google_api_ready'] );
$vt_n      = (int) $summary['virustotal_keys'];

$quick_links = array(
	array(
		'url'   => admin_url( 'admin.php?page=wp-span-checker-whitelist' ),
		'icon'  => '✓',
		'title' => __( 'Whitelist domains', 'wp-span-checker' ),
		'desc'  => __( 'Trusted domains that always pass', 'wp-span-checker' ),
	),
	array(
		'url'   => admin_url( 'admin.php?page=wp-span-checker-disposable' ),
		'icon'  => '⛔',
		'title' => __( 'Disposable list', 'wp-span-checker' ),
		'desc'  => __( 'Block throwaway email hosts', 'wp-span-checker' ),
	),
	array(
		'url'   => admin_url( 'admin.php?page=wp-span-checker-form-settings' ),
		'icon'  => '⚡',
		'title' => __( 'Form mappings', 'wp-span-checker' ),
		'desc'  => __( 'Connect site forms to validation', 'wp-span-checker' ),
	),
	array(
		'url'   => admin_url( 'admin.php?page=wp-span-checker-api' ),
		'icon'  => '🔐',
		'title' => __( 'API settings', 'wp-span-checker' ),
		'desc'  => __( 'Web Risk & VirusTotal keys', 'wp-span-checker' ),
	),
	array(
		'url'   => admin_url( 'admin.php?page=wp-span-checker-ai-settings' ),
		'icon'  => '🤖',
		'title' => __( 'AI Span Checker', 'wp-span-checker' ),
		'desc'  => __( 'OpenAI, Anthropic, Gemini, DeepSeek, or Bedrock', 'wp-span-checker' ),
	),
	array(
		'url'   => admin_url( 'admin.php?page=wp-span-checker-registration' ),
		'icon'  => '🛡',
		'title' => __( 'Registration guard', 'wp-span-checker' ),
		'desc'  => __( 'MX + reputation on new signups', 'wp-span-checker' ),
	),
);
?>

<div class="wrap wsc-admin">
<div class="wsc-dash">
	<?php
	wp_span_checker_admin_page_header(
		__( 'Dashboard', 'wp-span-checker' ),
		__( 'Monitor domain checks, list coverage, and API readiness in one place—built for calm, fast triage.', 'wp-span-checker' )
	);
	?>
	<div class="wsc-dash__badge-row" role="group" aria-label="<?php esc_attr_e( 'API and pass-rate summary', 'wp-span-checker' ); ?>">
		<span class="wsc-dash__badge <?php echo $google_on ? 'wsc-dash__badge--ok' : 'wsc-dash__badge--warn'; ?>">
			<?php echo $google_on ? esc_html__( 'Google Web Risk: key set', 'wp-span-checker' ) : esc_html__( 'Google Web Risk: add key', 'wp-span-checker' ); ?>
		</span>
		<span class="wsc-dash__badge <?php echo $vt_n > 0 ? 'wsc-dash__badge--ok' : 'wsc-dash__badge--warn'; ?>">
			<?php
			if ( $vt_n > 0 ) {
				echo esc_html(
					sprintf(
						/* translators: %d: number of VirusTotal keys */
						_n( 'VirusTotal: %d key', 'VirusTotal: %d keys', $vt_n, 'wp-span-checker' ),
						$vt_n
					)
				);
			} else {
				esc_html_e( 'VirusTotal: no keys', 'wp-span-checker' );
			}
			?>
		</span>
		<span class="wsc-dash__badge wsc-dash__badge--neutral">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %d: percentage of successful validations */
					__( 'Pass rate: %d%%', 'wp-span-checker' ),
					$pass_rate
				)
			);
			?>
		</span>
		<?php if ( ! empty( $ai_cfg['ai_enabled'] ) ) : ?>
			<span class="wsc-dash__badge <?php echo $ai_blocked_guests > 0 ? 'wsc-dash__badge--warn' : 'wsc-dash__badge--ok'; ?>">
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: max strikes before block, 2: number of blocked commenters */
						__( 'AI comments: max %1$d strikes · %2$d blocked', 'wp-span-checker' ),
						(int) $ai_cfg['comment_max_strikes'],
						$ai_blocked_guests
					)
				);
				?>
			</span>
		<?php endif; ?>
	</div>

	<div class="wsc-dash__grid">
		<div class="wsc-dash__card">
			<span class="wsc-dash__card-glow wsc-dash__card-glow--cyan"></span>
			<p class="wsc-dash__card-label"><?php esc_html_e( 'Total checks logged', 'wp-span-checker' ); ?></p>
			<p class="wsc-dash__card-value"><?php echo esc_html( (string) (int) $summary['total_logs'] ); ?></p>
			<p class="wsc-dash__card-note"><?php esc_html_e( 'All validation events recorded by WP Span Checker.', 'wp-span-checker' ); ?></p>
		</div>
		<div class="wsc-dash__card">
			<span class="wsc-dash__card-glow wsc-dash__card-glow--emerald"></span>
			<p class="wsc-dash__card-label"><?php esc_html_e( 'Passed', 'wp-span-checker' ); ?></p>
			<p class="wsc-dash__card-value"><?php echo esc_html( (string) (int) $summary['success_logs'] ); ?></p>
			<p class="wsc-dash__card-note"><?php esc_html_e( 'Domains that cleared your rules and optional APIs.', 'wp-span-checker' ); ?></p>
		</div>
		<div class="wsc-dash__card">
			<span class="wsc-dash__card-glow wsc-dash__card-glow--rose"></span>
			<p class="wsc-dash__card-label"><?php esc_html_e( 'Blocked / failed', 'wp-span-checker' ); ?></p>
			<p class="wsc-dash__card-value"><?php echo esc_html( (string) (int) $summary['failed_validations'] ); ?></p>
			<p class="wsc-dash__card-note"><?php esc_html_e( 'Disposable list, HTTPS, or reputation blocks.', 'wp-span-checker' ); ?></p>
		</div>
		<div class="wsc-dash__card">
			<p class="wsc-dash__card-label"><?php esc_html_e( 'Whitelist entries', 'wp-span-checker' ); ?></p>
			<p class="wsc-dash__card-value"><?php echo esc_html( (string) (int) $summary['whitelist_count'] ); ?></p>
		</div>
		<div class="wsc-dash__card">
			<p class="wsc-dash__card-label"><?php esc_html_e( 'Disposable domains', 'wp-span-checker' ); ?></p>
			<p class="wsc-dash__card-value"><?php echo esc_html( (string) (int) $summary['disposable_count'] ); ?></p>
		</div>
		<div class="wsc-dash__card">
			<span class="wsc-dash__card-glow wsc-dash__card-glow--amber"></span>
			<p class="wsc-dash__card-label"><?php esc_html_e( 'Form mappings', 'wp-span-checker' ); ?></p>
			<p class="wsc-dash__card-value"><?php echo esc_html( (string) (int) $summary['form_mappings'] ); ?></p>
			<p class="wsc-dash__card-note"><?php esc_html_e( 'Active form ↔ validation profiles.', 'wp-span-checker' ); ?></p>
		</div>
		<div class="wsc-dash__card">
			<p class="wsc-dash__card-label"><?php esc_html_e( 'Login events', 'wp-span-checker' ); ?></p>
			<p class="wsc-dash__card-value"><?php echo esc_html( (string) (int) $summary['login_attempts'] ); ?></p>
		</div>
		<div class="wsc-dash__card">
			<p class="wsc-dash__card-label"><?php esc_html_e( 'Registration events', 'wp-span-checker' ); ?></p>
			<p class="wsc-dash__card-value"><?php echo esc_html( (string) (int) $summary['registration_attempts'] ); ?></p>
		</div>
	</div>

	<h2 class="wsc-dash__section-title"><?php esc_html_e( 'Analysis & insights', 'wp-span-checker' ); ?></h2>
	<div class="wsc-dash__analysis">
		<div class="wsc-dash__panel wsc-dash__panel--wide">
			<h3 class="wsc-dash__panel-title"><?php esc_html_e( 'Events by type', 'wp-span-checker' ); ?></h3>
			<?php if ( empty( $analysis['events_by_type'] ) ) : ?>
				<p class="wsc-dash__muted"><?php esc_html_e( 'No log data to chart yet.', 'wp-span-checker' ); ?></p>
			<?php else : ?>
				<ul class="wsc-dash__bars" role="list">
					<?php foreach ( $analysis['events_by_type'] as $et ) : ?>
						<?php
						$cnt           = (int) $et['count'];
						$pct           = (int) round( 100 * $cnt / $max_event_type );
						$log_type_name = (string) $et['type'];
						?>
						<li class="wsc-dash__bar-row">
							<span class="wsc-dash__bar-label"><?php echo esc_html( '' !== $log_type_name ? $log_type_name : __( '(empty)', 'wp-span-checker' ) ); ?></span>
							<span class="wsc-dash__bar-track" aria-hidden="true">
								<span class="wsc-dash__bar-fill" style="width: <?php echo esc_attr( (string) $pct ); ?>%;"></span>
							</span>
							<span class="wsc-dash__bar-count"><?php echo esc_html( (string) $cnt ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<div class="wsc-dash__panel">
			<h3 class="wsc-dash__panel-title"><?php esc_html_e( 'Signals', 'wp-span-checker' ); ?></h3>
			<ul class="wsc-dash__insight-list">
				<?php foreach ( $insights as $line ) : ?>
					<li><?php echo esc_html( $line ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>

	<div class="wsc-dash__analysis wsc-dash__analysis--split">
		<div class="wsc-dash__panel">
			<h3 class="wsc-dash__panel-title"><?php esc_html_e( 'Top blocked domains', 'wp-span-checker' ); ?></h3>
			<?php if ( empty( $analysis['top_failed_domains'] ) ) : ?>
				<p class="wsc-dash__muted"><?php esc_html_e( 'No failed domain counts yet.', 'wp-span-checker' ); ?></p>
			<?php else : ?>
				<ul class="wsc-dash__mini-bars" role="list">
					<?php foreach ( $analysis['top_failed_domains'] as $d ) : ?>
						<?php
						$dc  = (int) $d['count'];
						$dp  = (int) round( 100 * $dc / $max_domain_hits );
						$dom = (string) $d['domain'];
						?>
						<li class="wsc-dash__mini-row">
							<code class="wsc-dash__code"><?php echo esc_html( $dom ); ?></code>
							<span class="wsc-dash__mini-track" aria-hidden="true"><span class="wsc-dash__mini-fill" style="width: <?php echo esc_attr( (string) $dp ); ?>%;"></span></span>
							<span class="wsc-dash__mini-n"><?php echo esc_html( (string) $dc ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<div class="wsc-dash__panel">
			<h3 class="wsc-dash__panel-title"><?php esc_html_e( 'Top failure messages', 'wp-span-checker' ); ?></h3>
			<?php if ( empty( $analysis['top_failed_messages'] ) ) : ?>
				<p class="wsc-dash__muted"><?php esc_html_e( 'No failure messages grouped yet.', 'wp-span-checker' ); ?></p>
			<?php else : ?>
				<ul class="wsc-dash__mini-bars" role="list">
					<?php foreach ( $analysis['top_failed_messages'] as $fail_msg_row ) : ?>
						<?php
						$mc  = (int) $fail_msg_row['count'];
						$mp  = (int) round( 100 * $mc / $max_message_hits );
						$msg = (string) $fail_msg_row['message'];
						?>
						<li class="wsc-dash__mini-row wsc-dash__mini-row--msg">
							<span class="wsc-dash__msg"><?php echo esc_html( wp_trim_words( $msg, 12, '…' ) ); ?></span>
							<span class="wsc-dash__mini-track" aria-hidden="true"><span class="wsc-dash__mini-fill wsc-dash__mini-fill--rose" style="width: <?php echo esc_attr( (string) $mp ); ?>%;"></span></span>
							<span class="wsc-dash__mini-n"><?php echo esc_html( (string) $mc ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>

	<h2 class="wsc-dash__section-title"><?php esc_html_e( 'Quick actions', 'wp-span-checker' ); ?></h2>
	<div class="wsc-dash__actions">
		<?php foreach ( $quick_links as $quick_item ) : ?>
			<a class="wsc-dash__action" href="<?php echo esc_url( $quick_item['url'] ); ?>">
				<span class="wsc-dash__action-icon" aria-hidden="true"><?php echo esc_html( $quick_item['icon'] ); ?></span>
				<span class="wsc-dash__action-text">
					<strong><?php echo esc_html( $quick_item['title'] ); ?></strong>
					<span><?php echo esc_html( $quick_item['desc'] ); ?></span>
				</span>
			</a>
		<?php endforeach; ?>
	</div>

	<h2 class="wsc-dash__section-title"><?php esc_html_e( 'Recent blocked activity', 'wp-span-checker' ); ?></h2>
	<div class="wsc-dash__table-wrap">
		<table class="wsc-dash__table widefat">
			<thead>
				<tr>
					<th><?php esc_html_e( 'ID', 'wp-span-checker' ); ?></th>
					<th><?php esc_html_e( 'Type', 'wp-span-checker' ); ?></th>
					<th><?php esc_html_e( 'IP', 'wp-span-checker' ); ?></th>
					<th><?php esc_html_e( 'Domain', 'wp-span-checker' ); ?></th>
					<th><?php esc_html_e( 'Status', 'wp-span-checker' ); ?></th>
					<th><?php esc_html_e( 'Message', 'wp-span-checker' ); ?></th>
					<th><?php esc_html_e( 'Date', 'wp-span-checker' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php if ( empty( $spam_logs ) ) : ?>
				<tr>
					<td colspan="7"><?php esc_html_e( 'No failed validations yet—your traffic looks quiet.', 'wp-span-checker' ); ?></td>
				</tr>
			<?php else : ?>
				<?php foreach ( $spam_logs as $log ) : ?>
					<tr>
						<td><?php echo esc_html( (string) $log['id'] ); ?></td>
						<td><?php echo esc_html( (string) $log['type'] ); ?></td>
						<td><?php echo esc_html( (string) $log['ip'] ); ?></td>
						<td><?php echo esc_html( (string) $log['domain'] ); ?></td>
						<td><span class="wsc-dash__pill wsc-dash__pill--fail"><?php echo esc_html( (string) $log['status'] ); ?></span></td>
						<td><?php echo esc_html( (string) $log['message'] ); ?></td>
						<td><?php echo esc_html( (string) $log['created_at'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>
	</div>

	<div class="wsc-dash__footer">
		<span>
			<?php
			echo wp_kses_post(
				sprintf(
					/* translators: %s: linked "VMS Universe" */
					__( 'Powered by %s', 'wp-span-checker' ),
					'<a href="https://vmsuniverse.com" target="_blank" rel="noopener noreferrer">VMS Universe</a>'
				)
			);
			?>
		</span>
		<span><?php esc_html_e( 'WP Span Checker — domain intelligence for WordPress forms.', 'wp-span-checker' ); ?></span>
	</div>
</div>
</div>
