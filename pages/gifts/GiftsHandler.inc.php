<?php

/**
 * @defgroup gifts
 */

/**
 * @file pages/gifts/GiftsHandler.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GiftsHandler
 * @ingroup gifts
 *
 * @brief Handle requests to buy gifts
 */

import('classes.handler.Handler');

class GiftsHandler extends Handler {
	/**
	 * Constructor
	 **/
	function GiftsHandler() {
		parent::Handler();
	}

	/**
	 * Display payment form for buying a gift subscription
	 */	
	function purchaseGiftSubscription($args, $request) {
		$journal =& Request::getJournal();
		if (!$journal) Request::redirect(null, 'index');

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager =& OJSPaymentManager::getManager();
		$acceptSubscriptionPayments = $paymentManager->acceptGiftSubscriptionPayments();
		if (!$acceptSubscriptionPayments) Request::redirect(null, 'index');

		$this->setupTemplate();

		import('classes.subscription.form.GiftIndividualSubscriptionForm');
		$giftSubscriptionForm = new GiftIndividualSubscriptionForm();
		$giftSubscriptionForm->initData();
		$giftSubscriptionForm->display();
	}

	/**
	 * Process payment form for buying a gift subscription
	 */
	function payPurchaseGiftSubscription($args, $request) {
		$journal =& Request::getJournal();
		if (!$journal) Request::redirect(null, 'index');

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager =& OJSPaymentManager::getManager();
		$acceptSubscriptionPayments = $paymentManager->acceptGiftSubscriptionPayments();
		if (!$acceptSubscriptionPayments) Request::redirect(null, 'index');

		$this->setupTemplate();
		$journalId = $journal->getId();
		$user =& Request::getUser();

		// If buyer is logged in, save buyer user id as part of gift details
		if ($user) {
			$buyerUserId = $user->getId();
		} else {
			$buyerUserId = null;
		}

		import('classes.subscription.form.GiftIndividualSubscriptionForm');
		$giftSubscriptionForm = new GiftIndividualSubscriptionForm($buyerUserId);
		$giftSubscriptionForm->readInputData();

		if ($giftSubscriptionForm->validate()) {
			$giftSubscriptionForm->execute();
		} else {
			$giftSubscriptionForm->display();
		}
	}

	/**
	 * Display generic thank you message following payment
	 */
	function thankYou($args, $request) {
		$templateMgr =& TemplateManager::getManager();
		$this->setupTemplate();
		$journal =& Request::getJournal();

		$templateMgr->assign(array(
			'currentUrl' => Request::url(null, null, 'gifts'),
			'pageTitle' => 'gifts.thankYou',
			'journalName' => $journal->getLocalizedTitle(),
			'message' => 'gifts.thankYouMessage'
		));
		$templateMgr->display('common/message.tpl');
	}
}

?>
