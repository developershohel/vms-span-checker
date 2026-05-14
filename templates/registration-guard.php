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
	
	// Save frontend settings to AI config
	$ai_cfg_data = array(
		'registration_guard_frontend'  => ! empty( $_POST['rg_frontend'] ),
		'registration_guard_recaptcha' => ! empty( $_POST['rg_recaptcha'] ),
		'registration_guard_scope'     => isset( $_POST['rg_scope'] ) ? sanitize_text_field( wp_unslash( $_POST['rg_scope'] ) ) : 'default',
		'registration_guard_page_ids'  => isset( $_POST['rg_page_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['rg_page_ids'] ) ) : '',
	);
	\WP_Span_Checker\AI_Span_Config::update( $ai_cfg_data );
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

		<?php
		$ai_cfg            = \WP_Span_Checker\AI_Span_Config::get();
		$recaptcha_cfg     = get_option( 'wsc-recaptcha-config', array() );
		$has_recaptcha_key = ! empty( $recaptcha_cfg['site_key'] ) && ! empty( $recaptcha_cfg['secret_key'] );
		?>
		<h3><?php esc_html_e( 'Frontend Validation', 'wp-span-checker' ); ?></h3>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable frontend validation', 'wp-span-checker' ); ?></th>
				<td>
					<?php
					wp_span_checker_admin_switch(
						array(
							'name'        => 'rg_frontend',
							'checked'     => ! empty( $ai_cfg['registration_guard_frontend'] ),
							'description' => __( 'Adds a validation button to registration forms. Email is validated via AJAX before form submission. Stores a validation token (IP-based) to verify backend.', 'wp-span-checker' ),
						)
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable reCAPTCHA', 'wp-span-checker' ); ?></th>
				<td>
					<?php
					wp_span_checker_admin_switch(
						array(
							'name'        => 'rg_recaptcha',
							'checked'     => ! empty( $ai_cfg['registration_guard_recaptcha'] ),
							'description' => __( 'Add Google reCAPTCHA to registration forms for bot protection.', 'wp-span-checker' ),
						)
					);
					?>
					<?php if ( ! $has_recaptcha_key ) : ?>
						<p class="description" style="color: #d63638;"><span class="dashicons dashicons-warning"></span> <?php esc_html_e( 'reCAPTCHA keys not configured.', 'wp-span-checker' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-span-checker-api' ) ); ?>"><?php esc_html_e( 'Configure API Settings', 'wp-span-checker' ); ?></a></p>
					<?php else : ?>
						<p class="description" style="color: #2e7d32;"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'reCAPTCHA configured', 'wp-span-checker' ); ?> (<?php echo esc_html( ucfirst( $recaptcha_cfg['version'] ?? 'v2' ) ); ?>)</p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="rg_scope"><?php esc_html_e( 'Registration Page', 'wp-span-checker' ); ?></label></th>
				<td>
					<select name="rg_scope" id="rg_scope">
						<option value="default" <?php selected( (string) ( $ai_cfg['registration_guard_scope'] ?? 'default' ), 'default' ); ?>><?php esc_html_e( 'Default WordPress registration (wp-login.php?action=register)', 'wp-span-checker' ); ?></option>
						<option value="specific" <?php selected( (string) ( $ai_cfg['registration_guard_scope'] ?? 'default' ), 'specific' ); ?>><?php esc_html_e( 'Custom registration page(s)', 'wp-span-checker' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Choose where to apply frontend validation and reCAPTCHA.', 'wp-span-checker' ); ?></p>
				</td>
			</tr>
			<tr id="rg_pages_row" style="display:none;">
				<th scope="row"><label><?php esc_html_e( 'Select Registration Pages', 'wp-span-checker' ); ?></label></th>
				<td>
					<div class="wsc-page-selector">
						<div class="wsc-page-search-wrap">
							<input type="text" id="rg_page_search" class="regular-text" placeholder="<?php esc_attr_e( 'Search pages...', 'wp-span-checker' ); ?>" autocomplete="off">
							<div id="rg_page_results" class="wsc-page-results" style="display:none;"></div>
						</div>
						<div id="rg_selected_pages" class="wsc-selected-pages">
							<?php
							$selected_ids = array_filter( array_map( 'absint', explode( ',', $ai_cfg['registration_guard_page_ids'] ?? '' ) ) );
							foreach ( $selected_ids as $pid ) :
								$page = get_post( $pid );
								if ( $page && 'page' === $page->post_type ) :
									?>
									<span class="wsc-page-badge" data-id="<?php echo esc_attr( $pid ); ?>">
										<?php echo esc_html( $page->post_title ); ?>
										<button type="button" class="wsc-badge-remove" aria-label="<?php esc_attr_e( 'Remove', 'wp-span-checker' ); ?>">&times;</button>
									</span>
									<?php
								endif;
							endforeach;
							?>
						</div>
						<input type="hidden" name="rg_page_ids" id="rg_page_ids" value="<?php echo esc_attr( (string) ( $ai_cfg['registration_guard_page_ids'] ?? '' ) ); ?>">
					</div>
					<p class="description"><?php esc_html_e( 'Select pages that contain custom registration forms (e.g., WooCommerce registration, membership plugin signup pages).', 'wp-span-checker' ); ?></p>
				</td>
			</tr>
		</table>
			<p class="submit">
				<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save settings', 'wp-span-checker' ); ?>">
			</p>
		</form>
	</div>
</div>

<style>
.wsc-page-selector { max-width: 500px; }
.wsc-page-search-wrap { position: relative; margin-bottom: 10px; }
.wsc-page-results {
	position: absolute;
	top: 100%;
	left: 0;
	right: 0;
	background: #fff;
	border: 1px solid #8c8f94;
	border-top: none;
	max-height: 200px;
	overflow-y: auto;
	z-index: 1000;
	box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
.wsc-page-results .wsc-page-item {
	padding: 8px 12px;
	cursor: pointer;
	border-bottom: 1px solid #f0f0f0;
}
.wsc-page-results .wsc-page-item:hover {
	background: #f0f7ff;
}
.wsc-page-results .wsc-page-item:last-child {
	border-bottom: none;
}
.wsc-page-results .wsc-no-results {
	padding: 8px 12px;
	color: #666;
	font-style: italic;
}
.wsc-selected-pages {
	display: flex;
	flex-wrap: wrap;
	gap: 8px;
	min-height: 36px;
	padding: 8px;
	background: #f9f9f9;
	border: 1px solid #ddd;
	border-radius: 4px;
}
.wsc-page-badge {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	padding: 4px 8px 4px 12px;
	background: #2271b1;
	color: #fff;
	border-radius: 20px;
	font-size: 13px;
}
.wsc-badge-remove {
	background: rgba(255,255,255,0.2);
	border: none;
	color: #fff;
	cursor: pointer;
	padding: 0 4px;
	border-radius: 50%;
	font-size: 14px;
	line-height: 1;
}
.wsc-badge-remove:hover {
	background: rgba(255,255,255,0.4);
}
</style>
<script>
jQuery(function($) {
	var ajaxUrl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
	var nonce = '<?php echo esc_js( wp_create_nonce( 'wp_span_checker_nonce' ) ); ?>';
	var searchTimeout;

	function togglePageIds() {
		var scope = $('#rg_scope').val();
		$('#rg_pages_row').toggle(scope === 'specific');
	}
	$('#rg_scope').on('change', togglePageIds);
	togglePageIds();

	$('#rg_page_search').on('input', function() {
		var query = $(this).val().trim();
		clearTimeout(searchTimeout);
		
		if (query.length < 2) {
			$('#rg_page_results').hide();
			return;
		}

		searchTimeout = setTimeout(function() {
			$.ajax({
				url: ajaxUrl,
				type: 'POST',
				data: {
					action: 'wsc_search_pages',
					nonce: nonce,
					search: query,
					per_page: 10
				},
				success: function(response) {
					if (response.success && response.data.items) {
						var html = '';
						var selectedIds = getSelectedIds();
						
						response.data.items.forEach(function(item) {
							if (selectedIds.indexOf(item.id) === -1) {
								html += '<div class="wsc-page-item" data-id="' + item.id + '" data-title="' + escapeHtml(item.title) + '">' + escapeHtml(item.title) + '</div>';
							}
						});
						
						if (html === '') {
							html = '<div class="wsc-no-results"><?php echo esc_js( __( 'No pages found or all matching pages already selected.', 'wp-span-checker' ) ); ?></div>';
						}
						
						$('#rg_page_results').html(html).show();
					}
				}
			});
		}, 300);
	});

	$(document).on('click', '#rg_page_results .wsc-page-item', function() {
		var id = $(this).data('id');
		var title = $(this).data('title');
		addPageBadge(id, title);
		$('#rg_page_search').val('');
		$('#rg_page_results').hide();
	});

	$(document).on('click', '#rg_selected_pages .wsc-badge-remove', function() {
		$(this).closest('.wsc-page-badge').remove();
		updateHiddenInput();
	});

	$(document).on('click', function(e) {
		if (!$(e.target).closest('.wsc-page-search-wrap').length) {
			$('#rg_page_results').hide();
		}
	});

	function addPageBadge(id, title) {
		var badge = '<span class="wsc-page-badge" data-id="' + id + '">' +
			escapeHtml(title) +
			'<button type="button" class="wsc-badge-remove" aria-label="<?php echo esc_js( __( 'Remove', 'wp-span-checker' ) ); ?>">&times;</button>' +
			'</span>';
		$('#rg_selected_pages').append(badge);
		updateHiddenInput();
	}

	function getSelectedIds() {
		var ids = [];
		$('#rg_selected_pages .wsc-page-badge').each(function() {
			ids.push(parseInt($(this).data('id'), 10));
		});
		return ids;
	}

	function updateHiddenInput() {
		var ids = getSelectedIds();
		$('#rg_page_ids').val(ids.join(','));
	}

	function escapeHtml(text) {
		var div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}
});
</script>
