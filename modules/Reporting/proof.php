<?php
if (isActionAccessible($guid, $connection2,"/modules/Reporting/proof.php")==FALSE) {
    //Acess denied
    print "<div class='error'>" ;
            print "You do not have access to this action." ;
    print "</div>" ;
} else {
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Proof').'</div>';
    echo '</div>';    
    // proceed
    // include function pages
    $modpath =  "./modules/".$_SESSION[$guid]["module"];
    include $modpath."/proof_function.php" ;
    include $modpath."/subject_function.php" ;
    include $modpath."/function.php";
    setSessionVariables($guid, $connection2);

    $proof = new proof();
    $proof->proofInit($guid, $connection2);
    $proof->modpath = $modpath;

    // return page for forms
    $thisPage = 'proof';
    $title = 'Proof Reading';

    echo "<div class='instruct' id='instruct' style='display:none'>";
    echo "<div style='float:left'><strong>Instructions</strong></div>";
    echo "<div style='float:right'>";
    echo "<a href='#' onclick='instructHide()'>Hide</a>";
    echo "</div>";
    echo "<div style=clear:both></div>";
    echo "<p>Check comments for any student</p>";
    echo "<ul>";
    echo "<li>Select a year group from the drop down box on the right</li>";
    echo "<li>Select a roll group</li>";
    echo "<li>Select a report</li>";
    echo "<li>Select a student</li>";
    echo "<li>Click on a comment to open a text box in which you can edit and save the existing comment.</li>";
    echo "</ul>";
    echo "</div>";
    echo "<div id='instructShow' style='display:block;float:right' class='smalltext'>";
    echo "<a href='#' onclick='instructShow()'>Instructions</a>";
    echo "</div>";
    echo "<div style='clear:both;'></div>";
    ?>
    <script type="text/javascript">
    document.onkeypress = stopRKey;
    </script>
    <?php
    ///////////////////////////////////////////////////////////////////////////////////////////
    // output to screen
    ///////////////////////////////////////////////////////////////////////////////////////////
    if ($proof->rollGroupID > 0 || $proof->rollGroupID != '') {
        navbar($guid, $connection2, $thisPage, $proof->studentID, $proof->reportID, $proof->classID, $proof->rollGroupID, $proof->schoolYearID, $proof->yearGroupID);
    }
    
    $_SESSION[$guid]['sidebarExtra'] = "<div>";
    $_SESSION[$guid]['sidebarExtra'] .= chooseSchoolYear($connection2, $proof->studentID, $proof->reportID, $proof->schoolYearID);
    $_SESSION[$guid]['sidebarExtra'] .= chooseYearGroup($connection2, $proof->yearGroupID, $proof->schoolYearID);
    if ($proof->yearGroupID > 0) {
        $_SESSION[$guid]['sidebarExtra'] .= $proof->chooseRollgroup($connection2);
    }
    if ($proof->rollGroupID > 0) {
        $_SESSION[$guid]['sidebarExtra'] .= chooseReport($connection2, $proof->classID, $proof->reportID, $proof->rollGroupID, $proof->schoolYearID, $proof->teacherID, $proof->yearGroupID);
    }
    $_SESSION[$guid]['sidebarExtra'] .= "</div><div style = 'clear:both;'>&nbsp;</div>";
    $_SESSION[$guid]['sidebarExtra'] .= $proof->showRollGroupList($guid, $connection2);

    echo "<div class = '$proof->class' id = 'status'>$proof->msg</div>";
    
    //echo "<div>&nbsp</div>";
    echo "<div>";
        if ($proof->reportID > 0) { // rep_status shows whether user may view edit or do nothing to reports
            if ($proof->repAccess == 0) {
                echo "<div class='highlight'>Reports may be viewed but not edited</div>";
            }
            if ($proof->rollGroupID > 0) { // make sure a class has been selected
                $proof->mainform();
            }
        }
    echo "</div>";
}