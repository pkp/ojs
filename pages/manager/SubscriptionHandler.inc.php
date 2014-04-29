<?php

/**
 * @file pages/manager/SubscriptionHandler.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionHandler
 * @ingroup pages_manager
 *
 * Handle requests for subscription management functions.
 */

import('pages.manager.ManagerHandler');

class SubscriptionHandler extends ManagerHandler {
	/**
	 * Constructor
	 **/
	function SubscriptionHandler() {
		parent::ManagerHandler();
	}

	/**
	 * Display subscriptions summary page for the current journal.
	 */
	function subscriptionsSummary() {
		$this->validate();
		$this->setupTemplate();

		import('classes.subscription.SubscriptionAction');
		SubscriptionAction::subscriptionsSummary();
	}

	/**
	 * Display a list of subscriptions for the current journal.
	 */
	function subscriptions($args) {
		if (isset($args) && !empty($args)) {
			if ($args[0] == 'individual') {
				$institutional  = false;
			} else {
				$institutional = true;
			}
		} else {
			Request::redirect(null, 'manager');
		}

		$this->validate();
		$this->setupTemplate();

		array_shift($args);
		import('classes.subscription.SubscriptionAction');
		SubscriptionAction::subscriptions($institutional);
	}

	/**
	 * Delete a subscription.
	 * @param $args array first parameter is the ID of the subscription to delete
	 */
	function deleteSubscription($args) {
		if (isset($args) && !empty($args)) {
			if ($args[0] == 'individual') {
				$institutional  = false;
				$redirect = 'individual';
			} else {
				$institutional = true;
				$redirect = 'institutional';
			}
		} else {
			Request::redirect(null, 'manager');
		}

		$this->validate();
		$this->setupTemplate();

		array_shift($args);
		import('classes.subscription.SubscriptionAction');
		SubscriptionAction::deleteSubscription($args, $institutional);

		Request::redirect(null, null, 'subscriptions', $redirect);
	}

	/**
	 * Renew a subscription.
	 * @param $args array first parameter is the ID of the subscription to renew
	 */
	function renewSubscription($args) {
		if (isset($args) && !empty($args)) {
			if ($args[0] == 'individual') {
				$institutional  = false;
				$redirect = 'individual';
			} else {
				$institutional = true;
				$redirect = 'institutional';
			}
		} else {
			Request::redirect(null, 'manager');
		}

		$this->validate();
		$this->setupTemplate();

		array_shift($args);
		import('classes.subscription.SubscriptionAction');
		SubscriptionAction::renewSubscription($args, $institutional);

		Request::redirect(null, null, 'subscriptions', $redirect);
	}

	/**
	 * Display form to edit a subscription.
	 * @param $args array optional, first parameter is the ID of the subscription to edit
	 */
	function editSubscription($args) {
		if (isset($args) && !empty($args)) {
			if ($args[0] == 'individual') {
				$institutional  = false;
				$redirect = 'individual';
			} else {
				$institutional = true;
				$redirect = 'institutional';
			}
		} else {
			Request::redirect(null, 'manager');
		}

		$this->validate();
		$this->setupTemplate(true, $institutional);

		array_shift($args);
		import('classes.subscription.SubscriptionAction');
		$editSuccess = SubscriptionAction::editSubscription($args, $institutional);

		if (!$editSuccess) {
			Request::redirect(null, null, 'subscriptions', $redirect);
		}
	}

	/**
	 * Display form to create new subscription.
	 */
	function createSubscription($args) {
		$this->editSubscription($args);
	}

	/**
	 * Display a list of users from which to choose a subscriber.
	 */
	function selectSubscriber($args) {
		if (isset($args) && !empty($args)) {
			if ($args[0] == 'individual') {
				$institutional  = false;
				$redirect = 'individual';
			} else {
				$institutional = true;
				$redirect = 'institutional';
			}
		} else {
			Request::redirect(null, 'manager');
		}

		$this->validate();
		$this->setupTemplate(true, $institutional);

		array_shift($args);
		import('classes.subscription.SubscriptionAction');
		SubscriptionAction::selectSubscriber($args, $institutional);
	}

	/**
	 * Save changes to a subscription.
	 */
	function updateSubscription($args) {
		if (isset($args) && !empty($args)) {
			if ($args[0] == 'individual') {
				$institutional  = false;
				$redirect = 'individual';
			} else {
				$institutional = true;
				$redirect = 'institutional';
			}
		} else {
			Request::redirect(null, 'manager');
		}

		$this->validate();
		$this->setupTemplate(true, $institutional);

		array_shift($args);
		import('classes.subscription.SubscriptionAction');
		$updateSuccess = SubscriptionAction::updateSubscription($args, $institutional);

		if ($updateSuccess && Request::getUserVar('createAnother')) {
			Request::redirect(null, null, 'selectSubscriber', $redirect);
		} elseif ($updateSuccess) {
			Request::redirect(null, null, 'subscriptions', $redirect);
		}
	}

	/**
	 * Reset a subscription reminder date.
	 */
	function resetDateReminded($args, &$request) {
		if (isset($args) && !empty($args)) {
			if ($args[0] == 'individual') {
				$institutional  = false;
				$redirect = 'individual';
			} else {
				$institutional = true;
				$redirect = 'institutional';
			}
		} else {
			Request::redirect(null, 'manager');
		}

		$this->validate();
		$this->setupTemplate(true, $institutional);

		array_shift($args);
		$subscriptionId = (int) $args[0];
		import('classes.subscription.SubscriptionAction');
		SubscriptionAction::resetDateReminded($args, $institutional);

		Request::redirect(null, null, 'editSubscription', array($redirect, $subscriptionId));
	}

	/**
	 * Display a list of subscription types for the current journal.
	 */
	function subscriptionTypes() {
		$this->validate();
		$this->setupTemplate();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->addJavaScript('lib/pkp/js/lib/jquery/plugins/jquery.tablednd.js');
		$templateMgr->addJavaScript('lib/pkp/js/functions/tablednd.js');

		import('classes.subscription.SubscriptionAction');
		SubscriptionAction::subscriptionTypes();
	}

	/**
	 * Rearrange the order of subscription types.
	 */
	function moveSubscriptionType($args) {
		$this->validate();
		$this->setupTemplate();

		import('classes.subscription.SubscriptionAction');
		SubscriptionAction::moveSubscriptionType($args);

		Request::redirect(null, null, 'subscriptionTypes');
	}

	/**
	 * Delete a subscription type.
	 * @param $args array first parameter is the ID of the subscription type to delete
	 */
	function deleteSubscriptionType($args) {
		$this->validate();
		$this->setupTemplate();

		import('classes.subscription.SubscriptionAction');
		SubscriptionAction::deleteSubscriptionType($args);

		Request::redirect(null, null, 'subscriptionTypes');
	}

	/**
	 * Display form to edit a subscription type.
	 * @param $args array optional, first parameter is the ID of the subscription type to edit
	 */
	function editSubscriptionType($args = array()) {
		$this->validate();
		$this->setupTemplate();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->append('pageHierarchy', array(Request::url(null, 'manager', 'subscriptionTypes'), 'manager.subscriptionTypes'));

		import('classes.subscription.SubscriptionAction');
		$editSuccess = SubscriptionAction::editSubscriptionType($args);

		if (!$editSuccess) {
			Request::redirect(null, null, 'subscriptionTypes');
		}
	}

	/**
	 * Display form to create new subscription type.
	 */
	function createSubscriptionType() {
		$this->editSubscriptionType();
	}

	/**
	 * Save changes to a subscription type.
	 */
	function updateSubscriptionType() {
		$this->validate();
		$this->setupTemplate();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->append('pageHierarchy', array(Request::url(null, 'manager', 'subscriptionTypes'), 'manager.subscriptionTypes'));

		import('classes.subscription.SubscriptionAction');
		$updateSuccess = SubscriptionAction::updateSubscriptionType();

		if ($updateSuccess && Request::getUserVar('createAnother')) {
			Request::redirect(null, null, 'createSubscriptionType', null, array('subscriptionTypeCreated' => 1));
		} elseif ($updateSuccess) {
			Request::redirect(null, null, 'subscriptionTypes');
		}
	}

	/**
	 * Display subscription policies for the current journal.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function subscriptionPolicies($args, &$request) {
		$this->validate();
		$this->setupTemplate();

		import('classes.subscription.SubscriptionAction');
		SubscriptionAction::subscriptionPolicies($args, $request);
	}

	/**
	 * Save subscription policies for the current journal.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function saveSubscriptionPolicies($args, $request) {
		$this->validate();
		$this->setupTemplate();

		import('classes.subscription.SubscriptionAction');
		SubscriptionAction::saveSubscriptionPolicies($args, $request);
	}

	/**
	 * Setup common template variables.
	 */
	function setupTemplate($subclass = false, $institutional = false) {
		parent::setupTemplate(true);
		if ($subclass) {
			$templateMgr =& TemplateManager::getManager();
			if ($institutional) {
				$templateMgr->append('pageHierarchy', array(Request::url(null, 'manager', 'subscriptions', 'institutional'), 'manager.institutionalSubscriptions'));
			} else {
				$templateMgr->append('pageHierarchy', array(Request::url(null, 'manager', 'subscriptions', 'individual'), 'manager.individualSubscriptions'));
			}
		}
	}
}

?>
