<?php

/*
 * Project:
 * Author:   Andy Statham
 * Date:
 */
// in case we need more functions

class MYPDF extends TCPDF {
    //Page header
    public function Header() {
        GLOBAL $setpdf;
        
    }

    // Page footer
    public function Footer() {
        GLOBAL $setpdf;
        //$text = "A Community, Learning for Tomorrow";
        $text = $setpdf->officialName;
        $this->SetFont('helvetica', 'BI', 8);
        $this->Cell(0, 0, $text, 0, 0, 'C');
    }
}

class setpdf {

    var $class;
    var $msg;
    
    var $topMargin = 18;
    var $footerMargin = 20;
    var $pageHeight = 278;

    var $leftMargin = 13;
    //var $pageWidth = 180;
    var $critCol1 = 60;
    var $critCol2 = 20;

    var $small = 9;
    var $standard = 10;
    var $heading1 = 12;
    var $gray = 200;
    
    var $insertList = array(
        "Official name", "First name", "Preferred name", "Surname", "Class"
    );

    function setpdfInit($guid, $dbh) {
        $this->guid = $guid;
        $this->dbh = $dbh;
        $this->schoolYearID = isset($_POST['schoolYearID']) ? $_POST['schoolYearID'] : 0;
        $this->schoolYearName = $this->findSchoolYearName();
        $this->yearGroupID = $_POST['yearGroupID'];
        $this->rollGroupID = $_POST['rollGroupID'];
        $this->reportID = $_POST['reportID'];
        $this->showLeft = $_POST['showLeft'];
    
        // get teacher and roll group names
        $this->findRollGroup();
    
        $this->reportDetail = readReportDetail($dbh, $this->reportID);
        $reportRow = $this->reportDetail->fetch();
        $this->term = $reportRow['reportNum'];
        $this->gradeScale = $reportRow['gradeScale']; // id for grade scale to be used for assessment
        $this->gradeList = readGradeList($dbh, $this->gradeScale);
        $this->orientation = $reportRow['orientation'];
        if ($this->orientation == 1) {
            $this->pageWidth = 180;
            $this->pageOrientation = 'P';
        } else {
            $this->pageWidth = 240;
            $this->pageOrientation = 'L';
        }
        
        $_SESSION[$guid]['archivePath'] = $_SESSION[$guid]['absolutePath']."/archive/";
        
        //$this->term = $this->findTerm($this->dbh, $this->reportID);
        $this->yearGroupName = $this->findYearGroupName();

        $this->printList = array();
        foreach ($_POST AS $key => $year) {
            $subdata = array();
            if (substr($key, 0, 5) == 'check') {
                $subdata['studentID'] = substr($key, 5);
                $this->printList[] = $subdata;
            }
        }
        
        // download the file to local computer when button is clicked
        if (isset($_POST['downloadPDF'])) {
            $this->download();
            exit();
        }
    }
    ////////////////////////////////////////////////////////////////////////////

    
    ////////////////////////////////////////////////////////////////////////////
    function download() {

        $files = $this->printList;
        
        $basepath = "../../archive/reporting/";
        
        // remove any zip files over 5 minutes old
        $now = strtotime(date("Y-m-d H:i:s"));
        foreach (glob($basepath."*.zip") AS $filename) {
            if ((filemtime($filename) + 300) < $now) {
                unlink($filename);
            }
        }

        $overwrite = true;
        if (!file_exists($basepath)) {
            mkdir($basepath);
        }

        $destination = $basepath."download_".time()."_".intval($_SESSION[$this->guid]['gibbonPersonID']).".zip";

        //vars
        $valid_files = array();

        //if files were passed in...
        if (is_array($files)) {
            //cycle through each file
            foreach($files as $student_id) {
                //make sure the file exists
                $this->studentID = $student_id['studentID'];
                $archive_name = $this->read_archive_name();
                if ($archive_name != "") {
                    $file = $basepath.$this->schoolYearName."/".$archive_name;
                    if (file_exists($file)) {
                        $valid_files[] = $file;
                    }
                }
            }
        }
        //if we have good files...
        $ok = true;
        if (count($valid_files)) {
            //create the archive
            $zip = new ZipArchive();
            touch($destination);
            
            if ($zip->open($destination, ZipArchive::OVERWRITE) !== true) {
                $msg = "Cannot create zip file";
                $ok = false;
            }

            if ($ok) {
                //add the files
                foreach($valid_files as $file) {
                    $zip->addFile($file, basename($file));
                }
                $zip->close();

                if (!file_exists($destination)) {
                    $msg = "Zip file not found";
                    $ok = false;
                } 

                if ($ok) {
                    $file_name = basename($destination);
                    header("Content-Type: application/zip");
                    header("Content-Disposition: attachment; filename=$file_name");
                    header("Content-Length: " . filesize($destination));
                    header('HTTP/1.0 200 OK', true, 200);

                    set_time_limit(0);
                    $file = fopen($destination, "rb");

                    while(!feof($file)) {
                        print(fread($file, 1024*8));
                        //ob_flush();
                        //flush();
                    }
                    fclose($file);
                }
            }
        } else {
            $ok = false;
            $msg = "No valid files - click on the back button to continue";
        }
        if (!$ok) {
            echo $msg;
        } else {
            unset($destination);
            echo "<script>window.close();</script>";
        }
    }
    ////////////////////////////////////////////////////////////////////////////
    
    ////////////////////////////////////////////////////////////////////////////
    function read_archive_name() {
        // read database to see if there is a file recorded for this student
        try {
            $data = array(
                'studentID' => $this->studentID,
                'reportID' => $this->reportID
            );
            $sql = "SELECT *
                FROM arrArchive
                WHERE studentID = :studentID
                AND reportID = :reportID";
            $rs = $this->dbh->prepare($sql);
            $rs->execute($data);
        } catch(Exception $e) {
            print "<div>" . $e->getMessage() . "</div>" ;
            die();
        }
        // if record exists return name and date it was created
        if ($rs->rowCount() > 0) {
            $row = $rs->fetch();
            $reportName = $row['reportName'];
        } else {
            $reportName = '';
        }
        return $reportName;
    }
    ////////////////////////////////////////////////////////////////////////////
    
    ////////////////////////////////////////////////////////////////////////////
    function findSchoolYearName() {
        try {
            $data = array(
                'schoolYearID' => $this->schoolYearID
            );
            $sql = "SELECT name AS schoolYearName
                FROM gibbonSchoolYear
                WHERE gibbonSchoolYearID = :schoolYearID";
            $rs = $this->dbh->prepare($sql);
            $rs->execute($data);
            $row = $rs->fetch();
            return $row['schoolYearName'];
        } catch (Exception $ex) {
            die($ex);
        }            
    }
    ////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////
    function readStudentDetail() {
        try {
            $data = array(
                'studentID' => $this->studentID,
                'schoolYearID' => $this->schoolYearID
            );
            $sql = "SELECT surname, firstName, preferredName, officialName, dob, rollOrder
                FROM gibbonPerson
                INNER JOIN gibbonStudentEnrolment
                ON gibbonStudentEnrolment.gibbonPersonID = gibbonPerson.gibbonPersonID
                WHERE gibbonPerson.gibbonPersonID = :studentID
                AND gibbonStudentEnrolment.gibbonSchoolYearID = :schoolYearID";
            $rs = $this->dbh->prepare($sql);
            $rs->execute($data);
            return $rs;
        } catch (Exception $ex) {
            die($ex);
        }            
    }
    ////////////////////////////////////////////////////////////////////////////
    
    ////////////////////////////////////////////////////////////////////////////
    function checkFolder() {
        // check if archive folder exists

        $path = $_SESSION[$this->guid]['archivePath'];
        if (!is_dir($path)) {
            mkdir($path);
        }
        //makeIndex($path);

        $path = $path.'/reporting';
        if (!is_dir($path)) {
            mkdir($path);
        }
        //makeIndex($path);

        $path = $path.'/'.$this->schoolYearName;
        if (!is_dir($path)) {
            mkdir($path);
        }
        //makeIndex($path);
    }

    function checkLanguage($text) {
        $language = 'english';
        if (preg_match("/\p{Han}+/u", $text)) {
            $language = 'chinese';
        }
        return strtolower($language);
    }

    function makeIndex($path) {
       // check there is an index file
        $index = 'index.html';
        $pathIndex = $path.'/'.$index;
        if (!file_exists($pathIndex)) {
            //echo $pathIndex;
            //echo "<br>";
            $handle = fopen($pathIndex, 'w') or die('Cannot open file:  '.$pathIndex); //implicitly creates file
            fclose($handle);
        }
    }

    ////////////////////////////////////////////////////////////////////////////////
    function findReportName() {
        try {
            $data = array('reportID' => $this->reportID);
            $sql = "SELECT *
                FROM arrReport
                WHERE arrReportID = :reportID";
            $rs = $this->dbh->prepare($sql);
            $rs->execute($data);
        } catch (Exception $ex) {
            die($ex);
        }
        $this->reportName = '';
        $this->reportDate = '';
        if ($rs->rowCount() > 0) {
            $row = $rs->fetch();
            $this->reportName = substr($row['arrReportName'], strlen($this->yearGroupName));
            $this->reportDate = $row['arrReportDate'];
            $this->dateStart = $row['dateStart'];
            $this->dateEnd = $row['dateEnd'];
        }
    }
    // -------------------------------------------------------------------------

    // -------------------------------------------------------------------------
    function findTerm() {
        // find which term the current report is for
        try {
            $data = array('reportID' => $this->reportID);
            $sql = "SELECT reportNum
                FROM arrReport
                WHERE arrReportID = :reportID";
            $rs = $this->dbh->prepare($sql);
            $rs->execute($data);
            $row = $rs->fetch();
            return $row['arrReportNum'];
        } catch (Exception $ex) {
            die($ex);
        }
    }
    // -------------------------------------------------------------------------

    // -------------------------------------------------------------------------
    function findRepAccess() {
        // check if use should have editing access to the reports

        // check if administrator
        $admin = read_access($this->dbh, 'admin', $_SESSION[$this->guid]["gibbonPersonID"]);

        //   or slt
        $slt = read_access($this->dbh, 'senior', $_SESSION[$this->guid]["gibbonPersonID"]);

        $access = 1;
        if ($admin || $slt) {
            $access = 2;
        }

        return $access;
    }
    // -------------------------------------------------------------------------

    // -------------------------------------------------------------------------
    function findRollGroup() {
        try {
            $data = array(
                'rollGroupID' => $this->rollGroupID
            );
            $sql = "SELECT name, CONCAT(firstName, ' ', surname) AS teacherName
                FROM gibbonFormGroup
                INNER JOIN gibbonPerson
                ON gibbonFormGroup.gibbonPersonIDTutor = gibbonPerson.gibbonPersonID
                WHERE gibbonFormGroupID = :rollGroupID";
            //print $sql;
            //print_r($data);
            $rs = $this->dbh->prepare($sql);
            $rs->execute($data);
        } catch (Exception $ex) {
            die($ex);
        }
        $rollGroupName = '';
        $classTeacher = '';
        if ($rs->rowCount() > 0) {
            $row = $rs->fetch();
            $rollGroupName = $row['name'];
            $classTeacher = $row['teacherName'];
        }
        $this->rollGroupName = $rollGroupName;
        $this->classTeacher = $classTeacher;
    }
    // -------------------------------------------------------------------------

    // -------------------------------------------------------------------------
    function findYearGroupName() {
        // find which term the current report is for
        try {
            $data = array('yearGroupID' => $this->yearGroupID);
            $sql = "SELECT name
                FROM gibbonYearGroup
                WHERE gibbonYearGroupID = :yearGroupID";
            $rs = $this->dbh->prepare($sql);
            $rs->execute($data);
            $row = $rs->fetch();
            return $row['name'];
        } catch (Exception $ex) {
            die($ex);
        }            
    }
    ////////////////////////////////////////////////////////////////////////////////


    ////////////////////////////////////////////////////////////////////////////
    // text section
    ////////////////////////////////////////////////////////////////////////////
    function textSection($pdf) {
        // read details
        try {
            $data = array(
                'sectionID' => $this->sectionID
            );
            $sql = "SELECT *
                FROM arrReportSectionDetail
                WHERE sectionID = :sectionID";
            $rs = $this->dbh->prepare($sql);
            $rs->execute($data);
        } catch (Exception $ex) {
            die($ex);
        }
        if ($rs->rowCount() > 0) {
            $row = $rs->fetch();
            $html = $row['sectionContent'];
            // replace codes
            $html = str_replace("[Preferred name]", $this->preferredName, $html);
            $html = str_replace("[First name]", $this->firstName, $html);
            $html = str_replace("[Surname]", $this->surname, $html);
            $html = str_replace("[Official name]", $this->officialName, $html);
            $html = str_replace("[Class]", $this->rollGroupName, $html);
            $html = str_replace("[Roll Number]", $this->rollOrder, $html);
            $html = str_replace("./archive/", "../../archive/", $html);
            $pdf->writeHTML($html);
        }
    }

    ////////////////////////////////////////////////////////////////////////////
    // subject section
    ////////////////////////////////////////////////////////////////////////////
    function subjectReportRow($pdf) {
        $sublist = readStudentClassListNoRepeat($this->dbh, $this->studentID, $this->schoolYearID);
        $this->rowHeight = 12;
        $this->commentHeight = 46;
        foreach ($sublist AS $row) {
            $subjectID = $row['subjectID'];
            $subjectName = $row['subjectName'];
            $teacherName = $row['teacherName'];
            //$teacherName = getTeacherName($this->dbh, $row['classID']);
            $subreport = readSubReport($this->dbh, $this->studentID, $subjectID, $this->reportID);
            $criteriaList = readCriteriaGrade($this->dbh, $this->studentID, $subjectID, $this->reportID);
            $row_subject = $subreport->fetch();
            $comment = $row_subject['subjectComment'];
            $html = '';
            $html .= '<style>';
                $html .= 'body{font-size:12px; font-family: sans-serif;}';
                $html .= '.subjectname {color:#999999; font-weight:bold;}';
                $html .= '.teachername {font-style:italic;}';
                $html .= '.gradeTable table{width:100%;}';
                $html .= 'td {font-size:10px;}';
                $html .= '.gradeTable th {border: 1px solid #cccccc; background-color: #dddddd; padding:1px;}';
                $html .= '.gradeTable td {border: 1px solid #cccccc; padding:1px;}';
                $html .= '.col1 {text-align:left; width:70%}';
                $html .= '.col2 {text-align:center; width:30%}';
                $html .= '.commentHead {width:'. $this->pageWidth .'mm}';
            $html .= '</style>';

            if ($criteriaList->rowCount() > 0 || $comment != '') {
                $html .= '<table cellpadding="4">';
                    $html .= '<tr><td class="subjectname">'.$subjectName.'</td></tr>';
                    $html .= '<tr><td class="teachername">'.$teacherName.'</td></tr>';
                $html .= '</table>';

                if ($criteriaList->rowCount() > 0) {
                    $html .= '<table class="gradeTable" cellpadding="4">';
                        $html .= '<tr>';
                            $html .= '<th class="col1">Criteria</th>';
                            $html .= '<th class="col2">Mark/Grade</th>';
                        $html .= '</tr>';

                        while ($row_criteria = $criteriaList->fetch()) {
                            $html .= '<tr>';
                            $html .= '<td class="col1">'.$row_criteria['criteriaName'].'</td>';
                            
                            //$grade = findGrade($this->gradeList, $row_criteria['gradeID']);
                            //$mark = $row_criteria['mark'];
                            //$percent = $row_criteria['percent'];
                            $html .= '<td class="col2">';
                                if ($row_criteria['criteriaType'] == 0) {
                                    $html .= $row_criteria['grade'];
                                } else {
                                    $html .= $row_criteria['mark'];
                                }
                            $html .= "</td>"; 
                            $html .= "</tr>";
                        }
                        
                    $html .= '</table>';
                }

                if ($comment != '') {
                    $html .= '<table class="gradeTable" cellpadding="4">';
                        $html .= '<tr>';
                            $html .= '<th colspan="2">Comment</th>';
                        $html .= '</tr>';
                        $html .= '<tr>';
                            $html .= '<td colspan="2">';
                                $html .= nl2br($comment);
                            $html .= '</td>';
                        $html .= '</tr>';
                    $html .= '</table>';
                }
                $html .= '<div>&nbsp;</div>';

                $cp =  $pdf->getPage(); // current page number
                $pdf->SetFont('stsongstdlight', '', 8);
                $pdf->startTransaction();
                $pdf->writeHTML($html, true, false, true, false, '');
                if ($pdf->getPage() != $cp) {
                    $pdf->rollBackTransaction(true);
                    $pdf->addPage();
                    $pdf->setY($this->topMargin);
                    $pdf->writeHTML($html, true, false, true, false, '');
                } else {
                    $pdf->commitTransaction();
                }
            }
        }
    }
    ////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////
    function subjectReportColumn($pdf) {
        $sublist = readStudentClassListNoRepeat($this->dbh, $this->studentID, $this->schoolYearID);
        $this->rowHeight = 12;
        $this->commentHeight = 46;
        $html = '';
        //$col1Width = $this->pageWidth * 60 /100;
        //$col2Width = $this->pageWidth * 40 /100;
        $numcol = 0;
        $colhead = array();
        foreach ($sublist AS $row) {
            print_r($row);
            print "<br>";
            $subjectID = $row['subjectID'];
            $criteriaList = readCriteriaGrade($this->dbh, $this->studentID, $subjectID, $this->reportID);
            if (count($criteriaList) > $numcol && count($colhead) == 0) {
                while($row = $criteriaList->fetch()) {
                    $colhead[] = $row['criteriaName'];
                }
            }
        }
        die();
        if (count($colhead) > 0) {
            $gradeWidth = (50 / count($colhead));
        } else {
            $gradeWidth = 0;
        }

        $html .= '<style>';
            $html .= 'body{font-size:12px; font-family: sans-serif;}';
            $html .= '.subjectname {color:#999999; font-weight:bold;}';
            $html .= '.teachername {font-style:italic;}';
            $html .= '.smalltext {font-size:11px;}';
            $html .= '.gradeTable table{width:100%;}';
            $html .= '.gradeTable th {border: 1px solid #cccccc; background-color: #dddddd; padding:1px;}';
            $html .= '.gradeTable td {border: 1px solid #cccccc; padding:1px;}';
            //$html .= '.col1 {text-align:left; width:110mm;}';
            //$html .= '.col2 {text-align:center; width:70mm;}';
            $html .= '.col1 {text-align:left; width:30%}';
            $html .= '.col2 {text-align:left; width:20%}';
            $html .= '.col3 {text-align:center; width:'.$gradeWidth.'%}';
            $html .= '.commentHead {width:100%;}';
        $html .= '</style>';

        $html .= '<table class="gradeTable" cellpadding="4">';
            $html .= '<tr>';
                $html .= '<th class="col1">Subject</th>';
                $html .= '<th class="col2">Teacher</th>';
                for ($i=0; $i<count($colhead); $i++) {
                    $html .= '<th class="col3">'.$colhead[$i].'</th>';
                }
            $html .= '</tr>';

            foreach ($sublist AS $row) {
                $subjectID = $row['subjectID'];
                $subjectName = $row['subjectName'];
                $teacherName = $row['teacherName'];
                //$teacherName = getTeacherName($this->dbh, $row['classID']);
                $subreport = readSubReport($this->dbh, $this->studentID, $subjectID, $this->reportID);
                $criteriaList = readCriteriaGrade($this->dbh, $this->studentID, $subjectID, $this->reportID);
                $row_subject = $subreport->fetch();
                $comment = $row_subject['subjectComment'];

                if ($criteriaList->rowCount() > 0 || $comment != '') {
                    
                        $html .= '<tr>';
                            $html .= '<td class="col1">'. $row['subjectName'].'</td>';
                            $html .= '<td class="col2">'.$row['teacherName'].'</td>';

                            while ($row_criteria = $criteriaList->fetch()) {
                                $html .= '<td class="col3">';
                                if ($row_criteria['criteriaType'] == 0) {
                                    $html .= $row_criteria['grade'];
                                } else {
                                    $html .= $row_criteria['mark'];
                                }
                                $html .= '</td>';
                                /*
                                $criteriaName = $row_criteria['criteriaName'];
                                $grade = findGrade($this->gradeList, $row_criteria['gradeID']);
                                $html .= '<td class="col3">';
                                    $html .= $grade;
                                $html .= "</td>";
                                 * 
                                 */
                            }
                        $html .= '</tr>';
                    
                }
            }
        $html .= '</table>';

        $cp =  $pdf->getPage(); // current page number
        $pdf->startTransaction();
        $pdf->writeHTML($html, true, false, true, false, '');
        if ($pdf->getPage() != $cp) {
            $pdf->rollBackTransaction(true);
            $pdf->addPage();
            $pdf->setY($this->topMargin);
            $pdf->writeHTML($html, true, false, true, false, '');
        } else {
            $pdf->commitTransaction();
        }
        
    }
    ////////////////////////////////////////////////////////////////////////////
    
    
    ////////////////////////////////////////////////////////////////////////////
    // pastoral section
    ////////////////////////////////////////////////////////////////////////////
    function pastoralReport($pdf) { 
        //$sublist = readStudentClassList($this->dbh, $this->studentID, $this->schoolYearID);
        $this->rowHeight = 12;
        $this->commentHeight = 46;
        $html = '';
        $html .= '<style>';
            $html .= 'body{font-size:12px; font-family: sans-serif;}';
            $html .= '.subjectname {color:#999999; font-weight:bold;}';
            $html .= '.teachername {font-style:italic;}';
            $html .= '.smalltext {font-size:11px;}';
            $html .= '.gradeTable table{width:100%;}';
            $html .= '.gradeTable th {border: 1px solid #cccccc; background-color: #dddddd; padding:1px;}';
            $html .= '.gradeTable td {border: 1px solid #cccccc; padding:1px;}';
            $html .= '.col1 {text-align:left; width:60%}';
            $html .= '.col2 {text-align:center; width:40%;}';
            $html .= '.commentHead {width:100%;}';
        $html .= '</style>';
        
        $subjectID = 0;
        $subjectName = "Pastoral";
        $teacherName = $this->classTeacher;
        $subreport = readSubReport($this->dbh, $this->studentID, 0, $this->reportID);
        //$criterialist = readCriteriaList($this->dbh, $subjectID);
        $criteriaList = readCriteriaGrade($this->dbh, $this->studentID, 0, $this->reportID);
        $row_subject = $subreport->fetch();
        $comment = $row_subject['subjectComment'];

        if ($criteriaList->rowCount() > 0 || $comment != '') {
            $html .= '<table cellpadding="4">';
                $html .= '<tr><td class="subjectname">'.$subjectName.'</td></tr>';
                $html .= '<tr><td class="teachername">'.$teacherName.'</td></tr>';
            $html .= '</table>';

            if ($criteriaList->rowCount() > 0) {
                $html .= '<table class="gradeTable" cellpadding="4">';
                    $html .= '<tr>';
                        $html .= '<th class="col1">Criteria</th>';
                        $html .= '<th class="col2">Grade</th>';
                    $html .= '</tr>';

                    while ($row_criteria = $criteriaList->fetch()) {
                        $criteriaName = $row_criteria['criteriaName'];
                        $grade = findGrade($this->gradeList, $row_criteria['gradeID']);
                        $html .= '<tr>';
                            $html .= '<td class="col1">'.$criteriaName.'</td>';
                            $html .= '<td class="col2">';
                                $html .= $grade;
                            $html .= "</td>";
                        $html .= "</tr>";
                    }
                $html .= '</table>';
            }

            if ($comment != '') {
                $html .= '<table class="gradeTable" cellpadding="4">';
                    $html .= '<tr>';
                        $html .= '<th colspan="2" class="commentHead">Comment</th>';
                    $html .= '</tr>';
                    $html .= '<tr>';
                        $html .= '<td colspan="2">';
                            $html .= nl2br($comment);
                        $html .= '</td>';
                    $html .= '</tr>';
                $html .= '</table>';
            }
            $html .= '<div>&nbsp;</div>';
        }
        
        $cp =  $pdf->getPage(); // current page number
        $pdf->startTransaction();
        $pdf->writeHTML($html, true, false, true, false, '');
        if ($pdf->getPage() != $cp) {
            $pdf->rollBackTransaction(true);
            $pdf->addPage();
            $pdf->setY($this->topMargin);
            $pdf->writeHTML($html, true, false, true, false, '');
        } else {
            $pdf->commitTransaction();
        }
    }
    ////////////////////////////////////////////////////////////////////////////
    
    ////////////////////////////////////////////////////////////////////////////
    function readCriteriaList() {
        try {
            $data = array(
                "classID" => $this->classID,
                "gradesetID" => 1,
                "reportSubjectID" => $this->reportSubjectID
            );
            $sql = "SELECT arrCriteria.arrCriteriaID, arrCriteriaName, arrGradesetDetailID, arrGrade
                FROM gibbonCourseClass
                INNER JOIN arrCriteria
                ON gibbonCourseClass.gibbonCourseID = arrCriteria.arrCourseID

                LEFT JOIN
                (
                SELECT arrCriteriaID, arrGradesetDetail.arrGradesetDetailID, arrGrade
                FROM arrReportGrade
                LEFT JOIN arrGradesetDetail
                ON arrReportGrade.arrGradesetDetailID = arrGradesetDetail.arrGradesetDetailID
                WHERE arrReportSubjectID = :reportSubjectID
                AND arrGradesetID = :gradesetID
                ) AS grade

                ON grade.arrCriteriaID = arrCriteria.arrCriteriaID

                WHERE gibbonCourseClassID = :classID
                ORDER BY arrCriteriaOrder";
            //print $sql;
            //print_r($data);
            $rs = $this->dbh->prepare($sql);
            $rs->execute($data);
            return $rs;
        } catch (Exception $ex) {
            die($ex);
        }            
    }

    function readReport() {
        try {
            $data = array(
                'classID' => $this->classID,
                'reportID' => $this->reportID,
                'studentID' => $this->studentID
            );
            $sql = "SELECT arrReportSubjectID, CONCAT(title, ' ', LEFT(preferredName, 1), '.', surname) AS teacherName, arrSubjectComment
                FROM arrReportSubject
                LEFT JOIN gibbonPerson
                ON arrReportSubject.arrTeacherID = gibbonPerson.gibbonPersonID
                WHERE arrStudentID = :studentID
                AND arrClassID = :classID
                AND arrReportID = :reportID";
            //print $sql;
            //print_r($data);
            $rs = $this->dbh->prepare($sql);
            $rs->execute($data);
            return $rs;
        } catch (Exception $ex) {
            die($ex);
        }            
    }


    function readSubjectList() {
        try {
            $data = array(
                'studentID' => $this->studentID,
                'schoolYearID' => $this->schoolYearID
            );
            $sql = "SELECT DISTINCT gibbonCourse.gibbonCourseID, gibbonCourseClassPerson.gibbonCourseClassID,
                gibbonCourse.name, arrCourseType, arrRowHeight, arrCommentHeight
                FROM gibbonCourseClass
                INNER JOIN gibbonCourseClassPerson
                ON gibbonCourseClass.gibbonCourseClassID = gibbonCourseClassPerson.gibbonCourseClassID
                INNER JOIN gibbonCourse
                ON gibbonCourse.gibbonCourseID = gibbonCourseClass.gibbonCourseID
                INNER JOIN arrSubjectDetail
                ON arrSubjectDetail.arrCourseID = gibbonCourseClass.gibbonCourseID
                WHERE gibbonCourse.gibbonSchoolYearID = :schoolYearID
                AND gibbonCourseClassPerson.gibbonPersonID = :studentID
                AND gibbonCourseClass.reportable = 'Y'

                AND arrSubjectDetail.arrCourseType <> '-'
                ORDER BY arrSubjectPosition";
            //print $sql;
            //print_r($data);
            $rs = $this->dbh->prepare($sql);
            $rs->execute($data);
            return $rs;
        } catch (Exception $ex) {
            die($ex);
        }            
    }
    
    function readReportSetting() {
        
        $rs = $this->dbh->prepare($sql);
        $rs->execute($data);
        return $rs;
    }
    
    ////////////////////////////////////////////////////////////////////////////////
    function readReportSectionList() {
        // read list of sections that make up the report
        try {
            $data = array(
                'reportID' => $this->reportID
            );
            $sql = "SELECT *
                FROM arrReportSection
                INNER JOIN arrReportSectionType
                ON arrReportSectionType.reportSectionTypeID = arrReportSection.sectionType
                WHERE reportID = :reportID
                ORDER BY sectionOrder";
            $rs = $this->dbh->prepare($sql);
            $rs->execute($data);
            return $rs;
        } catch (Exception $ex) {
            die($ex);
        }
    }
    ////////////////////////////////////////////////////////////////////////////////
}

function makeIndex($path) {
   // check there is an index file
    $index = 'index.html';
    $pathIndex = $path.'/'.$index;
    if (!file_exists($pathIndex)) {
        echo $pathIndex;
        echo "<br>";
        $handle = fopen($pathIndex, 'w') or die('Cannot open file:  '.$pathIndex); //implicitly creates file
        fclose($handle);
    }
}

////////////////////////////////////////////////////////////////////////////////
function findTerm($reportID) {
    // find which term the current report is for
    try {
        $data = array('reportID' => $reportID);
        $sql = "SELECT arrReportNum
            FROM arrReport
            WHERE arrReportID = :reportID";
        $rs = $this->dbh->prepare($sql);
        $rs->execute($data);
        $row = $rs->fetch();
        return $row['arrReportNum'];
    } catch (Exception $ex) {
        die($ex);
    }    
}
// -------------------------------------------------------------------------

// -------------------------------------------------------------------------
function findYearGroupName($yearGroupID) {
    // find which term the current report is for
    try {
        $data = array('yearGroupID' => $yearGroupID);
        $sql = "SELECT nameShort
            FROM gibbonYearGroup
            WHERE gibbonYearGroupID = :yearGroupID";
        $rs = $this->dbh->prepare($sql);
        $rs->execute($data);
        $row = $rs->fetch();
        return $row['nameShort'];
    } catch (Exception $ex) {
        die($ex);
    }
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function readStudentClassListNoRepeat($dbh, $studentID, $schoolYearID) {
    // read classes/subjects attended by selected student
    try {
        $data = array(
            'studentID' => $studentID,
            'schoolYearID' => $schoolYearID
        );
        $sql = "SELECT gibbonCourseClassPerson.gibbonCourseClassID AS classID, 
            gibbonCourseClass.name AS subjectClassName, 
            gibbonCourseClass.nameShort AS subjectClassNameShort, 
            gibbonCourse.gibbonCourseID AS subjectID, 
            gibbonCourse.name AS subjectName,
            teacher.gibbonPersonID,
            CONCAT(gibbonPerson.surname, '.', LEFT(gibbonPerson.firstName,1)) AS teacherName
            FROM gibbonCourseClassPerson 
            INNER JOIN gibbonCourseClass 
            ON gibbonCourseClass.gibbonCourseClassID = gibbonCourseClassPerson.gibbonCourseClassID INNER JOIN gibbonCourse 
            ON gibbonCourse.gibbonCourseID = gibbonCourseClass.gibbonCourseID 
            INNER JOIN gibbonCourseClassPerson AS teacher 
            ON teacher.gibbonCourseClassID = gibbonCourseClass.gibbonCourseClassID 
            INNER JOIN arrSubjectOrder
            ON arrSubjectOrder.subjectID = gibbonCourse.gibbonCourseID
            LEFT JOIN gibbonPerson
            ON gibbonPerson.gibbonPersonID = teacher.gibbonPersonID
            WHERE gibbonCourseClassPerson.gibbonPersonID = :studentID
            AND gibbonCourse.gibbonSchoolYearID = :schoolYearID
            AND gibbonCourseClass.reportable = 'Y'
            AND teacher.role = 'Teacher'
            GROUP BY gibbonCourse.gibbonCourseID
            ORDER BY arrSubjectOrder.subjectOrder";
        //print $sql;
        //print_r($data);
        $rs = $dbh->prepare($sql);
        $rs->execute($data);
    } catch (Exception $ex) {
        die($ex);
    }        
    return $rs;
    /*
    // there maybe multiple teachers so reduce this to one row per class
    $classList = array();
    $rowdata = array();
    $lastClass = 0;
    $teacherName = '';
    while ($row = $rs->fetch()) {
        if ($row['classID'] != $lastClass) {
            if ($lastClass > 0) {
                $rowdata['teacherName'] = $teacherName;
                $classList[] = $rowdata;
            }
            $lastClass = $row['classID'];
            $rowdata = [];
            $rowdata['classID'] = $row['classID'];
            $rowdata['subjectClassName'] = $row['subjectClassName'];
            $rowdata['subjectClassNameShort'] = $row['subjectClassNameShort'];
            $rowdata['subjectID'] = $row['subjectID'];
            $rowdata['subjectName'] = $row['subjectName'];
            $teacherName = '';
            $comma = "";
        }
        $teacherName .= $comma.$row['teacherName'];
        $comma = ", ";
    }
    $rowdata['teacherName'] = $teacherName;
    $classList[] = $rowdata;
    return $classList;
     * 
     */
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function get_browser_name($user_agent) {
    if (strpos($user_agent, 'Opera') || strpos($user_agent, 'OPR/')) return 'Opera';
    elseif (strpos($user_agent, 'Edge')) return 'Edge';
    elseif (strpos($user_agent, 'Chrome')) return 'Chrome';
    elseif (strpos($user_agent, 'Safari')) return 'Safari';
    elseif (strpos($user_agent, 'Firefox')) return 'Firefox';
    elseif (strpos($user_agent, 'MSIE') || strpos($user_agent, 'Trident/7')) return 'Internet Explorer';
    
    return 'Other';
}