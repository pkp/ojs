<?php

/**
 * @file mail/traits/SubscriptionInstitutional.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionInstitutional
 *
 * @ingroup mail_traits
 *
 * @brief Mailable trait to set institutional subscription variables
 */

namespace APP\mail\traits;

use APP\subscription\InstitutionalSubscription;
use PKP\institution\Institution;

trait SubscriptionInstitutional
{
    abstract public function addData(array $variables);

    protected static string $institutionName = 'institutionName';
    protected static string $institutionMailingAddress = 'institutionMailingAddress';
    protected static string $domain = 'domain';
    protected static string $ipRanges = 'ipRanges';

    protected function setupInstitutionalVariables(InstitutionalSubscription $subscription, Institution $institution): void
    {
        $this->addData([
            static::$institutionName => $institution->getLocalizedName(),
            static::$institutionMailingAddress => $subscription->getInstitutionMailingAddress(),
            static::$domain => $subscription->getDomain(),
            static::$ipRanges => implode(' ', $institution->getIPRanges()),
        ]);
    }

    protected static function addInstitutionalVariablesDescription(array $variables): array
    {
        return array_merge(
            $variables,
            [
                static::$institutionName => __('emailTemplate.variable.subscription.institutionName'),
                static::$institutionMailingAddress => __('emailTemplate.variable.subscription.institutionMailingAddress'),
                static::$domain => __('emailTemplate.variable.subscription.domain'),
                static::$ipRanges => __('emailTemplate.variable.subscription.ipRanges'),
            ]
        );
    }
}
