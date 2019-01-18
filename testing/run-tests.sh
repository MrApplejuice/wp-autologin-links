#!/bin/bash

prefix=$( tr -d '.' <<< ${prefix:-testing} )

echo "Installing internal test code..."
docker exec -it ${prefix}_wordpress_1  rm -rf /tmp/internal-tests || true
docker cp internal-tests ${prefix}_wordpress_1:/tmp/internal-tests

tests=$( ls $(dirname $(readlink -e $0))/internal-tests/test_*.php | xargs -r -n1 basename | sort )
any_failed=false

echo
echo "== TESTS =="
for test in $tests ; do
  echo "... running $test"

  docker exec -it -e TEST_FRAMEWORK=/tmp/internal-tests/test-framework.php \
      ${prefix}_wordpress_1 php /tmp/internal-tests/$test

  error_code=$?
  echo

  if [ $error_code -eq 21 ] ; then
    echo "SUCCEEDED - $test"
  else
    echo "FAILED - exit_code=$error_code (must be 21) - $test"
    any_failed=true
  fi
done
echo "== ALL TESTS WERE RUN =="
echo

if $any_failed ; then
  echo "Some tests failed"
  exit 1
fi
