<?php

/*
 * Project:
 * Author:   Andy Statham
 * Date:
 */
class startyear {
    var $msg;
    var $class;

    function startyearInit($guid, $dbh) {
        $this->guid = $guid;
        $this->dbh = $dbh;
        
        //$this->previousYear = $this->get_schoolYearPrevious($this->dbh);
        $this->copyToYear = isset($_POST['copyToYear']) ? $_POST['copyToYear'] : $_SESSION[$guid]['gibbonSchoolYearID'];
        $this->copyFromYear = isset($_POST['copyFromYear']) ? $_POST['copyFromYear'] : $_SESSION[$guid]['gibbonSchoolYearID'];
        $this->schoolYearID = getSchoolYearID($this->dbh, $schoolYearName, $currentYear);
        if (isset($_POST['copyreportsubmit'])) {
            $this->copyData();
        }
    }
    ////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////
    function mainform() {
        $yearList = readSchoolYearList($this->dbh);
        echo "<form name='copyreport' id='copyreport' method='post' action=''>";
            //echo "<input type='hidden' name='schoolYearID' value='$this->schoolYearID' />";
            echo "<p>This will copy data from last year and will include:</p>";
            echo "<ul>";
            echo "<li>Report templates</li>";
            echo "<li>Assign reports to current year groups</li>";
            echo "<li>Setup details for each template</li>";
            echo "<li>Subject criteria</li>";
            echo "</ul>";
            echo "<p>If you run this feature it will not overwrite existing data</p>";
            echo "<p>When you are ready click on the button below</p>";
            
            echo "<div>";
                echo "Copy from ";
                echo "<select name='copyFromYear' id='copyFromYear'>";
                    $yearList->execute();
                    while ($row = $yearList->fetch()) {
                        $selected = ($row['gibbonSchoolYearID'] == $this->copyFromYear) ? "selected" : "";
                        echo "<option $selected value='".$row['gibbonSchoolYearID']."'>".$row['name']."</option>";
                    }
                echo "</select>";

                echo "&nbsp;&nbsp;Copy to ";
                echo "<select name='copyToYear' id='copyToYear'>";
                    $yearList->execute();
                    while ($row = $yearList->fetch()) {
                        $selected = ($row['gibbonSchoolYearID'] == $this->copyToYear) ? "selected" : "";
                        echo "<option $selected value='".$row['gibbonSchoolYearID']."'>".$row['name']."</option>";
                    }
                echo "</select>";
            echo "</div>";
            echo "<input type='submit' name='copyreportsubmit' value='Go' />";
            //echo "<p class='highlight'>Not yet ready to use</p>";
            //echo "<input type='submit' name='copyreportsubmit' value='Copy' />";
        echo "</form>";
        
        ?>
        <script>
            $('#copyreport').submit(function(e) {
                var copyFromYear = $('#copyFromYear').val();
                var copyToYear = $('#copyToYear').val();
                if (copyFromYear === copyToYear) {
                    alert("Years must be different");
                    e.preventDefault();
                }
                if (copyFromYear === 0 && copyToYear === 0) {
                    alert("Please select from and to years");
                    e.preventDefault();
                }
            });
        </script>
        <?php
    }
    ////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////
    function copyData() {
        // reports
        $ok = $this->copyReport();
        $msg = '';
        
        if ($ok) {
            $msg = "<div class='success'>Operation complete</div>";
        } else {
            $msg = "<div class='error'>Possible problem.  Please contact the administrator</div>";
        }
        
        setStatus($ok, 'Save', $this->msg, $this->class);
    }
    ////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////
    function copyReport() {
        // get last year's reports
        $ok = true;
        $data = array(
            'copyFromYear' => $this->copyFromYear,
            'copyToYear' => $this->copyToYear
        );
        try {
            $sql = "INSERT IGNORE INTO arrReport
                (schoolYearID, reportName, reportNum, reportOrder, orientation)
                SELECT :copyToYear, reportName, reportNum, reportOrder, orientation
                FROM arrReport
                WHERE arrReport.schoolYearID = :copyFromYear";
            $rs = $this->dbh->prepare($sql);
            $result = $rs->execute($data);
            if (!$result) {
                $ok = $result;
            }
        } catch (Exception $ex) {
            die($ex);
        }

        if ($ok) {
            try {
                // report sections
                $sql = "INSERT IGNORE INTO arrReportSection
                    (reportID, sectionType, sectionOrder)
                    SELECT newReport.reportID, 
                    arrReportSection.sectionType,
                    arrReportSection.sectionOrder
                    FROM arrReport AS newReport
                    INNER JOIN arrReport AS oldReport
                    ON oldReport.reportName = newReport.reportName
                    INNER JOIN arrReportSection
                    ON arrReportSection.reportID = oldReport.reportID
                    WHERE newReport.schoolYearID = :copyToYear
                    AND oldReport.schoolYearID = :copyFromYear";
                $rs = $this->dbh->prepare($sql);
                $result = $rs->execute($data);
                if (!$result) {
                    $ok = $result;
                }
            } catch (Exception $ex) {
                die($ex);
            }
        }
        
        if ($ok) {
            try {
                // section details
                $sql = "INSERT IGNORE INTO arrReportSectionDetail
                    (sectionID, sectionContent)
                    SELECT  
                    newSection.sectionID,
                    arrReportSectionDetail.sectionContent
                    FROM arrReport AS newReport
                    INNER JOIN arrReport AS oldReport
                    ON oldReport.reportName = newReport.reportName
                    INNER JOIN arrReportSection AS oldSection
                    ON oldSection.reportID = oldReport.reportID
                    INNER JOIN arrReportSection AS newSection
                    ON newSection.reportID = newReport.reportID
                    AND newSection.sectionOrder = oldSection.sectionOrder
                    INNER JOIN arrReportSectionDetail
                    ON arrReportSectionDetail.sectionID = oldSection.sectionID
                    WHERE newReport.schoolYearID = :copyToYear
                    AND oldReport.schoolYearID = :copyFromYear";
                $rs = $this->dbh->prepare($sql);
                $result = $rs->execute($data);
                if (!$result) {
                    $ok = $result;
                }
            } catch (Exception $ex) {
                die($ex);
            }
        }
        
        if ($ok) {
            try {
                $sql = "INSERT IGNORE INTO arrCriteria
                    (
                        subjectID,
                        schoolYearID,
                        yearGroupID,
                        criteriaName,
                        criteriaType,
                        gradeScaleID,
                        criteriaOrder
                    )
                    SELECT 
                    newCourse.gibbonCourseID,
                    newCourse.gibbonSchoolYearID,
                    arrCriteria.yearGroupID,
                    arrCriteria.criteriaName,
                    arrCriteria.criteriaType,
                    arrCriteria.gradeScaleID,
                    arrCriteria.criteriaOrder
                    FROM gibbonCourse AS newCourse
                    INNER JOIN gibbonCourse AS oldCourse
                    ON oldCourse.name = newCourse.name
                    INNER JOIN arrCriteria
                    ON arrCriteria.subjectID = oldCourse.gibbonCourseID
                    WHERE newCourse.gibbonSchoolYearID = :copyToYear 
                    AND oldCourse.gibbonSchoolYearID = :copyFromYear";
                $rs = $this->dbh->prepare($sql);
                $result = $rs->execute($data);
                if (!$result) {
                    $ok = $result;
                }
            } catch (Exception $ex) {
                die($ex);
            }
        }
        
        if ($ok) {
            // copy assign
            try {
                $sql = "INSERT IGNORE INTO arrSubjectOrder
                (
                    arrSubjectOrder.subjectID,
                    arrSubjectOrder.schoolYearID,
                    arrSubjectOrder.yearGroupID,
                    arrSubjectOrder.subjectOrder
                )
                SELECT 
                newCourse.gibbonCourseID,
                :copyToYear,
                arrSubjectOrder.yearGroupID,
                arrSubjectOrder.subjectOrder
                FROM arrSubjectOrder
                INNER JOIN gibbonCourse AS oldCourse
                ON oldCourse.gibbonCourseID = arrSubjectOrder.subjectID
                INNER JOIN gibbonCourse AS newCourse
                ON newCourse.name = oldCourse.name
                WHERE arrSubjectOrder.schoolYearID = :copyFromYear
                AND newCourse.gibbonSchoolYearID = :copyToYear";
                $rs = $this->dbh->prepare($sql);
                $result = $rs->execute($data);
                if (!$result) {
                    $ok = $result;
                }
            } catch (Exception $ex) {
                die($ex);
            }
        }
        
        if ($ok) {
            try {
            $sql = "SELECT arrReportAssign.reportAssignID,
                arrReportAssign.schoolYearID,
                arrReportAssign.yearGroupID,
                arrReportAssign.reportID,
                arrReportAssign.assignStatus,
                newReport.reportID
                FROM arrReportAssign
                INNER JOIN arrReport AS oldReport
                ON oldReport.reportID = arrReportAssign.reportID
                INNER JOIN arrReport AS newReport
                ON newReport.reportName = oldReport.reportName
                WHERE arrReportAssign.schoolYearID = :copyFromYear
                AND newReport.schoolYearID = :copyToYear";
                $rs = $this->dbh->prepare($sql);
                $result = $rs->execute($data);
                if (!$result) {
                    $ok = $result;
                }
            } catch (Exception $ex) {
                die($ex);
            }
        }
        
        if ($ok) {
            try {
                $sql = "INSERT IGNORE INTO arrReportAssign
                    (
                        arrReportAssign.schoolYearID,
                        arrReportAssign.yearGroupID,
                        arrReportAssign.reportID,
                        arrReportAssign.assignStatus
                    )
                    SELECT 
                    :copyToYear,
                    arrReportAssign.yearGroupID,
                    newReport.reportID,
                    arrReportAssign.assignStatus
                    FROM arrReportAssign
                    INNER JOIN arrReport AS oldReport
                    ON oldReport.reportID = arrReportAssign.reportID
                    INNER JOIN arrReport AS newReport
                    ON newReport.reportName = oldReport.reportName
                    WHERE arrReportAssign.schoolYearID = :copyFromYear
                    AND newReport.schoolYearID = :copyToYear";
                $rs = $this->dbh->prepare($sql);
                $result = $rs->execute($data);
                if (!$result) {
                    $ok = $result;
                }
            } catch (Exception $ex) {
                die($ex);
            }
            
            if ($ok) {
                try {
                    $sql = "INSERT IGNORE INTO arrStatus
                        (
                            reportID,
                            roleID,
                            reportStatus
                        )
                        SELECT 
                        newReport.reportID,
                        arrStatus.roleID,
                        arrStatus.reportStatus
                        FROM arrStatus
                        INNER JOIN arrReport AS oldReport
                        ON oldReport.reportID = arrStatus.reportID
                        INNER JOIN arrReport AS newReport
                        ON newReport.reportName = oldReport.reportName
                        WHERE oldReport.schoolYearID = :copyFromYear
                        AND newReport.schoolYearID = :copyToYear";
                    $rs = $this->dbh->prepare($sql);
                    $result = $rs->execute($data);
                    if (!$result) {
                        $ok = $result;
                    }
                } catch (Exception $ex) {
                    die($ex);
                }
            }
        }
        return $ok;
    }
}