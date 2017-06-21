<?php

/**
 * @defgroup pages_subscriptions Subscription Management Pages
 */

/**
 * @file pages/subscriptions/index.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_subscriptions
 * @brief Handle requests for subscription management functions.
 *
 */

switch ($op) {
	//
	// Issue
	//
	case 'index':
	case 'subscriptions':
	case 'subscriptionTypes':
	case 'subscriptionPolicies':
	case 'saveSubscriptionPolicies':
	case 'payments':
		define('HANDLER_CLASS', 'SubscriptionsHandler');
		import('pages.subscriptions.SubscriptionsHandler');
		break;
}

?>
