<?php
require($_ENV['TEST_FRAMEWORK']);

$error = activate_plugin("autologin-links/autologin-links.php");
if ($error !== null) {
  fail_test("Cannot activate plugin: " . $error->get_error_message() . "\n");
}

finish_test();

?>
