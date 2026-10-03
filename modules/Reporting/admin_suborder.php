<?php
/*
 * create reports to be written during the year
 */

if (isActionAccessible($guid, $connection2,"/modules/Reporting/admin_suborder.php")==FALSE) {
    //Acess denied
    print "<div class='error'>" ;
            print "You do not have access to this action." ;
    print "</div>" ;
} else {
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Admin Order').'</div>';
    echo '</div>';    
    // proceed
    // include function pages
    $modpath =  "./modules/".$_SESSION[$guid]["module"];

    include $modpath."/admin_function.php" ;
    include $modpath."/admin_suborder_function.php" ;
    include $modpath."/function.php";

    $ord = new ord();
    $ord->ordInit($guid, $connection2);
    $ord->modpath = $modpath;

    $title = "Order";
    setSessionVariables($guid, $connection2);

    ///////////////////////////////////////////////////////////////////////////////////////////
    // output to screen
    ///////////////////////////////////////////////////////////////////////////////////////////
    
    // help
    echo "<div class='instruct' id='instruct' style='display:none'>";
    echo "<div style='float:left'><strong>Instructions</strong></div>";
    echo "<div style='float:right'>";
    echo "<a href='#' onclick='instructHide()'>Hide</a>";
    echo "</div>";
    echo "<div style=clear:both></div>";
    echo "<p>".__($guid, "Set the order in which subjects should appear in reports")."</p>";
    echo "<ul>";
    echo "<li>".__($guid, "Select the school year.")."</li>";
    echo "<li>".__($guid, "Drag the subjects to the required order.")."</li>";
    echo "<li>Save.</li>";
    echo "</ul>";
    echo "</div>";
    echo "<div id='instructShow' style='display:block;float:right' class='smalltext'>";
    echo "<a href='#' onclick='instructShow()'>".__($guid, "Instructions")."</a>";
    echo "</div>";
    echo "<div style='clear:both;'></div>";


    echo "<div class = '$ord->class' id = 'status'>$ord->msg</div>";
    echo "<div>&nbsp;</div>";
    admin_navbar($guid, $connection2, $title);
    $_SESSION[$guid]['sidebarExtra'] = chooseSchoolYear($connection2, '', '', $ord->schoolYearID);
    $_SESSION[$guid]['sidebarExtra'] .= chooseYearGroup($connection2, $ord->yearGroupID, $ord->schoolYearID);

    $ord->mainform();
}