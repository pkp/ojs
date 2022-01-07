<?php

/**
 * @file controllers/grid/subscriptions/InstitutionalSubscriptionForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class InstitutionalSubscriptionForm
 * @ingroup subscription
 *
 * @brief Form class for institutional subscription create/edits.
 */

use APP\subscription\form\SubscriptionForm;
use APP\notification\NotificationManager;
use APP\subscription\InstitutionalSubscription;
use APP\subscription\SubscriptionType;
use PKP\notification\PKPNotification;

class InstitutionalSubscriptionForm extends SubscriptionForm
{
    /**
     * Constructor
     *
     * @param PKPRequest $request
     * @param int $subscriptionId leave as default for new subscription
     */
    public function __construct($request, $subscriptionId = null)
    {
        parent::__construct('payments/institutionalSubscriptionForm.tpl', $subscriptionId);

        $subscriptionId = isset($subscriptionId) ? (int) $subscriptionId : null;
        $userId = isset($userId) ? (int) $userId : null;

        $journal = $request->getJournal();
        $journalId = $journal->getId();

        if (isset($subscriptionId)) {
            $subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO');
            if ($subscriptionDao->subscriptionExists($subscriptionId)) {
                $this->subscription = $subscriptionDao->getById($subscriptionId);
            }
        }

        $subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /** @var SubscriptionTypeDAO $subscriptionTypeDao */
        $subscriptionTypeIterator = $subscriptionTypeDao->getByInstitutional($journalId, true);
        $this->subscriptionTypes = [];
        while ($subscriptionType = $subscriptionTypeIterator->next()) {
            $this->subscriptionTypes[$subscriptionType->getId()] = $subscriptionType->getSummaryString();
        }

        if (count($this->subscriptionTypes) == 0) {
            $this->addError('typeId', __('manager.subscriptions.form.typeRequired'));
            $this->addErrorField('typeId');
        }

        // Ensure subscription type is valid
        $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'typeId', 'required', 'manager.subscriptions.form.typeIdValid', function ($typeId) use ($journalId) {
            $subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /** @var SubscriptionTypeDAO $subscriptionTypeDao */
            return $subscriptionTypeDao->subscriptionTypeExistsByTypeId($typeId, $journalId) && $subscriptionTypeDao->getSubscriptionTypeInstitutional($typeId);
        }));

        // Ensure institution name is provided
        $this->addCheck(new \PKP\form\validation\FormValidator($this, 'institutionName', 'required', 'manager.subscriptions.form.institutionNameRequired'));

        // If provided, domain is valid
        $this->addCheck(new \PKP\form\validation\FormValidatorRegExp($this, 'domain', 'optional', 'manager.subscriptions.form.domainValid', '/^' .
                '[A-Z0-9]+([\-_\.][A-Z0-9]+)*' .
                '\.' .
                '[A-Z]{2,4}' .
            '$/i'));
    }

    /**
     * Initialize form data from current subscription.
     */
    public function initData()
    {
        parent::initData();

        if (isset($this->subscription)) {
            $this->_data = array_merge(
                $this->_data,
                [
                    'institutionName' => $this->subscription->getInstitutionName(),
                    'institutionMailingAddress' => $this->subscription->getInstitutionMailingAddress(),
                    'domain' => $this->subscription->getDomain(),
                    'ipRanges' => join($this->subscription->getIPRanges(), "\r\n"),
                ]
            );
        }
    }

    /**
     * Assign form data to user-submitted data.
     */
    public function readInputData()
    {
        parent::readInputData();

        $this->readUserVars(['institutionName', 'institutionMailingAddress', 'domain', 'ipRanges']);

        // Check if IP range has been provided
        $ipRanges = $this->getData('ipRanges');
        $ipRangeProvided = !empty(trim($ipRanges));

        $subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /** @var SubscriptionTypeDAO $subscriptionTypeDao */
        $subscriptionType = $subscriptionTypeDao->getById($this->getData('typeId'));

        // If online or print + online, domain or at least one IP range has been provided
        if ($subscriptionType->getFormat() != SubscriptionType::SUBSCRIPTION_TYPE_FORMAT_PRINT) {
            $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'domain', 'optional', 'manager.subscriptions.form.domainIPRangeRequired', function ($domain) use ($ipRangeProvided) {
                return ($domain != '' || $ipRangeProvided) ? true : false;
            }));
        }

        // If provided ensure IP ranges have IP address format; IP addresses may contain wildcards
        if ($ipRangeProvided) {
            $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'ipRanges', 'required', 'manager.subscriptions.form.ipRangeValid', function ($ipRanges) {
                foreach (explode("\r\n", trim($ipRanges)) as $ipRange) {
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
        $insert = false;
        if (!isset($this->subscription)) {
            $this->subscription = new InstitutionalSubscription();
            $insert = true;
        }

        parent::execute(...$functionArgs);

        $this->subscription->setInstitutionName($this->getData('institutionName'));
        $this->subscription->setInstitutionMailingAddress($this->getData('institutionMailingAddress'));
        $this->subscription->setDomain($this->getData('domain'));

        $ipRanges = $this->getData('ipRanges');
        $ipRanges = explode("\r\n", trim($ipRanges));
        $this->subscription->setIPRanges($ipRanges);

        $institutionalSubscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO'); /** @var InstitutionalSubscriptionDAO $institutionalSubscriptionDao */
        if ($insert) {
            $institutionalSubscriptionDao->insertObject($this->subscription);
        } else {
            $institutionalSubscriptionDao->updateObject($this->subscription);
        }

        // Send notification email
        if ($this->_data['notifyEmail'] == 1) {
            $mail = $this->_prepareNotificationEmail('SUBSCRIPTION_NOTIFY');
            if (!$mail->send()) {
                $notificationMgr = new NotificationManager();
                $request = Application::get()->getRequest();
                $notificationMgr->createTrivialNotification($request->getUser()->getId(), PKPNotification::NOTIFICATION_TYPE_ERROR, ['contents' => __('email.compose.error')]);
            }
        }
    }
}
