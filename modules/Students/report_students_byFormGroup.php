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
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Forms\Prefab\BulkActionForm;
use Gibbon\Tables\Prefab\ReportTable;
use Gibbon\Services\Format;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\Students\MedicalGateway;
use Gibbon\Domain\FormGroups\FormGroupGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Students/report_students_byFormGroup.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!

    if (!empty($page->return)) {
        $page->return->addReturns([
            'success0' => __('Selected student(s) have been marked as Passed Out successfully.'),
        ]);
    }

    $gibbonFormGroupID = (isset($_GET['gibbonFormGroupID']) ? $_GET['gibbonFormGroupID'] : null);
    $view = isset($_GET['view']) ? $_GET['view'] : 'basic';
    $viewMode = isset($_REQUEST['format']) ? $_REQUEST['format'] : '';

    if (empty($viewMode)) {
        $page->breadcrumbs->add(__('Students by Form Group'));

        $form = Form::create('action', $session->get('absoluteURL').'/index.php', 'get');
        $form->setTitle(__('Choose Form Group'))
            ->setFactory(DatabaseFormFactory::create($pdo))
            ->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', "/modules/".$session->get('module')."/report_students_byFormGroup.php");

        $row = $form->addRow();
            $row->addLabel('gibbonFormGroupID', __('Form Group'));
            $row->addSelectFormGroup('gibbonFormGroupID', $session->get('gibbonSchoolYearID'), true)->selected($gibbonFormGroupID)->placeholder()->required();

        $row = $form->addRow();
            $row->addLabel('view', __('View'));
            $row->addSelect('view')->fromArray(array('basic' => __('Basic'), 'extended' =>__('Extended')))->selected($view)->required();

        $row = $form->addRow();
            $row->addFooter();
            $row->addSearchSubmit($gibbon->session);

        echo $form->getOutput();
    }

    // Cancel out early if there's no form group selected
    if (empty($gibbonFormGroupID)) return;

    $formGroupGateway = $container->get(FormGroupGateway::class);
    $studentGateway = $container->get(StudentGateway::class);
    $medicalGateway = $container->get(MedicalGateway::class);

    // QUERY
    $criteria = $studentGateway->newQueryCriteria(true)
        ->sortBy(['formGroup', 'surname', 'preferredName'])
        ->pageSize(!empty($viewMode) ? 0 : 50)
        ->filterBy('view', $view)
        ->fromArray($_POST);
    
    $students = $studentGateway->queryStudentEnrolmentByFormGroup($criteria, $gibbonFormGroupID != '*' ? $gibbonFormGroupID : null);

    // DATA TABLE
    $table = ReportTable::createPaginated('studentsByFormGroup', $criteria)->withData($students)->setViewMode($viewMode, $gibbon->session);
    $table->setTitle(__('Report Data'));
    $table->setDescription(function () use ($gibbonFormGroupID, $formGroupGateway) {
        $output = '';

        if ($gibbonFormGroupID == '*') return $output;
        
        if ($formGroup = $formGroupGateway->getFormGroupByID($gibbonFormGroupID)) {
            $output .= '<b>'.__('Form Group').'</b>: '.$formGroup['name'];
        }
        if ($tutors = $formGroupGateway->selectTutorsByFormGroup($gibbonFormGroupID)->fetchAll()) {
            $output .= '<br/><b>'.__('Tutors').'</b>: '.Format::nameList($tutors, 'Staff');
        }

        return $output;
    });

    $table->addMetaData('filterOptions', [
        'view:basic'    => __('View').': '.__('Basic'),
        'view:extended' => __('View').': '.__('Extended'),
    ]);

    if (empty($viewMode)) {
        $table->addCheckboxColumn('gibbonPersonID');
    }

    $table->addColumn('formGroup', __('Form Group'))->width('5%');
    $table->addColumn('student', __('Student'))
        ->sortable(['surname', 'preferredName'])
        ->format(function ($person) {
            return Format::name('', $person['preferredName'], $person['surname'], 'Student', true, true) . '<br/><small><i>'.Format::userStatusInfo($person).'</i></small>';
        });

    if ($criteria->hasFilter('view', 'extended')) {
        $table->addColumn('gender', __('Gender'))
                ->format(Format::using('genderName', 'gender'));
        $table->addColumn('dob', __('Age').'<br/>'.Format::small('DOB'))
            ->format(function ($values) {
                return !empty($values['dob'])
                    ? Format::age($values['dob'], true).'<br/>'.Format::small(Format::date($values['dob']))
                    : '';
            });
        $table->addColumn('citizenship', __('Nationality'));
        $table->addColumn('transport', __('Transport'));
        $table->addColumn('house', __('House'));
        $table->addColumn('lockerNumber', __('Locker'));
        $table->addColumn('longTermMedication', __('Medical'))->format(function ($values) use ($medicalGateway) {
            $output = '';

            if (!empty($values['longTermMedication'])) {
                if ($values['longTermMedication'] == 'Y') {
                    $output .= '<b><i>'.__('Long Term Medication').'</i></b>: '.$values['longTermMedicationDetails'].'<br/>';
                }

                if ($values['conditionCount'] > 0) {
                    $conditions = $medicalGateway->selectMedicalConditionsByID($values['gibbonPersonMedicalID'])->fetchAll();

                    foreach ($conditions as $index => $condition) {
                        $output .= '<b><i>'.__('Condition').' '.($index+1).'</i></b>: '.$condition['name'];
                        $output .= ' <span style="color: '.$condition['alertColor'].'; font-weight: bold">('.__($condition['risk']).' '.__('Risk').')</span>';
                        $output .= '<br/>';
                    }
                }
            } else {
                $output = '<i>'.__('No medical data').'</i>';
            }

            return $output;
        });
    }
    
    if (empty($viewMode)) {
        $form = BulkActionForm::create('bulkAction', $session->get('absoluteURL').'/modules/'.$session->get('module').'/report_students_byFormGroupProcessBulk.php');
        $form->addHiddenValue('gibbonFormGroupID', $gibbonFormGroupID);
        $form->addHiddenValue('view', $view);
        $form->addHiddenValue('action', 'Passout');

        $form->addRow()->addElement($table);

        echo $form->getOutput();
        ?>
        <style type="text/css">
        .bulkActionPanel { display: none !important; }
        </style>
        <script type="text/javascript">
        $(function() {
            function createButton() {
                return $('<button type="button" id="btnPassoutBulk" class="ml-2 py-2 px-3 rounded text-xxs border border-red-700 bg-red-600 hover:bg-red-700 text-white font-bold cursor-pointer transition duration-150 ease-in-out shadow-sm inline-flex items-center" style="display: none;" title="<?php echo __('Mark selected students as Passed Out'); ?>">' +
                    '<span style="font-size: 11px; margin-right: 4px; line-height: 1;">&#10003;</span> ' +
                    '<span><?php echo __('Passed Out'); ?></span>' +
                    '<span class="passout-count font-bold ml-1"></span>' +
                    '</button>');
            }

            function updateCount() {
                var count = $('.bulkCheckbox :checkbox:checked').length;
                var $btn = $('#btnPassoutBulk');
                if ($btn.length === 0) return;

                if (count > 0) {
                    $btn.find('.passout-count').text('(' + count + ')');
                    $btn.css({
                        'background-color': '#b91c1c',
                        'border-color': '#991b1b',
                        'box-shadow': '0 0 0 2px rgba(239, 68, 68, 0.4)'
                    });
                    $btn.stop(true, true).fadeIn(150);
                } else {
                    $btn.find('.passout-count').text('');
                    $btn.stop(true, true).fadeOut(150);
                }
            }

            function attachPassedOutButton() {
                var $clear = $('.filter.clear');
                var $existing = $('#btnPassoutBulk');

                if ($clear.length > 0) {
                    if ($existing.length > 0 && $existing.prev().is($clear)) {
                        return;
                    }
                    if ($existing.length > 0) {
                        $existing.detach();
                    }
                    $clear.after(createButton());
                } else {
                    if ($existing.length > 0) return;
                    var $targetNav = $('#studentsByFormGroup nav.cursor-default');
                    if ($targetNav.length > 0) {
                        $targetNav.first().append(createButton());
                    } else {
                        var $container = $('#studentsByFormGroup .flex.items-stretch').first();
                        if ($container.length > 0) {
                            $container.append(createButton());
                        }
                    }
                }
                updateCount();
            }

            attachPassedOutButton();

            $(document).ajaxComplete(function() {
                attachPassedOutButton();
            });

            var target = document.getElementById('studentsByFormGroup');
            if (target && window.MutationObserver) {
                var observer = new MutationObserver(function() {
                    attachPassedOutButton();
                });
                observer.observe(target, { childList: true, subtree: true });
            }

            $(document).on('click change', '.bulkCheckbox :checkbox, .checkall', function() {
                setTimeout(updateCount, 20);
            });

            if ($('#action').length > 0) {
                $('#action').val('Passout');
            }

            $(document).on('click', '#btnPassoutBulk', function(e) {
                e.preventDefault();
                var checked = $('.bulkCheckbox :checkbox:checked');
                var count = checked.length;

                if (count === 0) {
                    alert('Please select at least one student.\n(कृपया कम से कम एक छात्र को सेलेक्ट करें।)');
                    return false;
                }

                var confirmMsg = 'Are you sure you want to mark ' + count + ' selected student(s) as Passed Out?\n\n(क्या आप चुने हुए ' + count + ' छात्र(ओं) को Passed Out करना चाहते हैं?)';
                if (confirm(confirmMsg)) {
                    if ($('#action').length === 0) {
                        $('#bulkAction').append('<input type="hidden" name="action" id="action" value="Passout">');
                    } else {
                        $('#action').val('Passout');
                    }

                    var form = document.getElementById('bulkAction');
                    if (form) {
                        form.onsubmit = null;
                        form.submit();
                    }
                }
            });
        });
        </script>
        <?php
    } else {
        echo $table->render($students);
    }
}

