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
		'fieldType'                => __( 'Field type', 'wp-span-checker' ),
		'fieldId'                  => __( 'Field ID', 'wp-span-checker' ),
		'fieldClass'               => __( 'Field class', 'wp-span-checker' ),
		'eventName'                => __( 'Event name', 'wp-span-checker' ),
		'formField'                => __( 'Form field', 'wp-span-checker' ),
		'javascriptEvent'          => __( 'JavaScript event', 'wp-span-checker' ),
		'optionUrl'                => __( 'URL', 'wp-span-checker' ),
		'optionEmail'              => __( 'Email', 'wp-span-checker' ),
		'optionText'               => __( 'Text', 'wp-span-checker' ),
		'optionChange'             => __( 'Change', 'wp-span-checker' ),
		'optionInput'              => __( 'Input', 'wp-span-checker' ),
		'optionFormSubmit'         => __( 'Form submit', 'wp-span-checker' ),
		'labelId'                  => __( 'ID', 'wp-span-checker' ),
		'labelClass'               => __( 'Class', 'wp-span-checker' ),
		'selectFieldType'          => __( 'Select field type', 'wp-span-checker' ),
	);
}
