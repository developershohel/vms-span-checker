<?php
/**
 * Uninstall: drop custom tables and plugin options.
 *
 * Direct database calls (DROP TABLE) are required during uninstall to remove
 * plugin-owned custom tables; identifiers are built from `$wpdb->prefix` and
 * a hardcoded list, so they are safe and intentional.
 *
 * @package VMS_Span_Checker
 *
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange
 * phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
 * phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Free-owned tables only. Pro tables (`vms_span_checker_form_settings`,
// `vms_span_checker_forms`, `vms_span_checker_ai_post_summary`) are dropped by
// the Pro plugin's own uninstall.php.
$tables = array(
	'span_whitelist_domains',
	'span_disposable_domains',
	'vms_span_checker_logs',
	'vms_span_checker_api_keys',
	'vms_span_checker_comment_enforcement',
);

$options = array(
	'wsc-google-config',
	'wsc-virustotal-config',
	'wsc-ai-span-config',
	'wsc-registration-guard',
	'vms_span_checker_db_version',
	'vms_span_checker_schema_version',
);

/**
 * Remove data for one site.
 */
$clean_site = static function () use ( $wpdb, $tables, $options ) {
	foreach ( $options as $option ) {
		delete_option( $option );
	}
	foreach ( $tables as $table ) {
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$table}" );
	}
};

if ( is_multisite() ) {
	$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );
	foreach ( $blog_ids as $blog_id ) {
		switch_to_blog( (int) $blog_id );
		$clean_site();
		restore_current_blog();
	}
} else {
	$clean_site();
}
