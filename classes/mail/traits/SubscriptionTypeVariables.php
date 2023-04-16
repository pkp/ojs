<?php

/**
 * @file mail/traits/SubscriptionTypeVariables.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionTypeVariables
 *
 * @ingroup mail_traits
 *
 * @brief Mailable trait to set variables related to subscription type
 */

namespace APP\mail\traits;

use APP\core\Application;
use APP\journal\Journal;
use APP\subscription\SubscriptionType;

trait SubscriptionTypeVariables
{
    protected static string $subscriptionUrl = 'subscriptionUrl';
    protected static string $subscriptionType = 'subscriptionType';

    abstract public function addData(array $variables);

    protected function setupSubscriptionTypeVariables(SubscriptionType $subscriptionType, Journal $context): void
    {
        $this->addData([
            static::$subscriptionUrl => $this->getSubscriptionUrl($subscriptionType, $context),
            static::$subscriptionType => $subscriptionType->getSummaryString(),
        ]);
    }

    protected static function addSubscriptionTypeVariablesDescription(array $variables): array
    {
        return array_merge(
            $variables,
            [
                static::$subscriptionUrl => __('emailTemplate.variable.subscription.subscriptionUrl'),
                static::$subscriptionType => __('emailTemplate.variable.subscription.subscriptionType'),
            ]
        );
    }

    protected function getSubscriptionUrl(SubscriptionType $subscriptionType, Journal $context): string
    {
        $application = Application::get();
        $request = $application->getRequest();
        $dispatcher = $application->getDispatcher();

        return $dispatcher->url(
            $request,
            Application::ROUTE_PAGE,
            $context->getData('urlPath'),
            'payments',
            null,
            null,
            null,
            $subscriptionType->getInstitutional() ? 'institutional' : 'individual',
        );
    }
}
