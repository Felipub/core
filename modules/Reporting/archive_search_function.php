<?php

/*
 * Project:
 * Author:   Andy Statham
 * Date:
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
        
        $this->searchName = '';
        if (isset($_POST['searchName'])) {
            $this->searchName = $_POST['searchName'];
        }
        $this->studentID = '';
        if (isset($_POST['studentID'])) {
            $this->studentID = $_POST['studentID'];
        }
    }
    ////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////
    function readArchiveList() {
        try {
            $data = array('studentID' => $this->studentID);
            $sql = "SELECT arrReport.reportName AS reportName, 
                arrArchive.reportName AS reportLink, name AS schoolYear
                FROM arrArchive
                INNER JOIN arrReport
                ON arrArchive.reportID = arrReport.reportID
                INNER JOIN gibbonSchoolYear
                ON arrReport.schoolYearID = gibbonSchoolYear.gibbonSchoolYearID
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
    function readStudentList() {
        try {
            $data = array('name' => '%'.$this->searchName.'%');
            $sql = "SELECT gibbonPersonID, surname, firstName, preferredName, dateEnd
                FROM gibbonPerson
                WHERE surname LIKE :name
                OR firstName LIKE :name
                OR preferredName LIKE :name
                AND gibbonRoleIDPrimary = 1
                ORDER BY surname, firstName";
            $rs = $this->dbh->prepare($sql);
            $rs->execute($data);
            return $rs;
        } catch (Exception $ex) {
            die($ex);
        }            
    }
    ////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////
    function mainform() {
        // form for search function
        $leftCol = '160px';
        ?>
        <form name="frm_search" method="post" action="" class='smalltext'>
            <div style="float:left;width:<?php echo $leftCol ?>">Type all or part of name</div>
            <div style="float:left;"><input type="text" name="searchName" value="<?php echo $this->searchName ?>" size='40' /></div>
            <div style='clear:both'>&nbsp;</div>
            <?php
            if ($this->searchName != '') {
                $studentList = $this->readStudentList();
                if ($studentList->rowCount() > 0) {
                ?>
                <div style="float:left;width:<?php echo $leftCol ?>">Select name</div>
                <div style="float:left;"><select name="studentID">
                        <option></option>
                        <?php
                        while ($row = $studentList->fetch()) {
                            $name = $row['surname'].', '.$row['firstName'].' ('.$row['preferredName'].')';
                            if ($row['dateEnd'] != '') {
                                $name .= " - Left ".$row['dateEnd'];
                            }
                            $selected = ($this->studentID == $row['gibbonPersonID']) ? "selected" : "";
                            echo "<option $selected value='".$row['gibbonPersonID']."'>";
                                echo $name;
                            echo "</option>";
                        }
                        ?>
                    </select>
                </div>
                <?php
                } else {
                    echo "<p>No matches</p>";
                }
            }
            ?>
            <div style='clear:both'>&nbsp;</div>
            <div><input type='submit' name='submit' value='Go' /></div>
        </form>
        <div>&nbsp;</div>
        <hr />
        <div>&nbsp;</div>
        <?php
        // list reports
        if ($this->studentID > 0) {
            echo "<div class='smalltext'>";
            $list = $this->readArchiveList();
            $count = 0;
            if ($list->rowCount() > 0) {
                while ($row = $list->fetch()) {
                    $pos = strpos($row['reportLink'], '_');
                    $link = $_SESSION[$this->guid]['absoluteURL'].$_SESSION['archivePath'].
                            $row['schoolYear'].'/'.
                            $row['reportLink'];
                    ?>
                    <a href='<?php echo $link ?>' target='_blank'><?php echo $row['reportName'].' ('.$row['schoolYear'].')' ?></a>
                    <?php
                    $count++;
                    if ($count < $list->rowCount()) {
                        echo "<br />";
                    }
                }
            } else {
                echo "No reports available for this student";
            }
            echo "</div>";
        }
    }
    ////////////////////////////////////////////////////////////////////////////
}