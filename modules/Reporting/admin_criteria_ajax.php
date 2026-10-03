<?php
/*
 * set criteria order on the fly
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
    for ($i=4; $i<count($formData); $i++) {
        $rowData = explode("=", $formData[$i]);
        try {
            $data = array(
                'criteriaID' => $rowData[1],
                'criteriaOrder' => ($i-3)
            );
            $sql = "UPDATE arrCriteria
                SET criteriaOrder = :criteriaOrder
                WHERE criteriaID = :criteriaID";
            $rs = $connection2->prepare($sql);
            $rs->execute($data);
        } catch(PDOException $e) {
            print "<div>" . $e->getMessage() . "</div>" ;
        }              
    }
}