<?php
if (isActionAccessible($guid, $connection2,"/modules/Reporting/pastoral.php")==FALSE) {
    //Acess denied
    print "<div class='error'>" ;
            print "You do not have access to this action." ;
    print "</div>" ;
} else {
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Pastoral').'</div>';
    echo '</div>';    
    // proceed
    // include function pages
    $modpath =  "./modules/".$_SESSION[$guid]["module"];
    include $modpath."/pastoral_function.php" ;
    include $modpath."/function.php";
    setSessionVariables($guid, $connection2);

    $pastoral = new pastoral();
    $pastoral->pastoralInit($guid, $connection2);
    $pastoral->modpath = $modpath;

    // return page for forms
    $thisPage = 'pastoral';
    $title = 'Pastoral';

    echo "<div class='instruct' id='instruct' style='display:none'>";
    echo "<div style='float:left'><strong>Instructions</strong></div>";
    echo "<div style='float:right'>";
    echo "<a href='#' onclick='instructHide()'>Hide</a>";
    echo "</div>";
    echo "<div style=clear:both></div>";
    echo "<p>Select</p>";
    echo "<ul>";
    echo "<li>Year group</li>";
    echo "<li>Class</li>";
    echo "<li>Report</li>";
    echo "<li>Student</li>";
    echo "<li>Read assessments from other teachers</li>";
    echo "<li>Write comment and save</li>";
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
    if ($pastoral->rollGroupID > 0 || $pastoral->rollGroupID != '') {
        navbar($guid, $connection2, $thisPage, $pastoral->studentID, $pastoral->reportID, $pastoral->classID, $pastoral->rollGroupID, $pastoral->schoolYearID, $pastoral->yearGroupID);
    }
    
    $_SESSION[$guid]['sidebarExtra'] = "<div>";
    $_SESSION[$guid]['sidebarExtra'] .= chooseSchoolYear($connection2, $pastoral->studentID, $pastoral->reportID, $pastoral->schoolYearID);
    $_SESSION[$guid]['sidebarExtra'] .= chooseYearGroup($connection2, $pastoral->yearGroupID, $pastoral->schoolYearID);
    if ($pastoral->yearGroupID > 0) {
        $_SESSION[$guid]['sidebarExtra'] .= $pastoral->chooseRollgroup($connection2);
    }
    if ($pastoral->rollGroupID > 0) {
        $_SESSION[$guid]['sidebarExtra'] .= chooseReport($connection2, $pastoral->classID, $pastoral->reportID, $pastoral->rollGroupID, $pastoral->schoolYearID, $pastoral->teacherID, $pastoral->yearGroupID);
    }
    $_SESSION[$guid]['sidebarExtra'] .= "</div><div style = 'clear:both;'>&nbsp;</div>";
    $_SESSION[$guid]['sidebarExtra'] .= $pastoral->showRollGroupList($guid, $connection2);

    //echo "<div>&nbsp</div>";
    echo "<div class = '$pastoral->class' id = 'status'>$pastoral->msg</div>";
    echo "<div>";
        if ($pastoral->reportID > 0) { // rep_status shows whether user may view edit or do nothing to reports
            if ($pastoral->repAccess == 0) {
                echo "<div class='highlight'>Reports may be viewed but not edited</div>";
            }
            if ($pastoral->rollGroupID > 0) { // make sure a class has been selected
                $pastoral->mainform();
            }
        }
    echo "</div>";
}