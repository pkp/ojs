<?php
/**
 * @file classes/components/form/decision/RequestPaymentDecisionForm.inc.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class RequestPaymentDecisionForm
 * @ingroup classes_controllers_form
 *
 * @brief A form to request or waive an APC payment when making an editorial decision
 */

namespace APP\components\forms\decision;

use PKP\components\forms\FieldOptions;
use PKP\components\forms\FormComponent;
use PKP\context\Context;

define('FORM_REQUEST_PAYMENT_DECISION', 'requestPaymentDecision');

class RequestPaymentDecisionForm extends FormComponent
{
    /** @copydoc FormComponent::$id */
    public $id = FORM_REQUEST_PAYMENT_DECISION;

    /** @copydoc FormComponent::$action */
    public $action = FormComponent::ACTION_EMIT;

    /**
     * Constructor
     */
    public function __construct(Context $context)
    {
        $this->addField(new FieldOptions('requestPayment', [
            'label' => __('common.payment'),
            'type' => 'radio',
            'options' => [
                [
                    'value' => true,
                    'label' => __(
                        'payment.requestPublicationFee',
                        ['feeAmount' => $context->getData('publicationFee') . ' ' . $context->getData('currency')]
                    ),
                ],
                [
                    'value' => false,
                    'label' => __('payment.waive'),
                ],
            ],
            'value' => true,
            'groupId' => 'default',
        ]));
    }
}
