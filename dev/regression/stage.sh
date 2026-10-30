#!/bin/sh
set -eu
case "$EXPECTED_COMMIT" in *[!a-f0-9]*|'') echo 'Invalid commit' >&2; exit 1;; esac
cd /var/www/html
test ! -e .FUNCTIONAL_STAGE_COMPLETE
tar -xf /snapshot.tar -C /var/www/html \
    --exclude=.vscode --exclude='*/error_log' --exclude=error_log \
    --exclude=mysqltosheets --exclude=backup_script.sh \
    --exclude='*.sql*' --exclude='*.csv' --exclude='*.xlsx'
tar -xf /schema.tar -C /var/www/html
cp /harness/config.php config.php
cp /harness/RegressionTester.php tests/config/support/RegressionTester.php
mkdir -p var/sessions var/tmp uploads resources/templates/cache tests/log
printf '%s\n' "$EXPECTED_COMMIT" > SANDBOX_COMMIT
