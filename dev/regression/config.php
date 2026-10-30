<?php
// Only synthetic credentials. Never load a host/private config.php.
if (getenv('TEST_ENV') !== 'codeception' || getenv('DB_HOST') !== 'db' || getenv('DB_NAME') !== 'regression') {
    throw new RuntimeException('Refusing non-sandbox configuration');
}
$databaseServer = 'db';
$databaseName = 'regression';
$databaseUsername = 'regression';
$databasePassword = 'synthetic-local-only';
$guid = 'regression-local-only';
$caching = 1;
$testEnvironment = 'codeception';
$testPath = '/var/www/html';
$testURL = getenv('ABSOLUTE_URL');
