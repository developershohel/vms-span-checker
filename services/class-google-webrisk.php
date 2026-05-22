<?php
/**
 * Google Web Risk API client.
 *
 * @package VMS_Span_Checker
 */

namespace VMS_Span_Checker\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Minimal Web Risk lookup.
 */
class GoogleWebRisk {

	/**
	 * API key (Web Risk `uris:search` uses the `key` query parameter only).
	 *
	 * @var string
	 */
	private $api_key = '';

	/**
	 * Load options.
	 */
	public function __construct() {
		$config = get_option( 'wsc-google-config', array() );

		$this->api_key = ( is_array( $config ) && isset( $config['api_key'] ) ) ? (string) $config['api_key'] : '';
	}

	/**
	 * Check a URL against Web Risk.
	 *
	 * @param string $url Full URL to check.
	 * @return array{status:bool,message:string}
	 */
	public function check_url( $url ) {
		if ( empty( $this->api_key ) ) {
			return array(
				'status'  => false,
				'message' => __( 'Google API key is required.', 'vms-span-checker' ),
			);
		}

		$endpoint = 'https://webrisk.googleapis.com/v1/uris:search';

		$query = array(
			'uri'         => $url,
			'threatTypes' => array( 'MALWARE', 'SOCIAL_ENGINEERING', 'UNWANTED_SOFTWARE' ),
			'key'         => $this->api_key,
		);

		$full_url = $endpoint . '?' . http_build_query( $query );

		$response = wp_remote_get(
			$full_url,
			array(
				'timeout' => 10,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'status'  => false,
				/* translators: %s: WordPress error message */
				'message' => sprintf( __( 'Web Risk API request failed: %s', 'vms-span-checker' ), $response->get_error_message() ),
			);
		}

		$body = wp_remote_retrieve_body( $response );
		if ( ! $body ) {
			return array(
				'status'  => false,
				'message' => __( 'Web Risk API returned an empty response.', 'vms-span-checker' ),
			);
		}

		$data = json_decode( $body, true );

		if ( isset( $data['threat'] ) ) {
			return array(
				'status'  => false,
				'message' => __( 'Threat detected.', 'vms-span-checker' ),
			);
		}

		return array(
			'status'  => true,
			'message' => __( 'No threats detected.', 'vms-span-checker' ),
		);
	}
}
