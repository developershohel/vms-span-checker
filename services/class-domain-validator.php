<?php
/**
 * Domain validation pipeline.
 *
 * @package WP_Span_Checker
 */

namespace WP_Span_Checker\Services;

use WP_Span_Checker\Disposable;
use WP_Span_Checker\Logger;
use WP_Span_Checker\Whitelist;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whitelist, disposable, HTTPS, and optional reputation checks.
 */
class Domain_Validator {

	/**
	 * Whitelist repository.
	 *
	 * @var Whitelist
	 */
	private $whitelist;

	/**
	 * Disposable list repository.
	 *
	 * @var Disposable
	 */
	private $disposable;

	/**
	 * Activity logger.
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * VirusTotal client.
	 *
	 * @var VirusTotal
	 */
	private $virustotal;

	/**
	 * Google Web Risk client.
	 *
	 * @var GoogleWebRisk
	 */
	private $webrisk;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->whitelist  = new Whitelist();
		$this->disposable = new Disposable();
		$this->logger     = new Logger();
		$this->virustotal = new VirusTotal();
		$this->webrisk    = new GoogleWebRisk();
	}

	/**
	 * Validate a domain for whitelist / disposable / HTTPS / API checks.
	 *
	 * @param string               $domain   Hostname.
	 * @param string               $type     Context label for logs.
	 * @param string               $ip       Client IP.
	 * @param array<string, mixed> $settings Flags: is_webrisk, is_virustotal.
	 * @return array{status:bool,message:string}
	 */
	public function validate_domain( $domain, $type = 'registration', $ip = '', $settings = array() ) {
		$domain = strtolower( trim( $domain ) );

		$skip_https = ! empty( $settings['skip_https'] );

		$require_dns = array_key_exists( 'require_dns_live', $settings )
			? ! empty( $settings['require_dns_live'] )
			: true;

		$whitelist_domains = array_column( $this->whitelist->get_all(), 'domain' );
		if ( in_array( $domain, $whitelist_domains, true ) ) {
			return $this->log_and_return( $type, $ip, $domain, 'success', __( 'Domain is whitelisted.', 'wp-span-checker' ), true );
		}

		$disposable_domains = array_column( $this->disposable->get_all(), 'domain' );
		if ( in_array( $domain, $disposable_domains, true ) ) {
			return $this->log_and_return( $type, $ip, $domain, 'failed', __( 'Disposable email or domain detected.', 'wp-span-checker' ), false );
		}

		if ( $require_dns && ! $this->domain_dns_is_live( $domain ) ) {
			return $this->log_and_return(
				$type,
				$ip,
				$domain,
				'failed',
				__( 'This email domain does not resolve in DNS (no live mail or host records). Use an address at a real, active domain.', 'wp-span-checker' ),
				false
			);
		}

		if ( ! empty( $settings['require_mx'] ) ) {
			$mx_ok = $this->domain_has_inbound_mail_dns( $domain, ! empty( $settings['mx_allow_a_fallback'] ) );
			if ( ! $mx_ok ) {
				return $this->log_and_return(
					$type,
					$ip,
					$domain,
					'failed',
					__( 'Email domain has no MX (or acceptable A) DNS records — mail may not be deliverable.', 'wp-span-checker' ),
					false
				);
			}
		}

		if ( ! $skip_https ) {
			$https_status = $this->is_https_available( $domain );
			if ( ! $https_status['status'] ) {
				return $this->log_and_return( $type, $ip, $domain, 'failed', $https_status['message'], false );
			}
		}

		$use_webrisk = ! empty( $settings['is_webrisk'] );
		$use_vt      = ! empty( $settings['is_virustotal'] );
		// When both APIs are on: if Web Risk is clean, skip VirusTotal (default) to save quota.
		$vt_skip_if_webrisk_clean = ! array_key_exists( 'vt_skip_if_webrisk_clean', $settings )
			? true
			: ! empty( $settings['vt_skip_if_webrisk_clean'] );

		if ( $use_webrisk ) {
			$webrisk_result = $this->webrisk->check_url( 'https://' . $domain );
			if ( ! $webrisk_result['status'] ) {
				return $this->log_and_return( $type, $ip, $domain, 'failed', $webrisk_result['message'], false );
			}
			if ( $use_vt && ! $vt_skip_if_webrisk_clean ) {
				$vt_result = $this->virustotal->check_domain( $domain );
				if ( ! $vt_result['status'] ) {
					return $this->log_and_return( $type, $ip, $domain, 'failed', $vt_result['message'], false );
				}
			}
		} elseif ( $use_vt ) {
			$vt_result = $this->virustotal->check_domain( $domain );
			if ( ! $vt_result['status'] ) {
				return $this->log_and_return( $type, $ip, $domain, 'failed', $vt_result['message'], false );
			}
		}

		return $this->log_and_return( $type, $ip, $domain, 'success', __( 'Domain is safe and valid.', 'wp-span-checker' ), true );
	}

	/**
	 * MX exists, or optional A-record fallback (some small hosts).
	 *
	 * @param string $domain ASCII hostname.
	 * @param bool   $allow_a_fallback Allow A record if no MX.
	 */
	/**
	 * True if the hostname has common DNS presence (registered / “live” for mail or hosting).
	 *
	 * @param string $domain ASCII hostname.
	 */
	private function domain_dns_is_live( string $domain ): bool {
		if ( '' === $domain || ! function_exists( 'dns_get_record' ) ) {
			return true;
		}

		$mask = DNS_MX | DNS_NS | DNS_SOA;
		if ( defined( 'DNS_A' ) ) {
			$mask |= DNS_A;
		}
		if ( defined( 'DNS_AAAA' ) ) {
			$mask |= DNS_AAAA;
		}

		$rec = @dns_get_record( $domain, $mask );
		return is_array( $rec ) && count( $rec ) > 0;
	}

	/**
	 * MX exists, or optional A-record fallback (some small hosts).
	 *
	 * @param string $domain ASCII hostname.
	 * @param bool   $allow_a_fallback Allow A record if no MX.
	 */
	private function domain_has_inbound_mail_dns( string $domain, bool $allow_a_fallback ): bool {
		if ( '' === $domain || ! function_exists( 'dns_get_record' ) ) {
			return true;
		}

		$mx = @dns_get_record( $domain, DNS_MX );
		if ( is_array( $mx ) && count( $mx ) > 0 ) {
			return true;
		}

		if ( $allow_a_fallback ) {
			$a = @dns_get_record( $domain, DNS_A );
			if ( is_array( $a ) && count( $a ) > 0 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check HTTPS availability for a host.
	 *
	 * @param string $domain Hostname or URL.
	 * @return array{message:string,status:bool}
	 */
	private function is_https_available( $domain ) {
		if ( '' === $domain ) {
			return array(
				'message' => __( 'No URL provided.', 'wp-span-checker' ),
				'status'  => false,
			);
		}

		$url = preg_match( '#^https?://#i', $domain )
			? esc_url_raw( $domain )
			: esc_url_raw( 'https://' . $domain );

		if ( empty( $url ) ) {
			return array(
				'message' => __( 'Invalid domain.', 'wp-span-checker' ),
				'status'  => false,
			);
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 10,
				'redirection' => 5,
				'sslverify'   => true,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				/* translators: %s: WordPress error message */
				'message' => sprintf( __( 'Request failed: %s', 'wp-span-checker' ), $response->get_error_message() ),
				'status'  => false,
			);
		}

		$status = (int) wp_remote_retrieve_response_code( $response );

		if ( $status >= 200 && $status < 300 ) {
			return array(
				'message' => __( 'Site responded successfully.', 'wp-span-checker' ),
				'status'  => true,
			);
		}
		if ( $status >= 300 && $status < 400 ) {
			return array(
				/* translators: %d: HTTP status code */
				'message' => sprintf( __( 'Redirect only (%d).', 'wp-span-checker' ), $status ),
				'status'  => false,
			);
		}
		if ( $status >= 400 && $status < 500 ) {
			return array(
				/* translators: %d: HTTP status code */
				'message' => sprintf( __( 'Client error (%d).', 'wp-span-checker' ), $status ),
				'status'  => false,
			);
		}
		if ( $status >= 500 ) {
			return array(
				/* translators: %d: HTTP status code */
				'message' => sprintf( __( 'Server error (%d).', 'wp-span-checker' ), $status ),
				'status'  => false,
			);
		}

		return array(
			/* translators: %d: HTTP status code */
			'message' => sprintf( __( 'Unknown status (%d).', 'wp-span-checker' ), $status ),
			'status'  => false,
		);
	}

	/**
	 * Log and build JSON-safe payload.
	 *
	 * @param string $type    Log type.
	 * @param string $ip      IP address.
	 * @param string $domain  Domain checked.
	 * @param string $status  success|failed.
	 * @param string $message Human-readable message.
	 * @param bool   $success Whether validation passed.
	 * @return array{status:bool,message:string}
	 */
	private function log_and_return( $type, $ip, $domain, $status, $message, $success ) {
		$this->logger->log( $type, $ip, $domain, $status, $message );

		return array(
			'status'  => $success,
			'message' => $message,
		);
	}

	/**
	 * Validate an email address by extracting its domain.
	 *
	 * @param string $email  Email.
	 * @param string $type   Log context.
	 * @param string $ip     Client IP.
	 * @return array{status:bool,message:string}
	 */
	public function validate_email( $email, $type = 'registration', $ip = '', $settings = array() ) {
		if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
			return $this->log_and_return( $type, $ip, '', 'failed', __( 'Invalid email format.', 'wp-span-checker' ), false );
		}

		$domain = strtolower( substr( strrchr( $email, '@' ), 1 ) );

		return $this->validate_domain( $domain, $type, $ip, $settings );
	}
}
