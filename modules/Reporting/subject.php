<?php
/*
 * write subject reports
 */

if (isActionAccessible($guid, $connection2,"/modules/Reporting/subject.php")==FALSE) {
    //Acess denied
    print "<div class='error'>" ;
            print "You do not have access to this action." ;
    print "</div>" ;
} else {
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Subject').'</div>';
    echo '</div>';    
    // proceed
    // include function pages
    $modpath =  "./modules/".$_SESSION[$guid]["module"];
    include $modpath."/subject_function.php" ;
    include $modpath."/function.php";

    setSessionVariables($guid, $connection2);
    
    $subrep = new subrep();
    $subrep->subrepInit($guid, $connection2);
    $subrep->modpath = $modpath;

    // return page for forms
    $thisPage = 'subject';
    $title = 'Write Subject Reports';
    ?>
    <script type="text/javascript">
    document.onkeypress = stopRKey;
    </script>
    <?php

    ///////////////////////////////////////////////////////////////////////////////////////////
    // output to screen
    ///////////////////////////////////////////////////////////////////////////////////////////
    echo "<div class='instruct' id='instruct' style='display:none'>";
    echo "<div style='float:left'><strong>Instructions</strong></div>";
    echo "<div style='float:right'>";
    echo "<a href='#' onclick='instructHide()'>Hide</a>";
    echo "</div>";
    echo "<div style=clear:both></div>";
    echo "<ul>";
    echo "<li>Use this page to write <em>subject</em> and <em>UOI</em> reports</li>";
    echo "<li>Select a class from the drop down box on the right</li>";
    echo "<li>Select a report</li>";
    echo "<li>Select a student</li>";
    echo "<li>Write report</li>";
    echo "<li>Save</li>";
    echo "</ul>";
    echo "</div>";
    echo "<div id='instructShow' style='display:block;float:right' class='smalltext'>";
    echo "<a href='#' onclick='instructShow()'>Instructions</a>";
    echo "</div>";
    echo "<div style='clear:both;'></div>";


    navbar($guid, $connection2, $thisPage, $subrep->studentID, $subrep->reportID, $subrep->classID, '', $subrep->schoolYearID, $subrep->yearGroupID);
    
    $_SESSION[$guid]['sidebarExtra'] = "<div>";
    $_SESSION[$guid]['sidebarExtra'] .= chooseSchoolYear($connection2, $subrep->studentID, $subrep->reportID, $subrep->schoolYearID);
    $_SESSION[$guid]['sidebarExtra'] .= $subrep->showTeacherList($connection2);
    $_SESSION[$guid]['sidebarExtra'] .= $subrep->showClassesList($connection2);
    if ($subrep->classID > 0) {
        $_SESSION[$guid]['sidebarExtra'] .= chooseReport($connection2, $subrep->classID, $subrep->reportID, '', $subrep->schoolYearID, $subrep->teacherID, $subrep->yearGroupID);
    }
    $_SESSION[$guid]['sidebarExtra'] .= "</div><div style = 'clear:both;'>&nbsp;</div>";
    $_SESSION[$guid]['sidebarExtra'] .= $subrep->showClassList($guid, $connection2);

    //echo "<div>&nbsp</div>";
    echo "<div class = '$subrep->class' id = 'status'>$subrep->msg</div>";
    echo "<div>";
        if ($subrep->reportID > 0) { // rep_status shows whether user may view edit or do nothing to reports
            if ($subrep->repAccess == 0) {
                echo "<div class='highlight'>Reports may be viewed but not edited</div>";
            }
            if ($subrep->classID > 0) { // make sure a class has been selected
                if ((($subrep->view == $subrep->studView  && $subrep->studentID > 0) || $subrep->view == $subrep->classView) && $subrep->classList->rowCount() > 0) {
                    // show report if a student is selected in student view or class view is selected and there are students in the class
                    $subrep->showReport($guid, $connection2);
                }
            }
        }  
    echo "</div>";
}