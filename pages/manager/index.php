<?php

/**
 * @defgroup pages_manager Manager Pages
 */

/**
 * @file pages/manager/index.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_manager
 *
 * @brief Handle requests for journal management functions.
 *
 */

switch ($op) {
    //
    // Subscription Policies
    //
    case 'subscriptionPolicies':
    case 'saveSubscriptionPolicies':
        //
        // Subscription Types
        //
    case 'subscriptionTypes':
    case 'deleteSubscriptionType':
    case 'createSubscriptionType':
    case 'selectSubscriber':
    case 'editSubscriptionType':
    case 'updateSubscriptionType':
    case 'moveSubscriptionType':
}
