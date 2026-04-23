<?php
add_shortcode('wp_span_form', 'wp_span_form_shortcode');
function wp_span_form_shortcode($atts) {
	global $wpdb;
	$atts = shortcode_atts(['id'=>0], $atts);
	$table = $wpdb->prefix . 'span_checker_forms';
	$form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $atts['id']), ARRAY_A);
	if (!$form) return '';

	$fields = json_decode($form['fields'], true);
	$html = '<form class="'.esc_attr($form['form_id']).'">';
	foreach($fields as $f) {
		$req = !empty($f['required']) ? 'required' : '';
		$placeholder = esc_attr($f['placeholder'] ?? '');
		$name = esc_attr($f['name']);
		$label = esc_html($f['label']);
		$type = esc_attr($f['type']);

		if($type === 'textarea') {
			$html .= "<label>$label <textarea name='$name' placeholder='$placeholder' $req></textarea></label>";
		} elseif($type === 'select') {
			$html .= "<label>$label <select name='$name' $req></select></label>";
		} else {
			$html .= "<label>$label <input type='$type' name='$name' placeholder='$placeholder' $req></label>";
		}
	}
	$html .= '<input type="submit" value="Submit">';
	$html .= '</form>';

	return $html;
}
