<?php
/**
 * Block User settings page.
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
if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['wsc_block_user_save'] ) ) {
	if ( ! isset( $_POST['wsc_block_user_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wsc_block_user_nonce'] ) ), 'wsc_block_user_action' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'wp-span-checker' ) );
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission.', 'wp-span-checker' ) );
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

<div class="wrap wsc-admin">
	<?php
	wp_span_checker_admin_page_header(
		__( 'Block User Settings', 'wp-span-checker' ),
		__( 'Configure how users and guests are blocked after repeated spam attempts.', 'wp-span-checker' )
	);
	?>

	<?php if ( $updated ) : ?>
		<div class="updated"><p><?php esc_html_e( 'Settings saved.', 'wp-span-checker' ); ?></p></div>
	<?php endif; ?>

	<form method="post" class="wsc-card" style="max-width: 800px;">
		<?php wp_nonce_field( 'wsc_block_user_action', 'wsc_block_user_nonce' ); ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable Block User', 'wp-span-checker' ); ?></th>
				<td>
					<?php
					wp_span_checker_admin_switch(
						array(
							'name'        => 'block_user_enabled',
							'checked'     => ! empty( $cfg['block_user_enabled'] ),
							'label'       => __( 'Track strikes and block repeat offenders', 'wp-span-checker' ),
							'description' => __( 'When enabled, spam attempts will accumulate strikes against users/guests.', 'wp-span-checker' ),
						)
					);
					?>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="block_user_max_strikes"><?php esc_html_e( 'Max Strikes Before Block', 'wp-span-checker' ); ?></label>
				</th>
				<td>
					<input type="number" name="block_user_max_strikes" id="block_user_max_strikes" 
						value="<?php echo esc_attr( (string) (int) ( $cfg['block_user_max_strikes'] ?? 5 ) ); ?>" 
						min="1" max="100" class="small-text">
					<p class="description">
						<?php esc_html_e( 'After this many spam attempts, the user/guest will be blocked from submitting forms.', 'wp-span-checker' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Block Login', 'wp-span-checker' ); ?></th>
				<td>
					<?php
					wp_span_checker_admin_switch(
						array(
							'name'        => 'block_user_login_block',
							'checked'     => ! empty( $cfg['block_user_login_block'] ),
							'label'       => __( 'Block users from logging in when max strikes reached', 'wp-span-checker' ),
							'description' => __( 'Blocked users will see an error when trying to log in until they are unblocked or strikes expire.', 'wp-span-checker' ),
						)
					);
					?>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Auto Logout', 'wp-span-checker' ); ?></th>
				<td>
					<?php
					wp_span_checker_admin_switch(
						array(
							'name'        => 'block_user_auto_logout',
							'checked'     => ! empty( $cfg['block_user_auto_logout'] ),
							'label'       => __( 'Automatically log out users when blocked', 'wp-span-checker' ),
							'description' => __( 'When a logged-in user reaches max strikes, they will be immediately logged out.', 'wp-span-checker' ),
						)
					);
					?>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="block_user_strike_expiry_days"><?php esc_html_e( 'Strike Expiry', 'wp-span-checker' ); ?></label>
				</th>
				<td>
					<input type="number" name="block_user_strike_expiry_days" id="block_user_strike_expiry_days" 
						value="<?php echo esc_attr( (string) (int) ( $cfg['block_user_strike_expiry_days'] ?? 30 ) ); ?>" 
						min="0" max="365" class="small-text">
					<?php esc_html_e( 'days', 'wp-span-checker' ); ?>
					<p class="description">
						<?php esc_html_e( 'Strikes will automatically reset after this many days. Set to 0 for never (permanent until manually unblocked).', 'wp-span-checker' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Exempt Administrators', 'wp-span-checker' ); ?></th>
				<td>
					<?php
					wp_span_checker_admin_switch(
						array(
							'name'        => 'block_user_exempt_admins',
							'checked'     => ! empty( $cfg['block_user_exempt_admins'] ),
							'label'       => __( 'Do not count strikes for administrators', 'wp-span-checker' ),
							'description' => __( 'Admins can test the plugin without being blocked. Recommended to keep enabled.', 'wp-span-checker' ),
						)
					);
					?>
				</td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" name="wsc_block_user_save" value="1" class="button button-primary">
				<?php esc_html_e( 'Save Settings', 'wp-span-checker' ); ?>
			</button>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wsc-comment-blocks' ) ); ?>" class="button">
				<?php esc_html_e( 'View Blocked Users', 'wp-span-checker' ); ?>
			</a>
		</p>
	</form>

	<div class="wsc-card" style="max-width: 800px; margin-top: 20px;">
		<h3><?php esc_html_e( 'How It Works', 'wp-span-checker' ); ?></h3>
		<ol>
			<li><?php esc_html_e( 'When spam is detected (Form Guard validation, Comment spam, etc.), the user/guest receives a strike.', 'wp-span-checker' ); ?></li>
			<li><?php esc_html_e( 'Strikes accumulate until the max is reached.', 'wp-span-checker' ); ?></li>
			<li><?php esc_html_e( 'Once blocked, users cannot submit forms and optionally cannot log in.', 'wp-span-checker' ); ?></li>
			<li><?php esc_html_e( 'Strikes expire after the configured number of days, or can be manually reset.', 'wp-span-checker' ); ?></li>
			<li><?php esc_html_e( 'Administrators are exempt from strikes when the option is enabled.', 'wp-span-checker' ); ?></li>
		</ol>
	</div>
</div>
