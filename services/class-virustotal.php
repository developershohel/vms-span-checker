<?php

namespace WP_Span_Checker\Services;

class VirusTotal {
	private mixed $api_keys = [];

	public function __construct() {
		$config = get_option( 'wsc-virustotal-config', array() );
		$this->api_keys = $config['keys'] ?? [];
	}

	private function getRandomKey(): ?string {
		$valid = array_filter( $this->api_keys, fn( $k ) => ! empty( $k ) );

		return empty( $valid ) ? null : $valid[ array_rand( $valid ) ];
	}

	public function checkDomain(string $domain): array {
		if(empty($this->api_keys)) {
			return ['status' => false, 'message' => 'VirusTotal API key is required.'];
		}
		$apiKey = $this->getRandomKey();
		if (!$apiKey) {
			return ['status' => false, 'message' => 'No valid VirusTotal key.'];
		}

		$url = "https://www.virustotal.com/api/v3/domains/$domain";

		$response = wp_remote_get($url, [
			'headers' => [
				'x-apikey' => $apiKey
			],
			'timeout' => 10
		]);

		// Check for errors
		if (is_wp_error($response)) {
			return ['status' => false, 'message' => 'VirusTotal API request failed: ' . $response->get_error_message()];
		}

		$body = wp_remote_retrieve_body($response);
		if (!$body) {
			return ['status' => false, 'message' => 'VirusTotal API returned empty response.'];
		}

		$data = json_decode($body, true);
		$malicious = $data['data']['attributes']['last_analysis_stats']['malicious'] ?? 0;

		return $malicious > 0
			? ['status' => false, 'message' => "Malicious ($malicious engines)"]
			: ['status' => true, 'message' => 'Safe domain.'];
	}

}
