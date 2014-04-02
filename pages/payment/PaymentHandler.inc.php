<?php

/**
 * @file pages/payment/PaymentHandler.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PaymentHandler
 * @ingroup pages_payment
 *
 * @brief Handle requests for payment functions.
 */

import('classes.handler.Handler');

class PaymentHandler extends Handler {
	/**
	 * Constructor
	 */
	function PaymentHandler() {
		parent::Handler();
	}
		 
	/**
	 * Pass request to plugin.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function plugin($args, &$request) {
		$paymentMethodPlugins =& PluginRegistry::loadCategory('paymethod');
		$paymentMethodPluginName = array_shift($args);
		if (empty($paymentMethodPluginName) || !isset($paymentMethodPlugins[$paymentMethodPluginName])) {
			$request->redirect(null, null, 'index');
		}

		$paymentMethodPlugin =& $paymentMethodPlugins[$paymentMethodPluginName];
		if (!$paymentMethodPlugin->isConfigured()) {
			$request->redirect(null, null, 'index');
		}

		$paymentMethodPlugin->handle($args, $request);
	}
}

?>
