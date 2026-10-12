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

use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\School\SchoolYearGateway;
use Gibbon\Domain\FormGroups\FormGroupGateway;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/formGroup_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
    $gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? '';

    $page->breadcrumbs->add(__('Manage Form Groups'));

    $gibbonSchoolYearID = isset($_REQUEST['gibbonSchoolYearID'])? $_REQUEST['gibbonSchoolYearID'] : $session->get('gibbonSchoolYearID');

    // GS: Display messages from center closing process
    if ($session->has('center_close_errors')) {
        $errors = $session->get('center_close_errors');
        $session->remove('center_close_errors');
        $msg = '<div class="font-bold text-base mb-1">' . __('निम्न केंद्रों में अभी सक्रिय छात्र हैं, इसलिए इन्हें बंद नहीं किया जा सकता:') . '</div>';
        $msg .= '<div class="mb-2 text-sm text-red-900 font-semibold">' . __('कृपया पहले बच्चों को Passed Out / Left करें, तब यह केंद्र बंद होगा।') . ' <a href="' . $session->get('absoluteURL') . '/index.php?q=/modules/Students/report_students_byFormGroup.php" class="underline font-bold text-red-900 hover:text-black ml-2">' . __('Go to Students by Center to Pass Out &rarr;') . '</a></div>';
        $msg .= '<ul class="list-disc pl-5">';
        foreach ($errors as $err) {
            $msg .= '<li class="my-1 font-medium">' . $err . '</li>';
        }
        $msg .= '</ul>';
        $page->addError($msg);
    }

    if ($session->has('center_close_successes')) {
        $successes = $session->get('center_close_successes');
        $session->remove('center_close_successes');
        $msg = '<div class="font-bold text-base mb-1">' . __('सफलतापूर्वक बंद किए गए केंद्र (Successfully Closed Centers):') . '</div>';
        $msg .= '<ul class="list-disc pl-5">';
        foreach ($successes as $suc) {
            $msg .= '<li class="my-1">' . $suc . '</li>';
        }
        $msg .= '</ul>';
        $page->addSuccess($msg);
    }

    if ($session->has('center_close_notices')) {
        $notices = $session->get('center_close_notices');
        $session->remove('center_close_notices');
        $msg = '<ul class="list-disc pl-5">';
        foreach ($notices as $not) {
            $msg .= '<li class="my-1">' . $not . '</li>';
        }
        $msg .= '</ul>';
        $page->addWarning($msg);
    }

    // GS: Pre-fetch active student count for each center in this school year
    $activeCounts = [];
    $sqlActive = "SELECT se.gibbonFormGroupID, 
                         COUNT(se.gibbonStudentEnrolmentID) as totalCount,
                         COALESCE(SUM(CASE WHEN p.status != 'Left' THEN 1 ELSE 0 END), 0) as activeCount
                  FROM gibbonStudentEnrolment se
                  JOIN gibbonPerson p ON se.gibbonPersonID = p.gibbonPersonID
                  WHERE se.gibbonSchoolYearID = :gibbonSchoolYearID
                  GROUP BY se.gibbonFormGroupID";
    $resActive = $pdo->executeQuery(['gibbonSchoolYearID' => $gibbonSchoolYearID], $sqlActive);
    while ($rowA = $resActive->fetch()) {
        $activeCounts[$rowA['gibbonFormGroupID']] = intval($rowA['activeCount']);
    }

    // School Year Picker
    if (!empty($gibbonSchoolYearID)) {
        $schoolYearGateway = $container->get(SchoolYearGateway::class);
        $targetSchoolYear = $schoolYearGateway->getSchoolYearByID($gibbonSchoolYearID);

        echo '<h2>';
        echo $targetSchoolYear['name'];
        echo '</h2>';

        echo "<div class='linkTop'>";
            if ($prevSchoolYear = $schoolYearGateway->getPreviousSchoolYearByID($gibbonSchoolYearID)) {
                echo "<a href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q'].'&gibbonSchoolYearID='.$prevSchoolYear['gibbonSchoolYearID']."'>".__('Previous Year').'</a> ';
            } else {
                echo __('Previous Year').' ';
            }
			echo ' | ';
			if ($nextSchoolYear = $schoolYearGateway->getNextSchoolYearByID($gibbonSchoolYearID)) {
				echo "<a href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q'].'&gibbonSchoolYearID='.$nextSchoolYear['gibbonSchoolYearID']."'>".__('Next Year').'</a> ';
			} else {
				echo __('Next Year').' ';
			}
        echo '</div>';
    }

    $formGroupGateway = $container->get(FormGroupGateway::class);

    // QUERY
    $criteria = $formGroupGateway->newQueryCriteria(true)
        ->sortBy(['sequenceNumber', 'gibbonFormGroup.name'])
        ->fromPOST();

    $formGroups = $formGroupGateway->queryFormGroups($criteria, $gibbonSchoolYearID);

    $formatTutorsList = function($row) use ($formGroupGateway) {
        $tutors = $formGroupGateway->selectTutorsByFormGroup($row['gibbonFormGroupID'])->fetchAll();
        if (count($tutors) > 1) $tutors[0]['surname'] .= ' ('.__('Main Tutor').')';

        return Format::nameList($tutors, 'Staff', false, true);
    };

    // DATA TABLE
    $table = DataTable::createPaginated('formGroupManage', $criteria);

    // GS: Multi-select checkbox column
    $table->addCheckboxColumn('gibbonFormGroupID');

    if (!empty($nextSchoolYear)) {
        $table->addHeaderAction('copy', __('Copy All To Next Year'))
            ->setURL('/modules/School Admin/formGroup_manage_copyProcess.php')
            ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->addParam('gibbonSchoolYearIDNext', $nextSchoolYear['gibbonSchoolYearID'])
            ->setIcon('copy')
            ->onClick('return confirm("'.__('Are you sure you want to continue?').' '.__('This operation cannot be undone.').'");')
            ->displayLabel()
            ->directLink()
            ->append('&nbsp;|&nbsp;');
    }

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/School Admin/formGroup_manage_add.php')
        ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
        ->displayLabel();

    $table->addColumn('name', __('Name'))
          ->description(__('Short Name'))
          ->format(function ($formGroup) {
            return '<strong>' . $formGroup['name'] . '</strong><br/><small><i>' . $formGroup['nameShort'] . '</i></small>';
          });

    // GS: Center Status Column (Open / Closed) with Active Student count
    $table->addColumn('status', __('Status'))
        ->format(function ($formGroup) use (&$activeCounts) {
            $isClosed = stripos($formGroup['name'], 'close') !== false;
            $active = $activeCounts[$formGroup['gibbonFormGroupID']] ?? 0;

            if ($isClosed) {
                $badge = '<span class="inline-block px-2 py-0.5 text-xs font-semibold rounded bg-red-100 text-red-800 border border-red-300">' . __('Closed / बंद') . '</span>';
            } else {
                $badge = '<span class="inline-block px-2 py-0.5 text-xs font-semibold rounded bg-green-100 text-green-800 border border-green-300">' . __('Open / चालू') . '</span>';
            }

            $studentInfo = $active > 0
                ? '<br/><span class="text-xs text-amber-700 font-medium">(' . $active . ' ' . __('active') . ')</span>'
                : '<br/><span class="text-xs text-gray-400">(' . __('0 active') . ')</span>';

            return $badge . $studentInfo;
        });

    $table->addColumn('tutors', __('Form Tutors'))->sortable(false)->format($formatTutorsList);
    $table->addColumn('space', __('Location'));
    //GS//$table->addColumn('website', __('Website'))
    $table->addColumn('website', __('Timing')); //GS//
            //GS//->format(Format::using('link', ['website']));

    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonFormGroupID')
        ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
        ->format(function ($formGroup, $actions) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/School Admin/formGroup_manage_edit.php');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/School Admin/formGroup_manage_delete.php');

            // GS: Add Close action for open centers
            $isClosed = stripos($formGroup['name'], 'close') !== false;
            if (!$isClosed) {
                $actions->addAction('close', __('Close'))
                        ->setURL('/modules/School Admin/formGroup_manage_closeProcess.php')
                        ->setIcon('cross')
                        ->setTitle(__('Close Center / केंद्र बंद करें'))
                        ->onClick('return confirm("' . __('Are you sure you want to close this center? / क्या आप वाकई इस केंद्र को बंद करना चाहते हैं?') . '");');
            }
        });

    //GS//echo $table->render($formGroups);
    ?>
    <form id="formGroupBulkAction" class="bulkActionForm" method="POST" action="<?php echo $session->get('absoluteURL'); ?>/modules/School Admin/formGroup_manage_closeProcess.php">
        <input type="hidden" name="gibbonSchoolYearID" value="<?php echo htmlspecialchars($gibbonSchoolYearID); ?>">
        
        <div class="flex justify-between items-center my-3" style="min-height: 38px;">
            <div class="flex items-center">
                <button type="submit" id="btnCloseSelected" class="button button-danger" style="display: none; background-color: #dc2626; color: white; border: 1px solid #b91c1c; padding: 7px 16px; font-weight: 600; border-radius: 6px; box-shadow: 0 2px 4px rgba(220, 38, 38, 0.25); cursor: pointer; transition: all 0.2s;">
                    <svg style="width: 15px; height: 15px; display: inline-block; vertical-align: -2px; margin-right: 5px; fill: currentColor;" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                    <span><?php echo __('Close Selected Centers / चयनित केंद्र बंद करें'); ?></span>
                    <span class="close-count font-bold ml-1.5 px-2 py-0.5 rounded-full text-xs" style="background: rgba(0,0,0,0.3);"></span>
                </button>
            </div>
            <div class="text-xs text-gray-500 font-medium">
                <?php echo __('Note: Multi-select centers using checkboxes to close them. Centers with active students cannot be closed.'); ?>
            </div>
        </div>

        <?php echo $table->render($formGroups); ?>
    </form>

    <script type="text/javascript">
    $(function() {
        function updateCloseButton() {
            var count = $('.bulkCheckbox :checkbox:checked').length;
            var $btn = $('#btnCloseSelected');
            if ($btn.length === 0) return;
            if (count > 0) {
                $btn.find('.close-count').text('(' + count + ')');
                $btn.stop(true, true).fadeIn(150);
            } else {
                $btn.find('.close-count').text('');
                $btn.stop(true, true).fadeOut(150);
            }
        }

        //GS// Select all checkbox handler in table header
        $(document).on('click change', '.checkall', function(e) {
            e.stopImmediatePropagation();
            var isChecked = $(this).prop('checked');
            var $table = $(this).closest('table');
            if ($table.length === 0) {
                $table = $('#formGroupBulkAction table');
            }
            var $checkboxes = $table.find('tbody .bulkCheckbox :checkbox');

            $checkboxes.prop('checked', isChecked);
            $checkboxes.closest('tr').toggleClass('selected', isChecked);

            $table.find('.checkall').prop('checked', isChecked).prop('indeterminate', false);

            updateCloseButton();
        });

        //GS// Click on th header cell also toggles checkall for convenience
        $(document).on('click', 'th:has(.checkall)', function(e) {
            if ($(e.target).is(':checkbox, label')) {
                return;
            }
            var $checkall = $(this).find('.checkall');
            if ($checkall.length > 0) {
                var newState = !$checkall.prop('checked');
                $checkall.prop('checked', newState).trigger('change');
            }
        });

        //GS// Individual row checkbox change
        $(document).on('click change', 'tbody .bulkCheckbox :checkbox', function(e) {
            e.stopImmediatePropagation();
            var $table = $(this).closest('table');
            if ($table.length === 0) {
                $table = $('#formGroupBulkAction table');
            }
            var $boxes = $table.find('tbody .bulkCheckbox :checkbox');
            var total = $boxes.length;
            var checked = $boxes.filter(':checked').length;

            $(this).closest('tr').toggleClass('selected', $(this).prop('checked'));

            var $checkall = $table.find('.checkall');
            if (checked === 0) {
                $checkall.prop('checked', false).prop('indeterminate', false);
            } else if (checked === total) {
                $checkall.prop('checked', true).prop('indeterminate', false);
            } else {
                $checkall.prop('checked', false).prop('indeterminate', true);
            }

            updateCloseButton();
        });

        $(document).ajaxComplete(function() {
            updateCloseButton();
        });

        //GS// $(document).on('click change', '.bulkCheckbox :checkbox, .checkall', function() {
        //GS//     setTimeout(updateCloseButton, 20);
        //GS// });

        $('#btnCloseSelected').on('click', function(e) {
            var count = $('.bulkCheckbox :checkbox:checked').length;
            if (count === 0) {
                alert('<?php echo __('Please select at least one center to close. / कृपया बंद करने के लिए कम से कम एक केंद्र चुनें।'); ?>');
                return false;
            }
            var msg = '<?php echo __('Are you sure you want to close the selected centers? / क्या आप वाकई चयनित केंद्रों को बंद करना चाहते हैं?'); ?>';
            if (!confirm(msg + ' (' + count + ')')) {
                return false;
            }
        });

        updateCloseButton();
    });
    </script>
    <?php
}
