<?php
/**
 * Upsell / "Upgrade to Pro" template.
 *
 * Used as the page callback for every locked Pro menu stub when the Pro
 * plugin is not installed or its license check fails.
 *
 * @package VMS_Span_Checker
 *
 * @var array<string, mixed> $feature       Pro feature metadata (slug, title, description).
 * @var string               $upgrade_url   Marketing-site URL.
 * @var string               $license_url   Local License page URL.
 * @var array<int, array<string, mixed>> $all_features Every Pro feature exposed by the bridge.
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Locals come from Admin_Menu render callback.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$title       = isset( $feature['title'] ) ? (string) $feature['title'] : __( 'Pro feature', 'vms-span-checker' );
$description = isset( $feature['description'] ) ? (string) $feature['description'] : '';
$slug        = isset( $feature['slug'] ) ? (string) $feature['slug'] : '';
?>
<div class="wrap vms-span-checker-promo">
	<h1 style="display:flex;align-items:center;gap:8px;">
		<span class="dashicons dashicons-lock" style="color:#d63638;font-size:24px;width:24px;height:24px;"></span>
		<?php echo esc_html( $title ); ?>
		<span style="font-size:12px;background:#d63638;color:#fff;padding:2px 8px;border-radius:10px;letter-spacing:.5px;">
			<?php esc_html_e( 'PRO', 'vms-span-checker' ); ?>
		</span>
	</h1>

	<div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;margin-top:16px;max-width:1100px;">
		<div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:28px;">
			<p style="font-size:15px;color:#1d2327;">
				<?php echo esc_html( $description ); ?>
			</p>

			<h2 style="margin-top:20px;font-size:16px;">
				<?php esc_html_e( 'Everything you get in VMS Span Checker Pro', 'vms-span-checker' ); ?>
			</h2>
			<ul style="line-height:1.8;margin-left:18px;list-style:disc;">
				<?php
				if ( ! empty( $all_features ) && is_array( $all_features ) ) {
					foreach ( $all_features as $f ) {
						if ( empty( $f['title'] ) ) {
							continue;
						}
						printf(
							'<li><strong>%1$s</strong> — %2$s</li>',
							esc_html( (string) $f['title'] ),
							esc_html( isset( $f['description'] ) ? (string) $f['description'] : '' )
						);
					}
				}
				?>
			</ul>

			<p style="margin-top:24px;">
				<a href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank" class="button button-primary button-hero">
					<?php esc_html_e( 'Upgrade to Pro', 'vms-span-checker' ); ?>
				</a>
				<a href="<?php echo esc_url( $license_url ); ?>" class="button button-large" style="margin-left:8px;">
					<?php esc_html_e( 'Already have a license? Activate it', 'vms-span-checker' ); ?>
				</a>
			</p>
		</div>

		<div style="background:linear-gradient(135deg,#1e293b 0%,#0f172a 100%);color:#e5e7eb;border-radius:8px;padding:28px;">
			<h2 style="color:#fff;margin:0 0 12px;font-size:16px;">
				<?php esc_html_e( 'Why upgrade?', 'vms-span-checker' ); ?>
			</h2>
			<ul style="line-height:1.8;margin:0 0 0 18px;list-style:disc;">
				<li><?php esc_html_e( 'AI-assisted moderation on every form', 'vms-span-checker' ); ?></li>
				<li><?php esc_html_e( 'Custom-form mapping (Form Guard)', 'vms-span-checker' ); ?></li>
				<li><?php esc_html_e( 'WooCommerce review moderation', 'vms-span-checker' ); ?></li>
				<li><?php esc_html_e( 'Visual email-template editor', 'vms-span-checker' ); ?></li>
				<li><?php esc_html_e( 'AI summaries for posts & products', 'vms-span-checker' ); ?></li>
				<li><?php esc_html_e( 'Priority email support', 'vms-span-checker' ); ?></li>
			</ul>
		</div>
	</div>

	<?php if ( '' !== $slug ) : ?>
		<input type="hidden" name="vms_promo_for" value="<?php echo esc_attr( $slug ); ?>" />
	<?php endif; ?>
</div>
