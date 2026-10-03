<?php
/*
 * create and copy criteria
 */

if (isActionAccessible($guid, $connection2,"/modules/Reporting/admin_criteria.php")==FALSE) {
    //Acess denied
    print "<div class='error'>" ;
            print "You do not have access to this action." ;
    print "</div>" ;
} else {
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Admin Criteria').'</div>';
    echo '</div>';    
    // proceed
    // include function pages
    $modpath =  "./modules/".$_SESSION[$guid]["module"];

    include $modpath."/admin_function.php" ;
    include $modpath."/admin_criteria_function.php" ;
    include $modpath."/function.php";

    $crit = new crit();
    $crit->critInit($guid, $connection2);
    $crit->modpath = $modpath;

    $title = "Criteria";
    setSessionVariables($guid, $connection2);

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
    echo "<li>".__($guid, "Use this page to enter the criteria that need to be graded for each subject.")."</li>";
    echo "<li>".__($guid, "Select the year group and subject.")."</li>";
    echo "<li>".__($guid, "Add, edit and delete the criteria.")."</li>";
    echo "<li>".__($guid, "Drag criteria to the required order.")."</li>";
    echo "<li>".__($guid, "Once setup, click on copy to copy to other subjects and year groups.")."</li>";
    echo "</ul>";
    echo "</div>";
    echo "<div id='instructShow' style='display:block;float:right' class='smalltext'>";
    echo "<a href='#' onclick='instructShow()'>".__($guid, "Instructions")."</a>";
    echo "</div>";
    echo "<div style='clear:both;'></div>";
    
    admin_navbar($guid, $connection2, $title);
    $_SESSION[$guid]['sidebarExtra'] = chooseSchoolYear($connection2, '', '', $crit->schoolYearID);
    $_SESSION[$guid]['sidebarExtra'] .= chooseYearGroup($connection2, $crit->yearGroupID, $crit->schoolYearID);
    $_SESSION[$guid]['sidebarExtra'] .= $crit->choose_subject($connection2);
    
    echo "<div class = '$crit->class' id = 'status'>$crit->msg</div>";
    
    $crit->mainform();
}
