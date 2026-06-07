<?php
/**
 * Block User settings page.
 *
 * Variables below are received from the including admin handler scope.
 *
 * @package VMS_Elements_Form_Guard
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 */

use VMS_Elements_Form_Guard\AI_Span_Config;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cfg     = AI_Span_Config::get();
$updated = false;

// phpcs:disable WordPress.Security.NonceVerification.Missing
if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['vefg_block_user_save'] ) ) {
	if ( ! isset( $_POST['vefg_block_user_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vefg_block_user_nonce'] ) ), 'vefg_block_user_action' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'vms-elements-form-guard' ) );
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission.', 'vms-elements-form-guard' ) );
	}

	$new_cfg = array(
		'block_user_enabled'            => ! empty( $_POST['block_user_enabled'] ),
		'block_user_max_strikes'        => isset( $_POST['block_user_max_strikes'] ) ? absint( $_POST['block_user_max_strikes'] ) : 5,
		'block_user_login_block'        => ! empty( $_POST['block_user_login_block'] ),
		'block_user_strike_expiry_days' => isset( $_POST['block_user_strike_expiry_days'] ) ? absint( $_POST['block_user_strike_expiry_days'] ) : 30,
		'block_user_auto_logout'        => ! empty( $_POST['block_user_auto_logout'] ),
		'block_user_exempt_admins'      => ! empty( $_POST['block_user_exempt_admins'] ),
	);

	AI_Span_Config::update( $new_cfg );
	$cfg     = AI_Span_Config::get();
	$updated = true;
}
// phpcs:enable WordPress.Security.NonceVerification.Missing
?>

<div class="wrap vefg-admin">
	<?php
	vms_elements_form_guard_admin_page_header(
		__( 'Block User Settings', 'vms-elements-form-guard' ),
		__( 'Configure how users and guests are blocked after repeated spam attempts.', 'vms-elements-form-guard' )
	);
	?>

	<?php if ( $updated ) : ?>
		<div class="updated"><p><?php esc_html_e( 'Settings saved.', 'vms-elements-form-guard' ); ?></p></div>
	<?php endif; ?>

	<form method="post" class="vefg-card" style="max-width: 800px;">
		<?php wp_nonce_field( 'vefg_block_user_action', 'vefg_block_user_nonce' ); ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable Block User', 'vms-elements-form-guard' ); ?></th>
				<td>
					<?php
					vms_elements_form_guard_admin_switch(
						array(
							'name'        => 'block_user_enabled',
							'checked'     => ! empty( $cfg['block_user_enabled'] ),
							'label'       => __( 'Track strikes and block repeat offenders', 'vms-elements-form-guard' ),
							'description' => __( 'When enabled, spam attempts will accumulate strikes against users/guests.', 'vms-elements-form-guard' ),
						)
					);
					?>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="block_user_max_strikes"><?php esc_html_e( 'Max Strikes Before Block', 'vms-elements-form-guard' ); ?></label>
				</th>
				<td>
					<input type="number" name="block_user_max_strikes" id="block_user_max_strikes" 
						value="<?php echo esc_attr( (string) (int) ( $cfg['block_user_max_strikes'] ?? 5 ) ); ?>" 
						min="1" max="100" class="small-text">
					<p class="description">
						<?php esc_html_e( 'After this many spam attempts, the user/guest will be blocked from submitting forms.', 'vms-elements-form-guard' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Block Login', 'vms-elements-form-guard' ); ?></th>
				<td>
					<?php
					vms_elements_form_guard_admin_switch(
						array(
							'name'        => 'block_user_login_block',
							'checked'     => ! empty( $cfg['block_user_login_block'] ),
							'label'       => __( 'Block users from logging in when max strikes reached', 'vms-elements-form-guard' ),
							'description' => __( 'Blocked users will see an error when trying to log in until they are unblocked or strikes expire.', 'vms-elements-form-guard' ),
						)
					);
					?>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Auto Logout', 'vms-elements-form-guard' ); ?></th>
				<td>
					<?php
					vms_elements_form_guard_admin_switch(
						array(
							'name'        => 'block_user_auto_logout',
							'checked'     => ! empty( $cfg['block_user_auto_logout'] ),
							'label'       => __( 'Automatically log out users when blocked', 'vms-elements-form-guard' ),
							'description' => __( 'When a logged-in user reaches max strikes, they will be immediately logged out.', 'vms-elements-form-guard' ),
						)
					);
					?>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="block_user_strike_expiry_days"><?php esc_html_e( 'Strike Expiry', 'vms-elements-form-guard' ); ?></label>
				</th>
				<td>
					<input type="number" name="block_user_strike_expiry_days" id="block_user_strike_expiry_days" 
						value="<?php echo esc_attr( (string) (int) ( $cfg['block_user_strike_expiry_days'] ?? 30 ) ); ?>" 
						min="0" max="365" class="small-text">
					<?php esc_html_e( 'days', 'vms-elements-form-guard' ); ?>
					<p class="description">
						<?php esc_html_e( 'Strikes will automatically reset after this many days. Set to 0 for never (permanent until manually unblocked).', 'vms-elements-form-guard' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Exempt Administrators', 'vms-elements-form-guard' ); ?></th>
				<td>
					<?php
					vms_elements_form_guard_admin_switch(
						array(
							'name'        => 'block_user_exempt_admins',
							'checked'     => ! empty( $cfg['block_user_exempt_admins'] ),
							'label'       => __( 'Do not count strikes for administrators', 'vms-elements-form-guard' ),
							'description' => __( 'Admins can test the plugin without being blocked. Recommended to keep enabled.', 'vms-elements-form-guard' ),
						)
					);
					?>
				</td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" name="vefg_block_user_save" value="1" class="button button-primary">
				<?php esc_html_e( 'Save Settings', 'vms-elements-form-guard' ); ?>
			</button>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=vefg-comment-blocks' ) ); ?>" class="button">
				<?php esc_html_e( 'View Blocked Users', 'vms-elements-form-guard' ); ?>
			</a>
		</p>
	</form>

	<div class="vefg-card" style="max-width: 800px; margin-top: 20px;">
		<h3><?php esc_html_e( 'How It Works', 'vms-elements-form-guard' ); ?></h3>
		<ol>
			<li><?php esc_html_e( 'When spam is detected (Form Guard validation, Comment spam, etc.), the user/guest receives a strike.', 'vms-elements-form-guard' ); ?></li>
			<li><?php esc_html_e( 'Strikes accumulate until the max is reached.', 'vms-elements-form-guard' ); ?></li>
			<li><?php esc_html_e( 'Once blocked, users cannot submit forms and optionally cannot log in.', 'vms-elements-form-guard' ); ?></li>
			<li><?php esc_html_e( 'Strikes expire after the configured number of days, or can be manually reset.', 'vms-elements-form-guard' ); ?></li>
			<li><?php esc_html_e( 'Administrators are exempt from strikes when the option is enabled.', 'vms-elements-form-guard' ); ?></li>
		</ol>
	</div>
</div>
