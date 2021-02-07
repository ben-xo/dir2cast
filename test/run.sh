#!/bin/bash
set -e

SCRIPT_DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )

cd "$SCRIPT_DIR"

tests=($@)
if [[ "$tests" == "" ]]; then
	tests=('.')
fi

phpunit --bootstrap bootstrap.php $tests
