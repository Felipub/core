<?php
session_start();
include  "../../config.php";
include "../../functions.php";
include "../Attendance/moduleFunctions.php";

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

    //include "./pdf_function.php" ;
    include "./function.php";

    $root = "../..";
    //include $root."/lib/tcpdf/tcpdf.php";
    include $root."/vendor/tecnickcom/tcpdf/tcpdf.php";
    include "./pdf_create_function.php";
    
    setSessionVariables($guid, $connection2);

    $setpdf = new setpdf();
    $setpdf->setpdfInit($guid, $connection2);
    $reportSection = $setpdf->readReportSectionList($connection2);
    
    // check folder exists
    $setpdf->checkFolder();
    $path = '../..'.$_SESSION['archivePath'].$setpdf->schoolYearName.'/';

    // go through class list to see which ones need to be printed
    //$rollGroupList = $setpdf->rollGroupList;
    for ($i=0; $i<count($setpdf->printList); $i++) {
        $setpdf->studentID = $setpdf->printList[$i]['studentID'];
        $studentDetail = $setpdf->readStudentDetail();
        $row = $studentDetail->fetch();
        $setpdf->officialName = $row['officialName'];
        $setpdf->firstName = $row['firstName'];
        $setpdf->preferredName = $row['preferredName'];
        $setpdf->surname = $row['surname'];
        $setpdf->rollOrder = $row['rollOrder'];
        $setpdf->studentAbrName = str_replace("'", "", $row['surname'].substr($row['firstName'], 0, 1));
        $dob = $row['dob'];
        if ($dob != '' && substr($dob, 0, 4) != '0000') {
            $dob = date('d/m/Y', strtotime($dob));
        } else {
            $dob = '';
        }
        $setpdf->dob = $dob;

        $reportName =
                $setpdf->schoolYearName.'-'.
                $setpdf->yearGroupName.'-'.
                $setpdf->term.'-'.
                intval($setpdf->studentID).'-'.
                $setpdf->studentAbrName.".pdf";
        $fileName = $path.$reportName;

        ////////////////////////////////////////////////////////////////////
        // start pdf file
        ////////////////////////////////////////////////////////////////////
        $pdf = new MYPDF ($setpdf->pageOrientation, 'mm', 'A4', true, 'UTF-8', false);
        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin($setpdf->topMargin);
        $pdf->SetFooterMargin($setpdf->footerMargin);
        $pdf->AddPage();

        ////////////////////////////////////////////////////////////////////
        // subject report
        ////////////////////////////////////////////////////////////////////
        $reportSection->execute();
        while ($rowSection = $reportSection->fetch()) {
            $setpdf->sectionID = $rowSection['sectionID'];
            $sectionTypeID = $rowSection['sectionType'];
            $sectionTypeName = $rowSection['sectionTypeName'];
            switch ($sectionTypeName) {
                case 'Text':
                    $setpdf->textSection($pdf);
                    break;
                
                case 'Subject (row)':
                    $setpdf->subjectReportRow($pdf);
                    break;

                case 'Subject (column)':
                    $setpdf->subjectReportColumn($pdf);
                    break;
                
                case 'Pastoral':
                    $setpdf->pastoralReport($pdf);
                    break;
                
                case 'Page Break':
                    $pdf->AddPage();
                    break;
            }
        }

        ////////////////////////////////////////////////////////////////////
        // output to PDF
        ////////////////////////////////////////////////////////////////////
        $pdf->Output($fileName, 'F');

        ////////////////////////////////////////////////////////////////////
        // update history
        ////////////////////////////////////////////////////////////////////
        try {
            $data = array(
                'studentID' => $setpdf->studentID,
                'reportID' => $setpdf->reportID
            );
            $sql = "SELECT *
                FROM arrArchive
                WHERE studentID = :studentID
                AND reportID = :reportID";
            $rs = $connection2->prepare($sql);
            $rs->execute($data);
        } catch (Exception $ex) {
            die($ex);
        }
        

        if ($rs->rowCount() > 0) {
            // update record
            $row2 = $rs->fetch();
            $data = array(
                'archiveID' => $row2['archiveID'],
                'reportName' => $reportName,
                'created' => date('Y-m-d H:i:s')
            );
            $sql = "UPDATE arrArchive
                SET reportName = :reportName,
                created = :created
                WHERE archiveID = :archiveID";
        } else {
            $data = array(
                'studentID' => $setpdf->studentID,
                'reportID' => $setpdf->reportID,
                'reportName' => $reportName,
                'created' => date('Y-m-d H:i:s')
            );
            $sql = "INSERT IGNORE INTO arrArchive
                SET studentID = :studentID,
                reportID = :reportID,
                reportName = :reportName,
                created = :created";
        }
        //print $sql."<br>";
        //print_r($data);
        //print "<br>";
        try {
            $rs = $connection2->prepare($sql);
            $rs->execute($data);      
        } catch (Exception $ex) {
            die($ex);
        }            
    } // end rollGroup while loop


    // return to class list page
    $returnPath = $_SESSION[$guid]["absoluteURL"]."/index.php?q=/modules/Reporting/pdf.php".
            "&yearGroupID=$setpdf->yearGroupID".
            "&schoolYearID=$setpdf->schoolYearID".
            "&rollGroupID=$setpdf->rollGroupID".
            "&reportID=$setpdf->reportID";
    header("location:$returnPath");
}