<?php
/* Template Name: Signup Form */
/**
 * Signup form  for our theme
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
session_destroy();
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
                <div id="tz-signup-form-section" class="align-items-center">
                    <div id="tz-form-logo-section" class="mb-4">
                        <a href="<?php bloginfo( 'url' ); ?>" title="<?php bloginfo( 'name' ); ?>">
                            <img src="<?php echo get_template_directory_uri() . '/assets/images/themezone-logo.png'; ?>"
                                 class="tz-form-logo img-fluid" alt="<?php bloginfo( 'name' ); ?>">
                        </a>
                    </div>
                    <div id="tz-form-header" class="mb-4">
                        <h1 class="tz-form-title">Sign UpTo <a href="<?php bloginfo( 'url' ); ?>"
                                                               title="<?php bloginfo( 'name' ); ?>"> <?php bloginfo( 'name' ); ?> </a>
                        </h1>
                    </div>
                    <div class="tz-form-message">
                        <ul>
							<?php
							if ( $errors->has_errors() ) {
								$errors_msg = $errors->get_error_messages();
								foreach ( $errors_msg as $msg ) {
									echo '<li class="tz-form-error-message">' . $msg . '</li>';
								}
							}
							?>
                        </ul>
                    </div>
                    <div id="tz-social-form-section">
                        <div id="tz-google-login" class="mb-4 tz-social-form-button">
                            <img src="<?php echo get_template_directory_uri() . '/assets/images/icons/google.png' ?>"
                                 alt="Pronations Google Login"> <span
                                    class="google-login-text">Continue with Google</span>
                        </div>
                        <div id="tz-facebook-login" class="tz-social-form-button">
                            <img src="<?php echo get_template_directory_uri() . '/assets/images/icons/facebook.png' ?>"
                                 alt="Pronations Google Login"> <span
                                    class="facebook-login-text">Continue with Facebook</span>
                        </div>
                    </div>
                    <div id="tz-form-separator">
                        <hr>
                        <span class="pe-2 ps-2">OR</span>
                        <hr>
                    </div>
                    <div id="tz-signup-form">
                        <div id="tz-form-body">
                            <form action="<?php the_permalink() ?>" class="tz-signup-form" method="post"
                                  nonce="tz_nonce"
                                  id="login">

                                <div id="tz-name" class="mb-4">
                                    <i class="user-icon fa fa-user-tie"></i>
                                    <input type="text" name="tz_name" placeholder="Name">
                                </div>
                                <div id="tz-username" class="mb-4">
                                    <i class="user-icon fa fa-user"></i>
                                    <input type="text" name="tz_username" placeholder="Username">
                                    <i class="progress-icon fa fa-spinner"></i>
                                </div>
                                <div id="tz-email" class="mb-4">
                                    <i class="user-icon fa fa-envelope"></i>
                                    <input type="text" name="tz_email" placeholder="Email">
                                    <i class="progress-icon fa fa-spinner"></i>
                                </div>
                                <div id="tz-password" class="mb-4">
                                    <i class="user-icon fa fa-lock"></i>
                                    <input type="password" name="tz_password" placeholder="Password">
                                </div>
                                <div id="tz-confirm-password" class="mb-4">
                                    <i class="user-icon fa fa-lock"></i>
                                    <input type="password" name="tz_confirm_password" placeholder="Confirm Password">
                                </div>
                                <div id="tz-user-agreement-section"
                                     class="mb-4 d-flex align-items-center justify-content-between">
                                     <span id="tz-user-agreement">
                                    <label for="user-agreement" class="d-flex align-items-center">
                                        <i class="user-icon fa fa-check-circle ps-1"></i>
                                        <input type="checkbox" name="remember" id="user-agreement">
                                       <span> I agree to the Pronations <a href="">User Agreement</a> and <a
                                                   href="<?php echo get_privacy_policy_url(); ?>">Privacy Policy</a>. </span>
                                       </label>
                                    </span>
                                </div>
                                <div id="tz-submit">
                                    <input type="submit" value="Sign Up" name="tz_signup">
                                </div>
                            </form>
                        </div>
                    </div>
                    <div id="form-separator">
                        <hr>
                    </div>
                    <div id="tz-form-footer" class="mb-3">
                        <div id="form-redirect-link" class="d-flex align-items-center justify-content-center mb-2">
                            <span>Don't have an account? <a href="<?php echo tz_post_redirect( 'login' ); ?>"
                                                            class="pe-2">Log In</a> </span>
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
