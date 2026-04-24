<?php
/**
 * Form Guard — map front-end forms to validation rules.
 *
 * @package WP_Span_Checker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wsc_pages  = get_pages(
	array(
		'post_status' => 'publish',
		'sort_column' => 'post_title',
	)
);
$wsc_posts  = get_posts(
	array(
		'post_type'              => 'post',
		'post_status'            => 'publish',
		'numberposts'            => 300,
		'orderby'                => 'title',
		'order'                  => 'ASC',
		'no_found_rows'          => true,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
	)
);
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
					<div class="wsc-target-panel">
						<strong class="wsc-target-panel__title"><?php esc_html_e( 'Common locations', 'wp-span-checker' ); ?></strong>
						<ul class="wsc-target-presets" role="list">
							<?php foreach ( $wsc_presets as $slug => $label ) : ?>
								<li>
									<label class="wsc-target-check wsc-switch wsc-switch--compact">
										<input type="checkbox" class="wsc-switch__input wsc-page-target" name="wsc_page_target[]" value="<?php echo esc_attr( $slug ); ?>">
										<span class="wsc-switch__track" aria-hidden="true"></span>
										<span class="wsc-switch__body">
											<span class="wsc-switch__label"><?php echo esc_html( $label ); ?></span>
										</span>
									</label>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
					<div class="wsc-target-panel">
						<label for="wsc-target-pages" class="wsc-target-panel__title"><?php esc_html_e( 'Specific pages', 'wp-span-checker' ); ?></label>
						<select id="wsc-target-pages" class="wsc-input wsc-input-primary wsc-target-multiselect" multiple size="8">
							<?php foreach ( $wsc_pages as $page ) : ?>
								<?php
								$ptitle = wp_strip_all_tags( $page->post_title );
								if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) && mb_strlen( $ptitle ) > 80 ) {
									$ptitle = mb_substr( $ptitle, 0, 80 ) . __( '…', 'wp-span-checker' );
								}
								?>
								<option value="<?php echo esc_attr( (string) $page->ID ); ?>"><?php echo esc_html( $ptitle ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="wsc-target-panel">
						<label for="wsc-target-posts" class="wsc-target-panel__title"><?php esc_html_e( 'Specific posts', 'wp-span-checker' ); ?></label>
						<select id="wsc-target-posts" class="wsc-input wsc-input-primary wsc-target-multiselect" multiple size="8">
							<?php foreach ( $wsc_posts as $post_row ) : ?>
								<?php
								$ptitle = wp_strip_all_tags( $post_row->post_title );
								if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) && mb_strlen( $ptitle ) > 80 ) {
									$ptitle = mb_substr( $ptitle, 0, 80 ) . __( '…', 'wp-span-checker' );
								}
								?>
								<option value="<?php echo esc_attr( (string) $post_row->ID ); ?>"><?php echo esc_html( $ptitle ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
				<span class="wsc-form-error-message wsc-form-error"></span>
			</div>
			<div class="wsc-form-group">
				<label for="form_id" class="wsc-form-label"><?php esc_html_e( 'Form ID', 'wp-span-checker' ); ?></label>
				<input class="wsc-input wsc-input-primary" type="text" name="form_id" id="form_id" value=""
					placeholder="<?php esc_attr_e( 'login-form', 'wp-span-checker' ); ?>" required/>
				<span class="wsc-form-info-message wsc-text-info"><?php esc_html_e( 'Do not include “#” in the form ID—use letters, numbers, and hyphens only.', 'wp-span-checker' ); ?></span>
				<span class="wsc-form-error-message wsc-form-error"></span>
			</div>
			<div class="wsc-form-group">
				<label for="form_class" class="wsc-form-label"><?php esc_html_e( 'Form classes', 'wp-span-checker' ); ?></label>
				<input class="wsc-input wsc-input-primary" type="text" name="form_class" id="form_class" value=""
					placeholder="<?php esc_attr_e( 'login-form wp-login-form', 'wp-span-checker' ); ?>"/>
				<span class="wsc-form-info-message wsc-text-info"><?php esc_html_e( 'Do not include leading dots—use space-separated class names.', 'wp-span-checker' ); ?></span>
				<span class="wsc-form-error-message wsc-form-error"></span>
			</div>
			<div class="wsc-form-fields mb-4" id="wsc-form-fields">
				<div class="wsc-form-group">
					<label class="wsc-form-label" for="form-field-1"><?php esc_html_e( 'Form field', 'wp-span-checker' ); ?></label>
					<select class="wsc-input wsc-input-primary form-field" id="form-field-1" name="form-field-1" data-id="1">
						<option value="text"><?php esc_html_e( 'Text', 'wp-span-checker' ); ?></option>
						<option value="textarea"><?php esc_html_e( 'Textarea', 'wp-span-checker' ); ?></option>
						<option value="email"><?php esc_html_e( 'Email', 'wp-span-checker' ); ?></option>
						<option value="url"><?php esc_html_e( 'URL', 'wp-span-checker' ); ?></option>
						<option value="tel"><?php esc_html_e( 'Telephone', 'wp-span-checker' ); ?></option>
						<option value="number"><?php esc_html_e( 'Number', 'wp-span-checker' ); ?></option>
						<option value="password"><?php esc_html_e( 'Password', 'wp-span-checker' ); ?></option>
					</select>
					<label for="form-id-1" class="wsc-form-label wsc-mt-4"><?php esc_html_e( 'Field ID', 'wp-span-checker' ); ?></label>
					<input id="form-id-1" type="text" class="wsc-input wsc-input-primary field-id"
						name="form-field-id-1" data-id="1" placeholder="<?php esc_attr_e( 'Field ID', 'wp-span-checker' ); ?>">
					<label for="form-class-1" class="wsc-form-label wsc-mt-4"><?php esc_html_e( 'Field class', 'wp-span-checker' ); ?></label>
					<input id="form-class-1" type="text" class="wsc-input wsc-input-primary field-class"
						name="form-field-class-1" data-class="1" placeholder="<?php esc_attr_e( 'Field class', 'wp-span-checker' ); ?>">
					<label class="wsc-form-label wsc-mt-4" for="form-event-1"><?php esc_html_e( 'JavaScript event', 'wp-span-checker' ); ?></label>
					<select class="wsc-input wsc-input-primary form-event wsc-mt-4" id="form-event-1" name="form-event-1" data-id="1">
						<option value="change"><?php esc_html_e( 'Change', 'wp-span-checker' ); ?></option>
						<option value="input"><?php esc_html_e( 'Input', 'wp-span-checker' ); ?></option>
						<option value="submit"><?php esc_html_e( 'Form submit', 'wp-span-checker' ); ?></option>
					</select>
					<div class="wsc-form-attr wsc-mt-4">
						<p class="wsc-form-label"><?php esc_html_e( 'Required field', 'wp-span-checker' ); ?></p>
						<div class="wsc-switch-control" id="wsc-required-status">
							<span class="wsc-switch-option wsc-check">
								<input type="radio" id="is_required-enable" name="is_required" value="1">
								<label for="is_required-enable"><?php esc_html_e( 'Enable', 'wp-span-checker' ); ?></label>
							</span>
							<span class="wsc-switch-option">
								<input type="radio" id="is_required-disable" name="is_required" value="0" checked="checked">
								<label for="is_required-disable"><?php esc_html_e( 'Disable', 'wp-span-checker' ); ?></label>
							</span>
						</div>
						<span class="wsc-form-info-message wsc-text-info"><?php esc_html_e( 'Mark the field as required in the browser.', 'wp-span-checker' ); ?></span>
					</div>
					<div class="wsc-form-attr wsc-mt-4">
						<p class="wsc-form-label"><?php esc_html_e( 'Require validation', 'wp-span-checker' ); ?></p>
						<div class="wsc-switch-control" id="wsc-validation-status">
							<span class="wsc-switch-option wsc-check">
								<input type="radio" id="is_validate-enable" name="is_validate" value="1">
								<label for="is_validate-enable"><?php esc_html_e( 'Enable', 'wp-span-checker' ); ?></label>
							</span>
							<span class="wsc-switch-option">
								<input type="radio" id="is_validate-disable" name="is_validate" value="0" checked="checked">
								<label for="is_validate-disable"><?php esc_html_e( 'Disable', 'wp-span-checker' ); ?></label>
							</span>
						</div>
						<span class="wsc-form-info-message wsc-text-info"><?php esc_html_e( 'Run server-side validation for this field.', 'wp-span-checker' ); ?></span>
					</div>
				</div>
			</div>
		</div>
		<div class="wsc-form-group">
			<p class="wsc-form-label"><?php esc_html_e( 'Google Web Risk', 'wp-span-checker' ); ?></p>
			<div class="wsc-switch-control" id="wsc-webrisk-status">
				<span class="wsc-switch-option wsc-check">
					<input type="radio" id="is_webrisk-enable" name="is_webrisk" value="1" checked>
					<label for="is_webrisk-enable"><?php esc_html_e( 'Enable', 'wp-span-checker' ); ?></label>
				</span>
				<span class="wsc-switch-option">
					<input type="radio" id="is_webrisk-disable" name="is_webrisk" value="0">
					<label for="is_webrisk-disable"><?php esc_html_e( 'Disable', 'wp-span-checker' ); ?></label>
				</span>
			</div>
			<span class="wsc-form-info-message wsc-text-info"><?php esc_html_e( 'Call Google Web Risk when validating this form.', 'wp-span-checker' ); ?></span>
			<span class="wsc-form-error-message wsc-form-error"></span>
		</div>
		<div class="wsc-form-group">
			<p class="wsc-form-label"><?php esc_html_e( 'VirusTotal scanner', 'wp-span-checker' ); ?></p>
			<div class="wsc-switch-control" id="wsc-virustotal-status">
				<span class="wsc-switch-option">
					<input type="radio" id="is_virustotal-enable" name="is_virustotal" value="1" />
					<label for="is_virustotal-enable"><?php esc_html_e( 'Enable', 'wp-span-checker' ); ?></label>
				</span>
				<span class="wsc-switch-option wsc-check">
					<input type="radio" id="is_virustotal-disable" name="is_virustotal" value="0" checked />
					<label for="is_virustotal-disable"><?php esc_html_e( 'Disable', 'wp-span-checker' ); ?></label>
				</span>
			</div>
			<span class="wsc-form-info-message wsc-text-info"><?php esc_html_e( 'Query VirusTotal when validating this form.', 'wp-span-checker' ); ?></span>
			<span class="wsc-form-error-message wsc-form-error"></span>
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
					<th><?php esc_html_e( 'Form ID', 'wp-span-checker' ); ?></th>
					<th><?php esc_html_e( 'Form class', 'wp-span-checker' ); ?></th>
					<th class="wsc-min-w-300"><?php esc_html_e( 'Form fields', 'wp-span-checker' ); ?></th>
					<th><?php esc_html_e( 'Web Risk', 'wp-span-checker' ); ?></th>
					<th><?php esc_html_e( 'VirusTotal', 'wp-span-checker' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'wp-span-checker' ); ?></th>
				</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
	</div>
</div>
