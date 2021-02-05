<?php

/**
 * @file pages/payments/PaymentsHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PaymentsHandler
 * @ingroup pages_payments
 *
 * @brief Handle requests for payment management.
 */

import('classes.handler.Handler');

class PaymentsHandler extends Handler {

	/** @copydoc PKPHandler::_isBackendPage */
	var $_isBackendPage = true;

	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER, ROLE_ID_SUBSCRIPTION_MANAGER),
			array('index', 'subscriptions', 'subscriptionTypes', 'subscriptionPolicies', 'saveSubscriptionPolicies', 'paymentTypes', 'savePaymentTypes', 'payments')
		);
	}

	/**
	 * Display a list of payment tabs for the current journal.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function index($args, $request) {
		$this->setupTemplate($request);
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_MANAGER);
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign([
			'pageTitle' => __('manager.subscriptions'),
		]);
		$templateMgr->display('payments/index.tpl');
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

		$paymentManager = Application::getPaymentManager($request->getJournal());
		$templateMgr->assign('acceptSubscriptionPayments', $paymentManager->isConfigured());

		$subscriptionPolicyForm = new SubscriptionPolicyForm();
		$subscriptionPolicyForm->initData();
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
			$notificationManager = new NotificationManager();
			$user = $request->getUser();
			$notificationManager->createTrivialNotification($user->getId());
			return new JSONMessage(true);
		}
		return new JSONMessage(true, $subscriptionPolicyForm->fetch($request));
	}

	/**
	 * Display payment types for the current journal.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function paymentTypes($args, $request) {
		$this->validate();
		$this->setupTemplate($request);

		import('classes.subscription.form.PaymentTypesForm');

		$paymentTypesForm = new PaymentTypesForm();
		$paymentTypesForm->initData();
		return new JSONMessage(true, $paymentTypesForm->fetch($request));
	}

	/**
	 * Save payment types for the current journal.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function savePaymentTypes($args, $request) {
		$this->validate();
		$this->setupTemplate($request);

		import('classes.subscription.form.PaymentTypesForm');
		$paymentTypesForm = new PaymentTypesForm();
		$paymentTypesForm->readInputData();
		if ($paymentTypesForm->validate()) {
			$paymentTypesForm->execute();
			$notificationManager = new NotificationManager();
			$user = $request->getUser();
			$notificationManager->createTrivialNotification($user->getId());
			return new JSONMessage(true);
		}
		return new JSONMessage(true, $paymentTypesForm->fetch($request));
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
}
