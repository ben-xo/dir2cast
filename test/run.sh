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

if [[ ! -e vendor ]]; then
	echo "Error: Make sure you have php, composer and xdebug installed, then do 'composer install'"
	echo "On a typical MacOS that would be the following commands:"
	echo "  brew install php composer"
	echo "  pecl install xdebug"
	echo "  composer install"
	exit 1
fi

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

vendor/bin/phpunit \
    --colors=always \
    --bootstrap "$SCRIPT_DIR/bootstrap.php" \
    --coverage-php /tmp/cov-main \
    --coverage-filter ../dir2cast.php \
    $PATH_COVERAGE_OPTION \
    ${tests[@]}

php combine_coverage.php
rm -rf testdir /tmp/cov-*
