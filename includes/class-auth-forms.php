<?php
/**
 * Auth Forms - Custom authentication form templates with validation.
 *
 * @package WP_Span_Checker
 */

namespace WP_Span_Checker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Auth Forms handler.
 */
class Auth_Forms {

	/**
	 * Option key for form settings.
	 */
	const OPTION_KEY = 'wsc_auth_forms_settings';

	/**
	 * Option key for SMTP settings.
	 */
	const SMTP_OPTION_KEY = 'wsc_smtp_settings';

	/**
	 * Available form templates.
	 *
	 * @var array
	 */
	private static $form_types = array(
		'login'           => 'Login Form',
		'register'        => 'Registration Form',
		'forgot_password' => 'Forgot Password Form',
		'reset_password'  => 'Reset Password Form',
		'otp_verify'      => 'OTP Verification Form',
		'activation'      => 'Account Activation',
	);

	/**
	 * Constructor - register hooks.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_shortcodes' ) );
		add_action( 'wp_ajax_wsc_generate_auth_pages', array( $this, 'ajax_generate_auth_pages' ) );
		add_action( 'wp_ajax_wsc_save_auth_form_settings', array( $this, 'ajax_save_settings' ) );
		add_action( 'wp_ajax_wsc_save_smtp_settings', array( $this, 'ajax_save_smtp_settings' ) );
		add_action( 'wp_ajax_wsc_test_smtp', array( $this, 'ajax_test_smtp' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_form_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_form_meta' ) );
		add_action( 'phpmailer_init', array( $this, 'configure_smtp' ) );
		add_filter( 'wp_mail_from', array( $this, 'set_mail_from' ) );
		add_filter( 'wp_mail_from_name', array( $this, 'set_mail_from_name' ) );

		// Auth form AJAX handlers
		add_action( 'wp_ajax_nopriv_wsc_auth_login', array( $this, 'ajax_handle_login' ) );
		add_action( 'wp_ajax_nopriv_wsc_auth_register', array( $this, 'ajax_handle_register' ) );
		add_action( 'wp_ajax_nopriv_wsc_auth_forgot_password', array( $this, 'ajax_handle_forgot_password' ) );
		add_action( 'wp_ajax_nopriv_wsc_auth_reset_password', array( $this, 'ajax_handle_reset_password' ) );
		add_action( 'wp_ajax_nopriv_wsc_auth_verify_otp', array( $this, 'ajax_handle_verify_otp' ) );
		add_action( 'wp_ajax_nopriv_wsc_auth_resend_otp', array( $this, 'ajax_handle_resend_otp' ) );
		add_action( 'wp_ajax_nopriv_wsc_auth_activate', array( $this, 'ajax_handle_activation' ) );

		// Load email templates
		require_once WP_SPAN_CHECKER_DIR . 'templates/emails/wsc-email-template-functions.php';
	}

	/**
	 * Get default settings.
	 *
	 * @return array
	 */
	public static function get_defaults() {
		return array(
			'primary_color'      => '#2563eb',
			'secondary_color'    => '#1e40af',
			'text_color'         => '#1f2937',
			'background_color'   => '#ffffff',
			'border_color'       => '#d1d5db',
			'border_hover_color' => '#9ca3af',
			'border_focus_color' => '#2563eb',
			'input_bg_color'     => '#ffffff',
			'input_focus_bg'     => '#f9fafb',
			'error_color'        => '#dc2626',
			'success_color'      => '#16a34a',
			'border_radius'      => '8',
			'form_width'         => '400',
			'show_labels'        => true,
			'show_placeholders'  => true,
			'button_style'       => 'filled',
			'login_redirect'     => '',
			'register_redirect'  => '',
			'login_page_id'      => 0,
			'register_page_id'   => 0,
			'forgot_page_id'     => 0,
			'reset_page_id'      => 0,
			// Validation settings per form
			'login_recaptcha'    => false,
			'register_recaptcha' => false,
			'register_check_dns' => true,
			'register_check_mx'  => true,
			'register_check_disposable' => true,
			'register_webrisk'   => false,
			'register_virustotal' => false,
			// Email Verification (combined OTP + Activation Link)
			'enable_email_verification' => false,
			'otp_expires_minutes'       => 10,
			'link_expires_hours'        => 24,
			'verify_page_id'            => 0,
		);
	}

	/**
	 * Get SMTP defaults.
	 *
	 * @return array
	 */
	public static function get_smtp_defaults() {
		return array(
			'enabled'     => false,
			'host'        => '',
			'port'        => 587,
			'encryption'  => 'tls',
			'auth'        => true,
			'username'    => '',
			'password'    => '',
			'from_email'  => '',
			'from_name'   => '',
		);
	}

	/**
	 * Get current settings.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$saved = get_option( self::OPTION_KEY, array() );
		return wp_parse_args( $saved, self::get_defaults() );
	}

	/**
	 * Get SMTP settings.
	 *
	 * @return array
	 */
	public static function get_smtp_settings() {
		$saved = get_option( self::SMTP_OPTION_KEY, array() );
		return wp_parse_args( $saved, self::get_smtp_defaults() );
	}

	/**
	 * Get form types.
	 *
	 * @return array
	 */
	public static function get_form_types() {
		return self::$form_types;
	}

	/**
	 * Register shortcodes.
	 */
	public function register_shortcodes() {
		add_shortcode( 'wsc_login_form', array( $this, 'render_login_form' ) );
		add_shortcode( 'wsc_register_form', array( $this, 'render_register_form' ) );
		add_shortcode( 'wsc_forgot_password_form', array( $this, 'render_forgot_password_form' ) );
		add_shortcode( 'wsc_reset_password_form', array( $this, 'render_reset_password_form' ) );
		add_shortcode( 'wsc_verify_form', array( $this, 'render_verify_form' ) );
		// Legacy shortcodes (redirect to combined verify form)
		add_shortcode( 'wsc_otp_verify_form', array( $this, 'render_verify_form' ) );
		add_shortcode( 'wsc_activation_form', array( $this, 'render_verify_form' ) );
	}

	/**
	 * Render login form.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_login_form( $atts = array() ) {
		if ( is_user_logged_in() ) {
			return '<p class="wsc-auth-message">' . esc_html__( 'You are already logged in.', 'wp-span-checker' ) . '</p>';
		}

		$settings = self::get_settings();
		$ai_cfg   = AI_Span_Config::get();

		ob_start();
		$this->output_form_styles( $settings );
		?>
		<div class="wsc-auth-form-wrap" data-form-type="login">
			<form class="wsc-auth-form wsc-auth-login" id="wsc-login-form" method="post">
				<h2 class="wsc-auth-title"><?php esc_html_e( 'Login', 'wp-span-checker' ); ?></h2>
				
				<div class="wsc-auth-message-area"></div>

				<div class="wsc-auth-field">
					<?php if ( $settings['show_labels'] ) : ?>
						<label for="wsc-login-user"><?php esc_html_e( 'Username or Email', 'wp-span-checker' ); ?></label>
					<?php endif; ?>
					<input type="text" id="wsc-login-user" name="user_login" 
						<?php echo $settings['show_placeholders'] ? 'placeholder="' . esc_attr__( 'Username or Email', 'wp-span-checker' ) . '"' : ''; ?>
						required autocomplete="username">
				</div>

				<div class="wsc-auth-field">
					<?php if ( $settings['show_labels'] ) : ?>
						<label for="wsc-login-pass"><?php esc_html_e( 'Password', 'wp-span-checker' ); ?></label>
					<?php endif; ?>
					<div class="wsc-auth-password-wrap">
						<input type="password" id="wsc-login-pass" name="user_password" 
							<?php echo $settings['show_placeholders'] ? 'placeholder="' . esc_attr__( 'Password', 'wp-span-checker' ) . '"' : ''; ?>
							required autocomplete="current-password">
						<button type="button" class="wsc-auth-toggle-pass" aria-label="<?php esc_attr_e( 'Toggle password visibility', 'wp-span-checker' ); ?>">
							<span class="dashicons dashicons-visibility"></span>
						</button>
					</div>
				</div>

				<div class="wsc-auth-field wsc-auth-remember">
					<label>
						<input type="checkbox" name="remember" value="1">
						<?php esc_html_e( 'Remember me', 'wp-span-checker' ); ?>
					</label>
				</div>

				<?php if ( ! empty( $settings['login_recaptcha'] ) && ! empty( $ai_cfg['recaptcha_site_key'] ) ) : ?>
					<div class="wsc-auth-recaptcha" id="wsc-login-recaptcha"></div>
				<?php endif; ?>

				<input type="hidden" name="action" value="wsc_auth_login">
				<?php wp_nonce_field( 'wsc_auth_login', 'wsc_auth_nonce' ); ?>

				<button type="submit" class="wsc-auth-submit">
					<span class="wsc-auth-submit-text"><?php esc_html_e( 'Login', 'wp-span-checker' ); ?></span>
					<span class="wsc-auth-spinner"></span>
				</button>

				<div class="wsc-auth-links">
					<?php if ( $settings['forgot_page_id'] ) : ?>
						<a href="<?php echo esc_url( get_permalink( $settings['forgot_page_id'] ) ); ?>"><?php esc_html_e( 'Forgot Password?', 'wp-span-checker' ); ?></a>
					<?php endif; ?>
					<?php if ( $settings['register_page_id'] && get_option( 'users_can_register' ) ) : ?>
						<a href="<?php echo esc_url( get_permalink( $settings['register_page_id'] ) ); ?>"><?php esc_html_e( 'Create an account', 'wp-span-checker' ); ?></a>
					<?php endif; ?>
				</div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render registration form.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_register_form( $atts = array() ) {
		if ( is_user_logged_in() ) {
			return '<p class="wsc-auth-message">' . esc_html__( 'You are already logged in.', 'wp-span-checker' ) . '</p>';
		}

		if ( ! get_option( 'users_can_register' ) ) {
			return '<p class="wsc-auth-message">' . esc_html__( 'Registration is currently disabled.', 'wp-span-checker' ) . '</p>';
		}

		$settings = self::get_settings();
		$ai_cfg   = AI_Span_Config::get();

		ob_start();
		$this->output_form_styles( $settings );
		?>
		<div class="wsc-auth-form-wrap" data-form-type="register">
			<form class="wsc-auth-form wsc-auth-register" id="wsc-register-form" method="post">
				<h2 class="wsc-auth-title"><?php esc_html_e( 'Create Account', 'wp-span-checker' ); ?></h2>
				
				<div class="wsc-auth-message-area"></div>

				<div class="wsc-auth-field">
					<?php if ( $settings['show_labels'] ) : ?>
						<label for="wsc-reg-user"><?php esc_html_e( 'Username', 'wp-span-checker' ); ?> <span class="required">*</span></label>
					<?php endif; ?>
					<input type="text" id="wsc-reg-user" name="user_login" 
						<?php echo $settings['show_placeholders'] ? 'placeholder="' . esc_attr__( 'Username', 'wp-span-checker' ) . '"' : ''; ?>
						required autocomplete="username">
				</div>

				<div class="wsc-auth-field">
					<?php if ( $settings['show_labels'] ) : ?>
						<label for="wsc-reg-email"><?php esc_html_e( 'Email', 'wp-span-checker' ); ?> <span class="required">*</span></label>
					<?php endif; ?>
					<input type="email" id="wsc-reg-email" name="user_email" 
						<?php echo $settings['show_placeholders'] ? 'placeholder="' . esc_attr__( 'Email Address', 'wp-span-checker' ) . '"' : ''; ?>
						required autocomplete="email">
					<span class="wsc-auth-field-status"></span>
				</div>

				<div class="wsc-auth-field">
					<?php if ( $settings['show_labels'] ) : ?>
						<label for="wsc-reg-pass"><?php esc_html_e( 'Password', 'wp-span-checker' ); ?> <span class="required">*</span></label>
					<?php endif; ?>
					<div class="wsc-auth-password-wrap">
						<input type="password" id="wsc-reg-pass" name="user_password" 
							<?php echo $settings['show_placeholders'] ? 'placeholder="' . esc_attr__( 'Password', 'wp-span-checker' ) . '"' : ''; ?>
							required autocomplete="new-password">
						<button type="button" class="wsc-auth-toggle-pass" aria-label="<?php esc_attr_e( 'Toggle password visibility', 'wp-span-checker' ); ?>">
							<span class="dashicons dashicons-visibility"></span>
						</button>
					</div>
					<div class="wsc-auth-password-strength"></div>
				</div>

				<div class="wsc-auth-field">
					<?php if ( $settings['show_labels'] ) : ?>
						<label for="wsc-reg-pass-confirm"><?php esc_html_e( 'Confirm Password', 'wp-span-checker' ); ?> <span class="required">*</span></label>
					<?php endif; ?>
					<div class="wsc-auth-password-wrap">
						<input type="password" id="wsc-reg-pass-confirm" name="user_password_confirm" 
							<?php echo $settings['show_placeholders'] ? 'placeholder="' . esc_attr__( 'Confirm Password', 'wp-span-checker' ) . '"' : ''; ?>
							required autocomplete="new-password">
					</div>
				</div>

				<?php if ( ! empty( $settings['register_recaptcha'] ) && ! empty( $ai_cfg['recaptcha_site_key'] ) ) : ?>
					<div class="wsc-auth-recaptcha" id="wsc-register-recaptcha"></div>
				<?php endif; ?>

				<!-- Validation rules info -->
				<div class="wsc-auth-validation-info">
					<?php
					$rules = array();
					if ( ! empty( $settings['register_check_dns'] ) ) {
						$rules[] = __( 'Valid domain', 'wp-span-checker' );
					}
					if ( ! empty( $settings['register_check_mx'] ) ) {
						$rules[] = __( 'Email deliverable', 'wp-span-checker' );
					}
					if ( ! empty( $settings['register_check_disposable'] ) ) {
						$rules[] = __( 'No disposable emails', 'wp-span-checker' );
					}
					if ( ! empty( $rules ) ) :
					?>
						<p class="wsc-auth-rules-note">
							<span class="dashicons dashicons-shield"></span>
							<?php echo esc_html( implode( ' • ', $rules ) ); ?>
						</p>
					<?php endif; ?>
				</div>

				<input type="hidden" name="action" value="wsc_auth_register">
				<?php wp_nonce_field( 'wsc_auth_register', 'wsc_auth_nonce' ); ?>

				<button type="submit" class="wsc-auth-submit">
					<span class="wsc-auth-submit-text"><?php esc_html_e( 'Create Account', 'wp-span-checker' ); ?></span>
					<span class="wsc-auth-spinner"></span>
				</button>

				<div class="wsc-auth-links">
					<?php if ( $settings['login_page_id'] ) : ?>
						<a href="<?php echo esc_url( get_permalink( $settings['login_page_id'] ) ); ?>"><?php esc_html_e( 'Already have an account? Login', 'wp-span-checker' ); ?></a>
					<?php endif; ?>
				</div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render forgot password form.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_forgot_password_form( $atts = array() ) {
		if ( is_user_logged_in() ) {
			return '<p class="wsc-auth-message">' . esc_html__( 'You are already logged in.', 'wp-span-checker' ) . '</p>';
		}

		$settings = self::get_settings();

		ob_start();
		$this->output_form_styles( $settings );
		?>
		<div class="wsc-auth-form-wrap" data-form-type="forgot_password">
			<form class="wsc-auth-form wsc-auth-forgot" id="wsc-forgot-form" method="post">
				<h2 class="wsc-auth-title"><?php esc_html_e( 'Reset Password', 'wp-span-checker' ); ?></h2>
				<p class="wsc-auth-subtitle"><?php esc_html_e( 'Enter your email address and we\'ll send you a link to reset your password.', 'wp-span-checker' ); ?></p>
				
				<div class="wsc-auth-message-area"></div>

				<div class="wsc-auth-field">
					<?php if ( $settings['show_labels'] ) : ?>
						<label for="wsc-forgot-email"><?php esc_html_e( 'Email Address', 'wp-span-checker' ); ?></label>
					<?php endif; ?>
					<input type="email" id="wsc-forgot-email" name="user_email" 
						<?php echo $settings['show_placeholders'] ? 'placeholder="' . esc_attr__( 'Email Address', 'wp-span-checker' ) . '"' : ''; ?>
						required autocomplete="email">
				</div>

				<input type="hidden" name="action" value="wsc_auth_forgot_password">
				<?php wp_nonce_field( 'wsc_auth_forgot_password', 'wsc_auth_nonce' ); ?>

				<button type="submit" class="wsc-auth-submit">
					<span class="wsc-auth-submit-text"><?php esc_html_e( 'Send Reset Link', 'wp-span-checker' ); ?></span>
					<span class="wsc-auth-spinner"></span>
				</button>

				<div class="wsc-auth-links">
					<?php if ( $settings['login_page_id'] ) : ?>
						<a href="<?php echo esc_url( get_permalink( $settings['login_page_id'] ) ); ?>"><?php esc_html_e( 'Back to Login', 'wp-span-checker' ); ?></a>
					<?php endif; ?>
				</div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render reset password form.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_reset_password_form( $atts = array() ) {
		if ( is_user_logged_in() ) {
			return '<p class="wsc-auth-message">' . esc_html__( 'You are already logged in.', 'wp-span-checker' ) . '</p>';
		}

		// Check for reset key and login
		$rp_key   = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
		$rp_login = isset( $_GET['login'] ) ? sanitize_user( wp_unslash( $_GET['login'] ) ) : '';

		if ( empty( $rp_key ) || empty( $rp_login ) ) {
			$settings = self::get_settings();
			ob_start();
			$this->output_form_styles( $settings );
			?>
			<div class="wsc-auth-form-wrap">
				<div class="wsc-auth-form">
					<div class="wsc-auth-message wsc-auth-message--error">
						<?php esc_html_e( 'Invalid password reset link. Please request a new one.', 'wp-span-checker' ); ?>
					</div>
					<?php if ( $settings['forgot_page_id'] ) : ?>
						<div class="wsc-auth-links">
							<a href="<?php echo esc_url( get_permalink( $settings['forgot_page_id'] ) ); ?>"><?php esc_html_e( 'Request new reset link', 'wp-span-checker' ); ?></a>
						</div>
					<?php endif; ?>
				</div>
			</div>
			<?php
			return ob_get_clean();
		}

		// Verify the key
		$user = check_password_reset_key( $rp_key, $rp_login );
		if ( is_wp_error( $user ) ) {
			$settings = self::get_settings();
			ob_start();
			$this->output_form_styles( $settings );
			?>
			<div class="wsc-auth-form-wrap">
				<div class="wsc-auth-form">
					<div class="wsc-auth-message wsc-auth-message--error">
						<?php esc_html_e( 'This password reset link has expired or is invalid. Please request a new one.', 'wp-span-checker' ); ?>
					</div>
					<?php if ( $settings['forgot_page_id'] ) : ?>
						<div class="wsc-auth-links">
							<a href="<?php echo esc_url( get_permalink( $settings['forgot_page_id'] ) ); ?>"><?php esc_html_e( 'Request new reset link', 'wp-span-checker' ); ?></a>
						</div>
					<?php endif; ?>
				</div>
			</div>
			<?php
			return ob_get_clean();
		}

		$settings = self::get_settings();

		ob_start();
		$this->output_form_styles( $settings );
		?>
		<div class="wsc-auth-form-wrap" data-form-type="reset_password">
			<form class="wsc-auth-form wsc-auth-reset" id="wsc-reset-form" method="post">
				<h2 class="wsc-auth-title"><?php esc_html_e( 'Set New Password', 'wp-span-checker' ); ?></h2>
				
				<div class="wsc-auth-message-area"></div>

				<div class="wsc-auth-field">
					<?php if ( $settings['show_labels'] ) : ?>
						<label for="wsc-reset-pass"><?php esc_html_e( 'New Password', 'wp-span-checker' ); ?></label>
					<?php endif; ?>
					<div class="wsc-auth-password-wrap">
						<input type="password" id="wsc-reset-pass" name="user_password" 
							<?php echo $settings['show_placeholders'] ? 'placeholder="' . esc_attr__( 'New Password', 'wp-span-checker' ) . '"' : ''; ?>
							required autocomplete="new-password">
						<button type="button" class="wsc-auth-toggle-pass" aria-label="<?php esc_attr_e( 'Toggle password visibility', 'wp-span-checker' ); ?>">
							<span class="dashicons dashicons-visibility"></span>
						</button>
					</div>
					<div class="wsc-auth-password-strength"></div>
				</div>

				<div class="wsc-auth-field">
					<?php if ( $settings['show_labels'] ) : ?>
						<label for="wsc-reset-pass-confirm"><?php esc_html_e( 'Confirm New Password', 'wp-span-checker' ); ?></label>
					<?php endif; ?>
					<div class="wsc-auth-password-wrap">
						<input type="password" id="wsc-reset-pass-confirm" name="user_password_confirm" 
							<?php echo $settings['show_placeholders'] ? 'placeholder="' . esc_attr__( 'Confirm Password', 'wp-span-checker' ) . '"' : ''; ?>
							required autocomplete="new-password">
					</div>
				</div>

				<input type="hidden" name="rp_key" value="<?php echo esc_attr( $rp_key ); ?>">
				<input type="hidden" name="rp_login" value="<?php echo esc_attr( $rp_login ); ?>">
				<input type="hidden" name="action" value="wsc_auth_reset_password">
				<?php wp_nonce_field( 'wsc_auth_reset_password', 'wsc_auth_nonce' ); ?>

				<button type="submit" class="wsc-auth-submit">
					<span class="wsc-auth-submit-text"><?php esc_html_e( 'Reset Password', 'wp-span-checker' ); ?></span>
					<span class="wsc-auth-spinner"></span>
				</button>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render combined verification form (handles both OTP and activation link).
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_verify_form( $atts = array() ) {
		if ( is_user_logged_in() ) {
			return '<p class="wsc-auth-message">' . esc_html__( 'You are already logged in.', 'wp-span-checker' ) . '</p>';
		}

		$settings = self::get_settings();

		// Check for activation link parameters
		$key   = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
		$login = isset( $_GET['login'] ) ? sanitize_user( wp_unslash( $_GET['login'] ) ) : '';
		$email = isset( $_GET['email'] ) ? sanitize_email( wp_unslash( $_GET['email'] ) ) : '';

		ob_start();
		$this->output_form_styles( $settings );

		// If activation link provided, process it automatically
		if ( ! empty( $key ) && ! empty( $login ) ) {
			$user = get_user_by( 'login', $login );
			if ( $user ) {
				$stored_key = get_user_meta( $user->ID, 'wsc_activation_key', true );
				$expiry     = get_user_meta( $user->ID, 'wsc_activation_expiry', true );

				if ( $stored_key === $key && time() < $expiry ) {
					// Valid activation link - activate user
					delete_user_meta( $user->ID, 'wsc_activation_key' );
					delete_user_meta( $user->ID, 'wsc_activation_expiry' );
					delete_user_meta( $user->ID, 'wsc_otp_code' );
					delete_user_meta( $user->ID, 'wsc_otp_expiry' );
					update_user_meta( $user->ID, 'wsc_account_verified', true );

					// Send welcome email
					$login_url = $settings['login_page_id'] ? get_permalink( $settings['login_page_id'] ) : wp_login_url();
					$body      = function_exists( 'wsc_email_welcome' ) ? wsc_email_welcome( $user->display_name, $login_url ) : '';
					if ( $body ) {
						wsc_send_html_email( $user->user_email, sprintf( __( '[%s] Welcome!', 'wp-span-checker' ), get_bloginfo( 'name' ) ), $body );
					}
					?>
					<div class="wsc-auth-form-wrap" data-form-type="activation_success">
						<div class="wsc-auth-form">
							<div class="wsc-auth-success-icon">
								<span class="dashicons dashicons-yes-alt"></span>
							</div>
							<h2 class="wsc-auth-title"><?php esc_html_e( 'Account Verified!', 'wp-span-checker' ); ?></h2>
							<p class="wsc-auth-subtitle"><?php esc_html_e( 'Your account has been successfully verified. You can now log in.', 'wp-span-checker' ); ?></p>
							<a href="<?php echo esc_url( $login_url ); ?>" class="wsc-auth-submit"><?php esc_html_e( 'Login Now', 'wp-span-checker' ); ?></a>
						</div>
					</div>
					<?php
					return ob_get_clean();
				} else {
					// Invalid or expired link - show form with error
					$email = $user->user_email;
					?>
					<div class="wsc-auth-form-wrap" data-form-type="verify">
						<div class="wsc-auth-form">
							<div class="wsc-auth-message wsc-auth-message--error">
								<?php esc_html_e( 'This activation link has expired. Please enter the verification code or request a new link.', 'wp-span-checker' ); ?>
							</div>
						</div>
					</div>
					<?php
				}
			}
		}

		// Show verification form (OTP input)
		if ( empty( $email ) ) {
			?>
			<div class="wsc-auth-form-wrap">
				<div class="wsc-auth-form">
					<div class="wsc-auth-message wsc-auth-message--error">
						<?php esc_html_e( 'Invalid verification request. Please check your email for the verification link.', 'wp-span-checker' ); ?>
					</div>
					<?php if ( $settings['login_page_id'] ) : ?>
						<div class="wsc-auth-links" style="margin-top: 20px;">
							<a href="<?php echo esc_url( get_permalink( $settings['login_page_id'] ) ); ?>"><?php esc_html_e( 'Back to Login', 'wp-span-checker' ); ?></a>
						</div>
					<?php endif; ?>
				</div>
			</div>
			<?php
			return ob_get_clean();
		}
		?>
		<div class="wsc-auth-form-wrap" data-form-type="verify">
			<form class="wsc-auth-form wsc-auth-verify" id="wsc-verify-form" method="post">
				<h2 class="wsc-auth-title"><?php esc_html_e( 'Verify Your Email', 'wp-span-checker' ); ?></h2>
				<p class="wsc-auth-subtitle">
					<?php printf( esc_html__( 'We sent a verification code and activation link to %s', 'wp-span-checker' ), '<strong>' . esc_html( $email ) . '</strong>' ); ?>
				</p>
				
				<div class="wsc-auth-message-area"></div>

				<div class="wsc-auth-field">
					<?php if ( $settings['show_labels'] ) : ?>
						<label><?php esc_html_e( 'Enter 6-digit verification code', 'wp-span-checker' ); ?></label>
					<?php endif; ?>
					<div class="wsc-auth-otp-inputs">
						<input type="text" class="wsc-otp-digit" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="one-time-code" autofocus>
						<input type="text" class="wsc-otp-digit" maxlength="1" pattern="[0-9]" inputmode="numeric">
						<input type="text" class="wsc-otp-digit" maxlength="1" pattern="[0-9]" inputmode="numeric">
						<input type="text" class="wsc-otp-digit" maxlength="1" pattern="[0-9]" inputmode="numeric">
						<input type="text" class="wsc-otp-digit" maxlength="1" pattern="[0-9]" inputmode="numeric">
						<input type="text" class="wsc-otp-digit" maxlength="1" pattern="[0-9]" inputmode="numeric">
					</div>
					<input type="hidden" name="otp_code" id="wsc-otp-code">
				</div>

				<input type="hidden" name="email" value="<?php echo esc_attr( $email ); ?>">
				<input type="hidden" name="action" value="wsc_auth_verify_otp">
				<?php wp_nonce_field( 'wsc_auth_verify_otp', 'wsc_auth_nonce' ); ?>

				<button type="submit" class="wsc-auth-submit">
					<span class="wsc-auth-submit-text"><?php esc_html_e( 'Verify Code', 'wp-span-checker' ); ?></span>
					<span class="wsc-auth-spinner"></span>
				</button>

				<div class="wsc-auth-links">
					<p class="wsc-auth-resend">
						<?php esc_html_e( "Didn't receive the code?", 'wp-span-checker' ); ?>
						<a href="#" id="wsc-resend-otp" data-email="<?php echo esc_attr( $email ); ?>"><?php esc_html_e( 'Resend', 'wp-span-checker' ); ?></a>
					</p>
					<p class="wsc-auth-link-hint">
						<?php esc_html_e( 'You can also click the activation link in your email.', 'wp-span-checker' ); ?>
					</p>
				</div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Legacy: Render activation page (redirects to verify form).
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_activation_form( $atts = array() ) {
		return $this->render_verify_form( $atts );
	}

	/**
	 * Legacy: Render OTP verification form (redirects to verify form).
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_otp_verify_form( $atts = array() ) {
		return $this->render_verify_form( $atts );
	}

	/**
	 * Output form CSS custom properties.
	 *
	 * @param array $settings Settings.
	 */
	private function output_form_styles( $settings ) {
		static $styles_output = false;
		if ( $styles_output ) {
			return;
		}
		$styles_output = true;
		?>
		<style>
			.wsc-auth-form-wrap {
				--wsc-primary: <?php echo esc_attr( $settings['primary_color'] ); ?>;
				--wsc-secondary: <?php echo esc_attr( $settings['secondary_color'] ); ?>;
				--wsc-text: <?php echo esc_attr( $settings['text_color'] ); ?>;
				--wsc-bg: <?php echo esc_attr( $settings['background_color'] ); ?>;
				--wsc-border: <?php echo esc_attr( $settings['border_color'] ?? '#d1d5db' ); ?>;
				--wsc-border-hover: <?php echo esc_attr( $settings['border_hover_color'] ?? '#9ca3af' ); ?>;
				--wsc-border-focus: <?php echo esc_attr( $settings['border_focus_color'] ?? '#2563eb' ); ?>;
				--wsc-input-bg: <?php echo esc_attr( $settings['input_bg_color'] ?? '#ffffff' ); ?>;
				--wsc-input-focus-bg: <?php echo esc_attr( $settings['input_focus_bg'] ?? '#f9fafb' ); ?>;
				--wsc-error: <?php echo esc_attr( $settings['error_color'] ?? '#dc2626' ); ?>;
				--wsc-success: <?php echo esc_attr( $settings['success_color'] ?? '#16a34a' ); ?>;
				--wsc-radius: <?php echo esc_attr( $settings['border_radius'] ); ?>px;
				--wsc-width: <?php echo esc_attr( $settings['form_width'] ); ?>px;
			}
		</style>
		<?php
	}

	/**
	 * Handle login AJAX.
	 */
	public function ajax_handle_login() {
		check_ajax_referer( 'wsc_auth_login', 'wsc_auth_nonce' );

		$user_login = isset( $_POST['user_login'] ) ? sanitize_text_field( wp_unslash( $_POST['user_login'] ) ) : '';
		$password   = isset( $_POST['user_password'] ) ? wp_unslash( $_POST['user_password'] ) : '';
		$remember   = isset( $_POST['remember'] ) && $_POST['remember'] === '1';

		if ( empty( $user_login ) || empty( $password ) ) {
			wp_send_json_error( array( 'message' => __( 'Please fill in all fields.', 'wp-span-checker' ) ) );
		}

		// Check reCAPTCHA if enabled
		$settings = self::get_settings();
		$ai_cfg   = AI_Span_Config::get();

		if ( ! empty( $settings['login_recaptcha'] ) && ! empty( $ai_cfg['recaptcha_site_key'] ) ) {
			$recaptcha_token = isset( $_POST['recaptcha_token'] ) ? sanitize_text_field( wp_unslash( $_POST['recaptcha_token'] ) ) : '';
			if ( empty( $recaptcha_token ) ) {
				wp_send_json_error( array( 'message' => __( 'Please complete the reCAPTCHA.', 'wp-span-checker' ) ) );
			}

			$ajax = new Ajax();
			$result = $ajax->verify_recaptcha( $recaptcha_token );
			if ( ! $result['success'] ) {
				wp_send_json_error( array( 'message' => __( 'reCAPTCHA verification failed.', 'wp-span-checker' ) ) );
			}
		}

		$user = wp_signon( array(
			'user_login'    => $user_login,
			'user_password' => $password,
			'remember'      => $remember,
		) );

		if ( is_wp_error( $user ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid username or password.', 'wp-span-checker' ) ) );
		}

		$redirect = $settings['login_redirect'];
		if ( empty( $redirect ) ) {
			$redirect = home_url();
		}

		wp_send_json_success( array(
			'message'  => __( 'Login successful! Redirecting...', 'wp-span-checker' ),
			'redirect' => $redirect,
		) );
	}

	/**
	 * Handle registration AJAX.
	 */
	public function ajax_handle_register() {
		check_ajax_referer( 'wsc_auth_register', 'wsc_auth_nonce' );

		$user_login = isset( $_POST['user_login'] ) ? sanitize_user( wp_unslash( $_POST['user_login'] ) ) : '';
		$user_email = isset( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '';
		$password   = isset( $_POST['user_password'] ) ? wp_unslash( $_POST['user_password'] ) : '';
		$password_confirm = isset( $_POST['user_password_confirm'] ) ? wp_unslash( $_POST['user_password_confirm'] ) : '';

		// Basic validation
		if ( empty( $user_login ) || empty( $user_email ) || empty( $password ) ) {
			wp_send_json_error( array( 'message' => __( 'Please fill in all required fields.', 'wp-span-checker' ) ) );
		}

		if ( $password !== $password_confirm ) {
			wp_send_json_error( array( 'message' => __( 'Passwords do not match.', 'wp-span-checker' ) ) );
		}

		if ( strlen( $password ) < 8 ) {
			wp_send_json_error( array( 'message' => __( 'Password must be at least 8 characters.', 'wp-span-checker' ) ) );
		}

		$settings = self::get_settings();
		$ai_cfg   = AI_Span_Config::get();

		// Check reCAPTCHA if enabled
		if ( ! empty( $settings['register_recaptcha'] ) && ! empty( $ai_cfg['recaptcha_site_key'] ) ) {
			$recaptcha_token = isset( $_POST['recaptcha_token'] ) ? sanitize_text_field( wp_unslash( $_POST['recaptcha_token'] ) ) : '';
			if ( empty( $recaptcha_token ) ) {
				wp_send_json_error( array( 'message' => __( 'Please complete the reCAPTCHA.', 'wp-span-checker' ) ) );
			}

			$ajax = new Ajax();
			$result = $ajax->verify_recaptcha( $recaptcha_token );
			if ( ! $result['success'] ) {
				wp_send_json_error( array( 'message' => __( 'reCAPTCHA verification failed.', 'wp-span-checker' ) ) );
			}
		}

		// Email domain validation
		$domain = substr( strrchr( $user_email, '@' ), 1 );
		$needs_api_checks = ! empty( $settings['register_webrisk'] ) || ! empty( $settings['register_virustotal'] );

		// DNS check (mandatory if API checks enabled)
		if ( ! empty( $settings['register_check_dns'] ) || $needs_api_checks ) {
			if ( ! wp_span_checker_check_domain_dns( $domain ) ) {
				wp_send_json_error( array( 'message' => __( 'Email domain does not exist.', 'wp-span-checker' ) ) );
			}
		}

		// MX check (mandatory if API checks enabled)
		if ( ! empty( $settings['register_check_mx'] ) || $needs_api_checks ) {
			if ( ! wp_span_checker_check_mx_record( $domain ) ) {
				wp_send_json_error( array( 'message' => __( 'Email domain cannot receive emails.', 'wp-span-checker' ) ) );
			}
		}

		// Disposable check
		if ( ! empty( $settings['register_check_disposable'] ) ) {
			if ( wp_span_checker_is_disposable_domain( $domain ) ) {
				wp_send_json_error( array( 'message' => __( 'Disposable email addresses are not allowed.', 'wp-span-checker' ) ) );
			}
		}

		// Web Risk check
		if ( ! empty( $settings['register_webrisk'] ) ) {
			$webrisk_result = wp_span_checker_check_webrisk( $domain );
			if ( $webrisk_result && ! empty( $webrisk_result['threat'] ) ) {
				wp_send_json_error( array( 'message' => __( 'Email domain flagged for security issues.', 'wp-span-checker' ) ) );
			}
		}

		// VirusTotal check
		if ( ! empty( $settings['register_virustotal'] ) ) {
			$vt_result = wp_span_checker_check_virustotal( $domain );
			if ( $vt_result && isset( $vt_result['malicious'] ) && $vt_result['malicious'] > 0 ) {
				wp_send_json_error( array( 'message' => __( 'Email domain flagged as potentially harmful.', 'wp-span-checker' ) ) );
			}
		}

		// Check if username exists
		if ( username_exists( $user_login ) ) {
			wp_send_json_error( array( 'message' => __( 'Username already exists.', 'wp-span-checker' ) ) );
		}

		// Check if email exists
		if ( email_exists( $user_email ) ) {
			wp_send_json_error( array( 'message' => __( 'Email address already registered.', 'wp-span-checker' ) ) );
		}

		// Check if email verification is enabled (combined OTP + activation link)
		if ( ! empty( $settings['enable_email_verification'] ) ) {
			// Generate OTP and activation key
			$otp             = self::generate_otp();
			$otp_expires     = (int) ( $settings['otp_expires_minutes'] ?? 10 );
			$activation_key  = wp_generate_password( 32, false );
			$link_expires    = (int) ( $settings['link_expires_hours'] ?? 24 );
			$link_expires_ts = time() + ( $link_expires * HOUR_IN_SECONDS );

			// Store OTP, activation key, and user data in transient
			$transient_key = 'wsc_verify_' . md5( $user_email );
			set_transient( $transient_key, array(
				'otp'            => $otp,
				'otp_expires'    => time() + ( $otp_expires * MINUTE_IN_SECONDS ),
				'activation_key' => $activation_key,
				'link_expires'   => $link_expires_ts,
				'user_data'      => array(
					'user_login' => $user_login,
					'user_email' => $user_email,
					'password'   => $password,
				),
			), max( $otp_expires * MINUTE_IN_SECONDS, $link_expires * HOUR_IN_SECONDS ) );

			// Build verification/activation URL
			$verify_page_id = $settings['verify_page_id'] ?? 0;
			$activation_url = $verify_page_id 
				? add_query_arg( array( 'key' => $activation_key, 'login' => $user_login, 'email' => $user_email ), get_permalink( $verify_page_id ) )
				: add_query_arg( array( 'key' => $activation_key, 'login' => $user_login, 'email' => $user_email ), home_url( '/verify/' ) );

			// Send combined verification email (OTP + activation link)
			if ( function_exists( 'wsc_email_verification' ) && function_exists( 'wsc_send_html_email' ) ) {
				$subject = sprintf( __( '[%s] Verify Your Email', 'wp-span-checker' ), get_bloginfo( 'name' ) );
				$body    = wsc_email_verification( $activation_url, $otp, $user_login, $user_email, $otp_expires, $link_expires );
				$sent    = wsc_send_html_email( $user_email, $subject, $body );

				if ( ! $sent ) {
					delete_transient( $transient_key );
					wp_send_json_error( array( 'message' => __( 'Failed to send verification email. Please try again.', 'wp-span-checker' ) ) );
				}
			}

			// Redirect to verification page
			$redirect = $verify_page_id 
				? add_query_arg( 'email', urlencode( $user_email ), get_permalink( $verify_page_id ) )
				: add_query_arg( 'email', urlencode( $user_email ), home_url( '/verify/' ) );

			wp_send_json_success( array(
				'message'          => __( 'Verification email sent! Check your inbox for the code or activation link.', 'wp-span-checker' ),
				'redirect'         => $redirect,
				'require_verify'   => true,
			) );
			return;
		}

		// Standard registration (no OTP or activation)
		$user_id = wp_create_user( $user_login, $password, $user_email );

		if ( is_wp_error( $user_id ) ) {
			wp_send_json_error( array( 'message' => $user_id->get_error_message() ) );
		}

		update_user_meta( $user_id, '_wsc_account_activated', true );

		// Auto login
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id );

		$redirect = $settings['register_redirect'];
		if ( empty( $redirect ) ) {
			$redirect = home_url();
		}

		wp_send_json_success( array(
			'message'  => __( 'Account created successfully! Redirecting...', 'wp-span-checker' ),
			'redirect' => $redirect,
		) );
	}

	/**
	 * Handle forgot password AJAX.
	 */
	public function ajax_handle_forgot_password() {
		check_ajax_referer( 'wsc_auth_forgot_password', 'wsc_auth_nonce' );

		$user_email = isset( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '';

		if ( empty( $user_email ) || ! is_email( $user_email ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'wp-span-checker' ) ) );
		}

		$user = get_user_by( 'email', $user_email );

		// Don't reveal if user exists
		if ( ! $user ) {
			wp_send_json_success( array( 'message' => __( 'If an account exists with that email, you will receive a password reset link.', 'wp-span-checker' ) ) );
			return;
		}

		// Generate reset key
		$key = get_password_reset_key( $user );

		if ( is_wp_error( $key ) ) {
			wp_send_json_error( array( 'message' => __( 'Unable to generate reset link. Please try again.', 'wp-span-checker' ) ) );
		}

		// Build reset URL
		$settings  = self::get_settings();
		$reset_url = $settings['reset_page_id']
			? add_query_arg( array( 'key' => $key, 'login' => rawurlencode( $user->user_login ) ), get_permalink( $settings['reset_page_id'] ) )
			: network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user->user_login ), 'login' );

		// Send email
		$subject = sprintf( __( '[%s] Password Reset', 'wp-span-checker' ), get_bloginfo( 'name' ) );
		$message = sprintf(
			__( "Hello %s,\n\nYou requested a password reset for your account.\n\nClick the link below to reset your password:\n%s\n\nIf you didn't request this, you can ignore this email.\n\nThanks,\n%s", 'wp-span-checker' ),
			$user->display_name,
			$reset_url,
			get_bloginfo( 'name' )
		);

		$sent = wp_mail( $user_email, $subject, $message );

		if ( ! $sent ) {
			wp_send_json_error( array( 'message' => __( 'Failed to send email. Please check SMTP settings.', 'wp-span-checker' ) ) );
		}

		wp_send_json_success( array( 'message' => __( 'If an account exists with that email, you will receive a password reset link.', 'wp-span-checker' ) ) );
	}

	/**
	 * Handle reset password AJAX.
	 */
	public function ajax_handle_reset_password() {
		check_ajax_referer( 'wsc_auth_reset_password', 'wsc_auth_nonce' );

		$rp_key   = isset( $_POST['rp_key'] ) ? sanitize_text_field( wp_unslash( $_POST['rp_key'] ) ) : '';
		$rp_login = isset( $_POST['rp_login'] ) ? sanitize_user( wp_unslash( $_POST['rp_login'] ) ) : '';
		$password = isset( $_POST['user_password'] ) ? wp_unslash( $_POST['user_password'] ) : '';
		$password_confirm = isset( $_POST['user_password_confirm'] ) ? wp_unslash( $_POST['user_password_confirm'] ) : '';

		if ( empty( $rp_key ) || empty( $rp_login ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid reset link.', 'wp-span-checker' ) ) );
		}

		if ( empty( $password ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a new password.', 'wp-span-checker' ) ) );
		}

		if ( $password !== $password_confirm ) {
			wp_send_json_error( array( 'message' => __( 'Passwords do not match.', 'wp-span-checker' ) ) );
		}

		if ( strlen( $password ) < 8 ) {
			wp_send_json_error( array( 'message' => __( 'Password must be at least 8 characters.', 'wp-span-checker' ) ) );
		}

		$user = check_password_reset_key( $rp_key, $rp_login );

		if ( is_wp_error( $user ) ) {
			wp_send_json_error( array( 'message' => __( 'Reset link has expired or is invalid.', 'wp-span-checker' ) ) );
		}

		reset_password( $user, $password );

		$settings = self::get_settings();
		$redirect = $settings['login_page_id'] ? get_permalink( $settings['login_page_id'] ) : wp_login_url();

		wp_send_json_success( array(
			'message'  => __( 'Password reset successfully! Redirecting to login...', 'wp-span-checker' ),
			'redirect' => $redirect,
		) );
	}

	/**
	 * AJAX: Generate auth pages.
	 */
	public function ajax_generate_auth_pages() {
		check_ajax_referer( 'wp_span_checker_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-span-checker' ) ) );
		}

		$pages = array(
			'login' => array(
				'title'     => __( 'Login', 'wp-span-checker' ),
				'shortcode' => '[wsc_login_form]',
				'option'    => 'login_page_id',
			),
			'register' => array(
				'title'     => __( 'Register', 'wp-span-checker' ),
				'shortcode' => '[wsc_register_form]',
				'option'    => 'register_page_id',
			),
			'forgot_password' => array(
				'title'     => __( 'Forgot Password', 'wp-span-checker' ),
				'shortcode' => '[wsc_forgot_password_form]',
				'option'    => 'forgot_page_id',
			),
			'reset_password' => array(
				'title'     => __( 'Reset Password', 'wp-span-checker' ),
				'shortcode' => '[wsc_reset_password_form]',
				'option'    => 'reset_page_id',
			),
			'verify' => array(
				'title'     => __( 'Verify Email', 'wp-span-checker' ),
				'shortcode' => '[wsc_verify_form]',
				'option'    => 'verify_page_id',
			),
		);

		$settings = self::get_settings();
		$created  = array();

		foreach ( $pages as $key => $page ) {
			// Skip if page already exists
			if ( ! empty( $settings[ $page['option'] ] ) && get_post( $settings[ $page['option'] ] ) ) {
				continue;
			}

			$page_id = wp_insert_post( array(
				'post_title'   => $page['title'],
				'post_content' => $page['shortcode'],
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'meta_input'   => array(
					'_wsc_auth_form_type' => $key,
				),
			) );

			if ( $page_id && ! is_wp_error( $page_id ) ) {
				$settings[ $page['option'] ] = $page_id;
				$created[] = $page['title'];
			}
		}

		update_option( self::OPTION_KEY, $settings );

		if ( empty( $created ) ) {
			wp_send_json_success( array( 'message' => __( 'All auth pages already exist.', 'wp-span-checker' ) ) );
		}

		wp_send_json_success( array(
			'message' => sprintf( __( 'Created pages: %s', 'wp-span-checker' ), implode( ', ', $created ) ),
			'settings' => $settings,
		) );
	}

	/**
	 * AJAX: Save settings.
	 */
	public function ajax_save_settings() {
		check_ajax_referer( 'wp_span_checker_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-span-checker' ) ) );
		}

		$settings = array(
			'primary_color'      => sanitize_hex_color( wp_unslash( $_POST['primary_color'] ?? '' ) ) ?: '#2563eb',
			'secondary_color'    => sanitize_hex_color( wp_unslash( $_POST['secondary_color'] ?? '' ) ) ?: '#1e40af',
			'text_color'         => sanitize_hex_color( wp_unslash( $_POST['text_color'] ?? '' ) ) ?: '#1f2937',
			'background_color'   => sanitize_hex_color( wp_unslash( $_POST['background_color'] ?? '' ) ) ?: '#ffffff',
			// Input field colors
			'border_color'       => sanitize_hex_color( wp_unslash( $_POST['border_color'] ?? '' ) ) ?: '#d1d5db',
			'border_hover_color' => sanitize_hex_color( wp_unslash( $_POST['border_hover_color'] ?? '' ) ) ?: '#9ca3af',
			'border_focus_color' => sanitize_hex_color( wp_unslash( $_POST['border_focus_color'] ?? '' ) ) ?: '#2563eb',
			'input_bg_color'     => sanitize_hex_color( wp_unslash( $_POST['input_bg_color'] ?? '' ) ) ?: '#ffffff',
			'input_focus_bg'     => sanitize_hex_color( wp_unslash( $_POST['input_focus_bg'] ?? '' ) ) ?: '#f9fafb',
			'error_color'        => sanitize_hex_color( wp_unslash( $_POST['error_color'] ?? '' ) ) ?: '#dc2626',
			'success_color'      => sanitize_hex_color( wp_unslash( $_POST['success_color'] ?? '' ) ) ?: '#16a34a',
			// Layout
			'border_radius'      => absint( $_POST['border_radius'] ?? 8 ),
			'form_width'         => absint( $_POST['form_width'] ?? 400 ),
			'show_labels'        => ! empty( $_POST['show_labels'] ),
			'show_placeholders'  => ! empty( $_POST['show_placeholders'] ),
			'button_style'       => sanitize_text_field( wp_unslash( $_POST['button_style'] ?? 'filled' ) ),
			'login_redirect'     => esc_url_raw( wp_unslash( $_POST['login_redirect'] ?? '' ) ),
			'register_redirect'  => esc_url_raw( wp_unslash( $_POST['register_redirect'] ?? '' ) ),
			'login_page_id'      => absint( $_POST['login_page_id'] ?? 0 ),
			'register_page_id'   => absint( $_POST['register_page_id'] ?? 0 ),
			'forgot_page_id'     => absint( $_POST['forgot_page_id'] ?? 0 ),
			'reset_page_id'      => absint( $_POST['reset_page_id'] ?? 0 ),
			'login_recaptcha'    => ! empty( $_POST['login_recaptcha'] ),
			'register_recaptcha' => ! empty( $_POST['register_recaptcha'] ),
			'register_check_dns' => ! empty( $_POST['register_check_dns'] ),
			'register_check_mx'  => ! empty( $_POST['register_check_mx'] ),
			'register_check_disposable' => ! empty( $_POST['register_check_disposable'] ),
			'register_webrisk'   => ! empty( $_POST['register_webrisk'] ),
			'register_virustotal' => ! empty( $_POST['register_virustotal'] ),
			// Email Verification (OTP + Activation Link combined)
			'enable_email_verification' => ! empty( $_POST['enable_otp_verification'] ),
			'otp_expires_minutes'       => absint( $_POST['otp_expires_minutes'] ?? 10 ),
			'link_expires_hours'        => absint( $_POST['link_expires_hours'] ?? 24 ),
			'verify_page_id'            => absint( $_POST['verify_page_id'] ?? 0 ),
		);

		update_option( self::OPTION_KEY, $settings );

		wp_send_json_success( array( 'message' => __( 'Settings saved.', 'wp-span-checker' ) ) );
	}

	/**
	 * AJAX: Save SMTP settings.
	 */
	public function ajax_save_smtp_settings() {
		check_ajax_referer( 'wp_span_checker_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-span-checker' ) ) );
		}

		$settings = array(
			'enabled'    => ! empty( $_POST['smtp_enabled'] ),
			'host'       => sanitize_text_field( wp_unslash( $_POST['smtp_host'] ?? '' ) ),
			'port'       => absint( $_POST['smtp_port'] ?? 587 ),
			'encryption' => sanitize_text_field( wp_unslash( $_POST['smtp_encryption'] ?? 'tls' ) ),
			'auth'       => ! empty( $_POST['smtp_auth'] ),
			'username'   => sanitize_text_field( wp_unslash( $_POST['smtp_username'] ?? '' ) ),
			'password'   => wp_unslash( $_POST['smtp_password'] ?? '' ),
			'from_email' => sanitize_email( wp_unslash( $_POST['smtp_from_email'] ?? '' ) ),
			'from_name'  => sanitize_text_field( wp_unslash( $_POST['smtp_from_name'] ?? '' ) ),
		);

		update_option( self::SMTP_OPTION_KEY, $settings );

		wp_send_json_success( array( 'message' => __( 'SMTP settings saved.', 'wp-span-checker' ) ) );
	}

	/**
	 * AJAX: Test SMTP.
	 */
	public function ajax_test_smtp() {
		check_ajax_referer( 'wp_span_checker_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-span-checker' ) ) );
		}

		$to = isset( $_POST['test_email'] ) ? sanitize_email( wp_unslash( $_POST['test_email'] ) ) : '';

		if ( empty( $to ) || ! is_email( $to ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'wp-span-checker' ) ) );
		}

		$subject = sprintf( __( '[%s] SMTP Test Email', 'wp-span-checker' ), get_bloginfo( 'name' ) );
		$message = __( 'This is a test email to verify your SMTP configuration is working correctly.', 'wp-span-checker' );

		$sent = wp_mail( $to, $subject, $message );

		if ( $sent ) {
			wp_send_json_success( array( 'message' => __( 'Test email sent successfully!', 'wp-span-checker' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to send test email. Check your SMTP settings.', 'wp-span-checker' ) ) );
		}
	}

	/**
	 * Configure PHPMailer SMTP.
	 *
	 * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer PHPMailer instance.
	 */
	public function configure_smtp( $phpmailer ) {
		$settings = self::get_smtp_settings();

		if ( empty( $settings['enabled'] ) || empty( $settings['host'] ) ) {
			return;
		}

		$phpmailer->isSMTP();
		$phpmailer->Host       = $settings['host'];
		$phpmailer->Port       = $settings['port'];
		$phpmailer->SMTPSecure = $settings['encryption'];
		$phpmailer->SMTPAuth   = $settings['auth'];

		if ( $settings['auth'] ) {
			$phpmailer->Username = $settings['username'];
			$phpmailer->Password = $settings['password'];
		}
	}

	/**
	 * Set mail from address.
	 *
	 * @param string $from From address.
	 * @return string
	 */
	public function set_mail_from( $from ) {
		$settings = self::get_smtp_settings();

		if ( ! empty( $settings['enabled'] ) && ! empty( $settings['from_email'] ) ) {
			return $settings['from_email'];
		}

		return $from;
	}

	/**
	 * Set mail from name.
	 *
	 * @param string $name From name.
	 * @return string
	 */
	public function set_mail_from_name( $name ) {
		$settings = self::get_smtp_settings();

		if ( ! empty( $settings['enabled'] ) && ! empty( $settings['from_name'] ) ) {
			return $settings['from_name'];
		}

		return $name;
	}

	/**
	 * Add meta box for form selection.
	 */
	public function add_form_meta_box() {
		add_meta_box(
			'wsc_auth_form_meta',
			__( 'WP Span Checker Auth Form', 'wp-span-checker' ),
			array( $this, 'render_form_meta_box' ),
			'page',
			'side',
			'default'
		);
	}

	/**
	 * Render form meta box.
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function render_form_meta_box( $post ) {
		$current = get_post_meta( $post->ID, '_wsc_auth_form_type', true );
		wp_nonce_field( 'wsc_auth_form_meta', 'wsc_auth_form_nonce' );
		?>
		<p>
			<label for="wsc_auth_form_type"><?php esc_html_e( 'Select Auth Form', 'wp-span-checker' ); ?></label>
			<select name="wsc_auth_form_type" id="wsc_auth_form_type" class="widefat">
				<option value=""><?php esc_html_e( '— None —', 'wp-span-checker' ); ?></option>
				<?php foreach ( self::$form_types as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current, $key ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>
		<p class="description">
			<?php esc_html_e( 'If selected, the page content will display the chosen auth form with built-in validation.', 'wp-span-checker' ); ?>
		</p>
		<p class="description" style="margin-top: 10px;">
			<strong><?php esc_html_e( 'Shortcodes:', 'wp-span-checker' ); ?></strong><br>
			<code>[wsc_login_form]</code><br>
			<code>[wsc_register_form]</code><br>
			<code>[wsc_forgot_password_form]</code><br>
			<code>[wsc_reset_password_form]</code><br>
			<code>[wsc_otp_verify_form]</code><br>
			<code>[wsc_activation_form]</code>
		</p>
		<?php
	}

	/**
	 * Save form meta.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_form_meta( $post_id ) {
		if ( ! isset( $_POST['wsc_auth_form_nonce'] ) || ! wp_verify_nonce( $_POST['wsc_auth_form_nonce'], 'wsc_auth_form_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$form_type = isset( $_POST['wsc_auth_form_type'] ) ? sanitize_text_field( wp_unslash( $_POST['wsc_auth_form_type'] ) ) : '';

		if ( empty( $form_type ) ) {
			delete_post_meta( $post_id, '_wsc_auth_form_type' );
		} else {
			update_post_meta( $post_id, '_wsc_auth_form_type', $form_type );
		}
	}

	/**
	 * Generate OTP code.
	 *
	 * @param int $length OTP length.
	 * @return string
	 */
	public static function generate_otp( $length = 6 ) {
		$otp = '';
		for ( $i = 0; $i < $length; $i++ ) {
			$otp .= wp_rand( 0, 9 );
		}
		return $otp;
	}

	/**
	 * Send OTP email.
	 *
	 * @param string $email User email.
	 * @param string $otp   OTP code.
	 * @param string $name  User name.
	 * @return bool
	 */
	public static function send_otp_email( $email, $otp, $name = '' ) {
		if ( ! function_exists( 'wsc_email_otp_verification' ) || ! function_exists( 'wsc_send_html_email' ) ) {
			return false;
		}

		$settings = self::get_settings();
		$expires  = (int) ( $settings['otp_expires_minutes'] ?? 10 );
		$subject  = sprintf( __( '[%s] Your Verification Code', 'wp-span-checker' ), get_bloginfo( 'name' ) );
		$body     = wsc_email_otp_verification( $otp, $name, $expires );

		return wsc_send_html_email( $email, $subject, $body );
	}

	/**
	 * AJAX: Verify OTP.
	 */
	public function ajax_handle_verify_otp() {
		check_ajax_referer( 'wsc_auth_verify_otp', 'wsc_auth_nonce' );

		$email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$otp_code = isset( $_POST['otp_code'] ) ? sanitize_text_field( wp_unslash( $_POST['otp_code'] ) ) : '';

		if ( empty( $email ) || empty( $otp_code ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'wp-span-checker' ) ) );
		}

		// Try new combined transient first, then legacy
		$transient_key = 'wsc_verify_' . md5( $email );
		$stored_data   = get_transient( $transient_key );

		// Fallback to legacy key
		if ( ! $stored_data ) {
			$transient_key = 'wsc_otp_' . md5( $email );
			$stored_data   = get_transient( $transient_key );
		}

		if ( ! $stored_data || ! is_array( $stored_data ) ) {
			wp_send_json_error( array( 'message' => __( 'Verification code expired. Please request a new one.', 'wp-span-checker' ) ) );
		}

		// Check if OTP has expired
		if ( isset( $stored_data['otp_expires'] ) && time() > $stored_data['otp_expires'] ) {
			wp_send_json_error( array( 'message' => __( 'Verification code expired. Please request a new one or use the activation link.', 'wp-span-checker' ) ) );
		}

		if ( $stored_data['otp'] !== $otp_code ) {
			wp_send_json_error( array( 'message' => __( 'Invalid verification code.', 'wp-span-checker' ) ) );
		}

		// OTP verified - complete registration
		delete_transient( $transient_key );

		$user_data = $stored_data['user_data'] ?? array();

		if ( empty( $user_data ) ) {
			wp_send_json_error( array( 'message' => __( 'Registration data not found. Please register again.', 'wp-span-checker' ) ) );
		}

		// Create user
		$user_id = wp_create_user( $user_data['user_login'], $user_data['password'], $email );

		if ( is_wp_error( $user_id ) ) {
			wp_send_json_error( array( 'message' => $user_id->get_error_message() ) );
		}

		// Mark as verified
		update_user_meta( $user_id, 'wsc_email_verified', true );
		update_user_meta( $user_id, 'wsc_account_verified', true );

		// Send welcome email
		$settings  = self::get_settings();
		$login_url = $settings['login_page_id'] ? get_permalink( $settings['login_page_id'] ) : wp_login_url();
		
		if ( function_exists( 'wsc_email_welcome' ) && function_exists( 'wsc_send_html_email' ) ) {
			$body = wsc_email_welcome( $user_data['user_login'], $login_url );
			wsc_send_html_email( $email, sprintf( __( '[%s] Welcome!', 'wp-span-checker' ), get_bloginfo( 'name' ) ), $body );
		}

		// Auto login
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id );

		$redirect = $settings['register_redirect'] ?: home_url();

		wp_send_json_success( array(
			'message'  => __( 'Email verified! Redirecting...', 'wp-span-checker' ),
			'redirect' => $redirect,
		) );
	}

	/**
	 * AJAX: Resend OTP.
	 */
	public function ajax_handle_resend_otp() {
		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

		if ( empty( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'wp-span-checker' ) ) );
		}

		// Try new combined transient first, then legacy
		$transient_key = 'wsc_verify_' . md5( $email );
		$stored_data   = get_transient( $transient_key );

		// Fallback to legacy key
		if ( ! $stored_data ) {
			$transient_key = 'wsc_otp_' . md5( $email );
			$stored_data   = get_transient( $transient_key );
		}

		if ( ! $stored_data || ! isset( $stored_data['user_data'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Session expired. Please register again.', 'wp-span-checker' ) ) );
		}

		$settings    = self::get_settings();
		$otp_expires = (int) ( $settings['otp_expires_minutes'] ?? 10 );

		// Generate new OTP
		$new_otp                   = self::generate_otp();
		$stored_data['otp']        = $new_otp;
		$stored_data['otp_expires'] = time() + ( $otp_expires * MINUTE_IN_SECONDS );

		// Calculate remaining transient time
		$link_expires = isset( $stored_data['link_expires'] ) ? $stored_data['link_expires'] - time() : $otp_expires * MINUTE_IN_SECONDS;
		$transient_ttl = max( $otp_expires * MINUTE_IN_SECONDS, $link_expires );

		set_transient( $transient_key, $stored_data, $transient_ttl );

		// Send email
		$name = $stored_data['user_data']['user_login'] ?? '';
		$sent = self::send_otp_email( $email, $new_otp, $name );

		if ( ! $sent ) {
			wp_send_json_error( array( 'message' => __( 'Failed to send email. Please try again.', 'wp-span-checker' ) ) );
		}

		wp_send_json_success( array( 'message' => __( 'A new verification code has been sent.', 'wp-span-checker' ) ) );
	}

	/**
	 * AJAX: Handle activation.
	 */
	public function ajax_handle_activation() {
		$key   = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
		$login = isset( $_POST['login'] ) ? sanitize_user( wp_unslash( $_POST['login'] ) ) : '';

		if ( empty( $key ) || empty( $login ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid activation link.', 'wp-span-checker' ) ) );
		}

		$user = get_user_by( 'login', $login );
		if ( ! $user ) {
			wp_send_json_error( array( 'message' => __( 'User not found.', 'wp-span-checker' ) ) );
		}

		$stored_key = get_user_meta( $user->ID, '_wsc_activation_key', true );
		$expires    = get_user_meta( $user->ID, '_wsc_activation_expires', true );

		if ( $stored_key !== $key ) {
			wp_send_json_error( array( 'message' => __( 'Invalid activation key.', 'wp-span-checker' ) ) );
		}

		if ( $expires && time() > $expires ) {
			wp_send_json_error( array( 'message' => __( 'Activation link has expired.', 'wp-span-checker' ) ) );
		}

		// Activate
		delete_user_meta( $user->ID, '_wsc_activation_key' );
		delete_user_meta( $user->ID, '_wsc_activation_expires' );
		update_user_meta( $user->ID, '_wsc_account_activated', true );

		$settings  = self::get_settings();
		$login_url = $settings['login_page_id'] ? get_permalink( $settings['login_page_id'] ) : wp_login_url();

		wp_send_json_success( array(
			'message'  => __( 'Account activated! You can now log in.', 'wp-span-checker' ),
			'redirect' => $login_url,
		) );
	}
}
