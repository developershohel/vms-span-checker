<?php
/* Template Name: Forgot Password */
/**
 * Forgot Password form  for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Pronations Themes
 * @subpackage Pronations Landing Theme
 * @since 2.1.0
 */

require_once( get_template_directory() . '/form/tz-form-data.php' );
global $errors;
$current_time = time();
$session      = isset( $_SESSION['forgot_password_resent_otp_session'] );
if ( $session && $session < $current_time ) {
	session_destroy();

	session_start();
	$_SESSION['session_expire'] = 'Your Session is expire';
	pn_form_redirect( 'login' );
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta <?php bloginfo( 'charset' ); ?>>
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
	<?php wp_head(); ?>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12 col-md-12 col-lg-12">
            <div id="tz-form-section" <?php post_class( 'tz-form-section' ); ?>>
                <div id="tz-forgot-password-form-section" class="align-items-center">
                    <div id="tz-form-logo-section" class="mb-2">
                        <a href="<?php bloginfo( 'url' ); ?>" title="<?php bloginfo( 'name' ); ?>">
                            <img src="<?php echo get_template_directory_uri() . '/assets/images/themezonepn_-logo.png'; ?>"
                                 class="tz-form-logo img-fluid" alt="<?php bloginfo( 'name' ); ?>">
                        </a>
                    </div>
                    <div id="tz-form-header" class="mb-4">
                        <h1 class="tz-form-title">Forgot Password</h1>
                    </div>
                    <div class="tz-form-message">
                        <ul>
							<?php
							if ( $errors->has_errors() ) {
								if ( isset( $_SESSION['session_expire'] ) ) {
									echo '<li class="tz-form-error-message">' . $_SESSION['session_expire'] . '</li>';
								}
								$errors_msg = $errors->get_error_messages();
								foreach ( $errors_msg as $msg ) {
									echo '<li class="tz-form-error-message">' . $msg . '</li>';
								}
							}
							?>
                        </ul>
                    </div>
                    <div id="tz-forgot-password-form">
                        <div id="tz-form-body">
                            <form action="<?php the_permalink() ?>" class="tz-forgot-password-form" method="post"
                                  nonce="pn_nonce"
                                  id="login">
                                <div id="tz-forgot-password" class="mb-4">
                                    <i class="user-icon fa fa-envelope"></i>
                                    <input type="email" name="pn_email" placeholder="Email" required>
                                    <i class="progress-icon fa fa-spinner"></i>
                                </div>
                                <div id="tz-submit">
                                    <input type="submit" value="Send Code" name="pn_forgot_password">
                                </div>
                            </form>
                        </div>
                    </div>
                    <div id="form-separator">
                        <hr>
                    </div>
                    <div id="tz-form-footer" class="mb-3">
                        <div id="form-redirect-link" class="d-flex align-items-center justify-content-center mb-2">
                            <span>Don't have an account? <a href="" class="pe-2">Sing Up</a> </span>
                        </div>
                        <div id="form-footer-menu">
                            <ul class="nav navbar-expand">
                                <li class="nav-item"><a class="nav-link" href="<?php echo esc_url( home_url() ); ?>">Home</a>
                                </li>
                                <li class="nav-item"><a class="nav-link" href="">About Us</a></li>
                                <li class="nav-item"><a class="nav-link" href="">Contact Us</a></li>
                                <li class="nav-item"><a class="nav-link" href="<?php echo get_privacy_policy_url(); ?>">Privacy
                                        Policy</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php wp_footer(); ?>
</body>
</html>
