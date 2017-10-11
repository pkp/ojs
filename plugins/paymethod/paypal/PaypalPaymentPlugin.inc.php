<?php

/**
 * @file plugins/paymethod/paypal/PaypalPaymentPlugin.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PaypalPaymentPlugin
 * @ingroup plugins_paymethod_paypal
 *
 * @brief Paypal payment plugin class
 */

import('lib.pkp.classes.plugins.PaymethodPlugin');

class PaypalPaymentPlugin extends PaymethodPlugin {

	/**
	 * @see Plugin::getName
	 */
	function getName() {
		return 'PaypalPayment';
	}

	/**
	 * @see Plugin::getDisplayName
	 */
	function getDisplayName() {
		return __('plugins.paymethod.paypal.displayName');
	}

	/**
	 * @see Plugin::getDescription
	 */
	function getDescription() {
		return __('plugins.paymethod.paypal.description');
	}

	/**
	 * @see Plugin::register
	 */
	function register($category, $path) {
		if (parent::register($category, $path)) {
			$this->addLocaleData();
			return true;
		}
		return false;
	}

	/**
	 * @copydoc PaymentPlugin::getSettingsForm()
	 */
	function getSettingsForm($context) {
		$this->import('PaypalPaymentSettingsForm');
		return new PaypalPaymentSettingsForm($this, $context->getId());
	}

	/**
	 * @see PaymentPlugin::isConfigured
	 */
	function isConfigured() {
		$context = $this->getRequest()->getContext();
		if (!$context) return false;
		if ($this->getSetting($context->getId(), 'serviceUrl') == '') return false;
		if ($this->getSetting($context->getId(), 'accountName') == '') return false;
		return true;
	}

	/**
	 * @see Plugin::getInstallEmailTemplatesFile
	 */
	function getInstallEmailTemplatesFile() {
		return ($this->getPluginPath() . DIRECTORY_SEPARATOR . 'emailTemplates.xml');
	}

	/**
	 * @see Plugin::getInstallEmailTemplateDataFile
	 */
	function getInstallEmailTemplateDataFile() {
		return ($this->getPluginPath() . '/locale/{$installedLocale}/emailTemplates.xml');
	}

	/**
	 * @copydoc Plugin::getTemplatePath()
	 */
	function getTemplatePath($inCore = false) {
		return parent::getTemplatePath($inCore) . 'templates/';
	}
}

?>
