<?php
if (PHP_SAPI !== 'cli') throw new RuntimeException('CLI only');
echo is_file('/staged/.FUNCTIONAL_STAGE_COMPLETE') && is_file('/staged/SANDBOX_COMMIT')
    ? trim(file_get_contents('/staged/SANDBOX_COMMIT'))
    : 'incomplete';
