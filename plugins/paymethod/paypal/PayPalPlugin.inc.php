<?php

/**
 * @file plugins/paymethod/paypal/PayPalPlugin.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2006-2009 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PayPalPlugin
 * @ingroup plugins_paymethod_paypal
 *
 * @brief PayPal Paymethod plugin class
 */

import('classes.plugins.PaymethodPlugin');

class PayPalPlugin extends PaymethodPlugin {
	/**
	 * Constructor
	 */
	function PayPalPlugin() {
		parent::PaymethodPlugin();
	}

	/**
	 * Get the Plugin's internal name
	 * @return String
	 */
	function getName() {
		return 'Paypal';
	}

	/**
	 * Get the Plugin's display name
	 * @return String
	 */	
	function getDisplayName() {
		return __('plugins.paymethod.paypal.displayName');
	}

	/**
	 * Get a description of the plugin
	 * @return String
	 */
	function getDescription() {
		return __('plugins.paymethod.paypal.description');
	}   

	/**
	 * Register plugin
	 * @return bool
	 */
	function register($category, $path) {
		if (parent::register($category, $path)) {
			if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return true;
			$this->addLocaleData();
			$this->import('PayPalDAO');
			$payPalDao = new PayPalDAO();
			DAORegistry::registerDAO('PayPalDAO', $payPalDao);
			return true;
		}
		return false;
	}

	/**
	 * Get an array of the fields in the settings form
	 * @return array
	 */
	function getSettingsFormFieldNames() {
		return array('paypalurl', 'selleraccount');
	}

	/**
	 * return if required Curl is installed
	 * @return bool
	 */	
	function isCurlInstalled() {
		return (function_exists('curl_init'));
	}

	/**
	 * Check if plugin is configured and ready for use
	 * @return bool
	 */
	function isConfigured() {
		$journal =& Request::getJournal();
		if (!$journal) return false;

		// Make sure CURL support is included.
		if (!$this->isCurlInstalled()) return false;

		// Make sure that all settings form fields have been filled in
		foreach ($this->getSettingsFormFieldNames() as $settingName) {
			$setting = $this->getSetting($journal->getId(), $settingName);
			if (empty($setting)) return false;
		}
		return true;
	}

	/**
	 * Display the settings form
	 * @param $params
	 * @param $smarty Smarty
	 */
	function displayPaymentSettingsForm(&$params, &$smarty) {
		$smarty->assign('isCurlInstalled', $this->isCurlInstalled());
		return parent::displayPaymentSettingsForm($params, $smarty);
	}

	/**
	 * Display the payment form
	 * @param $queuedPaymentId int
	 * @param $queuedPayment QueuedPayment
	 * @param $request PKPRequest
	 */
	function displayPaymentForm($queuedPaymentId, &$queuedPayment, &$request) {
		if (!$this->isConfigured()) return false;
		AppLocale::requireComponents(LOCALE_COMPONENT_APPLICATION_COMMON);
		$journal =& $request->getJournal();
		$user =& $request->getUser();

		$params = array(
			'charset' => Config::getVar('i18n', 'client_charset'),
			'business' => $this->getSetting($journal->getId(), 'selleraccount'),
			'item_name' => $queuedPayment->getName(),
			'item_description' => $queuedPayment->getDescription(),  // not a paypal parameter (PayPal uses item_name)
			'amount' => sprintf('%.2F', $queuedPayment->getAmount()),
			'quantity' => 1,
			'no_note' => 1,
			'no_shipping' => 1,
			'currency_code' => $queuedPayment->getCurrencyCode(),
			'lc' => String::substr(AppLocale::getLocale(), 3), 
			'custom' => $queuedPaymentId,
			'notify_url' => $request->url(null, 'payment', 'plugin', array($this->getName(), 'ipn')),  
			'return' => $queuedPayment->getRequestUrl(),
			'cancel_return' => $request->url(null, 'payment', 'plugin', array($this->getName(), 'cancel')),
			'first_name' => ($user)?$user->getFirstName():'',  
			'last_name' => ($user)?$user->getLastname():'',
			'item_number' => $queuedPayment->getAssocId(),
			'cmd' => '_xclick'
		);

		AppLocale::requireComponents(LOCALE_COMPONENT_APPLICATION_COMMON);
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('params', $params);
		$templateMgr->assign('paypalFormUrl', $this->getSetting($journal->getId(), 'paypalurl'));
		$templateMgr->display($this->getTemplatePath() . 'paymentForm.tpl');
		return true;
	}

	/**
	 * Handle incoming requests/notifications
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function handle($args, &$request) {
		$templateMgr =& TemplateManager::getManager();
		$journal =& $request->getJournal();
		if (!$journal) return parent::handle($args, $request);

		// Just in case we need to contact someone
		import('classes.mail.MailTemplate');
		// Prefer technical support contact
		$contactName = $journal->getSetting('supportName');
		$contactEmail = $journal->getSetting('supportEmail');
		if (!$contactEmail) { // Fall back on primary contact
			$contactName = $journal->getSetting('contactName');
			$contactEmail = $journal->getSetting('contactEmail');
		}
		$mail = new MailTemplate('PAYPAL_INVESTIGATE_PAYMENT');
		$mail->setFrom($contactEmail, $contactName);
		$mail->addRecipient($contactEmail, $contactName);

		$paymentStatus = $request->getUserVar('payment_status');

		switch (array_shift($args)) {
			case 'ipn':
				// Build a confirmation transaction.
				$req = 'cmd=_notify-validate';
				if (get_magic_quotes_gpc()) {
					foreach ($_POST as $key => $value) $req .= '&' . urlencode(stripslashes($key)) . '=' . urlencode(stripslashes($value));
				} else {
					foreach ($_POST as $key => $value) $req .= '&' . urlencode($key) . '=' . urlencode($value);	
				}
				// Create POST response
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $this->getSetting($journal->getId(), 'paypalurl'));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded', 'Content-Length: ' . strlen($req)));
				curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
				$ret = curl_exec ($ch);
				$curlError = curl_error($ch);
				curl_close ($ch);

				// Check the confirmation response and handle as necessary.
				if (strcmp($ret, 'VERIFIED') == 0) switch ($paymentStatus) {
					case 'Completed':
						$payPalDao =& DAORegistry::getDAO('PayPalDAO');
						$transactionId = $request->getUserVar('txn_id');
						if ($payPalDao->transactionExists($transactionId)) {
							// A duplicate transaction was received; notify someone.
							$mail->assignParams(array(
								'journalName' => $journal->getLocalizedTitle(),
								'postInfo' => print_r($_POST, true),
								'additionalInfo' => "Duplicate transaction ID: $transactionId",
								'serverVars' => print_r($_SERVER, true)
							));
							$mail->send();
							exit();
						} else {
							// New transaction succeeded. Record it.
							$payPalDao->insertTransaction(
								$transactionId,
								$request->getUserVar('txn_type'),
								String::strtolower($request->getUserVar('payer_email')),
								String::strtolower($request->getUserVar('receiver_email')),
								$request->getUserVar('item_number'),
								$request->getUserVar('payment_date'),
								$request->getUserVar('payer_id'),
								$request->getUserVar('receiver_id')
							);
							$queuedPaymentId = $request->getUserVar('custom');

							import('classes.payment.ojs.OJSPaymentManager');
							$ojsPaymentManager = new OJSPaymentManager($request);

							// Verify the cost and user details as per PayPal spec.
							$queuedPayment =& $ojsPaymentManager->getQueuedPayment($queuedPaymentId);
							if (!$queuedPayment) {
								// The queued payment entry is missing. Complain.
								$mail->assignParams(array(
									'journalName' => $journal->getLocalizedTitle(),
									'postInfo' => print_r($_POST, true),
									'additionalInfo' => "Missing queued payment ID: $queuedPaymentId",
									'serverVars' => print_r($_SERVER, true)
								));
								$mail->send();
								exit();
							}

							//NB: if/when paypal subscriptions are enabled, these checks will have to be adjusted
							// because subscription prices may change over time
							if (
								(($queuedAmount = $queuedPayment->getAmount()) != ($grantedAmount = $request->getUserVar('mc_gross')) && $queuedAmount > 0) ||
								($queuedCurrency = $queuedPayment->getCurrencyCode()) != ($grantedCurrency = $request->getUserVar('mc_currency')) ||
								($grantedEmail = String::strtolower($request->getUserVar('receiver_email'))) != ($queuedEmail = String::strtolower($this->getSetting($journal->getId(), 'selleraccount')))
							) {
								// The integrity checks for the transaction failed. Complain.
								$mail->assignParams(array(
									'journalName' => $journal->getLocalizedTitle(),
									'postInfo' => print_r($_POST, true),
									'additionalInfo' =>
										"Granted amount: $grantedAmount\n" .
										"Queued amount: $queuedAmount\n" .
										"Granted currency: $grantedCurrency\n" .
										"Queued currency: $queuedCurrency\n" .
										"Granted to PayPal account: $grantedEmail\n" .
										"Configured PayPal account: $queuedEmail",
									'serverVars' => print_r($_SERVER, true)
								));
								$mail->send();
								exit();
							}

							// Update queued amount if amount set by user (e.g. donation)
							if ($queuedAmount == 0 && $grantedAmount > 0) {
								$queuedPaymentDao =& DAORegistry::getDAO('QueuedPaymentDAO');
								$queuedPayment->setAmount($grantedAmount);
								$queuedPayment->setCurrencyCode($grantedCurrency);
								$queuedPaymentDao->updateQueuedPayment($queuedPaymentId, $queuedPayment);
							}

							// Fulfill the queued payment.
							if ($ojsPaymentManager->fulfillQueuedPayment($queuedPayment, $this->getName())) exit();
							
							// If we're still here, it means the payment couldn't be fulfilled.
							$mail->assignParams(array(
								'journalName' => $journal->getLocalizedTitle(),
								'postInfo' => print_r($_POST, true),
								'additionalInfo' => "Queued payment ID $queuedPaymentId could not be fulfilled.",
								'serverVars' => print_r($_SERVER, true)
							));
							$mail->send();
						}
						exit();
					case 'Pending':
						// Ignore.
						exit();
					default:
						// An unhandled payment status was received; notify someone.
						$mail->assignParams(array(
							'journalName' => $journal->getLocalizedTitle(),
							'postInfo' => print_r($_POST, true),
							'additionalInfo' => "Payment status: $paymentStatus",
							'serverVars' => print_r($_SERVER, true)
						));
						$mail->send();
						exit();
				} else {
					// An unknown confirmation response was received; notify someone.
					$mail->assignParams(array(
						'journalName' => $journal->getLocalizedTitle(),
						'postInfo' => print_r($_POST, true),
						'additionalInfo' => "Confirmation return: $ret\nCURL error: $curlError",
						'serverVars' => print_r($_SERVER, true)
					));
					$mail->send();
					exit();
				}

				break;
			case 'cancel':
				AppLocale::requireComponents(LOCALE_COMPONENT_PKP_COMMON, LOCALE_COMPONENT_PKP_USER, LOCALE_COMPONENT_APPLICATION_COMMON);
				$templateMgr->assign(array(
					'currentUrl' => $request->url(null, 'index'),
					'pageTitle' => 'plugins.paymethod.paypal.purchase.cancelled.title',
					'message' => 'plugins.paymethod.paypal.purchase.cancelled',
					'backLink' => $request->getUserVar('ojsReturnUrl'),
					'backLinkLabel' => 'common.continue'
				));
				$templateMgr->display('common/message.tpl');
				exit();
				break;
		}
		parent::handle($args, $request); // Don't know what to do with it
	}

	/**
	 * @see Plugin::getInstallSchemaFile
	 */
	function getInstallSchemaFile() {
		return ($this->getPluginPath() . DIRECTORY_SEPARATOR . 'schema.xml');
	}

	/**
	 * @see getIntsallEmailTemplatesFile
	 */
	function getInstallEmailTemplatesFile() {
		return ($this->getPluginPath() . DIRECTORY_SEPARATOR . 'emailTemplates.xml');
	}

	/**
	 * @see getInstallEmailTemplateDataFile
	 */
	function getInstallEmailTemplateDataFile() {
		return ($this->getPluginPath() . '/locale/{$installedLocale}/emailTemplates.xml');
	}
}

?>
