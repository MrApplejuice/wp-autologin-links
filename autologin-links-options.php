<?php

/**
 * Utility function allowing to obtain setting options ina standardized form.
 * 
 * @param $name
 *   String indentifying the option to obtain.
 * 
 * @return
 *   Value of the type associated with the corresponding option.
 */
function pkg_autologin_get_default_option($name) {
    if ($name === PKG_AUTOLOGIN_OPTION_SECURITY_LOCKOUT_REPEATITIONS) {
        return intval(get_option(PKG_AUTOLOGIN_OPTION_SECURITY_LOCKOUT_REPEATITIONS, "20"));
    }
    if ($name === PKG_AUTOLOGIN_OPTION_SECURITY_LOCKOUT_TIMEOUT) {
        return intval(get_option(PKG_AUTOLOGIN_OPTION_SECURITY_LOCKOUT_TIMEOUT, "10"));
    }
    if ($name === PKG_AUTOLOGIN_OPTION_SECURITY_LOCKOUT_RECORDS) {
        $result = json_decode(
            get_option(PKG_AUTOLOGIN_OPTION_SECURITY_LOCKOUT_RECORDS, "{}"),
            true);

        if ($result !== null) {
            return $result;
        }
        return array();
    }
    throw "Not a valid option";
}

?>
