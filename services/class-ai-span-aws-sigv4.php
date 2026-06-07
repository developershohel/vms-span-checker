<?php
/**
 * Minimal AWS Signature Version 4 for Bedrock InvokeModel POST.
 *
 * @package VMS_Elements_Form_Guard
 */

namespace VMS_Elements_Form_Guard\Services;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sign a single POST with JSON body for bedrock-runtime.
 */
class AI_Span_Aws_SigV4 {

	/**
	 * @return array{headers:array<string,string>}|WP_Error
	 */
	public static function sign_request( string $access_key, string $secret_key, string $region, string $service, string $method, string $host, string $path, string $body ) {
		$method = strtoupper( $method );
		$now    = gmdate( 'Ymd\THis\Z' );
		$date   = gmdate( 'Ymd' );

		$payload_hash = hash( 'sha256', $body );
		$canonical_headers =
			'host:' . $host . "\n" .
			'x-amz-content-sha256:' . $payload_hash . "\n" .
			'x-amz-date:' . $now . "\n";
		$signed_headers = 'host;x-amz-content-sha256;x-amz-date';

		$canonical_request =
			$method . "\n" .
			self::uri_encode_path( $path ) . "\n" .
			"\n" .
			$canonical_headers . "\n" .
			$signed_headers . "\n" .
			$payload_hash;

		$credential_scope = $date . '/' . $region . '/' . $service . '/aws4_request';
		$string_to_sign     =
			"AWS4-HMAC-SHA256\n{$now}\n{$credential_scope}\n" .
			hash( 'sha256', $canonical_request );

		$k_date    = hash_hmac( 'sha256', $date, 'AWS4' . $secret_key, true );
		$k_region  = hash_hmac( 'sha256', $region, $k_date, true );
		$k_service = hash_hmac( 'sha256', $service, $k_region, true );
		$k_signing = hash_hmac( 'sha256', 'aws4_request', $k_service, true );
		$signature = hash_hmac( 'sha256', $string_to_sign, $k_signing );

		$auth_header =
			'AWS4-HMAC-SHA256 Credential=' . $access_key . '/' . $credential_scope .
			', SignedHeaders=' . $signed_headers .
			', Signature=' . $signature;

		return array(
			'headers' => array(
				'Authorization'       => $auth_header,
				'Host'                => $host,
				'X-Amz-Date'          => $now,
				'X-Amz-Content-Sha256' => $payload_hash,
				'Content-Type'        => 'application/json',
			),
		);
	}

	/**
	 * URI-encode each path segment (AWS SigV4).
	 */
	private static function uri_encode_path( string $path ): string {
		$parts = explode( '/', trim( $path, '/' ) );
		$enc   = array();
		foreach ( $parts as $p ) {
			$enc[] = str_replace( '%2F', '/', rawurlencode( $p ) );
		}
		return '/' . implode( '/', $enc );
	}
}
