<?php

/**
 * @file classes/mail/ArticleMailTemplate.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleMailTemplate
 * @ingroup mail
 *
 * @brief Subclass of MailTemplate for sending emails related to articles.
 *
 * This allows for article-specific functionality like logging, etc.
 */

import('classes.mail.MailTemplate');
import('classes.log.SubmissionEmailLogEntry'); // Bring in log constants

class ArticleMailTemplate extends MailTemplate {

	/** @var object the associated article */
	var $article;

	/** @var object the associated journal */
	var $journal;

	/** @var int Event type of this email */
	var $eventType;

	/**
	 * Constructor.
	 * @param $article object
	 * @param $emailType string optional
	 * @param $locale string optional
	 * @param $enableAttachments boolean optional
	 * @param $journal object optional
	 * @param $includeSignature boolean optional
	 * @param $ignorePostedData boolean optional
	 * @see MailTemplate::MailTemplate()
	 */
	function ArticleMailTemplate($article, $emailKey = null, $locale = null, $enableAttachments = null, $journal = null, $includeSignature = true, $ignorePostedData = false) {
		parent::MailTemplate($emailKey, $locale, $enableAttachments, $journal, $includeSignature, $ignorePostedData);
		$this->article = $article;
	}

	function assignParams($paramArray = array()) {
		$article =& $this->article;
		$journal = isset($this->journal)?$this->journal:Request::getJournal();

		$paramArray['articleTitle'] = strip_tags($article->getLocalizedTitle());
		$paramArray['articleId'] = $article->getId();
		$paramArray['journalName'] = strip_tags($journal->getLocalizedName());
		$paramArray['sectionName'] = strip_tags($article->getSectionTitle());
		$paramArray['articleAbstract'] = String::html2text($article->getLocalizedAbstract());
		$paramArray['authorString'] = strip_tags($article->getAuthorString());

		parent::assignParams($paramArray);
	}

	/**
	 * @see parent::send()
	 */
	function send($request = null) {
		if (parent::send(false)) {
			if (!isset($this->skip) || !$this->skip) $this->log($request);
			if ($request) {
				$user = $request->getUser();
				if ($user && $this->attachmentsEnabled) $this->_clearAttachments($user->getId());
			}
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @see parent::sendWithParams()
	 * @param $paramArray array
	 * @param $request object
	 */
	function sendWithParams($paramArray, $request) {
		$savedSubject = $this->getSubject();
		$savedBody = $this->getBody();

		$this->assignParams($paramArray);

		$ret = $this->send($request);

		$this->setSubject($savedSubject);
		$this->setBody($savedBody);

		return $ret;
	}

	/**
	 * Set the journal this message is associated with.
	 * @param $journal object
	 */
	function setJournal($journal) {
		$this->journal = $journal;
	}

	/**
	 * Save the email in the article email log.
	 * @param $request object
	 */
	function log($request = null) {
		$articleEmailLogDao = DAORegistry::getDAO('SubmissionEmailLogDAO');
		$entry = $articleEmailLogDao->newDataObject();
		$article =& $this->article;

		// Log data
		$entry->setEventType($this->eventType);
		$entry->setSubject($this->getSubject());
		$entry->setBody($this->getBody());
		$entry->setFrom($this->getFromString(false));
		$entry->setRecipients($this->getRecipientString());
		$entry->setCcs($this->getCcString());
		$entry->setBccs($this->getBccString());
		$entry->setAssocType(ASSOC_TYPE_SUBMISSION);
		$entry->setAssocId($article->getId());
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

		// Add log entry
		import('classes.log.ArticleLog');
		$logEntryId = $articleEmailLogDao->insertObject($entry);

		// Add attachments
		import('classes.file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($article->getId());
		foreach ($this->getAttachmentFiles() as $attachment) {
			$articleFileManager->temporaryFileToArticleFile(
				$attachment,
				SUBMISSION_FILE_ATTACHMENT,
				$logEntryId
			);
		}
	}

	function toAssignedEditors($articleId) {
		$returner = array();
		$editAssignmentDao = DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignments = $editAssignmentDao->getEditorAssignmentsByArticleId($articleId);
		while ($editAssignment = $editAssignments->next()) {
			$this->addRecipient($editAssignment->getEditorEmail(), $editAssignment->getEditorFullName());
			$returner[] = $editAssignment;
		}
		return $returner;
	}

	function toAssignedReviewingSectionEditors($articleId) {
		$returner = array();
		$editAssignmentDao = DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignments = $editAssignmentDao->getReviewingSectionEditorAssignmentsByArticleId($articleId);
		while ($editAssignment = $editAssignments->next()) {
			$this->addRecipient($editAssignment->getEditorEmail(), $editAssignment->getEditorFullName());
			$returner[] = $editAssignment;
		}
		return $returner;
	}

	function toAssignedEditingSectionEditors($articleId) {
		$returner = array();
		$editAssignmentDao = DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignments = $editAssignmentDao->getEditingSectionEditorAssignmentsByArticleId($articleId);
		while ($editAssignment = $editAssignments->next()) {
			$this->addRecipient($editAssignment->getEditorEmail(), $editAssignment->getEditorFullName());
			$returner[] = $editAssignment;
		}
		return $returner;
	}

	function ccAssignedEditors($articleId) {
		$returner = array();
		$editAssignmentDao = DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignments = $editAssignmentDao->getEditorAssignmentsByArticleId($articleId);
		while ($editAssignment = $editAssignments->next()) {
			$this->addCc($editAssignment->getEditorEmail(), $editAssignment->getEditorFullName());
			$returner[] = $editAssignment;
		}
		return $returner;
	}

	function ccAssignedReviewingSectionEditors($articleId) {
		$returner = array();
		$editAssignmentDao = DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignments = $editAssignmentDao->getReviewingSectionEditorAssignmentsByArticleId($articleId);
		while ($editAssignment = $editAssignments->next()) {
			$this->addCc($editAssignment->getEditorEmail(), $editAssignment->getEditorFullName());
			$returner[] = $editAssignment;
		}
		return $returner;
	}

	function ccAssignedEditingSectionEditors($articleId) {
		$returner = array();
		$editAssignmentDao = DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignments = $editAssignmentDao->getEditingSectionEditorAssignmentsByArticleId($articleId);
		while ($editAssignment = $editAssignments->next()) {
			$this->addCc($editAssignment->getEditorEmail(), $editAssignment->getEditorFullName());
			$returner[] = $editAssignment;
		}
		return $returner;
	}

	/**
	 *  Send this email to all assigned section editors in the given stage
	 * @param $articleId int
	 * @param $stageId int
	 */
	function toAssignedSectionEditors($articleId, $stageId) {
		return $this->_addUsers($articleId, ROLE_ID_SUB_EDITOR, $stageId, 'addRecipient');
	}

	/**
	 *  CC this email to all assigned section editors in the given stage
	 * @param $articleId int
	 * @param $stageId int
	 * @return array of Users (note, this differs from OxS which returns EditAssignment objects)
	 */
	function ccAssignedSectionEditors($articleId, $stageId) {
		return $this->_addUsers($articleId, ROLE_ID_SUB_EDITOR, $stageId, 'addCc');
	}

	/**
	 *  BCC this email to all assigned section editors in the given stage
	 * @param $articleId int
	 * @param $stageId int
	 */
	function bccAssignedSectionEditors($articleId, $stageId) {
		return $this->_addUsers($articleId, ROLE_ID_SUB_EDITOR, $stageId, 'addBcc');
	}

	/**
	 * Private method to fetch the requested users and add to the email
	 * @param $articleId int
	 * @param $roleId int
	 * @param $stageId int
	 * @param $method string one of addRecipient, addCC, or addBCC
	 * @return array of Users (note, this differs from OxS which returns EditAssignment objects)
	 */
	function _addUsers($articleId, $roleId, $stageId, $method) {
		assert(in_array($method, array('addRecipient', 'addCc', 'addBcc')));

		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$userGroups =& $userGroupDao->getByRoleId($this->journal->getId(), $roleId);

		$returner = array();
		// Cycle through all the userGroups for this role
		while ( $userGroup =& $userGroups->next() ) {
			$userStageAssignmentDao = DAORegistry::getDAO('UserStageAssignmentDAO');
			// FIXME: #6692# Should this be getting users just for a specific user group?
			$users = $userStageAssignmentDao->getUsersBySubmissionAndStageId($articleId, $stageId, $userGroup->getId());
			while ($user = $users->next()) {
				$this->$method($user->getEmail(), $user->getFullName());
				$returner[] = $user;
			}
			unset($userGroup);
		}
		return $returner;
	}
}

?>
