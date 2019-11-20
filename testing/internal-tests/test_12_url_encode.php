<?php
require($_ENV['TEST_FRAMEWORK']);

$_GET = array(
    "autologin_code" => "abc"
);

$postfix = pkg_autologin_generate_get_postfix();
echo "test postfix 1 = '$postfix'\n";
test_assert(strlen($postfix) === 0, "get query string should be empty but is not: '$postfix'");

$_GET = array(
    "autologin_code" => "abc",
    "testarg" => "a b"
);

$postfix = pkg_autologin_generate_get_postfix();
echo "test postfix 2 = '$postfix'\n";
test_assert(strpos($postfix, "?") !== FALSE, "questionmark not at first location: '$postfix'");
test_assert(strpos($postfix, "%20") > 0, "spaces were not encoded: '$postfix'");

finish_test();
?>
