<?php
/**
 * Plugin Name:       VMS Elements Form Guard
 * Plugin URI:        https://wordpress.org/plugins/vms-elements-form-guard
 * Description:       Validates email domains on front-end forms using disposable domain lists, HTTPS checks, and optional third-party safe-browsing APIs.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            VMS Elements
 * Author URI:        https://vmselements.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       vms-elements-form-guard
 * Domain Path:       /languages
 *
 * @package VMS_Elements_Form_Guard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'VMS_ELEMENTS_FORM_GUARD_VERSION', '1.0.0' );
define( 'VMS_ELEMENTS_FORM_GUARD_FILE', __FILE__ );
define( 'VMS_ELEMENTS_FORM_GUARD_DIR', plugin_dir_path( __FILE__ ) );
define( 'VMS_ELEMENTS_FORM_GUARD_URL', plugin_dir_url( __FILE__ ) );
define( 'VMS_ELEMENTS_FORM_GUARD_ASSETS_URL', VMS_ELEMENTS_FORM_GUARD_URL . 'assets/' );

/*
 * Public marketing URL used by the free plugin "Upgrade Now" page. The free
 * plugin never collects or validates a license key — activation lives
 * exclusively in the separately-distributed Pro plugin.
 */
if ( ! defined( 'VMS_ELEMENTS_FORM_GUARD_PRO_UPGRADE_URL' ) ) {
	define( 'VMS_ELEMENTS_FORM_GUARD_PRO_UPGRADE_URL', 'https://vmselements.com/product/vms-elements-form-guard-pro' );
}

require_once VMS_ELEMENTS_FORM_GUARD_DIR . 'includes/functions.php';
require_once VMS_ELEMENTS_FORM_GUARD_DIR . 'includes/class-activator.php';

/*
 * Translations: WordPress 4.6+ auto-loads translation files from the bundled
 * languages/ folder using the "Text Domain" and "Domain Path" headers in this
 * file. No explicit load_plugin_textdomain() call is needed (and is in fact
 * discouraged — it causes "loaded too early" notices in WP 6.7+).
 */

add_action( 'plugins_loaded', array( 'VMS_Elements_Form_Guard\Activator', 'maybe_upgrade_schema' ), 5 );

register_activation_hook( VMS_ELEMENTS_FORM_GUARD_FILE, array( 'VMS_Elements_Form_Guard\Activator', 'activate' ) );

/**
 * Uninstall callback — WordPress defines WP_UNINSTALL_PLUGIN before calling this.
 */
function vms_elements_form_guard_uninstall() {
	require_once VMS_ELEMENTS_FORM_GUARD_DIR . 'uninstall.php';
}

register_uninstall_hook( VMS_ELEMENTS_FORM_GUARD_FILE, 'vms_elements_form_guard_uninstall' );

/**
 * Load plugin.
 */
function vms_elements_form_guard_bootstrap() {
	require_once VMS_ELEMENTS_FORM_GUARD_DIR . 'services/class-google-webrisk.php';
	require_once VMS_ELEMENTS_FORM_GUARD_DIR . 'services/class-virustotal.php';
	require_once VMS_ELEMENTS_FORM_GUARD_DIR . 'services/class-domain-validator.php';

	require_once VMS_ELEMENTS_FORM_GUARD_DIR . 'includes/class-whitelist.php';
	require_once VMS_ELEMENTS_FORM_GUARD_DIR . 'includes/class-disposable.php';
	require_once VMS_ELEMENTS_FORM_GUARD_DIR . 'includes/class-logger.php';
	require_once VMS_ELEMENTS_FORM_GUARD_DIR . 'includes/class-plugin-activity-log.php';
	require_once VMS_ELEMENTS_FORM_GUARD_DIR . 'includes/class-api-settings.php';
	require_once VMS_ELEMENTS_FORM_GUARD_DIR . 'includes/class-dashboard.php';
	require_once VMS_ELEMENTS_FORM_GUARD_DIR . 'includes/class-registration-guard.php';
	require_once VMS_ELEMENTS_FORM_GUARD_DIR . 'includes/class-ai-span-config.php';
	require_once VMS_ELEMENTS_FORM_GUARD_DIR . 'services/class-ai-span-aws-sigv4.php';
	require_once VMS_ELEMENTS_FORM_GUARD_DIR . 'services/class-ai-span-completion.php';
	require_once VMS_ELEMENTS_FORM_GUARD_DIR . 'includes/spam/interface-spam-check-component.php';
	require_once VMS_ELEMENTS_FORM_GUARD_DIR . 'includes/spam/class-spam-check-helpers.php';
	require_once VMS_ELEMENTS_FORM_GUARD_DIR . 'includes/spam/class-default-spam-check-components.php';
	require_once VMS_ELEMENTS_FORM_GUARD_DIR . 'includes/spam/class-comment-spam-controller.php';
	require_once VMS_ELEMENTS_FORM_GUARD_DIR . 'includes/class-comment-spam-rules.php';
	require_once VMS_ELEMENTS_FORM_GUARD_DIR . 'includes/class-comment-enforcement.php';
	require_once VMS_ELEMENTS_FORM_GUARD_DIR . 'includes/class-ai-span-comments.php';
	require_once VMS_ELEMENTS_FORM_GUARD_DIR . 'includes/class-ajax.php';
	require_once VMS_ELEMENTS_FORM_GUARD_DIR . 'includes/class-admin-menu.php';
	require_once VMS_ELEMENTS_FORM_GUARD_DIR . 'includes/class-enqueue-scripts.php';
	require_once VMS_ELEMENTS_FORM_GUARD_DIR . 'includes/class-auth-forms.php';
	require_once VMS_ELEMENTS_FORM_GUARD_DIR . 'includes/class-users-list-actions.php';

	// Pro feature surface (lightweight: no license code, no API calls). Just
	// declares the default filter values and fires the loaded action that the
	// Pro plugin hooks into to register its own features.
	require_once VMS_ELEMENTS_FORM_GUARD_DIR . 'includes/class-pro-features.php';
	$vms_elements_form_guard_pro_features = VMS_Elements_Form_Guard\Pro_Features::instance();

	new VMS_Elements_Form_Guard\Admin_Menu();
	new VMS_Elements_Form_Guard\Ajax();
	new VMS_Elements_Form_Guard\Enqueue_Scripts();
	new VMS_Elements_Form_Guard\Auth_Forms();
	new VMS_Elements_Form_Guard\AI_Span_Comments();
	new VMS_Elements_Form_Guard\Registration_Guard();
	new VMS_Elements_Form_Guard\Plugin_Activity_Log();
	if ( is_admin() ) {
		new VMS_Elements_Form_Guard\Users_List_Actions();
	}

	// Let the Pro plugin register its features. Pro must require its own
	// classes / shortcodes (free no longer ships them).
	$vms_elements_form_guard_pro_features->fire_loaded();
}

add_action( 'init', 'vms_elements_form_guard_bootstrap', 0 );

/**
 * Block login for users who have exceeded strike limit.
 *
 * @param WP_User|WP_Error|null $user     User object or error.
 * @param string                $username Username.
 * @param string                $password Password.
 * @return WP_User|WP_Error
 */
function vms_elements_form_guard_check_login_blocked( $user, $username, $password ) {
	if ( is_wp_error( $user ) ) {
		return $user;
	}

	if ( ! $user instanceof WP_User ) {
		return $user;
	}

	$cfg = get_option( 'vefg-ai-span-config', array() );
	if ( ! empty( $cfg['block_user_exempt_admins'] ) && user_can( $user, 'manage_options' ) ) {
		return $user;
	}

	if ( function_exists( 'vms_elements_form_guard_is_login_blocked' ) && vms_elements_form_guard_is_login_blocked( $user->ID ) ) {
		$expiry_days = isset( $cfg['block_user_strike_expiry_days'] ) ? (int) $cfg['block_user_strike_expiry_days'] : 30;
		if ( $expiry_days > 0 ) {
			$message = sprintf(
				/* translators: %d: number of days */
				__( 'Your account has been temporarily blocked due to suspicious activity. Please try again after %d days or contact support.', 'vms-elements-form-guard' ),
				$expiry_days
			);
		} else {
			$message = __( 'Your account has been blocked due to suspicious activity. Please contact support.', 'vms-elements-form-guard' );
		}
		return new WP_Error( 'vefg_login_blocked', $message );
	}

	return $user;
}
add_filter( 'authenticate', 'vms_elements_form_guard_check_login_blocked', 100, 3 );
