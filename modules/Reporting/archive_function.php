<?php
/*
 * functions to view PDF reports that have already been created
 */

class arc {

    var $class;
    var $msg;

    var $classView;
    var $studView;
    var $view;

    var $schoolYearID;

    function arcInit($guid, $dbh) {
        $this->guid = $guid;
        $this->dbh = $dbh;
        
        // get value of selected year
        $this->schoolYearName = '';
        $this->schoolYearID = getSchoolYearID($this->dbh, $this->schoolYearName, $this->currentYearID);

        $this->rollGroupID = getRollGroupID();

        $this->yearGroupID = getYearGroupID();

        $this->reportID = getReportID();

        // check if left students should be shown
        $this->showLeft = getLeft();

        if ($this->rollGroupID > 0) {
            // list of students in roll group
            $this->rollGroupList = readRollGroupList($this->dbh, $this->rollGroupID, $this->showLeft);
        }
    }
    ////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////
    function readArchiveList() {
        // list of pre created PDFs
        try {
            $data = array('studentID' => $this->studentID);
            $sql = "SELECT arrReport.reportName AS reportName, 
                arrArchive.reportName AS reportLink,
                gibbonSchoolYear.name AS schoolYearName
                FROM arrArchive
                INNER JOIN arrReport
                ON arrArchive.reportID = arrReport.reportID
                INNER JOIN gibbonSchoolYear
                ON gibbonSchoolYear.gibbonSchoolYearID = arrReport.schoolYearID
                WHERE studentID = :studentID
                ORDER BY reportName";
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
        return ob_get_clean();
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
            <div style = "float:left;width:30%;" class = "smalltext">Class</div>
            <div style = "float:left;">
                <form name="frm_class" method="post" action="">
                    <input type="hidden" name="yearGroupID" value="<?php echo $this->yearGroupID ?>" />
                    <input type="hidden" name="studentID" value="<?php echo $this->studentID ?>" />
                    <select name="rollGroupID" onchange="this.form.submit();">
                        <option></option>
                        <?php
                        while ($row = $rs->fetch()) {
                            ?>
                            <option value="<?php echo $row['gibbonFormGroupID'] ?>"
                                    <?php if ($this->rollGroupID == $row['gibbonFormGroupID'])
                                        echo "selected='selected'" ?>>
                                <?php echo $row['nameShort'] ?>
                            </option>
                            <?php
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
    function showRollGroupList() {
        // show classlist with appropriate links and colours
        // this is going in session sidebar so use output buffer
        $rollGroupList = $this->rollGroupList;
        $rollGroupList->execute();
        ob_start();
        if ($this->rollGroupID > 0) { // only do something if a class has been selected
            if ($rollGroupList->rowCount() > 0) { // only worry if there are students in the class
                if ($this->classID != $this->allClass) {
                    $this->changeView($guid); // show link to change between student and class view
                }
                while ($row = $rollGroupList->fetch()) { // for each student in the class
                    $this->classStudentID = $row['gibbonPersonID']; // read their ID
                    $this->name = $row['surname'].', '.$row['preferredName']; // name to be shown in list
                    $this->studentLink(); // create link and display
                }
            } else { // no one in the class
                echo "<div>&nbsp;</div>";
                echo "<div class = 'smalltext'>No students listed</div>";
            }
            $this->chooseLeft($this->showLeft); // show students who have left the class
        }
        return ob_get_clean();
    }
    ////////////////////////////////////////////////////////////////////////////


    ////////////////////////////////////////////////////////////////////////////
    function mainform() {
        // show class list and archived files
        if ($this->rollGroupID > 0) {
            $processPath = $this->modpath."/pdf_create.php";
            $rollGroupList = $this->rollGroupList;
            $path = 'archive/'.$this->schoolYearName.'/';

            ?>
            <table class='mini'>
                <tr>
                    <th style='width:200px;'>Student</th>
                    <th style='width:400px;'>Files</th>
                </tr>
                <?php
                $c = 0;
                while ($row = $rollGroupList->fetch()) {
                    //$c++;
                    $rowcol = oddEven($c++);
                    ?>
                    <tr class='<?php echo $rowcol ?>'>
                        <td><?php echo $row['surname'],', '.$row['preferredName'] ?></td>
                        <td>
                            <?php
                            $this->studentID = $row['gibbonPersonID'];
                            $list = $this->readArchiveList();
                            $count = 0;
                            while ($row2 = $list->fetch()) {
                                $pos = strpos($row2['reportLink'], '_');
                                $linkPath = "./".$_SESSION['archivePath'].$row2['schoolYearName'].'/';
                                $file = $linkPath.$row2['reportLink'];
                                $link = $_SESSION[$this->guid]['absoluteURL'].$_SESSION['archivePath'].
                                        $row2['schoolYearName'].'/'.
                                        $row2['reportLink'];
                                if (file_exists($file)) {
                                    ?>
                                    <a href='<?php echo $link ?>' target='_blank'><?php echo $row2['reportName'] ?></a>
                                    <?php
                                    $count++;
                                    if ($count < $list->rowCount()) {
                                        echo "<br />";
                                    }
                                }
                            }
                            ?>
                        </td>
                    </tr>

                    <?php
                }
                ?>
            </table>
            <?php
        }
    }
    ////////////////////////////////////////////////////////////////////////////
}