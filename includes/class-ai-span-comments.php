<?php
/**
 * AI-assisted comment spam checks and strike / block enforcement.
 *
 * @package WP_Span_Checker
 */

namespace WP_Span_Checker;

use WP_Error;
use WP_Span_Checker\Services\AI_Span_Completion;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hooks preprocess_comment + REST comment insert.
 */
class AI_Span_Comments {

	/**
	 * One-shot notice for the current request after redirect from wp-comments-post.php.
	 *
	 * @var array{title:string,message:string}|null
	 */
	private static $frontend_comment_notice = null;

	/**
	 * Whether the inline notice was already output via comment_form_before.
	 *
	 * @var bool
	 */
	private $comment_notice_printed = false;

	/**
	 * True when this request hides the comment form due to strike / block (not site-wide ban).
	 *
	 * @var bool
	 */
	private static $strike_closed_comments = false;

	/**
	 * Avoid duplicating the strike notice in footer + comment_form_comments_closed.
	 *
	 * @var bool
	 */
	private $strike_notice_printed = false;

	public function __construct() {
		add_filter( 'preprocess_comment', array( $this, 'preprocess_comment' ), 5, 1 );
		add_filter( 'rest_pre_insert_comment', array( $this, 'rest_pre_insert_comment' ), 10, 2 );
		add_action( 'template_redirect', array( $this, 'consume_comment_error_query' ), 5 );
		add_action( 'template_redirect', array( $this, 'enforce_site_ban_front' ), 2 );
		add_filter( 'authenticate', array( $this, 'block_site_banned_login' ), 99, 3 );
		add_filter( 'comments_open', array( $this, 'comments_open_for_strike_blocked' ), 20, 2 );
		add_action( 'comment_form_comments_closed', array( $this, 'print_strike_block_comment_notice' ), 5, 1 );
		add_action( 'wp_footer', array( $this, 'maybe_print_strike_block_notice_footer' ), 50 );
		add_action( 'wp_footer', array( $this, 'maybe_flash_site_ban_contact_notice' ), 8 );
	}

	/**
	 * After logout redirect, show one line on the contact page.
	 */
	public function maybe_flash_site_ban_contact_notice(): void {
		if ( is_admin() || empty( $_GET['wsc_site_ban'] ) ) {
			return;
		}
		$c         = AI_Span_Config::get();
		$contact_id = (int) ( $c['contact_guard_page_id'] ?? 0 );
		if ( $contact_id <= 0 ) {
			$contact_id = (int) ( $c['comment_contact_page_id'] ?? 0 );
		}
		if ( $contact_id <= 0 || ! is_page( $contact_id ) ) {
			return;
		}
		echo '<div class="wsc-site-ban-flash" role="status" style="max-width:720px;margin:1rem auto;padding:12px 14px;border-left:4px solid #d63638;background:#fcf0f1;border-radius:4px;">';
		echo esc_html__( 'Your account was signed out because it has been permanently restricted from this site due to repeated abuse.', 'wp-span-checker' );
		echo '</div>';
	}

	/**
	 * Read one-time error payload from query string + transient (set on blocked comment redirect).
	 */
	public function consume_comment_error_query(): void {
		if ( is_admin() ) {
			return;
		}
		if ( empty( $_GET['wsc_comment_err'] ) ) {
			return;
		}
		$token = sanitize_text_field( wp_unslash( (string) $_GET['wsc_comment_err'] ) );
		if ( strlen( $token ) !== 16 || ! ctype_alnum( $token ) ) {
			return;
		}
		$key  = 'wsc_cerr_' . $token;
		$data = get_transient( $key );
		delete_transient( $key );
		if ( ! is_array( $data ) || empty( $data['message'] ) ) {
			return;
		}
		self::$frontend_comment_notice = array(
			'title'   => isset( $data['title'] ) ? (string) $data['title'] : __( 'Comment not posted', 'wp-span-checker' ),
			'message' => (string) $data['message'],
		);
		add_action( 'comment_form_before', array( $this, 'print_comment_form_error_notice' ), 5 );
		add_action( 'wp_footer', array( $this, 'maybe_print_comment_notice_fallback' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_comment_blocked_assets' ), 25 );
	}

	/**
	 * Inline notice above the comment form (in addition to SweetAlert).
	 */
	public function print_comment_form_error_notice(): void {
		if ( empty( self::$frontend_comment_notice ) ) {
			return;
		}
		$this->render_comment_notice_markup();
		$this->comment_notice_printed = true;
	}

	/**
	 * If the theme never calls comment_form(), still show the inline notice once in the footer.
	 */
	public function maybe_print_comment_notice_fallback(): void {
		if ( empty( self::$frontend_comment_notice ) || $this->comment_notice_printed ) {
			return;
		}
		if ( ! is_singular() ) {
			return;
		}
		$this->render_comment_notice_markup();
		$this->comment_notice_printed = true;
	}

	/**
	 * Shared HTML for the page notice (matches core “error” notice feel).
	 */
	private function render_comment_notice_markup(): void {
		if ( empty( self::$frontend_comment_notice ) ) {
			return;
		}
		$msg = self::$frontend_comment_notice['message'];
		echo '<div class="wsc-comment-blocked-notice" role="alert" style="border-left:4px solid #d63638;background:#fcf0f1;padding:12px 14px;margin:0 0 1.25em;border-radius:4px;color:#1d2327;font-size:14px;line-height:1.45;">';
		echo '<strong style="display:block;margin-bottom:6px;">' . esc_html( self::$frontend_comment_notice['title'] ) . '</strong>';
		echo '<span>' . esc_html( $msg ) . '</span>';
		echo '</div>';
	}

	/**
	 * SweetAlert2 + inline script when a comment was blocked and the user was redirected back.
	 */
	public function enqueue_frontend_comment_blocked_assets(): void {
		if ( empty( self::$frontend_comment_notice ) ) {
			return;
		}

		wp_enqueue_style(
			'wsc-public-sweetalert',
			WP_Span_Checker_ASSETS_URL . 'plugins/sweetalert2/sweetalert2.min.css',
			array(),
			WP_Span_Checker_VERSION
		);
		wp_enqueue_script(
			'wsc-public-sweetalert',
			WP_Span_Checker_ASSETS_URL . 'plugins/sweetalert2/sweetalert2.all.min.js',
			array(),
			WP_Span_Checker_VERSION,
			true
		);

		$payload = wp_json_encode(
			array(
				'title'   => self::$frontend_comment_notice['title'],
				'message' => self::$frontend_comment_notice['message'],
				'ok'      => __( 'OK', 'wp-span-checker' ),
			)
		);

		wp_add_inline_script(
			'wsc-public-sweetalert',
			'document.addEventListener("DOMContentLoaded",function(){var d=' . $payload . ';'
			. 'if(typeof Swal!=="undefined"){Swal.fire({icon:"error",title:d.title,text:d.message,confirmButtonText:d.ok});}'
			. 'if(window.history&&history.replaceState){var u=new URL(window.location.href);u.searchParams.delete("wsc_comment_err");'
			. 'history.replaceState(null,"",u.pathname+u.search+window.location.hash);}});',
			'after'
		);
	}

	/**
	 * @param array<string, mixed> $commentdata .
	 * @return array<string, mixed>
	 */
	public function preprocess_comment( $commentdata ) {
		$check = $this->evaluate_comment_submission( $commentdata );
		if ( is_wp_error( $check ) ) {
			$this->redirect_comment_error( $commentdata, $check );
		}
		return $commentdata;
	}

	/**
	 * Send user back to the post with a one-time token instead of wp_die on wp-comments-post.php.
	 *
	 * @param array<string, mixed> $commentdata .
	 * @param WP_Error             $error       .
	 */
	private function redirect_comment_error( array $commentdata, WP_Error $error ): void {
		$post_id = isset( $commentdata['comment_post_ID'] ) ? (int) $commentdata['comment_post_ID'] : 0;
		$url     = ( $post_id > 0 ) ? get_permalink( $post_id ) : home_url( '/' );
		if ( ! is_string( $url ) || '' === $url ) {
			$url = home_url( '/' );
		}

		$token = function_exists( 'wp_generate_password' )
			? wp_generate_password( 16, false, false )
			: bin2hex( random_bytes( 8 ) );
		set_transient(
			'wsc_cerr_' . $token,
			array(
				'title'   => __( 'Comment blocked', 'wp-span-checker' ),
				'message' => $error->get_error_message(),
			),
			5 * MINUTE_IN_SECONDS
		);

		$target = add_query_arg( 'wsc_comment_err', $token, $url );
		// Fragment after query string (permalink structure-safe).
		$target .= '#respond';

		wp_safe_redirect( $target );
		exit;
	}

	/**
	 * @param \WP_Comment $prepared_comment .
	 * @param \WP_REST_Request $request .
	 * @return \WP_Comment|WP_Error
	 */
	public function rest_pre_insert_comment( $prepared_comment, $request ) {
		$data = array(
			'comment_post_ID'      => $prepared_comment->comment_post_ID,
			'comment_author'       => $prepared_comment->comment_author,
			'comment_author_email' => $prepared_comment->comment_author_email,
			'comment_author_url'   => isset( $prepared_comment->comment_author_url ) ? $prepared_comment->comment_author_url : '',
			'comment_content'      => $prepared_comment->comment_content,
			'comment_type'         => isset( $prepared_comment->comment_type ) ? $prepared_comment->comment_type : 'comment',
			'user_id'              => $prepared_comment->user_id,
		);
		$check = $this->evaluate_comment_submission( $data );
		if ( is_wp_error( $check ) ) {
			return $check;
		}
		return $prepared_comment;
	}

	/**
	 * @param array<string, mixed> $commentdata .
	 * @return true|WP_Error
	 */
	private function evaluate_comment_submission( array $commentdata ) {
		$c       = AI_Span_Config::get();
		$post_id = isset( $commentdata['comment_post_ID'] ) ? (int) $commentdata['comment_post_ID'] : 0;
		if ( $post_id <= 0 ) {
			return true;
		}

		$actor = $this->get_actor( $commentdata );
		$row   = $this->get_enforcement_row( $actor['key'] );

		if ( $row && ! empty( $row['site_banned'] ) ) {
			return new WP_Error(
				'wsc_site_banned',
				__( 'You cannot use this site anymore due to repeated abuse. Use the contact page if you need to reach the owner.', 'wp-span-checker' )
			);
		}

		if ( ! empty( $c['comment_antispam_enabled'] ) ) {
			$spam_check = Comment_Spam_Rules::evaluate( $commentdata, $c );
			if ( is_wp_error( $spam_check ) ) {
				if ( ! empty( $c['comment_strike_on_heuristic'] ) ) {
					$this->register_strike( $actor, $spam_check->get_error_message(), $c );
				}
				return $spam_check;
			}
		}

		if ( ! empty( $c['ai_enabled'] ) ) {
			$content = isset( $commentdata['comment_content'] ) ? (string) $commentdata['comment_content'] : '';

			$summary = AI_Span_Summary::get_summary_text( $post_id );
			if ( $summary !== null && $summary !== '' ) {
				$sys = (string) ( $c['system_prompt'] ?? '' );
				if ( $sys === '' ) {
					$sys = AI_Span_Config::default_system_prompt();
				}

				$review_note = ! empty( $c['product_review_filter'] )
					? 'PRODUCT_REVIEW_MODE: yes — allow genuine short reviews.'
					: 'PRODUCT_REVIEW_MODE: no';

				$usr = $review_note . "\n\nPOST_SUMMARY:\n" . $summary . "\n\nCOMMENT_TEXT:\n" . $content;

				$raw = AI_Span_Completion::complete( $sys, $usr );
				if ( ! is_wp_error( $raw ) ) {
					$verdict = AI_Span_Completion::parse_json_verdict( (string) $raw );
					if ( ! is_wp_error( $verdict ) && 'spam' === $verdict['status'] ) {
						$this->register_strike( $actor, $verdict['message'], $c );
						return new WP_Error(
							'wsc_spam',
							sprintf(
								/* translators: %s: short reason from AI */
								__( 'Comment rejected: %s', 'wp-span-checker' ),
								$verdict['message']
							)
						);
					}
				}
			}
		}

		$row = $this->get_enforcement_row( $actor['key'] );
		if ( $row && ! empty( $row['blocked'] ) && empty( $row['site_banned'] ) ) {
			return new WP_Error(
				'wsc_blocked',
				__( 'You are blocked from commenting on this site due to repeated spam attempts.', 'wp-span-checker' )
			);
		}

		return true;
	}

	/**
	 * @param array<string, mixed> $commentdata .
	 * @return array{key:string,label:string}
	 */
	private function get_actor( array $commentdata ): array {
		$uid = isset( $commentdata['user_id'] ) ? (int) $commentdata['user_id'] : 0;
		if ( $uid > 0 ) {
			return array(
				'key'   => 'u:' . $uid,
				'label' => 'user:' . $uid,
			);
		}

		$email = isset( $commentdata['comment_author_email'] ) ? strtolower( trim( (string) $commentdata['comment_author_email'] ) ) : '';
		$ip    = $this->get_ip();

		$key = substr( hash( 'sha256', $ip . '|' . $email ), 0, 64 );

		return array(
			'key'   => 'g:' . $key,
			'label' => $email !== '' ? $email : $ip,
		);
	}

	private function get_ip(): string {
		if ( function_exists( 'wp_span_checker_get_user_ip' ) ) {
			return wp_span_checker_get_user_ip();
		}
		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) ) : '';
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private function get_enforcement_row( string $actor_key ): ?array {
		global $wpdb;
		$table = $wpdb->prefix . 'span_checker_comment_enforcement';
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE actor_key = %s", $actor_key ),
			ARRAY_A
		);
		return is_array( $row ) ? $row : null;
	}

	/**
	 * @param array{key:string,label:string} $actor .
	 * @param array<string, mixed>          $c     Config.
	 */
	private function register_strike( array $actor, string $reason, array $c ): void {
		global $wpdb;
		$table        = $wpdb->prefix . 'span_checker_comment_enforcement';
		$max_comment  = (int) ( $c['comment_max_strikes'] ?? 5 );
		$ban_enabled  = ! empty( $c['comment_site_ban_enabled'] );
		$ban_at       = (int) ( $c['comment_site_ban_strikes'] ?? 10 );
		$now          = current_time( 'mysql' );
		$reason       = function_exists( 'mb_substr' ) ? mb_substr( $reason, 0, 500 ) : substr( $reason, 0, 500 );
		$ip           = $this->get_ip();
		$existing     = $this->get_enforcement_row( $actor['key'] );

		if ( $existing ) {
			$strikes      = (int) $existing['strikes'] + 1;
			$was_blocked  = (int) $existing['blocked'];
			$site_banned  = (int) ( $existing['site_banned'] ?? 0 );
			$blocked      = ( $strikes >= $max_comment ) ? 1 : $was_blocked;
			if ( $ban_enabled && $strikes >= $ban_at ) {
				$site_banned = 1;
			}
			$blocked_at     = (string) ( $existing['blocked_at'] ?? '' );
			if ( $blocked && ! $was_blocked ) {
				$blocked_at = $now;
			}
			$wpdb->update(
				$table,
				array(
					'strikes'        => $strikes,
					'blocked'        => $blocked,
					'site_banned'    => $site_banned,
					'last_ip'        => $ip,
					'blocked_at'     => $blocked_at,
					'last_strike_at' => $now,
					'last_reason'    => $reason,
					'actor_label'    => $actor['label'],
				),
				array( 'actor_key' => $actor['key'] ),
				array( '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s' ),
				array( '%s' )
			);
			return;
		}

		$strikes     = 1;
		$blocked     = ( $strikes >= $max_comment ) ? 1 : 0;
		$site_banned = ( $ban_enabled && $strikes >= $ban_at ) ? 1 : 0;
		$row         = array(
			'actor_key'      => $actor['key'],
			'actor_label'    => $actor['label'],
			'strikes'        => $strikes,
			'blocked'        => $blocked,
			'site_banned'    => $site_banned,
			'last_ip'        => $ip,
			'last_strike_at' => $now,
			'last_reason'    => $reason,
		);
		if ( $blocked ) {
			$row['blocked_at'] = $now;
		}
		$wpdb->insert( $table, $row );
	}

	/**
	 * Admin: clear block and optionally strikes.
	 */
	public static function admin_unblock( string $actor_key ): bool {
		global $wpdb;
		$table = $wpdb->prefix . 'span_checker_comment_enforcement';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name uses trusted prefix.
		$result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET blocked = 0, site_banned = 0, login_blocked = 0, strikes = 0, blocked_at = NULL, strikes_expire_at = NULL, last_ip = '' WHERE actor_key = %s",
				$actor_key
			)
		);
		return false !== $result && '' === $wpdb->last_error;
	}

	/**
	 * Permanent site ban: block front-end except the configured contact page.
	 */
	public function enforce_site_ban_front(): void {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
			return;
		}

		$c          = AI_Span_Config::get();
		$contact_id = (int) ( $c['contact_guard_page_id'] ?? 0 );
		if ( $contact_id <= 0 ) {
			$contact_id = (int) ( $c['comment_contact_page_id'] ?? 0 );
		}
		if ( $contact_id > 0 && is_page( $contact_id ) ) {
			return;
		}
		$script = isset( $_SERVER['SCRIPT_NAME'] ) ? basename( (string) $_SERVER['SCRIPT_NAME'] ) : '';
		if ( 'wp-login.php' === $script ) {
			return;
		}

		$banned = $this->get_viewer_site_ban_row();
		if ( ! $banned ) {
			return;
		}

		if ( is_user_logged_in() ) {
			wp_logout();
			$target = ( $contact_id > 0 ) ? get_permalink( $contact_id ) : home_url( '/' );
			if ( ! is_string( $target ) || '' === $target ) {
				$target = home_url( '/' );
			}
			$target = add_query_arg( 'wsc_site_ban', '1', $target );
			wp_safe_redirect( $target );
			exit;
		}

		$msg  = __( 'Your access to this site has been permanently restricted due to repeated spam or abuse.', 'wp-span-checker' );
		$msg .= ' ' . $this->get_contact_owner_html_fragment();
		wp_die( wp_kses_post( $msg ), esc_html__( 'Access restricted', 'wp-span-checker' ), array( 'response' => 403 ) );
	}

	/**
	 * @param \WP_User|\WP_Error|null $user     .
	 * @param string                  $username .
	 * @param string                  $password .
	 * @return \WP_User|\WP_Error|null
	 */
	public function block_site_banned_login( $user, $username, $password ) {
		if ( $user instanceof WP_Error || null === $user ) {
			return $user;
		}
		if ( ! ( $user instanceof \WP_User ) ) {
			return $user;
		}
		$row = $this->get_enforcement_row( 'u:' . (int) $user->ID );
		if ( $row && ! empty( $row['site_banned'] ) ) {
			return new WP_Error(
				'wsc_site_banned_login',
				__( 'This account cannot sign in because it was permanently restricted due to repeated abuse. Please contact the site owner.', 'wp-span-checker' )
			);
		}
		return $user;
	}

	/**
	 * Hide comment form for viewers who are strike-blocked (not site-banned — they are redirected).
	 *
	 * @param bool $open    .
	 * @param int  $post_id .
	 * @return bool
	 */
	public function comments_open_for_strike_blocked( $open, $post_id ) {
		if ( is_admin() || ! $open ) {
			return (bool) $open;
		}
		if ( ! $this->viewer_is_strike_blocked_from_comments() ) {
			return (bool) $open;
		}
		self::$strike_closed_comments = true;
		return false;
	}

	/**
	 * @param int $post_id Post ID passed from comment_form().
	 */
	public function print_strike_block_comment_notice( $post_id ): void {
		if ( ! self::$strike_closed_comments ) {
			return;
		}
		echo wp_kses_post( $this->get_strike_block_notice_html() );
		$this->strike_notice_printed = true;
	}

	/**
	 * Themes that never call comment_form() when comments are closed still need the notice.
	 */
	public function maybe_print_strike_block_notice_footer(): void {
		if ( ! self::$strike_closed_comments || ! is_singular() || $this->strike_notice_printed ) {
			return;
		}
		echo '<div class="wsc-strike-comment-note-wrap" style="max-width:720px;margin:1.25rem auto;padding:0 1rem;">';
		echo wp_kses_post( $this->get_strike_block_notice_html() );
		echo '</div>';
		$this->strike_notice_printed = true;
	}

	/**
	 * HTML for strike-only block (explains + contact link).
	 */
	private function get_strike_block_notice_html(): string {
		$c = AI_Span_Config::get();
		/* translators: %d: strike threshold */
		$explain = sprintf( __( 'Comments are disabled for you on this site because your submissions exceeded the strike limit (%d). This helps protect the community from spam.', 'wp-span-checker' ), (int) ( $c['comment_max_strikes'] ?? 5 ) );
		$contact = $this->get_contact_owner_html_fragment();
		return '<div class="wsc-strike-comment-note" role="status" style="border-left:4px solid #996800;background:#fcf9e8;padding:14px 16px;border-radius:4px;line-height:1.5;">'
			. '<p style="margin:0 0 8px;"><strong>' . esc_html__( 'Why you cannot comment here', 'wp-span-checker' ) . '</strong></p>'
			. '<p style="margin:0 0 10px;">' . esc_html( $explain ) . '</p>'
			. '<p style="margin:0;">' . $contact . '</p>'
			. '</div>';
	}

	/**
	 * Short line with link to the contact page when configured.
	 */
	private function get_contact_owner_html_fragment(): string {
		$c          = AI_Span_Config::get();
		$page_id    = (int) ( $c['contact_guard_page_id'] ?? 0 );
		if ( $page_id <= 0 ) {
			$page_id = (int) ( $c['comment_contact_page_id'] ?? 0 );
		}
		$permalink  = ( $page_id > 0 ) ? get_permalink( $page_id ) : '';
		if ( ! is_string( $permalink ) || '' === $permalink ) {
			return esc_html__( 'If you believe this is a mistake, please reach the site owner through any contact method they publish on this website.', 'wp-span-checker' );
		}
		return sprintf(
			/* translators: %s: anchor HTML to contact page */
			__( 'If you need to reach the site owner, use the %s page.', 'wp-span-checker' ),
			'<a href="' . esc_url( $permalink ) . '">' . esc_html__( 'contact', 'wp-span-checker' ) . '</a>'
		);
	}

	/**
	 * Strike-blocked from comments but not necessarily site-banned.
	 */
	private function viewer_is_strike_blocked_from_comments(): bool {
		if ( $this->get_viewer_site_ban_row() ) {
			return false;
		}
		if ( is_user_logged_in() ) {
			$row = $this->get_enforcement_row( 'u:' . get_current_user_id() );
			return (bool) ( $row && ! empty( $row['blocked'] ) && empty( $row['site_banned'] ) );
		}
		$ip = $this->get_ip();
		if ( '' === $ip ) {
			return false;
		}
		global $wpdb;
		$table = $wpdb->prefix . 'span_checker_comment_enforcement';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$n = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE blocked = 1 AND site_banned = 0 AND last_ip = %s", $ip ) );
		return $n > 0;
	}

	/**
	 * Current viewer row if site-wide banned (logged-in: user key; guest: IP on last strike).
	 *
	 * @return array<string, mixed>|null
	 */
	private function get_viewer_site_ban_row(): ?array {
		if ( is_user_logged_in() ) {
			$row = $this->get_enforcement_row( 'u:' . get_current_user_id() );
			return ( $row && ! empty( $row['site_banned'] ) ) ? $row : null;
		}
		$ip = $this->get_ip();
		if ( '' === $ip ) {
			return null;
		}
		global $wpdb;
		$table = $wpdb->prefix . 'span_checker_comment_enforcement';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE site_banned = 1 AND last_ip = %s LIMIT 1", $ip ), ARRAY_A );
		return is_array( $row ) ? $row : null;
	}
}
