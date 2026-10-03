<?php
/*
 * function for creating reports to be written
 */

class ord {

    var $class;
    var $msg;
    var $yearGroupID;

    function ordInit($guid, $dbh) {
        $this->guid = $guid;
        $this->dbh = $dbh;
        
        // get value of selected year
        $this->schoolYearID = getSchoolYearID($this->dbh, $schoolYearName, $currentYear);
        
        $this->yearGroupID = getYearGroupID();

        // save changes
        if (isset($_POST['save'])) {
            $ok = $this->save();
            setStatus($ok, 'Save', $this->msg, $this->class);
        }

        // cancel changes
        if (isset($_POST['cancel'])) {
            $this->reportID = '';
            $this->mode = '';
        }

    }
    ////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////
    function save() {
        // save report
        $subjectList = $_POST['subjectID'];
        $ok = true;
        for ($i=0; $i<count($subjectList); $i++) {
            $data = array(
                'subjectID' => $subjectList[$i],
                'schoolYearID' => $this->schoolYearID,
                'yearGroupID' => $this->yearGroupID,
                'subjectOrder' => ($i+1)
            );
            $set = "subjectID = :subjectID, 
                schoolYearID = :schoolYearID,
                yearGroupID = :yearGroupID,
                subjectOrder = :subjectOrder";
            $sql = "INSERT INTO arrSubjectOrder SET $set
                    ON DUPLICATE KEY UPDATE $set";
            $rs = $this->dbh->prepare($sql);
            $result = $rs->execute($data);    
            If (!$result) {
                $ok = $result;
            }
        }
        return $ok;
    }
    ////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////
    function mainform() {
        // show table with all reports
        if ($this->schoolYearID == 0 || $this->yearGroupID == 0) {
            return;
        }
        $rs = $this->readSubjectList();
        ?>
        <form name='frm_ord' method='post' action=''>
            <input type='submit' name='save' value='Save' />
            <input type='hidden' name='schoolYearID' value='<?php  echo $this->schoolYearID ?>' />
            <table class='mini' id='subTable' style='width:100%'>
                <thead>
                    <tr>
                        <th style='width:10%'><?php echo __($this->guid, "Order") ?></th>
                        <th style='width:90%'><?php echo __($this->guid, "Course Name") ?></th>
                    </tr>
                </thead>

                <tbody>
                <?php
                while ($row = $rs->fetch()) {
                    echo "<tr>";
                        echo "<td><img src='".$this->modpath."/images/drag.png' alt='drag' height='16' /></td>";
                        echo "<td>";
                            echo "<input type='hidden' name='subjectID[]' value='".$row['subjectID']."' />";
                            echo $row['subjectName'];
                        echo "</td>";
                    echo "</tr>";
                }
                ?>
                </tbody>
            </table>
            <input type='submit' name='save' value='Save' />
        </form>
        <script>
            $('#subTable tbody').sortable({
                change: function(event, ui) {
                    notSaved('status');
                }
            });

        </script>
        <?php
    }
    ////////////////////////////////////////////////////////////////////////////
    
    ////////////////////////////////////////////////////////////////////////////
    function readSubjectList() {
        // read list of subjects in order
        $data = array(
            'schoolYearID' => $this->schoolYearID,
            'yearGroupID' => $this->yearGroupID
        );
        $sql = "
            SELECT DISTINCT gibbonCourse.gibbonCourseID AS subjectID, 
                gibbonCourse.name AS subjectName 
                FROM gibbonCourse 
                LEFT JOIN 
                (
                    SELECT *
                    FROM arrSubjectOrder 
                    WHERE arrSubjectOrder.yearGroupID = :yearGroupID
                ) AS subjectOrder
                ON subjectOrder.subjectID = gibbonCourse.gibbonCourseID 
                AND subjectOrder.schoolYearID = gibbonCourse.gibbonSchoolYearID 
                LEFT JOIN gibbonCourseClass
                ON gibbonCourseClass.gibbonCourseID = gibbonCourse.gibbonCourseID
                WHERE gibbonCourse.gibbonSchoolYearID = :schoolYearID
                AND gibbonCourse.gibbonYearGroupIDList LIKE CONCAT('%', :yearGroupID, '%')
                AND gibbonCourseClass.reportable = 'Y'
                ORDER BY subjectOrder.subjectOrder, gibbonCourse.name";
        $rs = $this->dbh->prepare($sql);
        $rs->execute($data);
        return $rs;
    }
    ////////////////////////////////////////////////////////////////////////////
}