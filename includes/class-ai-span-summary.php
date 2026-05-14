<?php
/**
 * AI summaries for posts and WooCommerce products (comment / review moderation context).
 *
 * @package WP_Span_Checker
 */

namespace WP_Span_Checker;

use WP_Post;
use WP_Span_Checker\Services\AI_Span_Completion;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores summaries in span_checker_ai_post_summary.
 */
class AI_Span_Summary {

	/**
	 * @var int
	 */
	private static $pending_post_id = 0;

	public function __construct() {
		add_action( 'save_post', array( $this, 'on_save_post' ), 99, 3 );
		add_action( 'shutdown', array( $this, 'run_pending_summary' ), 999 );
		add_action( 'wsc_ai_generate_post_summary', array( $this, 'cron_generate' ), 10, 1 );
	}

	/**
	 * Schedule when a supported post type is published/updated.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 */
	public function on_save_post( $post_id, $post, $update ): void { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$post_id = (int) $post_id;
		if ( $post_id <= 0 || ! ( $post instanceof \WP_Post ) ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		$c = AI_Span_Config::get();
		if ( empty( $c['ai_enabled'] ) ) {
			return;
		}

		$types = $c['summary_post_types'] ?? array( 'post' );
		if ( ! in_array( $post->post_type, $types, true ) ) {
			return;
		}

		if ( 'publish' !== $post->post_status && 'future' !== $post->post_status ) {
			return;
		}

		self::$pending_post_id = $post_id;
	}

	public function run_pending_summary(): void {
		if ( self::$pending_post_id <= 0 ) {
			return;
		}
		$pid = self::$pending_post_id;
		self::$pending_post_id = 0;
		$this->generate_for_post( $pid, array() );
	}

	/**
	 * Cron callback.
	 *
	 * @param int $post_id Post ID.
	 */
	public function cron_generate( $post_id ): void {
		$this->generate_for_post( (int) $post_id, array() );
	}

	/**
	 * Build AI summary for a post or product.
	 *
	 * @param int               $post_id Post ID.
	 * @param array<string,mixed> $opts   force: skip summary_post_types gate (admin regenerate, review moderation).
	 */
	public function generate_for_post( int $post_id, array $opts = array() ): bool {
		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post || ! in_array( $post->post_status, array( 'publish', 'future' ), true ) ) {
			return false;
		}

		$c = AI_Span_Config::get();
		if ( empty( $c['ai_enabled'] ) ) {
			return false;
		}

		$force = ! empty( $opts['force'] );
		if ( ! $force ) {
			$types = $c['summary_post_types'] ?? array( 'post' );
			if ( ! in_array( $post->post_type, $types, true ) ) {
				return false;
			}
		}

		$now = current_time( 'mysql' );

		if ( 'product' === $post->post_type && function_exists( 'wc_get_product' ) ) {
			return $this->generate_product_summary( $post_id, $post, $now );
		}

		return $this->generate_generic_post_summary( $post, $now );
	}

	/**
	 * When Product Review Guard needs context and no row exists yet, generate one (if AI on).
	 *
	 * @return string|null Summary text or null if unavailable.
	 */
	public static function ensure_summary_for_product_review( int $product_id ): ?string {
		$existing = self::get_summary_text( $product_id );
		if ( null !== $existing && '' !== $existing ) {
			return $existing;
		}

		$c = AI_Span_Config::get();
		if ( empty( $c['ai_enabled'] ) || empty( $c['review_ai_auto_product_summary'] ) ) {
			return null;
		}

		$runner = new self();
		$ok     = $runner->generate_for_post( $product_id, array( 'force' => true ) );
		if ( ! $ok ) {
			return null;
		}

		return self::get_summary_text( $product_id );
	}

	/**
	 * Blog/post/page summary (original behavior).
	 */
	private function generate_generic_post_summary( WP_Post $post, string $now ): bool {
		$post_id = (int) $post->ID;

		$title   = wp_strip_all_tags( $post->post_title );
		$content = wp_strip_all_tags( $post->post_content );
		$content = wp_html_excerpt( $content, 12000, '…' );

		$sys = __(
			'You summarize content for spam-detection context. Reply with ONLY valid JSON: {"summary":"2-4 neutral sentences describing what this content is about. No markdown."}',
			'wp-span-checker'
		);
		$usr = "TITLE:\n{$title}\n\nBODY:\n{$content}";

		return $this->complete_and_store( $post_id, $sys, $usr, $now );
	}

	/**
	 * WooCommerce product summary for review moderation (richer than raw post body).
	 */
	private function generate_product_summary( int $post_id, WP_Post $post, string $now ): bool {
		$product = function_exists( 'wc_get_product' ) ? wc_get_product( $post_id ) : null;
		if ( ! $product || ! is_a( $product, '\WC_Product' ) ) {
			return $this->generate_generic_post_summary( $post, $now );
		}

		$name = wp_strip_all_tags( $product->get_name() );
		$sku  = wp_strip_all_tags( (string) $product->get_sku() );

		$short = wp_strip_all_tags( $product->get_short_description() );
		$short = wp_html_excerpt( $short, 4000, '…' );

		$long = wp_strip_all_tags( $product->get_description() );
		$long = wp_html_excerpt( $long, 12000, '…' );

		$cats = '';
		$terms = get_the_terms( $post_id, 'product_cat' );
		if ( is_array( $terms ) && array() !== $terms ) {
			$cats = implode(
				', ',
				array_map(
					static function ( $t ) {
						return $t->name;
					},
					$terms
				)
			);
		}

		$tags = '';
		$tag_terms = get_the_terms( $post_id, 'product_tag' );
		if ( is_array( $tag_terms ) && array() !== $tag_terms ) {
			$tags = implode(
				', ',
				array_map(
					static function ( $t ) {
						return $t->name;
					},
					$tag_terms
				)
			);
		}

		$attr_lines = array();
		foreach ( $product->get_attributes() as $attr ) {
			if ( ! is_object( $attr ) || ! method_exists( $attr, 'get_visible' ) || ! $attr->get_visible() ) {
				continue;
			}
			$label = method_exists( $attr, 'get_name' ) ? wc_attribute_label( $attr->get_name() ) : '';
			if ( ! is_string( $label ) ) {
				$label = '';
			}
			$options = $attr->get_options();
			$parts   = array();
			if ( is_array( $options ) ) {
				foreach ( $options as $opt ) {
					if ( is_numeric( $opt ) ) {
						$term = get_term( (int) $opt );
						if ( $term && ! is_wp_error( $term ) ) {
							$parts[] = $term->name;
						}
					} else {
						$parts[] = (string) $opt;
					}
				}
			}
			if ( '' !== $label && array() !== $parts ) {
				$attr_lines[] = $label . ': ' . implode( ', ', $parts );
			}
		}
		$attr_blob = wp_html_excerpt( implode( '; ', $attr_lines ), 3000, '…' );

		$price_note = '';
		if ( '' !== (string) $product->get_price() ) {
			$price_note = wp_strip_all_tags( wc_price( $product->get_price() ) );
		}

		$sys = __(
			'You summarize a single WooCommerce product for review-moderation context. Describe what the product is, its apparent purpose/audience, and notable claims from the listing—factually and neutrally, without inventing specs. Reply with ONLY valid JSON: {"summary":"3-6 sentences. No markdown."}',
			'wp-span-checker'
		);

		$usr  = "PRODUCT_NAME:\n{$name}\n\n";
		$usr .= 'SKU:' . ( '' !== $sku ? $sku : __( '(none)', 'wp-span-checker' ) ) . "\n\n";
		if ( '' !== $price_note ) {
			$usr .= "LIST_PRICE_HTML_STRIPPED:\n{$price_note}\n\n";
		}
		$usr .= "SHORT_DESCRIPTION:\n{$short}\n\n";
		$usr .= "LONG_DESCRIPTION:\n{$long}\n\n";
		$usr .= 'CATEGORIES:' . ( '' !== $cats ? $cats : __( '(none)', 'wp-span-checker' ) ) . "\n\n";
		$usr .= 'TAGS:' . ( '' !== $tags ? $tags : __( '(none)', 'wp-span-checker' ) ) . "\n\n";
		$usr .= 'ATTRIBUTES:' . ( '' !== $attr_blob ? $attr_blob : __( '(none)', 'wp-span-checker' ) );

		return $this->complete_and_store( $post_id, $sys, $usr, $now );
	}

	/**
	 * Call AI and persist row.
	 */
	private function complete_and_store( int $post_id, string $sys, string $usr, string $now ): bool {
		$raw = AI_Span_Completion::complete( $sys, $usr );
		if ( is_wp_error( $raw ) ) {
			$this->upsert_row(
				$post_id,
				'',
				'failed',
				$raw->get_error_message(),
				$now
			);
			return false;
		}

		$parsed  = json_decode( (string) $raw, true );
		$summary = '';
		if ( is_array( $parsed ) && ! empty( $parsed['summary'] ) ) {
			$summary = sanitize_textarea_field( (string) $parsed['summary'] );
		} else {
			$summary = sanitize_textarea_field( (string) $raw );
		}

		if ( '' === $summary ) {
			$this->upsert_row( $post_id, '', 'failed', __( 'Empty summary from AI.', 'wp-span-checker' ), $now );
			return false;
		}

		$this->upsert_row( $post_id, $summary, 'generated', '', $now );
		return true;
	}

	/**
	 * @param string $status pending|generated|failed .
	 */
	private function upsert_row( int $post_id, string $summary, string $status, string $error, string $now ): void {
		global $wpdb;
		$table = $wpdb->prefix . 'span_checker_ai_post_summary';

		$existing = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT id FROM {$table} WHERE post_id = %d", $post_id )
		);

		$row = array(
			'post_id'    => $post_id,
			'summary'    => $summary,
			'status'     => $status,
			'last_error' => $error,
			'updated_at' => $now,
		);

		if ( $existing ) {
			$wpdb->update( $table, $row, array( 'post_id' => $post_id ), array( '%d', '%s', '%s', '%s', '%s' ), array( '%d' ) );
		} else {
			$wpdb->insert( $table, $row, array( '%d', '%s', '%s', '%s', '%s' ) );
		}
	}

	/**
	 * @return string|null Summary text or null.
	 */
	public static function get_summary_text( int $post_id ): ?string {
		global $wpdb;
		$table = $wpdb->prefix . 'span_checker_ai_post_summary';
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT summary, status FROM {$table} WHERE post_id = %d",
				$post_id
			),
			ARRAY_A
		);
		if ( ! $row || 'generated' !== $row['status'] || $row['summary'] === '' ) {
			return null;
		}
		return (string) $row['summary'];
	}
}
