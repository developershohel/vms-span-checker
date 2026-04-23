<?php
/**
 * AI Span Checker — provider choice (OpenAI, Anthropic, Gemini, DeepSeek, AWS Bedrock).
 *
 * @package WP_Span_Checker
 */

use WP_Span_Checker\AI_Span_Config;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cfg = AI_Span_Config::get();

$bedrock_presets = array(
	'Anthropic Claude 3 Haiku'    => 'anthropic.claude-3-haiku-20240307-v1:0',
	'Anthropic Claude 3.5 Sonnet' => 'anthropic.claude-3-5-sonnet-20240620-v1:0',
	'Meta Llama 3 8B Instruct'    => 'meta.llama3-8b-instruct-v1:0',
	'Meta Llama 3 70B Instruct'   => 'meta.llama3-70b-instruct-v1:0',
	'Mistral 7B Instruct'         => 'mistral.mistral-7b-instruct-v0:2',
	'Mistral Mixtral 8x7B'        => 'mistral.mixtral-8x7b-instruct-v0:1',
	'Amazon Titan Text Express'   => 'amazon.titan-text-express-v1',
);

$allowed_providers = array( 'openai', 'anthropic', 'gemini', 'deepseek', 'bedrock' );

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
		'bedrock_region'     => isset( $_POST['bedrock_region'] ) ? sanitize_text_field( wp_unslash( $_POST['bedrock_region'] ) ) : '',
		'bedrock_model'      => isset( $_POST['bedrock_model'] ) ? sanitize_text_field( wp_unslash( $_POST['bedrock_model'] ) ) : '',
		'summary_post_types' => isset( $_POST['summary_post_types'] ) && is_array( $_POST['summary_post_types'] ) ? wp_unslash( $_POST['summary_post_types'] ) : array(),
	);

	$secret_fields = array(
		'openai_api_key',
		'anthropic_api_key',
		'gemini_api_key',
		'deepseek_api_key',
		'bedrock_access_key',
		'bedrock_secret_key',
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
$current_bedrock_model = (string) ( $cfg['bedrock_model'] ?? '' );
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
							<option value="bedrock" <?php selected( $current_provider, 'bedrock' ); ?>><?php esc_html_e( 'Amazon Bedrock', 'wp-span-checker' ); ?></option>
						</select>
					</td>
				</tr>
			</table>

			<div class="wsc-ai-provider-panel" data-wsc-provider="openai">
				<h2 class="title"><?php esc_html_e( 'OpenAI', 'wp-span-checker' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Uses Chat Completions with JSON object mode. Create a key in the OpenAI dashboard.', 'wp-span-checker' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="openai_api_key"><?php esc_html_e( 'API key', 'wp-span-checker' ); ?></label></th>
						<td>
							<input type="password" name="openai_api_key" id="openai_api_key" value="" class="regular-text" autocomplete="off" placeholder="<?php esc_attr_e( 'Leave blank to keep current', 'wp-span-checker' ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="openai_model"><?php esc_html_e( 'Model', 'wp-span-checker' ); ?></label></th>
						<td><input type="text" name="openai_model" id="openai_model" value="<?php echo esc_attr( (string) ( $cfg['openai_model'] ?? '' ) ); ?>" class="regular-text"></td>
					</tr>
				</table>
			</div>

			<div class="wsc-ai-provider-panel" data-wsc-provider="anthropic">
				<h2 class="title"><?php esc_html_e( 'Anthropic', 'wp-span-checker' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Direct Anthropic Messages API (not Bedrock).', 'wp-span-checker' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="anthropic_api_key"><?php esc_html_e( 'API key', 'wp-span-checker' ); ?></label></th>
						<td>
							<input type="password" name="anthropic_api_key" id="anthropic_api_key" value="" class="regular-text" autocomplete="off" placeholder="<?php esc_attr_e( 'Leave blank to keep current', 'wp-span-checker' ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="anthropic_model"><?php esc_html_e( 'Model', 'wp-span-checker' ); ?></label></th>
						<td><input type="text" name="anthropic_model" id="anthropic_model" value="<?php echo esc_attr( (string) ( $cfg['anthropic_model'] ?? '' ) ); ?>" class="regular-text"></td>
					</tr>
				</table>
			</div>

			<div class="wsc-ai-provider-panel" data-wsc-provider="gemini">
				<h2 class="title"><?php esc_html_e( 'Google Gemini', 'wp-span-checker' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Uses the Generative Language API. Put your API key below.', 'wp-span-checker' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="gemini_api_key"><?php esc_html_e( 'API key', 'wp-span-checker' ); ?></label></th>
						<td>
							<input type="password" name="gemini_api_key" id="gemini_api_key" value="" class="regular-text" autocomplete="off" placeholder="<?php esc_attr_e( 'Leave blank to keep current', 'wp-span-checker' ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="gemini_model"><?php esc_html_e( 'Model', 'wp-span-checker' ); ?></label></th>
						<td><input type="text" name="gemini_model" id="gemini_model" value="<?php echo esc_attr( (string) ( $cfg['gemini_model'] ?? '' ) ); ?>" class="regular-text"></td>
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
							<input type="password" name="deepseek_api_key" id="deepseek_api_key" value="" class="regular-text" autocomplete="off" placeholder="<?php esc_attr_e( 'Leave blank to keep current', 'wp-span-checker' ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="deepseek_model"><?php esc_html_e( 'Model', 'wp-span-checker' ); ?></label></th>
						<td><input type="text" name="deepseek_model" id="deepseek_model" value="<?php echo esc_attr( (string) ( $cfg['deepseek_model'] ?? '' ) ); ?>" class="regular-text"></td>
					</tr>
				</table>
			</div>

			<div class="wsc-ai-provider-panel" data-wsc-provider="bedrock">
				<h2 class="title"><?php esc_html_e( 'Amazon Bedrock', 'wp-span-checker' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'IAM user with bedrock:InvokeModel. The plugin adapts the request body per model family (Anthropic Messages, Meta Llama, Mistral, Titan).', 'wp-span-checker' ); ?>
				</p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="bedrock_access_key"><?php esc_html_e( 'Access key ID', 'wp-span-checker' ); ?></label></th>
						<td>
							<input type="password" name="bedrock_access_key" id="bedrock_access_key" value="" class="regular-text" autocomplete="off" placeholder="<?php esc_attr_e( 'Leave blank to keep current', 'wp-span-checker' ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="bedrock_secret_key"><?php esc_html_e( 'Secret access key', 'wp-span-checker' ); ?></label></th>
						<td>
							<input type="password" name="bedrock_secret_key" id="bedrock_secret_key" value="" class="regular-text" autocomplete="off" placeholder="<?php esc_attr_e( 'Leave blank to keep current', 'wp-span-checker' ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="bedrock_region"><?php esc_html_e( 'Region', 'wp-span-checker' ); ?></label></th>
						<td><input type="text" name="bedrock_region" id="bedrock_region" value="<?php echo esc_attr( (string) ( $cfg['bedrock_region'] ?? 'us-east-1' ) ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th scope="row"><label for="bedrock_model_preset"><?php esc_html_e( 'Model preset', 'wp-span-checker' ); ?></label></th>
						<td>
							<select id="bedrock_model_preset">
								<option value=""><?php esc_html_e( '— Choose a common model —', 'wp-span-checker' ); ?></option>
								<?php foreach ( $bedrock_presets as $label => $id ) : ?>
									<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $current_bedrock_model, $id ); ?>><?php echo esc_html( $label . ' — ' . $id ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Selecting a preset fills the model ID field. You can paste any Bedrock model ID from the AWS console.', 'wp-span-checker' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="bedrock_model"><?php esc_html_e( 'Model ID', 'wp-span-checker' ); ?></label></th>
						<td>
							<input type="text" name="bedrock_model" id="bedrock_model" value="<?php echo esc_attr( $current_bedrock_model ); ?>" class="large-text">
						</td>
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

			var preset = document.getElementById( 'bedrock_model_preset' );
			var model = document.getElementById( 'bedrock_model' );
			if ( preset && model ) {
				preset.addEventListener( 'change', function () {
					if ( preset.value ) {
						model.value = preset.value;
					}
				} );
			}
		} )();
		</script>
	</div>
</div>
