<?php
/**
 * VirusTotal API v3 client.
 *
 * @package VMS_Elements_Form_Guard
 */

namespace VMS_Elements_Form_Guard\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Domain reputation lookup.
 */
class VirusTotal {

	/**
	 * API keys from settings.
	 *
	 * @var array<int, string>
	 */
	private $api_keys = array();

	/**
	 * Load stored keys.
	 */
	public function __construct() {
		$config         = get_option( 'vefg-virustotal-config', array() );
		$this->api_keys = isset( $config['keys'] ) && is_array( $config['keys'] ) ? $config['keys'] : array();
	}

	/**
	 * Pick a random non-empty key.
	 *
	 * @return string|null
	 */
	private function get_random_key() {
		$valid = array_filter(
			$this->api_keys,
			static function ( $k ) {
				return ! empty( $k );
			}
		);

		if ( empty( $valid ) ) {
			return null;
		}

		return $valid[ array_rand( $valid ) ];
	}

	/**
	 * Fetch domain report.
	 *
	 * @param string $domain Hostname.
	 * @return array{status:bool,message:string}
	 */
	public function check_domain( $domain ) {
		if ( empty( $this->api_keys ) ) {
			return array(
				'status'  => false,
				'message' => __( 'Security reputation check is temporarily unavailable. Please try again later.', 'vms-elements-form-guard' ),
			);
		}

		$api_key = $this->get_random_key();
		if ( ! $api_key ) {
			return array(
				'status'  => false,
				'message' => __( 'Security reputation check is temporarily unavailable. Please try again later.', 'vms-elements-form-guard' ),
			);
		}

		$url = 'https://www.virustotal.com/api/v3/domains/' . rawurlencode( $domain );

		$response = wp_remote_get(
			$url,
			array(
				'headers' => array(
					'x-apikey' => $api_key,
				),
				'timeout' => 10,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'status'  => false,
				'message' => __( 'Security reputation check could not be completed. Please try again.', 'vms-elements-form-guard' ),
			);
		}

		$http_code = (int) wp_remote_retrieve_response_code( $response );
		if ( $http_code < 200 || $http_code >= 300 ) {
			return array(
				'status'  => false,
				'message' => __( 'Security reputation check could not be completed. Please try again.', 'vms-elements-form-guard' ),
			);
		}

		$body = wp_remote_retrieve_body( $response );
		if ( ! $body ) {
			return array(
				'status'  => false,
				'message' => __( 'Security reputation check returned an empty response. Please try again.', 'vms-elements-form-guard' ),
			);
		}

		$data = json_decode( $body, true );
		if ( ! is_array( $data ) ) {
			return array(
				'status'  => false,
				'message' => __( 'Security reputation check returned an invalid response. Please try again.', 'vms-elements-form-guard' ),
			);
		}

		$stats = isset( $data['data']['attributes']['last_analysis_stats'] ) && is_array( $data['data']['attributes']['last_analysis_stats'] )
			? $data['data']['attributes']['last_analysis_stats']
			: array();

		if ( empty( $stats ) ) {
			return array(
				'status'  => false,
				'message' => __( 'Security reputation report was incomplete. Please try again.', 'vms-elements-form-guard' ),
			);
		}

		$malicious  = isset( $stats['malicious'] ) ? (int) $stats['malicious'] : 0;
		$suspicious = isset( $stats['suspicious'] ) ? (int) $stats['suspicious'] : 0;

		$config     = get_option( 'vefg-virustotal-config', array() );
		$max_bad    = isset( $config['max_malicious'] ) ? max( 0, (int) $config['max_malicious'] ) : 0;
		$max_susp   = isset( $config['max_suspicious'] ) ? (int) $config['max_suspicious'] : -1;

		if ( $malicious > $max_bad ) {
			return array(
				'status'  => false,
				'message' => sprintf(
					/* translators: 1: malicious engine count, 2: allowed max */
					__( 'This email domain failed security reputation checks (%1$d signals; allowed max %2$d).', 'vms-elements-form-guard' ),
					$malicious,
					$max_bad
				),
			);
		}

		if ( $max_susp >= 0 && $suspicious > $max_susp ) {
			return array(
				'status'  => false,
				'message' => sprintf(
					/* translators: 1: suspicious engine count, 2: allowed max */
					__( 'This email domain failed security reputation checks (%1$d suspicious signals; allowed max %2$d).', 'vms-elements-form-guard' ),
					$suspicious,
					$max_susp
				),
			);
		}

		return array(
			'status'  => true,
			'message' => __( 'Domain passed reputation checks.', 'vms-elements-form-guard' ),
		);
	}
}
