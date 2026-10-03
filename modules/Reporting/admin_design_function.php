<?php
/*
 * functions for html designer
 */

class des {

    var $class;
    var $msg;

    function desInit($guid, $dbh) {
        $this->guid = $guid;
        $this->dbh = $dbh;
        
        // get value of selected year
        $this->schoolYearID = getSchoolYearID($this->dbh, $schoolYearName, $currentYear);

        // check if reportID has been passed to page
        $this->reportID = getReportID();
        
    }
    ////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////
    function chooseReport() {
        // select report to design
        $repList = $this->readReportList();
        $repList->execute();

        ob_start();
        ?>
        <div style = "padding:2px;">
            <?php
            if ($repList->rowCount() > 0) {
                ?>
                <div style = "float:left;width:30%;" class = "smalltext">Report</div>
                <div style = "float:left;">
                    <form name="frm_selectreport" method="post" action="">
                        <input type="hidden" name="schoolYearID" value="<?php echo $this->schoolYearID ?>" />
                        <select name="reportID" onchange="this.form.submit()">
                            <option></option>
                            <?php
                            while ($row = $repList->fetch()) {
                                $selected = ($this->reportID == $row['reportID']) ? "selected" : "";
                                echo "<option value='".$row['reportID']."' $selected>";   
                                    echo $row['reportName'];
                                echo "</option>";
                            }
                            ?>
                        </select>
                    </form>
                </div>
                <?php
            } else {
                echo "<div class='smalltext'>No reports created for this year</div>";
            }
            ?>
        </div>
        <?php
        return ob_get_clean();
    }
    ////////////////////////////////////////////////////////////////////////////
    
    ////////////////////////////////////////////////////////////////////////////////
    function readReportList() {
        // read reports available for this year group
        try {
            $data = array(
                'schoolYearID' => $this->schoolYearID
            );
            $sql = "SELECT arrReport.reportID, reportName
                FROM arrReport
                WHERE arrReport.schoolYearID = :schoolYearID
                ORDER BY reportNum";
            //print $sql;
            //print_r($data);
            $rs  = $this->dbh->prepare($sql);
            $rs->execute($data);
            return $rs;
        } catch(PDOException $e) {
            print "<div>" . $e->getMessage() . "</div>" ;
        }
    }
    ////////////////////////////////////////////////////////////////////////////
    
    ////////////////////////////////////////////////////////////////////////////
    function mainform() {
        // setup designer template
        ?>
        <input type='hidden' id='reportID' value='<?php echo $this->reportID ?>' />
        
        <p id='selectReport' style="padding-top:4px;"></p>

        <p id='sectionTypeList'></p>

        <div id='template'>
            <form id='report_template'>
                <table id='template_table' style='width:100%'>
                    <thead></thead>
                    <tbody></tbody>
                </table>
            </form>
        </div>
        <div>&nbsp;</div>
        <?php
    }
    ////////////////////////////////////////////////////////////////////////////
}