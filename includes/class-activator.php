<?php
/**
 * Plugin activation: database tables and initial data.
 *
 * The queries in this file create and seed plugin-owned custom tables. They
 * intentionally use direct `$wpdb` calls (no caching, schema changes, and
 * interpolated table names built from `$wpdb->prefix`). All identifiers are
 * hardcoded inside the plugin and never derived from user input.
 *
 * @package VMS_Elements_Form_Guard
 *
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange
 * phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
 * phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
 * phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter
 */

namespace VMS_Elements_Form_Guard;

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

		// Migrate any pre-existing legacy `span_*` domain tables to the plugin
		// prefix before (re)creating the schema so existing data is preserved.
		self::rename_legacy_domain_tables();

		// Free plugin owns these tables. The Pro plugin's own activator creates
		// `vms_elements_form_guard_form_settings`, `vms_elements_form_guard_forms` and
		// `vms_elements_form_guard_ai_post_summary` (Pro tables). Pre-existing
		// installs that already have those tables keep them — we never DROP.
		$tables = array(
			"CREATE TABLE {$wpdb->prefix}vms_elements_form_guard_whitelist_domains (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				domain varchar(255) NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY domain (domain)
			) $charset_collate;",
			"CREATE TABLE {$wpdb->prefix}vms_elements_form_guard_disposable_domains (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				domain varchar(255) NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY domain (domain)
			) $charset_collate;",
			"CREATE TABLE {$wpdb->prefix}vms_elements_form_guard_logs (
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
			"CREATE TABLE {$wpdb->prefix}vms_elements_form_guard_api_keys (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				key_name varchar(100) NOT NULL DEFAULT '',
				api_key text NOT NULL,
				status varchar(20) NOT NULL DEFAULT 'active',
				created_at datetime NOT NULL,
				PRIMARY KEY  (id)
			) $charset_collate;",
			"CREATE TABLE {$wpdb->prefix}vms_elements_form_guard_comment_enforcement (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				actor_key varchar(191) NOT NULL,
				actor_label varchar(191) NOT NULL DEFAULT '',
				user_id bigint(20) unsigned NULL,
				strikes int(10) unsigned NOT NULL DEFAULT 0,
				blocked tinyint(1) NOT NULL DEFAULT 0,
				site_banned tinyint(1) NOT NULL DEFAULT 0,
				login_blocked tinyint(1) NOT NULL DEFAULT 0,
				last_ip varchar(45) NOT NULL DEFAULT '',
				blocked_at datetime NULL,
				last_strike_at datetime NULL,
				strikes_expire_at datetime NULL,
				last_reason varchar(500) NOT NULL DEFAULT '',
				strike_source varchar(50) NOT NULL DEFAULT 'comment',
				PRIMARY KEY  (id),
				UNIQUE KEY actor_key (actor_key),
				KEY blocked (blocked),
				KEY site_banned_ip (site_banned, last_ip(40)),
				KEY user_id (user_id),
				KEY login_blocked (login_blocked)
			) $charset_collate;",
		);

		foreach ( $tables as $sql ) {
			dbDelta( $sql );
		}

		self::maybe_seed_disposable_domains();
		self::maybe_seed_whitelist_domains();

		update_option( 'vms_elements_form_guard_db_version', VMS_ELEMENTS_FORM_GUARD_VERSION );
		update_option( 'vms_elements_form_guard_schema_version', '11' );
	}

	/**
	 * Rename legacy `span_*` domain tables to the plugin prefix.
	 *
	 * Older installs created `{$prefix}span_whitelist_domains` and
	 * `{$prefix}span_disposable_domains`. These are renamed in place so the
	 * stored domain lists survive the prefix normalization. No-op when the
	 * legacy tables are absent or the new tables already exist.
	 */
	private static function rename_legacy_domain_tables(): void {
		global $wpdb;

		$renames = array(
			'span_whitelist_domains'  => 'vms_elements_form_guard_whitelist_domains',
			'span_disposable_domains' => 'vms_elements_form_guard_disposable_domains',
		);

		foreach ( $renames as $old => $new ) {
			$old_table = $wpdb->prefix . $old;
			$new_table = $wpdb->prefix . $new;

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names use trusted prefix.
			$old_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$old_table}'" );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names use trusted prefix.
			$new_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$new_table}'" );

			if ( $old_exists && ! $new_exists ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names use trusted prefix.
				$wpdb->query( "RENAME TABLE {$old_table} TO {$new_table}" );
			}
		}
	}

	/**
	 * Schema migrations (page_id width, AI tables).
	 */
	public static function maybe_upgrade_schema(): void {
		global $wpdb;

		$current = (string) get_option( 'vms_elements_form_guard_schema_version', '1' );

		if ( version_compare( $current, '2', '<' ) ) {
			$table = $wpdb->prefix . 'vms_elements_form_guard_form_settings';
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name uses trusted prefix.
			$exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
			if ( $exists ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name uses trusted prefix.
				$wpdb->query( "ALTER TABLE {$table} MODIFY COLUMN page_id text NOT NULL" );
			}
			update_option( 'vms_elements_form_guard_schema_version', '2' );
			$current = '2';
		}

		if ( version_compare( $current, '3', '<' ) ) {
			self::install_ai_tables();
			update_option( 'vms_elements_form_guard_schema_version', '3' );
			$current = '3';
		}

		if ( version_compare( $current, '4', '<' ) ) {
			$table = $wpdb->prefix . 'vms_elements_form_guard_comment_enforcement';
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name uses trusted prefix.
			$exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
			if ( $exists ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name uses trusted prefix.
				$wpdb->query( "ALTER TABLE {$table} MODIFY COLUMN actor_key varchar(191) NOT NULL" );
			}
			update_option( 'vms_elements_form_guard_schema_version', '4' );
			$current = '4';
		}

		if ( version_compare( $current, '5', '<' ) ) {
			$table = $wpdb->prefix . 'vms_elements_form_guard_comment_enforcement';
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
			update_option( 'vms_elements_form_guard_schema_version', '5' );
			$current = '5';
		}

		if ( version_compare( $current, '6', '<' ) ) {
			$table = $wpdb->prefix . 'vms_elements_form_guard_form_settings';
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
			update_option( 'vms_elements_form_guard_schema_version', '6' );
			$current = '6';
		}

		if ( version_compare( $current, '7', '<' ) ) {
			$table = $wpdb->prefix . 'vms_elements_form_guard_form_settings';
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name uses trusted prefix.
			$exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
			if ( $exists ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$has_auto = $wpdb->get_results( "SHOW COLUMNS FROM {$table} LIKE 'auto_validation'" );
				if ( empty( $has_auto ) ) {
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$wpdb->query( "ALTER TABLE {$table} ADD COLUMN auto_validation tinyint(1) NOT NULL DEFAULT 1 AFTER submit_selector" );
				}
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$has_rules = $wpdb->get_results( "SHOW COLUMNS FROM {$table} LIKE 'auto_rules'" );
				if ( empty( $has_rules ) ) {
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$wpdb->query( "ALTER TABLE {$table} ADD COLUMN auto_rules longtext NULL AFTER auto_validation" );
				}
			}
			update_option( 'vms_elements_form_guard_schema_version', '7' );
			$current = '7';
		}

		if ( version_compare( $current, '8', '<' ) ) {
			$table = $wpdb->prefix . 'vms_elements_form_guard_form_settings';
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name uses trusted prefix.
			$exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
			if ( $exists ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$has_col = $wpdb->get_results( "SHOW COLUMNS FROM {$table} LIKE 'enable_recaptcha'" );
				if ( empty( $has_col ) ) {
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$wpdb->query( "ALTER TABLE {$table} ADD COLUMN enable_recaptcha tinyint(1) NOT NULL DEFAULT 0 AFTER auto_rules" );
				}
			}
			update_option( 'vms_elements_form_guard_schema_version', '8' );
			$current = '8';
		}

		if ( version_compare( $current, '9', '<' ) ) {
			$table = $wpdb->prefix . 'vms_elements_form_guard_comment_enforcement';
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name uses trusted prefix.
			$exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
			if ( $exists ) {
				// Add user_id column for logged-in users
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$has_user_id = $wpdb->get_results( "SHOW COLUMNS FROM {$table} LIKE 'user_id'" );
				if ( empty( $has_user_id ) ) {
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$wpdb->query( "ALTER TABLE {$table} ADD COLUMN user_id bigint(20) unsigned NULL AFTER actor_label" );
				}
				// Add login_blocked column
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$has_login_blocked = $wpdb->get_results( "SHOW COLUMNS FROM {$table} LIKE 'login_blocked'" );
				if ( empty( $has_login_blocked ) ) {
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$wpdb->query( "ALTER TABLE {$table} ADD COLUMN login_blocked tinyint(1) NOT NULL DEFAULT 0 AFTER site_banned" );
				}
				// Add strike_source column to track where strikes came from
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$has_strike_source = $wpdb->get_results( "SHOW COLUMNS FROM {$table} LIKE 'strike_source'" );
				if ( empty( $has_strike_source ) ) {
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$wpdb->query( "ALTER TABLE {$table} ADD COLUMN strike_source varchar(50) NOT NULL DEFAULT 'comment' AFTER last_reason" );
				}
				// Add strikes_expire_at column for auto-expiry
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$has_expire = $wpdb->get_results( "SHOW COLUMNS FROM {$table} LIKE 'strikes_expire_at'" );
				if ( empty( $has_expire ) ) {
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$wpdb->query( "ALTER TABLE {$table} ADD COLUMN strikes_expire_at datetime NULL AFTER last_strike_at" );
				}
				// Add index on user_id
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$has_idx = $wpdb->get_results( "SHOW INDEX FROM {$table} WHERE Key_name = 'user_id'" );
				if ( empty( $has_idx ) ) {
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$wpdb->query( "ALTER TABLE {$table} ADD INDEX user_id (user_id)" );
				}
			}
			update_option( 'vms_elements_form_guard_schema_version', '9' );
			$current = '9';
		}

		if ( version_compare( $current, '10', '<' ) ) {
			// Free/Pro split bump. Pre-existing Pro-owned tables
			// (vms_elements_form_guard_form_settings, vms_elements_form_guard_forms,
			// vms_elements_form_guard_ai_post_summary) are intentionally NOT dropped
			// — the Pro plugin's activator will dbDelta them when installed.
			update_option( 'vms_elements_form_guard_schema_version', '10' );
			$current = '10';
		}

		if ( version_compare( $current, '11', '<' ) ) {
			// Normalize legacy `span_*` domain table names to the plugin prefix.
			self::rename_legacy_domain_tables();
			update_option( 'vms_elements_form_guard_schema_version', '11' );
			$current = '11';
		}
	}

	/**
	 * Free-only AI moderation tables.
	 *
	 * The `ai_post_summary` table now lives in the Pro plugin's activator
	 * (it's only used by AI Post Summaries, a Pro feature). We still ensure
	 * `comment_enforcement` is present here since Comment Guard (free) needs it.
	 */
	public static function install_ai_tables(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = array(
			"CREATE TABLE {$wpdb->prefix}vms_elements_form_guard_comment_enforcement (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				actor_key varchar(191) NOT NULL,
				actor_label varchar(191) NOT NULL DEFAULT '',
				user_id bigint(20) unsigned NULL,
				strikes int(10) unsigned NOT NULL DEFAULT 0,
				blocked tinyint(1) NOT NULL DEFAULT 0,
				site_banned tinyint(1) NOT NULL DEFAULT 0,
				login_blocked tinyint(1) NOT NULL DEFAULT 0,
				last_ip varchar(45) NOT NULL DEFAULT '',
				blocked_at datetime NULL,
				last_strike_at datetime NULL,
				strikes_expire_at datetime NULL,
				last_reason varchar(500) NOT NULL DEFAULT '',
				strike_source varchar(50) NOT NULL DEFAULT 'comment',
				PRIMARY KEY  (id),
				UNIQUE KEY actor_key (actor_key),
				KEY blocked (blocked),
				KEY site_banned_ip (site_banned, last_ip(40)),
				KEY user_id (user_id),
				KEY login_blocked (login_blocked)
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

		$table = $wpdb->prefix . 'vms_elements_form_guard_disposable_domains';
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		if ( $count > 0 ) {
			return;
		}

		$file = VMS_ELEMENTS_FORM_GUARD_DIR . 'includes/data/disposable-domains.php';
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

		$table = $wpdb->prefix . 'vms_elements_form_guard_whitelist_domains';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name uses trusted prefix.
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		if ( $count > 0 ) {
			return;
		}

		$file = VMS_ELEMENTS_FORM_GUARD_DIR . 'includes/data/whitelist.sql';
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
