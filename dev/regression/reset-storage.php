<?php
if (PHP_SAPI !== 'cli' || getenv('TEST_ENV') !== 'codeception' || getenv('DB_HOST') !== 'db' || !is_file('/var/www/html/SANDBOX_COMMIT')) throw new RuntimeException('Not sandbox');
foreach (['uploads','var','resources/templates/cache','tests/log'] as $relative) {
    $root='/var/www/html/'.$relative;
    $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $file) {
        if ($file->getFilename()==='.htaccess') continue;
        if ($file->isLink()) unlink($file->getPathname());
        elseif ($file->isDir()) rmdir($file->getPathname());
        else unlink($file->getPathname());
    }
}
foreach (['var/sessions','var/tmp'] as $dir) { mkdir('/var/www/html/'.$dir,0770,true); chown('/var/www/html/'.$dir,33); chgrp('/var/www/html/'.$dir,33); }
echo "Sandbox storage cleared\n";
