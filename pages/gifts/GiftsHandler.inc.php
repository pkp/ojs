<?php

/**
 * @file pages/gifts/GiftsHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GiftsHandler
 * @ingroup gift
 *
 * @brief Handle requests to buy gifts
 */

import('classes.handler.Handler');

class GiftsHandler extends Handler {

	/**
	 * Display payment form for buying a gift subscription
	 * @param $args array
	 * @param $request PKPRequest
	 */	
	function purchaseGiftSubscription($args, $request) {
		$journal = $request->getJournal();
		if (!$journal) $request->redirect(null, 'index');

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($request);
		$acceptSubscriptionPayments = $paymentManager->acceptGiftSubscriptionPayments();
		if (!$acceptSubscriptionPayments) $request->redirect(null, 'index');

		$this->setupTemplate();

		import('classes.subscription.form.GiftIndividualSubscriptionForm');
		$giftSubscriptionForm = new GiftIndividualSubscriptionForm($request);
		$giftSubscriptionForm->initData();
		$giftSubscriptionForm->display();
	}

	/**
	 * Process payment form for buying a gift subscription
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function payPurchaseGiftSubscription($args, $request) {
		$journal = $request->getJournal();
		if (!$journal) $request->redirect(null, 'index');

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($request);
		$acceptSubscriptionPayments = $paymentManager->acceptGiftSubscriptionPayments();
		if (!$acceptSubscriptionPayments) $request->redirect(null, 'index');

		$this->setupTemplate();
		$user = $request->getUser();

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
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function thankYou($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$this->setupTemplate();
		$journal = $request->getJournal();

		$templateMgr->assign(array(
			'currentUrl' => $request->url(null, null, 'gifts'),
			'pageTitle' => 'gifts.thankYou',
			'journalName' => $journal->getLocalizedName(),
			'message' => 'gifts.thankYouMessage'
		));
		$templateMgr->display('frontend/pages/message.tpl');
	}
}

?>
