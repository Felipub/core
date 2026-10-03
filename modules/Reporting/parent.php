<?php
if (isActionAccessible($guid, $connection2,"/modules/Reporting/parent.php")==FALSE) {
    //Acess denied
    print "<div class='error'>" ;
            print "You do not have access to this action." ;
    print "</div>" ;
} else {
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Parent').'</div>';
    echo '</div>';    
    // proceed
    // include function pages
    $modpath =  "./modules/".$_SESSION[$guid]["module"];
    include $modpath."/parent_function.php" ;
    include $modpath."/function.php";

//    setSessionVariables($guid, $connection2);

    $par = new par();
    $par->parInit($guid, $connection2);
    //$par->modpath = $modpath;

    
    // return page for forms
    $thisPage = 'parent';
    $title = "Parent";

    ///////////////////////////////////////////////////////////////////////////////////////////
    // output to screen
    ///////////////////////////////////////////////////////////////////////////////////////////
    pageTitle($title);
    
    echo "<div class='instruct' id='instruct' style='display:none'>";
    echo "<div style='float:left'><strong>Instructions</strong></div>";
    echo "<div style='float:right'>";
    echo "<a href='#' onclick='instructHide()'>Hide</a>";
    echo "</div>";
    echo "<div style=clear:both></div>";
    echo "<ul>";
    echo "<li>Click on the report you wish to download</li>";
    echo "</ul>";
    echo "</div>";
    echo "<div id='instructShow' style='display:block;float:right' class='smalltext'>";
    echo "<a href='#' onclick='instructShow()'>Instructions</a>";
    echo "</div>";
    echo "<div style='clear:both;'></div>";
    
    echo "<div>&nbsp</div>";
    echo "<div>";
        $par->mainform($guid, $connection2);
    echo "</div>";
}