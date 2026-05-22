<?php
/**
 * License admin page + activation/deactivation handler.
 *
 * Wires the `License` sub-menu, renders {@see templates/license-settings.php},
 * and handles activate / deactivate form submissions.
 *
 * @package VMS_Span_Checker
 */

namespace VMS_Span_Checker\Licensing;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-license-manager.php';

/**
 * Admin UI + form handler for the License page.
 */
class License_Admin {

	const PAGE_SLUG  = 'vms-span-checker-license';
	const NONCE_NAME = 'vms_span_checker_license_nonce';
	const NOTICE_KEY = 'vms_span_checker_license_notice';

	/**
	 * @var License_Manager
	 */
	private $manager;

	public function __construct() {
		$this->manager = License_Manager::instance();
		add_action( 'admin_menu', array( $this, 'register_menu' ), 100 );
		add_action( 'admin_post_vms_span_checker_license_save', array( $this, 'handle_post' ) );
		add_action( 'admin_notices', array( $this, 'maybe_render_notice' ) );
	}

	/**
	 * Hook into the existing top-level admin menu.
	 */
	public function register_menu(): void {
		// The parent slug must match Admin_Menu::register_admin_menu()'s top-level slug.
		add_submenu_page(
			'vms-span-checker',
			__( 'License', 'vms-span-checker' ),
			__( 'License', 'vms-span-checker' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Render the License settings template.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$manager     = $this->manager;
		$info        = $manager->get_info();
		$is_active   = $manager->is_pro_active();
		$status      = $manager->status_label();
		$action_url  = admin_url( 'admin-post.php' );
		$nonce_field = wp_nonce_field( self::NONCE_NAME, self::NONCE_NAME, true, false );
		$upgrade_url = defined( 'VMS_SPAN_CHECKER_PRO_UPGRADE_URL' ) ? VMS_SPAN_CHECKER_PRO_UPGRADE_URL : '#';
		include VMS_SPAN_CHECKER_DIR . 'templates/license-settings.php';
	}

	/**
	 * Handle activate / deactivate form submissions.
	 */
	public function handle_post(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'vms-span-checker' ) );
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked immediately below.
		$nonce = isset( $_POST[ self::NONCE_NAME ] ) ? sanitize_text_field( wp_unslash( (string) $_POST[ self::NONCE_NAME ] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE_NAME ) ) {
			wp_die( esc_html__( 'Security check failed.', 'vms-span-checker' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
		$action = isset( $_POST['license_action'] ) ? sanitize_key( wp_unslash( (string) $_POST['license_action'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
		$license_key = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['license_key'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
		$email = isset( $_POST['license_email'] ) ? sanitize_email( wp_unslash( (string) $_POST['license_email'] ) ) : '';

		$result = array(
			'success' => false,
			'message' => __( 'Unknown action.', 'vms-span-checker' ),
		);

		switch ( $action ) {
			case 'activate':
				$result = $this->manager->activate( $license_key, $email );
				break;
			case 'deactivate':
				$result = $this->manager->deactivate();
				break;
			case 'verify':
				$result = $this->manager->verify();
				break;
		}

		set_transient(
			self::NOTICE_KEY,
			array(
				'type'    => $result['success'] ? 'success' : 'error',
				'message' => (string) $result['message'],
			),
			60
		);

		$redirect = add_query_arg(
			array(
				'page'    => self::PAGE_SLUG,
				'updated' => $result['success'] ? '1' : '0',
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Render the one-shot admin notice after a POST.
	 */
	public function maybe_render_notice(): void {
		$notice = get_transient( self::NOTICE_KEY );
		if ( ! is_array( $notice ) || empty( $notice['message'] ) ) {
			return;
		}
		delete_transient( self::NOTICE_KEY );
		$class   = 'success' === ( $notice['type'] ?? 'error' ) ? 'notice-success' : 'notice-error';
		$message = (string) $notice['message'];
		printf(
			'<div class="notice %1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $class ),
			esc_html( $message )
		);
	}
}
