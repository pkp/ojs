<?php

/**
 * PaymentHandler.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.payment
 *
 * Handle requests for payment functions.
 *
 * $Id$
 */

class PaymentHandler extends Handler {
		 
	/**
	 * Display scheduled conference view page.
	 */
	function plugin($args) {
		$paymentMethodPlugins =& PluginRegistry::loadCategory('paymethod');
		$paymentMethodPluginName = array_shift($args);
		if (empty($paymentMethodPluginName) || !isset($paymentMethodPlugins[$paymentMethodPluginName])) {
			Request::redirect(null, null, 'index');
		}

		$paymentMethodPlugin =& $paymentMethodPlugins[$paymentMethodPluginName];
		if (!$paymentMethodPlugin->isConfigured()) {
			Request::redirect(null, null, 'index');
		}

		$paymentMethodPlugin->handle($args);
	}
}

?>
