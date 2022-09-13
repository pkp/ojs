<?php

/**
 * @file classes/mail/mailables/SubscriptionPurchaseIndividual.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionPurchaseIndividual
 *
 * @brief Email sent automatically to notify a subscription manager about new subscription
 */

namespace APP\mail\mailables;

use APP\journal\Journal;
use APP\mail\traits\SubscriptionTypeVariables;
use APP\mail\variables\SubscriptionEmailVariable;
use APP\subscription\IndividualSubscription;
use APP\subscription\Subscription;
use APP\subscription\SubscriptionType;
use PKP\mail\Mailable;
use PKP\mail\traits\Configurable;
use PKP\mail\traits\Sender;
use PKP\security\Role;

class SubscriptionPurchaseIndividual extends Mailable
{
    use Configurable;
    use Sender;
    use SubscriptionTypeVariables;

    protected static ?string $name = 'mailable.subscriptionPurchaseIndividual.name';
    protected static ?string $description = 'mailable.subscriptionPurchaseIndividual.description';
    protected static ?string $emailTemplateKey = 'SUBSCRIPTION_PURCHASE_INDL';
    protected static array $groupIds = [self::GROUP_OTHER];
    protected static array $fromRoleIds = [Role::ROLE_ID_READER];
    protected static array $toRoleIds = [Role::ROLE_ID_SUBSCRIPTION_MANAGER];

    public function __construct(Journal $context, IndividualSubscription $subscription, SubscriptionType $subscriptionType)
    {
        parent::__construct([$context, $subscription]);
        $this->setupSubscriptionTypeVariables($subscriptionType, $context);
    }

    /**
     * Setup subscription related variables
     */
    protected static function templateVariablesMap(): array
    {
        $map = parent::templateVariablesMap();
        $map[Subscription::class] = SubscriptionEmailVariable::class;
        return $map;
    }

    /**
     * Description for subscription type related variables
     */
    public static function getDataDescriptions(): array
    {
        $variables = parent::getDataDescriptions();
        return static::addSubscriptionTypeVariablesDescription($variables);
    }
}
