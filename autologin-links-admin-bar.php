<?php
/* ADMIN BAR - GENERATE LINK FUNCTIONS */

/**
 * Check whether the admin bar is enabled or not.
 */
function pkg_autologin_is_admin_bar_enabled() {
  return get_option(PKG_AUTOLOGIN_OPTION_ADMIN_BAR_ENABLE, "1") === "1";
}

add_action('wp_enqueue_scripts', 'pkg_autologin_load_autologin_show_link_scripts');
function pkg_autologin_load_autologin_show_link_scripts() {
  if (pkg_autologin_check_modify_permissions() && pkg_autologin_is_admin_bar_enabled()) {
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
  if (!pkg_autologin_is_admin_bar_enabled()) return;
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