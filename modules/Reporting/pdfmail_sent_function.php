<?php

/*
 * Project:
 * Author:   Andy Statham
 * Date:
 */
// in case we need more functions

class sent {

    var $class;
    var $msg;

    var $schoolYearID;

    function sent($guid, $connection2) {
        $this->guid = $guid;
        $this->connection2 = $connection2;

        $this->schoolYearID = $_POST['schoolYearID'];
        $this->yearGroupID = $_POST['yearGroupID'];
        $this->rollGroupID = $_POST['rollGroupID'];
        $this->reportID = $_POST['reportID'];
        $this->showLeft = $_POST['showLeft'];
        $this->text = $_POST['text'];
        
    
    }
    ////////////////////////////////////////////////////////////////////////////
}