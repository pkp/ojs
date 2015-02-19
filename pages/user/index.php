<?php

/**
 * @defgroup pages_user
 */

/**
 * @file pages/user/index.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
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
		import('pages.user.ProfileHandler');
		break;
	//
	// Registration
	//
	case 'register':
	case 'registerUser':
	case 'activateUser':
		define('HANDLER_CLASS', 'RegistrationHandler');
		import('pages.user.RegistrationHandler');
		break;
	//
	// Email
	//
	case 'email':
		define('HANDLER_CLASS', 'EmailHandler');
		import('pages.user.EmailHandler');
		break;
	//
	// Misc.
	//
	case 'index':
	case 'gifts':
	case 'redeemGift':
	case 'subscriptions':
	case 'setLocale':
	case 'become':
	case 'authorizationDenied':
	case 'viewCaptcha':
	case 'viewPublicProfile':
	case 'purchaseSubscription':
	case 'payPurchaseSubscription':
	case 'completePurchaseSubscription':
	case 'payRenewSubscription':
	case 'payMembership':
		define('HANDLER_CLASS', 'UserHandler');
		import('pages.user.UserHandler');
		break;
	case 'getInterests':
		define('HANDLER_CLASS', 'PKPUserHandler');
		import('lib.pkp.pages.user.PKPUserHandler');
		break;
}

?>
