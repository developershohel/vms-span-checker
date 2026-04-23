<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * @package YourPluginName
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Example 1: Delete plugin options from wp_options
 */
delete_option( 'wsc_plugin_settings' );
delete_option( 'wsc_other_option' );

// If you used multisite, remove options from each site
if ( is_multisite() ) {
	global $wpdb;
	$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
	foreach ( $blog_ids as $blog_id ) {
		switch_to_blog( $blog_id );
		delete_option( 'wsc_plugin_settings' );
		delete_option( 'wsc_other_option' );
		restore_current_blog();
	}
}

/**
 * Example 2: Remove custom DB tables (if you created any)
 */
global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wsc_forms" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wsc_form_entries" );

/**
 * Example 3: Remove post meta or custom posts if your plugin added them
 */
// Delete all post meta keys you added
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
		'wsc_%'
	)
);

// Delete custom post type content (if used)
$wpdb->query(
	"DELETE FROM {$wpdb->posts} WHERE post_type = 'wsc_form'"
);
