<?php
// Private fixture sidecar only: no mount in application webroot and no published port.
require_once '/regression-tests/data/sandbox.php';
header('Content-Type: application/json');
try {
    $pdo=regressionPDO();
    $path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
    if ($path==='/reset' && $_SERVER['REQUEST_METHOD']==='POST') {
        // Import DDL once, then restore only mutable fixture tables for each independent case.
        $baselineFile='/var/www/html/var/functional-baseline.json';
        $signature=hash('sha256',gmdate('Y-m-d').file_get_contents(__FILE__).file_get_contents('/harness/reset.php').file_get_contents('/regression-tests/data/seed.php').file_get_contents('/regression-tests/data/deployed-schema.sql').file_get_contents('/var/www/html/SANDBOX_COMMIT'));
        $baseline=is_file($baselineFile)?json_decode(file_get_contents($baselineFile),true):null;
        if (!$baseline || $baseline['signature']!==$signature) {
            ob_start();
            require '/harness/reset.php';
            require '/regression-tests/data/seed.php';
            ob_end_clean();
            $baseline=['signature'=>$signature,'tables'=>[]];
            foreach (['gibbonPerson','gibbonStaff','gibbonStudentEnrolment','gibbonStudentEnrolmentChanged','gibbonFamily','gibbonFamilyAdult','gibbonFamilyChild','gibbonFamilyRelationship','gibbonFinanceInvoicee','gibbonUsernameFormat','gibbonApplicationForm','gibbonApplicationFormLink','gibbonApplicationFormRelationship','gibbonAttendanceLogPerson','gibbonAttendanceLogFormGroup','gibbonStudentNote','gibbonNotification','gibbonSetting','gibbonTheme','gibbonPersonMedical','gibbonPersonalDocument'] as $table) {
                $baseline['tables'][$table]=$pdo->query('SELECT * FROM `'.$table.'`')->fetchAll(PDO::FETCH_ASSOC);
            }
            file_put_contents($baselineFile,json_encode($baseline));
        } else {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
            $pdo->beginTransaction();
            foreach ($baseline['tables'] as $table=>$rows) {
                if (!preg_match('/^gibbon[A-Za-z0-9]+$/',$table)) throw new RuntimeException('Invalid baseline table');
                $pdo->exec('DELETE FROM `'.$table.'`');
                if (!$rows) continue;
                $columns=array_keys($rows[0]);
                $insert=$pdo->prepare('INSERT INTO `'.$table.'` (`'.implode('`,`',$columns).'`) VALUES ('.implode(',',array_fill(0,count($columns),'?')).')');
                foreach ($rows as $row) $insert->execute(array_values($row));
            }
            $pdo->commit();
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        }
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator('/var/www/html/uploads',FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST) as $file) {
            if ($file->getFilename()==='.htaccess') continue;
            if ($file->isDir() && !$file->isLink()) rmdir($file->getPathname()); else unlink($file->getPathname());
        }
        echo json_encode(['ready'=>true]);
    } elseif ($path==='/workbook' && $_SERVER['REQUEST_METHOD']==='POST') {
        $file=$_FILES['file']??null;
        if (!$file || $file['error']!==UPLOAD_ERR_OK || $file['size']>2000000 || !is_uploaded_file($file['tmp_name'])) throw new RuntimeException('Small synthetic workbook upload required');
        require_once '/var/www/html/vendor/autoload.php';
        $reader=new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setReadDataOnly(true);
        echo json_encode($reader->load($file['tmp_name'])->getActiveSheet()->toArray());
    } elseif ($path==='/candidate' && $_SERVER['REQUEST_METHOD']==='POST') {
        fixtureInsert($pdo,'gibbonApplicationForm',['gibbonApplicationFormID'=>100,'gibbonApplicationFormHash'=>md5('synthetic-application-100'),'status'=>'Pending','firstName'=>'Review Synthetic','preferredName'=>'Review Synthetic','surname'=>'Fixture','officialName'=>'Review Synthetic Fixture','gender'=>'F','dob'=>'2015-01-15','dateStart'=>gmdate('Y-m-d'),'gibbonSchoolYearIDEntry'=>2,'gibbonYearGroupIDEntry'=>1,'gibbonFormGroupID'=>1,'gibbonFamilyID'=>1,'parent1gibbonPersonID'=>5,'parent1relationship'=>'Mother','homeAddress'=>'123 Synthetic Street','homeAddressDistrict'=>'Synthetic District','homeAddressCountry'=>'India','sen'=>'N','payment'=>'Family','fields'=>'{}','parent1fields'=>'{}','parent2fields'=>'{}']);
        echo json_encode(['id'=>100]);
    } elseif ($path==='/state' && $_SERVER['REQUEST_METHOD']==='GET') {
        $state=[];
        foreach (['gibbonPerson','gibbonStudentEnrolment','gibbonStudentEnrolmentChanged','gibbonFamily','gibbonFamilyAdult','gibbonFamilyChild','gibbonApplicationForm','gibbonAttendanceLogPerson','gibbonStudentNote'] as $table) {
            $rows=$pdo->query('SELECT * FROM `'.$table.'` ORDER BY 1')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as &$row) foreach (array_keys($row) as $key) if (preg_match('/password|token|secret/i',$key)) unset($row[$key]);
            unset($row);
            $state[$table]=$rows;
        }
        $state['fixture']=json_decode(file_get_contents('/var/www/html/var/functional-fixture.json'),true);
        $state['uploads']=[];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator('/var/www/html/uploads',FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->isFile() && !$file->isLink() && $file->getFilename()!=='.htaccess') $state['uploads'][substr($file->getPathname(),strlen('/var/www/html/'))]=['bytes'=>$file->getSize(),'sha256'=>hash_file('sha256',$file->getPathname())];
        }
        echo json_encode($state);
    } elseif ($path==='/theme' && $_SERVER['REQUEST_METHOD']==='POST') {
        $name=$_GET['name']??'';
        if (!in_array($name,['Default','Gyansetu'],true)) throw new RuntimeException('Unknown fixture theme');
        $q=$pdo->prepare('SELECT gibbonThemeID FROM gibbonTheme WHERE name=?'); $q->execute([$name]);
        $id=$q->fetchColumn();
        if (!$id) {
            fixtureInsert($pdo,'gibbonTheme',['name'=>$name,'active'=>'Y']);
            $id=$pdo->lastInsertId();
        }
        $pdo->prepare("UPDATE gibbonTheme SET active=IF(gibbonThemeID=?,'Y','N')")->execute([$id]);
        $pdo->prepare('UPDATE gibbonPerson SET gibbonThemeIDPersonal=?')->execute([$id]);
        echo json_encode(['theme'=>$name]);
    } else { http_response_code(404); echo json_encode(['error'=>'Unknown fixture operation']); }
} catch (Throwable $e) {
    if (ob_get_level()) ob_end_clean();
    http_response_code(500);
    echo json_encode(['error'=>$e->getMessage()]);
}
