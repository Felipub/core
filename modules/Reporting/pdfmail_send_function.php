<?php

/*
 * Project:
 * Author:   Andy Statham
 * Date:
 */
// in case we need more functions

class setpdf {

    var $class;
    var $msg;

    var $schoolYearID;

    function setpdfInit($guid, $connection2) {
        $this->guid = $guid;
        $this->connection2 = $connection2;

        $this->schoolYearID = $_POST['schoolYearID'];
        $this->schoolYearName = $this->findSchoolYearName();
        $this->yearGroupID = $_POST['yearGroupID'];
        $this->rollGroupID = $_POST['rollGroupID'];
        $this->reportID = $_POST['reportID'];
        $this->showLeft = $_POST['showLeft'];
    
        // get teacher and roll group names
        $this->findRollGroup();
        
        $this->reportDetail = readReportDetail($connection2, $this->reportID);
        $reportRow = $this->reportDetail->fetch();
        $this->term = $reportRow['reportNum'];
        
        //$this->term = $this->findTerm($connection2, $this->reportID);
        $this->yearGroupName = $this->findYearGroupName($connection2, $this->yearGroupID);

        $this->printList = array();
        foreach ($_POST AS $key => $year) {
            $subdata = array();
            if (substr($key, 0, 5) == 'check') {
                $subdata['studentID'] = substr($key, 5);
                $this->printList[] = $subdata;
            }
        }
    }
    ////////////////////////////////////////////////////////////////////////////
    
    ////////////////////////////////////////////////////////////////////////////
    function readParentDetail() {
        try {
            $data = array(
                'studentID' => $this->studentID
            );
            $sql = "SELECT gibbonPerson.title,
                gibbonPerson.surname,
                gibbonPerson.email
                FROM gibbonFamilyChild
                INNER JOIN gibbonFamilyAdult
                ON gibbonFamilyAdult.gibbonFamilyID = gibbonFamilyChild.gibbonFamilyID
                INNER JOIN gibbonPerson
                ON gibbonPerson.gibbonPersonID = gibbonFamilyAdult.gibbonPersonID
                WHERE gibbonFamilyChild.gibbonPersonID = :studentID";
            $rs = $this->connection2->prepare($sql);
            $rs->execute($data); 
            return $rs;
        } catch (Exception $ex) {
            die($ex);
        }            
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
            $rs = $this->connection2->prepare($sql);
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
            $rs = $this->connection2->prepare($sql);
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

        $path = '../../archive';
        if (!is_dir($path)) {
            mkdir($path);
        }
        makeIndex($path);

        $path = $path.'/reporting';
        if (!is_dir($path)) {
            mkdir($path);
        }
        makeIndex($path);

        $path = $path.'/'.$this->schoolYearName;
        if (!is_dir($path)) {
            mkdir($path);
        }
        makeIndex($path);
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
            echo $pathIndex;
            echo "<br>";
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
            $rs = $this->connection2->prepare($sql);
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
            $rs = $this->connection2->prepare($sql);
            $rs->execute($data);
        } catch (Exception $ex) {
            die($ex);
        }
        $row = $rs->fetch();
        return $row['arrReportNum'];
    }
    // -------------------------------------------------------------------------

    // -------------------------------------------------------------------------
    function findRepAccess() {
        // check if use should have editing access to the reports

        // check if administrator
        $admin = read_access($this->connection2, 'admin', $_SESSION[$this->guid]["gibbonPersonID"]);

        //   or slt
        $slt = read_access($this->connection2, 'senior', $_SESSION[$this->guid]["gibbonPersonID"]);

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
            $rs = $this->connection2->prepare($sql);
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
            $rs = $this->connection2->prepare($sql);
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
            $rs = $this->connection2->prepare($sql);
            $rs->execute($data);
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
                $pdf->writeHTML($html);
            }
        } catch (Exception $ex) {
            die($ex);
        }            
    }

    ////////////////////////////////////////////////////////////////////////////
    // subject section
    ////////////////////////////////////////////////////////////////////////////
    function subjectReportRow($pdf) {
        $sublist = readStudentClassListNoRepeat($this->connection2, $this->studentID, $this->schoolYearID);
        $this->rowHeight = 12;
        $this->commentHeight = 46;
        $html = '';
        //$col1Width = $this->pageWidth * 60 /100;
        //$col2Width = $this->pageWidth * 40 /100;
        
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
            $html .= '.col1 {text-align:left; width:60%}';
            $html .= '.col2 {text-align:center; width:40%}';
            $html .= '.commentHead {width:'. $this->pageWidth .'mm}';
        $html .= '</style>';
        
        foreach ($sublist AS $row) {
            $subjectID = $row['subjectID'];
            $subjectName = $row['subjectName'];
            $teacherName = $row['teacherName'];
            //$teacherName = getTeacherName($this->connection2, $row['classID']);
            $subreport = readSubReport($this->connection2, $this->studentID, $subjectID, $this->reportID);
            $criteriaList = readCriteriaGrade($this->connection2, $this->studentID, $subjectID, $this->reportID);
            $row_subject = $subreport->fetch();
            $comment = $row_subject['subjectComment'];
            
            if ($criteriaList->rowCount() > 0 || $comment != '') {
                $html .= '<table>';
                    $html .= '<tr><td class="subjectname">'.$subjectName.'</td></tr>';
                    $html .= '<tr><td class="teachername">'.$teacherName.'</td></tr>';
                $html .= '</table>';

                if ($criteriaList->rowCount() > 0) {
                    $html .= '<table class="gradeTable">';
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
                    $html .= '<table class="gradeTable">';
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

            }
        }
        
        $pdf->writeHTML($html, true, false, true, false, '');
    }
    ////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////
    function subjectReportColumn($pdf) {
        $sublist = readStudentClassListNoRepeat($this->connection2, $this->studentID, $this->schoolYearID);
        $this->rowHeight = 12;
        $this->commentHeight = 46;
        $html = '';
        //$col1Width = $this->pageWidth * 60 /100;
        //$col2Width = $this->pageWidth * 40 /100;
        $numcol = 0;
        $colhead = array();
        foreach ($sublist AS $row) {
            $subjectID = $row['subjectID'];
            $criteriaList = readCriteriaGrade($this->connection2, $this->studentID, $subjectID, $this->reportID);
            if (count($criteriaList) > $numcol && count($colhead) == 0) {
                while($row = $criteriaList->fetch()) {
                    $colhead[] = $row['criteriaName'];
                }
            }
        }
        $gradeWidth = (50 / count($colhead));
                
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
        
        
         
        $html .= '<table class="gradeTable">';
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
                //$teacherName = getTeacherName($this->connection2, $row['classID']);
                $subreport = readSubReport($this->connection2, $this->studentID, $subjectID, $this->reportID);
                $criteriaList = readCriteriaGrade($this->connection2, $this->studentID, $subjectID, $this->reportID);
                $row_subject = $subreport->fetch();
                $comment = $row_subject['subjectComment'];

                if ($criteriaList->rowCount() > 0 || $comment != '') {


                    if ($criteriaList->rowCount() > 0) {
                        $html .= '<tr>';
                            $html .= '<td class="col1">'. $row['subjectName'].'</td>';
                            $html .= '<td class="col2">'.$row['teacherName'].'</td>';

                            while ($row_criteria = $criteriaList->fetch()) {
                                $criteriaName = $row_criteria['criteriaName'];
                                $grade = findGrade($this->gradeList, $row_criteria['gradeID']);
                                $html .= '<td class="col3">';
                                    $html .= $grade;
                                $html .= "</td>";
                            }
                        $html .= '</tr>';
                    }
                }
            }
        $html .= '</table>';
        
        $pdf->writeHTML($html, true, false, true, false, '');
    }
    ////////////////////////////////////////////////////////////////////////////
    
    
    ////////////////////////////////////////////////////////////////////////////
    // pastoral section
    ////////////////////////////////////////////////////////////////////////////
    function pastoralReport($pdf) { 
        //$sublist = readStudentClassList($this->connection2, $this->studentID, $this->schoolYearID);
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
        $subreport = readSubReport($this->connection2, $this->studentID, 0, $this->reportID);
        //$criterialist = readCriteriaList($connection2, $subjectID);
        $criteriaList = readCriteriaGrade($this->connection2, $this->studentID, 0, $this->reportID);
        $row_subject = $subreport->fetch();
        $comment = $row_subject['subjectComment'];

        if ($criteriaList->rowCount() > 0 || $comment != '') {
            $html .= '<table>';
                $html .= '<tr><td class="subjectname">'.$subjectName.'</td></tr>';
                $html .= '<tr><td class="teachername">'.$teacherName.'</td></tr>';
            $html .= '</table>';

            if ($criteriaList->rowCount() > 0) {
                $html .= '<table class="gradeTable">';
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
                $html .= '<table class="gradeTable">';
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
        //echo $html;
        $pdf->writeHTML($html, true, false, true, false, '');
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
            $rs = $this->connection2->prepare($sql);
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
            $rs = $this->connection2->prepare($sql);
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
            $rs = $this->connection2->prepare($sql);
            $rs->execute($data);
            return $rs;
        } catch (Exception $ex) {
            die($ex);
        }            
    }
    
    function readReportSetting() {
        
        $rs = $this->connection2->prepare($sql);
        $rs->execute($data);
        return $rs;
    }
    
    ////////////////////////////////////////////////////////////////////////////////
    function readReportSectionList($connection2) {
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
            $rs = $connection2->prepare($sql);
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
        $rs = $this->connection2->prepare($sql);
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
        $rs = $this->connection2->prepare($sql);
        $rs->execute($data);
        $row = $rs->fetch();
        return $row['nameShort'];
    } catch (Exception $ex) {
        die($ex);
    }        
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
function readStudentClassListNoRepeat($connection2, $studentID, $schoolYearID) {
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
            CONCAT(gibbonPerson.preferredName,' ',gibbonPerson.surname) AS teacherName

            FROM gibbonCourseClassPerson 
            INNER JOIN gibbonCourseClass 
            ON gibbonCourseClass.gibbonCourseClassID = gibbonCourseClassPerson.gibbonCourseClassID INNER JOIN gibbonCourse 
            ON gibbonCourse.gibbonCourseID = gibbonCourseClass.gibbonCourseID 
            INNER JOIN gibbonCourseClassPerson AS teacher 
            ON teacher.gibbonCourseClassID = gibbonCourseClass.gibbonCourseClassID 

            LEFT JOIN gibbonPerson
            ON gibbonPerson.gibbonPersonID = teacher.gibbonPersonID
            WHERE gibbonCourseClassPerson.gibbonPersonID = :studentID
            AND gibbonCourse.gibbonSchoolYearID = :schoolYearID
            AND gibbonCourseClass.reportable = 'Y'
            AND teacher.role = 'Teacher'
            ORDER BY gibbonCourse.name";
        $rs = $connection2->prepare($sql);
        $rs->execute($data);
    } catch (Exception $ex) {
        die($ex);
    }        
    
    // there maybe multiple teachers so reduce this to one row per class
    $classList = array();
    $rowdata = array();
    $lastClass = 0;
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
}
////////////////////////////////////////////////////////////////////////////////