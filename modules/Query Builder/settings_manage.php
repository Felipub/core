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
use Gibbon\Domain\System\SettingGateway;

if (isActionAccessible($guid, $connection2, '/modules/Query Builder/settings_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('Manage Settings'));

    $form = Form::create('settingsManage', $session->get('absoluteURL').'/modules/'.$session->get('module').'/settings_manageProcess.php');

    $form->addHiddenValue('address', $session->get('address'));

    $row = $form->addRow()->addHeading(__m('Export Settings'));

    $fileTypes = [
        'Excel2007'    => __m('Excel 2007 and above (.xlsx)'),
        'Excel5'       => __m('Excel 95 and above (.xls)'),
        'OpenDocument' => __m('OpenDocument (.ods)'),
        'CSV'          => __m('Comma Separated (.csv)'),
    ];
    $setting = $container->get(SettingGateway::class)->getSettingByScope('Query Builder', 'exportDefaultFileType', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description($setting['description']);
        $row->addSelect($setting['name'])->fromArray($fileTypes)->selected($setting['value']);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
