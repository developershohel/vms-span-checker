<?php
/**
 * Auth Forms admin template.
 *
 * Variables below are received from the including admin handler scope.
 *
 * @package VMS_Span_Checker
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings      = \VMS_Span_Checker\Auth_Forms::get_settings();
$smtp_settings = \VMS_Span_Checker\Auth_Forms::get_smtp_settings();
$form_types    = \VMS_Span_Checker\Auth_Forms::get_form_types();
$ai_cfg        = \VMS_Span_Checker\AI_Span_Config::get();
$has_recaptcha = ! empty( $ai_cfg['recaptcha_site_key'] ) && ! empty( $ai_cfg['recaptcha_secret_key'] );

// Get all pages for dropdowns
$pages = get_pages( array( 'post_status' => 'publish' ) );
?>
<div class="wrap wsc-auth-forms-admin">
	<h1><?php esc_html_e( 'Auth Form Templates', 'vms-span-checker' ); ?></h1>

	<div class="wsc-auth-admin-tabs">
		<button class="wsc-auth-tab active" data-tab="templates"><?php esc_html_e( 'Form Templates', 'vms-span-checker' ); ?></button>
		<button class="wsc-auth-tab" data-tab="design"><?php esc_html_e( 'Design & Preview', 'vms-span-checker' ); ?></button>
		<button class="wsc-auth-tab" data-tab="validation"><?php esc_html_e( 'Validation Rules', 'vms-span-checker' ); ?></button>
		<button class="wsc-auth-tab" data-tab="smtp"><?php esc_html_e( 'SMTP Settings', 'vms-span-checker' ); ?></button>
		<button class="wsc-auth-tab" data-tab="guide"><?php esc_html_e( 'How to Use', 'vms-span-checker' ); ?></button>
	</div>

	<!-- Templates Tab -->
	<div class="wsc-auth-panel active" id="panel-templates">
		<div class="wsc-auth-card">
			<h2><?php esc_html_e( 'Available Form Templates', 'vms-span-checker' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Use these shortcodes to add authentication forms to any page. Each form includes built-in validation and spam protection.', 'vms-span-checker' ); ?></p>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Form Type', 'vms-span-checker' ); ?></th>
						<th><?php esc_html_e( 'Shortcode', 'vms-span-checker' ); ?></th>
						<th><?php esc_html_e( 'Assigned Page', 'vms-span-checker' ); ?></th>
						<th><?php esc_html_e( 'Status', 'vms-span-checker' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><strong><?php esc_html_e( 'Login Form', 'vms-span-checker' ); ?></strong></td>
						<td><code>[wsc_login_form]</code> <button type="button" class="button button-small wsc-copy-btn" data-copy="[wsc_login_form]"><?php esc_html_e( 'Copy', 'vms-span-checker' ); ?></button></td>
						<td>
							<select name="login_page_id" class="wsc-page-select" data-setting="login_page_id">
								<option value=""><?php esc_html_e( '— Select Page —', 'vms-span-checker' ); ?></option>
								<?php foreach ( $pages as $page ) : ?>
									<option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( $settings['login_page_id'], $page->ID ); ?>>
										<?php echo esc_html( $page->post_title ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
						<td>
							<?php if ( $settings['login_page_id'] ) : ?>
								<span class="wsc-status wsc-status--active"><?php esc_html_e( 'Active', 'vms-span-checker' ); ?></span>
								<a href="<?php echo esc_url( get_permalink( $settings['login_page_id'] ) ); ?>" target="_blank"><?php esc_html_e( 'View', 'vms-span-checker' ); ?></a>
							<?php else : ?>
								<span class="wsc-status wsc-status--inactive"><?php esc_html_e( 'Not Assigned', 'vms-span-checker' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Registration Form', 'vms-span-checker' ); ?></strong></td>
						<td><code>[wsc_register_form]</code> <button type="button" class="button button-small wsc-copy-btn" data-copy="[wsc_register_form]"><?php esc_html_e( 'Copy', 'vms-span-checker' ); ?></button></td>
						<td>
							<select name="register_page_id" class="wsc-page-select" data-setting="register_page_id">
								<option value=""><?php esc_html_e( '— Select Page —', 'vms-span-checker' ); ?></option>
								<?php foreach ( $pages as $page ) : ?>
									<option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( $settings['register_page_id'], $page->ID ); ?>>
										<?php echo esc_html( $page->post_title ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
						<td>
							<?php if ( $settings['register_page_id'] ) : ?>
								<span class="wsc-status wsc-status--active"><?php esc_html_e( 'Active', 'vms-span-checker' ); ?></span>
								<a href="<?php echo esc_url( get_permalink( $settings['register_page_id'] ) ); ?>" target="_blank"><?php esc_html_e( 'View', 'vms-span-checker' ); ?></a>
							<?php else : ?>
								<span class="wsc-status wsc-status--inactive"><?php esc_html_e( 'Not Assigned', 'vms-span-checker' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Forgot Password Form', 'vms-span-checker' ); ?></strong></td>
						<td><code>[wsc_forgot_password_form]</code> <button type="button" class="button button-small wsc-copy-btn" data-copy="[wsc_forgot_password_form]"><?php esc_html_e( 'Copy', 'vms-span-checker' ); ?></button></td>
						<td>
							<select name="forgot_page_id" class="wsc-page-select" data-setting="forgot_page_id">
								<option value=""><?php esc_html_e( '— Select Page —', 'vms-span-checker' ); ?></option>
								<?php foreach ( $pages as $page ) : ?>
									<option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( $settings['forgot_page_id'], $page->ID ); ?>>
										<?php echo esc_html( $page->post_title ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
						<td>
							<?php if ( $settings['forgot_page_id'] ) : ?>
								<span class="wsc-status wsc-status--active"><?php esc_html_e( 'Active', 'vms-span-checker' ); ?></span>
								<a href="<?php echo esc_url( get_permalink( $settings['forgot_page_id'] ) ); ?>" target="_blank"><?php esc_html_e( 'View', 'vms-span-checker' ); ?></a>
							<?php else : ?>
								<span class="wsc-status wsc-status--inactive"><?php esc_html_e( 'Not Assigned', 'vms-span-checker' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Reset Password Form', 'vms-span-checker' ); ?></strong></td>
						<td><code>[wsc_reset_password_form]</code> <button type="button" class="button button-small wsc-copy-btn" data-copy="[wsc_reset_password_form]"><?php esc_html_e( 'Copy', 'vms-span-checker' ); ?></button></td>
						<td>
							<select name="reset_page_id" class="wsc-page-select" data-setting="reset_page_id">
								<option value=""><?php esc_html_e( '— Select Page —', 'vms-span-checker' ); ?></option>
								<?php foreach ( $pages as $page ) : ?>
									<option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( $settings['reset_page_id'], $page->ID ); ?>>
										<?php echo esc_html( $page->post_title ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
						<td>
							<?php if ( $settings['reset_page_id'] ) : ?>
								<span class="wsc-status wsc-status--active"><?php esc_html_e( 'Active', 'vms-span-checker' ); ?></span>
								<a href="<?php echo esc_url( get_permalink( $settings['reset_page_id'] ) ); ?>" target="_blank"><?php esc_html_e( 'View', 'vms-span-checker' ); ?></a>
							<?php else : ?>
								<span class="wsc-status wsc-status--inactive"><?php esc_html_e( 'Not Assigned', 'vms-span-checker' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Email Verify Form', 'vms-span-checker' ); ?></strong><br><small style="color:#6b7280;"><?php esc_html_e( 'OTP + Activation Link', 'vms-span-checker' ); ?></small></td>
						<td><code>[wsc_verify_form]</code> <button type="button" class="button button-small wsc-copy-btn" data-copy="[wsc_verify_form]"><?php esc_html_e( 'Copy', 'vms-span-checker' ); ?></button></td>
						<td>
							<select name="verify_page_id" class="wsc-page-select" data-setting="verify_page_id">
								<option value=""><?php esc_html_e( '— Select Page —', 'vms-span-checker' ); ?></option>
								<?php foreach ( $pages as $page ) : ?>
									<option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( $settings['verify_page_id'] ?? 0, $page->ID ); ?>>
										<?php echo esc_html( $page->post_title ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
						<td>
							<?php if ( ! empty( $settings['verify_page_id'] ) ) : ?>
								<span class="wsc-status wsc-status--active"><?php esc_html_e( 'Active', 'vms-span-checker' ); ?></span>
								<a href="<?php echo esc_url( get_permalink( $settings['verify_page_id'] ) ); ?>" target="_blank"><?php esc_html_e( 'View', 'vms-span-checker' ); ?></a>
							<?php else : ?>
								<span class="wsc-status wsc-status--inactive"><?php esc_html_e( 'Not Assigned', 'vms-span-checker' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
				</tbody>
			</table>

			<div class="wsc-auth-actions" style="margin-top: 20px;">
				<button type="button" class="button button-primary" id="wsc-generate-pages">
					<span class="dashicons dashicons-admin-page" style="margin-top: 4px;"></span>
					<?php esc_html_e( 'Auto-Generate All Auth Pages', 'vms-span-checker' ); ?>
				</button>
				<p class="description"><?php esc_html_e( 'Creates Login, Register, Forgot Password, Reset Password, and Email Verify pages automatically with the correct shortcodes.', 'vms-span-checker' ); ?></p>
			</div>
		</div>

		<div class="wsc-auth-card" style="margin-top: 20px;">
			<h2><?php esc_html_e( 'Redirect Settings', 'vms-span-checker' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><label for="login_redirect"><?php esc_html_e( 'After Login Redirect', 'vms-span-checker' ); ?></label></th>
					<td>
						<input type="url" id="login_redirect" name="login_redirect" value="<?php echo esc_url( $settings['login_redirect'] ); ?>" class="regular-text" placeholder="<?php echo esc_url( home_url() ); ?>">
						<p class="description"><?php esc_html_e( 'URL to redirect after successful login. Leave empty for home page.', 'vms-span-checker' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="register_redirect"><?php esc_html_e( 'After Registration Redirect', 'vms-span-checker' ); ?></label></th>
					<td>
						<input type="url" id="register_redirect" name="register_redirect" value="<?php echo esc_url( $settings['register_redirect'] ); ?>" class="regular-text" placeholder="<?php echo esc_url( home_url() ); ?>">
						<p class="description"><?php esc_html_e( 'URL to redirect after successful registration. Leave empty for home page.', 'vms-span-checker' ); ?></p>
					</td>
				</tr>
			</table>
		</div>
	</div>

	<!-- Design Tab -->
	<div class="wsc-auth-panel" id="panel-design">
		<div class="wsc-auth-design-wrap">
			<div class="wsc-auth-design-controls">
				<div class="wsc-auth-card">
					<h2><?php esc_html_e( 'Form Design', 'vms-span-checker' ); ?></h2>

					<table class="form-table">
						<tr>
							<th><label for="primary_color"><?php esc_html_e( 'Primary Color', 'vms-span-checker' ); ?></label></th>
							<td><input type="color" id="primary_color" name="primary_color" value="<?php echo esc_attr( $settings['primary_color'] ); ?>" class="wsc-color-picker"></td>
						</tr>
						<tr>
							<th><label for="secondary_color"><?php esc_html_e( 'Secondary Color', 'vms-span-checker' ); ?></label></th>
							<td><input type="color" id="secondary_color" name="secondary_color" value="<?php echo esc_attr( $settings['secondary_color'] ); ?>" class="wsc-color-picker"></td>
						</tr>
						<tr>
							<th><label for="text_color"><?php esc_html_e( 'Text Color', 'vms-span-checker' ); ?></label></th>
							<td><input type="color" id="text_color" name="text_color" value="<?php echo esc_attr( $settings['text_color'] ); ?>" class="wsc-color-picker"></td>
						</tr>
						<tr>
							<th><label for="background_color"><?php esc_html_e( 'Background Color', 'vms-span-checker' ); ?></label></th>
							<td><input type="color" id="background_color" name="background_color" value="<?php echo esc_attr( $settings['background_color'] ); ?>" class="wsc-color-picker"></td>
						</tr>
					</table>
				</div>

				<div class="wsc-auth-card" style="margin-top: 20px;">
					<h2><?php esc_html_e( 'Input Field Colors', 'vms-span-checker' ); ?></h2>
					<table class="form-table">
						<tr>
							<th><label for="border_color"><?php esc_html_e( 'Border Color', 'vms-span-checker' ); ?></label></th>
							<td><input type="color" id="border_color" name="border_color" value="<?php echo esc_attr( $settings['border_color'] ?? '#d1d5db' ); ?>" class="wsc-color-picker"></td>
						</tr>
						<tr>
							<th><label for="border_hover_color"><?php esc_html_e( 'Border Hover Color', 'vms-span-checker' ); ?></label></th>
							<td><input type="color" id="border_hover_color" name="border_hover_color" value="<?php echo esc_attr( $settings['border_hover_color'] ?? '#9ca3af' ); ?>" class="wsc-color-picker"></td>
						</tr>
						<tr>
							<th><label for="border_focus_color"><?php esc_html_e( 'Border Focus Color', 'vms-span-checker' ); ?></label></th>
							<td><input type="color" id="border_focus_color" name="border_focus_color" value="<?php echo esc_attr( $settings['border_focus_color'] ?? '#2563eb' ); ?>" class="wsc-color-picker"></td>
						</tr>
						<tr>
							<th><label for="input_bg_color"><?php esc_html_e( 'Input Background', 'vms-span-checker' ); ?></label></th>
							<td><input type="color" id="input_bg_color" name="input_bg_color" value="<?php echo esc_attr( $settings['input_bg_color'] ?? '#ffffff' ); ?>" class="wsc-color-picker"></td>
						</tr>
						<tr>
							<th><label for="input_focus_bg"><?php esc_html_e( 'Input Focus Background', 'vms-span-checker' ); ?></label></th>
							<td><input type="color" id="input_focus_bg" name="input_focus_bg" value="<?php echo esc_attr( $settings['input_focus_bg'] ?? '#f9fafb' ); ?>" class="wsc-color-picker"></td>
						</tr>
						<tr>
							<th><label for="error_color"><?php esc_html_e( 'Error Color', 'vms-span-checker' ); ?></label></th>
							<td><input type="color" id="error_color" name="error_color" value="<?php echo esc_attr( $settings['error_color'] ?? '#dc2626' ); ?>" class="wsc-color-picker"></td>
						</tr>
						<tr>
							<th><label for="success_color"><?php esc_html_e( 'Success Color', 'vms-span-checker' ); ?></label></th>
							<td><input type="color" id="success_color" name="success_color" value="<?php echo esc_attr( $settings['success_color'] ?? '#16a34a' ); ?>" class="wsc-color-picker"></td>
						</tr>
					</table>
				</div>

				<div class="wsc-auth-card" style="margin-top: 20px;">
					<h2><?php esc_html_e( 'Layout & Style', 'vms-span-checker' ); ?></h2>
					<table class="form-table">
						<tr>
							<th><label for="border_radius"><?php esc_html_e( 'Border Radius', 'vms-span-checker' ); ?></label></th>
							<td>
								<input type="range" id="border_radius" name="border_radius" value="<?php echo esc_attr( $settings['border_radius'] ); ?>" min="0" max="24" class="wsc-range-input">
								<span class="wsc-range-value"><?php echo esc_html( $settings['border_radius'] ); ?>px</span>
							</td>
						</tr>
						<tr>
							<th><label for="form_width"><?php esc_html_e( 'Form Width', 'vms-span-checker' ); ?></label></th>
							<td>
								<input type="range" id="form_width" name="form_width" value="<?php echo esc_attr( $settings['form_width'] ); ?>" min="300" max="600" step="20" class="wsc-range-input">
								<span class="wsc-range-value"><?php echo esc_html( $settings['form_width'] ); ?>px</span>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Display Options', 'vms-span-checker' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="show_labels" value="1" <?php checked( $settings['show_labels'] ); ?>>
									<?php esc_html_e( 'Show field labels', 'vms-span-checker' ); ?>
								</label><br>
								<label>
									<input type="checkbox" name="show_placeholders" value="1" <?php checked( $settings['show_placeholders'] ); ?>>
									<?php esc_html_e( 'Show placeholders', 'vms-span-checker' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th><label for="button_style"><?php esc_html_e( 'Button Style', 'vms-span-checker' ); ?></label></th>
							<td>
								<select id="button_style" name="button_style">
									<option value="filled" <?php selected( $settings['button_style'], 'filled' ); ?>><?php esc_html_e( 'Filled', 'vms-span-checker' ); ?></option>
									<option value="outline" <?php selected( $settings['button_style'], 'outline' ); ?>><?php esc_html_e( 'Outline', 'vms-span-checker' ); ?></option>
									<option value="gradient" <?php selected( $settings['button_style'], 'gradient' ); ?>><?php esc_html_e( 'Gradient', 'vms-span-checker' ); ?></option>
								</select>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<div class="wsc-auth-design-preview">
				<div class="wsc-auth-card">
					<h2><?php esc_html_e( 'Live Preview', 'vms-span-checker' ); ?></h2>
					<div class="wsc-auth-preview-select">
						<select id="preview-form-type">
							<option value="login"><?php esc_html_e( 'Login Form', 'vms-span-checker' ); ?></option>
							<option value="register"><?php esc_html_e( 'Registration Form', 'vms-span-checker' ); ?></option>
							<option value="forgot"><?php esc_html_e( 'Forgot Password', 'vms-span-checker' ); ?></option>
						</select>
					</div>
					<div class="wsc-auth-preview-container" id="wsc-preview-container">
						<!-- Preview will be rendered here -->
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Validation Tab -->
	<div class="wsc-auth-panel" id="panel-validation">
		<div class="wsc-auth-card">
			<h2><?php esc_html_e( 'Login Form Validation', 'vms-span-checker' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'reCAPTCHA Protection', 'vms-span-checker' ); ?></th>
					<td>
						<?php vms_span_checker_admin_switch( array( 'name' => 'login_recaptcha', 'checked' => $settings['login_recaptcha'] ) ); ?>
						<?php if ( ! $has_recaptcha ) : ?>
							<p class="description" style="color: #d63638;"><?php esc_html_e( 'Configure reCAPTCHA keys in API Settings first.', 'vms-span-checker' ); ?></p>
						<?php endif; ?>
					</td>
				</tr>
			</table>
		</div>

		<div class="wsc-auth-card" style="margin-top: 20px;">
			<h2><?php esc_html_e( 'Registration Form Validation', 'vms-span-checker' ); ?></h2>
			<p class="description"><?php esc_html_e( 'These validation rules are automatically applied when users register through your auth forms.', 'vms-span-checker' ); ?></p>

			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'reCAPTCHA Protection', 'vms-span-checker' ); ?></th>
					<td>
						<?php vms_span_checker_admin_switch( array( 'name' => 'register_recaptcha', 'checked' => $settings['register_recaptcha'] ) ); ?>
						<?php if ( ! $has_recaptcha ) : ?>
							<p class="description" style="color: #d63638;"><?php esc_html_e( 'Configure reCAPTCHA keys in API Settings first.', 'vms-span-checker' ); ?></p>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Check DNS (Domain Exists)', 'vms-span-checker' ); ?></th>
					<td><?php vms_span_checker_admin_switch( array( 'name' => 'register_check_dns', 'checked' => $settings['register_check_dns'] ) ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Check MX (Can Receive Email)', 'vms-span-checker' ); ?></th>
					<td><?php vms_span_checker_admin_switch( array( 'name' => 'register_check_mx', 'checked' => $settings['register_check_mx'] ) ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Block Disposable Emails', 'vms-span-checker' ); ?></th>
					<td><?php vms_span_checker_admin_switch( array( 'name' => 'register_check_disposable', 'checked' => $settings['register_check_disposable'] ) ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Google Web Risk Check', 'vms-span-checker' ); ?></th>
					<td>
						<?php vms_span_checker_admin_switch( array( 'name' => 'register_webrisk', 'checked' => $settings['register_webrisk'] ) ); ?>
						<p class="description"><?php esc_html_e( 'Requires Web Risk API key. DNS and MX checks become mandatory.', 'vms-span-checker' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'VirusTotal Check', 'vms-span-checker' ); ?></th>
					<td>
						<?php vms_span_checker_admin_switch( array( 'name' => 'register_virustotal', 'checked' => $settings['register_virustotal'] ) ); ?>
						<p class="description"><?php esc_html_e( 'Requires VirusTotal API key. DNS and MX checks become mandatory.', 'vms-span-checker' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<div class="wsc-auth-card" style="margin-top: 20px;">
			<h2><?php esc_html_e( 'Email Verification (Choose One)', 'vms-span-checker' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Add extra security by requiring users to verify their email before completing registration.', 'vms-span-checker' ); ?></p>

			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Email Verification', 'vms-span-checker' ); ?></th>
					<td>
						<?php vms_span_checker_admin_switch( array( 'name' => 'enable_otp_verification', 'checked' => $settings['enable_email_verification'] ?? false ) ); ?>
						<p class="description"><?php esc_html_e( 'Require email verification before account creation. Users receive both OTP code and activation link.', 'vms-span-checker' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="otp_expires_minutes"><?php esc_html_e( 'OTP Expires (minutes)', 'vms-span-checker' ); ?></label></th>
					<td>
						<input type="number" id="otp_expires_minutes" name="otp_expires_minutes" value="<?php echo esc_attr( $settings['otp_expires_minutes'] ?? 10 ); ?>" min="1" max="60" class="small-text">
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Verification Page', 'vms-span-checker' ); ?></th>
					<td>
						<select name="verify_page_id" class="wsc-page-select" data-setting="verify_page_id">
							<option value=""><?php esc_html_e( '— Select Page —', 'vms-span-checker' ); ?></option>
							<?php foreach ( $pages as $page ) : ?>
								<option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( $settings['verify_page_id'] ?? 0, $page->ID ); ?>>
									<?php echo esc_html( $page->post_title ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Verification page with [wsc_verify_form] shortcode. Users can verify via OTP code OR activation link.', 'vms-span-checker' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="link_expires_hours"><?php esc_html_e( 'Link Expires (hours)', 'vms-span-checker' ); ?></label></th>
					<td>
						<input type="number" id="link_expires_hours" name="link_expires_hours" value="<?php echo esc_attr( $settings['link_expires_hours'] ?? 24 ); ?>" min="1" max="168" class="small-text">
						<p class="description"><?php esc_html_e( 'How long the activation link in email remains valid.', 'vms-span-checker' ); ?></p>
					</td>
				</tr>
			</table>

			<div class="notice notice-info inline" style="margin: 15px 0;">
				<p><?php esc_html_e( 'When enabled, users receive both an activation link AND a 6-digit OTP code. They can verify using either method.', 'vms-span-checker' ); ?></p>
			</div>
		</div>
	</div>

	<!-- SMTP Tab -->
	<div class="wsc-auth-panel" id="panel-smtp">
		<div class="wsc-auth-card">
			<h2><?php esc_html_e( 'SMTP Configuration', 'vms-span-checker' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Configure SMTP to ensure password reset emails are delivered reliably.', 'vms-span-checker' ); ?></p>

			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Enable SMTP', 'vms-span-checker' ); ?></th>
					<td><?php vms_span_checker_admin_switch( array( 'name' => 'smtp_enabled', 'checked' => $smtp_settings['enabled'] ) ); ?></td>
				</tr>
				<tr>
					<th><label for="smtp_host"><?php esc_html_e( 'SMTP Host', 'vms-span-checker' ); ?></label></th>
					<td>
						<input type="text" id="smtp_host" name="smtp_host" value="<?php echo esc_attr( $smtp_settings['host'] ); ?>" class="regular-text" placeholder="smtp.example.com">
					</td>
				</tr>
				<tr>
					<th><label for="smtp_port"><?php esc_html_e( 'SMTP Port', 'vms-span-checker' ); ?></label></th>
					<td>
						<input type="number" id="smtp_port" name="smtp_port" value="<?php echo esc_attr( $smtp_settings['port'] ); ?>" class="small-text" placeholder="587">
						<p class="description"><?php esc_html_e( 'Common ports: 25, 465 (SSL), 587 (TLS)', 'vms-span-checker' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="smtp_encryption"><?php esc_html_e( 'Encryption', 'vms-span-checker' ); ?></label></th>
					<td>
						<select id="smtp_encryption" name="smtp_encryption">
							<option value="" <?php selected( $smtp_settings['encryption'], '' ); ?>><?php esc_html_e( 'None', 'vms-span-checker' ); ?></option>
							<option value="tls" <?php selected( $smtp_settings['encryption'], 'tls' ); ?>>TLS</option>
							<option value="ssl" <?php selected( $smtp_settings['encryption'], 'ssl' ); ?>>SSL</option>
						</select>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Authentication', 'vms-span-checker' ); ?></th>
					<td><?php vms_span_checker_admin_switch( array( 'name' => 'smtp_auth', 'checked' => $smtp_settings['auth'] ) ); ?></td>
				</tr>
				<tr>
					<th><label for="smtp_username"><?php esc_html_e( 'Username', 'vms-span-checker' ); ?></label></th>
					<td>
						<input type="text" id="smtp_username" name="smtp_username" value="<?php echo esc_attr( $smtp_settings['username'] ); ?>" class="regular-text" autocomplete="off">
					</td>
				</tr>
				<tr>
					<th><label for="smtp_password"><?php esc_html_e( 'Password', 'vms-span-checker' ); ?></label></th>
					<td>
						<input type="password" id="smtp_password" name="smtp_password" value="<?php echo esc_attr( $smtp_settings['password'] ); ?>" class="regular-text" autocomplete="new-password">
					</td>
				</tr>
				<tr>
					<th><label for="smtp_from_email"><?php esc_html_e( 'From Email', 'vms-span-checker' ); ?></label></th>
					<td>
						<input type="email" id="smtp_from_email" name="smtp_from_email" value="<?php echo esc_attr( $smtp_settings['from_email'] ); ?>" class="regular-text" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
					</td>
				</tr>
				<tr>
					<th><label for="smtp_from_name"><?php esc_html_e( 'From Name', 'vms-span-checker' ); ?></label></th>
					<td>
						<input type="text" id="smtp_from_name" name="smtp_from_name" value="<?php echo esc_attr( $smtp_settings['from_name'] ); ?>" class="regular-text" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
					</td>
				</tr>
			</table>

			<div class="wsc-smtp-test" style="margin-top: 20px; padding: 15px; background: #f0f0f1; border-radius: 4px;">
				<h3><?php esc_html_e( 'Test SMTP Configuration', 'vms-span-checker' ); ?></h3>
				<p>
					<input type="email" id="smtp_test_email" class="regular-text" placeholder="<?php esc_attr_e( 'Enter email to send test', 'vms-span-checker' ); ?>">
					<button type="button" class="button" id="wsc-test-smtp"><?php esc_html_e( 'Send Test Email', 'vms-span-checker' ); ?></button>
				</p>
				<div id="smtp-test-result"></div>
			</div>
		</div>
	</div>

	<!-- Guide Tab -->
	<div class="wsc-auth-panel" id="panel-guide">
		<div class="wsc-auth-card">
			<h2><?php esc_html_e( 'How to Use Auth Form Templates', 'vms-span-checker' ); ?></h2>

			<div class="wsc-guide-section">
				<h3><?php esc_html_e( 'Quick Start (Recommended)', 'vms-span-checker' ); ?></h3>
				<ol>
					<li><?php esc_html_e( 'Click "Auto-Generate All Auth Pages" button in the Form Templates tab.', 'vms-span-checker' ); ?></li>
					<li><?php esc_html_e( 'This creates Login, Register, Forgot Password, and Reset Password pages automatically.', 'vms-span-checker' ); ?></li>
					<li><?php esc_html_e( 'Customize the design in the "Design & Preview" tab.', 'vms-span-checker' ); ?></li>
					<li><?php esc_html_e( 'Configure validation rules in the "Validation Rules" tab.', 'vms-span-checker' ); ?></li>
					<li><?php esc_html_e( 'Set up SMTP for reliable email delivery.', 'vms-span-checker' ); ?></li>
				</ol>
			</div>

			<div class="wsc-guide-section">
				<h3><?php esc_html_e( 'Manual Setup', 'vms-span-checker' ); ?></h3>
				<p><?php esc_html_e( 'If you prefer to create pages manually:', 'vms-span-checker' ); ?></p>
				<ol>
					<li><?php esc_html_e( 'Create a new page in WordPress.', 'vms-span-checker' ); ?></li>
					<li><?php esc_html_e( 'Add the appropriate shortcode to the page content:', 'vms-span-checker' ); ?>
						<ul style="margin-left: 20px; list-style: disc;">
							<li><code>[wsc_login_form]</code> - <?php esc_html_e( 'Login form', 'vms-span-checker' ); ?></li>
							<li><code>[wsc_register_form]</code> - <?php esc_html_e( 'Registration form', 'vms-span-checker' ); ?></li>
							<li><code>[wsc_forgot_password_form]</code> - <?php esc_html_e( 'Forgot password form', 'vms-span-checker' ); ?></li>
							<li><code>[wsc_reset_password_form]</code> - <?php esc_html_e( 'Reset password form', 'vms-span-checker' ); ?></li>
						</ul>
					</li>
					<li><?php esc_html_e( 'Publish the page.', 'vms-span-checker' ); ?></li>
					<li><?php esc_html_e( 'Assign the page in the Form Templates tab so forms can link to each other.', 'vms-span-checker' ); ?></li>
				</ol>
			</div>

			<div class="wsc-guide-section">
				<h3><?php esc_html_e( 'Using the Page Editor Meta Box', 'vms-span-checker' ); ?></h3>
				<p><?php esc_html_e( 'When editing any page, you\'ll see a "VMS Span Checker Auth Form" meta box in the sidebar. Select a form type from the dropdown to indicate this page uses one of our auth forms. This helps with:', 'vms-span-checker' ); ?></p>
				<ul style="margin-left: 20px; list-style: disc;">
					<li><?php esc_html_e( 'Organizing your auth pages', 'vms-span-checker' ); ?></li>
					<li><?php esc_html_e( 'Automatic linking between forms', 'vms-span-checker' ); ?></li>
					<li><?php esc_html_e( 'Quick reference for which pages have auth forms', 'vms-span-checker' ); ?></li>
				</ul>
			</div>

			<div class="wsc-guide-section">
				<h3><?php esc_html_e( 'Built-in Features', 'vms-span-checker' ); ?></h3>
				<ul style="margin-left: 20px; list-style: disc;">
					<li><strong><?php esc_html_e( 'AJAX Submissions', 'vms-span-checker' ); ?></strong> - <?php esc_html_e( 'Forms submit without page reload', 'vms-span-checker' ); ?></li>
					<li><strong><?php esc_html_e( 'Email Validation', 'vms-span-checker' ); ?></strong> - <?php esc_html_e( 'DNS, MX, disposable, and reputation checks', 'vms-span-checker' ); ?></li>
					<li><strong><?php esc_html_e( 'reCAPTCHA', 'vms-span-checker' ); ?></strong> - <?php esc_html_e( 'Optional bot protection', 'vms-span-checker' ); ?></li>
					<li><strong><?php esc_html_e( 'Password Strength', 'vms-span-checker' ); ?></strong> - <?php esc_html_e( 'Visual indicator for password quality', 'vms-span-checker' ); ?></li>
					<li><strong><?php esc_html_e( 'Auto Redirect', 'vms-span-checker' ); ?></strong> - <?php esc_html_e( 'Configurable redirects after login/register', 'vms-span-checker' ); ?></li>
					<li><strong><?php esc_html_e( 'Mobile Responsive', 'vms-span-checker' ); ?></strong> - <?php esc_html_e( 'Works on all screen sizes', 'vms-span-checker' ); ?></li>
				</ul>
			</div>

			<div class="wsc-guide-section">
				<h3><?php esc_html_e( 'Advantages Over Default WordPress Forms', 'vms-span-checker' ); ?></h3>
				<table class="wp-list-table widefat">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Feature', 'vms-span-checker' ); ?></th>
							<th><?php esc_html_e( 'Default WordPress', 'vms-span-checker' ); ?></th>
							<th><?php esc_html_e( 'VMS Span Checker', 'vms-span-checker' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><?php esc_html_e( 'Custom Design', 'vms-span-checker' ); ?></td>
							<td>❌</td>
							<td>✅ <?php esc_html_e( 'Full customization', 'vms-span-checker' ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Email Validation', 'vms-span-checker' ); ?></td>
							<td>❌ <?php esc_html_e( 'Basic format only', 'vms-span-checker' ); ?></td>
							<td>✅ <?php esc_html_e( 'DNS, MX, disposable, reputation', 'vms-span-checker' ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Spam Protection', 'vms-span-checker' ); ?></td>
							<td>❌</td>
							<td>✅ <?php esc_html_e( 'reCAPTCHA + validation', 'vms-span-checker' ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'AJAX Submission', 'vms-span-checker' ); ?></td>
							<td>❌</td>
							<td>✅</td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Page Integration', 'vms-span-checker' ); ?></td>
							<td>❌ <?php esc_html_e( 'Separate wp-login.php', 'vms-span-checker' ); ?></td>
							<td>✅ <?php esc_html_e( 'Any page via shortcode', 'vms-span-checker' ); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>

	<div class="wsc-auth-save-bar">
		<button type="button" class="button button-primary button-large" id="wsc-save-auth-settings">
			<?php esc_html_e( 'Save All Settings', 'vms-span-checker' ); ?>
		</button>
		<span class="wsc-save-status"></span>
	</div>
</div>

<style>
.wsc-auth-forms-admin { max-width: 1400px; }
.wsc-auth-admin-tabs { display: flex; gap: 0; border-bottom: 1px solid #c3c4c7; margin-bottom: 20px; }
.wsc-auth-tab { background: none; border: none; padding: 12px 20px; font-size: 14px; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -1px; }
.wsc-auth-tab:hover { background: #f0f0f1; }
.wsc-auth-tab.active { border-bottom-color: #2271b1; color: #2271b1; font-weight: 500; }
.wsc-auth-panel { display: none; }
.wsc-auth-panel.active { display: block; }
.wsc-auth-card { background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 20px; }
.wsc-auth-card h2 { margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #f0f0f1; }
.wsc-status { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 12px; }
.wsc-status--active { background: #d4edda; color: #155724; }
.wsc-status--inactive { background: #f8d7da; color: #721c24; }
.wsc-copy-btn { margin-left: 5px !important; }
.wsc-auth-design-wrap { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.wsc-color-picker { width: 60px; height: 36px; padding: 2px; cursor: pointer; }
.wsc-range-input { width: 200px; vertical-align: middle; }
.wsc-range-value { margin-left: 10px; font-weight: 500; }
.wsc-auth-preview-container { background: #f0f0f1; padding: 30px; border-radius: 4px; margin-top: 15px; min-height: 400px; }
.wsc-auth-save-bar { position: sticky; bottom: 0; background: #fff; padding: 15px 20px; border-top: 1px solid #c3c4c7; margin: 20px -20px -20px; display: flex; align-items: center; gap: 15px; }
.wsc-save-status { color: #00a32a; font-weight: 500; }
.wsc-guide-section { margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #f0f0f1; }
.wsc-guide-section:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
.wsc-guide-section h3 { color: #1d2327; }
.wsc-guide-section ol, .wsc-guide-section ul { line-height: 1.8; }
@media (max-width: 1200px) {
	.wsc-auth-design-wrap { grid-template-columns: 1fr; }
}
</style>

<script>
jQuery(document).ready(function($) {
	// Tab switching
	$('.wsc-auth-tab').on('click', function() {
		var tab = $(this).data('tab');
		$('.wsc-auth-tab').removeClass('active');
		$(this).addClass('active');
		$('.wsc-auth-panel').removeClass('active');
		$('#panel-' + tab).addClass('active');
	});

	// Copy shortcode
	$('.wsc-copy-btn').on('click', function() {
		var text = $(this).data('copy');
		navigator.clipboard.writeText(text).then(function() {
			Swal.fire({ icon: 'success', title: '<?php esc_html_e( 'Copied!', 'vms-span-checker' ); ?>', timer: 1000, showConfirmButton: false });
		});
	});

	// Range input display
	$('.wsc-range-input').on('input', function() {
		$(this).siblings('.wsc-range-value').text($(this).val() + 'px');
		updatePreview();
	});

	// Color picker change
	$('.wsc-color-picker').on('input', function() {
		updatePreview();
	});

	// Checkbox changes
	$('input[name="show_labels"], input[name="show_placeholders"]').on('change', updatePreview);
	$('#button_style').on('change', updatePreview);
	$('#preview-form-type').on('change', updatePreview);

	// Generate auth pages
	$('#wsc-generate-pages').on('click', function() {
		var $btn = $(this);
		$btn.prop('disabled', true).text('<?php esc_html_e( 'Generating...', 'vms-span-checker' ); ?>');

		$.post(ajaxurl, {
			action: 'wsc_generate_auth_pages',
			nonce: WPSpanChecker.nonce
		}, function(response) {
			$btn.prop('disabled', false).html('<span class="dashicons dashicons-admin-page" style="margin-top: 4px;"></span> <?php esc_html_e( 'Auto-Generate All Auth Pages', 'vms-span-checker' ); ?>');
			if (response.success) {
				Swal.fire({ icon: 'success', title: '<?php esc_html_e( 'Success', 'vms-span-checker' ); ?>', text: response.data.message }).then(function() {
					location.reload();
				});
			} else {
				Swal.fire({ icon: 'error', title: '<?php esc_html_e( 'Error', 'vms-span-checker' ); ?>', text: response.data.message });
			}
		});
	});

	// Save settings
	$('#wsc-save-auth-settings').on('click', function() {
		var $btn = $(this);
		var $status = $('.wsc-save-status');
		$btn.prop('disabled', true);
		$status.text('<?php esc_html_e( 'Saving...', 'vms-span-checker' ); ?>');

		// Collect form settings
		var formData = {
			action: 'wsc_save_auth_form_settings',
			nonce: WPSpanChecker.nonce,
			primary_color: $('#primary_color').val(),
			secondary_color: $('#secondary_color').val(),
			text_color: $('#text_color').val(),
			background_color: $('#background_color').val(),
			// Input field colors
			border_color: $('#border_color').val(),
			border_hover_color: $('#border_hover_color').val(),
			border_focus_color: $('#border_focus_color').val(),
			input_bg_color: $('#input_bg_color').val(),
			input_focus_bg: $('#input_focus_bg').val(),
			error_color: $('#error_color').val(),
			success_color: $('#success_color').val(),
			// Layout
			border_radius: $('#border_radius').val(),
			form_width: $('#form_width').val(),
			show_labels: $('input[name="show_labels"]').is(':checked') ? 1 : 0,
			show_placeholders: $('input[name="show_placeholders"]').is(':checked') ? 1 : 0,
			button_style: $('#button_style').val(),
			login_redirect: $('#login_redirect').val(),
			register_redirect: $('#register_redirect').val(),
			login_page_id: $('select[data-setting="login_page_id"]').val(),
			register_page_id: $('select[data-setting="register_page_id"]').val(),
			forgot_page_id: $('select[data-setting="forgot_page_id"]').val(),
			reset_page_id: $('select[data-setting="reset_page_id"]').val(),
			login_recaptcha: $('input[name="login_recaptcha"]').is(':checked') ? 1 : 0,
			register_recaptcha: $('input[name="register_recaptcha"]').is(':checked') ? 1 : 0,
			register_check_dns: $('input[name="register_check_dns"]').is(':checked') ? 1 : 0,
			register_check_mx: $('input[name="register_check_mx"]').is(':checked') ? 1 : 0,
			register_check_disposable: $('input[name="register_check_disposable"]').is(':checked') ? 1 : 0,
			register_webrisk: $('input[name="register_webrisk"]').is(':checked') ? 1 : 0,
			register_virustotal: $('input[name="register_virustotal"]').is(':checked') ? 1 : 0,
			// OTP and Activation
			enable_otp_verification: $('input[name="enable_otp_verification"]').is(':checked') ? 1 : 0,
			otp_expires_minutes: $('#otp_expires_minutes').val(),
			link_expires_hours: $('#link_expires_hours').val(),
			verify_page_id: $('select[name="verify_page_id"]').val() || $('select[data-setting="verify_page_id"]').val()
		};

		$.post(ajaxurl, formData, function(response) {
			// Also save SMTP settings
			var smtpData = {
				action: 'wsc_save_smtp_settings',
				nonce: WPSpanChecker.nonce,
				smtp_enabled: $('input[name="smtp_enabled"]').is(':checked') ? 1 : 0,
				smtp_host: $('#smtp_host').val(),
				smtp_port: $('#smtp_port').val(),
				smtp_encryption: $('#smtp_encryption').val(),
				smtp_auth: $('input[name="smtp_auth"]').is(':checked') ? 1 : 0,
				smtp_username: $('#smtp_username').val(),
				smtp_password: $('#smtp_password').val(),
				smtp_from_email: $('#smtp_from_email').val(),
				smtp_from_name: $('#smtp_from_name').val()
			};

			$.post(ajaxurl, smtpData, function() {
				$btn.prop('disabled', false);
				$status.text('<?php esc_html_e( 'Saved!', 'vms-span-checker' ); ?>');
				setTimeout(function() { $status.text(''); }, 3000);
			});
		});
	});

	// Test SMTP
	$('#wsc-test-smtp').on('click', function() {
		var email = $('#smtp_test_email').val();
		var $result = $('#smtp-test-result');

		if (!email) {
			$result.html('<span style="color: #d63638;"><?php esc_html_e( 'Please enter an email address.', 'vms-span-checker' ); ?></span>');
			return;
		}

		$result.html('<?php esc_html_e( 'Sending...', 'vms-span-checker' ); ?>');

		$.post(ajaxurl, {
			action: 'wsc_test_smtp',
			nonce: WPSpanChecker.nonce,
			test_email: email
		}, function(response) {
			if (response.success) {
				$result.html('<span style="color: #00a32a;">' + response.data.message + '</span>');
			} else {
				$result.html('<span style="color: #d63638;">' + response.data.message + '</span>');
			}
		});
	});

	// Live preview
	function updatePreview() {
		var formType = $('#preview-form-type').val();
		var settings = {
			primaryColor: $('#primary_color').val(),
			secondaryColor: $('#secondary_color').val(),
			textColor: $('#text_color').val(),
			backgroundColor: $('#background_color').val(),
			borderColor: $('#border_color').val() || '#d1d5db',
			borderHoverColor: $('#border_hover_color').val() || '#9ca3af',
			borderFocusColor: $('#border_focus_color').val() || '#2563eb',
			inputBgColor: $('#input_bg_color').val() || '#ffffff',
			inputFocusBg: $('#input_focus_bg').val() || '#f9fafb',
			errorColor: $('#error_color').val() || '#dc2626',
			successColor: $('#success_color').val() || '#16a34a',
			borderRadius: $('#border_radius').val() + 'px',
			formWidth: $('#form_width').val() + 'px',
			showLabels: $('input[name="show_labels"]').is(':checked'),
			showPlaceholders: $('input[name="show_placeholders"]').is(':checked'),
			buttonStyle: $('#button_style').val()
		};

		var html = generatePreviewHTML(formType, settings);
		$('#wsc-preview-container').html(html);
		
		// Add interactive hover/focus effects
		addPreviewInteractions(settings);
	}

	function addPreviewInteractions(s) {
		var $container = $('#wsc-preview-container');
		
		$container.find('.wsc-preview-input').each(function() {
			var $input = $(this);
			
			$input.on('mouseenter', function() {
				if (!$(this).is(':focus')) {
					$(this).css('border-color', s.borderHoverColor);
				}
			}).on('mouseleave', function() {
				if (!$(this).is(':focus')) {
					$(this).css('border-color', s.borderColor);
				}
			}).on('focus', function() {
				$(this).css({
					'border-color': s.borderFocusColor,
					'background-color': s.inputFocusBg,
					'box-shadow': '0 0 0 3px ' + hexToRgba(s.borderFocusColor, 0.1)
				});
				$(this).closest('.wsc-preview-field').find('label').css('color', s.primaryColor);
			}).on('blur', function() {
				$(this).css({
					'border-color': s.borderColor,
					'background-color': s.inputBgColor,
					'box-shadow': 'none'
				});
				$(this).closest('.wsc-preview-field').find('label').css('color', s.textColor);
			});
		});
		
		// Demo error/success states on click
		$container.find('.wsc-preview-demo-error').on('click', function(e) {
			e.preventDefault();
			var $field = $container.find('.wsc-preview-field').first().find('.wsc-preview-input');
			$field.css({ 'border-color': s.errorColor, 'background-color': '#fef2f2' });
			setTimeout(function() {
				$field.css({ 'border-color': s.borderColor, 'background-color': s.inputBgColor });
			}, 2000);
		});
		
		$container.find('.wsc-preview-demo-success').on('click', function(e) {
			e.preventDefault();
			var $field = $container.find('.wsc-preview-field').eq(1).find('.wsc-preview-input');
			$field.css({ 'border-color': s.successColor, 'background-color': '#f0fdf4' });
			setTimeout(function() {
				$field.css({ 'border-color': s.borderColor, 'background-color': s.inputBgColor });
			}, 2000);
		});
	}
	
	function hexToRgba(hex, alpha) {
		var r = parseInt(hex.slice(1, 3), 16);
		var g = parseInt(hex.slice(3, 5), 16);
		var b = parseInt(hex.slice(5, 7), 16);
		return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + alpha + ')';
	}

	function generatePreviewHTML(type, s) {
		var labelDisplay = s.showLabels ? 'block' : 'none';
		var placeholderAttr = s.showPlaceholders ? 'placeholder="<?php esc_html_e( 'Type here...', 'vms-span-checker' ); ?>"' : '';

		var buttonStyle = '';
		if (s.buttonStyle === 'filled') {
			buttonStyle = 'background:' + s.primaryColor + ';color:#fff;border:none;';
		} else if (s.buttonStyle === 'outline') {
			buttonStyle = 'background:transparent;color:' + s.primaryColor + ';border:2px solid ' + s.primaryColor + ';';
		} else if (s.buttonStyle === 'gradient') {
			buttonStyle = 'background:linear-gradient(135deg,' + s.primaryColor + ',' + s.secondaryColor + ');color:#fff;border:none;';
		}

		var formStyle = 'max-width:' + s.formWidth + ';background:' + s.backgroundColor + ';padding:30px;border-radius:' + s.borderRadius + ';box-shadow:0 4px 6px rgba(0,0,0,0.1);margin:0 auto;';
		var inputStyle = 'width:100%;padding:12px 14px;border:1px solid ' + s.borderColor + ';border-radius:' + s.borderRadius + ';box-sizing:border-box;background:' + s.inputBgColor + ';color:' + s.textColor + ';font-size:15px;transition:all 0.2s;outline:none;';
		var labelStyle = 'display:' + labelDisplay + ';margin-bottom:6px;color:' + s.textColor + ';font-weight:500;font-size:14px;transition:color 0.2s;';
		var titleStyle = 'color:' + s.textColor + ';margin:0 0 24px 0;text-align:center;font-size:22px;';
		var fieldStyle = 'margin-bottom:18px;';
		var btnStyle = buttonStyle + 'width:100%;padding:14px;border-radius:' + s.borderRadius + ';cursor:pointer;font-size:16px;font-weight:500;margin-top:8px;';

		var title = '<?php esc_html_e( 'Login', 'vms-span-checker' ); ?>';
		var fields = '';
		var btnText = '<?php esc_html_e( 'Login', 'vms-span-checker' ); ?>';

		if (type === 'login') {
			title = '<?php esc_html_e( 'Login', 'vms-span-checker' ); ?>';
			btnText = '<?php esc_html_e( 'Login', 'vms-span-checker' ); ?>';
			fields = '<div class="wsc-preview-field" style="' + fieldStyle + '"><label style="' + labelStyle + '"><?php esc_html_e( 'Username or Email', 'vms-span-checker' ); ?></label><input type="text" class="wsc-preview-input" style="' + inputStyle + '" ' + placeholderAttr + '></div>' +
				'<div class="wsc-preview-field" style="' + fieldStyle + '"><label style="' + labelStyle + '"><?php esc_html_e( 'Password', 'vms-span-checker' ); ?></label><input type="password" class="wsc-preview-input" style="' + inputStyle + '" ' + placeholderAttr + '></div>';
		} else if (type === 'register') {
			title = '<?php esc_html_e( 'Create Account', 'vms-span-checker' ); ?>';
			btnText = '<?php esc_html_e( 'Create Account', 'vms-span-checker' ); ?>';
			fields = '<div class="wsc-preview-field" style="' + fieldStyle + '"><label style="' + labelStyle + '"><?php esc_html_e( 'Username', 'vms-span-checker' ); ?></label><input type="text" class="wsc-preview-input" style="' + inputStyle + '" ' + placeholderAttr + '></div>' +
				'<div class="wsc-preview-field" style="' + fieldStyle + '"><label style="' + labelStyle + '"><?php esc_html_e( 'Email', 'vms-span-checker' ); ?></label><input type="email" class="wsc-preview-input" style="' + inputStyle + '" ' + placeholderAttr + '></div>' +
				'<div class="wsc-preview-field" style="' + fieldStyle + '"><label style="' + labelStyle + '"><?php esc_html_e( 'Password', 'vms-span-checker' ); ?></label><input type="password" class="wsc-preview-input" style="' + inputStyle + '" ' + placeholderAttr + '></div>' +
				'<div class="wsc-preview-field" style="' + fieldStyle + '"><label style="' + labelStyle + '"><?php esc_html_e( 'Confirm Password', 'vms-span-checker' ); ?></label><input type="password" class="wsc-preview-input" style="' + inputStyle + '" ' + placeholderAttr + '></div>';
		} else if (type === 'forgot') {
			title = '<?php esc_html_e( 'Reset Password', 'vms-span-checker' ); ?>';
			btnText = '<?php esc_html_e( 'Send Reset Link', 'vms-span-checker' ); ?>';
			fields = '<p style="color:' + s.textColor + ';text-align:center;margin-bottom:20px;font-size:14px;"><?php esc_html_e( 'Enter your email to receive a reset link.', 'vms-span-checker' ); ?></p>' +
				'<div class="wsc-preview-field" style="' + fieldStyle + '"><label style="' + labelStyle + '"><?php esc_html_e( 'Email', 'vms-span-checker' ); ?></label><input type="email" class="wsc-preview-input" style="' + inputStyle + '" ' + placeholderAttr + '></div>';
		}

		var demoButtons = '<div style="margin-top:16px;text-align:center;font-size:12px;">' +
			'<span style="color:#6b7280;"><?php esc_html_e( 'Test states:', 'vms-span-checker' ); ?></span> ' +
			'<a href="#" class="wsc-preview-demo-error" style="color:' + s.errorColor + ';margin:0 8px;"><?php esc_html_e( 'Error', 'vms-span-checker' ); ?></a>' +
			'<a href="#" class="wsc-preview-demo-success" style="color:' + s.successColor + ';"><?php esc_html_e( 'Success', 'vms-span-checker' ); ?></a>' +
			'</div>';

		return '<div style="' + formStyle + '">' +
			'<h2 style="' + titleStyle + '">' + title + '</h2>' +
			fields +
			'<button type="button" style="' + btnStyle + '">' + btnText + '</button>' +
			demoButtons +
			'</div>';
	}

	// Initialize preview
	updatePreview();
});
</script>
