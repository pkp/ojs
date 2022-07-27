<?php

/**
 * @file classes/subscription/form/UserIndividualSubscriptionForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class UserIndividualSubscriptionForm
 * @ingroup subscription
 *
 * @brief Form class for user purchase of individual subscription.
 */

namespace APP\subscription\form;

use APP\payment\ojs\OJSPaymentManager;
use APP\subscription\Subscription;
use APP\template\TemplateManager;
use PKP\form\Form;

class UserIndividualSubscriptionForm extends Form
{
    /** @var PKPRequest */
    public $request;

    /** @var userId int the user associated with the subscription */
    public $userId;

    /** @var subscription the subscription being purchased */
    public $subscription;

    /** @var subscriptionTypes Array subscription types */
    public $subscriptionTypes;

    /**
     * Constructor
     *
     * @param PKPRequest $request
     * @param int $userId
     * @param int $subscriptionId
     */
    public function __construct($request, $userId = null, $subscriptionId = null)
    {
        parent::__construct('frontend/pages/purchaseIndividualSubscription.tpl');

        $this->userId = isset($userId) ? (int) $userId : null;
        $this->subscription = null;
        $this->request = $request;

        $subscriptionId = isset($subscriptionId) ? (int) $subscriptionId : null;

        if (isset($subscriptionId)) {
            $subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO'); /** @var IndividualSubscriptionDAO $subscriptionDao */
            if ($subscriptionDao->subscriptionExists($subscriptionId)) {
                $this->subscription = $subscriptionDao->getById($subscriptionId);
            }
        }

        $journal = $this->request->getJournal();
        $journalId = $journal->getId();

        $subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /** @var SubscriptionTypeDAO $subscriptionTypeDao */
        $subscriptionTypes = $subscriptionTypeDao->getByInstitutional($journalId, false, false);
        $this->subscriptionTypes = $subscriptionTypes->toAssociativeArray();

        // Ensure subscription type is valid
        $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'typeId', 'required', 'user.subscriptions.form.typeIdValid', function ($typeId) use ($journalId) {
            $subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /** @var SubscriptionTypeDAO $subscriptionTypeDao */
            return $subscriptionTypeDao->subscriptionTypeExistsByTypeId($typeId, $journalId) && !$subscriptionTypeDao->getSubscriptionTypeInstitutional($typeId) && !$subscriptionTypeDao->getSubscriptionTypeDisablePublicDisplay($typeId);
        }));

        // Ensure that user does not already have a subscription for this journal
        if (!isset($subscriptionId)) {
            $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'userId', 'required', 'user.subscriptions.form.subscriptionExists', [DAORegistry::getDAO('IndividualSubscriptionDAO'), 'subscriptionExistsByUserForJournal'], [$journalId], true));
        } else {
            $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'userId', 'required', 'user.subscriptions.form.subscriptionExists', function ($userId) use ($journalId, $subscriptionId) {
                $subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO'); /** @var IndividualSubscriptionDAO $subscriptionDao */
                $checkId = $subscriptionDao->getByUserIdForJournal($userId, $journalId);
                return ($checkId == 0 || $checkId == $subscriptionId) ? true : false;
            }));
        }

        $this->addCheck(new \PKP\form\validation\FormValidatorPost($this));
        $this->addCheck(new \PKP\form\validation\FormValidatorCSRF($this));
    }

    /**
     * Initialize form data from current subscription.
     */
    public function initData()
    {
        if (isset($this->subscription)) {
            $subscription = $this->subscription;

            $this->_data = [
                'typeId' => $subscription->getTypeId(),
                'membership' => $subscription->getMembership()
            ];
        }
    }

    /**
     * @copydoc Form::display
     *
     * @param null|mixed $request
     * @param null|mixed $template
     */
    public function display($request = null, $template = null)
    {
        if (is_null($request)) {
            $request = $this->request;
        }
        $templateMgr = TemplateManager::getManager($this->request);
        $templateMgr->assign([
            'subscriptionId' => $this->subscription ? $this->subscription->getId() : null,
            'subscriptionTypes' => array_map(
                function ($subscriptionType) {
                    return $subscriptionType->getLocalizedName() . ' (' . $subscriptionType->getCost() . ' ' . $subscriptionType->getCurrencyCodeAlpha() . ')';
                },
                $this->subscriptionTypes
            ),
        ]);
        parent::display($request, $template);
    }

    /**
     * Assign form data to user-submitted data.
     */
    public function readInputData()
    {
        $this->readUserVars(['typeId', 'membership']);

        // If subscription type requires it, membership is provided
        $subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /** @var SubscriptionTypeDAO $subscriptionTypeDao */
        $needMembership = $subscriptionTypeDao->getSubscriptionTypeMembership($this->getData('typeId'));

        if ($needMembership) {
            $this->addCheck(new \PKP\form\validation\FormValidator($this, 'membership', 'required', 'user.subscriptions.form.membershipRequired'));
        }
    }

    /**
     * @copydoc Form::execute
     */
    public function execute(...$functionArgs)
    {
        $journal = $this->request->getJournal();
        $journalId = $journal->getId();
        $typeId = $this->getData('typeId');
        $individualSubscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO'); /** @var IndividualSubscriptionDAO $individualSubscriptionDao */
        $subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /** @var SubscriptionTypeDAO $subscriptionTypeDao */
        $subscriptionType = $subscriptionTypeDao->getById($typeId, $journalId);
        $nonExpiring = $subscriptionType->getNonExpiring();
        $today = date('Y-m-d');
        $insert = false;

        parent::execute(...$functionArgs);

        if (!isset($this->subscription)) {
            $subscription = $individualSubscriptionDao->newDataObject();
            $subscription->setJournalId($journalId);
            $subscription->setUserId($this->userId);
            $subscription->setReferenceNumber(null);
            $subscription->setNotes(null);

            $insert = true;
        } else {
            $subscription = $this->subscription;
        }

        $paymentManager = Application::getPaymentManager($journal);
        $paymentPlugin = $paymentManager->getPaymentPlugin();

        if ($paymentPlugin->getName() == 'ManualPayment') {
            $subscription->setStatus(Subscription::SUBSCRIPTION_STATUS_AWAITING_MANUAL_PAYMENT);
        } else {
            $subscription->setStatus(Subscription::SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT);
        }

        $subscription->setTypeId($typeId);
        $subscription->setMembership($this->getData('membership') ? $this->getData('membership') : null);
        $subscription->setDateStart($nonExpiring ? null : $today);
        $subscription->setDateEnd($nonExpiring ? null : $today);

        if ($subscription->getId()) {
            $individualSubscriptionDao->updateObject($subscription);
        } else {
            $individualSubscriptionDao->insertObject($subscription);
        }

        $queuedPayment = $paymentManager->createQueuedPayment($this->request, OJSPaymentManager::PAYMENT_TYPE_PURCHASE_SUBSCRIPTION, $this->userId, $subscription->getId(), $subscriptionType->getCost(), $subscriptionType->getCurrencyCodeAlpha());
        $paymentManager->queuePayment($queuedPayment);

        $paymentForm = $paymentManager->getPaymentForm($queuedPayment);
        $paymentForm->display($this->request);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\subscription\form\UserIndividualSubscriptionForm', '\UserIndividualSubscriptionForm');
}
