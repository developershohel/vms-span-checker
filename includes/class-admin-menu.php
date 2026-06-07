<?php
/**
 * Register wp-admin menus.
 *
 * Pro admin pages are registered only by the Pro plugin when it is active.
 * The free plugin exposes a single "Upgrade Now" page (no lock stubs or
 * license UI) so WordPress.org guidelines stay satisfied.
 *
 * @package VMS_Elements_Form_Guard
 */

namespace VMS_Elements_Form_Guard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin menu registration.
 */
class Admin_Menu {

	/**
	 * Admin slug for the Upgrade Now screen.
	 */
	public const UPGRADE_PAGE_SLUG = 'vefg-upgrade-now';

	/**
	 * Former Pro stub slugs — redirect to Upgrade Now when Pro is inactive.
	 *
	 * @var array<int, string>
	 */
	private $legacy_pro_slugs = array(
		'vms-elements-form-guard-form-settings',
		'vms-elements-form-guard-ai-summaries',
		'vms-elements-form-guard-ai-product-summaries',
		'vms-elements-form-guard-product-review-guard',
		'vms-elements-form-guard-contact-guard',
		'vms-elements-form-guard-subscribe-guard',
		'vefg-email-templates',
	);

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'maybe_redirect_legacy_pro_pages' ) );
	}

	/**
	 * True when Pro is installed *and* licensed.
	 */
	private function is_pro_active(): bool {
		return function_exists( 'vms_elements_form_guard_pro_runtime_ready' )
			? vms_elements_form_guard_pro_runtime_ready()
			: (bool) apply_filters( 'vms_elements_form_guard_is_pro_active', false );
	}

	/**
	 * Register top-level menu and submenus.
	 */
	public function register_menu() {
		add_menu_page(
			__( 'VMS Elements Form Guard', 'vms-elements-form-guard' ),
			__( 'VMS Elements Form Guard', 'vms-elements-form-guard' ),
			'manage_options',
			'vms-elements-form-guard',
			array( $this, 'dashboard_page' ),
			'dashicons-shield-alt',
			30
		);

		add_submenu_page(
			'vms-elements-form-guard',
			__( 'Dashboard', 'vms-elements-form-guard' ),
			__( 'Dashboard', 'vms-elements-form-guard' ),
			'manage_options',
			'vms-elements-form-guard',
			array( $this, 'dashboard_page' )
		);
		add_submenu_page(
			'vms-elements-form-guard',
			__( 'Whitelist Domains', 'vms-elements-form-guard' ),
			__( 'Whitelist Domains', 'vms-elements-form-guard' ),
			'manage_options',
			'vms-elements-form-guard-whitelist',
			array( $this, 'whitelist_page' )
		);
		add_submenu_page(
			'vms-elements-form-guard',
			__( 'Disposable Domains', 'vms-elements-form-guard' ),
			__( 'Disposable Domains', 'vms-elements-form-guard' ),
			'manage_options',
			'vms-elements-form-guard-disposable',
			array( $this, 'disposable_page' )
		);
		add_submenu_page(
			'vms-elements-form-guard',
			__( 'API Settings', 'vms-elements-form-guard' ),
			__( 'API Settings', 'vms-elements-form-guard' ),
			'manage_options',
			'vms-elements-form-guard-api',
			array( $this, 'api_settings_page' )
		);
		add_submenu_page(
			'vms-elements-form-guard',
			__( 'Registration guard', 'vms-elements-form-guard' ),
			__( 'Registration guard', 'vms-elements-form-guard' ),
			'manage_options',
			'vms-elements-form-guard-registration',
			array( $this, 'registration_guard_page' )
		);
		add_submenu_page(
			'vms-elements-form-guard',
			__( 'Login Guard', 'vms-elements-form-guard' ),
			__( 'Login Guard', 'vms-elements-form-guard' ),
			'manage_options',
			'vms-elements-form-guard-login-guard',
			array( $this, 'login_guard_page' )
		);
		add_submenu_page(
			'vms-elements-form-guard',
			__( 'Tools & log', 'vms-elements-form-guard' ),
			__( 'Tools & log', 'vms-elements-form-guard' ),
			'manage_options',
			'vms-elements-form-guard-tools',
			array( $this, 'tools_page' )
		);
		add_submenu_page(
			'vms-elements-form-guard',
			__( 'Error Messages', 'vms-elements-form-guard' ),
			__( 'Error Messages', 'vms-elements-form-guard' ),
			'manage_options',
			'vefg-error-messages',
			array( $this, 'error_messages_page' )
		);
		add_submenu_page(
			'vms-elements-form-guard',
			__( 'AI Span Settings', 'vms-elements-form-guard' ),
			__( 'AI Span Settings', 'vms-elements-form-guard' ),
			'manage_options',
			'vms-elements-form-guard-ai-settings',
			array( $this, 'ai_settings_page' )
		);
		add_submenu_page(
			'vms-elements-form-guard',
			__( 'Comment Guard', 'vms-elements-form-guard' ),
			__( 'Comment Guard', 'vms-elements-form-guard' ),
			'manage_options',
			'vms-elements-form-guard-comment-settings',
			array( $this, 'comment_settings_page' )
		);
		add_submenu_page(
			'vms-elements-form-guard',
			__( 'Blocked Users', 'vms-elements-form-guard' ),
			__( 'Blocked Users', 'vms-elements-form-guard' ),
			'manage_options',
			'vms-elements-form-guard-comment-blocks',
			array( $this, 'comment_blocks_page' )
		);
		add_submenu_page(
			'vms-elements-form-guard',
			__( 'Block User Settings', 'vms-elements-form-guard' ),
			__( 'Block User Settings', 'vms-elements-form-guard' ),
			'manage_options',
			'vefg-block-user-settings',
			array( $this, 'block_user_settings_page' )
		);
		add_submenu_page(
			'vms-elements-form-guard',
			__( 'Auth Form Templates', 'vms-elements-form-guard' ),
			__( 'Auth Form Templates', 'vms-elements-form-guard' ),
			'manage_options',
			'vefg-auth-forms',
			array( $this, 'auth_forms_page' )
		);
		add_submenu_page(
			'vms-elements-form-guard',
			__( 'SMTP Settings', 'vms-elements-form-guard' ),
			__( 'SMTP Settings', 'vms-elements-form-guard' ),
			'manage_options',
			'vefg-smtp-settings',
			array( $this, 'smtp_settings_redirect' )
		);

		if ( ! $this->is_pro_active() ) {
			add_submenu_page(
				'vms-elements-form-guard',
				__( 'Upgrade Now', 'vms-elements-form-guard' ),
				__( 'Upgrade Now', 'vms-elements-form-guard' ),
				'manage_options',
				self::UPGRADE_PAGE_SLUG,
				array( $this, 'render_upgrade_page' )
			);
		}
	}

	/**
	 * Send bookmarked Pro stub URLs to the Upgrade Now page.
	 */
	public function maybe_redirect_legacy_pro_pages(): void {
		if ( $this->is_pro_active() || ! is_admin() ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only redirect target.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( (string) $_GET['page'] ) ) : '';
		if ( '' === $page || ! in_array( $page, $this->legacy_pro_slugs, true ) ) {
			return;
		}
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::UPGRADE_PAGE_SLUG ) );
		exit;
	}

	/**
	 * Upgrade Now screen — feature list + external product link only.
	 */
	public function render_upgrade_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$all_features = apply_filters( 'vms_elements_form_guard_pro_features', array() );
		if ( ! is_array( $all_features ) ) {
			$all_features = array();
		}

		usort(
			$all_features,
			static function ( $a, $b ) {
				$pos_a = is_array( $a ) && isset( $a['position'] ) ? (int) $a['position'] : 0;
				$pos_b = is_array( $b ) && isset( $b['position'] ) ? (int) $b['position'] : 0;
				return $pos_a <=> $pos_b;
			}
		);

		$upgrade_url = defined( 'VMS_ELEMENTS_FORM_GUARD_PRO_UPGRADE_URL' ) ? VMS_ELEMENTS_FORM_GUARD_PRO_UPGRADE_URL : 'https://vmselements.com';
		include VMS_ELEMENTS_FORM_GUARD_DIR . 'templates/upgrade-now.php';
	}

	/**
	 * Dashboard screen.
	 */
	public function dashboard_page() {
		require VMS_ELEMENTS_FORM_GUARD_DIR . 'templates/dashboard.php';
	}

	/**
	 * Whitelist domains screen.
	 */
	public function whitelist_page() {
		require VMS_ELEMENTS_FORM_GUARD_DIR . 'templates/whitelist.php';
	}

	/**
	 * Disposable domains screen.
	 */
	public function disposable_page() {
		require VMS_ELEMENTS_FORM_GUARD_DIR . 'templates/disposable.php';
	}

	/**
	 * API settings screen.
	 */
	public function api_settings_page() {
		require VMS_ELEMENTS_FORM_GUARD_DIR . 'templates/api-settings.php';
	}

	/**
	 * Registration email / domain validation.
	 */
	public function registration_guard_page() {
		require VMS_ELEMENTS_FORM_GUARD_DIR . 'templates/registration-guard.php';
	}

	/**
	 * Login Guard - reCAPTCHA protection for login forms.
	 */
	public function login_guard_page() {
		require VMS_ELEMENTS_FORM_GUARD_DIR . 'templates/login-guard.php';
	}

	/**
	 * Manual API tests and activity log console.
	 */
	public function tools_page() {
		require VMS_ELEMENTS_FORM_GUARD_DIR . 'templates/tools.php';
	}

	/**
	 * Error messages customization page.
	 */
	public function error_messages_page() {
		require VMS_ELEMENTS_FORM_GUARD_DIR . 'templates/error-messages.php';
	}

	/**
	 * AI provider and post-type summary settings (used by Comment Guard's AI moderation).
	 */
	public function ai_settings_page() {
		require VMS_ELEMENTS_FORM_GUARD_DIR . 'templates/ai-settings.php';
	}

	/**
	 * Comment rules, strikes, AI prompt.
	 */
	public function comment_settings_page() {
		require VMS_ELEMENTS_FORM_GUARD_DIR . 'templates/comment-settings.php';
	}

	/**
	 * Blocked / strike list.
	 */
	public function comment_blocks_page() {
		require VMS_ELEMENTS_FORM_GUARD_DIR . 'templates/comment-blocks.php';
	}

	/**
	 * Block User settings.
	 */
	public function block_user_settings_page() {
		require VMS_ELEMENTS_FORM_GUARD_DIR . 'templates/block-user-settings.php';
	}

	/**
	 * Auth Form Templates page.
	 */
	public function auth_forms_page() {
		require VMS_ELEMENTS_FORM_GUARD_DIR . 'templates/auth-forms.php';
	}

	/**
	 * SMTP Settings - redirect to Auth Forms SMTP tab.
	 */
	public function smtp_settings_redirect() {
		wp_safe_redirect( admin_url( 'admin.php?page=vefg-auth-forms#panel-smtp' ) );
		exit;
	}
}
