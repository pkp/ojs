<?php

/**
 * @file pages/user/UserHandler.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class UserHandler
 *
 * @ingroup pages_user
 *
 * @brief Handle requests for user functions.
 */

namespace APP\pages\user;

use APP\core\Application;
use APP\facades\Repo;
use APP\journal\Journal;
use APP\payment\ojs\OJSPaymentManager;
use APP\subscription\form\UserIndividualSubscriptionForm;
use APP\subscription\form\UserInstitutionalSubscriptionForm;
use APP\subscription\IndividualSubscriptionDAO;
use APP\subscription\InstitutionalSubscriptionDAO;
use APP\subscription\Subscription;
use APP\subscription\SubscriptionTypeDAO;
use APP\template\TemplateManager;
use PKP\db\DAORegistry;
use PKP\pages\user\PKPUserHandler;

class UserHandler extends PKPUserHandler
{
    /**
     * Display subscriptions page
     *
     * @param array $args
     * @param \APP\core\Request $request
     */
    public function subscriptions($args, $request)
    {
        $this->validate(null, $request);

        $journal = $request->getJournal();
        $user = $request->getUser();
        $templateMgr = TemplateManager::getManager($request);
        if (!$journal || !$user || $journal->getData('publishingMode') != Journal::PUBLISHING_MODE_SUBSCRIPTION) {
            $request->redirect(null, 'index');
        }

        $subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /** @var SubscriptionTypeDAO $subscriptionTypeDao */
        $individualSubscriptionTypesExist = $subscriptionTypeDao->subscriptionTypesExistByInstitutional($journal->getId(), false);
        $institutionalSubscriptionTypesExist = $subscriptionTypeDao->subscriptionTypesExistByInstitutional($journal->getId(), true);
        if (!$individualSubscriptionTypesExist && !$institutionalSubscriptionTypesExist) {
            $request->redirect(null, 'index');
        }

        // Subscriptions contact and additional information
        // Get subscriptions and options for current journal
        if ($individualSubscriptionTypesExist) {
            $subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO'); /** @var IndividualSubscriptionDAO $subscriptionDao */
            $userIndividualSubscription = $subscriptionDao->getByUserIdForJournal($user->getId(), $journal->getId());
            $templateMgr->assign('userIndividualSubscription', $userIndividualSubscription);
        }

        if ($institutionalSubscriptionTypesExist) {
            /** @var InstitutionalSubscriptionDAO $subscriptionDao */
            $subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO');
            $userInstitutionalSubscriptions = $subscriptionDao->getByUserIdForJournal($user->getId(), $journal->getId())->toArray();
            $templateMgr->assign('userInstitutionalSubscriptions', $userInstitutionalSubscriptions);
            $institutions = [];
            foreach ($userInstitutionalSubscriptions as $userInstitutionalSubscription) {
                $institution = Repo::institution()->get($userInstitutionalSubscription->getInstitutionId());
                $institutions[$userInstitutionalSubscription->getId()] = $institution;
            }
            $templateMgr->assign('institutions', $institutions);
        }

        $paymentManager = Application::getPaymentManager($journal);

        $this->setupTemplate($request);

        $templateMgr->assign([
            'subscriptionName' => $journal->getData('subscriptionName'),
            'subscriptionEmail' => $journal->getData('subscriptionEmail'),
            'subscriptionPhone' => $journal->getData('subscriptionPhone'),
            'subscriptionMailingAddress' => $journal->getData('subscriptionMailingAddress'),
            'subscriptionAdditionalInformation' => $journal->getLocalizedData('subscriptionAdditionalInformation'),
            'journalTitle' => $journal->getLocalizedName(),
            'journalPath' => $journal->getPath(),
            'individualSubscriptionTypesExist' => $individualSubscriptionTypesExist,
            'institutionalSubscriptionTypesExist' => $institutionalSubscriptionTypesExist,
            'paymentsEnabled' => $paymentManager->isConfigured(),
        ]);
        $templateMgr->display('frontend/pages/userSubscriptions.tpl');
    }

    /**
     * Determine if the journal's setup has been sufficiently completed.
     *
     * @param object $journal
     *
     * @return bool True iff setup is incomplete
     */
    public function _checkIncompleteSetup($journal)
    {
        if ($journal->getLocalizedAcronym() == '' || $journal->getData('contactEmail') == '' ||
           $journal->getData('contactName') == '' || $journal->getLocalizedData('abbreviation') == '') {
            return true;
        } else {
            return false;
        }
    }


    //
    // Payments
    //
    /**
     * Purchase a subscription.
     *
     * @param array $args
     * @param \APP\core\Request $request
     */
    public function purchaseSubscription($args, $request)
    {
        $this->validate(null, $request);
        $journal = $request->getContext();
        if (empty($args) || !$journal || $journal->getData('publishingMode') != Journal::PUBLISHING_MODE_SUBSCRIPTION) {
            $request->redirect(null, 'index');
        }

        $paymentManager = Application::getPaymentManager($journal);
        $acceptSubscriptionPayments = $paymentManager->isConfigured();
        if (!$acceptSubscriptionPayments) {
            $request->redirect(null, 'index');
        }

        $this->setupTemplate($request);
        $user = $request->getUser();

        $institutional = array_shift($args);
        if (!empty($args)) {
            $subscriptionId = (int) array_shift($args);
        }

        if ($institutional == 'institutional') {
            $institutional = true;
            $subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO'); /** @var InstitutionalSubscriptionDAO $subscriptionDao */
        } else {
            $institutional = false;
            $subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO'); /** @var IndividualSubscriptionDAO $subscriptionDao */
        }

        if (isset($subscriptionId)) {
            // Ensure subscription to be updated is for this user
            if (!$subscriptionDao->subscriptionExistsByUser($subscriptionId, $user->getId())) {
                $request->redirect(null, 'index');
            }

            // Ensure subscription can be updated
            $subscription = $subscriptionDao->getById($subscriptionId);
            $subscriptionStatus = $subscription->getStatus();
            $validStatus = [
                Subscription::SUBSCRIPTION_STATUS_ACTIVE,
                Subscription::SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT,
                Subscription::SUBSCRIPTION_STATUS_AWAITING_MANUAL_PAYMENT
            ];

            if (!in_array($subscriptionStatus, $validStatus)) {
                $request->redirect(null, 'index');
            }

            if ($institutional) {
                $subscriptionForm = new UserInstitutionalSubscriptionForm($request, $user->getId(), $subscriptionId);
            } else {
                $subscriptionForm = new UserIndividualSubscriptionForm($request, $user->getId(), $subscriptionId);
            }
        } else {
            if ($institutional) {
                $subscriptionForm = new UserInstitutionalSubscriptionForm($request, $user->getId());
            } else {
                // Ensure user does not already have an individual subscription
                if ($subscriptionDao->subscriptionExistsByUserForJournal($user->getId(), $journal->getId())) {
                    $request->redirect(null, 'index');
                }
                $subscriptionForm = new UserIndividualSubscriptionForm($request, $user->getId());
            }
        }

        $subscriptionForm->initData();
        $subscriptionForm->display();
    }

    /**
     * Pay for a subscription purchase.
     *
     * @param array $args
     * @param \APP\core\Request $request
     */
    public function payPurchaseSubscription($args, $request)
    {
        $this->validate(null, $request);

        if (empty($args)) {
            $request->redirect(null, 'index');
        }

        $journal = $request->getContext();
        if (!$journal) {
            $request->redirect(null, 'index');
        }
        if ($journal->getData('publishingMode') != Journal::PUBLISHING_MODE_SUBSCRIPTION) {
            $request->redirect(null, 'index');
        }

        $paymentManager = Application::getPaymentManager($journal);
        $acceptSubscriptionPayments = $paymentManager->isConfigured();
        if (!$acceptSubscriptionPayments) {
            $request->redirect(null, 'index');
        }

        $this->setupTemplate($request);
        $user = $request->getUser();

        $institutional = array_shift($args);
        if (!empty($args)) {
            $subscriptionId = (int) array_shift($args);
        }

        if ($institutional == 'institutional') {
            $institutional = true;
            $subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO'); /** @var InstitutionalSubscriptionDAO $subscriptionDao */
        } else {
            $institutional = false;
            $subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO'); /** @var IndividualSubscriptionDAO $subscriptionDao */
        }

        if (isset($subscriptionId)) {
            // Ensure subscription to be updated is for this user
            if (!$subscriptionDao->subscriptionExistsByUser($subscriptionId, $user->getId())) {
                $request->redirect(null, 'index');
            }

            // Ensure subscription can be updated
            $subscription = $subscriptionDao->getById($subscriptionId);
            $subscriptionStatus = $subscription->getStatus();
            $validStatus = [
                Subscription::SUBSCRIPTION_STATUS_ACTIVE,
                Subscription::SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT,
                Subscription::SUBSCRIPTION_STATUS_AWAITING_MANUAL_PAYMENT
            ];

            if (!in_array($subscriptionStatus, $validStatus)) {
                $request->redirect(null, 'index');
            }

            if ($institutional) {
                $subscriptionForm = new UserInstitutionalSubscriptionForm($request, $user->getId(), $subscriptionId);
            } else {
                $subscriptionForm = new UserIndividualSubscriptionForm($request, $user->getId(), $subscriptionId);
            }
        } else {
            if ($institutional) {
                $subscriptionForm = new UserInstitutionalSubscriptionForm($request, $user->getId());
            } else {
                // Ensure user does not already have an individual subscription
                if ($subscriptionDao->subscriptionExistsByUserForJournal($user->getId(), $journal->getId())) {
                    $request->redirect(null, 'index');
                }
                $subscriptionForm = new UserIndividualSubscriptionForm($request, $user->getId());
            }
        }

        $subscriptionForm->readInputData();

        // Check for any special cases before trying to save
        if ($request->getUserVar('addIpRange')) {
            $editData = true;
            $ipRanges = $subscriptionForm->getData('ipRanges');
            $ipRanges[] = '';
            $subscriptionForm->setData('ipRanges', $ipRanges);
        } elseif (($delIpRange = $request->getUserVar('delIpRange')) && count($delIpRange) == 1) {
            $editData = true;
            [$delIpRange] = array_keys($delIpRange);
            $delIpRange = (int) $delIpRange;
            $ipRanges = $subscriptionForm->getData('ipRanges');
            array_splice($ipRanges, $delIpRange, 1);
            $subscriptionForm->setData('ipRanges', $ipRanges);
        }

        if (isset($editData)) {
            $subscriptionForm->display();
        } else {
            if ($subscriptionForm->validate()) {
                $subscriptionForm->execute();
            } else {
                $subscriptionForm->display();
            }
        }
    }

    /**
     * Complete the purchase subscription process.
     *
     * @param array $args
     * @param \APP\core\Request $request
     */
    public function completePurchaseSubscription($args, $request)
    {
        $this->validate(null, $request);
        $journal = $request->getContext();
        if (!$journal || count($args) != 2 || $journal->getData('publishingMode') != Journal::PUBLISHING_MODE_SUBSCRIPTION) {
            $request->redirect(null, 'index');
        }

        $paymentManager = Application::getPaymentManager($journal);
        $acceptSubscriptionPayments = $paymentManager->isConfigured();
        if (!$acceptSubscriptionPayments) {
            $request->redirect(null, 'index');
        }

        $this->setupTemplate($request);
        $user = $request->getUser();
        $institutional = array_shift($args);
        $subscriptionId = (int) array_shift($args);

        if ($institutional == 'institutional') {
            $subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO'); /** @var InstitutionalSubscriptionDAO $subscriptionDao */
        } else {
            $subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO'); /** @var IndividualSubscriptionDAO $subscriptionDao */
        }

        if (!$subscriptionDao->subscriptionExistsByUser($subscriptionId, $user->getId())) {
            $request->redirect(null, 'index');
        }

        $subscription = $subscriptionDao->getById($subscriptionId);
        $subscriptionStatus = $subscription->getStatus();
        $validStatus = [Subscription::SUBSCRIPTION_STATUS_ACTIVE, Subscription::SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT];

        if (!in_array($subscriptionStatus, $validStatus)) {
            $request->redirect(null, 'index');
        }

        $subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /** @var SubscriptionTypeDAO $subscriptionTypeDao */
        $subscriptionType = $subscriptionTypeDao->getById($subscription->getTypeId());

        $queuedPayment = $paymentManager->createQueuedPayment($request, OJSPaymentManager::PAYMENT_TYPE_PURCHASE_SUBSCRIPTION, $user->getId(), $subscriptionId, $subscriptionType->getCost(), $subscriptionType->getCurrencyCodeAlpha());
        $paymentManager->queuePayment($queuedPayment);

        $paymentForm = $paymentManager->getPaymentForm($queuedPayment);
        $paymentForm->display($request);
    }

    /**
     * Pay the "renew subscription" fee.
     *
     * @param array $args
     * @param \APP\core\Request $request
     */
    public function payRenewSubscription($args, $request)
    {
        $this->validate(null, $request);
        $journal = $request->getContext();
        if (count($args) != 2 || !$journal || $journal->getData('publishingMode') != Journal::PUBLISHING_MODE_SUBSCRIPTION) {
            $request->redirect(null, 'index');
        }

        $paymentManager = Application::getPaymentManager($journal);
        $acceptSubscriptionPayments = $paymentManager->isConfigured();
        if (!$acceptSubscriptionPayments) {
            $request->redirect(null, 'index');
        }

        $this->setupTemplate($request);
        $user = $request->getUser();
        $institutional = array_shift($args);
        $subscriptionId = (int) array_shift($args);

        if ($institutional == 'institutional') {
            $subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO'); /** @var InstitutionalSubscriptionDAO $subscriptionDao */
        } else {
            $subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO'); /** @var IndividualSubscriptionDAO $subscriptionDao */
        }

        if (!$subscriptionDao->subscriptionExistsByUser($subscriptionId, $user->getId())) {
            $request->redirect(null, 'index');
        }

        $subscription = $subscriptionDao->getById($subscriptionId);

        if ($subscription->isNonExpiring()) {
            $request->redirect(null, 'index');
        }

        $subscriptionStatus = $subscription->getStatus();
        $validStatus = [
            Subscription::SUBSCRIPTION_STATUS_ACTIVE,
            Subscription::SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT,
            Subscription::SUBSCRIPTION_STATUS_AWAITING_MANUAL_PAYMENT
        ];

        if (!in_array($subscriptionStatus, $validStatus)) {
            $request->redirect(null, 'index');
        }

        $subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /** @var SubscriptionTypeDAO $subscriptionTypeDao */
        $subscriptionType = $subscriptionTypeDao->getById($subscription->getTypeId());

        $queuedPayment = $paymentManager->createQueuedPayment($request, OJSPaymentManager::PAYMENT_TYPE_RENEW_SUBSCRIPTION, $user->getId(), $subscriptionId, $subscriptionType->getCost(), $subscriptionType->getCurrencyCodeAlpha());
        $paymentManager->queuePayment($queuedPayment);

        $paymentForm = $paymentManager->getPaymentForm($queuedPayment);
        $paymentForm->display($request);
    }

    /**
     * Pay for a membership.
     *
     * @param array $args
     * @param \APP\core\Request $request
     */
    public function payMembership($args, $request)
    {
        $this->validate(null, $request);
        $this->setupTemplate($request);
        $journal = $request->getContext();
        $user = $request->getUser();

        $paymentManager = Application::getPaymentManager($journal);

        $queuedPayment = $paymentManager->createQueuedPayment($request, OJSPaymentManager::PAYMENT_TYPE_MEMBERSHIP, $user->getId(), null, $journal->getData('membershipFee'));
        $paymentManager->queuePayment($queuedPayment);

        $paymentForm = $paymentManager->getPaymentForm($queuedPayment);
        $paymentForm->display($request);
    }
}
