<?php
/**
 * Google Web Risk API client.
 *
 * @package VMS_Elements_Form_Guard
 */

namespace VMS_Elements_Form_Guard\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Minimal Web Risk lookup.
 */
class Google_Webrisk {

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
		$config = get_option( 'vefg-google-config', array() );

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
				'message' => __( 'Google API key is required.', 'vms-elements-form-guard' ),
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
				'message' => sprintf( __( 'Web Risk API request failed: %s', 'vms-elements-form-guard' ), $response->get_error_message() ),
			);
		}

		$body = wp_remote_retrieve_body( $response );
		if ( ! $body ) {
			return array(
				'status'  => false,
				'message' => __( 'Web Risk API returned an empty response.', 'vms-elements-form-guard' ),
			);
		}

		$data = json_decode( $body, true );

		if ( isset( $data['threat'] ) ) {
			return array(
				'status'  => false,
				'message' => __( 'Threat detected.', 'vms-elements-form-guard' ),
			);
		}

		return array(
			'status'  => true,
			'message' => __( 'No threats detected.', 'vms-elements-form-guard' ),
		);
	}
}
