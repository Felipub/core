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

use Gibbon\View\View;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Forms\PersonalDocumentHandler;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\System\SettingGateway;

//Module includes from User Admin (for custom fields)
include './modules/User Admin/moduleFunctions.php';

$proceed = false;
$public = false;

if (!$session->has('username')) {
    $public = true;

    //Get public access
    $publicApplications = getSettingByScope($connection2, 'Application Form', 'publicApplications');
    if ($publicApplications == 'Y') {
        $proceed = true;
    }
} else {
    if (isActionAccessible($guid, $connection2, '/modules/Students/applicationForm.php') != false) {
        $proceed = true;
    }
}

//Set gibbonPersonID of the person completing the application
$gibbonPersonID = null;
if ($session->has('gibbonPersonID')) {
    $gibbonPersonID = $session->get('gibbonPersonID');
}

if ($proceed == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Application Form'));

    if (!$session->has('username')) {
        echo "<div class='warning' style='font-weight: bold'>".sprintf(__('If you already have an account for %1$s %2$s, please log in now to prevent creation of duplicate data about you! Once logged in, you can find the form under People > Students in the main menu.'), $session->get('organisationNameShort'), $session->get('systemName')).' '.sprintf(__('If you do not have an account for %1$s %2$s, please use the form below.'), $session->get('organisationNameShort'), $session->get('systemName')).'</div>';
    } else {
        // Application Manager
        if (isActionAccessible($guid, $connection2, '/modules/Students/applicationForm_manage.php')) {
            $applicationType = (isset($_POST['applicationType']))? $_POST['applicationType'] : '';

            if ($applicationType == 'blank') {
                $public = true;
                $gibbonFamilyID = null;
                $gibbonPersonID = null;
            } else if ($applicationType == 'family') {
                $gibbonFamilyID = (isset($_POST['gibbonFamilyID']))? $_POST['gibbonFamilyID'] : '';
            } else if ($applicationType == 'person') {
                $gibbonPersonID = (isset($_POST['gibbonPersonID']))? $_POST['gibbonPersonID'] : '';
            }
        }
    }

    $returnExtra = '';
    $gibbonApplicationFormID = null;

    if (!empty($_GET['id'])) {
    	// Use the returned hash to get the actual ID from the database
    	$data = array( 'gibbonApplicationFormHash' => $_GET['id'] );
        $sql = "SELECT * FROM gibbonApplicationForm WHERE gibbonApplicationFormHash=:gibbonApplicationFormHash";
        $resultID = $pdo->executeQuery($data, $sql);

        if ($resultID && $resultID->rowCount() == 1) {
            $application = $resultID->fetch();
            $gibbonApplicationFormID = str_pad( intval($application['gibbonApplicationFormID']), 7, '0', STR_PAD_LEFT);
        } else {
        	echo "<div class='error'>";
		    echo __('The application link does not match an existing record in our system. The record may have been removed or the link is no longer valid.');
		    echo '</div>';
        }

        $returnExtra = '<br/><br/>'.__('If you need to contact the school in reference to this application, please quote the following number:').' <b><u>'.$gibbonApplicationFormID.'</b></u>.';
    }
    if ($session->get('organisationAdmissionsName') != '' and $session->get('organisationAdmissionsEmail') != '') {
        $returnExtra .= '<br/><br/>'.sprintf(__('Please contact %1$s if you have any questions, comments or complaints.'), "<a href='mailto:".$session->get('organisationAdmissionsEmail')."'>".$session->get('organisationAdmissionsName').'</a>');
    }

    $returns = array();
    $returns['success0'] = __('Your application was successfully submitted. Our admissions team will review your application and be in touch in due course.').$returnExtra;
    $returns['success1'] = __('Your application was successfully submitted and payment has been made to your credit card. Our admissions team will review your application and be in touch in due course.').$returnExtra;
    $returns['success2'] = __('Your application was successfully submitted, but payment could not be made to your credit card. Our admissions team will review your application and be in touch in due course.').$returnExtra;
    $returns['success3'] = __('Your application was successfully submitted, payment has been made to your credit card, but there has been an error recording your payment. Please print this screen and contact the school ASAP. Our admissions team will review your application and be in touch in due course.').$returnExtra;
    $returns['success4'] = __("Your application was successfully submitted, but payment could not be made as the payment gateway does not support the system's currency. Our admissions team will review your application and be in touch in due course.").$returnExtra;
    $page->return->addReturns($returns);

    // JS success return addition
    $return = (isset($_GET['return']))? $_GET['return'] : '';

    if ($return == 'success0' or $return == 'success1' or $return == 'success2'  or $return == 'success4') {
        echo "<script type='text/javascript'>";
        echo '$(document).ready(function(){';
        echo "alert('Your application was successfully submitted. Please read the information in the green box above the application form for additional information.') ;";
        echo '});';
        echo '</script>';
    }

    // Get intro
    $intro = getSettingByScope($connection2, 'Application Form', 'introduction');
    if ($intro != '') {
        echo '<p>';
        echo $intro;
        echo '</p>';
    }

    $currency = getSettingByScope($connection2, 'System', 'currency');
    $applicationFee = getSettingByScope($connection2, 'Application Form', 'applicationFee');
    $applicationProcessFee = getSettingByScope($connection2, 'Application Form', 'applicationProcessFee');
    $enablePayments = getSettingByScope($connection2, 'System', 'enablePayments');
    $paypalAPIUsername = getSettingByScope($connection2, 'System', 'paypalAPIUsername');
    $paypalAPIPassword = getSettingByScope($connection2, 'System', 'paypalAPIPassword');
    $paypalAPISignature = getSettingByScope($connection2, 'System', 'paypalAPISignature');
    $uniqueEmailAddress = getSettingByScope($connection2, 'User Admin', 'uniqueEmailAddress');

    if (!empty($applicationFee) || !empty($applicationProcessFee)) {
        echo "<div class='warning'>";
        if ($applicationFee > 0 and is_numeric($applicationFee)) {
            echo __('Please note that there is an application fee of:').' <b><u>'.$currency.$applicationFee.'</u></b>. ';
        }
        if ($applicationProcessFee > 0 and is_numeric($applicationProcessFee)) {
            echo __('A processing fee of {fee} may be sent by email after your application has been submitted.', ['fee' => '<b><u>'.$currency.$applicationProcessFee.'</u></b>']);
        }
        if ($enablePayments == 'Y' and $paypalAPIUsername != '' and $paypalAPIPassword != '' and $paypalAPISignature != '' && !empty($applicationFee)) {
            echo ' '.__('Payment must be made by credit card, using our secure PayPal payment gateway. When you press Submit at the end of this form, you will be directed to PayPal in order to make payment. During this process we do not see or store your credit card details.');
        }
        echo '</div>';
    }

    $siblingApplicationMode = !empty($gibbonApplicationFormID);

    $customFieldHandler = $container->get(CustomFieldHandler::class);
    $personalDocumentHandler = $container->get(PersonalDocumentHandler::class);

    $form = Form::create('applicationForm', $session->get('absoluteURL').'/modules/'.$session->get('module').'/applicationFormProcess.php');
    $form->setAutocomplete('on');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));

    // SIBLING APPLICATIONS
    if ($siblingApplicationMode == true) {
        $gibbonFamilyID = (!empty($application['gibbonFamilyID']))? $application['gibbonFamilyID'] : null;
        $gibbonPersonID = null;

        $form->addHiddenValue('linkedApplicationFormID', $gibbonApplicationFormID);

        $row = $form->addRow()->setClass('break');
            $heading = $row->addSubheading(__('Add Another Application'));
            $heading->append(__('You may continue submitting applications for siblings with the form below and they will be linked to your family data.'));
            $heading->append(__('Some information has been pre-filled for you, feel free to change this information as needed.'));

        $data = array( 'gibbonApplicationFormID' => $gibbonApplicationFormID );
        $sql = 'SELECT DISTINCT gibbonApplicationFormID, preferredName, surname, officialName, dob FROM gibbonApplicationForm
                LEFT JOIN gibbonApplicationFormLink ON (gibbonApplicationForm.gibbonApplicationFormID=gibbonApplicationFormLink.gibbonApplicationFormID1 OR gibbonApplicationForm.gibbonApplicationFormID=gibbonApplicationFormLink.gibbonApplicationFormID2)
                WHERE (gibbonApplicationFormID=:gibbonApplicationFormID AND gibbonApplicationFormLinkID IS NULL)
                OR gibbonApplicationFormID1=:gibbonApplicationFormID
                OR gibbonApplicationFormID2=:gibbonApplicationFormID
                ORDER BY gibbonApplicationFormID';
        $resultLinked = $pdo->executeQuery($data, $sql);

        $linkedApplicationText = '';
        if ($resultLinked && $resultLinked->rowCount() > 0) {
            $linkedApplicationText .= '<ul style="width:302px;display:inline-block">';
            $linkedApplications = $resultLinked->fetchAll();

            foreach ($linkedApplications as $rowLinked) {
                $linkedApplicationText .= '<li>'. Format::name('', $rowLinked['preferredName'], $rowLinked['surname'], 'Student', true);
                $linkedApplicationText .= ' ('.str_pad( intval($rowLinked['gibbonApplicationFormID']), 7, '0', STR_PAD_LEFT).')</li>';
            }
            $linkedApplicationText .= '</ul>';
        }

        $row = $form->addRow();
            $row->addLabel('', __('Current Applications'));
            $row->addContent($linkedApplicationText);
    }

    // Cache all User custom fields for quick ordered access
    $allCustomFields = [];
    $cfResult = $pdo->executeQuery([], "SELECT * FROM gibbonCustomField WHERE context = 'User'");
    while ($cfRow = $cfResult->fetch()) {
        $allCustomFields[$cfRow['gibbonCustomFieldID']] = $cfRow;
    }
    $studentExistingFields = !empty($application['fields']) ? json_decode($application['fields'], true) : [];

    // Helper to render custom field in exact order
    $addCustomFieldRow = function(&$form, $id, $label = null, $description = null, $required = null, $cssClass = '') use (&$allCustomFields, &$studentExistingFields) {
        if (empty($allCustomFields[$id])) return null;
        $field = $allCustomFields[$id];
        if ($required !== null) {
            $field['required'] = $required ? 'Y' : 'N';
        }
        $name = 'custom' . $id;
        $fieldLabel = $label ?? __($field['name']);
        $fieldDesc = $description ?? __($field['description']);
        $fieldValue = $studentExistingFields[$id] ?? '';
        if (!empty($fieldValue) && $field['type'] == 'date') {
            $fieldValue = Format::date($fieldValue);
        } elseif (!empty($fieldValue) && $field['type'] == 'checkboxes') {
            $fieldValue = is_array($fieldValue) ? $fieldValue : explode(',', $fieldValue);
        }

        $row = $form->addRow();
        if (!empty($cssClass)) {
            $row->setClass($cssClass);
        }
        $lbl = $row->addLabel($name, $fieldLabel);
        if (!empty($fieldDesc)) {
            $lbl->description($fieldDesc);
        }
        $row->addCustomField($name, $field)->setValue($fieldValue);
        return $row;
    };

    // STUDENT PHOTO UPLOAD (Top Right Corner)
    $studentPhotoURL = !empty($application['image_240']) && file_exists($session->get('absolutePath').'/'.$application['image_240'])
        ? $session->get('absoluteURL').'/'.$application['image_240']
        : '';

    $photoUploadHtml = '
    <div style="display: flex; justify-content: flex-end; width: 100%; margin: 0; padding: 4px 0;">
        <div style="display: flex; flex-direction: column; align-items: center; background: #ffffff; border: 2px dashed #93c5fd; border-radius: 8px; padding: 10px; width: 145px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.06);">
            <div style="font-size: 11px; font-weight: 600; color: #1e40af; margin-bottom: 6px;">
                ' . __('Student Photo') . '<br/><span style="font-size: 10px; color: #64748b;">' . __('छात्र की फोटो') . '</span>
            </div>
            <div id="student_photo_preview_container" onclick="document.getElementById(\'student_image_upload\').click();" title="' . __('Click to choose photo / फोटो चुनने के लिए क्लिक करें') . '" style="width: 115px; height: 135px; border-radius: 6px; overflow: hidden; background: #f8fafc; display: flex; align-items: center; justify-content: center; margin-bottom: 8px; border: 1px solid #cbd5e1; position: relative; cursor: pointer;">
                <img id="student_photo_preview" src="' . htmlspecialchars($studentPhotoURL) . '" alt="Student Photo" style="width: 100%; height: 100%; object-fit: cover; ' . (!empty($studentPhotoURL) ? '' : 'display: none;') . '" />
                <div id="student_photo_placeholder" style="color: #64748b; font-size: 11px; text-align: center; padding: 4px; ' . (!empty($studentPhotoURL) ? 'display: none;' : '') . '">
                    <svg style="width: 42px; height: 42px; margin: 0 auto 4px; display: block; fill: #94a3b8;" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                    <span>' . __('Click to upload') . '<br/>' . __('फोटो अपलोड करें') . '</span>
                </div>
            </div>
            <label for="student_image_upload" style="display: inline-block; cursor: pointer; background: #2563eb; color: #ffffff; font-size: 11px; font-weight: 600; padding: 5px 8px; border-radius: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); width: 100%; text-align: center; box-sizing: border-box; margin: 0;">
                ' . __('Upload / फोटो चुनें') . '
            </label>
            <input type="file" id="student_image_upload" name="image_240" accept="image/*" style="display: none;" onchange="previewStudentPhoto(this);" />
            <div style="font-size: 10px; color: #94a3b8; margin-top: 3px;">JPG, JPEG, PNG</div>
        </div>
    </div>
    <script type="text/javascript">
    function previewStudentPhoto(input) {
        if (input.files && input.files[0]) {
            var file = input.files[0];
            if (!file.type.match("image.*")) {
                alert("Please select an image file (JPG, PNG, GIF).");
                input.value = "";
                return;
            }
            var reader = new FileReader();
            reader.onload = function(e) {
                var preview = document.getElementById("student_photo_preview");
                var placeholder = document.getElementById("student_photo_placeholder");
                if (preview) {
                    preview.src = e.target.result;
                    preview.style.display = "block";
                }
                if (placeholder) {
                    placeholder.style.display = "none";
                }
            };
            reader.readAsDataURL(file);
        }
    }
    </script>';

    $row = $form->addRow();
    $row->addContent($photoUploadHtml);

        // STUDENT PERSONAL DATA
    $form->addRow()->addHeading(__('Student - विद्यार्थी'));

    // 1. Gyansetu Center name - ज्ञानसेतु केंद्र का नाम * (at the beginning)
    $sqlSelect = "SELECT gibbonFormGroupID as value, name, gibbonSchoolYearID FROM gibbonFormGroup ORDER BY gibbonSchoolYearID, name";
    $resultSelect = $pdo->executeQuery(array(), $sqlSelect);

    $rollGroups = ($resultSelect->rowCount() > 0)? $resultSelect->fetchAll() : array();
    $rollGroupsChained = array_combine(array_column($rollGroups, 'value'), array_column($rollGroups, 'gibbonSchoolYearID'));
    $rollGroupsOptions = array_combine(array_column($rollGroups, 'value'), array_column($rollGroups, 'name'));

    $row = $form->addRow();
        $row->addLabel('gibbonFormGroupID', __('Gyansetu Center name - ज्ञानसेतु केंद्र का नाम'));
        $row->addSelect('gibbonFormGroupID')
            ->fromArray($rollGroupsOptions)
            ->chainedTo('gibbonSchoolYearID', $rollGroupsChained)
            ->required()
            ->placeholder();

    // 2. Block :
    $addCustomFieldRow($form, '0028', __('Block :'), '');

    // 3. District : Lucknow/ Ayodhya / Barabanki / Sitapur / Other
    $addCustomFieldRow($form, '0029', __('District :'), '');


    //GS//$form->addRow()->addSubheading(__('Student Personal Data - विद्यार्थी का विवरण'));
    $form->addRow()->addSubheading(__('Child’s  Personal Data - विद्यार्थी का विवरण'));
    $row = $form->addRow();
        $row->addLabel('firstName', __('Name - नाम'))->description(__('First name as shown in ID documents. - पहला नाम जैसा कि आईडी दस्तावेजों में दिखाया गया है'));
        $row->addTextField('firstName')->required()->maxLength(60);

    $row = $form->addRow();
        $row->addLabel('surname', __('Surname - उपनाम'))->description(__('Surname used by family as per government documents. - परिवार द्वारा प्रयोग किया जा रहा उपनाम सरकारी दस्तावेज़ के अनुसार'));
        //GS//$row->addTextField('surname')->required()->maxLength(60);
        $row->addTextField('surname')->maxLength(60); //GS//

    //GS//$row = $form->addRow();
    //GS//    $row->addLabel('preferredName', __('Preferred Name'))->description(__('Most common name, alias, nickname, etc.'));
    //GS//    $row->addTextField('preferredName')->required()->maxLength(60);
    $form->addHiddenValue('preferredName', isset($application['preferredName'])? $application['preferredName'] : ''); //GS//

    //GS//$row = $form->addRow();
    //GS//    $row->addLabel('officialName', __('Official Name'))->description(__('Full name as shown in ID documents.'));
    //GS//    $row->addTextField('officialName')->required()->maxLength(150)->setTitle(__('Please enter full name as shown in ID documents'));
    $form->addHiddenValue('officialName', isset($application['officialName'])? $application['officialName'] : ''); //GS//

    //GS//$row = $form->addRow();
    //GS//    $row->addLabel('nameInCharacters', __('Name In Characters'))->description(__('Chinese or other character-based name.'));
    //GS//    $row->addTextField('nameInCharacters')->maxLength(60);
    $form->addHiddenValue('nameInCharacters', isset($application['nameInCharacters'])? $application['nameInCharacters'] : ''); //GS//

    $row = $form->addRow();
        $row->addLabel('gender', __('Gender - लिंग'));
        $row->addSelectGender('gender')->required();

    $row = $form->addRow();
        $row->addLabel('dob', __('Date of Birth - जन्म तिथि'))->description($session->get('i18n')['dateFormat'])->prepend(__('Format - प्रारूप:'));
        $row->addDate('dob')->required();

    $religions = getSettingByScope($connection2, 'User Admin', 'religions'); //GS//
    $row = $form->addRow(); //GS//
        $row->addLabel('religion', __('Religion - धर्म')); //GS//
        if (!empty($religions)) { //GS//
            $row->addSelect('religion')->fromString($religions)->placeholder()->required(); //GS//
        } else { //GS//
            $row->addTextField('religion')->maxLength(30); //GS//
        } //GS//


    // 9. Caste - जाति * - Caste of the person - व्यक्ति की जाति (move this field here)
    $addCustomFieldRow($form, '0001', __('Caste - जाति'), __('Caste of the person - व्यक्ति की जाति'), true);
    // STUDENT BACKGROUND
    //GS//$form->addRow()->addSubheading(__('Student Background - छात्र पृष्ठभूमि'));

    //GS//$row = $form->addRow();
    //GS//    $row->addLabel('languageHomePrimary', __('Home Language - Primary'))->description(__('The primary language used in the student\'s home.'));
    //GS//    $row->addSelectLanguage('languageHomePrimary')->required();
    $form->addHiddenValue('languageHomePrimary', isset($application['languageHomePrimary'])? $application['languageHomePrimary'] : ''); //GS//

    //GS//$row = $form->addRow();
    //GS//    $row->addLabel('languageHomeSecondary', __('Home Language - Secondary'));
    //GS//    $row->addSelectLanguage('languageHomeSecondary')->placeholder('');
    $form->addHiddenValue('languageHomeSecondary', isset($application['languageHomeSecondary'])? $application['languageHomeSecondary'] : ''); //GS//

    //GS//$row = $form->addRow();
    //GS//    $row->addLabel('languageFirst', __('First Language'))->description(__('Student\'s native/first/mother language.'));
    //GS//    $row->addSelectLanguage('languageFirst')->required();
    $form->addHiddenValue('languageFirst', isset($application['languageFirst'])? $application['languageFirst'] : ''); //GS//

    //GS//$row = $form->addRow();
    //GS//    $row->addLabel('languageSecond', __('Second Language'));
    //GS//    $row->addSelectLanguage('languageSecond')->placeholder('');
    $form->addHiddenValue('languageSecond', isset($application['languageSecond'])? $application['languageSecond'] : ''); //GS//

    //GS//$row = $form->addRow();
    //GS//    $row->addLabel('languageThird', __('Third Language'));
    //GS//    $row->addSelectLanguage('languageThird')->placeholder('');
    $form->addHiddenValue('languageThird', isset($application['languageThird'])? $application['languageThird'] : ''); //GS//

    //GS//$row = $form->addRow();
    //GS//    $row->addLabel('countryOfBirth', __('Country of Birth'));
    //GS//    $row->addSelectCountry('countryOfBirth')->required();
    $form->addHiddenValue('countryOfBirth', isset($application['countryOfBirth'])? $application['countryOfBirth'] : ''); //GS//

    $countryName = ($session->has('country')) ? __($session->get('country')).' ' : '';
    $nationalityList = getSettingByScope($connection2, 'User Admin', 'nationality');
    $residencyStatusList = getSettingByScope($connection2, 'User Admin', 'residencyStatus');

    // 10. Personal Documents-
    $form->addRow()->addSubheading(__('Personal Documents-'));

    // 11. Does the child has Adhar Card : Y / N / Na
    $addCustomFieldRow($form, '0030', __('Does the child has Adhar Card'), __('Y / N / Na'));

    // 12. India Aadhar Card Aadhar Card Number
    // PERSONAL DOCUMENTS
    $params = ['student' => true, 'applicationForm' => true];
    $personalDocumentHandler->addPersonalDocumentsToForm($form, null, null, $params);

    // STUDENT CONTACT
    //GS//$form->addRow()->addSubheading(__('Student Contact'));

    //GS//$row = $form->addRow();
    //GS//    $row->addLabel('email', __('Email'));
    //GS//    $email = $row->addEmail('email');
    //GS//    if ($uniqueEmailAddress == 'Y') {
    //GS//        $email->uniqueField('./publicRegistrationCheck.php');
    //GS//    }
    $form->addHiddenValue('email', isset($application['email'])? $application['email'] : ''); //GS//

    for ($i = 1; $i < 3; ++$i) {
        $row = $form->addRow();
            //GS//$row->addLabel('', __('Phone').' '.$i)->description(__('Type, country code, number.'));
            //GS//$row->addPhoneNumber('phone'.$i);
            $form->addHiddenValue('phone'.$i, isset($application['phone'.$i])? $application['phone'.$i] : ''); //GS//
    }

    // SPECIAL EDUCATION & MEDICAL
    //GS//$senOptionsActive = getSettingByScope($connection2, 'Application Form', 'senOptionsActive');
    //GS//    //GS//if ($senOptionsActive == 'Y') {
    //GS//$heading = $form->addRow()->addSubheading(__('Special Educational Needs & Medical'));
    //GS//    //GS//$applicationFormSENText = getSettingByScope($connection2, 'Students', 'applicationFormSENText');
    //GS//if (!empty($applicationFormSENText)) {
    //GS//$heading->append($applicationFormSENText);
    //GS//}
    //GS//    //GS//$row = $form->addRow();
    //GS//$row->addLabel('sen', __('Special Educational Needs (SEN)'))->description(__('Are there any known or suspected SEN concerns, or previous SEN assessments?'));
    //GS//$row->addYesNo('sen')->required()->placeholder(__('Please select...'));
    //GS//    //GS//$form->toggleVisibilityByClass('senDetailsRow')->onSelect('sen')->when('Y');
    //GS//    //GS//$row = $form->addRow()->setClass('senDetailsRow');
    //GS//$column = $row->addColumn();
    //GS//$column->addLabel('senDetails', __('SEN Details'))->description(__('Provide any comments or information concerning your child\'s development and SEN history.'));
    //GS//$column->addTextArea('senDetails')->setRows(5)->required()->setClass('fullWidth');
    //GS//    //GS//} else {
        $form->addHiddenValue('sen', 'N');
    //GS//}

    //GS//$row = $form->addRow();
    //GS//    $row->addLabel('medical', __('Medical Conditions - चिकित्सीय स्थितियां'))->description(__('Does your child have any medical conditions or concerns? - क्या बच्चे को कोई चिकित्सीय स्थिति या चिंता है?'));
    //GS//    $row->addYesNo('medical')->required()->placeholder(__('Please select...'));
    $form->addHiddenValue('medical', ''); //GS//

    $form->addHiddenValue('medical', ''); //GS//
    $form->addHiddenValue('medicalInformation', ''); //GS//
    //GS//$form->toggleVisibilityByClass('medicalDetailsRow')->onSelect('medical')->when('Y');

    //GS//$col = $form->addRow()->setClass('medicalDetailsRow')->addColumn();
    //GS//$col->addLabel('medicalInformation', __('Medical Information'))->description(__('Please indicate any medical conditions.'));
    //GS//$col->addTextArea('medicalInformation')->setRows(5)->required()->setClass('fullWidth');

    // STUDENT EDUCATION
    //GS//$heading = $form->addRow()->addSubheading(__('Student Education'));

    //GS////GS//$row = $form->addRow();
    //GS//    $row->addLabel('gibbonSchoolYearIDEntry', __('Anticipated Year of Entry'))->description(__('What school year will the student join in?'));

    //GS//    $enableLimitedYearsOfEntry = getSettingByScope($connection2, 'Application Form', 'enableLimitedYearsOfEntry');
    //GS//    $availableYearsOfEntry = getSettingByScope($connection2, 'Application Form', 'availableYearsOfEntry');
    //GS//    if ($enableLimitedYearsOfEntry == 'Y' && !empty($availableYearsOfEntry)) {
    //GS//        $data = array('gibbonSchoolYearIDList' => $availableYearsOfEntry);
    //GS//        $sql = "SELECT gibbonSchoolYearID as value, name FROM gibbonSchoolYear WHERE FIND_IN_SET(gibbonSchoolYearID, :gibbonSchoolYearIDList) ORDER BY sequenceNumber";
    //GS//    } else {
    //GS//        $data = array();
    //GS//        $sql = "SELECT gibbonSchoolYearID as value, name FROM gibbonSchoolYear WHERE (status='Current' OR status='Upcoming') ORDER BY sequenceNumber";
    //GS//    }
    //GS//    $row->addSelect('gibbonSchoolYearIDEntry')->fromQuery($pdo, $sql, $data)->required()->placeholder(__('Please select...'));


    $sqlSelect = "SELECT gibbonSchoolYearID as value, name FROM gibbonSchoolYear WHERE status='Current' ORDER BY sequenceNumber"; //GS//
    $resultSelect = $pdo->executeQuery(array(), $sqlSelect); //GS//
    $currentSchoolYear = ($resultSelect->rowCount() > 0)? $resultSelect->fetchAll() : array(); //GS//

    //GS//$form->addHiddenValue('gibbonSchoolYearIDEntry', $currentSchoolYear[0]["value"]); //GS//
    $form->addHiddenValue('gibbonSchoolYearIDEntry', !empty($currentSchoolYear) ? $currentSchoolYear[0]["value"] : ''); //GS//
    $form->addHiddenValue('gibbonYearGroupIDEntry', '001'); //GS//


    //GS//$row = $form->addRow();
    //GS//$row->addLabel('dateStart', __('Start Date - प्रवेश तिथि'))->description(__('Student\'s intended first day at school. - छात्र के शामिल होने की तिथि'))->append('<br/>'.__('Format - प्रारूप:'))->append(' '.$session->get('i18n')['dateFormat']);
    //GS//$row->addDate('dateStart')->required();

    //GS//$row = $form->addRow();
    //GS//$row->addLabel('gibbonYearGroupIDEntry', __('Year Group at Entry - प्रवेश स्तर'))->description('Which year level will student enter. - छात्र किस वर्ष के स्तर पर प्रवेश करेगा');
    //GS//$sql = "SELECT gibbonYearGroupID as value, name FROM gibbonYearGroup ORDER BY sequenceNumber";
    //GS//$row->addSelect('gibbonYearGroupIDEntry')->fromQuery($pdo, $sql)->required()->placeholder(__('Please select...'));

    // ROLL GROUP //GS//
    //GS//$sqlSelect = "SELECT gibbonFormGroupID as value, name, gibbonSchoolYearID FROM gibbonFormGroup ORDER BY gibbonSchoolYearID, name"; //GS//
    //GS//$resultSelect = $pdo->executeQuery(array(), $sqlSelect); //GS//

    //GS//$rollGroups = ($resultSelect->rowCount() > 0)? $resultSelect->fetchAll() : array(); //GS//
    //GS//$rollGroupsChained = array_combine(array_column($rollGroups, 'value'), array_column($rollGroups, 'gibbonSchoolYearID')); //GS//
    //GS//$rollGroupsOptions = array_combine(array_column($rollGroups, 'value'), array_column($rollGroups, 'name')); //GS//

    //GS//$row = $form->addRow(); //GS//
    //GS//$row->addLabel('gibbonFormGroupID', __('Gyansetu Roll Group name - ज्ञानसेतु केंद्र का नाम')); //GS//
    //GS//$row->addSelect('gibbonFormGroupID') //GS//
    //GS//->fromArray($rollGroupsOptions) //GS//
    //GS//->chainedTo('gibbonSchoolYearID', $rollGroupsChained) //GS//
    //GS//->required() //GS//
    //GS//->placeholder(); //GS//

    // DAY TYPE
    //GS//$dayTypeOptions = getSettingByScope($connection2, 'User Admin', 'dayTypeOptions');
    //GS//if (!empty($dayTypeOptions)) {
    //GS//$row = $form->addRow();
    //GS//$row->addLabel('dayType', __('Day Type'))->description(getSettingByScope($connection2, 'User Admin', 'dayTypeText'));
    //GS//$row->addSelect('dayType')->fromString($dayTypeOptions);
    //GS//}
    $form->addHiddenValue('dayType', 'Full Day'); //GS//

    // REFEREE EMAIL
    //GS//$applicationFormRefereeLink = getSettingByScope($connection2, 'Students', 'applicationFormRefereeLink');
    //GS//if (!empty($applicationFormRefereeLink)) {
    //GS//$applicationFormRefereeRequired = getSettingByScope($connection2, 'Students', 'applicationFormRefereeRequired', true);
    //GS//$row = $form->addRow();
    //GS//$row->addLabel('referenceEmail', __('Current School Reference Email'))->description(__('An email address for a referee at the applicant\'s current school.'));
    //GS//if ($applicationFormRefereeRequired["value"] == "Y") {
    //GS//$row->addEmail('referenceEmail')->required();
    //GS//}
    //GS//else {
    //GS//$row->addEmail('referenceEmail');
    //GS//}
    //GS//}
    //GS//    //GS////GS//$row = $form->addRow();
    //GS////GS//    $row->addLabel('', __('Previous Schools'))->description(__('Please give information on the last two schools attended by the applicant.'));
    //GS//    //GS//// PREVIOUS SCHOOLS TABLE
    //GS////GS//$table = $form->addRow()->addTable()->addClass('colorOddEven');
    //GS//    //GS////GS//$header = $table->addHeaderRow();
    //GS////GS//$header->addContent(__('School Name'));
    //GS////GS//$header->addContent(__('Address'));
    //GS////GS//$header->addContent(sprintf(__('Grades%1$sAttended'), '<br/>'));
    //GS////GS//$header->addContent(sprintf(__('Language of%1$sInstruction'), '<br/>'));
    //GS////GS//$header->addContent(__('Joining Date'))->append('<br/><small>'.$session->get('i18n')['dateFormat'].'</small>');
    //GS//    //GS////GS//// Grab some languages, for auto-complete
    //GS////GS//$results = $pdo->executeQuery(array(), "SELECT name FROM gibbonLanguage ORDER BY name");
    //GS////GS//$languages = ($results && $results->rowCount() > 0)? $results->fetchAll(PDO::FETCH_COLUMN) : array();
    //GS//    //GS////GS//for ($i = 1; $i < 3; ++$i) {
    //GS////GS//    $row = $table->addRow();
    //GS////GS//    $row->addTextField('schoolName'.$i)->maxLength(50)->setSize(18);
    //GS////GS//    $row->addTextField('schoolAddress'.$i)->maxLength(255)->setSize(20);
    //GS////GS//    $row->addTextField('schoolGrades'.$i)->maxLength(20)->setSize(8);
    //GS////GS//    $row->addTextField('schoolLanguage'.$i)->autocomplete($languages)->setSize(10);
    //GS////GS//    $row->addDate('schoolDate'.$i)->setSize(10);
    //GS////GS//}
    //GS//    //GS//for ($i = 1; $i < 3; ++$i) {//GS//
    //GS//$form->addHiddenValue('schoolName'.$i, ''); //GS//
    //GS//$form->addHiddenValue('schoolAddress'.$i, '');//GS//
    //GS//$form->addHiddenValue('schoolGrades'.$i, '');//GS//
    //GS//}//GS//
    //GS//    //GS//// CUSTOM FIELDS FOR STUDENT
    //GS//$params = ['student' => 1, 'applicationForm' => 1, 'headingLevel' => 'h4'];
    //GS//$customFieldHandler->addCustomFieldsToForm($form, 'User', $params);
    //GS//
    // 13. Start Date - प्रवेश तिथि *
    // Child's date of joining. - छात्र के शामिल होने की तिथि- Format - प्रारूप: dd/mm/yyyy
    $row = $form->addRow();
        $row->addLabel('dateStart', __('Start Date - प्रवेश तिथि'))->description(__('Child\'s date of joining. - छात्र के शामिल होने की तिथि'))->append('<br/>'.__('Format - प्रारूप:'))->append(' '.$session->get('i18n')['dateFormat']);
        $row->addDate('dateStart')->required();

    // 14. Level at Entry - प्रवेश स्तर * Which entry level will Child enter. - छात्र किस वर्ष के स्तर पर प्रवेश करेगा
    // Please select...ECD / Beginner / Advanced
    $addCustomFieldRow($form, '0033', __('Level at Entry - प्रवेश स्तर'), __('Which entry level will Child enter. - छात्र किस वर्ष के स्तर पर प्रवेश करेगा'), true);

    // 15. Commitment fee status* : dropdown(Paid/ Free seat)
    $addCustomFieldRow($form, '0031', __('Commitment fee status'), '', true);

    // 16. If free seat (give reasons) :__________________________
    $addCustomFieldRow($form, '0032', __('If free seat (give reasons) :'), '', false, 'freeSeatReason');
    $form->toggleVisibilityByClass('freeSeatReason')->onSelect('custom0031')->when('Free seat');

    // 17. (change sequencing) Family members number - परिवार के सदस्यों की संख्य *- Number of people living at home. - घर में रहने वाले लोगों की संख्या।
    $addCustomFieldRow($form, '0003', __('Family members number - परिवार के सदस्यों की संख्य'), __('Number of people living at home. - घर में रहने वाले लोगों की संख्या।'), true);

    // 18. (change sequencing) Family income - पारिवारिक आय *-Total money that comes in the family monthly in rupees. - मासिक कुल आय (रूपये में)।
    $addCustomFieldRow($form, '0004', __('Family income - पारिवारिक आय'), __('Total money that comes in the family monthly in rupees. - मासिक कुल आय (रूपये में)।'), true);

    // 19. (change sequencing) Does the Child contribute towards family income?- क्या बच्चा पारिवारिक आय (कमाई) के लिए कही कोई कार्य करता है- हाँ या नही | - Yes/ No
    $addCustomFieldRow($form, '0027', __('Does the Child contribute towards family income?'), __('क्या बच्चा पारिवारिक आय (कमाई) के लिए कही कोई कार्य करता है- हाँ या नही |'), false);

    // 20. Child’s occupation / job - विद्यार्थी का व्यवसाय- If the Child is working, what is the job. - विद्यार्थी का व्यवसाय / नौकरी
    $addCustomFieldRow($form, '0005', __('Child’s occupation / job - विद्यार्थी का व्यवसाय'), __('If the Child is working, what is the job. - विद्यार्थी का व्यवसाय / नौकरी'), false);

    // 21. Child’s job start time - नौकरी शुरू होने का समय- Time when the Child starts the job - काम शुरू होने का समय
    $addCustomFieldRow($form, '0007', __('Child’s  job start time - नौकरी शुरू होने का समय'), __('Time when the Child starts the job - काम शुरू होने का समय'), false);

    // 22. Time at which the Child’s work gets over- बच्चे का काम से वापस आने का समय
    $addCustomFieldRow($form, '0008', __('Time at which the Child’s work gets over'), __('बच्चे का काम से वापस आने का समय'), false);

    // 23. Monthly salary - मासिक आय *- Monthly salary in rupees. - रुपये में मासिक आय
    $addCustomFieldRow($form, '0006', __('Monthly salary - मासिक आय'), __('Monthly salary in rupees. - रुपये में मासिक आय'), true);

    // 24. Is the Child in another school? *- क्या छात्र वर्तमान में दूसरे स्कूल में नामांकित है
    // Child’s schooling status : not enrolled / Going to school / highly irregular / dropped out
    $addCustomFieldRow($form, '0011', __('Is the Child in another school? - क्या छात्र वर्तमान में दूसरे स्कूल में नामांकित है'), __('not enrolled / Going to school / highly irregular / dropped out'), true);

    // 25. Name of school - विद्यालय का नाम- In case the Child is going to a school, name of it. - यदि विद्यार्थी किसी विद्यालय में जा रहा है तो उसका नाम बताएं।
    $addCustomFieldRow($form, '0012', __('Name of school - विद्यालय का नाम'), __('In case the Child is going to a school, name of it. - यदि विद्यार्थी किसी विद्यालय में जा रहा है तो उसका नाम बताएं।'), false);

    // 26. Class in school - कक्षा- What class is he Child enrolled in? - वह किस कक्षा का छात्र है?
    $addCustomFieldRow($form, '0014', __('Class in school - कक्षा'), __('What class is he Child enrolled in? - वह किस कक्षा का छात्र है?'), false);

    // 27. Type of current school - वर्तमान स्कूल का प्रकार- If the Child is enrolled to another school, the type of it. - यदि छात्र किसी अन्य स्कूल में नामांकित है, तो उसका प्रकार।
    $addCustomFieldRow($form, '0013', __('Type of current school - वर्तमान स्कूल का प्रकार'), __('If the Child is enrolled to another school, the type of it. - यदि छात्र किसी अन्य स्कूल में नामांकित है, तो उसका प्रकार।'), false);

    // 28. Year started schooling- विद्यालय शुरू करने का वर्ष- What year did the Child start any school. - छात्र ने किस वर्ष स्कूल जाना शुरू किया।
    $addCustomFieldRow($form, '0009', __('Year started schooling- विद्यालय शुरू करने का वर्ष'), __('What year did the Child start any school. - छात्र ने किस वर्ष स्कूल जाना शुरू किया।'), false);

    // 29. Year in which the Child dropped out - स्कूल छोड़ने का वर्ष- What year did the Child leave the last school. - छात्र ने स्कूल किस वर्ष छोड़ा था।
    $addCustomFieldRow($form, '0010', __('Year in which the Child dropped out - स्कूल छोड़ने का वर्ष'), __('What year did the Child leave the last school. - छात्र ने स्कूल किस वर्ष छोड़ा था।'), false);

    // 30. Reason for not attending school- स्कूल न जाने का कारण
    $addCustomFieldRow($form, '0016', __('Reason for not attending school'), __('स्कूल न जाने का कारण'), false);

    // 31. Reason for coming to the Gyansetu center- ज्ञानसेतु पर आने का कारण
    $addCustomFieldRow($form, '0015', __('Reason for coming to the Gyansetu center'), __('ज्ञानसेतु पर आने का कारण'), false);

    // FAMILY
    if (!empty($gibbonFamilyID)) {
        $dataSelect = array('gibbonFamilyID' => $gibbonFamilyID);
        $sqlSelect = 'SELECT * FROM gibbonFamily WHERE gibbonFamily.gibbonFamilyID=:gibbonFamilyID ORDER BY name';
    } else {
        $dataSelect = array('gibbonPersonID' => $gibbonPersonID);
        $sqlSelect = 'SELECT * FROM gibbonFamily JOIN gibbonFamilyAdult ON (gibbonFamily.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID) WHERE gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID GROUP BY gibbonFamily.gibbonFamilyID ORDER BY name';
    }

    $resultSelect = $pdo->executeQuery($dataSelect, $sqlSelect);

    if ($public == true or $resultSelect->rowCount() < 1) {

        $form->addHiddenValue('gibbonFamily', 'FALSE');

        //GS//if ($siblingApplicationMode == true) {
        //GS//$form->addHiddenValue('homeAddress', isset($application['homeAddress'])? $application['homeAddress'] : '');
        //GS//$form->addHiddenValue('homeAddressDistrict', isset($application['homeAddressDistrict'])? $application['homeAddressDistrict'] : '');
        //GS//$form->addHiddenValue('homeAddressCountry', isset($application['homeAddressCountry'])? $application['homeAddressCountry'] : '');
        //GS//} else {
        //GS//// HOME ADDRESS
        //GS//$form->addRow()->addHeading(__('Gyansetu Center Address - मकान न.,गांव, ब्लॉक')); //GS//
        //GS//        //GS//$row = $form->addRow();
        //GS//$row->addLabel('homeAddress', __('Gyansetu Center Address - ज्ञानसेतु केंद्र का पता'))->description(__('Unit, Building, Street - इकाई, भवन, गली'));
        //GS//$row->addTextArea('homeAddress')->required()->maxLength(255)->setRows(2);
        //GS//        //GS//$row = $form->addRow();
        //GS//$row->addLabel('homeAddressDistrict', __('Gyansetu Center Address (State) - ज्ञानसेतु केंद्र का पता (राज्य)'));
        //GS////GS//$row->addTextFieldDistrict('homeAddressDistrict')->required();
        //GS//$row->addTextField('homeAddressDistrict')->setValue('Uttar Pradesh')->required(); //GS//
        //GS//        //GS//$row = $form->addRow();
        //GS//$row->addLabel('homeAddressCountry', __('Gyansetu Center Address (Country) - ज्ञानसेतु केंद्र का पता (देश)'));
        //GS//$row->addSelectCountry('homeAddressCountry')->required();
        //GS//}
        $form->addHiddenValue('homeAddress', isset($application['homeAddress']) && $application['homeAddress'] != '' ? $application['homeAddress'] : 'Gyansetu');
        $form->addHiddenValue('homeAddressDistrict', isset($application['homeAddressDistrict']) && $application['homeAddressDistrict'] != '' ? $application['homeAddressDistrict'] : 'Uttar Pradesh');
        $form->addHiddenValue('homeAddressCountry', isset($application['homeAddressCountry']) && $application['homeAddressCountry'] != '' ? $application['homeAddressCountry'] : 'India');

        // PARENT 1 - IF EXISTS
        if (!empty($gibbonPersonID) || !empty($application['parent1gibbonPersonID'])) {

            if (!empty($application['parent1gibbonPersonID'])) {
                // Get parent info from sibling application
                $parent1gibbonPersonID = $application['parent1gibbonPersonID'];
            } else {
                // Get parent info from gibbonPersonID
                $parent1gibbonPersonID = $gibbonPersonID;
            }

            $dataParent = array('gibbonPersonID' => $parent1gibbonPersonID);
            $sqlParent = 'SELECT username, email, surname, preferredName, fields FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
            $resultParent= $pdo->executeQuery($dataParent, $sqlParent);

            if ($parent = $resultParent->fetch()) {
                $parent1username = $parent['username'];
                $parent1email = $parent['email'];
                $parent1surname = $parent['surname'];
                $parent1preferredName = $parent['preferredName'];
                $parent1fields = $parent['fields'];
            }

            $form->addRow()->addHeading(__('Parent/Guardian - अभिभावक की जानकारी').' 1');

            $form->addHiddenValue('parent1email', $parent1email);
            $form->addHiddenValue('parent1gibbonPersonID', $parent1gibbonPersonID);

            $row = $form->addRow();
                $row->addLabel('parent1username', __('Username'))->description(__('System login ID.'));
                $row->addTextField('parent1username')->setValue($parent1username)->maxLength(30)->readOnly();

            $row = $form->addRow();
                $row->addLabel('parent1surname', __('Surname'))->description(__('Family name as shown in ID documents.'));
                $row->addTextField('parent1surname')->setValue($parent1surname)->maxLength(30)->readOnly();

            $row = $form->addRow();
                $row->addLabel('parent1preferredName', __('Preferred Name'))->description(__('Most common name, alias, nickname, etc.'));
                $row->addTextField('parent1preferredName')->setValue($parent1preferredName)->maxLength(30)->readOnly();

            $row = $form->addRow();
                $row->addLabel('parent1relationship', __('Relationship'));
                $row->addSelectRelationship('parent1relationship')->required();

            // CUSTOM FIELDS FOR PARENT 1 WITH FAMILY
            $params = ['parent' => 1, 'applicationForm' => 1, 'prefix' => 'parent1custom', 'headingPrefix' => __('Parent/Guardian - माता-पिता/अभिभावक').' 1', 'headingLevel' => 'h4'];
            $customFieldHandler->addCustomFieldsToForm($form, 'User', $params, $parent1fields);

            $start = 2;
        } else {
            $start = 1;
        }

        // PARENTS
        for ($i = $start;$i < 3;++$i) {
            $subheading = '';
            if ($i == 1) {
                $subheading = '<span style="font-size: 75%">'.__('(e.g. mother)').'</span>';
            } elseif ($i == 2 and $gibbonPersonID == '') {
                $subheading = '<span style="font-size: 75%">'.__('(e.g. father)').'</span>';
            }

            $form->addRow()->addHeading(__('Parent/Guardian - माता-पिता/अभिभावक').' '.$i)->append($subheading);

            if ($i == 2) {
                $checked = ($siblingApplicationMode && !empty($application['parent2gibbonPersonID'])) ? 'No' : 'Yes';
                $form->addRow()->addCheckbox('secondParent')->setValue('No')->checked($checked)->prepend(__('Do not include a second parent/guardian - दूसरे माता-पिता/अभिभावक को शामिल न करें'));
                $form->toggleVisibilityByClass('parentSection2')->onCheckbox('secondParent')->whenNot('No');
            }

            // PARENT PERSONAL DATA
            $row = $form->addRow()->setClass("parentSection{$i}");
                //GS//$row->addSubheading(__('Parent/Guardian - अभिभावक की जानकारी')." $i ".__('Personal Data'));

            //GS//$row = $form->addRow()->setClass("parentSection{$i}");
            //GS//    $row->addLabel("parent{$i}title", __('Title'));
            //GS//    $row->addSelectTitle("parent{$i}title")->required()->loadFrom($application);
            $form->addHiddenValue('parent{$i}title', isset($application['parent{$i}title'])? $application['parent{$i}title'] : ''); //GS//

            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addLabel("parent{$i}firstName", __('Name - नाम'))->description(__('First name as shown in government documents. - पहला नाम जैसा कि आईडी दस्तावेजों में दिखाया गया है'));
                $row->addTextField("parent{$i}firstName")->required()->maxLength(30)->loadFrom($application);

            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addLabel("parent{$i}surname", __('Surname - उपनाम'))->description(__('Family name as shown in government documents. - परिवार का उपनाम सरकारी दस्तावेज के अनुसार'));
                $row->addTextField("parent{$i}surname")->required()->maxLength(30)->loadFrom($application);

            //GS//$row = $form->addRow()->setClass("parentSection{$i}");
            //GS//    $row->addLabel("parent{$i}preferredName", __('Preferred Name'))->description(__('Most common name, alias, nickname, etc.'));
            //GS//    $row->addTextField("parent{$i}preferredName")->required()->maxLength(30)->loadFrom($application);
            $form->addHiddenValue('parent{$i}preferredName', isset($application['parent{$i}preferredName'])? $application['parent{$i}preferredName'] : ''); //GS//

            //GS//$row = $form->addRow()->setClass("parentSection{$i}");
            //GS//    $row->addLabel("parent{$i}officialName", __('Official Name'))->description(__('Full name as shown in ID documents.'));
            //GS//    $row->addTextField("parent{$i}officialName")->required()->maxLength(150)->loadFrom($application);
            $form->addHiddenValue('parent{$i}officialName', isset($application['parent{$i}officialName'])? $application['parent{$i}officialName'] : ''); //GS//

            //GS//$row = $form->addRow()->setClass("parentSection{$i}");
            //GS//    $row->addLabel("parent{$i}nameInCharacters", __('Name In Characters'))->description(__('Chinese or other character-based name.'));
            //GS//    $row->addTextField("parent{$i}nameInCharacters")->maxLength(20)->loadFrom($application);
            $form->addHiddenValue('parent{$i}nameInCharacters', isset($application['parent{$i}nameInCharacters'])? $application['parent{$i}nameInCharacters'] : ''); //GS//

            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addLabel("parent{$i}gender", __('Gender - लिंग'));
                $row->addSelectGender("parent{$i}gender")->required()->loadFrom($application);

            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addLabel("parent{$i}relationship", __('Relationship - विद्यार्थी का सम्बन्ध'));
                $row->addSelectRelationship("parent{$i}relationship")->required();

            // PARENT PERSONAL BACKGROUND
            //GS//$row = $form->addRow()->setClass("parentSection{$i}");
            //GS//    $row->addSubheading(__('Parent/Guardian')." $i ".__('Personal Background'));

            //GS//$row = $form->addRow()->setClass("parentSection{$i}");
            //GS//    $row->addLabel("parent{$i}languageFirst", __('First Language'));
            //GS//    $row->addSelectLanguage("parent{$i}languageFirst")->placeholder()->loadFrom($application);
            $form->addHiddenValue('parent{$i}languageFirst', isset($application['parent{$i}languageFirst'])? $application['parent{$i}languageFirst'] : ''); //GS//

            //GS//$row = $form->addRow()->setClass("parentSection{$i}");
            //GS//    $row->addLabel("parent{$i}languageSecond", __('Second Language'));
            //GS//    $row->addSelectLanguage("parent{$i}languageSecond")->placeholder()->loadFrom($application);
            $form->addHiddenValue('parent{$i}languageSecond', isset($application['parent{$i}languageSecond'])? $application['parent{$i}languageSecond'] : ''); //GS//

            // PERSONAL DOCUMENTS
            //GS//$params = ['parent' => true, 'applicationForm' => true, 'prefix' => "parent{$i}", 'class' => "parentSection{$i}"];
            //GS//$personalDocumentHandler->addPersonalDocumentsToForm($form, null, null, $params);
            $form->addHiddenValue('parent{$i}document', isset($application['parent{$i}document'])? $application['parent{$i}document'] : ''); //GS//

            // PARENT CONTACT
            //GS//$row = $form->addRow()->setClass("parentSection{$i}");
            //GS//    $row->addSubheading(__('Parent/Guardian - माता-पिता/अभिभावक')." $i ".__('Contact - संपर्क'));

            //GS//$row = $form->addRow()->setClass("parentSection{$i}");
            //GS//    $row->addLabel("parent{$i}email", __('Email'));
            //GS//    $email = $row->addEmail("parent{$i}email")->required($i == 1)->loadFrom($application);
            //GS//    if ($uniqueEmailAddress == 'Y') {
            //GS//        $email->uniqueField('./publicRegistrationCheck.php', array('fieldName' => 'email'));
            //GS//    }
            $form->addHiddenValue('parent{$i}email', isset($application['parent{$i}email'])? $application['parent{$i}email'] : ''); //GS//


            //GS//for ($y = 1; $y < 3; ++$y) { //GS//
            //GS//$row = $form->addRow()->setClass("parentSection{$i}");
            //GS//$row->addLabel("parent{$i}phone{$y}", __('Phone - फोन').' '.$y)->description(__('Type, country code, number.'));
            //GS//$row->addPhoneNumber("parent{$i}phone{$y}")->setRequired($i == 1 && $y == 1)->loadFrom($application);
            //GS//}
            // Phone - फोन 1 * (only required for parent 1)
            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addLabel("parent{$i}phone1", __('Phone - फोन 1'));
                $row->addPhoneNumber("parent{$i}phone1")->setRequired($i == 1)->loadFrom($application);

            // Phone - फोन 2
            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addLabel("parent{$i}phone2", __('Phone - फोन 2'));
                $row->addPhoneNumber("parent{$i}phone2")->loadFrom($application);

            // PARENT EMPLOYMENT
            //GS//$row = $form->addRow()->setClass("parentSection{$i}");
            //GS//    $row->addSubheading(__('Parent/Guardian - माता-पिता/अभिभावक')." $i ".__('Employment - रोज़गार'));

            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addLabel("parent{$i}profession", __('Parent\'s work - कार्य'));
                $row->addTextField("parent{$i}profession")->required($i == 1)->maxLength(90)->loadFrom($application);

            //GS//$row = $form->addRow()->setClass("parentSection{$i}");
            //GS//    $row->addLabel("parent{$i}employer", __('Employer'));
            //GS//    $row->addTextField("parent{$i}employer")->maxLength(90)->loadFrom($application);
            $form->addHiddenValue('parent{$i}employer', isset($application['parent{$i}employer'])? $application['parent{$i}employer'] : ''); //GS//

            // CUSTOM FIELDS FOR PARENTS
            //GS//$params = ['parent' => 1, 'applicationForm' => 1, 'prefix' => "parent{$i}custom", 'headingPrefix' => __('Parent/Guardian - माता-पिता/अभिभावक')." $i", 'headingLevel' => 'h4', 'class' => "parentSection{$i}"];
            //GS//$customFieldHandler->addCustomFieldsToForm($form, 'User', $params, $application["parent{$i}fields"] ?? '');

            $parentExistingFields = !empty($application["parent{$i}fields"]) ? json_decode($application["parent{$i}fields"], true) : [];

            // Education - शिक्षा *
            $educationField = $allCustomFields['0023'] ?? null;
            if (!empty($educationField)) {
                $eduFieldCopy = $educationField;
                $eduFieldCopy['required'] = 'Y';
                $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addLabel("parent{$i}custom0023", __('Education - शिक्षा'))->description(__('Maximum degree obtained - प्राप्त की गई अधिकतम डिग्री'));
                $row->addCustomField("parent{$i}custom0023", $eduFieldCopy)->setValue($parentExistingFields['0023'] ?? '');
            }

            // Monthly salary - मासिक आय *
            $salaryField = $allCustomFields['0006'] ?? null;
            if (!empty($salaryField)) {
                $salFieldCopy = $salaryField;
                $salFieldCopy['required'] = 'Y';
                $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addLabel("parent{$i}custom0006", __('Monthly salary - मासिक आय'))->description(__('Monthly salary in rupees. - रुपये में मासिक आय'));
                $row->addCustomField("parent{$i}custom0006", $salFieldCopy)->setValue($parentExistingFields['0006'] ?? '');
            }
        }
    } else {
        // LOGGED IN PARENT WITH FAMILY
        $form->addHiddenValue('gibbonFamily', 'TRUE');

        $row = $form->addRow();
            $row->addHeading(__('Family'))->append(__('Choose the family you wish to associate this application with.'));

        $table = $form->addRow()->addTable()->addClass('colorOddEven');

        $header = $table->addHeaderRow();
        $header->addContent(__('Family Name'));
        $header->addContent(__('Selected'));
        $header->addContent(__('Relationships'));

        $checked = null;
        while ($rowSelect = $resultSelect->fetch()) {
            // Re-select the family for sibling applications, otherwise select the first family
            if (isset($application['gibbonFamilyID'])) {
                $checked = $application['gibbonFamilyID'];
            } else if (is_null($checked)) {
                $checked = $rowSelect['gibbonFamilyID'];
            }

            // Get the family relationships

                $dataRelationships = array('gibbonFamilyID' => $rowSelect['gibbonFamilyID']);
                $sqlRelationships = 'SELECT surname, preferredName, title, gender, gibbonFamilyAdult.gibbonPersonID FROM gibbonFamilyAdult JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID';
                $resultRelationships = $connection2->prepare($sqlRelationships);
                $resultRelationships->execute($dataRelationships);

            $row = $table->addRow()->setClass('break');
            $row->addContent($rowSelect['name'])->wrap('<strong>','</strong>')->addClass('shortWidth');
            $row->addRadio('gibbonFamilyID')->fromArray(array($rowSelect['gibbonFamilyID'] => ''))->checked($checked);
            $subTable = $row->addTable()->setClass('blank');

            while ($rowRelationships = $resultRelationships->fetch()) {
                $selected = ($rowRelationships['gender'] == 'F')? 'Mother' : (($rowRelationships['gender'] == 'M')? 'Father' : '');

                $subTableRow = $subTable->addRow()->addClass('right');
                $subTableRow->addContent(Format::name($rowRelationships['title'], $rowRelationships['preferredName'], $rowRelationships['surname'], 'Parent'))->setClass('mediumWidth');
                $subTableRow->addSelectRelationship($rowSelect['gibbonFamilyID'].'-relationships[]')->selected($selected)->setClass('mediumWidth');
                $form->addHiddenValue($rowSelect['gibbonFamilyID'].'-relationshipsGibbonPersonID[]', $rowRelationships['gibbonPersonID']);
            }

            // If there's only one family, set this now so the Siblings section works
            if ($resultSelect->rowCount() == 1) {
                $gibbonFamilyID = $rowSelect['gibbonFamilyID'];
            }
        }
    }

    // SIBLINGS
    //GS//$form->addRow()->addHeading(__('Siblings'))->append(__('Please give information on the applicants\'s siblings.'));

    //GS//$table = $form->addRow()->addTable()->addClass('colorOddEven');

    //GS//$header = $table->addHeaderRow();
    //GS//$header->addContent(__('Sibling Name'));
    //GS//$header->addContent(__('Date of Birth'))->append('<br/><small>'.$session->get('i18n')['dateFormat'].'</small>');
    //GS//$header->addContent(__('School Attending'));
    //GS//$header->addContent(__('Joining Date'))->append('<br/><small>'.$session->get('i18n')['dateFormat'].'</small>');

    //GS//$rowCount = 1;

    //GS//// List siblings who have been to or are at the school
    //GS//if (isset($gibbonFamilyID)) {

    //GS//        $dataSibling = array('gibbonFamilyID' => $gibbonFamilyID);
    //GS//        $sqlSibling = 'SELECT surname, preferredName, dob, dateStart FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID ORDER BY dob ASC, surname, preferredName';
    //GS//        $resultSibling = $connection2->prepare($sqlSibling);
    //GS//        $resultSibling->execute($dataSibling);

    //GS//    while ($rowSibling = $resultSibling->fetch()) {
    //GS//        $name = Format::name('', $rowSibling['preferredName'], $rowSibling['surname'], 'Student');

    //GS//        $row = $table->addRow();
    //GS//        $row->addTextField('siblingName'.$rowCount)->maxLength(50)->setSize(26)->setValue($name);
    //GS//        $row->addDate('siblingDOB'.$rowCount)->setSize(10)->setValue(Format::date($rowSibling['dob']));
    //GS//        $row->addTextField('siblingSchool'.$rowCount)->maxLength(50)->setSize(30)->setValue($session->get('organisationName'));
    //GS//        $row->addDate('siblingSchoolJoiningDate'.$rowCount)->setSize(10)->setValue(Format::date($rowSibling['dateStart']));

    //GS//        $rowCount++;
    //GS//    }
    //GS//}

    //GS//// Add additional sibling rows up to 3
    //GS//for ($i = $rowCount; $i <= 3; ++$i) {
    //GS//    $row = $table->addRow();
    //GS//    $nameField = $row->addTextField('siblingName'.$i)->maxLength(50)->setSize(26);
    //GS//    $dobField = $row->addDate('siblingDOB'.$i)->setSize(10);
    //GS//    $row->addTextField('siblingSchool'.$i)->maxLength(50)->setSize(30);
    //GS//    $row->addDate('siblingSchoolJoiningDate'.$i)->setSize(10);

    //GS//    // Fill in some info from any sibling applications
    //GS//    if (!empty($linkedApplications[$i-1])) {
    //GS//        $nameField->setValue($linkedApplications[$i-1]['officialName']);
    //GS//        $dobField->setValue(Format::date($linkedApplications[$i-1]['dob']));
    //GS//    }
    //GS//}

    // LANGUAGE OPTIONS
    $languageOptionsActive = getSettingByScope($connection2, 'Application Form', 'languageOptionsActive');
    $languageOptionsBlurb = getSettingByScope($connection2, 'Application Form', 'languageOptionsBlurb');
    $languageOptionsLanguageList = getSettingByScope($connection2, 'Application Form', 'languageOptionsLanguageList');

    if ($languageOptionsActive == 'Y' && ($languageOptionsBlurb != '' OR $languageOptionsLanguageList != '')) {

        $heading = $form->addRow()->addHeading(__('Language Selection'));

        if (!empty($languageOptionsBlurb)) {
            $heading->append($languageOptionsBlurb);
        }

        if ($languageOptionsLanguageList != '') {
            $languages = array_map(function($item) { return trim($item); }, explode(',', $languageOptionsLanguageList));

            $row = $form->addRow();
                $row->addLabel('languageChoice', __('Language Choice'))->description(__('Please choose preferred additional language to study.'));
                $row->addSelect('languageChoice')->fromArray($languages)->required()->placeholder();

            $row = $form->addRow();
                $column = $row->addColumn();
                $column->addLabel('languageChoiceExperience', __('Language Choice Experience'))->description(__('Has the applicant studied the selected language before? If so, please describe the level and type of experience.'));
                $column->addTextArea('languageChoiceExperience')->required()->setRows(5)->setClass('fullWidth');
        }
    }

    // SCHOLARSHIPS
    $scholarshipOptionsActive = getSettingByScope($connection2, 'Application Form', 'scholarshipOptionsActive');

    if ($scholarshipOptionsActive == 'Y') {
        $heading = $form->addRow()->addHeading(__('Scholarships'));

        $scholarship = getSettingByScope($connection2, 'Application Form', 'scholarships');
        if (!empty($scholarship)) {
            $heading->append($scholarship);
        }

        $row = $form->addRow();
            $row->addLabel('scholarshipInterest', __('Interest'))->description(__('Indicate if you are interested in a scholarship.'));
            $row->addRadio('scholarshipInterest')->fromArray(array('Y' => __('Yes'), 'N' => __('No')))->checked('N')->inline();

        $row = $form->addRow();
            $row->addLabel('scholarshipRequired', __('Required?'))->description(__('Is a scholarship required for you to take up a place at the school?'));
            $row->addRadio('scholarshipRequired')->fromArray(array('Y' => __('Yes'), 'N' => __('No')))->checked('N')->inline();
    }


    // PAYMENT
    $paymentOptionsActive = getSettingByScope($connection2, 'Application Form', 'paymentOptionsActive');

    if ($paymentOptionsActive == 'Y') {
        $form->addRow()->addHeading(__('Payment'));

        $form->addRow()->addContent(__('If you choose family, future invoices will be sent according to your family\'s contact preferences, which can be changed at a later date by contacting the school. For example you may wish both parents to receive the invoice, or only one. Alternatively, if you choose Company, you can choose for all or only some fees to be covered by the specified company.'))->wrap('<p>','</p>');

        $row = $form->addRow();
            $row->addLabel('payment', __('Send Future Invoices To'));
            $row->addRadio('payment')
                ->fromArray(array('Family' => __('Family'), 'Company' => __('Company')))
                ->checked('Family')
                ->inline()
                ->loadFrom($application);

        $form->toggleVisibilityByClass('paymentCompany')->onRadio('payment')->when('Company');

        // COMPANY DETAILS
        $row = $form->addRow()->addClass('paymentCompany');
            $row->addLabel('companyName', __('Company Name'));
            $row->addTextField('companyName')->required()->maxLength(100)->loadFrom($application);

        $row = $form->addRow()->addClass('paymentCompany');
            $row->addLabel('companyContact', __('Company Contact Person'));
            $row->addTextField('companyContact')->required()->maxLength(100)->loadFrom($application);

        $row = $form->addRow()->addClass('paymentCompany');
            $row->addLabel('companyAddress', __('Company Address'));
            $row->addTextField('companyAddress')->required()->maxLength(255)->loadFrom($application);

        $row = $form->addRow()->addClass('paymentCompany');
            $row->addLabel('companyEmail', __('Company Emails'))->description(__('Comma-separated list of email address'));
            $row->addTextField('companyEmail')->required()->loadFrom($application);

        $row = $form->addRow()->addClass('paymentCompany');
            $row->addLabel('companyCCFamily', __('CC Family?'))->description(__('Should the family be sent a copy of billing emails?'));
            $row->addYesNo('companyCCFamily')->selected('N')->loadFrom($application);

        $row = $form->addRow()->addClass('paymentCompany');
            $row->addLabel('companyPhone', __('Company Phone'));
            $row->addTextField('companyPhone')->maxLength(20)->loadFrom($application);

        // COMPANY FEE CATEGORIES
        $sqlFees = "SELECT gibbonFinanceFeeCategoryID as value, name FROM gibbonFinanceFeeCategory WHERE active='Y' AND NOT gibbonFinanceFeeCategoryID=1 ORDER BY name";
        $resultFees = $pdo->executeQuery(array(), $sqlFees);

        if (!$resultFees || $resultFees->rowCount() == 0) {
            $form->addHiddenValue('companyAll', 'Y');
        } else {
            $row = $form->addRow()->addClass('paymentCompany');
                $row->addLabel('companyAll', __('Company All?'))->description(__('Should all items be billed to the specified company, or just some?'));
                $row->addRadio('companyAll')->fromArray(array('Y' => __('All'), 'N' => __('Selected')))->checked('Y')->inline()->loadFrom($application);

            $form->toggleVisibilityByClass('paymentCompanyCategories')->onRadio('companyAll')->when('N');

            $existingFeeCategoryIDList = (isset($application['gibbonFinanceFeeCategoryIDList']) && is_array($application['gibbonFinanceFeeCategoryIDList']))? $application['gibbonFinanceFeeCategoryIDList'] : array();

            $row = $form->addRow()->addClass('paymentCompany')->addClass('paymentCompanyCategories');
                $row->addLabel('gibbonFinanceFeeCategoryIDList[]', __('Company Fee Categories'))->description(__('If the specified company is not paying all fees, which categories are they paying?'));
                $row->addCheckbox('gibbonFinanceFeeCategoryIDList[]')
                    ->fromResults($resultFees)
                    ->fromArray(array('0001' => __('Other')))

                    ->loadFromCSV($application);
        }
    } else {
        $form->addHiddenValue('payment', 'Family');
    }

    // REQURIED DOCUMENTS
    $requiredDocuments = getSettingByScope($connection2, 'Application Form', 'requiredDocuments');

    if (!empty($requiredDocuments)) {
        $requiredDocumentsText = getSettingByScope($connection2, 'Application Form', 'requiredDocumentsText');
        $requiredDocumentsCompulsory = getSettingByScope($connection2, 'Application Form', 'requiredDocumentsCompulsory');

        $heading = $form->addRow()->addHeading(__('Supporting Documents'));

        if (!empty($requiredDocumentsText)) {
            $heading->append($requiredDocumentsText);

            if ($requiredDocumentsCompulsory == 'Y') {
                $heading->append(__('All documents must all be included before the application can be submitted.'));
            } else {
                $heading->append(__('These documents are all required, but can be submitted separately to this form if preferred. Please note, however, that your application will be processed faster if the documents are included here.'));
            }
        }

        $fileUploader = new Gibbon\FileUploader($pdo, $gibbon->session);

        $requiredDocumentsList = explode(',', $requiredDocuments);

        for ($i = 0; $i < count($requiredDocumentsList); $i++) {
            $form->addHiddenValue('fileName'.$i, $requiredDocumentsList[$i]);

            $row = $form->addRow();
                $row->addLabel('file'.$i, $requiredDocumentsList[$i]);
                $row->addFileUpload('file'.$i)
                    ->accepts($fileUploader->getFileExtensions())
                    ->setRequired($requiredDocumentsCompulsory == 'Y')
                    ->setMaxUpload(false);
        }

        $row = $form->addRow()->addContent(getMaxUpload($guid));
        $form->addHiddenValue('fileCount', count($requiredDocumentsList));
    }


    // MISCELLANEOUS
    //GS//$form->addRow()->addHeading(__('Miscellaneous'));

    $howDidYouHear = getSettingByScope($connection2, 'Application Form', 'howDidYouHear');
    $howDidYouHearList = explode(',', $howDidYouHear);

    //GS//$row = $form->addRow();
    //GS//    $row->addLabel('howDidYouHear', __('How Did You Hear About Us?'));

    //GS//if (empty($howDidYouHear)) {
    //GS//    $row->addTextField('howDidYouHear')->required()->maxLength(30)->loadFrom($application);
    //GS//} else {
    //GS//    $row->addSelect('howDidYouHear')->fromArray($howDidYouHearList)->required()->placeholder()->loadFrom($application);

    //GS//    $form->toggleVisibilityByClass('tellUsMore')->onSelect('howDidYouHear')->whenNot(__('Please select...'));

    //GS//    $row = $form->addRow()->addClass('tellUsMore');
    //GS//        $row->addLabel('howDidYouHearMore', __('Tell Us More'))->description(__('The name of a person or link to a website, etc.'));
    //GS//        $row->addTextField('howDidYouHearMore')->maxLength(255)->loadFrom($application);
    //GS//}
    $form->addHiddenValue('howDidYouHear', isset($application['howDidYouHear'])? $application['howDidYouHear'] : ''); //GS//

    // PRIVACY
    $privacySetting = getSettingByScope($connection2, 'User Admin', 'privacy');
    $privacyBlurb = getSettingByScope($connection2, 'User Admin', 'privacyBlurb');
    $privacyOptions = getSettingByScope($connection2, 'User Admin', 'privacyOptions');

    if ($privacySetting == 'Y' && !empty($privacyOptions)) {

        $form->addRow()->addSubheading(__('Privacy'))->append($privacyBlurb);

        $options = array_map(function($item) { return trim($item); }, explode(',', $privacyOptions));

        $row = $form->addRow();
            $row->addLabel('privacyOptions[]', __('Privacy Options'));
            $row->addCheckbox('privacyOptions[]')->fromArray($options)->addClass('md:max-w-lg');
    }

    // AGREEMENT
    $agreement = getSettingByScope($connection2, 'Application Form', 'agreement');
    if (!empty($agreement)) {
        $form->addRow()->addHeading(__('Agreement'))->append($agreement);

        $row = $form->addRow();
            $row->addLabel('agreement', '<b>'.__('Do you agree to the above?').'</b>');
            $row->addCheckbox('agreement')->description(__('Yes'))->setValue('on')->required();
    }

    // OFFICE ONLY
    if (isActionAccessible($guid, $connection2, '/modules/Students/applicationForm_manage.php')) {
        $form->addRow()->addHeading(__('For Office Use'));

        $row = $form->addRow();
            $row->addLabel('skipEmailNotification', __('Skip sending a notification email to parents?'));
            $row->addCheckbox('skipEmailNotification')->description(__('Yes'))->setValue('on')->checked('on');
    }

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();

    //GS: trying to constraint inputs to english chars and numbers
    echo('<script type="text/javascript">
    ///function onlyLettersNnumbers(e) {
    ///    var regex = new RegExp("^[a-zA-Z0-9_ ]+$");
    ///    var str = String.fromCharCode(!e.charCode ? e.which : e.charCode);
    ///    if (regex.test(str)) {
    ///        return true;
    ///    }
    ///    e.preventDefault();
    ///    return false;
    ///}
    ///
    ///firstName.onkeypress = onlyLettersNnumbers;


    // Function to validate the date


    </script>
    ');
    
    //Get postscrript
    $postscript = getSettingByScope($connection2, 'Application Form', 'postscript');
    if ($postscript != '') {
        echo '<h2>';
        echo __('Further Information');
        echo '</h2>';
        echo "<p style='padding-bottom: 15px'>";
        echo $postscript;
        echo '</p>';
    }
}
