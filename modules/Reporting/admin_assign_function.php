<?php
/*
 * functions to assign reports to year groups
 */

class ass {

    var $class;
    var $msg;

    function assInit($guid, $dbh) {
        $this->guid = $guid;
        $this->dbh = $dbh;
        
        // get value of selected year
        $this->schoolYearID = getSchoolYearID($this->dbh, $schoolYearName, $currentYear);
        
        $this->replist = readReport($this->dbh, $this->schoolYearID);
        $this->yearGroupList = readYeargroup($this->dbh);

        if (isset($_POST['save'])) {
            $ok = $this->save();
            setStatus($ok, 'Save', $this->msg, $this->class);
        }

    }
    ////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////
    function save() {
        // save year groups assigned to reports
        $repList = $this->replist;
        $yearGroupList = $this->yearGroupList;
        $ok = true;
        $repList->execute();
        while ($row_report = $repList->fetch()) {
            $yearGroupList->execute();
            while ($row_yeargroup = $yearGroupList->fetch()) {
                $fld_id = "yg".$row_yeargroup['gibbonYearGroupID'].'rep'.$row_report['reportID'];
                $status = 0;
                if (isset($_POST[$fld_id])) {
                    $status = $_POST[$fld_id];
                }

                try {
                    // check if there is an entry
                    $data = array(
                        "yearGroupID" => $row_yeargroup['gibbonYearGroupID'],
                        "reportID" => $row_report['reportID'],
                        "schoolYearID" => $this->schoolYearID
                    );
                    $sql = "SELECT reportAssignID
                        FROM arrReportAssign
                        WHERE yearGroupID = :yearGroupID
                        AND reportID = :reportID
                        AND schoolYearID = :schoolYearID";
                    $rs = $this->dbh->prepare($sql);
                    $rs->execute($data);
                } catch(PDOException $e) {
                    print "<div>" . $e->getMessage() . "</div>" ;
                }  

                if ($rs->rowCount() > 0) {
                    // already exists so update
                    $row = $rs->fetch();
                    //$reportAssignID = $row['arrReportAssignID'];
                    $data = array(
                        "reportAssignID" => $row['reportAssignID'],
                        "assignStatus" => $status
                    );
                    $sql = "UPDATE arrReportAssign
                        SET assignStatus = :assignStatus
                        WHERE reportAssignID = :reportAssignID";
                } else {
                    // new record
                    $data = array(
                        "yearGroupID" => $row_yeargroup['gibbonYearGroupID'],
                        "reportID" => $row_report['reportID'],
                        "assignStatus" => $status,
                        "schoolYearID" => $this->schoolYearID
                    );
                    $sql = "INSERT IGNORE INTO arrReportAssign
                        SET yearGroupID = :yearGroupID,
                        reportID = :reportID,
                        assignStatus = :assignStatus,
                        schoolYearID = :schoolYearID";
                }
                try {
                    $rs = $this->dbh->prepare($sql);
                    $result = $rs->execute($data);
                    if (!$result) {
                        $ok = false;
                    }
                } catch(PDOException $e) {
                    print "<div>" . $e->getMessage() . "</div>" ;
                }  
            }
        }
        return $ok;
    }
    ////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////
    function mainform() {
        // show table of year group assignments and reports
        $repList = $this->replist;
        $yearGroupList = $this->yearGroupList;
        $repList->execute();
        $yearGroupList->execute();
        if ($this->schoolYearID == 0) {
            return;
        }
        ?>
        <form name='frm_status' id='frm_status' method='post' action=''>
            <input type='hidden' name='schoolYearID' value='<?php echo $this->schoolYearID ?>' />
            <table class='mini'>
                <tr>
                    <th style='width:150px;'>Report</th>
                    <?php
                    while ($row_yeargroup = $yearGroupList->fetch()) {
                        ?>
                        <th style='width:50px;'><?php echo $row_yeargroup['nameShort'] ?></td>
                        <?php
                    }
                    ?>
                </tr>
                <?php
                while ($row_report = $repList->fetch()) {
                    ?>
                    <tr>
                        <td><?php echo $row_report['reportName'] ?></td>
                        <?php
                        $yearGroupList->execute();
                        while ($row_yeargroup = $yearGroupList->fetch()) {
                            $fldID = "yg".$row_yeargroup['gibbonYearGroupID'].'rep'.$row_report['reportID'];
                            $this->reportID = $row_report['reportID'];
                            $this->yearGroupID = $row_yeargroup['gibbonYearGroupID'];
                            $status = $this->readAssignStatus();
                            if ($status == 1) {
                                $checked = "checked='checked'";
                            } else {
                                $checked = '';
                            }
                            ?>
                            <td style='width:50px;text-align:center'>
                                <input type='checkbox' name='<?php echo $fldID ?>' value='1' <?php echo $checked ?> onchange="notSaved('status')" />
                            </td>
                            <?php
                        }
                        ?>
                    </tr>
                    <?php
                }
                ?>
            </table>
            <input type='submit' name='save' value='Save' />
        </form>
        <?php
    }
    ////////////////////////////////////////////////////////////////////////////
    
    ////////////////////////////////////////////////////////////////////////////////
    function readAssignStatus() {
        // check if report has been assign to yeargroup
        try {
            $data = array(
                'reportID' => $this->reportID,
                'yearGroupID' => $this->yearGroupID
            );
            $sql = "SELECT *
                FROM arrReportAssign
                WHERE reportID = :reportID
                AND yearGroupID = :yearGroupID";
            $rs = $this->dbh->prepare($sql);
            $rs->execute($data);
        } catch(PDOException $e) {
            print "<div>" . $e->getMessage() . "</div>" ;
        }
        $assignStatus = false;
        if ($rs->rowCount() > 0) {
            $row = $rs->fetch();
            $assignStatus = $row['assignStatus'];
        }
        return $assignStatus;
    }
    ////////////////////////////////////////////////////////////////////////////////
}