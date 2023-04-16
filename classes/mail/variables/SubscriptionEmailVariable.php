<?php

/**
 * @file classes/mail/variables/SubscriptionEmailVariable.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionEmailVariable
 *
 * @ingroup mail_variables
 *
 * @brief Represents variables associated with a subscription
 */

namespace APP\mail\variables;

use APP\facades\Repo;
use APP\journal\Journal;
use APP\subscription\Subscription;
use PKP\core\PKPString;
use PKP\mail\Mailable;
use PKP\mail\variables\Variable;
use PKP\user\User;

class SubscriptionEmailVariable extends Variable
{
    public const SUBSCRIBER_DETAILS = 'subscriberDetails';
    public const SUBSCRIPTION_SIGNATURE = 'subscriptionSignature';
    public const EXPIRY_DATE = 'expiryDate';
    public const MEMBERSHIP = 'membership';

    protected User $subscriber;
    protected Subscription $subscription;

    public function __construct(Subscription $subscription, Mailable $mailable)
    {
        parent::__construct($mailable);

        $this->subscriber = Repo::user()->get($subscription->getUserId());
        $this->subscription = $subscription;
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
            self::EXPIRY_DATE => __('emailTemplate.variable.subscription.expiryDate'),
            self::MEMBERSHIP => __('emailTemplate.variable.subscription.membership'),
        ];
    }

    /**
     * @copydoc Variable::values()
     */
    public function values(string $locale): array
    {
        $context = $this->getContext();
        return
        [
            self::SUBSCRIBER_DETAILS => PKPString::stripUnsafeHtml($this->subscriber->getSignature($locale) ?? ''),
            self::SUBSCRIPTION_SIGNATURE => $this->getSubscriptionSignature($context),
            self::EXPIRY_DATE => $this->subscription->getDateEnd(),
            self::MEMBERSHIP => htmlspecialchars($this->subscription->getMembership()),
        ];
    }

    /**
     * Subscription signature consisting of contact details of the person responsible for subscriptions included in the
     * context's Subscription Policies form, Subscription Manager section
     */
    protected function getSubscriptionSignature(Journal $context): string
    {
        $subscriptionName = htmlspecialchars($context->getData('subscriptionName'));
        $subscriptionEmail = htmlspecialchars($context->getData('subscriptionEmail'));
        $subscriptionPhone = htmlspecialchars($context->getData('subscriptionPhone'));
        $subscriptionMailingAddress = PKPString::stripUnsafeHtml($context->getData('subscriptionMailingAddress'));
        $subscriptionContactSignature = $subscriptionName;

        if ($subscriptionMailingAddress != '') {
            $subscriptionContactSignature .= "\n" . $subscriptionMailingAddress;
        }
        if ($subscriptionPhone != '') {
            $subscriptionContactSignature .= "\n" . __('user.phone') . ': ' . $subscriptionPhone;
        }

        return $subscriptionContactSignature . "\n" . __('user.email') . ': ' . $subscriptionEmail;
    }
}
