<?php
/**
 * VirusTotal API v3 client.
 *
 * @package WP_Span_Checker
 */

namespace WP_Span_Checker\Services;

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
		$config         = get_option( 'wsc-virustotal-config', array() );
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
				'message' => __( 'VirusTotal API key is required.', 'wp-span-checker' ),
			);
		}

		$api_key = $this->get_random_key();
		if ( ! $api_key ) {
			return array(
				'status'  => false,
				'message' => __( 'No valid VirusTotal key.', 'wp-span-checker' ),
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
				/* translators: %s: WordPress error message */
				'message' => sprintf( __( 'VirusTotal API request failed: %s', 'wp-span-checker' ), $response->get_error_message() ),
			);
		}

		$body = wp_remote_retrieve_body( $response );
		if ( ! $body ) {
			return array(
				'status'  => false,
				'message' => __( 'VirusTotal API returned an empty response.', 'wp-span-checker' ),
			);
		}

		$data = json_decode( $body, true );
		$stats = isset( $data['data']['attributes']['last_analysis_stats'] ) && is_array( $data['data']['attributes']['last_analysis_stats'] )
			? $data['data']['attributes']['last_analysis_stats']
			: array();

		$malicious  = isset( $stats['malicious'] ) ? (int) $stats['malicious'] : 0;
		$suspicious = isset( $stats['suspicious'] ) ? (int) $stats['suspicious'] : 0;

		$config     = get_option( 'wsc-virustotal-config', array() );
		$max_bad    = isset( $config['max_malicious'] ) ? max( 0, (int) $config['max_malicious'] ) : 0;
		$max_susp   = isset( $config['max_suspicious'] ) ? (int) $config['max_suspicious'] : -1;

		if ( $malicious > $max_bad ) {
			return array(
				'status'  => false,
				/* translators: 1: malicious engine count, 2: allowed max */
				'message' => sprintf(
					__( 'VirusTotal: too many malicious detections (%1$d; allowed max %2$d).', 'wp-span-checker' ),
					$malicious,
					$max_bad
				),
			);
		}

		if ( $max_susp >= 0 && $suspicious > $max_susp ) {
			return array(
				'status'  => false,
				/* translators: 1: suspicious engine count, 2: allowed max */
				'message' => sprintf(
					__( 'VirusTotal: too many suspicious detections (%1$d; allowed max %2$d).', 'wp-span-checker' ),
					$suspicious,
					$max_susp
				),
			);
		}

		return array(
			'status'  => true,
			'message' => __( 'VirusTotal: domain within your thresholds.', 'wp-span-checker' ),
		);
	}
}
