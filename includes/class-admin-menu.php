<?php
/**
 * Register wp-admin menus.
 *
 * Pro features show up as lock-icon stubs unless the Pro plugin is installed
 * and licensed — in which case Pro replaces the page callbacks with the real
 * ones via the `vms_span_checker_loaded` action.
 *
 * @package VMS_Span_Checker
 */

namespace VMS_Span_Checker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin menu registration.
 */
class Admin_Menu {

	/**
	 * Pro-only feature slugs we render as lock stubs when Pro is inactive.
	 *
	 * @var array<int, string>
	 */
	private $pro_slugs = array(
		'vms-span-checker-form-settings',
		'vms-span-checker-ai-summaries',
		'vms-span-checker-ai-product-summaries',
		'vms-span-checker-product-review-guard',
		'vms-span-checker-contact-guard',
		'vms-span-checker-subscribe-guard',
		'wsc-email-templates',
	);

	/**
	 * Map of pro slug → feature metadata key used to pull the title/description
	 * from the bridge's `vms_span_checker_pro_features` list.
	 *
	 * @var array<string, string>
	 */
	private $pro_meta_map = array(
		'vms-span-checker-form-settings'         => 'wsc-form-guard',
		'vms-span-checker-ai-summaries'          => 'wsc-ai-summaries',
		'vms-span-checker-ai-product-summaries'  => 'wsc-ai-product-summaries',
		'vms-span-checker-product-review-guard'  => 'wsc-product-review-guard',
		'vms-span-checker-contact-guard'         => 'wsc-contact-guard',
		'vms-span-checker-subscribe-guard'       => 'wsc-subscribe-guard',
		'wsc-email-templates'                    => 'wsc-email-templates',
	);

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_notices', array( $this, 'maybe_render_upgrade_notice' ) );
	}

	/**
	 * True when Pro is installed *and* licensed.
	 */
	private function is_pro_active(): bool {
		return function_exists( 'vms_span_checker_pro_runtime_ready' )
			? vms_span_checker_pro_runtime_ready()
			: (bool) apply_filters( 'vms_span_checker_is_pro_active', false );
	}

	/**
	 * Decorate a label with a lock dashicon for inactive Pro menu items.
	 */
	private function pro_label( string $label ): string {
		return $label . ' <span class="dashicons dashicons-lock" style="font-size:14px;vertical-align:text-bottom;color:#d63638;width:14px;height:14px;line-height:1;"></span>';
	}

	/**
	 * Register top-level menu and submenus.
	 */
	public function register_menu() {
		$pro_active = $this->is_pro_active();

		add_menu_page(
			__( 'VMS Span Checker', 'vms-span-checker' ),
			__( 'VMS Span Checker', 'vms-span-checker' ),
			'manage_options',
			'vms-span-checker',
			array( $this, 'dashboard_page' ),
			'dashicons-shield-alt',
			30
		);

		add_submenu_page(
			'vms-span-checker',
			__( 'Dashboard', 'vms-span-checker' ),
			__( 'Dashboard', 'vms-span-checker' ),
			'manage_options',
			'vms-span-checker',
			array( $this, 'dashboard_page' )
		);
		add_submenu_page(
			'vms-span-checker',
			__( 'Whitelist Domains', 'vms-span-checker' ),
			__( 'Whitelist Domains', 'vms-span-checker' ),
			'manage_options',
			'vms-span-checker-whitelist',
			array( $this, 'whitelist_page' )
		);
		add_submenu_page(
			'vms-span-checker',
			__( 'Disposable Domains', 'vms-span-checker' ),
			__( 'Disposable Domains', 'vms-span-checker' ),
			'manage_options',
			'vms-span-checker-disposable',
			array( $this, 'disposable_page' )
		);
		add_submenu_page(
			'vms-span-checker',
			__( 'API Settings', 'vms-span-checker' ),
			__( 'API Settings', 'vms-span-checker' ),
			'manage_options',
			'vms-span-checker-api',
			array( $this, 'api_settings_page' )
		);
		add_submenu_page(
			'vms-span-checker',
			__( 'Registration guard', 'vms-span-checker' ),
			__( 'Registration guard', 'vms-span-checker' ),
			'manage_options',
			'vms-span-checker-registration',
			array( $this, 'registration_guard_page' )
		);
		add_submenu_page(
			'vms-span-checker',
			__( 'Login Guard', 'vms-span-checker' ),
			__( 'Login Guard', 'vms-span-checker' ),
			'manage_options',
			'vms-span-checker-login-guard',
			array( $this, 'login_guard_page' )
		);
		add_submenu_page(
			'vms-span-checker',
			__( 'Tools & log', 'vms-span-checker' ),
			__( 'Tools & log', 'vms-span-checker' ),
			'manage_options',
			'vms-span-checker-tools',
			array( $this, 'tools_page' )
		);
		add_submenu_page(
			'vms-span-checker',
			__( 'Error Messages', 'vms-span-checker' ),
			__( 'Error Messages', 'vms-span-checker' ),
			'manage_options',
			'wsc-error-messages',
			array( $this, 'error_messages_page' )
		);
		add_submenu_page(
			'vms-span-checker',
			__( 'AI Span Settings', 'vms-span-checker' ),
			__( 'AI Span Settings', 'vms-span-checker' ),
			'manage_options',
			'vms-span-checker-ai-settings',
			array( $this, 'ai_settings_page' )
		);
		add_submenu_page(
			'vms-span-checker',
			__( 'Comment Guard', 'vms-span-checker' ),
			__( 'Comment Guard', 'vms-span-checker' ),
			'manage_options',
			'vms-span-checker-comment-settings',
			array( $this, 'comment_settings_page' )
		);
		add_submenu_page(
			'vms-span-checker',
			__( 'Blocked Users', 'vms-span-checker' ),
			__( 'Blocked Users', 'vms-span-checker' ),
			'manage_options',
			'vms-span-checker-comment-blocks',
			array( $this, 'comment_blocks_page' )
		);
		add_submenu_page(
			'vms-span-checker',
			__( 'Block User Settings', 'vms-span-checker' ),
			__( 'Block User Settings', 'vms-span-checker' ),
			'manage_options',
			'wsc-block-user-settings',
			array( $this, 'block_user_settings_page' )
		);
		add_submenu_page(
			'vms-span-checker',
			__( 'Auth Form Templates', 'vms-span-checker' ),
			__( 'Auth Form Templates', 'vms-span-checker' ),
			'manage_options',
			'wsc-auth-forms',
			array( $this, 'auth_forms_page' )
		);
		add_submenu_page(
			'vms-span-checker',
			__( 'SMTP Settings', 'vms-span-checker' ),
			__( 'SMTP Settings', 'vms-span-checker' ),
			'manage_options',
			'wsc-smtp-settings',
			array( $this, 'smtp_settings_redirect' )
		);

		// ------------------------------------------------------------------
		// Pro menu items. Render as lock stubs when Pro is inactive.
		// Pro replaces these callbacks on `vms_span_checker_loaded`.
		// ------------------------------------------------------------------
		$pro_pages = array(
			'vms-span-checker-form-settings'        => __( 'Form Guard', 'vms-span-checker' ),
			'vms-span-checker-ai-summaries'         => __( 'AI Post Summaries', 'vms-span-checker' ),
			'vms-span-checker-ai-product-summaries' => __( 'AI Product Summaries', 'vms-span-checker' ),
			'vms-span-checker-product-review-guard' => __( 'Product Review Guard', 'vms-span-checker' ),
			'vms-span-checker-contact-guard'        => __( 'Contact Guard', 'vms-span-checker' ),
			'vms-span-checker-subscribe-guard'      => __( 'Subscribe Guard', 'vms-span-checker' ),
			'wsc-email-templates'                   => __( 'Email Templates', 'vms-span-checker' ),
		);

		// When licensed + Pro plugin is active, the Pro plugin registers these pages
		// (see Pro_Loader::register_pro_menus). Registering stubs here would leave
		// WordPress hooks pointing at the placeholder — remove_submenu_page() does
		// not remove the original callback.
		if ( $pro_active ) {
			return;
		}

		foreach ( $pro_pages as $slug => $label ) {
			add_submenu_page(
				'vms-span-checker',
				$label,
				$this->pro_label( $label ),
				'manage_options',
				$slug,
				array( $this, 'render_pro_stub' )
			);
		}
	}

	/**
	 * Top-of-screen upsell notice when Pro is not active. Shown only on plugin pages.
	 */
	public function maybe_render_upgrade_notice(): void {
		if ( $this->is_pro_active() ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || empty( $screen->id ) ) {
			return;
		}
		$id = (string) $screen->id;
		if ( false === strpos( $id, 'vms-span-checker' ) && false === strpos( $id, 'wsc-' ) ) {
			return;
		}
		$upgrade_url = defined( 'VMS_SPAN_CHECKER_PRO_UPGRADE_URL' ) ? VMS_SPAN_CHECKER_PRO_UPGRADE_URL : '#';
		$pro_loaded  = function_exists( 'vms_span_checker_is_pro_plugin_loaded' ) && vms_span_checker_is_pro_plugin_loaded();

		$license_button = '';
		if ( $pro_loaded ) {
			$license_button = sprintf(
				' <a href="%1$s" class="button" style="margin-left:4px;">%2$s</a>',
				esc_url( admin_url( 'admin.php?page=vms-span-checker-license' ) ),
				esc_html__( 'Enter license key', 'vms-span-checker' )
			);
		}

		printf(
			'<div class="notice notice-info" style="border-left-color:#d63638;"><p><span class="dashicons dashicons-lock" style="color:#d63638;vertical-align:text-bottom;"></span> %1$s <a href="%2$s" target="_blank" class="button button-primary" style="margin-left:8px;">%3$s</a>%4$s</p></div>',
			esc_html__( 'Unlock Form Guard, Contact Guard, Subscribe Guard, AI Summaries, Email Templates and more with VMS Span Checker Pro.', 'vms-span-checker' ),
			esc_url( $upgrade_url ),
			esc_html__( 'Upgrade to Pro', 'vms-span-checker' ),
			$license_button // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped above.
		);
	}

	/**
	 * Lock-icon stub page. Pro replaces this callback at runtime.
	 */
	public function render_pro_stub(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		// Resolve which page we're on.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only request param used purely to choose copy.
		$page_slug = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( (string) $_GET['page'] ) ) : '';
		$meta_key  = $this->pro_meta_map[ $page_slug ] ?? '';

		$all_features = apply_filters( 'vms_span_checker_pro_features', array() );
		if ( ! is_array( $all_features ) ) {
			$all_features = array();
		}

		$feature = array();
		foreach ( $all_features as $f ) {
			if ( is_array( $f ) && isset( $f['slug'] ) && $f['slug'] === $meta_key ) {
				$feature = $f;
				break;
			}
		}
		if ( empty( $feature ) ) {
			$feature = array(
				'slug'        => $meta_key,
				'title'       => __( 'Pro feature', 'vms-span-checker' ),
				'description' => __( 'This feature is available in VMS Span Checker Pro.', 'vms-span-checker' ),
			);
		}

		$upgrade_url = defined( 'VMS_SPAN_CHECKER_PRO_UPGRADE_URL' ) ? VMS_SPAN_CHECKER_PRO_UPGRADE_URL : '#';
		$license_url = admin_url( 'admin.php?page=vms-span-checker-license' );
		include VMS_SPAN_CHECKER_DIR . 'templates/license-promo.php';
	}

	/**
	 * Dashboard screen.
	 */
	public function dashboard_page() {
		require VMS_SPAN_CHECKER_DIR . 'templates/dashboard.php';
	}

	/**
	 * Whitelist domains screen.
	 */
	public function whitelist_page() {
		require VMS_SPAN_CHECKER_DIR . 'templates/whitelist.php';
	}

	/**
	 * Disposable domains screen.
	 */
	public function disposable_page() {
		require VMS_SPAN_CHECKER_DIR . 'templates/disposable.php';
	}

	/**
	 * API settings screen.
	 */
	public function api_settings_page() {
		require VMS_SPAN_CHECKER_DIR . 'templates/api-settings.php';
	}

	/**
	 * Registration email / domain validation.
	 */
	public function registration_guard_page() {
		require VMS_SPAN_CHECKER_DIR . 'templates/registration-guard.php';
	}

	/**
	 * Login Guard - reCAPTCHA protection for login forms.
	 */
	public function login_guard_page() {
		require VMS_SPAN_CHECKER_DIR . 'templates/login-guard.php';
	}

	/**
	 * Manual API tests and activity log console.
	 */
	public function tools_page() {
		require VMS_SPAN_CHECKER_DIR . 'templates/tools.php';
	}

	/**
	 * Error messages customization page.
	 */
	public function error_messages_page() {
		require VMS_SPAN_CHECKER_DIR . 'templates/error-messages.php';
	}

	/**
	 * AI provider and post-type summary settings (used by Comment Guard's AI moderation).
	 */
	public function ai_settings_page() {
		require VMS_SPAN_CHECKER_DIR . 'templates/ai-settings.php';
	}

	/**
	 * Comment rules, strikes, AI prompt.
	 */
	public function comment_settings_page() {
		require VMS_SPAN_CHECKER_DIR . 'templates/comment-settings.php';
	}

	/**
	 * Blocked / strike list.
	 */
	public function comment_blocks_page() {
		require VMS_SPAN_CHECKER_DIR . 'templates/comment-blocks.php';
	}

	/**
	 * Block User settings.
	 */
	public function block_user_settings_page() {
		require VMS_SPAN_CHECKER_DIR . 'templates/block-user-settings.php';
	}

	/**
	 * Auth Form Templates page.
	 */
	public function auth_forms_page() {
		require VMS_SPAN_CHECKER_DIR . 'templates/auth-forms.php';
	}

	/**
	 * SMTP Settings - redirect to Auth Forms SMTP tab.
	 */
	public function smtp_settings_redirect() {
		wp_safe_redirect( admin_url( 'admin.php?page=wsc-auth-forms#panel-smtp' ) );
		exit;
	}
}
