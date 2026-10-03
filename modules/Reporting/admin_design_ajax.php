<?php
/*
 * ajax call from design page
 */

session_start();
include "./function.php";
include  "../../config.php";
include "../../functions.php";

//New PDO DB connection
try {
    $dbh=new PDO("mysql:host=$databaseServer;
            dbname=$databaseName;
            charset=utf8", $databaseUsername, $databasePassword);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // reset coding
} catch(PDOException $e) {
    echo $e->getMessage();
}
    
$action = $_POST['action'];

switch($action) {
    case 'load':
        try {
            $reportID = $_POST['reportID'];
            $data = array(
                'reportID' => $reportID
            );
            $sql = "SELECT arrReportSection.sectionID, 
                arrReportSection.sectionType, 
                arrReportSectionDetail.sectionContent
                FROM arrReportSection
                LEFT JOIN arrReportSectionDetail
                ON arrReportSectionDetail.sectionID = arrReportSection.sectionID
                WHERE reportID = :reportID
                ORDER BY sectionOrder";
            $rs = $dbh->prepare($sql);
            $rs->execute($data);
            $section = $rs->fetchAll();

            $res = array(
                'section' => $section
            );
            echo json_encode($res);
        } catch(PDOException $e) {
            print "<div>" . $e->getMessage() . "</div>" ;
        }  
        break;


    case 'save':
        // Loop over each item in the form.
        $reportID = $_POST['reportID'];
        $formData = explode('&', $_POST['formData']);
        
        $idlist = array();
        $ok = true;
        $numcol = 2;
        
        if ($formData[0] != "") {
            // get the IDs of all existing sections that need to be kept
            for ($i=0; $i<count($formData)/$numcol; $i++) {
                $rowdata = explode('=', $formData[($i*$numcol)]);
                $sectionID = $rowdata[1];
                $idlist[] = $sectionID;
            }
        } else {
            $idlist = [];
        }
        
        // remove any sections that have not been sent
        // convert $idlist to string
        $idliststring = implode(',', $idlist);
        while (substr($idliststring, strlen($idliststring)-1, 1) === ',') {
            $idliststring = substr($idliststring, 0, strlen($idliststring) - 1);
        }
        if ($idliststring != '') {
            $data = array(
                'reportID' => $reportID
            );
            try {
                $sql = "DELETE arrReportSectionDetail.*
                    FROM arrReportSectionDetail
                    INNER JOIN arrReportSection
                    ON arrReportSection.sectionID = arrReportSectionDetail.sectionID
                    WHERE arrReportSection.sectionID NOT IN ($idliststring)
                    AND reportID = :reportID;
                    DELETE arrReportSection.*
                    FROM arrReportSection
                    WHERE arrReportSection.sectionID NOT IN ($idliststring)
                    AND reportID = :reportID";
                $rs = $dbh->prepare($sql);
                $result = $rs->execute($data);
                if (!$result) {
                    $ok = $result;
                }
            } catch(PDOException $e) {
                print "<div>" . $e->getMessage() . "</div>" ;
            }                      
        } else {
            try {
                $data = array(
                    'reportID' => $reportID
                );
                $sql = "DELETE arrReportSectionDetail.*
                    FROM arrReportSectionDetail
                    INNER JOIN arrReportSection
                    ON arrReportSection.sectionID = arrReportSectionDetail.sectionID
                    WHERE arrReportSection.reportID = :reportID";
                $rs = $dbh->prepare($sql);
                $rs->execute($data);
                $sql = "DELETE arrReportSection.*
                    FROM arrReportSection
                    WHERE arrReportSection.reportID = :reportID";
                $rs = $dbh->prepare($sql);
                $rs->execute($data);
                $rs = $dbh->prepare($sql);
                $result = $rs->execute($data);
                if (!$result) {
                    $ok = $result;
                }
            } catch(PDOException $e) {
                print "<div>" . $e->getMessage() . "</div>" ;
            }   
        }

        // save changes and new sections
        if ($formData[0] != "") {
            for ($i=0; $i<count($formData)/$numcol; $i++) {
                $rowdata = explode('=', $formData[($i*$numcol)]);
                $sectionID = $rowdata[1];
                $rowdata = explode('=', $formData[($i*$numcol)+1]);
                $sectionType = $rowdata[1];
                try {
                    $data = array(
                        'sectionType' => $sectionType,
                        'sectionOrder' => $i+1
                    );
                    $set = "SET sectionType = :sectionType, sectionOrder = :sectionOrder";
                    if ($sectionID > 0) {
                        $data['sectionID'] = $sectionID;
                        // update
                        $sql = "UPDATE arrReportSection ".$set." WHERE sectionID = :sectionID";
                    } else {
                        // insert new section
                        $data['reportID'] = $reportID;
                        $set .= ", reportID = :reportID";
                        $sql = "INSERT INTO arrReportSection ".$set;
                    }
                    $rs = $dbh->prepare($sql);
                    $result = $rs->execute($data);
                    if (!$result) {
                        $ok = $result;
                    }
                } catch(PDOException $e) {
                    print "<div>" . $e->getMessage() . "</div>" ;
                }  
            }
        }
        echo $ok;
        break;

    case 'save_detail':
        $ok = true;
        $sectionID = $_POST['sectionID'];
        $sectionContent = $_POST['sectionContent'];
        try {
            $data = array(
                'sectionID' => $sectionID,
                'sectionContent' => $sectionContent
            );
            $sql = "INSERT INTO arrReportSectionDetail
                SET sectionContent = :sectionContent,
                sectionID = :sectionID
                ON DUPLICATE KEY update
                sectionContent = :sectionContent";
            $rs = $dbh->prepare($sql);
            $result = $rs->execute($data);
            if (!$result) {
                $ok = $result;
            }
            echo $ok;
        } catch(PDOException $e) {
            print "<div>" . $e->getMessage() . "</div>" ;
        }  
        break;

    case 'report_list':
        try {
            $yearID = $_POST['yearID'];
            $data = array(
                'schoolYearID' => $yearID
            );
            $sql = "SELECT *
                FROM arrReport
                WHERE schoolYearID = :schoolYearID";
            $rs = $dbh->prepare($sql);
            $rs->execute($data);
            $report = $rs->fetchAll();
            $res = array(
                'report' => $report
            );
            echo json_encode($res);
        } catch(PDOException $e) {
            print "<div>" . $e->getMessage() . "</div>" ;
        }  
        break;
}