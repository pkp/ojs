<?php

/**
 * @file classes/submission/form/PKPSubmissionSubmitStep1Form.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPSubmissionSubmitStep1Form
 * @ingroup submission_form
 *
 * @brief Form for Step 1 of author submission: terms, conditions, etc.
 */

import('lib.pkp.classes.submission.form.SubmissionSubmitForm');

class PKPSubmissionSubmitStep1Form extends SubmissionSubmitForm {
	/**
	 * Constructor.
	 * @param $context Context
	 * @param $submission Submission (optional)
	 */
	function __construct($context, $submission = null) {
		parent::__construct($context, $submission, 1);

		// Validation checks for this form
		$supportedSubmissionLocales = $context->getSupportedSubmissionLocales();
		if (!is_array($supportedSubmissionLocales) || count($supportedSubmissionLocales) < 1) $supportedSubmissionLocales = array($context->getPrimaryLocale());
		$this->addCheck(new FormValidatorInSet($this, 'locale', 'required', 'submission.submit.form.localeRequired', $supportedSubmissionLocales));
		if ((boolean) $context->getSetting('copyrightNoticeAgree')) {
			$this->addCheck(new FormValidator($this, 'copyrightNoticeAgree', 'required', 'submission.submit.copyrightNoticeAgreeRequired'));
		}
		$this->addCheck(new FormValidator($this, 'authorUserGroupId', 'required', 'author.submit.userGroupRequired'));

		foreach ((array) $context->getLocalizedSetting('submissionChecklist') as $key => $checklistItem) {
			$this->addCheck(new FormValidator($this, "checklist-$key", 'required', 'submission.submit.checklistErrors'));
		}
	}

	/**
	 * Perform additional validation checks
	 * @copydoc Form::validate
	 */
	function validate() {
		if (!parent::validate()) return false;

		// Ensure that the user is in the specified authorUserGroupId
		$authorUserGroupId = $this->getData('authorUserGroupId');
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$request = Application::getRequest();
		$context = $request->getContext();
		$user = $request->getUser();
		if (!$user) return false;

		$userGroups = $userGroupDao->getByUserId($user->getId(), $context->getId());
		while ($userGroup = $userGroups->next()) {
			if ($userGroup->getId() == $authorUserGroupId) return true;
		}
		return false;
	}

	/**
	 * Fetch the form.
	 */
	function fetch($request) {
		$user = $request->getUser();
		$templateMgr = TemplateManager::getManager($request);

		$templateMgr->assign(
			'supportedSubmissionLocaleNames',
			$this->context->getSupportedSubmissionLocaleNames()
		);

		// if this context has a copyright notice that the author must agree to, present the form items.
		if ((boolean) $this->context->getSetting('copyrightNoticeAgree')) {
			$templateMgr->assign('copyrightNotice', $this->context->getLocalizedSetting('copyrightNotice'));
			$templateMgr->assign('copyrightNoticeAgree', true);
		}

		// Get list of user's author user groups.  If its more than one, we'll need to display an author user group selector
		$userGroupAssignmentDao = DAORegistry::getDAO('UserGroupAssignmentDAO');
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$authorUserGroupAssignments = $userGroupAssignmentDao->getByUserId($user->getId(), $this->context->getId(), ROLE_ID_AUTHOR);
		$userGroupNames = array();
		if (!$authorUserGroupAssignments->wasEmpty()) {
			while($authorUserGroupAssignment = $authorUserGroupAssignments->next()) {
				$authorUserGroup = $userGroupDao->getById($authorUserGroupAssignment->getUserGroupId());
				if ($userGroupDao->userGroupAssignedToStage($authorUserGroup->getId(), WORKFLOW_STAGE_ID_SUBMISSION)) {
					$userGroupNames[$authorUserGroup->getId()] = $authorUserGroup->getLocalizedName();
				}
			}
			$templateMgr->assign('authorUserGroupOptions', $userGroupNames);
		} else {
			// The user doesn't have any author user group assignments.  They should be either a manager.
			// Add all manager user groups
			$managerUserGroupAssignments = $userGroupAssignmentDao->getByUserId($user->getId(), $this->context->getId(), ROLE_ID_MANAGER);
			if($managerUserGroupAssignments) while($managerUserGroupAssignment = $managerUserGroupAssignments->next()) {
				$managerUserGroup = $userGroupDao->getById($managerUserGroupAssignment->getUserGroupId());
				$userGroupNames[$managerUserGroup->getId()] = $managerUserGroup->getLocalizedName();
			}

			$templateMgr->assign('authorUserGroupOptions', $userGroupNames);
		}

		return parent::fetch($request);
	}

	/**
	 * Initialize form data from current submission.
	 */
	function initData($data = array()) {
		if (isset($this->submission)) {
			$query = $this->getCommentsToEditor($this->submissionId);
			$this->_data = array_merge($data, array(
				'locale' => $this->submission->getLocale(),
				'commentsToEditor' => $query ? $query->getHeadNote()->getContents() : '',
			));
		} else {
			$supportedSubmissionLocales = $this->context->getSupportedSubmissionLocales();
			// Try these locales in order until we find one that's
			// supported to use as a default.
			$tryLocales = array(
				$this->getFormLocale(), // Current form locale
				AppLocale::getLocale(), // Current UI locale
				$this->context->getPrimaryLocale(), // Context locale
				$supportedSubmissionLocales[array_shift(array_keys($supportedSubmissionLocales))] // Fallback: first one on the list
			);
			$this->_data = $data;
			foreach ($tryLocales as $locale) {
				if (in_array($locale, $supportedSubmissionLocales)) {
					// Found a default to use
					$this->_data['locale'] = $locale;
					break;
				}
			}
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$vars = array(
			'authorUserGroupId', 'locale', 'copyrightNoticeAgree', 'commentsToEditor', 'copyrightNoticeAgree'
		);
		foreach ((array) $this->context->getLocalizedSetting('submissionChecklist') as $key => $checklistItem) {
			$vars[] = "checklist-$key";
		}

		$this->readUserVars($vars);
	}

	/**
	 * Set the submission data from the form.
	 * @param $submission Submission
	 */
	function setSubmissionData($submission) {
		$this->submission->setLanguage(PKPString::substr($this->submission->getLocale(), 0, 2));
		$this->submission->setLocale($this->getData('locale'));
	}

	/**
	 * Add or update comments to editor
	 * @param $submissionId int
	 * @param $commentsToEditor string
	 * @param $userId int
	 * @param $query Query optional
	 */
	function setCommentsToEditor($submissionId, $commentsToEditor, $userId, $query = null) {
		$queryDao = DAORegistry::getDAO('QueryDAO');
		$noteDao = DAORegistry::getDAO('NoteDAO');

		if (!isset($query)){
			if ($commentsToEditor) {
				$query = $queryDao->newDataObject();
				$query->setAssocType(ASSOC_TYPE_SUBMISSION);
				$query->setAssocId($submissionId);
				$query->setStageId(WORKFLOW_STAGE_ID_SUBMISSION);
				$query->setSequence(REALLY_BIG_NUMBER);
				$queryDao->insertObject($query);
				$queryDao->resequence(ASSOC_TYPE_SUBMISSION, $submissionId);
				$queryDao->insertParticipant($query->getId(), $userId);
				$queryId = $query->getId();

				$note = $noteDao->newDataObject();
				$note->setUserId($userId);
				$note->setAssocType(ASSOC_TYPE_QUERY);
				$note->setTitle(__('submission.submit.coverNote'));
				$note->setContents($commentsToEditor);
				$note->setDateCreated(Core::getCurrentDate());
				$note->setDateModified(Core::getCurrentDate());
				$note->setAssocId($queryId);
				$noteDao->insertObject($note);
			}
		} else{
			$queryId = $query->getId();
			$notes = $noteDao->getByAssoc(ASSOC_TYPE_QUERY, $queryId);
			if (!$notes->wasEmpty()) {
				$note = $notes->next();
				if ($commentsToEditor) {
					$note->setContents($commentsToEditor);
					$note->setDateModified(Core::getCurrentDate());
					$noteDao->updateObject($note);
				} else {
					$noteDao->deleteObject($note);
					$queryDao->deleteObject($query);
				}
			}
		}
	}

	/**
	 * Get comments to editor
	 * @param $submissionId int
	 * @return null|Query
	 */
	function getCommentsToEditor($submissionId) {
		$query = null;
		$queryDao = DAORegistry::getDAO('QueryDAO');
		$queries = $queryDao->getByAssoc(ASSOC_TYPE_SUBMISSION, $submissionId);
		if ($queries) $query = $queries->next();
		return $query;
	}

	/**
	 * Save changes to submission.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return int the submission ID
	 */
	function execute($args, $request) {
		$submissionDao = Application::getSubmissionDAO();
		$user = $request->getUser();

		if (isset($this->submission)) {
			// Update existing submission
			$this->setSubmissionData($this->submission);
			if ($this->submission->getSubmissionProgress() <= $this->step) {
				$this->submission->stampStatusModified();
				$this->submission->setSubmissionProgress($this->step + 1);
			}
			// Add, remove or update comments to editor
			$query = $this->getCommentsToEditor($this->submissionId);
			$this->setCommentsToEditor($this->submissionId, $this->getData('commentsToEditor'), $user->getId(), $query);

			$submissionDao->updateObject($this->submission);
		} else {
			// Create new submission
			$this->submission = $submissionDao->newDataObject();
			$this->submission->setContextId($this->context->getId());

			$this->setSubmissionData($this->submission);

			$this->submission->stampStatusModified();
			$this->submission->setSubmissionProgress($this->step + 1);
			$this->submission->setStageId(WORKFLOW_STAGE_ID_SUBMISSION);
			$this->submission->setCopyrightNotice($this->context->getLocalizedSetting('copyrightNotice'), $this->getData('locale'));
			// Insert the submission
			$this->submissionId = $submissionDao->insertObject($this->submission);

			// Set user to initial author
			$authorDao = DAORegistry::getDAO('AuthorDAO');
			$author = $authorDao->newDataObject();
			$author->setFirstName($user->getFirstName());
			$author->setMiddleName($user->getMiddleName());
			$author->setLastName($user->getLastName());
			$author->setAffiliation($user->getAffiliation(null), null);
			$author->setCountry($user->getCountry());
			$author->setEmail($user->getEmail());
			$author->setUrl($user->getUrl());
			$author->setBiography($user->getBiography(null), null);
			$author->setPrimaryContact(1);
			$author->setIncludeInBrowse(1);

			// Get the user group to display the submitter as
			$authorUserGroupId = (int) $this->getData('authorUserGroupId');
			$author->setUserGroupId($authorUserGroupId);

			$author->setSubmissionId($this->submissionId);
			$authorDao->insertObject($author);

			// Assign the user author to the stage
			$stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
			$stageAssignmentDao->build($this->submissionId, $authorUserGroupId, $user->getId());

			// Add comments to editor
			if ($this->getData('commentsToEditor')){
				$this->setCommentsToEditor($this->submissionId, $this->getData('commentsToEditor'), $user->getId());
			}

		}

		return $this->submissionId;
	}
}

?>
