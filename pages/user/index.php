<?php

/**
 * @defgroup pages_user
 */

/**
 * @file pages/user/index.php
 *
 * Copyright (c) 2003-2013 John Willinsky
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
	case 'saveProfile':
	case 'changePassword':
	case 'savePassword':
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
	case 'gifts':
	case 'redeemGift':
	case 'subscriptions':
	case 'setLocale':
	case 'become':
	case 'authorizationDenied':
	case 'viewPublicProfile':
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
