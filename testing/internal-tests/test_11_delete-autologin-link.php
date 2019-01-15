<?php
require($_ENV['TEST_FRAMEWORK']);

echo '== Create users ==' . "\n";
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

// ================================
echo '== Login as admin ==' . "\n";
test_login_user($admin_user->ID);

$logged_in_user = wp_get_current_user();
test_assert($logged_in_user->ID == $admin_user->ID, "admin user not logged in");

// ================================
echo "== \"Open\" the admin page for user $test_user->ID ==\n";
$action_name = pkg_new_user_update_nonce_name($test_user->ID);
test_admin_referer_nonce($action_name);
$_GET["user_id"] = $test_user->ID;
$_POST["user_id"] = $test_user->ID;
  
// ================================
echo "== Create a new staged user-login link for a given page ==\n";
$new_code = pkg_stage_new_code();
$staging_nonce = get_user_meta($test_user->ID, PKG_AUTOLOGIN_STAGED_CODE_NONCE_USER_META_KEY, True);

// ================================
echo "== Save the key for the given user ==\n";
$action_name = "update-user_" . $test_user->ID;
test_admin_referer_nonce($action_name);
$_POST["pkg_autologin_update"] = "update";
$_POST[PKG_AUTOLOGIN_STAGED_CODE_NONCE_USER_META_KEY] = "$staging_nonce";
pkg_autologin_update_link();

$autologin_key = get_user_meta($test_user->ID, PKG_AUTOLOGIN_USER_META_KEY, True);
test_assert($autologin_key === $new_code, "Staged key not equal to stored key after saving: '$autologin_key' != '$new_code'");

// ================================
echo "== \"Open\" the admin page for user $test_user->ID, again ==\n";
$action_name = pkg_new_user_update_nonce_name($test_user->ID);
test_admin_referer_nonce($action_name);
$_GET["user_id"] = $test_user->ID;
$_POST["user_id"] = $test_user->ID;

// ================================
echo "== Stage deletion of the login link ==\n";
pkg_delete_code();
$staging_nonce = get_user_meta($test_user->ID, PKG_AUTOLOGIN_STAGED_CODE_NONCE_USER_META_KEY, True);

// ================================
echo "== Execute the deletion ==\n";

$action_name = "update-user_" . $test_user->ID;
test_admin_referer_nonce($action_name);
$_POST["pkg_autologin_update"] = "delete";
$_POST[PKG_AUTOLOGIN_STAGED_CODE_NONCE_USER_META_KEY] = "$staging_nonce";
pkg_autologin_update_link();

$autologin_key = get_user_meta($test_user->ID, PKG_AUTOLOGIN_USER_META_KEY, True);
test_assert($autologin_key === null, "Staged key not deleted");

finish_test();
?>
