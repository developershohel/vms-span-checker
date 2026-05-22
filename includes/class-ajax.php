<?php
/**
 * AJAX handlers.
 *
 * All direct `$wpdb` calls below target plugin-owned custom tables (mappings,
 * form settings, comment moderation tables, etc.). Table identifiers are
 * always `{$wpdb->prefix}` + a hardcoded suffix; values are prepared via
 * `$wpdb->prepare()` or insert / update / delete helpers.
 *
 * @package VMS_Span_Checker
 *
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 * phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
 * phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
 * phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter
 */

namespace VMS_Span_Checker;

use Exception;
use VMS_Span_Checker\Services\Domain_Validator;

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
		// Form Guard CRUD endpoints moved to vms-span-checker-pro (Pro_Ajax).
		add_action( 'wp_ajax_validateDomainName', array( $this, 'ajax_validate_domain_name' ) );
		add_action( 'wp_ajax_nopriv_validateDomainName', array( $this, 'ajax_validate_domain_name' ) );
		add_action( 'wp_ajax_validateFormGuardField', array( $this, 'ajax_validate_form_guard_field' ) );
		add_action( 'wp_ajax_nopriv_validateFormGuardField', array( $this, 'ajax_validate_form_guard_field' ) );
		add_action( 'wp_ajax_validateAutoField', array( $this, 'ajax_validate_auto_field' ) );
		add_action( 'wp_ajax_nopriv_validateAutoField', array( $this, 'ajax_validate_auto_field' ) );
		add_action( 'wp_ajax_validateAllFields', array( $this, 'ajax_validate_all_fields' ) );
		add_action( 'wp_ajax_nopriv_validateAllFields', array( $this, 'ajax_validate_all_fields' ) );
		// AI summary regeneration is a Pro feature (Pro_Ajax handles it).
		add_action( 'wp_ajax_import_whitelist_seed', array( $this, 'ajax_import_whitelist_seed' ) );
		add_action( 'wp_ajax_wsc_search_pages', array( $this, 'ajax_search_pages' ) );
		add_action( 'wp_ajax_wsc_search_posts', array( $this, 'ajax_search_posts' ) );
		// Subscribe Guard and Contact Guard validation moved to Pro_Ajax.
		add_action( 'wp_ajax_wsc_validate_registration', array( $this, 'ajax_validate_registration' ) );
		add_action( 'wp_ajax_nopriv_wsc_validate_registration', array( $this, 'ajax_validate_registration' ) );
		add_action( 'wp_ajax_wsc_lookup_user', array( $this, 'ajax_lookup_user' ) );
		add_action( 'wp_ajax_wsc_manual_block_user', array( $this, 'ajax_manual_block_user' ) );
		add_action( 'wp_ajax_wsc_edit_block_scope', array( $this, 'ajax_edit_block_scope' ) );
		add_action( 'wp_ajax_wsc_unblock_user', array( $this, 'ajax_unblock_user' ) );
	}

	/**
	 * AJAX: unblock a user by user ID (clears strikes + all scope flags).
	 */
	public function ajax_unblock_user() {
		check_ajax_referer( 'vms_span_checker_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'vms-span-checker' ) ) );
		}

		$user_id = isset( $_POST['user_id'] ) ? absint( wp_unslash( $_POST['user_id'] ) ) : 0;
		if ( $user_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid user ID.', 'vms-span-checker' ) ) );
		}

		$actor_key = 'u:' . $user_id;
		if ( ! AI_Span_Comments::admin_unblock( $actor_key ) ) {
			wp_send_json_error( array( 'message' => __( 'Could not unblock that user.', 'vms-span-checker' ) ) );
		}

		wp_send_json_success(
			array(
				'message'   => __( 'User unblocked.', 'vms-span-checker' ),
				'actor_key' => $actor_key,
			)
		);
	}

	/**
	 * AJAX: resolve a user by ID, username, or email; return summary card data.
	 */
	public function ajax_lookup_user() {
		check_ajax_referer( 'vms_span_checker_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'vms-span-checker' ) ) );
		}

		$query = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['query'] ) ) : '';
		if ( '' === $query ) {
			wp_send_json_error( array( 'message' => __( 'Enter an ID, username, or email.', 'vms-span-checker' ) ) );
		}

		$user = AI_Span_Comments::find_user_by_input( $query );
		if ( ! $user instanceof \WP_User ) {
			wp_send_json_error( array( 'message' => __( 'No user matches that input.', 'vms-span-checker' ) ) );
		}

		$actor_key   = 'u:' . (int) $user->ID;
		$existing    = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT actor_key, strikes, blocked, site_banned, login_blocked, last_reason FROM {$this->wpdb->prefix}vms_span_checker_comment_enforcement WHERE actor_key = %s",
				$actor_key
			),
			ARRAY_A
		);
		$is_blocked  = is_array( $existing ) && (
			! empty( $existing['blocked'] ) ||
			! empty( $existing['site_banned'] ) ||
			! empty( $existing['login_blocked'] )
		);

		$avatar_url = get_avatar_url( $user->ID, array( 'size' => 64 ) );

		wp_send_json_success(
			array(
				'user'    => array(
					'id'           => (int) $user->ID,
					'login'        => $user->user_login,
					'email'        => $user->user_email,
					'display_name' => $user->display_name,
					'roles'        => array_values( (array) $user->roles ),
					'avatar'       => $avatar_url ? (string) $avatar_url : '',
					'edit_url'     => current_user_can( 'edit_user', $user->ID )
						? get_edit_user_link( $user->ID )
						: '',
				),
				'block'   => array(
					'is_blocked'    => $is_blocked,
					'strikes'       => is_array( $existing ) ? (int) ( $existing['strikes'] ?? 0 ) : 0,
					'form'          => is_array( $existing ) ? ! empty( $existing['blocked'] ) : false,
					'login'         => is_array( $existing ) ? ! empty( $existing['login_blocked'] ) : false,
					'site'          => is_array( $existing ) ? ! empty( $existing['site_banned'] ) : false,
					'last_reason'   => is_array( $existing ) ? (string) ( $existing['last_reason'] ?? '' ) : '',
					'actor_key'     => $actor_key,
				),
			)
		);
	}

	/**
	 * AJAX: manually block a user by ID (after lookup).
	 */
	public function ajax_manual_block_user() {
		check_ajax_referer( 'vms_span_checker_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'vms-span-checker' ) ) );
		}

		$user_id = isset( $_POST['user_id'] ) ? absint( wp_unslash( $_POST['user_id'] ) ) : 0;
		if ( $user_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid user ID.', 'vms-span-checker' ) ) );
		}

		$scope_raw = isset( $_POST['scope'] )
			? map_deep( (array) wp_unslash( $_POST['scope'] ), 'sanitize_key' )
			: array();
		$scope = array();
		foreach ( $scope_raw as $s ) {
			$s = (string) $s;
			if ( in_array( $s, array( 'form', 'login', 'site' ), true ) ) {
				$scope[] = $s;
			}
		}

		$reason      = isset( $_POST['reason'] ) ? sanitize_textarea_field( wp_unslash( (string) $_POST['reason'] ) ) : '';
		$expiry_days = isset( $_POST['expiry_days'] ) ? absint( wp_unslash( $_POST['expiry_days'] ) ) : 0;

		$result = AI_Span_Comments::admin_manual_block(
			$user_id,
			array(
				'scope'       => $scope,
				'reason'      => $reason,
				'expiry_days' => $expiry_days,
			)
		);

		if ( empty( $result['success'] ) ) {
			wp_send_json_error( array( 'message' => $result['message'] ) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX: update block scope flags on an existing enforcement row.
	 */
	public function ajax_edit_block_scope() {
		check_ajax_referer( 'vms_span_checker_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'vms-span-checker' ) ) );
		}

		$actor_key = isset( $_POST['actor_key'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['actor_key'] ) ) : '';
		if ( '' === $actor_key ) {
			wp_send_json_error( array( 'message' => __( 'Missing actor key.', 'vms-span-checker' ) ) );
		}

		$scope_raw = isset( $_POST['scope'] )
			? map_deep( (array) wp_unslash( $_POST['scope'] ), 'sanitize_key' )
			: array();
		$scope = array();
		foreach ( $scope_raw as $s ) {
			$s = (string) $s;
			if ( in_array( $s, array( 'form', 'login', 'site' ), true ) ) {
				$scope[] = $s;
			}
		}

		$ok = AI_Span_Comments::admin_edit_block_scope( $actor_key, $scope );
		if ( ! $ok ) {
			wp_send_json_error( array( 'message' => __( 'Could not update block scope.', 'vms-span-checker' ) ) );
		}

		wp_send_json_success( array( 'message' => __( 'Block scope updated.', 'vms-span-checker' ) ) );
	}

	/**
	 * AJAX: generate or refresh AI summary for one post.
	 */
	// ajax_ai_regenerate_summary moved to vms-span-checker-pro (Pro_Ajax).

	/**
	 * AJAX: list whitelist or disposable domains.
	 */
	public function ajax_get_domains() {
		check_ajax_referer( 'vms_span_checker_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'vms-span-checker' ) ) );
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

	// Form Guard CRUD handlers (ajax_get_form_settings, ajax_add_form_settings,
	// ajax_delete_form_setting) have been moved to vms-span-checker-pro (Pro_Ajax).

	/**
	 * AJAX: add domain to whitelist or disposable list.
	 */
	public function ajax_add_domain() {
		check_ajax_referer( 'vms_span_checker_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'vms-span-checker' ) ) );
		}

		$type   = isset( $_POST['domain_type'] ) ? sanitize_text_field( wp_unslash( $_POST['domain_type'] ) ) : 'whitelist';
		$domain = isset( $_POST['domain'] ) ? sanitize_text_field( wp_unslash( $_POST['domain'] ) ) : '';

		if ( ! in_array( $type, array( 'whitelist', 'disposable' ), true ) ) {
			$type = 'whitelist';
		}

		if ( '' === $domain ) {
			wp_send_json_error( array( 'message' => __( 'Domain is required.', 'vms-span-checker' ) ) );
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
		check_ajax_referer( 'vms_span_checker_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'vms-span-checker' ) ) );
		}

		$type = isset( $_POST['domain_type'] ) ? sanitize_text_field( wp_unslash( $_POST['domain_type'] ) ) : 'whitelist';
		$id   = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

		if ( ! in_array( $type, array( 'whitelist', 'disposable' ), true ) ) {
			$type = 'whitelist';
		}

		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid ID.', 'vms-span-checker' ) ) );
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
		check_ajax_referer( 'vms_span_checker_nonce', 'nonce' );

		// Raw domain value is normalized + lowercased by the helper below.
		$raw    = isset( $_POST['domain'] ) ? wp_unslash( $_POST['domain'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by normalizer.
		$domain = vms_span_checker_normalize_domain_input( $raw );

		if ( '' === $domain ) {
			wp_send_json_error(
				array(
					'message' => __( 'Domain is required.', 'vms-span-checker' ),
					'status'  => false,
				)
			);
		}

		$type     = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : 'unknown';
		$ip       = vms_span_checker_get_user_ip();
		$settings = vms_span_checker_parse_validation_settings( wp_unslash( $_POST ) );

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
		check_ajax_referer( 'vms_span_checker_nonce', 'nonce' );

		$mapping_id  = isset( $_POST['mappingId'] ) ? absint( $_POST['mappingId'] ) : 0;
		$field_index = isset( $_POST['fieldIndex'] ) ? absint( $_POST['fieldIndex'] ) : 0;
		// Field value is passed through the field-specific validator, which
		// handles type-specific sanitization for text, email, url, etc.
		$value_raw   = isset( $_POST['value'] ) ? wp_unslash( $_POST['value'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated by validator.
		$value       = is_string( $value_raw ) ? $value_raw : '';

		if ( ! $mapping_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid mapping.', 'vms-span-checker' ), 'status' => false ) );
		}

		$table = $this->wpdb->prefix . 'vms_span_checker_form_settings';
		$row   = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $mapping_id ), ARRAY_A );

		if ( ! $row || empty( $row['settings'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Mapping not found.', 'vms-span-checker' ), 'status' => false ) );
		}

		$fields = json_decode( $row['settings'], true );
		if ( ! is_array( $fields ) || ! isset( $fields[ $field_index ] ) || ! is_array( $fields[ $field_index ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Field configuration not found.', 'vms-span-checker' ), 'status' => false ) );
		}

		$field = $fields[ $field_index ];
		$type  = isset( $field['field'] ) ? sanitize_text_field( (string) $field['field'] ) : 'text';

		// Form Guard validation is a Pro feature. Without it, the only safe
		// answer is to accept the value (no rules configured); Pro overrides
		// this endpoint when active.
		if ( ! class_exists( '\\VMS_Span_Checker\\Form_Guard_Conditional' ) ) {
			wp_send_json_success( array( 'success' => true, 'message' => '' ) );
		}

		$result = \VMS_Span_Checker\Form_Guard_Conditional::validate_field_value( $type, $field, $value, $row );
		wp_send_json_success( $result );
	}

	/**
	 * AJAX: validate auto-detected field (public).
	 */
	public function ajax_validate_auto_field() {
		check_ajax_referer( 'vms_span_checker_nonce', 'nonce' );

		$mapping_id  = isset( $_POST['mappingId'] ) ? absint( $_POST['mappingId'] ) : 0;
		$field_type  = isset( $_POST['fieldType'] ) ? sanitize_text_field( wp_unslash( $_POST['fieldType'] ) ) : '';
		$field_name  = isset( $_POST['fieldName'] ) ? sanitize_text_field( wp_unslash( $_POST['fieldName'] ) ) : '';
		// Field value is passed through the field-specific validator.
		$value_raw   = isset( $_POST['value'] ) ? wp_unslash( $_POST['value'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated by validator.
		$value       = is_string( $value_raw ) ? $value_raw : '';
		// Raw JSON rules are decoded below; text sanitization is not appropriate.
		$rules_raw   = isset( $_POST['rules'] ) ? wp_unslash( $_POST['rules'] ) : '{}'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON string sanitized by decoder.

		if ( ! $mapping_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid mapping.', 'vms-span-checker' ), 'status' => false ) );
		}

		$rules = json_decode( $rules_raw, true );
		if ( ! is_array( $rules ) ) {
			$rules = array();
		}

		$table = $this->wpdb->prefix . 'vms_span_checker_form_settings';
		$row   = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $mapping_id ), ARRAY_A );

		if ( ! $row ) {
			wp_send_json_error( array( 'message' => __( 'Mapping not found.', 'vms-span-checker' ), 'status' => false ) );
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
						$mx_valid = vms_span_checker_check_mx_record( $domain );
					}

					if ( ! empty( $rules['mx'] ) && $domain && ! $mx_valid ) {
						$result = array(
							'status'  => false,
							'message' => vms_span_checker_get_error_message( 'email_mx_failed' ),
						);
						break;
					}

					if ( ! empty( $rules['disposable'] ) && $domain ) {
						$is_disposable = vms_span_checker_is_disposable_domain( $domain );
						if ( $is_disposable ) {
							$result = array(
								'status'  => false,
								'message' => vms_span_checker_get_error_message( 'email_disposable' ),
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
						$webrisk_result = vms_span_checker_check_webrisk( $domain );
						if ( $webrisk_result && isset( $webrisk_result['threat'] ) && $webrisk_result['threat'] ) {
							$result = array(
								'status'  => false,
								'message' => vms_span_checker_get_error_message( 'email_webrisk_flagged' ),
							);
							break;
						}
					}

					if ( ! empty( $rules['virustotal'] ) && $domain ) {
						$vt_result = vms_span_checker_check_virustotal( $domain );
						if ( $vt_result && isset( $vt_result['malicious'] ) && $vt_result['malicious'] > 0 ) {
							$result = array(
								'status'  => false,
								'message' => vms_span_checker_get_error_message( 'email_virustotal_flagged' ),
							);
							break;
						}
					}
				}
				break;

			case 'url':
				if ( ! empty( $rules['webrisk'] ) || ! empty( $rules['virustotal'] ) ) {
					$domain = vms_span_checker_normalize_domain_input( $value );
					
					// Check domain DNS (A record) first before external API checks
					if ( $domain ) {
						$domain_exists = vms_span_checker_check_domain_dns( $domain );
						if ( ! $domain_exists ) {
							$result = array(
								'status'  => false,
								'message' => vms_span_checker_get_error_message( 'url_dns_failed' ),
							);
							break;
						}
					}
					
					if ( ! empty( $rules['webrisk'] ) && $domain ) {
						$webrisk_result = vms_span_checker_check_webrisk( $domain );
						if ( $webrisk_result && isset( $webrisk_result['threat'] ) && $webrisk_result['threat'] ) {
							$result = array(
								'status'  => false,
								'message' => vms_span_checker_get_error_message( 'url_webrisk_flagged' ),
							);
							break;
						}
					}

					if ( ! empty( $rules['virustotal'] ) && $domain ) {
						$vt_result = vms_span_checker_check_virustotal( $domain );
						if ( $vt_result && isset( $vt_result['malicious'] ) && $vt_result['malicious'] > 0 ) {
							$result = array(
								'status'  => false,
								'message' => vms_span_checker_get_error_message( 'url_virustotal_flagged' ),
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
						'form_name'  => $form_name ? $form_name : __( 'Contact Form', 'vms-span-checker' ),
						'field_type' => 'textarea',
						'field_name' => $field_name ? $field_name : 'message',
						'page_title' => $page_title,
					);
					
					$spam_result = vms_span_checker_check_ai_spam( $value, $ai_context );
					if ( $spam_result && isset( $spam_result['is_spam'] ) && $spam_result['is_spam'] ) {
						$result = array(
							'status'  => false,
							'message' => vms_span_checker_get_error_message( 'spam_detected' ),
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
							'message' => vms_span_checker_get_error_message( 'username_taken' ),
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
		try {
			check_ajax_referer( 'vms_span_checker_nonce', 'nonce' );

			// Check if user is already blocked (skip for admins)
			$cfg = \VMS_Span_Checker\AI_Span_Config::get();
			$is_admin_exempt = ! empty( $cfg['block_user_exempt_admins'] ) && current_user_can( 'manage_options' );
			
			if ( ! $is_admin_exempt && ! empty( $cfg['block_user_enabled'] ) ) {
				$user_id = is_user_logged_in() ? get_current_user_id() : 0;
				$strike_count = vms_span_checker_get_strike_count( $user_id );
				$max_strikes = (int) ( $cfg['block_user_max_strikes'] ?? 5 );
				
				if ( $strike_count >= $max_strikes ) {
					wp_send_json_success( array(
						'status'  => false,
						'blocked' => true,
						'errors'  => array(
						array(
							'fieldName' => 'form',
							'message'   => vms_span_checker_get_error_message( 'user_blocked' ),
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

			// `fields` is a JSON-encoded array decoded immediately below;
			// each field is sanitized per-key inside the validation loop.
			$fields_raw = isset( $_POST['fields'] ) ? wp_unslash( $_POST['fields'] ) : '[]'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON string sanitized by decoder.
			$fields     = json_decode( $fields_raw, true );
			
			if ( ! is_array( $fields ) || empty( $fields ) ) {
				wp_send_json_success( array( 'status' => true ) );
				return;
			}

			$errors = array();
			$table  = $this->wpdb->prefix . 'vms_span_checker_form_settings';

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
							$error = __( 'Invalid email address format.', 'vms-span-checker' );
							break;
						}

						// Check if API checks are enabled - DNS and MX become mandatory prerequisites
						$needs_api_checks = ! empty( $rules['webrisk'] ) || ! empty( $rules['virustotal'] );

						// Check DNS A record (domain exists) - mandatory if API checks enabled
						$dns_valid = vms_span_checker_check_domain_dns( $domain );
						if ( $needs_api_checks && ! $dns_valid ) {
							$error = __( 'Email domain does not exist (no DNS A record found).', 'vms-span-checker' );
							break;
						}

						// Check MX record
						$mx_valid = vms_span_checker_check_mx_record( $domain );

						// If MX rule explicitly enabled OR API checks need MX as prerequisite
						if ( ( ! empty( $rules['mx'] ) || $needs_api_checks ) && ! $mx_valid ) {
							$error = vms_span_checker_get_error_message( 'email_mx_failed' );
							break;
						}

						if ( ! empty( $rules['disposable'] ) ) {
							if ( vms_span_checker_is_disposable_domain( $domain ) ) {
								$error = vms_span_checker_get_error_message( 'email_disposable' );
								break;
							}
						}

						// Run API checks only if domain is verified live (DNS + MX passed)
						if ( ! empty( $rules['webrisk'] ) ) {
							$webrisk_result = vms_span_checker_check_webrisk( $domain );
							if ( $webrisk_result && isset( $webrisk_result['threat'] ) && $webrisk_result['threat'] ) {
								$error = vms_span_checker_get_error_message( 'email_webrisk_flagged' );
								break;
							}
						}

						if ( ! empty( $rules['virustotal'] ) ) {
							$vt_result = vms_span_checker_check_virustotal( $domain );
							if ( $vt_result && isset( $vt_result['malicious'] ) && $vt_result['malicious'] > 0 ) {
								$error = vms_span_checker_get_error_message( 'email_virustotal_flagged' );
								break;
							}
						}
						break;

					case 'url':
						$domain = vms_span_checker_normalize_domain_input( $value );
						
						if ( empty( $domain ) ) {
							break; // No domain to check
						}

						// Check if API checks are enabled - DNS becomes mandatory prerequisite
						$needs_api_checks = ! empty( $rules['webrisk'] ) || ! empty( $rules['virustotal'] );
						
						// Check domain DNS - mandatory before API checks
						$domain_exists = vms_span_checker_check_domain_dns( $domain );
						if ( $needs_api_checks && ! $domain_exists ) {
							$error = vms_span_checker_get_error_message( 'url_dns_failed' );
							break;
						}
						
						// Run API checks only if domain exists
						if ( ! empty( $rules['webrisk'] ) ) {
							$webrisk_result = vms_span_checker_check_webrisk( $domain );
							if ( $webrisk_result && isset( $webrisk_result['threat'] ) && $webrisk_result['threat'] ) {
								$error = vms_span_checker_get_error_message( 'url_webrisk_flagged' );
								break;
							}
						}

						if ( ! empty( $rules['virustotal'] ) ) {
							$vt_result = vms_span_checker_check_virustotal( $domain );
							if ( $vt_result && isset( $vt_result['malicious'] ) && $vt_result['malicious'] > 0 ) {
								$error = vms_span_checker_get_error_message( 'url_virustotal_flagged' );
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
								'form_name'  => $form_name ? $form_name : __( 'Contact Form', 'vms-span-checker' ),
								'field_type' => 'textarea',
								'field_name' => $field_name ? $field_name : 'message',
								'page_title' => $page_title,
							);
							
							$spam_result = vms_span_checker_check_ai_spam( $value, $ai_context );
							if ( $spam_result && isset( $spam_result['is_spam'] ) && $spam_result['is_spam'] ) {
								$error = __( 'Your message appears to be spam.', 'vms-span-checker' );
							}
						}
						break;

					case 'username':
						if ( ! empty( $rules['check_exists'] ) ) {
							$user = get_user_by( 'login', $value );
							if ( $user ) {
								$error = __( 'This username is already taken.', 'vms-span-checker' );
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
				
				// Extract email from fields for guest strike tracking
				$guest_email = '';
				foreach ( $fields as $field ) {
					$ft = isset( $field['fieldType'] ) ? $field['fieldType'] : '';
					$fv = isset( $field['value'] ) ? $field['value'] : '';
					if ( 'email' === $ft && is_email( $fv ) ) {
						$guest_email = sanitize_email( $fv );
						break;
					}
				}
				
				$strike_result = vms_span_checker_record_strike( $reason, 'form_guard', 0, $guest_email );

				$response = array(
					'status' => false,
					'errors' => $errors,
				);

				// Add strike info to response
				if ( $strike_result['blocked'] ) {
					$response['blocked'] = true;
					$response['strike_message'] = vms_span_checker_get_error_message( 'user_blocked' );
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
		check_ajax_referer( 'vms_span_checker_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'vms-span-checker' ) ) );
		}

		$file = VMS_SPAN_CHECKER_DIR . 'includes/data/whitelist.sql';
		if ( ! is_readable( $file ) ) {
			wp_send_json_error( array( 'message' => __( 'Whitelist SQL file not found.', 'vms-span-checker' ) ) );
		}

		$sql = file_get_contents( $file );
		if ( ! is_string( $sql ) || '' === trim( $sql ) ) {
			wp_send_json_error( array( 'message' => __( 'Whitelist SQL file is empty.', 'vms-span-checker' ) ) );
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
			wp_send_json_error( array( 'message' => __( 'No domains found in whitelist SQL.', 'vms-span-checker' ) ) );
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
					__( 'Whitelist import complete: %1$d inserted, %2$d already existed.', 'vms-span-checker' ),
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
	check_ajax_referer( 'vms_span_checker_nonce', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'vms-span-checker' ) ) );
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
	check_ajax_referer( 'vms_span_checker_nonce', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'vms-span-checker' ) ) );
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
				'message' => vms_span_checker_get_error_message( 'recaptcha_required' ),
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
				'message' => vms_span_checker_get_error_message( 'recaptcha_failed' ),
			);
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) || empty( $data['success'] ) ) {
			$error_codes = isset( $data['error-codes'] ) ? implode( ', ', $data['error-codes'] ) : 'unknown';
			return array(
				'success' => false,
				'message' => vms_span_checker_get_error_message( 'recaptcha_failed' ),
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
					'message' => vms_span_checker_get_error_message( 'recaptcha_failed' ),
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

	// ajax_validate_subscribe / ajax_validate_contact handlers have been moved
	// to vms-span-checker-pro (Pro_Ajax). The free plugin no longer exposes
	// Contact Guard or Subscribe Guard validation endpoints.

	/**
	 * AJAX: Validate registration email (frontend validation).
	 * Validates email and stores a validation token for backend verification.
	 */
	public function ajax_validate_registration() {
		check_ajax_referer( 'vms_span_checker_nonce', 'nonce' );

		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		
		if ( empty( $email ) || ! is_email( $email ) ) {
			wp_send_json_success( array(
				'status'  => false,
				'message' => vms_span_checker_get_error_message( 'email_invalid_format' ),
			) );
			return;
		}

		// Check reCAPTCHA if provided
		$recaptcha_token = isset( $_POST['recaptcha_token'] ) ? sanitize_text_field( wp_unslash( $_POST['recaptcha_token'] ) ) : '';
		$ai_cfg          = \VMS_Span_Checker\AI_Span_Config::get();
		
		if ( ! empty( $ai_cfg['registration_guard_recaptcha'] ) && ! empty( $recaptcha_token ) ) {
			$recaptcha_result = $this->verify_recaptcha( $recaptcha_token );
			if ( ! $recaptcha_result['success'] ) {
				wp_send_json_success( array(
					'status'  => false,
					'message' => vms_span_checker_get_error_message( 'recaptcha_failed' ),
				) );
				return;
			}
		}

		// Use the Registration Guard validation logic
		$rejection_msg = \VMS_Span_Checker\Registration_Guard::rejection_message_for_registration_email( $email );
		
		if ( $rejection_msg !== null ) {
			wp_send_json_success( array(
				'status'  => false,
				'message' => $rejection_msg,
			) );
			return;
		}

		// Generate validation token (IP-based)
		$ip         = function_exists( 'vms_span_checker_get_user_ip' ) ? vms_span_checker_get_user_ip() : '';
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
