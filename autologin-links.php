<?php
/*
Plugin Name: Autologin Links
Plugin URI: https://www.craftware.info/projects-lists/wordpress-autologin/
Description: Lets administrators generate autologin links for users.
Author: Paul Konstantin Gerke
Version: 1.11.3
Author URI: http://www.craftware.info/
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

// Yup, we will use some internal wordpress features here!
require_once ABSPATH . WPINC . "/class-phpass.php";

//! In-code defintion to allow detection of the autlogin-plugin and its version
define('PKG_AUTOLOGIN_VERSION', 11103); // Version: 1.11.3

//! Length for newly generated autologin links 
define('PKG_AUTOLOGIN_CODE_LENGTH', 32);

//! Valid characters for a newly generated autologin link
define('PKG_AUTOLOGIN_CODE_CHARACTERS', "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789");

//! The URL-$_GET key used to transport a autologin-link to the website
define('PKG_AUTOLOGIN_VALUE_NAME', 'autologin_code');

//! The key for the user metadata database table that autologin links are under.
define('PKG_AUTOLOGIN_USER_META_KEY', 'pkg_autologin_code');

//! The key for the user metadata database that stores staged (unsaved) login keys
define('PKG_AUTOLOGIN_STAGED_CODE_USER_META_KEY', 'pkg_autologin_staged_code');

//! The nonce used to stage the changed code. Must match the submitted nonce 
//! on save, otherwise the staged nonce will be dismissed.
define('PKG_AUTOLOGIN_STAGED_CODE_NONCE_USER_META_KEY', 'pkg_autologin_staged_code_nonce');

//! Language domain key for localization
define('PKG_AUTOLOGIN_LANGUAGE_DOMAIN', 'pkg_autologin');

//! The number of registered users after which the website is considered a "big website", disabling
//! certain widgets for performance benefits.
define('PKG_AUTOLOGIN_BIG_WEBSITE_THRESHOLD', 20);


/********* TOOL FUNCTION *********/

/**
 * <p>Checks whether the current user has the right to change the autologin link of 
 * the user $user_id</p>
 * 
 * @param $user_id
 *   (int or NULL) If NULL, the "current user" is meant, if int the user-id of
 *   the user for that the current user wants to change the autologin link is meant
 * @return
 *   True if the current user has the right to do this action, False otherwise
 */
function pkg_autologin_check_modify_permissions($user_id=NULL) {
  if ($user_id === NULL) {
    $user_id = wp_get_current_user()->ID;
  }
  
  return current_user_can('administrator');
}

/**
 * <p>Checks whether the current user has the right to view the autologin link of 
 * the user $user_id. Not every user may view the autologin link of other users
 * because of (hopefully obvious) security problems.</p>
 * 
 * @param $user_id
 *   (int or NULL) If NULL, the "current user" is meant, if int the user-id of
 *   the user for that the current user wants to view the autologin link is meant
 * @return
 *   True if the current user has the right to do this action, False otherwise
 */
function pkg_autologin_check_view_permissions($user_id=NULL) {
  if ($user_id === NULL) {
    $user_id = wp_get_current_user()->ID;
  }
  
  return (defined("IS_PROFILE_PAGE") && IS_PROFILE_PAGE) || pkg_autologin_check_modify_permissions();
}

/**
 * <p>Joins the array-list of parameters to a correct GET-request parameter list. For example:</p>
 * 
 * <p>array('a' => 1, 'b' => 2, 'c' => 3) becomes a=1&b=2&c=3</p>
 * 
 * @param array $parameters
 *   The parameters to join together to form the GET-request url part
 * @return string
 *   The formed get-request string
 */
function pkg_autologin_join_get_parameters($parameters) {
  $keys = array_keys($parameters);
  $assignments = array();
  foreach ($keys as $key) {
    $assignments[] = rawurlencode($key) . "=" . rawurlencode($parameters[$key]);
  }
  return implode('&', $assignments);
}

/**
 * This function reads the user_id from the page request ($_GET by default) 
 * thats page is being edited and validates that it is a correct user id.
 * The id is retrieved via the 'user_id' key in the $parameterArray (defaults
 * to the $_GET array).
 * 
 * @param array $parameterArray
 *   Allows to override the default of using the $_GET-array as source for
 *   reading the 'user_id' field.
 * @return boolean
 *   False if the user_id could not be read from $_GET or derived 
 *   via wp_get_current_user if the current page is a user's profile page.
 *   (int) The user_id of the user thats page is being edited if 
 */
function pkg_autologin_get_page_user_id($parameterArray=NULL) {
  if ($parameterArray === NULL) {
    $parameterArray = $_GET;
  }
  
  $result = False;
  
  // On profile page?
  if (defined('IS_PROFILE_PAGE') && IS_PROFILE_PAGE) {
    $user = wp_get_current_user();
    if ($user && ($user->ID != 0)) {
      $result = $user->ID;
    }
  } else { // Not on profile page -> read user_id from $parameterArray
    if (isset($parameterArray['user_id'])) { 
      $result = (int) $parameterArray['user_id'];
      if (!get_userdata($result)) {
        $result = False;
      }
    }
  }
  
  return $result;
}

/**
 * Generates a get-query string out of the $_GET data map 
 * including the '?'-separator from the URL-part. While generating the string
 * the function also strips the PKG_AUTOLOGIN_VALUE_NAME value name if defined
 * so that it can be redefined by appending it later.
 * 
 * @return string
 *   The reassembled $_GET-query string specified in the URL. Will
 *   include the '?'-separator any $_GET-query data was present. Example value:
 *     '?id=53&metadata=join' 
 *       given $_GET = array( 'id' => '53', 'metadata' => 'join' )
 */
function pkg_autologin_generate_get_postfix() {
  $GETcopy = $_GET;
  unset($GETcopy[PKG_AUTOLOGIN_VALUE_NAME]);
  $GETQuery = pkg_autologin_join_get_parameters($GETcopy);
  if (strlen($GETQuery) > 0) {
    $GETQuery = '?' . $GETQuery;
  }
  return $GETQuery;
}

/*********** ACTIONS/PLUGIN PART *************/

add_action('init', 'pkg_autologin_localization');
function pkg_autologin_localization() {
  load_plugin_textdomain(PKG_AUTOLOGIN_LANGUAGE_DOMAIN, false, plugin_basename(dirname(__FILE__)) . '/languages');
}

// Hook general init to login users if an autologin code is specified
add_action('init', 'pkg_autologin_authenticate');
function pkg_autologin_authenticate() {
  global $wpdb;
  
  // Check if autologin link is specified - if there is one the work begins
  if (isset($_GET[PKG_AUTOLOGIN_VALUE_NAME])) {    
    $autologin_code = preg_replace('/[^a-zA-Z0-9]+/', '', $_GET[PKG_AUTOLOGIN_VALUE_NAME]);
    
    if ($autologin_code) { // Check if not empty
      // Get part left of ? of the request URI for resassembling the target url later
      $subURIs = array();
      if (preg_match('/^([^\?]+)\?/', $_SERVER["REQUEST_URI"], $subURIs) === 1) {
        $targetPage = $subURIs[1];

        if (isset($_SERVER["HTTP_X_FORWARDED_PREFIX"])) {
          $prefix = $_SERVER["HTTP_X_FORWARDED_PREFIX"];
          if (substr($prefix, -1) == "/") {
            $prefix = substr($prefix, 0, -1);
          }
          $targetPage = $prefix . $targetPage;
        }
        
        // Query login codes
        $loginCodeQuery = $wpdb->prepare(
          "SELECT user_id, meta_value as login_code FROM $wpdb->usermeta WHERE meta_key = %s and meta_value = '%s';",
          PKG_AUTOLOGIN_USER_META_KEY,
          $autologin_code); // $autologin_code has been heavily cleaned before
        
        $userIds = array();
        $results = $wpdb->get_results($loginCodeQuery, ARRAY_A);
        if ($results === NULL) {
          wp_dir("Query failed!");
        }
        foreach ($results as $row) {
          if ($row["login_code"] === $autologin_code) {
            $userIds[] = $row["user_id"];
          }
        }
        
        // Double login codes? should never happen - better safe than sudden admin rights for someone :D
        if (count($userIds) > 1) {
          wp_die("Please login normally - this is a statistic bug and prevents you from using login links securely!"); // TODO !!!
        }

        // Only login if there is only ONE possible user
        if (count($userIds) == 1) {
          $userToLogin = get_user_by('id', (int) $userIds[0]);
    
          // Check if user exists
          if ($userToLogin) {
            wp_set_auth_cookie($userToLogin->ID, false);
            do_action('wp_login', $userToLogin->name, $userToLogin);

            // Create redirect URL without autologin code
            $GETQuery = pkg_autologin_generate_get_postfix();
            
            $protocol = (isset($_SERVER["HTTPS"]) && ($_SERVER["HTTPS"] === "on")) ? "https" : "http";
            
            // Augment my solution with https://stackoverflow.com/questions/1907653/how-to-force-page-not-to-be-cached-in-php
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
            header("Cache-Control: no-cache, no-store, must-revalidate, private, max-age=0, s-maxage=0");
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache");
            header("Expires: Mon, 01 Jan 1990 01:00:00 GMT");
            
            wp_redirect($protocol . '://' . $_SERVER['HTTP_HOST'] . $targetPage . $GETQuery);
            exit;
          }
        }
      } 
    }
    
    // If something went wrong send the user to login-page (and log the old user out if there was any)
    wp_logout();
    wp_redirect(home_url('wp-login.php?pkg_autologin_error=invalid_login_code'));
    exit;
  }
}

// Hook special login head to be able to display specialized "invalid autologin link" error
add_action('login_head', 'pkg_autologin_extract_login_link_error');
function pkg_autologin_extract_login_link_error() {
  global $errors;

  if (isset($_GET['pkg_autologin_error'])) {
    $rawMsg = $_GET['pkg_autologin_error'];
    
    // Check if valid pkg_autologin_error
    if (in_array($rawMsg, array('invalid_login_code'))) {
      $secureMsg = $rawMsg;
      
      // Add error texts
      switch ($secureMsg) {
        case 'invalid_login_code':
          $errors->add("invalid_autologin_link", __("Invalid autologin link.", PKG_AUTOLOGIN_LANGUAGE_DOMAIN));
          break;
      }
    }
  }
}

// Does not work for some reason... :-(
/*
add_filter('shake_error_codes', 'pkg_autologin_add_shake_error_codes', 20, 1);
function pkg_autologin_add_shake_error_codes($errorCodes) {
  $errorCodes[] = "invalid_autologin_link";
  return $errorCodes;
}
*/

add_action('admin_enqueue_scripts', 'pkg_autologin_load_autologin_scripts');
function pkg_autologin_load_autologin_scripts() {
  // TODO: I give up... enqueue scripts to all admin pages. With IS_PROFILE_PAGE I can check whether 
  // someone visits his own profile page, but since admins can also add user data, it should be possible
  // to check if one visits a "user edit" page, too. Could not find any simple method for the latter. If
  // there is one, scripts should only be added to "IS_PROFILE_PAGE" and "EDIT_USER_DATA" pages.
  
  
  // This is kind of hacky. Add javascript to ALL pages if the user_id is 
  // mentioned and the current user might view his autologin link. Can only be 
  // fixed if TODO above is fixed and script can propoerly distinguish between 
  // different admin pages.
  $user_id = pkg_autologin_get_page_user_id(); 
  
  if ($user_id) { // Only if page is asking for data of a valid user
    if (pkg_autologin_check_view_permissions($user_id)) {
      wp_enqueue_script('pkg_autologin_client_script', plugins_url('autologin-client.js', __FILE__), array("jquery"));
      if (pkg_autologin_check_modify_permissions($user_id)) { 
        wp_enqueue_script('pkg_autologin_admin_script', plugins_url('autologin-admin.js', __FILE__), array("pkg_autologin_client_script"));
      }
    }
  }
}

// Add autologin links to user pages and corresponding update elements to admin pages
add_action('personal_options_update', 'pkg_autologin_update_link');
add_action('edit_user_profile_update', 'pkg_autologin_update_link');

function pkg_autologin_validate_update_nonce($user_id) {
  if (!array_key_exists(PKG_AUTOLOGIN_STAGED_CODE_NONCE_USER_META_KEY, $_POST)) {
    wp_die(__("Missing or invalid staging nonce"));
  }
  $submitted_staging_nonce = (string) $_POST[PKG_AUTOLOGIN_STAGED_CODE_NONCE_USER_META_KEY];
  $nonce = get_user_meta($user_id, PKG_AUTOLOGIN_STAGED_CODE_NONCE_USER_META_KEY, True);
  if ($nonce !== $submitted_staging_nonce) {
    wp_die(__("Invalid request for login code change."));
  }
}

/**
 * This function is registered for handling update-profile requests on a user's admin page.
 */
function pkg_autologin_update_link() {
  // Check if code should be updated
  if (array_key_exists('pkg_autologin_update', $_POST) && 
      (($_POST["pkg_autologin_update"] === "update") || ($_POST["pkg_autologin_update"] === "delete"))) {
    $user_id = pkg_autologin_get_page_user_id($_POST); // Get data from POST array
    if (!$user_id) {
      wp_die(__('Invalid user ID.'));
    }
    
    if (!check_admin_referer('update-user_' . $user_id)) { // Check nonce - not validated before in user-edit.php :-(
      wp_die("YOU SHOULD NOT GET HERE BECAUSE EXECUTION SHOULD HAVE DIED - However, and you may not do this!");
    }
    
    if (!pkg_autologin_check_modify_permissions($user_id)) {
      wp_die(__( 'You do not have permission to edit this user.' )); // Use general error message - Perhaps better use special one like "you may not change the autologin link" ?
    }
    
    if ($_POST["pkg_autologin_update"] === "update") {
      pkg_autologin_validate_update_nonce($user_id);
      
      $newKey = "" . get_user_meta($user_id, PKG_AUTOLOGIN_STAGED_CODE_USER_META_KEY, True);
      $cleanedKey = "";
      for ($i = 0; $i < strlen($newKey); $i++) {
        $c = substr($newKey, $i, 1);
        if (strpos(PKG_AUTOLOGIN_CODE_CHARACTERS, $c) !== False) {
          $cleanedKey = $cleanedKey . $c;
        }
      }
      if (strlen($cleanedKey) != PKG_AUTOLOGIN_CODE_LENGTH) {
        wp_die(__('Invalid autologin code.', PKG_AUTOLOGIN_LANGUAGE_DOMAIN));
      }
      
      if (!add_user_meta($user_id, PKG_AUTOLOGIN_USER_META_KEY, $cleanedKey, True)) {
        if (!update_user_meta($user_id, PKG_AUTOLOGIN_USER_META_KEY, $cleanedKey)) {
          // Check if the key was changed at all - if not this is an error of update_user_meta
          if (get_user_meta($user_id, PKG_AUTOLOGIN_USER_META_KEY, True) != $cleanedKey) {
            wp_die(__('Failed to update autologin link.', PKG_AUTOLOGIN_LANGUAGE_DOMAIN));
          }
        }
      }
    } else if ($_POST["pkg_autologin_update"] === "delete") {
      pkg_autologin_validate_update_nonce($user_id);
      
      $new_key = get_user_meta($user_id, PKG_AUTOLOGIN_STAGED_CODE_USER_META_KEY, True);
      if ($new_key !== null) {
        wp_die(__('Invalid autologin deletion request.'));
      }
      
      if (get_user_meta($user_id, PKG_AUTOLOGIN_USER_META_KEY, True)) {
        if (!delete_user_meta($user_id, PKG_AUTOLOGIN_USER_META_KEY)) {
          wp_die(__('Failed to delete autologin link.', PKG_AUTOLOGIN_LANGUAGE_DOMAIN));
        }
      }
    } else {
      wp_die(__("Invalid action."));
    }
  }
}

/**
 * Constructs a new nonce key name to modify some
 * user's auto login key. This key should be passed into
 * wp_create_nonce to create a real nonce for the currently 
 * logged in user.
 * 
 * @param int $user_id
 *   The user id whose autologin code should be adapted.
 * @return string
 *   The nonce key name. This can be passed
 */
function pkg_new_user_update_nonce_name($user_id) {
  $metadata = get_user_meta($user_id, PKG_AUTOLOGIN_USER_META_KEY, false);
  if (count($metadata) > 0) {
    $codeNonceData = "c" . $metadata[0];
  } else {
    $codeNonceData = 'e';
  }
  return "pkg-update-user-link_" . wp_nonce_tick() . "_$user_id" . "_$codeNonceData";
}

/**
 * This function generates a new code string that can be used as a autologin code. The
 * function uses safely randomized integer values from the wordpress API.
 * 
 * @return string
 *   The newly generated autologin code.
 */
function pkg_autologin_generate_code() {
  $hasher = new PasswordHash(8, true); // The PasswordHasher has a php-version independent "safeish" random generator
  
  // Workaround: first value seems to always be zero, so we will skip the first value
  $random_ints = unpack("L*", $hasher->get_random_bytes(4 * (PKG_AUTOLOGIN_CODE_LENGTH + 1)));
  $char_count = strlen(PKG_AUTOLOGIN_CODE_CHARACTERS);
  $new_code = "";
  $_str_copy_php55 = PKG_AUTOLOGIN_CODE_CHARACTERS;
  for ($i = 0; $i < PKG_AUTOLOGIN_CODE_LENGTH; $i++) {
    $new_code = $new_code . $_str_copy_php55[$random_ints[$i + 1] % $char_count];
  }
  return $new_code;
}

/**
 * Stages a new randomly generated code for saving later. When the store-command 
 * is actually triggered, this stored key will be stored as the new current key. To verify that the stored
 * staged key refers to the current user-presented update form, the nonce associated
 * with the profile page is also stored.
 * 
 * $_POST["user_id"] and $_POST["_wpnonce"] must be set before this function can be executed. 
 * 
 * @return string
 *   Returns the newly generated code.
 */
function pkg_autologin_stage_new_code() {
  $user_id = pkg_autologin_get_page_user_id($_POST);
  if (!$user_id) {
    wp_die(__('Invalid user ID.'), '', array('response' => 400));
  }

  if (!check_ajax_referer(pkg_new_user_update_nonce_name($user_id))) {
    wp_die(__('Invalid referer.'));
  }

  $new_code = pkg_autologin_generate_code();
  
  $wpnonce = $_REQUEST['_wpnonce'];
  update_user_meta($user_id, PKG_AUTOLOGIN_STAGED_CODE_NONCE_USER_META_KEY, $wpnonce);
  update_user_meta($user_id, PKG_AUTOLOGIN_STAGED_CODE_USER_META_KEY, $new_code);
  
  return $new_code;
}

/**
 * Stages a deletion of the currently saved user autologin code.
 */
function pkg_autologin_stage_code_deletion() {
  $user_id = pkg_autologin_get_page_user_id($_POST);
  if (!$user_id) {
    wp_die(__('Invalid user ID.'), '', array('response' => 400));
  }
  
  if (!check_ajax_referer(pkg_new_user_update_nonce_name($user_id))) {
    wp_die(__('Invalid referer.'));
  }

  $wpnonce = $_REQUEST['_wpnonce'];
  update_user_meta($user_id, PKG_AUTOLOGIN_STAGED_CODE_NONCE_USER_META_KEY, $wpnonce);
  update_user_meta($user_id, PKG_AUTOLOGIN_STAGED_CODE_USER_META_KEY, null);
}

add_action('wp_ajax_pkg_autologin_plugin_ajax_new_code', 'pkg_autologin_plugin_new_code_ajax_wrapper');
function pkg_autologin_plugin_new_code_ajax_wrapper() {
  $user_id = pkg_autologin_get_page_user_id($_POST);
  if (!$user_id) {
    wp_die(__('Invalid user ID.'), '', array('response' => 400));
  }

  if (!isset($_REQUEST['_wpnonce'])) {
    $_REQUEST['_wpnonce'] = $_REQUEST['_ajax_nonce'];
  }
  
  $new_code = pkg_autologin_stage_new_code();
  wp_send_json(array(
    "user_id" => $user_id,
    "new_code" => $new_code,
  ));
}

add_action('wp_ajax_pkg_autologin_plugin_ajax_delete_code', 'pkg_autologin_plugin_delete_code_ajax_wrapper');
function pkg_autologin_plugin_delete_code_ajax_wrapper() {
  $user_id = pkg_autologin_get_page_user_id($_POST);
  if (!$user_id) {
    wp_die(__('Invalid user ID.'), '', array('response' => 400));
  }
  
  if (!isset($_REQUEST['_wpnonce'])) {
    $_REQUEST['_wpnonce'] = $_REQUEST['_ajax_nonce'];
  }

  pkg_autologin_stage_code_deletion();
  wp_send_json(array(
    "user_id" => $user_id,
   ));
}
  
add_action('show_user_profile', 'pkg_autologin_plugin_add_extra_profile_fields');
add_action('edit_user_profile', 'pkg_autologin_plugin_add_extra_profile_fields');

function pkg_autologin_add_control_buttons($current_link_code, $user_id) { // Controls for generating new links or deleting old ones only available to admins
  $prefix = home_url('?' . PKG_AUTOLOGIN_VALUE_NAME . '=');
  $prefix = apply_filters('pkg_autologin_links_sample_url_prefix', $prefix);
  ?>
  <input type="hidden" autocomplete="off" id="pkg_autologin_update" name="pkg_autologin_update" value="" />
  <input type="hidden" autocomplete="off" id="pkg_autologin_nonce" name="pkg_autologin_staged_code_nonce" value="<?php echo wp_create_nonce(pkg_new_user_update_nonce_name($user_id)); ?>" />
  <input type="button" value="<?php _e("New", PKG_AUTOLOGIN_LANGUAGE_DOMAIN); ?>" id="pkg_autologin_new_link_button" onclick="pkg_autologin_new_link_click(this, <?php echo "'$prefix'"; ?>)" class="button" />
  <input type="button" value="<?php _e("Delete", PKG_AUTOLOGIN_LANGUAGE_DOMAIN); ?>" id="pkg_autologin_delete_link_button" onclick="pkg_autologin_delete_link_click(this)" class="button" />
  <?php
}

function pkg_autologin_plugin_add_extra_profile_fields() {
  $user_id = pkg_autologin_get_page_user_id();
  
  if (!$user_id) {
    wp_die(__('Invalid user ID.'));
  } else {
    $current_link_code = get_user_meta($user_id, PKG_AUTOLOGIN_USER_META_KEY, True);
    if (!$current_link_code) {
      $current_link_code = "";
    }
    
    if (pkg_autologin_check_view_permissions()) {
      ?>
<h3><?php _e("Auto-login", PKG_AUTOLOGIN_LANGUAGE_DOMAIN);?></h3>
<table class="form-table">
  <tbody>
    <tr>
      <th><label for="pkg_autologin_link"><?php _e("Auto-login link", PKG_AUTOLOGIN_LANGUAGE_DOMAIN); ?></label></th>
      <td>
        <span id="pkg_autologin_user_id" style="display:none"><?php echo $user_id; ?></span>
        <img style="display:none;" id="pkg_autologin_link_wait_spinner" src="<?php echo plugins_url("wait.gif", __FILE__); ?>" />
        <span id="pkg_autologin_unsaved_marker" style="display:none">&#91;<?php _e("Unsaved", PKG_AUTOLOGIN_LANGUAGE_DOMAIN);?>&#93;</span>
        <p id="pkg_autologin_link"><?php echo ($current_link_code ? home_url('?' . PKG_AUTOLOGIN_VALUE_NAME . "=$current_link_code") : "-"); ?></p>
        <?php if (pkg_autologin_check_modify_permissions()) { pkg_autologin_add_control_buttons($current_link_code, $user_id); } else { ?>
          <i>[<?php _e("Please ask an administrator to change your login link", PKG_AUTOLOGIN_LANGUAGE_DOMAIN);?>]</i>
        <?php } ?>
      </td>
    </tr>
  </tbody>
</table>
      <?php
    }
  }
}

/* ADMIN BAR - GENERATE LINK FUNCTIONS */

add_action('wp_enqueue_scripts', 'pkg_autologin_load_autologin_show_link_scripts');
function pkg_autologin_load_autologin_show_link_scripts() {
  if (pkg_autologin_check_modify_permissions()) {
    wp_enqueue_script('pkg_autologin_show_link_popup_script',  plugins_url( 'autologin-show-link-popup.js', __FILE__ ), array( 'jquery-ui-dialog' ));
    wp_localize_script('pkg_autologin_show_link_popup_script', 'pkg_autologin_show_link_translation_strings', array(
      'link_text' =>    esc_html(__("Link:", PKG_AUTOLOGIN_LANGUAGE_DOMAIN . " javascript popup link title")),
      'press_ctrl_c' => esc_html(__("(press ctrl+c to copy)", PKG_AUTOLOGIN_LANGUAGE_DOMAIN . " javascript popup copy instruction")),
      'title_prefix' => __("Link for ", PKG_AUTOLOGIN_LANGUAGE_DOMAIN . " javascript popup prefix (followed by username)"),
      'ok_button' =>    __("Ok", PKG_AUTOLOGIN_LANGUAGE_DOMAIN . " javascript popup")
    ));
    
    wp_enqueue_style('pkg_autologin_show_link_popup_stylesheet', plugins_url('autologin-show-link-popup.css', __FILE__));
  }
}


/**
 * Fuses a wordpress site postfix with the siteurl to
 * build a complete url finding the largest overlap between the urls
 */
function pkg_autologin_fuse_url_with_site_url($url) {
  $siteurl = site_url();

  $overlap = min(strlen($url), strlen($siteurl));
  while ($overlap > 0) {
    if (substr($siteurl, -$overlap, $overlap) == substr($url, 0, $overlap)) {
      break;
    }
    $overlap -= 1;
  }
  
  return substr($siteurl, 0, strlen($siteurl) - $overlap) . $url;
}

add_action('admin_bar_menu', 'pkg_autologin_add_admin_bar_generate_link_button', 125); // 125 is somewhere behind the "edit"-button
function pkg_autologin_add_admin_bar_generate_link_button($wp_admin_bar) {
  if (!is_admin()) {
    if (pkg_autologin_check_modify_permissions()) {
      $title = '<span class="ab-icon"></span><span class="ab-label">' . __('Auto-login link', PKG_AUTOLOGIN_LANGUAGE_DOMAIN) . '</span>';

      $wp_admin_bar->add_menu( array(
        'id'    => 'pkg-generate-auto-login-link-menu',
        'title' => $title
      ));
      
      // Add usernames that have a autologin link
      $autologin_link_users = get_users(
        array (
          'meta_key'     => PKG_AUTOLOGIN_USER_META_KEY,
          'meta_compare' => 'EXISTS',
          'number' => PKG_AUTOLOGIN_BIG_WEBSITE_THRESHOLD + 1,
        )
      );
      
      if (count($autologin_link_users) == 0) {
        // No uses can use autologin links, show verbose message
        $title = __('No users with autologin codes', PKG_AUTOLOGIN_LANGUAGE_DOMAIN);
        $wp_admin_bar->add_menu( array(
          'parent' => 'pkg-generate-auto-login-link-menu',
          'id'     => 'pkg-generate-auto-login-link-menu-nousers',
          'title'  => $title
        ));
      } elseif (count($autologin_link_users) > PKG_AUTOLOGIN_BIG_WEBSITE_THRESHOLD) {
        $title = sprintf(__(
          'Disabled for big websites with more than %d users',
          PKG_AUTOLOGIN_LANGUAGE_DOMAIN
        ), PKG_AUTOLOGIN_BIG_WEBSITE_THRESHOLD);
        $wp_admin_bar->add_menu( array(
          'parent' => 'pkg-generate-auto-login-link-menu',
          'id'     => 'pkg-generate-auto-login-link-menu-nousers',
          'title'  => $title
        ));
      } else {
        // Get the target website address parts
        $subURIs = array();
        if (preg_match('/^([^\?]+)\?(.*)/', $_SERVER["REQUEST_URI"], $subURIs) === 1) {
          // Page contained $_GET element - reassamble
          $targetPage = $subURIs[1];             
        } else {
          $targetPage = $_SERVER["REQUEST_URI"];
        }
        $GETQueryPrefix = pkg_autologin_generate_get_postfix();
        if (strlen($GETQueryPrefix) > 0) {
          $GETQueryPrefix = $GETQueryPrefix . "&";
        } else {
          $GETQueryPrefix = '?';
        }

        // Now generate menu items with autologin codes for each user
        $i = 0;
        foreach ($autologin_link_users as $user) {
          $autologin_key = get_user_meta($user->ID, PKG_AUTOLOGIN_USER_META_KEY, True);
        
          $url = $targetPage . $GETQueryPrefix . PKG_AUTOLOGIN_VALUE_NAME . "=" . $autologin_key;
          $htmlUserName = esc_html($user->first_name) . " " . esc_html($user->last_name) . " (" . esc_html($user->user_login) . ")";
          $title = __("Login link for", PKG_AUTOLOGIN_LANGUAGE_DOMAIN) . " " . $htmlUserName;
          
          $onclick_url = pkg_autologin_fuse_url_with_site_url($url);
          $wp_admin_bar->add_menu( array(
            'parent'  => 'pkg-generate-auto-login-link-menu',
            'id'      => 'pkg-generate-auto-login-link-menu-userindex' . strval($i),
            'title'   => $title,
            'href'    => $url,
            'meta' => array(
              'onclick' => 'pkg_autologin_show_copy_link_dialog("' . esc_html($user->user_login) . '", "' . esc_html($onclick_url) . '"); return false;',
              'target'  => "_blank"
            )
          ));
          
          $i += 1;
        }
      }
    }
  }
}

?>
