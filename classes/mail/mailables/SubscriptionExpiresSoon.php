<?php

/**
 * @file classes/mail/mailables/SubscriptionExpiresSoon.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionNotify
 *
 * @brief Email sent automatically to notify a subscriber that their subscription expires soon
 */

namespace APP\mail\mailables;

use APP\journal\Journal;
use APP\mail\variables\SubscriptionEmailVariable;
use APP\subscription\Subscription;
use PKP\mail\Mailable;
use PKP\mail\traits\Configurable;
use PKP\mail\traits\Recipient;
use PKP\security\Role;

class SubscriptionExpiresSoon extends Mailable
{
    use Configurable;
    use Recipient;

    protected static ?string $name = 'mailable.SubscriptionExpiresSoon.name';
    protected static ?string $description = 'mailable.SubscriptionExpiresSoon.description';
    protected static ?string $emailTemplateKey = 'SUBSCRIPTION_BEFORE_EXPIRY';
    protected static array $groupIds = [self::GROUP_OTHER];
    protected static array $toRoleIds = [Role::ROLE_ID_READER];

    public function __construct(Journal $journal, Subscription $subscription)
    {
        parent::__construct(func_get_args());
    }

    protected static function templateVariablesMap(): array
    {
        $map = parent::templateVariablesMap();
        $map[Subscription::class] = SubscriptionEmailVariable::class;
        return $map;
    }
}
