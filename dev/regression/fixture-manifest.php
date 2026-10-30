<?php
if (PHP_SAPI !== 'cli' || getenv('TEST_ENV') !== 'codeception' || getenv('DB_HOST') !== 'db' || getenv('DB_NAME') !== 'regression') throw new RuntimeException('Not sandbox');
$pdo=new PDO('mysql:host=db;dbname=regression','regression','synthetic-local-only',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$manifest=[];
foreach (['gibbonRole','gibbonPerson','gibbonStaff','gibbonSchoolYear','gibbonYearGroup','gibbonFormGroup','gibbonStudentEnrolment','gibbonAttendanceLogPerson','gibbonFamily','gibbonFamilyAdult','gibbonFamilyChild','gibbonCustomField'] as $table) {
    $rows=$pdo->query('SELECT * FROM `'.$table.'` ORDER BY 1')->fetchAll(PDO::FETCH_ASSOC);
    $ddl=$pdo->query('SHOW CREATE TABLE `'.$table.'`')->fetch(PDO::FETCH_NUM)[1];
    $manifest[$table]=['rows'=>count($rows),'data'=>hash('sha256',json_encode($rows)),'schema'=>hash('sha256',$ddl)];
}
echo json_encode($manifest,JSON_UNESCAPED_SLASHES)."\n";
