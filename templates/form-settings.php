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
				<label for="form_selector" class="wsc-form-label"><?php esc_html_e( 'Form id/class', 'wp-span-checker' ); ?></label>
				<input class="wsc-input wsc-input-primary" type="text" name="form_selector" id="form_selector" value=""
					placeholder="#contact-form.contact-us"
					required/>
				<span class="wsc-form-info-message wsc-text-info"><?php esc_html_e( 'CSS selector for the form element, e.g. #contactus.contact-us (ID plus classes). Old mappings that used separate Form ID + classes still load here.', 'wp-span-checker' ); ?></span>
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
			<p class="wsc-form-info-message wsc-text-info wsc-mb-4"><?php esc_html_e( 'Each front-end input needs its own row here (for example, 10 inputs → use “Add field” until you have 10 rows). Every row repeats the same validation block (required, server validation, regex) plus shared reputation toggles—Web Risk and VirusTotal run only for Email/URL rows when validation is on. On submit, each configured row is validated separately.', 'wp-span-checker' ); ?></p>
			<div class="wsc-form-fields mb-4" id="wsc-form-fields"></div>
			<div class="wsc-form-group">
				<button type="button" class="wsc-btn wsc-btn-outline-primary" id="wscAddFormField"><?php esc_html_e( 'Add field', 'wp-span-checker' ); ?></button>
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
					<th class="wsc-min-w-300"><?php esc_html_e( 'Form fields', 'wp-span-checker' ); ?></th>
					<th><?php esc_html_e( 'Web Risk (summary)', 'wp-span-checker' ); ?></th>
					<th><?php esc_html_e( 'VirusTotal (summary)', 'wp-span-checker' ); ?></th>
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
			<p class="wsc-text-info wsc-mb-4"><?php esc_html_e( 'Each preset shows a valid and invalid example. Choose “Use pattern” to copy it into the custom regex field for the active row.', 'wp-span-checker' ); ?></p>
			<ul id="wsc-regex-preset-list" class="wsc-regex-preset-list" role="list"></ul>
		</div>
	</div>
</div>
