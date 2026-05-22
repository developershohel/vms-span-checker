<?php
/**
 * Contract for one pluggable spam check step (heuristic pipeline).
 *
 * @package VMS_Span_Checker
 */

namespace VMS_Span_Checker\Spam;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Implementations run in order; first WP_Error stops the pipeline.
 */
interface Spam_Check_Component {

	public function get_id(): string;

	/**
	 * Short admin-facing name (documentation, future UI).
	 */
	public function get_label(): string;

	/**
	 * @param array<string, mixed> $commentdata Raw comment payload.
	 * @param array<string, mixed> $config      Merged AI_Span_Config / spam options.
	 * @return true|\WP_Error
	 */
	public function evaluate( array $commentdata, array $config );
}
