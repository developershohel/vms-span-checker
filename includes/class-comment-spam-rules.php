<?php
/**
 * Facade for heuristic comment anti-spam (delegates to Spam component pipeline).
 *
 * @package VMS_Span_Checker
 */

namespace VMS_Span_Checker;

use VMS_Span_Checker\Spam\Comment_Spam_Controller;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @deprecated Prefer Comment_Spam_Controller::run() in new code; kept for backward compatibility.
 */
class Comment_Spam_Rules {

	/**
	 * @param array<string, mixed> $commentdata Raw comment payload.
	 * @param array<string, mixed> $c           Merged AI_Span_Config.
	 * @return true|\WP_Error
	 */
	public static function evaluate( array $commentdata, array $c ) {
		return Comment_Spam_Controller::run( $commentdata, $c );
	}
}
