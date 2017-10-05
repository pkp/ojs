<?php

/**
 * @file PaypalPaymentForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PaypalPaymentForm
 *
 * Form for Paypal-based payments.
 *
 */

import('lib.pkp.classes.form.Form');
require_once(dirname(__FILE__) . '/vendor/autoload.php');

use Omnipay\Omnipay;

class PaypalPaymentForm extends Form {
	/** @var PaypalPaymentPlugin */
	var $_paypalPaymentPlugin;

	/** @var QueuedPayment */
	var $_queuedPayment;

	/**
	 * @param $paypalPaymentPlugin PaypalPaymentPlugin
	 * @param $queuedPayment QueuedPayment
	 */
	function __construct($paypalPaymentPlugin, $queuedPayment) {
		$this->_paypalPaymentPlugin = $paypalPaymentPlugin;
		$this->_queuedPayment = $queuedPayment;
		parent::__construct($this->_paypalPaymentPlugin->getTemplatePath() . '/paymentForm.tpl');
	}

	/**
	 * @copydoc Form::display()
	 */
	function display($request = null, $template = null) {
		try {
			$journal = $request->getJournal();
			$gateway = Omnipay::create('PayPal_Rest');
			$gateway->initialize(array(
				'clientId' => $this->_paypalPaymentPlugin->getSetting($journal->getId(), 'clientId'),
				'secret' => $this->_paypalPaymentPlugin->getSetting($journal->getId(), 'secret'),
				'testMode' => $this->_paypalPaymentPlugin->getSetting($journal->getId(), 'testMode'),
			));
			$transaction = $gateway->purchase(array(
				'amount' => number_format($this->_queuedPayment->getAmount(), 2),
				'currency' => $this->_queuedPayment->getCurrencyCode(),
				'description' => $this->_queuedPayment->getName(),
				'returnUrl' => $this->_queuedPayment->getRequestUrl(),
				'cancelUrl' => $request->url(null, 'index'),
				'notifyUrl' => $request->url(null, 'payment', 'plugin', array($this->_paypalPaymentPlugin->getName(), 'ipn')),
			));
			$response = $transaction->send();
			if ($response->isRedirect()) {
				$request->redirectUrl($response->getRedirectUrl());
			} else if ($response->isSuccessful()) {
				die('SUCCESS');
			} else {print_r($response);exit();
				error_log('PayPal transaction error: ' . $response->getMessage());
				$templateMgr = TemplateManager::getManager($request);
				$templateMgr->assign('message', 'plugins.paymethod.paypal.error');
				$templateMgr->display('frontend/pages/message.tpl');
			}
		} catch (\Exception $e) {
			error_log('PayPal transaction exception: ' . $e->getMessage());
			$templateMgr = TemplateManager::getManager($request);
			$templateMgr->assign('message', 'plugins.paymethod.paypal.error');
			$templateMgr->display('frontend/pages/message.tpl');
		}
	}
}

?>
