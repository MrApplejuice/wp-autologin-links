<?php
require_once "autologin-links-options.php";

/**
 * Generates a footprint for the connecting side to indentify the remote side
 * without storing the actual IP address.
 * 
 * @return string
 *   Returns a string reference fo the remote side.
 */
function pkg_autologin_generate_remote_footprint() {
    $salt = $_SERVER['SERVER_ADDR'] . ":" . "Autologinlinks4lt1111";
    return substr(md5($salt . ':' . $_SERVER['REMOTE_ADDR']), 0, 16);
}

/**
 * Clean up timed-out entries in the lockout database.
 * 
 * @return array
 *   The cleaned record list that is also stored in the database.
 */
function pkg_autologin_cleanup_remote_login_records() {
    $records = pkg_autologin_get_default_option(PKG_AUTOLOGIN_OPTION_SECURITY_LOCKOUT_RECORDS);
    $timeoutMinutes = pkg_autologin_get_default_option(PKG_AUTOLOGIN_OPTION_SECURITY_LOCKOUT_TIMEOUT);
    $lockoutCount = pkg_autologin_get_default_option(PKG_AUTOLOGIN_OPTION_SECURITY_LOCKOUT_REPEATITIONS);

    $modified = false;
    $newRecords = array();
    if ((count($records) > 0) && ($lockoutCount == 0)) {
        $modified = true;
    } elseif ($lockoutCount > 0) {
        $now = time();
        foreach ($records as $key => $info) {
            if ($now - $info["t"] < $timeoutMinutes * 60) {
                $newRecords[$key] = $info;
            } else {
                $modified = true;
            }
        }
    } else {
        // pass, nothing to do
    }

    if ($modified) {
        update_option(PKG_AUTOLOGIN_OPTION_SECURITY_LOCKOUT_RECORDS, json_encode($newRecords));
        return $newRecords;
    } else {
        return $records;
    }
}

/**
 * Check if the current remote address can login to the server.
 * 
 * @return boolean
 *   True if the remote address is not blacklisted/allowed to login.
 */
function pkg_autologin_check_remote_can_login() {
    $lockoutCount = pkg_autologin_get_default_option(PKG_AUTOLOGIN_OPTION_SECURITY_LOCKOUT_REPEATITIONS);
    if ($lockoutCount <= 0) {
        return true;
    }

    $records = pkg_autologin_cleanup_remote_login_records();
    $footprint = pkg_autologin_generate_remote_footprint();
    if (!isset($records[$footprint]) || ($records[$footprint]["c"] < $lockoutCount)) {
        return true;
    }
    return false;
}

/**
 * Mark the current remote server having had "failed login" using an autologin
 * link.
 */
function pkg_autologin_mark_failed_login() {
    $footprint = pkg_autologin_generate_remote_footprint();

    $records = pkg_autologin_cleanup_remote_login_records();
    if (!isset($records[$footprint])) {
        $records[$footprint] = array("c" => 0);
    }
    $records[$footprint]["t"] = time();
    $records[$footprint]["c"] = $records[$footprint]["c"] + 1;

    update_option(PKG_AUTOLOGIN_OPTION_SECURITY_LOCKOUT_RECORDS, json_encode($records));
}

/**
 * Mark the current remote server having had "succeeded login" using an autologin
 * link.
 */
function pkg_autologin_mark_successful_login() {
    $footprint = pkg_autologin_generate_remote_footprint();

    $records = pkg_autologin_cleanup_remote_login_records();
    if (isset($records[$footprint])) {
        unset($records[$footprint]);
    }
    update_option(PKG_AUTOLOGIN_OPTION_SECURITY_LOCKOUT_RECORDS, json_encode($records));
}

?>