#!/bin/bash
set -e

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
vendor/bin/phpunit --bootstrap "$SCRIPT_DIR/bootstrap.php" --coverage-php /tmp/cov-main --coverage-filter ../dir2cast.php ${tests[@]}
php combine_coverage.php
rm -rf testdir /tmp/cov-*
