<?php
/**
 * High-level license manager.
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

	const CRON_HOOK = 'vms_span_checker_license_refresh';

	/**
	 * Singleton.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * @var License_Base
	 */
	private $base;

	/**
	 * @var bool|null
	 */
	private $is_valid_cache = null;

	private function __construct() {
		$this->base = License_Base::instance( defined( 'VMS_SPAN_CHECKER_FILE' ) ? VMS_SPAN_CHECKER_FILE : '' );

		add_filter( 'vms_span_checker_is_pro_active', array( $this, 'filter_is_pro_active' ), 5 );

		License_Base::add_on_delete(
			static function () {
				delete_transient( 'vms_span_checker_license_state' );
				wp_clear_scheduled_hook( self::CRON_HOOK );
			}
		);

		add_action( self::CRON_HOOK, array( $this, 'cron_refresh' ) );
		add_action( 'init', array( $this, 'ensure_cron_scheduled' ) );
		add_filter( 'cron_schedules', array( $this, 'register_cron_interval' ) );
		add_action( 'wp_ajax_vms_span_checker_license_refresh', array( $this, 'ajax_refresh' ) );
	}

	/**
	 * @param array<string, array<string, int|string>> $schedules WP cron schedules.
	 * @return array<string, array<string, int|string>>
	 */
	public function register_cron_interval( array $schedules ): array {
		$schedules['vms_span_checker_five_minutes'] = array(
			'interval' => License_Base::REFRESH_INTERVAL,
			'display'  => __( 'Every 5 minutes (VMS license)', 'vms-span-checker' ),
		);
		return $schedules;
	}

	/**
	 * Admin AJAX heartbeat — validates license; removes local record when invalid.
	 */
	public function ajax_refresh(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Forbidden.', 'vms-span-checker' ) ), 403 );
		}
		check_ajax_referer( 'vms_span_checker_nonce', 'nonce' );

		$force  = ! empty( $_POST['force'] );
		$result = $this->refresh( (bool) $force );

		if ( ! empty( $result['throttled'] ) ) {
			wp_send_json_success(
				array(
					'valid'     => $this->is_pro_active(),
					'throttled' => true,
					'message'   => $result['message'],
				)
			);
		}

		if ( ! empty( $result['blocked'] ) || ! $result['success'] ) {
			wp_send_json_success(
				array(
					'valid'   => false,
					'blocked' => ! empty( $result['blocked'] ),
					'message' => $result['message'],
				)
			);
		}

		wp_send_json_success(
			array(
				'valid'   => true,
				'message' => $result['message'],
			)
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
	 * @param bool $value Incoming filter value.
	 */
	public function filter_is_pro_active( $value ): bool {
		if ( $value ) {
			return true;
		}
		return $this->is_pro_active();
	}

	/**
	 * @return object|null
	 */
	public function get_info() {
		return $this->base->get_register_info();
	}

	/**
	 * @return array{success:bool,message:string,info:?object,blocked?:bool,throttled?:bool}
	 */
	public function activate( string $license_key, string $email = '' ): array {
		$license_key = trim( $license_key );
		if ( '' !== $email ) {
			$this->base->set_email_address( $email );
		}

		$block = $this->base->fetch_block_status( $license_key );
		if ( ! empty( $block['blocked'] ) ) {
			$this->clear_cache();
			return array(
				'success' => false,
				'blocked' => true,
				'message' => $this->format_blocked_notice( $block ),
				'info'    => null,
			);
		}

		$err  = '';
		$resp = null;
		$ok   = $this->base->check_wp_plugin( $license_key, $err, $resp, true );
		$this->clear_cache();

		if ( $ok ) {
			$this->schedule_cron();
			return array(
				'success' => true,
				'message' => __( 'License activated.', 'vms-span-checker' ),
				'info'    => is_object( $resp ) ? $resp : null,
			);
		}

		$strike = $this->base->report_strike( $license_key, $err ?: __( 'Activation failed in plugin settings.', 'vms-span-checker' ) );
		if ( ! empty( $strike['blocked'] ) ) {
			return array(
				'success' => false,
				'blocked' => true,
				'message' => $this->format_blocked_notice( $strike ),
				'info'    => null,
			);
		}

		$strikes_left = isset( $strike['strikes_remaining'] ) ? (int) $strike['strikes_remaining'] : null;
		$message      = $err ?: __( 'License activation failed.', 'vms-span-checker' );
		if ( null !== $strikes_left && $strikes_left >= 0 ) {
			$message .= ' ' . sprintf(
				/* translators: %d: strikes remaining before block */
				__( '(%d failed activation attempts remaining before automatic block.)', 'vms-span-checker' ),
				$strikes_left
			);
		}

		return array(
			'success' => false,
			'message' => $message,
			'info'    => null,
		);
	}

	/**
	 * @return array{success:bool,message:string}
	 */
	public function deactivate(): array {
		$msg = '';
		$ok  = $this->base->remove_license_key( $msg );
		$this->clear_cache();
		wp_clear_scheduled_hook( self::CRON_HOOK );
		return array(
			'success' => (bool) $ok,
			'message' => $msg ?: ( $ok ? __( 'License deactivated.', 'vms-span-checker' ) : __( 'Could not deactivate license.', 'vms-span-checker' ) ),
		);
	}

	/**
	 * Force a fresh validate round-trip.
	 *
	 * @return array{success:bool,message:string,throttled?:bool}
	 */
	public function verify(): array {
		return $this->refresh( true );
	}

	/**
	 * Validate license with optional force (respects 5-minute minimum interval).
	 *
	 * @return array{success:bool,message:string,throttled?:bool,blocked?:bool}
	 */
	public function refresh( bool $force = false ): array {
		$info = $this->base->get_register_info();
		if ( ! is_object( $info ) || empty( $info->license_key ) ) {
			return array(
				'success' => false,
				'message' => __( 'No license to verify.', 'vms-span-checker' ),
			);
		}

		if ( $force && ! $this->allow_forced_refresh() ) {
			return array(
				'success'   => $this->is_pro_active(),
				'throttled' => true,
				'message'   => sprintf(
					/* translators: %d: minutes */
					__( 'License was checked recently. Automatic checks run every %d minutes.', 'vms-span-checker' ),
					(int) ( License_Base::REFRESH_INTERVAL / 60 )
				),
			);
		}

		$key   = (string) $info->license_key;
		$block = $this->base->fetch_block_status( $key );
		if ( ! empty( $block['blocked'] ) ) {
			$discard = '';
			$this->base->remove_license_key( $discard );
			$this->clear_cache();
			wp_clear_scheduled_hook( self::CRON_HOOK );
			return array(
				'success' => false,
				'blocked' => true,
				'message' => $this->format_blocked_notice( $block ),
			);
		}

		$err  = '';
		$resp = null;
		$ok   = $this->base->validate_license( $key, $err, $resp, $force );
		$this->clear_cache();

		if ( ! $ok ) {
			wp_clear_scheduled_hook( self::CRON_HOOK );
			return array(
				'success' => false,
				'message' => $err ?: __( 'License is no longer valid for this site.', 'vms-span-checker' ),
			);
		}

		$this->schedule_cron();
		return array(
			'success' => true,
			'message' => __( 'License verified.', 'vms-span-checker' ),
		);
	}

	/**
	 * WP-Cron: validate every 5 minutes while a license is stored.
	 */
	public function cron_refresh(): void {
		$this->refresh( false );
	}

	/**
	 * Schedule recurring validation when a license exists.
	 */
	public function ensure_cron_scheduled(): void {
		if ( ! $this->get_info() ) {
			return;
		}
		$this->schedule_cron();
	}

	private function schedule_cron(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + License_Base::REFRESH_INTERVAL, 'vms_span_checker_five_minutes', self::CRON_HOOK );
		}
	}

	/**
	 * Throttle manual / AJAX forced checks to once per 5 minutes.
	 */
	private function allow_forced_refresh(): bool {
		$key  = 'vms_span_checker_license_force_ts';
		$last = (int) get_transient( $key );
		if ( $last > 0 && ( time() - $last ) < License_Base::REFRESH_INTERVAL ) {
			return false;
		}
		set_transient( $key, time(), License_Base::REFRESH_INTERVAL );
		return true;
	}

	public function status_label(): string {
		if ( ! $this->is_pro_active() ) {
			$info = $this->get_info();
			if ( is_object( $info ) && ! empty( $info->blocked ) ) {
				return __( 'Blocked', 'vms-span-checker' );
			}
			return __( 'Inactive', 'vms-span-checker' );
		}
		$info = $this->get_info();
		if ( is_object( $info ) && ! empty( $info->expire_date ) ) {
			$exp = strtolower( (string) $info->expire_date );
			if ( 'no expiry' === $exp || 'unlimited' === $exp ) {
				return __( 'Active (lifetime)', 'vms-span-checker' );
			}
			return sprintf(
				/* translators: %s: date */
				__( 'Active (until %s)', 'vms-span-checker' ),
				$info->expire_date
			);
		}
		return __( 'Active', 'vms-span-checker' );
	}

	/**
	 * Whether admin heartbeat JS should run.
	 */
	public function should_run_admin_heartbeat(): bool {
		return is_admin() && (bool) $this->get_info();
	}

	/**
	 * @param array<string, mixed> $payload Block/strike API body.
	 */
	private function format_blocked_notice( array $payload ): string {
		if ( ! empty( $payload['message'] ) ) {
			return (string) $payload['message'];
		}
		if ( ! empty( $payload['blocked_reason'] ) ) {
			return (string) $payload['blocked_reason'];
		}
		$contact = ! empty( $payload['contact'] ) ? (string) $payload['contact'] : 'support@vmselements.com';
		return sprintf(
			/* translators: %s: support email */
			__( 'License blocked after 5 failed activation attempts. Contact %s to restore access.', 'vms-span-checker' ),
			$contact
		);
	}

	private function clear_cache(): void {
		$this->is_valid_cache = null;
		delete_transient( 'vms_span_checker_license_state' );
	}
}
