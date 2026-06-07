<?php
/**
 * AI VMS Elements Form Guard configuration (providers, comment rules, prompts).
 *
 * @package VMS_Elements_Form_Guard
 */

namespace VMS_Elements_Form_Guard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stored in option vefg-ai-span-config.
 */
class AI_Span_Config {

	public const OPTION_KEY = 'vefg-ai-span-config';

	/**
	 * Default configuration.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'ai_enabled'                 => false,
			'provider'                   => 'gemini',
			'openai_api_key'             => '',
			'openai_model'               => 'gpt-4o-mini',
			'anthropic_api_key'          => '',
			'anthropic_model'            => 'claude-3-5-haiku-latest',
			'gemini_api_key'             => '',
			'gemini_model'               => 'gemini-2.0-flash-lite',
			'deepseek_api_key'           => '',
			'deepseek_model'             => 'deepseek-chat',
			'system_prompt'              => '',
			'summary_post_types'         => array( 'post' ),
			// Block User / Strike system settings
			'block_user_enabled'            => true,
			'block_user_max_strikes'        => 5,
			'block_user_login_block'        => true,
			'block_user_strike_expiry_days' => 30,
			'block_user_auto_logout'        => true,
			'block_user_exempt_admins'      => true,
			'comment_max_strikes'           => 5,
			'comment_contact_page_id'       => 0,
			'contact_guard_enabled'         => false,
			'contact_guard_page_id'         => 0,
			'contact_guard_scope'           => 'site',
			'contact_guard_page_ids'        => '',
			'contact_guard_form_selector'   => '',
			'contact_guard_check_dns'       => true,
			'contact_guard_check_mx'        => true,
			'contact_guard_check_disposable' => true,
			'contact_guard_webrisk'         => false,
			'contact_guard_virustotal'      => false,
			'contact_guard_ai_spam'         => false,
			'contact_guard_recaptcha'       => false,
			'subscribe_guard_enabled'       => false,
			'subscribe_guard_scope'         => 'site',
			'subscribe_guard_page_ids'      => '',
			'subscribe_guard_form_selector' => '',
			'subscribe_guard_check_dns'     => true,
			'subscribe_guard_check_mx'      => true,
			'subscribe_guard_check_disposable' => true,
			'subscribe_guard_webrisk'       => false,
			'subscribe_guard_virustotal'    => false,
			'subscribe_guard_recaptcha'     => false,
			// Login Guard
			'login_guard_enabled'           => false,
			'login_guard_recaptcha'         => false,
			'login_guard_scope'             => 'default',
			'login_guard_page_ids'          => '',
			// Registration Guard Frontend
			'registration_guard_frontend'        => false,
			'registration_guard_recaptcha'       => false,
			'registration_guard_scope'           => 'default',
			'registration_guard_page_ids'        => '',
			'comment_site_ban_enabled'      => false,
			'comment_site_ban_strikes'      => 10,
			'comment_allow_links'           => true,
			'product_review_filter'         => false,
			'comment_antispam_enabled'      => true,
			'comment_strike_on_heuristic'   => true,
			'comment_min_length'            => 2,
			'comment_max_length'            => 8000,
			'comment_max_links'             => 4,
			'comment_block_keywords'        => '',
			'comment_block_email_domains'   => '',
			'comment_block_duplicate'       => true,
			'comment_rate_limit_max'        => 10,
			'comment_rate_limit_window'     => 15,
			'comment_rate_limit_scope'      => 'ip',
			'comment_block_bbcode'            => true,
			'comment_block_dangerous_markup'  => true,
			'comment_block_punycode_abuse'    => true,
			'comment_emoji_flood_max'         => 28,
			'comment_block_excessive_repeats' => true,
			'comment_max_caps_ratio'        => 0.82,
			'comment_block_disposable_email' => true,
			'comment_builtin_bad_phrases'   => true,
			'comment_respect_whitelist'     => true,
			'comment_block_trackbacks'      => true,
			'comment_disallow_guest_website' => false,
			'comment_block_http_author_url' => false,
			// WooCommerce Product Review Guard (when enabled, WC reviews skip Comment Guard pipeline).
			'product_review_guard_enabled'   => false,
			'review_mirror_comment_rules'    => true,
			'review_antispam_enabled'        => true,
			'review_strike_on_heuristic'     => true,
			'review_min_length'              => 4,
			'review_max_length'              => 8000,
			'review_max_links'               => 2,
			'review_allow_links'             => false,
			'review_block_duplicate'         => true,
			'review_rate_limit_max'          => 8,
			'review_rate_limit_window'       => 30,
			'review_rate_limit_scope'        => 'ip_post',
			'review_ai_semantic_check'       => true,
			'review_ai_auto_product_summary' => true,
			'review_system_prompt'           => '',
			'review_require_rating'          => true,
			'review_require_verified_purchase' => false,
			'review_one_per_customer'        => true,
			'review_block_guest'             => false,
		);
	}

	/**
	 * @return string
	 */
	public static function default_system_prompt(): string {
		return __(
			'You are a strict comment moderator. Compare the POST_SUMMARY with the COMMENT_TEXT. Decide if the comment is good-faith and on-topic, or spam: promotional/affiliate, SEO or backlink pitches, pharma/gambling/adult promos, unrelated topics, gibberish, foreign-language off-topic ads, contact harvesting (“email me at…”), crypto/loan scams, essay-writing services, or mass emoji/noise. If PRODUCT_REVIEW_MODE is yes, genuine short product reviews are allowed. Respond with ONLY valid JSON (no markdown, no code fences): {"status":"ok"|"spam","message":"If spam, name the spam pattern in English; if ok use a short neutral phrase."}',
			'vms-elements-form-guard'
		);
	}

	/**
	 * AI prompt when Product Review Guard uses semantic checks (override empty review_system_prompt).
	 */
	public static function default_review_system_prompt(): string {
		return __(
			'You are an expert WooCommerce review moderator. Each request includes PRODUCT_SUMMARY (an AI-generated factual overview of the exact product being reviewed—use it as ground truth for what the item is), PRODUCT_CONTEXT (name, SKU, categories), STAR_RATING_SUBMITTED, and REVIEW_TEXT.

Your task: Decide whether REVIEW_TEXT genuinely discusses THIS product—quality, materials, fit/sizing, performance, packaging, shipping speed, seller support, defects, honest comparisons to expectations—or whether it is spam or abuse.

ACCEPT: Thoughtful, brief, or emotional opinions (positive or negative) that clearly relate to PRODUCT_SUMMARY; reasonable complaints; mixed ratings; non-native English if still product-specific.

REJECT as spam: Promotional or affiliate pitches; URLs/link farming or SEO keyword stuffing; obvious copy-paste templates not tied to this product; reviews clearly about a different item; competitor ads; phishing or contact harvesting (“DM me”, “WhatsApp”, bulk emails); scams (crypto, loans, jobs); pharma/gambling/adult promotions; unrelated languages or topics; meaningless gibberish; emoji/noise floods; fake testimonial patterns (“life-changing miracle”) with zero product-specific detail.

Always compare REVIEW_TEXT against PRODUCT_SUMMARY—reject generic praise or rage that could apply to any product.

Respond ONLY with valid JSON (no markdown, no code fences): {"status":"ok"|"spam","message":"If spam, name the pattern briefly in English; if ok use a short neutral phrase."}',
			'vms-elements-form-guard'
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get(): array {
		$raw = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $raw ) ) {
			$raw = array();
		}
		$merged = array_merge( self::defaults(), $raw );
		return self::apply_translated_prompt_defaults( $merged );
	}

	/**
	 * Fill empty AI prompts with translated defaults (only after `init`).
	 *
	 * @param array<string, mixed> $config Merged config.
	 * @return array<string, mixed>
	 */
	private static function apply_translated_prompt_defaults( array $config ): array {
		if ( '' === trim( (string) ( $config['system_prompt'] ?? '' ) ) ) {
			$config['system_prompt'] = self::default_system_prompt();
		}
		if ( '' === trim( (string) ( $config['review_system_prompt'] ?? '' ) ) ) {
			$config['review_system_prompt'] = self::default_review_system_prompt();
		}
		return $config;
	}

	/**
	 * @param array<string, mixed> $data Merged with defaults then saved.
	 */
	public static function update( array $data ): void {
		$merged = array_merge( self::defaults(), self::get(), $data );
		$merged = self::sanitize_for_save( $merged );
		update_option( self::OPTION_KEY, $merged, false );
	}

	/**
	 * @param array<string, mixed> $c Config.
	 * @return array<string, mixed>
	 */
	public static function sanitize_for_save( array $c ): array {
		$d = self::defaults();

		$c['ai_enabled']            = ! empty( $c['ai_enabled'] );
		$c['provider']              = in_array( $c['provider'] ?? '', array( 'openai', 'anthropic', 'gemini', 'deepseek' ), true )
			? $c['provider']
			: 'gemini';
		$c['openai_api_key']        = sanitize_text_field( (string) ( $c['openai_api_key'] ?? '' ) );
		$c['openai_model']          = sanitize_text_field( (string) ( $c['openai_model'] ?? $d['openai_model'] ) );
		$c['anthropic_api_key']     = sanitize_text_field( (string) ( $c['anthropic_api_key'] ?? '' ) );
		$c['anthropic_model']       = sanitize_text_field( (string) ( $c['anthropic_model'] ?? $d['anthropic_model'] ) );
		$c['gemini_api_key']        = sanitize_text_field( (string) ( $c['gemini_api_key'] ?? '' ) );
		$c['gemini_model']          = sanitize_text_field( (string) ( $c['gemini_model'] ?? $d['gemini_model'] ) );
		$c['deepseek_api_key']      = sanitize_text_field( (string) ( $c['deepseek_api_key'] ?? '' ) );
		$c['deepseek_model']        = sanitize_text_field( (string) ( $c['deepseek_model'] ?? $d['deepseek_model'] ) );
		$c['system_prompt']         = wp_kses_post( (string) ( $c['system_prompt'] ?? $d['system_prompt'] ) );

		// Block User / Strike system
		$c['block_user_enabled']            = ! empty( $c['block_user_enabled'] );
		$c['block_user_max_strikes']        = max( 1, min( 100, absint( $c['block_user_max_strikes'] ?? 5 ) ) );
		$c['block_user_login_block']        = ! empty( $c['block_user_login_block'] );
		$c['block_user_strike_expiry_days'] = max( 0, min( 365, absint( $c['block_user_strike_expiry_days'] ?? 30 ) ) );
		$c['block_user_auto_logout']        = ! empty( $c['block_user_auto_logout'] );
		$c['block_user_exempt_admins']      = ! empty( $c['block_user_exempt_admins'] );

		$c['comment_max_strikes']   = max( 1, min( 100, absint( $c['comment_max_strikes'] ?? 5 ) ) );
		$c['comment_contact_page_id'] = absint( $c['comment_contact_page_id'] ?? 0 );

		// Contact Guard settings
		$c['contact_guard_enabled'] = ! empty( $c['contact_guard_enabled'] );
		$c['contact_guard_page_id'] = absint( $c['contact_guard_page_id'] ?? 0 );
		$cg_scope = (string) ( $c['contact_guard_scope'] ?? 'site' );
		$c['contact_guard_scope'] = in_array( $cg_scope, array( 'site', 'specific' ), true ) ? $cg_scope : 'site';
		$c['contact_guard_page_ids'] = sanitize_text_field( (string) ( $c['contact_guard_page_ids'] ?? '' ) );
		$c['contact_guard_form_selector'] = sanitize_text_field( (string) ( $c['contact_guard_form_selector'] ?? '' ) );
		$c['contact_guard_check_dns'] = isset( $c['contact_guard_check_dns'] ) ? ! empty( $c['contact_guard_check_dns'] ) : true;
		$c['contact_guard_check_mx'] = isset( $c['contact_guard_check_mx'] ) ? ! empty( $c['contact_guard_check_mx'] ) : true;
		$c['contact_guard_check_disposable'] = isset( $c['contact_guard_check_disposable'] ) ? ! empty( $c['contact_guard_check_disposable'] ) : true;
		$c['contact_guard_webrisk'] = ! empty( $c['contact_guard_webrisk'] );
		$c['contact_guard_virustotal'] = ! empty( $c['contact_guard_virustotal'] );
		$c['contact_guard_ai_spam'] = ! empty( $c['contact_guard_ai_spam'] );
		$c['contact_guard_recaptcha'] = ! empty( $c['contact_guard_recaptcha'] );
		$c['subscribe_guard_enabled'] = ! empty( $c['subscribe_guard_enabled'] );
		$scope = (string) ( $c['subscribe_guard_scope'] ?? 'site' );
		$c['subscribe_guard_scope'] = in_array( $scope, array( 'site', 'specific' ), true ) ? $scope : 'site';
		$c['subscribe_guard_page_ids'] = sanitize_text_field( (string) ( $c['subscribe_guard_page_ids'] ?? '' ) );
		$c['subscribe_guard_form_selector'] = sanitize_text_field( (string) ( $c['subscribe_guard_form_selector'] ?? '' ) );
		$c['subscribe_guard_check_dns'] = isset( $c['subscribe_guard_check_dns'] ) ? ! empty( $c['subscribe_guard_check_dns'] ) : true;
		$c['subscribe_guard_check_mx'] = isset( $c['subscribe_guard_check_mx'] ) ? ! empty( $c['subscribe_guard_check_mx'] ) : true;
		$c['subscribe_guard_check_disposable'] = isset( $c['subscribe_guard_check_disposable'] ) ? ! empty( $c['subscribe_guard_check_disposable'] ) : true;
		$c['subscribe_guard_webrisk'] = ! empty( $c['subscribe_guard_webrisk'] );
		$c['subscribe_guard_virustotal'] = ! empty( $c['subscribe_guard_virustotal'] );
		$c['subscribe_guard_recaptcha'] = ! empty( $c['subscribe_guard_recaptcha'] );
		// Login Guard
		$c['login_guard_enabled'] = ! empty( $c['login_guard_enabled'] );
		$c['login_guard_recaptcha'] = ! empty( $c['login_guard_recaptcha'] );
		$lg_scope = (string) ( $c['login_guard_scope'] ?? 'default' );
		$c['login_guard_scope'] = in_array( $lg_scope, array( 'default', 'specific' ), true ) ? $lg_scope : 'default';
		$c['login_guard_page_ids'] = sanitize_text_field( (string) ( $c['login_guard_page_ids'] ?? '' ) );
		// Registration Guard Frontend
		$c['registration_guard_frontend'] = ! empty( $c['registration_guard_frontend'] );
		$c['registration_guard_recaptcha'] = ! empty( $c['registration_guard_recaptcha'] );
		$rg_scope = (string) ( $c['registration_guard_scope'] ?? 'default' );
		$c['registration_guard_scope'] = in_array( $rg_scope, array( 'default', 'specific' ), true ) ? $rg_scope : 'default';
		$c['registration_guard_page_ids'] = sanitize_text_field( (string) ( $c['registration_guard_page_ids'] ?? '' ) );
		$c['comment_site_ban_enabled'] = ! empty( $c['comment_site_ban_enabled'] );
		$ban_at                      = max( 2, min( 500, absint( $c['comment_site_ban_strikes'] ?? 10 ) ) );
		$c['comment_site_ban_strikes'] = max( $ban_at, $c['comment_max_strikes'] );
		$c['comment_allow_links']   = ! empty( $c['comment_allow_links'] );
		$c['product_review_filter'] = ! empty( $c['product_review_filter'] );

		$c['comment_antispam_enabled']    = ! empty( $c['comment_antispam_enabled'] );
		$c['comment_strike_on_heuristic'] = ! empty( $c['comment_strike_on_heuristic'] );
		$c['comment_min_length']          = max( 0, min( 500, absint( $c['comment_min_length'] ?? 2 ) ) );
		$c['comment_max_length']          = max( 0, min( 65535, absint( $c['comment_max_length'] ?? 8000 ) ) );
		$c['comment_max_links']           = max( 0, min( 100, absint( $c['comment_max_links'] ?? 4 ) ) );
		$c['comment_block_keywords']      = sanitize_textarea_field( (string) ( $c['comment_block_keywords'] ?? '' ) );
		$c['comment_block_email_domains'] = sanitize_textarea_field( (string) ( $c['comment_block_email_domains'] ?? '' ) );
		$c['comment_block_duplicate']     = ! empty( $c['comment_block_duplicate'] );
		$c['comment_rate_limit_max']      = max( 0, min( 500, absint( $c['comment_rate_limit_max'] ?? 10 ) ) );
		$c['comment_rate_limit_window']   = max( 1, min( 1440, absint( $c['comment_rate_limit_window'] ?? 15 ) ) );
		$scope                            = (string) ( $c['comment_rate_limit_scope'] ?? 'ip' );
		$c['comment_rate_limit_scope']    = in_array( $scope, array( 'ip', 'ip_post', 'ip_product' ), true ) ? $scope : 'ip';
		$c['comment_block_bbcode']             = ! empty( $c['comment_block_bbcode'] );
		$c['comment_block_dangerous_markup']   = ! empty( $c['comment_block_dangerous_markup'] );
		$c['comment_block_punycode_abuse']     = ! empty( $c['comment_block_punycode_abuse'] );
		$c['comment_emoji_flood_max']          = max( 0, min( 500, absint( $c['comment_emoji_flood_max'] ?? 28 ) ) );
		$c['comment_block_excessive_repeats'] = ! empty( $c['comment_block_excessive_repeats'] );
		$ratio = isset( $c['comment_max_caps_ratio'] ) ? (float) $c['comment_max_caps_ratio'] : (float) $d['comment_max_caps_ratio'];
		if ( $ratio <= 0 || $ratio >= 1 ) {
			$c['comment_max_caps_ratio'] = 0;
		} else {
			$c['comment_max_caps_ratio'] = round( min( 0.99, max( 0.5, $ratio ) ), 4 );
		}
		$c['comment_block_disposable_email'] = ! empty( $c['comment_block_disposable_email'] );
		$c['comment_builtin_bad_phrases'] = ! empty( $c['comment_builtin_bad_phrases'] );
		$c['comment_respect_whitelist']   = ! empty( $c['comment_respect_whitelist'] );
		$c['comment_block_trackbacks']    = ! empty( $c['comment_block_trackbacks'] );
		$c['comment_disallow_guest_website'] = ! empty( $c['comment_disallow_guest_website'] );
		$c['comment_block_http_author_url']  = ! empty( $c['comment_block_http_author_url'] );

		$c['product_review_guard_enabled'] = ! empty( $c['product_review_guard_enabled'] );
		$c['review_mirror_comment_rules']   = ! empty( $c['review_mirror_comment_rules'] );
		$c['review_antispam_enabled']       = ! empty( $c['review_antispam_enabled'] );
		$c['review_strike_on_heuristic']    = ! empty( $c['review_strike_on_heuristic'] );
		$c['review_min_length']             = max( 0, min( 500, absint( $c['review_min_length'] ?? 4 ) ) );
		$c['review_max_length']             = max( 0, min( 65535, absint( $c['review_max_length'] ?? 8000 ) ) );
		$c['review_max_links']              = max( 0, min( 100, absint( $c['review_max_links'] ?? 2 ) ) );
		$c['review_allow_links']            = ! empty( $c['review_allow_links'] );
		$c['review_block_duplicate']        = ! empty( $c['review_block_duplicate'] );
		$c['review_rate_limit_max']         = max( 0, min( 500, absint( $c['review_rate_limit_max'] ?? 8 ) ) );
		$c['review_rate_limit_window']      = max( 1, min( 1440, absint( $c['review_rate_limit_window'] ?? 30 ) ) );
		$review_rl_scope                   = (string) ( $c['review_rate_limit_scope'] ?? 'ip_post' );
		$c['review_rate_limit_scope']       = in_array( $review_rl_scope, array( 'ip', 'ip_post', 'ip_product' ), true ) ? $review_rl_scope : 'ip_post';
		$c['review_ai_semantic_check']       = ! empty( $c['review_ai_semantic_check'] );
		$c['review_ai_auto_product_summary'] = ! empty( $c['review_ai_auto_product_summary'] );
		$c['review_system_prompt']          = wp_kses_post( (string) ( $c['review_system_prompt'] ?? '' ) );
		$c['review_require_rating']         = ! empty( $c['review_require_rating'] );
		$c['review_require_verified_purchase'] = ! empty( $c['review_require_verified_purchase'] );
		$c['review_one_per_customer']       = ! empty( $c['review_one_per_customer'] );
		$c['review_block_guest']            = ! empty( $c['review_block_guest'] );

		$pts = isset( $c['summary_post_types'] ) ? $c['summary_post_types'] : array();
		if ( ! is_array( $pts ) ) {
			$pts = array( 'post' );
		}
		$clean_pts = array();
		foreach ( $pts as $pt ) {
			$pt = sanitize_key( (string) $pt );
			if ( $pt !== '' ) {
				$clean_pts[] = $pt;
			}
		}
		$c['summary_post_types'] = array_values( array_unique( $clean_pts ) );
		if ( array() === $c['summary_post_types'] ) {
			$c['summary_post_types'] = array( 'post' );
		}

		return array_merge( $d, $c );
	}
}
