<?php
/**
 * Blocked Users and strike history.
 *
 * @package WP_Span_Checker
 */

use WP_Span_Checker\AI_Span_Comments;
use WP_Span_Checker\AI_Span_Config;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$table = $wpdb->prefix . 'span_checker_comment_enforcement';
$cfg   = AI_Span_Config::get();

// phpcs:disable WordPress.Security.NonceVerification.Missing
if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] && ! empty( $_POST['wsc_unblock_actor'] ) ) {
	if ( ! isset( $_POST['wsc_blocks_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wsc_blocks_nonce'] ) ), 'wsc_blocks_action' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'wp-span-checker' ) );
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission.', 'wp-span-checker' ) );
	}
	$key = isset( $_POST['actor_key'] ) ? sanitize_text_field( wp_unslash( $_POST['actor_key'] ) ) : '';
	if ( $key !== '' && AI_Span_Comments::admin_unblock( $key ) ) {
		echo '<div class="updated"><p>' . esc_html__( 'User was unblocked and strikes were reset.', 'wp-span-checker' ) . '</p></div>';
	} elseif ( $key !== '' ) {
		echo '<div class="error"><p>' . esc_html__( 'Could not update that record.', 'wp-span-checker' ) . '</p></div>';
	}
}
// phpcs:enable WordPress.Security.NonceVerification.Missing

// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name uses trusted prefix.
$rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY login_blocked DESC, site_banned DESC, blocked DESC, strikes DESC, last_strike_at DESC LIMIT 500", ARRAY_A );
if ( ! is_array( $rows ) ) {
	$rows = array();
}
?>

<div class="wrap wsc-admin">
	<?php
	wp_span_checker_admin_page_header(
		__( 'Blocked Users', 'wp-span-checker' ),
		__( 'Users and guests accumulate strikes when spam is detected. When strikes reach the limit, users are blocked from submitting forms and optionally from logging in.', 'wp-span-checker' )
	);
	?>

	<!-- Settings Summary -->
	<div class="wsc-card" style="margin-bottom: 20px; padding: 15px;">
		<h3 style="margin-top: 0;"><?php esc_html_e( 'Block User Settings', 'wp-span-checker' ); ?></h3>
		<table class="form-table" role="presentation">
			<tr>
				<th><?php esc_html_e( 'Status', 'wp-span-checker' ); ?></th>
				<td>
					<?php if ( ! empty( $cfg['block_user_enabled'] ) ) : ?>
						<span class="dashicons dashicons-yes-alt" style="color: #2e7d32;"></span>
						<?php esc_html_e( 'Enabled', 'wp-span-checker' ); ?>
					<?php else : ?>
						<span class="dashicons dashicons-dismiss" style="color: #c62828;"></span>
						<?php esc_html_e( 'Disabled', 'wp-span-checker' ); ?>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Max Strikes', 'wp-span-checker' ); ?></th>
				<td><?php echo esc_html( (string) (int) ( $cfg['block_user_max_strikes'] ?? 5 ) ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Block Login', 'wp-span-checker' ); ?></th>
				<td><?php echo ! empty( $cfg['block_user_login_block'] ) ? esc_html__( 'Yes', 'wp-span-checker' ) : esc_html__( 'No', 'wp-span-checker' ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Strike Expiry', 'wp-span-checker' ); ?></th>
				<td>
					<?php
					$expiry = (int) ( $cfg['block_user_strike_expiry_days'] ?? 30 );
					if ( $expiry > 0 ) {
						/* translators: %d: number of days */
						printf( esc_html__( '%d days', 'wp-span-checker' ), $expiry );
					} else {
						esc_html_e( 'Never', 'wp-span-checker' );
					}
					?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Admin Exempt', 'wp-span-checker' ); ?></th>
				<td><?php echo ! empty( $cfg['block_user_exempt_admins'] ) ? esc_html__( 'Yes', 'wp-span-checker' ) : esc_html__( 'No', 'wp-span-checker' ); ?></td>
			</tr>
		</table>
		<p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wsc-block-user-settings' ) ); ?>" class="button"><?php esc_html_e( 'Edit Settings', 'wp-span-checker' ); ?></a>
		</p>
	</div>

	<div class="wsc-card wsc-admin__table-wrap">
		<h3><?php esc_html_e( 'Blocked Users List', 'wp-span-checker' ); ?></h3>
		<?php if ( empty( $rows ) ) : ?>
			<p><?php esc_html_e( 'No blocked users yet.', 'wp-span-checker' ); ?></p>
		<?php else : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'User / Guest', 'wp-span-checker' ); ?></th>
						<th><?php esc_html_e( 'Strikes', 'wp-span-checker' ); ?></th>
						<th><?php esc_html_e( 'Login Blocked', 'wp-span-checker' ); ?></th>
						<th><?php esc_html_e( 'Form Blocked', 'wp-span-checker' ); ?></th>
						<th><?php esc_html_e( 'Source', 'wp-span-checker' ); ?></th>
						<th><?php esc_html_e( 'Expires', 'wp-span-checker' ); ?></th>
						<th><?php esc_html_e( 'Last Strike', 'wp-span-checker' ); ?></th>
						<th><?php esc_html_e( 'Reason', 'wp-span-checker' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'wp-span-checker' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $row ) : ?>
						<?php
						$user_label = (string) ( $row['actor_label'] ?? '' );
						$user_id    = ! empty( $row['user_id'] ) ? (int) $row['user_id'] : 0;
						if ( $user_id > 0 ) {
							$user_obj = get_userdata( $user_id );
							if ( $user_obj ) {
								$user_label = $user_obj->display_name . ' (' . $user_obj->user_login . ')';
							}
						}

						$source = (string) ( $row['strike_source'] ?? 'comment' );
						$source_labels = array(
							'comment'    => __( 'Comment', 'wp-span-checker' ),
							'form_guard' => __( 'Form Guard', 'wp-span-checker' ),
							'login'      => __( 'Login', 'wp-span-checker' ),
						);
						$source_label = isset( $source_labels[ $source ] ) ? $source_labels[ $source ] : ucfirst( $source );

						$expires = '';
						if ( ! empty( $row['strikes_expire_at'] ) ) {
							$exp_time = strtotime( $row['strikes_expire_at'] );
							if ( $exp_time < time() ) {
								$expires = __( 'Expired', 'wp-span-checker' );
							} else {
								$expires = human_time_diff( time(), $exp_time );
							}
						} else {
							$expires = __( 'Never', 'wp-span-checker' );
						}
						?>
						<tr>
							<td>
								<?php echo esc_html( $user_label ); ?>
								<?php if ( $user_id > 0 ) : ?>
									<br><small class="description"><?php echo esc_html( sprintf( __( 'User ID: %d', 'wp-span-checker' ), $user_id ) ); ?></small>
								<?php endif; ?>
							</td>
							<td>
								<strong><?php echo esc_html( (string) (int) ( $row['strikes'] ?? 0 ) ); ?></strong>
								/ <?php echo esc_html( (string) (int) ( $cfg['block_user_max_strikes'] ?? 5 ) ); ?>
							</td>
							<td>
								<?php if ( ! empty( $row['login_blocked'] ) ) : ?>
									<span style="color: #c62828;"><span class="dashicons dashicons-lock"></span> <?php esc_html_e( 'Yes', 'wp-span-checker' ); ?></span>
								<?php else : ?>
									<?php esc_html_e( 'No', 'wp-span-checker' ); ?>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( ! empty( $row['blocked'] ) ) : ?>
									<span style="color: #c62828;"><?php esc_html_e( 'Yes', 'wp-span-checker' ); ?></span>
								<?php else : ?>
									<?php esc_html_e( 'No', 'wp-span-checker' ); ?>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( $source_label ); ?></td>
							<td><?php echo esc_html( $expires ); ?></td>
							<td><?php echo esc_html( (string) ( $row['last_strike_at'] ?? '' ) ); ?></td>
							<td>
								<span title="<?php echo esc_attr( (string) ( $row['last_reason'] ?? '' ) ); ?>">
									<?php echo esc_html( wp_trim_words( (string) ( $row['last_reason'] ?? '' ), 5, '...' ) ); ?>
								</span>
							</td>
							<td>
								<?php if ( ! empty( $row['blocked'] ) || ! empty( $row['site_banned'] ) || ! empty( $row['login_blocked'] ) || (int) ( $row['strikes'] ?? 0 ) > 0 ) : ?>
									<form method="post" style="display:inline;">
										<?php wp_nonce_field( 'wsc_blocks_action', 'wsc_blocks_nonce' ); ?>
										<input type="hidden" name="actor_key" value="<?php echo esc_attr( (string) ( $row['actor_key'] ?? '' ) ); ?>">
										<button type="submit" name="wsc_unblock_actor" value="1" class="button button-small"><?php esc_html_e( 'Unblock & Reset', 'wp-span-checker' ); ?></button>
									</form>
								<?php else : ?>
									—
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
</div>
