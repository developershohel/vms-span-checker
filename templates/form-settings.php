<?php
/**
 * Form Guard - map front-end forms to validation rules.
 *
 * @package WP_Span_Checker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wsc_presets = wp_span_checker_page_target_presets();

?>

<div class="wrap wsc-wrap wsc-admin" id="wsc-content-wrapper">
	<?php
	wp_span_checker_admin_page_header(
		__( 'Form Guard', 'wp-span-checker' ),
		__( 'Protect front-end forms: map each form to fields, disposable checks, and optional Web Risk / VirusTotal scans.', 'wp-span-checker' )
	);
	?>

	<div class="wsc-control-group wsc-flex wsc-justify-end wsc-items-center wsc-mb-8">
		<button type="button" class="wsc-btn wsc-btn-outline-primary" id="wscAddFormSetting"><?php esc_html_e( 'Add form guard mapping', 'wp-span-checker' ); ?></button>
	</div>

	<form method="post" class="wsc-form wsc-bg-white wsc-p-8 wsc-rounded-3xl wsc-hidden" id="wsc-settings-form">
		<input type="hidden" name="form_settings_id" id="form_settings_id" value="0">
		<span class="toggleFormField dashicons dashicons-no-alt"></span>
		<div class="wsc-form-content" id="wsc-form-content">
			<div class="wsc-form-group">
				<label for="form_type" class="wsc-form-label"><?php esc_html_e( 'Form type', 'wp-span-checker' ); ?></label>
				<select name="form_type" id="form_type" class="wsc-input wsc-input-primary" required="required">
					<option value="login"><?php esc_html_e( 'Login', 'wp-span-checker' ); ?></option>
					<option value="register"><?php esc_html_e( 'Register', 'wp-span-checker' ); ?></option>
					<option value="contact"><?php esc_html_e( 'Contact', 'wp-span-checker' ); ?></option>
					<option value="comment"><?php esc_html_e( 'Comment', 'wp-span-checker' ); ?></option>
					<option value="newsletter"><?php esc_html_e( 'Newsletter', 'wp-span-checker' ); ?></option>
					<option value="custom"><?php esc_html_e( 'Custom', 'wp-span-checker' ); ?></option>
				</select>
				<span class="wsc-form-error-message wsc-form-error"></span>
			</div>
			<div class="wsc-form-group wsc-form-group--targets">
				<span class="wsc-form-label"><?php esc_html_e( 'Where to run this mapping', 'wp-span-checker' ); ?></span>
				<p class="wsc-form-info-message wsc-text-info wsc-targets-help">
					<?php esc_html_e( 'Choose any combination. The mapping runs if the visitor is on at least one matching location.', 'wp-span-checker' ); ?>
				</p>
				<div class="wsc-target-panels">
					<div class="wsc-target-panel" id="wsc-panel-common">
						<div class="wsc-target-panel__header">
							<span class="wsc-target-panel__title"><?php esc_html_e( 'Common locations', 'wp-span-checker' ); ?></span>
						</div>
						<ul class="wsc-target-presets wsc-panel-content" role="list">
							<?php foreach ( $wsc_presets as $slug => $label ) : ?>
								<li>
									<label class="wsc-target-check wsc-switch wsc-switch--compact">
										<input type="checkbox" class="wsc-switch__input wsc-page-target wsc-common-target" name="wsc_page_target[]" value="<?php echo esc_attr( $slug ); ?>" data-covers="<?php echo esc_attr( 'all-pages' === $slug ? 'all' : ( 'singular-page' === $slug ? 'pages' : ( 'singular-post' === $slug ? 'posts' : ( 'singular-any' === $slug ? 'all-singular' : '' ) ) ) ); ?>">
										<span class="wsc-switch__track" aria-hidden="true"></span>
										<span class="wsc-switch__body">
											<span class="wsc-switch__label"><?php echo esc_html( $label ); ?></span>
										</span>
									</label>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
					<div class="wsc-target-panel" id="wsc-panel-pages">
						<div class="wsc-target-panel__header">
							<span class="wsc-target-panel__title"><?php esc_html_e( 'Specific pages', 'wp-span-checker' ); ?></span>
						</div>
						<div class="wsc-panel-content">
							<div class="wsc-badge-select-wrap" id="wsc-pages-badge-wrap">
								<div class="wsc-badge-selected" id="wsc-pages-selected"></div>
								<div class="wsc-badge-search-wrap">
									<input type="text" class="wsc-input wsc-input-primary wsc-badge-search" id="wsc-search-pages" placeholder="<?php esc_attr_e( 'Search pages by title...', 'wp-span-checker' ); ?>" autocomplete="off">
									<span class="wsc-badge-search-icon dashicons dashicons-search"></span>
								</div>
								<div class="wsc-badge-dropdown wsc-hidden" id="wsc-pages-dropdown">
									<div class="wsc-badge-dropdown-list" id="wsc-pages-list"></div>
									<div class="wsc-badge-dropdown-loading wsc-hidden"><?php esc_html_e( 'Loading...', 'wp-span-checker' ); ?></div>
									<div class="wsc-badge-dropdown-empty wsc-hidden"><?php esc_html_e( 'No pages found', 'wp-span-checker' ); ?></div>
								</div>
							</div>
						</div>
					</div>
					<div class="wsc-target-panel" id="wsc-panel-posts">
						<div class="wsc-target-panel__header">
							<span class="wsc-target-panel__title"><?php esc_html_e( 'Specific posts', 'wp-span-checker' ); ?></span>
						</div>
						<div class="wsc-panel-content">
							<div class="wsc-badge-select-wrap" id="wsc-posts-badge-wrap">
								<div class="wsc-badge-selected" id="wsc-posts-selected"></div>
								<div class="wsc-badge-search-wrap">
									<input type="text" class="wsc-input wsc-input-primary wsc-badge-search" id="wsc-search-posts" placeholder="<?php esc_attr_e( 'Search posts by title...', 'wp-span-checker' ); ?>" autocomplete="off">
									<span class="wsc-badge-search-icon dashicons dashicons-search"></span>
								</div>
								<div class="wsc-badge-dropdown wsc-hidden" id="wsc-posts-dropdown">
									<div class="wsc-badge-dropdown-list" id="wsc-posts-list"></div>
									<div class="wsc-badge-dropdown-loading wsc-hidden"><?php esc_html_e( 'Loading...', 'wp-span-checker' ); ?></div>
									<div class="wsc-badge-dropdown-empty wsc-hidden"><?php esc_html_e( 'No posts found', 'wp-span-checker' ); ?></div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<span class="wsc-form-error-message wsc-form-error"></span>
			</div>
			<div class="wsc-form-group" id="wsc-form-selector-group">
				<label for="form_selector" class="wsc-form-label">
					<?php esc_html_e( 'Form id/class', 'wp-span-checker' ); ?>
					<span class="wsc-label-optional" id="wsc-form-selector-optional"><?php esc_html_e( '(optional)', 'wp-span-checker' ); ?></span>
					<span class="wsc-label-required wsc-hidden" id="wsc-form-selector-required"><?php esc_html_e( '(required for Entire site)', 'wp-span-checker' ); ?></span>
				</label>
				<input class="wsc-input wsc-input-primary" type="text" name="form_selector" id="form_selector" value=""
					placeholder="#contact-form.contact-us"/>
				<span class="wsc-form-info-message wsc-text-info"><?php esc_html_e( 'CSS selector for the form element, e.g. #contactus.contact-us (ID plus classes). Old mappings that used separate Form ID + classes still load here.', 'wp-span-checker' ); ?></span>
				<span class="wsc-form-info-message wsc-text-warning wsc-hidden" id="wsc-entire-site-notice"><?php esc_html_e( 'When targeting the entire site, Form id/class is required so the script can identify the correct form on each page.', 'wp-span-checker' ); ?></span>
				<input type="hidden" name="form_id" id="form_id" value="">
				<input type="hidden" name="form_class" id="form_class" value="">
				<span class="wsc-form-error-message wsc-form-error"></span>
			</div>
			<div class="wsc-form-group">
				<label for="submit_selector" class="wsc-form-label"><?php esc_html_e( 'Submit button id/class (optional)', 'wp-span-checker' ); ?></label>
				<input class="wsc-input wsc-input-primary" type="text" name="submit_selector" id="submit_selector" value=""
					placeholder="#send-message.btn-primary"/>
				<span class="wsc-form-info-message wsc-text-info"><?php esc_html_e( 'If empty, the first submit button inside the form is used. You may use a selector scoped to the form or a global one.', 'wp-span-checker' ); ?></span>
				<span class="wsc-form-error-message wsc-form-error"></span>
			</div>

			<!-- Auto Validation Toggle -->
			<div class="wsc-form-group wsc-validation-mode-group">
				<div class="wsc-validation-mode-toggle">
					<label class="wsc-switch">
						<input type="checkbox" class="wsc-switch__input" id="wsc-auto-validation" name="auto_validation" checked>
						<span class="wsc-switch__track" aria-hidden="true"></span>
						<span class="wsc-switch__body">
							<span class="wsc-switch__label wsc-form-label"><?php esc_html_e( 'Auto Validation', 'wp-span-checker' ); ?></span>
							<span class="wsc-switch__desc"><?php esc_html_e( 'Automatically detect and validate form fields by their type and name attributes.', 'wp-span-checker' ); ?></span>
						</span>
					</label>
				</div>
			</div>

			<!-- Auto Validation Rules (shown when Auto Validation is ON) -->
			<div class="wsc-auto-validation-section" id="wsc-auto-validation-rules">
				<div class="wsc-card wsc-p-4 wsc-mb-4">
					<h3 class="wsc-form-label wsc-mb-2"><?php esc_html_e( 'Validation Rules', 'wp-span-checker' ); ?></h3>
					<p class="wsc-form-info-message wsc-text-info wsc-mb-4"><?php esc_html_e( 'Configure which validations to apply automatically. The system will detect fields by their type (email, password, text, textarea) and name attributes (username, message, etc.).', 'wp-span-checker' ); ?></p>
					
					<div class="wsc-validation-rules-grid">
						<!-- Email Validation -->
						<fieldset class="wsc-validation-rule-fieldset">
							<legend class="wsc-rule-legend">
								<span class="dashicons dashicons-email"></span>
								<?php esc_html_e( 'Email Fields', 'wp-span-checker' ); ?>
								<code>type="email"</code>
							</legend>
							<div class="wsc-rule-options">
								<label class="wsc-switch wsc-switch--compact">
									<input type="checkbox" class="wsc-switch__input" name="auto_email_validate" id="auto_email_validate" checked>
									<span class="wsc-switch__track" aria-hidden="true"></span>
									<span class="wsc-switch__body">
										<span class="wsc-switch__label"><?php esc_html_e( 'Validate email format', 'wp-span-checker' ); ?></span>
									</span>
								</label>
								<label class="wsc-switch wsc-switch--compact">
									<input type="checkbox" class="wsc-switch__input" name="auto_email_mx" id="auto_email_mx" checked>
									<span class="wsc-switch__track" aria-hidden="true"></span>
									<span class="wsc-switch__body">
										<span class="wsc-switch__label"><?php esc_html_e( 'Check MX records & domain', 'wp-span-checker' ); ?></span>
									</span>
								</label>
								<label class="wsc-switch wsc-switch--compact">
									<input type="checkbox" class="wsc-switch__input" name="auto_email_disposable" id="auto_email_disposable" checked>
									<span class="wsc-switch__track" aria-hidden="true"></span>
									<span class="wsc-switch__body">
										<span class="wsc-switch__label"><?php esc_html_e( 'Block disposable emails', 'wp-span-checker' ); ?></span>
									</span>
								</label>
								<label class="wsc-switch wsc-switch--compact">
									<input type="checkbox" class="wsc-switch__input" name="auto_email_webrisk" id="auto_email_webrisk">
									<span class="wsc-switch__track" aria-hidden="true"></span>
									<span class="wsc-switch__body">
										<span class="wsc-switch__label"><?php esc_html_e( 'Google Web Risk', 'wp-span-checker' ); ?></span>
									</span>
								</label>
								<label class="wsc-switch wsc-switch--compact">
									<input type="checkbox" class="wsc-switch__input" name="auto_email_virustotal" id="auto_email_virustotal">
									<span class="wsc-switch__track" aria-hidden="true"></span>
									<span class="wsc-switch__body">
										<span class="wsc-switch__label"><?php esc_html_e( 'VirusTotal', 'wp-span-checker' ); ?></span>
									</span>
								</label>
							</div>
						</fieldset>

						<!-- URL Validation -->
						<fieldset class="wsc-validation-rule-fieldset">
							<legend class="wsc-rule-legend">
								<span class="dashicons dashicons-admin-links"></span>
								<?php esc_html_e( 'URL Fields', 'wp-span-checker' ); ?>
								<code>type="url"</code>
							</legend>
							<div class="wsc-rule-options">
								<label class="wsc-switch wsc-switch--compact">
									<input type="checkbox" class="wsc-switch__input" name="auto_url_validate" id="auto_url_validate" checked>
									<span class="wsc-switch__track" aria-hidden="true"></span>
									<span class="wsc-switch__body">
										<span class="wsc-switch__label"><?php esc_html_e( 'Validate URL format', 'wp-span-checker' ); ?></span>
									</span>
								</label>
								<label class="wsc-switch wsc-switch--compact">
									<input type="checkbox" class="wsc-switch__input" name="auto_url_webrisk" id="auto_url_webrisk">
									<span class="wsc-switch__track" aria-hidden="true"></span>
									<span class="wsc-switch__body">
										<span class="wsc-switch__label"><?php esc_html_e( 'Google Web Risk', 'wp-span-checker' ); ?></span>
									</span>
								</label>
								<label class="wsc-switch wsc-switch--compact">
									<input type="checkbox" class="wsc-switch__input" name="auto_url_virustotal" id="auto_url_virustotal">
									<span class="wsc-switch__track" aria-hidden="true"></span>
									<span class="wsc-switch__body">
										<span class="wsc-switch__label"><?php esc_html_e( 'VirusTotal', 'wp-span-checker' ); ?></span>
									</span>
								</label>
							</div>
						</fieldset>

						<!-- Textarea / Message Validation -->
						<fieldset class="wsc-validation-rule-fieldset">
							<legend class="wsc-rule-legend">
								<span class="dashicons dashicons-editor-paragraph"></span>
								<?php esc_html_e( 'Message / Textarea', 'wp-span-checker' ); ?>
								<code>textarea, name="message"</code>
							</legend>
							<div class="wsc-rule-options">
								<label class="wsc-switch wsc-switch--compact">
									<input type="checkbox" class="wsc-switch__input" name="auto_textarea_links" id="auto_textarea_links">
									<span class="wsc-switch__track" aria-hidden="true"></span>
									<span class="wsc-switch__body">
										<span class="wsc-switch__label"><?php esc_html_e( 'Block links in message', 'wp-span-checker' ); ?></span>
									</span>
								</label>
								<label class="wsc-switch wsc-switch--compact">
									<input type="checkbox" class="wsc-switch__input" name="auto_textarea_ai" id="auto_textarea_ai">
									<span class="wsc-switch__track" aria-hidden="true"></span>
									<span class="wsc-switch__body">
										<span class="wsc-switch__label"><?php esc_html_e( 'AI Spam Checker', 'wp-span-checker' ); ?></span>
									</span>
								</label>
							</div>
						</fieldset>

						<!-- Username Validation -->
						<fieldset class="wsc-validation-rule-fieldset">
							<legend class="wsc-rule-legend">
								<span class="dashicons dashicons-admin-users"></span>
								<?php esc_html_e( 'Username Fields', 'wp-span-checker' ); ?>
								<code>name="username", name="user_login"</code>
							</legend>
							<div class="wsc-rule-options">
								<label class="wsc-switch wsc-switch--compact">
									<input type="checkbox" class="wsc-switch__input" name="auto_username_exists" id="auto_username_exists">
									<span class="wsc-switch__track" aria-hidden="true"></span>
									<span class="wsc-switch__body">
										<span class="wsc-switch__label"><?php esc_html_e( 'Check if username exists', 'wp-span-checker' ); ?></span>
									</span>
								</label>
							</div>
						</fieldset>

						<!-- Password Validation -->
						<fieldset class="wsc-validation-rule-fieldset">
							<legend class="wsc-rule-legend">
								<span class="dashicons dashicons-lock"></span>
								<?php esc_html_e( 'Password Fields', 'wp-span-checker' ); ?>
								<code>type="password"</code>
							</legend>
							<div class="wsc-rule-options">
								<label class="wsc-switch wsc-switch--compact">
									<input type="checkbox" class="wsc-switch__input" name="auto_password_strength" id="auto_password_strength">
									<span class="wsc-switch__track" aria-hidden="true"></span>
									<span class="wsc-switch__body">
										<span class="wsc-switch__label"><?php esc_html_e( 'Require strong password', 'wp-span-checker' ); ?></span>
									</span>
								</label>
							</div>
						</fieldset>

						<!-- Text Fields -->
						<fieldset class="wsc-validation-rule-fieldset">
							<legend class="wsc-rule-legend">
								<span class="dashicons dashicons-editor-textcolor"></span>
								<?php esc_html_e( 'Text Fields', 'wp-span-checker' ); ?>
								<code>type="text"</code>
							</legend>
							<div class="wsc-rule-options">
								<label class="wsc-switch wsc-switch--compact">
									<input type="checkbox" class="wsc-switch__input" name="auto_text_no_urls" id="auto_text_no_urls">
									<span class="wsc-switch__track" aria-hidden="true"></span>
									<span class="wsc-switch__body">
										<span class="wsc-switch__label"><?php esc_html_e( 'Block URLs in text fields', 'wp-span-checker' ); ?></span>
									</span>
								</label>
							</div>
						</fieldset>
					</div>

					<p class="wsc-form-info-message wsc-text-info wsc-mt-4">
						<strong><?php esc_html_e( 'Auto-detected field names:', 'wp-span-checker' ); ?></strong>
						<?php esc_html_e( 'email, user_email, username, user_login, user_name, password, user_pass, message, comment, content, subject, name, first_name, last_name, phone, tel, url, website', 'wp-span-checker' ); ?>
					</p>
				</div>
			</div>

			<!-- Google reCAPTCHA -->
			<?php
			$recaptcha_config = get_option( 'wsc-recaptcha-config', array() );
			$has_recaptcha    = ! empty( $recaptcha_config['site_key'] ) && ! empty( $recaptcha_config['secret_key'] );
			?>
			<div class="wsc-form-section wsc-mt-4">
				<div class="wsc-form-row">
					<label class="wsc-switch">
						<input type="checkbox" class="wsc-switch__input" name="enable_recaptcha" id="wsc-enable-recaptcha" <?php echo $has_recaptcha ? '' : 'disabled'; ?>>
						<span class="wsc-switch__track" aria-hidden="true"></span>
						<span class="wsc-switch__body">
							<span class="wsc-switch__label">
								<?php esc_html_e( 'Enable Google reCAPTCHA', 'wp-span-checker' ); ?>
								<?php if ( $has_recaptcha ) : ?>
									<span class="wsc-badge wsc-badge--success"><?php echo esc_html( $recaptcha_config['version'] ?? 'v2' ); ?></span>
								<?php else : ?>
									<span class="wsc-badge wsc-badge--warning"><?php esc_html_e( 'Not configured', 'wp-span-checker' ); ?></span>
								<?php endif; ?>
							</span>
							<span class="wsc-switch__hint">
								<?php if ( $has_recaptcha ) : ?>
									<?php esc_html_e( 'Adds reCAPTCHA protection before form submission.', 'wp-span-checker' ); ?>
								<?php else : ?>
									<?php
									echo wp_kses(
										sprintf(
											/* translators: %s: URL to API settings */
											__( 'Please <a href="%s">configure reCAPTCHA API keys</a> first.', 'wp-span-checker' ),
											esc_url( admin_url( 'admin.php?page=wp-span-checker-api' ) )
										),
										array( 'a' => array( 'href' => true ) )
									);
									?>
								<?php endif; ?>
							</span>
						</span>
					</label>
				</div>
			</div>

			<!-- Manual Field Mapping (shown when Auto Validation is OFF) -->
			<div class="wsc-manual-validation-section wsc-hidden" id="wsc-manual-fields-section">
				<p class="wsc-form-info-message wsc-text-info wsc-mb-4"><?php esc_html_e( 'Add fields manually. You can identify fields by type attribute only, or specify ID/class for precise targeting.', 'wp-span-checker' ); ?></p>
				<div class="wsc-form-fields mb-4" id="wsc-form-fields"></div>
				<div class="wsc-form-group">
					<button type="button" class="wsc-btn wsc-btn-outline-primary" id="wscAddFormField"><?php esc_html_e( 'Add field', 'wp-span-checker' ); ?></button>
				</div>
			</div>
		</div>
		<div class="wsc-form-group">
			<button type="submit" class="wsc-btn wsc-btn-success wsc-flex wsc-items-center" id="saveFormSetting">
				<span><?php esc_html_e( 'Save mapping', 'wp-span-checker' ); ?></span>
				<span class="wsc-spinner wsc-hidden dashicons dashicons-admin-generic wsc-mr-4 wsc-text-success"></span>
			</button>
			<span class="wsc-form-error-message wsc-form-error wsc-block" id="wsc-form-error-message"></span>
		</div>
	</form>
	<div class="wsc-card wsc-card--table">
		<h2 class="wsc-card__title"><?php esc_html_e( 'Saved mappings', 'wp-span-checker' ); ?></h2>
		<div class="wsc-admin__table-wrap">
			<table id="form-setting-table" class="display nowrap" style="width:100%">
				<thead>
				<tr>
					<th><?php esc_html_e( 'ID', 'wp-span-checker' ); ?></th>
					<th><?php esc_html_e( 'Form type', 'wp-span-checker' ); ?></th>
					<th><?php esc_html_e( 'Page', 'wp-span-checker' ); ?></th>
					<th><?php esc_html_e( 'Form id/class', 'wp-span-checker' ); ?></th>
					<th><?php esc_html_e( 'Submit', 'wp-span-checker' ); ?></th>
					<th><?php esc_html_e( 'Mode', 'wp-span-checker' ); ?></th>
					<th class="wsc-min-w-300"><?php esc_html_e( 'Validation', 'wp-span-checker' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'wp-span-checker' ); ?></th>
				</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
	</div>
	<div id="wsc-regex-preset-modal" class="wsc-regex-modal-wrap wsc-hidden" aria-hidden="true">
		<div class="wsc-regex-modal-overlay"></div>
		<div class="wsc-regex-modal-panel wsc-card wsc-p-6">
			<div class="wsc-flex wsc-justify-between wsc-items-center wsc-mb-4">
				<strong><?php esc_html_e( 'Preset regex patterns', 'wp-span-checker' ); ?></strong>
				<button type="button" class="wsc-btn wsc-btn-outline-primary wsc-close-regex-modal" aria-label="<?php esc_attr_e( 'Close', 'wp-span-checker' ); ?>">&times;</button>
			</div>
			<p class="wsc-text-info wsc-mb-4"><?php esc_html_e( 'Each preset shows a valid and invalid example. Choose "Use pattern" to copy it into the custom regex field for the active row.', 'wp-span-checker' ); ?></p>
			<ul id="wsc-regex-preset-list" class="wsc-regex-preset-list" role="list"></ul>
		</div>
	</div>
</div>
