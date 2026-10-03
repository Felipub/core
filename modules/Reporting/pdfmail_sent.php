<?php
if (isActionAccessible($guid, $connection2,"/modules/Reporting/pdf.php")==FALSE) {
    //Acess denied
    print "<div class='error'>" ;
            print "You do not have access to this action." ;
    print "</div>" ;
} else {
    // proceed
    // include function pages
    
    $modpath =  "./modules/Reporting";
    
    include $modpath."/function.php";
    
    //setSessionVariables($guid, $connection2);

    $getstring = "";
    $getstring .= "&schoolYearID=".$_POST['schoolYearID'];
    $getstring .= "&yearGroupID=".$_POST['yearGroupID'];
    $getstring .= "&rollGroupID=".$_POST['rollGroupID'];
    $getstring .= "&reportID=".$_POST['reportID'];
    $getstring .= "&showLeft=".$_POST['showLeft'];
    $text = $_POST['text'];
    
    
    $returnPath = $_SESSION[$guid]["absoluteURL"]."/index.php?q=/modules/".$_SESSION[$guid]["module"]."/pdfmail.php".$getstring;
    
    // return page for forms
    $thisPage = 'pdfmail';
    $title = "Sent reports";

    ///////////////////////////////////////////////////////////////////////////////////////////
    // output to screen
    ///////////////////////////////////////////////////////////////////////////////////////////
    pageTitle($title);


    echo "<div>&nbsp</div>";
    echo "<div>";
        echo $text;
        echo "<p>&nbsp;</p>";
        echo "<p><a href='$returnPath'>Return to previous page</a></p>";
    echo "</div>";
}