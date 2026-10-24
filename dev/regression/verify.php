<?php
if (getenv('TEST_ENV') !== 'codeception' || getenv('DB_HOST') !== 'db') throw new RuntimeException('Not sandbox');
require '/var/www/html/config.php';
if (session_name() !== 'CORE_'.getenv('SANDBOX_INSTANCE')) throw new RuntimeException('Session cookie isolation failed');
$pdo=new PDO('mysql:host=db;dbname=regression','regression','synthetic-local-only',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
if ($pdo->query("SELECT value FROM gibbonSetting WHERE name='installType'")->fetchColumn()!=='Testing') throw new RuntimeException('Not Testing');
foreach (['curl','mbstring','gettext','PDO','pdo_mysql','gd','zip','intl','soap','bcmath','dom','xml','xmlwriter','fileinfo'] as $extension) if (!extension_loaded($extension)) throw new RuntimeException('Missing '.$extension);
foreach (['uploads','var/sessions','var/tmp','resources/templates/cache','tests/log'] as $path) if (!is_writable('/var/www/html/'.$path)) throw new RuntimeException('Not writable '.$path);
foreach (['admin','teacher','staff','student','parent'] as $username) {
    $q=$pdo->prepare('SELECT * FROM gibbonPerson WHERE username=?'); $q->execute([$username]); $p=$q->fetch(PDO::FETCH_ASSOC);
    if (!$p || !hash_equals($p['passwordStrong'],hash('sha256',$p['passwordStrongSalt'].'Sandbox-Only-2026!'))) throw new RuntimeException('Fixture credential mismatch');
}
passthru('composer check-platform-reqs', $result); if ($result) exit($result);
passthru('vendor/bin/phpunit --version',$result); if ($result) exit($result);
passthru('vendor/bin/phpunit --configuration /harness/phpunit.xml --list-suites',$result); if ($result) exit($result);
passthru('vendor/bin/codecept --version',$result); if ($result) exit($result);
chdir('/var/www/html/tests');
passthru('../vendor/bin/codecept build', $result); if ($result) exit($result);
echo "Infrastructure checks passed; browser login still checked separately.\n";
