<?php

/**
 * @file pages/subscriptionManager/SubscriptionManagerHandler.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionManagerHandler
 * @ingroup pages_subscriptionManager
 *
 * @brief Handle requests for subscription management functions.
 */

import('classes.handler.Handler');

class SubscriptionManagerHandler extends Handler {
	/**
	 * Constructor
	 */
	function SubscriptionManagerHandler() {
		parent::Handler();
		$this->addCheck(new HandlerValidatorRoles($this, true, null, null, array(ROLE_ID_SUBSCRIPTION_MANAGER)));
	}

	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.ContextRequiredPolicy');
		$this->addPolicy(new ContextRequiredPolicy($request));
		return parent::authorize($request, $args, $roleAssignments);
	}

	function index($args, $request) {
		$this->subscriptionsSummary($args, $request);
	}

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
			$institutional = false;
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
		$this->setupTemplate($request, true, $institutional);

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
			$institutional = false;
			$redirect = 'individual';
		} else {
			$institutional = true;
			$redirect = 'institutional';
		}

		$this->validate();
		$this->setupTemplate($request, true, $institutional);

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
		$this->setupTemplate($request, true, $institutional);

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

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->addJavaScript('lib/pkp/js/lib/jquery/plugins/jquery.tablednd.js');
		$templateMgr->addJavaScript('lib/pkp/js/functions/tablednd.js');

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

	/**
	 * Display form to create a user profile.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function createUser($args, $request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$journal = $request->getJournal();

		$templateMgr = TemplateManager::getManager($request);

		import('classes.manager.form.UserManagementForm');

		$templateMgr->assign('currentUrl', $request->url(null, null, 'createUser'));
		$userForm = new UserManagementForm();
		if ($userForm->isLocaleResubmit()) {
			$userForm->readInputData();
		} else {
			$userForm->initData();
		}
		$userForm->display();
	}

	/**
	 * Save changes to a user profile.
	 */
	function updateUser($args, $request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$journal = $request->getJournal();

		import('classes.manager.form.UserManagementForm');

		$userForm = new UserManagementForm();
		$userForm->readInputData();

		if ($userForm->validate()) {
			$userForm->execute();

			if ($request->getUserVar('createAnother')) {
				$this->setupTemplate($request, true);
				$templateMgr = TemplateManager::getManager($request);
				$templateMgr->assign('currentUrl', $request->url(null, null, 'index'));
				$templateMgr->assign('userCreated', true);
				$userForm = new UserManagementForm();
				$userForm->initData();
				$userForm->display();

			} else {
				$source = $request->getUserVar('source');
				if (isset($source) && !empty($source)) {
					$request->redirectUrl($source);
				} else {
					$request->redirect(null, null, 'selectSubscriber');
				}
			}

		} else {
			$userForm->display();
		}
	}

	/**
	 * Display payments settings form
	 */
	function payments($args, $request) {
		$this->validate();
		$this->setupTemplate($request);

		import('classes.payment.ojs.OJSPaymentAction');
		OJSPaymentAction::payments($args, $request);
	}

	/**
	 * Execute the payments form or display it again if there are problems
	 */
	function savePaymentSettings($args, $request) {
		$this->validate();
		$this->setupTemplate($request);

		import('classes.payment.ojs.OJSPaymentAction');
		$success = OJSPaymentAction::savePaymentSettings($args, $request);

		if ($success) {
 			$templateMgr = TemplateManager::getManager($request);
			$templateMgr->assign(array(
				'currentUrl' => $request->url(null, null, 'payments'),
				'pageTitle' => 'manager.payment.feePaymentOptions',
				'message' => 'common.changesSaved',
				'backLink' => $request->url(null, null, 'payments'),
				'backLinkLabel' => 'manager.payment.feePaymentOptions'
			));
			$templateMgr->display('common/message.tpl');
		}
	}

	/**
	 * Display all payments previously made
	 */
	function viewPayments($args, $request) {
		$this->validate();
		$this->setupTemplate($request);

		import('classes.payment.ojs.OJSPaymentAction');
		OJSPaymentAction::viewPayments($args, $request);
	}

	/**
	 * Display a single Completed payment
	 */
	function viewPayment($args, $request) {
		$this->validate();
		$this->setupTemplate($request);

		import('classes.payment.ojs.OJSPaymentAction');
		OJSPaymentAction::viewPayment($args, $request);
	}

	/**
	 * Display form to edit program settings.
	 */
	function payMethodSettings($args, $request) {
		$this->validate();
		$this->setupTemplate($request);

		import('classes.payment.ojs.OJSPaymentAction');
		OJSPaymentAction::payMethodSettings($request);
	}

	/**
	 * Save changes to payment settings.
	 */
	function savePayMethodSettings($args, $request) {
		$this->validate();
		$this->setupTemplate($request);

		import('classes.payment.ojs.OJSPaymentAction');
		$success = OJSPaymentAction::savePayMethodSettings($request);

		if ($success) {
 			$templateMgr = TemplateManager::getManager($request);
			$templateMgr->assign(array(
				'currentUrl' => $request->url(null, null, 'payMethodSettings'),
				'pageTitle' => 'manager.payment.paymentMethods',
				'message' => 'common.changesSaved',
				'backLink' => $request->url(null, null, 'payMethodSettings'),
				'backLinkLabel' => 'manager.payment.paymentMethods'
			));
			$templateMgr->display('common/message.tpl');
		}
	}

	/**
	 * Get a suggested username, making sure it's not
	 * already used by the system. (Poor-man's AJAX.)
	 */
	function suggestUsername($args, $request) {
		$this->validate();
		$suggestion = Validation::suggestUsername(
			$request->getUserVar('firstName'),
			$request->getUserVar('lastName')
		);
		echo $suggestion;
	}

	/**
	 * Setup common template variables.
	 */
	function setupTemplate($request) {
		parent::setupTemplate($request);
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_MANAGER, LOCALE_COMPONENT_APP_MANAGER);
	}
}

?>
