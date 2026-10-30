<?php
// Shared guards for destructive fixtures and direct DB assertions. Never load private config.
function regressionPDO(): PDO
{
    if (getenv('REGRESSION_DISPOSABLE') !== 'functional-tests' || getenv('DB_HOST') !== 'db' || getenv('DB_NAME') !== 'regression' || getenv('TEST_ENV') !== 'codeception') {
        throw new RuntimeException('Disposable functional test environment required');
    }
    return new PDO('mysql:host=db;dbname=regression;charset=utf8', 'regression', 'synthetic-local-only', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}

function fixtureInsert(PDO $pdo, string $table, array $row): void
{
    if (!preg_match('/^gibbon[A-Za-z0-9]+$/', $table)) throw new RuntimeException('Invalid fixture table');
    foreach ($pdo->query('SHOW COLUMNS FROM `'.$table.'`') as $column) {
        $name = $column['Field'];
        if (array_key_exists($name, $row) || $column['Null'] === 'YES' || $column['Default'] !== null || strpos($column['Extra'], 'auto_increment') !== false) continue;
        if (preg_match("/^enum\('([^']*)'/", $column['Type'], $m)) $row[$name] = $m[1];
        elseif (preg_match('/int|decimal|float|double/', $column['Type'])) $row[$name] = 0;
        else $row[$name] = '';
    }
    $columns = array_keys($row);
    $pdo->prepare('INSERT INTO `'.$table.'` (`'.implode('`,`', $columns).'`) VALUES ('.implode(',', array_fill(0, count($columns), '?')).')')->execute(array_values($row));
}
