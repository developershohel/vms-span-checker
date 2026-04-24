<?php
/**
 * AI Span Checker configuration (providers, comment rules, prompts).
 *
 * @package WP_Span_Checker
 */

namespace WP_Span_Checker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stored in option wsc-ai-span-config.
 */
class AI_Span_Config {

	public const OPTION_KEY = 'wsc-ai-span-config';

	/**
	 * Default configuration.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'ai_enabled'                 => false,
			'provider'                   => 'openai',
			'openai_api_key'             => '',
			'openai_model'               => 'gpt-4o-mini',
			'anthropic_api_key'          => '',
			'anthropic_model'            => 'claude-3-5-haiku-20241022',
			'gemini_api_key'             => '',
			'gemini_model'               => 'gemini-1.5-flash',
			'deepseek_api_key'           => '',
			'deepseek_model'             => 'deepseek-chat',
			'bedrock_access_key'         => '',
			'bedrock_secret_key'         => '',
			'bedrock_region'             => 'us-east-1',
			'bedrock_model'              => 'anthropic.claude-3-haiku-20240307-v1:0',
			'system_prompt'              => self::default_system_prompt(),
			'summary_post_types'         => array( 'post' ),
			'comment_max_strikes'           => 5,
			'comment_contact_page_id'       => 0,
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
		);
	}

	/**
	 * @return string
	 */
	public static function default_system_prompt(): string {
		return __(
			'You are a strict comment moderator. Compare the POST_SUMMARY with the COMMENT_TEXT. Decide if the comment is good-faith and on-topic, or spam: promotional/affiliate, SEO or backlink pitches, pharma/gambling/adult promos, unrelated topics, gibberish, foreign-language off-topic ads, contact harvesting (“email me at…”), crypto/loan scams, essay-writing services, or mass emoji/noise. If PRODUCT_REVIEW_MODE is yes, genuine short product reviews are allowed. Respond with ONLY valid JSON (no markdown, no code fences): {"status":"ok"|"spam","message":"If spam, name the spam pattern in English; if ok use a short neutral phrase."}',
			'wp-span-checker'
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
		return array_merge( self::defaults(), $raw );
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
		$c['provider']              = in_array( $c['provider'] ?? '', array( 'openai', 'anthropic', 'gemini', 'deepseek', 'bedrock' ), true )
			? $c['provider']
			: 'openai';
		$c['openai_api_key']        = sanitize_text_field( (string) ( $c['openai_api_key'] ?? '' ) );
		$c['openai_model']          = sanitize_text_field( (string) ( $c['openai_model'] ?? $d['openai_model'] ) );
		$c['anthropic_api_key']     = sanitize_text_field( (string) ( $c['anthropic_api_key'] ?? '' ) );
		$c['anthropic_model']       = sanitize_text_field( (string) ( $c['anthropic_model'] ?? $d['anthropic_model'] ) );
		$c['gemini_api_key']        = sanitize_text_field( (string) ( $c['gemini_api_key'] ?? '' ) );
		$c['gemini_model']          = sanitize_text_field( (string) ( $c['gemini_model'] ?? $d['gemini_model'] ) );
		$c['deepseek_api_key']      = sanitize_text_field( (string) ( $c['deepseek_api_key'] ?? '' ) );
		$c['deepseek_model']        = sanitize_text_field( (string) ( $c['deepseek_model'] ?? $d['deepseek_model'] ) );
		$c['bedrock_access_key']    = sanitize_text_field( (string) ( $c['bedrock_access_key'] ?? '' ) );
		$c['bedrock_secret_key']    = sanitize_text_field( (string) ( $c['bedrock_secret_key'] ?? '' ) );
		$c['bedrock_region']        = preg_replace( '/[^a-z0-9\-]/', '', strtolower( (string) ( $c['bedrock_region'] ?? 'us-east-1' ) ) );
		$c['bedrock_model']         = sanitize_text_field( (string) ( $c['bedrock_model'] ?? $d['bedrock_model'] ) );
		$c['system_prompt']         = wp_kses_post( (string) ( $c['system_prompt'] ?? $d['system_prompt'] ) );
		$c['comment_max_strikes']   = max( 1, min( 100, absint( $c['comment_max_strikes'] ?? 5 ) ) );
		$c['comment_contact_page_id'] = absint( $c['comment_contact_page_id'] ?? 0 );
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
		$c['comment_rate_limit_scope']    = in_array( $scope, array( 'ip', 'ip_post' ), true ) ? $scope : 'ip';
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
