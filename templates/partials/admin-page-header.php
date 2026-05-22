<?php
/**
 * Shared admin heading for all VMS Span Checker screens.
 *
 * @package VMS_Span_Checker
 *
 * Expected before include (set by vms_span_checker_admin_page_header()):
 *
 * @var string $wsc_header_title Screen title (translated).
 * @var string $wsc_header_lede  Optional intro (translated); empty string to omit.
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wsc_header_title = isset( $wsc_header_title ) ? (string) $wsc_header_title : '';
$wsc_header_lede  = isset( $wsc_header_lede ) ? (string) $wsc_header_lede : '';
?>
<div class="wsc-admin__header">
	<div class="wsc-admin__page-head wsc-admin__page-head--branded">
		<img
			class="wsc-admin__logo"
			src="<?php echo esc_url( VMS_SPAN_CHECKER_URL . 'assets/brand/logo.svg' ); ?>"
			width="40"
			height="40"
			alt=""
			decoding="async"
		/>
		<div class="wsc-admin__titles">
			<p class="wsc-admin__kicker"><?php esc_html_e( 'VMS Span Checker', 'vms-span-checker' ); ?></p>
			<h1 class="wsc-admin__title"><?php echo esc_html( $wsc_header_title ); ?></h1>
		</div>
	</div>
	<?php if ( '' !== $wsc_header_lede ) : ?>
		<p class="wsc-admin__lede"><?php echo esc_html( $wsc_header_lede ); ?></p>
	<?php endif; ?>
</div>
