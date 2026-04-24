<?php
/**
 * Register wp-admin menus.
 *
 * @package WP_Span_Checker
 */

namespace WP_Span_Checker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin menu registration.
 */
class Admin_Menu {

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
	}

	/**
	 * Register top-level menu and submenus.
	 */
	public function register_menu() {
		add_menu_page(
			__( 'WP Span Checker', 'wp-span-checker' ),
			__( 'WP Span Checker', 'wp-span-checker' ),
			'manage_options',
			'wp-span-checker',
			array( $this, 'dashboard_page' ),
			'dashicons-shield-alt',
			30
		);

		add_submenu_page(
			'wp-span-checker',
			__( 'Dashboard', 'wp-span-checker' ),
			__( 'Dashboard', 'wp-span-checker' ),
			'manage_options',
			'wp-span-checker',
			array( $this, 'dashboard_page' )
		);
		add_submenu_page(
			'wp-span-checker',
			__( 'Whitelist Domains', 'wp-span-checker' ),
			__( 'Whitelist Domains', 'wp-span-checker' ),
			'manage_options',
			'wp-span-checker-whitelist',
			array( $this, 'whitelist_page' )
		);
		add_submenu_page(
			'wp-span-checker',
			__( 'Disposable Domains', 'wp-span-checker' ),
			__( 'Disposable Domains', 'wp-span-checker' ),
			'manage_options',
			'wp-span-checker-disposable',
			array( $this, 'disposable_page' )
		);
		add_submenu_page(
			'wp-span-checker',
			__( 'API Settings', 'wp-span-checker' ),
			__( 'API Settings', 'wp-span-checker' ),
			'manage_options',
			'wp-span-checker-api',
			array( $this, 'api_settings_page' )
		);
		add_submenu_page(
			'wp-span-checker',
			__( 'Registration guard', 'wp-span-checker' ),
			__( 'Registration guard', 'wp-span-checker' ),
			'manage_options',
			'wp-span-checker-registration',
			array( $this, 'registration_guard_page' )
		);
		add_submenu_page(
			'wp-span-checker',
			__( 'Tools & log', 'wp-span-checker' ),
			__( 'Tools & log', 'wp-span-checker' ),
			'manage_options',
			'wp-span-checker-tools',
			array( $this, 'tools_page' )
		);
		add_submenu_page(
			'wp-span-checker',
			__( 'Form Guard', 'wp-span-checker' ),
			__( 'Form Guard', 'wp-span-checker' ),
			'manage_options',
			'wp-span-checker-form-settings',
			array( $this, 'form_settings_page' )
		);
		add_submenu_page(
			'wp-span-checker',
			__( 'AI Span Settings', 'wp-span-checker' ),
			__( 'AI Span Settings', 'wp-span-checker' ),
			'manage_options',
			'wp-span-checker-ai-settings',
			array( $this, 'ai_settings_page' )
		);
		add_submenu_page(
			'wp-span-checker',
			__( 'AI Post Summaries', 'wp-span-checker' ),
			__( 'AI Post Summaries', 'wp-span-checker' ),
			'manage_options',
			'wp-span-checker-ai-summaries',
			array( $this, 'ai_summaries_page' )
		);
		add_submenu_page(
			'wp-span-checker',
			__( 'Comment Guard', 'wp-span-checker' ),
			__( 'Comment Guard', 'wp-span-checker' ),
			'manage_options',
			'wp-span-checker-comment-settings',
			array( $this, 'comment_settings_page' )
		);
		add_submenu_page(
			'wp-span-checker',
			__( 'Contact & Subscribe Guard', 'wp-span-checker' ),
			__( 'Contact & Subscribe Guard', 'wp-span-checker' ),
			'manage_options',
			'wp-span-checker-contact-subscribe-guard',
			array( $this, 'contact_subscribe_guard_page' )
		);
		add_submenu_page(
			'wp-span-checker',
			__( 'Blocked commenters', 'wp-span-checker' ),
			__( 'Blocked commenters', 'wp-span-checker' ),
			'manage_options',
			'wp-span-checker-comment-blocks',
			array( $this, 'comment_blocks_page' )
		);
	}

	/**
	 * Dashboard screen.
	 */
	public function dashboard_page() {
		require WP_SPAN_CHECKER_DIR . 'templates/dashboard.php';
	}

	/**
	 * Whitelist domains screen.
	 */
	public function whitelist_page() {
		require WP_SPAN_CHECKER_DIR . 'templates/whitelist.php';
	}

	/**
	 * Disposable domains screen.
	 */
	public function disposable_page() {
		require WP_SPAN_CHECKER_DIR . 'templates/disposable.php';
	}

	/**
	 * API settings screen.
	 */
	public function api_settings_page() {
		require WP_SPAN_CHECKER_DIR . 'templates/api-settings.php';
	}

	/**
	 * Registration email / domain validation.
	 */
	public function registration_guard_page() {
		require WP_SPAN_CHECKER_DIR . 'templates/registration-guard.php';
	}

	/**
	 * Manual API tests and activity log console.
	 */
	public function tools_page() {
		require WP_SPAN_CHECKER_DIR . 'templates/tools.php';
	}

	/**
	 * Form Guard — map front-end forms to validation rules.
	 */
	public function form_settings_page() {
		require WP_SPAN_CHECKER_DIR . 'templates/form-settings.php';
	}

	/**
	 * AI provider and post-type summary settings.
	 */
	public function ai_settings_page() {
		require WP_SPAN_CHECKER_DIR . 'templates/ai-settings.php';
	}

	/**
	 * Post summary status and manual generation.
	 */
	public function ai_summaries_page() {
		require WP_SPAN_CHECKER_DIR . 'templates/ai-summaries.php';
	}

	/**
	 * Comment rules, strikes, AI prompt.
	 */
	public function comment_settings_page() {
		require WP_SPAN_CHECKER_DIR . 'templates/comment-settings.php';
	}

	/**
	 * Contact and subscribe guard settings.
	 */
	public function contact_subscribe_guard_page() {
		require WP_SPAN_CHECKER_DIR . 'templates/contact-subscribe-guard.php';
	}

	/**
	 * Blocked / strike list.
	 */
	public function comment_blocks_page() {
		require WP_SPAN_CHECKER_DIR . 'templates/comment-blocks.php';
	}
}
