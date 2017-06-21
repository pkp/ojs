<?php

/**
 * @defgroup pages_user User Pages
 */

/**
 * @file pages/user/index.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_user
 * @brief Handle requests for user functions.
 *
 */

switch ($op) {
	//
	// Profiles
	//
	case 'profile':
		define('HANDLER_CLASS', 'ProfileHandler');
		import('lib.pkp.pages.user.ProfileHandler');
		break;
	//
	// Registration
	//
	case 'register':
	case 'registerUser':
	case 'activateUser':
		define('HANDLER_CLASS', 'RegistrationHandler');
		import('lib.pkp.pages.user.RegistrationHandler');
		break;
	//
	// Misc.
	//
	case 'index':
	case 'gifts':
	case 'redeemGift':
	case 'subscriptions':
	case 'setLocale':
	case 'authorizationDenied':
	case 'purchaseSubscription':
	case 'payPurchaseSubscription':
	case 'completePurchaseSubscription':
	case 'payRenewSubscription':
	case 'payMembership':
	case 'toggleHelp':
	case 'getInterests':
		define('HANDLER_CLASS', 'UserHandler');
		import('pages.user.UserHandler');
		break;
}

?>
