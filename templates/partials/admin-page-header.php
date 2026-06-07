<?php
/**
 * Shared admin heading for all VMS Elements Form Guard screens.
 *
 * @package VMS_Elements_Form_Guard
 *
 * Expected before include (set by vms_elements_form_guard_admin_page_header()):
 *
 * @var string $vefg_header_title Screen title (translated).
 * @var string $vefg_header_lede  Optional intro (translated); empty string to omit.
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$vefg_header_title = isset( $vefg_header_title ) ? (string) $vefg_header_title : '';
$vefg_header_lede  = isset( $vefg_header_lede ) ? (string) $vefg_header_lede : '';
?>
<div class="vefg-admin__header">
	<div class="vefg-admin__page-head vefg-admin__page-head--branded">
		<img
			class="vefg-admin__logo"
			src="<?php echo esc_url( VMS_ELEMENTS_FORM_GUARD_URL . 'assets/brand/logo.svg' ); ?>"
			width="40"
			height="40"
			alt=""
			decoding="async"
		/>
		<div class="vefg-admin__titles">
			<p class="vefg-admin__kicker"><?php esc_html_e( 'VMS Elements Form Guard', 'vms-elements-form-guard' ); ?></p>
			<h1 class="vefg-admin__title"><?php echo esc_html( $vefg_header_title ); ?></h1>
		</div>
	</div>
	<?php if ( '' !== $vefg_header_lede ) : ?>
		<p class="vefg-admin__lede"><?php echo esc_html( $vefg_header_lede ); ?></p>
	<?php endif; ?>
</div>
