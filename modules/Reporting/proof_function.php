<?php

/*
 * Project:
 * Author:   Andy Statham
 * Date:
 */
class proof {

    var $class;
    var $msg;

    var $allClass = 'xxx';
    var $eal = 'eal';
    var $noComment = "No comment written";

    var $classView;
    var $studView;
    var $view;

    var $schoolYearID;
    var $schoolYearName;
    var $classTeacherID;
    var $yearGroupName;

    function proofInit($guid, $dbh) {
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
        $this->pastoralTeacherName = $this->getPastoralTeacherName();

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
        //findMaxChar($this->dbh, $this->classID, $this->courseType, $this->maxChar);
        $this->maxChar = 1000;
        
        // adjust box size for size of comment
        //$this->numRows = intval($this->maxChar/60);
        //$this->numCols = $_SESSION['numCols'];
        $this->numRows = 15;
        $this->numCols = 80;
        
        
        $this->repAccess = 0;
        $this->reportID = getReportID();
        if ($this->reportID > 0) {
            $this->repAccess = findReportstatus($this->dbh, $this->reportID, $this->role);
            $this->reportDetail = readReportDetail($this->dbh, $this->reportID);
            $reportRow = $this->reportDetail->fetch();
            $this->gradeScale = $reportRow['gradeScale']; // id for grade scale to be used for assessment
            $this->gradeList = readGradeList($this->dbh, $this->gradeScale);
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

        // if class has been selected read the class list
        //$this->classList = $this->readClassList($this->dbh);

        //$this->rollGroupList = $this->readRollGroupList($this->dbh);
        $this->rollGroupList = readRollGroupList($this->dbh, $this->rollGroupID, $this->showLeft);

        if (isset($_POST['subsubmit'])) {
            $ok = $this->save($this->dbh);
            setStatus($ok, 'Save', $this->msg, $this->class);
        }

    }
    ////////////////////////////////////////////////////////////////////////////
 

    ////////////////////////////////////////////////////////////////////////////
    function save() {
        // single student only
        $ok = true;
        
        $subjectID = $_POST['subjectID'];
        $studentID = $_POST['studentID'];
        $reportID = $_POST['reportID'];
        $idtext = 'comtext'.$subjectID.'_'.$studentID;
        $comment = $_POST[$idtext];
;
        try {
            $data = array(
                'subjectID' => $subjectID,
                'studentID' => $studentID,
                'reportID' => $reportID,
                'comment' => $comment
            );
            $sql = "INSERT INTO arrReportSubject
                SET studentID = :studentID,
                subjectID = :subjectID,
                reportID = :reportID,
                subjectComment = :comment
                ON DUPLICATE KEY UPDATE
                subjectComment = :comment";
            $rs = $this->dbh->prepare($sql);
            return $rs->execute($data);
        } catch (Exception $ex) {
            die($ex);
        }            
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
        //$complete = $thisreportComplete($this->dbh, $studentID);

        // show link
        echo "<div class = 'studlist'>";
            ?>
            <a href = "#" style = "" onclick = "<?php echo $click ?>">
            <?php
            
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

        $found = false;
        while ($row = $rs->fetch()) {
            if ($row['gibbonFormGroupID'] == $this->rollGroupID) {
                $found = true;
            }
        }
        if (!$found) {
            $this->rollGroupID = 0;
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
                        $rs->execute();
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
    function showComment($fldComment, $comment, $charBarID, $numCharID) {
	// show pastoral comment for edit or display
        
        $idanchor = "pastoral".$this->studentID;
        
        $idtext = 'comtext0_'.$this->studentID;
        $idtext2 = 'comtext02_'.$this->studentID;
        $idedit = 'comedit0_'.$this->studentID;
        $idshow = 'comshow0_'.$this->studentID;
        
        echo "<div class='subjectname'><a name='$idanchor'>Pastoral</a></div>";
        echo "<div class='teachername'>$this->pastoralTeacherName</div>";
        
        echo "<div name='$idedit' id='$idedit' style='display:none' class='idedit'>";
            showRepLength($comment, $this->maxChar, $charBarID, $numCharID);
            echo "<form name='frm_editcom' id='frm_editcom' method='post'>";
                echo "<input type='hidden' name='reportID' value='$this->reportID' />";
                echo "<input type='hidden' name='subjectID' value='0' />";
                echo "<input type='hidden' name='rollGroupID' value='$this->rollGroupID' />";
                echo "<input type='hidden' name='schoolYearID' value='$this->schoolYearID' />";
                echo "<input type='hidden' name='studentID' value='$this->studentID' />";
                echo "<input type='hidden' name='teacherID' value='$this->teacherID' />";
                echo "<input type='hidden' name='yearGroupID' value='$this->yearGroupID' />";
                echo "<div>";
                    echo "<textarea
                       name='$idtext'
                       id='$idtext'
                       rows='$this->numRows'
                       cols='$this->numCols'
                       onkeyup = 'checkEnter(this.value, \"$this->maxChar\", \"submit\", \"$numCharID\", \"$charBarID\")'
                       class='subtextbox'
                       onkeydown='notSaved(\"status\")'
                       >$comment</textarea>";
                echo "</div>";

                echo "<div>";
                    echo "<input type='submit' name='subsubmit' class='submit' value='Save' />";
                    echo "<input type='button' name='subcancel' value='Cancel' onclick='showEdit(\"$idshow\", \"$idedit\", \"\");' />";
                echo "</div>";

            echo "</form>";
        echo "</div>";

        if ($comment == '') {
            $comment = "No comment entered";
        }
        echo "<div class='reportbox idshow smalltext' name='$idshow' id='$idshow'>";
            if ($this->repAccess) {
                echo "<a href='#' id='$idtext2' onclick='showEdit(\"$idedit\", \"$idshow\", \"$idanchor\");return false;'>";
                    echo nl2br($comment);
                echo "</a>";
            } else {
                echo nl2br($comment);
            }
        echo "</div>"; 
        echo "<div class='reportend'>&nbsp;</div>";
    }
    ////////////////////////////////////////////////////////////////////////////
    
    ////////////////////////////////////////////////////////////////////////////
    function showRollGroupList() {
        // show classlist with appropriate links and colours
        // this is going in session sidebar so use output buffer
        if ($this->reportID > 0 && $this->yearGroupID > 0 && $this->rollGroupID > 0) {
            $rollGroupList = $this->rollGroupList;
            $rollGroupList->execute();
            ob_start();
            if ($this->rollGroupID > 0) { // only do something if a class has been selected
                if ($rollGroupList->rowCount() > 0) { // only worry if there are students in the class
                    if ($this->classID != $this->allClass) {
                        //$this->changeView($guid); // show link to change between student and class view
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
    function showReport($studentID, $schoolYearID) {
        $fldComment = '';
        
        $numRows = intval($this->maxChar/60);
        
        $studentName = getStudentName($this->dbh, $studentID); // id has already been passed to this function so just make the name
        $fldStudentID    = "student".$studentID; // store the student's ID
        
        echo "<div class='studentname'>$studentName</div>";
        
        // read report
        // 
        
        //$report = readPasReport($this->dbh, $studentID, $this->reportID); // get the student's report
        $report = readSubReport($this->dbh, $studentID, 0, $this->reportID);
        $row = $report->fetch(); // read the report
        $reportPastoralID = $row['reportSubjectID'];
        $comment = $row['subjectComment']; // get the comment
        $charBarID        = "pastBar".$studentID; // used for displaying character count
        $numCharID        = "pastChar".$studentID; // used for displaying character count
        
        echo "<div style = 'float:left;'>"; // the report
            $this->showComment($fldComment, $comment, $charBarID, $numCharID);
            //showComment($fldComment, $comment, $charBarID, $this->maxChar, $numCharID, $this->numRows, $this->enabledState);
        echo "</div>";
        
        echo "<div style = 'float:left;width:10px;'>&nbsp;</div>"; // spacer between report and photo
            showPhoto($this->guid, $this->dbh, $studentID);
        echo "<div style = 'clear:both;'></div>";
        
        // get list of subjects/classes for student
        $sublist = readStudentClassList($this->dbh, $studentID, $schoolYearID);
        while ($row = $sublist->fetch()) {
            $subjectID = $row['subjectID'];
            $teacherName = $row['teacherName'];
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
            echo "<div class='teachername'>$teacherName</div>";
            
            if ($criterialist->rowCount() > 0) {
                echo "<table>";
                    echo "<tr>";
                        echo "<th style='width:300px;'>Criteria</th>";
                        echo "<th style='width:150px;'>Grade</th>";
                    echo "</tr>";

                    while ($row_criteria = $criterialist->fetch()) {
                        echo "<tr>";
                            echo "<td>".$row_criteria['criteriaName']."</td>";
                            echo "<td>";
                                if ($row_criteria['criteriaType'] == 0) {
                                    echo $row_criteria['grade'];
                                    //echo "<input type='hidden' name='$markID' value='' />";
                                    //selectGrade($fldID, $row['gradeID'], $this->enabledState, $gradeList);
                                } else {
                                    echo $row_criteria['mark'];
                                    //echo "<input type='hidden' name='$fldID' value='' />";
                                    //echo "<input type='text' name='$markID' value='".$row['mark']."' size='5' />";
                                }
                            echo "</td>";
                            /*
                            echo "<td>";
                                echo findGrade($this->gradeList, $row_criteria['gradeID']);
                            echo "</td>";
                             * 
                             */
                        echo "</tr>";
                    }
                echo "</table>";
            }
                
            echo "<div name='$idedit' id='$idedit' style='display:none' class='idedit'>";
                showRepLength($comment, $this->maxChar, $charBarID, $numCharID);
                echo "<form name='frm_editcom' id='frm_editcom' method='post'>";
                    echo "<input type='hidden' name='reportID' value='$this->reportID' />";
                    echo "<input type='hidden' name='subjectID' value='$subjectID' />";
                    echo "<input type='hidden' name='rollGroupID' value='$this->rollGroupID' />";
                    echo "<input type='hidden' name='schoolYearID' value='$schoolYearID' />";
                    echo "<input type='hidden' name='studentID' value='$studentID' />";
                    echo "<input type='hidden' name='teacherID' value='$this->teacherID' />";
                    echo "<input type='hidden' name='yearGroupID' value='$this->yearGroupID' />";
                    echo "<div>";
                        echo "<textarea
                           name='$idtext'
                           id='$idtext'
                           rows='$numRows'
                           cols='$this->numCols'
                           onkeyup = 'checkEnter(this.value, \"$this->maxChar\", \"submit\", \"$numCharID\", \"$charBarID\")'
                           class='subtextbox'
                           onkeydown='notSaved(\"status\")'
                           >$comment</textarea>";
                    echo "</div>";

                    echo "<div>";
                        echo "<input type='submit' name='subsubmit' class='submit' value='Save' />";
                        echo "<input type='button' name='subcancel' value='Cancel' onclick='showEdit(\"$idshow\", \"$idedit\", \"\");' />";
                    echo "</div>";
                echo "</form>";
            echo "</div>";

            if ($comment == '') {
                $comment = "No comment entered";
            }
            echo "<div class='reportbox idshow smalltext' name='$idshow' id='$idshow'>";
                if ($this->repAccess) {
                    echo "<a href='#' id='$idtext2' onclick='showEdit(\"$idedit\", \"$idshow\", \"$idanchor\");return false;'>";
                        echo nl2br($comment);
                    echo "</a>";
                } else {
                    echo nl2br($comment);
                }
            echo "</div>";
            echo "<div class='reportend'>&nbsp;</div>";
        } // end of subjects
    }
    ////////////////////////////////////////////////////////////////////////////
    
    ////////////////////////////////////////////////////////////////////////////
    function mainform() {
        echo "<div>&nbsp</div>";
        if ($this->view == $this->studView) {
            if ($this->studentID > 0) {
                // single student selected
                $this->showReport($this->studentID, $this->schoolYearID);
            }
        } else {
            $rollGroupList = $this->rollGroupList;
            $rollGroupList->execute();
            while ($row = $rollGroupList->fetch()) {
                $this->showReport($row['gibbonPersonID'], $this->schoolYearID);
            }
        }
    }
    ////////////////////////////////////////////////////////////////////////////
    
    ////////////////////////////////////////////////////////////////////////////
    function getPastoralTeacherName() {
        $data = array(
            'rollGroupID' => $this->rollGroupID
        );
        $sql = "SELECT CONCAT(gibbonPerson.firstName, ' ', gibbonPerson.surname) AS teacherName
            FROM gibbonFormGroup
            INNER JOIN gibbonPerson
            ON gibbonPerson.gibbonPersonID = gibbonFormGroup.gibbonPersonIDTutor
            WHERE gibbonFormGroup.gibbonFormGroupID = :rollGroupID";
        $rs = $this->dbh->prepare($sql);
        $rs->execute($data);
        $teacherName = "";
        if ($rs->rowCount() > 0) {
            $row = $rs->fetch();
            $teacherName = $row['teacherName'];
        }
        return $teacherName;
    }
    ////////////////////////////////////////////////////////////////////////////
}