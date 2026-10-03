<?php
/*
 * functions for setting access to reports
 */

class acc {

    var $class;
    var $msg;

    function accInit($guid, $dbh) {
        $this->guid = $guid;
        $this->dbh = $dbh;
        
        // get value of selected year
        $this->schoolYearID = getSchoolYearID($this->dbh, $schoolYearName, $currentYear);

        $this->yearGroupList = readYeargroup($this->dbh);
        $this->reportList = readReport($this->dbh, $this->schoolYearID);
        $this->roleList = $this->read_roleList();

        // make sure each role has an entry in the assign list
        while ($row = $this->reportList->fetch()) {
            $this->roleList->execute();
            while ($row2 = $this->roleList->fetch()) {
                // set all roles to false for each report
                try {
                    $data = array(
                        'reportID' => $row['reportID'],
                        'roleID' => $row2['roleID']
                    );
                    $sql = "INSERT IGNORE INTO arrStatus
                        SET reportID = :reportID,
                        roleID = :roleID,
                        reportStatus = 0";
                    $rs = $this->dbh->prepare($sql);
                    $rs->execute($data);
                } catch(PDOException $e) {
                    print "<div>" . $e->getMessage() . "</div>" ;
                }  
            }
        }

        // save any changes
        if (isset($_POST['save'])) {
            $ok = $this->save($this->dbh);
            setStatus($ok, 'Save', $this->msg, $this->class);
        }
    }
    ////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////
    function readReport() {
        // get list of reports
        try {
            $data = array(
                'yearGroupID' => $this->yearGroupID,
                'schoolYearID' => $this->schoolYearID,
                'reportNum' => $this->term
            );
            $sql = "SELECT *
                FROM arrReportAssign
                INNER JOIN arrReport
                ON arrReportAssign.reportID = arrReport.reportID
                WHERE arrReportAssign.schoolYearID = :schoolYearID
                AND yearGroupID = :yearGroupID
                AND reportNum = :reportNum
                AND assignStatus = 1";
            //print $sql."<br>";
            //print_r($data);
            //print"<br>";
            $rs = $this->dbh->prepare($sql);
            $rs->execute($data);
            if ($rs->rowCount() > 0) {
                $row = $rs->fetch();
                $staff = $row['teacherOpen'];
            } else {
                $staff = 'X';
            }
            return $staff;
        } catch(PDOException $e) {
            print "<div>" . $e->getMessage() . "</div>" ;
        }  
    }
    ////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////
    function readReportID() {
        try {
            $data = array(
                'yearGroupID' => $this->yearGroupID,
                'schoolYearID' => $this->schoolYearID,
                'reportNum' => $this->term
            );
            $sql = "SELECT *
                FROM arrReportAssign
                INNER JOIN arrReport
                ON arrReportAssign.reportID = arrReport.reportID
                WHERE arrReportAssign.schoolYearID = :schoolYearID
                AND yearGroupID = :yearGroupID
                AND reportNum = :reportNum
                AND assignStatus = 1";
            //print $sql."<br>";
            //print_r($data);
            //print"<br>";
            $rs = $this->dbh->prepare($sql);
            $rs->execute($data);
            $reportAssignID = 0;
            if ($rs->rowCount() > 0) {
                $row = $rs->fetch();
                $reportAssignID = $row['reportAssignID'];
            }
            return $reportAssignID;
        } catch(PDOException $e) {
         print "<div>" . $e->getMessage() . "</div>" ;
        }  
    }
    ////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////
    function read_roleList() {
        try {
            $sql = "SELECT gibbonRoleID AS roleID, name AS roleName
                FROM gibbonRole
                WHERE category = 'Staff'
                ORDER BY name";
            $rs = $this->dbh->prepare($sql);
            $rs->execute();
            return $rs;
        } catch(PDOException $e) {
            print "<div>" . $e->getMessage() . "</div>" ;
        }  
    }
    ////////////////////////////////////////////////////////////////////////////
    
    ////////////////////////////////////////////////////////////////////////////
    function save() {
        // save status for each report for each role
        $reportList = $this->reportList;
        $reportList->execute();
        
        // set everyone to 0 for this report
        while ($row = $reportList->fetch()) {
            try {
                $data = array(
                    'reportID' => $row['reportID']
                );
                $sql = "UPDATE arrStatus
                    SET reportStatus = 0
                    WHERE reportID = :reportID";
                $rs = $this->dbh->prepare($sql);
                $rs->execute($data);
            } catch(PDOException $e) {
                print "<div>" . $e->getMessage() . "</div>" ;
            }  
        }
        
        $ok = true;
        foreach($_POST AS $key => $value) {
            // get abbreviated name of role
            $pos = strpos($key, "_");
            $reportID = intval(substr($key, 6, ($pos-6)));
            $roleID = intval(substr($key, $pos+1));
            try {
                $data = array(
                    'reportID' => $reportID,
                    'roleID' => $roleID
                );
                $sql = "UPDATE arrStatus
                    SET reportStatus = 1
                    WHERE reportID = :reportID
                    AND roleID = :roleID";
                $rs = $this->dbh->prepare($sql);
                $result = $rs->execute($data);
                if (!$result) {
                    $ok = $result;
                }
            } catch(PDOException $e) {
                print "<div>" . $e->getMessage() . "</div>" ;
            }                  
        }
        return $ok;
    }
    ////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////
    function mainform() {
        // show table of reports and roles
        $roleList = $this->roleList;
        $roleList->execute();
        $reportList = $this->reportList;
        $reportList->execute();
        if ($this->schoolYearID == 0) {
            return;
        }
        ?>
        <form name="frm_access" id="frm_access" method="post" action="">
            <input type='hidden' name='schoolYearID' value='<?php echo $this->schoolYearID ?>' />
            <table class='mini'>
                <tr>
                    <th style='width:100px;'>Report</th>
                    <?php
                    while ($row = $roleList->fetch()) {
                        echo "<th>".substr($row['roleName'], 0, 8)."</th>";
                    }
                    ?>
                </tr>
                <?php
                while ($row = $reportList->fetch()) {
                    // read status for each role for this report
                    echo "<tr>";
                        echo "<td style='width:150px;'>".substr($row['reportName'], 0, 20)."</td>";
                        $roleList->execute();
                        while ($row2 = $roleList->fetch()) {
                            $this->reportID = $row['reportID'];
                            $this->roleID = intval($row2['roleID']);
                            $status = $this->read_status();
                            $id = "status".$row['reportID'].'_'.$row2['roleID'];
                            $checked = '';
                            if ($status) {
                                $checked = 'checked';
                            }
                            echo "<td>";
                                echo "<input type='checkbox' name='$id' id='$id' $checked onchange='notSaved(\"status\")' />";
                            echo "</td>";
                        }
                    echo "</tr>";
                }
                ?>
            </table>
            <input type="submit" name="save" value="Save" />
        </form>
        <?php
    }
    ////////////////////////////////////////////////////////////////////////////
    
    ////////////////////////////////////////////////////////////////////////////////
    function read_status() {
        // check if report is assigned to year group
        try {
            $data = array(
                'reportID' => $this->reportID,
                'roleID' => $this->roleID
            );
            $sql = "SELECT reportStatus
                FROM arrStatus
                WHERE reportID = :reportID
                AND roleID = :roleID";
            $rs = $this->dbh->prepare($sql);
            $rs->execute($data);
            $reportStatus = false;
            if ($rs->rowCount() > 0) {
                $row = $rs->fetch();
                $reportStatus = $row['reportStatus'];
            }
            return $reportStatus;
        } catch(PDOException $e) {
            print "<div>" . $e->getMessage() . "</div>" ;
        }     
    }
    ////////////////////////////////////////////////////////////////////////////
}
