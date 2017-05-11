<?php

/**
 * @file classes/mail/SubmissionMailTemplate.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionMailTemplate
 * @ingroup mail
 *
 * @brief Subclass of MailTemplate for sending emails related to submissions.
 *
 * This allows for submission-specific functionality like logging, etc.
 */

import('lib.pkp.classes.mail.MailTemplate');

class SubmissionMailTemplate extends MailTemplate {

	/** @var object the associated submission */
	var $submission;

	/** @var object the associated context */
	var $context;

	/** @var int Event type of this email for logging purposes */
	var $logEventType;

	/**
	 * Constructor.
	 * @param $submission Submission
	 * @param $emailKey string optional
	 * @param $locale string optional
	 * @param $context object optional
	 * @param $includeSignature boolean optional
	 * @see MailTemplate::MailTemplate()
	 */
	function __construct($submission, $emailKey = null, $locale = null, $context = null, $includeSignature = true) {
		parent::__construct($emailKey, $locale, $context, $includeSignature);
		$this->submission = $submission;
	}

	/**
	 * Assign parameters to template
	 * @param $paramArray array
	 */
	function assignParams($paramArray = array()) {
		$submission = $this->submission;
		$application = PKPApplication::getApplication();
		$request = $application->getRequest();
		parent::assignParams(array_merge(
			array(
				'submissionTitle' => strip_tags($submission->getLocalizedTitle()),
				'submissionId' => $submission->getId(),
				'submissionAbstract' => PKPString::stripUnsafeHtml($submission->getLocalizedAbstract()),
				'authorString' => strip_tags($submission->getAuthorString()),
			),
			$paramArray
		));
	}

	/**
	 * @see parent::send()
	 * @param $request PKPRequest optional (used for logging purposes)
	 */
	function send($request = null) {
		if (parent::send(false)) {
			$this->log($request);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @copydoc parent::sendWithParams()
	 */
	function sendWithParams($paramArray) {
		$savedSubject = $this->getSubject();
		$savedBody = $this->getBody();

		$this->assignParams($paramArray);

		$ret = $this->send();

		$this->setSubject($savedSubject);
		$this->setBody($savedBody);

		return $ret;
	}

	/**
	 * Add logging properties to this email.
	 * @param $eventType int
	 */
	function setEventType($eventType) {
		$this->logEventType = $eventType;
	}

	/**
	 * Set the context this message is associated with.
	 * @param $context object
	 */
	function setContext($context) {
		$this->context = $context;
	}

	/**
	 * Save the email in the submission email log.
	 */
	function log($request = null) {
		import('classes.log.SubmissionEmailLogEntry');
		$entry = new SubmissionEmailLogEntry();
		$submission = $this->submission;

		// Event data
		$entry->setEventType($this->logEventType);
		$entry->setAssocType(ASSOC_TYPE_SUBMISSION);
		$entry->setAssocId($submission->getId());
		$entry->setDateSent(Core::getCurrentDate());

		// User data
		if ($request) {
			$user = $request->getUser();
			$entry->setSenderId($user == null ? 0 : $user->getId());
			$entry->setIPAddress($request->getRemoteAddr());
		} else {
			// No user supplied -- this is e.g. a cron-automated email
			$entry->setSenderId(0);
		}

		// Email data
		$entry->setSubject($this->getSubject());
		$entry->setBody($this->getBody());
		$entry->setFrom($this->getFromString(false));
		$entry->setRecipients($this->getRecipientString());
		$entry->setCcs($this->getCcString());
		$entry->setBccs($this->getBccString());

		// Add log entry
		$logDao = DAORegistry::getDAO('SubmissionEmailLogDAO');
		$logEntryId = $logDao->insertObject($entry);
	}

	/**
	 *  Send this email to all assigned sub editors in the given stage
	 * @param $submissionId int
	 * @param $stageId int
	 */
	function toAssignedSubEditors($submissionId, $stageId) {
		return $this->_addUsers($submissionId, ROLE_ID_SUB_EDITOR, $stageId, 'addRecipient');
	}

	/**
	 *  CC this email to all assigned sub editors in the given stage
	 * @param $submissionId int
	 * @param $stageId int
	 * @return array of Users
	 */
	function ccAssignedSubEditors($submissionId, $stageId) {
		return $this->_addUsers($submissionId, ROLE_ID_SUB_EDITOR, $stageId, 'addCc');
	}

	/**
	 *  BCC this email to all assigned sub editors in the given stage
	 * @param $submissionId int
	 * @param $stageId int
	 */
	function bccAssignedSubEditors($submissionId, $stageId) {
		return $this->_addUsers($submissionId, ROLE_ID_SUB_EDITOR, $stageId, 'addBcc');
	}

	/**
	 * Fetch the requested users and add to the email
	 * @param $submissionId int
	 * @param $roleId int
	 * @param $stageId int
	 * @param $method string one of addRecipient, addCC, or addBCC
	 * @return array of Users
	 */
	protected function _addUsers($submissionId, $roleId, $stageId, $method) {
		assert(in_array($method, array('addRecipient', 'addCc', 'addBcc')));

		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$userGroups = $userGroupDao->getByRoleId($this->context->getId(), $roleId);

		$returner = array();
		// Cycle through all the userGroups for this role
		while ($userGroup = $userGroups->next()) {
			$userStageAssignmentDao = DAORegistry::getDAO('UserStageAssignmentDAO');
			// FIXME: #6692# Should this be getting users just for a specific user group?
			$users = $userStageAssignmentDao->getUsersBySubmissionAndStageId($submissionId, $stageId, $userGroup->getId());
			while ($user = $users->next()) {
				$this->$method($user->getEmail(), $user->getFullName());
				$returner[] = $user;
			}
		}
		return $returner;
	}
}

?>
