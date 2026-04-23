<?php
/**
 * Shortcode renderer for stored forms.
 *
 * @package WP_Span_Checker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcode callback: [wp_span_form id="1"].
 *
 * @param array<string, string>|string $atts Shortcode attributes.
 * @return string HTML form markup.
 */
function wp_span_form_shortcode( $atts ) {
	global $wpdb;

	$atts = shortcode_atts(
		array(
			'id' => 0,
		),
		$atts,
		'wp_span_form'
	);

	$form_id = (int) $atts['id'];
	if ( $form_id < 1 ) {
		return '';
	}

	$table = $wpdb->prefix . 'span_checker_forms';
	$form  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $form_id ), ARRAY_A );

	if ( ! $form || empty( $form['fields'] ) ) {
		return '';
	}

	$fields = json_decode( $form['fields'], true );
	if ( ! is_array( $fields ) ) {
		return '';
	}

	$html = '<form class="' . esc_attr( $form['form_id'] ) . '">';
	foreach ( $fields as $f ) {
		if ( ! is_array( $f ) ) {
			continue;
		}
		$req         = ! empty( $f['required'] ) ? ' required' : '';
		$placeholder = isset( $f['placeholder'] ) ? esc_attr( $f['placeholder'] ) : '';
		$name        = isset( $f['name'] ) ? esc_attr( $f['name'] ) : '';
		$label       = isset( $f['label'] ) ? esc_html( $f['label'] ) : '';
		$type        = isset( $f['type'] ) ? esc_attr( $f['type'] ) : 'text';

		if ( 'textarea' === $type ) {
			$html .= '<label>' . $label . ' <textarea name="' . $name . '" placeholder="' . $placeholder . '"' . $req . '></textarea></label>';
		} elseif ( 'select' === $type ) {
			$html .= '<label>' . $label . ' <select name="' . $name . '"' . $req . '></select></label>';
		} else {
			$html .= '<label>' . $label . ' <input type="' . $type . '" name="' . $name . '" placeholder="' . $placeholder . '"' . $req . ' /></label>';
		}
	}
	$html .= '<input type="submit" value="' . esc_attr__( 'Submit', 'wp-span-checker' ) . '" />';
	$html .= '</form>';

	return $html;
}

add_shortcode( 'wp_span_form', 'wp_span_form_shortcode' );
