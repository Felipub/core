<?php
/*
 * pastoral reports
 */

class pastoral {

    var $class;
    var $msg;

    var $allClass = 'xxx';
    var $eal = 'eal';
    var $noComment = "No comment written";

    var $classView;
    var $studView;
    var $view;

    var $schoolYearName;
    var $classTeacherID;
    var $yearGroupName;

    function pastoralInit($guid, $dbh) {
        $this->guid = $guid;
        $this->dbh = $dbh;
        
        // get value of selected year

        $this->classView    = $_SESSION[$guid]['classView'];
        $this->studView     = $_SESSION[$guid]['studView'];
        $this->maxGrade     = $_SESSION[$guid]['maxGrade'];
        $this->repView      = $_SESSION[$guid]['repView'];
        $this->repEdit      = $_SESSION[$guid]['repEdit'];
        
        // check if user is viewing own reports or those of another teacher
        $this->teacherID = getTeacherID($guid);
        
        // check user's role to see if they have access to these reports
        $this->role = $_SESSION[$guid]['gibbonRoleIDCurrent'];
        
        $this->schoolYearID = getSchoolYearID($this->dbh, $schoolYearName, $this->currentYearID);

        // id of student being viewed
        $this->studentID = getStudentID();

        // check if left students should be shown
        $this->showLeft = getLeft();
        
        $this->rollGroupID = getRollGroupID();

        $this->yearGroupID = getYearGroupID();
        
        if ($this->yearGroupID != '') {
            $data = array('yearGroupID' => $this->yearGroupID);
            $sql = "SELECT name FROM gibbonYearGroup WHERE gibbonYearGroupID = :yearGroupID";
            $rs = $this->dbh->prepare($sql);
            $rs->execute($data);
            $row = $rs->fetch();
            $this->yearGroupName = $row['name'];
        }

        
        // find maximum length of comment for this class
        $this->maxChar = 1000;
        
        // adjust box size for size of comment
        $this->numRows = 15;
        $this->numCols = 80;
        
        
        $this->repAccess = 0;
        $this->reportID = getReportID();
        if ($this->reportID > 0) {
            $this->repAccess = findReportstatus($this->dbh, $this->reportID, $this->role);
            $this->reportDetail = readReportDetail($this->dbh, $this->reportID);
            $reportRow = $this->reportDetail->fetch();           
        }

        // if view only use enabledState to disable controls
        if ($this->repAccess) {
            $this->enabledState = "";
        } else {
            $this->enabledState = "disabled='disabled'";
        }
        
        // check whether to view individual student or whole class
        $this->view = getView();

        // check if a class has been selected
        $this->classID = getClassID();

        if ($this->classID == $this->allClass) {
            $this->view = $this->studView;
        }

        $this->numCols = $_SESSION['numCols'];

        $this->rollGroupList = readRollGroupList($this->dbh, $this->rollGroupID, $this->showLeft);

        if (isset($_POST['passubmit'])) {
            $ok = $this->save($this->dbh);
            setStatus($ok, 'Save', $this->msg, $this->class);
        }

    }
    ////////////////////////////////////////////////////////////////////////////


    ////////////////////////////////////////////////////////////////////////////
    function readRollGroupList() {
        // return list of students in the selected roll group
        try {
            $data = array(
                'rollGroupID' => $this->rollGroupID
            );
            $sql = "SELECT gibbonStudentEnrolment.gibbonPersonID, surname, preferredName
                FROM gibbonStudentEnrolment
                INNER JOIN gibbonPerson
                ON gibbonStudentEnrolment.gibbonPersonID = gibbonPerson.gibbonPersonID
                WHERE gibbonFormGroupID = :rollGroupID ";
                if ($this->showLeft == 0) {
                    $sql .= "AND status = 'Full' ";
                }
                $sql .= "ORDER BY surname, firstName";
            //print $sql;
            //print_r($data);
            $rs = $this->dbh->prepare($sql);
            $rs->execute($data);
            return $rs;
        } catch (Exception $ex) {
            die($ex);
        }            
    }
    ////////////////////////////////////////////////////////////////////////////
    
    ////////////////////////////////////////////////////////////////////////////
    function save() {
        // begin save process
        $ok = true;
        if ($this->view == $this->studView) {
            // single student only
            //$idtext = 'comtext'.$this->classID.'_'.$this->studentID;
            $ok = $this->saveReportDetail();
        } else {
            // class view
            $rollGroupList = $this->rollGroupList;
            $rollGroupList->execute();
            while ($row = $rollGroupList->fetch()) {
                $this->studentID = $row['gibbonPersonID'];
                $result = $this->saveReportDetail();
            }
        }
        return $ok;
    }
    ////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////
    function saveReportDetail() {
        // save report for individual student
        $fldComment = "comment".$this->studentID;
        $comment = $_POST[$fldComment];
        $comment = preg_replace( "/\r|\n/", "", $comment );

        // save report
        $ok = true;
        try {
            // save comment
            $data = array(
                "studentID"=>$this->studentID,
                "reportID"=>$this->reportID,
                "comment"=>$comment
            );
            $sql = "INSERT INTO arrReportSubject
                SET studentID = :studentID,
                reportID = :reportID,
                subjectID = 0,
                subjectComment = :comment
                ON DUPLICATE KEY UPDATE
                subjectComment = :comment";
            $rs = $this->dbh->prepare($sql);
            $result = $rs->execute($data);
            if (!$result) {
                $ok = $result;
            }
            return $ok;
        } catch(PDOException $e) {
            print "<div>" . $e->getMessage() . "</div>" ;
        }
    }
    ////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////
    function reportComplete($studentID) {
        // check completeness of report
        $count = 0;
        $numcrit = 1;

        // read report
        $report = readSubReport($this->dbh, $studentID, 0, $this->reportID, $this->schoolYearID);

        if ($report->rowCount() > 0) {
            // report exists so check contents
            $row = $report->fetch();
            $comment = $row['subjectComment'];
            if (strlen($comment) > 0) {
                $count = 1; // this only shows something has been written
            }
            try {
                // check number of grades
                $data = array(
                    'studentID' => $studentID,
                    'subjectID' => 0,
                    'reportID' => $this->reportID
                );
                $sql = "SELECT gradeID
                    FROM arrReportGrade
                    LEFT JOIN arrCriteria
                    ON arrCriteria.criteriaID = arrReportGrade.criteriaID
                    WHERE subjectID = :subjectID
                    AND studentID = :studentID
                    AND reportID = :reportID
                    AND gradeID > 0";
                //print $sql;
                //print_r($data);
                $rs = $this->dbh->prepare($sql);
                $rs->execute($data);
                $count = $count + $rs->rowCount();
            } catch (Exception $ex) {
                die($ex);
            }
        }

        // return colour to show for name
        if ($count == $numcrit) {
            // everything present
            $complete = "blue";
        } else {
            if ($count > 0) {
                // partially complete
                $complete = "green";
            } else {
                // nothing started
                $complete = "red";
            }
        }
        return 'color:'.$complete;
    }
    ////////////////////////////////////////////////////////////////////////////
    
    ////////////////////////////////////////////////////////////////////////////
    function studentLink($name, $studentID) {
        // set links in class list
        $page = $_SESSION[$this->guid]['address'];
        if ($this->view == $this->studView) {
            $link = $_SESSION[$this->guid]['absoluteURL']."/index.php?q=".$page.
            "&amp;studentID=".$studentID.
            "&amp;view=".$this->studView.
            "&amp;showLeft=".$this->showLeft.
            "&amp;rollGroupID=".$this->rollGroupID.
            "&amp;reportID=".$this->reportID.
            "&amp;classID=".$this->classID.
            "&amp;yearGroupID=".$this->yearGroupID.
            "&amp;schoolYearID=".$this->schoolYearID;
            $click = "if (checkForEdit('status') == true) location.href ='$link'";
        } else {
            $link = "#".$studentID;
            $click = '';
        }

        // check completeness of report
        $complete = $this->reportComplete($studentID);

        // show link
        echo "<div class = 'studlist'>";
            if ($this->view == $this->studView) {
                // student view.  check that no changes have been made before moving to a new student
                ?>
                <a href = "#" style = "<?php echo $complete ?>;" onclick = "<?php echo $click ?>">
                <?php
            } else {
                // class view, just move to selected student
                ?>
                <a href = "<?php echo $link ?>" style="<?php echo $complete ?>">
                <?php
            }
            echo $name;
            echo "</a>";
        echo "</div>";
    }
    ////////////////////////////////////////////////////////////////////////////

    
    ////////////////////////////////////////////////////////////////////////////
    function changeView() {
        // change between class and student view
        $path = $_SESSION[$this->guid]['absoluteURL'];
        $page = $_SESSION[$this->guid]['address'];
        $link = $path."/index.php?q=".$page;
        if ($this->view == $this->studView) {
            $viewlink = $link."&amp;view=".$this->classView.
                    "&amp;classID=".$this->classID.
                    "&amp;rollGroupID=".$this->rollGroupID.
                    "&amp;reportID=".$this->reportID.
                    "&amp;yearGroupID=".$this->yearGroupID.
                    "&amp;schoolYearID=".$this->schoolYearID;
            $text = "Change to class view";
        } else {
            $viewlink = $link."&amp;view=".$this->studView.
                    "&amp;classID=".$this->classID.
                    "&amp;rollGroupID=".$this->rollGroupID.
                    "&amp;reportID=".$this->reportID.
                    "&amp;yearGroupID=".$this->yearGroupID.
                    "&amp;schoolYearID=".$this->schoolYearID;
            $text = "Change to student view";
        }
        $click = "if (checkForEdit('status') == true) location.href ='$viewlink'";
        ?>

        <div class = "smalltext">
        <a href = "#" onclick = "<?php echo $click ?>"><?php echo $text ?></a>
        </div>
        <div>&nbsp;</div>
        <?php
    }
    ////////////////////////////////////////////////////////////////////////////
   
    ////////////////////////////////////////////////////////////////////////////
    function chooseLeft() {
        // choose whether to show students who have left
        echo "<div>&nbsp;</div>";
        echo "<div class = 'smalltext'>";
            echo "<form name = 'frm_showleft' method = 'post' action = ''>";
                echo "<input type = 'hidden' name = 'rollGroupID' value = '$this->rollGroupID' />";
                echo "<input type = 'hidden' name = 'classID' value = '$this->classID' />";
                echo "<input type = 'hidden' name = 'reportID' value = '$this->reportID' />";
                echo "<input type = 'hidden' name = 'schoolYearID' value = '$this->schoolYearID' />";
                echo "<input type = 'hidden' name = 'yearGroupID' value = '$this->yearGroupID' />";
                echo "<input type = 'hidden' name = 'studentID' value = '' />";
                echo "<input type = 'hidden' name = 'view' value = '$this->view' />";
                echo "<input type = 'hidden' name = 'showLeft' value = '$this->showLeft' />";
                echo "<input type = 'checkbox' name = 'setShowLeft' value = '1' ";
                    if ($this->showLeft == 1) {
                        echo "checked='checked'";
                    }
                    echo "onclick = 'if (this.checked) this.form.showLeft.value = 1; else this.form.showLeft.value = 0;this.form.submit();' ";
                echo "/>";
                echo " show left";
            echo "</form>";
        echo "</div>";
    }
    ////////////////////////////////////////////////////////////////////////////

    
    ////////////////////////////////////////////////////////////////////////////
    function chooseRollGroup() {
        // drop down box to select roll group
        try {
            $data = array(
                'schoolYearID' => $this->schoolYearID,
                'yearGroupID' => $this->yearGroupID
            );
            $sql = "SELECT DISTINCT gibbonStudentEnrolment.gibbonFormGroupID, gibbonFormGroup.nameShort
                FROM gibbonFormGroup
                INNER JOIN gibbonStudentEnrolment
                ON gibbonFormGroup.gibbonFormGroupID = gibbonStudentEnrolment.gibbonFormGroupID
                WHERE gibbonYearGroupID = :yearGroupID
                AND gibbonStudentEnrolment.gibbonSchoolYearID = :schoolYearID
                ORDER BY nameShort";
            //print $sql;
            //print_r($data);
            $rs = $this->dbh->prepare($sql);
            $rs->execute($data);
        } catch (Exception $ex) {
            die($ex);
        }

        ob_start();
        ?>
        <div style = "padding:2px;">
            <div style = "float:left;width:30%;" class = "smalltext">Roll Group</div>
            <div style = "float:left;">
                <form name="frm_class" method="post" action="">
                    <input type="hidden" name="schoolYearID" value="<?php echo $this->schoolYearID ?>" />
                    <input type="hidden" name="yearGroupID" value="<?php echo $this->yearGroupID ?>" />
                    <input type="hidden" name="classID" value="" />
                    <input type="hidden" name="reportID" value="" />
                    <input type="hidden" name="studentID" value="" />
                    <select name="rollGroupID" onchange="this.form.submit();">
                        <option></option>
                        <?php
                        while ($row = $rs->fetch()) {
                            $selected = ($this->rollGroupID == $row['gibbonFormGroupID']) ? "selected" : "";
                            echo "<option value='".$row['gibbonFormGroupID']."' $selected>";
                                echo $row['nameShort'];
                            echo "</option>";
                        }
                        ?>
                    </select>
                </form>
            </div>
            <div style="clear:both"></div>
        </div>
        <?php
        return ob_get_clean();
    }
    ////////////////////////////////////////////////////////////////////////////
    
    ////////////////////////////////////////////////////////////////////////////
    function showReport($studentID) {
        // the actual form to fill in when writing reports
        $studentName = getStudentName($this->dbh, $studentID); // id has already been passed to this function so just make the name

        $fldStudentID    = "student".$studentID; // store the student's ID

        echo "<div class = 'studentname'>"; // show the name
            echo "&nbsp;<a name = '$studentID'>".$studentName."</a>";
            echo "<input type = 'hidden' name = '$fldStudentID' value = '$studentID' />"; // pass the student id
        echo "</div>";

        // all other subject reports
        // set field names
        $fldStudentID    = "student".$studentID;
        $fldComment      = "comment".$studentID;
        $charBarID        = "charBar".$studentID; // used for displaying character count
        $numCharID        = "numChar".$studentID; // used for displaying character count

        // read report
        //$report = read_pasReport($this->dbh, $studentID, $this->reportID); // get the student's report
        $report = readSubReport($this->dbh, $studentID, 0, $this->reportID); // get the student's report
        $row = $report->fetch(); // read the report
        $reportsubjectID = $row['reportSubjectID'];
        $comment = $row['subjectComment']; // get the comment
        
        echo "<div style = 'float:left;'>"; // the report
            //$this->showComment($fldComment, $comment, $charBarID, $numCharID);
            showComment($fldComment, $comment, $charBarID, $this->maxChar, $numCharID, $this->numRows, $this->enabledState);
        echo "</div>";

        echo "<div style = 'float:left;width:10px;'>&nbsp;</div>"; // spacer between report and photo
            showPhoto($this->guid, $this->dbh, $studentID);
        echo "<div style = 'clear:both;'></div>";

        echo "<div class = 'smalltext'>";
            if ($this->repAccess) {
                echo "<input type = 'submit' name = 'passubmit' class='submit' value = 'Save' />";
            }
            if ($this->view == $this->classView) {
                echo "&nbsp;<a href = '#top'>Top</a>&nbsp;|&nbsp;<a href = '#bottom'>Bottom</a>";
            }
        echo "</div>";
        echo "<div>&nbsp;</div>";
        
        if ($this->view != $this->classView) {
            // get list of subjects/classes for student
            $sublist = readStudentClassList($this->dbh, $studentID, $this->schoolYearID);
            while ($row = $sublist->fetch()) {
                $subjectID = $row['subjectID'];
                //$teacherName = getTeacherName($this->dbh, $row['classID']);
                $subreport = readSubReport($this->dbh, $studentID, $subjectID, $this->reportID);
                //$criterialist = readCriteriaList($this->dbh, $subjectID);
                $criterialist = readCriteriaGrade($this->dbh, $studentID, $subjectID, $this->reportID);
                $row_subject = $subreport->fetch();
                $comment = $row_subject['subjectComment'];

                $idedit = 'comedit'.$subjectID.'_'.$studentID;
                $idanchor = 'anchor'.$subjectID.'_'.$studentID;
                $idtext = 'comtext'.$subjectID.'_'.$studentID;
                $idtext2 = 'comtext2'.$subjectID.'_'.$studentID;
                $idshow = 'comshow'.$subjectID.'_'.$studentID;
                $numCharID = 'numCharID'.$subjectID.'_'.$studentID;
                $charBarID = 'charBarID'.$subjectID.'_'.$studentID;

                echo "<div class='subjectname'><a name='$idanchor'>".$row['subjectName']."</a></div>";
                echo "<div class='teachername'>".$row['teacherName']."</div>";

                if ($criterialist->rowCount() > 0) {
                    echo "<table style='width:100%;'>";
                        echo "<tr>";
                            echo "<th style='width:60%;'>Criteria</th>";
                            echo "<th style='width:40%;'>Grade</th>";
                        echo "</tr>";

                        while ($row_criteria = $criterialist->fetch()) {
                            //$gradeList = readGradeList($this->dbh, $row['gradeScaleID']);
                            echo "<tr>";
                                echo "<td>".$row_criteria['criteriaName']."</td>";
                                echo "<td>";
                                    if ($row_criteria['criteriaType'] == 0) {
                                        echo $row_criteria['grade'];
                                    } else {
                                        echo $row_criteria['mark'];
                                    }
                                echo "</td>";
                            echo "</tr>";
                        }
                    echo "</table>";
                }

                echo "<div class='reportbox idshow smalltext'>";
                    if ($comment == '') {
                        $comment = "No comment entered";
                    } else {
                        echo nl2br($comment);
                    }
                echo "</div>";
                echo "<div class='reportend'>&nbsp;</div>";
                
            }
        }
    }
    ////////////////////////////////////////////////////////////////////////////
    
    ////////////////////////////////////////////////////////////////////////////
    function showRollGroupList() {
        // show classlist with appropriate links and colours
        // this is going in session sidebar so use output buffer
        if ($this->reportID > 0 && $this->rollGroupID > 0) {
            $rollGroupList = $this->rollGroupList;
            $rollGroupList->execute();
            ob_start();
            if ($this->rollGroupID > 0) { // only do something if a class has been selected
                if ($rollGroupList->rowCount() > 0) { // only worry if there are students in the class
                    if ($this->classID != $this->allClass) {
                        $this->changeView(); // show link to change between student and class view
                    }
                    while ($row = $rollGroupList->fetch()) { // for each student in the class
                        $classStudentID = $row['gibbonPersonID']; // read their ID
                        $name = $row['surname'].', '.$row['preferredName']; // name to be shown in list
                        $this->studentLink($name, $classStudentID); // create link and display
                    }
                } else { // no one in the class
                    echo "<div>&nbsp;</div>";
                    echo "<div class = 'smalltext'>No students listed</div>";
                }
                echo "<div>&nbsp;</div>";
                $this->chooseLeft($this->showLeft); // show students who have left the class
            }
            return ob_get_clean();
        }
    }
    ////////////////////////////////////////////////////////////////////////////


    ////////////////////////////////////////////////////////////////////////////
    function mainform() {
        echo "<div>&nbsp</div>";
        echo "<form name='frm_editcom' id='frm_editcom' method='post'>";
            echo "<input type='hidden' name='classID' value='$this->classID' />";
            echo "<input type='hidden' name='reportID' value='$this->reportID' />";
            echo "<input type='hidden' name='rollGroupID' value='$this->rollGroupID' />";
            echo "<input type='hidden' name='schoolYearID' value='$this->schoolYearID' />";
            echo "<input type='hidden' name='teacherID' value='$this->teacherID' />";
            echo "<input type='hidden' name='yearGroupID' value='$this->yearGroupID' />";
            if ($this->view == $this->studView) {
                if ($this->studentID > 0) {
                    // single student selected
                    $this->showReport($this->studentID);
                }
            } else {
                $rollGroupList = $this->rollGroupList;
                $rollGroupList->execute();
                while ($row = $rollGroupList->fetch()) {
                    $this->showReport($row['gibbonPersonID']);
                }
            }
        echo "</form>";
        if ($this->view == $this->classView) {
            // in class view put a marker so user can link to bottom of page
            echo "<div><a name = 'bottom' style = 'color:white;'>xx&nbsp;</a></div>";
        }
    }
    ////////////////////////////////////////////////////////////////////////////
}