<?php
$sql=array();
$count=0;
$sql[$count][0]="1.00" ; // version number
$sql[$count][1]="" ; // sql statements
$count++;
$sql[$count][0]="1.01" ; // version number
$sql[$count][1]="" ; // sql statements
$count++;
$sql[$count][0]="1.02" ; // version number
$sql[$count][1]="" ; // sql statements
$count++;
$sql[$count][0]="1.03" ; // version number
$sql[$count][1]="" ; // sql statements
$count++;
$sql[$count][0]="1.04" ; // version number
$sql[$count][1]="" ; // sql statements
$count++;
$sql[$count][0]="1.05" ; // version number
$sql[$count][1]="
    ALTER TABLE arrReport DROP INDEX schoolYearID;
    ALTER TABLE arrReport ADD UNIQUE( schoolYearID, reportName);
";
$count++;
$sql[$count][0]="1.06" ; // version number
$sql[$count][1]="
    INSERT INTO `arrReportSectionType` (`reportSectionTypeID`, `sectionTypeName`) VALUES
        (1, 'Text'),
        (2, 'Subject'),
        (3, 'Pastoral'),
        (4, 'Page Break');";
$count++;
$sql[$count][0]="1.08" ; // version number
$sql[$count][1]="" ; // sql statements

$count++;
$sql[$count][0]="1.09" ; // version number
$sql[$count][1]="" ; // sql statements

$count++;
$sql[$count][0]="1.10" ; // version number
$sql[$count][1]="" ; // sql statements

$count++;
$sql[$count][0]="1.11" ; // version number
$sql[$count][1]="" ; // sql statements

$count++;
$sql[$count][0]="1.12" ; // version number
$sql[$count][1]="" ; // sql statements

$count++;
$sql[$count][0]="1.13" ; // version number
$sql[$count][1]="ALTER TABLE arrCriteria
ADD UNIQUE INDEX `criteriaName` (`subjectID` ASC, `criteriaName` ASC);" ; // sql statements

++$count;
$sql[$count][0] = '1.14';
$sql[$count][1] = "ALTER TABLE arrReport
ADD COLUMN orientation TINYINT(4) UNSIGNED NOT NULL DEFAULT 1 AFTER reportOrder;
INSERT IGNORE INTO gibbonAction
SET gibbonAction.gibbonModuleID = 
(
    SELECT gibbonModule.gibbonModuleID
    FROM gibbonModule
	WHERE gibbonModule.name = 'Reporting'
),
gibbonAction.name = 'PDF Mail',
gibbonAction.precedence = 0,
gibbonAction.category = 'ARR',
gibbonAction.description = 'Email PDF report to parents',
gibbonAction.URLList = 'pdfmail.php',
gibbonAction.entryURL = 'pdfmail.php',
gibbonAction.entrySidebar = 'Y',
gibbonAction.menuShow = 'Y',
gibbonAction.defaultPermissionAdmin = 'Y',
gibbonAction.defaultPermissionTeacher = 'N',
gibbonAction.defaultPermissionStudent = 'N',
gibbonAction.defaultPermissionParent = 'N',
gibbonAction.defaultPermissionSupport = 'Y';

INSERT IGNORE INTO gibbonAction
SET gibbonAction.gibbonModuleID = 
(
    SELECT gibbonModule.gibbonModuleID
    FROM gibbonModule
	WHERE gibbonModule.name = 'Reporting'
),
gibbonAction.name = 'Parent',
gibbonAction.precedence = 0,
gibbonAction.category = 'ARR',
gibbonAction.description = 'Parent login section',
gibbonAction.URLList = 'parent.php',
gibbonAction.entryURL = 'parent.php',
gibbonAction.entrySidebar = 'Y',
gibbonAction.menuShow = 'Y',
gibbonAction.defaultPermissionAdmin = 'N',
gibbonAction.defaultPermissionTeacher = 'Y',
gibbonAction.defaultPermissionStudent = 'N',
gibbonAction.defaultPermissionParent = 'N',
gibbonAction.defaultPermissionSupport = 'N';

ALTER TABLE arrReportGrade
ADD COLUMN mark FLOAT NOT NULL AFTER timestamp,
ADD COLUMN percent FLOAT NOT NULL AFTER mark;
";

$count++;
$sql[$count][0]="1.15" ; // version number
$sql[$count][1]="" ; // sql statements

$count++;
$sql[$count][0]="1.16" ; // version number
$sql[$count][1]="" ; // sql statements

$count++;
$sql[$count][0]="1.17" ; // version number
$sql[$count][1]="" ; // sql statements

$count++;
$sql[$count][0]="1.18" ; // version number
$sql[$count][1]="" ; // sql statements

$count++;
$sql[$count][0]="1.19" ; // version number
$sql[$count][1]="" ; // sql statements

$count++;
$sql[$count][0]="1.20" ; // version number
$sql[$count][1]="" ; // sql statements

$count++;
$sql[$count][0]="1.21" ; // version number
$sql[$count][1]="
ALTER TABLE `arrCriteria` 
ADD COLUMN `gradeScaleID` INT(10) UNSIGNED NOT NULL AFTER `criteriaName`;
ALTER TABLE `arrCriteria` 
ADD COLUMN `criteriaType` TINYINT(4) UNSIGNED NOT NULL DEFAULT 0 AFTER `criteriaName`;
ALTER TABLE `arrCriteria` 
ADD COLUMN `schoolYearID` INT(10) UNSIGNED NOT NULL AFTER `subjectID`,
ADD COLUMN `arrCriteriacol` VARCHAR(45) NULL AFTER `timestamp`;
ALTER TABLE `arrCriteria` 
ADD COLUMN `yearGroupID` INT(10) UNSIGNED NOT NULL AFTER `schoolYearID`;";

$count++;
$sql[$count][0]="1.22" ; // version number
$sql[$count][1]=""; 

$count++;
$sql[$count][0]="1.23" ; // version number
$sql[$count][1]=""; 

$count++;
$sql[$count][0]="1.24" ; // version number
$sql[$count][1]="ALTER TABLE `arrReportGrade` 
DROP INDEX `criteriaID` ,
ADD UNIQUE INDEX `criteriaID` (`studentID` ASC, `criteriaID` ASC, `reportID` ASC);";

$count++;
$sql[$count][0]="2.00" ; // version number
$sql[$count][1]=""; 

$count++;
$sql[$count][0]="2.01" ; // version number
$sql[$count][1]="
ALTER TABLE `arrReportGrade` 
CHANGE COLUMN `gradeID` `gradeID` VARCHAR(10) NOT NULL ;";

$count++;
$sql[$count][0]="2.02" ; // version number
$sql[$count][1]=""; 

$count++;
$sql[$count][0]="2.03" ; // version number
$sql[$count][1]=""; 

$count++;
$sql[$count][0]="2.05" ; // version number
$sql[$count][1]=""; 

$count++;
$sql[$count][0]="2.06" ; // version number
$sql[$count][1]=""; 

$count++;
$sql[$count][0]="2.07" ; // version number
$sql[$count][1]="
CREATE TABLE IF NOT EXISTS arrSubjectOrder (
  subjectOrderID int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  subjectID int(10) UNSIGNED NOT NULL,
  schoolYearID int(10) UNSIGNED NOT NULL,
  yearGroupID int(10) UNSIGNED NOT NULL,
  subjectOrder tinyint(4) UNSIGNED NOT NULL,
  PRIMARY KEY (subjectOrderID),
  UNIQUE KEY subjectYear (subjectID,schoolYearID,yearGroupID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
UPDATE gibbonAction
SET gibbonAction.URLlist = CONCAT(gibbonAction.URLlist, ', admin_suborder.php')
WHERE gibbonAction.gibbonModuleID = 
(
    SELECT gibbonModule.gibbonModuleID
    FROM gibbonModule
    WHERE gibbonModule.name = 'Reporting'
)
AND gibbonAction.name = 'Administration'";

$count++;
$sql[$count][0]="2.08" ; // version number
$sql[$count][1]=""; 

$count++;
$sql[$count][0]="2.09" ; // version number
$sql[$count][1]=""; 

$count++;
$sql[$count][0]="2.10" ; // version number
$sql[$count][1]=""; 

$count++;
$sql[$count][0]="2.11" ; // version number
$sql[$count][1]=""; 

$count++;
$sql[$count][0]="2.12" ; // version number
$sql[$count][1]=""; 

$count++;
$sql[$count][0]="2.13" ; // version number
$sql[$count][1]=""; 

$count++;
$sql[$count][0]="2.14" ; // version number
$sql[$count][1]=""; 

$count++;
$sql[$count][0]="2.15" ; // version number
$sql[$count][1]="
    UPDATE gibbonAction
INNER JOIN gibbonModule
ON gibbonModule.gibbonModuleID = gibbonAction.gibbonModuleID
SET URLList = 'index.php, admin.php, admin_access.php, admin_assign.php, admin_complete.php, admin_criteria.php, admin_define.php, admin_design.php, admin_startyear.php, admin_suborder.php'
WHERE gibbonModule.name = 'Reporting'
AND gibbonAction.name = 'Administration'";

$count++;
$sql[$count][0]="2.16" ; // version number
$sql[$count][1]=""; 

$count++;
$sql[$count][0]="2.17" ; // version number
$sql[$count][1]=""; 

$count++;
$sql[$count][0]="2.18" ; // version number
$sql[$count][1]=""; 

$count++;
$sql[$count][0]="2.19" ; // version number
$sql[$count][1]=""; 

$count++;
$sql[$count][0]="2.20" ; // version number
$sql[$count][1]=""; 

$count++;
$sql[$count][0]="2.21" ; // version number
$sql[$count][1]="ALTER TABLE `arrReportSection` ADD UNIQUE( `reportID`, `sectionOrder`);"; 

$count++;
$sql[$count][0]="2.22" ; // version number
$sql[$count][1]=""; 

$count++;
$sql[$count][0]="2.23" ; // version number
$sql[$count][1]=""; 

$count++;
$sql[$count][0]="2.24" ; // version number
$sql[$count][1]=""; 