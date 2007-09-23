<?php

/**
 * @file PayPalPlugin.inc.php
 *
 * Copyright (c) 2006-2007 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.paymethod.paypal
 * @class PayPalPlugin
 *
 * PayPal Paymethod plugin class
 *
 */

import('classes.plugins.PaymethodPlugin');

class PayPalPlugin extends PaymethodPlugin {

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
		return Locale::translate('plugins.paymethod.paypal.displayName');
	}

	/**
	 * Get a description of the plugin
	 * @return String
	 */
	function getDescription() {
		return Locale::translate('plugins.paymethod.paypal.description');
	}   

	/**
	 * Register plugin
	 * @return bool
	 */
	function register($category, $path) {
		if (parent::register($category, $path)) {			
			$this->addLocaleData();
			$this->import('PayPalDAO');
			$payPalDao =& new PayPalDAO();
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
			$setting = $this->getSetting($journal->getJournalId(), $settingName);
			if (empty($setting)) return false;
		}
		return true;
	}

	/**
	 * Display hte settings form
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
	 */
	function displayPaymentForm($queuedPaymentId, &$queuedPayment) {
		if (!$this->isConfigured()) return false;
		$journal =& Request::getJournal();
		$user =& Request::getUser();

		$params = array(
			'business' => $this->getSetting($journal->getJournalId(), 'selleraccount'),
			'item_name' => $queuedPayment->getDescription(),
			'amount' => $queuedPayment->getAmount(),
			'quantity' => 1,
			'no_note' => 1,
			'no_shipping' => 1,
			'currency_code' => $queuedPayment->getCurrencyCode(),
			'lc' => String::substr(Locale::getLocale(), 3), 
			'custom' => $queuedPaymentId,
			'notify_url' => Request::url(null, 'payment', 'plugin', array($this->getName(), 'ipn')),  
			'return' => $queuedPayment->getRequestUrl(),
			'cancel_return' => Request::url(null, 'payment', 'plugin', array($this->getName(), 'cancel')),
			'first_name' => ($user)?$user->getFirstName():'',  
			'last_name' => ($user)?$user->getLastname():'',
			'item_number' => $queuedPayment->getAssocId(),
			'cmd' => '_xclick'
		);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('params', $params);
		$templateMgr->assign('paypalFormUrl', $this->getSetting($journal->getJournalId(), 'paypalurl'));
		$templateMgr->display($this->getTemplatePath() . 'paymentForm.tpl');
	}

	/**
	 * Handle incoming requests/notifications
	 */
	function handle($args) {
		$templateMgr =& TemplateManager::getManager();
		$journal =& Request::getJournal();
		if (!$journal) return parent::handle($args);

		// Just in case we need to contact someone
		import('mail.MailTemplate');
		$contactName = $journal->getSetting('contactName');
		$contactEmail = $journal->getSetting('contactEmail');
		$mail = &new MailTemplate('PAYPAL_INVESTIGATE_PAYMENT');
		$mail->setFrom($contactEmail, $contactName);
		$mail->addRecipient($contactEmail, $contactName);

		$paymentStatus = Request::getUserVar('payment_status');

		switch (array_shift($args)) {
			case 'ipn':
				// Build a confirmation transaction.
				$req = 'cmd=_notify-validate';
				foreach ($_POST as $key => $value) $req .= '&' . urlencode($key) . '=' . urlencode($value);
				// Create POST response
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $this->getSetting($journal->getJournalId(), 'paypalurl'));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_HTTPHEADER, Array('Content-Type: application/x-www-form-urlencoded', 'Content-Length: ' . strlen($req)));
				curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
				$ret = curl_exec ($ch);
				curl_close ($ch);

				// Check the confirmation response and handle as necessary.
				if (strcmp($ret, 'VERIFIED') == 0) switch ($paymentStatus) {
					case 'Completed':
						$payPalDao =& DAORegistry::getDAO('PayPalDAO');
						$transactionId = Request::getUserVar('txn_id');
						if ($payPalDao->transactionExists($transactionId)) {
							// A duplicate transaction was received; notify someone.
							$mail->assignParams(array(
								'journalName' => $journal->getJournalTitle(),
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
								Request::getUserVar('txn_type'),
								Request::getUserVar('payer_email'),
								Request::getUserVar('receiver_email'),
								Request::getUserVar('item_number'),
								Request::getUserVar('payment_date'),
								Request::getUserVar('payer_id'),
								Request::getUserVar('receiver_id')
							);
							$queuedPaymentId = Request::getUserVar('custom');

							import('payment.ojs.OJSPaymentManager');
							$ojsPaymentManager =& OJSPaymentManager::getManager();

							// Verify the cost and user details as per PayPal spec.
							$queuedPayment =& $ojsPaymentManager->getQueuedPayment($queuedPaymentId);
							if (!$queuedPayment) {
								// The queued payment entry is missing. Complain.
								$mail->assignParams(array(
									'journalName' => $journal->getJournalTitle(),
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
								(($queuedAmount = $queuedPayment->getAmount()) != ($grantedAmount = Request::getUserVar('mc_gross')) && $queuedAmount > 0) ||
								($queuedCurrency = $queuedPayment->getCurrencyCode()) != ($grantedCurrency = Request::getUserVar('mc_currency')) ||
								($grantedEmail = Request::getUserVar('receiver_email')) != ($queuedEmail = $this->getSetting($journal->getJournalId(), 'selleraccount'))
							) {
								// The integrity checks for the transaction failed. Complain.
								$mail->assignParams(array(
									'journalName' => $journal->getJournalTitle(),
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

							// Fulfill the queued payment.
							if ($ojsPaymentManager->fulfillQueuedPayment($queuedPayment, $this->getName())) exit();
							
							// If we're still here, it means the payment couldn't be fulfilled.
							$mail->assignParams(array(
								'journalName' => $journal->getJournalTitle(),
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
							'journalName' => $journal->getJournalTitle(),
							'postInfo' => print_r($_POST, true),
							'additionalInfo' => "Payment status: $paymentStatus",
							'serverVars' => print_r($_SERVER, true)
						));
						$mail->send();
						exit();
				} else {
					// An unknown confirmation response was received; notify someone.
					$mail->assignParams(array(
						'journalName' => $journal->getJournalTitle(),
						'postInfo' => print_r($_POST, true),
						'additionalInfo' => "Confirmation return: $ret",
						'serverVars' => print_r($_SERVER, true)
					));
					$mail->send();
					exit();
				}

				break;
			case 'cancel':
				$templateMgr->assign(array(
					'currentUrl' => Request::url(null, null, 'index'),
					'pageTitle' => 'plugins.paymethod.paypal.purchase.cancelled.title',
					'message' => 'plugins.paymethod.paypal.purchase.cancelled',
					'backLink' => Request::getUserVar('ojsReturnUrl'),
					'backLinkLabel' => 'common.continue'
				));
				$templateMgr->display('common/message.tpl');
				exit();
				break;
		}
		parent::handle($args); // Don't know what to do with it
	}

	function getInstallSchemaFile() {
		return ($this->getPluginPath() . DIRECTORY_SEPARATOR . 'schema.xml');
	}

	function getInstallDataFile() {
		return ($this->getPluginPath() . DIRECTORY_SEPARATOR . 'data.xml');
	}
}

?>
