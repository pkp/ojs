<?php

/**
 * @file pages/subscriptions/SubscriptionsHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionsHandler
 * @ingroup pages_subscription
 *
 * @brief Handle requests for subscription management.
 */

import('classes.handler.Handler');

class SubscriptionsHandler extends Handler {
	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER, ROLE_ID_SUBSCRIPTION_MANAGER),
			array('index', 'subscriptions', 'subscriptionTypes', 'subscriptionPolicies', 'saveSubscriptionPolicies', 'payments')
		);
	}

	/**
	 * Display a list of subscriptions for the current journal.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function index($args, $request) {
		$this->setupTemplate($request);
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_MANAGER);
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->display('subscriptions/index.tpl');
	}

	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.PKPSiteAccessPolicy');
		$this->addPolicy(new PKPSiteAccessPolicy($request, null, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Display a list of subscriptions for the current journal.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function subscriptions($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$dispatcher = $request->getDispatcher();
		switch (array_shift($args)) {
			case 'institutional':
				return $templateMgr->fetchAjax(
					'institutionalSubscriptionsGridContainer',
					$dispatcher->url(
						$request, ROUTE_COMPONENT, null,
						'grid.subscriptions.InstitutionalSubscriptionsGridHandler', 'fetchGrid'
					)
				);
			case 'individual':
				return $templateMgr->fetchAjax(
					'individualSubscriptionsGridContainer',
					$dispatcher->url(
						$request, ROUTE_COMPONENT, null,
						'grid.subscriptions.IndividualSubscriptionsGridHandler', 'fetchGrid'
					)
				);
		}
		$dispatcher->handle404();
	}

	/**
	 * Display a list of subscription types for the current journal.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function subscriptionTypes($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$dispatcher = $request->getDispatcher();
		return $templateMgr->fetchAjax(
			'subscriptionTypesGridContainer',
			$dispatcher->url(
				$request, ROUTE_COMPONENT, null,
				'grid.subscriptions.SubscriptionTypesGridHandler', 'fetchGrid'
			)
		);
	}

	/**
	 * Display subscription policies for the current journal.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function subscriptionPolicies($args, $request) {
		$this->validate();
		$this->setupTemplate($request);

		import('classes.subscription.form.SubscriptionPolicyForm');

		$templateMgr = TemplateManager::getManager($request);

		if (Config::getVar('general', 'scheduled_tasks')) {
			$templateMgr->assign('scheduledTasksEnabled', true);
		}

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($request);
		$templateMgr->assign('acceptSubscriptionPayments', $paymentManager->acceptSubscriptionPayments());

		$subscriptionPolicyForm = new SubscriptionPolicyForm();
		if ($subscriptionPolicyForm->isLocaleResubmit()) {
			$subscriptionPolicyForm->readInputData();
		} else {
			$subscriptionPolicyForm->initData();
		}
		return new JSONMessage(true, $subscriptionPolicyForm->fetch($request));
	}

	/**
	 * Save subscription policies for the current journal.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function saveSubscriptionPolicies($args, $request) {
		$this->validate();
		$this->setupTemplate($request);

		import('classes.subscription.form.SubscriptionPolicyForm');
		$subscriptionPolicyForm = new SubscriptionPolicyForm();
		$subscriptionPolicyForm->readInputData();
		if ($subscriptionPolicyForm->validate()) {
			$subscriptionPolicyForm->execute();
			return new JSONMessage(true);
		}
		return new JSONMessage(true, $subscriptionPolicyForm->fetch($request));
	}

	/**
	 * Display a list of payments for the current journal.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function payments($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$dispatcher = $request->getDispatcher();
		return $templateMgr->fetchAjax(
			'paymentsGridContainer',
			$dispatcher->url(
				$request, ROUTE_COMPONENT, null,
				'grid.subscriptions.PaymentsGridHandler', 'fetchGrid'
			)
		);
	}
	// ----------------------- 8< CRUFT LINE ------------------------------------

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

		$journal = $request->getJournal();
		$subscriptionId = empty($args[0]) ? null : (int) $args[0];

		if ($institutional) {
			$subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO');
		} else {
			$subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO');
		}

		// Ensure subscription is for this journal
		if ($subscriptionDao->getSubscriptionJournalId($subscriptionId) == $journal->getId()) {
			$subscription = $subscriptionDao->getById($subscriptionId);
			if ($subscription) $subscriptionDao->renewSubscription($subscription);
		}

		$request->redirect(null, null, 'subscriptions', $redirect);
	}
}

?>
