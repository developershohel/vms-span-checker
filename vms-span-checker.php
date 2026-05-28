<?php
/**
 * Plugin Name:       VMS Span Checker
 * Plugin URI:        https://wordpress.org/plugins/vms-span-checker
 * Description:       Validates email domains on front-end forms using disposable domain lists, HTTPS checks, and optional third-party safe-browsing APIs.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            VMS Elements
 * Author URI:        https://vmselements.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       vms-span-checker
 * Domain Path:       /languages
 *
 * @package VMS_Span_Checker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'VMS_SPAN_CHECKER_VERSION', '1.0.0' );
define( 'VMS_SPAN_CHECKER_FILE', __FILE__ );
define( 'VMS_SPAN_CHECKER_DIR', plugin_dir_path( __FILE__ ) );
define( 'VMS_SPAN_CHECKER_URL', plugin_dir_url( __FILE__ ) );

/*
 * License API — VMS Elements (vmselements.com).
 *
 * @see LICENSE_INTEGRATION.md in the vmselements repository.
 */
if ( ! defined( 'VMS_SPAN_CHECKER_LICENSE_API' ) ) {
	define( 'VMS_SPAN_CHECKER_LICENSE_API', 'https://vmselements.com/api/licenses' );
}
/** @deprecated Legacy Worker shared key — used only to decrypt pre-migration local records. */
if ( ! defined( 'VMS_SPAN_CHECKER_LICENSE_KEY' ) ) {
	define( 'VMS_SPAN_CHECKER_LICENSE_KEY', 'Vms5pAn2026PrOK1' );
}
if ( ! defined( 'VMS_SPAN_CHECKER_LICENSE_STORAGE_SALT' ) ) {
	define( 'VMS_SPAN_CHECKER_LICENSE_STORAGE_SALT', 'vms_license_api_v1' );
}
if ( ! defined( 'VMS_SPAN_CHECKER_PRODUCT_ID' ) ) {
	define( 'VMS_SPAN_CHECKER_PRODUCT_ID', 'vms-span-checker-pro' );
}
if ( ! defined( 'VMS_SPAN_CHECKER_PRODUCT_BASE' ) ) {
	define( 'VMS_SPAN_CHECKER_PRODUCT_BASE', 'vms_span_checker_pro_options' );
}
if ( ! defined( 'VMS_SPAN_CHECKER_PRO_UPGRADE_URL' ) ) {
	define( 'VMS_SPAN_CHECKER_PRO_UPGRADE_URL', 'https://vmselements.com/product/vms-span-checker-pro' );
}

/** @deprecated Use VMS_SPAN_CHECKER_* constants; kept for backward compatibility with includes. */
define( 'VMS_Span_Checker_VERSION', VMS_SPAN_CHECKER_VERSION );
define( 'VMS_Span_Checker_ASSETS_URL', VMS_SPAN_CHECKER_URL . 'assets/' );

require_once VMS_SPAN_CHECKER_DIR . 'includes/functions.php';
require_once VMS_SPAN_CHECKER_DIR . 'includes/class-activator.php';

/*
 * Translations: WordPress 4.6+ auto-loads translation files from the bundled
 * languages/ folder using the "Text Domain" and "Domain Path" headers in this
 * file. No explicit load_plugin_textdomain() call is needed (and is in fact
 * discouraged — it causes "loaded too early" notices in WP 6.7+).
 */

add_action( 'plugins_loaded', array( 'VMS_Span_Checker\Activator', 'maybe_upgrade_schema' ), 5 );

register_activation_hook( VMS_SPAN_CHECKER_FILE, array( 'VMS_Span_Checker\Activator', 'activate' ) );

/**
 * Uninstall callback — WordPress defines WP_UNINSTALL_PLUGIN before calling this.
 */
function vms_span_checker_uninstall() {
	require_once VMS_SPAN_CHECKER_DIR . 'uninstall.php';
}

register_uninstall_hook( VMS_SPAN_CHECKER_FILE, 'vms_span_checker_uninstall' );

/**
 * Load plugin.
 */
function vms_span_checker_bootstrap() {
	require_once VMS_SPAN_CHECKER_DIR . 'services/class-google-webrisk.php';
	require_once VMS_SPAN_CHECKER_DIR . 'services/class-virustotal.php';
	require_once VMS_SPAN_CHECKER_DIR . 'services/class-domain-validator.php';

	require_once VMS_SPAN_CHECKER_DIR . 'includes/class-whitelist.php';
	require_once VMS_SPAN_CHECKER_DIR . 'includes/class-disposable.php';
	require_once VMS_SPAN_CHECKER_DIR . 'includes/class-logger.php';
	require_once VMS_SPAN_CHECKER_DIR . 'includes/class-plugin-activity-log.php';
	require_once VMS_SPAN_CHECKER_DIR . 'includes/class-api-settings.php';
	require_once VMS_SPAN_CHECKER_DIR . 'includes/class-dashboard.php';
	require_once VMS_SPAN_CHECKER_DIR . 'includes/class-registration-guard.php';
	require_once VMS_SPAN_CHECKER_DIR . 'includes/class-ai-span-config.php';
	require_once VMS_SPAN_CHECKER_DIR . 'services/class-ai-span-aws-sigv4.php';
	require_once VMS_SPAN_CHECKER_DIR . 'services/class-ai-span-completion.php';
	require_once VMS_SPAN_CHECKER_DIR . 'includes/spam/interface-spam-check-component.php';
	require_once VMS_SPAN_CHECKER_DIR . 'includes/spam/class-spam-check-helpers.php';
	require_once VMS_SPAN_CHECKER_DIR . 'includes/spam/class-default-spam-check-components.php';
	require_once VMS_SPAN_CHECKER_DIR . 'includes/spam/class-comment-spam-controller.php';
	require_once VMS_SPAN_CHECKER_DIR . 'includes/class-comment-spam-rules.php';
	require_once VMS_SPAN_CHECKER_DIR . 'includes/class-comment-enforcement.php';
	require_once VMS_SPAN_CHECKER_DIR . 'includes/class-ai-span-comments.php';
	require_once VMS_SPAN_CHECKER_DIR . 'includes/class-ajax.php';
	require_once VMS_SPAN_CHECKER_DIR . 'includes/class-admin-menu.php';
	require_once VMS_SPAN_CHECKER_DIR . 'includes/class-enqueue-scripts.php';
	require_once VMS_SPAN_CHECKER_DIR . 'includes/class-auth-forms.php';
	require_once VMS_SPAN_CHECKER_DIR . 'includes/class-users-list-actions.php';

	// License core + Pro bridge. Must come before the rest of the plugin so the
	// `vms_span_checker_is_pro_active` filter is available everywhere.
	require_once VMS_SPAN_CHECKER_DIR . 'licensing/class-license-base.php';
	require_once VMS_SPAN_CHECKER_DIR . 'licensing/class-license-manager.php';
	require_once VMS_SPAN_CHECKER_DIR . 'licensing/class-license-admin.php';
	require_once VMS_SPAN_CHECKER_DIR . 'licensing/class-pro-bridge.php';

	VMS_Span_Checker\Licensing\License_Manager::instance();
	$vms_span_checker_pro_bridge = VMS_Span_Checker\Licensing\Pro_Bridge::instance();
	if ( is_admin() ) {
		new VMS_Span_Checker\Licensing\License_Admin();
	}

	new VMS_Span_Checker\Admin_Menu();
	new VMS_Span_Checker\Ajax();
	new VMS_Span_Checker\Enqueue_Scripts();
	new VMS_Span_Checker\Auth_Forms();
	new VMS_Span_Checker\AI_Span_Comments();
	new VMS_Span_Checker\Registration_Guard();
	new VMS_Span_Checker\Plugin_Activity_Log();
	if ( is_admin() ) {
		new VMS_Span_Checker\Users_List_Actions();
	}

	// Let the Pro plugin register its features. Pro must require its own
	// classes / shortcodes (free no longer ships them).
	$vms_span_checker_pro_bridge->fire_loaded();
}

add_action( 'plugins_loaded', 'vms_span_checker_bootstrap', 20 );

/**
 * Block login for users who have exceeded strike limit.
 *
 * @param WP_User|WP_Error|null $user     User object or error.
 * @param string                $username Username.
 * @param string                $password Password.
 * @return WP_User|WP_Error
 */
function vms_span_checker_check_login_blocked( $user, $username, $password ) {
	if ( is_wp_error( $user ) ) {
		return $user;
	}

	// If no user found, return as is
	if ( ! $user instanceof WP_User ) {
		return $user;
	}

	// Skip for admins if exempt
	$cfg = get_option( 'wsc-ai-span-config', array() );
	if ( ! empty( $cfg['block_user_exempt_admins'] ) && user_can( $user, 'manage_options' ) ) {
		return $user;
	}

	// Check if login is blocked
	if ( function_exists( 'vms_span_checker_is_login_blocked' ) && vms_span_checker_is_login_blocked( $user->ID ) ) {
		$expiry_days = isset( $cfg['block_user_strike_expiry_days'] ) ? (int) $cfg['block_user_strike_expiry_days'] : 30;
		if ( $expiry_days > 0 ) {
			$message = sprintf(
				/* translators: %d: number of days */
				__( 'Your account has been temporarily blocked due to suspicious activity. Please try again after %d days or contact support.', 'vms-span-checker' ),
				$expiry_days
			);
		} else {
			$message = __( 'Your account has been blocked due to suspicious activity. Please contact support.', 'vms-span-checker' );
		}
		return new WP_Error( 'wsc_login_blocked', $message );
	}

	return $user;
}
add_filter( 'authenticate', 'vms_span_checker_check_login_blocked', 100, 3 );
