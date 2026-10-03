<?php
class par {
    
    function parInit($guid, $connection2) {
        $this->dbh = $connection2;
        $this->personID = $_SESSION[$guid]['gibbonPersonID'];
        $this->schoolYearID = $_SESSION[$guid]["gibbonSchoolYearIDCurrent"];
        // read list of students associated with parent
        $this->childList = $this->readChildList();
    }
    
    function mainform($guid, $connection2) {
        // show list of students and links to archived reports
        $lastStud = 0;
        $path = "./archive/reporting/";
        while ($row = $this->childList->fetch()) {
            if ($row['gibbonPersonID'] != $lastStud) {
                echo "<p><strong>".$row['studentName'].' ('.$row['rollGroupName'].")</strong></p>";
                $lastStud = $row['gibbonPersonID'];
            }
            echo "<p>";
                if ($row['archiveName'] != null) {
                    $folder = substr($row['archiveName'],0,9).'/';
                    echo "<a href='".$path.$folder.$row['archiveName']."' target='_blank'>";
                        echo $row['reportName'];
                    echo "</a>";
                } else {
                    echo "No reports";
                }
            echo "</p>";
        }
    }
    
    function readChildList() {
        // read list of children for this parent
        try {
            $data = array(
                'gibbonPersonID' => $this->personID,
                'schoolYearID' => $this->schoolYearID
            );
            $sql = "SELECT gibbonPerson.gibbonPersonID,
                CONCAT(gibbonPerson.surname, gibbonPerson.preferredName) AS studentName,
                gibbonFormGroup.name AS rollGroupName,
                archive.archiveName,
                archive.reportName
                FROM gibbonFamilyChild
                INNER JOIN gibbonFamilyAdult
                ON gibbonFamilyAdult.gibbonFamilyID = gibbonFamilyChild.gibbonFamilyID
                INNER JOIN gibbonPerson
                ON gibbonPerson.gibbonPersonID = gibbonFamilyChild.gibbonPersonID
                INNER JOIN gibbonStudentEnrolment
                ON gibbonStudentEnrolment.gibbonPersonID = gibbonPerson.gibbonPersonID
                INNER JOIN gibbonFormGroup
                ON gibbonFormGroup.gibbonFormGroupID = gibbonStudentEnrolment.gibbonFormGroupID
                LEFT JOIN 
                (
                    SELECT arrArchive.studentID, 
                    arrArchive.reportName AS archiveName, 
                    arrReport.reportName AS reportName,
                    arrReport.reportNum
                    FROM arrArchive
                    INNER JOIN arrReport
                    ON arrReport.reportID = arrArchive.reportID
                ) AS archive
                ON archive.studentID = gibbonPerson.gibbonPersonID
                WHERE gibbonFamilyAdult.gibbonPersonID = :gibbonPersonID
                AND gibbonStudentEnrolment.gibbonSchoolYearID = :schoolYearID
                ORDER BY rollGroupName, studentName, reportNum DESC";
            $rs = $this->dbh->prepare($sql);
            $rs->execute($data);
            return $rs;
        } catch (Exception $ex) {
            die($ex);
        }            
    }
}