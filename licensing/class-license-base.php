<?php
/**
 * License validation core — VMS Elements license API.
 *
 * @see https://vmselements.com/api/licenses
 * @package VMS_Span_Checker
 */

namespace VMS_Span_Checker\Licensing;

use stdClass;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * HTTP client + local license record storage.
 */
class License_Base {

	const REFRESH_INTERVAL = 300;

	/**
	 * API base URL (no trailing slash).
	 *
	 * @var string
	 */
	private $api_base;

	/**
	 * Product slug stored in local metadata.
	 *
	 * @var string
	 */
	private $product_id;

	/**
	 * Option-key namespace used to derive per-site option names.
	 *
	 * @var string
	 */
	private $product_base;

	/**
	 * Salt for the per-install option name (not sent to the API).
	 *
	 * @var string
	 */
	private $storage_salt;

	/**
	 * Optional contact email captured during activation.
	 *
	 * @var string
	 */
	private $email_address = '';

	/**
	 * Plugin file used to derive the per-domain hash.
	 *
	 * @var string
	 */
	private $plugin_file;

	/**
	 * Plugin version reported on activation metadata.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Singleton.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Callbacks fired when the local license record is deleted.
	 *
	 * @var array<int, callable>
	 */
	private static $on_delete_license = array();

	/**
	 * @param string $plugin_file Absolute path to the host plugin's main file.
	 */
	public function __construct( $plugin_file = '' ) {
		$this->api_base     = defined( 'VMS_SPAN_CHECKER_LICENSE_API' ) ? VMS_SPAN_CHECKER_LICENSE_API : 'https://vmselements.com/api/licenses';
		$this->product_id   = defined( 'VMS_SPAN_CHECKER_PRODUCT_ID' ) ? VMS_SPAN_CHECKER_PRODUCT_ID : 'vms-span-checker-pro';
		$this->product_base = defined( 'VMS_SPAN_CHECKER_PRODUCT_BASE' ) ? VMS_SPAN_CHECKER_PRODUCT_BASE : 'vms_span_checker_pro_options';
		$this->storage_salt = defined( 'VMS_SPAN_CHECKER_LICENSE_STORAGE_SALT' ) ? VMS_SPAN_CHECKER_LICENSE_STORAGE_SALT : 'vms_license_api_v1';

		if ( empty( $plugin_file ) && defined( 'VMS_SPAN_CHECKER_FILE' ) ) {
			$plugin_file = VMS_SPAN_CHECKER_FILE;
		}
		$this->plugin_file = $plugin_file;
		$this->version     = defined( 'VMS_SPAN_CHECKER_VERSION' ) ? VMS_SPAN_CHECKER_VERSION : '1.0.0';

		$this->maybe_migrate_legacy_record();
	}

	/**
	 * Singleton accessor.
	 */
	public static function instance( $plugin_file = '' ): self {
		if ( null === self::$instance ) {
			self::$instance = new self( $plugin_file );
		}
		return self::$instance;
	}

	/**
	 * Set the email address stored with activation metadata.
	 */
	public function set_email_address( string $email ): void {
		$this->email_address = $email;
	}

	/**
	 * Register a callback to run when the local license is removed.
	 *
	 * @param callable $func Callback (no args).
	 */
	public static function add_on_delete( $func ): void {
		if ( is_callable( $func ) ) {
			self::$on_delete_license[] = $func;
		}
	}

	/**
	 * Activate / refresh a license (server-side validate).
	 *
	 * @param string $purchase_key User-entered license key.
	 * @param string $error_msg    [out] Error message on failure.
	 * @param object $response_obj [out] Decoded license info on success.
	 * @param bool   $force          Skip cached grace window.
	 * @return bool
	 */
	public function check_wp_plugin( string $purchase_key, string &$error_msg = '', &$response_obj = null, bool $force = false ): bool {
		if ( '' === $purchase_key ) {
			$this->remove_local_license_record();
			$error_msg = '';
			return false;
		}

		$cached = $this->get_local_license_record();

		if ( ! $force && ! empty( $cached ) ) {
			if ( $this->record_is_expired( $cached ) ) {
				$force = true;
			} elseif (
				! empty( $cached->is_valid )
				&& ! empty( $cached->next_request )
				&& (int) $cached->next_request > time()
				&& ! empty( $cached->license_key )
				&& $purchase_key === $cached->license_key
			) {
				$response_obj = clone $cached;
				unset( $response_obj->next_request );
				return true;
			}
		}

		$block = $this->fetch_block_status( $purchase_key );
		if ( ! empty( $block['blocked'] ) ) {
			$this->remove_local_license_record();
			$error_msg = $this->blocked_message( $block );
			return false;
		}

		$domain = $this->normalize_domain( $this->get_site_url() );
		$body   = $this->api_post(
			'/activate',
			array(
				'license_key' => $purchase_key,
				'domain'      => $domain,
				'metadata'    => array(
					'product_version' => $this->version,
					'product_id'      => $this->product_id,
					'email'           => '' !== $this->email_address ? $this->email_address : (string) get_option( 'admin_email', '' ),
				),
			)
		);

		if ( $this->response_is_activation_success( $body ) ) {
			$validated = $this->fetch_validate( $purchase_key, $domain );
			if ( ! empty( $validated['valid'] ) ) {
				$record       = $this->build_record_from_api( $purchase_key, $validated, isset( $body['message'] ) ? (string) $body['message'] : '' );
				$response_obj = clone $record;
				unset( $response_obj->next_request );
				return true;
			}
			$record       = $this->build_minimal_record( $purchase_key, isset( $body['message'] ) ? (string) $body['message'] : '' );
			$response_obj = clone $record;
			unset( $response_obj->next_request );
			return true;
		}

		if ( $this->try_grace_period( $cached, $response_obj ) ) {
			return true;
		}

		$this->remove_local_license_record();
		$error_msg = $this->extract_error_message( $body, __( 'License activation failed.', 'vms-span-checker' ) );
		return false;
	}

	/**
	 * Validate an existing license (cron / heartbeat).
	 *
	 * @param string $purchase_key License key.
	 * @param string $error_msg    [out] Error message.
	 * @param object $response_obj [out] License info.
	 * @param bool   $force        Ignore next_request throttle.
	 * @return bool
	 */
	public function validate_license( string $purchase_key, string &$error_msg = '', &$response_obj = null, bool $force = false ): bool {
		if ( '' === $purchase_key ) {
			$error_msg = __( 'No license key.', 'vms-span-checker' );
			return false;
		}

		$cached = $this->get_local_license_record();

		if ( ! $force && ! empty( $cached ) ) {
			if ( $this->record_is_expired( $cached ) ) {
				$force = true;
			} elseif (
				! empty( $cached->is_valid )
				&& ! empty( $cached->next_request )
				&& (int) $cached->next_request > time()
				&& ! empty( $cached->license_key )
				&& $purchase_key === $cached->license_key
			) {
				$response_obj = clone $cached;
				unset( $response_obj->next_request );
				return true;
			}
		}

		$block = $this->fetch_block_status( $purchase_key );
		if ( ! empty( $block['blocked'] ) ) {
			$this->remove_local_license_record();
			$error_msg = $this->blocked_message( $block );
			return false;
		}

		$domain    = $this->normalize_domain( $this->get_site_url() );
		$validated = $this->fetch_validate( $purchase_key, $domain );

		if ( ! empty( $validated['valid'] ) ) {
			$record       = $this->build_record_from_api( $purchase_key, $validated );
			$response_obj = clone $record;
			unset( $response_obj->next_request );
			return true;
		}

		if ( $this->is_license_blocked_response( $validated ) ) {
			$this->remove_local_license_record();
			$error_msg = $this->extract_error_message( $validated, __( 'License is blocked. Contact support@vmselements.com.', 'vms-span-checker' ) );
			return false;
		}

		if ( $this->try_grace_period( $cached, $response_obj ) ) {
			return true;
		}

		$this->remove_local_license_record();
		$error_msg = $this->extract_error_message( $validated, __( 'License is not valid for this site.', 'vms-span-checker' ) );
		return false;
	}

	/**
	 * Deactivate license on the server and remove the local record.
	 */
	public function remove_license_key( string &$message = '' ): bool {
		$cached = $this->get_local_license_record();
		if ( empty( $cached ) || empty( $cached->license_key ) ) {
			$this->remove_local_license_record();
			$message = __( 'License removed.', 'vms-span-checker' );
			return true;
		}

		$key    = (string) $cached->license_key;
		$domain = $this->normalize_domain( $this->get_site_url() );
		$body   = $this->api_post(
			'/deactivate',
			array(
				'license_key' => $key,
				'domain'      => $domain,
			)
		);

		if ( ! empty( $body['success'] ) ) {
			$message = isset( $body['message'] ) ? (string) $body['message'] : __( 'License deactivated.', 'vms-span-checker' );
			$this->remove_local_license_record();
			return true;
		}

		$message = $this->extract_error_message( $body, __( 'License deactivated locally.', 'vms-span-checker' ) );
		$this->remove_local_license_record();
		return true;
	}

	/**
	 * Public accessor used by License_Manager.
	 *
	 * @return object|null
	 */
	public function get_register_info() {
		return $this->get_local_license_record();
	}

	/**
	 * Normalize a URL or hostname to lowercase host without www.
	 */
	public function normalize_domain( string $input ): string {
		$input = trim( $input );
		if ( '' === $input ) {
			return '';
		}
		if ( false === strpos( $input, '://' ) ) {
			$input = 'https://' . $input;
		}
		$host = wp_parse_url( $input, PHP_URL_HOST );
		if ( ! is_string( $host ) || '' === $host ) {
			return '';
		}
		$host = strtolower( $host );
		if ( 0 === strpos( $host, 'www.' ) ) {
			$host = substr( $host, 4 );
		}
		return $host;
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Reuse the cached record if the API is unreachable (max 2 tries).
	 */
	private function try_grace_period( $cached, &$response_obj ): bool {
		if ( empty( $cached ) || empty( $cached->is_valid ) ) {
			return false;
		}
		$tried = isset( $cached->tried ) ? (int) $cached->tried : 0;
		if ( $tried > 2 ) {
			return false;
		}
		$cached->next_request = time() + self::REFRESH_INTERVAL;
		$cached->tried        = $tried + 1;
		$response_obj         = clone $cached;
		unset( $response_obj->next_request, $response_obj->tried );
		$this->save_local_license_record( $cached );
		return true;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function fetch_block_status( string $license_key ): array {
		$body = $this->api_post( '/block-status', array( 'license_key' => $license_key ) );
		return is_array( $body ) ? $body : array();
	}

	private function fetch_validate( string $license_key, string $domain ): array {
		$body = $this->api_post(
			'/validate',
			array(
				'license_key' => $license_key,
				'domain'      => $domain,
			)
		);
		return is_array( $body ) ? $body : array();
	}

	/**
	 * Report a failed activation attempt (5 strikes = block).
	 *
	 * @return array<string, mixed>
	 */
	public function report_strike( string $license_key, string $reason ): array {
		$body = $this->api_post(
			'/strike',
			array(
				'license_key' => $license_key,
				'domain'      => $this->normalize_domain( $this->get_site_url() ),
				'reason'      => $reason,
			)
		);
		return is_array( $body ) ? $body : array();
	}

	/**
	 * @param array<string, mixed> $body Response body.
	 */
	private function response_is_activation_success( array $body ): bool {
		return ! empty( $body['success'] );
	}

	/**
	 * @param array<string, mixed> $body Response body.
	 */
	private function is_license_blocked_response( array $body ): bool {
		return ! empty( $body['blocked'] ) || ( isset( $body['code'] ) && 'LICENSE_BLOCKED' === (string) $body['code'] );
	}

	/**
	 * @param array<string, mixed> $body Response body.
	 */
	private function blocked_message( array $body ): string {
		if ( ! empty( $body['blocked_reason'] ) ) {
			return (string) $body['blocked_reason'];
		}
		if ( ! empty( $body['message'] ) ) {
			return (string) $body['message'];
		}
		$contact = ! empty( $body['contact'] ) ? (string) $body['contact'] : 'support@vmselements.com';
		return sprintf(
			/* translators: %s: support email */
			__( 'License blocked after repeated failed activations. Contact %s.', 'vms-span-checker' ),
			$contact
		);
	}

	/**
	 * @param array<string, mixed> $body Response body.
	 */
	private function extract_error_message( array $body, string $fallback ): string {
		if ( ! empty( $body['error'] ) ) {
			return (string) $body['error'];
		}
		if ( ! empty( $body['message'] ) ) {
			return (string) $body['message'];
		}
		return $fallback;
	}

	/**
	 * @param array<string, mixed> $api Validate/refresh payload.
	 */
	private function build_record_from_api( string $license_key, array $api, string $msg = '' ): stdClass {
		$record                = new stdClass();
		$record->is_valid      = true;
		$record->license_key   = $license_key;
		$record->license_title = __( 'VMS Span Checker Pro', 'vms-span-checker' );
		$record->expire_date   = $this->format_expiration( $api['expiration_date'] ?? null );
		$record->support_end   = 'No Support';
		$record->renew_link    = 'https://vmselements.com/dashboard/user/licenses';
		$record->domains_used  = isset( $api['sites_used'] ) ? (int) $api['sites_used'] : 0;
		$record->domains_limit = $this->parse_sites_allowed( $api['sites_allowed'] ?? null );
		$record->msg           = $msg;
		$record->next_request  = time() + self::REFRESH_INTERVAL;
		$record->activated_at  = time();
		$record->tried         = 0;
		$record->blocked       = false;
		$this->save_local_license_record( $record );
		return $record;
	}

	private function build_minimal_record( string $license_key, string $msg = '' ): stdClass {
		$record                = new stdClass();
		$record->is_valid      = true;
		$record->license_key   = $license_key;
		$record->license_title = __( 'VMS Span Checker Pro', 'vms-span-checker' );
		$record->expire_date   = 'No Expiry';
		$record->support_end   = 'No Support';
		$record->renew_link    = 'https://vmselements.com/dashboard/user/licenses';
		$record->domains_used  = 0;
		$record->domains_limit = 0;
		$record->msg           = $msg;
		$record->next_request  = time() + self::REFRESH_INTERVAL;
		$record->activated_at  = time();
		$record->tried         = 0;
		$record->blocked       = false;
		$this->save_local_license_record( $record );
		return $record;
	}

	/**
	 * @param mixed $sites_allowed API value (int|string "unlimited").
	 */
	private function parse_sites_allowed( $sites_allowed ): int {
		if ( null === $sites_allowed || '' === $sites_allowed ) {
			return 0;
		}
		if ( is_numeric( $sites_allowed ) ) {
			return (int) $sites_allowed;
		}
		$s = strtolower( (string) $sites_allowed );
		if ( 'unlimited' === $s ) {
			return 0;
		}
		return (int) $sites_allowed;
	}

	/**
	 * @param mixed $expiration ISO date or null.
	 */
	private function format_expiration( $expiration ): string {
		if ( null === $expiration || '' === $expiration ) {
			return 'No Expiry';
		}
		$ts = strtotime( (string) $expiration );
		if ( false === $ts ) {
			return 'No Expiry';
		}
		return gmdate( 'Y-m-d', $ts );
	}

	private function record_is_expired( $record ): bool {
		if ( ! is_object( $record ) || empty( $record->expire_date ) ) {
			return false;
		}
		$exp = strtolower( (string) $record->expire_date );
		if ( 'no expiry' === $exp || 'unlimited' === $exp ) {
			return false;
		}
		$ts = strtotime( (string) $record->expire_date );
		return false !== $ts && $ts < time();
	}

	/**
	 * POST JSON to the license API.
	 *
	 * @param string               $path Relative path e.g. `/activate`.
	 * @param array<string, mixed> $payload Request body.
	 * @return array<string, mixed>
	 */
	private function api_post( string $path, array $payload ): array {
		$url  = rtrim( $this->api_base, '/' ) . '/' . ltrim( $path, '/' );
		$site = $this->get_site_url();

		$args = array(
			'method'  => 'POST',
			'timeout' => 20,
			'headers' => array(
				'Content-Type' => 'application/json',
				'Referer'      => $site,
				'Origin'       => home_url( '/' ),
			),
			'body'    => wp_json_encode( $payload ),
		);

		$response = wp_remote_post( $url, $args );
		if ( is_wp_error( $response ) ) {
			return array(
				'valid'   => false,
				'success' => false,
				'error'   => $response->get_error_message(),
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$raw  = (string) wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			return array(
				'valid'   => false,
				'success' => false,
				'error'   => sprintf(
					/* translators: %d: HTTP status code */
					__( 'License server returned %d.', 'vms-span-checker' ),
					$code
				),
			);
		}

		if ( 403 === $code && $this->is_license_blocked_response( $data ) ) {
			$data['blocked'] = true;
		}

		return $data;
	}

	private function get_site_url(): string {
		if ( function_exists( 'site_url' ) ) {
			return (string) site_url();
		}
		return function_exists( 'home_url' ) ? (string) home_url( '/' ) : '';
	}

	private function get_record_key(): string {
		return hash(
			'crc32b',
			$this->get_site_url() . $this->plugin_file . $this->product_id . $this->product_base . $this->storage_salt . 'LIC'
		);
	}

	private function get_legacy_record_key(): string {
		$legacy_key = defined( 'VMS_SPAN_CHECKER_LICENSE_KEY' ) ? VMS_SPAN_CHECKER_LICENSE_KEY : 'Vms5pAn2026PrOK1';
		return hash(
			'crc32b',
			$this->get_site_url() . $this->plugin_file . $this->product_id . $this->product_base . $legacy_key . 'LIC'
		);
	}

	/**
	 * Move encrypted license blob from the old Worker-based option name.
	 */
	private function maybe_migrate_legacy_record(): void {
		if ( null !== $this->get_local_license_record() ) {
			return;
		}
		$legacy_key = $this->get_legacy_record_key();
		$payload    = get_option( $legacy_key, null );
		if ( empty( $payload ) ) {
			return;
		}
		$plain = $this->decrypt_legacy( (string) $payload, $this->get_site_url() );
		$record = @unserialize( $plain ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
		if ( ! is_object( $record ) || empty( $record->license_key ) ) {
			delete_option( $legacy_key );
			return;
		}
		$this->save_local_license_record( $record );
		delete_option( $legacy_key );
	}

	private function save_local_license_record( $record ): void {
		$serial   = serialize( $record );
		$payload  = $this->encrypt_local( $serial, $this->get_site_url() );
		$key_name = $this->get_record_key();
		if ( ! update_option( $key_name, $payload ) ) {
			add_option( $key_name, $payload );
		}
	}

	/**
	 * @return object|null
	 */
	private function get_local_license_record() {
		$payload = get_option( $this->get_record_key(), null );
		if ( empty( $payload ) ) {
			return null;
		}
		$plain  = $this->decrypt_local( (string) $payload, $this->get_site_url() );
		$record = @unserialize( $plain ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
		return is_object( $record ) ? $record : null;
	}

	private function remove_local_license_record(): bool {
		$deleted = delete_option( $this->get_record_key() );
		foreach ( self::$on_delete_license as $func ) {
			if ( is_callable( $func ) ) {
				call_user_func( $func );
			}
		}
		return (bool) $deleted;
	}

	/**
	 * Local-only encryption (legacy-compatible envelope).
	 */
	private function encrypt_local( string $plain_text, string $password ): string {
		$key = hash( 'crc32b', $this->storage_salt . $password );
		$plain_text = wp_rand( 10, 99 ) . $plain_text . wp_rand( 10, 99 );
		$method     = 'aes-256-cbc';
		$enc_key    = substr( hash( 'sha256', $key, true ), 0, 32 );
		$iv         = substr( strtoupper( md5( $key ) ), 0, 16 );
		$cipher     = openssl_encrypt( $plain_text, $method, $enc_key, OPENSSL_RAW_DATA, $iv );
		return base64_encode( (string) $cipher );
	}

	private function decrypt_local( string $payload, string $password ): string {
		$key     = hash( 'crc32b', $this->storage_salt . $password );
		$method  = 'aes-256-cbc';
		$enc_key = substr( hash( 'sha256', $key, true ), 0, 32 );
		$iv      = substr( strtoupper( md5( $key ) ), 0, 16 );
		$decoded = base64_decode( $payload, true );
		if ( false === $decoded ) {
			return '';
		}
		$plain = openssl_decrypt( $decoded, $method, $enc_key, OPENSSL_RAW_DATA, $iv );
		if ( false === $plain ) {
			return '';
		}
		return substr( $plain, 2, -2 );
	}

	/**
	 * Decrypt legacy Worker-era local records.
	 */
	private function decrypt_legacy( string $payload, string $password ): string {
		$legacy_key = defined( 'VMS_SPAN_CHECKER_LICENSE_KEY' ) ? VMS_SPAN_CHECKER_LICENSE_KEY : 'Vms5pAn2026PrOK1';
		$method     = 'aes-256-cbc';
		$enc_key    = substr( hash( 'sha256', $legacy_key, true ), 0, 32 );
		$iv         = substr( strtoupper( md5( $legacy_key ) ), 0, 16 );
		$decoded    = base64_decode( $this->b64_dc_legacy( $payload ), true );
		if ( false === $decoded ) {
			return '';
		}
		$plain = openssl_decrypt( $decoded, $method, $enc_key, OPENSSL_RAW_DATA, $iv );
		if ( false === $plain ) {
			$enc_key = substr( hash( 'sha256', $password, true ), 0, 32 );
			$iv      = substr( strtoupper( md5( $password ) ), 0, 16 );
			$plain   = openssl_decrypt( $this->b64_dc_legacy( $payload ), $method, $enc_key, OPENSSL_RAW_DATA, $iv );
		}
		if ( false === $plain ) {
			return '';
		}
		return substr( $plain, 2, -2 );
	}

	private function b64_dc_legacy( string $str ): string {
		$fn = preg_replace( '#[^a-z0-9_]#i', '', 'ba*s-e#6-4#_d$e!c#o!d#e' );
		return (string) call_user_func( $fn, $str );
	}
}
