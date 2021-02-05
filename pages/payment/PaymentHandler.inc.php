<?php

/**
 * @file pages/payment/PaymentHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PaymentHandler
 * @ingroup pages_payment
 *
 * @brief Handle requests for payment functions.
 */

import('classes.handler.Handler');

class PaymentHandler extends Handler {
		 
	/**
	 * Pass request to plugin.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function plugin($args, $request) {
		$paymentMethodPlugins = PluginRegistry::loadCategory('paymethod');
		$paymentMethodPluginName = array_shift($args);
		if (empty($paymentMethodPluginName) || !isset($paymentMethodPlugins[$paymentMethodPluginName])) {
			$request->redirect(null, null, 'index');
		}

		$paymentMethodPlugin =& $paymentMethodPlugins[$paymentMethodPluginName];
		if (!$paymentMethodPlugin->isConfigured($request->getContext())) {
			$request->redirect(null, null, 'index');
		}

		$paymentMethodPlugin->handle($args, $request);
	}

	/**
	 * Present a landing page from which to fulfill a payment.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function pay($args, $request) {
		if (!Validation::isLoggedIn()) {
			Validation::redirectLogin();
		}

		$paymentManager = Application::getPaymentManager($request->getContext());
		$templateMgr = TemplateManager::getManager($request);
		$queuedPaymentDao = DAORegistry::getDAO('QueuedPaymentDAO'); /* @var $queuedPaymentDao QueuedPaymentDAO */
		$queuedPayment = $queuedPaymentDao->getById($queuedPaymentId = array_shift($args));
		if (!$queuedPayment) {
			$templateMgr->assign(array(
				'pageTitle' => 'common.payment',
				'message' => 'payment.notFound',
			));
			$templateMgr->display('frontend/pages/message.tpl');
			return;
		}

		$paymentForm = $paymentManager->getPaymentForm($queuedPayment);
		$paymentForm->display($request);
	}
}


