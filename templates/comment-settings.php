<?php
/**
 * Comment moderation — heuristic anti-spam, AI, strikes, prompts.
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
	if ( ! isset( $_POST['wsc_comment_settings_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wsc_comment_settings_nonce'] ) ), 'wsc_comment_settings_save' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'wp-span-checker' ) );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to save these settings.', 'wp-span-checker' ) );
	}

	$scope = isset( $_POST['comment_rate_limit_scope'] ) ? sanitize_text_field( wp_unslash( $_POST['comment_rate_limit_scope'] ) ) : 'ip';
	$caps  = isset( $_POST['comment_max_caps_ratio'] ) ? (float) $_POST['comment_max_caps_ratio'] : 0;

	$incoming = array(
		'comment_antispam_enabled'        => ! empty( $_POST['comment_antispam_enabled'] ),
		'comment_strike_on_heuristic'     => ! empty( $_POST['comment_strike_on_heuristic'] ),
		'comment_min_length'              => isset( $_POST['comment_min_length'] ) ? absint( $_POST['comment_min_length'] ) : 0,
		'comment_max_length'              => isset( $_POST['comment_max_length'] ) ? absint( $_POST['comment_max_length'] ) : 0,
		'comment_max_links'               => isset( $_POST['comment_max_links'] ) ? absint( $_POST['comment_max_links'] ) : 0,
		'comment_block_keywords'          => isset( $_POST['comment_block_keywords'] ) ? wp_unslash( $_POST['comment_block_keywords'] ) : '',
		'comment_block_email_domains'     => isset( $_POST['comment_block_email_domains'] ) ? wp_unslash( $_POST['comment_block_email_domains'] ) : '',
		'comment_block_duplicate'         => ! empty( $_POST['comment_block_duplicate'] ),
		'comment_rate_limit_max'          => isset( $_POST['comment_rate_limit_max'] ) ? absint( $_POST['comment_rate_limit_max'] ) : 0,
		'comment_rate_limit_window'       => isset( $_POST['comment_rate_limit_window'] ) ? absint( $_POST['comment_rate_limit_window'] ) : 15,
		'comment_rate_limit_scope'        => $scope,
		'comment_block_bbcode'            => ! empty( $_POST['comment_block_bbcode'] ),
		'comment_block_dangerous_markup'  => ! empty( $_POST['comment_block_dangerous_markup'] ),
		'comment_block_excessive_repeats' => ! empty( $_POST['comment_block_excessive_repeats'] ),
		'comment_max_caps_ratio'          => $caps,
		'comment_block_disposable_email'  => ! empty( $_POST['comment_block_disposable_email'] ),
		'comment_builtin_bad_phrases'     => ! empty( $_POST['comment_builtin_bad_phrases'] ),
		'comment_respect_whitelist'       => ! empty( $_POST['comment_respect_whitelist'] ),
		'comment_block_trackbacks'        => ! empty( $_POST['comment_block_trackbacks'] ),
		'comment_disallow_guest_website'  => ! empty( $_POST['comment_disallow_guest_website'] ),
		'comment_block_http_author_url'   => ! empty( $_POST['comment_block_http_author_url'] ),
		'comment_block_punycode_abuse'    => ! empty( $_POST['comment_block_punycode_abuse'] ),
		'comment_emoji_flood_max'         => isset( $_POST['comment_emoji_flood_max'] ) ? absint( $_POST['comment_emoji_flood_max'] ) : 0,
		'comment_max_strikes'             => isset( $_POST['comment_max_strikes'] ) ? absint( $_POST['comment_max_strikes'] ) : 5,
		'comment_contact_page_id'         => isset( $_POST['comment_contact_page_id'] ) ? absint( $_POST['comment_contact_page_id'] ) : 0,
		'comment_site_ban_enabled'        => ! empty( $_POST['comment_site_ban_enabled'] ),
		'comment_site_ban_strikes'        => isset( $_POST['comment_site_ban_strikes'] ) ? absint( $_POST['comment_site_ban_strikes'] ) : 10,
		'comment_allow_links'             => ! empty( $_POST['comment_allow_links'] ),
		'product_review_filter'           => ! empty( $_POST['product_review_filter'] ),
		'system_prompt'                   => isset( $_POST['system_prompt'] ) ? wp_unslash( $_POST['system_prompt'] ) : '',
	);

	AI_Span_Config::update( $incoming );
	$cfg = AI_Span_Config::get();
	echo '<div class="updated"><p>' . esc_html__( 'Comment moderation settings saved.', 'wp-span-checker' ) . '</p></div>';
}
// phpcs:enable WordPress.Security.NonceVerification.Missing

$prompt = (string) ( $cfg['system_prompt'] ?? '' );
if ( $prompt === '' ) {
	$prompt = AI_Span_Config::default_system_prompt();
}

$caps_val = isset( $cfg['comment_max_caps_ratio'] ) ? (float) $cfg['comment_max_caps_ratio'] : 0;
?>

<div class="wrap wsc-admin">
	<?php
	wp_span_checker_admin_page_header(
		__( 'Comment moderation', 'wp-span-checker' ),
		__( 'Works fully without AI: a pluggable component pipeline (filters, lists, flood control). Enable AI separately for semantic checks when post summaries exist.', 'wp-span-checker' )
	);
	?>

	<div class="wsc-card">
		<form method="post" id="wsc-comment-moderation-form">
			<?php wp_nonce_field( 'wsc_comment_settings_save', 'wsc_comment_settings_nonce' ); ?>

			<h2 class="nav-tab-wrapper wsc-spam-nav" style="padding-top:0;margin-top:0;">
				<a href="#" class="nav-tab nav-tab-active" data-wsc-tab="wsc-spam-shield"><?php esc_html_e( 'Spam shield (no AI)', 'wp-span-checker' ); ?></a>
				<a href="#" class="nav-tab" data-wsc-tab="wsc-spam-ai"><?php esc_html_e( 'AI & strikes', 'wp-span-checker' ); ?></a>
			</h2>

			<div id="wsc-spam-shield" class="wsc-spam-tab-panel">
			<p class="description" style="margin:12px 0;">
				<?php esc_html_e( 'Checks run as ordered components. Developers can reorder or add classes implementing Spam_Check_Component via the', 'wp-span-checker' ); ?>
				<code>wsc_spam_check_components</code>
				<?php esc_html_e( 'filter.', 'wp-span-checker' ); ?>
			</p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable rule-based checks', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'comment_antispam_enabled',
								'checked'     => ! empty( $cfg['comment_antispam_enabled'] ),
								'description' => __( 'Recommended. Runs all checks below before AI (if enabled).', 'wp-span-checker' ),
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
								'name'        => 'comment_strike_on_heuristic',
								'checked'     => ! empty( $cfg['comment_strike_on_heuristic'] ),
								'description' => __( 'Count heuristic blocks toward the strike limit and auto-block.', 'wp-span-checker' ),
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Block trackbacks & pingbacks', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'comment_block_trackbacks',
								'checked'     => ! empty( $cfg['comment_block_trackbacks'] ),
								'description' => __( 'Rejects trackback/pingback comment types (common spam vector).', 'wp-span-checker' ),
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="comment_rate_limit_max"><?php esc_html_e( 'Flood limit', 'wp-span-checker' ); ?></label></th>
					<td>
						<input type="number" name="comment_rate_limit_max" id="comment_rate_limit_max" value="<?php echo esc_attr( (string) (int) $cfg['comment_rate_limit_max'] ); ?>" min="0" max="500" class="small-text">
						<?php esc_html_e( 'comments per', 'wp-span-checker' ); ?>
						<input type="number" name="comment_rate_limit_window" id="comment_rate_limit_window" value="<?php echo esc_attr( (string) (int) $cfg['comment_rate_limit_window'] ); ?>" min="1" max="1440" class="small-text">
						<?php esc_html_e( 'minutes (0 = disable rate limit)', 'wp-span-checker' ); ?>
						<p class="description"><?php esc_html_e( 'Uses the visitor IP. Each attempt counts, including failed spam, to slow bots.', 'wp-span-checker' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="comment_rate_limit_scope"><?php esc_html_e( 'Flood scope', 'wp-span-checker' ); ?></label></th>
					<td>
						<select name="comment_rate_limit_scope" id="comment_rate_limit_scope">
							<option value="ip" <?php selected( $cfg['comment_rate_limit_scope'] ?? 'ip', 'ip' ); ?>><?php esc_html_e( 'Per IP (whole site)', 'wp-span-checker' ); ?></option>
							<option value="ip_post" <?php selected( $cfg['comment_rate_limit_scope'] ?? 'ip', 'ip_post' ); ?>><?php esc_html_e( 'Per IP per post', 'wp-span-checker' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Block duplicate comments', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'comment_block_duplicate',
								'checked'     => ! empty( $cfg['comment_block_duplicate'] ),
								'description' => __( 'Same body text cannot be posted twice on one post (existing non-spam comments).', 'wp-span-checker' ),
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="comment_min_length"><?php esc_html_e( 'Comment length', 'wp-span-checker' ); ?></label></th>
					<td>
						<?php esc_html_e( 'Min', 'wp-span-checker' ); ?>
						<input type="number" name="comment_min_length" id="comment_min_length" value="<?php echo esc_attr( (string) (int) $cfg['comment_min_length'] ); ?>" min="0" max="500" class="small-text">
						<?php esc_html_e( 'characters (0 = off)', 'wp-span-checker' ); ?>
						&nbsp; <?php esc_html_e( 'Max', 'wp-span-checker' ); ?>
						<input type="number" name="comment_max_length" id="comment_max_length" value="<?php echo esc_attr( (string) (int) $cfg['comment_max_length'] ); ?>" min="0" max="65535" class="small-text">
						<?php esc_html_e( '(0 = off)', 'wp-span-checker' ); ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="comment_max_links"><?php esc_html_e( 'Max links in body', 'wp-span-checker' ); ?></label></th>
					<td>
						<input type="number" name="comment_max_links" id="comment_max_links" value="<?php echo esc_attr( (string) (int) $cfg['comment_max_links'] ); ?>" min="0" max="100" class="small-text">
						<p class="description"><?php esc_html_e( 'Counts http(s) and HTML anchor hrefs. 0 = no extra cap when links are allowed.', 'wp-span-checker' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Allow links in comment body', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'comment_allow_links',
								'checked'     => ! empty( $cfg['comment_allow_links'] ),
								'description' => __( 'When unchecked, any http(s) or href in the body is rejected by the link policy component.', 'wp-span-checker' ),
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Block BBCode links', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'comment_block_bbcode',
								'checked'     => ! empty( $cfg['comment_block_bbcode'] ),
								'description' => __( 'Flags [url=…], [link=…], [img …] patterns.', 'wp-span-checker' ),
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Block script / iframe injection', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'comment_block_dangerous_markup',
								'checked'     => ! empty( $cfg['comment_block_dangerous_markup'] ),
								'description' => __( 'Rejects <script, javascript: URLs, data:text/html, and <iframe tags.', 'wp-span-checker' ),
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Block punycode / IDN abuse', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'comment_block_punycode_abuse',
								'checked'     => ! empty( $cfg['comment_block_punycode_abuse'] ),
								'description' => __( 'Rejects xn-- domains in email, website field, or comment text (homograph / IDN trick spam).', 'wp-span-checker' ),
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="comment_emoji_flood_max"><?php esc_html_e( 'Emoji / symbol flood limit', 'wp-span-checker' ); ?></label></th>
					<td>
						<input type="number" name="comment_emoji_flood_max" id="comment_emoji_flood_max" value="<?php echo esc_attr( (string) (int) ( $cfg['comment_emoji_flood_max'] ?? 0 ) ); ?>" min="0" max="500" class="small-text">
						<p class="description"><?php esc_html_e( 'Max emoji / pictographs in the comment body (Unicode property). 0 = disable this check.', 'wp-span-checker' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Block character spam', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'comment_block_excessive_repeats',
								'checked'     => ! empty( $cfg['comment_block_excessive_repeats'] ),
								'description' => __( 'Same character repeated many times in a row (keyboard spam).', 'wp-span-checker' ),
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="comment_max_caps_ratio"><?php esc_html_e( 'Shout / caps ratio', 'wp-span-checker' ); ?></label></th>
					<td>
						<input type="number" name="comment_max_caps_ratio" id="comment_max_caps_ratio" value="<?php echo esc_attr( (string) $caps_val ); ?>" min="0" max="0.99" step="0.01" class="small-text">
						<p class="description"><?php esc_html_e( 'If more than this fraction of letters are uppercase (min ~25 letters), reject. Use 0 to disable.', 'wp-span-checker' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Disposable email hosts', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'comment_block_disposable_email',
								'checked'     => ! empty( $cfg['comment_block_disposable_email'] ),
								'description' => __( 'Block addresses whose domain is in your Disposable Domains list.', 'wp-span-checker' ),
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Respect whitelist for email', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'comment_respect_whitelist',
								'checked'     => ! empty( $cfg['comment_respect_whitelist'] ),
								'description' => __( 'If the email domain matches Whitelist Domains, skip the disposable check.', 'wp-span-checker' ),
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Bundled spam phrase list', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'comment_builtin_bad_phrases',
								'checked'     => ! empty( $cfg['comment_builtin_bad_phrases'] ),
								'description' => __( 'English SEO / pharma / promo phrases shipped with the plugin (editable in code if needed).', 'wp-span-checker' ),
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="comment_block_keywords"><?php esc_html_e( 'Custom blocked phrases', 'wp-span-checker' ); ?></label></th>
					<td>
						<textarea name="comment_block_keywords" id="comment_block_keywords" rows="5" class="large-text" placeholder="<?php esc_attr_e( 'One phrase per line (substring match, case-insensitive). Lines starting with # are ignored.', 'wp-span-checker' ); ?>"><?php echo esc_textarea( (string) ( $cfg['comment_block_keywords'] ?? '' ) ); ?></textarea>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="comment_block_email_domains"><?php esc_html_e( 'Blocked email domains', 'wp-span-checker' ); ?></label></th>
					<td>
						<textarea name="comment_block_email_domains" id="comment_block_email_domains" rows="4" class="large-text" placeholder="<?php esc_attr_e( 'One domain per line, e.g. competitor.ru', 'wp-span-checker' ); ?>"><?php echo esc_textarea( (string) ( $cfg['comment_block_email_domains'] ?? '' ) ); ?></textarea>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Guest website field', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'comment_disallow_guest_website',
								'checked'     => ! empty( $cfg['comment_disallow_guest_website'] ),
								'description' => __( 'Guests must leave the website/URL field empty.', 'wp-span-checker' ),
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Block http(s) in website field', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'comment_block_http_author_url',
								'checked'     => ! empty( $cfg['comment_block_http_author_url'] ),
								'description' => __( 'Rejects comments where the author website field contains a web URL (logged-in users too).', 'wp-span-checker' ),
							)
						);
						?>
					</td>
				</tr>
			</table>
			</div>

			<div id="wsc-spam-ai" class="wsc-spam-tab-panel" style="display:none;">
			<p class="description" style="margin:12px 0;"><?php esc_html_e( 'AI moderation is optional. Turn it on under AI Span Settings and ensure post summaries exist for posts you want to protect.', 'wp-span-checker' ); ?></p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="comment_max_strikes"><?php esc_html_e( 'Max strikes before block', 'wp-span-checker' ); ?></label></th>
					<td>
						<input type="number" name="comment_max_strikes" id="comment_max_strikes" value="<?php echo esc_attr( (string) (int) $cfg['comment_max_strikes'] ); ?>" min="1" max="100" class="small-text">
						<p class="description"><?php esc_html_e( 'After this many rejected comments (per visitor), commenting is disabled for them. Further spam attempts can still add strikes toward a site ban.', 'wp-span-checker' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="comment_contact_page_id"><?php esc_html_e( 'Contact page', 'wp-span-checker' ); ?></label></th>
					<td>
						<?php
						wp_dropdown_pages(
							array(
								'name'              => 'comment_contact_page_id',
								'id'                => 'comment_contact_page_id',
								'selected'          => (int) ( $cfg['comment_contact_page_id'] ?? 0 ),
								'show_option_none'  => __( '— None —', 'wp-span-checker' ),
								'option_none_value' => '0',
							)
						);
						?>
						<p class="description"><?php esc_html_e( 'Shown to strike-blocked visitors so they know why comments are hidden and how to reach you. Recommended.', 'wp-span-checker' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Permanent site restriction', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'comment_site_ban_enabled',
								'checked'     => ! empty( $cfg['comment_site_ban_enabled'] ),
								'description' => __( 'After the strike count below, block the visitor from the front of the site (guests by IP; logged-in users lose access and cannot sign in again until you unblock them). The contact page above stays available.', 'wp-span-checker' ),
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="comment_site_ban_strikes"><?php esc_html_e( 'Strikes before permanent restriction', 'wp-span-checker' ); ?></label></th>
					<td>
						<input type="number" name="comment_site_ban_strikes" id="comment_site_ban_strikes" value="<?php echo esc_attr( (string) (int) ( $cfg['comment_site_ban_strikes'] ?? 10 ) ); ?>" min="2" max="500" class="small-text">
						<p class="description"><?php esc_html_e( 'Must be at least the “max strikes before block” value. Shared IPs can affect guests—unblock from Blocked commenters if needed.', 'wp-span-checker' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Product review mode (AI)', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'product_review_filter',
								'checked'     => ! empty( $cfg['product_review_filter'] ),
								'description' => __( 'When AI moderation runs, allow genuine short product reviews.', 'wp-span-checker' ),
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="system_prompt"><?php esc_html_e( 'AI system prompt (JSON only)', 'wp-span-checker' ); ?></label></th>
					<td>
						<textarea name="system_prompt" id="system_prompt" rows="8" class="large-text"><?php echo esc_textarea( $prompt ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Used only when AI is enabled and a post summary exists. Output must be JSON with status and message.', 'wp-span-checker' ); ?></p>
					</td>
				</tr>
			</table>
			</div>

			<p class="submit">
				<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save settings', 'wp-span-checker' ); ?>">
			</p>
		</form>
		<script>
		jQuery( function ( $ ) {
			$( '.wsc-spam-nav .nav-tab' ).on( 'click', function ( e ) {
				e.preventDefault();
				var id = $( this ).data( 'wsc-tab' );
				$( '.wsc-spam-nav .nav-tab' ).removeClass( 'nav-tab-active' );
				$( this ).addClass( 'nav-tab-active' );
				$( '.wsc-spam-tab-panel' ).hide();
				$( '#' + id ).show();
			} );
		} );
		</script>
	</div>
</div>
