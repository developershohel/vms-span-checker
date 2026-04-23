<?php
/**
 * Shared admin heading for all WP Span Checker screens.
 *
 * @package WP_Span_Checker
 *
 * Expected before include (set by wp_span_checker_admin_page_header()):
 *
 * @var string $wsc_header_title Screen title (translated).
 * @var string $wsc_header_lede  Optional intro (translated); empty string to omit.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wsc_header_lede = isset( $wsc_header_lede ) ? (string) $wsc_header_lede : '';
?>
<div class="wsc-admin__header">
	<div class="wsc-admin__page-head">
		<p class="wsc-admin__kicker"><?php esc_html_e( 'WP Span Checker', 'wp-span-checker' ); ?></p>
		<h1 class="wsc-admin__title"><?php echo esc_html( $wsc_header_title ); ?></h1>
	</div>
	<?php if ( '' !== $wsc_header_lede ) : ?>
		<p class="wsc-admin__lede"><?php echo esc_html( $wsc_header_lede ); ?></p>
	<?php endif; ?>
</div>
