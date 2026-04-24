<?php
/**
 * Blocked commenters and strike history.
 *
 * @package WP_Span_Checker
 */

use WP_Span_Checker\AI_Span_Comments;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$table = $wpdb->prefix . 'span_checker_comment_enforcement';

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
		echo '<div class="updated"><p>' . esc_html__( 'User or guest fingerprint was unblocked and strikes were reset.', 'wp-span-checker' ) . '</p></div>';
	} elseif ( $key !== '' ) {
		echo '<div class="error"><p>' . esc_html__( 'Could not update that record.', 'wp-span-checker' ) . '</p></div>';
	}
}
// phpcs:enable WordPress.Security.NonceVerification.Missing

// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name uses trusted prefix.
$rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY site_banned DESC, blocked DESC, strikes DESC, last_strike_at DESC LIMIT 500", ARRAY_A );
if ( ! is_array( $rows ) ) {
	$rows = array();
}
?>

<div class="wrap wsc-admin">
	<?php
	wp_span_checker_admin_page_header(
		__( 'Blocked commenters', 'wp-span-checker' ),
		__( 'Actors accumulate strikes when comments are rejected. Comment-blocked rows cannot use the comment form; site-restricted rows are blocked from the rest of the site (contact page excepted) until you unblock them.', 'wp-span-checker' )
	);
	?>

	<div class="wsc-card wsc-admin__table-wrap">
		<?php if ( empty( $rows ) ) : ?>
			<p><?php esc_html_e( 'No enforcement records yet.', 'wp-span-checker' ); ?></p>
		<?php else : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Label', 'wp-span-checker' ); ?></th>
						<th><?php esc_html_e( 'Strikes', 'wp-span-checker' ); ?></th>
						<th><?php esc_html_e( 'Comment block', 'wp-span-checker' ); ?></th>
						<th><?php esc_html_e( 'Site restricted', 'wp-span-checker' ); ?></th>
						<th><?php esc_html_e( 'Last strike', 'wp-span-checker' ); ?></th>
						<th><?php esc_html_e( 'Last reason', 'wp-span-checker' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'wp-span-checker' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<td><?php echo esc_html( (string) ( $row['actor_label'] ?? '' ) ); ?></td>
							<td><?php echo esc_html( (string) (int) ( $row['strikes'] ?? 0 ) ); ?></td>
							<td><?php echo ! empty( $row['blocked'] ) ? esc_html__( 'Yes', 'wp-span-checker' ) : esc_html__( 'No', 'wp-span-checker' ); ?></td>
							<td><?php echo ! empty( $row['site_banned'] ) ? esc_html__( 'Yes', 'wp-span-checker' ) : esc_html__( 'No', 'wp-span-checker' ); ?></td>
							<td><?php echo esc_html( (string) ( $row['last_strike_at'] ?? '' ) ); ?></td>
							<td><?php echo esc_html( (string) ( $row['last_reason'] ?? '' ) ); ?></td>
							<td>
								<?php if ( ! empty( $row['blocked'] ) || ! empty( $row['site_banned'] ) || (int) ( $row['strikes'] ?? 0 ) > 0 ) : ?>
									<form method="post" style="display:inline;">
										<?php wp_nonce_field( 'wsc_blocks_action', 'wsc_blocks_nonce' ); ?>
										<input type="hidden" name="actor_key" value="<?php echo esc_attr( (string) ( $row['actor_key'] ?? '' ) ); ?>">
										<button type="submit" name="wsc_unblock_actor" value="1" class="button button-small"><?php esc_html_e( 'Unblock & reset strikes', 'wp-span-checker' ); ?></button>
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
