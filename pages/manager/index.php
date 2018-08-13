<?php

/**
 * @defgroup pages_manager Manager Pages
 */

/**
 * @file pages/manager/index.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_manager
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
	//
	// Subscriptions
	//
	case 'subscriptions':
	case 'subscriptionsSummary':
	case 'deleteSubscription':
	case 'renewSubscription':
	case 'createSubscription':
	case 'editSubscription':
	case 'updateSubscription':
		define('HANDLER_CLASS', 'SubscriptionHandler');
		import('pages.manager.SubscriptionHandler');
		break;
	//
	// Payment
	//
	case 'payments':
	case 'savePaymentSettings':
	case 'viewPayments':
	case 'viewPayment':
		define('HANDLER_CLASS', 'ManagerPaymentHandler');
		import('pages.manager.ManagerPaymentHandler');
		break;
}

?>
