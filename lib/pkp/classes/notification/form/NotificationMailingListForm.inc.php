<?php

/**
 * @file classes/notification/form/NotificationMailingListForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NotificationMailingListForm
 * @ingroup notification_form
 *
 * @brief Form to subscribe to the notification mailing list
 */


import('lib.pkp.classes.form.Form');
import('classes.notification.Notification');

class NotificationMailingListForm extends Form {

	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct('notification/maillist.tpl');

		// Validation checks for this form
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
		$this->addCheck(new FormValidatorEmail($this, 'email', 'required', 'notification.mailList.emailInvalid'));
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$userVars = array('email');

		$this->readUserVars($userVars);
	}

	/**
	 * Display the form.
	 * @param $request PKPRequest
	 */
	function display($request) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('new', true);

		return parent::display();
	}

	/**
	 * Save the form
	 * @param $request PKPRequest
	 */
	function execute($request) {
		$userEmail = $this->getData('email');
		$context = $request->getContext();

		$notificationMailListDao = DAORegistry::getDAO('NotificationMailListDAO');
		if($password = $notificationMailListDao->subscribeGuest($userEmail, $context->getId())) {
			$notificationManager = new NotificationManager();
			$notificationManager->sendMailingListEmail($request, $userEmail, $password, 'NOTIFICATION_MAILLIST_WELCOME');
			return true;
		} else {
			$request->redirect(null, 'notification', 'mailListSubscribed', array('error'));
			return false;
		}
	}
}

?>
