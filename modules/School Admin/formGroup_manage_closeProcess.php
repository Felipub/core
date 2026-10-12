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

$gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');
$URL = $session->get('absoluteURL') . '/index.php?q=/modules/School Admin/formGroup_manage.php&gibbonSchoolYearID=' . $gibbonSchoolYearID;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/formGroup_manage.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
}

// Retrieve center IDs from bulk POST array or single GET/POST
$centerIDs = $_POST['gibbonFormGroupID'] ?? [];
if (empty($centerIDs) && !empty($_GET['gibbonFormGroupID'])) {
    $centerIDs = [$_GET['gibbonFormGroupID']];
}

if (empty($centerIDs) || !is_array($centerIDs)) {
    $session->set('center_close_errors', [__('कृपया बंद करने के लिए कम से कम एक केंद्र सेलेक्ट करें। (Please select at least one center to close.)')]);
    header("Location: {$URL}");
    exit;
}

$errors = [];
$successes = [];
$notices = [];

foreach ($centerIDs as $id) {
    $id = trim($id);
    if (empty($id)) {
        continue;
    }

    // Fetch center details
    $stmt = $pdo->executeQuery(['gibbonFormGroupID' => $id], "SELECT * FROM gibbonFormGroup WHERE gibbonFormGroupID = :gibbonFormGroupID");
    $center = $stmt->fetch();

    if (!$center) {
        $errors[] = sprintf(__('Center ID %s not found.'), $id);
        continue;
    }

    $centerName = $center['name'];

    // Check if already closed
    if (stripos($centerName, 'close') !== false) {
        $notices[] = sprintf(__('केंद्र "%s" पहले से ही बंद है। (Center "%s" is already closed.)'), $centerName, $centerName);
        continue;
    }

    // Check for active students enrolled in this center
    // Active students: status != 'Left' (i.e. 'Full' or 'Expected')
    $sqlActive = "
        SELECT 
            COUNT(se.gibbonStudentEnrolmentID) as totalEnroled,
            COALESCE(SUM(CASE WHEN p.status != 'Left' THEN 1 ELSE 0 END), 0) as activeCount
        FROM gibbonStudentEnrolment se
        JOIN gibbonPerson p ON se.gibbonPersonID = p.gibbonPersonID
        WHERE se.gibbonFormGroupID = :gibbonFormGroupID
    ";
    $stmtActive = $pdo->executeQuery(['gibbonFormGroupID' => $id], $sqlActive);
    $activeInfo = $stmtActive->fetch();
    $activeCount = intval($activeInfo['activeCount'] ?? 0);

    if ($activeCount > 0) {
        // CANNOT CLOSE: Center still has active students
        $errors[] = sprintf(
            __('केंद्र "%s" में %d बच्चे अभी सक्रिय (Active) हैं। पहले बच्चों को Passed Out / Left करें, तब यह केंद्र बंद होगा। (Center "%s" has %d active student(s). Please mark students as Passed Out or Left before closing the center.)'),
            $centerName,
            $activeCount,
            $centerName,
            $activeCount
        );
    } else {
        // CAN CLOSE: 0 active students
        $newName = trim($centerName) . ' Closed';
        try {
            $pdo->executeQuery([
                'name'              => $newName,
                'attendance'        => 'N',
                'gibbonFormGroupID' => $id,
            ], "UPDATE gibbonFormGroup SET name = :name, attendance = :attendance WHERE gibbonFormGroupID = :gibbonFormGroupID");

            $successes[] = sprintf(
                __('केंद्र "%s" को सफलतापूर्वक बंद कर दिया गया। (Center "%s" was successfully closed as "%s".)'),
                $centerName,
                $centerName,
                $newName
            );
        } catch (\Exception $e) {
            $errors[] = sprintf(__('Error closing center "%s": %s'), $centerName, $e->getMessage());
        }
    }
}

// Store results in session for display
if (!empty($errors)) {
    $session->set('center_close_errors', $errors);
}
if (!empty($successes)) {
    $session->set('center_close_successes', $successes);
}
if (!empty($notices)) {
    $session->set('center_close_notices', $notices);
}

header("Location: {$URL}");
exit;
