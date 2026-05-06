<?php
/**
 * Plugin activation: database tables and initial data.
 *
 * @package WP_Span_Checker
 */

namespace WP_Span_Checker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Activator {

	/**
	 * Create custom tables and seed defaults.
	 */
	public static function activate(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		$tables = array(
			"CREATE TABLE {$wpdb->prefix}span_whitelist_domains (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				domain varchar(255) NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY domain (domain)
			) $charset_collate;",
			"CREATE TABLE {$wpdb->prefix}span_disposable_domains (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				domain varchar(255) NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY domain (domain)
			) $charset_collate;",
			"CREATE TABLE {$wpdb->prefix}span_checker_form_settings (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				form_type varchar(50) NOT NULL DEFAULT '',
				page_id text NOT NULL,
				form_id varchar(191) NOT NULL DEFAULT '',
				form_class varchar(500) NOT NULL DEFAULT '',
				submit_selector varchar(500) NOT NULL DEFAULT '',
				settings longtext NULL,
				is_webrisk tinyint(1) NOT NULL DEFAULT 0,
				is_virustotal tinyint(1) NOT NULL DEFAULT 0,
				PRIMARY KEY  (id),
				KEY form_id (form_id(100))
			) $charset_collate;",
			"CREATE TABLE {$wpdb->prefix}span_checker_logs (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				type varchar(50) NOT NULL DEFAULT '',
				ip varchar(100) NOT NULL DEFAULT '',
				domain varchar(255) NOT NULL DEFAULT '',
				status varchar(20) NOT NULL DEFAULT '',
				message text NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY created_at (created_at),
				KEY status_type (status,type)
			) $charset_collate;",
			"CREATE TABLE {$wpdb->prefix}span_checker_forms (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				form_id varchar(191) NOT NULL DEFAULT '',
				fields longtext NULL,
				PRIMARY KEY  (id),
				KEY form_id (form_id(100))
			) $charset_collate;",
			"CREATE TABLE {$wpdb->prefix}span_checker_api_keys (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				key_name varchar(100) NOT NULL DEFAULT '',
				api_key text NOT NULL,
				status varchar(20) NOT NULL DEFAULT 'active',
				created_at datetime NOT NULL,
				PRIMARY KEY  (id)
			) $charset_collate;",
			"CREATE TABLE {$wpdb->prefix}span_checker_ai_post_summary (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				post_id bigint(20) unsigned NOT NULL,
				summary longtext NULL,
				status varchar(20) NOT NULL DEFAULT 'pending',
				last_error text NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY post_id (post_id),
				KEY status (status)
			) $charset_collate;",
			"CREATE TABLE {$wpdb->prefix}span_checker_comment_enforcement (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				actor_key varchar(191) NOT NULL,
				actor_label varchar(191) NOT NULL DEFAULT '',
				strikes int(10) unsigned NOT NULL DEFAULT 0,
				blocked tinyint(1) NOT NULL DEFAULT 0,
				site_banned tinyint(1) NOT NULL DEFAULT 0,
				last_ip varchar(45) NOT NULL DEFAULT '',
				blocked_at datetime NULL,
				last_strike_at datetime NULL,
				last_reason varchar(500) NOT NULL DEFAULT '',
				PRIMARY KEY  (id),
				UNIQUE KEY actor_key (actor_key),
				KEY blocked (blocked),
				KEY site_banned_ip (site_banned, last_ip(40))
			) $charset_collate;",
		);

		foreach ( $tables as $sql ) {
			dbDelta( $sql );
		}

		self::maybe_seed_disposable_domains();
		self::maybe_seed_whitelist_domains();

		update_option( 'wp_span_checker_db_version', WP_SPAN_CHECKER_VERSION );
		update_option( 'wp_span_checker_schema_version', '5' );
	}

	/**
	 * Schema migrations (page_id width, AI tables).
	 */
	public static function maybe_upgrade_schema(): void {
		global $wpdb;

		$current = (string) get_option( 'wp_span_checker_schema_version', '1' );

		if ( version_compare( $current, '2', '<' ) ) {
			$table = $wpdb->prefix . 'span_checker_form_settings';
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name uses trusted prefix.
			$exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
			if ( $exists ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name uses trusted prefix.
				$wpdb->query( "ALTER TABLE {$table} MODIFY COLUMN page_id text NOT NULL" );
			}
			update_option( 'wp_span_checker_schema_version', '2' );
			$current = '2';
		}

		if ( version_compare( $current, '3', '<' ) ) {
			self::install_ai_tables();
			update_option( 'wp_span_checker_schema_version', '3' );
			$current = '3';
		}

		if ( version_compare( $current, '4', '<' ) ) {
			$table = $wpdb->prefix . 'span_checker_comment_enforcement';
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name uses trusted prefix.
			$exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
			if ( $exists ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name uses trusted prefix.
				$wpdb->query( "ALTER TABLE {$table} MODIFY COLUMN actor_key varchar(191) NOT NULL" );
			}
			update_option( 'wp_span_checker_schema_version', '4' );
			$current = '4';
		}

		if ( version_compare( $current, '5', '<' ) ) {
			$table = $wpdb->prefix . 'span_checker_comment_enforcement';
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name uses trusted prefix.
			$exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
			if ( $exists ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$has_sb = $wpdb->get_results( "SHOW COLUMNS FROM {$table} LIKE 'site_banned'" );
				if ( empty( $has_sb ) ) {
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$wpdb->query( "ALTER TABLE {$table} ADD COLUMN site_banned tinyint(1) NOT NULL DEFAULT 0 AFTER blocked" );
				}
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$has_ip = $wpdb->get_results( "SHOW COLUMNS FROM {$table} LIKE 'last_ip'" );
				if ( empty( $has_ip ) ) {
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$wpdb->query( "ALTER TABLE {$table} ADD COLUMN last_ip varchar(45) NOT NULL DEFAULT '' AFTER site_banned" );
				}
			}
			update_option( 'wp_span_checker_schema_version', '5' );
			$current = '5';
		}

		if ( version_compare( $current, '6', '<' ) ) {
			$table = $wpdb->prefix . 'span_checker_form_settings';
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name uses trusted prefix.
			$exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
			if ( $exists ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$has_col = $wpdb->get_results( "SHOW COLUMNS FROM {$table} LIKE 'submit_selector'" );
				if ( empty( $has_col ) ) {
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$wpdb->query( "ALTER TABLE {$table} ADD COLUMN submit_selector varchar(500) NOT NULL DEFAULT '' AFTER form_class" );
				}
			}
			update_option( 'wp_span_checker_schema_version', '6' );
		}
	}

	/**
	 * AI / comment moderation tables (upgrade path).
	 */
	public static function install_ai_tables(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = array(
			"CREATE TABLE {$wpdb->prefix}span_checker_ai_post_summary (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				post_id bigint(20) unsigned NOT NULL,
				summary longtext NULL,
				status varchar(20) NOT NULL DEFAULT 'pending',
				last_error text NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY post_id (post_id),
				KEY status (status)
			) $charset_collate;",
			"CREATE TABLE {$wpdb->prefix}span_checker_comment_enforcement (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				actor_key varchar(191) NOT NULL,
				actor_label varchar(191) NOT NULL DEFAULT '',
				strikes int(10) unsigned NOT NULL DEFAULT 0,
				blocked tinyint(1) NOT NULL DEFAULT 0,
				site_banned tinyint(1) NOT NULL DEFAULT 0,
				last_ip varchar(45) NOT NULL DEFAULT '',
				blocked_at datetime NULL,
				last_strike_at datetime NULL,
				last_reason varchar(500) NOT NULL DEFAULT '',
				PRIMARY KEY  (id),
				UNIQUE KEY actor_key (actor_key),
				KEY blocked (blocked),
				KEY site_banned_ip (site_banned, last_ip(40))
			) $charset_collate;",
		);

		foreach ( $sql as $stmt ) {
			dbDelta( $stmt );
		}
	}

	/**
	 * Insert bundled disposable domains when the table is empty.
	 */
	private static function maybe_seed_disposable_domains(): void {
		global $wpdb;

		$table = $wpdb->prefix . 'span_disposable_domains';
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		if ( $count > 0 ) {
			return;
		}

		$file = WP_SPAN_CHECKER_DIR . 'includes/data/disposable-domains.php';
		if ( ! is_readable( $file ) ) {
			return;
		}

		$domains = include $file;
		if ( ! is_array( $domains ) ) {
			return;
		}

		foreach ( $domains as $domain ) {
			$domain = sanitize_text_field( $domain );
			if ( $domain === '' ) {
				continue;
			}
			$wpdb->insert(
				$table,
				array( 'domain' => $domain ),
				array( '%s' )
			);
		}
	}

	/**
	 * Insert bundled whitelist email provider domains when the table is empty.
	 */
	private static function maybe_seed_whitelist_domains(): void {
		global $wpdb;

		$table = $wpdb->prefix . 'span_whitelist_domains';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name uses trusted prefix.
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		if ( $count > 0 ) {
			return;
		}

		$file = WP_SPAN_CHECKER_DIR . 'includes/data/whitelist.sql';
		if ( ! is_readable( $file ) ) {
			return;
		}

		$sql_content = file_get_contents( $file );
		if ( empty( $sql_content ) ) {
			return;
		}

		preg_match_all( "/VALUES\s*\(\s*'([^']+)'\s*\)/i", $sql_content, $matches );
		if ( empty( $matches[1] ) ) {
			return;
		}

		$domains = array_unique( $matches[1] );
		foreach ( $domains as $domain ) {
			$domain = sanitize_text_field( strtolower( trim( $domain ) ) );
			if ( $domain === '' ) {
				continue;
			}
			$wpdb->insert(
				$table,
				array( 'domain' => $domain ),
				array( '%s' )
			);
		}
	}
}
