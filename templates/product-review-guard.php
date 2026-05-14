<?php
/**
 * WooCommerce Product Review Guard settings.
 *
 * @package WP_Span_Checker
 */

use WP_Span_Checker\AI_Span_Config;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wc_on = class_exists( '\WooCommerce', false ) || function_exists( 'WC' );
$cfg   = AI_Span_Config::get();

// phpcs:disable WordPress.Security.NonceVerification.Missing
if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['wsc_product_review_guard_save'] ) ) {
	if ( ! isset( $_POST['wsc_product_review_guard_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wsc_product_review_guard_nonce'] ) ), 'wsc_product_review_guard_save' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'wp-span-checker' ) );
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to save these settings.', 'wp-span-checker' ) );
	}

	$incoming = array(
		'product_review_guard_enabled'       => ! empty( $_POST['product_review_guard_enabled'] ),
		'review_mirror_comment_rules'       => ! empty( $_POST['review_mirror_comment_rules'] ),
		'review_antispam_enabled'           => ! empty( $_POST['review_antispam_enabled'] ),
		'review_strike_on_heuristic'        => ! empty( $_POST['review_strike_on_heuristic'] ),
		'review_min_length'                 => isset( $_POST['review_min_length'] ) ? absint( $_POST['review_min_length'] ) : 0,
		'review_max_length'                 => isset( $_POST['review_max_length'] ) ? absint( $_POST['review_max_length'] ) : 0,
		'review_max_links'                  => isset( $_POST['review_max_links'] ) ? absint( $_POST['review_max_links'] ) : 0,
		'review_allow_links'                => ! empty( $_POST['review_allow_links'] ),
		'review_block_duplicate'            => ! empty( $_POST['review_block_duplicate'] ),
		'review_rate_limit_max'             => isset( $_POST['review_rate_limit_max'] ) ? absint( $_POST['review_rate_limit_max'] ) : 0,
		'review_rate_limit_window'          => isset( $_POST['review_rate_limit_window'] ) ? absint( $_POST['review_rate_limit_window'] ) : 15,
		'review_rate_limit_scope'           => isset( $_POST['review_rate_limit_scope'] ) ? sanitize_text_field( wp_unslash( $_POST['review_rate_limit_scope'] ) ) : 'ip_post',
		'review_ai_semantic_check'           => ! empty( $_POST['review_ai_semantic_check'] ),
		'review_ai_auto_product_summary'    => ! empty( $_POST['review_ai_auto_product_summary'] ),
		'review_system_prompt'              => isset( $_POST['review_system_prompt'] ) ? wp_unslash( $_POST['review_system_prompt'] ) : '',
		'review_require_rating'             => ! empty( $_POST['review_require_rating'] ),
		'review_require_verified_purchase' => ! empty( $_POST['review_require_verified_purchase'] ),
		'review_one_per_customer'           => ! empty( $_POST['review_one_per_customer'] ),
		'review_block_guest'                => ! empty( $_POST['review_block_guest'] ),
	);

	AI_Span_Config::update( $incoming );
	$cfg = AI_Span_Config::get();
	echo '<div class="updated"><p>' . esc_html__( 'Product Review Guard settings saved.', 'wp-span-checker' ) . '</p></div>';
}
// phpcs:enable WordPress.Security.NonceVerification.Missing

$review_prompt_display = trim( (string) ( $cfg['review_system_prompt'] ?? '' ) );
if ( '' === $review_prompt_display ) {
	$review_prompt_display = AI_Span_Config::default_review_system_prompt();
}
?>

<div class="wrap wsc-admin">
	<?php
	wp_span_checker_admin_page_header(
		__( 'Product Review Guard', 'wp-span-checker' ),
		__( 'Protects WooCommerce product reviews with the same heuristic pipeline and strike system as Comment Guard, plus store-specific checks (verified buyer, star rating, one review per customer).', 'wp-span-checker' )
	);
	?>

	<?php if ( ! $wc_on ) : ?>
		<div class="notice notice-warning"><p><?php esc_html_e( 'WooCommerce is not active. This guard only runs when WooCommerce is installed and product reviews are submitted.', 'wp-span-checker' ); ?></p></div>
	<?php endif; ?>

	<div class="wsc-card">
		<form method="post">
			<?php wp_nonce_field( 'wsc_product_review_guard_save', 'wsc_product_review_guard_nonce' ); ?>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable Product Review Guard', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'product_review_guard_enabled',
								'checked'     => ! empty( $cfg['product_review_guard_enabled'] ),
								'description' => __( 'When enabled, WooCommerce product reviews are validated here first. Comment Guard no longer processes those submissions (regular blog comments are unchanged).', 'wp-span-checker' ),
							)
						);
						?>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'WooCommerce rules', 'wp-span-checker' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Require star rating', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'review_require_rating',
								'checked'     => ! empty( $cfg['review_require_rating'] ),
								'description' => __( 'Reject submissions without a valid 1–5 rating (classic review form and REST when rating is present).', 'wp-span-checker' ),
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Verified purchase only', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'review_require_verified_purchase',
								'checked'     => ! empty( $cfg['review_require_verified_purchase'] ),
								'description' => __( 'Runs a server-side WooCommerce order check before the review is accepted.', 'wp-span-checker' ),
							)
						);
						?>
						<p class="description" style="margin-top:10px;max-width:720px;">
							<?php esc_html_e( 'How it works: the plugin calls WooCommerce’s purchase lookup (wc_customer_bought_product) using the reviewer’s logged-in user ID and/or the email they enter on the review form. That matches customers who have bought this exact product ID—not just similar titles.', 'wp-span-checker' ); ?>
						</p>
						<p class="description" style="max-width:720px;">
							<?php esc_html_e( 'WooCommerce also has its own “Reviews can only be left by verified owners” setting. You can use either that setting or this toggle; enabling both means two layers of the same kind of check (usually redundant). Use this when you want fake-review blocking enforced by WP Span Checker regardless of the WooCommerce checkbox.', 'wp-span-checker' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'One review per customer per product', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'review_one_per_customer',
								'checked'     => ! empty( $cfg['review_one_per_customer'] ),
								'description' => __( 'Blocks a second review from the same user ID or guest email on the same product (pending reviews count).', 'wp-span-checker' ),
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Logged-in reviews only', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'review_block_guest',
								'checked'     => ! empty( $cfg['review_block_guest'] ),
								'description' => __( 'Guests cannot submit product reviews (must have a WordPress account and be logged in).', 'wp-span-checker' ),
							)
						);
						?>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Spam shield', 'wp-span-checker' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Uses the same component pipeline as Comment Guard (links, length, disposable email, phrases, flood control, etc.). Keyword lists and most toggles come from Comment Guard unless you turn off mirroring below.', 'wp-span-checker' ); ?></p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Mirror Comment Guard rules', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'review_mirror_comment_rules',
								'checked'     => ! empty( $cfg['review_mirror_comment_rules'] ),
								'description' => __( 'Recommended. Use Comment Guard’s spam shield settings and flood scope for reviews. Turn off to override lengths, links, flood, and rule toggles below only.', 'wp-span-checker' ),
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable rule-based checks', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'review_antispam_enabled',
								'checked'     => ! empty( $cfg['review_antispam_enabled'] ),
								'description' => __( 'When mirroring is off, this toggles the pipeline independently.', 'wp-span-checker' ),
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Strikes on rule violations', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'review_strike_on_heuristic',
								'checked'     => ! empty( $cfg['review_strike_on_heuristic'] ),
								'description' => __( 'Count heuristic blocks toward the shared strike limits (see Comment Guard → AI & strikes).', 'wp-span-checker' ),
							)
						);
						?>
					</td>
				</tr>
				<tr class="wsc-review-advanced" <?php echo ! empty( $cfg['review_mirror_comment_rules'] ) ? 'style="display:none;"' : ''; ?>>
					<th scope="row"><label for="review_min_length"><?php esc_html_e( 'Review text length', 'wp-span-checker' ); ?></label></th>
					<td>
						<?php esc_html_e( 'Min', 'wp-span-checker' ); ?>
						<input type="number" name="review_min_length" id="review_min_length" value="<?php echo esc_attr( (string) (int) ( $cfg['review_min_length'] ?? 4 ) ); ?>" min="0" max="500" class="small-text">
						<?php esc_html_e( '(0 = off)', 'wp-span-checker' ); ?>
						&nbsp; <?php esc_html_e( 'Max', 'wp-span-checker' ); ?>
						<input type="number" name="review_max_length" id="review_max_length" value="<?php echo esc_attr( (string) (int) ( $cfg['review_max_length'] ?? 8000 ) ); ?>" min="0" max="65535" class="small-text">
						<?php esc_html_e( '(0 = off)', 'wp-span-checker' ); ?>
						<p class="description"><?php esc_html_e( 'Only used when mirroring is off.', 'wp-span-checker' ); ?></p>
					</td>
				</tr>
				<tr class="wsc-review-advanced" <?php echo ! empty( $cfg['review_mirror_comment_rules'] ) ? 'style="display:none;"' : ''; ?>>
					<th scope="row"><label for="review_max_links"><?php esc_html_e( 'Max links in review body', 'wp-span-checker' ); ?></label></th>
					<td>
						<input type="number" name="review_max_links" id="review_max_links" value="<?php echo esc_attr( (string) (int) ( $cfg['review_max_links'] ?? 2 ) ); ?>" min="0" max="100" class="small-text">
						<p class="description"><?php esc_html_e( 'Only used when mirroring is off.', 'wp-span-checker' ); ?></p>
					</td>
				</tr>
				<tr class="wsc-review-advanced" <?php echo ! empty( $cfg['review_mirror_comment_rules'] ) ? 'style="display:none;"' : ''; ?>>
					<th scope="row"><?php esc_html_e( 'Allow links in review body', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'review_allow_links',
								'checked'     => ! empty( $cfg['review_allow_links'] ),
								'description' => __( 'Only used when mirroring is off.', 'wp-span-checker' ),
							)
						);
						?>
					</td>
				</tr>
				<tr class="wsc-review-advanced" <?php echo ! empty( $cfg['review_mirror_comment_rules'] ) ? 'style="display:none;"' : ''; ?>>
					<th scope="row"><?php esc_html_e( 'Block duplicate review body', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'review_block_duplicate',
								'checked'     => ! empty( $cfg['review_block_duplicate'] ),
								'description' => __( 'Same text cannot be posted twice on one product.', 'wp-span-checker' ),
							)
						);
						?>
					</td>
				</tr>
				<tr class="wsc-review-advanced" <?php echo ! empty( $cfg['review_mirror_comment_rules'] ) ? 'style="display:none;"' : ''; ?>>
					<th scope="row"><label for="review_rate_limit_max"><?php esc_html_e( 'Review flood limit', 'wp-span-checker' ); ?></label></th>
					<td>
						<input type="number" name="review_rate_limit_max" id="review_rate_limit_max" value="<?php echo esc_attr( (string) (int) ( $cfg['review_rate_limit_max'] ?? 8 ) ); ?>" min="0" max="500" class="small-text">
						<?php esc_html_e( 'reviews per', 'wp-span-checker' ); ?>
						<input type="number" name="review_rate_limit_window" id="review_rate_limit_window" value="<?php echo esc_attr( (string) (int) ( $cfg['review_rate_limit_window'] ?? 30 ) ); ?>" min="1" max="1440" class="small-text">
						<?php esc_html_e( 'minutes (0 = disable)', 'wp-span-checker' ); ?>
					</td>
				</tr>
				<tr class="wsc-review-advanced" <?php echo ! empty( $cfg['review_mirror_comment_rules'] ) ? 'style="display:none;"' : ''; ?>>
					<th scope="row"><label for="review_rate_limit_scope"><?php esc_html_e( 'Flood scope', 'wp-span-checker' ); ?></label></th>
					<td>
						<select name="review_rate_limit_scope" id="review_rate_limit_scope">
							<option value="ip" <?php selected( $cfg['review_rate_limit_scope'] ?? 'ip_post', 'ip' ); ?>><?php esc_html_e( 'Per IP (whole site)', 'wp-span-checker' ); ?></option>
							<option value="ip_post" <?php selected( $cfg['review_rate_limit_scope'] ?? 'ip_post', 'ip_post' ); ?>><?php esc_html_e( 'Per IP per product', 'wp-span-checker' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( '“Per product” limits rapid reviews on the same item only.', 'wp-span-checker' ); ?></p>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'AI semantic check', 'wp-span-checker' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Run AI on reviews', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'review_ai_semantic_check',
								'checked'     => ! empty( $cfg['review_ai_semantic_check'] ),
								'description' => __( 'Requires AI Span Settings → AI enabled. Compares the review text against an AI product summary so off-topic or copy-paste spam is easier to catch.', 'wp-span-checker' ),
							)
						);
						?>
						<p class="description">
							<?php esc_html_e( 'Manage bulk summaries:', 'wp-span-checker' ); ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-span-checker-ai-product-summaries' ) ); ?>"><?php esc_html_e( 'AI Product Summaries', 'wp-span-checker' ); ?></a>
							<?php esc_html_e( 'or', 'wp-span-checker' ); ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-span-checker-ai-summaries' ) ); ?>"><?php esc_html_e( 'AI Post Summaries', 'wp-span-checker' ); ?></a>
							<?php esc_html_e( '(when “product” is included in AI Span Settings → Summaries).', 'wp-span-checker' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Auto-generate missing product summary', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'review_ai_auto_product_summary',
								'checked'     => ! empty( $cfg['review_ai_auto_product_summary'] ),
								'description' => __( 'If no AI summary exists yet for this product, request one at review time (uses title, descriptions, categories, attributes). Falls back to a plain excerpt only if generation fails.', 'wp-span-checker' ),
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="review_system_prompt"><?php esc_html_e( 'Review AI system prompt', 'wp-span-checker' ); ?></label></th>
					<td>
						<textarea name="review_system_prompt" id="review_system_prompt" rows="14" class="large-text"><?php echo esc_textarea( $review_prompt_display ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Default prompt is pre-filled for strong spam/off-topic detection and PRODUCT_SUMMARY grounding. Edit freely; output must stay JSON with status and message. Clear the field and save to restore the plugin default text on next load.', 'wp-span-checker' ); ?></p>
					</td>
				</tr>
			</table>

			<p class="description"><?php esc_html_e( 'Strike limits, site bans, blocked-user management, and contact-page notices are shared with Comment Guard.', 'wp-span-checker' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-span-checker-comment-settings' ) ); ?>"><?php esc_html_e( 'Open Comment Guard', 'wp-span-checker' ); ?></a>
			</p>

			<p class="submit">
				<button type="submit" name="wsc_product_review_guard_save" value="1" class="button button-primary"><?php esc_html_e( 'Save settings', 'wp-span-checker' ); ?></button>
			</p>
		</form>

		<script>
		jQuery(function($) {
			function toggleAdvanced() {
				var on = $('input[name="review_mirror_comment_rules"]').is(':checked');
				$('.wsc-review-advanced').toggle(!on);
			}
			$('input[name="review_mirror_comment_rules"]').on('change', toggleAdvanced);
			toggleAdvanced();
		});
		</script>
	</div>
</div>
