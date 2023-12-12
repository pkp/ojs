<?php
/**
 * @file classes/components/form/counter/CounterReportForm.php
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CounterReportForm
 *
 * @ingroup classes_controllers_form
 *
 * @brief A form for setting a counter report
 */

namespace APP\components\forms\counter;

use APP\sushi\IR;
use APP\sushi\IR_A1;
use APP\sushi\PR;
use APP\sushi\PR_P1;
use APP\sushi\TR;
use APP\sushi\TR_J3;
use PKP\components\forms\counter\PKPCounterReportForm;

class CounterReportForm extends PKPCounterReportForm
{
    public function setReportFields(): void
    {
        $formFieldsPR = PR::getReportSettingsFormFields();
        $this->reportFields['PR'] = array_map(function ($field) {
            $field->groupId = 'default';
            return $field;
        }, $formFieldsPR);

        $formFieldsPR_P1 = PR_P1::getReportSettingsFormFields();
        $this->reportFields['PR_P1'] = array_map(function ($field) {
            $field->groupId = 'default';
            return $field;
        }, $formFieldsPR_P1);

        $formFieldsTR = TR::getReportSettingsFormFields();
        $this->reportFields['TR'] = array_map(function ($field) {
            $field->groupId = 'default';
            return $field;
        }, $formFieldsTR);

        $formFieldsTR_J3 = TR_J3::getReportSettingsFormFields();
        $this->reportFields['TR_J3'] = array_map(function ($field) {
            $field->groupId = 'default';
            return $field;
        }, $formFieldsTR_J3);

        $formFieldsIR = IR::getReportSettingsFormFields();
        $this->reportFields['IR'] = array_map(function ($field) {
            $field->groupId = 'default';
            return $field;
        }, $formFieldsIR);

        $formFieldsIR_A1 = IR_A1::getReportSettingsFormFields();
        $this->reportFields['IR_A1'] = array_map(function ($field) {
            $field->groupId = 'default';
            return $field;
        }, $formFieldsIR_A1);
    }
}
