<?php
/**
 * Contact Guard settings screen - Contact form protection site-wide.
 *
 * @package WP_Span_Checker
 */

use WP_Span_Checker\AI_Span_Config;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cfg     = AI_Span_Config::get();
$updated = false;

// phpcs:disable WordPress.Security.NonceVerification.Missing
if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['wsc_contact_guard_save'] ) ) {
	if ( ! isset( $_POST['wsc_contact_guard_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wsc_contact_guard_nonce'] ) ), 'wsc_contact_guard_action' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'wp-span-checker' ) );
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to save these settings.', 'wp-span-checker' ) );
	}

	$incoming = array(
		'contact_guard_enabled'       => ! empty( $_POST['contact_guard_enabled'] ),
		'contact_guard_page_id'       => isset( $_POST['contact_guard_page_id'] ) ? absint( $_POST['contact_guard_page_id'] ) : 0,
		'contact_guard_scope'         => isset( $_POST['contact_guard_scope'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_guard_scope'] ) ) : 'site',
		'contact_guard_page_ids'      => isset( $_POST['contact_guard_page_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_guard_page_ids'] ) ) : '',
		'contact_guard_form_selector' => isset( $_POST['contact_guard_form_selector'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_guard_form_selector'] ) ) : '',
		'contact_guard_check_dns'     => ! empty( $_POST['contact_guard_check_dns'] ),
		'contact_guard_check_mx'      => ! empty( $_POST['contact_guard_check_mx'] ),
		'contact_guard_check_disposable' => ! empty( $_POST['contact_guard_check_disposable'] ),
		'contact_guard_webrisk'       => ! empty( $_POST['contact_guard_webrisk'] ),
		'contact_guard_virustotal'    => ! empty( $_POST['contact_guard_virustotal'] ),
		'contact_guard_ai_spam'       => ! empty( $_POST['contact_guard_ai_spam'] ),
		'contact_guard_recaptcha'     => ! empty( $_POST['contact_guard_recaptcha'] ),
	);

	AI_Span_Config::update( $incoming );
	$cfg     = AI_Span_Config::get();
	$updated = true;
}
// phpcs:enable WordPress.Security.NonceVerification.Missing

// Check API keys
$google_config   = get_option( 'wsc-google-config', array() );
$vt_config       = get_option( 'wsc-virustotal-config', array() );
$ai_config       = AI_Span_Config::get();
$has_webrisk_key = ! empty( $google_config['api_key'] );
$has_vt_key      = ! empty( $vt_config['keys'] ) && is_array( $vt_config['keys'] ) && count( $vt_config['keys'] ) > 0;
$has_ai_key      = ! empty( $ai_config['openai_api_key'] ) || ! empty( $ai_config['anthropic_api_key'] ) || ! empty( $ai_config['gemini_api_key'] ) || ! empty( $ai_config['deepseek_api_key'] );
?>

<div class="wrap wsc-admin">
	<?php
	wp_span_checker_admin_page_header(
		__( 'Contact Guard', 'wp-span-checker' ),
		__( 'Protect contact forms from spam and fake emails site-wide.', 'wp-span-checker' )
	);
	?>

	<?php if ( $updated ) : ?>
		<div class="updated"><p><?php esc_html_e( 'Contact Guard settings saved.', 'wp-span-checker' ); ?></p></div>
	<?php endif; ?>

	<div class="wsc-card" style="max-width: 800px;">
		<form method="post">
			<?php wp_nonce_field( 'wsc_contact_guard_action', 'wsc_contact_guard_nonce' ); ?>

			<h3><?php esc_html_e( 'General Settings', 'wp-span-checker' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable Contact Guard', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'contact_guard_enabled',
								'checked'     => ! empty( $cfg['contact_guard_enabled'] ),
								'label'       => __( 'Validate contact forms automatically', 'wp-span-checker' ),
								'description' => __( 'Auto-detect and protect contact forms with email + message validation.', 'wp-span-checker' ),
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="contact_guard_page_id"><?php esc_html_e( 'Contact Page (for redirects)', 'wp-span-checker' ); ?></label></th>
					<td>
						<?php
						wp_dropdown_pages(
							array(
								'name'              => 'contact_guard_page_id',
								'id'                => 'contact_guard_page_id',
								'selected'          => (int) ( $cfg['contact_guard_page_id'] ?? 0 ),
								'show_option_none'  => __( '— Select a page —', 'wp-span-checker' ),
								'option_none_value' => '0',
							)
						);
						?>
						<p class="description"><?php esc_html_e( 'Site-banned users can still access this page to contact you.', 'wp-span-checker' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="contact_guard_scope"><?php esc_html_e( 'Scope', 'wp-span-checker' ); ?></label></th>
					<td>
						<select name="contact_guard_scope" id="contact_guard_scope">
							<option value="site" <?php selected( (string) ( $cfg['contact_guard_scope'] ?? 'site' ), 'site' ); ?>><?php esc_html_e( 'Whole site', 'wp-span-checker' ); ?></option>
							<option value="specific" <?php selected( (string) ( $cfg['contact_guard_scope'] ?? 'site' ), 'specific' ); ?>><?php esc_html_e( 'Specific pages only', 'wp-span-checker' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Choose whether to protect contact forms site-wide or only on specific pages.', 'wp-span-checker' ); ?></p>
					</td>
				</tr>
				<tr id="contact_guard_pages_row">
					<th scope="row"><label><?php esc_html_e( 'Select Pages', 'wp-span-checker' ); ?></label></th>
					<td>
						<div class="wsc-page-selector">
							<div class="wsc-page-search-wrap">
								<input type="text" id="contact_guard_page_search" class="regular-text" placeholder="<?php esc_attr_e( 'Search pages...', 'wp-span-checker' ); ?>" autocomplete="off">
								<div id="contact_guard_page_results" class="wsc-page-results" style="display:none;"></div>
							</div>
							<div id="contact_guard_selected_pages" class="wsc-selected-pages">
								<?php
								$selected_ids = array_filter( array_map( 'absint', explode( ',', $cfg['contact_guard_page_ids'] ?? '' ) ) );
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
							<input type="hidden" name="contact_guard_page_ids" id="contact_guard_page_ids" value="<?php echo esc_attr( (string) ( $cfg['contact_guard_page_ids'] ?? '' ) ); ?>">
						</div>
						<p class="description"><?php esc_html_e( 'Search and select pages where Contact Guard should be active.', 'wp-span-checker' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="contact_guard_form_selector"><?php esc_html_e( 'Form Selector', 'wp-span-checker' ); ?></label></th>
					<td>
						<input type="text" name="contact_guard_form_selector" id="contact_guard_form_selector" class="regular-text" value="<?php echo esc_attr( (string) ( $cfg['contact_guard_form_selector'] ?? '' ) ); ?>" placeholder=".wpcf7-form, #contact-form, .wpforms-form">
						<p class="description">
							<?php esc_html_e( 'CSS selector(s) to identify contact forms. Examples: .wpcf7-form, .wpforms-form', 'wp-span-checker' ); ?>
							<br>
							<?php esc_html_e( 'Leave empty to auto-detect forms with email and message/textarea fields.', 'wp-span-checker' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<h3><?php esc_html_e( 'Email Validation Rules', 'wp-span-checker' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Check Domain DNS', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'contact_guard_check_dns',
								'checked'     => $cfg['contact_guard_check_dns'] ?? true,
								'label'       => __( 'Verify email domain exists (A record)', 'wp-span-checker' ),
								'description' => __( 'Checks if the email domain has a valid DNS A record.', 'wp-span-checker' ),
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Check MX Record', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'contact_guard_check_mx',
								'checked'     => $cfg['contact_guard_check_mx'] ?? true,
								'label'       => __( 'Verify email domain can receive mail', 'wp-span-checker' ),
								'description' => __( 'Checks if the domain has MX records configured.', 'wp-span-checker' ),
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Block Disposable Emails', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'contact_guard_check_disposable',
								'checked'     => $cfg['contact_guard_check_disposable'] ?? true,
								'label'       => __( 'Block temporary/disposable email addresses', 'wp-span-checker' ),
								'description' => __( 'Rejects emails from known disposable providers.', 'wp-span-checker' ),
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
								'name'        => 'contact_guard_webrisk',
								'checked'     => ! empty( $cfg['contact_guard_webrisk'] ),
								'label'       => __( 'Check domain against Google Web Risk', 'wp-span-checker' ),
							)
						);
						?>
						<?php if ( ! $has_webrisk_key ) : ?>
							<p class="description" style="color: #d63638;"><span class="dashicons dashicons-warning"></span> <?php esc_html_e( 'Web Risk API key not configured.', 'wp-span-checker' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-span-checker-api' ) ); ?>"><?php esc_html_e( 'Configure', 'wp-span-checker' ); ?></a></p>
						<?php else : ?>
							<p class="description" style="color: #2e7d32;"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'API key configured', 'wp-span-checker' ); ?></p>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'VirusTotal', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'contact_guard_virustotal',
								'checked'     => ! empty( $cfg['contact_guard_virustotal'] ),
								'label'       => __( 'Check domain with VirusTotal', 'wp-span-checker' ),
							)
						);
						?>
						<?php if ( ! $has_vt_key ) : ?>
							<p class="description" style="color: #d63638;"><span class="dashicons dashicons-warning"></span> <?php esc_html_e( 'VirusTotal API key not configured.', 'wp-span-checker' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-span-checker-api' ) ); ?>"><?php esc_html_e( 'Configure', 'wp-span-checker' ); ?></a></p>
						<?php else : ?>
							<p class="description" style="color: #2e7d32;"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'API key configured', 'wp-span-checker' ); ?></p>
						<?php endif; ?>
					</td>
				</tr>
			</table>

			<h3><?php esc_html_e( 'Message Validation', 'wp-span-checker' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'AI Spam Detection', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'contact_guard_ai_spam',
								'checked'     => ! empty( $cfg['contact_guard_ai_spam'] ),
								'label'       => __( 'Check message content for spam using AI', 'wp-span-checker' ),
								'description' => __( 'Uses configured AI provider to detect spam in message textarea.', 'wp-span-checker' ),
							)
						);
						?>
						<?php if ( ! $has_ai_key ) : ?>
							<p class="description" style="color: #d63638;"><span class="dashicons dashicons-warning"></span> <?php esc_html_e( 'No AI API key configured.', 'wp-span-checker' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-span-checker-ai-settings' ) ); ?>"><?php esc_html_e( 'Configure AI Settings', 'wp-span-checker' ); ?></a></p>
						<?php else : ?>
							<p class="description" style="color: #2e7d32;"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'AI provider configured', 'wp-span-checker' ); ?> (<?php echo esc_html( ucfirst( $ai_config['provider'] ?? 'gemini' ) ); ?>)</p>
						<?php endif; ?>
					</td>
				</tr>
			</table>

			<?php
			$recaptcha_cfg     = get_option( 'wsc-recaptcha-config', array() );
			$has_recaptcha_key = ! empty( $recaptcha_cfg['site_key'] ) && ! empty( $recaptcha_cfg['secret_key'] );
			?>
			<h3><?php esc_html_e( 'reCAPTCHA Protection', 'wp-span-checker' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable reCAPTCHA', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'contact_guard_recaptcha',
								'checked'     => ! empty( $cfg['contact_guard_recaptcha'] ),
								'label'       => __( 'Add Google reCAPTCHA to contact forms', 'wp-span-checker' ),
								'description' => __( 'Requires reCAPTCHA to be completed before form submission.', 'wp-span-checker' ),
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
			</table>

			<p class="submit">
				<button type="submit" name="wsc_contact_guard_save" value="1" class="button button-primary">
					<?php esc_html_e( 'Save Settings', 'wp-span-checker' ); ?>
				</button>
			</p>
		</form>
	</div>

	<div class="wsc-card" style="max-width: 800px; margin-top: 20px;">
		<h3><?php esc_html_e( 'How It Works', 'wp-span-checker' ); ?></h3>
		<ol>
			<li><?php esc_html_e( 'Contact Guard auto-detects contact forms (forms with email + message fields).', 'wp-span-checker' ); ?></li>
			<li><?php esc_html_e( 'When submitted, email is validated (DNS, MX, disposable check).', 'wp-span-checker' ); ?></li>
			<li><?php esc_html_e( 'Message content is checked for spam patterns using AI (if enabled).', 'wp-span-checker' ); ?></li>
			<li><?php esc_html_e( 'Spam attempts record strikes - too many strikes = user blocked.', 'wp-span-checker' ); ?></li>
		</ol>
		<h4><?php esc_html_e( 'vs Form Guard', 'wp-span-checker' ); ?></h4>
		<p>
			<strong><?php esc_html_e( 'Contact Guard', 'wp-span-checker' ); ?>:</strong> <?php esc_html_e( 'Simple, auto-detects contact forms site-wide. Best for most sites.', 'wp-span-checker' ); ?>
			<br>
			<strong><?php esc_html_e( 'Form Guard', 'wp-span-checker' ); ?>:</strong> <?php esc_html_e( 'Advanced, manual configuration per form. For custom/complex forms.', 'wp-span-checker' ); ?>
		</p>
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
		var scope = $('#contact_guard_scope').val();
		$('#contact_guard_pages_row').toggle(scope === 'specific');
	}
	$('#contact_guard_scope').on('change', togglePageIds);
	togglePageIds();

	// Page search
	$('#contact_guard_page_search').on('input', function() {
		var query = $(this).val().trim();
		clearTimeout(searchTimeout);
		
		if (query.length < 2) {
			$('#contact_guard_page_results').hide();
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
						
						$('#contact_guard_page_results').html(html).show();
					}
				}
			});
		}, 300);
	});

	// Select page from results
	$(document).on('click', '#contact_guard_page_results .wsc-page-item', function() {
		var id = $(this).data('id');
		var title = $(this).data('title');
		
		addPageBadge(id, title);
		$('#contact_guard_page_search').val('');
		$('#contact_guard_page_results').hide();
	});

	// Remove badge
	$(document).on('click', '#contact_guard_selected_pages .wsc-badge-remove', function() {
		$(this).closest('.wsc-page-badge').remove();
		updateHiddenInput();
	});

	// Hide results on click outside
	$(document).on('click', function(e) {
		if (!$(e.target).closest('.wsc-page-search-wrap').length) {
			$('#contact_guard_page_results').hide();
		}
	});

	function addPageBadge(id, title) {
		var badge = '<span class="wsc-page-badge" data-id="' + id + '">' +
			escapeHtml(title) +
			'<button type="button" class="wsc-badge-remove" aria-label="<?php echo esc_js( __( 'Remove', 'wp-span-checker' ) ); ?>">&times;</button>' +
			'</span>';
		$('#contact_guard_selected_pages').append(badge);
		updateHiddenInput();
	}

	function getSelectedIds() {
		var ids = [];
		$('#contact_guard_selected_pages .wsc-page-badge').each(function() {
			ids.push(parseInt($(this).data('id'), 10));
		});
		return ids;
	}

	function updateHiddenInput() {
		var ids = getSelectedIds();
		$('#contact_guard_page_ids').val(ids.join(','));
	}

	function escapeHtml(text) {
		var div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}
});
</script>
