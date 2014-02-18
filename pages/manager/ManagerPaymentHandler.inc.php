<?php

/**
 * @file pages/manager/ManagerPaymentHandler.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
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
	 function payments($args, $request) {
		$this->validate();
		$this->setupTemplate($request, true);

		import('classes.payment.ojs.OJSPaymentAction');
		OJSPaymentAction::payments($args, $request);
	 }
	 
	 /**
	  * Execute the form or display it again if there are problems
	  */
	 function savePaymentSettings($args, $request) {
		$this->validate();
		$this->setupTemplate($request, true);

		import('classes.payment.ojs.OJSPaymentAction');
		$success = OJSPaymentAction::savePaymentSettings($args, $request);

		if ($success) {
 			$templateMgr = TemplateManager::getManager($request);
			$templateMgr->assign(array(
				'currentUrl' => $request->url(null, null, 'payments'),
				'pageTitle' => 'manager.payment.feePaymentOptions',
				'message' => 'common.changesSaved',
				'backLink' => $request->url(null, null, 'payments'),
				'backLinkLabel' => 'manager.payment.feePaymentOptions'
			));
			$templateMgr->display('common/message.tpl');		
		}
	 }	 
	 
	 /** 
	  * Display all payments previously made
	  */
	 function viewPayments($args, $request) {
		$this->validate();
		$this->setupTemplate($request, true);

		import('classes.payment.ojs.OJSPaymentAction');
		OJSPaymentAction::viewPayments($args, $request);
	 }

	 /** 
	  * Display a single Completed payment 
	  */
	 function viewPayment($args, $request) {
		$this->validate();
		$this->setupTemplate($request, true);

		import('classes.payment.ojs.OJSPaymentAction');
		OJSPaymentAction::viewPayment($args, $request);
	 }

	/**
	 * Display form to edit program settings.
	 */
	function payMethodSettings($args, $request) {
		$this->validate();
		$this->setupTemplate($request, true);

		import('classes.payment.ojs.OJSPaymentAction');
		OJSPaymentAction::payMethodSettings($request);
	}
	
	/**
	 * Save changes to payment settings.
	 */
	function savePayMethodSettings($args, $request) {
		$this->validate();
		$this->setupTemplate($request, true);

		import('classes.payment.ojs.OJSPaymentAction');
		$success = OJSPaymentAction::savePayMethodSettings($request);

		if ($success) {
 			$templateMgr = TemplateManager::getManager($request);
			$templateMgr->assign(array(
				'currentUrl' => $request->url(null, null, 'payMethodSettings'),
				'pageTitle' => 'manager.payment.paymentMethods',
				'message' => 'common.changesSaved',
				'backLink' => $request->url(null, null, 'payMethodSettings'),
				'backLinkLabel' => 'manager.payment.paymentMethods'
			));
			$templateMgr->display('common/message.tpl');		
		}
	}
}

?>
