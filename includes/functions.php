<?php
/**
 * Shared helpers for WP Span Checker.
 *
 * @package WP_Span_Checker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dump a WordPress table to a .sql file using $wpdb (requires mysqldump on server).
 *
 * @param string $table_name Table name without prefix, e.g., 'posts'.
 * @param string $file_path  Full path to save the SQL file.
 * @return string Success or error message.
 */
function wp_dump_table( $table_name, $file_path ) {
	global $wpdb;

	$full_table_name = $wpdb->prefix . $table_name;

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is built from trusted prefix + sanitized slug.
	$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $full_table_name ) );
	if ( ! $exists ) {
		/* translators: %s: database table name */
		return sprintf( __( 'Table %s does not exist.', 'wp-span-checker' ), $full_table_name );
	}

	$db_name = DB_NAME;
	$db_user = DB_USER;
	$db_pass = DB_PASSWORD;
	$db_host = DB_HOST;

	$file_path = str_replace( '\\', '/', $file_path );

	// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec -- Intentional optional dev utility; server must have mysqldump.
	$command = sprintf(
		'mysqldump --user=%s --password=%s --host=%s %s %s > %s',
		escapeshellarg( $db_user ),
		escapeshellarg( $db_pass ),
		escapeshellarg( $db_host ),
		escapeshellarg( $db_name ),
		escapeshellarg( $full_table_name ),
		escapeshellarg( $file_path )
	);

	$output     = null;
	$return_var = null;
	// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
	exec( $command, $output, $return_var );

	if ( 0 === $return_var ) {
		/* translators: %s: export file path */
		return sprintf( __( 'Table exported successfully to %s.', 'wp-span-checker' ), $file_path );
	}

	/* translators: %d: shell exit code */
	return sprintf( __( 'Export failed. Return code: %d', 'wp-span-checker' ), $return_var );
}

/**
 * Best-effort visitor IP for logging.
 *
 * @return string
 */
function wp_span_checker_get_user_ip() {
	$ip_keys = array(
		'HTTP_CF_CONNECTING_IP',
		'HTTP_X_FORWARDED_FOR',
		'HTTP_CLIENT_IP',
		'REMOTE_ADDR',
	);

	foreach ( $ip_keys as $key ) {
		if ( empty( $_SERVER[ $key ] ) ) {
			continue;
		}

		$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );

		if ( false !== strpos( $ip, ',' ) ) {
			$parts = explode( ',', $ip );
			$ip    = $parts[0];
		}

		return trim( $ip );
	}

	return '0.0.0.0';
}

/**
 * Normalize user or URL input to a hostname for validation.
 *
 * @param string $input Raw domain, email host, or URL.
 * @return string Lowercase hostname or empty string.
 */
function wp_span_checker_normalize_domain_input( $input ) {
	$input = is_string( $input ) ? trim( $input ) : '';
	if ( '' === $input ) {
		return '';
	}

	if ( preg_match( '#^https?://#i', $input ) ) {
		$host = wp_parse_url( $input, PHP_URL_HOST );
		return $host ? strtolower( $host ) : '';
	}

	$input = preg_replace( '#/.*$#', '', $input );
	$input = preg_replace( '#^www\.#i', '', $input );

	return strtolower( $input );
}

/**
 * Parse API validation toggles from AJAX POST (settings[0][is_webrisk] style).
 *
 * @param array $post Typically wp_unslash( $_POST ).
 * @return array{is_webrisk:bool,is_virustotal:bool}
 */
function wp_span_checker_parse_validation_settings( array $post ) {
	$raw = isset( $post['settings'] ) ? $post['settings'] : array();
	if ( is_array( $raw ) && isset( $raw[0] ) && is_array( $raw[0] ) ) {
		$row = $raw[0];
		return array(
			'is_webrisk'    => ! empty( $row['is_webrisk'] ) && '0' !== (string) $row['is_webrisk'],
			'is_virustotal' => ! empty( $row['is_virustotal'] ) && '0' !== (string) $row['is_virustotal'],
		);
	}

	return array(
		'is_webrisk'    => false,
		'is_virustotal' => false,
	);
}

/**
 * Whether a string looks like a CSS selector for Form Guard (combined id/classes).
 *
 * @param string $raw Raw stored form_id value.
 */
function wp_span_checker_form_guard_is_combined_selector( string $raw ): bool {
	$raw = trim( $raw );
	if ( '' === $raw ) {
		return false;
	}
	return false !== strpos( $raw, '#' ) || false !== strpos( $raw, '.' ) || false !== strpos( $raw, '[' );
}

/**
 * Safe preg_match for admin-supplied delimited patterns (ReDoS mitigation: length + delimiter form only).
 *
 * @param string $pattern Delimited regex e.g. /^abc$/u .
 * @param string $value   Subject string.
 */
function wp_span_checker_form_guard_preg_match_safe( string $pattern, string $value ): bool {
	$pattern = trim( $pattern );
	if ( strlen( $pattern ) > 512 || strlen( $pattern ) < 3 ) {
		return false;
	}
	if ( ! preg_match( '#^/.+/[a-zA-Z]*$#', $pattern ) ) {
		return false;
	}
	$m = @preg_match( $pattern, $value );

	return 1 === $m;
}

/**
 * Extract URLs from plain text (textarea link policy).
 *
 * @return string[]
 */
function wp_span_checker_form_guard_extract_urls( string $text ): array {
	if ( '' === trim( $text ) ) {
		return array();
	}
	if ( ! preg_match_all( '#https?://[^\s<>"\']+#i', $text, $matches ) ) {
		return array();
	}
	$out = array();
	foreach ( $matches[0] as $u ) {
		$u = rtrim( $u, '.,);]\'"' );
		if ( $u !== '' ) {
			$out[] = $u;
		}
	}

	return array_values( array_unique( $out ) );
}

/**
 * Merge per-field API flags with legacy row-level defaults.
 *
 * @param array<string, mixed> $field Field config from JSON settings.
 * @param array<string, mixed> $row   Full DB row.
 * @return array{is_webrisk:bool,is_virustotal:bool}
 */
function wp_span_checker_form_guard_field_api_flags( array $field, array $row ): array {
	$wr = isset( $field['is_webrisk'] ) ? (int) $field['is_webrisk'] : null;
	$vt = isset( $field['is_virustotal'] ) ? (int) $field['is_virustotal'] : null;

	return array(
		'is_webrisk'    => null !== $wr ? (bool) $wr : ! empty( $row['is_webrisk'] ),
		'is_virustotal' => null !== $vt ? (bool) $vt : ! empty( $row['is_virustotal'] ),
	);
}

/**
 * Output the standard plugin admin page heading (kicker, H1, optional lede).
 *
 * Use the same translated strings as submenu titles in Admin_Menu for consistency.
 *
 * @param string $title Already-translated H1 text.
 * @param string $lede  Optional already-translated intro; pass '' to omit the lede paragraph.
 */
function wp_span_checker_admin_page_header( $title, $lede = '' ) {
	$wsc_header_title = (string) $title;
	$wsc_header_lede  = (string) $lede;
	require WP_SPAN_CHECKER_DIR . 'templates/partials/admin-page-header.php';
}

/**
 * Output an accessible toggle switch (styled checkbox) for plugin admin forms.
 *
 * @param array<string, mixed> $args {
 *     @type string $name        Input `name` attribute (required).
 *     @type bool   $checked     Whether the control is on.
 *     @type string $value       Value when checked. Default `1`.
 *     @type string $id          Optional `id` attribute.
 *     @type string $label       Primary line next to the switch.
 *     @type string $description Secondary muted line under the label.
 *     @type string $input_class Extra classes on the checkbox input.
 *     @type string $wrapper_class Classes on the outer `<label>`.
 *     @type bool   $compact     Smaller track (dense lists).
 * }
 */
function wp_span_checker_admin_switch( array $args ): void {
	$name = isset( $args['name'] ) ? (string) $args['name'] : '';
	if ( $name === '' ) {
		return;
	}

	$checked     = ! empty( $args['checked'] );
	$value       = isset( $args['value'] ) ? (string) $args['value'] : '1';
	$id          = isset( $args['id'] ) ? (string) $args['id'] : '';
	$label       = isset( $args['label'] ) ? (string) $args['label'] : '';
	$description = isset( $args['description'] ) ? (string) $args['description'] : '';
	$input_class = isset( $args['input_class'] ) ? trim( (string) $args['input_class'] ) : '';
	$wrapper     = isset( $args['wrapper_class'] ) ? trim( (string) $args['wrapper_class'] ) : '';
	$compact     = ! empty( $args['compact'] );

	$label_class = 'wsc-switch';
	if ( $compact ) {
		$label_class .= ' wsc-switch--compact';
	}
	if ( $wrapper !== '' ) {
		$label_class .= ' ' . $wrapper;
	}

	$input_classes = trim( 'wsc-switch__input ' . $input_class );

	echo '<label class="' . esc_attr( $label_class ) . '">';
	echo '<input type="checkbox" class="' . esc_attr( $input_classes ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '"';
	if ( $id !== '' ) {
		echo ' id="' . esc_attr( $id ) . '"';
	}
	checked( $checked );
	echo ' />';
	echo '<span class="wsc-switch__track" aria-hidden="true"></span>';

	if ( $label !== '' || $description !== '' ) {
		echo '<span class="wsc-switch__body">';
		if ( $label !== '' ) {
			echo '<span class="wsc-switch__label">' . esc_html( $label ) . '</span>';
		}
		if ( $description !== '' ) {
			echo '<span class="wsc-switch__desc">' . esc_html( $description ) . '</span>';
		}
		echo '</span>';
	}

	echo '</label>';
}

/**
 * Post types shown in AI summary settings (all suitable types, not only `public`).
 *
 * Includes pages, WooCommerce products, and other CPTs that are public, publicly queryable,
 * or have an admin UI. Core infrastructure types (attachments, revisions, FSE templates, …)
 * are excluded.
 *
 * @return array<string, \WP_Post_Type> Post type name => object, sorted by singular label.
 */
function wp_span_checker_summary_selectable_post_types(): array {
	static $cache = null;
	if ( is_array( $cache ) ) {
		return $cache;
	}

	$exclude = array(
		'attachment',
		'revision',
		'nav_menu_item',
		'custom_css',
		'customize_changeset',
		'oembed_cache',
		'user_request',
		'wp_block',
		'wp_template',
		'wp_template_part',
		'wp_global_styles',
		'wp_navigation',
		'wp_font_family',
		'wp_font_face',
	);

	$objects = get_post_types( array(), 'objects' );
	$out     = array();

	foreach ( $objects as $name => $pt ) {
		if ( ! ( $pt instanceof \WP_Post_Type ) || in_array( $name, $exclude, true ) ) {
			continue;
		}
		if ( $pt->show_ui || $pt->publicly_queryable || $pt->public ) {
			$out[ $name ] = $pt;
		}
	}

	uasort(
		$out,
		static function ( $a, $b ) {
			$la = $a->labels->singular_name ?? $a->labels->name ?? $a->name;
			$lb = $b->labels->singular_name ?? $b->labels->name ?? $b->name;
			return strcasecmp( (string) $la, (string) $lb );
		}
	);

	/**
	 * Filter post types listed under AI → Summaries.
	 *
	 * @param array<string, \WP_Post_Type> $out     Candidate types keyed by slug.
	 * @param array<int, string>           $exclude Internal slugs always omitted.
	 */
	$cache = apply_filters( 'wsc_summary_selectable_post_types', $out, $exclude );

	return is_array( $cache ) ? $cache : $out;
}

/**
 * Preset slugs for “where this mapping runs” (not numeric IDs).
 *
 * @return array<string, string> slug => translated label
 */
function wp_span_checker_page_target_presets(): array {
	return array(
		'all-pages'        => __( 'Entire site (every page & view)', 'wp-span-checker' ),
		'front-page'       => __( 'Front page', 'wp-span-checker' ),
		'home-blog'        => __( 'Blog / posts index', 'wp-span-checker' ),
		'singular-page'    => __( 'Any single page', 'wp-span-checker' ),
		'singular-post'    => __( 'Any single post', 'wp-span-checker' ),
		'singular-any'     => __( 'Any singular content', 'wp-span-checker' ),
		'archive-any'      => __( 'Any archive', 'wp-span-checker' ),
		'archive-category' => __( 'Category archives', 'wp-span-checker' ),
		'archive-tag'      => __( 'Tag archives', 'wp-span-checker' ),
		'search'           => __( 'Search results', 'wp-span-checker' ),
		'404'              => __( '404 error page', 'wp-span-checker' ),
	);
}

/**
 * Allowed preset keys (for sanitizing incoming JSON).
 *
 * @return array<int, string>
 */
function wp_span_checker_page_target_preset_slugs(): array {
	return array_keys( wp_span_checker_page_target_presets() );
}

/**
 * Get WordPress body class for each preset slug (for frontend matching).
 *
 * @return array<string, string> slug => body class
 */
function wp_span_checker_preset_body_classes(): array {
	return array(
		'all-pages'        => '',
		'front-page'       => 'home',
		'home-blog'        => 'blog',
		'singular-page'    => 'page',
		'singular-post'    => 'single-post',
		'singular-any'     => 'singular',
		'archive-any'      => 'archive',
		'archive-category' => 'category',
		'archive-tag'      => 'tag',
		'search'           => 'search',
		'404'              => 'error404',
	);
}

/**
 * Normalize DB page_id value to a list of targets (legacy single string or JSON array).
 *
 * @param mixed $raw page_id column value.
 * @return array<int, string>
 */
function wp_span_checker_normalize_page_targets( $raw ): array {
	$raw = is_string( $raw ) ? trim( $raw ) : '';
	if ( '' === $raw ) {
		return array( 'all-pages' );
	}

	$decoded = json_decode( $raw, true );
	if ( is_array( $decoded ) && array() !== $decoded ) {
		$out = array();
		foreach ( $decoded as $item ) {
			$item = is_string( $item ) || is_numeric( $item ) ? trim( (string) $item ) : '';
			if ( '' !== $item ) {
				$out[] = $item;
			}
		}
		return array_values( array_unique( $out ) );
	}

	return array( $raw );
}

/**
 * Whether one target token matches the current request (front-end).
 *
 * @param string $target Preset slug or post ID string.
 */
function wp_span_checker_current_request_matches_target( string $target ): bool {
	$target = trim( $target );
	if ( '' === $target || 'all-pages' === $target ) {
		return true;
	}

	switch ( $target ) {
		case 'front-page':
			return is_front_page();
		case 'home-blog':
			return is_home();
		case 'singular-page':
			return is_singular( 'page' );
		case 'singular-post':
			return is_singular( 'post' );
		case 'singular-any':
			return is_singular();
		case 'archive-any':
			return is_archive();
		case 'archive-category':
			return is_category();
		case 'archive-tag':
			return is_tag();
		case 'search':
			return is_search();
		case '404':
			return is_404();
	}

	if ( ctype_digit( $target ) ) {
		$id = (int) $target;
		if ( $id <= 0 ) {
			return false;
		}
		if ( is_singular() && (int) get_queried_object_id() === $id ) {
			return true;
		}
		$page_for_posts = (int) get_option( 'page_for_posts' );
		if ( $page_for_posts && $id === $page_for_posts && is_home() ) {
			return true;
		}
		return false;
	}

	return false;
}

/**
 * Whether a form_settings row should load on this request (OR across targets).
 *
 * @param array<string, mixed> $row DB row.
 */
function wp_span_checker_row_matches_current_request( array $row ): bool {
	$targets = wp_span_checker_normalize_page_targets( $row['page_id'] ?? '' );
	foreach ( $targets as $t ) {
		if ( wp_span_checker_current_request_matches_target( (string) $t ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Get current page type for frontend localization.
 *
 * @return string page|post|common
 */
function wp_span_checker_get_current_page_type(): string {
	if ( is_singular( 'page' ) ) {
		return 'page';
	}
	if ( is_singular( 'post' ) ) {
		return 'post';
	}
	return 'common';
}

/**
 * Get body classes that match current request for frontend targeting.
 *
 * @return array<int, string>
 */
function wp_span_checker_get_current_body_classes(): array {
	$classes = array();
	
	if ( is_front_page() ) {
		$classes[] = 'home';
	}
	if ( is_home() ) {
		$classes[] = 'blog';
	}
	if ( is_singular( 'page' ) ) {
		$classes[] = 'page';
		$classes[] = 'page-id-' . get_queried_object_id();
	}
	if ( is_singular( 'post' ) ) {
		$classes[] = 'single-post';
		$classes[] = 'postid-' . get_queried_object_id();
	}
	if ( is_singular() ) {
		$classes[] = 'singular';
	}
	if ( is_archive() ) {
		$classes[] = 'archive';
	}
	if ( is_category() ) {
		$classes[] = 'category';
	}
	if ( is_tag() ) {
		$classes[] = 'tag';
	}
	if ( is_search() ) {
		$classes[] = 'search';
	}
	if ( is_404() ) {
		$classes[] = 'error404';
	}
	
	return $classes;
}

/**
 * Sanitize page targets from AJAX into a stored page_id string (JSON array).
 *
 * @param mixed $raw POST pageId (JSON string or legacy scalar).
 */
function wp_span_checker_sanitize_page_targets_param( $raw ): string {
	$allowed = array_flip( wp_span_checker_page_target_preset_slugs() );
	$clean   = array();

	if ( is_array( $raw ) ) {
		$list = $raw;
	} else {
		$s = is_string( $raw ) ? trim( wp_unslash( $raw ) ) : '';
		if ( '' === $s ) {
			return wp_json_encode( array( 'all-pages' ) );
		}
		$decoded = json_decode( $s, true );
		if ( is_array( $decoded ) ) {
			$list = $decoded;
		} else {
			$list = array( sanitize_text_field( $s ) );
		}
	}

	foreach ( $list as $item ) {
		$item = is_string( $item ) || is_numeric( $item ) ? trim( (string) $item ) : '';
		if ( '' === $item ) {
			continue;
		}
		if ( isset( $allowed[ $item ] ) ) {
			$clean[] = $item;
			continue;
		}
		if ( ctype_digit( $item ) ) {
			$clean[] = (string) absint( $item );
		}
	}

	$clean = array_values( array_unique( $clean ) );
	if ( array() === $clean ) {
		return wp_json_encode( array( 'all-pages' ) );
	}

	return wp_json_encode( $clean );
}

/**
 * Localized strings for front-end and admin JavaScript (wp_localize_script).
 *
 * @return array<string, string>
 */
function wp_span_checker_get_js_i18n() {
	return array(
		'formNotFound'             => __( 'Form not found. Check Form ID / class under WP Span Checker Form Guard.', 'wp-span-checker' ),
		'emailInvalid'             => __( 'Email address is invalid', 'wp-span-checker' ),
		'validationFailed'         => __( 'Validation failed', 'wp-span-checker' ),
		'emailRequired'            => __( 'Valid email is required', 'wp-span-checker' ),
		'emailFieldRequired'       => __( 'Email is required', 'wp-span-checker' ),
		'passwordRequired'         => __( 'Password is required', 'wp-span-checker' ),
		'passwordRequirements'     => __( 'Password must meet all requirements.', 'wp-span-checker' ),
		'urlRequired'              => __( 'URL is required', 'wp-span-checker' ),
		'urlNotValid'              => __( 'URL not valid', 'wp-span-checker' ),
		'urlValid'                 => __( 'URL is valid', 'wp-span-checker' ),
		'confirmDeleteDomain'      => __( 'Are you sure you want to delete this domain?', 'wp-span-checker' ),
		'confirmDeleteDomainTitle' => __( 'Remove this domain?', 'wp-span-checker' ),
		'confirmDeleteFormSetting' => __( 'Are you sure you want to delete this Form Guard mapping?', 'wp-span-checker' ),
		'confirmDeleteFormTitle'   => __( 'Remove this Form Guard mapping?', 'wp-span-checker' ),
		'cancel'                   => __( 'Cancel', 'wp-span-checker' ),
		'domainAdded'              => __( 'Domain added.', 'wp-span-checker' ),
		'domainRemoved'            => __( 'Domain removed.', 'wp-span-checker' ),
		'formSettingRemoved'       => __( 'Form Guard mapping removed.', 'wp-span-checker' ),
		'errorAddingDomain'        => __( 'Error adding domain.', 'wp-span-checker' ),
		'errorDeletingDomain'      => __( 'Error deleting domain.', 'wp-span-checker' ),
		'errorDeletingSetting'     => __( 'Could not delete Form Guard mapping.', 'wp-span-checker' ),
		'saved'                    => __( 'Saved', 'wp-span-checker' ),
		'delete'                   => __( 'Delete', 'wp-span-checker' ),
		'edit'                     => __( 'Edit', 'wp-span-checker' ),
		'copied'                   => __( 'Copied', 'wp-span-checker' ),
		'copy'                     => __( 'Copy', 'wp-span-checker' ),
		'examplePrefix'            => __( 'Example:', 'wp-span-checker' ),
		'copyFailed'               => __( 'Could not copy.', 'wp-span-checker' ),
		'requestFailed'            => __( 'Request failed', 'wp-span-checker' ),
		'validating'               => __( 'Validating...', 'wp-span-checker' ),
		'submitting'               => __( 'Submitting...', 'wp-span-checker' ),
		'validationPassed'         => __( 'Validation passed', 'wp-span-checker' ),
		'submit'                   => __( 'Submit', 'wp-span-checker' ),
		'fieldType'                => __( 'Field type', 'wp-span-checker' ),
		'fieldId'                  => __( 'Field ID', 'wp-span-checker' ),
		'fieldClass'               => __( 'Field class', 'wp-span-checker' ),
		'eventName'                => __( 'Event name', 'wp-span-checker' ),
		'formField'                => __( 'Form field', 'wp-span-checker' ),
		'javascriptEvent'          => __( 'JavaScript event', 'wp-span-checker' ),
		'optionUrl'                => __( 'URL', 'wp-span-checker' ),
		'optionEmail'              => __( 'Email', 'wp-span-checker' ),
		'optionText'               => __( 'Text', 'wp-span-checker' ),
		'optionUsername'           => __( 'Username', 'wp-span-checker' ),
		'optionChange'             => __( 'Change', 'wp-span-checker' ),
		'optionInput'              => __( 'Input', 'wp-span-checker' ),
		'optionFormSubmit'         => __( 'Form submit', 'wp-span-checker' ),
		'labelId'                  => __( 'ID', 'wp-span-checker' ),
		'labelClass'               => __( 'Class', 'wp-span-checker' ),
		'selectFieldType'          => __( 'Select field type', 'wp-span-checker' ),
		'optionTextarea'           => __( 'Textarea', 'wp-span-checker' ),
		'optionTel'                => __( 'Telephone', 'wp-span-checker' ),
		'optionNumber'             => __( 'Number', 'wp-span-checker' ),
		'optionPassword'           => __( 'Password', 'wp-span-checker' ),
		'enable'                   => __( 'Enable', 'wp-span-checker' ),
		'disable'                  => __( 'Disable', 'wp-span-checker' ),
		'requiredField'            => __( 'Required field', 'wp-span-checker' ),
		'requiredFieldHint'        => __( 'Mark the field as required in the browser.', 'wp-span-checker' ),
		'requireValidation'        => __( 'Require validation', 'wp-span-checker' ),
		'requireValidationHint'    => __( 'Run server-side validation for this field.', 'wp-span-checker' ),
		'googleWebRisk'            => __( 'Google Web Risk', 'wp-span-checker' ),
		'virusTotal'               => __( 'VirusTotal scanner', 'wp-span-checker' ),
		'usernameTakenCheck'       => __( 'Reject if username exists (live check)', 'wp-span-checker' ),
		'usernameTakenHint'        => __( 'Use for registration/login name inputs. When enabled, checks WordPress while typing (debounced) and on submit.', 'wp-span-checker' ),
		'textareaAllowLinks'       => __( 'Allow links in message', 'wp-span-checker' ),
		'textareaAiSpam'           => __( 'AI spam checker (textarea)', 'wp-span-checker' ),
		'textareaAiSpamHint'       => __( 'Uses AI settings from WP Span Checker → AI. Runs on the server when validation is enabled.', 'wp-span-checker' ),
		'textAllowUrls'            => __( 'Allow URLs in value', 'wp-span-checker' ),
		'textAllowUrlsHint'        => __( 'Disable to reject http(s) URLs typed into this single-line field.', 'wp-span-checker' ),
		'customRegex'              => __( 'Custom regex (delimited)', 'wp-span-checker' ),
		'customRegexHint'          => __( 'Optional. Must look like /pattern/flags. Checked on the server when validation is enabled.', 'wp-span-checker' ),
		'presetRegex'              => __( 'Preset patterns', 'wp-span-checker' ),
		'validExample'             => __( 'Valid', 'wp-span-checker' ),
		'invalidExample'           => __( 'Invalid', 'wp-span-checker' ),
		'usePattern'               => __( 'Use pattern', 'wp-span-checker' ),
		'fgNeedOneField'           => __( 'Keep at least one field row.', 'wp-span-checker' ),
		'mappedFieldTitle'         => __( 'Mapped form control', 'wp-span-checker' ),
		'mappedFieldGuardsBlurb'   => __( 'Guards in this row apply only to this field’s ID/class. Use “Add field” for each separate input (10 fields → 10 rows).', 'wp-span-checker' ),
		'fieldGuardsLegend'        => __( 'Guards for this field only', 'wp-span-checker' ),
		'securityMethodsLegend'    => __( 'Protection methods (based on field type)', 'wp-span-checker' ),
		'securityMethodsIntro'     => __( 'Email and URL rows show Web Risk and VirusTotal (Web Risk defaults ON when you switch to Email). Username rows show live “already registered” checks. Plain Text adds URL-in-value rules; textarea adds links + AI spam screening.', 'wp-span-checker' ),
		'webriskEmailUrlOnly'      => __( 'Used when “Form field” is Email or URL and “Require validation” is enabled for domain checks.', 'wp-span-checker' ),
		'vtEmailUrlOnly'           => __( 'Same as Web Risk: applies together with Email or URL domain validation.', 'wp-span-checker' ),
		'securityMethodsOtherHint' => __( 'Email and URL rows use the reputation toggles here together with validation above.', 'wp-span-checker' ),
		'validationRulesLegend'    => __( 'Validation rules', 'wp-span-checker' ),
		'labelWebRiskShort'        => __( 'Web Risk', 'wp-span-checker' ),
		'labelVtShort'             => __( 'VirusTotal', 'wp-span-checker' ),
		'onShort'                  => __( 'On', 'wp-span-checker' ),
		'offShort'                 => __( 'Off', 'wp-span-checker' ),
		'usernameCheckShort'       => __( 'Username exists check', 'wp-span-checker' ),
		'linksAllowedShort'        => __( 'Links allowed', 'wp-span-checker' ),
		'aiSpamShort'              => __( 'AI spam check', 'wp-span-checker' ),
		'textUrlsInFieldShort'     => __( 'URLs in text field', 'wp-span-checker' ),
		'regexShort'               => __( 'Regex', 'wp-span-checker' ),
		'locationRequired'         => __( 'Please select at least one location (Common locations, Specific pages, or Specific posts).', 'wp-span-checker' ),
		'formSelectorRequired'     => __( 'Please enter a Form id/class or Submit button selector to identify the form.', 'wp-span-checker' ),
		'formSelectorRequiredForEntireSite' => __( 'Form id/class is required when targeting the entire site.', 'wp-span-checker' ),
		'autoMode'                 => __( 'Auto', 'wp-span-checker' ),
		'manualMode'               => __( 'Manual', 'wp-span-checker' ),
		'defaultRules'             => __( 'Default rules', 'wp-span-checker' ),
		'emailInvalidFormat'       => __( 'Please enter a valid email address.', 'wp-span-checker' ),
		'emailDisposable'          => __( 'Disposable email addresses are not allowed.', 'wp-span-checker' ),
		'emailDomainInvalid'       => __( 'Email domain appears invalid.', 'wp-span-checker' ),
		'urlInvalidFormat'         => __( 'Please enter a valid URL.', 'wp-span-checker' ),
		'passwordWeak'             => __( 'Password is too weak. Use at least 8 characters with uppercase, lowercase, number, and symbol.', 'wp-span-checker' ),
		'linksNotAllowed'          => __( 'Links are not allowed in this field.', 'wp-span-checker' ),
		'urlsNotAllowed'           => __( 'URLs are not allowed in this field.', 'wp-span-checker' ),
		'usernameExists'           => __( 'This username is already taken.', 'wp-span-checker' ),
		'spamDetected'             => __( 'Your message appears to be spam.', 'wp-span-checker' ),
		'userBlocked'              => __( 'You have been blocked due to repeated violations. Please contact support.', 'wp-span-checker' ),
		'blocked'                  => __( 'Blocked', 'wp-span-checker' ),
	);
}

/**
 * Check MX record for a domain.
 *
 * @param string $domain Domain to check.
 * @return bool True if MX records found.
 */
function wp_span_checker_check_mx_record( string $domain ): bool {
	if ( empty( $domain ) ) {
		return false;
	}
	return (bool) checkdnsrr( $domain, 'MX' );
}

/**
 * Check if domain has valid A DNS record (domain is live/exists).
 *
 * @param string $domain Domain to check.
 * @return bool True if A record found.
 */
function wp_span_checker_check_domain_dns( string $domain ): bool {
	if ( empty( $domain ) ) {
		return false;
	}
	
	return (bool) checkdnsrr( $domain, 'A' );
}

/**
 * Check if domain is in the disposable list.
 *
 * @param string $domain Domain to check.
 * @return bool True if disposable.
 */
function wp_span_checker_is_disposable_domain( string $domain ): bool {
	global $wpdb;
	$table = $wpdb->prefix . 'span_disposable_domains';
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE domain = %s", strtolower( $domain ) ) );
	return (int) $count > 0;
}

/**
 * Check domain with Google Web Risk API.
 *
 * @param string $domain Domain to check.
 * @return array|null Result array with 'threat' key, or null on error.
 */
function wp_span_checker_check_webrisk( string $domain ) {
	$google_config = get_option( 'wsc-google-config', array() );
	$api_key       = isset( $google_config['api_key'] ) ? $google_config['api_key'] : '';
	
	if ( empty( $api_key ) || empty( $domain ) ) {
		return null;
	}

	$url = add_query_arg(
		array(
			'key'         => $api_key,
			'threatTypes' => 'MALWARE,SOCIAL_ENGINEERING,UNWANTED_SOFTWARE',
			'uri'         => 'https://' . $domain,
		),
		'https://webrisk.googleapis.com/v1/uris:search'
	);

	$response = wp_remote_get( $url, array( 'timeout' => 10 ) );
	if ( is_wp_error( $response ) ) {
		return null;
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	return array(
		'threat' => ! empty( $data['threat'] ),
	);
}

/**
 * Check domain with VirusTotal API.
 *
 * @param string $domain Domain to check.
 * @return array|null Result array with 'malicious' count, or null on error.
 */
function wp_span_checker_check_virustotal( string $domain ) {
	$vt_config = get_option( 'wsc-virustotal-config', array() );
	$api_key   = '';
	
	// Get first available key
	if ( ! empty( $vt_config['keys'] ) && is_array( $vt_config['keys'] ) ) {
		$api_key = reset( $vt_config['keys'] );
	}
	
	if ( empty( $api_key ) || empty( $domain ) ) {
		return null;
	}

	$response = wp_remote_get(
		'https://www.virustotal.com/api/v3/domains/' . urlencode( $domain ),
		array(
			'timeout' => 15,
			'headers' => array(
				'x-apikey' => $api_key,
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		return null;
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	$malicious = 0;
	if ( isset( $data['data']['attributes']['last_analysis_stats']['malicious'] ) ) {
		$malicious = (int) $data['data']['attributes']['last_analysis_stats']['malicious'];
	}

	return array(
		'malicious' => $malicious,
	);
}

/**
 * Record a strike against a user/visitor for spam behavior.
 *
 * @param string $reason    The reason for the strike.
 * @param string $source    The source of the strike (form_guard, comment, etc).
 * @param int    $user_id   Optional user ID for logged-in users.
 * @return array{blocked: bool, login_blocked: bool, strikes: int}
 */
function wp_span_checker_record_strike( string $reason, string $source = 'form_guard', int $user_id = 0 ): array {
	global $wpdb;

	// Check if admin is exempt
	$cfg = \WP_Span_Checker\AI_Span_Config::get();
	if ( ! empty( $cfg['block_user_exempt_admins'] ) && current_user_can( 'manage_options' ) ) {
		return array(
			'blocked'       => false,
			'login_blocked' => false,
			'strikes'       => 0,
		);
	}

	if ( ! $cfg['block_user_enabled'] ) {
		return array(
			'blocked'       => false,
			'login_blocked' => false,
			'strikes'       => 0,
		);
	}

	$table       = $wpdb->prefix . 'span_checker_comment_enforcement';
	$ip          = wp_span_checker_get_user_ip();
	$max_strikes = (int) ( $cfg['block_user_max_strikes'] ?? 5 );
	$expiry_days = (int) ( $cfg['block_user_strike_expiry_days'] ?? 30 );

	// Get current user ID if logged in
	if ( ! $user_id && is_user_logged_in() ) {
		$user_id = get_current_user_id();
	}

	// Generate actor key (prefer user ID, fallback to IP hash)
	if ( $user_id > 0 ) {
		$actor_key   = 'user_' . $user_id;
		$actor_label = '';
		$user_obj    = get_userdata( $user_id );
		if ( $user_obj ) {
			$actor_label = $user_obj->user_login;
		}
	} else {
		$actor_key   = 'guest_' . md5( $ip . ( isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '' ) );
		$actor_label = 'Guest (' . substr( $ip, 0, 12 ) . '...)';
	}

	// Calculate expiry time
	$expire_at = null;
	if ( $expiry_days > 0 ) {
		$expire_at = gmdate( 'Y-m-d H:i:s', time() + ( $expiry_days * DAY_IN_SECONDS ) );
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE actor_key = %s", $actor_key ), ARRAY_A );

	$now = current_time( 'mysql', true );

	if ( $existing ) {
		// Check if strikes have expired
		$strikes = (int) $existing['strikes'];
		if ( ! empty( $existing['strikes_expire_at'] ) && strtotime( $existing['strikes_expire_at'] ) < time() ) {
			$strikes = 0; // Reset expired strikes
		}
		$strikes++;

		$blocked       = ( $strikes >= $max_strikes ) ? 1 : (int) $existing['blocked'];
		$login_blocked = 0;
		if ( ! empty( $cfg['block_user_login_block'] ) && $strikes >= $max_strikes ) {
			$login_blocked = 1;
		}

		$wpdb->update(
			$table,
			array(
				'strikes'           => $strikes,
				'blocked'           => $blocked,
				'login_blocked'     => $login_blocked,
				'last_ip'           => $ip,
				'last_strike_at'    => $now,
				'strikes_expire_at' => $expire_at,
				'last_reason'       => substr( $reason, 0, 500 ),
				'strike_source'     => $source,
				'user_id'           => $user_id > 0 ? $user_id : null,
				'blocked_at'        => $blocked && empty( $existing['blocked_at'] ) ? $now : $existing['blocked_at'],
			),
			array( 'actor_key' => $actor_key )
		);

		// Auto-logout if enabled and blocked
		if ( $login_blocked && ! empty( $cfg['block_user_auto_logout'] ) && $user_id > 0 ) {
			wp_span_checker_force_logout_user( $user_id );
		}

		return array(
			'blocked'       => (bool) $blocked,
			'login_blocked' => (bool) $login_blocked,
			'strikes'       => $strikes,
		);
	}

	// New record
	$strikes       = 1;
	$blocked       = ( $strikes >= $max_strikes ) ? 1 : 0;
	$login_blocked = 0;
	if ( ! empty( $cfg['block_user_login_block'] ) && $strikes >= $max_strikes ) {
		$login_blocked = 1;
	}

	$wpdb->insert(
		$table,
		array(
			'actor_key'         => $actor_key,
			'actor_label'       => $actor_label,
			'user_id'           => $user_id > 0 ? $user_id : null,
			'strikes'           => $strikes,
			'blocked'           => $blocked,
			'login_blocked'     => $login_blocked,
			'last_ip'           => $ip,
			'blocked_at'        => $blocked ? $now : null,
			'last_strike_at'    => $now,
			'strikes_expire_at' => $expire_at,
			'last_reason'       => substr( $reason, 0, 500 ),
			'strike_source'     => $source,
		)
	);

	return array(
		'blocked'       => (bool) $blocked,
		'login_blocked' => (bool) $login_blocked,
		'strikes'       => $strikes,
	);
}

/**
 * Force logout a specific user by destroying their sessions.
 *
 * @param int $user_id User ID to logout.
 */
function wp_span_checker_force_logout_user( int $user_id ): void {
	if ( $user_id <= 0 ) {
		return;
	}
	$sessions = \WP_Session_Tokens::get_instance( $user_id );
	$sessions->destroy_all();
}

/**
 * Check if current user/visitor is blocked from login.
 *
 * @param int $user_id Optional user ID.
 * @return bool
 */
function wp_span_checker_is_login_blocked( int $user_id = 0 ): bool {
	global $wpdb;

	$cfg = \WP_Span_Checker\AI_Span_Config::get();
	if ( ! $cfg['block_user_enabled'] || ! $cfg['block_user_login_block'] ) {
		return false;
	}

	$table = $wpdb->prefix . 'span_checker_comment_enforcement';
	$ip    = wp_span_checker_get_user_ip();

	// Check by user ID
	if ( $user_id > 0 ) {
		$actor_key = 'user_' . $user_id;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE actor_key = %s", $actor_key ), ARRAY_A );
		if ( $row && ! empty( $row['login_blocked'] ) ) {
			// Check expiry
			if ( empty( $row['strikes_expire_at'] ) || strtotime( $row['strikes_expire_at'] ) > time() ) {
				return true;
			}
		}
	}

	// Also check by IP for guests
	$guest_key = 'guest_' . md5( $ip . ( isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '' ) );
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE actor_key = %s", $guest_key ), ARRAY_A );
	if ( $row && ! empty( $row['login_blocked'] ) ) {
		if ( empty( $row['strikes_expire_at'] ) || strtotime( $row['strikes_expire_at'] ) > time() ) {
			return true;
		}
	}

	return false;
}

/**
 * Get strike count for current user/visitor.
 *
 * @param int $user_id Optional user ID.
 * @return int
 */
function wp_span_checker_get_strike_count( int $user_id = 0 ): int {
	global $wpdb;

	$table = $wpdb->prefix . 'span_checker_comment_enforcement';
	$ip    = wp_span_checker_get_user_ip();

	// Check by user ID
	if ( $user_id > 0 ) {
		$actor_key = 'user_' . $user_id;
	} else {
		$actor_key = 'guest_' . md5( $ip . ( isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '' ) );
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE actor_key = %s", $actor_key ), ARRAY_A );
	if ( ! $row ) {
		return 0;
	}

	// Check if strikes have expired
	if ( ! empty( $row['strikes_expire_at'] ) && strtotime( $row['strikes_expire_at'] ) < time() ) {
		return 0;
	}

	return (int) $row['strikes'];
}

/**
 * Check message content for spam using AI.
 *
 * @param string $content Content to check.
 * @return array|null Result array with 'is_spam' key, or null on error.
 */
function wp_span_checker_check_ai_spam( string $content, array $context = array() ) {
	if ( empty( trim( $content ) ) ) {
		return null;
	}

	// Read from AI Span Config
	$settings      = get_option( 'wsc-ai-span-config', array() );
	$provider      = isset( $settings['provider'] ) ? $settings['provider'] : 'gemini';
	$openai_key    = isset( $settings['openai_api_key'] ) ? $settings['openai_api_key'] : '';
	$anthropic_key = isset( $settings['anthropic_api_key'] ) ? $settings['anthropic_api_key'] : '';
	$gemini_key    = isset( $settings['gemini_api_key'] ) ? $settings['gemini_api_key'] : '';
	$deepseek_key  = isset( $settings['deepseek_api_key'] ) ? $settings['deepseek_api_key'] : '';
	
	// Get configured models with defaults
	$openai_model    = isset( $settings['openai_model'] ) && $settings['openai_model'] ? $settings['openai_model'] : 'gpt-4o-mini';
	$anthropic_model = isset( $settings['anthropic_model'] ) && $settings['anthropic_model'] ? $settings['anthropic_model'] : 'claude-3-5-haiku-latest';
	$gemini_model    = isset( $settings['gemini_model'] ) && $settings['gemini_model'] ? $settings['gemini_model'] : 'gemini-2.0-flash-lite';
	$deepseek_model  = isset( $settings['deepseek_model'] ) && $settings['deepseek_model'] ? $settings['deepseek_model'] : 'deepseek-chat';

	// Validate provider has API key
	$valid_providers = array(
		'openai'    => ! empty( $openai_key ),
		'anthropic' => ! empty( $anthropic_key ),
		'gemini'    => ! empty( $gemini_key ),
		'deepseek'  => ! empty( $deepseek_key ),
	);

	if ( empty( $provider ) || ! isset( $valid_providers[ $provider ] ) || ! $valid_providers[ $provider ] ) {
		return null;
	}

	// Build context information for better AI analysis
	$form_name   = isset( $context['form_name'] ) ? $context['form_name'] : 'Contact Form';
	$field_type  = isset( $context['field_type'] ) ? $context['field_type'] : 'textarea';
	$field_name  = isset( $context['field_name'] ) ? $context['field_name'] : 'message';
	$page_title  = isset( $context['page_title'] ) ? $context['page_title'] : '';

	$prompt = "You are a strict spam moderator for website contact forms. Analyze the FORM_INPUT and determine if it is spam or legitimate.\n\n";
	$prompt .= "FORM_CONTEXT:\n";
	$prompt .= "- Form: {$form_name}\n";
	$prompt .= "- Field: {$field_name} ({$field_type})\n";
	if ( $page_title ) {
		$prompt .= "- Page: {$page_title}\n";
	}
	$prompt .= "\nFORM_INPUT:\n{$content}\n\n";
	$prompt .= "SPAM PATTERNS TO DETECT:\n";
	$prompt .= "- Promotional/affiliate content (earn money, work from home, click here)\n";
	$prompt .= "- SEO/backlink pitches (buy backlinks, rank #1, increase traffic)\n";
	$prompt .= "- Pharma/gambling/adult promotions (viagra, casino, xxx)\n";
	$prompt .= "- Crypto/loan/financial scams (bitcoin profit, instant loan)\n";
	$prompt .= "- Repeated text/emails (same content pasted multiple times)\n";
	$prompt .= "- Gibberish or random characters\n";
	$prompt .= "- Contact harvesting (email me at, whatsapp me)\n";
	$prompt .= "- Essay/homework writing services\n";
	$prompt .= "- Irrelevant content for a contact form\n";
	$prompt .= "- Suspicious or excessive links\n\n";
	$prompt .= "Respond with ONLY valid JSON (no markdown, no code fences):\n";
	$prompt .= "{\"status\":\"ok\"|\"spam\",\"reason\":\"Brief explanation if spam, or 'legitimate message' if ok\"}";

	$response = null;

	switch ( $provider ) {
		case 'openai':
			$response = wp_remote_post(
				'https://api.openai.com/v1/chat/completions',
				array(
					'timeout' => 30,
					'headers' => array(
						'Authorization' => 'Bearer ' . $openai_key,
						'Content-Type'  => 'application/json',
					),
					'body'    => wp_json_encode(
						array(
							'model'      => $openai_model,
							'messages'   => array(
								array( 'role' => 'user', 'content' => $prompt ),
							),
							'max_tokens' => 150,
						)
					),
				)
			);
			break;

		case 'anthropic':
			$response = wp_remote_post(
				'https://api.anthropic.com/v1/messages',
				array(
					'timeout' => 30,
					'headers' => array(
						'x-api-key'         => $anthropic_key,
						'anthropic-version' => '2023-06-01',
						'Content-Type'      => 'application/json',
					),
					'body'    => wp_json_encode(
						array(
							'model'      => $anthropic_model,
							'max_tokens' => 150,
							'messages'   => array(
								array( 'role' => 'user', 'content' => $prompt ),
							),
						)
					),
				)
			);
			break;

		case 'gemini':
			$response = wp_remote_post(
				'https://generativelanguage.googleapis.com/v1beta/models/' . $gemini_model . ':generateContent?key=' . $gemini_key,
				array(
					'timeout' => 30,
					'headers' => array(
						'Content-Type' => 'application/json',
					),
					'body'    => wp_json_encode(
						array(
							'contents' => array(
								array(
									'parts' => array(
										array( 'text' => $prompt ),
									),
								),
							),
							'generationConfig' => array(
								'maxOutputTokens' => 150,
							),
						)
					),
				)
			);
			break;

		case 'deepseek':
			$response = wp_remote_post(
				'https://api.deepseek.com/chat/completions',
				array(
					'timeout' => 30,
					'headers' => array(
						'Authorization' => 'Bearer ' . $deepseek_key,
						'Content-Type'  => 'application/json',
					),
					'body'    => wp_json_encode(
						array(
							'model'      => $deepseek_model,
							'messages'   => array(
								array( 'role' => 'user', 'content' => $prompt ),
							),
							'max_tokens' => 150,
						)
					),
				)
			);
			break;

		default:
			return null;
	}

	if ( is_wp_error( $response ) ) {
		return null;
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	// Extract reply based on provider response format
	$reply = '';
	switch ( $provider ) {
		case 'openai':
		case 'deepseek':
			// Both use OpenAI-compatible format
			if ( isset( $data['choices'][0]['message']['content'] ) ) {
				$reply = trim( $data['choices'][0]['message']['content'] );
			}
			break;

		case 'anthropic':
			if ( isset( $data['content'][0]['text'] ) ) {
				$reply = trim( $data['content'][0]['text'] );
			}
			break;

		case 'gemini':
			if ( isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
				$reply = trim( $data['candidates'][0]['content']['parts'][0]['text'] );
			}
			break;
	}

	// Parse JSON response
	$ai_result = json_decode( $reply, true );
	if ( is_array( $ai_result ) && isset( $ai_result['status'] ) ) {
		return array(
			'is_spam' => ( 'spam' === strtolower( $ai_result['status'] ) ),
			'reason'  => isset( $ai_result['reason'] ) ? $ai_result['reason'] : '',
		);
	}

	// Fallback: check for SPAM keyword in response
	$upper_reply = strtoupper( $reply );
	return array(
		'is_spam' => ( strpos( $upper_reply, 'SPAM' ) !== false && strpos( $upper_reply, 'NOT_SPAM' ) === false && strpos( $upper_reply, '"OK"' ) === false ),
	);
}
