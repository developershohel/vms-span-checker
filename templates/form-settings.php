<?php

use WP_Span_Checker\Form_Settings;

$form_settings = new Form_Settings();
$settings      = $form_settings->get_settings();
?>

<div class="wsc-wrap" id="wsc-content-wrapper">
    <div class="wsc-control-group wsc-flex wsc-gap-12 wsc-items-center wsc-mb-8">
        <h1 class="wsc-h1 wsc-text-primary">Form Settings</h1>
        <button type="button" class="wsc-btn wsc-btn-outline-primary" id="wscAddFormSetting">Add form Setting</button>
    </div>

    <form method="post" class="wsc-form wsc-bg-white wsc-p-8 wsc-rounded-3xl wsc-hidden" id="wsc-settings-form">
        <input type="hidden" name="form_settings_id" id="form_settings_id" value="0">
        <span class="toggleFormField dashicons dashicons-no-alt"></span>
        <div class="wsc-form-content" id="wsc-form-content">
            <div class="wsc-form-group">
                <label for="form_type" class="wsc-form-label">Form Type</label>
                <select name="form_type" id="form_type" class="wsc-input wsc-input-primary" required="required">
                    <option value="login">Login</option>
                    <option value="register">Register</option>
                    <option value="contact">Contact</option>
                    <option value="comment">Comment</option>
                    <option value="newsletter">Newsletter</option>
                    <option value="custom">Custom</option>
                </select>
                <span class="wsc-form-error-message wsc-form-error"></span>
            </div>
            <div class="wsc-form-group">
                <label for="page_id" class="wsc-form-label">Select Page</label>
                <?php
                // Get all published pages
                $pages = get_pages( array(
                        'post_status' => 'publish',
                        'sort_column' => 'post_title',
                ) );

                if ( ! empty( $pages ) ) { ?>
                    <select name="page_id" id="page_id" class="wsc-input wsc-input-primary" required="required">
                        <option value="all-pages">All Pages</option>
                        <?php foreach ( $pages as $page ) :
                            $title = wp_strip_all_tags( $page->post_title ); // Remove HTML
                            $title = mb_strlen( $title ) > 70 ? mb_substr( $title, 0, 70 ) . '…' : $title; // Trim to 70 chars
                            ?>
                            <option value="<?php echo esc_attr( $page->ID ); ?>">
                                <?php echo esc_html( $title ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php } else {
                    echo '<input class="wsc-input wsc-input-primary" type="number" name="page_id" id="page_id" value="" required />';
                } ?>
                <span class="wsc-form-error-message wsc-form-error"></span>
            </div>
            <div class="wsc-form-group">
                <label for="form_id" class="wsc-form-label">Form ID</label>
                <input class="wsc-input wsc-input-primary" type="text" name="form_id" id="form_id" value=""
                       placeholder="login-form" required/>
                <span class="wsc-form-info-message wsc-text-info">Please don't use # in form id field only text</span>
                <span class="wsc-form-error-message wsc-form-error"></span>
            </div>
            <div class="wsc-form-group">
                <label for="form_class" class="wsc-form-label">Form Classes</label>
                <input class="wsc-input wsc-input-primary" type="text" name="form_class" id="form_class" value=""
                       placeholder="login-form wp-login-form"/>
                <span class="wsc-form-info-message wsc-text-info">Please don't use . in form class field only text and space for multiple class name</span>
                <span class="wsc-form-error-message wsc-form-error"></span>
            </div>
            <div class="wsc-form-fields mb-4" id="wsc-form-fields">
                <div class="wsc-form-group">
                    <label class="wsc-form-label" for="form-field-1">Form Field</label>
                    <select class="wsc-input wsc-input-primary form-field" id="form-field-1" name="form-field-1" data-id="1">
                        <option value="text">Text</option>
                        <option value="textarea">Textarea</option>
                        <option value="email">Email</option>
                        <option value="url">URL</option>
                        <option value="tel">Telephone</option>
                        <option value="number">Number</option>
                        <option value="password">Password</option>
                    </select>
                    <label for="form-id-1" class="wsc-form-label wsc-mt-4">Field ID</label>
                    <input id="form-id-1" type="text" class="wsc-input wsc-input-primary field-id"
                           name="form-field-id-1" data-id="1" placeholder="Field ID">
                    <label for="form-class-1" class="wsc-form-label wsc-mt-4">Field Class</label>
                    <input id="form-class-1" type="text" class="wsc-input wsc-input-primary field-class"
                           name="form-field-class-1" data-class="1" placeholder="Field class">
                    <label class="wsc-form-label wsc-mt-4" for="form-event-1">Javascript Event Type</label>
                    <select class="wsc-input wsc-input-primary form-event wsc-mt-4" id="form-event-1" name="form-event-1"
                            data-id="1">
                        <option value="change">Chance</option>
                        <option value="input">Input</option>
                        <option value="submit">Form Submit</option>
                    </select>
                    <div class="wsc-form-attr wsc-mt-4">
                        <p class="wsc-form-label">Is Required</p>
                        <div class="wsc-switch-control" id="wsc-required-status">
                            <span class="wsc-switch-option wsc-check">
                                <input type="radio" id="is_required-enable" name="is_required" value="1">
                                <label for="is_required-enable">Enable</label>
                            </span>
                            <span class="wsc-switch-option">
                                <input type="radio" id="is_required-disable" name="is_required" value="0" checked="checked">
                                <label for="is_required-disable">Disable</label>
                            </span>
                        </div>
                        <span class="wsc-form-info-message wsc-text-info">Please enable this option to make the field is required</span>
                    </div>
                    <div class="wsc-form-attr wsc-mt-4">
                        <p class="wsc-form-label">Require Validation</p>
                        <div class="wsc-switch-control" id="wsc-validation-status">
                            <span class="wsc-switch-option wsc-check">
                                <input type="radio" id="is_required-enable" name="is_required" value="1">
                                <label for="is_required-enable">Enable</label>
                            </span>
                            <span class="wsc-switch-option">
                                <input type="radio" id="is_required-disable" name="is_required" value="0" checked="checked">
                                <label for="is_required-disable">Disable</label>
                            </span>
                        </div>
                        <span class="wsc-form-info-message wsc-text-info">Please enable this option to validate data</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="wsc-form-group">
            <p class="wsc-form-label">Google Web Risk</p>
            <div class="wsc-switch-control" id="wsc-webrisk-status">
                <span class="wsc-switch-option wsc-check">
                    <input type="radio" id="is_webrisk-enable" name="is_webrisk" value="1" checked>
                    <label for="is_webrisk-enable">Enable</label>
                </span>

                <span class="wsc-switch-option">
                    <input type="radio" id="is_webrisk-disable" name="is_webrisk" value="0">
                    <label for="is_webrisk-disable">Disable</label>
                </span>
            </div>
            <span class="wsc-form-info-message wsc-text-info">Please enable this option to use Google Web Risk</span>
            <span class="wsc-form-error-message wsc-form-error"></span>
        </div>
        <div class="wsc-form-group">
            <p class="wsc-form-label">VirusTotal Scanner</p>
            <div class="wsc-switch-control" id="wsc-virustotal-status">
                <span class="wsc-switch-option">
                    <input type="radio" id="is_virustotal-enable" name="is_virustotal" value="1" />
                    <label for="is_virustotal-enable">Enable</label>
                </span>

                <span class="wsc-switch-option wsc-check">
                    <input type="radio" id="is_virustotal-disable" name="is_virustotal" value="0" checked />
                    <label for="is_virustotal-disable">Disable</label>
                </span>
            </div>
            <span class="wsc-form-info-message wsc-text-info">Please enable this option to use Virustotal Scan</span>
            <span class="wsc-form-error-message wsc-form-error"></span>
        </div>
        <div class="wsc-form-group">
            <button type="submit" class="wsc-btn wsc-btn-success wsc-flex wsc-items-center" id="saveFormSetting">
                <span>Save Setting</span>
                <span class="wsc-spinner wsc-hidden dashicons dashicons-admin-generic wsc-mr-4 wsc-text-success"></span>
            </button>
            <span class="wsc-form-error-message wsc-form-error wsc-block" id="wsc-form-error-message"></span>
        </div>
    </form>
    <table id="form-setting-table" class="display nowrap" style="min-width:320px; max-width: 750px; margin: 0 0 0;">
        <thead>
        <tr>
            <th>ID</th>
            <th>Form Type</th>
            <th>Page</th>
            <th>Form ID</th>
            <th>Form Class</th>
            <th class="wsc-min-w-300">Form Fields</th>
            <th>Web Risk Status</th>
            <th>Virustotal Status</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
