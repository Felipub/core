<?php
/*
 * functions for creating criteria
 */

class crit {

    var $class;
    var $msg;
    var $typeList = array(
        "Grade Scale", "Numeric"
    );

    function critInit($guid, $dbh) {
        $this->guid = $guid;
        $this->dbh = $dbh;
        
        $this->schoolYearID = getSchoolYearID($this->dbh, $schoolYearName, $currentYear);

        $this->yearGroupID = getYearGroupID();
        $this->subjectID = $this->getSubjectID();
        $this->criteriaID = $this->getCriteriaID();

        // check if add, edit or delete is required
        $this->mode = getMode();

        if (isset($_POST['save'])) {
            $ok = $this->save();
            setStatus($ok, 'Save', $this->msg, $this->class);
            if ($ok) {
                $this->criteriaID = '';
                $this->mode = '';
            }
        }
        
        if (isset($_POST['orderSave'])) {
            $ok = $this->saveOrder();
            setStatus($ok, 'Save', $this->msg, $this->class);
            if ($ok) {
                $this->criteriaID = '';
                $this->mode = '';
            }
        }

        if (isset($_POST['cancel']) || isset($_POST['orderReset'])) {
            $this->criteriaID = '';
            $this->mode = '';
        }

        if ($this->mode == 'delete') {
            $ok = $this->delete();
            setStatus($ok, 'Save', $this->msg, $this->class);
            if ($ok) {
                $this->criteriaID = '';
                $this->mode = '';
            }
        }
        
        $this->gradeScaleList = readGradeScaleList($this->dbh, $this->schoolYearID);
    }
    ////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////
    function getSubjectID() {
        $subjectID = '';
        if (isset($_POST['subjectID'])) {
            $subjectID = $_POST['subjectID'];
        } else {
            if (isset($_GET['subjectID'])) {
               $subjectID = $_GET['subjectID'];
            }
        }
        return $subjectID;
    }

    function getCriteriaID() {
        $criteriaID = '';
        if (isset($_POST['criteriaID'])) {
            $criteriaID = $_POST['criteriaID'];
        } else {
            if (isset($_GET['criteriaID'])) {
               $criteriaID = $_GET['criteriaID'];
            }
        }
        return $criteriaID;
    }
    ////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////
    function readCriteriaList() {
        // read list of criteria associated with a course/subject
        try {
            $data = array(
                "subjectID" => $this->subjectID,
                "schoolYearID" => $this->schoolYearID,
                "yearGroupID" => $this->yearGroupID
            );
            $sql = "SELECT arrCriteria.criteriaID,
                arrCriteria.subjectID,
                arrCriteria.criteriaName,
                arrCriteria.criteriaType,
                arrCriteria.gradeScaleID,
                arrCriteria.criteriaOrder,
                gibbonScale.name,
                gibbonScale.nameShort
                FROM arrCriteria
                LEFT JOIN gibbonScale
                ON gibbonScale.gibbonScaleID = arrCriteria.gradeScaleID
                WHERE arrCriteria.subjectID = :subjectID
                AND arrCriteria.schoolYearID = :schoolYearID
                AND arrCriteria.yearGroupID = :yearGroupID
                ORDER BY criteriaOrder";
            $rs = $this->dbh->prepare($sql);
            $rs->execute($data);
            return $rs;
        } catch(PDOException $e) {
            print "<div>" . $e->getMessage() . "</div>" ;
        }  
    }
    ////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////
    function delete() {
        try {
            $data = array('criteriaID' => $this->criteriaID);
            $sql = "DELETE FROM arrCriteria
                WHERE criteriaID = :criteriaID";
            $rs = $this->dbh->prepare($sql);
            $ok = $rs->execute($data);
            return $ok;
        } catch(PDOException $e) {
            print "<div>" . $e->getMessage() . "</div>" ;
        }  
    }
    ////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////
    function save() {
        $criteriaName = isset($_POST['criteriaName']) ? trim($_POST['criteriaName']) : '';
        $criteriaType = isset($_POST['criteriaType']) ? $_POST['criteriaType'] : 0;
        if ($criteriaType == 1) {
            $gradeScaleID = 0;
        } else {
            $gradeScaleID = isset($_POST['gradeScaleID']) ? $_POST['gradeScaleID'] : 0;
        }
        $ok = true;
        // check if criterion name already exists
        try {
            $data = array(
                'criteriaName' => $criteriaName,
                'schoolYearID' => $this->schoolYearID,
                'subjectID' => $this->subjectID,
                'yearGroupID' => $this->yearGroupID
            );
            $sql = "SELECT criteriaID
                FROM arrCriteria
                WHERE criteriaName = :criteriaName
                AND schoolYearID = :schoolYearID
                AND subjectID = :subjectID
                AND yearGroupID = :yearGroupID";
            $rs = $this->dbh->prepare($sql);
            $rs->execute($data);
            if ($rs->rowCount() > 0) {
                $row = $rs->fetch();
                if ($this->criteriaID <> $row['criteriaID']) {
                    $ok = false;
                }
            }
        } catch(PDOException $e) {
            print "<div>" . $e->getMessage() . "</div>" ;
        } 
        
        if ($ok) {
            try {
                $data = array(
                    'criteriaName' => $criteriaName,
                    'criteriaType' => $criteriaType,
                    'gradeScaleID' => $gradeScaleID,
                    'schoolYearID' => $this->schoolYearID,
                    'yearGroupID' => $this->yearGroupID
                );
                $set = "SET criteriaName = :criteriaName,
                    criteriaType = :criteriaType,
                    gradeScaleID = :gradeScaleID,
                    schoolYearID = :schoolYearID,
                    yearGroupID = :yearGroupID";
                if ($this->criteriaID > 0) {
                    $data['criteriaID'] = $this->criteriaID;
                    $sql = "UPDATE arrCriteria $set WHERE criteriaID = :criteriaID";
                } else {
                    $data['subjectID'] = $this->subjectID;
                    $set .= ", subjectID = :subjectID";
                    $sql = "INSERT IGNORE INTO arrCriteria $set";
                }
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
    }
    ////////////////////////////////////////////////////////////////////////////
    
    ////////////////////////////////////////////////////////////////////////////
    function saveOrder() {
        $criteria = $_POST['rowCriteriaID'];
        $ok = true;
        for ($i=0; $i<count($criteria); $i++) {
            $criteriaID = $criteria[$i];
            $data = array(
                'criteriaID' => $criteriaID,
                'criteriaOrder' => ($i+1)
            );
            try {
                $sql = "UPDATE arrCriteria
                    SET criteriaOrder = :criteriaOrder
                    WHERE criteriaID = :criteriaID";
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
    function formCriteria() {
        $disabled = ($this->criteriaType == 1) ? "disabled" : "";
        echo "<tr>";
            //echo "<td></td>";
            echo "<td>";
                echo "<input type='text' name='criteriaName' id='criteriaName' value='$this->criteriaName' size='40' style='width:200px;' onkeydown='notSaved(\"status\")' />";
            echo "</td>";
            echo "<td>";
                echo "<select name='criteriaType' id='criteriaType' style='width:100px;' onchange='notSaved(\"status\")' >";
                    for ($i=0; $i<count($this->typeList); $i++) {
                        $selected = ($this->criteriaType == $i) ? "selected" : "";
                        echo "<option $selected value='$i'>".$this->typeList[$i]."</option>";
                    }
                echo "</select>";
            echo "</td>";
            echo "<td>";
                echo "<select $disabled name='gradeScaleID' id='gradeScaleID' style='width:200px;' onchange='notSaved(\"status\")' >";
                    echo "<option value='0'>None</option>";
                    while ($row = $this->gradeScaleList->fetch()) {
                        $selected = ($this->gradeScaleID == $row['gibbonScaleID']) ? "selected" : "";
                        echo "<option $selected value='".$row['gibbonScaleID']."'>".$row['name']."</option>";
                    }
                echo "</select>";
            echo "</td>";
            echo "<td>";
                echo "<input type='submit' name='save' value='Save' />";
                echo "<input type='submit' name='cancel' value='Cancel' />";
            echo "</td>";
        echo "</tr>";
        ?>
        <script>
            $('#criteriaType').change(function() {
                var status = ($(this).val() == '1') ? "disabled" : "";
                $('#gradeScaleID').prop('disabled', status);
            });
        </script>
        <?php
    }
    ////////////////////////////////////////////////////////////////////////////
    
    ////////////////////////////////////////////////////////////////////////////
    function mainform() {
      
        if ($this->yearGroupID > 0 && $this->subjectID > 0) {
            $path = $_SESSION[$this->guid]['absoluteURL']."/modules/".$_SESSION[$this->guid]["module"];
            $modpath = $_SESSION[$this->guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$this->guid]["module"];
            $linkPath = $modpath.'/admin_criteria.php';
            $linkNew = $linkPath.
                    "&amp;subjectID=".$this->subjectID.
                    "&amp;yearGroupID=".$this->yearGroupID.
                    "&amp;schoolYearID=".$this->schoolYearID.
                    "&amp;mode=new";
            $this->criteriaList = $this->readCriteriaList();
            ?>
            <div>&nbsp;</div>
            <form id='frm_define' name='frm_define' method='post' action=''>
                <?php
                echo "<input type='hidden' name='criteriaID' value='$this->criteriaID' />";
                echo "<input type='hidden' name='subjectID' value='$this->subjectID' />";
                echo "<input type='hidden' name='yearGroupID' value='$this->yearGroupID' />";
                echo "<input type='hidden' name='schoolYearID' id='schoolYearID' value='$this->schoolYearID' />";
                echo "<div style='font-size:small'>";
                echo "<div style='display:inline-block;margin-right:10px;'><a href='$linkNew'>".__($this->guid, "Add new")."</a>  ".__($this->guid, "(drag to change order)")."</div>";
                echo "<div style='display:inline-block;margin-right:10px;'><a href='#' id='copycrit'>".__($this->guid, "Copy")."</a></div>";
                echo "<div>Names must be unique</div>";
                echo "</div>";
                
                echo "<table class='mini' style='width:100%' id='critTable'>";
                    echo "<thead>";
                        echo "<tr>";
                            //echo "<th style='width:10%'>".__($this->guid, "Order")."</td>";
                            echo "<th style='width:30%;'>".__($this->guid, "Criteria")."</th>";
                            echo "<th style='width:15%;'>".__($this->guid, "Type")."</th>";
                            echo "<th style='width:35%;'>".__($this->guid, "Grade Scale")."</th>";
                            echo "<th style='width:20%;'>".__($this->guid, "Action")."</th>";
                        echo "</tr>";
                    echo "</thead>";
                    
                    echo "<tbody>";
                    if ($this->criteriaList->rowCount() == 0 || $this->mode == 'new') {
                        $this->criteriaName = '';
                        $this->criteriaType = 0;
                        $this->gradeScaleID = 0;
                        $this->criteriaOrder = $this->criteriaList->rowCount() + 1;
                        $this->formCriteria();
                    }
                    while ($row = $this->criteriaList->fetch()) {
                        if ($this->criteriaID == $row['criteriaID']) {
                            $this->criteriaName = $row['criteriaName'];
                            $this->criteriaType = $row['criteriaType'];
                            $this->gradeScaleID = $row['gradeScaleID'];
                            $this->formCriteria();
                        } else {
                            $linkEdit = $linkPath.
                                "&amp;criteriaID=".$row['criteriaID'].
                                "&amp;subjectID=".$this->subjectID.
                                "&amp;yearGroupID=".$this->yearGroupID.
                                "&amp;schoolYearID=".$this->schoolYearID.
                                "&amp;mode=edit";
                            $messageDelete = "WARNING All grades associated with this criterion will be lost.  Delete ".$row['criteriaName']."?";
                            $linkDelete = "window.location = \"$linkPath&amp;criteriaID=".$row['criteriaID'].
                                "&amp;subjectID=".$this->subjectID.
                                "&amp;yearGroupID=".$this->yearGroupID.
                                "&amp;schoolYearID=".$this->schoolYearID.
                                "&amp;mode=delete\"";
                            $gradeScale = ($row['name'] == NULL) ? "None" : $row['name'];
                            
                            echo "<tr class='crititem'>";
                                //echo "<td style='text-align:center;'><img src='".$this->modpath."/images/drag.png' alt='drag' height='16' /></td>";
                                echo "<td>";
                                    echo "<input type='hidden' name='rowCriteriaID[]' value='".$row['criteriaID']."' />";
                                    echo $row['criteriaName'];
                                echo "</td>";
                                echo "<td>";
                                    echo $this->typeList[$row['criteriaType']];
                                echo "</td>";
                                echo "<td>";
                                    echo $gradeScale; 
                                echo "</td>";
                                echo "<td style='text-align:center'>";
                                    echo "<a href='$linkEdit'>Edit</a> <a href='#' onclick='if (confirm(\"$messageDelete\")) $linkDelete'>".__($this->guid, "Delete")."</a>";
                                echo "</td>";
                            echo "</tr>";
                        }
                    }
                    echo "</tbody>";
                    ?>
                </table>
                <!--
                <input type='submit' name='orderSave' value='Save Order' />
                <input type='submit' name='orderReset' value='Reset' />
                -->
            </form>
            
            <p>&nbsp;</p>
            <div id='copyCriteria' style='background-color: #eeeeee;padding:4px;display:none;'>
                <form id='copyCriteriaForm' method='post'>
                    <?php
                    $this->copyCriteriaList($this->dbh);
                    //$this->copyReportList($this->dbh);
                    $this->copyYearGroupList($this->dbh);
                    $this->copySubjectList($this->dbh);
                    ?>
                    <div>
                    <button type='button' id='copySubmit'>Copy</button>
                    </div>
                </form>
            </div>
            <p>&nbsp;</p>
            
            <script>
                var path = '<?php echo $path ?>';
                var orderpath = path + "/admin_criteria_ajax.php"; 
                $('#critTable tbody').sortable({
                    // save order after dragging to new position
                    //change: function() {
                    //    $('#status').html('Remember to save').removeClass().addClass("warning");
                    //}
                    stop: function() {
                        var formData = $('#frm_define').serialize();
                        $.ajax({
                            url: orderpath,
                            data: {
                                formData: formData
                            },
                            type: 'POST',
                            success: function(data) {
                                console.log(data);
                            }
                        });
                    }
                });
                
                // preserve table width when dragging
                $('td').each(function(){
                    $(this).css('width', $(this).width() +'px');
                });
                
                $('#copycrit').click(function() {
                    $('#copyCriteria').show();
                });
                
                $('.criteriaListAll').click(function() {
                    checkAll('criteriaList', $(this).prop('checked'));
                });

                $('.subjectListAll').click(function() {
                    checkAll('subjectList', $(this).prop('checked'));
                });
                
                $('#frm_define').submit(function(e) {
                    if ($('#criteriaName').val() === '') {
                        alert("You must enter a name");
                        e.preventDefault();
                    }
                });
                
                // year group changed so change subject list
                $('#yearGroupIDcopy').change(function() {
                    orderpath = path + "/admin_criteria_subject_ajax.php",
                    $.ajax({
                        url: orderpath,
                        data: {
                            yearGroupID: $(this).val(),
                            schoolYearID: $('#schoolYearID').val()
                        },
                        type: 'POST',
                        dataType: 'JSON',
                        success: function(data) {
                            console.log(data);
                            var html = '';
                            $.each(data.subjectList, function(i, sub) {
                                html += "<div>";
                                    html += "<input type='checkbox' class='subjectList' name='subjectIDcopy' value='" + sub.subjectID + "' checked /> ";
                                    html += sub.subjectName;
                                html += "</div>";
                            });
                            $('#subjectList').html(html);
                        }
                    });
                });
                
                // submitted now copy criteria to seleted targets
                $('#copySubmit').click(function() {
                    // copy criteria to selected targets
                    var formData = $('#copyCriteriaForm').serialize();
                    //console.log(formData);
                    orderpath = path + "/admin_criteria_copy_ajax.php",
                    $.ajax({
                        url: orderpath,
                        data: {
                            formData: formData,
                            schoolYearID: $('#schoolYearID').val(),
                            yearGroupID: $('#yearGroupID').val()
                        },
                        type: 'POST',
                        success: function(data) {
                            console.log(data);
                            alert('Copied');
                        }
                    });
                });
            </script>
            <?php
        }
        ?>
        <?php
    }
    ////////////////////////////////////////////////////////////////////////////
    
    ////////////////////////////////////////////////////////////////////////////////
    function choose_subject() {
        // select subject
        ob_start();
        $found = false;
        if ($this->yearGroupID > 0) {
            $subjectList = $this->readSubjectlist($this->dbh);
            ?>
            <div style = "padding:2px;">
                <div style="float:left;width:30%" class = "smalltext">Subject</div>
                <div style="float:left;width:70%">
                    <form name='frm_subject' method='post' action='' style="display:inline">
                        <input type='hidden' name='yearGroupID' value='<?php echo $this->yearGroupID ?>' />
                        <input type='hidden' name='schoolYearID' value='<?php echo $this->schoolYearID ?>' />
                        <select name='subjectID' id='subjectID' style="width:95%" onchange="this.form.submit()">
                            <option></option>
                            <?php
                            if ($subjectList->rowCount() > 0) {
                                while ($row = $subjectList->fetch()) {
                                    $selected = "";
                                    if ($this->subjectID == $row['subjectID']) {
                                        $selected = "selected";
                                        $found = true;
                                    }
                                    $subjectName = trimCourseName($row['subjectName']);
                                    echo "<option value='".$row['subjectID']."' $selected>";
                                        echo trimCourseName($row['subjectName']);
                                    echo "</option>";
                                }
                            }
                            ?>
                        </select>
                    </form>
                </div>
                <div style="clear:both"></div>
            </div>
            <?php
            if (!$found) {
                $this->subjectID = 0;
            }
        }
        return ob_get_clean();
    }
    ////////////////////////////////////////////////////////////////////////////////
    
    ////////////////////////////////////////////////////////////////////////////////
    function readSubjectlist() {
        try {
            $data = array(
                "schoolYearID" => $this->schoolYearID,
                "yearGroupID" => '%'.$this->yearGroupID.'%'
            );
            $sql = "SELECT DISTINCT gibbonCourse.gibbonCourseID AS subjectID, 
                gibbonCourse.name AS subjectName
                FROM gibbonCourse
                INNER JOIN gibbonCourseClass
                ON gibbonCourse.gibbonCourseID = gibbonCourseClass.gibbonCourseID
                WHERE gibbonSchoolYearID = :schoolYearID
                AND gibbonYearGroupIDList LIKE :yearGroupID
                AND reportable = 'Y'
                ORDER BY subjectName";
            $rs = $this->dbh->prepare($sql);
            $rs->execute($data);
            return $rs;
        } catch(PDOException $e) {
            print "<div>" . $e->getMessage() . "</div>" ;
        }              
    }
    ////////////////////////////////////////////////////////////////////////////////
    
    ////////////////////////////////////////////////////////////////////////////////
    function copySubjectList() {
        $subjectList = $this->readSubjectlist($this->dbh);
        ?>
        <div style='display:inline-block;vertical-align:top;'>
            <div><strong>Select subjects to copy to</strong></div>
            <?php
            echo "<div style='margin-bottom:4px;'><input type='checkbox' class='subjectListAll' value='1' /> <em>Check all</em></div>";
            echo "<div id='subjectList'>";
                while ($row = $subjectList->fetch()) {
                    echo "<div>";
                        echo "<input type='checkbox' class='subjectList' name='subjectIDcopy' value='".$row['subjectID']."' checked /> ";
                        echo $row['subjectName'];
                    echo "</div>";
                }
            echo "</div>";
            ?>
        </div>
        <?php
    }
    ////////////////////////////////////////////////////////////////////////////////
    
    ////////////////////////////////////////////////////////////////////////////////
    function copyYearGroupList() {
        $yearGroupList = readYeargroup($this->dbh);
        ?>
        <div style='display:inline-block;margin-right:10px;vertical-align:top;'>
            <div><strong>Select year group to copy to</strong></div>
            <?php
            echo "<select name='yearGroupIDcopy' id='yearGroupIDcopy'>";
                while ($row = $yearGroupList->fetch()) {
                    $selected = ($row['gibbonYearGroupID'] == $this->yearGroupID) ? "selected" : "";
                    echo "<option $selected value='".$row['gibbonYearGroupID']."'>";
                        echo $row['name'];
                    echo "</option>";
                }
            echo "</select>";
            ?>
        </div>
        <?php
    }
    ////////////////////////////////////////////////////////////////////////////////
    
    ////////////////////////////////////////////////////////////////////////////////
    function copyCriteriaList() {
        ?>
        <div style='display:inline-block;margin-right:10px;vertical-align:top;'>
            <div><strong>Select criteria to copy</strong></div>
            <?php
            echo "<div style='margin-bottom:4px;'><input type='checkbox' class='criteriaListAll' value='1' /> <em>Check all</em></div>";
            $this->criteriaList->execute();
            while ($row = $this->criteriaList->fetch()) {
                echo "<div>";
                    echo "<input type='checkbox' class='criteriaList' name='criteriaIDcopy' value='".$row['criteriaID']."' checked /> ";
                    echo $row['criteriaName'];
                echo "</div>";
            }
            ?>
            <p>&nbsp;</p>
        </div>
        <?php
    }
    ////////////////////////////////////////////////////////////////////////////////
}