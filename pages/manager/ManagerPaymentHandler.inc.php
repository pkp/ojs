<?php

/**
 * @file ManagerPaymentHandler.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.manager
 * @class ManagerPaymentHandler
 *
 * Handle requests for configuring payments. 
 *
 */

class ManagerPaymentHandler extends ManagerHandler {

	/**
	 * Display Settings Form (main payments page)
	 */
	 function payments($args) {
		parent::validate();
		import('manager.form.PaymentSettingsForm');
		$form =& new PaymentSettingsForm();

		$journal = &Request::getJournal();
		$journalSettingsDAO =& DAORegistry::getDAO('JournalSettingsDAO');
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('enableSubscripitons', $journalSettingsDAO->getSetting($journal->getJournalId(), 'enableSubscriptions'));

		parent::setupTemplate(true);

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
		parent::validate();
		import('manager.form.PaymentSettingsForm');
		$settingsForm =& new PaymentSettingsForm();

		$journal = &Request::getJournal();
		$journalSettingsDAO =& DAORegistry::getDAO('JournalSettingsDAO');
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('enableSubscripitons', $journalSettingsDAO->getSetting($journal->getJournalId(), 'enableSubscriptions'));

		parent::setupTemplate(true);
		
		$settingsForm->readInputData();

		if ($settingsForm->validate()) {
			$settingsForm->save();

 			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign(array(
				'currentUrl' => Request::url(null, null, 'payments'),
				'pageTitle' => 'common.payments',
				'message' => 'common.changesSaved',
				'backLink' => Request::url(null, null, 'payments'),
				'backLinkLabel' => 'common.payments'
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
		$rangeInfo = &Handler::getRangeInfo('CompletedPayments');
		$paymentDao = &DAORegistry::getDAO('OJSCompletedPaymentDAO');
		$journal =& Request::getJournal();
		$templateMgr = &TemplateManager::getManager();
		$payments = &$paymentDao->getPaymentsByJournalId($journal->getJournalId(), $rangeInfo);

		$templateMgr->assign_by_ref('payments', $payments);

		parent::setupTemplate(true);
		$templateMgr->display('manager/payments/viewPayments.tpl');
	 }

	 /** 
	  * Display a single Completed payment 
	  */
	 function viewPayment($args) {
		$paymentDao = &DAORegistry::getDAO('OJSCompletedPaymentDAO');
		$completedPaymentId = $args[0];
		$payment = &$paymentDao->getCompletedPayment($completedPaymentId);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('payment', $payment);
	
		parent::setupTemplate(true);
		$templateMgr->display('manager/payments/viewPayment.tpl');
	 }

	/**
	 * Display form to edit program settings.
	 */
	function payMethodSettings() {
		parent::validate();
		parent::setupTemplate(true);
		
		$journal =& Request::getJournal();
		if (!$journal) Request::redirect (null, null, 'index');

		import('manager.form.PayMethodSettingsForm');
		
		$settingsForm = &new PayMethodSettingsForm();
		$settingsForm->initData();
		$settingsForm->display();
	}
	
	/**
	 * Save changes to payment settings.
	 */
	function savePayMethodSettings() {
		parent::validate();
		parent::setupTemplate(true);

		$journal =& Request::getJournal();
		if (!$journal) Request::redirect (null, null, 'index');

		import('manager.form.PayMethodSettingsForm');

		$settingsForm = &new PayMethodSettingsForm();
		$settingsForm->readInputData();

		if ($settingsForm->validate()) {
			$settingsForm->execute();

 			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign(array(
				'currentUrl' => Request::url(null, null, null, 'payMethodSettings'),
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
