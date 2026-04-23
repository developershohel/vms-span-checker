<?php
/**
 * Uninstall: drop custom tables and plugin options.
 *
 * @package WP_Span_Checker
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$tables = array(
	'span_whitelist_domains',
	'span_disposable_domains',
	'span_checker_form_settings',
	'span_checker_logs',
	'span_checker_forms',
	'span_checker_api_keys',
	'span_checker_ai_post_summary',
	'span_checker_comment_enforcement',
);

$options = array(
	'wsc-google-config',
	'wsc-virustotal-config',
	'wsc-ai-span-config',
	'wsc-registration-guard',
	'wp_span_checker_db_version',
	'wp_span_checker_schema_version',
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
