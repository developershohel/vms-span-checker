<?php
/**
 * Whitelist domains admin screen.
 *
 * @package VMS_Elements_Form_Guard
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$domain_type = 'whitelist';
?>

<div class="wrap vefg-admin" id="vms-elements-form-guard-wrap">
	<?php
	vms_elements_form_guard_admin_page_header(
		__( 'Whitelist Domains', 'vms-elements-form-guard' ),
		__( 'Trusted hostnames that always pass validation—ideal for partners and internal tools.', 'vms-elements-form-guard' )
	);
	?>

	<div class="vefg-card">
		<h2 class="vefg-card__title"><?php esc_html_e( 'Add domain', 'vms-elements-form-guard' ); ?></h2>
		<form id="add-domain-form" class="vefg-admin__inline-form">
			<input type="hidden" name="domain_type" value="<?php echo esc_attr( $domain_type ); ?>">
			<input type="text" name="domain" placeholder="<?php esc_attr_e( 'Enter domain', 'vms-elements-form-guard' ); ?>" required>
			<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Add domain', 'vms-elements-form-guard' ); ?>">
		</form>
		<p style="margin-top:12px;">
			<button type="button" id="vefg-import-whitelist-seed" class="button button-secondary">
				<?php esc_html_e( 'Import top provider whitelist (SQL)', 'vms-elements-form-guard' ); ?>
			</button>
		</p>
		<p class="description" style="margin-top:6px;">
			<?php esc_html_e( 'Loads bundled major email-provider domains from includes/data/whitelist.sql using insert-ignore (duplicates are skipped).', 'vms-elements-form-guard' ); ?>
		</p>
	</div>

	<div class="vefg-card vefg-card--table">
		<h2 class="vefg-card__title"><?php esc_html_e( 'Whitelist entries', 'vms-elements-form-guard' ); ?></h2>
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
