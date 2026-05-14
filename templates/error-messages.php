<?php
/**
 * Error Messages Settings Template
 *
 * @package WP_Span_Checker
 */

defined( 'ABSPATH' ) || exit;

$option_key = 'wsc-error-messages';
$saved      = get_option( $option_key, array() );

$defaults = wp_span_checker_get_default_error_messages();

if ( isset( $_POST['wsc_error_messages_nonce'] ) && wp_verify_nonce( $_POST['wsc_error_messages_nonce'], 'wsc_save_error_messages' ) ) {
	$fields = array(
		// Registration Guard Messages
		'reg_blocked_title',
		'reg_blocked_intro',
		'reg_dns_failed',
		'reg_mx_failed',
		'reg_disposable',
		'reg_rate_limit',
		'reg_reputation_failed',
		'reg_rate_limit_count',
		'reg_contact_admin',
		
		// Email Validation Messages
		'email_invalid_format',
		'email_dns_failed',
		'email_mx_failed',
		'email_disposable',
		'email_webrisk_flagged',
		'email_virustotal_flagged',
		
		// URL Validation Messages
		'url_invalid',
		'url_dns_failed',
		'url_webrisk_flagged',
		'url_virustotal_flagged',
		
		// Spam Detection Messages
		'spam_detected',
		
		// Username Validation Messages
		'username_taken',
		
		// reCAPTCHA Messages
		'recaptcha_required',
		'recaptcha_failed',
		
		// General Messages
		'user_blocked',
		'validation_failed',
		'field_required',
		'server_error',
	);
	
	$new_saved = array();
	foreach ( $fields as $field ) {
		$value = isset( $_POST[ $field ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $field ] ) ) : '';
		$default_val = isset( $defaults[ $field ] ) ? $defaults[ $field ] : '';
		// If value equals default, save empty to use the default system
		if ( trim( $value ) === trim( $default_val ) ) {
			$value = '';
		}
		$new_saved[ $field ] = $value;
	}
	
	update_option( $option_key, $new_saved );
	$saved = $new_saved;
	echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Error messages saved.', 'wp-span-checker' ) . '</p></div>';
}

function wsc_msg_field( $key, $saved, $defaults, $label, $description = '' ) {
	$value      = isset( $saved[ $key ] ) && '' !== $saved[ $key ] ? $saved[ $key ] : '';
	$default    = isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
	$show_value = '' !== $value ? $value : $default;
	$is_custom  = '' !== $value;
	?>
	<tr>
		<th scope="row">
			<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
		</th>
		<td>
			<textarea name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" rows="4" class="large-text wsc-msg-field" data-default="<?php echo esc_attr( $default ); ?>"><?php echo esc_textarea( $show_value ); ?></textarea>
			<p class="description">
				<?php if ( $description ) : ?>
					<?php echo esc_html( $description ); ?><br>
				<?php endif; ?>
				<?php if ( $is_custom ) : ?>
					<span class="wsc-custom-badge" style="background: #2271b1; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 11px;"><?php esc_html_e( 'Custom', 'wp-span-checker' ); ?></span>
					<a href="#" class="wsc-reset-single" data-field="<?php echo esc_attr( $key ); ?>" style="margin-left: 8px;"><?php esc_html_e( 'Reset to default', 'wp-span-checker' ); ?></a>
				<?php else : ?>
					<a href="#" class="wsc-reset-single wsc-default-badge" data-field="<?php echo esc_attr( $key ); ?>" style="background: #ddd; color: #50575e; padding: 2px 6px; border-radius: 3px; font-size: 11px; text-decoration: none; cursor: pointer;" title="<?php esc_attr_e( 'Click to reset to default', 'wp-span-checker' ); ?>"><?php esc_html_e( 'Default', 'wp-span-checker' ); ?></a>
				<?php endif; ?>
			</p>
		</td>
	</tr>
	<?php
}
?>

<div class="wrap wsc-wrap">
	<h1><?php esc_html_e( 'Error Messages', 'wp-span-checker' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Customize validation error messages shown to users. Leave empty to use the professional default message.', 'wp-span-checker' ); ?></p>

	<form method="post" action="">
		<?php wp_nonce_field( 'wsc_save_error_messages', 'wsc_error_messages_nonce' ); ?>

		<!-- Registration Guard Messages -->
		<h2 class="title"><?php esc_html_e( 'Registration Guard Messages', 'wp-span-checker' ); ?></h2>
		<table class="form-table" role="presentation">
			<?php
			wsc_msg_field( 'reg_blocked_title', $saved, $defaults, __( 'Blocked Title', 'wp-span-checker' ) );
			wsc_msg_field( 'reg_blocked_intro', $saved, $defaults, __( 'Blocked Introduction', 'wp-span-checker' ) );
			wsc_msg_field( 'reg_dns_failed', $saved, $defaults, __( 'DNS Check Failed', 'wp-span-checker' ) );
			wsc_msg_field( 'reg_mx_failed', $saved, $defaults, __( 'MX Check Failed', 'wp-span-checker' ) );
			wsc_msg_field( 'reg_disposable', $saved, $defaults, __( 'Disposable Email', 'wp-span-checker' ) );
			wsc_msg_field( 'reg_rate_limit', $saved, $defaults, __( 'Rate Limit Reached', 'wp-span-checker' ) );
			wsc_msg_field( 'reg_reputation_failed', $saved, $defaults, __( 'Reputation Check Failed', 'wp-span-checker' ) );
			wsc_msg_field( 'reg_rate_limit_count', $saved, $defaults, __( 'Rate Limit Count', 'wp-span-checker' ), __( 'Use %1$d for current attempts and %2$d for maximum.', 'wp-span-checker' ) );
			wsc_msg_field( 'reg_contact_admin', $saved, $defaults, __( 'Contact Admin Message', 'wp-span-checker' ) );
			?>
		</table>

		<!-- Email Validation Messages -->
		<h2 class="title"><?php esc_html_e( 'Email Validation Messages', 'wp-span-checker' ); ?></h2>
		<table class="form-table" role="presentation">
			<?php
			wsc_msg_field( 'email_invalid_format', $saved, $defaults, __( 'Invalid Email Format', 'wp-span-checker' ) );
			wsc_msg_field( 'email_dns_failed', $saved, $defaults, __( 'Domain Not Found', 'wp-span-checker' ) );
			wsc_msg_field( 'email_mx_failed', $saved, $defaults, __( 'No Mail Server', 'wp-span-checker' ) );
			wsc_msg_field( 'email_disposable', $saved, $defaults, __( 'Disposable Email', 'wp-span-checker' ) );
			wsc_msg_field( 'email_webrisk_flagged', $saved, $defaults, __( 'Web Risk Flagged', 'wp-span-checker' ) );
			wsc_msg_field( 'email_virustotal_flagged', $saved, $defaults, __( 'VirusTotal Flagged', 'wp-span-checker' ) );
			?>
		</table>

		<!-- URL Validation Messages -->
		<h2 class="title"><?php esc_html_e( 'URL Validation Messages', 'wp-span-checker' ); ?></h2>
		<table class="form-table" role="presentation">
			<?php
			wsc_msg_field( 'url_invalid', $saved, $defaults, __( 'Invalid URL', 'wp-span-checker' ) );
			wsc_msg_field( 'url_dns_failed', $saved, $defaults, __( 'Domain Not Reachable', 'wp-span-checker' ) );
			wsc_msg_field( 'url_webrisk_flagged', $saved, $defaults, __( 'Web Risk Flagged', 'wp-span-checker' ) );
			wsc_msg_field( 'url_virustotal_flagged', $saved, $defaults, __( 'VirusTotal Flagged', 'wp-span-checker' ) );
			?>
		</table>

		<!-- Content & Spam Messages -->
		<h2 class="title"><?php esc_html_e( 'Content & Spam Messages', 'wp-span-checker' ); ?></h2>
		<table class="form-table" role="presentation">
			<?php
			wsc_msg_field( 'spam_detected', $saved, $defaults, __( 'Spam Detected', 'wp-span-checker' ) );
			wsc_msg_field( 'username_taken', $saved, $defaults, __( 'Username Taken', 'wp-span-checker' ) );
			?>
		</table>

		<!-- reCAPTCHA Messages -->
		<h2 class="title"><?php esc_html_e( 'reCAPTCHA Messages', 'wp-span-checker' ); ?></h2>
		<table class="form-table" role="presentation">
			<?php
			wsc_msg_field( 'recaptcha_required', $saved, $defaults, __( 'reCAPTCHA Required', 'wp-span-checker' ) );
			wsc_msg_field( 'recaptcha_failed', $saved, $defaults, __( 'reCAPTCHA Failed', 'wp-span-checker' ) );
			?>
		</table>

		<!-- General Messages -->
		<h2 class="title"><?php esc_html_e( 'General Messages', 'wp-span-checker' ); ?></h2>
		<table class="form-table" role="presentation">
			<?php
			wsc_msg_field( 'user_blocked', $saved, $defaults, __( 'User Blocked', 'wp-span-checker' ) );
			wsc_msg_field( 'validation_failed', $saved, $defaults, __( 'Validation Failed', 'wp-span-checker' ) );
			wsc_msg_field( 'field_required', $saved, $defaults, __( 'Field Required', 'wp-span-checker' ) );
			wsc_msg_field( 'server_error', $saved, $defaults, __( 'Server Error', 'wp-span-checker' ) );
			?>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Messages', 'wp-span-checker' ); ?></button>
			<button type="button" class="button" id="wsc-reset-defaults"><?php esc_html_e( 'Reset to Defaults', 'wp-span-checker' ); ?></button>
		</p>
	</form>
</div>

<script>
jQuery(function($) {
	// Reset all to defaults
	$('#wsc-reset-defaults').on('click', function() {
		if (confirm('<?php echo esc_js( __( 'Reset all messages to defaults? This will restore all default messages.', 'wp-span-checker' ) ); ?>')) {
			$('.wsc-msg-field').each(function() {
				var $field = $(this);
				var defaultVal = $field.data('default') || '';
				$field.val(defaultVal);
			});
		}
	});
	
	// Reset single field to default (works for both Custom badge's "Reset to default" link and Default badge click)
	$(document).on('click', '.wsc-reset-single', function(e) {
		e.preventDefault();
		var fieldId = $(this).data('field');
		var $field = $('#' + fieldId);
		var defaultVal = $field.data('default') || '';
		$field.val(defaultVal);
		
		// Update the badge display - replace custom badge with default badge
		var $desc = $(this).closest('p.description');
		$desc.find('.wsc-custom-badge').remove();
		$desc.find('a.wsc-reset-single:not(.wsc-default-badge)').remove();
		
		// If no default badge exists, add one
		if (!$desc.find('.wsc-default-badge').length) {
			$desc.append('<a href="#" class="wsc-reset-single wsc-default-badge" data-field="' + fieldId + '" style="background: #ddd; color: #50575e; padding: 2px 6px; border-radius: 3px; font-size: 11px; text-decoration: none; cursor: pointer;" title="<?php echo esc_js( __( 'Click to reset to default', 'wp-span-checker' ) ); ?>"><?php echo esc_js( __( 'Default', 'wp-span-checker' ) ); ?></a>');
		}
	});
	
	// Track changes to show Custom badge when user edits a Default field
	$(document).on('input change', '.wsc-msg-field', function() {
		var $field = $(this);
		var fieldId = $field.attr('id');
		var defaultVal = $field.data('default') || '';
		var currentVal = $field.val();
		var $desc = $field.closest('td').find('p.description');
		
		if (currentVal !== defaultVal) {
			// Value is different from default - show Custom badge
			if (!$desc.find('.wsc-custom-badge').length) {
				$desc.find('.wsc-default-badge').remove();
				$desc.find('a.wsc-reset-single').remove();
				$desc.append('<span class="wsc-custom-badge" style="background: #2271b1; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 11px;"><?php echo esc_js( __( 'Custom', 'wp-span-checker' ) ); ?></span> ');
				$desc.append('<a href="#" class="wsc-reset-single" data-field="' + fieldId + '" style="margin-left: 8px;"><?php echo esc_js( __( 'Reset to default', 'wp-span-checker' ) ); ?></a>');
			}
		} else {
			// Value matches default - show Default badge
			$desc.find('.wsc-custom-badge').remove();
			$desc.find('a.wsc-reset-single:not(.wsc-default-badge)').remove();
			if (!$desc.find('.wsc-default-badge').length) {
				$desc.append('<a href="#" class="wsc-reset-single wsc-default-badge" data-field="' + fieldId + '" style="background: #ddd; color: #50575e; padding: 2px 6px; border-radius: 3px; font-size: 11px; text-decoration: none; cursor: pointer;" title="<?php echo esc_js( __( 'Click to reset to default', 'wp-span-checker' ) ); ?>"><?php echo esc_js( __( 'Default', 'wp-span-checker' ) ); ?></a>');
			}
		}
	});
});
</script>
