<?php
/**
 * Email Templates admin page.
 *
 * @package WP_Span_Checker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings    = \WP_Span_Checker\Email_Templates::get_settings();
$email_types = \WP_Span_Checker\Email_Templates::get_email_types();
?>
<div class="wrap wsc-email-templates-admin">
	<h1><?php esc_html_e( 'Email Templates', 'wp-span-checker' ); ?></h1>

	<div class="wsc-email-admin-tabs">
		<button class="wsc-email-tab active" data-tab="design"><?php esc_html_e( 'Design & Preview', 'wp-span-checker' ); ?></button>
		<button class="wsc-email-tab" data-tab="branding"><?php esc_html_e( 'Branding', 'wp-span-checker' ); ?></button>
		<button class="wsc-email-tab" data-tab="test"><?php esc_html_e( 'Send Test', 'wp-span-checker' ); ?></button>
	</div>

	<!-- Design & Preview Tab -->
	<div class="wsc-email-panel active" id="panel-design">
		<div class="wsc-email-design-wrap">
			<div class="wsc-email-design-controls">
				<div class="wsc-email-card">
					<h2><?php esc_html_e( 'Layout Settings', 'wp-span-checker' ); ?></h2>
					<table class="form-table">
						<tr>
							<th><label for="container_width"><?php esc_html_e( 'Container Width', 'wp-span-checker' ); ?></label></th>
							<td>
								<input type="range" id="container_width" name="container_width" value="<?php echo esc_attr( $settings['container_width'] ); ?>" min="400" max="800" step="20" class="wsc-range-input">
								<span class="wsc-range-value"><?php echo esc_html( $settings['container_width'] ); ?>px</span>
							</td>
						</tr>
						<tr>
							<th><label for="border_radius"><?php esc_html_e( 'Border Radius', 'wp-span-checker' ); ?></label></th>
							<td>
								<input type="range" id="border_radius" name="border_radius" value="<?php echo esc_attr( $settings['border_radius'] ); ?>" min="0" max="20" class="wsc-range-input">
								<span class="wsc-range-value"><?php echo esc_html( $settings['border_radius'] ); ?>px</span>
							</td>
						</tr>
						<tr>
							<th><label for="content_padding"><?php esc_html_e( 'Content Padding', 'wp-span-checker' ); ?></label></th>
							<td>
								<input type="range" id="content_padding" name="content_padding" value="<?php echo esc_attr( $settings['content_padding'] ); ?>" min="20" max="60" step="5" class="wsc-range-input">
								<span class="wsc-range-value"><?php echo esc_html( $settings['content_padding'] ); ?>px</span>
							</td>
						</tr>
					</table>
				</div>

				<div class="wsc-email-card" style="margin-top: 20px;">
					<h2><?php esc_html_e( 'Color Settings', 'wp-span-checker' ); ?></h2>
					<div class="wsc-color-grid">
						<div class="wsc-color-group">
							<h4><?php esc_html_e( 'Header', 'wp-span-checker' ); ?></h4>
							<div class="wsc-color-row">
								<label><?php esc_html_e( 'Background', 'wp-span-checker' ); ?></label>
								<input type="color" id="header_bg_color" name="header_bg_color" value="<?php echo esc_attr( $settings['header_bg_color'] ); ?>" class="wsc-color-picker">
							</div>
							<div class="wsc-color-row">
								<label><?php esc_html_e( 'Text', 'wp-span-checker' ); ?></label>
								<input type="color" id="header_text_color" name="header_text_color" value="<?php echo esc_attr( $settings['header_text_color'] ); ?>" class="wsc-color-picker">
							</div>
						</div>
						<div class="wsc-color-group">
							<h4><?php esc_html_e( 'Body', 'wp-span-checker' ); ?></h4>
							<div class="wsc-color-row">
								<label><?php esc_html_e( 'Page BG', 'wp-span-checker' ); ?></label>
								<input type="color" id="body_bg_color" name="body_bg_color" value="<?php echo esc_attr( $settings['body_bg_color'] ); ?>" class="wsc-color-picker">
							</div>
							<div class="wsc-color-row">
								<label><?php esc_html_e( 'Content BG', 'wp-span-checker' ); ?></label>
								<input type="color" id="content_bg_color" name="content_bg_color" value="<?php echo esc_attr( $settings['content_bg_color'] ); ?>" class="wsc-color-picker">
							</div>
							<div class="wsc-color-row">
								<label><?php esc_html_e( 'Text', 'wp-span-checker' ); ?></label>
								<input type="color" id="text_color" name="text_color" value="<?php echo esc_attr( $settings['text_color'] ); ?>" class="wsc-color-picker">
							</div>
							<div class="wsc-color-row">
								<label><?php esc_html_e( 'Headings', 'wp-span-checker' ); ?></label>
								<input type="color" id="heading_color" name="heading_color" value="<?php echo esc_attr( $settings['heading_color'] ); ?>" class="wsc-color-picker">
							</div>
							<div class="wsc-color-row">
								<label><?php esc_html_e( 'Links', 'wp-span-checker' ); ?></label>
								<input type="color" id="link_color" name="link_color" value="<?php echo esc_attr( $settings['link_color'] ); ?>" class="wsc-color-picker">
							</div>
							<div class="wsc-color-row">
								<label><?php esc_html_e( 'Borders', 'wp-span-checker' ); ?></label>
								<input type="color" id="border_color" name="border_color" value="<?php echo esc_attr( $settings['border_color'] ); ?>" class="wsc-color-picker">
							</div>
						</div>
						<div class="wsc-color-group">
							<h4><?php esc_html_e( 'Button', 'wp-span-checker' ); ?></h4>
							<div class="wsc-color-row">
								<label><?php esc_html_e( 'Background', 'wp-span-checker' ); ?></label>
								<input type="color" id="button_bg_color" name="button_bg_color" value="<?php echo esc_attr( $settings['button_bg_color'] ); ?>" class="wsc-color-picker">
							</div>
							<div class="wsc-color-row">
								<label><?php esc_html_e( 'Text', 'wp-span-checker' ); ?></label>
								<input type="color" id="button_text_color" name="button_text_color" value="<?php echo esc_attr( $settings['button_text_color'] ); ?>" class="wsc-color-picker">
							</div>
							<div class="wsc-color-row">
								<label><?php esc_html_e( 'Hover/Gradient', 'wp-span-checker' ); ?></label>
								<input type="color" id="button_hover_color" name="button_hover_color" value="<?php echo esc_attr( $settings['button_hover_color'] ); ?>" class="wsc-color-picker">
							</div>
						</div>
						<div class="wsc-color-group">
							<h4><?php esc_html_e( 'Footer', 'wp-span-checker' ); ?></h4>
							<div class="wsc-color-row">
								<label><?php esc_html_e( 'Background', 'wp-span-checker' ); ?></label>
								<input type="color" id="footer_bg_color" name="footer_bg_color" value="<?php echo esc_attr( $settings['footer_bg_color'] ); ?>" class="wsc-color-picker">
							</div>
							<div class="wsc-color-row">
								<label><?php esc_html_e( 'Text', 'wp-span-checker' ); ?></label>
								<input type="color" id="footer_text_color" name="footer_text_color" value="<?php echo esc_attr( $settings['footer_text_color'] ); ?>" class="wsc-color-picker">
							</div>
						</div>
						<div class="wsc-color-group">
							<h4><?php esc_html_e( 'Special', 'wp-span-checker' ); ?></h4>
							<div class="wsc-color-row">
								<label><?php esc_html_e( 'OTP BG', 'wp-span-checker' ); ?></label>
								<input type="color" id="otp_bg_color" name="otp_bg_color" value="<?php echo esc_attr( $settings['otp_bg_color'] ); ?>" class="wsc-color-picker">
							</div>
							<div class="wsc-color-row">
								<label><?php esc_html_e( 'OTP Code', 'wp-span-checker' ); ?></label>
								<input type="color" id="otp_text_color" name="otp_text_color" value="<?php echo esc_attr( $settings['otp_text_color'] ); ?>" class="wsc-color-picker">
							</div>
							<div class="wsc-color-row">
								<label><?php esc_html_e( 'Warning BG', 'wp-span-checker' ); ?></label>
								<input type="color" id="warning_bg_color" name="warning_bg_color" value="<?php echo esc_attr( $settings['warning_bg_color'] ); ?>" class="wsc-color-picker">
							</div>
							<div class="wsc-color-row">
								<label><?php esc_html_e( 'Warning Text', 'wp-span-checker' ); ?></label>
								<input type="color" id="warning_text_color" name="warning_text_color" value="<?php echo esc_attr( $settings['warning_text_color'] ); ?>" class="wsc-color-picker">
							</div>
							<div class="wsc-color-row">
								<label><?php esc_html_e( 'Info BG', 'wp-span-checker' ); ?></label>
								<input type="color" id="info_bg_color" name="info_bg_color" value="<?php echo esc_attr( $settings['info_bg_color'] ); ?>" class="wsc-color-picker">
							</div>
							<div class="wsc-color-row">
								<label><?php esc_html_e( 'Info Text', 'wp-span-checker' ); ?></label>
								<input type="color" id="info_text_color" name="info_text_color" value="<?php echo esc_attr( $settings['info_text_color'] ); ?>" class="wsc-color-picker">
							</div>
						</div>
					</div>
				</div>

				<div class="wsc-email-card" style="margin-top: 20px;">
					<h2><?php esc_html_e( 'Preview Email Type', 'wp-span-checker' ); ?></h2>
					<select id="preview-email-type" class="widefat">
						<?php foreach ( $email_types as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<button type="button" class="button" id="wsc-refresh-preview" style="margin-top: 10px;">
						<span class="dashicons dashicons-update" style="margin-top: 4px;"></span>
						<?php esc_html_e( 'Refresh Preview', 'wp-span-checker' ); ?>
					</button>
				</div>
			</div>

			<div class="wsc-email-preview-panel">
				<div class="wsc-email-card">
					<h2><?php esc_html_e( 'Live Preview', 'wp-span-checker' ); ?></h2>
					<div class="wsc-email-preview-frame" id="wsc-email-preview">
						<div class="wsc-preview-loading">
							<span class="spinner is-active"></span>
							<?php esc_html_e( 'Loading preview...', 'wp-span-checker' ); ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Branding Tab -->
	<div class="wsc-email-panel" id="panel-branding">
		<div class="wsc-email-card">
			<h2><?php esc_html_e( 'Logo & Company', 'wp-span-checker' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Show Logo', 'wp-span-checker' ); ?></th>
					<td><?php wp_span_checker_admin_switch( array( 'name' => 'show_logo', 'checked' => $settings['show_logo'] ) ); ?></td>
				</tr>
				<tr>
					<th><label for="logo_url"><?php esc_html_e( 'Logo URL', 'wp-span-checker' ); ?></label></th>
					<td>
						<input type="url" id="logo_url" name="logo_url" value="<?php echo esc_url( $settings['logo_url'] ); ?>" class="regular-text">
						<button type="button" class="button" id="wsc-upload-logo"><?php esc_html_e( 'Upload', 'wp-span-checker' ); ?></button>
						<p class="description"><?php esc_html_e( 'Leave empty to use site custom logo.', 'wp-span-checker' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="logo_width"><?php esc_html_e( 'Logo Width', 'wp-span-checker' ); ?></label></th>
					<td>
						<input type="number" id="logo_width" name="logo_width" value="<?php echo esc_attr( $settings['logo_width'] ); ?>" min="50" max="300" class="small-text"> px
					</td>
				</tr>
				<tr>
					<th><label for="company_name"><?php esc_html_e( 'Company Name', 'wp-span-checker' ); ?></label></th>
					<td>
						<input type="text" id="company_name" name="company_name" value="<?php echo esc_attr( $settings['company_name'] ); ?>" class="regular-text" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
					</td>
				</tr>
				<tr>
					<th><label for="company_address"><?php esc_html_e( 'Company Address', 'wp-span-checker' ); ?></label></th>
					<td>
						<input type="text" id="company_address" name="company_address" value="<?php echo esc_attr( $settings['company_address'] ); ?>" class="large-text" placeholder="123 Main St, City, Country">
					</td>
				</tr>
			</table>
		</div>

		<div class="wsc-email-card" style="margin-top: 20px;">
			<h2><?php esc_html_e( 'Social Links', 'wp-span-checker' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Show Social Links', 'wp-span-checker' ); ?></th>
					<td><?php wp_span_checker_admin_switch( array( 'name' => 'show_social_links', 'checked' => $settings['show_social_links'] ) ); ?></td>
				</tr>
				<tr>
					<th><label for="facebook_url"><?php esc_html_e( 'Facebook URL', 'wp-span-checker' ); ?></label></th>
					<td><input type="url" id="facebook_url" name="facebook_url" value="<?php echo esc_url( $settings['facebook_url'] ); ?>" class="regular-text" placeholder="https://facebook.com/..."></td>
				</tr>
				<tr>
					<th><label for="twitter_url"><?php esc_html_e( 'Twitter/X URL', 'wp-span-checker' ); ?></label></th>
					<td><input type="url" id="twitter_url" name="twitter_url" value="<?php echo esc_url( $settings['twitter_url'] ); ?>" class="regular-text" placeholder="https://twitter.com/..."></td>
				</tr>
				<tr>
					<th><label for="instagram_url"><?php esc_html_e( 'Instagram URL', 'wp-span-checker' ); ?></label></th>
					<td><input type="url" id="instagram_url" name="instagram_url" value="<?php echo esc_url( $settings['instagram_url'] ); ?>" class="regular-text" placeholder="https://instagram.com/..."></td>
				</tr>
				<tr>
					<th><label for="linkedin_url"><?php esc_html_e( 'LinkedIn URL', 'wp-span-checker' ); ?></label></th>
					<td><input type="url" id="linkedin_url" name="linkedin_url" value="<?php echo esc_url( $settings['linkedin_url'] ); ?>" class="regular-text" placeholder="https://linkedin.com/..."></td>
				</tr>
			</table>
		</div>
	</div>

	<!-- Send Test Tab -->
	<div class="wsc-email-panel" id="panel-test">
		<div class="wsc-email-card">
			<h2><?php esc_html_e( 'Send Test Email', 'wp-span-checker' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Send a test email to verify your template looks correct in email clients.', 'wp-span-checker' ); ?></p>

			<table class="form-table">
				<tr>
					<th><label for="test_email_type"><?php esc_html_e( 'Email Type', 'wp-span-checker' ); ?></label></th>
					<td>
						<select id="test_email_type" class="regular-text">
							<?php foreach ( $email_types as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="test_email_address"><?php esc_html_e( 'Recipient Email', 'wp-span-checker' ); ?></label></th>
					<td>
						<input type="email" id="test_email_address" class="regular-text" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
					</td>
				</tr>
				<tr>
					<th></th>
					<td>
						<button type="button" class="button button-primary" id="wsc-send-test-email">
							<span class="dashicons dashicons-email-alt" style="margin-top: 4px;"></span>
							<?php esc_html_e( 'Send Test Email', 'wp-span-checker' ); ?>
						</button>
						<span id="test-email-result" style="margin-left: 10px;"></span>
					</td>
				</tr>
			</table>
		</div>

		<div class="wsc-email-card" style="margin-top: 20px;">
			<h2><?php esc_html_e( 'Email Types Reference', 'wp-span-checker' ); ?></h2>
			<table class="wp-list-table widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Type', 'wp-span-checker' ); ?></th>
						<th><?php esc_html_e( 'Description', 'wp-span-checker' ); ?></th>
						<th><?php esc_html_e( 'When Sent', 'wp-span-checker' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><strong><?php esc_html_e( 'Email Verification', 'wp-span-checker' ); ?></strong></td>
						<td><?php esc_html_e( 'Activation link + 6-digit OTP code', 'wp-span-checker' ); ?></td>
						<td><?php esc_html_e( 'During registration - user can verify via link or OTP', 'wp-span-checker' ); ?></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Password Reset', 'wp-span-checker' ); ?></strong></td>
						<td><?php esc_html_e( 'Password reset link', 'wp-span-checker' ); ?></td>
						<td><?php esc_html_e( 'When user requests password reset', 'wp-span-checker' ); ?></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Welcome', 'wp-span-checker' ); ?></strong></td>
						<td><?php esc_html_e( 'Welcome message with login link', 'wp-span-checker' ); ?></td>
						<td><?php esc_html_e( 'After successful account activation', 'wp-span-checker' ); ?></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Account Blocked', 'wp-span-checker' ); ?></strong></td>
						<td><?php esc_html_e( 'Notification when account is blocked', 'wp-span-checker' ); ?></td>
						<td><?php esc_html_e( 'When user exceeds strike limit', 'wp-span-checker' ); ?></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Login Alert', 'wp-span-checker' ); ?></strong></td>
						<td><?php esc_html_e( 'New login notification with details', 'wp-span-checker' ); ?></td>
						<td><?php esc_html_e( 'When user logs in from new location', 'wp-span-checker' ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>

	<div class="wsc-email-save-bar">
		<button type="button" class="button button-primary button-large" id="wsc-save-email-settings">
			<?php esc_html_e( 'Save Settings', 'wp-span-checker' ); ?>
		</button>
		<span class="wsc-save-status"></span>
	</div>
</div>

<style>
.wsc-email-templates-admin { max-width: 1600px; }
.wsc-email-admin-tabs { display: flex; gap: 0; border-bottom: 1px solid #c3c4c7; margin-bottom: 20px; }
.wsc-email-tab { background: none; border: none; padding: 12px 20px; font-size: 14px; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -1px; }
.wsc-email-tab:hover { background: #f0f0f1; }
.wsc-email-tab.active { border-bottom-color: #2271b1; color: #2271b1; font-weight: 500; }
.wsc-email-panel { display: none; }
.wsc-email-panel.active { display: block; }
.wsc-email-card { background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 20px; }
.wsc-email-card h2 { margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #f0f0f1; }
.wsc-email-design-wrap { display: grid; grid-template-columns: 450px 1fr; gap: 20px; }
.wsc-email-preview-frame { background: #e5e7eb; border-radius: 4px; min-height: 600px; overflow: auto; }
.wsc-email-preview-frame iframe { width: 100%; height: 700px; border: none; }
.wsc-preview-loading { display: flex; align-items: center; justify-content: center; height: 300px; color: #6b7280; }
.wsc-color-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 15px; margin-top: 15px; }
.wsc-color-group { background: #f9fafb; border-radius: 6px; padding: 12px; }
.wsc-color-group h4 { margin: 0 0 10px; font-size: 13px; color: #374151; font-weight: 600; }
.wsc-color-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
.wsc-color-row:last-child { margin-bottom: 0; }
.wsc-color-row label { font-size: 12px; color: #6b7280; }
.wsc-email-colors-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; }
.wsc-color-picker { width: 50px; height: 32px; padding: 2px; cursor: pointer; border: 1px solid #c3c4c7; border-radius: 4px; }
.wsc-range-input { width: 180px; vertical-align: middle; }
.wsc-range-value { margin-left: 10px; font-weight: 500; display: inline-block; min-width: 50px; }
.wsc-email-save-bar { position: sticky; bottom: 0; background: #fff; padding: 15px 20px; border-top: 1px solid #c3c4c7; margin: 20px -20px -20px; display: flex; align-items: center; gap: 15px; }
.wsc-save-status { color: #00a32a; font-weight: 500; }
@media (max-width: 1200px) {
	.wsc-email-design-wrap { grid-template-columns: 1fr; }
	.wsc-email-preview-panel { order: -1; }
}
</style>

<script>
jQuery(document).ready(function($) {
	// Tab switching
	$('.wsc-email-tab').on('click', function() {
		var tab = $(this).data('tab');
		$('.wsc-email-tab').removeClass('active');
		$(this).addClass('active');
		$('.wsc-email-panel').removeClass('active');
		$('#panel-' + tab).addClass('active');
	});

	// Range input display
	$('.wsc-range-input').on('input', function() {
		$(this).siblings('.wsc-range-value').text($(this).val() + 'px');
	});

	// Color picker & range change - update preview
	$('.wsc-color-picker, .wsc-range-input, input[name="show_logo"], input[name="show_social_links"]').on('change input', function() {
		updatePreview();
	});

	// Email type change
	$('#preview-email-type').on('change', function() {
		updatePreview();
	});

	// Refresh preview button
	$('#wsc-refresh-preview').on('click', function() {
		updatePreview();
	});

	// Update preview
	function updatePreview() {
		var $preview = $('#wsc-email-preview');
		$preview.html('<div class="wsc-preview-loading"><span class="spinner is-active"></span> <?php esc_html_e( 'Loading preview...', 'wp-span-checker' ); ?></div>');

		var data = {
			action: 'wsc_preview_email_template',
			nonce: WPSpanChecker.nonce,
			email_type: $('#preview-email-type').val(),
			// Layout
			container_width: $('#container_width').val(),
			border_radius: $('#border_radius').val(),
			content_padding: $('#content_padding').val(),
			// Colors
			header_bg_color: $('#header_bg_color').val(),
			header_text_color: $('#header_text_color').val(),
			body_bg_color: $('#body_bg_color').val(),
			content_bg_color: $('#content_bg_color').val(),
			text_color: $('#text_color').val(),
			heading_color: $('#heading_color').val(),
			link_color: $('#link_color').val(),
			button_bg_color: $('#button_bg_color').val(),
			button_text_color: $('#button_text_color').val(),
			button_hover_color: $('#button_hover_color').val(),
			footer_bg_color: $('#footer_bg_color').val(),
			footer_text_color: $('#footer_text_color').val(),
			border_color: $('#border_color').val(),
			otp_bg_color: $('#otp_bg_color').val(),
			otp_text_color: $('#otp_text_color').val(),
			warning_bg_color: $('#warning_bg_color').val(),
			warning_text_color: $('#warning_text_color').val(),
			info_bg_color: $('#info_bg_color').val(),
			info_text_color: $('#info_text_color').val(),
			// Branding
			show_logo: $('input[name="show_logo"]').is(':checked') ? 1 : 0,
			logo_url: $('#logo_url').val(),
			logo_width: $('#logo_width').val(),
			company_name: $('#company_name').val(),
			company_address: $('#company_address').val(),
			show_social_links: $('input[name="show_social_links"]').is(':checked') ? 1 : 0,
			facebook_url: $('#facebook_url').val(),
			twitter_url: $('#twitter_url').val(),
			instagram_url: $('#instagram_url').val(),
			linkedin_url: $('#linkedin_url').val()
		};

		$.post(ajaxurl, data, function(response) {
			if (response.success) {
				var iframe = document.createElement('iframe');
				iframe.srcdoc = response.data.html;
				$preview.html(iframe);
			} else {
				$preview.html('<div class="wsc-preview-loading" style="color: #d63638;"><?php esc_html_e( 'Failed to load preview', 'wp-span-checker' ); ?></div>');
			}
		});
	}

	// Save settings
	$('#wsc-save-email-settings').on('click', function() {
		var $btn = $(this);
		var $status = $('.wsc-save-status');
		$btn.prop('disabled', true);
		$status.text('<?php esc_html_e( 'Saving...', 'wp-span-checker' ); ?>');

		var data = {
			action: 'wsc_save_email_template_settings',
			nonce: WPSpanChecker.nonce,
			container_width: $('#container_width').val(),
			border_radius: $('#border_radius').val(),
			content_padding: $('#content_padding').val(),
			header_bg_color: $('#header_bg_color').val(),
			header_text_color: $('#header_text_color').val(),
			body_bg_color: $('#body_bg_color').val(),
			content_bg_color: $('#content_bg_color').val(),
			text_color: $('#text_color').val(),
			heading_color: $('#heading_color').val(),
			link_color: $('#link_color').val(),
			button_bg_color: $('#button_bg_color').val(),
			button_text_color: $('#button_text_color').val(),
			button_hover_color: $('#button_hover_color').val(),
			footer_bg_color: $('#footer_bg_color').val(),
			footer_text_color: $('#footer_text_color').val(),
			border_color: $('#border_color').val(),
			otp_bg_color: $('#otp_bg_color').val(),
			otp_text_color: $('#otp_text_color').val(),
			warning_bg_color: $('#warning_bg_color').val(),
			warning_text_color: $('#warning_text_color').val(),
			info_bg_color: $('#info_bg_color').val(),
			info_text_color: $('#info_text_color').val(),
			show_logo: $('input[name="show_logo"]').is(':checked') ? 1 : 0,
			logo_url: $('#logo_url').val(),
			logo_width: $('#logo_width').val(),
			company_name: $('#company_name').val(),
			company_address: $('#company_address').val(),
			show_social_links: $('input[name="show_social_links"]').is(':checked') ? 1 : 0,
			facebook_url: $('#facebook_url').val(),
			twitter_url: $('#twitter_url').val(),
			instagram_url: $('#instagram_url').val(),
			linkedin_url: $('#linkedin_url').val()
		};

		$.post(ajaxurl, data, function(response) {
			$btn.prop('disabled', false);
			if (response.success) {
				$status.text('<?php esc_html_e( 'Saved!', 'wp-span-checker' ); ?>');
				setTimeout(function() { $status.text(''); }, 3000);
			} else {
				$status.css('color', '#d63638').text(response.data.message);
			}
		});
	});

	// Send test email
	$('#wsc-send-test-email').on('click', function() {
		var $btn = $(this);
		var $result = $('#test-email-result');
		var email = $('#test_email_address').val();
		var type = $('#test_email_type').val();

		if (!email) {
			$result.css('color', '#d63638').text('<?php esc_html_e( 'Please enter an email address.', 'wp-span-checker' ); ?>');
			return;
		}

		$btn.prop('disabled', true);
		$result.css('color', '#6b7280').text('<?php esc_html_e( 'Sending...', 'wp-span-checker' ); ?>');

		$.post(ajaxurl, {
			action: 'wsc_send_test_email',
			nonce: WPSpanChecker.nonce,
			test_email: email,
			email_type: type
		}, function(response) {
			$btn.prop('disabled', false);
			if (response.success) {
				$result.css('color', '#00a32a').text(response.data.message);
			} else {
				$result.css('color', '#d63638').text(response.data.message);
			}
		});
	});

	// Media uploader for logo
	$('#wsc-upload-logo').on('click', function(e) {
		e.preventDefault();
		var mediaUploader = wp.media({
			title: '<?php esc_html_e( 'Select Logo', 'wp-span-checker' ); ?>',
			button: { text: '<?php esc_html_e( 'Use this image', 'wp-span-checker' ); ?>' },
			multiple: false
		});
		mediaUploader.on('select', function() {
			var attachment = mediaUploader.state().get('selection').first().toJSON();
			$('#logo_url').val(attachment.url);
			updatePreview();
		});
		mediaUploader.open();
	});

	// Initial preview load
	updatePreview();
});
</script>
