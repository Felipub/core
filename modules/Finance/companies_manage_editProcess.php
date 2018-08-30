<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

include '../../gibbon.php';

include './moduleFunctions.php';

$gibbonFinanceInvoiceeCompanyID = $_GET['gibbonFinanceInvoiceeCompanyID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/companies_manage_edit.php&gibbonFinanceInvoiceeCompanyID=$gibbonFinanceInvoiceeCompanyID";

if (isActionAccessible($guid, $connection2, '/modules/Finance/companies_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if school year specified
    if ($gibbonFinanceInvoiceeCompanyID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonFinanceInvoiceeCompanyID' => $gibbonFinanceInvoiceeCompanyID);
            $sql = 'SELECT * FROM gibbonFinanceInvoiceeCompany WHERE gibbonFinanceInvoiceeCompanyID=:gibbonFinanceInvoiceeCompanyID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        if ($result->rowCount() != 1) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
        } else {
            //Proceed!
            $name = $_POST['companyName'];
            $contact = $_POST['companyContact'];
            $companyAddress = $_POST['companyAddress'];
            $phone = $_POST['companyPhone'];
            $email = $_POST['companyEmail'];

            if ($name == '' or $contact == '' or $email == '') {
                $URL .= '&return=error1';
                header("Location: {$URL}");
            } else {
                //Write to database
                try {
                    $data = array('name' => $name, 'contact' => $contact, 'companyAddress' => $companyAddress, 'phone' => $phone, 'email' => $email, 'gibbonPersonIDUpdate' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonFinanceInvoiceeCompanyID' => $gibbonFinanceInvoiceeCompanyID);

                    $sql = "UPDATE gibbonFinanceInvoiceeCompany SET companyName=:name, companyContact=:contact, companyAddress=:companyAddress, companyPhone=:phone, companyEmail=:email, gibbonPersonIDUpdate=:gibbonPersonIDUpdate, timestampUpdate='".date('Y-m-d H:i:s')."' WHERE gibbonFinanceInvoiceeCompanyID=:gibbonFinanceInvoiceeCompanyID";

                    $URL .= $sql;
                    
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                $URL .= '&return=success0';
                header("Location: {$URL}");
            }
        }
    }
}
