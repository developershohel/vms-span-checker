<?php
/**
 * Dump a WordPress table to a .sql file using $wpdb
 *
 * @param string $table_name Table name without prefix, e.g., 'posts'
 * @param string $file_path Full path to save the SQL file, e.g., 'C:/backups/wp_posts.sql'
 * @return string Success or error message
 */
function wp_dump_table($table_name, $file_path) {
	global $wpdb;

	// Full table name with prefix
	$full_table_name = $wpdb->prefix . $table_name;

	// Check if table exists
	$exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'");
	if (!$exists) {
		return "Table $full_table_name does not exist!";
	}

	// Get DB credentials from WordPress config
	$db_name = DB_NAME;
	$db_user = DB_USER;
	$db_pass = DB_PASSWORD;
	$db_host = DB_HOST;

	// Escape paths for Windows
	$file_path = str_replace('\\', '/', $file_path);

	// Build the mysqldump command
	$command = "mysqldump --user={$db_user} --password={$db_pass} --host={$db_host} {$db_name} {$full_table_name} > {$file_path}";

	// Execute command
	$output = null;
	$return_var = null;
	exec($command, $output, $return_var);

	if ($return_var === 0) {
		return "Table $full_table_name successfully exported to $file_path";
	} else {
		return "Error exporting table. Return code: $return_var";
	}
}

function wp_span_checker_get_user_ip() {
	$ip_keys = [
		'HTTP_CF_CONNECTING_IP', // Cloudflare
		'HTTP_X_FORWARDED_FOR',   // Proxy
		'HTTP_CLIENT_IP',         // Shared Internet
		'REMOTE_ADDR',            // Fallback
	];

	foreach ($ip_keys as $key) {
		if (!empty($_SERVER[$key])) {
			$ip = $_SERVER[$key];

			// If multiple IPs (e.g., X-Forwarded-For), take the first one
			if ( str_contains( $ip, ',' ) ) {
				$ip = explode(',', $ip)[0];
			}

			return trim($ip);
		}
	}

	return '0.0.0.0';
}
