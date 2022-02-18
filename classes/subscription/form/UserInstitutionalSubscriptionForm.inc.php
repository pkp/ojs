<?php

/**
 * @file classes/subscription/form/UserInstitutionalSubscriptionForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class UserInstitutionalSubscriptionForm
 * @ingroup subscription
 *
 * @brief Form class for user purchase of institutional subscription.
 */

namespace APP\subscription\form;

use APP\payment\ojs\OJSPaymentManager;
use APP\subscription\InstitutionalSubscription;
use APP\subscription\Subscription;
use APP\template\TemplateManager;
use PKP\form\Form;

class UserInstitutionalSubscriptionForm extends Form
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
        parent::__construct('frontend/pages/purchaseInstitutionalSubscription.tpl');

        $this->userId = isset($userId) ? (int) $userId : null;
        $this->subscription = null;
        $this->request = $request;

        $subscriptionId = isset($subscriptionId) ? (int) $subscriptionId : null;

        if (isset($subscriptionId)) {
            $subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO'); /** @var InstitutionalSubscriptionDAO $subscriptionDao */
            if ($subscriptionDao->subscriptionExists($subscriptionId)) {
                $this->subscription = $subscriptionDao->getById($subscriptionId);
            }
        }

        $journal = $this->request->getJournal();
        $journalId = $journal->getId();

        $subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /** @var SubscriptionTypeDAO $subscriptionTypeDao */
        $subscriptionTypes = $subscriptionTypeDao->getByInstitutional($journalId, true, false);
        $this->subscriptionTypes = $subscriptionTypes->toArray();

        // Ensure subscription type is valid
        $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'typeId', 'required', 'user.subscriptions.form.typeIdValid', function ($typeId) use ($journalId) {
            $subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /** @var SubscriptionTypeDAO $subscriptionTypeDao */
            return $subscriptionTypeDao->subscriptionTypeExistsByTypeId($typeId, $journalId) && $subscriptionTypeDao->getSubscriptionTypeInstitutional($typeId) && !$subscriptionTypeDao->getSubscriptionTypeDisablePublicDisplay($typeId);
        }));

        // Ensure institution name is provided
        $this->addCheck(new \PKP\form\validation\FormValidator($this, 'institutionName', 'required', 'user.subscriptions.form.institutionNameRequired'));

        // If provided, domain is valid
        $this->addCheck(new \PKP\form\validation\FormValidatorRegExp($this, 'domain', 'optional', 'user.subscriptions.form.domainValid', '/^' .
                '[A-Z0-9]+([\-_\.][A-Z0-9]+)*' .
                '\.' .
                '[A-Z]{2,4}' .
            '$/i'));

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
                'institutionName' => $subscription->getInstitutionName(),
                'institutionMailingAddress' => $subscription->getInstitutionMailingAddress(),
                'domain' => $subscription->getDomain(),
                'ipRanges' => $subscription->getIPRangesString()
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
            'subscriptionTypes' => $this->subscriptionTypes,
        ]);
        parent::display($request, $template);
    }

    /**
     * Assign form data to user-submitted data.
     */
    public function readInputData()
    {
        $this->readUserVars(['typeId', 'membership', 'institutionName', 'institutionMailingAddress', 'domain', 'ipRanges']);

        // If subscription type requires it, membership is provided
        $subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /** @var SubscriptionTypeDAO $subscriptionTypeDao */
        $needMembership = $subscriptionTypeDao->getSubscriptionTypeMembership($this->getData('typeId'));

        if ($needMembership) {
            $this->addCheck(new \PKP\form\validation\FormValidator($this, 'membership', 'required', 'user.subscriptions.form.membershipRequired'));
        }

        // Check if IP range has been provided
        $ipRanges = PKPString::regexp_split('/\s+/', trim($this->getData('ipRanges')));
        $ipRangeProvided = false;
        foreach ($ipRanges as $ipRange) {
            if ($ipRange != '') {
                $ipRangeProvided = true;
                break;
            }
        }

        // Domain or at least one IP range has been provided
        $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'domain', 'required', 'user.subscriptions.form.domainIPRangeRequired', function ($domain) use ($ipRangeProvided) {
            return ($domain != '' || $ipRangeProvided) ? true : false;
        }));

        // If provided ensure IP ranges have IP address format; IP addresses may contain wildcards
        if ($ipRangeProvided) {
            $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'ipRanges', 'required', 'manager.subscriptions.form.ipRangeValid', function ($ipRanges) {
                foreach (PKPString::regexp_split('/\s+/', trim($ipRanges)) as $ipRange) {
                    if (!PKPString::regexp_match(
                        '/^' .
                    // IP4 address (with or w/o wildcards) or IP4 address range (with or w/o wildcards) or CIDR IP4 address
                    '((([0-9]|[1-9][0-9]|[1][0-9]{2}|[2][0-4][0-9]|[2][5][0-5]|[' . InstitutionalSubscription::SUBSCRIPTION_IP_RANGE_WILDCARD . '])([.]([0-9]|[1-9][0-9]|[1][0-9]{2}|[2][0-4][0-9]|[2][5][0-5]|[' . InstitutionalSubscription::SUBSCRIPTION_IP_RANGE_WILDCARD . '])){3}((\s)*[' . InstitutionalSubscription::SUBSCRIPTION_IP_RANGE_RANGE . '](\s)*([0-9]|[1-9][0-9]|[1][0-9]{2}|[2][0-4][0-9]|[2][5][0-5]|[' . InstitutionalSubscription::SUBSCRIPTION_IP_RANGE_WILDCARD . '])([.]([0-9]|[1-9][0-9]|[1][0-9]{2}|[2][0-4][0-9]|[2][5][0-5]|[' . InstitutionalSubscription::SUBSCRIPTION_IP_RANGE_WILDCARD . '])){3}){0,1})|(([0-9]|[1-9][0-9]|[1][0-9]{2}|[2][0-4][0-9]|[2][5][0-5])([.]([0-9]|[1-9][0-9]|[1][0-9]{2}|[2][0-4][0-9]|[2][5][0-5])){3}([\/](([3][0-2]{0,1})|([1-2]{0,1}[0-9])))))' .
                    '$/i',
                        trim($ipRange)
                    )
                ) {
                        return false;
                    }
                }
                return true;
            }));
        }
    }

    /**
     * @copydoc Form::execute()
     */
    public function execute(...$functionArgs)
    {
        $journal = $this->request->getJournal();
        $journalId = $journal->getId();
        $typeId = $this->getData('typeId');
        $subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /** @var SubscriptionTypeDAO $subscriptionTypeDao */
        $institutionalSubscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO'); /** @var InstitutionalSubscriptionDAO $institutionalSubscriptionDao */
        $subscriptionType = $subscriptionTypeDao->getById($typeId);
        $nonExpiring = $subscriptionType->getNonExpiring();
        $today = date('Y-m-d');

        if (!isset($this->subscription)) {
            $subscription = $institutionalSubscriptionDao->newDataObject();
            $subscription->setJournalId($journalId);
            $subscription->setUserId($this->userId);
            $subscription->setReferenceNumber(null);
            $subscription->setNotes(null);
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
        $subscription->setInstitutionName($this->getData('institutionName'));
        $subscription->setInstitutionMailingAddress($this->getData('institutionMailingAddress'));
        $subscription->setDomain($this->getData('domain'));
        $subscription->setIPRanges(PKPString::regexp_split('/\s+/', trim($this->getData('ipRanges'))));

        if ($subscription->getId()) {
            $institutionalSubscriptionDao->updateObject($subscription);
        } else {
            $institutionalSubscriptionDao->insertObject($subscription);
        }

        $queuedPayment = $paymentManager->createQueuedPayment($this->request, OJSPaymentManager::PAYMENT_TYPE_PURCHASE_SUBSCRIPTION, $this->userId, $subscription->getId(), $subscriptionType->getCost(), $subscriptionType->getCurrencyCodeAlpha());
        $paymentManager->queuePayment($queuedPayment);

        $paymentForm = $paymentManager->getPaymentForm($queuedPayment);
        $paymentForm->display($this->request);
        parent::execute(...$functionArgs);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\subscription\form\UserInstitutionalSubscriptionForm', '\UserInstitutionalSubscriptionForm');
}
