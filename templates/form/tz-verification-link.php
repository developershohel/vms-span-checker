<?php
/* Template Name: Activation Link */
/**
 * Verification Link form  for our theme
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
global $wpdb, $errors;
$current_time   = time();
$session_signup = isset( $_SESSION['signup_session'] );
if ( $current_time > $session_signup ) {
	$username = $_SESSION['username'];

	$update_user_status = array(
		'user_activation_key' => '',
		'user_status'         => null,
	);
	$wpdb->update( $wpdb->users, $update_user_status, array( 'user_login' => $username ) );
	session_destroy();
	session_start();
	$_SESSION['session_expire'] = 'Your Session is expire';
	tz_form_redirect( 'login' );
} else {
	if ( isset( $_GET['tz_link'] ) ) {
		$key     = $_GET['tz_link'];
		$get_key = $wpdb->get_results( "SELECT user_activation_key FROM $wpdb->users WHERE user_activation_key = '$key'" );
		if ( $get_key ) {
			$update_user_data_args = array(
				'user_status'          => null,
				'user_activation_key'  => null,
				'user_verified_status' => 'verified'
			);

			$update_user_data = $wpdb->update( $wpdb->users, $update_user_data_args, array( 'user_activation_key' => $key ) );
			if ( $update_user_data ) {
				$_SESSION['success'] = 'Your account verified successfully';
				tz_form_redirect( 'login' );
			} else {
				$errors->add( 'otp_update_error', 'Something went to wrong. Please try to verify OTP code' );
				tz_form_redirect( 'otpverification' );
			}

		} else {
			$errors->add( 'otp_update_error', 'Something went to wrong. Please try to verify your account use verification code' );
			tz_form_redirect( 'otpverification' );
		}
	} else {
		tz_form_redirect( 'login' );
	}
}
