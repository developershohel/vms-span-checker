<?php
/**
 * License settings page (free plugin).
 *
 * @package VMS_Span_Checker
 *
 * @var \VMS_Span_Checker\Licensing\License_Manager $manager
 * @var object|null                                 $info
 * @var bool                                        $is_active
 * @var string                                      $status
 * @var string                                      $action_url
 * @var string                                      $nonce_field
 * @var string                                      $upgrade_url
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Locals come from License_Admin::render_page().

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$existing_key   = is_object( $info ) && ! empty( $info->license_key ) ? (string) $info->license_key : '';
$license_title  = is_object( $info ) && ! empty( $info->license_title ) ? (string) $info->license_title : '';
$expire         = is_object( $info ) && ! empty( $info->expire_date ) ? (string) $info->expire_date : '';
$support_end    = is_object( $info ) && ! empty( $info->support_end ) ? (string) $info->support_end : '';
$renew_link     = is_object( $info ) && ! empty( $info->renew_link ) ? (string) $info->renew_link : '';
$domains_used   = is_object( $info ) && isset( $info->domains_used ) ? (int) $info->domains_used : 0;
$domains_limit  = is_object( $info ) && isset( $info->domains_limit ) ? (int) $info->domains_limit : 0;
$default_email  = (string) get_option( 'admin_email', '' );
?>
<div class="wrap vms-span-checker-license">
	<?php
	if ( function_exists( 'vms_span_checker_admin_page_header' ) ) {
		vms_span_checker_admin_page_header(
			__( 'License', 'vms-span-checker' ),
			__( 'Activate your VMS Span Checker Pro license to unlock advanced features.', 'vms-span-checker' )
		);
	} else {
		echo '<h1>' . esc_html__( 'License', 'vms-span-checker' ) . '</h1>';
	}
	?>

	<div class="vms-license-card" style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:24px;max-width:780px;margin-top:16px;">
		<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
			<div>
				<h2 style="margin:0 0 4px;font-size:18px;">
					<?php esc_html_e( 'License status', 'vms-span-checker' ); ?>
				</h2>
				<p style="margin:0;color:#646970;">
					<?php if ( $is_active ) : ?>
						<span class="dashicons dashicons-yes-alt" style="color:#00a32a;"></span>
						<strong><?php echo esc_html( $status ); ?></strong>
					<?php else : ?>
						<span class="dashicons dashicons-lock" style="color:#d63638;"></span>
						<strong><?php echo esc_html( $status ); ?></strong> —
						<?php esc_html_e( 'enter your license key below to unlock Pro features.', 'vms-span-checker' ); ?>
					<?php endif; ?>
				</p>
			</div>
			<?php if ( ! $is_active ) : ?>
				<a href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank" class="button button-primary">
					<?php esc_html_e( 'Get a license', 'vms-span-checker' ); ?>
				</a>
			<?php endif; ?>
		</div>

		<?php if ( $is_active && is_object( $info ) ) : ?>
			<table class="widefat striped" style="margin-top:18px;">
				<tbody>
					<?php if ( '' !== $license_title ) : ?>
						<tr>
							<th style="width:180px;"><?php esc_html_e( 'Product', 'vms-span-checker' ); ?></th>
							<td><?php echo esc_html( $license_title ); ?></td>
						</tr>
					<?php endif; ?>
					<tr>
						<th><?php esc_html_e( 'Expires', 'vms-span-checker' ); ?></th>
						<td><?php echo esc_html( '' !== $expire ? $expire : __( 'Unlimited', 'vms-span-checker' ) ); ?></td>
					</tr>
					<?php if ( '' !== $support_end ) : ?>
						<tr>
							<th><?php esc_html_e( 'Support until', 'vms-span-checker' ); ?></th>
							<td><?php echo esc_html( $support_end ); ?></td>
						</tr>
					<?php endif; ?>
					<?php if ( $domains_limit > 0 ) : ?>
						<tr>
							<th><?php esc_html_e( 'Domain slots', 'vms-span-checker' ); ?></th>
							<td>
								<?php
								echo esc_html(
									sprintf(
										/* translators: 1: used count, 2: total slots */
										__( '%1$d of %2$d used', 'vms-span-checker' ),
										$domains_used,
										$domains_limit
									)
								);
								?>
							</td>
						</tr>
					<?php endif; ?>
					<?php if ( '' !== $renew_link ) : ?>
						<tr>
							<th><?php esc_html_e( 'Renew', 'vms-span-checker' ); ?></th>
							<td>
								<a href="<?php echo esc_url( $renew_link ); ?>" target="_blank">
									<?php esc_html_e( 'Renew this license', 'vms-span-checker' ); ?>
								</a>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>

	<form method="post" action="<?php echo esc_url( $action_url ); ?>" class="vms-license-form" style="margin-top:24px;max-width:780px;">
		<?php echo $nonce_field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_nonce_field already escapes. ?>
		<input type="hidden" name="action" value="vms_span_checker_license_save" />

		<div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:24px;">
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="license_key"><?php esc_html_e( 'License key', 'vms-span-checker' ); ?></label>
						</th>
						<td>
							<input
								type="text"
								id="license_key"
								name="license_key"
								class="regular-text code"
								value="<?php echo esc_attr( $existing_key ); ?>"
								<?php echo $is_active ? 'readonly' : ''; ?>
								placeholder="ABCD-1234-WXYZ-7890"
							/>
							<p class="description">
								<?php esc_html_e( 'Paste the license key from your account at vmselements.com.', 'vms-span-checker' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="license_email"><?php esc_html_e( 'Email', 'vms-span-checker' ); ?></label>
						</th>
						<td>
							<input
								type="email"
								id="license_email"
								name="license_email"
								class="regular-text"
								value="<?php echo esc_attr( $default_email ); ?>"
								<?php echo $is_active ? 'readonly' : ''; ?>
							/>
							<p class="description">
								<?php esc_html_e( 'The email tied to your purchase. Defaults to the site admin email.', 'vms-span-checker' ); ?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>

			<p>
				<?php if ( $is_active ) : ?>
					<button type="submit" name="license_action" value="verify" class="button">
						<span class="dashicons dashicons-update" style="vertical-align:text-bottom;"></span>
						<?php esc_html_e( 'Re-verify', 'vms-span-checker' ); ?>
					</button>
					<button type="submit" name="license_action" value="deactivate" class="button button-secondary">
						<?php esc_html_e( 'Deactivate license', 'vms-span-checker' ); ?>
					</button>
				<?php else : ?>
					<button type="submit" name="license_action" value="activate" class="button button-primary button-large">
						<?php esc_html_e( 'Activate license', 'vms-span-checker' ); ?>
					</button>
				<?php endif; ?>
			</p>
		</div>
	</form>

	<p style="max-width:780px;margin-top:16px;color:#646970;font-size:12px;">
		<?php
		echo wp_kses(
			__( 'The license server is <code>license.vmselements.com</code>. Activation binds the license to this site URL. You can deactivate to free up the slot for another site.', 'vms-span-checker' ),
			array( 'code' => array() )
		);
		?>
	</p>
</div>
