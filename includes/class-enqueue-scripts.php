<?php

namespace WP_Span_Checker;

use function Commentace\get_paged;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Enqueue_Scripts {
	private $wpdb;
	private $post;

	private $regex_list;

	public function __construct() {
		global $wpdb, $post;
		$this->wpdb = $wpdb;
		$this->post = $post;
		$this->regex_list = [
			[
				'name'    => 'Simple Email',
				'pattern' => '/^[^\s@]+@[^\s@]+\.[^\s@]+$/',
				'desc'    => 'Basic email check (good for UX-level validation).',
				'example' => 'user@example.com',
			],
			[
				'name'    => 'Common Email',
				'pattern' => '/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/',
				'desc'    => 'More restrictive email: enforces valid domain/TLD length.',
				'example' => 'john.doe@gmail.com',
			],
			[
				'name'    => 'Strict Email (TLD 2+)',
				'pattern' => '/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,6}$/',
				'desc'    => 'Stricter TLD length limit (2–6 chars).',
				'example' => 'user@domain.co.uk',
			],
			[
				'name'    => 'URL (http/https)',
				'pattern' => '/^(https?:\/\/)?([\w-]+\.)+[\w-]+(\/[-\w@:%_+.~#?&\/=]*)?$/',
				'desc'    => 'Checks basic http/https URLs. Not fully RFC but practical.',
				'example' => 'https://example.com/path?x=1',
			],
			[
				'name'    => 'Domain (hostname)',
				'pattern' => '/^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/i',
				'desc'    => 'Extract/validate domain like example.com or sub.domain.net',
				'example' => 'sub.example.com',
			],
			[
				'name'    => 'IPv4',
				'pattern' => '/^(25[0-5]|2[0-4]\d|1?\d?\d)(\.(25[0-5]|2[0-4]\d|1?\d?\d)){3}$/',
				'desc'    => 'Validates IPv4 addresses (0.0.0.0 - 255.255.255.255).',
				'example' => '192.168.0.1',
			],
			[
				'name'    => 'IPv6 (basic)',
				'pattern' => '/^([0-9a-f]{1,4}:){7}[0-9a-f]{1,4}$/i',
				'desc'    => 'Simple IPv6 block validation (full form).',
				'example' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
			],
			[
				'name'    => 'International Phone (E.164)',
				'pattern' => '/^\+?[1-9]\d{1,14}$/',
				'desc'    => 'E.164 international phone format (recommended).',
				'example' => '+14155552671',
			],
			[
				'name'    => 'US Phone (common)',
				'pattern' => '/^\(?([2-9][0-8][0-9])\)?[-.\s]?([2-9][0-9]{2})[-.\s]?([0-9]{4})$/',
				'desc'    => 'US phone numbers (with optional parentheses/dashes).',
				'example' => '(415) 555-2671',
			],
			[
				'name'    => 'Strong Password (recommended)',
				'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s]).{8,}$/',
				'desc'    => 'Min 8 chars, at least 1 lowercase,1 uppercase,1 number,1 special char.',
				'example' => 'Str0ng!Pass',
			],
			[
				'name'    => 'Username (3-16 chars)',
				'pattern' => '/^[a-zA-Z0-9._-]{3,16}$/',
				'desc'    => 'Common username rules: letters, numbers, dot, underscore, hyphen.',
				'example' => 'john_doe',
			],
			[
				'name'    => 'Slug (lowercase, hyphen)',
				'pattern' => '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
				'desc'    => 'URL slug (lowercase words separated by single hyphens).',
				'example' => 'my-blog-post-1',
			],
			[
				'name'    => 'Date YYYY-MM-DD',
				'pattern' => '/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/',
				'desc'    => "Simple date format check (doesn't validate month lengths/leap years).",
				'example' => '2025-09-21',
			],
			[
				'name'    => 'Hex Color (#rgb or #rrggbb)',
				'pattern' => '/^#?([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
				'desc'    => "Matches hex color codes with or without leading '#'.",
				'example' => '#1a2b3c',
			],
			[
				'name'    => 'UUID v4',
				'pattern' => '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
				'desc'    => 'Validates UUID version 4.',
				'example' => '550e8400-e29b-41d4-a716-446655440000',
			],
		];

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 30 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ), 15 );
	}

	/**
	 * Enqueue admin scripts
	 */
	public function enqueue_admin_scripts() {
		wp_enqueue_style( 'wp-span-checker-sweetalert', WP_Span_Checker_ASSETS_URL . 'plugins/sweetalert2/sweetalert2.min.css', array(), WP_Span_Checker_VERSION );
		wp_enqueue_script( 'wp-span-checker-sweetalert', WP_Span_Checker_ASSETS_URL . 'plugins/sweetalert2/sweetalert2.all.min.js', array( 'jquery' ), WP_Span_Checker_VERSION, true );
		wp_enqueue_style( 'wp-span-checker-datatable', WP_Span_Checker_ASSETS_URL . 'plugins/DataTables/datatables.min.css', array(), WP_Span_Checker_VERSION );
		wp_enqueue_script( 'wp-span-checker-datatable', WP_Span_Checker_ASSETS_URL . 'plugins/DataTables/datatables.min.js', array( 'jquery' ), WP_Span_Checker_VERSION, true );
		// React form builder

		wp_enqueue_style(
			'wp-span-checker',
			WP_Span_Checker_ASSETS_URL . 'css/wp-span-checker.min.css',
			[],
			WP_Span_Checker_VERSION
		);

		// AJAX for whitelist/disposable domains
		wp_enqueue_script(
			'wp-span-domain-js',
			WP_Span_Checker_ASSETS_URL . 'js/domains.js',
			[ 'jquery', 'wp-span-checker-sweetalert' ], WP_Span_Checker_VERSION
			,
			true
		);

		wp_localize_script( 'wp-span-domain-js', 'WPSpanChecker', [
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'wp_span_checker_nonce' ),
			'regexList' => $this->regex_list
		] );
	}

	public function enqueue_scripts() {
		$page_id  = get_queried_object_id();
		$settings = new Form_Settings();
		wp_enqueue_style( 'wp-span-checker-sweetalert', WP_Span_Checker_ASSETS_URL . 'plugins/sweetalert2/sweetalert2.min.css', array(), WP_Span_Checker_VERSION );
		wp_enqueue_script( 'wp-span-checker-sweetalert', WP_Span_Checker_ASSETS_URL . 'plugins/sweetalert2/sweetalert2.all.min.js', array( 'jquery' ), WP_Span_Checker_VERSION, true );
		wp_enqueue_script( 'wp-span-checker', WP_Span_Checker_ASSETS_URL . 'js/wp-span-checker.min.js', array(
			'jquery',
			'wp-span-checker-sweetalert'
		), WP_Span_Checker_VERSION, true );
		wp_localize_script( 'wp-span-checker', 'WPSpanChecker', [
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( 'wp_span_checker_nonce' ),
			'pageID'    => $page_id,
			'settings'  => $settings->get_settings() ?? [],
			'regexList' => $this->regex_list
		] );
	}
}