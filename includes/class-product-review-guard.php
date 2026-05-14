<?php
/**
 * WooCommerce product review anti-spam (heuristics, AI, strikes) — parallel to Comment Guard.
 *
 * @package WP_Span_Checker
 */

namespace WP_Span_Checker;

use WP_Error;
use WP_Span_Checker\Services\AI_Span_Completion;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Runs before Comment Guard when enabled for WC review comments only.
 */
final class Product_Review_Guard {

	/**
	 * True when this submission is handled here so Comment Guard skips it.
	 *
	 * @param array<string, mixed> $commentdata .
	 */
	public static function should_delegate_review_to_product_guard( array $commentdata ): bool {
		$c = AI_Span_Config::get();
		if ( empty( $c['product_review_guard_enabled'] ) ) {
			return false;
		}
		return self::is_woocommerce_product_review_comment( $commentdata );
	}

	/**
	 * @param array<string, mixed> $commentdata .
	 */
	public static function is_woocommerce_product_review_comment( array $commentdata ): bool {
		if ( ! self::woocommerce_loaded() ) {
			return false;
		}
		$type = isset( $commentdata['comment_type'] ) ? (string) $commentdata['comment_type'] : '';
		if ( 'review' !== $type ) {
			return false;
		}
		$post_id = isset( $commentdata['comment_post_ID'] ) ? (int) $commentdata['comment_post_ID'] : 0;
		return $post_id > 0 && 'product' === get_post_type( $post_id );
	}

	private static function woocommerce_loaded(): bool {
		return class_exists( '\WooCommerce', false ) || function_exists( 'WC' );
	}

	public function __construct() {
		add_filter( 'preprocess_comment', array( $this, 'preprocess_comment' ), 4, 1 );
		add_filter( 'rest_pre_insert_comment', array( $this, 'rest_pre_insert_comment' ), 9, 2 );
	}

	/**
	 * @param array<string, mixed> $commentdata .
	 * @return array<string, mixed>
	 */
	public function preprocess_comment( $commentdata ) {
		if ( ! self::should_delegate_review_to_product_guard( $commentdata ) ) {
			return $commentdata;
		}

		$rating = $this->get_rating_from_request();
		$check  = $this->evaluate_review_submission( $commentdata, $rating );
		if ( is_wp_error( $check ) ) {
			Comment_Enforcement::redirect_with_notice(
				$commentdata,
				$check,
				'#reviews',
				__( 'Review blocked', 'wp-span-checker' )
			);
		}

		return $commentdata;
	}

	/**
	 * @param \WP_Comment           $prepared_comment .
	 * @param \WP_REST_Request|null $request          .
	 * @return \WP_Comment|\WP_Error
	 */
	public function rest_pre_insert_comment( $prepared_comment, $request ) {
		if ( ! ( $prepared_comment instanceof \WP_Comment ) ) {
			return $prepared_comment;
		}

		$data = array(
			'comment_post_ID'      => $prepared_comment->comment_post_ID,
			'comment_author'       => $prepared_comment->comment_author,
			'comment_author_email' => $prepared_comment->comment_author_email,
			'comment_author_url'   => isset( $prepared_comment->comment_author_url ) ? $prepared_comment->comment_author_url : '',
			'comment_content'      => $prepared_comment->comment_content,
			'comment_type'         => isset( $prepared_comment->comment_type ) ? $prepared_comment->comment_type : 'comment',
			'user_id'              => $prepared_comment->user_id,
		);

		if ( ! self::should_delegate_review_to_product_guard( $data ) ) {
			return $prepared_comment;
		}

		$rating = $this->get_rating_from_rest_request( $request );
		if ( $rating <= 0 ) {
			$rating = $this->get_rating_from_request();
		}

		$check = $this->evaluate_review_submission( $data, $rating );
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		return $prepared_comment;
	}

	/**
	 * @param mixed $request WP_REST_Request or null.
	 */
	private function get_rating_from_rest_request( $request ): int {
		if ( ! ( $request instanceof \WP_REST_Request ) ) {
			return 0;
		}
		$direct = $request->get_param( 'rating' );
		if ( null !== $direct && '' !== $direct ) {
			return min( 5, max( 0, absint( $direct ) ) );
		}
		$meta = $request->get_param( 'meta' );
		if ( is_array( $meta ) && isset( $meta['rating'] ) ) {
			return min( 5, max( 0, absint( $meta['rating'] ) ) );
		}
		return 0;
	}

	private function get_rating_from_request(): int {
		if ( isset( $_POST['rating'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return min( 5, max( 0, absint( wp_unslash( $_POST['rating'] ) ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}
		return 0;
	}

	/**
	 * Build spam pipeline config: mirror Comment Guard or use review-specific overrides.
	 *
	 * @param array<string, mixed> $c Full AI_Span_Config.
	 * @return array<string, mixed>
	 */
	private function build_spam_config( array $c ): array {
		if ( ! empty( $c['review_mirror_comment_rules'] ) ) {
			return $c;
		}

		return array_merge(
			$c,
			array(
				'comment_antispam_enabled'      => ! empty( $c['review_antispam_enabled'] ),
				'comment_strike_on_heuristic'   => ! empty( $c['review_strike_on_heuristic'] ),
				'comment_min_length'            => (int) ( $c['review_min_length'] ?? 0 ),
				'comment_max_length'          => (int) ( $c['review_max_length'] ?? 0 ),
				'comment_max_links'           => (int) ( $c['review_max_links'] ?? 0 ),
				'comment_allow_links'         => ! empty( $c['review_allow_links'] ),
				'comment_block_duplicate'     => ! empty( $c['review_block_duplicate'] ),
				'comment_rate_limit_max'      => (int) ( $c['review_rate_limit_max'] ?? 0 ),
				'comment_rate_limit_window'   => (int) ( $c['review_rate_limit_window'] ?? 15 ),
				'comment_rate_limit_scope'    => (string) ( $c['review_rate_limit_scope'] ?? 'ip_post' ),
			)
		);
	}

	/**
	 * WooCommerce-only gates (rating, verified buyer, duplicate reviewer).
	 *
	 * @param array<string, mixed> $commentdata .
	 * @return true|WP_Error
	 */
	private function run_woocommerce_gates( array $commentdata, int $rating, array $c ) {
		if ( ! empty( $c['review_block_guest'] ) ) {
			$uid = isset( $commentdata['user_id'] ) ? (int) $commentdata['user_id'] : 0;
			if ( $uid <= 0 ) {
				return new WP_Error(
					'wsc_review_guest',
					__( 'Please sign in to submit a product review.', 'wp-span-checker' )
				);
			}
		}

		if ( ! empty( $c['review_require_rating'] ) ) {
			if ( $rating < 1 || $rating > 5 ) {
				return new WP_Error(
					'wsc_review_rating',
					__( 'Please select a valid star rating for your review.', 'wp-span-checker' )
				);
			}
		}

		$post_id = isset( $commentdata['comment_post_ID'] ) ? (int) $commentdata['comment_post_ID'] : 0;
		$email   = isset( $commentdata['comment_author_email'] ) ? strtolower( trim( (string) $commentdata['comment_author_email'] ) ) : '';
		$uid     = isset( $commentdata['user_id'] ) ? (int) $commentdata['user_id'] : 0;

		if ( ! empty( $c['review_require_verified_purchase'] ) && function_exists( 'wc_customer_bought_product' ) ) {
			if ( ! wc_customer_bought_product( $email, $uid, $post_id ) ) {
				return new WP_Error(
					'wsc_review_verified',
					__( 'Only verified buyers can review this product.', 'wp-span-checker' )
				);
			}
		}

		if ( ! empty( $c['review_one_per_customer'] ) && $post_id > 0 ) {
			global $wpdb;
			if ( $uid > 0 ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$found = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT comment_ID FROM {$wpdb->comments} WHERE comment_post_ID = %d AND comment_type = %s AND user_id = %d AND comment_approved NOT IN ('spam','trash') LIMIT 1",
						$post_id,
						'review',
						$uid
					)
				);
			} elseif ( '' !== $email && is_email( $email ) ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$found = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT comment_ID FROM {$wpdb->comments} WHERE comment_post_ID = %d AND comment_type = %s AND comment_author_email = %s AND user_id = 0 AND comment_approved NOT IN ('spam','trash') LIMIT 1",
						$post_id,
						'review',
						$email
					)
				);
			} else {
				$found = null;
			}

			if ( ! empty( $found ) ) {
				return new WP_Error(
					'wsc_review_duplicate_reviewer',
					__( 'You have already reviewed this product.', 'wp-span-checker' )
				);
			}
		}

		return true;
	}

	/**
	 * Product context string for AI (name, sku, categories).
	 */
	private function get_product_context_line( int $product_id ): string {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return '';
		}
		$p = wc_get_product( $product_id );
		if ( ! $p ) {
			return '';
		}
		$parts = array( $p->get_name() );
		$sku   = $p->get_sku();
		if ( is_string( $sku ) && '' !== $sku ) {
			$parts[] = 'SKU:' . $sku;
		}
		$terms = get_the_terms( $product_id, 'product_cat' );
		if ( is_array( $terms ) && array() !== $terms ) {
			$labels = array_map(
				static function ( $t ) {
					return $t->name;
				},
				$terms
			);
			$labels = array_slice( array_filter( $labels ), 0, 5 );
			if ( array() !== $labels ) {
				$parts[] = 'Categories:' . implode( ', ', $labels );
			}
		}
		return implode( ' | ', $parts );
	}

	/**
	 * @param array<string, mixed> $commentdata .
	 * @return true|WP_Error
	 */
	private function evaluate_review_submission( array $commentdata, int $rating ) {
		$c       = AI_Span_Config::get();
		$post_id = isset( $commentdata['comment_post_ID'] ) ? (int) $commentdata['comment_post_ID'] : 0;
		if ( $post_id <= 0 ) {
			return true;
		}

		$actor = Comment_Enforcement::get_actor( $commentdata );
		$row   = Comment_Enforcement::get_row( $actor['key'] );

		if ( $row && ! empty( $row['site_banned'] ) ) {
			return new WP_Error(
				'wsc_site_banned',
				__( 'You cannot use this site anymore due to repeated abuse. Use the contact page if you need to reach the owner.', 'wp-span-checker' )
			);
		}

		$wc_gate = $this->run_woocommerce_gates( $commentdata, $rating, $c );
		if ( is_wp_error( $wc_gate ) ) {
			return $wc_gate;
		}

		$spam_cfg = $this->build_spam_config( $c );

		if ( ! empty( $spam_cfg['comment_antispam_enabled'] ) ) {
			$spam_check = Comment_Spam_Rules::evaluate( $commentdata, $spam_cfg );
			if ( is_wp_error( $spam_check ) ) {
				if ( ! empty( $spam_cfg['comment_strike_on_heuristic'] ) ) {
					Comment_Enforcement::register_strike( $actor, $spam_check->get_error_message(), $c, 'product_review' );
				}
				return $spam_check;
			}
		}

		$run_ai = ! empty( $c['review_ai_semantic_check'] ) && ! empty( $c['ai_enabled'] );
		if ( $run_ai ) {
			$content = isset( $commentdata['comment_content'] ) ? (string) $commentdata['comment_content'] : '';

			$summary = AI_Span_Summary::get_summary_text( $post_id );
			if ( $summary === null || $summary === '' ) {
				$generated = AI_Span_Summary::ensure_summary_for_product_review( $post_id );
				if ( is_string( $generated ) && '' !== $generated ) {
					$summary = $generated;
				}
			}

			if ( $summary === null || $summary === '' ) {
				$product = get_post( $post_id );
				$summary = $product instanceof \WP_Post ? wp_strip_all_tags( $product->post_title . "\n" . wp_trim_words( $product->post_excerpt ? $product->post_excerpt : $product->post_content, 80 ) ) : '';
			}

			if ( $summary !== '' ) {
				$sys = trim( (string) ( $c['review_system_prompt'] ?? '' ) );
				if ( '' === $sys ) {
					$sys = AI_Span_Config::default_review_system_prompt();
				}

				$ctx = $this->get_product_context_line( $post_id );

				$usr = 'PRODUCT_REVIEW_MODE: yes (enforce WooCommerce review policy).' . "\n\n"
					. 'STAR_RATING_SUBMITTED: ' . (string) ( $rating > 0 ? $rating : __( '(none)', 'wp-span-checker' ) ) . "\n"
					. 'PRODUCT_CONTEXT: ' . $ctx . "\n\n"
					. "PRODUCT_SUMMARY:\n" . $summary . "\n\n"
					. "REVIEW_TEXT:\n" . $content;

				$raw = AI_Span_Completion::complete( $sys, $usr );
				if ( ! is_wp_error( $raw ) ) {
					$verdict = AI_Span_Completion::parse_json_verdict( (string) $raw );
					if ( ! is_wp_error( $verdict ) && 'spam' === $verdict['status'] ) {
						Comment_Enforcement::register_strike( $actor, $verdict['message'], $c, 'product_review' );
						return new WP_Error(
							'wsc_spam',
							sprintf(
								/* translators: %s: short reason */
								__( 'Review rejected: %s', 'wp-span-checker' ),
								$verdict['message']
							)
						);
					}
				}
			}
		}

		$row = Comment_Enforcement::get_row( $actor['key'] );
		if ( $row && ! empty( $row['blocked'] ) && empty( $row['site_banned'] ) ) {
			return new WP_Error(
				'wsc_blocked',
				__( 'You are blocked from submitting reviews on this site due to repeated spam attempts.', 'wp-span-checker' )
			);
		}

		return true;
	}
}
