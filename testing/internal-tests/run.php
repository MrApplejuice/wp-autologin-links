<?php
require('wp-load.php');
require('wp-admin/includes/plugin.php');

echo "Enabling plugin...\n";
$error = activate_plugin("autologin-links/autologin-links.php");
if ($error !== null) {
  die("Cannot activate plugin: " . $error->get_error_message() . "\n");
}
?>