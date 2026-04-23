<?php

namespace WP_Span_Checker;

use Exception;
use WP_Span_Checker\Services\Domain_Validator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Ajax {

	private $wpdb;

	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;

		// AJAX actions for domains
		add_action( 'wp_ajax_get_domains', [ $this, 'ajax_get_domains' ] );
		add_action( 'wp_ajax_add_domain', [ $this, 'ajax_add_domain' ] );
		add_action( 'wp_ajax_delete_domain', [ $this, 'ajax_delete_domain' ] );
		add_action( 'wp_ajax_get_form_settings', [ $this, 'ajax_get_form_settings' ] );
		add_action( 'wp_ajax_nopriv_get_form_settings', [ $this, 'ajax_get_form_settings' ] );
		add_action( 'wp_ajax_add_form_settings', [ $this, 'ajax_add_form_settings' ] );
		add_action( 'wp_ajax_nopriv_add_form_settings', [ $this, 'ajax_add_form_settings' ] );
		add_action( 'wp_ajax_delete_form_setting', [ $this, 'ajax_delete_form_setting' ] );
		add_action( 'wp_ajax_nopriv_delete_form_setting', [ $this, 'ajax_delete_form_setting' ] );
		add_action( 'wp_ajax_validateDomainName', [ $this, 'ajax_validateDomainName' ] );
		add_action( 'wp_ajax_nopriv_validateDomainName', [ $this, 'ajax_validateDomainName' ] );
	}


	/**
	 * AJAX: Get domains with pagination
	 */
	public function ajax_get_domains() {
		check_ajax_referer( 'wp_span_checker_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'No permission' );
		}
		$type = sanitize_text_field( $_POST['domain_type'] ?? 'whitelist' );

		$table   = $type === 'disposable'
			? $this->wpdb->prefix . 'span_disposable_domains'
			: $this->wpdb->prefix . 'span_whitelist_domains';
		$domains = $this->wpdb->get_results( "SELECT * FROM $table ORDER BY id ASC", ARRAY_A );
		wp_send_json_success( [
			'domains' => $domains,
		] );
	}

	/**
	 * AJAX: Get form settings
	 */
	public function ajax_get_form_settings() {
		check_ajax_referer( 'wp_span_checker_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'No permission' );
		}
		$table        = $this->wpdb->prefix . 'span_checker_form_settings';
		$formSettings = $this->wpdb->get_results( "SELECT * FROM $table ORDER BY id ASC", ARRAY_A );
		wp_send_json_success( [
			'formSettings' => $formSettings
		] );
	}

	/**
	 * AJAX: Add form settings
	 */
	public function ajax_add_form_settings() {
		check_ajax_referer( 'wp_span_checker_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'No permission' );
		}
		$id              = absint( $_POST['id'] ?? 0 );
		$formType        = sanitize_text_field( $_POST['formType'] ?? '' );
		$pageId          = sanitize_text_field( $_POST['pageId'] ?? '' );
		$formId          = sanitize_text_field( $_POST['formId'] ?? '' );
		$formClass       = sanitize_text_field( $_POST['formClass'] ?? '' );
		$formSettings    = $_POST['formSettings'] ?? [];
		$is_webrisk      = absint( $_POST['is_webrisk'] ?? 0 );
		$is_virustotal   = absint( $_POST['is_virustotal'] ?? 0 );
		$sanitized_array = map_deep( $formSettings, 'sanitize_text_field' );

		$table = $this->wpdb->prefix . 'span_checker_form_settings';
		try {
			if ( $id ) {
				$exists = $this->wpdb->get_var( $this->wpdb->prepare(
					"SELECT COUNT(*) FROM $table WHERE form_id = %d",
					$formId
				) );
				if ( $exists > 0 ) {
					$this->wpdb->update( $table,
						[
							'form_type'     => $formType,
							'page_id'       => $pageId,
							'form_id'       => $formId,
							'form_class'    => $formClass,
							'settings'      => wp_json_encode( $sanitized_array ),
							'is_webrisk'    => $is_webrisk,
							'is_virustotal' => $is_virustotal
						], [ 'id' => $id ] );
				} else {
					$this->wpdb->insert( $table,
						[
							'form_type'     => $formType,
							'page_id'       => $pageId,
							'form_id'       => $formId,
							'form_class'    => $formClass,
							'is_webrisk'    => $is_webrisk,
							'is_virustotal' => $is_virustotal,
							'settings'      => wp_json_encode( $sanitized_array )
						] );
				}
			} else {
				$this->wpdb->insert( $table,
					[
						'form_type'     => $formType,
						'page_id'       => $pageId,
						'form_id'       => $formId,
						'form_class'    => $formClass,
						'is_webrisk'    => $is_webrisk,
						'is_virustotal' => $is_virustotal,
						'settings'      => wp_json_encode( $sanitized_array )
					] );
			}

			wp_send_json_success();
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}
	}

	public function ajax_delete_form_setting() {
		check_ajax_referer( 'wp_span_checker_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'No permission' );
		}
		$id = intval( $_POST['id'] ?? 0 );
		if ( ! $id ) {
			wp_send_json_error( 'Invalid ID' );
		}

		$table = $this->wpdb->prefix . 'span_checker_form_settings';

		$this->wpdb->delete( $table, [ 'id' => $id ] );

		wp_send_json_success();
	}

	/**
	 * AJAX: Add domain
	 */
	public function ajax_add_domain() {
		check_ajax_referer( 'wp_span_checker_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'No permission' );
		}

		$type   = sanitize_text_field( $_POST['domain_type'] ?? 'whitelist' );
		$domain = sanitize_text_field( $_POST['domain'] ?? '' );

		if ( empty( $domain ) ) {
			wp_send_json_error( 'Domain required' );
		}

		$table = $type === 'disposable'
			? $this->wpdb->prefix . 'span_disposable_domains'
			: $this->wpdb->prefix . 'span_whitelist_domains';

		$this->wpdb->insert( $table, [ 'domain' => $domain ] );

		wp_send_json_success();
	}

	/**
	 * AJAX: Delete domain
	 */
	public function ajax_delete_domain() {
		check_ajax_referer( 'wp_span_checker_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'No permission' );
		}

		$type = sanitize_text_field( $_POST['domain_type'] ?? 'whitelist' );
		$id   = intval( $_POST['id'] ?? 0 );
		if ( ! $id ) {
			wp_send_json_error( 'Invalid ID' );
		}

		$table = $type === 'disposable'
			? $this->wpdb->prefix . 'span_disposable_domains'
			: $this->wpdb->prefix . 'span_whitelist_domains';

		$this->wpdb->delete( $table, [ 'id' => $id ] );

		wp_send_json_success();
	}

	public function ajax_validateDomainName() {
		check_ajax_referer( 'wp_span_checker_nonce', 'nonce' );
		$domain = sanitize_url( $_POST['domain'] ) ?? '';
		if ( empty( $domain ) ) {
			wp_send_json_error( [ 'message' => 'Domain required', 'status' => false ] );
		}
		$type     = sanitize_text_field( $_POST['type'] ?? 'unknown' );
		$ip       = wp_span_checker_get_user_ip();
		$settings = array_map( 'sanitize_text_field', $_POST['settings'] ?? [] );
		error_log( print_r( $settings, true ) );
		$domainValidation = new Domain_Validator();
		try {
			wp_send_json( $domainValidation->validate_domain( $domain, $type, $ip, $settings ) );
		} catch ( Exception $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage(), 'status' => false ] );
		}
	}
}
