<?php

/**
 * @file DonationsHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package donations
 * @class DonationsHandler
 *
 * Display a form for accepting donations
 *
 */
 
import('core.Handler');

class DonationsHandler extends Handler {
	function index( $args ) {
		import('payment.ojs.OJSPaymentManager');
		$paymentManager =& OJSPaymentManager::getManager();
		$journal =& Request::getJournal();

		if (!Validation::isLoggedIn()) {
			Validation::redirectLogin("payment.loginRequired.forDonation");
		}

		$user =& Request::getUser();

		$queuedPayment =& $paymentManager->createQueuedPayment($journal->getJournalId(), PAYMENT_TYPE_DONATION, $user->getUserId(), 0, 0);
		$queuedPaymentId = $paymentManager->queuePayment($queuedPayment);
	
		$paymentManager->displayPaymentForm($queuedPaymentId, $queuedPayment);		
	}	
	
	function thankYou( $args ) {
		$templateMgr =& TemplateManager::getManager();
		$journal =& Request::getJournal();
		
		$templateMgr->assign(array(
			'currentUrl' => Request::url(null, null, 'donations'),
			'pageTitle' => 'donations.thankYou',
			'journalName' => $journal->getJournalTitle(),
			'message' => 'donations.thankYouMessage'
		));
		$templateMgr->display('common/message.tpl');
	}
}

?>
