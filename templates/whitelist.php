<?php
/**
 * Whitelist domains admin screen.
 *
 * @package WP_Span_Checker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$domain_type = 'whitelist';
?>

<div class="wrap wsc-admin" id="wp-span-checker-wrap">
	<?php
	wp_span_checker_admin_page_header(
		__( 'Whitelist Domains', 'wp-span-checker' ),
		__( 'Trusted hostnames that always pass validation—ideal for partners and internal tools.', 'wp-span-checker' )
	);
	?>

	<div class="wsc-card">
		<h2 class="wsc-card__title"><?php esc_html_e( 'Add domain', 'wp-span-checker' ); ?></h2>
		<form id="add-domain-form" class="wsc-admin__inline-form">
			<input type="hidden" name="domain_type" value="<?php echo esc_attr( $domain_type ); ?>">
			<input type="text" name="domain" placeholder="<?php esc_attr_e( 'Enter domain', 'wp-span-checker' ); ?>" required>
			<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Add domain', 'wp-span-checker' ); ?>">
		</form>
	</div>

	<div class="wsc-card wsc-card--table">
		<h2 class="wsc-card__title"><?php esc_html_e( 'Whitelist entries', 'wp-span-checker' ); ?></h2>
		<div class="wsc-admin__table-wrap">
			<table id="domains-table" class="display nowrap" style="width:100%">
				<thead>
				<tr>
					<th><?php esc_html_e( 'ID', 'wp-span-checker' ); ?></th>
					<th><?php esc_html_e( 'Domain', 'wp-span-checker' ); ?></th>
					<th><?php esc_html_e( 'Action', 'wp-span-checker' ); ?></th>
				</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
	</div>
</div>
