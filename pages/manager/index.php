<?php

/**
 * @defgroup pages_manager Manager Pages
 */

/**
 * @file pages/manager/index.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_manager
 * @brief Handle requests for journal management functions.
 *
 */

switch ($op) {
	//
	// Review Form Management
	//
	case 'reviewForms':
	case 'createReviewForm':
	case 'editReviewForm':
	case 'updateReviewForm':
	case 'previewReviewForm':
	case 'deleteReviewForm':
	case 'activateReviewForm':
	case 'deactivateReviewForm':
	case 'copyReviewForm':
	case 'moveReviewForm':
	case 'reviewFormElements':
	case 'createReviewFormElement':
	case 'editReviewFormElement':
	case 'deleteReviewFormElement':
	case 'updateReviewFormElement':
	case 'moveReviewFormElement':
	case 'copyReviewFormElement':
		define('HANDLER_CLASS', 'ReviewFormHandler');
		import('pages.manager.ReviewFormHandler');
		break;
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
	// Import/Export
	//
	case 'importexport':
		define('HANDLER_CLASS', 'ImportExportHandler');
		import('pages.manager.ImportExportHandler');
		break;
	//
	// Payment
	//
	case 'payments':
	case 'savePaymentSettings':
	case 'payMethodSettings':
	case 'savePayMethodSettings':
	case 'viewPayments':
	case 'viewPayment':
		define('HANDLER_CLASS', 'ManagerPaymentHandler');
		import('pages.manager.ManagerPaymentHandler');
		break;
	case 'index':
		define('HANDLER_CLASS', 'ManagerHandler');
		import('pages.manager.ManagerHandler');
	//
	// Plugin Management
	//
	case 'plugin':
		define('HANDLER_CLASS', 'PluginHandler');
		import('lib.pkp.pages.manager.PluginHandler');
		break;
}

?>
