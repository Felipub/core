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

use Gibbon\Services\Format;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\Students\StudentNoteGateway;

include '../../gibbon.php';

$gibbonFormGroupID = $_POST['gibbonFormGroupID'] ?? '';
$view = $_POST['view'] ?? 'basic';
$action = $_POST['action'] ?? '';
$people = isset($_POST['gibbonPersonID']) ? $_POST['gibbonPersonID'] : [];

$params = [
    'q' => '/modules/Students/report_students_byFormGroup.php',
];
if (!empty($gibbonFormGroupID)) {
    $params['gibbonFormGroupID'] = $gibbonFormGroupID;
}
if (!empty($view)) {
    $params['view'] = $view;
}

$URL = $session->get('absoluteURL') . '/index.php?' . http_build_query($params);

if (isActionAccessible($guid, $connection2, '/modules/Students/report_students_byFormGroup.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
}

if (empty($action) || empty($people) || !is_array($people)) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
}

if ($action == 'Passout') {
    $userGateway = $container->get(UserGateway::class);
    $noteGateway = $container->get(StudentNoteGateway::class);
    $categoryID = $noteGateway->getNoteCategoryIDByName('Academic') ?? null;
    $creatorID = $session->get('gibbonPersonID');
    $today = date('Y-m-d');
    $now = date('Y-m-d H:i:s');

    $partialFail = false;
    $count = 0;

    foreach ($people as $gibbonPersonID) {
        if (empty($gibbonPersonID)) {
            continue;
        }

        try {
            $data = [
                'status'          => 'Left',
                'dateEnd'         => $today,
                'departureReason' => 'Passout',
            ];

            $updated = $userGateway->update($gibbonPersonID, $data);

            if ($updated) {
                $count++;

                // Record note in student profile
                try {
                    $noteGateway->insert([
                        'title'                       => __('Student Passed Out'),
                        'note'                        => __('Student marked as Passed Out from Center.'),
                        'gibbonPersonID'              => $gibbonPersonID,
                        'gibbonPersonIDCreator'       => $creatorID,
                        'gibbonStudentNoteCategoryID' => $categoryID,
                        'timestamp'                   => $now,
                    ]);
                } catch (\Exception $e) {
                    // Note failure should not fail the overall passout
                }
            } else {
                $partialFail = true;
            }
        } catch (\Exception $e) {
            $partialFail = true;
        }
    }

    if ($count == 0 && $partialFail) {
        $URL .= '&return=error2';
    } elseif ($partialFail) {
        $URL .= '&return=warning1';
    } else {
        $URL .= '&return=success0';
    }

    header("Location: {$URL}");
    exit;
} else {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
}
