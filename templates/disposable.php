<?php
/**
 * Disposable domains admin screen.
 *
 * @package VMS_Span_Checker
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$domain_type = 'disposable';
?>

<div class="wrap wsc-admin" id="vms-span-checker-wrap">
	<?php
	vms_span_checker_admin_page_header(
		__( 'Disposable Domains', 'vms-span-checker' ),
		__( 'Block throwaway and temporary email hosts. The plugin ships with a starter list you can extend.', 'vms-span-checker' )
	);
	?>

	<div class="wsc-card">
		<h2 class="wsc-card__title"><?php esc_html_e( 'Add domain', 'vms-span-checker' ); ?></h2>
		<form id="add-domain-form" class="wsc-admin__inline-form">
			<input type="hidden" name="domain_type" value="<?php echo esc_attr( $domain_type ); ?>">
			<input type="text" name="domain" placeholder="<?php esc_attr_e( 'Enter domain', 'vms-span-checker' ); ?>" required>
			<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Add domain', 'vms-span-checker' ); ?>">
		</form>
	</div>

	<div class="wsc-card wsc-card--table">
		<h2 class="wsc-card__title"><?php esc_html_e( 'Disposable list', 'vms-span-checker' ); ?></h2>
		<div class="wsc-admin__table-wrap">
			<table id="domains-table" class="display nowrap" style="width:100%">
				<thead>
				<tr>
					<th><?php esc_html_e( 'ID', 'vms-span-checker' ); ?></th>
					<th><?php esc_html_e( 'Domain', 'vms-span-checker' ); ?></th>
					<th><?php esc_html_e( 'Action', 'vms-span-checker' ); ?></th>
				</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
	</div>
</div>
