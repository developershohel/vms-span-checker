<?php
/**
 * Whitelist domains admin screen.
 *
 * @package VMS_Span_Checker
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$domain_type = 'whitelist';
?>

<div class="wrap wsc-admin" id="vms-span-checker-wrap">
	<?php
	vms_span_checker_admin_page_header(
		__( 'Whitelist Domains', 'vms-span-checker' ),
		__( 'Trusted hostnames that always pass validation—ideal for partners and internal tools.', 'vms-span-checker' )
	);
	?>

	<div class="wsc-card">
		<h2 class="wsc-card__title"><?php esc_html_e( 'Add domain', 'vms-span-checker' ); ?></h2>
		<form id="add-domain-form" class="wsc-admin__inline-form">
			<input type="hidden" name="domain_type" value="<?php echo esc_attr( $domain_type ); ?>">
			<input type="text" name="domain" placeholder="<?php esc_attr_e( 'Enter domain', 'vms-span-checker' ); ?>" required>
			<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Add domain', 'vms-span-checker' ); ?>">
		</form>
		<p style="margin-top:12px;">
			<button type="button" id="wsc-import-whitelist-seed" class="button button-secondary">
				<?php esc_html_e( 'Import top provider whitelist (SQL)', 'vms-span-checker' ); ?>
			</button>
		</p>
		<p class="description" style="margin-top:6px;">
			<?php esc_html_e( 'Loads bundled major email-provider domains from includes/data/whitelist.sql using insert-ignore (duplicates are skipped).', 'vms-span-checker' ); ?>
		</p>
	</div>

	<div class="wsc-card wsc-card--table">
		<h2 class="wsc-card__title"><?php esc_html_e( 'Whitelist entries', 'vms-span-checker' ); ?></h2>
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
