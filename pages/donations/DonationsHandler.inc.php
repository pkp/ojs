<?php

/**
 * @defgroup donations
 */
 
/**
 * @file pages/donations/DonationsHandler.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DonationsHandler
 * @ingroup donations
 *
 * @brief Display a form for accepting donations
 *
 */

import('classes.handler.Handler');

class DonationsHandler extends Handler {
	/**
	 * Constructor
	 */
	function DonationsHandler() {
		parent::Handler();
	}

	/**
	 * Display the donations page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function index($args, &$request) {
		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($request);
		$journal =& $request->getJournal();

		if (!Validation::isLoggedIn()) {
			Validation::redirectLogin('payment.loginRequired.forDonation');
		}

		$user =& $request->getUser();

		$queuedPayment =& $paymentManager->createQueuedPayment($journal->getId(), PAYMENT_TYPE_DONATION, $user->getId(), 0, 0);
		$queuedPaymentId = $paymentManager->queuePayment($queuedPayment);
	
		$paymentManager->displayPaymentForm($queuedPaymentId, $queuedPayment);
	}	

	/**
	 * Display a "thank you" page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function thankYou($args, &$request) {
		$templateMgr =& TemplateManager::getManager();
		$this->setupTemplate();
		$journal =& $request->getJournal();
		
		$templateMgr->assign(array(
			'currentUrl' => $request->url(null, null, 'donations'),
			'pageTitle' => 'donations.thankYou',
			'journalName' => $journal->getLocalizedTitle(),
			'message' => 'donations.thankYouMessage'
		));
		$templateMgr->display('common/message.tpl');
	}
}

?>
