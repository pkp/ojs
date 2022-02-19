<?php

/**
 * @file classes/subscription/form/PaymentTypesForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PaymentTypesForm
 * @ingroup subscription
 *
 * @brief Permit configuration of the various payment types.
 */

namespace APP\subscription\form;

use PKP\form\Form;
use PKP\db\DAORegistry;
use APP\core\Application;

class PaymentTypesForm extends Form
{
    /** @var array the setting names */
    protected $settings;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct('payments/paymentTypesForm.tpl');

        $this->settings = [
            'publicationFee' => 'float',
            'purchaseArticleFeeEnabled' => 'bool',
            'purchaseArticleFee' => 'float',
            'purchaseIssueFeeEnabled' => 'bool',
            'purchaseIssueFee' => 'float',
            'membershipFee' => 'float',
            'restrictOnlyPdf' => 'bool',
        ];

        $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'publicationFee', 'optional', 'manager.payment.form.numeric', function ($publicationFee) {
            return is_numeric($publicationFee) && $publicationFee >= 0;
        }));
        $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'purchaseArticleFee', 'optional', 'manager.payment.form.numeric', function ($purchaseArticleFee) {
            return is_numeric($purchaseArticleFee) && $purchaseArticleFee >= 0;
        }));
        $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'purchaseIssueFee', 'optional', 'manager.payment.form.numeric', function ($purchaseIssueFee) {
            return is_numeric($purchaseIssueFee) && $purchaseIssueFee >= 0;
        }));
        $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'membershipFee', 'optional', 'manager.payment.form.numeric', function ($membershipFee) {
            return is_numeric($membershipFee) && $membershipFee >= 0;
        }));
    }

    /**
     * Initialize form data from current group group.
     */
    public function initData()
    {
        $journal = Application::get()->getRequest()->getContext();
        foreach (array_keys($this->settings) as $settingName) {
            $this->setData($settingName, $journal->getData($settingName));
        }
    }

    /**
     * Assign form data to user-submitted data.
     */
    public function readInputData()
    {
        $this->readUserVars(array_keys($this->settings));
    }

    /**
     * @copydoc Form::execute
     */
    public function execute(...$functionArgs)
    {
        parent::execute(...$functionArgs);
        $journal = Application::get()->getRequest()->getJournal();
        foreach (array_keys($this->settings) as $settingName) {
            $journal->setData($settingName, $this->getData($settingName));
        }
        $journalDao = DAORegistry::getDAO('JournalDAO'); /** @var JournalDAO $journalDao */
        $journalDao->updateObject($journal);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\subscription\form\PaymentTypesForm', '\PaymentTypesForm');
}

