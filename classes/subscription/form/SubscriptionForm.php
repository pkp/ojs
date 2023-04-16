<?php

/**
 * @file classes/subscription/form/SubscriptionForm.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionForm
 *
 * @ingroup subscription
 *
 * @brief Base form class for subscription create/edits.
 */

namespace APP\subscription\form;

use APP\core\Application;
use APP\facades\Repo;
use APP\mail\mailables\SubscriptionNotify;
use APP\subscription\Subscription;
use APP\subscription\SubscriptionDAO;
use APP\subscription\SubscriptionTypeDAO;
use APP\template\TemplateManager;
use PKP\db\DAORegistry;
use PKP\facades\Locale;
use PKP\form\Form;

class SubscriptionForm extends Form
{
    /** @var Subscription the subscription being created/edited */
    public $subscription;

    /** @var int the user associated with the subscription */
    public $userId;

    /** @var array of subscription types */
    public $subscriptionTypes;

    /** @var array valid subscription status values */
    public $validStatus;

    /** @var array valid user country values */
    public $validCountries;

    /**
     * Constructor
     *
     * @param string? $template Template to use for form presentation
     * @param int $subscriptionId The subscription ID for this subscription; null for new subscription
     */
    public function __construct($template, $subscriptionId = null)
    {
        parent::__construct($template);

        $subscriptionId = isset($subscriptionId) ? (int) $subscriptionId : null;

        $this->subscription = null;
        $this->subscriptionTypes = null;

        $this->validStatus = SubscriptionDAO::getStatusOptions();

        $this->validCountries = [];
        foreach (Locale::getCountries() as $country) {
            $this->validCountries[$country->getAlpha2()] = $country->getLocalName();
        }
        asort($this->validCountries);

        // User is provided and valid
        $this->addCheck(new \PKP\form\validation\FormValidator($this, 'userId', 'required', 'manager.subscriptions.form.userIdRequired'));
        $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'userId', 'required', 'manager.subscriptions.form.userIdValid', fn ($userId) => !!Repo::user()->get($userId)));

        // Subscription status is provided and valid
        $this->addCheck(new \PKP\form\validation\FormValidator($this, 'status', 'required', 'manager.subscriptions.form.statusRequired'));
        $this->addCheck(new \PKP\form\validation\FormValidatorInSet($this, 'status', 'required', 'manager.subscriptions.form.statusValid', array_keys($this->validStatus)));
        // Subscription type is provided
        $this->addCheck(new \PKP\form\validation\FormValidator($this, 'typeId', 'required', 'manager.subscriptions.form.typeIdRequired'));
        // Notify email flag is valid value
        $this->addCheck(new \PKP\form\validation\FormValidatorBoolean($this, 'notifyEmail', 'manager.subscriptions.form.notifyEmailValid'));

        $this->addCheck(new \PKP\form\validation\FormValidatorPost($this));
        $this->addCheck(new \PKP\form\validation\FormValidatorCSRF($this));
    }

    /**
     * Display the form.
     *
     * @copydoc Form::fetch
     *
     * @param null|mixed $template
     */
    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign([
            'subscriptionId' => $this->subscription ? $this->subscription->getId() : null,
            'yearOffsetPast' => Subscription::SUBSCRIPTION_YEAR_OFFSET_PAST,
            'yearOffsetFuture' => Subscription::SUBSCRIPTION_YEAR_OFFSET_FUTURE,
            'validStatus' => $this->validStatus,
            'subscriptionTypes' => $this->subscriptionTypes,
        ]);
        return parent::fetch($request, $template, $display);
    }

    /**
     * Initialize form data from current subscription.
     */
    public function initData()
    {
        if (isset($this->subscription)) {
            $subscription = $this->subscription;
            $this->_data = [
                'status' => $subscription->getStatus(),
                'userId' => $subscription->getUserId(),
                'typeId' => $subscription->getTypeId(),
                'dateStart' => $subscription->getDateStart(),
                'dateEnd' => $subscription->getDateEnd(),
                'membership' => $subscription->getMembership(),
                'referenceNumber' => $subscription->getReferenceNumber(),
                'notes' => $subscription->getNotes()
            ];
        }
    }

    /**
     * Assign form data to user-submitted data.
     */
    public function readInputData()
    {
        $this->readUserVars(['status', 'userId', 'typeId', 'membership', 'referenceNumber', 'notes', 'notifyEmail', 'dateStart', 'dateEnd']);

        // If subscription type requires it, membership is provided
        $subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /** @var SubscriptionTypeDAO $subscriptionTypeDao */
        $needMembership = $subscriptionTypeDao->getSubscriptionTypeMembership($this->getData('typeId'));

        if ($needMembership) {
            $this->addCheck(new \PKP\form\validation\FormValidator($this, 'membership', 'required', 'manager.subscriptions.form.membershipRequired'));
        }

        // If subscription type requires it, start and end dates are provided
        $subscriptionType = $subscriptionTypeDao->getById($this->getData('typeId'));
        $nonExpiring = $subscriptionType->getNonExpiring();

        if (!$nonExpiring) {
            // Start date is provided and is valid
            $this->addCheck(new \PKP\form\validation\FormValidator($this, 'dateStart', 'required', 'manager.subscriptions.form.dateStartRequired'));
            $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'dateStart', 'required', 'manager.subscriptions.form.dateStartValid', function ($dateStart) {
                $dateStartYear = date('Y', strtotime($dateStart));
                $minYear = date('Y') + Subscription::SUBSCRIPTION_YEAR_OFFSET_PAST;
                $maxYear = date('Y') + Subscription::SUBSCRIPTION_YEAR_OFFSET_FUTURE;
                return ($dateStartYear >= $minYear && $dateStartYear <= $maxYear);
            }));
            $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'dateStart', 'required', 'manager.subscriptions.form.dateStartValid', function ($dateStart) {
                $dateStartMonth = date('m', strtotime($dateStart));
                return ($dateStartMonth >= 1 && $dateStartMonth <= 12);
            }));
            $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'dateStart', 'required', 'manager.subscriptions.form.dateStartValid', function ($dateStart) {
                $dateStartDay = date('d', strtotime($dateStart));
                return ($dateStartDay >= 1 && $dateStartDay <= 31);
            }));

            // End date is provided and is valid
            $this->addCheck(new \PKP\form\validation\FormValidator($this, 'dateEnd', 'required', 'manager.subscriptions.form.dateEndRequired'));
            $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'dateEnd', 'required', 'manager.subscriptions.form.dateEndValid', function ($dateEnd) {
                $dateEndYear = date('Y', strtotime($dateEnd));
                $minYear = date('Y') + Subscription::SUBSCRIPTION_YEAR_OFFSET_PAST;
                $maxYear = date('Y') + Subscription::SUBSCRIPTION_YEAR_OFFSET_FUTURE;
                return ($dateEndYear >= $minYear && $dateEndYear <= $maxYear);
            }));
            $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'dateEnd', 'required', 'manager.subscriptions.form.dateEndValid', function ($dateEnd) {
                $dateEndMonth = date('m', strtotime($dateEnd));
                return ($dateEndMonth >= 1 && $dateEndMonth <= 12);
            }));
            $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'dateEnd', 'required', 'manager.subscriptions.form.dateEndValid', function ($dateEnd) {
                $dateEndDay = date('d', strtotime($dateEnd));
                return ($dateEndDay >= 1 && $dateEndDay <= 31);
            }));
        } else {
            // Is non-expiring; ensure that start/end dates weren't entered.
            $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'dateStart', 'optional', 'manager.subscriptions.form.dateStartEmpty', function ($dateStart) {
                return empty($dateStart);
            }));
            $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'dateEnd', 'optional', 'manager.subscriptions.form.dateEndEmpty', function ($dateEnd) {
                return empty($dateEnd);
            }));
        }

        // If notify email is requested, ensure subscription contact name and email exist.
        if ($this->getData('notifyEmail')) {
            $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'notifyEmail', 'optional', 'manager.subscriptions.form.subscriptionContactRequired', function () {
                $request = Application::get()->getRequest();
                $journal = $request->getJournal();
                $subscriptionName = $journal->getData('subscriptionName');
                $subscriptionEmail = $journal->getData('subscriptionEmail');
                return $subscriptionName != '' && $subscriptionEmail != '';
            }));
        }
    }

    /**
     * @copydoc Form::execute
     */
    public function execute(...$functionArgs)
    {
        $request = Application::get()->getRequest();
        $journal = $request->getJournal();
        $subscription = & $this->subscription;

        parent::execute(...$functionArgs);

        $subscription->setJournalId($journal->getId());
        $subscription->setStatus($this->getData('status'));
        $subscription->setUserId($this->getData('userId'));
        $subscription->setTypeId($this->getData('typeId'));
        $subscription->setMembership($this->getData('membership') ? $this->getData('membership') : null);
        $subscription->setReferenceNumber($this->getData('referenceNumber') ? $this->getData('referenceNumber') : null);
        $subscription->setNotes($this->getData('notes') ? $this->getData('notes') : null);

        $subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /** @var SubscriptionTypeDAO $subscriptionTypeDao */
        $subscriptionType = $subscriptionTypeDao->getById($subscription->getTypeId());
        if (!$subscriptionType->getNonExpiring()) {
            $subscription->setDateStart($this->getData('dateStart'));
            $dateEnd = strtotime($this->getData('dateEnd'));
            $subscription->setDateEnd(mktime(23, 59, 59, (int) date('m', $dateEnd), (int) date('d', $dateEnd), (int) date('Y', $dateEnd)));
        }
    }

    /**
     * Internal function to prepare notification email
     */
    protected function _prepareNotificationEmail(): SubscriptionNotify
    {
        $request = Application::get()->getRequest();
        $context = $request->getJournal();
        $user = Repo::user()->get($this->subscription->getUserId());
        $subscriptionEmail = $context->getData('subscriptionEmail');
        $subscriptionName = $context->getData('subscriptionName');

        $subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /** @var SubscriptionTypeDAO $subscriptionTypeDao */
        $subscriptionType = $subscriptionTypeDao->getById($this->subscription->getTypeId(), $context->getId());

        $template = Repo::emailTemplate()->getByKey($context->getId(), SubscriptionNotify::getEmailTemplateKey());
        $mailable = new SubscriptionNotify($context, $this->subscription, $subscriptionType);
        $mailable
            ->recipients([$user])
            ->from($subscriptionEmail, $subscriptionName)
            ->subject($template->getLocalizedData('subject'))
            ->body($template->getLocalizedData('body'));

        return $mailable;
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\subscription\form\SubscriptionForm', '\SubscriptionForm');
}
