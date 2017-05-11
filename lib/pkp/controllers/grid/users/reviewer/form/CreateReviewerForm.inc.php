<?php

/**
 * @file controllers/grid/users/reviewer/form/CreateReviewerForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CreateReviewerForm
 * @ingroup controllers_grid_users_reviewer_form
 *
 * @brief Form for creating and subsequently adding a reviewer to a submission.
 */

import('lib.pkp.controllers.grid.users.reviewer.form.ReviewerForm');

class CreateReviewerForm extends ReviewerForm {
	/**
	 * Constructor.
	 * @param $submission Submission
	 * @param $reviewRound ReviewRound
	 */
	function __construct($submission, $reviewRound) {
		parent::__construct($submission, $reviewRound);
		$this->setTemplate('controllers/grid/users/reviewer/form/createReviewerForm.tpl');

		$this->addCheck(new FormValidator($this, 'firstName', 'required', 'user.profile.form.firstNameRequired'));
		$this->addCheck(new FormValidator($this, 'lastName', 'required', 'user.profile.form.lastNameRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'username', 'required', 'user.register.form.usernameExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByUsername'), array(), true));
		$this->addCheck(new FormValidatorUsername($this, 'username', 'required', 'user.register.form.usernameAlphaNumeric'));
		$this->addCheck(new FormValidatorEmail($this, 'email', 'required', 'user.profile.form.emailRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'email', 'required', 'user.register.form.emailExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByEmail'), array(), true));
		$this->addCheck(new FormValidator($this, 'userGroupId', 'required', 'user.profile.form.usergroupRequired'));
	}


	/**
	 * Fetch the form.
	 * @see Form::fetch()
	 */
	function fetch($request) {
		$advancedSearchAction = $this->getAdvancedSearchAction($request);

		$this->setReviewerFormAction($advancedSearchAction);
		return parent::fetch($request);
	}

	/**
	 * Assign form data to user-submitted data.
	 * @see Form::readInputData()
	 */
	function readInputData() {
		parent::readInputData();

		$this->readUserVars(array(
			'firstName',
			'middleName',
			'lastName',
			'affiliation',
			'interests',
			'username',
			'email',
			'skipEmail',
			'userGroupId',
		));
	}

	/**
	 * Save review assignment
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function execute($args, $request) {
		$userDao = DAORegistry::getDAO('UserDAO');
		$user = $userDao->newDataObject();

		$user->setFirstName($this->getData('firstName'));
		$user->setMiddleName($this->getData('middleName'));
		$user->setLastName($this->getData('lastName'));
		$user->setEmail($this->getData('email'));

		$authDao = DAORegistry::getDAO('AuthSourceDAO');
		$auth = $authDao->getDefaultPlugin();
		$user->setAuthId($auth?$auth->getAuthId():0);
		$user->setInlineHelp(1); // default new reviewers to having inline help visible

		$user->setUsername($this->getData('username'));
		$password = Validation::generatePassword();

		if (isset($auth)) {
			$user->setPassword($password);
			// FIXME Check result and handle failures
			$auth->doCreateUser($user);
			$user->setAuthId($auth->authId);
			$user->setPassword(Validation::encryptCredentials($user->getId(), Validation::generatePassword())); // Used for PW reset hash only
		} else {
			$user->setPassword(Validation::encryptCredentials($this->getData('username'), $password));
		}
		$user->setMustChangePassword(true); // Emailed P/W not safe

		$user->setDateRegistered(Core::getCurrentDate());
		$reviewerId = $userDao->insertObject($user);

		// Set the reviewerId in the Form for the parent class to use
		$this->setData('reviewerId', $reviewerId);

		// Insert the user interests
		import('lib.pkp.classes.user.InterestManager');
		$interestManager = new InterestManager();
		$interestManager->setInterestsForUser($user, $this->getData('interests'));

		// Assign the selected user group ID to the user
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO'); /* @var $userGroupDao UserGroupDAO */
		$userGroupId = (int) $this->getData('userGroupId');
		$userGroupDao->assignUserToGroup($reviewerId, $userGroupId);

		if (!$this->getData('skipEmail')) {
			// Send welcome email to user
			import('lib.pkp.classes.mail.MailTemplate');
			$mail = new MailTemplate('REVIEWER_REGISTER');
			if ($mail->isEnabled()) {
				$context = $request->getContext();
				$mail->setReplyTo($context->getSetting('contactEmail'), $context->getSetting('contactName'));
				$mail->assignParams(array('username' => $this->getData('username'), 'password' => $password, 'userFullName' => $user->getFullName()));
				$mail->addRecipient($user->getEmail(), $user->getFullName());
				$mail->send($request);
			}
		}

		return parent::execute($args, $request);
	}
}

?>
