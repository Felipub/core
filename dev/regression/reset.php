<?php
// Infrastructure fixture loader, not a functional test. CLI only, local DB only.
if (PHP_SAPI !== 'cli' || getenv('DB_HOST') !== 'db' || getenv('DB_NAME') !== 'regression' || getenv('TEST_ENV') !== 'codeception') {
    throw new RuntimeException('Refusing reset outside sandbox');
}
$pdo = new PDO('mysql:host=db;dbname=regression;charset=utf8', 'regression', 'synthetic-local-only', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec("SET SESSION sql_mode='NO_ENGINE_SUBSTITUTION'");
$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
    if (!preg_match('/^gibbon[A-Za-z0-9]+$/', $table)) throw new RuntimeException('Unexpected table');
    $pdo->exec('DROP TABLE `'.$table.'`');
}
// Import DDL and reviewed application metadata only. Never import demo or personal data.
$metadata = ['gibbonAction','gibbonAlertLevel','gibbonAttendanceCode','gibbonCountry','gibbonDaysOfWeek','gibbonEmailTemplate','gibbonExternalAssessment','gibbonExternalAssessmentField','gibbonFileExtension','gibbonFinanceFeeCategory','gibboni18n','gibbonINDescriptor','gibbonLanguage','gibbonLibraryType','gibbonMedicalCondition','gibbonModule','gibbonNotificationEvent','gibbonPermission','gibbonPersonalDocumentType','gibbonReportingCriteriaType','gibbonRole','gibbonScale','gibbonScaleGrade','gibbonSetting','gibbonStaffAbsenceType','gibbonStudentNoteCategory','gibbonTheme','gibbonUsernameFormat'];
$sql = gzdecode(file_get_contents('/var/www/html/gibbon.sql.gz'));
foreach (preg_split('/;\s*\r?\n/', $sql) as $statement) {
    $statement = preg_replace('/^--[^\n]*(\n|$)/m', '', $statement);
    $statement = trim($statement);
    if (preg_match('/^(CREATE TABLE|ALTER TABLE) `gibbon[A-Za-z0-9]+`/', $statement)) {
        $pdo->exec($statement);
    } elseif (preg_match('/^INSERT INTO `([^`]+)`/', $statement, $m) && in_array($m[1], $metadata, true)) {
        $pdo->exec($statement);
    }
}
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');
function insertRow(PDO $pdo, string $table, array $row): void {
    // Explicitly populate required columns without defaults; don't depend on MySQL coercion.
    foreach ($pdo->query('SHOW COLUMNS FROM `'.$table.'`') as $column) {
        $name = $column['Field'];
        if (array_key_exists($name, $row) || $column['Null'] === 'YES' || $column['Default'] !== null || strpos($column['Extra'], 'auto_increment') !== false) continue;
        $type = $column['Type'];
        if (preg_match("/^enum\('([^']*)'/", $type, $m)) $row[$name] = $m[1];
        elseif (preg_match('/int|decimal|float|double/', $type)) $row[$name] = 0;
        else $row[$name] = '';
    }
    $keys = array_keys($row);
    $pdo->prepare('INSERT INTO `'.$table.'` (`'.implode('`,`', $keys).'`) VALUES ('.implode(',', array_fill(0, count($keys), '?')).')')->execute(array_values($row));
}
// Validate core role IDs rather than assigning roles by an unchecked number.
foreach ([1=>'Administrator',2=>'Teacher',3=>'Student',4=>'Parent',6=>'Support Staff'] as $id=>$name) {
    $q=$pdo->prepare('SELECT name FROM gibbonRole WHERE gibbonRoleID=?'); $q->execute([$id]);
    if ($q->fetchColumn() !== $name) throw new RuntimeException('Role ID mismatch: '.$id.' expected '.$name);
}
foreach ([1=>['2025-2026','Past','2025-04-01','2026-03-31'],2=>['2026-2027','Current','2026-04-01','2027-03-31']] as $id=>$year) {
    insertRow($pdo,'gibbonSchoolYear', ['gibbonSchoolYearID'=>$id,'name'=>$year[0],'status'=>$year[1],'sequenceNumber'=>$id,'firstDay'=>$year[2],'lastDay'=>$year[3]]);
}
insertRow($pdo,'gibbonYearGroup',['gibbonYearGroupID'=>1,'name'=>'Beginner','nameShort'=>'Beg','sequenceNumber'=>1]);
foreach ([1=>['admin',1],2=>['teacher',2],3=>['staff',6],4=>['student',3],5=>['parent',4],6=>['leftstudent',3]] as $id=>$person) {
    $salt='synthetic-fixture-salt-'.$id;
    insertRow($pdo,'gibbonPerson',['gibbonPersonID'=>$id,'username'=>$person[0], 'firstName'=>ucfirst($person[0]),'preferredName'=>ucfirst($person[0]),'surname'=>'Synthetic','officialName'=>ucfirst($person[0]).' Synthetic','passwordStrongSalt'=>$salt,'passwordStrong'=>hash('sha256',$salt.'Sandbox-Only-2026!'),'status'=>$id===6?'Left':'Full','canLogin'=>$id===6?'N':'Y','gibbonRoleIDPrimary'=>$person[1],'gibbonRoleIDAll'=>str_pad($person[1],3,'0',STR_PAD_LEFT),'email'=>$person[0].'@example.invalid','dob'=>$person[1]===3?'2015-01-15':'1985-01-15','dateStart'=>'2025-04-01','dateEnd'=>$id===6?'2026-06-30':null,'receiveNotificationEmails'=>'N','fields'=>'{}']);
    if (in_array($id,[1,2,3])) insertRow($pdo,'gibbonStaff',['gibbonPersonID'=>$id,'type'=>$id===3?'Support':'Teaching']);
}
foreach ([1=>['Open Active','Y'],2=>['Open Empty','Y'],3=>['Example Closed','N'],4=>['Past Centre','N']] as $id=>$centre) {
    insertRow($pdo,'gibbonFormGroup',['gibbonFormGroupID'=>$id,'gibbonSchoolYearID'=>$id===4?1:2,'name'=>$centre[0],'nameShort'=>'C'.$id,'attendance'=>$centre[1],'gibbonPersonIDTutor'=>2]);
}
foreach ([[4,2,1],[6,2,3],[4,1,4]] as $enrolment) insertRow($pdo,'gibbonStudentEnrolment',['gibbonPersonID'=>$enrolment[0],'gibbonSchoolYearID'=>$enrolment[1],'gibbonYearGroupID'=>1,'gibbonFormGroupID'=>$enrolment[2]]);
insertRow($pdo,'gibbonFamily',['gibbonFamilyID'=>1,'name'=>'Synthetic Family','nameAddress'=>'Synthetic Family','status'=>'Other']);
insertRow($pdo,'gibbonFamilyAdult',['gibbonFamilyID'=>1,'gibbonPersonID'=>5,'childDataAccess'=>'Y','contactCall'=>'N','contactSMS'=>'N','contactEmail'=>'N','contactMail'=>'N']);
insertRow($pdo,'gibbonFamilyChild',['gibbonFamilyID'=>1,'gibbonPersonID'=>4]);
$code=$pdo->query("SELECT gibbonAttendanceCodeID FROM gibbonAttendanceCode WHERE direction='In' ORDER BY gibbonAttendanceCodeID LIMIT 1")->fetchColumn();
if (!$code) throw new RuntimeException('No In attendance code');
insertRow($pdo,'gibbonAttendanceLogPerson',['gibbonAttendanceCodeID'=>$code,'gibbonPersonID'=>4,'direction'=>'In','type'=>'Present','context'=>'Form Group','date'=>'2026-10-01','gibbonPersonIDTaker'=>2,'gibbonFormGroupID'=>1,'timestampTaken'=>'2026-10-01 09:00:00']);
$fields = json_decode(file_get_contents('/harness/custom-fields.json'),true);
foreach ($fields as $id=>$field) {
    insertRow($pdo,'gibbonCustomField',['gibbonCustomFieldID'=>(int)$id,'context'=>'User','name'=>$field[0],'type'=>$field[1],'options'=>$field[2],'heading'=>'Synthetic fixture','sequenceNumber'=>(int)$id,'activePersonStudent'=>1,'activePersonParent'=>in_array((int)$id,[6,23])?1:0,'activeApplicationForm'=>1]);
    $q=$pdo->prepare('SELECT name FROM gibbonCustomField WHERE gibbonCustomFieldID=?'); $q->execute([(int)$id]);
    if ($q->fetchColumn()!==$field[0]) throw new RuntimeException('Custom field mismatch '.$id);
}
// Remove outbound credentials/endpoints from defaults before configuring local operation.
$pdo->exec("UPDATE gibbonSetting SET value='' WHERE LOWER(name) REGEXP '(password|secret|token|apikey|sms|oauth|google|ftp|smtp)' ");
$settings=['absoluteURL'=>getenv('ABSOLUTE_URL'),'absolutePath'=>'/var/www/html','installType'=>'Testing','organisationName'=>'Synthetic Regression School','organisationNameShort'=>'SYN','organisationEmail'=>'school@example.invalid','systemName'=>'Core Sandbox','timezone'=>'UTC','country'=>'India','statsCollection'=>'N','cuttingEdgeCode'=>'N','gibbonSchoolYearID'=>'002','enableMailerSMTP'=>'N'];
foreach ($settings as $name=>$value) $pdo->prepare('UPDATE gibbonSetting SET value=? WHERE name=?')->execute([$value,$name]);
echo "Synthetic fixture reset complete; NOT a functional validation.\n";
