<?php
session_start();
include  "../../config.php";
include "../../functions.php";

//New PDO DB connection
try {
    $connection2=new PDO("mysql:host=$databaseServer;dbname=$databaseName;charset=utf8", $databaseUsername, $databasePassword);
	$connection2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch(PDOException $e) {
    echo $e->getMessage();
}

//Open Database connection
if (!($connection = mysql_connect($databaseServer, $databaseUsername, $databasePassword))) {
    showError() ;
}

//Select database
if (!(mysql_select_db($databaseName, $connection))) {
    showError() ;
}

if (isActionAccessible($guid, $connection2, "/modules/Reporting/subject_save.php")==FALSE) {
	//Acess denied
	print "<div class='error'>";
        print "You do not have access to this action.";
	print "</div>";
        exit;
} else {
    //$path =  $_SESSION[$guid]['absoluteURL']."/modules/".$_SESSION[$guid]["module"];
    $filepath = "./subject_function.php";
    include $filepath;

    $ok = 0;
    $result = 1;
    $status = 0;

    $teacherID    = $_POST['teacherID'];
    $classCode    = $_POST['classCode'];
    $reportNum    = $_POST['reportNum'];
    $reportType   = $_POST['reportType'];
    $schoolYearID = $_POST['schoolYearID'];
    $view         = $_POST['view'];
    $maxGrade     = $_POST['maxGrade'];

    if (isset($_POST['subsubmit'])) {
        $i = 1;
        $fld_studentID    = "student".$i;

        while (isset($_POST[$fld_studentID])) {
            $studentID    = $_POST[$fld_studentID];
            $fld_effort = "effort".$i;
            $fld_attainment = "attainment".$i;
            $fld_target = "target".$i;
            $effort = $_POST[$fld_effort];
            $attainment = $_POST[$fld_attainment];
            $target = $_POST[$fld_target];
            if ($reportType > 1) {
                $fld_comment = "comment".$i;
                $comment = $_POST[$fld_comment];
            } else {
                $comment = '';
            }
            $target = '';
            // second check for invalid grades
            if ($effort >= 0 && $effort <= $maxGrade && $attainment >= 0 && $attainment <= $maxGrade) {
                // check for existing report
                try {
                    $data = array(
                        "studentID"=>$studentID,
                        "classCode"=>$classCode,
                        "reportNum"=>$reportNum,
                        "schoolYearID"=>$schoolYearID,
                        "effort"=>$effort,
                        "attainment"=>$attainment,
                        "target"=>$target,
                        "comment"=>$comment
                    );
                    if (checkSubReportExists($connection2, $studentID, $classCode, $reportNum, $schoolYearID)) {
                        // update
                        $sql = "UPDATE arrReportSubject
                            SET arrEffort = :effort,
                            arrAttainment = :attainment,
                            arrTarget = :target,
                            arrSubjectComment = :comment
                            WHERE arrPersonID = :studentID
                            AND arrCourseClassID = :classCode
                            AND arrReportNum = :reportNum
                            AND arrSchoolYearID = :schoolYearID";
                    } else {
                        // insert
                        $sql = "INSERT INTO arrReportSubject
                            SET arrPersonID = :studentID,
                            arrCourseClassID = :classCode,
                            arrReportNum = :reportNum,
                            arrSchoolYearID = :schoolYearID,
                            arrEffort = :effort,
                            arrAttainment = :attainment,
                            arrTarget = :target,
                            arrSubjectComment = :comment";
                    }
                    $rs = $connection2->prepare($sql);

                    //$connection2->query("SET NAMES utf8");
                    //$connection2->query("SET CHARACTER SET utf8");
                    //print $sql;
                    //print_r($data);
                    $ok = $rs->execute($data);
                    if ($ok != TRUE) {
                        $result = 0;
                    }
                } catch(PDOException $e) {
                    print "<div>" . $e->getMessage() . "</div>" ;
                }
            } else {
                $result = 0;
            }
            $i++;
            $fld_studentID    = "student".$i;
        }

        if ($result == true)
            $status = 2;
        else
            $status = 1;
    }

    // return values to calling page
    $path = $_SESSION[$guid]['absoluteURL'];
    $link = $path."/index.php?q=".$_POST['address'].
            "&teacherID=".$teacherID.
            "&classCode=".$classCode.
            "&status=".$status.
            "&view=".$view;
    if ($view == 0)
            $link .= "&studentID=".$studentID;
    header("location: $link");
}