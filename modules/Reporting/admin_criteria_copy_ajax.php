<?php
/*
 * copy criteria to other subjects/year groups
 */

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

    $formData = $_POST['formData'];
    $formData = explode("&", $formData);
    $schoolYearID = $_POST['schoolYearID'];
    
    // make list of criteria to copy from
    //$copyFrom = array();
    $copyFrom = "";
    $comma = "";
    $reportTo = array();
    $yearGroupTo = array();
    $subjectTo = array();
    
    $overwrite = false;
    for ($i=0; $i<count($formData); $i++) {
        $rowData = explode("=", $formData[$i]);
        if ($rowData[0] == 'criteriaIDcopy') {
            $copyFrom .= $comma.$rowData[1];
            $comma = ", ";
        }
        if ($rowData[0] == 'yearGroupIDcopy') {
            $yearGroupID = $rowData[1];
        }
        if ($rowData[0] == 'subjectIDcopy') {
            $subjectTo[] = $rowData[1];
        }     
        /*
        if ($rowData[0] == 'overwrite') {
            $overwrite = true;
        }
         * 
         */
    }
    
    
    // for each subject
    for ($sub=0; $sub<count($subjectTo); $sub++) {
        try {
            $data = array(
                'subjectID' => $subjectTo[$sub],
                'schoolYearID' => $schoolYearID,
                'yearGroupID' => $yearGroupID
            );
            // check if criterion exists
            $sql = "INSERT IGNORE INTO arrCriteria
                (subjectID, schoolYearID, yearGroupID, criteriaName, criteriaType, gradeScaleID, criteriaOrder)
                SELECT :subjectID, :schoolYearID, :yearGroupID, criteriaName, criteriaType, gradeScaleID, criteriaOrder
                FROM arrCriteria
                WHERE arrCriteria.criteriaID IN ($copyFrom)";
            $rs = $connection2->prepare($sql);
            $rs->execute($data);
        } catch(PDOException $e) {
            print "<div>" . $e->getMessage() . "</div>" ;
        }  
    }
}