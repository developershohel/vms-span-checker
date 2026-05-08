<?php
/**
 * Form data for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Pronations Themes
 * @subpackage Pronations Landing Theme
 * @since 2.1.0
 */
if ( ! session_start() ) {
	session_start();
}

global $wpdb, $errors;

$errors = new WP_Error();

if ( isset( $_POST['tz_login'] ) ) {
	$username    = $_POST['tz_username'];
	$userpass    = $_POST['tz_password'];
	$user        = '';
	$credentials = array();

	if ( isset( $username ) ) {
		$username = $wpdb->escape( $username );
	}

	if ( isset( $userpass ) ) {
		$userpass = $wpdb->escape( $userpass );
	}


	if ( empty( $username ) ) {
		$errors->add( 'name_empty', "Username can't empty" );
	}

	if ( empty( $userpass ) ) {
		$errors->add( 'password_empty', "Password can't empty" );
	}

	if ( ! $errors->has_errors() ) {
		$has_user = $wpdb->get_results( "SELECT user_login, user_pass,user_email FROM $wpdb->users WHERE (user_login = '$username' OR user_email = '$username')" );
		if ( $has_user ) {
			$user_status = $wpdb->get_results( "SELECT user_verified_status FROM $wpdb->users WHERE (user_email = '$username' OR user_login = '$username') AND user_verified_status = 'verified'" );
			if ( $user_status ) {
				if ( strpos( $username, '@' ) ) {
					$user = get_user_by( 'email', $username );
				} else {
					$user = get_user_by( 'login', $username );
				}
				if ( ! wp_check_password( $userpass, $user->user_pass, $user->ID ) ) {
					$errors->add( 'password_invalid', "Incorrect Password" );
				} else {
					$credentials['user_login']    = wp_unslash( $username );
					$credentials['user_password'] = wp_unslash( $userpass );
					if ( ! empty( $_POST['rememberme'] ) ) {
						$credentials['remember'] = $_POST['rememberme'];
					}
					if ( ! empty( $credentials['remember'] ) ) {
						$credentials['remember'] = true;
					} else {
						$credentials['remember'] = false;
					}

					$tz_user = wp_signon( $credentials, false );
					if ( is_a( $tz_user, 'WP_User' ) ) {
						wp_safe_redirect( admin_url() );
						exit();
					} else {
						$errors->add( 'login_failed', 'Something went to wrong' );
					}
				}
			} else {
				$errors->add( 'unverified', "Your account isn't verified" );
			}
		} else {
			$errors->add( 'username_invalid', "Username don't exists" );
		}
	} else {
		return false;
	}
}

/**
 * Signup form
 *
 *
 */
if ( isset( $_POST['tz_signup'] ) ) {
	$name             = $_POST['tz_name'];
	$username         = $_POST['tz_username'];
	$useremail        = $_POST['tz_email'];
	$userpass         = $_POST['tz_password'];
	$confirm_userpass = $_POST['tz_confirm_password'];
	$user             = '';
	$credentials      = array();

	if ( empty( $name ) ) {
		$errors->add( 'name_empty', "Name can't empty" );
	}

	if ( empty( $username ) ) {
		$errors->add( 'name_empty', "Username can't empty" );
	} elseif ( username_exists( $username ) ) {
		$errors->add( 'username_exists', "Username already exists" );
	}

	if ( empty( $useremail ) ) {
		$errors->add( 'email_empty', "Email can't empty" );
	} elseif ( ! is_email( $useremail ) ) {
		$errors->add( 'invalid_email', "Please enter a invalid Email" );
	} elseif ( email_exists( $useremail ) ) {
		$errors->add( 'email_exists', "Email already exists" );
	}

	if ( empty( $userpass ) ) {
		$errors->add( 'password_empty', "Password can't empty" );
	} elseif ( strcmp( $userpass, $confirm_userpass ) !== 0 ) {
		$errors->add( 'password_unmatch', "Password Didn't match" );
	}

	if ( ! $errors->has_errors() ) {
		$insert_user = array(
			'user_login'    => (string) $username,
			'user_pass'     => (string) wp_hash_password( $userpass ),
			'user_email'    => (string) $useremail,
			'display_name'  => (string) $name,
			'user_nicename' => (string) $username
		);

		$exists_user = tz_create_user( $username, $userpass, $useremail, $name );

		if ( $exists_user ) {

			$user_code           = random_int( 111111, 999999 );
			$user_activation_key = wp_generate_password( 32, false );

			$update_user_status = array(
				'user_activation_key'  => $user_activation_key,
				'user_status'          => $user_code,
				'user_verified_status' => 'unverified'
			);
			$update_user        = $wpdb->update( $wpdb->users, $update_user_status, array( 'user_login' => $username ) );

			if ( $update_user ) {
				$_SESSION['success']        = "Please type the verification code send
to " . $useremail;
				$_SESSION['username']       = $username;
				$_SESSION['signup_session'] = time() + ( 2 * HOUR_IN_SECONDS );
				$activation_link            = home_url( '/activationlink?tz_link=' . $user_activation_key );
				$subject                    = $user_code . ' is your Pronations verification code';
				$message                    = '<tr>';
				$message                    .= '<td style="margin-bottom: 10px">';
				$message                    .= '<h1 style="font-size: 20px;"> Almost done, <strong style="color:#24292e; font-size: 20px;">' . $username . '</strong> To complete your Pronations sign up, we just need to verify your email address:<br/><strong style="color:#24292e; font-size: 20px;">' . $useremail . '</strong>.</h1>';
				$message                    .= '</td>';
				$message                    .= '</tr>';
				$message                    .= '<tr>';
				$message                    .= '<td style="margin-bottom: 10px">';
				$message                    .= '<a class="tz-activation-link-button btn btn-primary" href="' . $activation_link . '"target="_blank" style="box-sizing: border-box; text-decoration: none; background-color: #0366d6; border-radius: 5px; color: #ffffff; display: inline-block; font-size: 14px; font-weight: bold; cursor: pointer; margin: 0; padding: 10px 20px; border: 1px solid #0366d6;">Verify email address</a>';
				$message                    .= '</td>';
				$message                    .= '</tr>';
				$message                    .= '<tr>';
				$message                    .= '<td style="margin-bottom: 10px">';
				$message                    .= '<h4 style="font-size: 16px; color: #586069; margin: 0;">Once verified, you can start using all of Pronations features to explore, build, and share projects.</h4>';
				$message                    .= '<p style="font-size: 12px;">Button not working? Paste the following link into your browser:<br/> <a class="activation-link text-primary" href="' . $activation_link . '" style="font-size:12px; color: #0366d6;word-break: break-all;" target="_blank">' . $activation_link . '</a>
                                        </p>';
				$message                    .= '</td>';
				$message                    .= '</tr>';
				$message                    .= '<tr>';
				$message                    .= '<td style="margin-bottom: 10px">';
				$message                    .= '<h1 style="text-align: center; font-size: 20px; font-weight: bolder; color: #24292e;">Confirm by verification code</h1>';
				$message                    .= '</td>';
				$message                    .= '</tr>';
				$message                    .= '<tr>';
				$message                    .= '<td style="margin-bottom: 10px">';
				$message                    .= '<h4 style="font-size: 16px; margin: 0; color: #586069;">Please enter this verification code to verify your email and get started on Pronations:<br/> <strong style="color: #24292e; font-size: 24px;">' . $user_code . '</strong></h4>';
				$message                    .= '<span style="font-size: 14px;">Verification codes expire after two hours.</span>';
				$message                    .= '</td>';
				$message                    .= '</tr>';
				$message                    .= '<tr>';
				$message                    .= '<td>';
				$message                    .= '';
				$message                    .= '</td>';
				$message                    .= '</tr>';
				$sender                     = "From: support@themezone.live";
				$sender                     .= "MIME-Version: 1.0\r\n";
				$sender                     .= "Content-Type: text/html; charset=UTF-8\r\n";

				$sent_mail = wp_mail( $useremail, $subject, $message, $sender );
				if ( $sent_mail ) {
					tz_form_redirect( 'otpverification' );
				} else {
					$errors->add( 'email_sent_error', "Sorry we can't sent email successfully" );
				}
			}
		} else {
			$errors->add( 'invalid_login', "Something went to wrong" );
		}
	} else {
		return false;
	}
}

/**
 * OTP Verification
 *
 *
 */

if ( isset( $_POST['tz_otp_verification'] ) ) {
	$tz_otp_verification = $_POST['tz_otp_code'];

	if ( isset( $tz_otp_verification ) ) {
		$tz_otp_verification = (int) $tz_otp_verification;
	}

	if ( empty( $tz_otp_verification ) ) {
		$errors->add( 'otp_code_error', "Verification code can't empty" );
	}

	if ( ! $errors->has_errors() ) {
		$session_signup          = isset( $_SESSION['signup_session'] );
		$session_forgot_password = isset( $_SESSION['forgot_password_session'] );
		if ( $session_signup ) {
			$has_user = $wpdb->get_results( "SELECT user_activation_key,user_status,user_verified_status FROM $wpdb->users WHERE user_status = $tz_otp_verification" );
			if ( $has_user ) {

				$update_user_status = $wpdb->update( $wpdb->users, array(
					'user_activation_key'  => null,
					'user_status'          => null,
					'user_verified_status' => 'verified'
				), array( 'user_status' => $tz_otp_verification ) );
				if ( $update_user_status ) {
					$_SESSION['success'] = 'Your account verified successfully';
					tz_form_redirect( 'login' );
				} else {
					$errors->add( 'otp_error', "Something went to wrong" );
				}
			} else {
				$errors->add( 'invalid_otp', "You are enter invalid Verification code" );
			}
		} elseif ( $session_forgot_password ) {
			$has_user = $wpdb->get_results( "SELECT user_status FROM $wpdb->users WHERE user_status = $tz_otp_verification" );
			if ( $has_user ) {

				$update_user_status = $wpdb->update( $wpdb->users, array(
					'user_status' => null,
				), array( 'user_status' => $tz_otp_verification ) );
				if ( $update_user_status ) {
					$_SESSION['success'] = "Thanks for verification. Please change your password ";
					tz_form_redirect( 'changepassword' );
				} else {
					$errors->add( 'otp_error', "Something went to wrong" );
				}
			} else {
				$errors->add( 'invalid_otp', "You are enter invalid Verification code" );
			}
		}

	} else {
		return false;
	}
}

/**
 * Resent OPT Verification Code
 *
 *
 */
if ( isset( $_POST['tz_resent_code'] ) ) {
	$useremail = $_POST['tz_email'];

	if ( isset( $useremail ) ) {
		$useremail = $wpdb->escape( $useremail );
	}


	if ( empty( $useremail ) ) {
		$errors->add( 'email_empty', "Email can't empty" );
	} elseif ( ! is_email( $useremail ) ) {
		$errors->add( 'invalid_email', "Please enter a invalid Email" );
	} elseif ( ! email_exists( $useremail ) ) {
		$errors->add( 'email_exists', "Email doesn't exists" );
	}

	if ( ! $errors->has_errors() ) {
		$session_signup          = isset( $_SESSION['signup_session'] );
		$session_forgot_password = isset( $_SESSION['forgot_password_session'] );
		$exists_user             = $wpdb->get_results( "SELECT user_email FROM $wpdb->users WHERE user_email = '$useremail'" );

		if ( $exists_user ) {
			$user_code = random_int( 111111, 999999 );

			$update_user_status = array(
				'user_status' => $user_code,
			);

			$update_user = $wpdb->update( $wpdb->users, $update_user_status, array( 'user_email' => $useremail ) );

			if ( $update_user ) {
				$_SESSION['success']                 = "Your verification code sent
to " . $useremail;
				$_SESSION['signup_session']          = time() + ( 2 * HOUR_IN_SECONDS );
				$_SESSION['forgot_password_session'] = time() + ( 2 * HOUR_IN_SECONDS );
				$subject                             = $user_code . ' is your Pronations verification code';
				$message                             = '<tr>';
				$message                             .= '<td style="margin-bottom: 10px">';
				$message                             .= '<h1 style="text-align: center; font-size: 20px; font-weight: bolder; color: #24292e;">Verification code</h1>';
				$message                             .= '</td>';
				$message                             .= '</tr>';
				$message                             .= '<tr>';
				$message                             .= '<td style="margin-bottom: 10px">';
				$message                             .= '<h4 style="font-size: 16px; margin: 0; color: #586069;">Please enter this verification code:<br/> <strong style="color: #24292e; font-size: 24px;">' . $user_code . '</strong></h4>';
				$message                             .= '<span style="font-size: 14px;">Verification codes expire after 24 hours.</span>';
				$message                             .= '</td>';
				$message                             .= '</tr>';
				$message                             .= '<tr>';
				$message                             .= '<td>';
				$message                             .= '';
				$message                             .= '</td>';
				$message                             .= '</tr>';
				$sender                              = "From: support@themezone.live";
				$sender                              .= "MIME-Version: 1.0\r\n";
				$sender                              .= "Content-Type: text/html; charset=UTF-8\r\n";

				$sent_mail = wp_mail( $useremail, $subject, $message, $sender );
				if ( $sent_mail ) {
					if ( $session_signup ) {
						tz_form_redirect( 'otpverification' );
					} elseif ( $session_forgot_password ) {
						tz_form_redirect( 'otpverification' );
					}

				} else {
					$errors->add( 'email_sent_error', "Sorry we can't sent email successfully" );
				}
			}
		} else {
			$errors->add( 'invalid_login', "Something went to wrong" );
		}
	} else {
		return false;
	}
}

/**
 * Forgot Password
 *
 */
if ( isset( $_POST['tz_forgot_password'] ) ) {
	$useremail = $_POST['tz_email'];

	if ( isset( $useremail ) ) {
		$useremail = $wpdb->escape( $useremail );
	}


	if ( empty( $useremail ) ) {
		$errors->add( 'email_empty', "Email can't empty" );
	} elseif ( ! is_email( $useremail ) ) {
		$errors->add( 'invalid_email', "Please enter a invalid Email" );
	} elseif ( ! email_exists( $useremail ) ) {
		$errors->add( 'email_exists', "Email doesn't exists" );
	}

	if ( ! $errors->has_errors() ) {

		$exists_user = $wpdb->get_results( "SELECT user_email FROM $wpdb->users WHERE user_email = '$useremail'" );

		if ( $exists_user ) {
			$user_code = random_int( 111111, 999999 );

			$update_user_status = array(
				'user_status' => $user_code,
			);

			$update_user = $wpdb->update( $wpdb->users, $update_user_status, array( 'user_email' => $useremail ) );

			if ( $update_user ) {
				$_SESSION['success']                 = "Your verification code sent
to " . $useremail;
				$_SESSION['useremail']               = $useremail;
				$_SESSION['forgot_password_session'] = time() + ( 2 * HOUR_IN_SECONDS );
				$subject                             = $user_code . ' is your Pronations verification code';
				$message                             = '<tr>';
				$message                             .= '<td style="margin-bottom: 10px">';
				$message                             .= '<h1 style="text-align: center; font-size: 20px; font-weight: bolder; color: #24292e;">Verification code</h1>';
				$message                             .= '</td>';
				$message                             .= '</tr>';
				$message                             .= '<tr>';
				$message                             .= '<td style="margin-bottom: 10px">';
				$message                             .= '<h4 style="font-size: 16px; margin: 0; color: #586069;">Please enter this verification code:<br/> <strong style="color: #24292e; font-size: 24px;">' . $user_code . '</strong></h4>';
				$message                             .= '<span style="font-size: 14px;">Verification codes expire after 24 hours.</span>';
				$message                             .= '</td>';
				$message                             .= '</tr>';
				$message                             .= '<tr>';
				$message                             .= '<td>';
				$message                             .= '';
				$message                             .= '</td>';
				$message                             .= '</tr>';
				$sender                              = "From: support@themezone.live";
				$sender                              .= "MIME-Version: 1.0\r\n";
				$sender                              .= "Content-Type: text/html; charset=UTF-8\r\n";

				$sent_mail = wp_mail( $useremail, $subject, $message, $sender );
				if ( $sent_mail ) {
					tz_form_redirect( 'otpverification' );
				} else {
					$errors->add( 'email_sent_error', "Sorry we can't sent email successfully" );
				}
			}
		} else {
			$errors->add( 'invalid_login', "Something went to wrong" );
		}
	} else {
		return false;
	}
}

/**
 * Change Password
 *
 */

if ( isset( $_POST['tz_change_password'] ) ) {
	$useremail        = '';
	$userpass         = $_POST['tz_password'];
	$confirm_userpass = $_POST['tz_confirm_password'];

	if ( isset( $_SESSION['useremail'] ) ) {
		$useremail = $_SESSION['useremail'];
	}

	if ( isset( $userpass ) ) {
		$userpass = $wpdb->escape( $userpass );
	}

	if ( isset( $confirm_userpass ) ) {
		$confirm_userpass = $wpdb->escape( $confirm_userpass );
	}

	if ( empty( $userpass ) ) {
		$errors->add( 'password_empty', "Password can't empty" );
	} elseif ( strcmp( $userpass, $confirm_userpass ) !== 0 ) {
		$errors->add( 'password_unmatch', "Password Didn't match" );
	}

	if ( ! $errors->has_errors() ) {
		$update_user = $wpdb->update( $wpdb->users, array( 'user_pass' => (string) wp_hash_password( $userpass ) ), array( 'user_email' => $useremail ) );

		if ( $update_user ) {
			$_SESSION['success'] = "Your password change successfully";
			tz_form_redirect( 'login' );
		} else {
			$errors->add( 'change_password_error', "Something went to wrong" );
		}
	} else {
		return false;
	}
}
