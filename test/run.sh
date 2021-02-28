#!/bin/bash
set -e

SCRIPT_DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )

cd "$SCRIPT_DIR"

tests=($@)
if [[ "${tests[@]}" == "" ]]; then
	tests=('.')
fi

export XDEBUG_MODE=coverage
rm -f /tmp/cov-*
../vendor/bin/phpunit --bootstrap "$SCRIPT_DIR/bootstrap.php" --coverage-php /tmp/cov-main --coverage-filter ../dir2cast.php ${tests[@]}
php combine_coverage.php
rm -rf testdir /tmp/cov-*
