<?php

/**
 * @file classes/mail/variables/SubscriptionEmailVariable.inc.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionEmailVariable
 * @ingroup mail_variables
 *
 * @brief Represents variables associated with a subscription
 */

namespace APP\mail\variables;

use APP\core\Application;
use APP\facades\Repo;
use APP\journal\Journal;
use APP\journal\JournalDAO;
use APP\subscription\Subscription;
use APP\subscription\SubscriptionType;
use APP\subscription\SubscriptionTypeDAO;
use PKP\db\DAORegistry;
use PKP\mail\variables\Variable;
use PKP\user\User;

class SubscriptionEmailVariable extends Variable
{
    public const SUBSCRIBER_DETAILS = 'subscriberDetails';
    public const SUBSCRIPTION_SIGNATURE = 'subscriptionSignature';
    public const SUBSCRIPTION_URL = 'subscriptionUrl';
    public const EXPIRE_DATE = 'expireDate';
    public const SUBSCRIPTION_TYPE = 'subscriptionType';
    public const MEMBERSHIP = 'membership';

    protected User $subscriber;

    protected Subscription $subscription;

    protected Journal $context;

    protected SubscriptionType $subscriptionType;

    public function __construct(Subscription $subscription)
    {
        $this->subscriber = Repo::user()->get($subscription->getUserId());
        $this->subscription = $subscription;

        /** @var JournalDAO $journalDao */
        $contextDao = DAORegistry::getDAO('JournalDAO');
        $this->context = $contextDao->getById($this->subscription->getData('journalId'));

        /** @var SubscriptionTypeDAO $subscriptionTypeDao */
        $subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO');
        $this->subscriptionType = $subscriptionTypeDao->getById($subscription->getTypeId(), $this->context->getId());
    }

    /**
     * @copydoc Variable::descriptions()
     */
    public static function descriptions(): array
    {
        return
        [
            self::SUBSCRIBER_DETAILS => __('emailTemplate.variable.subscription.subscriberDetails'),
            self::SUBSCRIPTION_SIGNATURE => __('emailTemplate.variable.subscription.subscriptionSignature'),
            self::SUBSCRIPTION_URL => __('emailTemplate.variable.subscription.subscriptionUrl'),
            self::EXPIRE_DATE => __('emailTemplate.variable.subscription.expireDate'),
            self::SUBSCRIPTION_TYPE => __('emailTemplate.variable.subscription.subscriptionType'),
            self::MEMBERSHIP => __('emailTemplate.variable.subscription.membership'),
        ];
    }

    /**
     * @copydoc Variable::values()
     */
    public function values(string $locale): array
    {
        return
        [
            self::SUBSCRIBER_DETAILS => $this->subscriber->getSignature() ?? '',
            self::SUBSCRIPTION_SIGNATURE => $this->getSubscriptionSignature(),
            self::SUBSCRIPTION_URL => $this->getSubscriptionUrl(),
            self::EXPIRE_DATE => $this->subscription->getDateEnd(),
            self::SUBSCRIPTION_TYPE => $this->subscriptionType->getSummaryString(),
            self::MEMBERSHIP => $this->subscription->getMembership(),
        ];
    }

    /**
     * Subscription signature consisting of contact details of the person responsible for subscriptions included in the
     * context's Subscription Policies form, Subscription Manager section
     */
    protected function getSubscriptionSignature(): string
    {
        $subscriptionName = $this->context->getData('subscriptionName');
        $subscriptionEmail = $this->context->getData('subscriptionEmail');
        $subscriptionPhone = $this->context->getData('subscriptionPhone');
        $subscriptionMailingAddress = $this->context->getData('subscriptionMailingAddress');
        $subscriptionContactSignature = $subscriptionName;

        if ($subscriptionMailingAddress != '') {
            $subscriptionContactSignature .= "\n" . $subscriptionMailingAddress;
        }
        if ($subscriptionPhone != '') {
            $subscriptionContactSignature .= "\n" . __('user.phone') . ': ' . $subscriptionPhone;
        }

        return $subscriptionContactSignature . "\n" . __('user.email') . ': ' . $subscriptionEmail;
    }

    protected function getSubscriptionUrl(): string
    {
        $application = Application::get();
        $request = $application->getRequest();
        $dispatcher = $application->getDispatcher();

        return $dispatcher->url(
            $request,
            Application::ROUTE_PAGE,
            $this->context->getData('path'),
            'payments',
            null,
            null,
            null,
            $this->subscriptionType->getInstitutional() ? 'institutional' : 'individual',
        );
    }
}
