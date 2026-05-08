<?php
/**
 * AI Span Checker — provider choice (OpenAI, Anthropic, Gemini, DeepSeek).
 *
 * @package WP_Span_Checker
 */

use WP_Span_Checker\AI_Span_Config;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cfg = AI_Span_Config::get();

$allowed_providers = array( 'openai', 'anthropic', 'gemini', 'deepseek' );

// phpcs:disable WordPress.Security.NonceVerification.Missing
if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] ) {
	if ( ! isset( $_POST['wsc_ai_settings_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wsc_ai_settings_nonce'] ) ), 'wsc_ai_settings_save' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'wp-span-checker' ) );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to save these settings.', 'wp-span-checker' ) );
	}

	$p = isset( $_POST['provider'] ) ? sanitize_text_field( wp_unslash( $_POST['provider'] ) ) : '';
	$incoming          = array(
		'ai_enabled'         => ! empty( $_POST['ai_enabled'] ),
		'provider'           => in_array( $p, $allowed_providers, true ) ? $p : (string) ( $cfg['provider'] ?? 'openai' ),
		'openai_model'       => isset( $_POST['openai_model'] ) ? sanitize_text_field( wp_unslash( $_POST['openai_model'] ) ) : '',
		'anthropic_model'    => isset( $_POST['anthropic_model'] ) ? sanitize_text_field( wp_unslash( $_POST['anthropic_model'] ) ) : '',
		'gemini_model'       => isset( $_POST['gemini_model'] ) ? sanitize_text_field( wp_unslash( $_POST['gemini_model'] ) ) : '',
		'deepseek_model'     => isset( $_POST['deepseek_model'] ) ? sanitize_text_field( wp_unslash( $_POST['deepseek_model'] ) ) : '',
		'summary_post_types' => isset( $_POST['summary_post_types'] ) && is_array( $_POST['summary_post_types'] ) ? wp_unslash( $_POST['summary_post_types'] ) : array(),
	);

	$secret_fields = array(
		'openai_api_key',
		'anthropic_api_key',
		'gemini_api_key',
		'deepseek_api_key',
	);
	foreach ( $secret_fields as $field ) {
		$raw = isset( $_POST[ $field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) : '';
		if ( $raw === '' ) {
			$incoming[ $field ] = (string) ( $cfg[ $field ] ?? '' );
		} else {
			$incoming[ $field ] = $raw;
		}
	}

	AI_Span_Config::update( $incoming );
	$cfg = AI_Span_Config::get();
	echo '<div class="updated"><p>' . esc_html__( 'AI settings saved.', 'wp-span-checker' ) . '</p></div>';
}
// phpcs:enable WordPress.Security.NonceVerification.Missing

$post_types = wp_span_checker_summary_selectable_post_types();

$current_provider = in_array( (string) ( $cfg['provider'] ?? '' ), $allowed_providers, true )
	? (string) $cfg['provider']
	: 'openai';
?>

<div class="wrap wsc-admin">
	<?php
	wp_span_checker_admin_page_header(
		__( 'AI Span Settings', 'wp-span-checker' ),
		__( 'Choose one provider for summaries and comment moderation. Credentials for other providers are kept if you switch—only the active provider is used.', 'wp-span-checker' )
	);
	?>

	<div class="wsc-card">
		<form method="post">
			<?php wp_nonce_field( 'wsc_ai_settings_save', 'wsc_ai_settings_nonce' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable AI Span Checker', 'wp-span-checker' ); ?></th>
					<td>
						<?php
						wp_span_checker_admin_switch(
							array(
								'name'        => 'ai_enabled',
								'checked'     => ! empty( $cfg['ai_enabled'] ),
								'description' => __( 'Generate post summaries and run AI comment checks when a summary exists.', 'wp-span-checker' ),
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wsc_ai_provider"><?php esc_html_e( 'AI provider', 'wp-span-checker' ); ?></label></th>
					<td>
						<select name="provider" id="wsc_ai_provider">
							<option value="openai" <?php selected( $current_provider, 'openai' ); ?>><?php esc_html_e( 'OpenAI', 'wp-span-checker' ); ?></option>
							<option value="anthropic" <?php selected( $current_provider, 'anthropic' ); ?>><?php esc_html_e( 'Anthropic (API)', 'wp-span-checker' ); ?></option>
							<option value="gemini" <?php selected( $current_provider, 'gemini' ); ?>><?php esc_html_e( 'Google Gemini', 'wp-span-checker' ); ?></option>
							<option value="deepseek" <?php selected( $current_provider, 'deepseek' ); ?>><?php esc_html_e( 'DeepSeek', 'wp-span-checker' ); ?></option>
						</select>
					</td>
				</tr>
			</table>

			<?php
			// Helper to mask API key
			function wsc_mask_api_key( $key ) {
				if ( empty( $key ) || strlen( $key ) < 10 ) {
					return '';
				}
				$start = substr( $key, 0, 6 );
				$end   = substr( $key, -4 );
				return $start . '••••••••••••' . $end;
			}
			?>

			<div class="wsc-ai-provider-panel" data-wsc-provider="openai">
				<h2 class="title"><?php esc_html_e( 'OpenAI', 'wp-span-checker' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Uses Chat Completions with JSON object mode. Create a key in the OpenAI dashboard.', 'wp-span-checker' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="openai_api_key"><?php esc_html_e( 'API key', 'wp-span-checker' ); ?></label></th>
						<td>
							<div class="wsc-api-key-field">
								<input type="password" name="openai_api_key" id="openai_api_key" value="" class="regular-text wsc-api-key-input" autocomplete="off" placeholder="<?php echo esc_attr( wsc_mask_api_key( $cfg['openai_api_key'] ?? '' ) ?: __( 'Enter API key', 'wp-span-checker' ) ); ?>" data-has-key="<?php echo ! empty( $cfg['openai_api_key'] ) ? '1' : '0'; ?>">
								<button type="button" class="button wsc-toggle-key" data-target="openai_api_key">
									<span class="dashicons dashicons-visibility"></span>
								</button>
							</div>
							<?php if ( ! empty( $cfg['openai_api_key'] ) ) : ?>
								<p class="description" style="color: #2e7d32;"><span class="dashicons dashicons-yes-alt" style="font-size: 14px;"></span> <?php esc_html_e( 'API key configured', 'wp-span-checker' ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="openai_model"><?php esc_html_e( 'Model', 'wp-span-checker' ); ?></label></th>
						<td><input type="text" name="openai_model" id="openai_model" value="<?php echo esc_attr( (string) ( $cfg['openai_model'] ?? '' ) ); ?>" class="regular-text" placeholder="gpt-4o-mini"></td>
					</tr>
				</table>
			</div>

			<div class="wsc-ai-provider-panel" data-wsc-provider="anthropic">
				<h2 class="title"><?php esc_html_e( 'Anthropic', 'wp-span-checker' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Direct Anthropic Messages API.', 'wp-span-checker' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="anthropic_api_key"><?php esc_html_e( 'API key', 'wp-span-checker' ); ?></label></th>
						<td>
							<div class="wsc-api-key-field">
								<input type="password" name="anthropic_api_key" id="anthropic_api_key" value="" class="regular-text wsc-api-key-input" autocomplete="off" placeholder="<?php echo esc_attr( wsc_mask_api_key( $cfg['anthropic_api_key'] ?? '' ) ?: __( 'Enter API key', 'wp-span-checker' ) ); ?>" data-has-key="<?php echo ! empty( $cfg['anthropic_api_key'] ) ? '1' : '0'; ?>">
								<button type="button" class="button wsc-toggle-key" data-target="anthropic_api_key">
									<span class="dashicons dashicons-visibility"></span>
								</button>
							</div>
							<?php if ( ! empty( $cfg['anthropic_api_key'] ) ) : ?>
								<p class="description" style="color: #2e7d32;"><span class="dashicons dashicons-yes-alt" style="font-size: 14px;"></span> <?php esc_html_e( 'API key configured', 'wp-span-checker' ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="anthropic_model"><?php esc_html_e( 'Model', 'wp-span-checker' ); ?></label></th>
						<td><input type="text" name="anthropic_model" id="anthropic_model" value="<?php echo esc_attr( (string) ( $cfg['anthropic_model'] ?? '' ) ); ?>" class="regular-text" placeholder="claude-3-5-haiku-latest"></td>
					</tr>
				</table>
			</div>

			<div class="wsc-ai-provider-panel" data-wsc-provider="gemini">
				<h2 class="title"><?php esc_html_e( 'Google Gemini', 'wp-span-checker' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Uses the Generative Language API. Get your key from Google AI Studio.', 'wp-span-checker' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="gemini_api_key"><?php esc_html_e( 'API key', 'wp-span-checker' ); ?></label></th>
						<td>
							<div class="wsc-api-key-field">
								<input type="password" name="gemini_api_key" id="gemini_api_key" value="" class="regular-text wsc-api-key-input" autocomplete="off" placeholder="<?php echo esc_attr( wsc_mask_api_key( $cfg['gemini_api_key'] ?? '' ) ?: __( 'Enter API key', 'wp-span-checker' ) ); ?>" data-has-key="<?php echo ! empty( $cfg['gemini_api_key'] ) ? '1' : '0'; ?>">
								<button type="button" class="button wsc-toggle-key" data-target="gemini_api_key">
									<span class="dashicons dashicons-visibility"></span>
								</button>
							</div>
							<?php if ( ! empty( $cfg['gemini_api_key'] ) ) : ?>
								<p class="description" style="color: #2e7d32;"><span class="dashicons dashicons-yes-alt" style="font-size: 14px;"></span> <?php esc_html_e( 'API key configured', 'wp-span-checker' ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="gemini_model"><?php esc_html_e( 'Model', 'wp-span-checker' ); ?></label></th>
						<td><input type="text" name="gemini_model" id="gemini_model" value="<?php echo esc_attr( (string) ( $cfg['gemini_model'] ?? '' ) ); ?>" class="regular-text" placeholder="gemini-2.0-flash-lite"></td>
					</tr>
				</table>
			</div>

			<div class="wsc-ai-provider-panel" data-wsc-provider="deepseek">
				<h2 class="title"><?php esc_html_e( 'DeepSeek', 'wp-span-checker' ); ?></h2>
				<p class="description"><?php esc_html_e( 'OpenAI-compatible Chat Completions at api.deepseek.com.', 'wp-span-checker' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="deepseek_api_key"><?php esc_html_e( 'API key', 'wp-span-checker' ); ?></label></th>
						<td>
							<div class="wsc-api-key-field">
								<input type="password" name="deepseek_api_key" id="deepseek_api_key" value="" class="regular-text wsc-api-key-input" autocomplete="off" placeholder="<?php echo esc_attr( wsc_mask_api_key( $cfg['deepseek_api_key'] ?? '' ) ?: __( 'Enter API key', 'wp-span-checker' ) ); ?>" data-has-key="<?php echo ! empty( $cfg['deepseek_api_key'] ) ? '1' : '0'; ?>">
								<button type="button" class="button wsc-toggle-key" data-target="deepseek_api_key">
									<span class="dashicons dashicons-visibility"></span>
								</button>
							</div>
							<?php if ( ! empty( $cfg['deepseek_api_key'] ) ) : ?>
								<p class="description" style="color: #2e7d32;"><span class="dashicons dashicons-yes-alt" style="font-size: 14px;"></span> <?php esc_html_e( 'API key configured', 'wp-span-checker' ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="deepseek_model"><?php esc_html_e( 'Model', 'wp-span-checker' ); ?></label></th>
						<td><input type="text" name="deepseek_model" id="deepseek_model" value="<?php echo esc_attr( (string) ( $cfg['deepseek_model'] ?? '' ) ); ?>" class="regular-text" placeholder="deepseek-chat"></td>
					</tr>
				</table>
			</div>

			<h2 class="title"><?php esc_html_e( 'Summaries', 'wp-span-checker' ); ?></h2>
			<p class="description"><?php esc_html_e( 'When an item of a selected type is published or scheduled, the plugin requests a short summary using the active provider above. All suitable types are listed—posts, pages, WooCommerce products (if installed), and other public or admin-editable content types.', 'wp-span-checker' ); ?></p>
			<div class="wsc-post-type-grid" role="group" aria-label="<?php esc_attr_e( 'Post types for summaries', 'wp-span-checker' ); ?>">
				<?php
				$selected = $cfg['summary_post_types'] ?? array( 'post' );
				foreach ( $post_types as $pt ) {
					if ( ! ( $pt instanceof \WP_Post_Type ) ) {
						continue;
					}
					$id = 'wsc-pt-' . $pt->name;
					wp_span_checker_admin_switch(
						array(
							'name'        => 'summary_post_types[]',
							'id'          => $id,
							'value'       => $pt->name,
							'checked'     => in_array( $pt->name, $selected, true ),
							'label'       => (string) ( $pt->labels->singular_name ?? $pt->name ),
							'compact'     => true,
						)
					);
				}
				?>
			</div>

			<p class="submit">
				<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save settings', 'wp-span-checker' ); ?>">
			</p>
		</form>
		<style>
		.wsc-api-key-field {
			display: flex;
			align-items: center;
			gap: 8px;
		}
		.wsc-api-key-field .wsc-api-key-input {
			flex: 1;
			max-width: 350px;
		}
		.wsc-api-key-field .wsc-toggle-key {
			padding: 4px 8px;
			min-height: 30px;
			display: inline-flex;
			align-items: center;
			justify-content: center;
		}
		.wsc-api-key-field .wsc-toggle-key .dashicons {
			font-size: 18px;
			width: 18px;
			height: 18px;
		}
		.wsc-api-key-input::placeholder {
			color: #666;
			font-family: monospace;
			font-size: 13px;
		}
		</style>
		<script>
		( function () {
			function togglePanels() {
				var sel = document.getElementById( 'wsc_ai_provider' );
				if ( ! sel ) {
					return;
				}
				var v = sel.value;
				document.querySelectorAll( '.wsc-ai-provider-panel' ).forEach( function ( el ) {
					el.style.display = el.getAttribute( 'data-wsc-provider' ) === v ? '' : 'none';
				} );
			}
			var providerSel = document.getElementById( 'wsc_ai_provider' );
			if ( providerSel ) {
				providerSel.addEventListener( 'change', togglePanels );
			}
			togglePanels();

			// Toggle API key visibility
			document.querySelectorAll( '.wsc-toggle-key' ).forEach( function( btn ) {
				btn.addEventListener( 'click', function() {
					var targetId = btn.getAttribute( 'data-target' );
					var input = document.getElementById( targetId );
					var icon = btn.querySelector( '.dashicons' );
					
					if ( input ) {
						if ( input.type === 'password' ) {
							input.type = 'text';
							icon.classList.remove( 'dashicons-visibility' );
							icon.classList.add( 'dashicons-hidden' );
						} else {
							input.type = 'password';
							icon.classList.remove( 'dashicons-hidden' );
							icon.classList.add( 'dashicons-visibility' );
						}
					}
				} );
			} );

			// Clear placeholder when user starts typing
			document.querySelectorAll( '.wsc-api-key-input' ).forEach( function( input ) {
				input.addEventListener( 'focus', function() {
					if ( input.dataset.hasKey === '1' && input.value === '' ) {
						input.placeholder = '<?php echo esc_js( __( 'Enter new key to replace', 'wp-span-checker' ) ); ?>';
					}
				} );
				input.addEventListener( 'blur', function() {
					if ( input.dataset.hasKey === '1' && input.value === '' ) {
						// Restore masked placeholder
						var masks = {
							'openai_api_key': '<?php echo esc_js( wsc_mask_api_key( $cfg['openai_api_key'] ?? '' ) ); ?>',
							'anthropic_api_key': '<?php echo esc_js( wsc_mask_api_key( $cfg['anthropic_api_key'] ?? '' ) ); ?>',
							'gemini_api_key': '<?php echo esc_js( wsc_mask_api_key( $cfg['gemini_api_key'] ?? '' ) ); ?>',
							'deepseek_api_key': '<?php echo esc_js( wsc_mask_api_key( $cfg['deepseek_api_key'] ?? '' ) ); ?>'
						};
						input.placeholder = masks[ input.id ] || '';
					}
				} );
			} );
		} )();
		</script>
	</div>
</div>
