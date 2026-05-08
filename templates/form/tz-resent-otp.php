<?php
/* Template Name: Resent OTP Verification Code */
/**
 * Resent OTP Verification Code form  for our theme
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
$session_signup          = isset( $_SESSION['signup_session'] );
$session_forgot_password = isset( $_SESSION['forgot_password_session'] );

if ( $session_signup && $session_signup < $current_time ) {
	session_destroy();
	session_start();
	$_SESSION['session_expire'] = 'Your Session is expire';
	tz_form_redirect( 'login' );
}elseif ($session_forgot_password && $session_forgot_password < $current_time){
	session_destroy();
	session_start();
	$_SESSION['session_expire'] = 'Your Session is expire';
	tz_form_redirect( 'login' );
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
                <div id="tz-resent-otp-form-section" class="align-items-center">
                    <div id="tz-form-logo-section" class="mb-2">
                        <a href="<?php bloginfo( 'url' ); ?>" title="<?php bloginfo( 'name' ); ?>">
                            <img src="<?php echo get_template_directory_uri() . '/assets/images/themezone-logo.png'; ?>"
                                 class="tz-form-logo img-fluid" alt="<?php bloginfo( 'name' ); ?>">
                        </a>
                    </div>
                    <div id="tz-form-header" class="mb-4">
                        <h1 class="tz-form-title">Resend Code</h1>
                    </div>
                    <div class="tz-form-message">
                        <ul>
							<?php
							if ( isset( $_SESSION['session_expire'] ) ) {
								echo '<li class="tz-form-error-message">' . $_SESSION['session_expire'] . '</li>';
							}
							if ( $errors->has_errors() ) {
								$errors_msg = $errors->get_error_messages();
								foreach ( $errors_msg as $item ) {
									echo '<li class="tz-form-error-message">' . $item . '</li>';
								}
							}
							?>
                        </ul>
                    </div>
                    <div id="tz-resent-otp-form">
                        <div id="tz-form-body">
                            <form action="<?php the_permalink() ?>" class="tz-resent-otp-form" method="post"
                                  nonce="tz_nonce"
                                  id="login">
                                <div id="tz-resent-otp" class="mb-4">
                                    <i class="user-icon fa fa-envelope"></i>
                                    <input type="email" name="tz_email" placeholder="Email">
                                </div>
                                <div id="tz-submit">
                                    <input type="submit" value="Sent Code" name="tz_resent_code">
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
