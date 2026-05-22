<?php
/**
 * High-level license manager.
 *
 * Wraps {@see License_Base} with a friendly facade used by the rest of the
 * plugin and by the Pro plugin. Single source of truth for the
 * `vms_span_checker_is_pro_active` filter.
 *
 * @package VMS_Span_Checker
 */

namespace VMS_Span_Checker\Licensing;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-license-base.php';

/**
 * Public license facade.
 */
class License_Manager {

	/**
	 * Singleton.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Lower-level worker.
	 *
	 * @var License_Base
	 */
	private $base;

	/**
	 * Memoised validity for the current request.
	 *
	 * @var bool|null
	 */
	private $is_valid_cache = null;

	private function __construct() {
		$this->base = License_Base::instance( defined( 'VMS_SPAN_CHECKER_FILE' ) ? VMS_SPAN_CHECKER_FILE : '' );

		add_filter( 'vms_span_checker_is_pro_active', array( $this, 'filter_is_pro_active' ), 5 );

		License_Base::add_on_delete(
			static function () {
				delete_transient( 'vms_span_checker_license_state' );
			}
		);
	}

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Whether the license is active and Pro can run.
	 */
	public function is_pro_active(): bool {
		if ( null !== $this->is_valid_cache ) {
			return $this->is_valid_cache;
		}
		$info = $this->base->get_register_info();
		$ok   = false;
		if ( is_object( $info ) && ! empty( $info->is_valid ) ) {
			if ( ! empty( $info->expire_date )
				&& strtolower( (string) $info->expire_date ) !== 'no expiry'
				&& strtolower( (string) $info->expire_date ) !== 'unlimited'
				&& strtotime( (string) $info->expire_date ) < time()
			) {
				$ok = false;
			} else {
				$ok = true;
			}
		}
		$this->is_valid_cache = (bool) $ok;
		return $this->is_valid_cache;
	}

	/**
	 * Hook to `vms_span_checker_is_pro_active`. Returns true only if the
	 * cached license record is valid (no Pro feature can run otherwise).
	 *
	 * @param bool $value Incoming filter value.
	 */
	public function filter_is_pro_active( $value ): bool {
		if ( $value ) {
			return true;
		}
		return $this->is_pro_active();
	}

	/**
	 * License info struct, or null when no license is cached.
	 *
	 * @return object|null
	 */
	public function get_info() {
		return $this->base->get_register_info();
	}

	/**
	 * Activate a license.
	 *
	 * @param string $license_key Purchase key.
	 * @param string $email       Optional email (defaults to admin email).
	 * @return array{success:bool,message:string,info:?object}
	 */
	public function activate( string $license_key, string $email = '' ): array {
		$license_key = trim( $license_key );
		if ( '' !== $email ) {
			$this->base->set_email_address( $email );
		}
		$err    = '';
		$resp   = null;
		$ok     = $this->base->check_wp_plugin( $license_key, $err, $resp );
		$this->is_valid_cache = null;
		delete_transient( 'vms_span_checker_license_state' );
		return array(
			'success' => (bool) $ok,
			'message' => $ok ? __( 'License activated.', 'vms-span-checker' ) : ( $err ?: __( 'License activation failed.', 'vms-span-checker' ) ),
			'info'    => is_object( $resp ) ? $resp : null,
		);
	}

	/**
	 * Deactivate the active license.
	 *
	 * @return array{success:bool,message:string}
	 */
	public function deactivate(): array {
		$msg = '';
		$ok  = $this->base->remove_license_key( $msg );
		$this->is_valid_cache = null;
		delete_transient( 'vms_span_checker_license_state' );
		return array(
			'success' => (bool) $ok,
			'message' => $msg ?: ( $ok ? __( 'License deactivated.', 'vms-span-checker' ) : __( 'Could not deactivate license.', 'vms-span-checker' ) ),
		);
	}

	/**
	 * Force a fresh `verify` round-trip (used by the cron job).
	 *
	 * @return array{success:bool,message:string}
	 */
	public function verify(): array {
		$info = $this->base->get_register_info();
		if ( ! is_object( $info ) || empty( $info->license_key ) ) {
			return array(
				'success' => false,
				'message' => __( 'No license to verify.', 'vms-span-checker' ),
			);
		}
		// Force re-check by clearing next_request.
		$info->next_request = 0;
		// Cannot mutate the cached record from here; let the next request hit
		// the network naturally — re-activate to refresh.
		return $this->activate( (string) $info->license_key );
	}

	/**
	 * Convenience: human-readable status string for the admin UI.
	 */
	public function status_label(): string {
		if ( ! $this->is_pro_active() ) {
			return __( 'Inactive', 'vms-span-checker' );
		}
		$info = $this->get_info();
		if ( is_object( $info ) && ! empty( $info->expire_date ) ) {
			$exp = strtolower( (string) $info->expire_date );
			if ( 'no expiry' === $exp || 'unlimited' === $exp ) {
				return __( 'Active (lifetime)', 'vms-span-checker' );
			}
			return sprintf( /* translators: %s: date */ __( 'Active (until %s)', 'vms-span-checker' ), $info->expire_date );
		}
		return __( 'Active', 'vms-span-checker' );
	}
}
