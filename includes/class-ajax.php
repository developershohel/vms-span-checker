<?php
/**
 * AJAX handlers.
 *
 * @package WP_Span_Checker
 */

namespace WP_Span_Checker;

use Exception;
use WP_Span_Checker\Services\Domain_Validator;
use WP_Span_Checker\AI_Span_Summary;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX endpoints.
 */
class Ajax {

	/**
	 * WordPress database object.
	 *
	 * @var \wpdb
	 */
	private $wpdb;

	/**
	 * Register AJAX actions.
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;

		add_action( 'wp_ajax_get_domains', array( $this, 'ajax_get_domains' ) );
		add_action( 'wp_ajax_add_domain', array( $this, 'ajax_add_domain' ) );
		add_action( 'wp_ajax_delete_domain', array( $this, 'ajax_delete_domain' ) );
		add_action( 'wp_ajax_get_form_settings', array( $this, 'ajax_get_form_settings' ) );
		add_action( 'wp_ajax_add_form_settings', array( $this, 'ajax_add_form_settings' ) );
		add_action( 'wp_ajax_delete_form_setting', array( $this, 'ajax_delete_form_setting' ) );
		add_action( 'wp_ajax_validateDomainName', array( $this, 'ajax_validate_domain_name' ) );
		add_action( 'wp_ajax_nopriv_validateDomainName', array( $this, 'ajax_validate_domain_name' ) );
		add_action( 'wp_ajax_validateFormGuardField', array( $this, 'ajax_validate_form_guard_field' ) );
		add_action( 'wp_ajax_nopriv_validateFormGuardField', array( $this, 'ajax_validate_form_guard_field' ) );
		add_action( 'wp_ajax_wsc_ai_regenerate_summary', array( $this, 'ajax_ai_regenerate_summary' ) );
		add_action( 'wp_ajax_import_whitelist_seed', array( $this, 'ajax_import_whitelist_seed' ) );
	}

	/**
	 * AJAX: generate or refresh AI summary for one post.
	 */
	public function ajax_ai_regenerate_summary() {
		check_ajax_referer( 'wp_span_checker_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wp-span-checker' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post.', 'wp-span-checker' ) ) );
		}

		$runner = new AI_Span_Summary();
		$ok     = $runner->generate_for_post( $post_id );
		if ( ! $ok ) {
			wp_send_json_error( array( 'message' => __( 'Summary generation failed. Check AI settings and the post status.', 'wp-span-checker' ) ) );
		}

		wp_send_json_success( array( 'message' => __( 'Summary saved.', 'wp-span-checker' ) ) );
	}

	/**
	 * AJAX: list whitelist or disposable domains.
	 */
	public function ajax_get_domains() {
		check_ajax_referer( 'wp_span_checker_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wp-span-checker' ) ) );
		}

		$type = isset( $_POST['domain_type'] ) ? sanitize_text_field( wp_unslash( $_POST['domain_type'] ) ) : 'whitelist';
		if ( ! in_array( $type, array( 'whitelist', 'disposable' ), true ) ) {
			$type = 'whitelist';
		}

		$table = ( 'disposable' === $type )
			? $this->wpdb->prefix . 'span_disposable_domains'
			: $this->wpdb->prefix . 'span_whitelist_domains';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is whitelisted above.
		$domains = $this->wpdb->get_results( "SELECT * FROM {$table} ORDER BY id ASC", ARRAY_A );

		wp_send_json_success(
			array(
				'domains' => $domains,
			)
		);
	}

	/**
	 * AJAX: list form validation mappings.
	 */
	public function ajax_get_form_settings() {
		check_ajax_referer( 'wp_span_checker_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wp-span-checker' ) ) );
		}

		$table         = $this->wpdb->prefix . 'span_checker_form_settings';
		$form_settings = $this->wpdb->get_results( "SELECT * FROM {$table} ORDER BY id ASC", ARRAY_A );

		wp_send_json_success(
			array(
				'formSettings' => $form_settings,
			)
		);
	}

	/**
	 * AJAX: save form validation mapping.
	 */
	public function ajax_add_form_settings() {
		check_ajax_referer( 'wp_span_checker_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wp-span-checker' ) ) );
		}

		$id            = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$form_type     = isset( $_POST['formType'] ) ? sanitize_text_field( wp_unslash( $_POST['formType'] ) ) : '';
		$page_id = wp_span_checker_sanitize_page_targets_param(
			isset( $_POST['pageId'] ) ? wp_unslash( $_POST['pageId'] ) : ''
		);
		$form_id          = isset( $_POST['formId'] ) ? sanitize_text_field( wp_unslash( $_POST['formId'] ) ) : '';
		$form_class       = isset( $_POST['formClass'] ) ? sanitize_text_field( wp_unslash( $_POST['formClass'] ) ) : '';
		$submit_selector  = isset( $_POST['submitSelector'] ) ? sanitize_text_field( wp_unslash( $_POST['submitSelector'] ) ) : '';
		$form_settings    = isset( $_POST['formSettings'] ) ? wp_unslash( $_POST['formSettings'] ) : array();
		$is_webrisk_post  = isset( $_POST['is_webrisk'] ) ? absint( $_POST['is_webrisk'] ) : null;
		$is_virustotal_post = isset( $_POST['is_virustotal'] ) ? absint( $_POST['is_virustotal'] ) : null;

		$sanitized_array = map_deep( $form_settings, 'sanitize_text_field' );
		foreach ( $sanitized_array as $idx => $f_item ) {
			if ( ! is_array( $f_item ) ) {
				continue;
			}
			$ft = isset( $f_item['field'] ) ? sanitize_text_field( (string) $f_item['field'] ) : 'text';
			$sanitized_array[ $idx ] = Form_Guard_Conditional::normalize_field_config( $ft, $f_item );
		}

		$table = $this->wpdb->prefix . 'span_checker_form_settings';

		$row_wr = 0;
		$row_vt = 0;
		foreach ( $sanitized_array as $f_item ) {
			if ( is_array( $f_item ) ) {
				if ( ! empty( $f_item['is_webrisk'] ) && '0' !== (string) $f_item['is_webrisk'] ) {
					$row_wr = 1;
				}
				if ( ! empty( $f_item['is_virustotal'] ) && '0' !== (string) $f_item['is_virustotal'] ) {
					$row_vt = 1;
				}
			}
		}
		if ( null !== $is_webrisk_post ) {
			$row_wr = max( $row_wr, $is_webrisk_post );
		}
		if ( null !== $is_virustotal_post ) {
			$row_vt = max( $row_vt, $is_virustotal_post );
		}

		$row = array(
			'form_type'        => $form_type,
			'page_id'          => $page_id,
			'form_id'          => $form_id,
			'form_class'       => $form_class,
			'submit_selector'  => $submit_selector,
			'settings'         => wp_json_encode( $sanitized_array ),
			'is_webrisk'       => $row_wr,
			'is_virustotal'    => $row_vt,
		);

		try {
			if ( $id > 0 ) {
				$updated = $this->wpdb->update(
					$table,
					$row,
					array( 'id' => $id )
				);
				if ( false === $updated ) {
					wp_send_json_error( array( 'message' => __( 'Could not update Form Guard mapping.', 'wp-span-checker' ) ) );
				}
			} else {
				$inserted = $this->wpdb->insert( $table, $row );
				if ( ! $inserted ) {
					wp_send_json_error( array( 'message' => __( 'Could not save Form Guard mapping.', 'wp-span-checker' ) ) );
				}
			}

			wp_send_json_success();
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * AJAX: delete form mapping row.
	 */
	public function ajax_delete_form_setting() {
		check_ajax_referer( 'wp_span_checker_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wp-span-checker' ) ) );
		}

		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid ID.', 'wp-span-checker' ) ) );
		}

		$table = $this->wpdb->prefix . 'span_checker_form_settings';
		$this->wpdb->delete( $table, array( 'id' => $id ) );

		wp_send_json_success();
	}

	/**
	 * AJAX: add domain to whitelist or disposable list.
	 */
	public function ajax_add_domain() {
		check_ajax_referer( 'wp_span_checker_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wp-span-checker' ) ) );
		}

		$type   = isset( $_POST['domain_type'] ) ? sanitize_text_field( wp_unslash( $_POST['domain_type'] ) ) : 'whitelist';
		$domain = isset( $_POST['domain'] ) ? sanitize_text_field( wp_unslash( $_POST['domain'] ) ) : '';

		if ( ! in_array( $type, array( 'whitelist', 'disposable' ), true ) ) {
			$type = 'whitelist';
		}

		if ( '' === $domain ) {
			wp_send_json_error( array( 'message' => __( 'Domain is required.', 'wp-span-checker' ) ) );
		}

		$table = ( 'disposable' === $type )
			? $this->wpdb->prefix . 'span_disposable_domains'
			: $this->wpdb->prefix . 'span_whitelist_domains';

		$this->wpdb->insert( $table, array( 'domain' => $domain ) );

		wp_send_json_success();
	}

	/**
	 * AJAX: remove domain row.
	 */
	public function ajax_delete_domain() {
		check_ajax_referer( 'wp_span_checker_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wp-span-checker' ) ) );
		}

		$type = isset( $_POST['domain_type'] ) ? sanitize_text_field( wp_unslash( $_POST['domain_type'] ) ) : 'whitelist';
		$id   = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

		if ( ! in_array( $type, array( 'whitelist', 'disposable' ), true ) ) {
			$type = 'whitelist';
		}

		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid ID.', 'wp-span-checker' ) ) );
		}

		$table = ( 'disposable' === $type )
			? $this->wpdb->prefix . 'span_disposable_domains'
			: $this->wpdb->prefix . 'span_whitelist_domains';

		$this->wpdb->delete( $table, array( 'id' => $id ) );

		wp_send_json_success();
	}

	/**
	 * AJAX: validate domain (public + admin).
	 */
	public function ajax_validate_domain_name() {
		check_ajax_referer( 'wp_span_checker_nonce', 'nonce' );

		$raw    = isset( $_POST['domain'] ) ? wp_unslash( $_POST['domain'] ) : '';
		$domain = wp_span_checker_normalize_domain_input( $raw );

		if ( '' === $domain ) {
			wp_send_json_error(
				array(
					'message' => __( 'Domain is required.', 'wp-span-checker' ),
					'status'  => false,
				)
			);
		}

		$type     = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : 'unknown';
		$ip       = wp_span_checker_get_user_ip();
		$settings = wp_span_checker_parse_validation_settings( wp_unslash( $_POST ) );

		$domain_validation = new Domain_Validator();

		try {
			$result = $domain_validation->validate_domain( $domain, $type, $ip, $settings );
			wp_send_json_success( $result );
		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
					'status'  => false,
				)
			);
		}
	}

	/**
	 * AJAX: run Form Guard checks for one mapped field (public).
	 */
	public function ajax_validate_form_guard_field() {
		check_ajax_referer( 'wp_span_checker_nonce', 'nonce' );

		$mapping_id  = isset( $_POST['mappingId'] ) ? absint( $_POST['mappingId'] ) : 0;
		$field_index = isset( $_POST['fieldIndex'] ) ? absint( $_POST['fieldIndex'] ) : 0;
		$value_raw   = isset( $_POST['value'] ) ? wp_unslash( $_POST['value'] ) : '';
		$value       = is_string( $value_raw ) ? $value_raw : '';

		if ( ! $mapping_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid mapping.', 'wp-span-checker' ), 'status' => false ) );
		}

		$table = $this->wpdb->prefix . 'span_checker_form_settings';
		$row   = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $mapping_id ), ARRAY_A );

		if ( ! $row || empty( $row['settings'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Mapping not found.', 'wp-span-checker' ), 'status' => false ) );
		}

		$fields = json_decode( $row['settings'], true );
		if ( ! is_array( $fields ) || ! isset( $fields[ $field_index ] ) || ! is_array( $fields[ $field_index ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Field configuration not found.', 'wp-span-checker' ), 'status' => false ) );
		}

		$field = $fields[ $field_index ];
		$type  = isset( $field['field'] ) ? sanitize_text_field( (string) $field['field'] ) : 'text';

		$result = Form_Guard_Conditional::validate_field_value( $type, $field, $value, $row );
		wp_send_json_success( $result );
	}

	/**
	 * AJAX: import bundled whitelist SQL domains with INSERT IGNORE behavior.
	 */
	public function ajax_import_whitelist_seed() {
		check_ajax_referer( 'wp_span_checker_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wp-span-checker' ) ) );
		}

		$file = WP_SPAN_CHECKER_DIR . 'includes/data/whitelist.sql';
		if ( ! is_readable( $file ) ) {
			wp_send_json_error( array( 'message' => __( 'Whitelist SQL file not found.', 'wp-span-checker' ) ) );
		}

		$sql = file_get_contents( $file );
		if ( ! is_string( $sql ) || '' === trim( $sql ) ) {
			wp_send_json_error( array( 'message' => __( 'Whitelist SQL file is empty.', 'wp-span-checker' ) ) );
		}

		preg_match_all( "/VALUES\\s*\\(\\s*'([^']+)'\\s*\\)/i", $sql, $matches );
		$domains = isset( $matches[1] ) && is_array( $matches[1] ) ? $matches[1] : array();
		$domains = array_values(
			array_unique(
				array_filter(
					array_map(
						static function ( $d ) {
							return strtolower( sanitize_text_field( (string) $d ) );
						},
						$domains
					)
				)
			)
		);

		if ( empty( $domains ) ) {
			wp_send_json_error( array( 'message' => __( 'No domains found in whitelist SQL.', 'wp-span-checker' ) ) );
		}

		$table    = $this->wpdb->prefix . 'span_whitelist_domains';
		$inserted = 0;
		$skipped  = 0;

		foreach ( $domains as $domain ) {
			$result = $this->wpdb->query(
				$this->wpdb->prepare(
					"INSERT IGNORE INTO {$table} (domain) VALUES (%s)",
					$domain
				)
			);
			if ( false === $result ) {
				continue;
			}
			if ( 1 === (int) $result ) {
				++$inserted;
			} else {
				++$skipped;
			}
		}

		wp_send_json_success(
			array(
				'message'  => sprintf(
					/* translators: 1: inserted count, 2: skipped duplicates */
					__( 'Whitelist import complete: %1$d inserted, %2$d already existed.', 'wp-span-checker' ),
					$inserted,
					$skipped
				),
				'inserted' => $inserted,
				'skipped'  => $skipped,
			)
		);
	}
}
