<?php
/*
 * function for creating reports to be written
 */

class def {

    var $class;
    var $msg;

    function defInit($guid, $dbh) {
        $this->guid = $guid;
        $this->dbh = $dbh;
        
        // get value of selected year
        $this->schoolYearID = getSchoolYearID($this->dbh, $schoolYearName, $currentYear);

        // check if reportID has been passed to page
        $this->reportID = getReportID();

        // check if add, edit or delete is required
        $this->mode = getMode();

        // save changes
        if (isset($_POST['save'])) {
            $ok = $this->save();
            setStatus($ok, 'Save', $this->msg, $this->class);
            if ($ok) {
                $this->reportID = '';
                $this->mode = '';
            }
        }

        // cancel changes
        if (isset($_POST['cancel'])) {
            $this->reportID = '';
            $this->mode = '';
        }

        // delete report
        if ($this->mode == 'delete') {
            $ok = $this->delete($this->dbh);
            setStatus($ok, 'Save', $this->msg, $this->class);
            if ($ok) {
                $this->reportID = '';
                $this->mode = '';
            }
        }
        $this->orientationList = array('', 'Portrait', 'Landscape');
    }
    ////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////
    function delete() {
        // delete report
        try {
            $data = array("reportID" => $this->reportID);
            $sql = "DELETE FROM arrReport
                WHERE reportID = :reportID";
            $rs = $this->dbh->prepare($sql);
            $ok = $rs->execute($data);
            return $rs;
        } catch(PDOException $e) {
            print "<div>" . $e->getMessage() . "</div>" ;
        }  
    }
    ////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////
    function save() {
        // save report
        $reportID = $_POST['reportID'];
        $reportName = $_POST['reportName'];
        $reportNum = $_POST['reportNum'];
        $orientation = $_POST['orientation'];
        
        // check values are valid
        $ok = true;

        // report name can't be blank
        if ($reportName == '') {
            $ok = false;
        }
        
        if ($ok) {
            // check for duplicate name
            try {
                $data = array(
                    "reportName" => $reportName,
                    "schoolYearID" => $this->schoolYearID
                );
                $sql = "SELECT reportID
                    FROM arrReport
                    WHERE reportName = :reportName
                    AND schoolYearID = :schoolYearID";
                $rs = $this->dbh->prepare($sql);
                $rs->execute($data);
                if ($rs->rowCount() > 0) {
                    $row = $rs->fetch();
                    if ($reportID <> $row['reportID']) {
                        $ok = false;
                    }
                }
            } catch(PDOException $e) {
                print "<div>" . $e->getMessage() . "</div>" ;
            }    
        }

        if ($ok) {
            try {
                $data = array(
                    "reportName" => $reportName,
                    "reportNum" => $reportNum,
                    "orientation" => $orientation,
                    "schoolYearID" => $this->schoolYearID
                );

                // values to update
                $set = "SET reportName = :reportName,
                    reportNum = :reportNum,
                    orientation = :orientation,
                    schoolYearID = :schoolYearID";
                
                if ($this->reportID > 0) {
                    // already exists so update
                    $data['reportID'] = $this->reportID;
                    $sql = "UPDATE arrReport $set WHERE reportID = :reportID";
                } else {
                    // new one so insert it
                    $sql = "INSERT IGNORE INTO arrReport $set";
                }
                $rs = $this->dbh->prepare($sql);
                $ok = $rs->execute($data);
            } catch(PDOException $e) {
                print "<div>" . $e->getMessage() . "</div>" ;
            }  
        }
        return $ok;
    }
    ////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////
    function formDefine() {
        // insert row in table to set values for report
        $termList = readTerm($this->dbh, $this->schoolYearID);
        ?>
        <tr>
            <td style='text-align:center'>
                <input type='hidden' name='reportID' value='<?php echo $this->reportID ?>' />
                <input type='text' name='reportName' value='<?php echo $this->reportName ?>' onkeydown="notSaved('status')" />
            </td>
            <td style='text-align:center'>
                <select name='reportNum' onchange="notSaved('status')")'>
                    <?php
                    while ($row = $termList->fetch()) {
                        ?>
                        <option value='<?php echo $row["termID"] ?>'
                                <?php if ($row["termID"] == $this->reportNum)
                                    echo "selected='selected'"; ?>>
                            <?php echo $row["name"] ?>
                        </option>
                        <?php
                    }
                    ?>
                </select>
            </td>
            <td>
                <select name='orientation' onchange="notSaved('status')">
                    <?php
                    for ($i=1; $i<count($this->orientationList); $i++) {
                        ?>
                        <option value='<?php echo $i ?>'
                            <?php if ($i == $this->orientation)
                                echo "selected='selected'"; ?>>
                            <?php echo $this->orientationList[$i] ?>
                        </option>
                        <?php
                    }
                    ?>
                </select>
            </td>
            <td style='text-align:center'>
                <input type='submit' name='save' value='Save' />
                <input type='submit' name='cancel' value='Cancel' />
            </td>
        </tr>
        <?php
    }
    ////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////
    function mainform() {
        // show table with all reports
        
        $linkPath = $_SESSION[$this->guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$this->guid]["module"].'/admin_define.php';
        $linkNew = $linkPath."&amp;mode=new";
        if ($this->schoolYearID == 0) {
            return;
        }
        ?>
        <form name='frm_define' id='frm_define' method='post' action=''>
            <?php
            echo "<input type='hidden' name='reportID' value='".$this->reportID."' />";
            echo "<input type='hidden' name='schoolYearID' value='".$this->schoolYearID."' />";
            echo "<p><a href='$linkNew'>".__($this->guid, "Add new")."</a></p>";
            echo "<table class='mini' style='width:100%'>";
                echo "<tr>";
                    echo "<th style='width:25%;'>".__($this->guid, "Report Name")."</th>";
                    echo "<th style='width:10%'>".__($this->guid, "Term")."</th>";
                    echo "<th style='width:15%'>".__($this->guid, "Orientation")."</th>";
                    echo "<th style='width:30%'>".__($this->guid, "Action")."</th>";
                echo "</tr>";

                
                // read list of reports for selected year
                $rs = readReport($this->dbh, $this->schoolYearID);
                if ($rs->rowCount() == 0 || $this->mode == 'new') {
                    $this->reportID = 0;
                    $this->reportName = '';
                    $this->reportNum = 0;
                    $this->orientation = 1;
                    $this->formDefine();
                }

                while ($row = $rs->fetch()) {
                    if ($this->reportID == $row['reportID']) {
                        $this->reportName = $row['reportName'];
                        $this->reportNum = $row['reportNum'];
                        $this->orientation = $row['orientation'];
                        $this->formDefine();
                    } else {
                        $linkEdit = $linkPath.
                                "&amp;reportID=".$row['reportID'].
                                "&amp;schoolYearID=".$this->schoolYearID.
                                "&amp;mode=edit";
                        $messageDelete = "WARNING All reports associated with this will be lost.  Delete ".$row['reportName']."?";
                        $linkDelete = "window.location = \"$linkPath&amp;reportID=".$row['reportID'].
                                "&amp;mode=delete\"";
                        
                        echo "<tr>";
                            echo "<td>".$row['reportName']."</td>";
                            echo "<td style='text-align:center'>".$row['termName']."</td>";
                            echo "<td>".$this->orientationList[$row['orientation']]."</td>";
                            echo "<td style='text-align:center'>";
                                echo "<a href='$linkEdit'>Edit</a> <a href='#' onclick='if (confirm(\"$messageDelete\")) $linkDelete'>Delete</a>";
                            echo "</td>";
                        echo "</tr>";
                        
                    }
                }
                ?>
            </table>
        </form>
        <?php
    }
    ////////////////////////////////////////////////////////////////////////////
}