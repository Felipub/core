<?php
/*
 * functions to go with admin pages
 */

function admin_navbar($guid, $connection2, $title) {
    // admin suite navigation
    $path = $_SESSION[$guid]['absoluteURL'];
    $pathroot = $path."/index.php?q=/modules/".$_SESSION[$guid]['module']."/";
    $option = array(
        'Create', 'admin_define.php',
        'Assign', 'admin_assign.php',
        'Access', 'admin_access.php',
        'Criteria', 'admin_criteria.php',
        'Order', 'admin_suborder.php',
        'Design', 'admin_design.php',
        'Start of Year', 'admin_startyear.php'
    );
    //'Complete', 'admin_complete.php',

    echo "<div style = 'float:left; width:20px;'>&nbsp;</div>";
    echo "<div style = 'float:left;' class = 'smalltext'>";
        for ($i=0; $i<count($option)/2; $i++) {
            if (strtolower($title) == strtolower($option[$i*2])) {
                echo $option[$i*2];
            } else {
                $link = $pathroot.$option[$i*2+1];
                echo "<a href='$link'>".$option[$i*2]."</a>";
            }
            // show separator after each option apart from last one
            if ($i<count($option)/2-1) {
                echo "&nbsp;|&nbsp;";
            }
        }
    echo "</div>";
    echo "<div style = 'clear:both;'>&nbsp;</div>";
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function getMode() {
    $mode = '';
    if (isset($_POST['mode'])) {
        $mode = $_POST['mode'];
    } else {
        if (isset($_GET['mode'])) {
            $mode = $_GET['mode'];
        }
    }
    return $mode;
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function readReport($connection2, $schoolYearID) {
    // read list of all reports for selected year
    try {
        $data = array('schoolYearID' => $schoolYearID);
        $sql = "SELECT reportID, schoolYearID, reportName, reportNum, reportOrder, orientation, 
            gradeScale, gibbonScale.nameShort, gibbonScale.usage, gibbonSchoolYearTerm.name As termName
            FROM arrReport
            LEFT JOIN gibbonScale
            ON gibbonScale.gibbonScaleID = arrReport.gradeScale
            LEFT JOIN gibbonSchoolYearTerm
            ON gibbonSchoolYearTerm.gibbonSchoolYearTermID = arrReport.reportNum
            WHERE schoolYearID = :schoolYearID
            ORDER BY reportNum, reportName";
        $rs = $connection2->prepare($sql);
        $rs->execute($data);
        return $rs;
    } catch(PDOException $e) {
        print "<div>" . $e->getMessage() . "</div>" ;
    }  
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function read_subjectlist($connection2, $yearGroupID, $schoolYearID) {
    try {
        $data = array(
            "schoolYearID" => $schoolYearID,
            "yearGroupID" => '%'.$yearGroupID.'%'
        );
        $sql = "SELECT DISTINCT gibbonCourse.gibbonCourseID AS subjectID, 
            gibbonCourse.name AS subjectName
            FROM gibbonCourse
            INNER JOIN gibbonCourseClass
            ON gibbonCourse.gibbonCourseID = gibbonCourseClass.gibbonCourseID
            WHERE gibbonSchoolYearID = :schoolYearID
            AND gibbonYearGroupIDList LIKE :yearGroupID
            AND reportable = 'Y'";
        //print $sql;
        //print_r($data);
        $rs = $connection2->prepare($sql);
        $rs->execute($data);
        return $rs;
    } catch(PDOException $e) {
        print "<div>" . $e->getMessage() . "</div>" ;
    }  
}
////////////////////////////////////////////////////////////////////////////////