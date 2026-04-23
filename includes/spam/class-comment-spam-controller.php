<?php
/**
 * Orchestrates ordered spam-check components (extend via wsc_spam_check_components).
 *
 * @package WP_Span_Checker
 */

namespace WP_Span_Checker\Spam;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Runs the heuristic pipeline; AI is handled separately in AI_Span_Comments.
 */
final class Comment_Spam_Controller {

	/**
	 * @param array<string, mixed> $commentdata .
	 * @param array<string, mixed> $config      .
	 * @return true|WP_Error
	 */
	public static function run( array $commentdata, array $config ) {
		$post_id = isset( $commentdata['comment_post_ID'] ) ? (int) $commentdata['comment_post_ID'] : 0;
		if ( $post_id <= 0 ) {
			return true;
		}

		foreach ( self::get_components() as $component ) {
			if ( ! $component instanceof Spam_Check_Component ) {
				continue;
			}
			$result = $component->evaluate( $commentdata, $config );
			if ( \is_wp_error( $result ) ) {
				return $result;
			}
		}

		return true;
	}

	/**
	 * Default stack order is intentional (flood first, cheap checks before DB).
	 *
	 * @return array<int, Spam_Check_Component>
	 */
	public static function get_components(): array {
		$list = array(
			new Trackback_Spam_Component(),
			new Rate_Limit_Spam_Component(),
			new Duplicate_Spam_Component(),
			new Length_Spam_Component(),
			new Disposable_Email_Spam_Component(),
			new Email_Domain_Blocklist_Spam_Component(),
			new Punycode_Spam_Component(),
			new Keyword_Spam_Component(),
			new Builtin_Phrases_Spam_Component(),
			new Guest_Website_Spam_Component(),
			new Author_Url_Http_Spam_Component(),
			new Bbcode_Spam_Component(),
			new Dangerous_Markup_Spam_Component(),
			new Caps_Ratio_Spam_Component(),
			new Repeated_Chars_Spam_Component(),
			new Emoji_Flood_Spam_Component(),
			new Link_Policy_Spam_Component(),
		);

		/**
		 * Filter the ordered list of heuristic spam components.
		 *
		 * @param array<int, Spam_Check_Component> $list Default pipeline.
		 */
		$filtered = apply_filters( 'wsc_spam_check_components', $list );
		return is_array( $filtered ) ? $filtered : $list;
	}
}
