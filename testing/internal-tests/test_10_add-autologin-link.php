<?php
require($_ENV['TEST_FRAMEWORK']);

test_wp_error($admin_test_user_id = wp_create_user(
    "test_admin_user_".microtime(),
    "1234",
    "admin-noemail".microtime()."@localhost.local"));
test_assert($admin_test_user_id != 0, "admin user has user-ID == 0");
test_wp_error($test_user_id = wp_create_user(
    "test_user_".microtime(),
    "1234",
    "noemail".microtime()."@localhost.local"));
test_assert($test_user_id != 0, "test_user has user-ID == 0");

$admin_user = new WP_User($admin_test_user_id);
$test_user = new WP_User($test_user_id);

$admin_user->add_role("administrator");

test_login_user($admin_user->ID);

$logged_in_user = wp_get_current_user();
test_assert($logged_in_user->ID == $admin_user->ID, "admin user not logged in");

$action_name = "update-user_" . $test_user->ID;
test_admin_referer_nonce($action_name);

test_assert(
  check_admin_referer($action_name),
  "update-user nonce verification failed");
test_assert(
  pkg_autologin_check_modify_permissions($test_user->ID),
  "user is not allowed to modify value");

$_POST["user_id"] = $test_user->ID;
$_POST["pkg_autologin_code"] = "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaa";
pkg_autologin_update_link();


$key = get_user_meta($test_user->ID, PKG_AUTOLOGIN_USER_META_KEY, True);
test_assert(strlen($key) == 30, "Key is not 30 characters long: ".strlen($key));

finish_test();
?>
