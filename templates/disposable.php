<?php
/**
 * Disposable domains admin screen.
 *
 * @package VMS_Elements_Form_Guard
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$domain_type = 'disposable';
?>

<div class="wrap vefg-admin" id="vms-elements-form-guard-wrap">
	<?php
	vms_elements_form_guard_admin_page_header(
		__( 'Disposable Domains', 'vms-elements-form-guard' ),
		__( 'Block throwaway and temporary email hosts. The plugin ships with a starter list you can extend.', 'vms-elements-form-guard' )
	);
	?>

	<div class="vefg-card">
		<h2 class="vefg-card__title"><?php esc_html_e( 'Add domain', 'vms-elements-form-guard' ); ?></h2>
		<form id="add-domain-form" class="vefg-admin__inline-form">
			<input type="hidden" name="domain_type" value="<?php echo esc_attr( $domain_type ); ?>">
			<input type="text" name="domain" placeholder="<?php esc_attr_e( 'Enter domain', 'vms-elements-form-guard' ); ?>" required>
			<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Add domain', 'vms-elements-form-guard' ); ?>">
		</form>
	</div>

	<div class="vefg-card vefg-card--table">
		<h2 class="vefg-card__title"><?php esc_html_e( 'Disposable list', 'vms-elements-form-guard' ); ?></h2>
		<div class="vefg-admin__table-wrap">
			<table id="domains-table" class="display nowrap" style="width:100%">
				<thead>
				<tr>
					<th><?php esc_html_e( 'ID', 'vms-elements-form-guard' ); ?></th>
					<th><?php esc_html_e( 'Domain', 'vms-elements-form-guard' ); ?></th>
					<th><?php esc_html_e( 'Action', 'vms-elements-form-guard' ); ?></th>
				</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
	</div>
</div>
