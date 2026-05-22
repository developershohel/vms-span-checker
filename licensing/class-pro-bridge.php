<?php
/**
 * Pro extension-point contract.
 *
 * The free plugin declares the filters/actions the Pro plugin hooks into.
 * This file is the *only* coupling between the two plugins — Pro never
 * imports free PHP symbols, it only consumes hooks defined here.
 *
 * If Pro is not installed (or its license check fails), every filter falls
 * back to its safe default, and the free plugin keeps running unchanged.
 *
 * @package VMS_Span_Checker
 */

namespace VMS_Span_Checker\Licensing;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tiny registry that documents and bootstraps the Pro bridge.
 */
class Pro_Bridge {

	/**
	 * @var self|null
	 */
	private static $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_filter( 'vms_span_checker_email_template_html', array( $this, 'default_email_html' ), 5, 3 );
		add_filter( 'vms_span_checker_post_summary_text', array( $this, 'default_post_summary' ), 5, 2 );
		add_filter( 'vms_span_checker_pro_features', array( $this, 'default_pro_features' ), 5 );
	}

	/**
	 * Fire the "loaded" action that Pro hooks into to register its own classes.
	 *
	 * Called from the main plugin bootstrap.
	 */
	public function fire_loaded(): void {
		do_action( 'vms_span_checker_loaded', $this );
	}

	/**
	 * Convenience for callers: is the Pro plugin active *and* licensed?
	 */
	public static function is_pro_active(): bool {
		return (bool) apply_filters( 'vms_span_checker_is_pro_active', false );
	}

	/**
	 * Default for `vms_span_checker_email_template_html` — fall back to the
	 * caller's hardcoded HTML when Pro is not active.
	 *
	 * Pro overrides this at a higher priority and returns its visual-editor
	 * output.
	 *
	 * @param string               $fallback_html Hardcoded HTML from the free plugin.
	 * @param string               $template_key  Logical key (e.g. `account_activation`).
	 * @param array<string, mixed> $vars          Variables substituted into the template.
	 * @return string
	 */
	public function default_email_html( $fallback_html, $template_key, $vars ): string {
		unset( $template_key, $vars );
		return is_string( $fallback_html ) ? $fallback_html : '';
	}

	/**
	 * Default for `vms_span_checker_post_summary_text` — no summary text
	 * available when Pro AI Summaries is not active.
	 *
	 * @param string $current Existing return value (kept for additive filtering).
	 * @param int    $post_id Post being summarised.
	 * @return string
	 */
	public function default_post_summary( $current, $post_id ): string {
		unset( $post_id );
		return is_string( $current ) ? $current : '';
	}

	/**
	 * Default for `vms_span_checker_pro_features` — list of Pro-only menu
	 * entries the free plugin should render as lock-icon stubs when Pro is
	 * inactive.
	 *
	 * Pro replaces / extends this list at a higher priority.
	 *
	 * @param array<int, array<string, mixed>> $features Feature metadata.
	 * @return array<int, array<string, mixed>>
	 */
	public function default_pro_features( $features ): array {
		if ( ! is_array( $features ) ) {
			$features = array();
		}
		$defaults = array(
			array(
				'slug'        => 'wsc-form-guard',
				'title'       => __( 'Form Guard', 'vms-span-checker' ),
				'description' => __( 'Validate any front-end form (Contact Form 7, WPForms, custom) with anti-spam rules, AI moderation, and per-form scope.', 'vms-span-checker' ),
				'position'    => 30,
			),
			array(
				'slug'        => 'wsc-contact-guard',
				'title'       => __( 'Contact Guard', 'vms-span-checker' ),
				'description' => __( 'Block disposable / risky senders on contact forms before they reach your inbox.', 'vms-span-checker' ),
				'position'    => 31,
			),
			array(
				'slug'        => 'wsc-subscribe-guard',
				'title'       => __( 'Subscribe Guard', 'vms-span-checker' ),
				'description' => __( 'Stop fake newsletter sign-ups and disposable-email subscribers at the source.', 'vms-span-checker' ),
				'position'    => 32,
			),
			array(
				'slug'        => 'wsc-product-review-guard',
				'title'       => __( 'Product Review Guard', 'vms-span-checker' ),
				'description' => __( 'Add WooCommerce review moderation with AI scoring and disposable-email blocks.', 'vms-span-checker' ),
				'position'    => 33,
			),
			array(
				'slug'        => 'wsc-ai-summaries',
				'title'       => __( 'AI Post Summaries', 'vms-span-checker' ),
				'description' => __( 'Generate short AI summaries for posts to give Comment Guard more context (and surface them on the front end).', 'vms-span-checker' ),
				'position'    => 34,
			),
			array(
				'slug'        => 'wsc-ai-product-summaries',
				'title'       => __( 'AI Product Summaries', 'vms-span-checker' ),
				'description' => __( 'AI-generated summaries for WooCommerce products.', 'vms-span-checker' ),
				'position'    => 35,
			),
			array(
				'slug'        => 'wsc-email-templates',
				'title'       => __( 'Email Templates', 'vms-span-checker' ),
				'description' => __( 'Visual editor for every transactional email sent by Auth Forms and the Guards.', 'vms-span-checker' ),
				'position'    => 36,
			),
		);
		return array_merge( $features, $defaults );
	}
}
