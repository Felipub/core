<?php
include  "../../config.php";
include "../../functions.php";

//New PDO DB connection
try {
    $connection2=new PDO("mysql:host=$databaseServer;
            dbname=$databaseName;
            charset=utf8", $databaseUsername, $databasePassword);
    $connection2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // reset coding
}
catch(PDOException $e) {
    echo $e->getMessage();
}

//if (isActionAccessible($guid, $connection2, "/modules/Reporting/pdf_make.php")==FALSE) {
if (1==2) {
    //Acess denied
    print "<div class='error'>";
    print "You do not have access to this action.";
    print "</div>";
    exit;
} else {

    $schoolYearID = $_POST['schoolYearID'];
    $yearGroupID = $_POST['yearGroupID'];
    try {
        $data = array(
            'schoolYearID' => $schoolYearID,
            'yearGroupID' => "%".$yearGroupID."%"
        );
        $sql = "SELECT gibbonCourse.gibbonCourseID AS subjectID,
            gibbonCourse.name AS subjectName
            FROM gibbonCourse
            WHERE gibbonCourse.gibbonSchoolYearID = :schoolYearID
            AND gibbonCourse.gibbonYearGroupIDList LIKE :yearGroupID
            ORDER BY gibbonCourse.name";
        $rs = $connection2->prepare($sql);
        $rs->execute($data);
        $subjectList = $rs->fetchAll();

        $res = array(
            'subjectList' => $subjectList
        );
        echo json_encode($res);
    } catch(PDOException $e) {
        print "<div>" . $e->getMessage() . "</div>" ;
    }  
}