<?php
if (isActionAccessible($guid, $connection2,"/modules/Reporting/admin_startyear.php")==FALSE) {
    //Acess denied
    print "<div class='error'>" ;
            print "You do not have access to this action." ;
    print "</div>" ;
} else {
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Admin Start of Year').'</div>';
    echo '</div>';    
    // proceed
    // include function pages
    $modpath =  "./modules/".$_SESSION[$guid]["module"];

    include $modpath."/admin_function.php" ;
    include $modpath."/admin_startyear_function.php" ;
    include $modpath."/function.php";

    $startyear = new startyear();
    $startyear->startyearInit($guid, $connection2);
    $startyear->modpath = $modpath;

    $title = "Start of Year";
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
    echo "<li>At the start of each academic year it will be necessary to copy some files and settings from the previous year</li>";
    echo "<li>Once complete this page will allow you to carry out this process automatically</li>";
    echo "</ul>";
    echo "</div>";
    echo "<div id='instructShow' style='display:block;float:right' class='smalltext'>";
    echo "<a href='#' onclick='instructShow()'>Instructions</a>";
    echo "</div>";
    echo "<div style='clear:both;'></div>";


    //echo "<div class = '$sub->class' id = 'status'>$sub->msg</div>";
    admin_navbar($guid, $connection2, $title);

    echo "<div class = '$startyear->class' id = 'status'>$startyear->msg</div>";
    //$_SESSION[$guid]['sidebarExtra'] = chooseSchoolYear($connection2, '', '', $sub->schoolYearID);

    $d1 = date('d-m-Y');
    $d2 = date('2012-10-02', strtotime('+1 years'));
    
    $startyear->mainform($guid, $connection2);

}
?>
