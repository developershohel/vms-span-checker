<?php
/**
 * Pro extension-point contract (free-side defaults).
 *
 * The free plugin declares the filters/actions the Pro plugin hooks into.
 * This file is the *only* coupling between the two plugins — Pro never
 * imports free PHP symbols, it only consumes hooks declared here.
 *
 * If Pro is not installed (or its license check fails), every filter falls
 * back to its safe default and the free plugin keeps running unchanged.
 *
 * When Pro is not active, the free plugin shows a single "Upgrade Now" admin
 * page that lists Pro features and links to https://vmselements.com — there
 * is no license activation UI in the free plugin.
 *
 * @package VMS_Elements_Form_Guard
 */

namespace VMS_Elements_Form_Guard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the default values for every Pro-side hook the free plugin
 * exposes, and fires the `vms_elements_form_guard_loaded` action that the
 * Pro plugin uses to register its own classes.
 */
class Pro_Features {

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
		add_filter( 'vms_elements_form_guard_email_template_html', array( $this, 'default_email_html' ), 5, 3 );
		add_filter( 'vms_elements_form_guard_post_summary_text', array( $this, 'default_post_summary' ), 5, 2 );
		add_filter( 'vms_elements_form_guard_pro_features', array( $this, 'default_pro_features' ), 5 );
	}

	/**
	 * Fire the "loaded" action that Pro hooks into to register its own
	 * classes / shortcodes / menu callbacks.
	 *
	 * Called from the main plugin bootstrap.
	 */
	public function fire_loaded(): void {
		do_action( 'vms_elements_form_guard_loaded', $this );
	}

	/**
	 * Convenience: is the Pro plugin active *and* (if applicable) licensed?
	 *
	 * Pro flips this filter to `true` from its own License_Manager once a
	 * valid license is stored. The free plugin never returns `true` here.
	 */
	public static function is_pro_active(): bool {
		return (bool) apply_filters( 'vms_elements_form_guard_is_pro_active', false );
	}

	/**
	 * Default for `vms_elements_form_guard_email_template_html` — fall back
	 * to the caller's hardcoded HTML when Pro is not active.
	 *
	 * @param string               $fallback_html Hardcoded HTML from the free plugin.
	 * @param string               $template_key  Logical key (e.g. `account_activation`).
	 * @param array<string, mixed> $vars          Variables substituted into the template.
	 */
	public function default_email_html( $fallback_html, $template_key, $vars ): string {
		unset( $template_key, $vars );
		return is_string( $fallback_html ) ? $fallback_html : '';
	}

	/**
	 * Default for `vms_elements_form_guard_post_summary_text` — no summary
	 * text available when Pro AI Summaries is not active.
	 *
	 * @param string $current Existing return value (kept for additive filtering).
	 * @param int    $post_id Post being summarised.
	 */
	public function default_post_summary( $current, $post_id ): string {
		unset( $post_id );
		return is_string( $current ) ? $current : '';
	}

	/**
	 * Default for `vms_elements_form_guard_pro_features` — metadata for the
	 * Upgrade Now page in the free plugin (feature titles and descriptions).
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
				'slug'        => 'vefg-form-guard',
				'title'       => __( 'Form Guard', 'vms-elements-form-guard' ),
				'description' => __( 'Validate any front-end form (Contact Form 7, WPForms, custom) with anti-spam rules, AI moderation, and per-form scope.', 'vms-elements-form-guard' ),
				'position'    => 30,
			),
			array(
				'slug'        => 'vefg-contact-guard',
				'title'       => __( 'Contact Guard', 'vms-elements-form-guard' ),
				'description' => __( 'Block disposable / risky senders on contact forms before they reach your inbox.', 'vms-elements-form-guard' ),
				'position'    => 31,
			),
			array(
				'slug'        => 'vefg-subscribe-guard',
				'title'       => __( 'Subscribe Guard', 'vms-elements-form-guard' ),
				'description' => __( 'Stop fake newsletter sign-ups and disposable-email subscribers at the source.', 'vms-elements-form-guard' ),
				'position'    => 32,
			),
			array(
				'slug'        => 'vefg-product-review-guard',
				'title'       => __( 'Product Review Guard', 'vms-elements-form-guard' ),
				'description' => __( 'Add WooCommerce review moderation with AI scoring and disposable-email blocks.', 'vms-elements-form-guard' ),
				'position'    => 33,
			),
			array(
				'slug'        => 'vefg-ai-summaries',
				'title'       => __( 'AI Post Summaries', 'vms-elements-form-guard' ),
				'description' => __( 'Generate short AI summaries for posts to give Comment Guard more context (and surface them on the front end).', 'vms-elements-form-guard' ),
				'position'    => 34,
			),
			array(
				'slug'        => 'vefg-ai-product-summaries',
				'title'       => __( 'AI Product Summaries', 'vms-elements-form-guard' ),
				'description' => __( 'AI-generated summaries for WooCommerce products.', 'vms-elements-form-guard' ),
				'position'    => 35,
			),
			array(
				'slug'        => 'vefg-email-templates',
				'title'       => __( 'Email Templates', 'vms-elements-form-guard' ),
				'description' => __( 'Visual editor for every transactional email sent by Auth Forms and the Guards.', 'vms-elements-form-guard' ),
				'position'    => 36,
			),
		);
		return array_merge( $features, $defaults );
	}
}
