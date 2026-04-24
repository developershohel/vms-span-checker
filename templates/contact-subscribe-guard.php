<?php
/**
 * Contact & Subscribe Guard settings screen.
 *
 * @package WP_Span_Checker
 */

use WP_Span_Checker\AI_Span_Config;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cfg = AI_Span_Config::get();

// phpcs:disable WordPress.Security.NonceVerification.Missing
if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] ) {
	if ( ! isset( $_POST['wsc_contact_subscribe_guard_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wsc_contact_subscribe_guard_nonce'] ) ), 'wsc_contact_subscribe_guard_save' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'wp-span-checker' ) );
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to save these settings.', 'wp-span-checker' ) );
	}

	$incoming = array(
		'contact_guard_page_id'    => isset( $_POST['contact_guard_page_id'] ) ? absint( $_POST['contact_guard_page_id'] ) : 0,
		'subscribe_guard_enabled'  => ! empty( $_POST['subscribe_guard_enabled'] ),
		'subscribe_guard_scope'    => isset( $_POST['subscribe_guard_scope'] ) ? sanitize_text_field( wp_unslash( $_POST['subscribe_guard_scope'] ) ) : 'site',
		'subscribe_guard_page_ids' => isset( $_POST['subscribe_guard_page_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['subscribe_guard_page_ids'] ) ) : '',
		'subscribe_guard_form_id'  => isset( $_POST['subscribe_guard_form_id'] ) ? sanitize_text_field( wp_unslash( $_POST['subscribe_guard_form_id'] ) ) : '',
	);

	AI_Span_Config::update( $incoming );
	$cfg = AI_Span_Config::get();
	echo '<div class="updated"><p>' . esc_html__( 'Contact & Subscribe Guard settings saved.', 'wp-span-checker' ) . '</p></div>';
}
// phpcs:enable WordPress.Security.NonceVerification.Missing
?>

<div class="wrap wsc-admin">
	<?php
	wp_span_checker_admin_page_header(
		__( 'Contact & Subscribe Guard', 'wp-span-checker' ),
		__( 'Configure contact page behavior used by comment restrictions, and newsletter/subscribe guard targeting.', 'wp-span-checker' )
	);
	?>

	<div class="wsc-card">
		<form method="post">
			<?php wp_nonce_field( 'wsc_contact_subscribe_guard_save', 'wsc_contact_subscribe_guard_nonce' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="contact_guard_page_id"><?php esc_html_e( 'Contact page', 'wp-span-checker' ); ?></label></th>
					<td>
						<?php
						wp_dropdown_pages(
							array(
								'name'              => 'contact_guard_page_id',
								'id'                => 'contact_guard_page_id',
								'selected'          => (int) ( $cfg['contact_guard_page_id'] ?? 0 ),
								'show_option_none'  => __( '— None —', 'wp-span-checker' ),
								'option_none_value' => '0',
							)
						);
						?>
						<p class="description"><?php esc_html_e( 'Used by comment restriction messages and site-ban flow as the destination page.', 'wp-span-checker' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable subscribe guard', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'subscribe_guard_enabled',
								'checked'     => ! empty( $cfg['subscribe_guard_enabled'] ),
								'description' => __( 'Enable newsletter/subscribe guard targeting options below.', 'wp-span-checker' ),
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="subscribe_guard_scope"><?php esc_html_e( 'Subscribe scope', 'wp-span-checker' ); ?></label></th>
					<td>
						<select name="subscribe_guard_scope" id="subscribe_guard_scope">
							<option value="site" <?php selected( (string) ( $cfg['subscribe_guard_scope'] ?? 'site' ), 'site' ); ?>><?php esc_html_e( 'Whole site', 'wp-span-checker' ); ?></option>
							<option value="specific" <?php selected( (string) ( $cfg['subscribe_guard_scope'] ?? 'site' ), 'specific' ); ?>><?php esc_html_e( 'Specific pages only', 'wp-span-checker' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="subscribe_guard_page_ids"><?php esc_html_e( 'Specific page IDs', 'wp-span-checker' ); ?></label></th>
					<td>
						<input type="text" name="subscribe_guard_page_ids" id="subscribe_guard_page_ids" class="regular-text" value="<?php echo esc_attr( (string) ( $cfg['subscribe_guard_page_ids'] ?? '' ) ); ?>" placeholder="12,34,56">
						<p class="description"><?php esc_html_e( 'Used when scope is "Specific pages only". Enter comma-separated page IDs.', 'wp-span-checker' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="subscribe_guard_form_id"><?php esc_html_e( 'Newsletter form ID/Class', 'wp-span-checker' ); ?></label></th>
					<td>
						<input type="text" name="subscribe_guard_form_id" id="subscribe_guard_form_id" class="regular-text" value="<?php echo esc_attr( (string) ( $cfg['subscribe_guard_form_id'] ?? '' ) ); ?>" placeholder="newsletter-form">
						<p class="description"><?php esc_html_e( 'Set the target subscribe form ID or class used by your plugin.', 'wp-span-checker' ); ?></p>
					</td>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save settings', 'wp-span-checker' ); ?>">
			</p>
		</form>
	</div>
</div>
