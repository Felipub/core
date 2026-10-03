<?php
/*
 * create reports to be written during the year
 */

if (isActionAccessible($guid, $connection2,"/modules/Reporting/admin_define.php")==FALSE) {
    //Acess denied
    print "<div class='error'>" ;
            print "You do not have access to this action." ;
    print "</div>" ;
} else {
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Admin Create').'</div>';
    echo '</div>';    
    // proceed
    // include function pages
    $modpath =  "./modules/".$_SESSION[$guid]["module"];

    include $modpath."/admin_function.php" ;
    include $modpath."/admin_define_function.php" ;
    include $modpath."/function.php";

    $def = new def();
    $def->defInit($guid, $connection2);
    $def->modpath = $modpath;

    $title = "Create";
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
    echo "<p>".__($guid, "Use this page to create reports")."</p>";
    echo "<ul>";
    echo "<li>".__($guid, "Create a name for the report.")."</li>";
    echo "<li>".__($guid, "Set the term and whether PDF output is to be portrait or landscape.")."</li>";
    echo "<li>You can add as many reports as you like.  You may define different reports for different year groups and different times of the year.</li>";
    echo "</ul>";
    echo "</div>";
    echo "<div id='instructShow' style='display:block;float:right' class='smalltext'>";
    echo "<a href='#' onclick='instructShow()'>".__($guid, "Instructions")."</a>";
    echo "</div>";
    echo "<div style='clear:both;'></div>";


    admin_navbar($guid, $connection2, $title);
    $_SESSION[$guid]['sidebarExtra'] = chooseSchoolYear($connection2, '', '', $def->schoolYearID);

    echo "<div class = '$def->class' id = 'status'>$def->msg</div>";
    
    $def->mainform();
}