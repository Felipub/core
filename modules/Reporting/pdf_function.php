<?php

/*
 * Project:
 * Author:   Andy Statham
 * Date:
 */
class setpdf {

    var $class;
    var $msg;

    var $schoolYearID;

    function setpdfInit($guid, $dbh) {
        $this->guid = $guid;
        $this->dbh = $dbh;
        
        // get value of selected year
        $this->repEdit = $_SESSION[$guid]['repEdit'];

        $this->schoolYearName = '';
        $this->schoolYearID = getSchoolYearID($this->dbh, $this->schoolYearName, $this->currentYearID);

        $this->rollGroupID = getRollGroupID();

        $this->yearGroupID = getYearGroupID();

        $this->reportID = getReportID();

        // check if left students should be shown
        $this->showLeft = getLeft();

        if ($this->rollGroupID > 0) {
            //$this->rollGroupList = $this->readRollGroupList($this->dbh);
            $this->rollGroupList = $this->readRollGroupList();
        }
        
        if (isset($_POST['subsubmit'])) {
            $ok = $this->save($this->dbh);
            setStatus($ok, 'Save', $this->msg, $this->class);
        }
    }
    ////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////
    function findRepAccess() {
        // check if use should have editing access to the reports

        // check if administrator
        $admin = read_access($this->dbh, 'admin', $_SESSION[$guid]["gibbonPersonID"]);

        //   or slt
        $slt = read_access($this->dbh, 'senior', $_SESSION[$guid]["gibbonPersonID"]);

        $access = 1;
        if ($admin || $slt) {
            $access = 2;
        }
        return 2;
        //return $access;
    }
    ////////////////////////////////////////////////////////////////////////////


    ////////////////////////////////////////////////////////////////////////////
    function readClassList() {
        // read list of classes for this roll group
        // may include multiple classes, e.g. chinese
        try {
            $data = array(
                'rollGroupID' => $this->rollGroupID,
                'schoolYearID' => $this->schoolYearID
            );
            $sql = "SELECT DISTINCT gibbonCourseClass.gibbonCourseClassID,
                gibbonCourse.name AS courseName,
                gibbonCourse.nameShort AS courseNameShort,
                gibbonCourseClass.name AS className,
                gibbonCourseClass.nameShort AS classNameShort
                FROM gibbonCourse
                INNER JOIN gibbonCourseClass
                ON gibbonCourse.gibbonCourseID = gibbonCourseClass.gibbonCourseID
                INNER JOIN gibbonCourseClassPerson
                ON gibbonCourseClassPerson.gibbonCourseClassID = gibbonCourseClass.gibbonCourseClassID
                INNER JOIN gibbonStudentEnrolment
                ON gibbonStudentEnrolment.gibbonPersonID = gibbonCourseClassPerson.gibbonPersonID
                WHERE gibbonStudentEnrolment.gibbonFormGroupID = :rollGroupID
                AND gibbonCourse.gibbonSchoolYearID = :schoolYearID
                AND gibbonCourseClass.reportable = 'Y'
                AND gibbonCourseClassPerson.reportable = 'Y'
                AND gibbonCourse.nameShort NOT LIKE '%HR%'
                ORDER BY gibbonCourse.name";
            //print $sql;
            //print_r($data);
            $rs = $this->dbh->prepare($sql);
            $rs->execute($data);
            return $rs;
        } catch (Exception $ex) {
            die($ex);
        }            
    }
    // -------------------------------------------------------------------------

    // -------------------------------------------------------------------------
    function readRollGroupList() {
        // return list of students in the selected roll group
        try {
            $data = array(
                'rollGroupID' => $this->rollGroupID,
                'reportID' => $this->reportID
            );
            $sql = "SELECT *
                FROM gibbonStudentEnrolment
                INNER JOIN gibbonPerson
                ON gibbonStudentEnrolment.gibbonPersonID = gibbonPerson.gibbonPersonID

                LEFT JOIN

                (SELECT *
                FROM arrArchive
                WHERE arrArchive.reportID = :reportID) AS archive
                ON gibbonStudentEnrolment.gibbonPersonID = archive.studentID

                WHERE gibbonFormGroupID = :rollGroupID 
                AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "')";

            if ($this->showLeft == 0) {
                $sql .= "AND status = 'Full' ";
            }

            $sql .= "ORDER BY surname, firstName";

            $rs = $this->dbh->prepare($sql);
            $rs->execute($data);
            return $rs;
        } catch (Exception $ex) {
            die($ex);
        }            
    }
    ////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////
    function chooseLeft() {
        // choose whether to show students who have left
        ob_start();
        echo "<div>&nbsp;</div>";
        echo "<div class = 'smalltext'>";
            echo "<form name = 'frm_showleft' method = 'post' action = ''>";
                echo "<input type = 'hidden' name = 'rollGroupID' value = '$this->rollGroupID' />";
                echo "<input type = 'hidden' name = 'reportID' value = '$this->reportID' />";
                echo "<input type = 'hidden' name = 'schoolYearID' value = '$this->schoolYearID' />";
                echo "<input type = 'hidden' name = 'yearGroupID' value = '$this->yearGroupID' />";
                echo "<input type = 'hidden' name = 'studentID' value = '' />";
                echo "<input type = 'hidden' name = 'showLeft' id='showLeft' value = '$this->showLeft' />";
                echo "<input type = 'checkbox' name = 'setShowLeft' value = '1' ";
                    if ($this->showLeft == 1) {
                        echo "checked='checked'";
                    }
                    echo "onclick = 'if (this.checked) this.form.showLeft.value = 1; else this.form.showLeft.value = 0;this.form.submit();' ";
                echo "/>";
                echo " show left";
            echo "</form>";
        echo "</div>";
        return ob_get_clean();
    }
    ////////////////////////////////////////////////////////////////////////////


    ////////////////////////////////////////////////////////////////////////////
    function mainform() {
        if ($this->rollGroupID > 0 && $this->reportID > 0) {
            $processPath = $this->modpath."/pdf_create.php";
            $rollGroupList = $this->rollGroupList;
            $path = $_SESSION[$this->guid]['absoluteURL'].$_SESSION['archivePath'].$this->schoolYearName.'/';
            $linkPath = "./".$_SESSION['archivePath'].$this->schoolYearName.'/';
            ?>
            <form name='frm_makepdf' method='post' action='<?php echo $processPath ?>'>
                <input type='hidden' name='schoolYearID' value='<?php echo $this->schoolYearID ?>' />
                <input type='hidden' name='yearGroupID' value='<?php echo $this->yearGroupID ?>' />
                <input type='hidden' name='rollGroupID' value='<?php echo $this->rollGroupID ?>' />
                <input type='hidden' name='reportID' value='<?php echo $this->reportID ?>' />
                <input type='hidden' name='showLeft' value='<?php echo $this->showLeft ?>' />
                <input type='submit' name='makepdf' value='Make PDF' onclick="return countBoxes('check')" />
                <input type='submit' name='downloadPDF' value='Download PDF' onclick="return countBoxes('check')" />
                <table style='width:100%'>
                    <tr>
                        <th style='width:30%'>Student</th>
                        <th style='width:40%;'>File</th>
                        <th style='width:20%;'>Date</th>
                        <th style='width:10%;text-align:center'><input type='checkbox' name='AllStudents' id='checkAllStudents' value='1' onclick='checkAll("check", this.checked);' /></th>
                    </tr>
                    <?php
                    $c = 0;
                    while ($row = $rollGroupList->fetch()) {
                        $rowcol = oddEven($c++);
                        $link = $path.$row['reportName'];
                        $check_id = 'check'.$row['gibbonPersonID'];
                        $created = $row['created'];
                        if ($created == '' || substr($created, 0, 4) == '0000') {
                            $created = '';
                        } else {
                            $created = date('d-m-Y H:i:s', strtotime($created));
                        }
                        $file = $linkPath.$row['reportName'];
                        ?>
                        <tr class='<?php echo $rowcol ?>'>
                            <td><?php echo $row['surname'],', '.$row['preferredName'] ?></td>
                            <td>
                                <?php 
                                if (file_exists($file)) {
                                    echo "<a href='$link' target='_blank'>".$row['reportName']."</a>";
                                } 
                                ?> 
                            </td>
                            <td>
                                <?php 
                                if (file_exists($file)) {
                                    echo $created;
                                }
                                ?>
                            </td>
                            <td style='text-align:center;'><input type='checkbox' name='<?php echo $check_id ?>' class='check' value='1' /></td>
                        </tr>
                        <?php
                    }
                    ?>
                </table>
                <input type='submit' name='makepdf' value='Make PDF' onclick="return countBoxes('check')" />
            </form>
            <?php
        }
    }
    ////////////////////////////////////////////////////////////////////////////
}