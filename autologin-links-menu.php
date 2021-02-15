<?php
require_once "autologin-links-admin-bar.php";

add_action('admin_menu', 'pkg_autologin_define_menu');
function pkg_autologin_define_menu() {
  add_options_page(
    'Autologin-links',
    'Autologin-links',
    'manage_options',
    'pkg_autologin_admin_menu',
    'pkg_autologin_options_menu');
}

function pkg_autologin_options_menu() {
  $adminbar_enabled = pkg_autologin_is_admin_bar_enabled();
  if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!check_admin_referer("pkg_autologin_options")) {
      wp_die("Invalid request");
    }

    $adminbar_enabled = isset($_POST["pkg-autologin-options-enable-admin-bar-enable"]) && ($_POST["pkg-autologin-options-enable-admin-bar-enable"] === "on");
    update_option("pkg_autologin_admin_bar_enabled",$adminbar_enabled ? 1 : 0);
    ?>
  <div class="notice notice-success is-dismissible">
    <p>Changes saved</p>
  </div>
    <?php
  }

  ?>
    <div class="wrap">
      <h1 class="wp-heading-inline">Autologin link options</h1>

      <form method="POST">
        <?php wp_nonce_field("pkg_autologin_options"); ?>
        <table class="form-table">
          <tbody>
            <tr>
              <th>Max retries lockout:</th>
              <td>
                <input name="pkg-autologin-options-lockout-count" type="number" value="20" />
                <p>
                  Number of allowed retries from a single IP until that IP is locked out for given amount of time.
                </p>
              </td>
            </tr>
            <tr>
              <th>Retry lockout timout:</th>
              <td>
                <input name="pkg-autologin-options-lockout-minutes" type="number" value="10" />
                <p>
                  Number of minutes until the lockout for a given IP address resets.
                </p>
              </td>
            </tr>
            <tr>
              <th>Admin bar</th>
              <td>
                <input name="pkg-autologin-options-enable-admin-bar-enable" id="pkg-autologin-options-enable-admin-bar-enable" type="checkbox" <?php if ($adminbar_enabled) { echo 'checked="checked"'; } ?> />
                <label for="pkg-autologin-options-enable-admin-bar-enable">Show</label>
              </td>
            </tr>
          </tbody>
        </table>
        <p class="submit">
          <input id="submit" class="button button-primary" name="submit" type="submit" value="Save changes" />
        </p>
      </form>
    </div>
  <?php
}

?>