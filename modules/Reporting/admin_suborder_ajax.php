<?php
/*
 * change subject order
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

$formData = $_POST['formData'];
$formData = explode("&", $formData);

for ($i=1; $i<count($formData); $i++) {
    $rowData = explode("=", $formData[$i]);
    try {
        $data = array(
            'subjectID' => $rowData[1],
            'subjectOrder' => ($i)
        );
        $sql = "UPDATE arrSubjectOrder
            SET subjectOrder = :subjectOrder
            WHERE subjectID = :subjectID";
        $rs = $connection2->prepare($sql);
        $rs->execute($data);
    } catch(PDOException $e) {
        print "<div>" . $e->getMessage() . "</div>" ;
    }              
}