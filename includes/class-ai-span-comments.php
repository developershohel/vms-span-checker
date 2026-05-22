<?php
/**
 * AI-assisted comment spam checks and strike / block enforcement.
 *
 * Direct `$wpdb` calls target the plugin-owned
 * `{$wpdb->prefix}vms_span_checker_comment_enforcement` custom table. Identifiers
 * are hardcoded; values are prepared via `$wpdb->prepare()` or insert helpers.
 *
 * @package VMS_Span_Checker
 *
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 * phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
 * phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter
 */

namespace VMS_Span_Checker;

use WP_Error;
use VMS_Span_Checker\Services\AI_Span_Completion;

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
		// Flag-only display notice; the request originates from a server-side
		// redirect after logout, so no nonce is involved.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only flag, no state change.
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
		echo esc_html__( 'Your account was signed out because it has been permanently restricted from this site due to repeated abuse.', 'vms-span-checker' );
		echo '</div>';
	}

	/**
	 * Read one-time error payload from query string + transient (set on blocked comment redirect).
	 */
	public function consume_comment_error_query(): void {
		if ( is_admin() ) {
			return;
		}
		// One-time token consumed from URL; the token itself is bound to a
		// transient created server-side, so no nonce is required.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Validated against server-stored transient below.
		if ( empty( $_GET['wsc_comment_err'] ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Validated against server-stored transient below.
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
			'title'   => isset( $data['title'] ) ? (string) $data['title'] : __( 'Comment not posted', 'vms-span-checker' ),
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
			VMS_Span_Checker_ASSETS_URL . 'plugins/sweetalert2/sweetalert2.min.css',
			array(),
			VMS_Span_Checker_VERSION
		);
		wp_enqueue_script(
			'wsc-public-sweetalert',
			VMS_Span_Checker_ASSETS_URL . 'plugins/sweetalert2/sweetalert2.all.min.js',
			array(),
			VMS_Span_Checker_VERSION,
			true
		);

		$payload = wp_json_encode(
			array(
				'title'   => self::$frontend_comment_notice['title'],
				'message' => self::$frontend_comment_notice['message'],
				'ok'      => __( 'OK', 'vms-span-checker' ),
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
			Comment_Enforcement::redirect_with_notice( $commentdata, $check, '#respond' );
		}
		return $commentdata;
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

		// Product Review Guard is a Pro feature — only delegate when it's loaded.
		if ( class_exists( '\\VMS_Span_Checker\\Product_Review_Guard' )
			&& \VMS_Span_Checker\Product_Review_Guard::should_delegate_review_to_product_guard( $commentdata )
		) {
			return true;
		}

		$actor = Comment_Enforcement::get_actor( $commentdata );
		$row   = Comment_Enforcement::get_row( $actor['key'] );

		if ( $row && ! empty( $row['site_banned'] ) ) {
			return new WP_Error(
				'wsc_site_banned',
				__( 'You cannot use this site anymore due to repeated abuse. Use the contact page if you need to reach the owner.', 'vms-span-checker' )
			);
		}

		if ( ! empty( $c['comment_antispam_enabled'] ) ) {
			$spam_check = Comment_Spam_Rules::evaluate( $commentdata, $c );
			if ( is_wp_error( $spam_check ) ) {
				if ( ! empty( $c['comment_strike_on_heuristic'] ) ) {
					Comment_Enforcement::register_strike( $actor, $spam_check->get_error_message(), $c, 'comment' );
				}
				return $spam_check;
			}
		}

		if ( ! empty( $c['ai_enabled'] ) ) {
			$content = isset( $commentdata['comment_content'] ) ? (string) $commentdata['comment_content'] : '';

			// Pro plugin's AI_Span_Summary supplies this via the bridge filter.
			// When Pro is absent, the filter returns '' and AI moderation runs
			// without post context (still effective for the comment body).
			$summary = (string) apply_filters( 'vms_span_checker_post_summary_text', '', $post_id );
			$summary = '' !== $summary ? $summary : null;
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
						Comment_Enforcement::register_strike( $actor, $verdict['message'], $c, 'comment' );
						return new WP_Error(
							'wsc_spam',
							sprintf(
								/* translators: %s: short reason from AI */
								__( 'Comment rejected: %s', 'vms-span-checker' ),
								$verdict['message']
							)
						);
					}
				}
			}
		}

		$row = Comment_Enforcement::get_row( $actor['key'] );
		if ( $row && ! empty( $row['blocked'] ) && empty( $row['site_banned'] ) ) {
			return new WP_Error(
				'wsc_blocked',
				__( 'You are blocked from commenting on this site due to repeated spam attempts.', 'vms-span-checker' )
			);
		}

		return true;
	}

	/**
	 * Admin: clear block and optionally strikes.
	 */
	public static function admin_unblock( string $actor_key ): bool {
		global $wpdb;
		$table = $wpdb->prefix . 'vms_span_checker_comment_enforcement';
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
	 * Look up a WP_User by an ambiguous identifier (numeric ID, email, or username/login).
	 *
	 * Detection rules:
	 * - Numeric value         -> treated as a user ID first.
	 * - Contains "@"          -> treated as an email address.
	 * - Otherwise             -> treated as a user_login, then user_nicename, then display_name.
	 *
	 * @param string $input Raw admin input.
	 * @return \WP_User|null Matched user or null if nothing found.
	 */
	public static function find_user_by_input( string $input ): ?\WP_User {
		$input = trim( $input );
		if ( '' === $input ) {
			return null;
		}

		if ( ctype_digit( $input ) ) {
			$user = get_user_by( 'id', (int) $input );
			if ( $user instanceof \WP_User ) {
				return $user;
			}
		}

		if ( false !== strpos( $input, '@' ) ) {
			$user = get_user_by( 'email', $input );
			if ( $user instanceof \WP_User ) {
				return $user;
			}
		}

		$user = get_user_by( 'login', $input );
		if ( $user instanceof \WP_User ) {
			return $user;
		}

		$user = get_user_by( 'slug', sanitize_title( $input ) );
		if ( $user instanceof \WP_User ) {
			return $user;
		}

		$matches = get_users(
			array(
				'search'         => '*' . esc_attr( $input ) . '*',
				'search_columns' => array( 'user_login', 'user_email', 'user_nicename', 'display_name' ),
				'number'         => 1,
				'fields'         => 'all',
			)
		);
		if ( ! empty( $matches ) && $matches[0] instanceof \WP_User ) {
			return $matches[0];
		}

		return null;
	}

	/**
	 * Admin: manually block a user. Creates or updates the enforcement row.
	 *
	 * Scope flags control which surfaces the user is blocked from:
	 * - 'form'  -> blocked = 1 (comments, product reviews, guarded forms)
	 * - 'login' -> login_blocked = 1 (login filter in main plugin file)
	 * - 'site'  -> site_banned = 1 (front-end ban, force logout)
	 *
	 * Any unspecified scope flag is cleared on this row.
	 *
	 * @param int                                                                         $user_id WordPress user ID.
	 * @param array{scope?:array<int,string>,reason?:string,expiry_days?:int,strikes?:int} $opts    Block options.
	 * @return array{success:bool,message:string,actor_key:string}
	 */
	public static function admin_manual_block( int $user_id, array $opts = array() ): array {
		$user = get_user_by( 'id', $user_id );
		if ( ! ( $user instanceof \WP_User ) ) {
			return array(
				'success'   => false,
				'message'   => __( 'User not found.', 'vms-span-checker' ),
				'actor_key' => '',
			);
		}

		$cfg                      = AI_Span_Config::get();
		$exempt_admins            = ! empty( $cfg['block_user_exempt_admins'] );
		if ( $exempt_admins && user_can( $user, 'manage_options' ) ) {
			return array(
				'success'   => false,
				'message'   => __( 'This user is an administrator and is exempt from blocking. Disable "Exempt Admins" in Block User Settings to override.', 'vms-span-checker' ),
				'actor_key' => '',
			);
		}

		$scope = isset( $opts['scope'] ) && is_array( $opts['scope'] ) ? $opts['scope'] : array( 'form' );
		$scope = array_values( array_intersect( array( 'form', 'login', 'site' ), $scope ) );
		if ( empty( $scope ) ) {
			$scope = array( 'form' );
		}

		$reason = isset( $opts['reason'] ) ? trim( (string) $opts['reason'] ) : '';
		if ( '' === $reason ) {
			$reason = __( 'Manually blocked by administrator.', 'vms-span-checker' );
		}
		$reason = function_exists( 'mb_substr' ) ? mb_substr( $reason, 0, 500 ) : substr( $reason, 0, 500 );

		$max_strikes = (int) ( $cfg['block_user_max_strikes'] ?? 5 );
		$strikes     = isset( $opts['strikes'] ) ? max( 0, (int) $opts['strikes'] ) : $max_strikes;

		$expiry_days = isset( $opts['expiry_days'] ) ? max( 0, (int) $opts['expiry_days'] ) : 0;
		$expires_at  = null;
		if ( $expiry_days > 0 ) {
			$expires_at = gmdate( 'Y-m-d H:i:s', time() + ( $expiry_days * DAY_IN_SECONDS ) );
		}

		$actor_key   = 'u:' . (int) $user_id;
		$actor_label = $user->display_name . ' (' . $user->user_login . ')';
		$now         = current_time( 'mysql' );

		$row_data = array(
			'actor_key'         => $actor_key,
			'actor_label'       => $actor_label,
			'user_id'           => (int) $user_id,
			'strikes'           => $strikes,
			'blocked'           => in_array( 'form', $scope, true ) ? 1 : 0,
			'site_banned'       => in_array( 'site', $scope, true ) ? 1 : 0,
			'login_blocked'     => in_array( 'login', $scope, true ) ? 1 : 0,
			'last_ip'           => '',
			'blocked_at'        => $now,
			'last_strike_at'    => $now,
			'strikes_expire_at' => $expires_at,
			'last_reason'       => $reason,
			'strike_source'     => 'manual',
		);

		global $wpdb;
		$table    = $wpdb->prefix . 'vms_span_checker_comment_enforcement';
		$existing = self::get_enforcement_row_by_key( $actor_key );

		if ( $existing ) {
			$updated = $wpdb->update(
				$table,
				$row_data,
				array( 'actor_key' => $actor_key ),
				array( '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' ),
				array( '%s' )
			);
			$ok = false !== $updated;
		} else {
			$inserted = $wpdb->insert(
				$table,
				$row_data,
				array( '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
			);
			$ok = false !== $inserted;
		}

		if ( ! $ok ) {
			return array(
				'success'   => false,
				'message'   => __( 'Database error while saving block.', 'vms-span-checker' ),
				'actor_key' => '',
			);
		}

		return array(
			'success'   => true,
			'message'   => sprintf(
				/* translators: %s: user display label */
				__( 'User %s is now blocked.', 'vms-span-checker' ),
				$actor_label
			),
			'actor_key' => $actor_key,
		);
	}

	/**
	 * Admin: update only the scope flags on an existing enforcement row.
	 *
	 * @param string             $actor_key Row identifier (e.g. "u:42").
	 * @param array<int, string> $scope     Subset of {'form','login','site'}.
	 * @return bool
	 */
	public static function admin_edit_block_scope( string $actor_key, array $scope ): bool {
		$scope = array_values( array_intersect( array( 'form', 'login', 'site' ), $scope ) );
		global $wpdb;
		$table  = $wpdb->prefix . 'vms_span_checker_comment_enforcement';
		$update = array(
			'blocked'       => in_array( 'form', $scope, true ) ? 1 : 0,
			'login_blocked' => in_array( 'login', $scope, true ) ? 1 : 0,
			'site_banned'   => in_array( 'site', $scope, true ) ? 1 : 0,
		);
		$result = $wpdb->update(
			$table,
			$update,
			array( 'actor_key' => $actor_key ),
			array( '%d', '%d', '%d' ),
			array( '%s' )
		);
		return false !== $result;
	}

	/**
	 * Lightweight helper to fetch a single enforcement row by actor_key.
	 *
	 * @return array<string, mixed>|null
	 */
	private static function get_enforcement_row_by_key( string $actor_key ): ?array {
		global $wpdb;
		$table = $wpdb->prefix . 'vms_span_checker_comment_enforcement';
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE actor_key = %s", $actor_key ),
			ARRAY_A
		);
		return is_array( $row ) ? $row : null;
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
		$script = isset( $_SERVER['SCRIPT_NAME'] ) ? basename( sanitize_text_field( wp_unslash( (string) $_SERVER['SCRIPT_NAME'] ) ) ) : '';
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

		$msg  = __( 'Your access to this site has been permanently restricted due to repeated spam or abuse.', 'vms-span-checker' );
		$msg .= ' ' . $this->get_contact_owner_html_fragment();
		wp_die( wp_kses_post( $msg ), esc_html__( 'Access restricted', 'vms-span-checker' ), array( 'response' => 403 ) );
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
		$row = Comment_Enforcement::get_row( 'u:' . (int) $user->ID );
		if ( $row && ! empty( $row['site_banned'] ) ) {
			return new WP_Error(
				'wsc_site_banned_login',
				__( 'This account cannot sign in because it was permanently restricted due to repeated abuse. Please contact the site owner.', 'vms-span-checker' )
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
		$explain = sprintf( __( 'Comments are disabled for you on this site because your submissions exceeded the strike limit (%d). This helps protect the community from spam.', 'vms-span-checker' ), (int) ( $c['comment_max_strikes'] ?? 5 ) );
		$contact = $this->get_contact_owner_html_fragment();
		return '<div class="wsc-strike-comment-note" role="status" style="border-left:4px solid #996800;background:#fcf9e8;padding:14px 16px;border-radius:4px;line-height:1.5;">'
			. '<p style="margin:0 0 8px;"><strong>' . esc_html__( 'Why you cannot comment here', 'vms-span-checker' ) . '</strong></p>'
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
			return esc_html__( 'If you believe this is a mistake, please reach the site owner through any contact method they publish on this website.', 'vms-span-checker' );
		}
		return sprintf(
			/* translators: %s: anchor HTML to contact page */
			__( 'If you need to reach the site owner, use the %s page.', 'vms-span-checker' ),
			'<a href="' . esc_url( $permalink ) . '">' . esc_html__( 'contact', 'vms-span-checker' ) . '</a>'
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
			$row = Comment_Enforcement::get_row( 'u:' . get_current_user_id() );
			return (bool) ( $row && ! empty( $row['blocked'] ) && empty( $row['site_banned'] ) );
		}
		$ip = Comment_Enforcement::get_ip();
		if ( '' === $ip ) {
			return false;
		}
		global $wpdb;
		$table = $wpdb->prefix . 'vms_span_checker_comment_enforcement';
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
			$row = Comment_Enforcement::get_row( 'u:' . get_current_user_id() );
			return ( $row && ! empty( $row['site_banned'] ) ) ? $row : null;
		}
		$ip = Comment_Enforcement::get_ip();
		if ( '' === $ip ) {
			return null;
		}
		global $wpdb;
		$table = $wpdb->prefix . 'vms_span_checker_comment_enforcement';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE site_banned = 1 AND last_ip = %s LIMIT 1", $ip ), ARRAY_A );
		return is_array( $row ) ? $row : null;
	}
}
