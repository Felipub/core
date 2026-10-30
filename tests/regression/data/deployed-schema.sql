-- DDL ONLY: missing deployed schema, extracted from the local 2026-10-23 backup.
-- No INSERTs, data rows, credentials, triggers or routines.
-- Source SHA256: 89df00849c1d055be15e4b22316bccac6943396c9035235809ce3c2068050556
ALTER TABLE `gibbonApplicationForm` ADD COLUMN `religion` varchar(30) CHARACTER SET utf16 NOT NULL;
CREATE TABLE `gibbonStudentEnrolmentChanged` (
  `gibbonStudentEnrolmentChangedID` int(8) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `gibbonStudentEnrolmentID` int(8) unsigned zerofill NOT NULL,
  `gibbonPersonID` int(10) unsigned zerofill NOT NULL,
  `gibbonSchoolYearID` int(3) unsigned zerofill NOT NULL,
  `gibbonYearGroupID` int(3) unsigned zerofill NOT NULL,
  `gibbonFormGroupID` int(5) unsigned zerofill NOT NULL,
  `rollOrder` int(2) DEFAULT NULL,
  `dateChanged` date DEFAULT NULL,
  PRIMARY KEY (`gibbonStudentEnrolmentChangedID`),
  KEY `gibbonSchoolYearID` (`gibbonSchoolYearID`),
  KEY `gibbonYearGroupID` (`gibbonYearGroupID`),
  KEY `gibbonPersonIndex` (`gibbonPersonID`,`gibbonSchoolYearID`),
  KEY `gibbonFormGroupID` (`gibbonFormGroupID`),
  KEY `gibbonStudentEnrolmentID` (`gibbonStudentEnrolmentID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
