<?php
$_SESSION['max_term'] = 3;
$_SESSION['numCols'] = 60;
$_SESSION['archivePath'] = '/archive/reporting/';

date_default_timezone_set('Asia/Hong_Kong');
ini_set('error_log', 'logfile.txt');

////////////////////////////////////////////////////////////////////////////////
function chooseReport($dbh, $classID, $reportID, $rollGroupID, $schoolYearID, $teacherID, $yearGroupID) {
    $repList = readReportList($dbh, $schoolYearID, $yearGroupID);
    $repList->execute();
    ob_start();
    ?>
    <div style = "padding:2px;">
        <?php
        if ($repList->rowCount() > 0) {
            ?>
            <div style = "float:left;width:30%;" class = "smalltext">Report</div>
            <div style = "float:left;">
                <form name="frm_selectreport" method="post" action="">
                    <input type="hidden" name="schoolYearID" value="<?php echo $schoolYearID ?>" />
                    <input type="hidden" name="yearGroupID" value="<?php echo $yearGroupID ?>" />
                    <input type="hidden" name="rollGroupID" value="<?php echo $rollGroupID ?>" />
                    <input type="hidden" name="teacherID" value="<?php echo $teacherID ?>" />
                    <input type="hidden" name="classID" value="<?php echo $classID ?>" />
                    <select name="reportID" onchange="this.form.submit()">
                        <option></option>
                        <?php
                        while ($row = $repList->fetch()) {
                            $selected = '';
                            if ($reportID == $row['reportID']) {
                                $selected = 'selected';
                            }
                            echo "<option value='".$row['reportID']."' $selected>";
                                echo $row['reportName'];
                            echo "</option>";
                        }
                        ?>
                    </select>
                </form>
            </div>
            <?php
        } else {
            echo "<div class='smalltext'>No reports assigned to this year group</div>";
        }
        ?>
    </div>
    <?php
    return ob_get_clean();
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function chooseRollGroup($dbh, $rollGroupID, $schoolYearID, $yearGroupID) {
    // drop down box to select roll group
    try {
        $data = array(
                'schoolYearID' => $schoolYearID,
                'yearGroupID' => $yearGroupID
        );
        $sql = "SELECT DISTINCT gibbonStudentEnrolment.gibbonFormGroupID, gibbonFormGroup.nameShort
            FROM gibbonFormGroup
            INNER JOIN gibbonStudentEnrolment
            ON gibbonFormGroup.gibbonFormGroupID = gibbonStudentEnrolment.gibbonFormGroupID
            WHERE gibbonYearGroupID = :yearGroupID
            AND gibbonStudentEnrolment.gibbonSchoolYearID = :schoolYearID
            ORDER BY nameShort";
        //print $sql;
        //print_r($data);
        $rs = $dbh->prepare($sql);
        $rs->execute($data);
    } catch (Exception $ex) {
        die($ex);
    }        

    ob_start();
    ?>
    <div style = "padding:2px;">
        <div style = "float:left;width:30%;" class = "smalltext">Roll Group</div>
        <div style = "float:left;">
            <form name="frm_class" method="post" action="">
                <input type="hidden" name="yearGroupID" value="<?php echo $yearGroupID ?>" />
                <input type="hidden" name="schoolYearID" value="<?php echo $schoolYearID ?>" />
                <select name="rollGroupID" onchange="this.form.submit();">
                    <option></option>
                    <?php
                    while ($row = $rs->fetch()) {
                        $selected = '';
                        if ($rollGroupID == $row['gibbonFormGroupID']) {
                            $selected = 'selected';
                        }
                        echo "<option value='".$row['gibbonFormGroupID']."' $selected>";
                            echo $row['nameShort'];
                        echo "</option>";
                    }
                    ?>
                </select>
            </form>
        </div>
        <div style="clear:both"></div>
    </div>
    <?php
    return ob_get_clean();
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function chooseSchoolYear($dbh, $studentID, $reportID, $schoolYearID) {
    // drop down box for selecting year
    $schoolYearList = readSchoolYearList($dbh);
    ob_start();
    ?>
    <div style = "padding:2px;">
        <div style = "float:left;width:30%;" class = "smalltext">Year</div>
        <div style = "float:left;">
            <form name = "frm_schoolyear" method = "post" action = "">
                <input type = "hidden" name = "classID" value = "" />
                <input type = "hidden" name = "reportID" value = "" />
                <input type = "hidden" name = "studentID" value = "" />
                <input type = "hidden" name = "yearGroupID" value = "" />
                <?php 
                echo "<select name='schoolYearID' onchange='this.form.submit()' style='width:95%'>";
                    $schoolYearList->execute();
                    while ($row = $schoolYearList->fetch()) {
                        $selected = ($schoolYearID == $row['gibbonSchoolYearID']) ? "selected" : "";
                        echo "<option value = '".$row['gibbonSchoolYearID']."' $selected>";    
                            echo $row["name"];
                        echo "</option>";
                    }
                echo "</select>";
                ?>
            </form>
        </div>
        <div style = "clear:both;"></div>
    </div>
    <?php
    return ob_get_clean();
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function chooseYearGroup($dbh, $yearGroupID, $schoolYearID) {
    $yearGroupList = readYeargroup($dbh);
    ob_start();
    ?>
    <div style = "padding:2px;">
        <div style = "float:left;width:30%;" class = "smalltext">Year Group</div>
        <div style = "float:left;">
            <form name='frm_yeargroup' method='post' action=''>
                <input type='hidden' name='courseID' value='' />
                <input type='hidden' name='classID' value='' />
                <input type='hidden' name='rollGroupID' value='' />
                <input type='hidden' name='studentID' value='' />
                <input type='hidden' name='reportID' value='' />
                <input type='hidden' name='schoolYearID' value='<?php echo $schoolYearID ?>' />
                <select name='yearGroupID' id='yearGroupID' onchange="this.form.submit();">
                    <option></option>
                    <?php
                    while ($row = $yearGroupList->fetch()) {
                        $selected = ($yearGroupID == $row['gibbonYearGroupID']) ? "selected" : "";
                        echo "<option value='".$row['gibbonYearGroupID']."' $selected>";
                            echo $row['nameShort'];
                        echo "</option>";
                    }
                    ?>
                </select>
            </form>
        </div>
        <div style="clear:both"></div>
    </div>
    <?php
    return ob_get_clean();
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function getClassID() {
    $classID = '';
    if (isset($_POST['classID'])) {
        $classID = $_POST['classID'];
    } else {
        if (isset($_GET['classID'])) {
            $classID = $_GET['classID'];
        }
    }
    return $classID;
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function getLeft() {
    $showLeft = 0;
    if (isset($_POST['showLeft'])) {
        $showLeft = $_POST['showLeft'];
    } else {
        if (isset($_GET['showLeft'])) {
            $showLeft = $_GET['showLeft'];
        }
    }
    return $showLeft;
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function getReportID() {
    // check if parameter has been passed to current page
    $reportID = '';
    if (isset($_POST['reportID'])) {
        $reportID = $_POST['reportID'];
    } else {
        if (isset($_GET['reportID'])) {
            $reportID = $_GET['reportID'];
        }
    }
    /*
    if ($reportID == '') {
        echo 4;
        if (isset($_SESSION['reportID'])) {
            echo 5;
            $reportID = $_SESSION['reportID'];
        }
    }
     * 
     */
    //$_SESSION['reportID'] = $reportID;    
    return $reportID;
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function getRollGroupID() {
    $rollGroupID = '';
    if (isset($_POST['rollGroupID'])) {
        $rollGroupID = $_POST['rollGroupID'];
    } else {
        if (isset($_GET['rollGroupID'])) {
            $rollGroupID = $_GET['rollGroupID'];
        }
    }
    if ($rollGroupID == '') {
        if (isset($_SESSION['rollGroupID'])) {
            $rollGroupID = $_SESSION['rollGroupID'];
        }
    }
    $_SESSION['rollGroupID'] = $rollGroupID;
    return $rollGroupID;
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function getSchoolYearCurrent($dbh) {
    try {
	// return details of current year
	$sql = "SELECT gibbonSchoolYearID
            FROM gibbonSchoolYear
            WHERE status = 'current'";
        $rs = $dbh->prepare($sql);
        $rs->execute();
        $schoolYearID = 0;
	if ($rs->rowCount() > 0) {
            $row = $rs->fetch();
            $schoolYearID = $row['gibbonSchoolYearID'];
	}
	return $schoolYearID;
    } catch(PDOException $e) {
        print "<div>" . $e->getMessage() . "</div>" ;
    }
}
////////////////////////////////////////////////////////////////////////////////
 
////////////////////////////////////////////////////////////////////////////////
function getSchoolYearID($dbh, &$schoolYearName, &$currentYearID) {
    // find selected year
    $currentYearID = getSchoolYearCurrent($dbh);
    if (isset($_POST['schoolYearID'])) {
        $schoolYearID = $_POST['schoolYearID'];
    } else {
        if (isset($_GET['schoolYearID'])) {
            $schoolYearID = $_GET['schoolYearID'];
        } else {
            $schoolYearID = $currentYearID;
        }
    }
    
    try {
	// get the name
	$data = array(":schoolYearID"=>$schoolYearID);
        $sql = "SELECT name
            FROM gibbonSchoolYear
            WHERE gibbonSchoolYearID = :schoolYearID";
        $rs = $dbh->prepare($sql);
        $rs->execute($data);
	$row_select = $rs->fetch();

    } catch(PDOException $e) {
        print "<div>" . $e->getMessage() . "</div>" ;
    }
    $schoolYearName = $row_select['name'];
    return $schoolYearID;
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function getStudentID() {
    // see if a student has been selected
    $studentID = '';
    if (isset($_POST['studentID'])) {
        $studentID = $_POST['studentID'];
    } else {
        if (isset($_GET['studentID'])) {
            $studentID = $_GET['studentID'];
        }
    }
    return $studentID;
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function getTeacherID($guid) {
    // see if teacher has been selected who is different from that logged in
    if (isset($_REQUEST['teacherID']) && $_REQUEST['teacherID'] != '') {
        $teacherID = $_REQUEST['teacherID'];
        if ($teacherID != $_SESSION[$guid]['teacherID'])
            $_SESSION[$guid]['classID'] = '';
    } else {
        if (isset($_SESSION[$guid]['teacherID'])) {
            $teacherID = $_SESSION[$guid]['teacherID'];
        } else {
            $teacherID = $_SESSION[$guid]['gibbonPersonID'];
        }
    }
    $_SESSION[$guid]['teacherID'] = $teacherID;
    return $teacherID;
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function getYearGroupID() {
    $yearGroupID = '';
    if (isset($_POST['yearGroupID'])) {
        $yearGroupID = $_POST['yearGroupID'];
    } else {
        if (isset($_GET['yearGroupID'])) {
            $yearGroupID = $_GET['yearGroupID'];
        }
    }
    if ($yearGroupID == '') {
        if (isset($_SESSION['yearGroupID'])) {
            $yearGroupID = $_SESSION['yearGroupID'];
        }
    }
    $_SESSION['yearGroupID'] = $yearGroupID;
    return $yearGroupID;
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function getView() {
    $view = '';
    if (isset($_POST['view'])) {
        $view = $_POST['view'];
    } else {
        if (isset($_GET['view'])) {
            $view = $_GET['view'];
        }
    }
    return $view;
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function oddEven($num) {
    if (($num-1)%2==0) {
        $rowNum="arreven" ;
    } else {
        $rowNum="arrodd" ;
    }
    return $rowNum;
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function pageTitle($title) {
    // display title
    echo "<div class='trail'>";
	echo "<div class='trailEnd'>$title</div>";
    echo "</div>";
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function archiveNavbar($guid, $page) {
    $path = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]["module"];
    $pageList = array('Current', 'archive.php', 'Search', 'archive_search.php');
    ?>
    <div class='smalltext'>
        <?php
        for ($p=0; $p<count($pageList)/2; $p++) {
            if ($page == strtolower($pageList[$p*2])) {
                echo "<span>".$pageList[$p*2]."</span>";
            } else {
                $link = $path.'/'.$pageList[$p*2+1];
                echo "<a href='$link'>".$pageList[$p*2]."</a>";
            }
            if ($p < (count($pageList)/2)-1) {
                echo "<span style='padding:2px;'>&nbsp;&nbsp;|&nbsp;&nbsp;</span>";
            }
        }
        ?>
    </div>
    <?php
}////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function navbar($guid, $dbh, $page, $studentID, &$reportID, $classID, $rollGroupID, $schoolYearID, $yearGroupID) {
    // subject navigation
    // shows title aand list of reports from which to select
    $path = $_SESSION[$guid]['absoluteURL'];
    $pathroot = $path."/index.php?q=/modules/".$_SESSION[$guid]['module']."/".strtolower($page).".php&amp;studentID=".$studentID;

    // see how many reports are available
    $repList = readReportList($dbh, $schoolYearID, $yearGroupID);

    // display list of report numbers and links
    echo "<div style = 'font-size:smaller;' class = 'smalltext'>";

    // counter used for deciding whether to place spacer between items.
    // do it for all but last item
    $c = 0;

    // if no reports match the current one it must be for a differeny
    // year/yeargroup so may need to reset it
    $match = 0;

    while ($row = $repList->fetch()) {
        $c++;
        if ($reportID == $row['reportID']) {
            // found a match so flag that there is no need to reset reportID
            $match = 1;
            echo "<span>".$row['reportName']."</span>";
        } else {
            if ($rollGroupID > 0) {
                $link = $pathroot."&amp;reportID=".$row['reportID'].
                    "&amp;schoolYearID=".$schoolYearID.
                    "&amp;yearGroupID=".$yearGroupID.
                    "&amp;rollGroupID=".$rollGroupID.
                    "&amp;classID=".$classID;
            } else {
                $link = $pathroot."&amp;reportID=".$row['reportID'].
                    "&amp;schoolYearID=".$schoolYearID.
                    "&amp;classID=".$classID;
            }
            echo "<span style='padding:2px'>";
            echo "<a href='$link'>";
                echo $row['reportName'];
            echo "</a>";
            echo "</span>";
        }
        if ($c < $repList->rowCount()) {
            echo "<span style='padding:2px;'>&nbsp;&nbsp;|&nbsp;&nbsp;</span>";
        }
    }
    if ($match == 0) {
        $reportID = 0;
    }
    echo "</div>";
    echo "<div class = 'header' style = 'clear:both;width:100%;'>&nbsp;</div>";
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function findMaxChar($dbh, $classID, &$courseType, &$maxChar) {
    try {
        $data = array('classID' => $classID);
        $sql = "SELECT *, arrCourseType, arrMaxChar
            FROM arrSubjectDetail
            INNER JOIN gibbonCourse
            ON gibbonCourse.gibbonCourseID = arrSubjectDetail.arrCourseID
            INNER JOIN gibbonCourseClass
            ON gibbonCourseClass.gibbonCourseID = gibbonCourse.gibbonCourseID
            WHERE gibbonCourseClassID = :classID";
        //print $sql;
        //print_r($data);
        $rs = $dbh->prepare($sql);
        $rs->execute($data);
        $row = $rs->fetch();
        $courseType = $row['arrCourseType'];
        $maxChar = $row['arrMaxChar'];
    } catch (Exception $ex) {
        die($ex);
    }        
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function findReportstatus($dbh, $reportID, $roleID) {
    try {
        $data = array(
            "reportID" => $reportID,
            "roleID" => $roleID
        );
        $sql = "SELECT reportStatus
            FROM arrStatus
            WHERE reportID = :reportID
            AND roleID = :roleID";
        //print $sql;
        //print_r($data);
        $rs = $dbh->prepare($sql);
        $rs->execute($data);
        $reportStatus = false;
        if ($rs->rowCount() > 0) {
            $row = $rs->fetch();
            $reportStatus = $row['reportStatus'];
        }
        return $reportStatus;
    } catch (Exception $ex) {
        die($ex);
    }
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function findGrade($gradeList, $gradeID) {
    // return grade value given its ID
    $grade = "-";
    $gradeList->execute();
    while ($row = $gradeList->fetch()) {
        if ($row['gibbonScaleGradeID'] == $gradeID) {
            $grade = $row['value'];
        }
    }
    return $grade;
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function getStudentName($dbh, $studentID) {
    // return name
    try {
        $data = array(":studentID"=>$studentID);
        $sql = "SELECT CONCAT(preferredName, ' ', surname) AS student_name
            FROM gibbonPerson
            WHERE gibbonPersonID = :studentID";
        //$dbh->query("SET NAMES 'utf8'");
        $rs = $dbh->prepare($sql);
        $rs->execute($data);
        $row = $rs->fetch();
        return $row['student_name'];
    } catch(PDOException $e) {
        print "<div>" . $e->getMessage() . "</div>" ;
    }
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function getTeacherName($dbh, $classID) {
    // return name
    try {
        $data = array("classID"=>$classID);
        $sql = "SELECT CONCAT(preferredName, ' ', surname) AS teacherName
            FROM gibbonPerson
            INNER JOIN gibbonCourseClassPerson
            ON gibbonCourseClassPerson.gibbonPersonID = gibbonPerson.gibbonPersonID
            WHERE gibbonCourseClassPerson.gibbonCourseClassID = :classID
            AND gibbonCourseClassPerson.role = 'Teacher'";
        //$dbh->query("SET NAMES 'utf8'");
        $rs = $dbh->prepare($sql);
        $rs->execute($data);
        $row = $rs->fetch();
        return $row['teacherName'];
    } catch(PDOException $e) {
        print "<div>" . $e->getMessage() . "</div>" ;
    }
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function readCriteriaGrade($dbh, $studentID, $subjectID, $reportID) {
    // read criteria for this subject
    // together with any associated grades that have been stored for this student
    try {
        $data = array(
            "studentID" => $studentID,
            "subjectID" => $subjectID,
            "reportID" => $reportID
        );
        
        $sql = "SELECT arrCriteria.criteriaID, arrCriteria.criteriaName, arrCriteria.criteriaType,
            arrCriteria.gradeScaleID,
            (
                SELECT arrReportGrade.gradeID
                FROM arrReportGrade
                WHERE arrReportGrade.criteriaID = arrCriteria.criteriaID
                AND reportID = :reportID
                AND studentID = :studentID
            ) AS gradeID,
            ( 
                SELECT gibbonScaleGrade.descriptor
                FROM gibbonScaleGrade
                LEFT JOIN arrReportGrade
                    ON arrReportGrade.gradeID = gibbonScaleGrade.value
                    WHERE arrReportGrade.criteriaID = arrCriteria.criteriaID 
                AND gibbonScaleGrade.gibbonScaleID = arrCriteria.gradeScaleID
                AND reportID = :reportID
                AND studentID = :studentID 
            ) AS grade,
            (
                SELECT arrReportGrade.mark
                FROM arrReportGrade
                WHERE arrReportGrade.criteriaID = arrCriteria.criteriaID
                AND reportID = :reportID
                AND studentID = :studentID
            ) AS mark,
            (
                SELECT arrReportGrade.percent
                FROM arrReportGrade
                WHERE arrReportGrade.criteriaID = arrCriteria.criteriaID
                AND reportID = :reportID
                AND studentID = :studentID
            ) AS percent
            FROM arrCriteria
            WHERE subjectID = :subjectID
            ORDER BY criteriaOrder";
        $rs = $dbh->prepare($sql);
        $rs->execute($data);
        return $rs;
    } catch(PDOException $e) {
        print "<div>" . $e->getMessage() . "</div>" ;
    }
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function readReportDetail($dbh, $reportID) {
    try {
        $data = array(
            'reportID' => $reportID
        );
        $sql = "SELECT *
            FROM arrReport
            WHERE reportID = :reportID";
        $rs = $dbh->prepare($sql);
        $rs->execute($data);
        return $rs;
    } catch (Exception $ex) {
        die($ex);
    }        
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function readReportList($dbh, $schoolYearID, $yearGroupID) {
    // read reports available for this year group
    try {
        $data = array(
            'schoolYearID' => $schoolYearID,
            'yearGroupID' => $yearGroupID
        );
        $sql = "SELECT arrReport.reportID, reportName
            FROM arrReport
            INNER JOIN arrReportAssign
            ON arrReport.reportID = arrReportAssign.reportID
            WHERE arrReportAssign.schoolYearID = :schoolYearID
            AND yearGroupID = :yearGroupID
            AND assignStatus = 1
            ORDER BY reportNum";
        //print $sql;
        //print_r($data);
        $rs  = $dbh->prepare($sql);
        $rs->execute($data);
        return $rs;
    } catch(PDOException $e) {
        print "<div>" . $e->getMessage() . "</div>" ;
    }
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function readRollGroupList($dbh, $rollGroupID, $showLeft) {
    // return list of students in the selected roll group
    try {
        $data = array(
            'rollGroupID' => $rollGroupID
        );
        $sql = "SELECT *
            FROM gibbonStudentEnrolment
            INNER JOIN gibbonPerson
            ON gibbonStudentEnrolment.gibbonPersonID = gibbonPerson.gibbonPersonID
            WHERE gibbonFormGroupID = :rollGroupID 
            AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "')";
        if ($showLeft == 0) {
            $sql .= "AND status = 'Full' ";
        }
        $sql .= "ORDER BY surname, firstName";
        $rs = $dbh->prepare($sql);
        $rs->execute($data);
        return $rs;
    } catch (Exception $ex) {
        die($ex);
    }        
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function readSchoolYearList($dbh) {
    try {
	// read list of academic years
	$sql = "SELECT gibbonSchoolYearID, name, status
            FROM gibbonSchoolYear
            ORDER BY sequenceNumber";
        $rs = $dbh->prepare($sql);
        $rs->execute();
        return $rs;
    } catch(PDOException $e) {
        print "<div>" . $e->getMessage() . "</div>" ;
    }
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function readGradeScaleList($dbh) {
    try {
        $sql = "SELECT gibbonScaleID, name, nameShort, gibbonScale.usage
            FROM gibbonScale
            WHERE gibbonScale.active = 'Y'
            ORDER BY name";
        $rs = $dbh->prepare($sql);
        $rs->execute();
        return $rs;
    } catch(PDOException $e) {
        print "<div>" . $e->getMessage() . "</div>" ;
    }    
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function readGradeList($dbh, $gradeScale) {
    try {
        $data = array(
            'gradeScaleID' => $gradeScale
        );
        $sql = "SELECT gibbonScaleGradeID, 
            gibbonScaleGrade.value, descriptor, sequenceNumber
            FROM gibbonScaleGrade
            WHERE gibbonScaleID = :gradeScaleID
            ORDER BY sequenceNumber";
        //print $sql;
        //print_r($data);
        $rs = $dbh->prepare($sql);
        $rs->execute($data);
        return $rs;
    } catch(PDOException $e) {
        print "<div>" . $e->getMessage() . "</div>" ;
    }   
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function readStudentClassList($dbh, $studentID, $schoolYearID) {
    // read classes/subjects attended by selected student
    try {
        $data = array(
            'studentID' => $studentID,
            'schoolYearID' => $schoolYearID
        );
        //CONCAT(gibbonPerson.preferredName,' ',gibbonPerson.surname) AS teacherName
        $sql = "SELECT gibbonCourseClassPerson.gibbonCourseClassID AS classID, 
            gibbonCourseClass.name AS subjectClassName, 
            gibbonCourseClass.nameShort AS subjectClassNameShort, 
            gibbonCourse.gibbonCourseID AS subjectID, 
            gibbonCourse.name AS subjectName,
            teacher.gibbonPersonID,
            CONCAT(gibbonPerson.surname, '.', LEFT(gibbonPerson.firstName,1)) AS teacherName

            FROM gibbonCourseClassPerson 
            INNER JOIN gibbonCourseClass 
            ON gibbonCourseClass.gibbonCourseClassID = gibbonCourseClassPerson.gibbonCourseClassID 
            INNER JOIN gibbonCourse 
            ON gibbonCourse.gibbonCourseID = gibbonCourseClass.gibbonCourseID 
            LEFT JOIN arrSubjectOrder
            ON arrSubjectOrder.subjectID = gibbonCourse.gibbonCourseID
            AND arrSubjectOrder.schoolYearID = gibbonCourse.gibbonSchoolYearID
            INNER JOIN gibbonCourseClassPerson AS teacher 
            ON teacher.gibbonCourseClassID = gibbonCourseClass.gibbonCourseClassID 

            LEFT JOIN gibbonPerson
            ON gibbonPerson.gibbonPersonID = teacher.gibbonPersonID
            WHERE gibbonCourseClassPerson.gibbonPersonID = :studentID
            AND gibbonCourse.gibbonSchoolYearID = :schoolYearID
            AND gibbonCourseClass.reportable = 'Y'
            AND teacher.role = 'Teacher'
            GROUP BY gibbonCourse.gibbonCourseID
            ORDER BY arrSubjectOrder.subjectOrder";
        $rs = $dbh->prepare($sql);
        $rs->execute($data);
        return $rs;
    } catch(PDOException $e) {
        print "<div>" . $e->getMessage() . "</div>" ;
    }  
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function readSubReport($dbh, $studentID, $subjectID, $reportID) {
    // get report for selected student
    try {
        $data = array(
            ":studentID"=>$studentID,
            ":subjectID"=>$subjectID,
            ":reportID"=>$reportID
        );
        $sql = "SELECT *
            FROM arrReportSubject
            WHERE studentID = :studentID
            AND subjectID = :subjectID
            AND reportID = :reportID";
        $rs = $dbh->prepare($sql);
        $rs->execute($data);
        return $rs;
    } catch(PDOException $e) {
        print "<div>" . $e->getMessage() . "</div>" ;
    }
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function readCriteriaList($dbh, $subjectID) {
    // read any criteria assocated with this class/subject
    // not UOI
    try {
        $data = array(
            "subjectID" => $subjectID
        );
        $sql = "SELECT criteriaID, criteriaName, criteriaType
            FROM arrCriteria
            WHERE subjectID = :subjectID
            ORDER BY criteriaOrder";
        //print $sql;
        //print_r($data);
        $rs = $dbh->prepare($sql);
        $rs->execute($data);
        return $rs;
    } catch(PDOException $e) {
        print "<div>" . $e->getMessage() . "</div>" ;
    }  
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function readTerm($dbh, $schoolYearID) {
    $data = array(
        "schoolYearID" => $schoolYearID
    );
    try {
        $sql = "SELECT gibbonSchoolYearTermID AS termID, name, nameShort
            FROM gibbonSchoolYearTerm
            WHERE gibbonSchoolYearTerm.gibbonSchoolYearID = :schoolYearID
            ORDER BY sequenceNumber";
        $rs = $dbh->prepare($sql);
        $rs->execute($data);
        return $rs;
    } catch(PDOException $e) {
        print "<div>" . $e->getMessage() . "</div>" ;
    }  
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function readYeargroup($dbh) {
    try {
        $sql = "SELECT *
            FROM gibbonYearGroup
            ORDER BY sequenceNumber";
        $rs = $dbh->prepare($sql);
        $rs->execute();
        return $rs;
    } catch(PDOException $e) {
        print "<div>" . $e->getMessage() . "</div>" ;
    }  
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function setSessionVariables($guid, $dbh) {
    $_SESSION[$guid]['schoolYearID'] = getSchoolYearCurrent($dbh);

    $_SESSION[$guid]['classView']    = 1;
    $_SESSION[$guid]['studView']     = 0;
    $_SESSION[$guid]['maxGrade']     = 7;

    $_SESSION[$guid]['minYear'] = 1;
    $_SESSION[$guid]['maxYear'] = 13;

    $_SESSION[$guid]['repEdit'] = 2;
    $_SESSION[$guid]['repView'] = 1;

    $uploadpath = "../../uploads/documents/reports";
    @mkdir($uploadpath);
    $_SESSION[$guid]['uploadpath'] = $uploadpath;
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function setStatus($ok, $action, &$msg, &$class) {
    // set values for displaying message after save
    if ($ok) {
        $msg = $action." successful";
        $class = "success";

    } else {
        $msg = $action." failed";
        $class = "warning";
    }
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function showPhoto($guid, $dbh, $studentID) {
    // display student photo
    $size = 75;
    try {
        $data = array(":student_id"=>$studentID);
        $sql = "SELECT image_240, image_240
            FROM gibbonPerson
            WHERE gibbonPersonID=:student_id";
        $rs = $dbh->prepare($sql);
        $rs->execute($data);
        if ($rs->rowCount() > 0) {
            $row_select = $rs->fetch();
            if ($size == 75)
                $image = $row_select['image_240'];
            else
                $image = $row_select['image_240'];
        } else {
            $image = '';
        }
    } catch(PDOException $e) {
        print "<div>" . $e->getMessage() . "</div>" ;
    }
    echo "<div class = 'photobox'>";
        //$image = get_photoPath($dbh, $studentID, 75);
        echo getUserPhoto($guid, $image, 75);
    echo "</div>";
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function showRepLength($comment, $maxChar, $charBarID, $numCharID) {
    // show length of comment
    echo "<div id='$charBarID' class='smalltext replenbar'>";
    echo "<span id='$numCharID'>";
    echo strlen($comment);
    echo "</span> characters $maxChar maximum";
    echo "</div>";
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function thisPage($guid, $page) {
    // path to current page
    $path = $_SESSION[$guid]['absoluteURL'];
    $thisPage = $path."/index.php?q=/modules/".$_SESSION[$guid]['module']."/".$page;
    return $thisPage;
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function trimCourseName($courseName) {
    switch (substr($courseName, 0, 4)) {
        case 'Year':
            $courseName = substr($courseName, 6);
            break;

        case 'Nurs':
            $courseName = substr($courseName, 8);
            break;

        case 'Rece':
            $courseName = substr($courseName, 9);
            break;

        case 'Default':
            $courseName = $courseName;
            break;
    }
    return $courseName;
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function showComment($fldComment, $comment, $charBarID, $maxChar, $numCharID, $numRows, $enabledState) {
    // show comment for edit or display
    // show number of characters entered and disable save if too long        
    showRepLength($comment, $maxChar, $charBarID, $numCharID);
    $cols = 10;
    ?>
    <div>
        <textarea
            name = "<?php echo $fldComment ?>"
            rows = "<?php echo $numRows ?>"
            cols = "<?php echo $cols ?>"
            onkeyup = "checkEnter(this.value, <?php echo $maxChar ?>, 'submit', '<?php echo $numCharID ?>', '<?php echo $charBarID ?>');"
            class = "subtextbox"
            onkeydown = "notSaved('status')"
            <?php echo $enabledState ?>
            ><?php echo $comment; ?></textarea>
    </div>
    <?php
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function freemium($modpath) {
    $path = $modpath.'/documents/gibbon_reporting_user_guide_2.00.pdf';
    $freemium = "<div id='freemium'>";
        $freemium .= "<table class='tableNoBorder'>";
            $freemium .= "<tr>";
                $freemium .= "<td>";
                    $freemium .= "Want extra features - contact:";
                $freemium .= "</td>";
                $freemium .= "<td>";
                    $freemium .= "<a href='mailto:info@rapid36.com'>info@rapid36.com</a>";
                $freemium .= "</td>";
            $freemium .= "</tr>";
            $freemium .= "<tr>";
                $freemium .= "<td>";
                    $freemium .= "User guide:";
                $freemium .= "</td>";
                $freemium .= "<td>";
                    $freemium .= "<a href='$path' target='_blank'>download PDF</a>";
                $freemium .= "</td>";
            $freemium .= "</tr>";
            $freemium .= "<tr>";
                $freemium .= "<td colspan='2'>";
                    $freemium .= "<a href='#' onclick='$(\"#freemium\").hide();'>Hide me</a>";
                $freemium .= "</td>";
            $freemium .= "</tr>";
        $freemium .= "</table>";
    $freemium .= "</div>";
    return $freemium;
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////
function selectGrade($fldID, $gradeID, $enabledState, $gradeList) {
    // drop down box for selecting grades
    ?>
    <select name="<?php echo $fldID ?>" <?php echo $enabledState ?> onchange="notSaved('status')">
        <option> </option>
        <?php
        $gradeset = $gradeList;
        $gradeset->execute();
        while ($row = $gradeset->fetch()) {
            $selected = '';
            if ($gradeID == $row['value']) {
                $selected = 'selected';
            }
            echo "<option value='".$row['value']."' $selected >";
                echo $row['descriptor'];
            echo "</option>";
        }
        ?>
    </select>
    <?php
}
////////////////////////////////////////////////////////////////////////////
