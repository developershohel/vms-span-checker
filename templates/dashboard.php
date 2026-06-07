<?php
/**
 * Admin dashboard template — VMS Elements Form Guard home.
 *
 * The DB lookups read aggregated counts from the plugin-owned
 * `{$wpdb->prefix}vms_elements_form_guard_logs` custom table; identifiers are hardcoded.
 *
 * @package VMS_Elements_Form_Guard
 *
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 */

use VMS_Elements_Form_Guard\AI_Span_Config;
use VMS_Elements_Form_Guard\Dashboard;

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
	$ai_blocked_guests = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}vms_elements_form_guard_comment_enforcement WHERE blocked = 1" );
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

$pro_active = function_exists( 'vms_elements_form_guard_pro_runtime_ready' )
	? vms_elements_form_guard_pro_runtime_ready()
	: (bool) apply_filters( 'vms_elements_form_guard_is_pro_active', false );

$insights = array();
if ( 0 === (int) $summary['form_mappings'] ) {
	if ( $pro_active ) {
		$insights[] = __( 'No form mappings yet—add one under Form Guard to start logging checks.', 'vms-elements-form-guard' );
	} else {
		$insights[] = __( 'Custom form mapping (Form Guard) is included in VMS Elements Form Guard Pro—see Upgrade Now in the menu.', 'vms-elements-form-guard' );
	}
}
if ( 0 === (int) $summary['total_logs'] ) {
	$insights[] = __( 'No validation events recorded yet. Traffic will appear here once forms are mapped and submitted.', 'vms-elements-form-guard' );
} else {
	$failed_n  = (int) $summary['failed_validations'];
	$success_n = (int) $summary['success_logs'];
	if ( $failed_n > 0 && $success_n > 0 && $failed_n > ( $success_n * 2 ) ) {
		$insights[] = __( 'Blocks are outpacing passes—review disposable rules and the top failing domains below.', 'vms-elements-form-guard' );
	} elseif ( $pass_rate >= 90 ) {
		$insights[] = __( 'Strong pass rate. Monitor API quotas and keep disposable lists current.', 'vms-elements-form-guard' );
	}
}
if ( empty( $insights ) ) {
	$insights[] = __( 'Dashboard is live—use the breakdowns below to spot patterns in validation traffic.', 'vms-elements-form-guard' );
}

$google_on = ! empty( $summary['google_api_ready'] );
$vt_n      = (int) $summary['virustotal_keys'];

$quick_links = array(
	array(
		'url'   => admin_url( 'admin.php?page=vms-elements-form-guard-whitelist' ),
		'icon'  => '✓',
		'title' => __( 'Whitelist domains', 'vms-elements-form-guard' ),
		'desc'  => __( 'Trusted domains that always pass', 'vms-elements-form-guard' ),
	),
	array(
		'url'   => admin_url( 'admin.php?page=vms-elements-form-guard-disposable' ),
		'icon'  => '⛔',
		'title' => __( 'Disposable list', 'vms-elements-form-guard' ),
		'desc'  => __( 'Block throwaway email hosts', 'vms-elements-form-guard' ),
	),
	array(
		'url'   => $pro_active
			? admin_url( 'admin.php?page=vms-elements-form-guard-form-settings' )
			: admin_url( 'admin.php?page=vefg-upgrade-now' ),
		'icon'  => $pro_active ? '⚡' : '⭐',
		'title' => $pro_active ? __( 'Form Guard', 'vms-elements-form-guard' ) : __( 'Upgrade Now', 'vms-elements-form-guard' ),
		'desc'  => $pro_active
			? __( 'Map forms to validation & API scans', 'vms-elements-form-guard' )
			: __( 'Form Guard, Contact Guard, Subscribe Guard, and more', 'vms-elements-form-guard' ),
	),
	array(
		'url'   => admin_url( 'admin.php?page=vms-elements-form-guard-api' ),
		'icon'  => '🔐',
		'title' => __( 'API settings', 'vms-elements-form-guard' ),
		'desc'  => __( 'Web Risk & VirusTotal keys', 'vms-elements-form-guard' ),
	),
	array(
		'url'   => admin_url( 'admin.php?page=vms-elements-form-guard-ai-settings' ),
		'icon'  => '🤖',
		'title' => __( 'AI VMS Elements Form Guard', 'vms-elements-form-guard' ),
		'desc'  => __( 'OpenAI, Anthropic, Gemini, DeepSeek, or Bedrock', 'vms-elements-form-guard' ),
	),
	array(
		'url'   => admin_url( 'admin.php?page=vms-elements-form-guard-registration' ),
		'icon'  => '🛡',
		'title' => __( 'Registration guard', 'vms-elements-form-guard' ),
		'desc'  => __( 'MX + reputation on new signups', 'vms-elements-form-guard' ),
	),
);
?>

<div class="wrap vefg-admin">
<div class="vefg-dash">
	<?php
	vms_elements_form_guard_admin_page_header(
		__( 'Dashboard', 'vms-elements-form-guard' ),
		__( 'Monitor domain checks, list coverage, and API readiness in one place—built for calm, fast triage.', 'vms-elements-form-guard' )
	);
	?>
	<div class="vefg-dash__badge-row" role="group" aria-label="<?php esc_attr_e( 'API and pass-rate summary', 'vms-elements-form-guard' ); ?>">
		<span class="vefg-dash__badge <?php echo $google_on ? 'vefg-dash__badge--ok' : 'vefg-dash__badge--warn'; ?>">
			<?php echo $google_on ? esc_html__( 'Google Web Risk: key set', 'vms-elements-form-guard' ) : esc_html__( 'Google Web Risk: add key', 'vms-elements-form-guard' ); ?>
		</span>
		<span class="vefg-dash__badge <?php echo $vt_n > 0 ? 'vefg-dash__badge--ok' : 'vefg-dash__badge--warn'; ?>">
			<?php
			if ( $vt_n > 0 ) {
				echo esc_html(
					sprintf(
						/* translators: %d: number of VirusTotal keys */
						_n( 'VirusTotal: %d key', 'VirusTotal: %d keys', $vt_n, 'vms-elements-form-guard' ),
						$vt_n
					)
				);
			} else {
				esc_html_e( 'VirusTotal: no keys', 'vms-elements-form-guard' );
			}
			?>
		</span>
		<span class="vefg-dash__badge vefg-dash__badge--neutral">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %d: percentage of successful validations */
					__( 'Pass rate: %d%%', 'vms-elements-form-guard' ),
					$pass_rate
				)
			);
			?>
		</span>
		<?php if ( ! empty( $ai_cfg['ai_enabled'] ) ) : ?>
			<span class="vefg-dash__badge <?php echo $ai_blocked_guests > 0 ? 'vefg-dash__badge--warn' : 'vefg-dash__badge--ok'; ?>">
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: max strikes before block, 2: number of blocked commenters */
						__( 'AI comments: max %1$d strikes · %2$d blocked', 'vms-elements-form-guard' ),
						(int) $ai_cfg['comment_max_strikes'],
						$ai_blocked_guests
					)
				);
				?>
			</span>
		<?php endif; ?>
	</div>

	<div class="vefg-dash__grid">
		<div class="vefg-dash__card">
			<p class="vefg-dash__card-label"><?php esc_html_e( 'Total checks logged', 'vms-elements-form-guard' ); ?></p>
			<p class="vefg-dash__card-value"><?php echo esc_html( (string) (int) $summary['total_logs'] ); ?></p>
			<p class="vefg-dash__card-note"><?php esc_html_e( 'All validation events recorded by VMS Elements Form Guard.', 'vms-elements-form-guard' ); ?></p>
		</div>
		<div class="vefg-dash__card">
			<p class="vefg-dash__card-label"><?php esc_html_e( 'Passed', 'vms-elements-form-guard' ); ?></p>
			<p class="vefg-dash__card-value"><?php echo esc_html( (string) (int) $summary['success_logs'] ); ?></p>
			<p class="vefg-dash__card-note"><?php esc_html_e( 'Domains that cleared your rules and optional APIs.', 'vms-elements-form-guard' ); ?></p>
		</div>
		<div class="vefg-dash__card">
			<p class="vefg-dash__card-label"><?php esc_html_e( 'Blocked / failed', 'vms-elements-form-guard' ); ?></p>
			<p class="vefg-dash__card-value"><?php echo esc_html( (string) (int) $summary['failed_validations'] ); ?></p>
			<p class="vefg-dash__card-note"><?php esc_html_e( 'Disposable list, HTTPS, or reputation blocks.', 'vms-elements-form-guard' ); ?></p>
		</div>
		<div class="vefg-dash__card">
			<p class="vefg-dash__card-label"><?php esc_html_e( 'Whitelist entries', 'vms-elements-form-guard' ); ?></p>
			<p class="vefg-dash__card-value"><?php echo esc_html( (string) (int) $summary['whitelist_count'] ); ?></p>
		</div>
		<div class="vefg-dash__card">
			<p class="vefg-dash__card-label"><?php esc_html_e( 'Disposable domains', 'vms-elements-form-guard' ); ?></p>
			<p class="vefg-dash__card-value"><?php echo esc_html( (string) (int) $summary['disposable_count'] ); ?></p>
		</div>
		<div class="vefg-dash__card">
			<p class="vefg-dash__card-label"><?php esc_html_e( 'Form mappings', 'vms-elements-form-guard' ); ?></p>
			<p class="vefg-dash__card-value"><?php echo esc_html( (string) (int) $summary['form_mappings'] ); ?></p>
			<p class="vefg-dash__card-note"><?php esc_html_e( 'Active form ↔ validation profiles.', 'vms-elements-form-guard' ); ?></p>
		</div>
		<div class="vefg-dash__card">
			<p class="vefg-dash__card-label"><?php esc_html_e( 'Login events', 'vms-elements-form-guard' ); ?></p>
			<p class="vefg-dash__card-value"><?php echo esc_html( (string) (int) $summary['login_attempts'] ); ?></p>
		</div>
		<div class="vefg-dash__card">
			<p class="vefg-dash__card-label"><?php esc_html_e( 'Registration events', 'vms-elements-form-guard' ); ?></p>
			<p class="vefg-dash__card-value"><?php echo esc_html( (string) (int) $summary['registration_attempts'] ); ?></p>
		</div>
	</div>

	<h2 class="vefg-dash__section-title"><?php esc_html_e( 'Analysis & insights', 'vms-elements-form-guard' ); ?></h2>
	<div class="vefg-dash__analysis">
		<div class="vefg-dash__panel vefg-dash__panel--wide">
			<h3 class="vefg-dash__panel-title"><?php esc_html_e( 'Events by type', 'vms-elements-form-guard' ); ?></h3>
			<?php if ( empty( $analysis['events_by_type'] ) ) : ?>
				<p class="vefg-dash__muted"><?php esc_html_e( 'No log data to chart yet.', 'vms-elements-form-guard' ); ?></p>
			<?php else : ?>
				<ul class="vefg-dash__bars" role="list">
					<?php foreach ( $analysis['events_by_type'] as $et ) : ?>
						<?php
						$cnt           = (int) $et['count'];
						$pct           = (int) round( 100 * $cnt / $max_event_type );
						$log_type_name = (string) $et['type'];
						?>
						<li class="vefg-dash__bar-row">
							<span class="vefg-dash__bar-label"><?php echo esc_html( '' !== $log_type_name ? $log_type_name : __( '(empty)', 'vms-elements-form-guard' ) ); ?></span>
							<span class="vefg-dash__bar-track" aria-hidden="true">
								<span class="vefg-dash__bar-fill" style="width: <?php echo esc_attr( (string) $pct ); ?>%;"></span>
							</span>
							<span class="vefg-dash__bar-count"><?php echo esc_html( (string) $cnt ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<div class="vefg-dash__panel">
			<h3 class="vefg-dash__panel-title"><?php esc_html_e( 'Signals', 'vms-elements-form-guard' ); ?></h3>
			<ul class="vefg-dash__insight-list">
				<?php foreach ( $insights as $line ) : ?>
					<li><?php echo esc_html( $line ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>

	<div class="vefg-dash__analysis vefg-dash__analysis--split">
		<div class="vefg-dash__panel">
			<h3 class="vefg-dash__panel-title"><?php esc_html_e( 'Top blocked domains', 'vms-elements-form-guard' ); ?></h3>
			<?php if ( empty( $analysis['top_failed_domains'] ) ) : ?>
				<p class="vefg-dash__muted"><?php esc_html_e( 'No failed domain counts yet.', 'vms-elements-form-guard' ); ?></p>
			<?php else : ?>
				<ul class="vefg-dash__mini-bars" role="list">
					<?php foreach ( $analysis['top_failed_domains'] as $d ) : ?>
						<?php
						$dc  = (int) $d['count'];
						$dp  = (int) round( 100 * $dc / $max_domain_hits );
						$dom = (string) $d['domain'];
						?>
						<li class="vefg-dash__mini-row">
							<code class="vefg-dash__code"><?php echo esc_html( $dom ); ?></code>
							<span class="vefg-dash__mini-track" aria-hidden="true"><span class="vefg-dash__mini-fill" style="width: <?php echo esc_attr( (string) $dp ); ?>%;"></span></span>
							<span class="vefg-dash__mini-n"><?php echo esc_html( (string) $dc ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<div class="vefg-dash__panel">
			<h3 class="vefg-dash__panel-title"><?php esc_html_e( 'Top failure messages', 'vms-elements-form-guard' ); ?></h3>
			<?php if ( empty( $analysis['top_failed_messages'] ) ) : ?>
				<p class="vefg-dash__muted"><?php esc_html_e( 'No failure messages grouped yet.', 'vms-elements-form-guard' ); ?></p>
			<?php else : ?>
				<ul class="vefg-dash__mini-bars" role="list">
					<?php foreach ( $analysis['top_failed_messages'] as $fail_msg_row ) : ?>
						<?php
						$mc  = (int) $fail_msg_row['count'];
						$mp  = (int) round( 100 * $mc / $max_message_hits );
						$msg = (string) $fail_msg_row['message'];
						?>
						<li class="vefg-dash__mini-row vefg-dash__mini-row--msg">
							<span class="vefg-dash__msg"><?php echo esc_html( wp_trim_words( $msg, 12, '…' ) ); ?></span>
							<span class="vefg-dash__mini-track" aria-hidden="true"><span class="vefg-dash__mini-fill vefg-dash__mini-fill--rose" style="width: <?php echo esc_attr( (string) $mp ); ?>%;"></span></span>
							<span class="vefg-dash__mini-n"><?php echo esc_html( (string) $mc ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>

	<h2 class="vefg-dash__section-title"><?php esc_html_e( 'Quick actions', 'vms-elements-form-guard' ); ?></h2>
	<div class="vefg-dash__actions">
		<?php foreach ( $quick_links as $quick_item ) : ?>
			<a class="vefg-dash__action" href="<?php echo esc_url( $quick_item['url'] ); ?>">
				<span class="vefg-dash__action-icon" aria-hidden="true"><?php echo esc_html( $quick_item['icon'] ); ?></span>
				<span class="vefg-dash__action-text">
					<strong><?php echo esc_html( $quick_item['title'] ); ?></strong>
					<span><?php echo esc_html( $quick_item['desc'] ); ?></span>
				</span>
			</a>
		<?php endforeach; ?>
	</div>

	<h2 class="vefg-dash__section-title"><?php esc_html_e( 'Recent blocked activity', 'vms-elements-form-guard' ); ?></h2>
	<div class="vefg-dash__table-wrap">
		<table class="vefg-dash__table widefat">
			<thead>
				<tr>
					<th><?php esc_html_e( 'ID', 'vms-elements-form-guard' ); ?></th>
					<th><?php esc_html_e( 'Type', 'vms-elements-form-guard' ); ?></th>
					<th><?php esc_html_e( 'IP', 'vms-elements-form-guard' ); ?></th>
					<th><?php esc_html_e( 'Domain', 'vms-elements-form-guard' ); ?></th>
					<th><?php esc_html_e( 'Status', 'vms-elements-form-guard' ); ?></th>
					<th><?php esc_html_e( 'Message', 'vms-elements-form-guard' ); ?></th>
					<th><?php esc_html_e( 'Date', 'vms-elements-form-guard' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php if ( empty( $spam_logs ) ) : ?>
				<tr>
					<td colspan="7"><?php esc_html_e( 'No failed validations yet—your traffic looks quiet.', 'vms-elements-form-guard' ); ?></td>
				</tr>
			<?php else : ?>
				<?php foreach ( $spam_logs as $log ) : ?>
					<tr>
						<td><?php echo esc_html( (string) $log['id'] ); ?></td>
						<td><?php echo esc_html( (string) $log['type'] ); ?></td>
						<td><?php echo esc_html( (string) $log['ip'] ); ?></td>
						<td><?php echo esc_html( (string) $log['domain'] ); ?></td>
						<td><span class="vefg-dash__pill vefg-dash__pill--fail"><?php echo esc_html( (string) $log['status'] ); ?></span></td>
						<td><?php echo esc_html( (string) $log['message'] ); ?></td>
						<td><?php echo esc_html( (string) $log['created_at'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>
	</div>

	<div class="vefg-dash__footer">
		<span><?php esc_html_e( 'VMS Elements Form Guard — domain intelligence for WordPress forms.', 'vms-elements-form-guard' ); ?></span>
	</div>
</div>
</div>
