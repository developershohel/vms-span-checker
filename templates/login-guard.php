<?php
/**
 * Login Guard settings screen - reCAPTCHA protection for login forms.
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
if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['wsc_login_guard_save'] ) ) {
	if ( ! isset( $_POST['wsc_login_guard_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wsc_login_guard_nonce'] ) ), 'wsc_login_guard_action' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'wp-span-checker' ) );
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to save these settings.', 'wp-span-checker' ) );
	}

	$incoming = array(
		'login_guard_enabled'   => ! empty( $_POST['login_guard_enabled'] ),
		'login_guard_recaptcha' => ! empty( $_POST['login_guard_recaptcha'] ),
		'login_guard_scope'     => isset( $_POST['login_guard_scope'] ) ? sanitize_text_field( wp_unslash( $_POST['login_guard_scope'] ) ) : 'default',
		'login_guard_page_ids'  => isset( $_POST['login_guard_page_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['login_guard_page_ids'] ) ) : '',
	);

	AI_Span_Config::update( $incoming );
	$cfg     = AI_Span_Config::get();
	$updated = true;
}
// phpcs:enable WordPress.Security.NonceVerification.Missing

$recaptcha_cfg     = get_option( 'wsc-recaptcha-config', array() );
$has_recaptcha_key = ! empty( $recaptcha_cfg['site_key'] ) && ! empty( $recaptcha_cfg['secret_key'] );
?>

<div class="wrap wsc-admin">
	<?php
	wp_span_checker_admin_page_header(
		__( 'Login Guard', 'wp-span-checker' ),
		__( 'Add reCAPTCHA protection to WordPress login forms to prevent brute force attacks.', 'wp-span-checker' )
	);
	?>

	<?php if ( $updated ) : ?>
		<div class="updated"><p><?php esc_html_e( 'Login Guard settings saved.', 'wp-span-checker' ); ?></p></div>
	<?php endif; ?>

	<div class="wsc-card" style="max-width: 800px;">
		<form method="post">
			<?php wp_nonce_field( 'wsc_login_guard_action', 'wsc_login_guard_nonce' ); ?>

			<h3><?php esc_html_e( 'General Settings', 'wp-span-checker' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable Login Guard', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'login_guard_enabled',
								'checked'     => ! empty( $cfg['login_guard_enabled'] ),
								'label'       => __( 'Enable Login Guard protection', 'wp-span-checker' ),
								'description' => __( 'Adds security features to the WordPress login page.', 'wp-span-checker' ),
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
								'name'        => 'login_guard_recaptcha',
								'checked'     => ! empty( $cfg['login_guard_recaptcha'] ),
								'label'       => __( 'Add Google reCAPTCHA to login form', 'wp-span-checker' ),
								'description' => __( 'Requires reCAPTCHA to be completed before login. Helps prevent automated login attempts.', 'wp-span-checker' ),
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
					<th scope="row"><label for="login_guard_scope"><?php esc_html_e( 'Login Page', 'wp-span-checker' ); ?></label></th>
					<td>
						<select name="login_guard_scope" id="login_guard_scope">
							<option value="default" <?php selected( (string) ( $cfg['login_guard_scope'] ?? 'default' ), 'default' ); ?>><?php esc_html_e( 'Default WordPress login (wp-login.php)', 'wp-span-checker' ); ?></option>
							<option value="specific" <?php selected( (string) ( $cfg['login_guard_scope'] ?? 'default' ), 'specific' ); ?>><?php esc_html_e( 'Custom login page(s)', 'wp-span-checker' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Choose where to apply Login Guard protection.', 'wp-span-checker' ); ?></p>
					</td>
				</tr>
				<tr id="login_guard_pages_row" style="display:none;">
					<th scope="row"><label><?php esc_html_e( 'Select Login Pages', 'wp-span-checker' ); ?></label></th>
					<td>
						<div class="wsc-page-selector">
							<div class="wsc-page-search-wrap">
								<input type="text" id="login_guard_page_search" class="regular-text" placeholder="<?php esc_attr_e( 'Search pages...', 'wp-span-checker' ); ?>" autocomplete="off">
								<div id="login_guard_page_results" class="wsc-page-results" style="display:none;"></div>
							</div>
							<div id="login_guard_selected_pages" class="wsc-selected-pages">
								<?php
								$selected_ids = array_filter( array_map( 'absint', explode( ',', $cfg['login_guard_page_ids'] ?? '' ) ) );
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
							<input type="hidden" name="login_guard_page_ids" id="login_guard_page_ids" value="<?php echo esc_attr( (string) ( $cfg['login_guard_page_ids'] ?? '' ) ); ?>">
						</div>
						<p class="description"><?php esc_html_e( 'Select pages that contain custom login forms (e.g., WooCommerce My Account, membership plugin login pages).', 'wp-span-checker' ); ?></p>
					</td>
				</tr>
			</table>

			<p class="submit">
				<button type="submit" name="wsc_login_guard_save" value="1" class="button button-primary">
					<?php esc_html_e( 'Save Settings', 'wp-span-checker' ); ?>
				</button>
			</p>
		</form>
	</div>

	<div class="wsc-card" style="max-width: 800px; margin-top: 20px;">
		<h3><?php esc_html_e( 'How It Works', 'wp-span-checker' ); ?></h3>
		<ol>
			<li><?php esc_html_e( 'Login Guard adds reCAPTCHA verification to the WordPress login page.', 'wp-span-checker' ); ?></li>
			<li><?php esc_html_e( 'Users must complete the reCAPTCHA before the login button becomes active (v2) or tokens are verified server-side (v3).', 'wp-span-checker' ); ?></li>
			<li><?php esc_html_e( 'This helps prevent brute force attacks and automated login attempts.', 'wp-span-checker' ); ?></li>
		</ol>
		<h4><?php esc_html_e( 'Recommended Setup', 'wp-span-checker' ); ?></h4>
		<ul style="list-style: disc; margin-left: 20px;">
			<li><strong><?php esc_html_e( 'reCAPTCHA v2', 'wp-span-checker' ); ?>:</strong> <?php esc_html_e( 'Shows a checkbox challenge. Login button is disabled until reCAPTCHA is completed.', 'wp-span-checker' ); ?></li>
			<li><strong><?php esc_html_e( 'reCAPTCHA v3', 'wp-span-checker' ); ?>:</strong> <?php esc_html_e( 'Invisible scoring system. No user interaction required, but suspicious logins may be blocked.', 'wp-span-checker' ); ?></li>
		</ul>
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
		var scope = $('#login_guard_scope').val();
		$('#login_guard_pages_row').toggle(scope === 'specific');
	}
	$('#login_guard_scope').on('change', togglePageIds);
	togglePageIds();

	$('#login_guard_page_search').on('input', function() {
		var query = $(this).val().trim();
		clearTimeout(searchTimeout);
		
		if (query.length < 2) {
			$('#login_guard_page_results').hide();
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
						
						$('#login_guard_page_results').html(html).show();
					}
				}
			});
		}, 300);
	});

	$(document).on('click', '#login_guard_page_results .wsc-page-item', function() {
		var id = $(this).data('id');
		var title = $(this).data('title');
		addPageBadge(id, title);
		$('#login_guard_page_search').val('');
		$('#login_guard_page_results').hide();
	});

	$(document).on('click', '#login_guard_selected_pages .wsc-badge-remove', function() {
		$(this).closest('.wsc-page-badge').remove();
		updateHiddenInput();
	});

	$(document).on('click', function(e) {
		if (!$(e.target).closest('.wsc-page-search-wrap').length) {
			$('#login_guard_page_results').hide();
		}
	});

	function addPageBadge(id, title) {
		var badge = '<span class="wsc-page-badge" data-id="' + id + '">' +
			escapeHtml(title) +
			'<button type="button" class="wsc-badge-remove" aria-label="<?php echo esc_js( __( 'Remove', 'wp-span-checker' ) ); ?>">&times;</button>' +
			'</span>';
		$('#login_guard_selected_pages').append(badge);
		updateHiddenInput();
	}

	function getSelectedIds() {
		var ids = [];
		$('#login_guard_selected_pages .wsc-page-badge').each(function() {
			ids.push(parseInt($(this).data('id'), 10));
		});
		return ids;
	}

	function updateHiddenInput() {
		var ids = getSelectedIds();
		$('#login_guard_page_ids').val(ids.join(','));
	}

	function escapeHtml(text) {
		var div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}
});
</script>
