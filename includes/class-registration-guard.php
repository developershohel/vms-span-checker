<?php

/**
 * Registration Guard: validates signup email domains using WordPress-wide hooks (not per–page-builder).
 *
 * Coverage is defined by **how WordPress creates users**, not by plugin name:
 *
 * - {@see 'registration_errors'} — core `register_new_user` / wp-login register.
 * - {@see 'wp_pre_insert_user_data'} — any code path that calls `wp_insert_user()` / `wp_create_user()`
 *   (most front-end form plugins do this).
 * - {@see 'query'} on `$wpdb` — blocks many raw `INSERT`/`REPLACE` statements targeting `{$wpdb->users}`
 *   when the statement shape can be parsed (not a substitute for database-level permissions).
 * - {@see 'user_register'} — deletes the account if it was still created with a blocked email (last resort).
 * - {@see 'woocommerce_registration_errors'} — WooCommerce “my account” registration when active.
 *
 * There is **no** maintainable list of “Elementor / Essential Addons / …” hooks: if an addon correctly
 * uses WordPress APIs above, it is already covered. If an addon creates the row **before** it validates,
 * only that addon’s authors can reorder their code; you may also call
 * {@see Registration_Guard::rejection_message_for_registration_email()} from custom PHP that runs *before*
 * their `wp_insert_user` call, if they provide such a hook.
 *
 * @package WP_Span_Checker
 *
 * Debug file logging: set {@see WP_DEBUG_LOG} to true in wp-config.php, or define
 * `WSC_REGISTRATION_GUARD_LOG` as true to log hook/outcome lines without full WP_DEBUG.
 *
 * `wp_pre_insert_user_data` may only return an array; core then does
 * `if ( empty( $data ) || ! is_array( $data ) ) { return new WP_Error( 'empty_data', ... ); }`.
 * To have {@see wp_insert_user()} return {@see WP_Error} with code `wsc_registration_email` instead of
 * `empty_data`, add immediately after the `apply_filters( 'wp_pre_insert_user_data', ... )` line in
 * `wp-includes/user.php`:
 *
 * `if ( is_wp_error( $data ) ) { return $data; }`
 */

namespace WP_Span_Checker;

use WP_Error;
use WP_Span_Checker\Services\Domain_Validator;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Validates registration emails using WordPress core hooks shared by all well-behaved registration code.
 */
class Registration_Guard
{

	public const OPTION_KEY = 'wsc-registration-guard';

	private const RATE_TRANSIENT_PREFIX = 'wsc_rg_rl_';

	/**
	 * When blocking via wp_pre_insert_user_data, we build this error and return it from a late filter
	 * (after core adds {@see is_wp_error()} for `$data`, it is returned as-is). Until then, core maps
	 * non-array data to WP_Error( 'empty_data', ... ); we swap that message via gettext.
	 *
	 * @var \WP_Error|null
	 */
	private static $wp_pre_insert_user_error = null;

	/**
	 * True when current request pre-insert validation decided to block registration.
	 *
	 * @var bool
	 */
	private static $wp_pre_insert_user_blocked = false;

	/**
	 * One-time front-end notice after redirecting a blocked registration attempt.
	 *
	 * @var array{title:string,message:string}|null
	 */
	private static $frontend_registration_notice = null;

	/**
	 * Avoid redirect loops when multiple hooks run in one request.
	 *
	 * @var bool
	 */
	private static $registration_redirect_done = false;

	/**
	 * Human-readable message for gettext when core still uses the empty_data branch.
	 *
	 * @var string|null
	 */
	private static $empty_data_message_replacement = null;

	/**
	 * Request cache: lowercase email => block message or null if allowed.
	 *
	 * @var array<string, string|null>
	 */
	private static $block_message_cache = array();

	/**
	 * Prevents recursion: {@see 'query'} must not trigger code that runs more SQL while the same outer query is in flight.
	 *
	 * @var bool
	 */
	private static $wpdb_query_guard_active = false;

	/**
	 * @return array<string, mixed>
	 */
	public static function defaults(): array
	{
		return array(
			'enabled'                    => false,
			'use_webrisk'                => true,
			'use_virustotal'             => true,
			'require_dns_live'           => true,
			'require_mx'                 => true,
			'mx_allow_a_fallback'        => true,
			'skip_https_check'           => true,
			'rate_limit_enabled'         => true,
			'rate_limit_max_burst'       => 5,
			'rate_limit_lockout_seconds' => 18000,
			'rate_limit_max_per_day'     => 10,
		);
	}

	public function __construct()
	{
		// Load EARLY (MU plugin preferred)

		// Validation
		add_filter('registration_errors', [$this, 'filter_registration_errors'], -9999, 3);

		// Pre-insert
		add_filter('wp_pre_insert_user_data', [$this, 'filter_wp_pre_insert_user_data'], PHP_INT_MIN, 4);
		add_filter('wp_pre_insert_user_data', [$this, 'filter_wp_pre_insert_user_data_return_wp_error'], PHP_INT_MAX, 4);

		// SQL guard
		add_filter('query', [$this, 'filter_wpdb_query_block_raw_user_insert'], -9999, 1);

		// Post insert
		add_action('user_register', [$this, 'action_user_register_enforce_guard'], -9999, 2);

		// Optional
		add_filter('gettext', [$this, 'filter_gettext_empty_data_replacement'], -9999, 3);
		add_action('template_redirect', [$this, 'consume_registration_error_query'], 5);
		add_action('login_init', [$this, 'consume_registration_error_query'], 5);
		add_action('wp_footer', [$this, 'maybe_print_registration_notice_fallback'], 6);
		add_action('login_form_register', [$this, 'print_registration_notice_login_form'], 5);
		add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_registration_blocked_assets'], 26);
		add_action('login_enqueue_scripts', [$this, 'enqueue_frontend_registration_blocked_assets'], 26);
		if (class_exists('WooCommerce')) {
			add_filter('woocommerce_registration_errors', array($this, 'filter_wc_registration_errors'), -9999, 3);
		}
	}

	/**
	 * @param \WP_Error $errors .
	 * @param string    $sanitized_user_login .
	 * @param string    $user_email .
	 * @return \WP_Error
	 */
	public function filter_registration_errors($errors, $sanitized_user_login, $user_email)
	{
		self::log_registration_guard(
			'registration_errors',
			'hook_invoked',
			array(
				'has_email' => '' !== (string) $user_email,
			)
		);
		return $this->apply_guard($errors, (string) $user_email, 'registration_errors');
	}

	/**
	 * @param \WP_Error $errors .
	 * @param string    $username .
	 * @param string    $email .
	 * @return \WP_Error
	 */
	public function filter_wc_registration_errors($errors, $username, $email)
	{
		self::log_registration_guard(
			'woocommerce_registration_errors',
			'hook_invoked',
			array(
				'has_email' => '' !== (string) $email,
			)
		);
		return $this->apply_guard($errors, (string) $email, 'woocommerce_registration_errors');
	}

	/**
	 * @param \WP_Error $errors .
	 * @param string    $user_email .
	 * @param string    $hook_source WordPress hook / path label for debug logs.
	 * @return \WP_Error
	 */
	private function apply_guard($errors, string $user_email, string $hook_source)
	{
		if (! ($errors instanceof WP_Error)) {
			$errors = new WP_Error();
		}

		$msg = self::get_block_message_for_email($user_email, $hook_source);
		if ($msg !== null) {
			$errors->add('wsc_registration_email', $msg);
		}

		return $errors;
	}

	/**
	 * Shared validation for registration_errors and programmatic signups via wp_insert_user.
	 *
	 * @param string $hook_source Hook label for error_log (e.g. registration_errors).
	 * @return string|null Error message if blocked, null if allowed or guard inactive.
	 */
	private static function get_block_message_for_email(string $user_email, string $hook_source = 'unknown'): ?string
	{
		$cfg = self::get();
		if (empty($cfg['enabled'])) {
			self::log_registration_guard($hook_source, 'skipped_guard_disabled');
			return null;
		}
		if ($user_email === '') {
			self::log_registration_guard($hook_source, 'skipped_empty_email');
			return null;
		}

		$domain = self::email_registrant_domain($user_email);
		$ip     = function_exists('wp_span_checker_get_user_ip') ? wp_span_checker_get_user_ip() : '';

		if (! empty($cfg['rate_limit_enabled'])) {
			$rl_msg = self::rate_limit_message_if_blocked($cfg, $ip);
			if ($rl_msg !== null) {
				self::log_registration_guard(
					$hook_source,
					'blocked_rate_limit',
					array(
						'domain' => $domain,
						'ip'     => $ip,
					)
				);
				return $rl_msg;
			}
		}

		$cache_key = strtolower($user_email);
		if (array_key_exists($cache_key, self::$block_message_cache)) {
			$cached = self::$block_message_cache[$cache_key];
			self::log_registration_guard(
				$hook_source,
				null === $cached ? 'allowed_cached' : 'blocked_cached',
				array('domain' => $domain)
			);
			return $cached;
		}

		$validator = new Domain_Validator();
		$settings  = array(
			'is_webrisk'               => ! empty($cfg['use_webrisk']),
			'is_virustotal'            => ! empty($cfg['use_virustotal']),
			'require_dns_live'         => ! empty($cfg['require_dns_live']),
			'require_mx'               => ! empty($cfg['require_mx']),
			'mx_allow_a_fallback'      => ! empty($cfg['mx_allow_a_fallback']),
			'skip_https'               => ! empty($cfg['skip_https_check']),
		);

		$result = $validator->validate_email($user_email, 'registration', $ip, $settings);
		$msg    = empty($result['status']) ? (string) $result['message'] : null;

		if ($msg !== null && ! empty($cfg['rate_limit_enabled'])) {
			self::record_registration_failure($cfg, $ip, $msg);
			$msg = self::append_rate_reference_id($msg, $ip);
		}

		self::$block_message_cache[$cache_key] = $msg;

		if (null === $msg) {
			self::log_registration_guard($hook_source, 'allowed', array('domain' => $domain));
		} else {
			$attempt_line = self::append_attempt_context($cfg, $ip);
			$msg          = self::brand_registration_block_message($msg, $attempt_line);
			self::log_registration_guard(
				$hook_source,
				'blocked_validation',
				array(
					'domain'  => $domain,
					'message' => self::truncate_for_log($msg, 200),
				)
			);
			
			if (function_exists('wp_span_checker_record_strike')) {
				wp_span_checker_record_strike((string) $result['message'], 'registration', 0, $user_email);
			}
			
			self::maybe_redirect_registration_error($msg, $hook_source);
		}

		return $msg;
	}

	/**
	 * Add clear attempt context for end users (current day counter / limit).
	 */
	private static function append_attempt_context(array $cfg, string $ip): string
	{
		if (empty($cfg['rate_limit_enabled'])) {
			return '';
		}
		$state   = self::get_rate_state($ip);
		$current = (int) ($state['day_fails'] ?? 0);
		$max     = max(1, (int) ($cfg['rate_limit_max_per_day'] ?? 10));
		return wp_span_checker_get_error_message('reg_rate_limit_count', array($current, $max));
	}

	/**
	 * Map raw validator output to a plain-language failed-check explanation.
	 */
	private static function registration_failed_check_line(string $reason): string
	{
		$r = strtolower($reason);
		if (false !== strpos($r, 'does not resolve in dns')) {
			return wp_span_checker_get_error_message('reg_dns_failed');
		}
		if (false !== strpos($r, 'no mx')) {
			return wp_span_checker_get_error_message('reg_mx_failed');
		}
		if (false !== strpos($r, 'disposable')) {
			return wp_span_checker_get_error_message('reg_disposable');
		}
		if (false !== strpos($r, 'too many failed registration attempts')
			|| false !== strpos($r, 'daily registration attempt limit reached')) {
			return wp_span_checker_get_error_message('reg_rate_limit');
		}
		return wp_span_checker_get_error_message('reg_reputation_failed');
	}

	/**
	 * Wrap raw technical reason with a clearer branded user-facing message.
	 */
	private static function brand_registration_block_message(string $reason, string $attempt_line = ''): string
	{
		$parts   = array();
		$parts[] = wp_span_checker_get_error_message('reg_blocked_intro');
		$parts[] = self::registration_failed_check_line($reason);
		if ('' !== $attempt_line) {
			$parts[] = $attempt_line;
		}
		$parts[] = wp_span_checker_get_error_message('reg_contact_admin');
		return implode(' ', $parts);
	}

	/**
	 * Redirect browser registrations back to the form with a one-time error token.
	 */
	private static function maybe_redirect_registration_error(string $message, string $hook_source): void
	{
		if (self::$registration_redirect_done || $message === '') {
			return;
		}
		if (headers_sent() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
			return;
		}
		if (! isset($_SERVER['REQUEST_METHOD']) || 'POST' !== strtoupper((string) $_SERVER['REQUEST_METHOD'])) {
			return;
		}

		$target = wp_get_referer();
		if (! is_string($target) || '' === $target) {
			$script = isset($_SERVER['SCRIPT_NAME']) ? basename((string) $_SERVER['SCRIPT_NAME']) : '';
			if ('wp-login.php' === $script) {
				$target = wp_login_url() . '?action=register';
			} else {
				$target = home_url('/');
			}
		}

		$token = function_exists('wp_generate_password')
			? wp_generate_password(16, false, false)
			: bin2hex(random_bytes(8));

		set_transient(
			'wsc_rerr_' . $token,
			array(
				'title'   => wp_span_checker_get_error_message('reg_blocked_title'),
				'message' => $message,
				'hook'    => $hook_source,
			),
			5 * MINUTE_IN_SECONDS
		);

		self::$registration_redirect_done = true;
		wp_safe_redirect(add_query_arg('wsc_reg_err', $token, $target));
		exit;
	}

	/**
	 * Consume one-time registration error token from query string.
	 */
	public function consume_registration_error_query(): void
	{
		if (is_admin() || empty($_GET['wsc_reg_err'])) {
			return;
		}
		$token = sanitize_text_field(wp_unslash((string) $_GET['wsc_reg_err']));
		if (strlen($token) !== 16 || ! ctype_alnum($token)) {
			return;
		}
		$data = get_transient('wsc_rerr_' . $token);
		delete_transient('wsc_rerr_' . $token);
		if (! is_array($data) || empty($data['message'])) {
			return;
		}
		self::$frontend_registration_notice = array(
			'title'   => isset($data['title']) ? (string) $data['title'] : wp_span_checker_get_error_message('reg_blocked_title'),
			'message' => (string) $data['message'],
		);
	}

	/**
	 * Print a modern inline error on wp-login register page.
	 */
	public function print_registration_notice_login_form(): void
	{
		if (empty(self::$frontend_registration_notice)) {
			return;
		}
		echo '<div class="message register" style="border-left-color:#d63638;"><strong>'
			. esc_html(self::$frontend_registration_notice['title'])
			. '</strong><br>'
			. wp_kses_post(nl2br(esc_html(self::$frontend_registration_notice['message'])))
			. '</div>';
	}

	/**
	 * Fallback inline notice for custom front-end registration pages.
	 */
	public function maybe_print_registration_notice_fallback(): void
	{
		if (empty(self::$frontend_registration_notice)) {
			return;
		}
		if (! is_singular()) {
			return;
		}
		echo '<div class="wsc-registration-blocked-notice" role="alert" style="max-width:720px;margin:1rem auto;border-left:4px solid #d63638;background:#fcf0f1;padding:12px 14px;border-radius:4px;color:#1d2327;font-size:14px;line-height:1.45;">';
		echo '<strong style="display:block;margin-bottom:6px;">' . esc_html(self::$frontend_registration_notice['title']) . '</strong>';
		echo '<span>' . wp_kses_post(nl2br(esc_html(self::$frontend_registration_notice['message']))) . '</span>';
		echo '</div>';
	}

	/**
	 * SweetAlert popup for blocked registration redirects.
	 */
	public function enqueue_frontend_registration_blocked_assets(): void
	{
		if (empty(self::$frontend_registration_notice)) {
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
				'title'   => self::$frontend_registration_notice['title'],
				'message' => self::$frontend_registration_notice['message'],
				'ok'      => __('OK', 'wp-span-checker'),
			)
		);
		wp_add_inline_script(
			'wsc-public-sweetalert',
			'document.addEventListener("DOMContentLoaded",function(){var d=' . $payload . ';'
			. 'if(typeof Swal!=="undefined"){Swal.fire({icon:"error",title:d.title,text:d.message,confirmButtonText:d.ok});}'
			. 'if(window.history&&history.replaceState){var u=new URL(window.location.href);u.searchParams.delete("wsc_reg_err");'
			. 'history.replaceState(null,"",u.pathname+u.search+window.location.hash);}});',
			'after'
		);
	}

	/**
	 * @param string $email .
	 * @return string Domain part only (lowercase), empty if invalid.
	 */
	private static function email_registrant_domain(string $email): string
	{
		$at = strrpos($email, '@');
		if (false === $at) {
			return '';
		}
		return strtolower(substr($email, $at + 1));
	}

	/**
	 * @param string $text    .
	 * @param int    $max_len .
	 */
	private static function truncate_for_log(string $text, int $max_len): string
	{
		$t = preg_replace('/\s+/', ' ', $text);
		if (strlen($t) <= $max_len) {
			return $t;
		}
		return substr($t, 0, $max_len) . '…';
	}

	/**
	 * Writes one JSON line to PHP’s error log when {@see WP_DEBUG_LOG} or `WSC_REGISTRATION_GUARD_LOG` is enabled.
	 *
	 * @param string               $hook    Which code path ran (filter/action name).
	 * @param string               $outcome allowed|blocked_*|wpdb_insert_cancelled|user_removed|…
	 * @param array<string, mixed> $context Extra fields (domain, user_id, etc.).
	 */
	private static function log_registration_guard(string $hook, string $outcome, array $context = array()): void
	{
		$row = array_merge(
			array(
				'component' => 'registration_guard',
				'hook'      => $hook,
				'outcome'   => $outcome,
			),
			$context
		);

		$ip     = function_exists('wp_span_checker_get_user_ip') ? wp_span_checker_get_user_ip() : '';
		$domain = isset($context['domain']) ? (string) $context['domain'] : '';
		$status = 'success';
		if (0 === strpos($outcome, 'blocked') || false !== strpos($outcome, 'removed') || false !== strpos($outcome, 'cancelled')) {
			$status = 'failed';
		}

		$logger = new Logger();
		$logger->log(
			'registration_guard',
			(string) $ip,
			$domain,
			$status,
			wp_json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
		);

		if (self::is_registration_guard_file_log_enabled()) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log('[WP Span Checker] ' . wp_json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		}
	}

	private static function is_registration_guard_file_log_enabled(): bool
	{
		if (defined('WSC_REGISTRATION_GUARD_LOG')) {
			return (bool) WSC_REGISTRATION_GUARD_LOG;
		}
		return defined('WP_DEBUG_LOG') && WP_DEBUG_LOG;
	}

	/**
	 * @param array<string, mixed> $cfg .
	 * @param string               $ip  .
	 * @return string|null Message when blocked by rate limit.
	 */
	private static function rate_limit_message_if_blocked(array $cfg, string $ip): ?string
	{
		$state = self::get_rate_state($ip);
		$now   = time();

		if (! empty($state['locked_until']) && $now < (int) $state['locked_until']) {
			$ref = self::rate_reference_id($ip);
			$eta = human_time_diff($now, (int) $state['locked_until']);
			return sprintf(
				/* translators: 1: time until retry, 2: support reference id */
				__('Too many failed registration attempts from your network. Please try again in about %1$s. Reference: %2$s', 'wp-span-checker'),
				$eta,
				$ref
			);
		}

		$today = function_exists('wp_date') ? wp_date('Y-m-d') : gmdate('Y-m-d');
		if (
			! empty($state['day']) && (string) $state['day'] === $today
			&& (int) ($state['day_fails'] ?? 0) >= (int) $cfg['rate_limit_max_per_day']
		) {
			$ref = self::rate_reference_id($ip);
			return sprintf(
				/* translators: %s: support reference id */
				__('Daily registration attempt limit reached for your network. Please try again tomorrow. Reference: %s', 'wp-span-checker'),
				$ref
			);
		}

		return null;
	}

	/**
	 * @param array<string, mixed> $cfg .
	 * @param string               $ip  .
	 * @return array<string, int|string>
	 */
	private static function get_rate_state(string $ip): array
	{
		$key   = self::RATE_TRANSIENT_PREFIX . md5($ip . '|registration');
		$state = get_transient($key);
		return is_array($state) ? $state : array();
	}

	/**
	 * @param array<string, mixed> $cfg     .
	 * @param string               $ip      .
	 * @param string               $message Failure reason (for future logging).
	 */
	private static function record_registration_failure(array $cfg, string $ip, string $message): void
	{
		unset($message);

		$key   = self::RATE_TRANSIENT_PREFIX . md5($ip . '|registration');
		$now   = time();
		$state = self::get_rate_state($ip);

		if (! empty($state['locked_until']) && $now >= (int) $state['locked_until']) {
			unset($state['locked_until']);
		}

		$today = function_exists('wp_date') ? wp_date('Y-m-d') : gmdate('Y-m-d');
		if (empty($state['day']) || (string) $state['day'] !== $today) {
			$state['day']       = $today;
			$state['day_fails'] = 0;
		}

		$lockout = max(60, (int) $cfg['rate_limit_lockout_seconds']);
		$max_b   = max(1, (int) $cfg['rate_limit_max_burst']);

		if (empty($state['burst_start']) || ($now - (int) $state['burst_start']) > $lockout) {
			$state['burst_start'] = $now;
			$state['burst_fails'] = 0;
		}

		$state['burst_fails'] = (int) ($state['burst_fails'] ?? 0) + 1;
		$state['day_fails']     = (int) ($state['day_fails'] ?? 0) + 1;

		if ($state['burst_fails'] >= $max_b) {
			$state['locked_until'] = $now + $lockout;
			$state['burst_fails']  = 0;
			$state['burst_start']  = 0;
		}

		set_transient($key, $state, 2 * DAY_IN_SECONDS);
	}

	/**
	 * Short id for user-facing messages (not a secret; correlates logs on the server by IP + date).
	 */
	private static function rate_reference_id(string $ip): string
	{
		$day = function_exists('wp_date') ? wp_date('Ymd') : gmdate('Ymd');
		return strtoupper(substr(sha1($ip . '|' . $day . '|wsc_rg'), 0, 10));
	}

	private static function append_rate_reference_id(string $message, string $ip): string
	{
		$ref = self::rate_reference_id($ip);
		return $message . ' ' . sprintf(
			/* translators: %s: support reference id */
			__('(Reference: %s)', 'wp-span-checker'),
			$ref
		);
	}

	/**
	 * Optional integration for custom registration code: same rules as the guard, for use **before**
	 * `wp_insert_user()` when a third-party plugin exposes an early PHP hook but does not use core register.
	 *
	 * @param string $email Email to validate.
	 * @return string|null Rejection message if blocked; null if allowed, guard disabled, or email empty.
	 */
	public static function rejection_message_for_registration_email(string $email): ?string
	{
		return self::get_block_message_for_email($email, 'public_api_rejection_message');
	}

	/**
	 * Covers wp_create_user / wp_insert_user (page builders often bypass registration_errors).
	 *
	 * @param array<string, mixed> $data     .
	 * @param bool                 $update   .
	 * @param int|null             $user_id  .
	 * @param array<string, mixed> $userdata .
	 * @return array<string, mixed>
	 */
	public function filter_wp_pre_insert_user_data($data, $update, $user_id, $userdata)
	{
		self::log_registration_guard(
			'wp_pre_insert_user_data',
			'hook_invoked',
			array(
				'is_update' => (bool) $update,
				'has_email' => isset($userdata['user_email']) || isset($data['user_email']),
			)
		);

		if ($update || ! is_array($data)) {
			return $data;
		}

		if (defined('WP_IMPORTING') && WP_IMPORTING) {
			return $data;
		}

		if (is_user_logged_in() && current_user_can('create_users')) {
			return $data;
		}

		$email = '';
		if (isset($userdata['user_email']) && is_string($userdata['user_email'])) {
			$email = $userdata['user_email'];
		} elseif (isset($data['user_email']) && is_string($data['user_email'])) {
			$email = $data['user_email'];
		}

		$msg = self::get_block_message_for_email($email, 'wp_pre_insert_user_data');
		if ($msg !== null) {
			self::$wp_pre_insert_user_error       = new WP_Error('wsc_registration_email', $msg);
			self::$empty_data_message_replacement = $msg;
			self::$wp_pre_insert_user_blocked     = true;
			return array();
		}

		self::$wp_pre_insert_user_blocked = false;
		return $data;
	}

	/**
	 * Runs last on wp_pre_insert_user_data: return a real WP_Error so patched core can pass it through
	 * {@see wp_insert_user()} (add `if ( is_wp_error( $data ) ) { return $data; }` after the filter in user.php).
	 *
	 * @param array<string, mixed>|\WP_Error $data     .
	 * @param bool                           $update   .
	 * @param int|null                       $user_id  .
	 * @param array<string, mixed>           $userdata .
	 * @return array<string, mixed>|\WP_Error
	 */
	public function filter_wp_pre_insert_user_data_return_wp_error($data, $update, $user_id, $userdata)
	{
		self::log_registration_guard(
			'wp_pre_insert_user_data_return_wp_error',
			'hook_invoked',
			array(
				'is_update'     => (bool) $update,
				'data_is_error' => is_wp_error($data),
			)
		);

		unset($userdata, $user_id);

		if ($update) {
			return $data;
		}

		if (is_wp_error($data)) {
			self::$wp_pre_insert_user_error = null;
			self::$wp_pre_insert_user_blocked = false;
			return $data;
		}

		if (! (self::$wp_pre_insert_user_error instanceof WP_Error)) {
			return $data;
		}

		if (self::$wp_pre_insert_user_blocked || (is_array($data) && array() === $data)) {
			$err = self::$wp_pre_insert_user_error;
			self::$wp_pre_insert_user_error = null;
			self::$wp_pre_insert_user_blocked = false;
			return $err;
		}

		self::$wp_pre_insert_user_error = null;
		self::$wp_pre_insert_user_blocked = false;
		return $data;
	}

	/**
	 * @param string $translated .
	 * @param string $text       .
	 * @param string $domain     .
	 * @return string
	 */
	public function filter_gettext_empty_data_replacement($translated, $text, $domain)
	{
		if (self::$empty_data_message_replacement === null) {
			return $translated;
		}
		if ('default' !== $domain || 'Not enough data to create this user.' !== $text) {
			return $translated;
		}
		$out                                 = self::$empty_data_message_replacement;
		self::$empty_data_message_replacement = null;
		self::$wp_pre_insert_user_error      = null;
		self::$wp_pre_insert_user_blocked    = false;
		return $out;
	}

	/**
	 * Blocks INSERT/REPLACE into {$wpdb->users} that bypass wp_insert_user (e.g. $wpdb->insert or raw SQL).
	 * Runs on the global {@see 'query'} filter so all queries pass through here.
	 *
	 * @param string $query SQL.
	 * @return string Empty string cancels the query (wpdb returns false).
	 */
	public function filter_wpdb_query_block_raw_user_insert($query)
	{
		if (! is_string($query) || $query === '') {
			return $query;
		}

		if (self::$wpdb_query_guard_active) {
			return $query;
		}

		global $wpdb;
		if (! isset($wpdb->users) || $wpdb->users === '') {
			return $query;
		}

		$users_table = $wpdb->users;
		if (stripos($query, $users_table) === false) {
			return $query;
		}

		$q = ltrim($query);
		if (! preg_match('/^(INSERT|REPLACE)\s+INTO\s+/i', $q)) {
			return $query;
		}

		if (! preg_match('/^(INSERT|REPLACE)\s+INTO\s+[`"\']?' . preg_quote($users_table, '/') . '[`"\']?\s+/i', $q)) {
			return $query;
		}

		self::log_registration_guard(
			'wpdb_query_filter',
			'hook_invoked',
			array(
				'statement' => strtoupper((string) (preg_match('/^(INSERT|REPLACE)\b/i', $q, $m) ? $m[1] : 'UNKNOWN')),
			)
		);

		self::$wpdb_query_guard_active = true;
		try {
			$cfg = self::get();
			if (empty($cfg['enabled'])) {
				return $query;
			}

			if (defined('WP_IMPORTING') && WP_IMPORTING) {
				return $query;
			}

			// Only after init: loading the current user runs SQL and must not run inside an unguarded query filter pass.
			if (
				did_action('init') && function_exists('is_user_logged_in') && is_user_logged_in()
				&& function_exists('current_user_can') && current_user_can('create_users')
			) {
				return $query;
			}

			$email = self::parse_user_email_from_users_set_syntax_sql($q, $users_table);
			if (null === $email) {
				$email = self::parse_user_email_from_users_insert_sql($q, $users_table);
			}
			if ($email === null) {
				return $query;
			}

			$msg = self::get_block_message_for_email($email, 'wpdb_query_filter');
			if ($msg !== null) {
				return '';
			}
		} finally {
			self::$wpdb_query_guard_active = false;
		}

		return $query;
	}

	/**
	 * Last-resort: delete the user if validation failed but the row was created anyway.
	 *
	 * @param int                  $user_id  .
	 * @param array<string, mixed> $userdata .
	 */
	public function action_user_register_enforce_guard($user_id, $userdata)
	{
		self::log_registration_guard(
			'user_register',
			'hook_invoked',
			array(
				'user_id'   => (int) $user_id,
				'has_email' => isset($userdata['user_email']) || isset($userdata['user_login']),
			)
		);

		if (defined('WP_IMPORTING') && WP_IMPORTING) {
			return;
		}

		if (is_user_logged_in() && current_user_can('create_users')) {
			return;
		}

		$cfg = self::get();
		if (empty($cfg['enabled'])) {
			return;
		}

		$user_id = (int) $user_id;
		$user    = get_userdata($user_id);
		if (! $user) {
			return;
		}

		$msg = self::get_block_message_for_email((string) $user->user_email, 'user_register');
		if ($msg === null) {
			return;
		}

		self::log_registration_guard(
			'user_register',
			'user_removed_after_insert',
			array(
				'user_id' => $user_id,
				'domain'  => self::email_registrant_domain((string) $user->user_email),
			)
		);

		if (! function_exists('wp_delete_user')) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
		}

		wp_delete_user($user_id);
	}

	/**
	 * MySQL: INSERT INTO tbl SET col=val, ...
	 *
	 * @param string $query       Full SQL.
	 * @param string $users_table $wpdb->users.
	 * @return string|null Email, '' if empty assignment, null if not SET syntax / no user_email.
	 */
	private static function parse_user_email_from_users_set_syntax_sql(string $query, string $users_table): ?string
	{
		if (! preg_match('/^(INSERT|REPLACE)\s+INTO\s+[`"\']?' . preg_quote($users_table, '/') . '[`"\']?\s+SET\s+/is', $query)) {
			return null;
		}
		if (preg_match('/\buser_email\s*=\s*\'((?:[^\'\\\\]|\\\\.|\'\')*)\'/is', $query, $m)) {
			return str_replace("''", "'", stripslashes($m[1]));
		}
		if (preg_match('/\buser_email\s*=\s*"((?:[^"\\\\]|\\\\.)*)"/is', $query, $m)) {
			return stripslashes($m[1]);
		}
		if (preg_match('/\buser_email\s*=\s*(NULL)\b/is', $query, $m)) {
			return '';
		}
		if (preg_match('/\buser_email\s*=\s*([^,\s]+)/is', $query, $m)) {
			return trim($m[1], "'\"\t\n\r ");
		}
		return null;
	}

	/**
	 * @param string $query       Full INSERT|REPLACE statement.
	 * @param string $users_table $wpdb->users.
	 * @return string|null Email to validate, or null if parsing failed / no user_email column.
	 */
	private static function parse_user_email_from_users_insert_sql(string $query, string $users_table): ?string
	{
		if (! preg_match('/^(INSERT|REPLACE)\s+INTO\s+[`"\']?' . preg_quote($users_table, '/') . '[`"\']?\s+\(\s*(.+?)\s*\)\s*VALUES\s*\(/is', $query, $col_match)) {
			return null;
		}

		$cols = array_map(
			static function ($c) {
				return strtolower(trim($c, "` \t\n\r\"'"));
			},
			preg_split('/\s*,\s*/', $col_match[1])
		);

		$idx = false;
		foreach ($cols as $i => $name) {
			if ('user_email' === $name) {
				$idx = (int) $i;
				break;
			}
		}

		if (false === $idx) {
			return null;
		}

		$values_start = stripos($query, 'VALUES');
		if (false === $values_start) {
			return null;
		}
		$lp = strpos($query, '(', $values_start);
		if (false === $lp) {
			return null;
		}

		$inner = self::extract_sql_parenthesized_values($query, $lp);
		if ($inner === null) {
			return null;
		}

		$tokens = self::tokenize_sql_values_list($inner);
		if (! isset($tokens[$idx])) {
			return null;
		}

		$raw = $tokens[$idx];
		if (null === $raw || '' === $raw) {
			return '';
		}

		return (string) $raw;
	}

	/**
	 * @param string $query Full SQL.
	 * @param int    $open  Index of opening '(' for VALUES list.
	 * @return string|null Inner content between VALUES ( ... ).
	 */
	private static function extract_sql_parenthesized_values(string $query, int $open): ?string
	{
		$len = strlen($query);
		$i   = $open + 1;
		$depth = 1;
		$in_string = false;
		$escape    = false;

		while ($i < $len && $depth > 0) {
			$ch = $query[$i];
			if ($in_string) {
				if ($escape) {
					$escape = false;
					++$i;
					continue;
				}
				if ('\\' === $ch) {
					$escape = true;
					++$i;
					continue;
				}
				if ("'" === $ch) {
					if ($i + 1 < $len && "'" === $query[$i + 1]) {
						$i += 2;
						continue;
					}
					$in_string = false;
					++$i;
					continue;
				}
				++$i;
				continue;
			}
			if ("'" === $ch) {
				$in_string = true;
				++$i;
				continue;
			}
			if ('(' === $ch) {
				++$depth;
				++$i;
				continue;
			}
			if (')' === $ch) {
				--$depth;
				++$i;
				continue;
			}
			++$i;
		}

		if (0 !== $depth) {
			return null;
		}

		return substr($query, $open + 1, $i - $open - 2);
	}

	/**
	 * @param string $inner Inside VALUES ( ... ).
	 * @return array<int, string|null>
	 */
	private static function tokenize_sql_values_list(string $inner): array
	{
		$tokens = array();
		$len    = strlen($inner);
		$i      = 0;

		while ($i < $len) {
			while ($i < $len && false !== strpos(" \t\n\r", $inner[$i])) {
				++$i;
			}
			if ($i >= $len) {
				break;
			}

			if ("'" === $inner[$i]) {
				++$i;
				$buf = '';
				while ($i < $len) {
					$ch = $inner[$i];
					if ('\\' === $ch && $i + 1 < $len) {
						$buf .= $inner[$i + 1];
						$i   += 2;
						continue;
					}
					if ("'" === $ch) {
						if ($i + 1 < $len && "'" === $inner[$i + 1]) {
							$buf .= "'";
							$i   += 2;
							continue;
						}
						++$i;
						$tokens[] = $buf;
						break;
					}
					$buf .= $ch;
					++$i;
				}
				while ($i < $len && false !== strpos(" \t\n\r", $inner[$i])) {
					++$i;
				}
				if ($i < $len && ',' === $inner[$i]) {
					++$i;
				}
				continue;
			}

			if ($i + 3 < $len && 0 === strcasecmp(substr($inner, $i, 4), 'NULL')) {
				$after = $inner[$i + 4] ?? ' ';
				if (! ctype_alnum($after) && '_' !== $after) {
					$tokens[] = null;
					$i       += 4;
					while ($i < $len && false !== strpos(" \t\n\r", $inner[$i])) {
						++$i;
					}
					if ($i < $len && ',' === $inner[$i]) {
						++$i;
					}
					continue;
				}
			}

			$start = $i;
			while ($i < $len && ',' !== $inner[$i]) {
				++$i;
			}
			$tokens[] = trim(substr($inner, $start, $i - $start));
			if ($i < $len && ',' === $inner[$i]) {
				++$i;
			}
		}

		return $tokens;
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get(): array
	{
		$raw = get_option(self::OPTION_KEY, array());
		if (! is_array($raw)) {
			$raw = array();
		}
		return array_merge(self::defaults(), $raw);
	}

	/**
	 * @param array<string, mixed> $data .
	 */
	public static function update(array $data): void
	{
		$d = self::defaults();
		$merged = array_merge($d, self::get(), $data);
		$merged['enabled']                    = ! empty($merged['enabled']);
		$merged['use_webrisk']                = ! empty($merged['use_webrisk']);
		$merged['use_virustotal']             = ! empty($merged['use_virustotal']);
		$merged['require_dns_live']          = ! empty($merged['require_dns_live']);
		$merged['require_mx']                 = ! empty($merged['require_mx']);
		$merged['mx_allow_a_fallback']        = ! empty($merged['mx_allow_a_fallback']);
		$merged['skip_https_check']           = ! empty($merged['skip_https_check']);
		$merged['rate_limit_enabled']         = ! empty($merged['rate_limit_enabled']);
		$merged['rate_limit_max_burst']       = max(1, (int) ($merged['rate_limit_max_burst'] ?? 5));
		$merged['rate_limit_lockout_seconds'] = max(60, (int) ($merged['rate_limit_lockout_seconds'] ?? 18000));
		$merged['rate_limit_max_per_day']     = max(1, (int) ($merged['rate_limit_max_per_day'] ?? 10));
		update_option(self::OPTION_KEY, array_merge($d, $merged), false);
	}
}
