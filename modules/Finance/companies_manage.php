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

if (isActionAccessible($guid, $connection2, '/modules/Finance/companies_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Companies').'</div>';
    echo '</div>';

    echo '<p>';
    echo __($guid, 'The table below shows all entities that may pay for student invoicees.');
    echo '</p>';

    echo '<h2>';
    echo __($guid, 'Filters');
    echo '</h2>';

    $search = null;
    if (isset($_GET['search'])) {
        $search = $_GET['search'];
    }
    $allCompanies = null;
    if (isset($_GET['allCompanies'])) {
        $allCompanies = $_GET['allCompanies'];
    }

    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');

    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/companies_manage.php");

    $row = $form->addRow();
        $row->addLabel('search', __('Search For'))->description(__('Name, contact, e-mail.'))->setClass('mediumWidth');
        $row->addTextField('search')->setValue($search);

    $row = $form->addRow();
        $row->addLabel('allCompanies', __('All Companies'))->description(__(''));
        $row->addCheckbox('allCompanies')->setValue('on')->checked($allCompanies);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($gibbon->session);

    echo $form->getOutput();

    echo '<h2>';
    echo __($guid, 'View');
    echo '</h2>';

    //Set pagination variable
    $page = 1;
    if (isset($_GET['page'])) {
        $page = $_GET['page'];
    }
    if ((!is_numeric($page)) or $page < 1) {
        $page = 1;
    }

    try {
        $data = array();

        $sql = 'SELECT gibbonFinanceInvoiceeCompanyID, companyName, companyContact, companyEmail, companyPhone FROM gibbonFinanceInvoiceeCompany ORDER BY companyName, companyContact';
        
        if ($search != '') {
            $data = array('search1' => "%$search%", 'search2' => "%$search%", 'search3' => "%$search%");

            $sql = 'SELECT gibbonFinanceInvoiceeCompanyID, companyName, companyContact, companyEmail, companyPhone FROM gibbonFinanceInvoiceeCompany WHERE ((companyName LIKE :search1) OR (companyContact LIKE :search1) OR (companyEmail LIKE :search1)) ORDER BY companyName, companyContact';
        }
        $sqlPage = $sql.' LIMIT '.$_SESSION[$guid]['pagination'].' OFFSET '.(($page - 1) * $_SESSION[$guid]['pagination']);
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }
        echo "<div class='linkTop'>";
        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/companies_manage_add.php'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
        echo '</div>';

    if ($result->rowCount() < 1) {
        echo "<div class='error'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {
        if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
            printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'top', "&search=$search&allCompanies=$allCompanies");
        }

        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __($guid, 'Name');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Contact');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'E-mail');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Telephone');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Action');
        echo '</th>';
        echo '</tr>';

        $count = 0;
        $rowNum = 'odd';
        try {
            $resultPage = $connection2->prepare($sqlPage);
            $resultPage->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }
        while ($row = $resultPage->fetch()) {
            if ($count % 2 == 0) {
                $rowNum = 'even';
            } else {
                $rowNum = 'odd';
            }

            //COLOR ROW BY STATUS!
            echo "<tr class=$rowNum>";
            echo '<td>';
            echo '<b>'.$row['companyName'].'</b><br/>';
            echo '</td>';
            echo '<td>';
            echo $row['companyContact'].'<br/>';
            echo '</td>';
            echo '<td>';
            echo $row['companyEmail'].'<br/>';
            echo '</td>';
            echo '<td>';
            echo $row['companyPhone'].'<br/>';
            echo '</td>';
            echo '<td>';
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/companies_manage_edit.php&gibbonFinanceInvoiceeCompanyID='.$row['gibbonFinanceInvoiceeCompanyID']."'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
            echo '</td>';
            echo '</tr>';

            ++$count;
        }
        echo '</table>';

        if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
            printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'bottom');
        }
    }
}
?>
