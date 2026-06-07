<?php
/**
 * Uninstall: drop custom tables and plugin options.
 *
 * Direct database calls (DROP TABLE) are required during uninstall to remove
 * plugin-owned custom tables; identifiers are built from `$wpdb->prefix` and
 * a hardcoded list, so they are safe and intentional.
 *
 * License-related records (transient + hashed option keys) are removed by
 * the Pro plugin's own uninstall.php — the free plugin never writes them.
 *
 * @package VMS_Elements_Form_Guard
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

// Free-owned tables only. Pro tables (`vms_elements_form_guard_form_settings`,
// `vms_elements_form_guard_forms`, `vms_elements_form_guard_ai_post_summary`)
// are dropped by the Pro plugin's own uninstall.php.
$tables = array(
	'vms_elements_form_guard_whitelist_domains',
	'vms_elements_form_guard_disposable_domains',
	'vms_elements_form_guard_logs',
	'vms_elements_form_guard_api_keys',
	'vms_elements_form_guard_comment_enforcement',
	// Legacy table names (pre-prefix-normalization) — dropped if the rename
	// migration never ran on this install.
	'span_whitelist_domains',
	'span_disposable_domains',
);

$options = array(
	'vefg-google-config',
	'vefg-virustotal-config',
	'vefg-ai-span-config',
	'vefg-registration-guard',
	'vefg-recaptcha-config',
	'vefg-error-messages',
	'vms_elements_form_guard_db_version',
	'vms_elements_form_guard_schema_version',
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
	foreach ( $blog_ids as $site_blog_id ) {
		switch_to_blog( (int) $site_blog_id );
		$clean_site();
		restore_current_blog();
	}
} else {
	$clean_site();
}
