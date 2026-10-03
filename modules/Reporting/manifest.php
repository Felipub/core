<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

//Basica variables
$name="Reporting" ;
$description="A gibbon module to allow teachers to write reports, and for students and parents to view them.  generic version" ;
$entryURL="index.php" ;
$type="Additional" ;
$category="Assess" ;
$version="2.25" ;
$author="Andy Statham" ;
$url="http://rapid36.com" ;

//Module tables
$moduleTables[0] = "
    CREATE TABLE `arrArchive` (
    `archiveID` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `studentID` int(10) unsigned NOT NULL,
    `reportID` int(10) unsigned NOT NULL,
    `reportName` varchar(255) NOT NULL,
    `created` datetime NOT NULL,
    `firstDate` datetime NOT NULL,
    `lastDate` datetime NOT NULL,
    `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`archiveID`),
    UNIQUE KEY `studentID` (`studentID`,`reportID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

$moduleTables[1] = "
    CREATE TABLE `arrCriteria` (
    `criteriaID` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `subjectID` int(10) unsigned zerofill NOT NULL,
    `schoolYearID` int(3) unsigned zerofill NOT NULL,
    `yearGroupID` int(3) unsigned zerofill NOT NULL,
    `criteriaName` varchar(255) NOT NULL,
    `criteriaType` tinyint(4) DEFAULT '0',
    `gradeScaleID` int(10) unsigned NOT NULL,
    `criteriaOrder` tinyint(3) unsigned NOT NULL,
    `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`criteriaID`),
    UNIQUE KEY `criteriaName` (`subjectID`,`criteriaName`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

$moduleTables[2] = "
    CREATE TABLE `arrReport` (
    reportID int(10) unsigned NOT NULL AUTO_INCREMENT,
    schoolYearID int(3) unsigned zerofill NOT NULL,
    reportName varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
    reportNum tinyint(3) unsigned NOT NULL DEFAULT '1',
    reportOrder tinyint(4) DEFAULT NULL,
    orientation tinyint(4) unsigned NOT NULL DEFAULT '1',
    gradeScale int(10) unsigned DEFAULT NULL,
    `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
   PRIMARY KEY (reportID),
   UNIQUE KEY schoolYearID (schoolYearID,reportName),
   KEY reportNum (reportNum)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
    
$moduleTables[3] = "
    CREATE TABLE `arrReportAssign` (
  `reportAssignID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `schoolYearID` int(3) unsigned zerofill NOT NULL,
  `yearGroupID` int(3) unsigned zerofill NOT NULL,
  `reportID` int(10) NOT NULL,
  `assignStatus` tinyint(1) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`reportAssignID`),
  UNIQUE KEY `yearGroupID` (`yearGroupID`,`reportID`,`schoolYearID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

$moduleTables[4] = "
    CREATE TABLE arrReportGrade (
  reportGradeID int(10) unsigned NOT NULL AUTO_INCREMENT,
  reportID int(10) unsigned DEFAULT NULL,
  criteriaID int(10) unsigned NOT NULL,
  studentID int(10) unsigned NOT NULL,
  gradeID varchar(10) NOT NULL,
  mark float NOT NULL,
  percent float NOT NULL,
  PRIMARY KEY (reportGradeID),
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY criteriaID (studentID, criteriaID, reportID)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

$moduleTables[5] = "
    CREATE TABLE `arrReportSection` (
    `sectionID` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `reportID` int(10) unsigned DEFAULT NULL,
    `sectionType` int(10) unsigned DEFAULT NULL,
    `sectionOrder` int(10) unsigned DEFAULT NULL,
    `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`sectionID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

$moduleTables[6] = "
    CREATE TABLE `arrReportSectionDetail` (
  `reportSectionDetailID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sectionID` int(10) unsigned DEFAULT NULL,
  `sectionContent` text,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`reportSectionDetailID`),
  UNIQUE KEY `sectionID` (`sectionID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

$moduleTables[7] = "
    CREATE TABLE `arrReportSectionType` (
  `reportSectionTypeID` int(11) NOT NULL AUTO_INCREMENT,
  `sectionTypeName` varchar(45) DEFAULT NULL,
    `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`reportSectionTypeID`),
    UNIQUE KEY sectionTypeName (sectionTypeName)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
INSERT INTO `arrReportSectionType` (`reportSectionTypeID`, `sectionTypeName`) VALUES
        (1, 'Text'),
        (2, 'Subject (row)'),
        (3, 'Subject (column)'),
        (4, 'Pastoral'),
        (5, 'Page Break');";

$moduleTables[8] = "
    CREATE TABLE `arrReportSubject` (
    `reportSubjectID` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `studentID` int(10) unsigned zerofill NOT NULL,
    `subjectID` int(10) unsigned NOT NULL,
    `classID` int(8) unsigned zerofill NOT NULL,
    `reportID` int(10) unsigned NOT NULL,
    `subjectComment` text,
    `teacherID` int(10) unsigned DEFAULT NULL,
    `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`reportSubjectID`),
    UNIQUE KEY `arrPersonID` (`studentID`,`reportID`,`subjectID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

$moduleTables[9] = "
    CREATE TABLE `arrStatus` (
    `statusID` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `reportID` int(10) NOT NULL,
    `roleID` int(3) unsigned zerofill NOT NULL,
    `reportStatus` tinyint(4) NOT NULL,
    `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`statusID`),
    UNIQUE KEY `reportID` (`reportID`,`roleID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

$moduleTables[10] = "CREATE TABLE `arrSubjectOrder` (
  `subjectOrderID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `subjectID` INT(10) UNSIGNED NOT NULL,
  `schoolYearID` INT(10) UNSIGNED NOT NULL,
  `yearGroupID` int(10) unsigned NOT NULL,
  `subjectOrder` TINYINT(4) UNSIGNED NOT NULL,
  PRIMARY KEY (`subjectOrderID`),
  UNIQUE KEY `subjectYear` (`subjectID`,`schoolYearID`,`yearGroupID`));";


//Action rows
// for admin and SLT only
$actionRows[0]["name"]="Administration" ;
$actionRows[0]["precedence"]="0";
$actionRows[0]["category"]="ARR" ;
$actionRows[0]["description"]="Manage housekeeping functions" ;
$actionRows[0]["URLList"]="index.php, admin.php, admin_access.php, admin_assign.php, admin_complete.php, admin_criteria.php, admin_define.php, admin_design.php, admin_startyear.php, admin_suborder.php" ;
$actionRows[0]["entryURL"]="admin.php" ;
$actionRows[0]["defaultPermissionAdmin"]="Y" ;
$actionRows[0]["defaultPermissionTeacher"]="N" ;
$actionRows[0]["defaultPermissionStudent"]="N" ;
$actionRows[0]["defaultPermissionParent"]="N" ;
$actionRows[0]["defaultPermissionPublic"]="N" ;
$actionRows[0]["defaultPermissionSupport"]="Y" ;
$actionRows[0]["categoryPermissionStaff"]="Y" ;
$actionRows[0]["categoryPermissionStudent"]="N" ;
$actionRows[0]["categoryPermissionParent"]="N" ;
$actionRows[0]["categoryPermissionOther"]="N" ;

$actionRows[1]["name"]="Archive" ;
$actionRows[1]["precedence"]="0";
$actionRows[1]["category"]="ARR" ;
$actionRows[1]["description"]="Find past reports" ;
$actionRows[1]["URLList"]="index.php, archive.php, archive_search.php" ;
$actionRows[1]["entryURL"]="archive.php" ;
$actionRows[1]["defaultPermissionAdmin"]="Y" ;
$actionRows[1]["defaultPermissionTeacher"]="Y" ;
$actionRows[1]["defaultPermissionStudent"]="N" ;
$actionRows[1]["defaultPermissionParent"]="N" ;
$actionRows[1]["defaultPermissionPublic"]="N" ;
$actionRows[1]["defaultPermissionSupport"]="Y" ;
$actionRows[1]["categoryPermissionStaff"]="Y" ;
$actionRows[1]["categoryPermissionStudent"]="N" ;
$actionRows[1]["categoryPermissionParent"]="N" ;
$actionRows[1]["categoryPermissionOther"]="N" ;

$actionRows[2]["name"]="PDF Creation" ;
$actionRows[2]["precedence"]="0";
$actionRows[2]["category"]="ARR" ;
$actionRows[2]["description"]="Create PDF reports" ;
$actionRows[2]["URLList"]="index.php, pdf.php, pdf_create.php" ;
$actionRows[2]["entryURL"]="pdf.php" ;
$actionRows[2]["defaultPermissionAdmin"]="Y" ;
$actionRows[2]["defaultPermissionTeacher"]="N" ;
$actionRows[2]["defaultPermissionStudent"]="N" ;
$actionRows[2]["defaultPermissionParent"]="N" ;
$actionRows[2]["defaultPermissionPublic"]="N" ;
$actionRows[2]["defaultPermissionSupport"]="Y" ;
$actionRows[2]["categoryPermissionStaff"]="Y" ;
$actionRows[2]["categoryPermissionStudent"]="N" ;
$actionRows[2]["categoryPermissionParent"]="N" ;
$actionRows[2]["categoryPermissionOther"]="N" ;

$actionRows[3]["name"]="PDF Mail" ;
$actionRows[3]["precedence"]="0";
$actionRows[3]["category"]="ARR" ;
$actionRows[3]["description"]="Send reports by email" ;
$actionRows[3]["URLList"]="index.php, pdfmail.php, pdfmail_send.php" ;
$actionRows[3]["entryURL"]="pdfmail.php" ;
$actionRows[3]["defaultPermissionAdmin"]="Y" ;
$actionRows[3]["defaultPermissionTeacher"]="N" ;
$actionRows[3]["defaultPermissionStudent"]="N" ;
$actionRows[3]["defaultPermissionParent"]="N" ;
$actionRows[3]["defaultPermissionPublic"]="N" ;
$actionRows[3]["defaultPermissionSupport"]="Y" ;
$actionRows[3]["categoryPermissionStaff"]="N" ;
$actionRows[3]["categoryPermissionStudent"]="N" ;
$actionRows[3]["categoryPermissionParent"]="N" ;
$actionRows[3]["categoryPermissionOther"]="N" ;

$actionRows[4]["name"]="Proof Reading" ;
$actionRows[4]["precedence"]="0";
$actionRows[4]["category"]="ARR" ;
$actionRows[4]["description"]="Check and amend reports" ;
$actionRows[4]["URLList"]="index.php, proof.php" ;
$actionRows[4]["entryURL"]="proof.php" ;
$actionRows[4]["defaultPermissionAdmin"]="Y" ;
$actionRows[4]["defaultPermissionTeacher"]="Y" ;
$actionRows[4]["defaultPermissionStudent"]="N" ;
$actionRows[4]["defaultPermissionParent"]="N" ;
$actionRows[4]["defaultPermissionPublic"]="N" ;
$actionRows[4]["defaultPermissionSupport"]="Y" ;
$actionRows[4]["categoryPermissionStaff"]="Y" ;
$actionRows[4]["categoryPermissionStudent"]="N" ;
$actionRows[4]["categoryPermissionParent"]="N" ;
$actionRows[4]["categoryPermissionOther"]="N" ;

// pastoral reports
$actionRows[5]["name"]="Reports - Pastoral" ;
$actionRows[5]["precedence"]="0";
$actionRows[5]["category"]="ARR" ;
$actionRows[5]["description"]="Pastoral reports" ;
$actionRows[5]["URLList"]="index.php, pastoral.php" ;
$actionRows[5]["entryURL"]="pastoral.php" ;
$actionRows[5]["defaultPermissionAdmin"]="Y" ;
$actionRows[5]["defaultPermissionTeacher"]="Y" ;
$actionRows[5]["defaultPermissionStudent"]="N" ;
$actionRows[5]["defaultPermissionParent"]="N" ;
$actionRows[5]["defaultPermissionPublic"]="N" ;
$actionRows[5]["defaultPermissionSupport"]="N" ;
$actionRows[5]["categoryPermissionStaff"]="Y" ;
$actionRows[5]["categoryPermissionStudent"]="N" ;
$actionRows[5]["categoryPermissionParent"]="N" ;
$actionRows[5]["categoryPermissionOther"]="N" ;

$actionRows[6]["name"]="Reports - Subject" ;
$actionRows[6]["precedence"]="0";
$actionRows[6]["category"]="ARR" ;
$actionRows[6]["description"]="Write subject reports" ;
$actionRows[6]["URLList"]="index.php, subject.php" ;
$actionRows[6]["entryURL"]="subject.php" ;
$actionRows[6]["defaultPermissionAdmin"]="Y" ;
$actionRows[6]["defaultPermissionTeacher"]="Y" ;
$actionRows[6]["defaultPermissionStudent"]="N" ;
$actionRows[6]["defaultPermissionParent"]="N" ;
$actionRows[6]["defaultPermissionPublic"]="N" ;
$actionRows[6]["defaultPermissionSupport"]="Y" ;
$actionRows[6]["categoryPermissionStaff"]="Y" ;
$actionRows[6]["categoryPermissionStudent"]="N" ;
$actionRows[6]["categoryPermissionParent"]="N" ;
$actionRows[6]["categoryPermissionOther"]="N" ;