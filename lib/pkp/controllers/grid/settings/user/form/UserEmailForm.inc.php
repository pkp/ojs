<?php

/**
 * @file controllers/grid/settings/user/form/UserEmailForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserEmailForm
 * @ingroup controllers_grid_settings_user_form
 *
 * @brief Form for sending an email to a user
 */

import('lib.pkp.classes.form.Form');

class UserEmailForm extends Form {

	/* @var the user id of user to send email to */
	var $userId;

	/**
	 * Constructor.
	 * @param $userId int User ID to contact.
	 */
	function __construct($userId) {
		parent::__construct('controllers/grid/settings/user/form/userEmailForm.tpl');

		$this->userId = (int) $userId;

		$this->addCheck(new FormValidator($this, 'subject', 'required', 'email.subjectRequired'));
		$this->addCheck(new FormValidator($this, 'message', 'required', 'email.bodyRequired'));
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	/**
	 * Assign form data to user-submitted data.
	 * @see Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array(
			'subject',
			'message',
		));
	}

	/**
	 * Display the form.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function fetch($args, $request) {
		$userDao = DAORegistry::getDAO('UserDAO');
		$user = $userDao->getById($this->userId);

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'userId' => $this->userId,
			'userFullName' => $user->getFullName(),
			'userEmail' => $user->getEmail(),
		));

		return parent::fetch($request);
	}

	/**
	 * Send the email
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function execute($args, $request) {
		$userDao = DAORegistry::getDAO('UserDAO');
		$toUser = $userDao->getById($this->userId);
		$fromUser = $request->getUser();

		import('lib.pkp.classes.mail.MailTemplate');
		$email = new MailTemplate();

		$email->addRecipient($toUser->getEmail(), $toUser->getFullName());
		$email->setReplyTo($fromUser->getEmail(), $fromUser->getFullName());
		$email->setSubject($this->getData('subject'));
		$email->setBody($this->getData('message'));
		$email->assignParams();
		$email->send();
	}
}

?>
