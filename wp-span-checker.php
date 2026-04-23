<?php
/**
 * Plugin Name:       WP Span Checker
 * Plugin URI:        https://wordpress.org/plugins/wp-span-checker
 * Description:       Validates email domains on front-end forms using disposable domain lists, HTTPS checks, and optional VirusTotal / Google Web Risk APIs.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            VMS Universe
 * Author URI:        https://vmsuniverse.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-span-checker
 * Domain Path:       /languages
 *
 * @package WP_Span_Checker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WP_SPAN_CHECKER_VERSION', '1.0.0' );
define( 'WP_SPAN_CHECKER_FILE', __FILE__ );
define( 'WP_SPAN_CHECKER_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_SPAN_CHECKER_URL', plugin_dir_url( __FILE__ ) );

/** @deprecated Use WP_SPAN_CHECKER_* constants; kept for backward compatibility with includes. */
define( 'WP_Span_Checker_VERSION', WP_SPAN_CHECKER_VERSION );
define( 'WP_Span_Checker_ASSETS_URL', WP_SPAN_CHECKER_URL . 'assets/' );

require_once WP_SPAN_CHECKER_DIR . 'includes/functions.php';
require_once WP_SPAN_CHECKER_DIR . 'includes/class-activator.php';

add_action( 'plugins_loaded', array( 'WP_Span_Checker\Activator', 'maybe_upgrade_schema' ), 5 );

register_activation_hook( WP_SPAN_CHECKER_FILE, array( 'WP_Span_Checker\Activator', 'activate' ) );

/**
 * Uninstall callback — WordPress defines WP_UNINSTALL_PLUGIN before calling this.
 */
function wp_span_checker_uninstall() {
	require_once WP_SPAN_CHECKER_DIR . 'uninstall.php';
}

register_uninstall_hook( WP_SPAN_CHECKER_FILE, 'wp_span_checker_uninstall' );

/**
 * Load plugin.
 */
function wp_span_checker_bootstrap() {
	load_plugin_textdomain( 'wp-span-checker', false, dirname( plugin_basename( WP_SPAN_CHECKER_FILE ) ) . '/languages' );

	require_once WP_SPAN_CHECKER_DIR . 'services/class-google-webrisk.php';
	require_once WP_SPAN_CHECKER_DIR . 'services/class-virustotal.php';
	require_once WP_SPAN_CHECKER_DIR . 'services/class-domain-validator.php';

	require_once WP_SPAN_CHECKER_DIR . 'includes/class-whitelist.php';
	require_once WP_SPAN_CHECKER_DIR . 'includes/class-disposable.php';
	require_once WP_SPAN_CHECKER_DIR . 'includes/class-logger.php';
	require_once WP_SPAN_CHECKER_DIR . 'includes/class-form-settings.php';
	require_once WP_SPAN_CHECKER_DIR . 'includes/class-form.php';
	require_once WP_SPAN_CHECKER_DIR . 'includes/class-api-settings.php';
	require_once WP_SPAN_CHECKER_DIR . 'includes/class-form-builder.php';
	require_once WP_SPAN_CHECKER_DIR . 'includes/class-dashboard.php';
	require_once WP_SPAN_CHECKER_DIR . 'includes/class-registration-guard.php';
	require_once WP_SPAN_CHECKER_DIR . 'includes/class-ai-span-config.php';
	require_once WP_SPAN_CHECKER_DIR . 'services/class-ai-span-aws-sigv4.php';
	require_once WP_SPAN_CHECKER_DIR . 'services/class-ai-span-completion.php';
	require_once WP_SPAN_CHECKER_DIR . 'includes/class-ai-span-summary.php';
	require_once WP_SPAN_CHECKER_DIR . 'includes/spam/interface-spam-check-component.php';
	require_once WP_SPAN_CHECKER_DIR . 'includes/spam/class-spam-check-helpers.php';
	require_once WP_SPAN_CHECKER_DIR . 'includes/spam/class-default-spam-check-components.php';
	require_once WP_SPAN_CHECKER_DIR . 'includes/spam/class-comment-spam-controller.php';
	require_once WP_SPAN_CHECKER_DIR . 'includes/class-comment-spam-rules.php';
	require_once WP_SPAN_CHECKER_DIR . 'includes/class-ai-span-comments.php';
	require_once WP_SPAN_CHECKER_DIR . 'includes/class-ajax.php';
	require_once WP_SPAN_CHECKER_DIR . 'includes/class-admin-menu.php';
	require_once WP_SPAN_CHECKER_DIR . 'includes/class-enqueue-scripts.php';

	new WP_Span_Checker\Admin_Menu();
	new WP_Span_Checker\Ajax();
	new WP_Span_Checker\Enqueue_Scripts();
	new WP_Span_Checker\AI_Span_Summary();
	new WP_Span_Checker\AI_Span_Comments();
	new WP_Span_Checker\Registration_Guard();

	require_once WP_SPAN_CHECKER_DIR . 'public/render-form.php';
}

add_action( 'plugins_loaded', 'wp_span_checker_bootstrap', 20 );
