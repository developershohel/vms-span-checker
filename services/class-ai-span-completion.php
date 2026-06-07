<?php
/**
 * Multi-provider AI completion: OpenAI, Anthropic, Gemini, DeepSeek, AWS Bedrock.
 *
 * @package VMS_Elements_Form_Guard
 */

namespace VMS_Elements_Form_Guard\Services;

use WP_Error;
use VMS_Elements_Form_Guard\AI_Span_Config;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * JSON-oriented chat completions across providers.
 */
class AI_Span_Completion {

	/**
	 * @param string $system System / developer instructions.
	 * @param string $user   User message.
	 * @return string|WP_Error Assistant text (trimmed).
	 */
	public static function complete( string $system, string $user ) {
		$c = AI_Span_Config::get();
		if ( empty( $c['ai_enabled'] ) ) {
			return new WP_Error( 'vefg_ai_off', __( 'AI VMS Elements Form Guard is disabled.', 'vms-elements-form-guard' ) );
		}

		$provider = (string) ( $c['provider'] ?? 'openai' );
		switch ( $provider ) {
			case 'openai':
				return self::openai( $c, $system, $user );
			case 'anthropic':
				return self::anthropic( $c, $system, $user );
			case 'gemini':
				return self::gemini( $c, $system, $user );
			case 'deepseek':
				return self::deepseek( $c, $system, $user );
			case 'bedrock':
				return self::bedrock( $c, $system, $user );
			default:
				return new WP_Error( 'vefg_ai_provider', __( 'Unknown AI provider.', 'vms-elements-form-guard' ) );
		}
	}

	/**
	 * Parse JSON object with status + message from model output.
	 *
	 * @param string $text Raw model output.
	 * @return array{status:string,message:string}|WP_Error
	 */
	public static function parse_json_verdict( string $text ) {
		$text = trim( $text );
		$text = preg_replace( '/^```(?:json)?\s*/i', '', $text );
		$text = preg_replace( '/\s*```$/', '', $text );

		$decoded = json_decode( $text, true );
		if ( ! is_array( $decoded ) ) {
			return new WP_Error( 'vefg_ai_json', __( 'AI did not return valid JSON.', 'vms-elements-form-guard' ) );
		}

		$status  = isset( $decoded['status'] ) ? strtolower( sanitize_text_field( (string) $decoded['status'] ) ) : '';
		$message = isset( $decoded['message'] ) ? sanitize_text_field( (string) $decoded['message'] ) : '';

		if ( ! in_array( $status, array( 'ok', 'spam' ), true ) ) {
			return new WP_Error( 'vefg_ai_status', __( 'AI JSON missing ok/spam status.', 'vms-elements-form-guard' ) );
		}

		return array(
			'status'  => $status,
			'message' => $message !== '' ? $message : ( 'spam' === $status ? __( 'Spam detected.', 'vms-elements-form-guard' ) : 'ok' ),
		);
	}

	/**
	 * @param array<string, mixed> $c .
	 */
	private static function openai( array $c, string $system, string $user ) {
		$key = $c['openai_api_key'] ?? '';
		if ( $key === '' ) {
			return new WP_Error( 'vefg_ai_key', __( 'OpenAI API key is missing.', 'vms-elements-form-guard' ) );
		}
		$model = (string) ( $c['openai_model'] ?? 'gpt-4o-mini' );
		if ( $model === '' ) {
			$model = 'gpt-4o-mini';
		}
		return self::openai_compatible_chat(
			$key,
			$model,
			$system,
			$user,
			'https://api.openai.com/v1/chat/completions'
		);
	}

	/**
	 * DeepSeek uses an OpenAI-compatible Chat Completions API.
	 *
	 * @param array<string, mixed> $c .
	 */
	private static function deepseek( array $c, string $system, string $user ) {
		$key = $c['deepseek_api_key'] ?? '';
		if ( $key === '' ) {
			return new WP_Error( 'vefg_ai_key', __( 'DeepSeek API key is missing.', 'vms-elements-form-guard' ) );
		}
		$model = (string) ( $c['deepseek_model'] ?? 'deepseek-chat' );
		if ( $model === '' ) {
			$model = 'deepseek-chat';
		}
		return self::openai_compatible_chat(
			$key,
			$model,
			$system,
			$user,
			'https://api.deepseek.com/v1/chat/completions'
		);
	}

	/**
	 * @return string|WP_Error
	 */
	private static function openai_compatible_chat( string $api_key, string $model, string $system, string $user, string $endpoint ) {
		$body = wp_json_encode(
			array(
				'model'             => $model,
				'messages'          => array(
					array(
						'role' => 'system',
						'content' => $system,
					),
					array(
						'role' => 'user',
						'content' => $user,
					),
				),
				'temperature'       => 0.2,
				'response_format'   => array( 'type' => 'json_object' ),
			)
		);

		$response = wp_remote_post(
			$endpoint,
			array(
				'timeout' => 60,
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => $body,
			)
		);

		$raw = self::request_http_body( $response, 'openai' );
		if ( is_wp_error( $raw ) ) {
			return $raw;
		}

		$data = json_decode( $raw, true );
		if ( isset( $data['choices'][0]['message']['content'] ) ) {
			return trim( (string) $data['choices'][0]['message']['content'] );
		}

		return new WP_Error( 'vefg_ai_openai', __( 'Unexpected chat completion response.', 'vms-elements-form-guard' ) );
	}

	/**
	 * @param array<string, mixed> $c .
	 */
	private static function anthropic( array $c, string $system, string $user ) {
		$key = $c['anthropic_api_key'] ?? '';
		if ( $key === '' ) {
			return new WP_Error( 'vefg_ai_key', __( 'Anthropic API key is missing.', 'vms-elements-form-guard' ) );
		}

		$model = (string) ( $c['anthropic_model'] ?? 'claude-3-5-haiku-20241022' );
		if ( $model === '' ) {
			$model = 'claude-3-5-haiku-20241022';
		}
		$body  = wp_json_encode(
			array(
				'model'      => $model,
				'max_tokens' => 512,
				'system'     => $system,
				'messages'   => array(
					array(
						'role'    => 'user',
						'content' => $user,
					),
				),
			)
		);

		$response = wp_remote_post(
			'https://api.anthropic.com/v1/messages',
			array(
				'timeout' => 60,
				'headers' => array(
					'x-api-key'         => $key,
					'anthropic-version' => '2023-06-01',
					'Content-Type'      => 'application/json',
				),
				'body'    => $body,
			)
		);

		$raw = self::request_http_body( $response, 'anthropic' );
		if ( is_wp_error( $raw ) ) {
			return $raw;
		}

		$data = json_decode( $raw, true );
		if ( isset( $data['content'][0]['text'] ) ) {
			return trim( (string) $data['content'][0]['text'] );
		}

		return new WP_Error( 'vefg_ai_anthropic', __( 'Unexpected Anthropic response.', 'vms-elements-form-guard' ) );
	}

	/**
	 * @param array<string, mixed> $c .
	 */
	private static function gemini( array $c, string $system, string $user ) {
		$key = $c['gemini_api_key'] ?? '';
		if ( $key === '' ) {
			return new WP_Error( 'vefg_ai_key', __( 'Google Gemini API key is missing.', 'vms-elements-form-guard' ) );
		}

		$model = (string) ( $c['gemini_model'] ?? 'gemini-1.5-flash' );
		if ( $model === '' ) {
			$model = 'gemini-1.5-flash';
		}
		$model = preg_replace( '/^models\//', '', $model );
		$url   = sprintf(
			'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
			rawurlencode( $model ),
			rawurlencode( $key )
		);

		$prompt = $system . "\n\n" . $user;
		$body   = wp_json_encode(
			array(
				'contents'         => array(
					array(
						'parts' => array(
							array( 'text' => $prompt ),
						),
					),
				),
				'generationConfig' => array(
					'temperature'      => 0.2,
					'responseMimeType' => 'application/json',
				),
			)
		);

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 60,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => $body,
			)
		);

		$raw = self::request_http_body( $response, 'gemini' );
		if ( is_wp_error( $raw ) ) {
			return $raw;
		}

		$data = json_decode( $raw, true );
		if ( isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
			return trim( (string) $data['candidates'][0]['content']['parts'][0]['text'] );
		}

		return new WP_Error( 'vefg_ai_gemini', __( 'Unexpected Gemini response.', 'vms-elements-form-guard' ) );
	}

	/**
	 * @param array<string, mixed> $c .
	 */
	private static function bedrock( array $c, string $system, string $user ) {
		$access = $c['bedrock_access_key'] ?? '';
		$secret = $c['bedrock_secret_key'] ?? '';
		$region = $c['bedrock_region'] ?? 'us-east-1';
		$model  = trim( (string) ( $c['bedrock_model'] ?? '' ) );

		if ( $access === '' || $secret === '' || $model === '' ) {
			return new WP_Error( 'vefg_ai_bedrock', __( 'AWS Bedrock access key, secret, and model ID are required.', 'vms-elements-form-guard' ) );
		}

		$built = self::bedrock_build_body( $model, $system, $user );
		if ( is_wp_error( $built ) ) {
			return $built;
		}

		$body   = $built['body'];
		$parser = $built['parser'];

		$host = 'bedrock-runtime.' . $region . '.amazonaws.com';
		$path = '/model/' . $model . '/invoke';

		$signed = AI_Span_Aws_SigV4::sign_request( $access, $secret, $region, 'bedrock', 'POST', $host, $path, $body );
		if ( is_wp_error( $signed ) ) {
			return $signed;
		}

		$response = wp_remote_post(
			'https://' . $host . $path,
			array(
				'timeout' => 90,
				'headers' => $signed['headers'],
				'body'    => $body,
			)
		);

		$raw = self::request_http_body( $response, 'bedrock' );
		if ( is_wp_error( $raw ) ) {
			return $raw;
		}

		return self::bedrock_parse_response( $raw, $parser );
	}

	/**
	 * @return array{body:string,parser:string}|WP_Error
	 */
	private static function bedrock_build_body( string $model, string $system, string $user ) {
		if ( self::bedrock_model_is_anthropic( $model ) ) {
			$body = wp_json_encode(
				array(
					'anthropic_version' => 'bedrock-2023-05-31',
					'max_tokens'        => 512,
					'system'            => $system,
					'messages'          => array(
						array(
							'role'    => 'user',
							'content' => $user,
						),
					),
				)
			);
			return array(
				'body' => (string) $body,
				'parser' => 'anthropic',
			);
		}

		if ( self::bedrock_model_is_meta( $model ) ) {
			$prompt = "Human: {$system}\n\n{$user}\n\nAssistant:";
			$body   = wp_json_encode(
				array(
					'prompt'      => $prompt,
					'max_gen_len' => 512,
					'temperature' => 0.2,
					'top_p'       => 0.9,
				)
			);
			return array(
				'body' => (string) $body,
				'parser' => 'meta',
			);
		}

		if ( self::bedrock_model_is_mistral( $model ) ) {
			$prompt = '<s>[INST] ' . $system . "\n\n" . $user . ' [/INST]';
			$body   = wp_json_encode(
				array(
					'prompt'      => $prompt,
					'max_tokens'  => 512,
					'temperature' => 0.2,
					'top_p'       => 0.9,
				)
			);
			return array(
				'body' => (string) $body,
				'parser' => 'mistral',
			);
		}

		if ( self::bedrock_model_is_titan( $model ) ) {
			$input = $system . "\n\n" . $user;
			$body  = wp_json_encode(
				array(
					'inputText'            => $input,
					'textGenerationConfig' => array(
						'maxTokenCount' => 512,
						'temperature'   => 0.2,
						'topP'          => 0.9,
					),
				)
			);
			return array(
				'body' => (string) $body,
				'parser' => 'titan',
			);
		}

		return new WP_Error(
			'vefg_ai_bedrock_model',
			__( 'Unrecognized Bedrock model ID. Use an Anthropic, Meta Llama, Mistral, or Amazon Titan text model ID from AWS.', 'vms-elements-form-guard' )
		);
	}

	private static function bedrock_model_is_anthropic( string $model ): bool {
		return strpos( $model, 'anthropic.' ) === 0;
	}

	private static function bedrock_model_is_meta( string $model ): bool {
		return strpos( $model, 'meta.' ) === 0 || strpos( $model, 'us.meta.' ) === 0;
	}

	private static function bedrock_model_is_mistral( string $model ): bool {
		return strpos( $model, 'mistral.' ) === 0;
	}

	private static function bedrock_model_is_titan( string $model ): bool {
		return strpos( $model, 'amazon.titan-text' ) === 0;
	}

	/**
	 * @param string $raw JSON body.
	 * @return string|WP_Error
	 */
	private static function bedrock_parse_response( string $raw, string $parser ) {
		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			return new WP_Error( 'vefg_ai_bedrock_parse', __( 'Unexpected Bedrock response.', 'vms-elements-form-guard' ) );
		}

		if ( 'anthropic' === $parser && isset( $data['content'][0]['text'] ) ) {
			return trim( (string) $data['content'][0]['text'] );
		}
		if ( 'meta' === $parser && isset( $data['generation'] ) ) {
			return trim( (string) $data['generation'] );
		}
		if ( 'mistral' === $parser && isset( $data['outputs'][0]['text'] ) ) {
			return trim( (string) $data['outputs'][0]['text'] );
		}
		if ( 'titan' === $parser && isset( $data['results'][0]['outputText'] ) ) {
			return trim( (string) $data['results'][0]['outputText'] );
		}

		return new WP_Error( 'vefg_ai_bedrock_parse', __( 'Unexpected Bedrock response shape for this model family.', 'vms-elements-form-guard' ) );
	}

	/**
	 * @param \WP_Error|array $response .
	 * @param string          $ctx      Context label for errors.
	 * @return string|WP_Error Response body on success.
	 */
	private static function request_http_body( $response, string $ctx ) {
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		if ( $code < 200 || $code >= 300 ) {
			return new WP_Error(
				'vefg_ai_http',
				sprintf(
					/* translators: 1: provider context, 2: HTTP code */
					__( 'AI request failed (%1$s) HTTP %2$s.', 'vms-elements-form-guard' ),
					$ctx,
					(string) $code
				)
			);
		}

		return $body;
	}
}
