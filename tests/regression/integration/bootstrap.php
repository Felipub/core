<?php
require_once '/regression-tests/data/sandbox.php';
$fixturePDO = regressionPDO();
if ($fixturePDO->query("SELECT value FROM gibbonSetting WHERE name='installType'")->fetchColumn() !== 'Testing') throw new RuntimeException('Testing database required');
if (!is_file('/var/www/html/var/functional-fixture.json')) throw new RuntimeException('Run functional reset first');
require_once '/var/www/html/tests/bootstrap.php';
