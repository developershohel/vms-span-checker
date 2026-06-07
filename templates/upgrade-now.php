<?php
/**
 * Upgrade Now — lists Pro features and links to the product page.
 *
 * Shown from the free plugin only when Pro is not active. No license UI,
 * lock icons, or disabled feature screens — WordPress.org compliant upsell.
 *
 * @package VMS_Elements_Form_Guard
 *
 * @var string                           $upgrade_url  Marketing-site URL.
 * @var array<int, array<string, mixed>> $all_features Every Pro feature exposed by the bridge.
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Locals come from Admin_Menu render callback.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap vms-elements-form-guard-promo">
	<h1><?php esc_html_e( 'Upgrade Now', 'vms-elements-form-guard' ); ?></h1>

	<p class="description" style="max-width:720px;font-size:14px;">
		<?php esc_html_e( 'VMS Elements Form Guard Pro is a separate plugin with advanced form protection, AI summaries, and email templates. The free plugin on WordPress.org stays fully functional without a license key.', 'vms-elements-form-guard' ); ?>
	</p>

	<div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:28px;margin-top:16px;max-width:900px;">
		<h2 style="margin-top:0;font-size:16px;">
			<?php esc_html_e( 'Everything included in VMS Elements Form Guard Pro', 'vms-elements-form-guard' ); ?>
		</h2>

		<?php if ( ! empty( $all_features ) && is_array( $all_features ) ) : ?>
			<ul style="line-height:1.8;margin:0 0 0 18px;list-style:disc;">
				<?php
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
				?>
			</ul>
		<?php else : ?>
			<p><?php esc_html_e( 'Form Guard, Contact Guard, Subscribe Guard, Product Review Guard, AI summaries, and email templates.', 'vms-elements-form-guard' ); ?></p>
		<?php endif; ?>

		<p style="margin-top:24px;margin-bottom:0;">
			<a href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank" rel="noopener" class="button button-primary button-hero">
				<?php esc_html_e( 'Upgrade Now', 'vms-elements-form-guard' ); ?>
			</a>
		</p>
	</div>
</div>
