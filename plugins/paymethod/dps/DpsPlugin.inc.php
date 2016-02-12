<?php

/**
 * @file plugins/paymethod/dps/DPSPlugin.inc.php
 *
 * Robert Carter <r.carter@auckland.ac.nz>
 *
 * Based on the work of these people:
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2006-2009 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DPSPlugin
 * @ingroup plugins_paymethod_dps
 *
 * @brief DPS Paymethod plugin class
 */

import('classes.plugins.PaymethodPlugin');

class DPSPlugin extends PaymethodPlugin {
	/**
	 * Constructor
	 */
	function DPSPlugin() {
		parent::PaymethodPlugin();
	}

	/**
	 * Get the Plugin's internal name
	 * @return String
	 */
	function getName() {
		return 'DPS';
	}

	/**
	 * Get the Plugin's display name
	 * @return String
	 */	
	function getDisplayName() {
		return __('plugins.paymethod.dps.displayName');
	}

	/**
	 * Get a description of the plugin
	 * @return String
	 */
	function getDescription() {
		return __('plugins.paymethod.dps.description');
	}   

	/**
	 * Register plugin
	 * @return bool
	 */
	function register($category, $path) {
		if (parent::register($category, $path)) {
			if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return true;
			$this->addLocaleData();
			/*
			$this->import('DPSDAO');
			$dpsDao = new DPSDAO();
			DAORegistry::registerDAO('DPSDAO', $dpsDao);
			*/
			return true;
		}
		return false;
	}

	/**
	 * Get an array of the fields in the settings form
	 * @return array
	 */
	function getSettingsFormFieldNames() {
		return array('dpsmerchant', 'dpsurl', 'dpsuser', 'dpskey', 'dpscertpath');
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

		// print_r($user);
		
		$params = array(
			// 'charset' => Config::getVar('i18n', 'client_charset'),

		// TODO figure out what these keys mean eg. business

			'item_name' => $queuedPayment->getName(),
			'item_description' => $queuedPayment->getDescription(), 
			'amount' => sprintf('%.2F', $queuedPayment->getAmount()),
			// 'quantity' => 1,
			// 'no_note' => 1,
			// 'no_shipping' => 1,
			// 'currency_code' => $queuedPayment->getCurrencyCode(),
			// 'lc' => String::substr(AppLocale::getLocale(), 3), 
			'custom' => $queuedPaymentId,
			// 'notify_url' => $request->url(null, 'payment', 'plugin', array($this->getName(), 'purchase')),  
			// 'return' => $queuedPayment->getRequestUrl(),
			// 'cancel_return' => $request->url(null, 'payment', 'plugin', array($this->getName(), 'cancel')),
			// 'first_name' => ($user)?$user->getFirstName():'',  
			// 'last_name' => ($user)?$user->getLastname():'',
			// 'item_number' => $queuedPayment->getAssocId(),
			// 'cmd' => '_xclick'
		);

		AppLocale::requireComponents(LOCALE_COMPONENT_APPLICATION_COMMON);
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('params', $params);
		$templateMgr->assign('dpsFormPostUrl', $request->url(null, 'payment', 'plugin', array($this->getName(), 'purchase')));
		$templateMgr->display($this->getTemplatePath() . 'paymentForm.tpl');
		return true;
	}

	/**
	 * Handle incoming requests/notifications
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function handle($args, &$request) {

		$user =& $request->getUser();
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
		$mail = new MailTemplate('DPS_INVESTIGATE_PAYMENT');
		$mail->setReplyTo(null);
		$mail->addRecipient($contactEmail, $contactName);

		$paymentStatus = $request->getUserVar('payment_status');

		session_start();

		switch (array_shift($args)) {

			case 'purchase':

			    error_log("Forming XML for transaction API call");

				try {
			    	$_SESSION['amount'] = $amount = $request->getUserVar('amount');
			    	$_SESSION['id'] = $orderId = $request->getUserVar('custom');

					$domDoc = new DOMDocument('1.0', 'UTF-8');
					$rootElt = $domDoc->createElement('GenerateRequest');
					$rootNode = $domDoc->appendChild($rootElt);
					$rootNode->appendChild($domDoc->createElement('PxPayUserId', $this->getSetting($journal->getId(), 'dpsuser')));
					$rootNode->appendChild($domDoc->createElement('PxPayKey', $this->getSetting($journal->getId(), 'dpskey')));
			        $rootNode->appendChild($domDoc->createElement('MerchantReference', $this->getSetting($journal->getId(), 'dpsmerchant')));
					$rootNode->appendChild($domDoc->createElement('AmountInput', $amount));
					$rootNode->appendChild($domDoc->createElement('CurrencyInput', 'NZD'));
					$rootNode->appendChild($domDoc->createElement('TxnType', 'Purchase'));
			        $rootNode->appendChild($domDoc->createElement('TxnData1', $orderId));
			        $rootNode->appendChild($domDoc->createElement('TxnData2', $user->getUserName()));
					$rootNode->appendChild($domDoc->createElement('EmailAddress', $user->getEmail() ));
					$rootNode->appendChild($domDoc->createElement('UrlSuccess', $request->url(null, 'payment', 'plugin', array($this->getName(), 'success'))));
					$rootNode->appendChild($domDoc->createElement('UrlFail', $request->url(null, 'payment', 'plugin', array($this->getName(), 'failure'))));

					$xmlRequest = $domDoc->saveXML();
					if (!$xmlRequest) {
            			throw new Exception("DPS: Generating XML API call failed ", "119");
            		}

            		error_log("xmlrequest: ".print_r($xmlRequest,true));

		            $ch = curl_init();

		            curl_setopt($ch, CURLOPT_URL, $this->getSetting($journal->getId(), 'dpsurl'));
		            curl_setopt($ch, CURLOPT_POST, 1);
		            curl_setopt($ch, CURLOPT_POSTFIELDS, $domDoc->saveXML());
		            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
		            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		            curl_setopt($ch, CURLOPT_CAINFO, $this->getSetting($journal->getId(), 'dpscertpath'));
		            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,5); 
					curl_setopt($ch, CURLOPT_TIMEOUT, 10);

		            $result = curl_exec($ch);
					$curlError = curl_error($ch);
					$curlErrorNo = curl_errno($ch);
					curl_close ($ch);

					# check that we got a response
				    if ($result == false) {
		                error_log("DPS error: $curlError ($curlErrorNo)");
		                throw new Exception("DPS error: $curlError", $curlErrorNo);
		            }

		            # make sure response is valid.
	                error_log("Parsing response XML");
	                libxml_use_internal_errors(true);
	                $rexml = simplexml_load_string($result);
	                error_log("XML response: ".print_r($rexml,true));

	                if (!$rexml) {
	                	error_log("Invalid XML response from DPS");
	                	throw new Exception("Invalid XML response from DPS");
					}  

                    # check URL exists in response
                    if (!isset($rexml->URI[0])) {
                    	throw new Exception("URI not returned: ".$rexml->ResponseText[0]);
                    }

                    $payment_url = (string) $rexml->URI[0];

                    # redirect to that URL
                    header("Location: $payment_url");
                    exit;

		        } catch (exception $e) {
					curl_close($ch);
					error_log("Fatal error with credit card entry stage: ".$e->getCode().": ".$e->getMessage());

					# create a notification about this error
					$params = array('contents' => "Fatal error with DPS response stage: ".$e->getMessage().". User:".$user->getUserName().". Email:".$user->getEmail().".");

					if(!$this->sendNotifications($params, $request)) {
						error_log("Failed to send notifications to journal managers");
					}

					AppLocale::requireComponents(LOCALE_COMPONENT_APPLICATION_COMMON);
					$templateMgr =& TemplateManager::getManager();
					$templateMgr->assign(array(
						'pageTitle' => 'plugins.paymethod.dps.purchase.failure.title',
						'detail' => $e->getMessage(),
						'backLink' => $request->url(null, 'user', 'subscriptions'),
					));
					$templateMgr->display($this->getTemplatePath() . 'failure.tpl');
	
					exit();
				}
				break;


			case 'success':
				try {
			        error_log("Forming XML ProcessResponse");
			        $domDoc = new DOMDocument('1.0', 'UTF-8');
			        $rootElt = $domDoc->createElement('ProcessResponse');
			        $rootNode = $domDoc->appendChild($rootElt);
   					$rootNode->appendChild($domDoc->createElement('PxPayUserId', $this->getSetting($journal->getId(), 'dpsuser')));
					$rootNode->appendChild($domDoc->createElement('PxPayKey', $this->getSetting($journal->getId(), 'dpskey')));
			        $rootNode->appendChild($domDoc->createElement('Response', $request->getUserVar('result')));
			        $xmlRequest = $domDoc->saveXML();

			        if (!$xmlRequest) {
			            throw new Exception("Failed to generate XML transaction response");
			        }

					# send confirmation to DPS
					error_log("Forming curl API request");
					$ch = curl_init();

		            curl_setopt($ch, CURLOPT_URL, $this->getSetting($journal->getId(), 'dpsurl'));
					curl_setopt($ch, CURLOPT_POST, 1);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $domDoc->saveXML());
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
					curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		            curl_setopt($ch, CURLOPT_CAINFO, $this->getSetting($journal->getId(), 'dpscertpath'));
		            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,5); 
					curl_setopt($ch, CURLOPT_TIMEOUT, 10);

					# check response is OK
		            $result = curl_exec($ch);
					$curlError = curl_error($ch);
					$curlErrorNo = curl_errno($ch);
					curl_close ($ch);

				    if ($result == false) {
		                error_log("Transaction response call failed: $curlError ($curlErrorNo)");
		                throw new Exception("Transaction response call failed: $curlError", $curlErrorNo);
		            }

	                error_log("Processing response");
	                libxml_use_internal_errors(true);
	
	                $rexml = simplexml_load_string($result);
	
	                # check xml is valid
	                if (!$rexml) {
	                    throw new Exception('Response from DPS not valid XML ', '130');
					}

					# check for success value
					if ($rexml->Success[0] == null) {
                        throw new Exception('Response code not returned by DPS', '130');
                    }

					# check for failed transaction
					$code = (int)$rexml->Success[0];
					if ($code != 1) {
                        throw new Exception('Transaction failed: '.$rexml->ResponseText[0]);
					}

                    if ($rexml->ResponseText[0] == null) {
                        throw new Exception('OJS: DPS: Response text not returned from transaction confirmation');
                    }

                    if ($rexml->TxnId[0] == null) {
                        throw new Exception('OJS: DPS: Reference number (txnId) not returned from transaction confirmation');
                    }

                    if ($rexml->MerchantReference[0]==null){
                        throw new Exception('OJS: DPS: Merchant reference not returned from transaction confirmation');
                    }

                    error_log("Response indicates success from DPS");

                    # sanity / double checks

                    # get access to queuedPayment to check that details match
					$queuedPaymentId = $_SESSION['id'];

					import('classes.payment.ojs.OJSPaymentManager');
					$ojsPaymentManager = new OJSPaymentManager($request);

					$queuedPayment =& $ojsPaymentManager->getQueuedPayment($queuedPaymentId);
					if (!$queuedPayment) {
                        throw new Exception("OJS: DPS: No order for this transaction or transaction ID lost from session. See DPS statement for OJS order number: TxnData1.");
					}

					error_log(print_r($queuedPayment, true));

					$amount = $_SESSION['amount'];
                    $paidAmount = (string) $rexml->AmountSettlement[0];
                    $pattern = "/[0-9]+(\.[0-9]{2})?/";

                    if ($paidAmount == null) {
                        throw new Exception('Paid amount not returned by DPS', '160');
                    } 

                    if (!preg_match($pattern, $paidAmount)) { 
                    	# check format whether correct for paid amount, etc: no negative 
                        throw new Exception('Paid amount format error: negative? badly formed decimal: '.$paidAmount, '170');
                    } 

                    # check userid returned by DPS
                    if ($rexml->TxnData2[0] == null) {  
                        throw new Exception('Validation failure due to user id is not returned by DPS', '180');
                    }

                    # check user id and amount match
                    if (number_format($paidAmount, 2, '.', '') != $_SESSION['amount']) {
                    	throw new Exception('Payment amount mismatch on transaction. Expected: '.$_SESSION['amount'].' got: '.$paidAmount);
                    }

                    $userId = (string) $rexml->TxnData2[0];
                    if ($user->getUserName() != $userId) {
                    	throw new Exception('User identity mismatch on transaction. Expected: '.$user->getUserName().' got: '.$userId);
					}

                    # clear session vars - avoid replay
                    unset($_SESSION['amount']);
                    unset($_SESSION['id']);
   
					# tick off queued payment as paid
					if (!$ojsPaymentManager->fulfillQueuedPayment($queuedPayment, $this->getName())) {
                    	throw new Exception('Could not fulill the queued payment in OJS');
					}

                    error_log("All validation tests pass. Transaction is OK.");

					# show success page with details

					AppLocale::requireComponents(LOCALE_COMPONENT_PKP_COMMON, LOCALE_COMPONENT_PKP_USER, LOCALE_COMPONENT_APPLICATION_COMMON);
					$templateMgr->assign(array(
						'pageTitle' => 'plugins.paymethod.dps.purchase.success.title',
						'message' => 'plugins.paymethod.dps.purchase.success',
						'backLink' => $request->url(null, 'index'),
						'backLinkLabel' => 'common.continue'
					));
					$templateMgr->display($this->getTemplatePath() . 'success.tpl');

					exit();
					break;

				} catch (exception $e) {
					@curl_close($ch);
					error_log("Fatal error with payment processing stage: ".$e->getCode().": ".$e->getMessage());

					# make notification
					$params = array('contents' => "Fatal error with DPS response stage: ".$e->getMessage().". User:".$user->getUserName().". Email:".$user->getEmail().".");

					if(!$this->sendNotifications($params, $request)) {
						error_log("Failed to send notifications to journal managers");
					}

					# render failure page to user
					AppLocale::requireComponents(LOCALE_COMPONENT_APPLICATION_COMMON);
					$templateMgr =& TemplateManager::getManager();
					$templateMgr->assign(array(
						'pageTitle' => 'plugins.paymethod.dps.purchase.failure.title',
						'detail' => $e->getMessage(),
						'backLink' => $request->url(null, 'user', 'subscriptions'),
					));
					$templateMgr->display($this->getTemplatePath() . 'failure.tpl');

					exit();
				}

				break;

				# DPS requested our failure URL - eg user canceled form
				case 'failure':
					AppLocale::requireComponents(LOCALE_COMPONENT_APPLICATION_COMMON);
					$templateMgr =& TemplateManager::getManager();
					$templateMgr->assign(array(
						'pageTitle' => 'plugins.paymethod.dps.purchase.failure.title',
						'backLink' => $request->url(null, 'user', 'subscriptions'),
					));
					$templateMgr->display($this->getTemplatePath() . 'cancel.tpl');
				break;


		}
		parent::handle($args, $request); // Don't know what to do with it
	}

	/**
	 * Send notifications to journal managers
	 * @param $params array
	 * @param $request PKPRequest
	 */
	function sendNotifications($params = array('contents' => ''), $request) {
		
		$journal =& $request->getJournal();
		$user =& $request->getUser();

		# create a notification 
        import('classes.notification.NotificationManager');
        $notificationManager = new NotificationManager();

		# send a notification to each journal manager
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$journalManagers =& $roleDao->getUsersByRoleId(ROLE_ID_JOURNAL_MANAGER, $journal->getId());
		while ($journalManagers && !$journalManagers->eof()) {
		        $journalManager =& $journalManagers->next();
		        $notification = $notificationManager->createNotification($request, $journalManager->getId(), NOTIFICATION_TYPE_ERROR, $journal->getId(), ASSOC_TYPE_JOURNAL, $journal->getId(), NOTIFICATION_LEVEL_NORMAL, $params);
		        // $notificationManager->sendNotificationEmail($request, $notification);
		        unset($journalManager);
		}
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