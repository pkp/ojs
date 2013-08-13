<?php

/**
 * @file classes/payment/ojs/OJSPaymentAction.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OJSPaymentAction
 * @ingroup payments
 *
 * Common actions for payment management functions.
 */

class OJSPaymentAction {
	/**
	 * Display Payments Settings Form (main payments page)
	 */
	function payments($args, $request) {
		import('classes.payment.ojs.form.PaymentSettingsForm');
		$form = new PaymentSettingsForm();

		$journal = $request->getJournal();
		$templateMgr = TemplateManager::getManager($request);

		if ($form->isLocaleResubmit()) {
			$form->readInputData();
		} else {
			$form->initData();
		}
		$form->display();
	}

	/**
	 * Execute the form or display it again if there are problems
	 */
	function savePaymentSettings($args, $request) {
		import('classes.payment.ojs.form.PaymentSettingsForm');
		$settingsForm = new PaymentSettingsForm();

		$journal = $request->getJournal();
		$templateMgr = TemplateManager::getManager($request);

		$settingsForm->readInputData();

		if ($settingsForm->validate()) {
			$settingsForm->save();
			return true;
		} else {
			$settingsForm->display();
			return false;
		}
	}

	/**
	 * Display all payments previously made
	 */
	function viewPayments($args, $request) {
		$rangeInfo = Handler::getRangeInfo($request, 'payments');
		$paymentDao = DAORegistry::getDAO('OJSCompletedPaymentDAO');
		$journal = $request->getJournal();
		$templateMgr = TemplateManager::getManager($request);
		$payments = $paymentDao->getPaymentsByJournalId($journal->getId(), $rangeInfo);
		$individualSubscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO');
		$institutionalSubscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO');
		$userDao = DAORegistry::getDAO('UserDAO');

		$templateMgr->assign('isJournalManager', Validation::isJournalManager($journal->getId()));
		$templateMgr->assign('individualSubscriptionDao', $individualSubscriptionDao);
		$templateMgr->assign('institutionalSubscriptionDao', $institutionalSubscriptionDao);
		$templateMgr->assign('userDao', $userDao);
		$templateMgr->assign('payments', $payments);

		$templateMgr->display('payments/viewPayments.tpl');
	}

	/**
	 * Display a single Completed payment
	 */
	function viewPayment($args, $request) {
		$paymentDao = DAORegistry::getDAO('OJSCompletedPaymentDAO');
		$completedPaymentId = $args[0];
		$payment = $paymentDao->getCompletedPayment($completedPaymentId);

		$journal = $request->getJournal();
		$templateMgr = TemplateManager::getManager($request);
		$individualSubscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO');
		$institutionalSubscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO');
		$userDao = DAORegistry::getDAO('UserDAO');

		$templateMgr->assign('isJournalManager', Validation::isJournalManager($journal->getId()));
		$templateMgr->assign('individualSubscriptionDao', $individualSubscriptionDao);
		$templateMgr->assign('institutionalSubscriptionDao', $institutionalSubscriptionDao);
		$templateMgr->assign('userDao', $userDao);
		$templateMgr->assign('payment', $payment);

		$templateMgr->display('payments/viewPayment.tpl');
	}

	/**
	 * Display form to edit program settings.
	 */
	function payMethodSettings($request) {
		$templateMgr = TemplateManager::getManager($request);

		$journal = $request->getJournal();
		import('classes.payment.ojs.form.PayMethodSettingsForm');

		$settingsForm = new PayMethodSettingsForm();
		$settingsForm->initData();
		$settingsForm->display();
	}

	/**
	 * Save changes to payment settings.
	 */
	function savePayMethodSettings($request) {
		$journal = $request->getJournal();
		import('classes.payment.ojs.form.PayMethodSettingsForm');

		$settingsForm = new PayMethodSettingsForm();
		$settingsForm->readInputData();

		if ($settingsForm->validate()) {
			$settingsForm->execute();
			return true;
		} else {
			$settingsForm->display();
			return false;
		}
	}
}

?>
