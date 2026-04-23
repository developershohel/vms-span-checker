<?php

namespace WP_Span_Checker\Services;

use WP_Span_Checker\Logger;
use WP_Span_Checker\Whitelist;
use WP_Span_Checker\Disposable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Domain_Validator {

	private Whitelist $whitelist;
	private Disposable $disposable;
	private Logger $logger;
	private VirusTotal $virustotal;
	private GoogleWebRisk $webrisk;

	public function __construct() {
		$this->whitelist  = new \WP_Span_Checker\Whitelist();
		$this->disposable = new \WP_Span_Checker\Disposable();
		$this->logger     = new \WP_Span_Checker\Logger();
		$this->virustotal = new \WP_Span_Checker\Services\VirusTotal();
		$this->webrisk    = new \WP_Span_Checker\Services\GoogleWebRisk();
	}

	/**
	 * Validate a domain for whitelist / disposable / HTTPS / API checks
	 */
	public function validate_domain( string $domain, string $type = 'registration', string $ip = '', array $settings = array() ): array {
		$domain = strtolower( trim( $domain ) );

		// 1️⃣ Whitelist check
		$whitelist_domains = array_column( $this->whitelist->get_all(), 'domain' );
		if ( in_array( $domain, $whitelist_domains ) ) {
			return $this->log_and_return( $type, $ip, $domain, 'success', 'Domain is whitelisted.', true );
		}

		// 2️⃣ Disposable check
		$disposable_domains = array_column( $this->disposable->get_all(), 'domain' );
		if ( in_array( $domain, $disposable_domains ) ) {
			return $this->log_and_return( $type, $ip, $domain, 'failed', 'Disposable email/domain detected.', false );
		}

		// 3️⃣ Check HTTPS availability
		$https_status = $this->is_https_available( $domain );
		if ( ! $https_status['status'] ) {
			return $this->log_and_return( $type, $ip, $domain, 'failed', $https_status['message'], false );
		}

		if ( $settings['is_webrisk'] && $settings['is_virustotal'] ) {
			// 5️⃣ Google WebRisk check
			$webrisk_result = $this->webrisk->checkUrl( "https://$domain" );
			if ( ! $webrisk_result['status'] ) {
				return $this->log_and_return( $type, $ip, $domain, 'failed', $webrisk_result['message'], false );
			}

			// 4️⃣ VirusTotal check
			$vt_result = $this->virustotal->checkDomain( $domain );
			if ( ! $vt_result['status'] ) {
				return $this->log_and_return( $type, $ip, $domain, 'failed', $vt_result['message'], false );
			}
		} else if ( $settings['is_virustotal'] ) {
			// 4️⃣ VirusTotal check
			$vt_result = $this->virustotal->checkDomain( $domain );
			if ( ! $vt_result['status'] ) {
				return $this->log_and_return( $type, $ip, $domain, 'failed', $vt_result['message'], false );
			}
		} else if ( $settings['is_webrisk'] ) {
			$webrisk_result = $this->webrisk->checkUrl( "https://$domain" );
			if ( ! $webrisk_result['status'] ) {
				return $this->log_and_return( $type, $ip, $domain, 'failed', $webrisk_result['message'], false );
			}
		} else {
			$webrisk_result = $this->webrisk->checkUrl( "https://$domain" );
			if ( ! $webrisk_result['status'] ) {
				return $this->log_and_return( $type, $ip, $domain, 'failed', $webrisk_result['message'], false );
			}
		}

		// ✅ Passed all checks
		return $this->log_and_return( $type, $ip, $domain, 'success', 'Domain is safe and valid.', true );
	}

	/**
	 * Check HTTPS availability for a domain
	 */
	private function is_https_available( string $domain ): array {
		if ( empty( $domain ) ) {
			return [ 'message' => 'No URL provided', 'status' => false ];
		}

		$url      = esc_url_raw( $domain );
		$response = wp_remote_get( $url, [ 'timeout' => 10 ] );

		if ( is_wp_error( $response ) ) {
			return [ 'message' => 'Request failed', 'status' => false ];
		}

		$status = wp_remote_retrieve_response_code( $response );

		if ( $status == 200 ) {
			return [ 'message' => '✅ Site is live (200 OK)', 'status' => true ];
		} elseif ( $status >= 300 && $status < 400 ) {
			return [ 'message' => '⚠️ Redirect detected (' . $status . ')', 'status' => false ];
		} elseif ( $status >= 400 && $status < 500 ) {
			return [ 'message' => '❌ Client error (' . $status . ')', 'status' => false ];
		} elseif ( $status >= 500 ) {
			return [ 'message' => '🔥 Server error (' . $status . ')', 'status' => false ];
		} else {
			return [ 'message' => 'Unknown status (' . $status . ')', 'status' => false ];
		}
	}

	/**
	 * Helper: log the result and return standardized response
	 */
	private function log_and_return( string $type, string $ip, string $domain, string $status, string $message, bool $success ): array {
		$this->logger->log( $type, $ip, $domain, $status, $message );

		return [
			'status'  => $success,
			'message' => $message
		];
	}

	/**
	 * Validate an email domain
	 */
	public function validate_email( string $email, string $type = 'registration', string $ip = '' ): array {
		if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
			return $this->log_and_return( $type, $ip, '', 'failed', 'Invalid email format.', false );
		}

		$domain = strtolower( substr( strrchr( $email, "@" ), 1 ) );

		return $this->validate_domain( $domain, $type, $ip );
	}
}
