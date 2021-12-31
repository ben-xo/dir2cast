#!/bin/bash
set -e

SCRIPT_DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )

cd "$SCRIPT_DIR"

if [[ ! -e vendor ]]; then
	composer install ||	(echo "Error: Make sure you have php, composer and xdebug installed."; exit 1)
fi

tests=($@)
if [[ "${tests[@]}" == "" ]]; then
	tests=('.')
fi

export XDEBUG_MODE=coverage
rm -f /tmp/cov-*
vendor/bin/phpunit --bootstrap "$SCRIPT_DIR/bootstrap.php" --coverage-php /tmp/cov-main --coverage-filter ../dir2cast.php ${tests[@]}
php combine_coverage.php
rm -rf testdir /tmp/cov-*
