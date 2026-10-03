<?php
session_start();
include  "../../config.php";
include "../../functions.php";

//New PDO DB connection
try {
    $connection2=new PDO("mysql:host=$databaseServer;
            dbname=$databaseName;
            charset=utf8", $databaseUsername, $databasePassword);
    $connection2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // reset coding
}
catch(PDOException $e) {
    echo $e->getMessage();
}

//if (isActionAccessible($guid, $connection2, "/modules/Reporting/pdf_make.php")==FALSE) {
if (1==2) {
    //Acess denied
    print "<div class='error'>";
    print "You do not have access to this action.";
    print "</div>";
    exit;
} else {
    
    //include "./pdf_function.php" ;
    include "./function.php";
    include "./pdfmail_send_function.php";
    include "../../vendor/phpmailer/phpmailer/class.phpmailer.php";
    
    setSessionVariables($guid, $connection2);

    $setpdf = new setpdf();
    $setpdf->setpdfInit($guid, $connection2);
    
    // check folder exists
    $setpdf->checkFolder();
    $path = '../..'.$_SESSION['archivePath'].$setpdf->schoolYearName.'/';

    // go through class list to see which ones need to be printed
    //$rollGroupList = $setpdf->rollGroupList;
    for ($i=0; $i<count($setpdf->printList); $i++) {
        $setpdf->studentID = $setpdf->printList[$i]['studentID'];
        $studentDetail = $setpdf->readStudentDetail();
        $row = $studentDetail->fetch();
        $setpdf->officialName = $row['officialName'];
        $setpdf->firstName = $row['firstName'];
        $setpdf->preferredName = $row['preferredName'];
        $setpdf->surname = $row['surname'];
        $setpdf->rollOrder = $row['rollOrder'];
        $setpdf->studentAbrName = str_replace("'", "", $row['surname'].substr($row['firstName'], 0, 1));
        $dob = $row['dob'];
        if ($dob != '' && substr($dob, 0, 4) != '0000') {
            $dob = date('d/m/Y', strtotime($dob));
        } else {
            $dob = '';
        }
        $setpdf->dob = $dob;

        $reportName =
                $setpdf->schoolYearName.'-'.
                $setpdf->yearGroupName.'-'.
                $setpdf->term.'-'.
                intval($setpdf->studentID).'-'.
                $setpdf->studentAbrName.".pdf";
        
        $rs = $setpdf->readParentDetail();
        $fileName = $path.$reportName;
        $text = '';
        while ($row = $rs->fetch()) {
            $to = $row['email'];
            $from = "noreply@rapid36.com";
            $parentName = $row['title'].' '.$row['surname'];
            if ($to != '') {
                $ok = sendmail($to, $from, $parentName, $fileName);
                if ($ok) {
                    $text .= "Mail sent to ".$parentName.' - '.$setpdf->officialName.' ('.$to.')<br />';
                } else {
                    $text .= "Failed to send to ".$parentName.' - '.$setpdf->officialName.' ('.$to.')<br />';
                }
            } else {
                $text .= "No email address for ".$parentName.' - '.$setpdf->officialName.'<br />';
            }
        }
    } // end rollGroup while loop

    $returnPath = $_SESSION[$guid]["absoluteURL"]."/index.php?q=/modules/Reporting/pdfmail_sent.php";
    echo "<form name='sentForm' method='post' action='$returnPath'>";
        echo "<input type='hidden' name='yearGroupID' value='".$setpdf->yearGroupID."' />";
        echo "<input type='hidden' name='schoolYearID' value='".$setpdf->schoolYearID."' />";
        echo "<input type='hidden' name='rollGroupID' value='".$setpdf->rollGroupID."' />";
        echo "<input type='hidden' name='reportID' value='".$setpdf->reportID."' />";
        echo "<input type='hidden' name='showLeft' value='".$setpdf->showLeft."' />";
        echo "<input type='hidden' name='text' value='$text' />";
    echo "</form>";
    
    ?>
    <script>
        document.forms['sentForm'].submit();
    </script>
    <?php
}


function sendmail($to, $from, $parentName, $report) {

    $mail = new PHPMailer(); // defaults to using php "mail()"
    
    $body = "Dear ".$parentName."<br><br>";
    $body .= "Please find school report attached.<br><br>";
    $body .= "The School";

    $mail->AddReplyTo($from,"Reply to");

    $mail->SetFrom($from, $from);

    $address = $to;
    $mail->AddAddress($address);

    $mail->Subject    = "Report";

    $mail->AltBody    = "To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test

    $mail->MsgHTML($body);

    $mail->AddAttachment($report);      // attachment

    return $mail->Send();

}