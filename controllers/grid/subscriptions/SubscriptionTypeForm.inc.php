<?php

/**
 * @file classes/subscription/form/SubscriptionTypeForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionTypeForm
 * @ingroup manager_form
 *
 * @brief Form for journal managers to create/edit subscription types.
 */

use APP\core\Application;
use APP\subscription\SubscriptionType;

use APP\template\TemplateManager;
use PKP\db\DAORegistry;
use PKP\facades\Locale;
use PKP\form\Form;
use Sokil\IsoCodes\IsoCodesFactory;

class SubscriptionTypeForm extends Form
{
    /** @var int $typeId the ID of the subscription type being edited */
    public $typeId;

    /** @var array $validFormats keys are valid subscription type formats */
    public $validFormats;

    /** @var array $validCurrencies keys are valid subscription type currencies */
    public $validCurrencies;

    /** @var int $journalId Journal ID */
    public $journalId;

    /**
     * Constructor
     *
     * @param int $journalId Journal ID
     * @param int $typeId leave as default for new subscription type
     * @param null|mixed $typeId
     */
    public function __construct($journalId, $typeId = null)
    {
        $this->journalId = $journalId;

        $this->validFormats = [
            SubscriptionType::SUBSCRIPTION_TYPE_FORMAT_ONLINE => __('subscriptionTypes.format.online'),
            SubscriptionType::SUBSCRIPTION_TYPE_FORMAT_PRINT => __('subscriptionTypes.format.print'),
            SubscriptionType::SUBSCRIPTION_TYPE_FORMAT_PRINT_ONLINE => __('subscriptionTypes.format.printOnline')
        ];

        $this->validCurrencies = [];
        foreach (Locale::getCurrencies() as $currency) {
            $this->validCurrencies[$currency->getLetterCode()] = $currency->getLocalName() . ' (' . $currency->getLetterCode() . ')';
        }
        asort($this->validCurrencies);

        $this->typeId = isset($typeId) ? (int) $typeId : null;

        parent::__construct('payments/subscriptionTypeForm.tpl');

        // Type name is provided
        $this->addCheck(new \PKP\form\validation\FormValidatorLocale($this, 'name', 'required', 'manager.subscriptionTypes.form.typeNameRequired'));

        // Cost	is provided and is numeric and positive
        $this->addCheck(new \PKP\form\validation\FormValidator($this, 'cost', 'required', 'manager.subscriptionTypes.form.costRequired'));
        $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'cost', 'required', 'manager.subscriptionTypes.form.costNumeric', fn($cost) => is_numeric($cost) && $cost >= 0));

        // Currency is provided and is valid value
        $this->addCheck(new \PKP\form\validation\FormValidator($this, 'currency', 'required', 'manager.subscriptionTypes.form.currencyRequired'));
        $this->addCheck(new \PKP\form\validation\FormValidatorInSet($this, 'currency', 'required', 'manager.subscriptionTypes.form.currencyValid', array_keys($this->validCurrencies)));

        // Format is provided and is valid value
        $this->addCheck(new \PKP\form\validation\FormValidator($this, 'format', 'required', 'manager.subscriptionTypes.form.formatRequired'));
        $this->addCheck(new \PKP\form\validation\FormValidatorInSet($this, 'format', 'required', 'manager.subscriptionTypes.form.formatValid', array_keys($this->validFormats)));

        // Institutional flag is valid value
        $this->addCheck(new \PKP\form\validation\FormValidatorInSet($this, 'institutional', 'optional', 'manager.subscriptionTypes.form.institutionalValid', ['0', '1']));

        $this->addCheck(new \PKP\form\validation\FormValidatorPost($this));
        $this->addCheck(new \PKP\form\validation\FormValidatorCSRF($this));
    }

    /**
     * Get a list of localized field names for this form
     *
     * @return array
     */
    public function getLocaleFieldNames()
    {
        $subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /** @var SubscriptionTypeDAO $subscriptionTypeDao */
        return $subscriptionTypeDao->getLocaleFieldNames();
    }

    /**
     * @copydoc Form::fetch()
     *
     * @param null|mixed $template
     */
    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign([
            'typeId' => $this->typeId,
            'validCurrencies' => $this->validCurrencies,
            'validFormats' => $this->validFormats,
        ]);
        return parent::fetch($request, $template, $display);
    }

    /**
     * Initialize form data from current subscription type.
     */
    public function initData()
    {
        if (isset($this->typeId)) {
            $subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /** @var SubscriptionTypeDAO $subscriptionTypeDao */
            $subscriptionType = $subscriptionTypeDao->getById($this->typeId, $this->journalId);

            if ($subscriptionType != null) {
                $this->_data = [
                    'name' => $subscriptionType->getName(null), // Localized
                    'description' => $subscriptionType->getDescription(null), // Localized
                    'cost' => $subscriptionType->getCost(),
                    'currency' => $subscriptionType->getCurrencyCodeAlpha(),
                    'duration' => $subscriptionType->getDuration(),
                    'format' => $subscriptionType->getFormat(),
                    'institutional' => $subscriptionType->getInstitutional(),
                    'membership' => $subscriptionType->getMembership(),
                    'disable_public_display' => $subscriptionType->getDisablePublicDisplay()
                ];
            } else {
                $this->typeId = null;
            }
        }
    }

    /**
     * Assign form data to user-submitted data.
     */
    public function readInputData()
    {
        $this->readUserVars(['name', 'description', 'cost', 'currency', 'duration', 'format', 'institutional', 'membership', 'disable_public_display']);

        $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'duration', 'optional', 'manager.subscriptionTypes.form.durationNumeric', fn($duration) => is_numeric($duration) && $duration >= 0));
    }

    /**
     * @copydoc Form::execute()
     */
    public function execute(...$functionArgs)
    {
        $subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /** @var SubscriptionTypeDAO $subscriptionTypeDao */

        if (isset($this->typeId)) {
            $subscriptionType = $subscriptionTypeDao->getById($this->typeId, $this->journalId);
        }

        if (!isset($subscriptionType)) {
            $subscriptionType = $subscriptionTypeDao->newDataObject();
            $subscriptionType->setInstitutional($this->getData('institutional') == null ? 0 : $this->getData('institutional'));
        }

        $request = Application::get()->getRequest();
        $journal = $request->getJournal();
        $subscriptionType->setJournalId($journal->getId());
        $subscriptionType->setName($this->getData('name'), null); // Localized
        $subscriptionType->setDescription($this->getData('description'), null); // Localized
        $subscriptionType->setCost(round($this->getData('cost'), 2));
        $subscriptionType->setCurrencyCodeAlpha($this->getData('currency'));
        $subscriptionType->setDuration(($duration = $this->getData('duration')) ? (int) $duration : null);
        $subscriptionType->setFormat($this->getData('format'));
        $subscriptionType->setMembership((int) $this->getData('membership'));
        $subscriptionType->setDisablePublicDisplay((int) $this->getData('disable_public_display'));

        parent::execute(...$functionArgs);

        // Update or insert subscription type
        if ($subscriptionType->getId() != null) {
            $subscriptionTypeDao->updateObject($subscriptionType);
        } else {
            $subscriptionType->setSequence(REALLY_BIG_NUMBER);
            $subscriptionTypeDao->insertObject($subscriptionType);

            // Re-order the subscription types so the new one is at the end of the list.
            $subscriptionTypeDao->resequenceSubscriptionTypes($subscriptionType->getJournalId());
        }
    }
}
