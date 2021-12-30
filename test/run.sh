#!/bin/bash
set -e

# Test runner.
#
# Usage:
#
# ./test/run.sh                    # runs all tests
# ./test/run.sh FilenameTest.php   # runs only that test
# PATH_COVERAGE=yes ./test/run.sh  # adds more (but slower) coverage reporting

SCRIPT_DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )

cd "$SCRIPT_DIR"

tests=($@)
if [[ "${tests[@]}" == "" ]]; then
	tests=('.')
fi

export XDEBUG_MODE=coverage
rm -f /tmp/cov-*

export PATH_COVERAGE=${PATH_COVERAGE:-}
PATH_COVERAGE_OPTION=""

if [[ "$PATH_COVERAGE" != '' ]]; then
	PATH_COVERAGE_OPTION="--path-coverage"
fi

../vendor/bin/phpunit \
    --bootstrap "$SCRIPT_DIR/bootstrap.php" \
    --coverage-php /tmp/cov-main \
    --coverage-filter ../dir2cast.php \
    $PATH_COVERAGE_OPTION \
    ${tests[@]}

php combine_coverage.php
rm -rf testdir /tmp/cov-*
