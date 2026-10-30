<?php
require_once __DIR__.'/sandbox.php';
$pdo = regressionPDO();
// Installation SQL omits two deployed definitions. Reviewed DDL only; no production rows.
$ddl=preg_replace('/^--[^\n]*(\n|$)/m','',file_get_contents(__DIR__.'/deployed-schema.sql'));
foreach (explode(';',$ddl) as $statement) {
    $statement=trim($statement);
    if (!$statement) continue;
    if (!preg_match('/^(ALTER TABLE `gibbonApplicationForm` ADD COLUMN `religion`|CREATE TABLE `gibbonStudentEnrolmentChanged`)/',$statement)) throw new RuntimeException('Unexpected schema fixture statement');
    $pdo->exec($statement);
}
$today = gmdate('Y-m-d');
$yesterday = gmdate('Y-m-d', strtotime('-1 day'));
$tomorrow = gmdate('Y-m-d', strtotime('+1 day'));
$pdo->exec("UPDATE gibbonPerson SET canLogin='N' WHERE gibbonRoleIDPrimary IN (3,4)");
$pdo->exec("UPDATE gibbonPerson SET gender='F', dateEnd=NULL WHERE gibbonPersonID=4");
$pdo->prepare("UPDATE gibbonPerson SET gender='M', departureReason='Dropout', dateEnd=? WHERE gibbonPersonID=6")->execute([$yesterday]);
$pdo->prepare("UPDATE gibbonSchoolYear SET firstDay=?,lastDay=? WHERE gibbonSchoolYearID=2")->execute([gmdate('Y-m-d', strtotime('-365 days')),gmdate('Y-m-d', strtotime('+365 days'))]);
fixtureInsert($pdo,'gibbonPerson',['gibbonPersonID'=>7,'username'=>'teacher2','firstName'=>'Second','preferredName'=>'Second','surname'=>'Teacher','officialName'=>'Second Teacher','status'=>'Full','canLogin'=>'Y','gibbonRoleIDPrimary'=>2,'gibbonRoleIDAll'=>'002','passwordStrongSalt'=>'fixture-teacher2','passwordStrong'=>hash('sha256','fixture-teacher2Sandbox-Only-2026!'),'email'=>'teacher2@example.invalid','dateStart'=>'2020-01-01','fields'=>'{}','receiveNotificationEmails'=>'N']);
fixtureInsert($pdo,'gibbonStaff',['gibbonPersonID'=>7,'type'=>'Teaching']);
fixtureInsert($pdo,'gibbonYearGroup',['gibbonYearGroupID'=>2,'name'=>'Beginner Entry','nameShort'=>'BE','sequenceNumber'=>2]);
$pdo->exec("UPDATE gibbonFormGroup SET gibbonPersonIDTutor=NULL");
$pdo->exec("UPDATE gibbonFormGroup SET gibbonPersonIDTutor=2 WHERE gibbonFormGroupID=1");
$pdo->exec("UPDATE gibbonFormGroup SET gibbonPersonIDTutor=7 WHERE gibbonFormGroupID IN (2,4)");
// Exercise the existing myGroups permission explicitly; installer defaults grant all groups.
$pdo->exec("DELETE p FROM gibbonPermission p JOIN gibbonAction a ON a.gibbonActionID=p.gibbonActionID WHERE p.gibbonRoleID=2 AND a.name='Attendance By Form Group_all'");
$pdo->exec("INSERT INTO gibbonPermission (gibbonRoleID,gibbonActionID) SELECT 2,gibbonActionID FROM gibbonAction WHERE name='Attendance By Form Group_myGroups'");
$people = [
    10=>['Active Boy','M','Full','2020-01-01',null,'',2],
    11=>['Future Girl','F','Full',$tomorrow,null,'',2],
    12=>['Ended Girl','F','Full','2020-01-01',$yesterday,'',2],
    13=>['Boundary Boy','M','Full',$today,$today,'',1],
    14=>['Passout Girl','F','Left','2020-01-01',$yesterday,'Passout',3],
    15=>['Transitioned Boy','M','Left','2020-01-01',$yesterday,'Transitioned',3],
    16=>['Migrated Boy','M','Left','2020-01-01',$yesterday,'Migrated',3],
    17=>['Unenrolled Girl','F','Full','2020-01-01',null,'',null],
    18=>['Past Girl','F','Full','2020-01-01',null,'',4],
];
foreach ($people as $id=>$p) {
    fixtureInsert($pdo,'gibbonPerson',['gibbonPersonID'=>$id,'username'=>'pupil'.$id,'firstName'=>$p[0],'preferredName'=>$p[0],'surname'=>'Fixture','officialName'=>$p[0].' Fixture','gender'=>$p[1],'status'=>$p[2],'canLogin'=>'N','gibbonRoleIDPrimary'=>3,'gibbonRoleIDAll'=>'003','dateStart'=>$p[3],'dateEnd'=>$p[4],'departureReason'=>$p[5],'dob'=>'2015-01-15','email'=>'pupil'.$id.'@example.invalid','fields'=>'{}','receiveNotificationEmails'=>'N']);
    if ($p[6] !== null) fixtureInsert($pdo,'gibbonStudentEnrolment',['gibbonPersonID'=>$id,'gibbonSchoolYearID'=>$id===18?1:2,'gibbonYearGroupID'=>1,'gibbonFormGroupID'=>$p[6]]);
}
fixtureInsert($pdo,'gibbonFamily',['gibbonFamilyID'=>2,'name'=>'Control Family','nameAddress'=>'Control Family','status'=>'Other']);
fixtureInsert($pdo,'gibbonFamilyChild',['gibbonFamilyID'=>2,'gibbonPersonID'=>10]);
fixtureInsert($pdo,'gibbonSchoolYearTerm',['gibbonSchoolYearID'=>2,'name'=>'Fixture term','nameShort'=>'FT','sequenceNumber'=>1,'firstDay'=>gmdate('Y-m-d', strtotime('-365 days')),'lastDay'=>gmdate('Y-m-d', strtotime('+365 days'))]);
$pdo->exec("UPDATE gibbonDaysOfWeek SET schoolDay='Y'");
$pdo->exec('DELETE FROM gibbonAttendanceLogPerson');
$code=$pdo->query("SELECT gibbonAttendanceCodeID FROM gibbonAttendanceCode WHERE name='Present' LIMIT 1")->fetchColumn();
if (!$code) throw new RuntimeException('Present attendance code missing');
foreach ([4,10] as $id) fixtureInsert($pdo,'gibbonAttendanceLogPerson',['gibbonAttendanceCodeID'=>$code,'gibbonPersonID'=>$id,'direction'=>'In','type'=>'Present','context'=>'Form Group','date'=>$today,'gibbonPersonIDTaker'=>2,'gibbonFormGroupID'=>$id===4?1:2,'timestampTaken'=>gmdate('Y-m-d H:i:s',time()-60)]);
// Fixture settings are explicit; no credentials, external transports, subjects or class planning.
$settings=['departureReasons'=>'Passout,Transitioned,Dropout,Migrated','publicApplications'=>'Y','enablePayments'=>'N','financeOnlinePaymentEnabled'=>'N'];
foreach ($settings as $name=>$value) $pdo->prepare('UPDATE gibbonSetting SET value=? WHERE name=?')->execute([$value,$name]);
file_put_contents('/var/www/html/var/functional-fixture.json',json_encode(['today'=>$today,'active'=>[4,10,13],'version'=>1]));
echo "Functional fixtures loaded ($today).\n";
