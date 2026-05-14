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
		add_action( 'wp_ajax_validateAutoField', array( $this, 'ajax_validate_auto_field' ) );
		add_action( 'wp_ajax_nopriv_validateAutoField', array( $this, 'ajax_validate_auto_field' ) );
		add_action( 'wp_ajax_validateAllFields', array( $this, 'ajax_validate_all_fields' ) );
		add_action( 'wp_ajax_nopriv_validateAllFields', array( $this, 'ajax_validate_all_fields' ) );
		add_action( 'wp_ajax_wsc_ai_regenerate_summary', array( $this, 'ajax_ai_regenerate_summary' ) );
		add_action( 'wp_ajax_import_whitelist_seed', array( $this, 'ajax_import_whitelist_seed' ) );
		add_action( 'wp_ajax_wsc_search_pages', array( $this, 'ajax_search_pages' ) );
		add_action( 'wp_ajax_wsc_search_posts', array( $this, 'ajax_search_posts' ) );
		add_action( 'wp_ajax_wsc_validate_subscribe', array( $this, 'ajax_validate_subscribe' ) );
		add_action( 'wp_ajax_nopriv_wsc_validate_subscribe', array( $this, 'ajax_validate_subscribe' ) );
		add_action( 'wp_ajax_wsc_validate_contact', array( $this, 'ajax_validate_contact' ) );
		add_action( 'wp_ajax_nopriv_wsc_validate_contact', array( $this, 'ajax_validate_contact' ) );
		add_action( 'wp_ajax_wsc_validate_registration', array( $this, 'ajax_validate_registration' ) );
		add_action( 'wp_ajax_nopriv_wsc_validate_registration', array( $this, 'ajax_validate_registration' ) );
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
		$ok     = $runner->generate_for_post( $post_id, array( 'force' => true ) );
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
		$auto_validation  = isset( $_POST['autoValidation'] ) ? absint( $_POST['autoValidation'] ) : 1;
		$auto_rules_raw   = isset( $_POST['autoRules'] ) ? wp_unslash( $_POST['autoRules'] ) : '{}';
		$auto_rules       = is_string( $auto_rules_raw ) ? $auto_rules_raw : wp_json_encode( $auto_rules_raw );
		$enable_recaptcha = isset( $_POST['enableRecaptcha'] ) ? absint( $_POST['enableRecaptcha'] ) : 0;
		$form_settings    = isset( $_POST['formSettings'] ) ? wp_unslash( $_POST['formSettings'] ) : array();
		$is_webrisk_post  = isset( $_POST['is_webrisk'] ) ? absint( $_POST['is_webrisk'] ) : null;
		$is_virustotal_post = isset( $_POST['is_virustotal'] ) ? absint( $_POST['is_virustotal'] ) : null;

		$sanitized_array = array();
		if ( ! $auto_validation && is_array( $form_settings ) ) {
			$sanitized_array = map_deep( $form_settings, 'sanitize_text_field' );
			foreach ( $sanitized_array as $idx => $f_item ) {
				if ( ! is_array( $f_item ) ) {
					continue;
				}
				$ft = isset( $f_item['field'] ) ? sanitize_text_field( (string) $f_item['field'] ) : 'text';
				$sanitized_array[ $idx ] = Form_Guard_Conditional::normalize_field_config( $ft, $f_item );
			}
		}

		$table = $this->wpdb->prefix . 'span_checker_form_settings';

		$row_wr = 0;
		$row_vt = 0;
		
		if ( $auto_validation ) {
			$parsed_rules = json_decode( $auto_rules, true );
			if ( is_array( $parsed_rules ) ) {
				if ( ! empty( $parsed_rules['email']['webrisk'] ) || ! empty( $parsed_rules['url']['webrisk'] ) ) {
					$row_wr = 1;
				}
				if ( ! empty( $parsed_rules['email']['virustotal'] ) || ! empty( $parsed_rules['url']['virustotal'] ) ) {
					$row_vt = 1;
				}
			}
		} else {
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
			'auto_validation'  => $auto_validation,
			'auto_rules'       => $auto_rules,
			'enable_recaptcha' => $enable_recaptcha,
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
	 * AJAX: validate auto-detected field (public).
	 */
	public function ajax_validate_auto_field() {
		check_ajax_referer( 'wp_span_checker_nonce', 'nonce' );

		$mapping_id  = isset( $_POST['mappingId'] ) ? absint( $_POST['mappingId'] ) : 0;
		$field_type  = isset( $_POST['fieldType'] ) ? sanitize_text_field( wp_unslash( $_POST['fieldType'] ) ) : '';
		$field_name  = isset( $_POST['fieldName'] ) ? sanitize_text_field( wp_unslash( $_POST['fieldName'] ) ) : '';
		$value_raw   = isset( $_POST['value'] ) ? wp_unslash( $_POST['value'] ) : '';
		$value       = is_string( $value_raw ) ? $value_raw : '';
		$rules_raw   = isset( $_POST['rules'] ) ? wp_unslash( $_POST['rules'] ) : '{}';

		if ( ! $mapping_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid mapping.', 'wp-span-checker' ), 'status' => false ) );
		}

		$rules = json_decode( $rules_raw, true );
		if ( ! is_array( $rules ) ) {
			$rules = array();
		}

		$table = $this->wpdb->prefix . 'span_checker_form_settings';
		$row   = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $mapping_id ), ARRAY_A );

		if ( ! $row ) {
			wp_send_json_error( array( 'message' => __( 'Mapping not found.', 'wp-span-checker' ), 'status' => false ) );
		}

		$result = array( 'status' => true );

		switch ( $field_type ) {
			case 'email':
				if ( ! empty( $rules['mx'] ) || ! empty( $rules['disposable'] ) || ! empty( $rules['webrisk'] ) || ! empty( $rules['virustotal'] ) ) {
					$domain = '';
					if ( strpos( $value, '@' ) !== false ) {
						$parts  = explode( '@', $value );
						$domain = end( $parts );
					}

					// Check MX record first (required before webrisk/virustotal for email)
					$mx_valid = false;
					if ( $domain ) {
						$mx_valid = wp_span_checker_check_mx_record( $domain );
					}

					if ( ! empty( $rules['mx'] ) && $domain && ! $mx_valid ) {
						$result = array(
							'status'  => false,
							'message' => __( 'Email domain appears invalid (no mail server found).', 'wp-span-checker' ),
						);
						break;
					}

					if ( ! empty( $rules['disposable'] ) && $domain ) {
						$is_disposable = wp_span_checker_is_disposable_domain( $domain );
						if ( $is_disposable ) {
							$result = array(
								'status'  => false,
								'message' => __( 'Disposable email addresses are not allowed.', 'wp-span-checker' ),
							);
							break;
						}
					}

					// Only run webrisk/virustotal if domain has valid MX record
					if ( ! $mx_valid && ( ! empty( $rules['webrisk'] ) || ! empty( $rules['virustotal'] ) ) ) {
						// Domain has no MX, skip external API checks
						break;
					}

					if ( ! empty( $rules['webrisk'] ) && $domain ) {
						$webrisk_result = wp_span_checker_check_webrisk( $domain );
						if ( $webrisk_result && isset( $webrisk_result['threat'] ) && $webrisk_result['threat'] ) {
							$result = array(
								'status'  => false,
								'message' => __( 'This domain is flagged as malicious.', 'wp-span-checker' ),
							);
							break;
						}
					}

					if ( ! empty( $rules['virustotal'] ) && $domain ) {
						$vt_result = wp_span_checker_check_virustotal( $domain );
						if ( $vt_result && isset( $vt_result['malicious'] ) && $vt_result['malicious'] > 0 ) {
							$result = array(
								'status'  => false,
								'message' => __( 'This domain is flagged as potentially harmful.', 'wp-span-checker' ),
							);
							break;
						}
					}
				}
				break;

			case 'url':
				if ( ! empty( $rules['webrisk'] ) || ! empty( $rules['virustotal'] ) ) {
					$domain = wp_span_checker_normalize_domain_input( $value );
					
					// Check domain DNS (A record) first before external API checks
					if ( $domain ) {
						$domain_exists = wp_span_checker_check_domain_dns( $domain );
						if ( ! $domain_exists ) {
							$result = array(
								'status'  => false,
								'message' => __( 'This domain does not exist or is unreachable.', 'wp-span-checker' ),
							);
							break;
						}
					}
					
					if ( ! empty( $rules['webrisk'] ) && $domain ) {
						$webrisk_result = wp_span_checker_check_webrisk( $domain );
						if ( $webrisk_result && isset( $webrisk_result['threat'] ) && $webrisk_result['threat'] ) {
							$result = array(
								'status'  => false,
								'message' => __( 'This URL is flagged as malicious.', 'wp-span-checker' ),
							);
							break;
						}
					}

					if ( ! empty( $rules['virustotal'] ) && $domain ) {
						$vt_result = wp_span_checker_check_virustotal( $domain );
						if ( $vt_result && isset( $vt_result['malicious'] ) && $vt_result['malicious'] > 0 ) {
							$result = array(
								'status'  => false,
								'message' => __( 'This URL is flagged as potentially harmful.', 'wp-span-checker' ),
							);
							break;
						}
					}
				}
				break;

			case 'textarea':
				if ( ! empty( $rules['ai_spam'] ) ) {
					// Get form context for better AI analysis
					$form_name  = '';
					$page_title = '';
					if ( $row ) {
						$form_name = ! empty( $row['form_id'] ) ? $row['form_id'] : '';
						$page_id   = ! empty( $row['page_id'] ) ? absint( $row['page_id'] ) : 0;
						if ( $page_id ) {
							$page_title = get_the_title( $page_id );
						}
					}
					
					$ai_context = array(
						'form_name'  => $form_name ? $form_name : __( 'Contact Form', 'wp-span-checker' ),
						'field_type' => 'textarea',
						'field_name' => $field_name ? $field_name : 'message',
						'page_title' => $page_title,
					);
					
					$spam_result = wp_span_checker_check_ai_spam( $value, $ai_context );
					if ( $spam_result && isset( $spam_result['is_spam'] ) && $spam_result['is_spam'] ) {
						$result = array(
							'status'  => false,
							'message' => __( 'Your message appears to be spam.', 'wp-span-checker' ),
						);
					}
				}
				break;

			case 'username':
				if ( ! empty( $rules['check_exists'] ) ) {
					$user = get_user_by( 'login', $value );
					if ( $user ) {
						$result = array(
							'status'  => false,
							'message' => __( 'This username is already taken.', 'wp-span-checker' ),
						);
					}
				}
				break;
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX: validate ALL fields in a single request.
	 */
	public function ajax_validate_all_fields() {
		// Enable error reporting for debugging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_reporting( E_ALL );
			ini_set( 'display_errors', 0 );
		}

		try {
			check_ajax_referer( 'wp_span_checker_nonce', 'nonce' );

			// Check if user is already blocked (skip for admins)
			$cfg = \WP_Span_Checker\AI_Span_Config::get();
			$is_admin_exempt = ! empty( $cfg['block_user_exempt_admins'] ) && current_user_can( 'manage_options' );
			
			if ( ! $is_admin_exempt && ! empty( $cfg['block_user_enabled'] ) ) {
				$user_id = is_user_logged_in() ? get_current_user_id() : 0;
				$strike_count = wp_span_checker_get_strike_count( $user_id );
				$max_strikes = (int) ( $cfg['block_user_max_strikes'] ?? 5 );
				
				if ( $strike_count >= $max_strikes ) {
					wp_send_json_success( array(
						'status'  => false,
						'blocked' => true,
						'errors'  => array(
							array(
								'fieldName' => 'form',
								'message'   => __( 'You have been blocked due to repeated violations. Please contact support if you believe this is an error.', 'wp-span-checker' ),
							),
						),
					) );
					return;
				}
			}

			// Verify reCAPTCHA if token provided
			$recaptcha_token = isset( $_POST['recaptcha_token'] ) ? sanitize_text_field( wp_unslash( $_POST['recaptcha_token'] ) ) : '';
			if ( $recaptcha_token ) {
				$recaptcha_result = $this->verify_recaptcha( $recaptcha_token );
				if ( ! $recaptcha_result['success'] ) {
					wp_send_json_success( array(
						'status' => false,
						'errors' => array(
							array(
								'fieldName' => 'recaptcha',
								'message'   => $recaptcha_result['message'],
							),
						),
					) );
					return;
				}
			}

			$fields_raw = isset( $_POST['fields'] ) ? wp_unslash( $_POST['fields'] ) : '[]';
			$fields     = json_decode( $fields_raw, true );
			
			if ( ! is_array( $fields ) || empty( $fields ) ) {
				wp_send_json_success( array( 'status' => true ) );
				return;
			}

			$errors = array();
			$table  = $this->wpdb->prefix . 'span_checker_form_settings';

			foreach ( $fields as $field ) {
				$field_type  = isset( $field['fieldType'] ) ? sanitize_text_field( $field['fieldType'] ) : '';
				$field_name  = isset( $field['fieldName'] ) ? sanitize_text_field( $field['fieldName'] ) : '';
				$field_index = isset( $field['fieldIndex'] ) ? absint( $field['fieldIndex'] ) : null;
				$value       = isset( $field['value'] ) ? $field['value'] : '';
				$rules       = isset( $field['rules'] ) && is_array( $field['rules'] ) ? $field['rules'] : array();
				$mapping_id  = isset( $field['mappingId'] ) ? absint( $field['mappingId'] ) : 0;
				$type        = isset( $field['type'] ) ? $field['type'] : 'auto';

				if ( empty( $value ) ) {
					continue;
				}

				$error = null;

				// Get mapping row for context
				$row = null;
				if ( $mapping_id ) {
					$row = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $mapping_id ), ARRAY_A );
				}

				switch ( $field_type ) {
					case 'email':
						$domain = '';
						if ( strpos( $value, '@' ) !== false ) {
							$parts  = explode( '@', $value );
							$domain = end( $parts );
						}

						if ( empty( $domain ) ) {
							$error = __( 'Invalid email address format.', 'wp-span-checker' );
							break;
						}

						// Check if API checks are enabled - DNS and MX become mandatory prerequisites
						$needs_api_checks = ! empty( $rules['webrisk'] ) || ! empty( $rules['virustotal'] );

						// Check DNS A record (domain exists) - mandatory if API checks enabled
						$dns_valid = wp_span_checker_check_domain_dns( $domain );
						if ( $needs_api_checks && ! $dns_valid ) {
							$error = __( 'Email domain does not exist (no DNS A record found).', 'wp-span-checker' );
							break;
						}

						// Check MX record
						$mx_valid = wp_span_checker_check_mx_record( $domain );

						// If MX rule explicitly enabled OR API checks need MX as prerequisite
						if ( ( ! empty( $rules['mx'] ) || $needs_api_checks ) && ! $mx_valid ) {
							$error = __( 'Email domain cannot receive emails (no MX record found).', 'wp-span-checker' );
							break;
						}

						if ( ! empty( $rules['disposable'] ) ) {
							if ( wp_span_checker_is_disposable_domain( $domain ) ) {
								$error = __( 'Disposable email addresses are not allowed.', 'wp-span-checker' );
								break;
							}
						}

						// Run API checks only if domain is verified live (DNS + MX passed)
						if ( ! empty( $rules['webrisk'] ) ) {
							$webrisk_result = wp_span_checker_check_webrisk( $domain );
							if ( $webrisk_result && isset( $webrisk_result['threat'] ) && $webrisk_result['threat'] ) {
								$error = __( 'This domain is flagged as malicious.', 'wp-span-checker' );
								break;
							}
						}

						if ( ! empty( $rules['virustotal'] ) ) {
							$vt_result = wp_span_checker_check_virustotal( $domain );
							if ( $vt_result && isset( $vt_result['malicious'] ) && $vt_result['malicious'] > 0 ) {
								$error = __( 'This domain is flagged as potentially harmful.', 'wp-span-checker' );
								break;
							}
						}
						break;

					case 'url':
						$domain = wp_span_checker_normalize_domain_input( $value );
						
						if ( empty( $domain ) ) {
							break; // No domain to check
						}

						// Check if API checks are enabled - DNS becomes mandatory prerequisite
						$needs_api_checks = ! empty( $rules['webrisk'] ) || ! empty( $rules['virustotal'] );
						
						// Check domain DNS - mandatory before API checks
						$domain_exists = wp_span_checker_check_domain_dns( $domain );
						if ( $needs_api_checks && ! $domain_exists ) {
							$error = __( 'This domain does not exist (no DNS A record found).', 'wp-span-checker' );
							break;
						}
						
						// Run API checks only if domain exists
						if ( ! empty( $rules['webrisk'] ) ) {
							$webrisk_result = wp_span_checker_check_webrisk( $domain );
							if ( $webrisk_result && isset( $webrisk_result['threat'] ) && $webrisk_result['threat'] ) {
								$error = __( 'This URL is flagged as malicious.', 'wp-span-checker' );
								break;
							}
						}

						if ( ! empty( $rules['virustotal'] ) ) {
							$vt_result = wp_span_checker_check_virustotal( $domain );
							if ( $vt_result && isset( $vt_result['malicious'] ) && $vt_result['malicious'] > 0 ) {
								$error = __( 'This URL is flagged as potentially harmful.', 'wp-span-checker' );
								break;
							}
						}
						break;

					case 'textarea':
						if ( ! empty( $rules['ai_spam'] ) ) {
							// Get form context for better AI analysis
							$form_name  = '';
							$page_title = '';
							if ( $row ) {
								$form_name = ! empty( $row['form_id'] ) ? $row['form_id'] : '';
								$page_id   = ! empty( $row['page_id'] ) ? absint( $row['page_id'] ) : 0;
								if ( $page_id ) {
									$page_title = get_the_title( $page_id );
								}
							}
							
							$ai_context = array(
								'form_name'  => $form_name ? $form_name : __( 'Contact Form', 'wp-span-checker' ),
								'field_type' => 'textarea',
								'field_name' => $field_name ? $field_name : 'message',
								'page_title' => $page_title,
							);
							
							$spam_result = wp_span_checker_check_ai_spam( $value, $ai_context );
							if ( $spam_result && isset( $spam_result['is_spam'] ) && $spam_result['is_spam'] ) {
								$error = __( 'Your message appears to be spam.', 'wp-span-checker' );
							}
						}
						break;

					case 'username':
						if ( ! empty( $rules['check_exists'] ) ) {
							$user = get_user_by( 'login', $value );
							if ( $user ) {
								$error = __( 'This username is already taken.', 'wp-span-checker' );
							}
						}
						break;
				}

				if ( $error ) {
					$errors[] = array(
						'fieldName'  => $field_name,
						'fieldIndex' => $field_index,
						'fieldType'  => $field_type,
						'message'    => $error,
					);
				}
			}

			if ( ! empty( $errors ) ) {
				// Record a strike for the spam attempt
				$strike_reasons = array();
				foreach ( $errors as $err ) {
					if ( isset( $err['message'] ) ) {
						$strike_reasons[] = $err['message'];
					}
				}
				$reason = implode( '; ', array_slice( $strike_reasons, 0, 3 ) );
				$strike_result = wp_span_checker_record_strike( $reason, 'form_guard' );

				$response = array(
					'status' => false,
					'errors' => $errors,
				);

				// Add strike info to response
				if ( $strike_result['blocked'] ) {
					$response['blocked'] = true;
					$response['strike_message'] = __( 'You have been blocked due to repeated violations.', 'wp-span-checker' );
				}

				wp_send_json_success( $response );
				return;
			}

			wp_send_json_success( array( 'status' => true ) );

		} catch ( \Exception $e ) {
			wp_send_json_error( array(
				'message' => 'Validation error: ' . $e->getMessage(),
				'status'  => false,
			) );
		} catch ( \Error $e ) {
			wp_send_json_error( array(
				'message' => 'PHP error: ' . $e->getMessage(),
				'status'  => false,
			) );
		}
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

/**
 * AJAX: search pages by title with pagination.
 */
public function ajax_search_pages() {
	check_ajax_referer( 'wp_span_checker_nonce', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-span-checker' ) ) );
	}

	$search   = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
	$per_page = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 20;
	$page_num = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;

	if ( $per_page > 100 ) {
		$per_page = 100;
	}
	if ( $per_page < 1 ) {
		$per_page = 20;
	}

	$args = array(
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'posts_per_page' => $per_page,
		'paged'          => $page_num,
		'orderby'        => 'title',
		'order'          => 'ASC',
	);

	if ( '' !== $search ) {
		$args['s'] = $search;
	}

	$query   = new \WP_Query( $args );
	$results = array();

	foreach ( $query->posts as $post ) {
		$title = wp_strip_all_tags( $post->post_title );
		if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) && mb_strlen( $title ) > 60 ) {
			$title = mb_substr( $title, 0, 60 ) . '...';
		}
		$results[] = array(
			'id'    => $post->ID,
			'title' => $title,
			'type'  => 'page',
		);
	}

	wp_send_json_success(
		array(
			'items'       => $results,
			'total'       => $query->found_posts,
			'total_pages' => $query->max_num_pages,
			'page'        => $page_num,
		)
	);
}

/**
 * AJAX: search posts by title with pagination.
 */
public function ajax_search_posts() {
	check_ajax_referer( 'wp_span_checker_nonce', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-span-checker' ) ) );
	}

	$search   = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
	$per_page = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 20;
	$page_num = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;

	if ( $per_page > 100 ) {
		$per_page = 100;
	}
	if ( $per_page < 1 ) {
		$per_page = 20;
	}

	$args = array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => $per_page,
		'paged'          => $page_num,
		'orderby'        => 'title',
		'order'          => 'ASC',
	);

	if ( '' !== $search ) {
		$args['s'] = $search;
	}

	$query   = new \WP_Query( $args );
	$results = array();

	foreach ( $query->posts as $post ) {
		$title = wp_strip_all_tags( $post->post_title );
		if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) && mb_strlen( $title ) > 60 ) {
			$title = mb_substr( $title, 0, 60 ) . '...';
		}
		$results[] = array(
			'id'    => $post->ID,
			'title' => $title,
			'type'  => 'post',
		);
	}

	wp_send_json_success(
		array(
			'items'       => $results,
			'total'       => $query->found_posts,
			'total_pages' => $query->max_num_pages,
			'page'        => $page_num,
		)
	);
}

	/**
	 * Verify Google reCAPTCHA token.
	 *
	 * @param string $token The reCAPTCHA response token.
	 * @return array
	 */
	private function verify_recaptcha( $token ) {
		if ( empty( $token ) ) {
			return array(
				'success' => false,
				'message' => __( 'reCAPTCHA verification required.', 'wp-span-checker' ),
			);
		}

		$config = get_option( 'wsc-recaptcha-config', array() );
		if ( ! is_array( $config ) ) {
			$config = array();
		}
		$secret = isset( $config['secret_key'] ) ? $config['secret_key'] : '';
		
		if ( empty( $secret ) ) {
			// No secret key configured, skip verification
			return array( 'success' => true, 'message' => '' );
		}

		$response = wp_remote_post(
			'https://www.google.com/recaptcha/api/siteverify',
			array(
				'timeout' => 15,
				'body'    => array(
					'secret'   => $secret,
					'response' => $token,
					'remoteip' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => __( 'reCAPTCHA verification failed. Please try again.', 'wp-span-checker' ),
			);
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) || empty( $data['success'] ) ) {
			$error_codes = isset( $data['error-codes'] ) ? implode( ', ', $data['error-codes'] ) : 'unknown';
			return array(
				'success' => false,
				'message' => __( 'reCAPTCHA verification failed. Please try again.', 'wp-span-checker' ),
			);
		}

		// For v3, check score (0.0 - 1.0, higher is more likely human)
		$version = isset( $config['version'] ) ? $config['version'] : 'v2';
		if ( 'v3' === $version ) {
			$score = isset( $data['score'] ) ? (float) $data['score'] : 0.0;
			// Block if score is too low (threshold 0.5)
			if ( $score < 0.5 ) {
				return array(
					'success' => false,
					'message' => __( 'reCAPTCHA verification failed. Please try again.', 'wp-span-checker' ),
					'score'   => $score,
				);
			}
		}

		return array(
			'success' => true,
			'message' => '',
			'score'   => isset( $data['score'] ) ? (float) $data['score'] : null,
		);
	}

	/**
	 * AJAX: Validate subscribe/newsletter email.
	 */
	public function ajax_validate_subscribe() {
		check_ajax_referer( 'wp_span_checker_nonce', 'nonce' );

		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		
		if ( empty( $email ) || ! is_email( $email ) ) {
			wp_send_json_success( array(
				'status'  => false,
				'message' => __( 'Please enter a valid email address.', 'wp-span-checker' ),
			) );
			return;
		}

		// Get subscribe guard settings
		$cfg = \WP_Span_Checker\AI_Span_Config::get();
		
		// Extract domain from email
		$parts  = explode( '@', $email );
		$domain = end( $parts );

		if ( empty( $domain ) ) {
			wp_send_json_success( array(
				'status'  => false,
				'message' => __( 'Invalid email address format.', 'wp-span-checker' ),
			) );
			return;
		}

		// Check if Web Risk or VirusTotal is enabled - DNS and MX become mandatory prerequisites
		$needs_api_checks = ! empty( $cfg['subscribe_guard_webrisk'] ) || ! empty( $cfg['subscribe_guard_virustotal'] );

		// Check DNS A record (domain exists) - mandatory if API checks enabled OR if explicitly enabled
		if ( ! empty( $cfg['subscribe_guard_check_dns'] ) || $needs_api_checks ) {
			$has_dns = wp_span_checker_check_domain_dns( $domain );
			if ( ! $has_dns ) {
				wp_send_json_success( array(
					'status'  => false,
					'message' => __( 'This email domain does not exist (no DNS A record found).', 'wp-span-checker' ),
				) );
				return;
			}
		}

		// Check MX record (can receive email) - mandatory if API checks enabled OR if explicitly enabled
		if ( ! empty( $cfg['subscribe_guard_check_mx'] ) || $needs_api_checks ) {
			$has_mx = wp_span_checker_check_mx_record( $domain );
			if ( ! $has_mx ) {
				wp_send_json_success( array(
					'status'  => false,
					'message' => __( 'This email domain cannot receive emails (no MX record found).', 'wp-span-checker' ),
				) );
				return;
			}
		}

		// Check disposable domains
		if ( ! empty( $cfg['subscribe_guard_check_disposable'] ) ) {
			$is_disposable = wp_span_checker_is_disposable_domain( $domain );
			if ( $is_disposable ) {
				wp_send_json_success( array(
					'status'  => false,
					'message' => __( 'Disposable email addresses are not allowed.', 'wp-span-checker' ),
				) );
				return;
			}
		}

		// Check Web Risk (only if domain is live - verified above)
		if ( ! empty( $cfg['subscribe_guard_webrisk'] ) ) {
			$webrisk_result = wp_span_checker_check_webrisk( $domain );
			if ( $webrisk_result && ! empty( $webrisk_result['threat'] ) ) {
				wp_send_json_success( array(
					'status'  => false,
					'message' => __( 'This email domain has been flagged for security issues.', 'wp-span-checker' ),
				) );
				return;
			}
		}

		// Check VirusTotal (only if domain is live - verified above)
		if ( ! empty( $cfg['subscribe_guard_virustotal'] ) ) {
			$vt_result = wp_span_checker_check_virustotal( $domain );
			if ( $vt_result && isset( $vt_result['malicious'] ) && $vt_result['malicious'] > 0 ) {
				wp_send_json_success( array(
					'status'  => false,
					'message' => __( 'This email domain has been flagged as potentially harmful.', 'wp-span-checker' ),
				) );
				return;
			}
		}

		// All checks passed
		wp_send_json_success( array(
			'status'  => true,
			'message' => '',
		) );
	}

	/**
	 * AJAX: Validate contact form (email + message).
	 */
	public function ajax_validate_contact() {
		check_ajax_referer( 'wp_span_checker_nonce', 'nonce' );

		$email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
		
		$errors = array();

		// Get contact guard settings
		$cfg = \WP_Span_Checker\AI_Span_Config::get();

		// Validate email
		if ( ! empty( $email ) ) {
			if ( ! is_email( $email ) ) {
				$errors[] = array(
					'field'   => 'email',
					'message' => __( 'Please enter a valid email address.', 'wp-span-checker' ),
				);
			} else {
				$parts  = explode( '@', $email );
				$domain = end( $parts );

				// Check if Web Risk or VirusTotal is enabled - DNS and MX become mandatory prerequisites
				$needs_api_checks = ! empty( $cfg['contact_guard_webrisk'] ) || ! empty( $cfg['contact_guard_virustotal'] );

				// Check DNS - mandatory if API checks enabled OR if explicitly enabled
				if ( ( ! empty( $cfg['contact_guard_check_dns'] ) || $needs_api_checks ) && ! empty( $domain ) ) {
					$has_dns = wp_span_checker_check_domain_dns( $domain );
					if ( ! $has_dns ) {
						$errors[] = array(
							'field'   => 'email',
							'message' => __( 'This email domain does not exist (no DNS A record found).', 'wp-span-checker' ),
						);
					}
				}

				// Check MX - mandatory if API checks enabled OR if explicitly enabled
				if ( empty( $errors ) && ( ! empty( $cfg['contact_guard_check_mx'] ) || $needs_api_checks ) && ! empty( $domain ) ) {
					$has_mx = wp_span_checker_check_mx_record( $domain );
					if ( ! $has_mx ) {
						$errors[] = array(
							'field'   => 'email',
							'message' => __( 'This email domain cannot receive emails (no MX record found).', 'wp-span-checker' ),
						);
					}
				}

				// Check disposable
				if ( empty( $errors ) && ! empty( $cfg['contact_guard_check_disposable'] ) && ! empty( $domain ) ) {
					$is_disposable = wp_span_checker_is_disposable_domain( $domain );
					if ( $is_disposable ) {
						$errors[] = array(
							'field'   => 'email',
							'message' => __( 'Disposable email addresses are not allowed.', 'wp-span-checker' ),
						);
					}
				}

				// Check Web Risk (only if domain is live - verified above)
				if ( empty( $errors ) && ! empty( $cfg['contact_guard_webrisk'] ) && ! empty( $domain ) ) {
					$webrisk_result = wp_span_checker_check_webrisk( $domain );
					if ( $webrisk_result && ! empty( $webrisk_result['threat'] ) ) {
						$errors[] = array(
							'field'   => 'email',
							'message' => __( 'This email domain has been flagged for security issues.', 'wp-span-checker' ),
						);
					}
				}

				// Check VirusTotal (only if domain is live - verified above)
				if ( empty( $errors ) && ! empty( $cfg['contact_guard_virustotal'] ) && ! empty( $domain ) ) {
					$vt_result = wp_span_checker_check_virustotal( $domain );
					if ( $vt_result && isset( $vt_result['malicious'] ) && $vt_result['malicious'] > 0 ) {
						$errors[] = array(
							'field'   => 'email',
							'message' => __( 'This email domain has been flagged as potentially harmful.', 'wp-span-checker' ),
						);
					}
				}
			}
		}

		// Validate message with AI spam check
		if ( ! empty( $message ) && ! empty( $cfg['contact_guard_ai_spam'] ) ) {
			$ai_context = array(
				'form_name'  => __( 'Contact Form', 'wp-span-checker' ),
				'field_type' => 'textarea',
				'field_name' => 'message',
				'page_title' => '',
			);

			$spam_result = wp_span_checker_check_ai_spam( $message, $ai_context );
			if ( $spam_result && isset( $spam_result['is_spam'] ) && $spam_result['is_spam'] ) {
				$errors[] = array(
					'field'   => 'message',
					'message' => __( 'Your message appears to be spam.', 'wp-span-checker' ),
				);
			}
		}

		// Record strike if errors
		if ( ! empty( $errors ) ) {
			$reasons = array_map( function( $e ) { return $e['message']; }, $errors );
			wp_span_checker_record_strike( implode( '; ', $reasons ), 'contact_guard' );

			wp_send_json_success( array(
				'status' => false,
				'errors' => $errors,
			) );
			return;
		}

		// All checks passed
		wp_send_json_success( array(
			'status' => true,
		) );
	}

	/**
	 * AJAX: Validate registration email (frontend validation).
	 * Validates email and stores a validation token for backend verification.
	 */
	public function ajax_validate_registration() {
		check_ajax_referer( 'wp_span_checker_nonce', 'nonce' );

		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		
		if ( empty( $email ) || ! is_email( $email ) ) {
			wp_send_json_success( array(
				'status'  => false,
				'message' => __( 'Please enter a valid email address.', 'wp-span-checker' ),
			) );
			return;
		}

		// Check reCAPTCHA if provided
		$recaptcha_token = isset( $_POST['recaptcha_token'] ) ? sanitize_text_field( wp_unslash( $_POST['recaptcha_token'] ) ) : '';
		$ai_cfg          = \WP_Span_Checker\AI_Span_Config::get();
		
		if ( ! empty( $ai_cfg['registration_guard_recaptcha'] ) && ! empty( $recaptcha_token ) ) {
			$recaptcha_result = $this->verify_recaptcha( $recaptcha_token );
			if ( ! $recaptcha_result['success'] ) {
				wp_send_json_success( array(
					'status'  => false,
					'message' => __( 'reCAPTCHA verification failed. Please try again.', 'wp-span-checker' ),
				) );
				return;
			}
		}

		// Use the Registration Guard validation logic
		$rejection_msg = \WP_Span_Checker\Registration_Guard::rejection_message_for_registration_email( $email );
		
		if ( $rejection_msg !== null ) {
			wp_send_json_success( array(
				'status'  => false,
				'message' => $rejection_msg,
			) );
			return;
		}

		// Generate validation token (IP-based)
		$ip         = function_exists( 'wp_span_checker_get_user_ip' ) ? wp_span_checker_get_user_ip() : '';
		$token      = wp_generate_password( 32, false, false );
		$token_key  = 'wsc_reg_token_' . md5( $ip . $email );
		
		// Store token for 5 minutes
		set_transient( $token_key, array(
			'email' => $email,
			'ip'    => $ip,
			'token' => $token,
			'time'  => time(),
		), 5 * MINUTE_IN_SECONDS );

		// All checks passed
		wp_send_json_success( array(
			'status' => true,
			'token'  => $token,
		) );
	}
}
