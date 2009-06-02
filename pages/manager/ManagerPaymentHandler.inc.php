<?php

/**
 * @file ManagerPaymentHandler.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ManagerPaymentHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for configuring payments. 
 *
 */

import('pages.manager.ManagerHandler');

class ManagerPaymentHandler extends ManagerHandler {
	/**
	 * Constructor
	 **/
	function ManagerPaymentHandler() {
		parent::ManagerHandler();
	}

	/**
	 * Display Settings Form (main payments page)
	 */
	 function payments($args) {
		$this->validate();
		import('manager.form.PaymentSettingsForm');
		// FIXME: Need construction by reference or validation always fails on PHP 4.x
		$form =& new PaymentSettingsForm();

		$journal =& Request::getJournal();
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'journal.managementPages.payments');

		$this->setupTemplate(true);

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
		$this->validate();
		import('manager.form.PaymentSettingsForm');
		// FIXME: Need construction by reference or validation always fails on PHP 4.x
		$settingsForm =& new PaymentSettingsForm();

		$journal =& Request::getJournal();
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'journal.managementPages.payments');

		$this->setupTemplate(true);
		
		$settingsForm->readInputData();

		if ($settingsForm->validate()) {
			$settingsForm->save();

 			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign(array(
				'currentUrl' => Request::url(null, null, 'payments'),
				'pageTitle' => 'manager.payment.feePaymentOptions',
				'message' => 'common.changesSaved',
				'backLink' => Request::url(null, null, 'payments'),
				'backLinkLabel' => 'manager.payment.feePaymentOptions'
			));
			$templateMgr->display('common/message.tpl');		
		} else {
			$settingsForm->display();
		}
	 }	 
	 
	 /** 
	  * Display all payments previously made
	  */
	 function viewPayments($args) {
		$rangeInfo =& Handler::getRangeInfo('CompletedPayments');
		$paymentDao =& DAORegistry::getDAO('OJSCompletedPaymentDAO');
		$journal =& Request::getJournal();
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'journal.managementPages.payments');
		$payments =& $paymentDao->getPaymentsByJournalId($journal->getJournalId(), $rangeInfo);
		$individualSubscriptionDao =& DAORegistry::getDAO('IndividualSubscriptionDAO');
		$institutionalSubscriptionDao =& DAORegistry::getDAO('InstitutionalSubscriptionDAO');

		$templateMgr->assign_by_ref('individualSubscriptionDao', $individualSubscriptionDao);
		$templateMgr->assign_by_ref('institutionalSubscriptionDao', $institutionalSubscriptionDao);
		$templateMgr->assign_by_ref('payments', $payments);

		$this->setupTemplate(true);
		$templateMgr->display('manager/payments/viewPayments.tpl');
	 }

	 /** 
	  * Display a single Completed payment 
	  */
	 function viewPayment($args) {
		$paymentDao =& DAORegistry::getDAO('OJSCompletedPaymentDAO');
		$completedPaymentId = $args[0];
		$payment =& $paymentDao->getCompletedPayment($completedPaymentId);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'journal.managementPages.payments');
		$individualSubscriptionDao =& DAORegistry::getDAO('IndividualSubscriptionDAO');
		$institutionalSubscriptionDao =& DAORegistry::getDAO('InstitutionalSubscriptionDAO');

		$templateMgr->assign_by_ref('individualSubscriptionDao', $individualSubscriptionDao);
		$templateMgr->assign_by_ref('institutionalSubscriptionDao', $institutionalSubscriptionDao);
		$templateMgr->assign_by_ref('payment', $payment);

		$this->setupTemplate(true);
		$templateMgr->display('manager/payments/viewPayment.tpl');
	 }

	/**
	 * Display form to edit program settings.
	 */
	function payMethodSettings() {
		$this->validate();
		$this->setupTemplate(true);
		
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'journal.managementPages.payments');

		$journal =& Request::getJournal();
		if (!$journal) Request::redirect (null, null, 'index');

		import('manager.form.PayMethodSettingsForm');
		
		// FIXME: Need construction by reference or validation always fails on PHP 4.x
		$settingsForm =& new PayMethodSettingsForm();
		$settingsForm->initData();
		$settingsForm->display();
	}
	
	/**
	 * Save changes to payment settings.
	 */
	function savePayMethodSettings() {
		$this->validate();
		$this->setupTemplate(true);

		$journal =& Request::getJournal();
		if (!$journal) Request::redirect (null, null, 'index');

		import('manager.form.PayMethodSettingsForm');

		// FIXME: Need construction by reference or validation always fails on PHP 4.x
		$settingsForm =& new PayMethodSettingsForm();
		$settingsForm->readInputData();

 		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'journal.managementPages.payments');

		if ($settingsForm->validate()) {
			$settingsForm->execute();
			$templateMgr->assign(array(
				'currentUrl' => Request::url(null, null, 'payMethodSettings'),
				'pageTitle' => 'manager.payment.paymentMethods',
				'message' => 'common.changesSaved',
				'backLink' => Request::url(null, null, 'payMethodSettings'),
				'backLinkLabel' => 'manager.payment.paymentMethods'
			));
			$templateMgr->display('common/message.tpl');		
		} else {
			$settingsForm->display();
		}
	}
}

?>
