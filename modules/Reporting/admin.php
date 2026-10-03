<?php
/*
 * setup basic data to allow reports to be written
 */

if (isActionAccessible($guid, $connection2,"/modules/Reporting/admin.php")==FALSE) {
    //Acess denied
    print "<div class='error'>" ;
            print "You do not have access to this action." ;
    print "</div>" ;
} else {
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Admin').'</div>';
    echo '</div>';
    // proceed
    // include function pages
    $modpath =  "./modules/".$_SESSION[$guid]["module"];

    include $modpath."/admin_function.php" ;
    include $modpath."/function.php";

    $title = "Admin";
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
    echo "<p>".__($guid, "Manage the system setup from these pages")."</p>";
    echo "<ul>";
    echo "<li>".__($guid, "Define - create reports")."</li>";
    echo "<li>".__($guid, "Assign - assign reports to year groups")."</li>";
    echo "<li>".__($guid, "Access - set access to reports for different roles")."</li>";
    echo "<li>".__($guid, "Criteria - Set up criteria on which each subject will report")."</li>";
    echo "<li>".__($guid, "Design - design the template for the report for PDF output")."</li>";
    echo "<li>".__($guid, "Start of year - bring forward settings from previous school year so you do not have to design everything from scratch")."</li>";
    echo "</ul>";
    echo "</div>";
    echo "<div id='instructShow' style='display:block;float:right' class='smalltext'>";
    echo "<a href='#' onclick='instructShow()'>".__($guid, "Instructions")."</a>";
    echo "</div>";
    echo "<div style='clear:both;'></div>";

    admin_navbar($guid, $connection2, $title);
    //$_SESSION[$guid]['sidebarExtra'] = freemium($modpath);
}