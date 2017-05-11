<?php

/**
 * @file lib/pkp/controllers/grid/users/stageParticipant/form/PKPStageParticipantNotifyForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPStageParticipantNotifyForm
 * @ingroup controllers_grid_users_stageParticipant_form
 *
 * @brief Form to notify a user regarding a file
 */

import('lib.pkp.classes.form.Form');

abstract class PKPStageParticipantNotifyForm extends Form {
	/** @var int The file/submission ID this form is for */
	var $_itemId;

	/** @var int The type of item the form is for (used to determine which email template to use) */
	var $_itemType;

	/** The stage Id **/
	var $_stageId;

	/** @var int the Submission id */
	var $_submissionId;

	/**
	 * Constructor.
	 */
	function __construct($itemId, $itemType, $stageId, $template = null) {
		$template = ($template != null) ? $template : 'controllers/grid/users/stageParticipant/form/notify.tpl';
		parent::__construct($template);
		$this->_itemId = $itemId;
		$this->_itemType = $itemType;
		$this->_stageId = $stageId;

		if($itemType == ASSOC_TYPE_SUBMISSION) {
			$this->_submissionId = $itemId;
		} else {
			$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
			$submissionFile = $submissionFileDao->getLatestRevision($itemId);
			$this->_submissionId = $submissionFile->getSubmissionId();
		}

		// Some other forms (e.g. the Add Participant form) subclass this form and do not have the list builder
		// or may not enforce the sending of an email.  Only include checks if the list builder is included.
		if ($this->includeNotifyUsersListbuilder()) {
			$this->addCheck(new FormValidatorListbuilder($this, 'users', 'stageParticipants.notify.warning'));
			$this->addCheck(new FormValidator($this, 'message', 'required', 'stageParticipants.notify.warning'));
		}
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	/**
	 * @copydoc Form::fetch()
	 */
	function fetch($request) {
		$submissionDao = Application::getSubmissionDAO();
		$submission = $submissionDao->getById($this->_submissionId);

		// All stages can choose the default template
		$templateKeys = array('NOTIFICATION_CENTER_DEFAULT');

		// Determine if the current user can use any custom templates defined.
		$user = $request->getUser();
		$roleDao = DAORegistry::getDAO('RoleDAO');
		$userRoles = $roleDao->getByUserId($user->getId(), $submission->getContextId());
		foreach ($userRoles as $userRole) {
			if (in_array($userRole->getId(), array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT))) {
				$emailTemplateDao = DAORegistry::getDAO('EmailTemplateDAO');
				$customTemplates = $emailTemplateDao->getCustomTemplateKeys(Application::getContextAssocType(), $submission->getContextId());
				$templateKeys = array_merge($templateKeys, $customTemplates);
				break;
			}
		}

		$stageTemplates = $this->_getStageTemplates();
		$currentStageId = $this->getStageId();
		if (array_key_exists($currentStageId, $stageTemplates)) {
			$templateKeys = array_merge($templateKeys, $stageTemplates[$currentStageId]);
		}
		foreach ($templateKeys as $templateKey) {
			$template = $this->_getMailTemplate($submission, $templateKey);
			$template->assignParams(array());
			$template->replaceParams();
			$templates[$templateKey] = $template->getSubject();
		}

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'templates' => $templates,
			'stageId' => $currentStageId,
			'includeNotifyUsersListbuilder' => $this->includeNotifyUsersListbuilder(),
			'submissionId' => $this->_submissionId,
			'itemId' => $this->_itemId,
			// check to see if we were handed a userId from the stage participants grid. If
			// so, pass that in so the listbuilder can pre-populate. Listbuilder validates.
			'userId' => (int) $request->getUserVar('userId'),
		));

		return parent::fetch($request);
	}

	/**
	 * @copydoc Form::readInputData()
	 */
	function readInputData($request) {
		$this->readUserVars(array('message', 'users', 'template'));
		import('lib.pkp.classes.controllers.listbuilder.ListbuilderHandler');

		$this->setData('userIds', array($request->getUserVar('userId')));
		$userData = $this->getData('users');
		ListbuilderHandler::unpack($request, $userData, array($this, 'deleteEntry'), array($this, 'insertEntry'), array($this, 'updateEntry'));
	}

	/**
	 * @copydoc Form::execute()
	 */
	function execute($request) {
		$submissionDao = Application::getSubmissionDAO();
		$submission = $submissionDao->getById($this->_submissionId);
		foreach ((array) $this->getData('userIds') as $userId) {
			$this->sendMessage($userId, $submission, $request);
		}
		return parent::execute($request);
	}

	/**
	 * @copydoc ListbuilderHandler::insertEntry()
	 */
	function insertEntry($request, $newRowId) {

		$userDao = DAORegistry::getDAO('UserDAO');
		$application = Application::getApplication();
		$request = $application->getRequest(); // need to do this because the method version is null.

		$submissionDao = Application::getSubmissionDAO();
		$submission = $submissionDao->getById($this->_submissionId);

		$this->setData('userIds', array_merge($this->getData('userIds'), $newRowId));
	}

	/**
	 * @copydoc ListbuilderHandler::deleteEntry
	 */
	function deleteEntry($request, $rowId) {
		$this->setData('userIds', array_diff($this->getData('userIds'), array($rowId)));
	}

	/**
	 * Send a message to a user.
	 * @param $userId int the user id to send email to.
	 * @param $submission Submission
	 * @param $request PKPRequest
	 */
	function sendMessage($userId, $submission, $request) {

		$template = $this->getData('template');
		$fromUser = $request->getUser();

		$email = $this->_getMailTemplate($submission, $template, false);
		$email->setReplyTo($fromUser->getEmail(), $fromUser->getFullName());

		import('lib.pkp.controllers.grid.submissions.SubmissionsListGridCellProvider');
		$dispatcher = $request->getDispatcher();

		$userDao = DAORegistry::getDAO('UserDAO');
		$user = $userDao->getById($userId);
		if (isset($user)) {
			$email->addRecipient($user->getEmail(), $user->getFullName());
			$email->setBody($this->getData('message'));
			$submissionUrl = SubmissionsListGridCellProvider::getUrlByUserRoles($request, $submission, $user->getId());

			// Parameters for various emails
			$email->assignParams(array(
				// COPYEDIT_REQUEST, LAYOUT_REQUEST, INDEX_REQUEST
				'participantName' => $user->getFullName(),
				'participantUsername' => $user->getUsername(),
				'submissionUrl' => $submissionUrl,
				// LAYOUT_COMPLETE, INDEX_COMPLETE, EDITOR_ASSIGN
				'editorialContactName' => $user->getFullname(),
				// EDITOR_ASSIGN
				'editorUsername' => $user->getUsername(),
			));

			$email->send($request);
			// remove the INDEX_ and LAYOUT_ tasks if a user has sent the appropriate _COMPLETE email
			switch ($template) {
				case 'COPYEDIT_REQUEST':
					$this->_addAssignmentTaskNotification($request, NOTIFICATION_TYPE_COPYEDIT_ASSIGNMENT, $user->getId(), $submission->getId());
					break;
				case 'LAYOUT_REQUEST':
					$this->_addAssignmentTaskNotification($request, NOTIFICATION_TYPE_LAYOUT_ASSIGNMENT, $user->getId(), $submission->getId());
					break;
				case 'INDEX_REQUEST':
					$this->_addAssignmentTaskNotification($request, NOTIFICATION_TYPE_INDEX_ASSIGNMENT, $user->getId(), $submission->getId());
					break;
			}

			// Create a query
			$queryDao = DAORegistry::getDAO('QueryDAO');
			$query = $queryDao->newDataObject();
			$query->setAssocType(ASSOC_TYPE_SUBMISSION);
			$query->setAssocId($submission->getId());
			$query->setStageId($this->_stageId);
			$query->setSequence(REALLY_BIG_NUMBER);
			$queryDao->insertObject($query);
			$queryDao->resequence(ASSOC_TYPE_SUBMISSION, $submission->getId());

			// Add the current user and message recipient as participants.
			$queryDao->insertParticipant($query->getId(), $user->getId());
			if ($user->getId() != $request->getUser()->getId()) {
				$queryDao->insertParticipant($query->getId(), $request->getUser()->getId());
			}

			// Create a head note
			$noteDao = DAORegistry::getDAO('NoteDAO');
			$headNote = $noteDao->newDataObject();
			$headNote->setUserId($request->getUser()->getId());
			$headNote->setAssocType(ASSOC_TYPE_QUERY);
			$headNote->setAssocId($query->getId());
			$headNote->setDateCreated(Core::getCurrentDate());
			$headNote->setTitle($email->getSubject());
			$headNote->setContents($email->getBody());
			$noteDao->insertObject($headNote);

			$notificationMgr = new NotificationManager();
			$notificationMgr->updateNotification(
				$request,
				array(
					NOTIFICATION_TYPE_ASSIGN_COPYEDITOR,
					NOTIFICATION_TYPE_AWAITING_COPYEDITS,
					NOTIFICATION_TYPE_ASSIGN_PRODUCTIONUSER,
					NOTIFICATION_TYPE_AWAITING_REPRESENTATIONS,
				),
				null,
				ASSOC_TYPE_SUBMISSION,
				$submission->getId()
			);

		}
	}

	/**
	 * Get the available email template variable names for the given template name.
	 * @param $emailKey string Email template key
	 * @return array
	 */
	function getEmailVariableNames($emailKey) {
		switch ($emailKey) {
			case 'COPYEDIT_REQUEST':
			case 'LAYOUT_REQUEST':
			case 'INDEX_REQUEST': return array(
					'participantName' => __('user.name'),
					'participantUsername' => __('user.username'),
					'submissionUrl' => __('common.url'),
				);
			case 'LAYOUT_COMPLETE':
			case 'INDEX_COMPLETE': return array(
				'editorialContactName' => __('user.role.editor'),
			);
			case 'EDITOR_ASSIGN': return array(
				'editorUsername' => __('user.username'),
				'editorialContactName' => __('user.role.editor'),
				'submissionUrl' => __('common.url'),
			);
		}
	}

	/**
	 * Get the stage ID
	 * @return int
	 */
	function getStageId() {
		return $this->_stageId;
	}

	/**
	 * Add upload task notifications.
	 * @param $request PKPRequest
	 * @param $type int NOTIFICATION_TYPE_...
	 * @param $userId int User ID
	 * @param $submissionId int Submission ID
	 */
	private function _addAssignmentTaskNotification($request, $type, $userId, $submissionId) {
		$notificationDao = DAORegistry::getDAO('NotificationDAO'); /* @var $notificationDao NotificationDAO */
		$notificationFactory = $notificationDao->getByAssoc(
			ASSOC_TYPE_SUBMISSION,
			$submissionId,
			$userId,
			$type
		);

		if ($notificationFactory->wasEmpty()) {
			$context = $request->getContext();
			$notificationMgr = new NotificationManager();
			$notificationMgr->createNotification(
				$request,
				$userId,
				$type,
				$context->getId(),
				ASSOC_TYPE_SUBMISSION,
				$submissionId,
				NOTIFICATION_LEVEL_TASK
			);
		}
	}

	/**
	 * whether or not to include the Notify Users listbuilder  true, by default.
	 * @return boolean
	 */
	function includeNotifyUsersListbuilder() {
		return true;
	}

	/**
	 * return app-specific stage templates.
	 * @return array
	 */
	abstract protected function _getStageTemplates();

	/**
	 * Return app-specific mail template.
	 * @param $submission Submission
	 * @param $templateKey string
	 * @param $includeSignature boolean
	 * @return array
	 */
	abstract protected function _getMailTemplate($submission, $templateKey, $includeSignature = true);
}

?>
