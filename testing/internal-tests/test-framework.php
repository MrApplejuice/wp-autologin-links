<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require('wp-load.php');
require('wp-admin/includes/plugin.php');

$_SERVER['HTTP_REFERER'] = admin_url();

function fail_test($message) {
  if (is_wp_error($message)) {
    $message = "(WP_error) ".$message->get_error_message();
  }
  echo "TEST ERROR: ".$message;
  die(20);
}

function test_wp_error($res) {
  if (is_wp_error($res)) {
    fail_test($res);
  }
}

function test_assert($assertion, $message) {
  if (!$assertion) {
    fail_test($message);
  }
}

/**
 * Logs in a user using a given userid
 * 
 * @param int $user_id
 */
function test_login_user($user_id) {
  $userToLogin = get_user_by('id', (int) $user_id);
  do_action('wp_login', $userToLogin->name, $userToLogin);
  
  $expiration = time() + apply_filters('auth_cookie_expiration', 600, $user_id, false);
  $manager = WP_Session_Tokens::get_instance( $user_id );
  $token   = $manager->create($expiration);
  
  $auth_cookie_name = AUTH_COOKIE;
  $auth_cookie  = wp_generate_auth_cookie($user_id, $expiration, "auth", $token);
  $logged_in_cookie = wp_generate_auth_cookie($user_id, $expiration, 'logged_in', $token);

  wp_set_auth_cookie($user_id, false);
  do_action('set_auth_cookie', $auth_cookie, $expire, $expiration, $user_id, $scheme, $token);
  do_action('set_logged_in_cookie', $logged_in_cookie, $expire, $expiration, $user_id, 'logged_in', $token);
  
  $_COOKIE[$auth_cookie_name] = $auth_cookie;
  $_COOKIE[LOGGED_IN_COOKIE] = $logged_in_cookie;
  
  $user_id = apply_filters( 'determine_current_user', false );
  wp_set_current_user( $user_id ? $user_id : 0 );
}

/**
 * Creates a nonce for an admin page such that its contents can be modified. 
 * 
 * @param string $key
 */
function test_admin_referer_nonce($key) {
  $_REQUEST["_wpnonce"] = wp_create_nonce($key);
}

/**
 * Called to finish a test successfully. 
 */
function finish_test() {
  die(21);
}

?>