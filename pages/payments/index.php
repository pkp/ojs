<?php

/**
 * @defgroup pages_payments Payment Management Pages
 */

/**
 * @file pages/payments/index.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_subscriptions
 * @brief Handle requests for payment management functions.
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
	case 'paymentTypes':
	case 'savePaymentTypes':
	case 'payments':
		define('HANDLER_CLASS', 'PaymentsHandler');
		import('pages.payments.PaymentsHandler');
		break;
}
