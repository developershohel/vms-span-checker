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
		add_action( 'wp_ajax_wsc_ai_regenerate_summary', array( $this, 'ajax_ai_regenerate_summary' ) );
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
		$form_id       = isset( $_POST['formId'] ) ? sanitize_text_field( wp_unslash( $_POST['formId'] ) ) : '';
		$form_class    = isset( $_POST['formClass'] ) ? sanitize_text_field( wp_unslash( $_POST['formClass'] ) ) : '';
		$form_settings = isset( $_POST['formSettings'] ) ? wp_unslash( $_POST['formSettings'] ) : array();
		$is_webrisk    = isset( $_POST['is_webrisk'] ) ? absint( $_POST['is_webrisk'] ) : 0;
		$is_virustotal = isset( $_POST['is_virustotal'] ) ? absint( $_POST['is_virustotal'] ) : 0;

		$sanitized_array = map_deep( $form_settings, 'sanitize_text_field' );

		$table = $this->wpdb->prefix . 'span_checker_form_settings';

		$row = array(
			'form_type'     => $form_type,
			'page_id'       => $page_id,
			'form_id'       => $form_id,
			'form_class'    => $form_class,
			'settings'      => wp_json_encode( $sanitized_array ),
			'is_webrisk'    => $is_webrisk,
			'is_virustotal' => $is_virustotal,
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
}
