<?php
/*
 *  assign reports to year groups
 */

if (isActionAccessible($guid, $connection2,"/modules/Reporting/admin_assign.php")==FALSE) {
    //Acess denied
    print "<div class='error'>" ;
            print "You do not have access to this action." ;
    print "</div>" ;
} else {
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Admin Assign').'</div>';
    echo '</div>';    
    // proceed
    // include function pages
    $modpath =  "./modules/".$_SESSION[$guid]["module"];

    include $modpath."/admin_function.php" ;
    include $modpath."/admin_assign_function.php" ;
    include $modpath."/function.php";

    $ass = new ass();
    $ass->assInit($guid, $connection2);
    $ass->modpath = $modpath;

    $title = "Assign";
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
    echo "<ul>";
    echo "<li>".__($guid, "Use this page to assign reports to year groups")."</li>";
    echo "<li>".__($guid, "Make sure you have created the reports you need in the <em>define</em> section")."</li>";
    echo "<li>".__($guid, "Check boxes in the appropriate columns")."</li>";
    echo "<li>".__($guid, "Click on <em>save</em>")."</li>";
    echo "</ul>";
    echo "</div>";
    echo "<div id='instructShow' style='display:block;float:right' class='smalltext'>";
    echo "<a href='#' onclick='instructShow()'>".__($guid, "Instructions")."</a>";
    echo "</div>";
    echo "<div style='clear:both;'></div>";


    admin_navbar($guid, $connection2, $title);
    $_SESSION[$guid]['sidebarExtra'] = chooseSchoolYear($connection2, '', '', $ass->schoolYearID);

    echo "<div class = '$ass->class' id = 'status'>$ass->msg</div>";    
    
    $ass->mainform();
}