<?php

/**
 * @file pages/manager/SubscriptionHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
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
	 * Display subscriptions summary page for the current journal.
	 */
	function subscriptionsSummary($args, $request) {
		$this->validate();
		$this->setupTemplate($request);

		import('classes.subscription.SubscriptionAction');
		SubscriptionAction::subscriptionsSummary($request);
	}

	/**
	 * Display a list of subscriptions for the current journal.
	 */
	function subscriptions($args, $request) {
		if (array_shift($args) == 'individual') {
			$institutional = false;
		} else {
			$institutional = true;
		}

		$this->validate();
		$this->setupTemplate($request);

		import('classes.subscription.SubscriptionAction');
		SubscriptionAction::subscriptions($request, $institutional);
	}

	/**
	 * Delete a subscription.
	 * @param $args array first parameter is the ID of the subscription to delete
	 */
	function deleteSubscription($args, $request) {
		if (array_shift($args) == 'individual') {
			$institutional  = false;
			$redirect = 'individual';
		} else {
			$institutional = true;
			$redirect = 'institutional';
		}

		$this->validate();
		$this->setupTemplate($request);

		import('classes.subscription.SubscriptionAction');
		SubscriptionAction::deleteSubscription($args, $request, $institutional);

		$request->redirect(null, null, 'subscriptions', $redirect);
	}

	/**
	 * Renew a subscription.
	 * @param $args array first parameter is the ID of the subscription to renew
	 */
	function renewSubscription($args, $request) {
		if (array_shift($args) == 'individual') {
			$institutional  = false;
			$redirect = 'individual';
		} else {
			$institutional = true;
			$redirect = 'institutional';
		}

		$this->validate();
		$this->setupTemplate($request);

		import('classes.subscription.SubscriptionAction');
		SubscriptionAction::renewSubscription($args, $request, $institutional);

		$request->redirect(null, null, 'subscriptions', $redirect);
	}

	/**
	 * Display form to edit a subscription.
	 * @param $args array optional, first parameter is the ID of the subscription to edit
	 */
	function editSubscription($args, $request) {
		if (array_shift($args) == 'individual') {
			$institutional  = false;
			$redirect = 'individual';
		} else {
			$institutional = true;
			$redirect = 'institutional';
		}

		$this->validate();
		$this->setupTemplate($request);

		import('classes.subscription.SubscriptionAction');
		$editSuccess = SubscriptionAction::editSubscription($args, $request, $institutional);

		if (!$editSuccess) {
			$request->redirect(null, null, 'subscriptions', $redirect);
		}
	}

	/**
	 * Display form to create new subscription.
	 */
	function createSubscription($args, $request) {
		$this->editSubscription($args, $request);
	}

	/**
	 * Display a list of users from which to choose a subscriber.
	 */
	function selectSubscriber($args, $request) {
		if (array_shift($args) == 'individual') {
			$institutional  = false;
			$redirect = 'individual';
		} else {
			$institutional = true;
			$redirect = 'institutional';
		}

		$this->validate();
		$this->setupTemplate($request);

		import('classes.subscription.SubscriptionAction');
		SubscriptionAction::selectSubscriber($args, $request, $institutional);
	}

	/**
	 * Save changes to a subscription.
	 */
	function updateSubscription($args, $request) {
		if (array_shift($args) == 'individual') {
			$institutional  = false;
			$redirect = 'individual';
		} else {
			$institutional = true;
			$redirect = 'institutional';
		}

		$this->validate();
		$this->setupTemplate($request);

		import('classes.subscription.SubscriptionAction');
		$updateSuccess = SubscriptionAction::updateSubscription($args, $request, $institutional);

		if ($updateSuccess && $request->getUserVar('createAnother')) {
			$request->redirect(null, null, 'selectSubscriber', $redirect);
		} elseif ($updateSuccess) {
			$request->redirect(null, null, 'subscriptions', $redirect);
		}
	}

	/**
	 * Display a list of subscription types for the current journal.
	 */
	function subscriptionTypes($args, $request) {
		$this->validate();
		$this->setupTemplate($request);

		import('classes.subscription.SubscriptionAction');
		SubscriptionAction::subscriptionTypes($request);
	}

	/**
	 * Rearrange the order of subscription types.
	 */
	function moveSubscriptionType($args, $request) {
		$this->validate();
		$this->setupTemplate($request);

		import('classes.subscription.SubscriptionAction');
		SubscriptionAction::moveSubscriptionType($args, $request);

		$request->redirect(null, null, 'subscriptionTypes');
	}

	/**
	 * Delete a subscription type.
	 * @param $args array first parameter is the ID of the subscription type to delete
	 */
	function deleteSubscriptionType($args, $request) {
		$this->validate();
		$this->setupTemplate($request);

		import('classes.subscription.SubscriptionAction');
		SubscriptionAction::deleteSubscriptionType($args, $request);

		$request->redirect(null, null, 'subscriptionTypes');
	}

	/**
	 * Display form to edit a subscription type.
	 * @param $args array optional, first parameter is the ID of the subscription type to edit
	 */
	function editSubscriptionType($args, $request) {
		$this->validate();
		$this->setupTemplate($request);

		import('classes.subscription.SubscriptionAction');
		$editSuccess = SubscriptionAction::editSubscriptionType($args, $request);

		if (!$editSuccess) {
			$request->redirect(null, null, 'subscriptionTypes');
		}
	}

	/**
	 * Display form to create new subscription type.
	 */
	function createSubscriptionType($args, $request) {
		$this->editSubscriptionType($args, $request);
	}

	/**
	 * Save changes to a subscription type.
	 */
	function updateSubscriptionType($args, $request) {
		$this->validate();
		$this->setupTemplate($request);

		import('classes.subscription.SubscriptionAction');
		$updateSuccess = SubscriptionAction::updateSubscriptionType($request);

		if ($updateSuccess && $request->getUserVar('createAnother')) {
			$request->redirect(null, null, 'createSubscriptionType', null, array('subscriptionTypeCreated' => 1));
		} elseif ($updateSuccess) {
			$request->redirect(null, null, 'subscriptionTypes');
		}
	}

	/**
	 * Display subscription policies for the current journal.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function subscriptionPolicies($args, $request) {
		$this->validate();
		$this->setupTemplate($request);

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
		$this->setupTemplate($request);

		import('classes.subscription.SubscriptionAction');
		SubscriptionAction::saveSubscriptionPolicies($args, $request);
	}
}

?>
