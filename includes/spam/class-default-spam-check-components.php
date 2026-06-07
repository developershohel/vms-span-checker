<?php
/**
 * Built-in Spam_Check_Component implementations (no AI required).
 *
 * The lookup uses the core comments table via `$wpdb->comments` (a core
 * identifier) with all dynamic values prepared via `$wpdb->prepare()`.
 *
 * This file is a deliberate "components bundle" — each class is a tiny
 * Spam_Check_Component implementation registered together by Default_Spam_Checks::register().
 * Splitting them into 16 separate files would only add boilerplate without
 * any maintenance benefit, so we disable the one-class-per-file rule here.
 *
 * @package VMS_Elements_Form_Guard
 *
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 * phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound
 */

namespace VMS_Elements_Form_Guard\Spam;

use WP_Error;
use VMS_Elements_Form_Guard\Disposable;
use VMS_Elements_Form_Guard\Whitelist;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Trackback_Spam_Component implements Spam_Check_Component {
	public function get_id(): string {
		return 'trackback';
	}
	public function get_label(): string {
		return __( 'Block trackbacks & pingbacks', 'vms-elements-form-guard' );
	}
	public function evaluate( array $commentdata, array $config ) {
		if ( empty( $config['comment_block_trackbacks'] ) ) {
			return true;
		}
		$type = isset( $commentdata['comment_type'] ) ? (string) $commentdata['comment_type'] : 'comment';
		if ( in_array( $type, array( 'trackback', 'pingback' ), true ) ) {
			return new WP_Error(
				'vefg_spam_trackback',
				__( 'Trackbacks and pingbacks are disabled.', 'vms-elements-form-guard' )
			);
		}
		return true;
	}
}

final class Rate_Limit_Spam_Component implements Spam_Check_Component {
	public function get_id(): string {
		return 'rate_limit';
	}
	public function get_label(): string {
		return __( 'IP flood limit', 'vms-elements-form-guard' );
	}
	public function evaluate( array $commentdata, array $config ) {
		$post_id = isset( $commentdata['comment_post_ID'] ) ? (int) $commentdata['comment_post_ID'] : 0;
		$max     = (int) ( $config['comment_rate_limit_max'] ?? 0 );
		$ip      = Spam_Check_Helpers::visitor_ip();
		if ( $max <= 0 || $ip === '' ) {
			return true;
		}
		$window = max( 1, (int) ( $config['comment_rate_limit_window'] ?? 15 ) );
		$scope  = (string) ( $config['comment_rate_limit_scope'] ?? 'ip' );
		$key    = 'vefg_crl_' . md5( $ip );
		if ( 'ip_post' === $scope || 'ip_product' === $scope ) {
			$key = 'vefg_crl_' . md5( $ip . '|' . $post_id );
		}
		$n = (int) get_transient( $key );
		if ( $n >= $max ) {
			return new WP_Error(
				'vefg_spam_ratelimit',
				__( 'You are submitting comments too quickly. Please wait and try again.', 'vms-elements-form-guard' )
			);
		}
		set_transient( $key, $n + 1, $window * MINUTE_IN_SECONDS );
		return true;
	}
}

final class Duplicate_Spam_Component implements Spam_Check_Component {
	public function get_id(): string {
		return 'duplicate';
	}
	public function get_label(): string {
		return __( 'Duplicate comment body', 'vms-elements-form-guard' );
	}
	public function evaluate( array $commentdata, array $config ) {
		if ( empty( $config['comment_block_duplicate'] ) ) {
			return true;
		}
		$post_id = isset( $commentdata['comment_post_ID'] ) ? (int) $commentdata['comment_post_ID'] : 0;
		$content = isset( $commentdata['comment_content'] ) ? (string) $commentdata['comment_content'] : '';
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Core comments table.
		$found = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT comment_ID FROM {$wpdb->comments} WHERE comment_post_ID = %d AND comment_content = %s AND comment_approved != 'spam' AND comment_approved != 'trash' LIMIT 1",
				$post_id,
				$content
			)
		);
		if ( $found ) {
			return new WP_Error(
				'vefg_spam_duplicate',
				__( 'This comment was already submitted on this post.', 'vms-elements-form-guard' )
			);
		}
		return true;
	}
}

final class Length_Spam_Component implements Spam_Check_Component {
	public function get_id(): string {
		return 'length';
	}
	public function get_label(): string {
		return __( 'Min / max length', 'vms-elements-form-guard' );
	}
	public function evaluate( array $commentdata, array $config ) {
		$content = isset( $commentdata['comment_content'] ) ? (string) $commentdata['comment_content'] : '';
		$min     = (int) ( $config['comment_min_length'] ?? 0 );
		$max     = (int) ( $config['comment_max_length'] ?? 0 );
		$len     = Spam_Check_Helpers::str_len( $content );
		if ( $min > 0 && $len < $min ) {
			return new WP_Error( 'vefg_spam_short', __( 'Comment is too short.', 'vms-elements-form-guard' ) );
		}
		if ( $max > 0 && $len > $max ) {
			return new WP_Error( 'vefg_spam_long', __( 'Comment is too long.', 'vms-elements-form-guard' ) );
		}
		return true;
	}
}

final class Disposable_Email_Spam_Component implements Spam_Check_Component {
	public function get_id(): string {
		return 'disposable_email';
	}
	public function get_label(): string {
		return __( 'Disposable email list', 'vms-elements-form-guard' );
	}
	public function evaluate( array $commentdata, array $config ) {
		if ( empty( $config['comment_block_disposable_email'] ) ) {
			return true;
		}
		$email  = isset( $commentdata['comment_author_email'] ) ? (string) $commentdata['comment_author_email'] : '';
		$domain = Spam_Check_Helpers::email_domain( $email );
		if ( $domain === '' ) {
			return true;
		}
		if ( ! empty( $config['comment_respect_whitelist'] ) ) {
			$wl = new Whitelist();
			if ( $wl->domain_on_list( $domain ) ) {
				return true;
			}
		}
		$d = new Disposable();
		if ( $d->email_domain_is_disposable( $domain ) ) {
			return new WP_Error(
				'vefg_spam_disposable',
				__( 'Disposable or throwaway email addresses are not allowed.', 'vms-elements-form-guard' )
			);
		}
		return true;
	}
}

final class Email_Domain_Blocklist_Spam_Component implements Spam_Check_Component {
	public function get_id(): string {
		return 'email_domain_blocklist';
	}
	public function get_label(): string {
		return __( 'Blocked email domains', 'vms-elements-form-guard' );
	}
	public function evaluate( array $commentdata, array $config ) {
		$raw   = isset( $config['comment_block_email_domains'] ) ? (string) $config['comment_block_email_domains'] : '';
		$lines = Spam_Check_Helpers::lines_to_array( $raw );
		if ( empty( $lines ) ) {
			return true;
		}
		$email  = isset( $commentdata['comment_author_email'] ) ? (string) $commentdata['comment_author_email'] : '';
		$domain = strtolower( Spam_Check_Helpers::email_domain( $email ) );
		if ( $domain === '' ) {
			return true;
		}
		foreach ( $lines as $blocked ) {
			$blen      = strlen( $blocked );
			$is_suffix = ( $blen > 0 && strlen( $domain ) > $blen && substr( $domain, -$blen - 1 ) === '.' . $blocked );
			if ( $domain === $blocked || $is_suffix ) {
				return new WP_Error(
					'vefg_spam_email_domain',
					__( 'This email domain is not allowed.', 'vms-elements-form-guard' )
				);
			}
		}
		return true;
	}
}

final class Punycode_Spam_Component implements Spam_Check_Component {
	public function get_id(): string {
		return 'punycode';
	}
	public function get_label(): string {
		return __( 'Punycode / IDN abuse', 'vms-elements-form-guard' );
	}
	public function evaluate( array $commentdata, array $config ) {
		if ( empty( $config['comment_block_punycode_abuse'] ) ) {
			return true;
		}
		$email  = isset( $commentdata['comment_author_email'] ) ? (string) $commentdata['comment_author_email'] : '';
		$domain = Spam_Check_Helpers::email_domain( $email );
		if ( $domain !== '' && strpos( $domain, 'xn--' ) !== false ) {
			return new WP_Error(
				'vefg_spam_punycode',
				__( 'Internationalized (punycode) email domains are not allowed here.', 'vms-elements-form-guard' )
			);
		}
		$url = isset( $commentdata['comment_author_url'] ) ? strtolower( (string) $commentdata['comment_author_url'] ) : '';
		if ( strpos( $url, 'xn--' ) !== false ) {
			return new WP_Error(
				'vefg_spam_punycode',
				__( 'Internationalized (punycode) URLs are not allowed in the website field.', 'vms-elements-form-guard' )
			);
		}
		$content = isset( $commentdata['comment_content'] ) ? (string) $commentdata['comment_content'] : '';
		if ( strpos( strtolower( $content ), 'xn--' ) !== false ) {
			return new WP_Error(
				'vefg_spam_punycode',
				__( 'Comments cannot contain punycode / IDN homograph URLs.', 'vms-elements-form-guard' )
			);
		}
		return true;
	}
}

final class Keyword_Spam_Component implements Spam_Check_Component {
	public function get_id(): string {
		return 'keywords';
	}
	public function get_label(): string {
		return __( 'Custom blocked phrases', 'vms-elements-form-guard' );
	}
	public function evaluate( array $commentdata, array $config ) {
		$raw   = isset( $config['comment_block_keywords'] ) ? (string) $config['comment_block_keywords'] : '';
		$lines = Spam_Check_Helpers::lines_to_array( $raw );
		if ( empty( $lines ) ) {
			return true;
		}
		$content = isset( $commentdata['comment_content'] ) ? (string) $commentdata['comment_content'] : '';
		$author  = isset( $commentdata['comment_author'] ) ? (string) $commentdata['comment_author'] : '';
		$lower   = strtolower( $content . "\n" . $author );
		foreach ( $lines as $word ) {
			if ( $word !== '' && strpos( $lower, $word ) !== false ) {
				return new WP_Error(
					'vefg_spam_keyword',
					__( 'Comment blocked: matched blocked phrase or pattern.', 'vms-elements-form-guard' )
				);
			}
		}
		return true;
	}
}

final class Builtin_Phrases_Spam_Component implements Spam_Check_Component {
	public function get_id(): string {
		return 'builtin_phrases';
	}
	public function get_label(): string {
		return __( 'Bundled spam phrase list', 'vms-elements-form-guard' );
	}
	public function evaluate( array $commentdata, array $config ) {
		if ( empty( $config['comment_builtin_bad_phrases'] ) ) {
			return true;
		}
		$file = VMS_ELEMENTS_FORM_GUARD_DIR . 'includes/data/comment-spam-phrases.php';
		if ( ! is_readable( $file ) ) {
			return true;
		}
		$phrases = include $file;
		if ( ! is_array( $phrases ) ) {
			return true;
		}
		$content = isset( $commentdata['comment_content'] ) ? (string) $commentdata['comment_content'] : '';
		$author  = isset( $commentdata['comment_author'] ) ? (string) $commentdata['comment_author'] : '';
		$lower   = strtolower( $content . "\n" . $author );
		foreach ( $phrases as $p ) {
			$p = strtolower( (string) $p );
			if ( $p !== '' && strpos( $lower, $p ) !== false ) {
				return new WP_Error(
					'vefg_spam_catalog',
					__( 'Comment blocked: promotional or spam-like content.', 'vms-elements-form-guard' )
				);
			}
		}
		return true;
	}
}

final class Guest_Website_Spam_Component implements Spam_Check_Component {
	public function get_id(): string {
		return 'guest_website';
	}
	public function get_label(): string {
		return __( 'Guest website field', 'vms-elements-form-guard' );
	}
	public function evaluate( array $commentdata, array $config ) {
		if ( empty( $config['comment_disallow_guest_website'] ) ) {
			return true;
		}
		$uid = isset( $commentdata['user_id'] ) ? (int) $commentdata['user_id'] : 0;
		if ( $uid > 0 ) {
			return true;
		}
		$url = isset( $commentdata['comment_author_url'] ) ? trim( (string) $commentdata['comment_author_url'] ) : '';
		if ( $url !== '' ) {
			return new WP_Error(
				'vefg_spam_guest_url',
				__( 'Please leave the website field empty when commenting as a guest.', 'vms-elements-form-guard' )
			);
		}
		return true;
	}
}

final class Author_Url_Http_Spam_Component implements Spam_Check_Component {
	public function get_id(): string {
		return 'author_url_http';
	}
	public function get_label(): string {
		return __( 'HTTP(S) in website field', 'vms-elements-form-guard' );
	}
	public function evaluate( array $commentdata, array $config ) {
		if ( empty( $config['comment_block_http_author_url'] ) ) {
			return true;
		}
		$url = isset( $commentdata['comment_author_url'] ) ? trim( (string) $commentdata['comment_author_url'] ) : '';
		if ( $url === '' ) {
			return true;
		}
		if ( preg_match( '#https?://#i', $url ) ) {
			return new WP_Error(
				'vefg_spam_author_url',
				__( 'Please remove the http(s) link from the website field.', 'vms-elements-form-guard' )
			);
		}
		return true;
	}
}

final class Bbcode_Spam_Component implements Spam_Check_Component {
	public function get_id(): string {
		return 'bbcode';
	}
	public function get_label(): string {
		return __( 'BBCode links', 'vms-elements-form-guard' );
	}
	public function evaluate( array $commentdata, array $config ) {
		if ( empty( $config['comment_block_bbcode'] ) ) {
			return true;
		}
		$content = isset( $commentdata['comment_content'] ) ? (string) $commentdata['comment_content'] : '';
		if ( preg_match( '/\[(url|link|img)\s*=/i', $content ) ) {
			return new WP_Error(
				'vefg_spam_bbcode',
				__( 'BBCode-style links are not allowed in comments.', 'vms-elements-form-guard' )
			);
		}
		return true;
	}
}

final class Dangerous_Markup_Spam_Component implements Spam_Check_Component {
	public function get_id(): string {
		return 'dangerous_markup';
	}
	public function get_label(): string {
		return __( 'Script / iframe injection', 'vms-elements-form-guard' );
	}
	public function evaluate( array $commentdata, array $config ) {
		if ( empty( $config['comment_block_dangerous_markup'] ) ) {
			return true;
		}
		$content = isset( $commentdata['comment_content'] ) ? (string) $commentdata['comment_content'] : '';
		$lower   = strtolower( $content );
		if ( strpos( $lower, '<script' ) !== false
			|| strpos( $lower, 'javascript:' ) !== false
			|| strpos( $lower, 'data:text/html' ) !== false
			|| preg_match( '/<iframe[\s>]/i', $content ) ) {
			return new WP_Error(
				'vefg_spam_markup',
				__( 'Comments cannot contain scripts, iframes, or javascript URLs.', 'vms-elements-form-guard' )
			);
		}
		return true;
	}
}

final class Caps_Ratio_Spam_Component implements Spam_Check_Component {
	public function get_id(): string {
		return 'caps_ratio';
	}
	public function get_label(): string {
		return __( 'ALL CAPS ratio', 'vms-elements-form-guard' );
	}
	public function evaluate( array $commentdata, array $config ) {
		$ratio = (float) ( $config['comment_max_caps_ratio'] ?? 0 );
		if ( $ratio <= 0 || $ratio >= 1 ) {
			return true;
		}
		$content = isset( $commentdata['comment_content'] ) ? (string) $commentdata['comment_content'] : '';
		$letters = 0;
		$upper   = 0;
		if ( preg_match_all( '/\p{L}/u', $content, $lm ) ) {
			$letters = count( $lm[0] );
		}
		if ( preg_match_all( '/\p{Lu}/u', $content, $um ) ) {
			$upper = count( $um[0] );
		}
		if ( $letters < 25 ) {
			return true;
		}
		if ( ( $upper / $letters ) > $ratio ) {
			return new WP_Error(
				'vefg_spam_caps',
				__( 'Too many capital letters — looks like shout/spam.', 'vms-elements-form-guard' )
			);
		}
		return true;
	}
}

final class Repeated_Chars_Spam_Component implements Spam_Check_Component {
	public function get_id(): string {
		return 'repeated_chars';
	}
	public function get_label(): string {
		return __( 'Repeated character spam', 'vms-elements-form-guard' );
	}
	public function evaluate( array $commentdata, array $config ) {
		if ( empty( $config['comment_block_excessive_repeats'] ) ) {
			return true;
		}
		$content = isset( $commentdata['comment_content'] ) ? (string) $commentdata['comment_content'] : '';
		if ( preg_match( '/(.)\1{14,}/us', $content ) ) {
			return new WP_Error(
				'vefg_spam_repeat',
				__( 'Comment contains excessive repeated characters.', 'vms-elements-form-guard' )
			);
		}
		return true;
	}
}

final class Emoji_Flood_Spam_Component implements Spam_Check_Component {
	public function get_id(): string {
		return 'emoji_flood';
	}
	public function get_label(): string {
		return __( 'Emoji / pictograph flood', 'vms-elements-form-guard' );
	}
	public function evaluate( array $commentdata, array $config ) {
		$max = (int) ( $config['comment_emoji_flood_max'] ?? 0 );
		if ( $max <= 0 ) {
			return true;
		}
		$content = isset( $commentdata['comment_content'] ) ? (string) $commentdata['comment_content'] : '';
		$n = 0;
		$res = preg_match_all( '/\p{Extended_Pictographic}/u', $content, $m );
		if ( false !== $res && ! empty( $m[0] ) ) {
			$n = count( $m[0] );
		}
		if ( $n > $max ) {
			return new WP_Error(
				'vefg_spam_emoji',
				__( 'Too many emoji or symbols in this comment.', 'vms-elements-form-guard' )
			);
		}
		return true;
	}
}

final class Link_Policy_Spam_Component implements Spam_Check_Component {
	public function get_id(): string {
		return 'link_policy';
	}
	public function get_label(): string {
		return __( 'Link count & allow links', 'vms-elements-form-guard' );
	}
	public function evaluate( array $commentdata, array $config ) {
		$content = isset( $commentdata['comment_content'] ) ? (string) $commentdata['comment_content'] : '';
		$allow   = ! empty( $config['comment_allow_links'] );
		$count   = Spam_Check_Helpers::count_links_in_text( $content );
		if ( ! $allow && $count > 0 ) {
			return new WP_Error( 'vefg_no_links', __( 'Comments with links are not allowed.', 'vms-elements-form-guard' ) );
		}
		$max = (int) ( $config['comment_max_links'] ?? 0 );
		if ( $allow && $max > 0 && $count > $max ) {
			return new WP_Error(
				'vefg_spam_many_links',
				__( 'Too many links in this comment.', 'vms-elements-form-guard' )
			);
		}
		return true;
	}
}
