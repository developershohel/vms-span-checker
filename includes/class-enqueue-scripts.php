<?php
/**
 * Front-end and admin assets.
 *
 * @package WP_Span_Checker
 */

namespace WP_Span_Checker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Script and style registration.
 */
class Enqueue_Scripts {

	/**
	 * Regex examples for admin UI (translated labels).
	 *
	 * @var array<int, array<string, string>>
	 */
	private $regex_list;

	/**
	 * Register hooks and data.
	 */
	public function __construct() {
		$this->regex_list = $this->get_regex_list();

		/* Priority 1: register assets before most plugins (default 10) so our JS runs first in footer. */
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ), 5 );
		add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );
	}

	/**
	 * Scope admin styling for plugin screens.
	 *
	 * @param string $classes Space-separated body classes.
	 * @return string
	 */
	public function admin_body_class( $classes ) {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return $classes;
		}
		$screen = get_current_screen();
		if ( $screen && false !== strpos( $screen->id, 'wp-span-checker' ) ) {
			$classes .= ' wsc-plugin-admin';
		}
		return $classes;
	}

	/**
	 * Localized regex documentation rows.
	 *
	 * @return array<int, array<string, string>>
	 */
	private function get_regex_list() {
		// Order: most-used contact / auth patterns first, niche formats later.
		return array(
			array(
				'key'             => 'strict_email_address_only',
				'name'            => __( 'Strict Email (addresses only)', 'wp-span-checker' ),
				'pattern'         => '/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/',
				'desc'            => __( 'One email-shaped address only—not a display name, sentence, or multiple addresses.', 'wp-span-checker' ),
				'example'         => 'john.doe@gmail.com',
				'valid_example'   => 'john.doe@gmail.com',
				'invalid_example' => 'Jane Doe <jane@example.com>',
			),
			array(
				'key'             => 'strict_email_tld_limit',
				'name'            => __( 'Strict Email (TLD length limit)', 'wp-span-checker' ),
				'pattern'         => '/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,6}$/',
				'desc'            => __( 'Email format with TLD between 2 and 6 letters.', 'wp-span-checker' ),
				'example'         => 'user@domain.co.uk',
				'valid_example'   => 'user@domain.co.uk',
				'invalid_example' => 'user@localhost',
			),
			array(
				'key'             => 'simple_email',
				'name'            => __( 'Simple Email (lenient)', 'wp-span-checker' ),
				'pattern'         => '/^[^\s@]+@[^\s@]+\.[^\s@]+$/',
				'desc'            => __( 'Very loose UX check—allows unusual formats.', 'wp-span-checker' ),
				'example'         => 'user@example.com',
				'valid_example'   => 'user@example.com',
				'invalid_example' => 'not-an-email',
			),
			array(
				'key'             => 'username_wp',
				'name'            => __( 'Username (3–16 characters)', 'wp-span-checker' ),
				'pattern'         => '/^[a-zA-Z0-9._-]{3,16}$/',
				'desc'            => __( 'Letters, numbers, dot, underscore, and hyphen.', 'wp-span-checker' ),
				'example'         => 'john_doe',
				'valid_example'   => 'john_doe',
				'invalid_example' => 'ab',
			),
			array(
				'key'             => 'password_strong',
				'name'            => __( 'Strong Password (recommended)', 'wp-span-checker' ),
				'pattern'         => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s]).{8,}$/',
				'desc'            => __( 'Minimum 8 characters with mixed case, number, and symbol.', 'wp-span-checker' ),
				'example'         => 'Str0ng!Pass',
				'valid_example'   => 'Str0ng!Pass',
				'invalid_example' => 'weak',
			),
			array(
				'key'             => 'phone_e164',
				'name'            => __( 'International Phone (E.164)', 'wp-span-checker' ),
				'pattern'         => '/^\+?[1-9]\d{1,14}$/',
				'desc'            => __( 'E.164 international phone format.', 'wp-span-checker' ),
				'example'         => '+14155552671',
				'valid_example'   => '+14155552671',
				'invalid_example' => '++4400',
			),
			array(
				'key'             => 'phone_us',
				'name'            => __( 'US Phone (common)', 'wp-span-checker' ),
				'pattern'         => '/^\(?([2-9][0-8][0-9])\)?[-.\s]?([2-9][0-9]{2})[-.\s]?([0-9]{4})$/',
				'desc'            => __( 'US phone numbers with optional parentheses or dashes.', 'wp-span-checker' ),
				'example'         => '(415) 555-2671',
				'valid_example'   => '(415) 555-2671',
				'invalid_example' => '12345',
			),
			array(
				'key'             => 'url_http',
				'name'            => __( 'URL (http/https)', 'wp-span-checker' ),
				'pattern'         => '/^(https?:\/\/)?([\w-]+\.)+[\w-]+(\/[-\w@:%_+.~#?&\/=]*)?$/',
				'desc'            => __( 'Checks basic HTTP/HTTPS URLs. Not fully RFC-compliant but practical.', 'wp-span-checker' ),
				'example'         => 'https://example.com/path?x=1',
				'valid_example'   => 'https://example.com/path',
				'invalid_example' => 'ht!tp://bad',
			),
			array(
				'key'             => 'no_links_textarea',
				'name'            => __( 'Plain text (no URLs)', 'wp-span-checker' ),
				'pattern'         => '/^(?!.*https?:\/\/).+$/is',
				'desc'            => __( 'Allows any text except strings that look like http(s) URLs.', 'wp-span-checker' ),
				'example'         => __( 'Hello, thanks for your message.', 'wp-span-checker' ),
				'valid_example'   => __( 'Hello, thanks for your message.', 'wp-span-checker' ),
				'invalid_example' => 'Visit https://spam.example',
			),
			array(
				'key'             => 'alphanumeric_spaces',
				'name'            => __( 'Letters and numbers (spaces OK)', 'wp-span-checker' ),
				'pattern'         => '/^[a-zA-Z0-9\s]{1,200}$/',
				'desc'            => __( 'Safe short labels without punctuation.', 'wp-span-checker' ),
				'example'         => 'Order 42 details',
				'valid_example'   => 'Order 42 details',
				'invalid_example' => 'hack<script>',
			),
			array(
				'key'             => 'numeric_only',
				'name'            => __( 'Digits only', 'wp-span-checker' ),
				'pattern'         => '/^\d+$/',
				'desc'            => __( 'Whole numbers with no spaces or symbols.', 'wp-span-checker' ),
				'example'         => '1024',
				'valid_example'   => '1024',
				'invalid_example' => '10a4',
			),
			array(
				'key'             => 'slug_lower',
				'name'            => __( 'Slug (lowercase, hyphen)', 'wp-span-checker' ),
				'pattern'         => '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
				'desc'            => __( 'URL slug with lowercase words separated by hyphens.', 'wp-span-checker' ),
				'example'         => 'my-blog-post-1',
				'valid_example'   => 'my-blog-post-1',
				'invalid_example' => 'CamelCase',
			),
			array(
				'key'             => 'date_iso',
				'name'            => __( 'Date YYYY-MM-DD', 'wp-span-checker' ),
				'pattern'         => '/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/',
				'desc'            => __( 'Simple date format (does not validate leap years).', 'wp-span-checker' ),
				'example'         => '2026-09-21',
				'valid_example'   => '2026-09-21',
				'invalid_example' => '2026-13-40',
			),
			array(
				'key'             => 'hostname',
				'name'            => __( 'Domain (hostname)', 'wp-span-checker' ),
				'pattern'         => '/^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/i',
				'desc'            => __( 'Hostname such as example.com or sub.example.net.', 'wp-span-checker' ),
				'example'         => 'sub.example.com',
				'valid_example'   => 'sub.example.com',
				'invalid_example' => '-bad-.com',
			),
			array(
				'key'             => 'ipv4',
				'name'            => __( 'IPv4', 'wp-span-checker' ),
				'pattern'         => '/^(25[0-5]|2[0-4]\d|1?\d?\d)(\.(25[0-5]|2[0-4]\d|1?\d?\d)){3}$/',
				'desc'            => __( 'Validates IPv4 addresses.', 'wp-span-checker' ),
				'example'         => '192.168.0.1',
				'valid_example'   => '192.168.0.1',
				'invalid_example' => '999.0.0.1',
			),
			array(
				'key'             => 'ipv6_basic',
				'name'            => __( 'IPv6 (basic)', 'wp-span-checker' ),
				'pattern'         => '/^([0-9a-f]{1,4}:){7}[0-9a-f]{1,4}$/i',
				'desc'            => __( 'Simple IPv6 validation (full form).', 'wp-span-checker' ),
				'example'         => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
				'valid_example'   => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
				'invalid_example' => 'gggg::1',
			),
			array(
				'key'             => 'hex_color',
				'name'            => __( 'Hex Color (#rgb or #rrggbb)', 'wp-span-checker' ),
				'pattern'         => '/^#?([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
				'desc'            => __( 'Hex color codes with or without a leading hash.', 'wp-span-checker' ),
				'example'         => '#1a2b3c',
				'valid_example'   => '#1a2b3c',
				'invalid_example' => '#gg0000',
			),
			array(
				'key'             => 'uuid_v4',
				'name'            => __( 'UUID v4', 'wp-span-checker' ),
				'pattern'         => '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
				'desc'            => __( 'Validates UUID version 4.', 'wp-span-checker' ),
				'example'         => '550e8400-e29b-41d4-a716-446655440000',
				'valid_example'   => '550e8400-e29b-41d4-a716-446655440000',
				'invalid_example' => '550e8400-e29b-41d4-a716',
			),
		);
	}

	/**
	 * Enqueue admin scripts (only on WP Span Checker screens).
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_admin_scripts( $hook_suffix = '' ) {
		$hook_suffix = (string) $hook_suffix;
		if ( false === strpos( $hook_suffix, 'wp-span-checker' ) ) {
			return;
		}

		wp_enqueue_style(
			'wp-span-checker-dashboard',
			WP_Span_Checker_ASSETS_URL . 'css/admin-dashboard.css',
			array(),
			WP_Span_Checker_VERSION
		);

		wp_enqueue_style(
			'wp-span-checker-ui',
			WP_Span_Checker_ASSETS_URL . 'css/wp-span-checker.css',
			array( 'wp-span-checker-dashboard' ),
			WP_Span_Checker_VERSION
		);

		wp_enqueue_style(
			'wp-span-checker-sweetalert',
			WP_Span_Checker_ASSETS_URL . 'plugins/sweetalert2/sweetalert2.min.css',
			array( 'wp-span-checker-ui' ),
			WP_Span_Checker_VERSION
		);
		wp_enqueue_script(
			'wp-span-checker-sweetalert',
			WP_Span_Checker_ASSETS_URL . 'plugins/sweetalert2/sweetalert2.all.min.js',
			array( 'jquery' ),
			WP_Span_Checker_VERSION,
			true
		);

		wp_enqueue_script(
			'wsc-admin-toast',
			WP_Span_Checker_ASSETS_URL . 'js/admin-toast.js',
			array( 'jquery', 'wp-span-checker-sweetalert' ),
			WP_Span_Checker_VERSION,
			true
		);

		$needs_datatables = (
			false !== strpos( $hook_suffix, 'whitelist' )
			|| false !== strpos( $hook_suffix, 'disposable' )
			|| false !== strpos( $hook_suffix, 'form-settings' )
		);

		$needs_ai_summary = ( false !== strpos( $hook_suffix, 'wp-span-checker-ai-summaries' ) );

		if ( $needs_ai_summary ) {
			wp_enqueue_script(
				'wsc-ai-admin',
				WP_Span_Checker_ASSETS_URL . 'js/ai-admin.js',
				array( 'jquery', 'wp-span-checker-sweetalert' ),
				WP_Span_Checker_VERSION,
				true
			);
			wp_localize_script(
				'wsc-ai-admin',
				'WSCAiAdmin',
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'wp_span_checker_nonce' ),
					'i18n'    => array(
						'error'   => __( 'Request failed.', 'wp-span-checker' ),
						'success' => __( 'Summary saved. Refreshing…', 'wp-span-checker' ),
					),
				)
			);
		}

		if ( ! $needs_datatables ) {
			return;
		}

		wp_enqueue_style( 'wp-span-checker-datatable', WP_Span_Checker_ASSETS_URL . 'plugins/DataTables/datatables.min.css', array( 'wp-span-checker-ui' ), WP_Span_Checker_VERSION );
		wp_enqueue_script( 'wp-span-checker-datatable', WP_Span_Checker_ASSETS_URL . 'plugins/DataTables/datatables.min.js', array( 'jquery' ), WP_Span_Checker_VERSION, true );

		wp_enqueue_script(
			'wp-span-domain-js',
			WP_Span_Checker_ASSETS_URL . 'js/domains.js',
			array( 'jquery', 'wp-span-checker-sweetalert' ),
			WP_Span_Checker_VERSION,
			true
		);

		wp_set_script_translations( 'wp-span-domain-js', 'wp-span-checker', WP_SPAN_CHECKER_DIR . 'languages' );

		wp_localize_script(
			'wp-span-domain-js',
			'WPSpanChecker',
			array(
				'ajaxurl'          => admin_url( 'admin-ajax.php' ),
				'nonce'            => wp_create_nonce( 'wp_span_checker_nonce' ),
				'regexList'        => $this->regex_list,
				'pageTargetLabels' => wp_span_checker_page_target_presets(),
				'i18n'             => wp_span_checker_get_js_i18n(),
			)
		);
	}

	/**
	 * Enqueue public scripts when a form mapping applies to this request.
	 */
	public function enqueue_scripts() {
		$page_id  = get_queried_object_id();
		$settings = new Form_Settings();
		$all_rows = $settings->get_settings() ?? array();
		$filtered = array();

		foreach ( $all_rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			if ( wp_span_checker_row_matches_current_request( $row ) ) {
				$filtered[] = $row;
			}
		}

		if ( empty( $filtered ) ) {
			return;
		}

		wp_enqueue_style( 'wp-span-checker-sweetalert', WP_Span_Checker_ASSETS_URL . 'plugins/sweetalert2/sweetalert2.min.css', array(), WP_Span_Checker_VERSION );
		wp_enqueue_script( 'wp-span-checker-sweetalert', WP_Span_Checker_ASSETS_URL . 'plugins/sweetalert2/sweetalert2.all.min.js', array( 'jquery' ), WP_Span_Checker_VERSION, true );
		wp_enqueue_script(
			'wp-span-checker',
			WP_Span_Checker_ASSETS_URL . 'js/wp-span-checker.js',
			array(
				'jquery',
				'wp-span-checker-sweetalert',
			),
			WP_Span_Checker_VERSION,
			true
		);

		wp_set_script_translations( 'wp-span-checker', 'wp-span-checker', WP_SPAN_CHECKER_DIR . 'languages' );

		wp_localize_script(
			'wp-span-checker',
			'WPSpanChecker',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'wp_span_checker_nonce' ),
				'pageID'    => $page_id,
				'settings'  => $filtered,
				'regexList' => $this->regex_list,
				'i18n'      => wp_span_checker_get_js_i18n(),
			)
		);
	}
}
