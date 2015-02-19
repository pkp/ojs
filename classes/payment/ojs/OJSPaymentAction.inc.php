<?php

/**
 * @file classes/payment/ojs/OJSPaymentAction.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
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
	 function payments($args) {
		import('classes.payment.ojs.form.PaymentSettingsForm');
		$form = new PaymentSettingsForm();

		$journal =& Request::getJournal();
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'journal.managementPages.payments');

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
	 function savePaymentSettings($args) {
		import('classes.payment.ojs.form.PaymentSettingsForm');
		$settingsForm = new PaymentSettingsForm();

		$journal =& Request::getJournal();
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'journal.managementPages.payments');

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
	 function viewPayments($args) {
		$rangeInfo =& Handler::getRangeInfo('payments');
		$paymentDao =& DAORegistry::getDAO('OJSCompletedPaymentDAO');
		$journal =& Request::getJournal();
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'journal.managementPages.payments');
		$payments =& $paymentDao->getPaymentsByJournalId($journal->getId(), $rangeInfo);
		$individualSubscriptionDao =& DAORegistry::getDAO('IndividualSubscriptionDAO');
		$institutionalSubscriptionDao =& DAORegistry::getDAO('InstitutionalSubscriptionDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');

		$templateMgr->assign('isJournalManager', Validation::isJournalManager($journal->getId()));
		$templateMgr->assign_by_ref('individualSubscriptionDao', $individualSubscriptionDao);
		$templateMgr->assign_by_ref('institutionalSubscriptionDao', $institutionalSubscriptionDao);
		$templateMgr->assign_by_ref('userDao', $userDao);
		$templateMgr->assign_by_ref('payments', $payments);

		$templateMgr->display('payments/viewPayments.tpl');
	 }

	 /**
	  * Display a single Completed payment
	  */
	 function viewPayment($args) {
		$paymentDao =& DAORegistry::getDAO('OJSCompletedPaymentDAO');
		$completedPaymentId = $args[0];
		$payment =& $paymentDao->getCompletedPayment($completedPaymentId);

		$journal =& Request::getJournal();
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'journal.managementPages.payments');
		$individualSubscriptionDao =& DAORegistry::getDAO('IndividualSubscriptionDAO');
		$institutionalSubscriptionDao =& DAORegistry::getDAO('InstitutionalSubscriptionDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');

		$templateMgr->assign('isJournalManager', Validation::isJournalManager($journal->getId()));
		$templateMgr->assign_by_ref('individualSubscriptionDao', $individualSubscriptionDao);
		$templateMgr->assign_by_ref('institutionalSubscriptionDao', $institutionalSubscriptionDao);
		$templateMgr->assign_by_ref('userDao', $userDao);
		$templateMgr->assign_by_ref('payment', $payment);

		$templateMgr->display('payments/viewPayment.tpl');
	 }

	/**
	 * Display form to edit program settings.
	 */
	function payMethodSettings() {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'journal.managementPages.payments');

		$journal =& Request::getJournal();
		import('classes.payment.ojs.form.PayMethodSettingsForm');

		$settingsForm = new PayMethodSettingsForm();
		$settingsForm->initData();
		$settingsForm->display();
	}

	/**
	 * Save changes to payment settings.
	 */
	function savePayMethodSettings() {
		$journal =& Request::getJournal();
		import('classes.payment.ojs.form.PayMethodSettingsForm');

		$settingsForm = new PayMethodSettingsForm();
		$settingsForm->readInputData();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'journal.managementPages.payments');

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
