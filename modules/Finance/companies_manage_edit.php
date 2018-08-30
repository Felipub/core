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

use Gibbon\Forms\Form;

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Finance/companies_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/companies_manage.php'>".__($guid, 'Manage Companies')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Company').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $gibbonFinanceInvoiceeCompanyID = $_GET['gibbonFinanceInvoiceeCompanyID'];
    if ($gibbonFinanceInvoiceeCompanyID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonFinanceInvoiceeCompanyID' => $gibbonFinanceInvoiceeCompanyID);
            $sql = 'SELECT * FROM gibbonFinanceInvoiceeCompany WHERE gibbonFinanceInvoiceeCompanyID=:gibbonFinanceInvoiceeCompanyID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record does not exist.');
            echo '</div>';
        } else {
            //Let's go!
            $values = $result->fetch();

            $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/companies_manage_editProcess.php?gibbonFinanceInvoiceeCompanyID=$gibbonFinanceInvoiceeCompanyID");

            $form->setClass('smallIntBorder fullWidth');

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);

            $row = $form->addRow();
            $row->addLabel('name', __('Name'));
            $row->addTextField('companyName')->maxLength(100)->isRequired();

            $row = $form->addRow();
            $row->addLabel('contact', __('Contact Name'));
            $row->addTextField('companyContact')->maxLength(140)->isRequired();
            
            $row = $form->addRow();
            $row->addLabel('address', __('Address'));
            $row->addTextArea('companyAddress');
                
            $row = $form->addRow();
            $row->addLabel('phone', __('Phone'));
            $row->addTextField('companyPhone')->maxLength(140);

            $row = $form->addRow();
            $row->addLabel('email', __('E-mail'));
            $row->addTextField('companyEmail')->maxLength(140)->isRequired();

            $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

            $form->loadAllValuesFrom($values);

            echo $form->getOutput();
        }
    }
}
?>
