<?php
/**
 * WooCommerce product AI summaries — same storage as post summaries, product post type only.
 *
 * @package WP_Span_Checker
 */

use WP_Span_Checker\AI_Span_Config;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cfg = AI_Span_Config::get();

$wc_on = class_exists( '\WooCommerce', false ) || function_exists( 'WC' );

global $wpdb;
$post_table = $wpdb->posts;
$sum_table  = $wpdb->prefix . 'span_checker_ai_post_summary';

$statuses = array( 'publish', 'future' );
$ph_stat  = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );

$sql = "
	SELECT p.ID, p.post_title, p.post_status, p.post_modified,
		s.status AS sum_status, s.summary, s.last_error, s.updated_at AS sum_updated
	FROM {$post_table} p
	LEFT JOIN {$sum_table} s ON s.post_id = p.ID
	WHERE p.post_type = 'product'
	AND p.post_status IN ({$ph_stat})
	ORDER BY p.post_modified DESC
	LIMIT 150
";

// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Static IN placeholders.
$posts = $wpdb->get_results( $wpdb->prepare( $sql, $statuses ), ARRAY_A );
if ( ! is_array( $posts ) ) {
	$posts = array();
}
?>

<div class="wrap wsc-admin">
	<?php
	wp_span_checker_admin_page_header(
		__( 'AI Product Summaries', 'wp-span-checker' ),
		__( 'Recent WooCommerce products with AI-generated summaries for Product Review Guard and moderation context. Uses the same provider and credentials as AI Span Settings.', 'wp-span-checker' )
	);
	?>

	<?php if ( ! $wc_on ) : ?>
		<div class="notice notice-warning"><p><?php esc_html_e( 'WooCommerce is not active. This list only applies when the WooCommerce plugin is running.', 'wp-span-checker' ); ?></p></div>
	<?php endif; ?>

	<?php if ( empty( $cfg['ai_enabled'] ) ) : ?>
		<div class="notice notice-warning"><p><?php esc_html_e( 'AI Span Checker is disabled. Enable it under AI Span Settings to run generation.', 'wp-span-checker' ); ?></p></div>
	<?php endif; ?>

	<p class="description">
		<?php esc_html_e( 'Summaries are built from product name, short & long descriptions, categories, tags, visible attributes, and SKU. Enable “product” under AI Span Settings → Summaries to auto-refresh when products are saved.', 'wp-span-checker' ); ?>
	</p>

	<div class="wsc-card wsc-admin__table-wrap">
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Product', 'wp-span-checker' ); ?></th>
					<th><?php esc_html_e( 'Status', 'wp-span-checker' ); ?></th>
					<th><?php esc_html_e( 'Summary state', 'wp-span-checker' ); ?></th>
					<th><?php esc_html_e( 'Updated', 'wp-span-checker' ); ?></th>
					<th><?php esc_html_e( 'Preview', 'wp-span-checker' ); ?></th>
					<th><?php esc_html_e( 'Action', 'wp-span-checker' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $posts ) ) : ?>
					<tr><td colspan="6"><?php esc_html_e( 'No published products found.', 'wp-span-checker' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $posts as $row ) : ?>
						<?php
						$pid     = (int) $row['ID'];
						$st      = $row['sum_status'] ?? '';
						$st_disp = '' !== $st ? $st : __( 'none', 'wp-span-checker' );
						$preview = isset( $row['summary'] ) ? wp_html_excerpt( (string) $row['summary'], 120, '…' ) : '';
						?>
						<tr>
							<td>
								<a href="<?php echo esc_url( get_edit_post_link( $pid ) ); ?>"><?php echo esc_html( (string) $row['post_title'] ); ?></a>
							</td>
							<td><?php echo esc_html( (string) $row['post_status'] ); ?></td>
							<td><?php echo esc_html( $st_disp ); ?></td>
							<td><?php echo esc_html( (string) ( $row['sum_updated'] ?? '' ) ); ?></td>
							<td>
								<?php if ( ! empty( $row['last_error'] ) && ( 'failed' === $st || '' === $st ) ) : ?>
									<span class="description"><?php echo esc_html( wp_html_excerpt( (string) $row['last_error'], 80, '…' ) ); ?></span>
								<?php else : ?>
									<?php echo esc_html( $preview ); ?>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( current_user_can( 'edit_post', $pid ) ) : ?>
									<button type="button" class="button button-small wsc-ai-generate-summary" data-post-id="<?php echo esc_attr( (string) $pid ); ?>">
										<?php esc_html_e( 'Generate summary', 'wp-span-checker' ); ?>
									</button>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
