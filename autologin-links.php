<?php
/*
Plugin Name: Autologin Links
Plugin URI: https://www.craftware.info/projects-lists/wordpress-autologin/
Description: Lets administrators generate autologin links for users.
Author: Paul Konstantin Gerke
Version: 1.08
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

define('PKG_AUTOLOGIN_VALUE_NAME', 'autologin_code');
define('PKG_AUTOLOGIN_USER_META_KEY', 'pkg_autologin_code');

define('PKG_AUTOLOGIN_LANGUAGE_DOMAIN', 'pkg_autologin');

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
 * @param $parameters
 *   The parameters to join together to form the GET-request url part
 * @return
 *   The formed get-request string
 */
function pkg_autologin_join_get_parameters($parameters) {
  $keys = array_keys($parameters);
  $assignments = array();
  foreach ($keys as $key) {
    $assignments[] = "$key=$parameters[$key]";
    
  }
  return implode('&', $assignments);
}

/**
 * This function reads the user_id from the page request ($_GET by default) 
 * thats page is being edited and validates that it is a correct user id.
 * The id is retrieved via the 'user_id' key in the $parameterArray (defaults
 * to the $_GET array).
 * 
 * @param $parameterArray
 *   Allows to override the default of using the $_GET-array as source for
 *   reading the 'user_id' field.
 * @return
 *   (boolean) False if the user_id could not be read from $_GET or derived 
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
 * @return
 *   (string) The reassembled $_GET-query string specified in the URL. Will
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
        
        // Query login codes
        $loginCodeQuery = $wpdb->prepare("SELECT user_id, meta_value as login_code FROM $wpdb->usermeta WHERE meta_value = '%s';", $autologin_code); // $autologin_code has been heavily cleaned before
        
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
      
      // Added error texts
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
      wp_enqueue_script('pkg_autologin_client_script', plugins_url('autologin-client.js',__FILE__));
      if (pkg_autologin_check_modify_permissions($user_id)) { 
        wp_enqueue_script('pkg_autologin_admin_script', plugins_url('autologin-admin.js',__FILE__), array("pkg_autologin_client_script"));
      }
    }
  }
}

// Add autologin links to user pages and corresponding update elements to admin pages
add_action('personal_options_update', 'pkg_autologin_update_link');
add_action('edit_user_profile_update', 'pkg_autologin_update_link');
function pkg_autologin_update_link() {
  $user_id = pkg_autologin_get_page_user_id($_POST); // Get data from POST array
  if (!$user_id) {
    wp_die(__('Invalid user ID.'));
  }

  if (array_key_exists('pkg_autologin_code', $_POST)) { // Check if code should be updated
    $cleanedKey = False;
    if ($_POST['pkg_autologin_code']) {
      $cleanedKey = preg_replace('/[^a-zA-Z0-9]+/', '', $_POST['pkg_autologin_code']);
      if (strlen($cleanedKey) != 30) {
        wp_die(__('Invalid autologin code.', PKG_AUTOLOGIN_LANGUAGE_DOMAIN));
      } 
    }
    
    if (check_admin_referer('update-user_' . $user_id)) { // Check nonce - not validated before in user-edit.php :-(
      if (pkg_autologin_check_modify_permissions($user_id)) {
        if ($cleanedKey) {
          if (!add_user_meta($user_id, PKG_AUTOLOGIN_USER_META_KEY, $cleanedKey, True)) {
            if (!update_user_meta($user_id, PKG_AUTOLOGIN_USER_META_KEY, $cleanedKey)) {
              // Check if the key was changed at all - if not this is an error of update_user_meta
              if (get_user_meta($user_id, PKG_AUTOLOGIN_USER_META_KEY, True) != $cleanedKey) {
                wp_die(__('Failed to update autologin link.', PKG_AUTOLOGIN_LANGUAGE_DOMAIN));
              }
            }
          }
        } else {
          if (get_user_meta($user_id, PKG_AUTOLOGIN_USER_META_KEY, True)) {
            if (!delete_user_meta($user_id, PKG_AUTOLOGIN_USER_META_KEY)) {
              wp_die(__('Failed to delete autologin link.', PKG_AUTOLOGIN_LANGUAGE_DOMAIN));
            }
          }
        }
      } else {
        wp_die(__( 'You do not have permission to edit this user.' )); // Use general error message - Perhaps better use special one like "you may not change the autologin link" ?
      }
    } else {
      wp_die("YOU SHOULD NOT GET HERE BECAUSE EXECUTION SHOULD HAVE DIED - However, and you may not do this!");
    }
  }
}

add_action('show_user_profile', 'pkg_autologin_plugin_add_extra_profile_fields');
add_action('edit_user_profile', 'pkg_autologin_plugin_add_extra_profile_fields');
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
      function addControlButtons($current_link_code) { // Controls for generating new links or deleting old ones only available to admins
        $prefix = home_url('?' . PKG_AUTOLOGIN_VALUE_NAME . '=');
        ?>
        <input type="hidden" autocomplete="off" id="pkg_autologin_code" name="pkg_autologin_code" value="<?php echo $current_link_code; ?>" />
        <input type="button" value="<?php _e("New", PKG_AUTOLOGIN_LANGUAGE_DOMAIN); ?>" id="pkg_autologin_new_link_button" onclick="pkg_autologin_new_link_click(this, <?php echo "'$prefix'"; ?>)" />
        <input type="button" value="<?php _e("Delete", PKG_AUTOLOGIN_LANGUAGE_DOMAIN); ?>" id="pkg_autologin_delete_link_button" onclick="pkg_autologin_delete_link_click(this)" />
        <?php
      }
      ?>
<h3><?php _e("Auto-login", PKG_AUTOLOGIN_LANGUAGE_DOMAIN);?></h3>
<table class="form-table">
  <tbody>
    <tr>
      <th><label for="pkg_autologin_link"><?php _e("Auto-login link", PKG_AUTOLOGIN_LANGUAGE_DOMAIN); ?></label></th>
      <td>
        <p id="pkg_autologin_link"><i id="pkg_autologin_unsaved_marker" style="display:none">[<?php _e("Unsaved", PKG_AUTOLOGIN_LANGUAGE_DOMAIN);?>] </i><?php echo ($current_link_code ? home_url('?' . PKG_AUTOLOGIN_VALUE_NAME . "=$current_link_code") : "-"); ?></p>
        <?php if (pkg_autologin_check_modify_permissions()) { addControlButtons($current_link_code); } else { ?>
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
          'meta_compare' => 'EXISTS') );
      
      if (count($autologin_link_users) == 0) {
        // No uses can use autologin links, show verbose message
        $title = __('No users with autologin codes', PKG_AUTOLOGIN_LANGUAGE_DOMAIN);
        $wp_admin_bar->add_menu( array(
          'parent' => 'pkg-generate-auto-login-link-menu',
          'id'     => 'pkg-generate-auto-login-link-menu-nousers',
          'title'  => $title
        ));
      } else {
        // Get the target website address parts
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

        function fuse_url_with_site_url($url) {
          // fuses a wordpress site postfix with the siteurl to
          // build a complete url finding the largest overlap between the urls
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
    
        // Now generate menu items with autologin codes for each user
        $i = 0;
        foreach ($autologin_link_users as $user) {
          $autologin_key = get_user_meta($user->ID, PKG_AUTOLOGIN_USER_META_KEY, True);
        
          $url = $targetPage . $GETQueryPrefix . PKG_AUTOLOGIN_VALUE_NAME . "=" . $autologin_key;
          $htmlUserName = esc_html($user->first_name) . " " . esc_html($user->last_name) . " (" . esc_html($user->user_login) . ")";
          $title = __("Login link for", PKG_AUTOLOGIN_LANGUAGE_DOMAIN) . " " . $htmlUserName;
          
          $onclick_url = fuse_url_with_site_url($url);
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
