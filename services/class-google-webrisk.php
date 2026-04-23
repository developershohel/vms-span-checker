<?php

namespace WP_Span_Checker\Services;

class GoogleWebRisk {
	private string $api_key;
	private string $secret_key;
	private string $client_id;

	public function __construct() {
		$config = get_option( 'wsc-google-config', array() );
		$this->api_key = $config['api_key'] ?? '';
		$this->secret_key = $config['secret_key'] ?? '';
		$this->client_id = $config['client_id'] ?? '';
	}

	public function checkUrl(string $url): array {
		if(empty($this->api_key)) {
			return ['status' => false, 'message' => 'Google API key is required.'];
		}
		$endpoint = 'https://webrisk.googleapis.com/v1/uris:search';

		// Build query parameters
		$query = [
			'uri'         => $url,
			'threatTypes' => ['MALWARE', 'SOCIAL_ENGINEERING', 'UNWANTED_SOFTWARE'],
			'key'         => $this->api_key
		];

		// Build full URL
		$full_url = $endpoint . '?' . http_build_query($query);

		// Make the request using wp_remote_get
		$response = wp_remote_get($full_url, ['timeout' => 10]);

		// Handle errors
		if (is_wp_error($response)) {
			return [
				'status'  => false,
				'message' => 'WebRisk API request failed: ' . $response->get_error_message()
			];
		}

		$body = wp_remote_retrieve_body($response);
		if (!$body) {
			return [
				'status'  => false,
				'message' => 'WebRisk API returned empty response.'
			];
		}

		$data = json_decode($body, true);

		// Check for threat
		if (isset($data['threat'])) {
			return [
				'status'  => false,
				'message' => 'Threat detected'
			];
		}

		return [
			'status'  => true,
			'message' => 'No threats detected'
		];
	}
}
