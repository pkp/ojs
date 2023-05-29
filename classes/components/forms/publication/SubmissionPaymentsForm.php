<?php
/**
 * @file classes/components/form/publication/SubmissionPaymentsForm.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionPaymentsForm
 *
 * @ingroup classes_controllers_form
 *
 * @brief A form for managing submission fees.
 */

namespace APP\components\forms\publication;

use APP\payment\ojs\OJSCompletedPaymentDAO;
use APP\payment\ojs\OJSPaymentManager;
use PKP\components\forms\FieldRadioInput;
use PKP\components\forms\FormComponent;
use PKP\db\DAORegistry;

define('FORM_SUBMISSION_PAYMENTS', 'submissionPayments');

class SubmissionPaymentsForm extends FormComponent
{
    /** @copydoc FormComponent::$id */
    public $id = FORM_SUBMISSION_PAYMENTS;

    /** @copydoc FormComponent::$method */
    public $method = 'PUT';

    /**
     * Constructor
     *
     * @param string $action URL to submit the form to
     * @param \APP\submission\Submission $submission The submission to inspect payment status of
     * @param \APP\journal\Journal $submissionContext The context of the submission
     */
    public function __construct($action, $submission, $submissionContext)
    {
        $this->action = $action;

        $completedPaymentDao = DAORegistry::getDAO('OJSCompletedPaymentDAO'); /** @var OJSCompletedPaymentDAO $completedPaymentDao */
        $publicationFeePayment = $completedPaymentDao->getByAssoc(null, OJSPaymentManager::PAYMENT_TYPE_PUBLICATION, $submission->getId());

        $this->addField(new FieldRadioInput('publicationFeeStatus', [
            'label' => __('payment.type.publication'),
            'type' => 'radio',
            'options' => [
                ['value' => 'waived', 'label' => __('payment.waived')],
                ['value' => 'paid', 'label' => __('payment.paid')],
                ['value' => 'unpaid', 'label' => __('payment.unpaid')],
            ],
            'value' => $publicationFeePayment
                ? ($publicationFeePayment->getAmount() ? 'paid' : 'waived')
                : 'unpaid'
        ]));
    }
}
