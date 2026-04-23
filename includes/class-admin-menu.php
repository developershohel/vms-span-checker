<?php

namespace WP_Span_Checker;

if (!defined('ABSPATH')) exit;

class Admin_Menu {

	private $wpdb;

	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;

		// Admin menu pages
		add_action('admin_menu', [$this, 'register_menu']);
	}

	/**
	 * Register Admin Menu & Submenu
	 */
	public function register_menu() {
		add_menu_page(
			'WP Spam Checker',
			'WP Spam Checker',
			'manage_options',
			'wp-span-checker',
			[$this, 'dashboard_page'],
			'dashicons-shield-alt',
			30
		);

		add_submenu_page('wp-span-checker', 'Dashboard', 'Dashboard', 'manage_options', 'wp-span-checker', [$this, 'dashboard_page']);
		add_submenu_page('wp-span-checker', 'Whitelist Domains', 'Whitelist Domains', 'manage_options', 'wp-span-checker-whitelist', [$this, 'whitelist_page']);
		add_submenu_page('wp-span-checker', 'Disposable Domains', 'Disposable Domains', 'manage_options', 'wp-span-checker-disposable', [$this, 'disposable_page']);
		add_submenu_page('wp-span-checker', 'API Settings', 'API Settings', 'manage_options', 'wp-span-checker-api', [$this, 'api_settings_page']);
		add_submenu_page('wp-span-checker', 'Form Settings', 'Form Settings', 'manage_options', 'wp-span-checker-form-settings', [$this, 'form_settings_page']);
	}

	/**
	 * Admin pages
	 */
	public function dashboard_page() {
		include WP_PLUGIN_DIR . '/wp-span-checker/templates/dashboard.php';
	}

	public function whitelist_page() {
		include WP_PLUGIN_DIR . '/wp-span-checker/templates/whitelist.php';
	}

	public function disposable_page() {
		include WP_PLUGIN_DIR . '/wp-span-checker/templates/disposable.php';
	}

//	public function form_builder_page() {
//		include WP_PLUGIN_DIR . '/wp-span-checker/templates/form-builder.php';
//	}

	public function api_settings_page() {
		include WP_PLUGIN_DIR . '/wp-span-checker/templates/api-settings.php';
	}

	public function form_settings_page() {
		include WP_PLUGIN_DIR . '/wp-span-checker/templates/form-settings.php';
	}
}
