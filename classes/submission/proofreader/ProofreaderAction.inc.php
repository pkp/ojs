<?php

/**
 * ProofreaderAction.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission.proofreader.ProofreaderAction
 *
 * ProofreaderAction class.
 *
 * $Id$
 */

class ProofreaderAction extends Action {

	/**
	 * Select a proofreader for submission
	 */
	function selectProofreader($userId, $articleId) {
		$proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
		$proofAssignment = $proofAssignmentDao->getProofAssignmentByArticleId($articleId);
		$proofAssignment->setProofreaderId($userId);
		$proofAssignmentDao->updateProofAssignment($proofAssignment);
	}

	/**
	 * Queue the submission for scheduling
	 */
	function queueForScheduling($articleId) {
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$article = $articleDao->getArticle($articleId);

		$article->setStatus(SCHEDULED);
		$articleDao->updateArticle($article);

		$proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
		$proofAssignment = $proofAssignmentDao->getProofAssignmentByArticleId($articleId);
		$proofAssignment->setDateSchedulingQueue(Core::getCurrentDate());
		$proofAssignmentDao->updateProofAssignment($proofAssignment);
	}

	/**
	 * Proofread Emails
	 * @param $articleId int
	 * @param $mailType defined string - type of proofread mail being sent
	 * @param $actionPath string - form action
	 */
	function proofreadEmail($articleId, $mailType, $actionPath = '') {

		$proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$sectionEditorSubmission = &$sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();

		$proofAssignment = $proofAssignmentDao->getProofAssignmentByArticleId($articleId);
		$useProofreaders = $journal->getSetting('useProofreaders');

		switch($mailType) {
			case 'PROOFREAD_AUTHOR_REQ':
				$eventType = ARTICLE_EMAIL_PROOFREAD_NOTIFY_AUTHOR;
				$assocType = ARTICLE_EMAIL_TYPE_PROOFREAD;
				$setDateField = 'setDateAuthorNotified';
				$receiver = &$userDao->getUser($sectionEditorSubmission->getUserId());
				$addParamArray = array(
					'authorName' => $receiver->getFullName(),
					'authorUsername' => $receiver->getUsername(),
					'authorPassword' => $receiver->getPassword(),
					'editorialContactSignature' => $user->getFullName() . "\n" . $journal->getTitle() . "\n" . $user->getAffiliation() 	
				);
				break;

			case 'PROOFREAD_AUTHOR_ACK':
				$eventType = ARTICLE_EMAIL_PROOFREAD_THANK_AUTHOR;
				$assocType = ARTICLE_EMAIL_TYPE_PROOFREAD;
				$setDateField = 'setDateAuthorAcknowledged';
				$receiver = &$userDao->getUser($sectionEditorSubmission->getUserId());
				$addParamArray = array(
					'authorName' => $receiver->getFullName(),
					'editorialContactSignature' => $user->getFullName() . "\n" . $journal->getTitle() . "\n" . $user->getAffiliation() 	
				);
				break;

			case 'PROOFREAD_AUTHOR_COMP':
				$eventType = ARTICLE_EMAIL_PROOFREAD_NOTIFY_AUTHOR_COMPLETE;
				$assocType = ARTICLE_EMAIL_TYPE_PROOFREAD;
				$setDateField = 'setDateAuthorCompleted';
				$editor = $sectionEditorSubmission->getEditor();
				$receiver = &$userDao->getUser($editor->getEditorId());
				$addParamArray = array(
					'editorialContactName' => $receiver->getFullName(),
					'authorName' => $user->getFullName()
				);
				break;
			
			case 'PROOFREAD_REQ':
				$eventType = ARTICLE_EMAIL_PROOFREAD_NOTIFY_PROOFREADER;
				$assocType = ARTICLE_EMAIL_TYPE_PROOFREAD;
				$setDateField = 'setDateProofreaderNotified';
				$receiver = &$userDao->getUser($proofAssignment->getProofreaderId());
				$addParamArray = array(
					'proofreaderName' => $receiver->getFullName(),
					'proofreaderUsername' => $receiver->getUsername(),
					'proofreaderPassword' => $receiver->getPassword(),
					'editorialContactSignature' => $user->getFullName() . "\n" . $journal->getTitle() . "\n" . $user->getAffiliation() 	
				);
				break;

			case 'PROOFREAD_ACK':
				$eventType = ARTICLE_EMAIL_PROOFREAD_THANK_PROOFREADER;
				$assocType = ARTICLE_EMAIL_TYPE_PROOFREAD;
				$setDateField = 'setDateProofreaderAcknowledged';
				$receiver = &$userDao->getUser($proofAssignment->getProofreaderId());
				$addParamArray = array(
					'proofreaderName' => $receiver->getFullName(),
					'editorialContactSignature' => $user->getFullName() . "\n" . $journal->getTitle() . "\n" . $user->getAffiliation() 	
				);
				break;

			case 'PROOFREAD_COMP':
				$eventType = ARTICLE_EMAIL_PROOFREAD_NOTIFY_PROOFREADER_COMPLETE;
				$assocType = ARTICLE_EMAIL_TYPE_PROOFREAD;
				$setDateField = 'setDateProofreaderCompleted';
				$editor = $sectionEditorSubmission->getEditor();
				$receiver = &$userDao->getUser($editor->getEditorId());
				$addParamArray = array(
					'editorialContactName' => $receiver->getFullName(),
					'proofreaderName' => $user->getFullName()
				);
				break;

			case 'PROOFREAD_LAYOUTEDITOR_REQ':
				$eventType = ARTICLE_EMAIL_PROOFREAD_NOTIFY_LAYOUTEDITOR;
				$assocType = ARTICLE_EMAIL_TYPE_PROOFREAD;
				$setDateField = 'setDateLayoutEditorNotified';
				$layoutAssignment = $sectionEditorSubmission->getLayoutAssignment();
				$receiver = &$userDao->getUser($layoutAssignment->getEditorId());
				$addParamArray = array(
					'layoutEditorName' => $receiver->getFullName(),
					'layoutEditorUsername' => $receiver->getUsername(),
					'layoutEditorPassword' => $receiver->getPassword(),
					'editorialContactSignature' => $user->getFullName() . "\n" . $journal->getTitle() . "\n" . $user->getAffiliation() 	
				);
				break;

			case 'PROOFREAD_LAYOUTEDITOR_ACK':
				$eventType = ARTICLE_EMAIL_PROOFREAD_THANK_LAYOUTEDITOR;
				$assocType = ARTICLE_EMAIL_TYPE_PROOFREAD;
				$setDateField = 'setDateLayoutEditorAcknowledged';
				$layoutAssignment = $sectionEditorSubmission->getLayoutAssignment();
				$receiver = &$userDao->getUser($layoutAssignment->getEditorId());
				$addParamArray = array(
					'layoutEditorName' => $receiver->getFullName(),
					'editorialContactSignature' => $user->getFullName() . "\n" . $journal->getTitle() . "\n" . $user->getAffiliation() 	
				);
				break;

			case 'PROOFREAD_LAYOUTEDITOR_COMP':
				$eventType = ARTICLE_EMAIL_PROOFREAD_NOTIFY_LAYOUTEDITOR_COMPLETE;
				$assocType = ARTICLE_EMAIL_TYPE_PROOFREAD;
				$setDateField = 'setDateLayoutEditorCompleted';
				$editor = $sectionEditorSubmission->getEditor();
				$receiver = &$userDao->getUser($editor->getEditorId());
				$addParamArray = array(
					'editorialContactName' => $receiver->getFullName(),
					'layoutEditorName' => $user->getFullName()
				);
				break;

			default:
				return;	
		}

		$email = &new ArticleMailTemplate($articleId, $mailType);

		if ($actionPath) {
			$paramArray = array(
				'journalName' => $journal->getTitle(),
				'journalUrl' => Request::getIndexUrl() . '/' . Request::getRequestedJournalPath(),
				'articleTitle' => $sectionEditorSubmission->getArticleTitle(),
			);
			if (isset($addParamArray)) {
				$paramArray += $addParamArray;
			}
			$email->assignParams($paramArray);
			$email->displayEditForm(Request::getPageUrl() . $actionPath, array('articleId' => $articleId));
		} else {
			$email->addRecipient($receiver->getEmail(), $receiver->getFullName());
			$email->setFrom($user->getEmail(), $user->getFullName());
			$email->setSubject(Request::getUserVar('subject'));
			$email->setBody(Request::getUserVar('body'));
			$email->setAssoc($eventType, $assocType, $articleId);
			$email->send();

			$proofAssignment->$setDateField(Core::getCurrentDate());
			$proofAssignmentDao->updateProofAssignment($proofAssignment);
		}
	}

	/**
	 * Set date for author proofreading underway
	 * @param $articleId int
	 */
	function authorProofreadingUnderway($articleId) {
		$proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
		$proofAssignment = $proofAssignmentDao->getProofAssignmentByArticleId($articleId);

		if (!$proofAssignment->getDateAuthorUnderway() && $proofAssignment->getDateAuthorNotified()) {
			$proofAssignment->setDateAuthorUnderway(Core::getCurrentDate());
		}

		$proofAssignmentDao->updateProofAssignment($proofAssignment);
	}

	/**
	 * Set date for proofreader proofreading underway
	 * @param $articleId int
	 */
	function ProofreaderProofreadingUnderway($articleId) {
		$proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
		$proofAssignment = $proofAssignmentDao->getProofAssignmentByArticleId($articleId);

		if (!$proofAssignment->getDateProofreaderUnderway() && $proofAssignment->getDateProofreaderNotified()) {
			$proofAssignment->setDateProofreaderUnderway(Core::getCurrentDate());
		}

		$proofAssignmentDao->updateProofAssignment($proofAssignment);
	}

	/**
	 * Set date for layout editor proofreading underway
	 * @param $articleId int
	 */
	function layoutEditorProofreadingUnderway($articleId) {
		$proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
		$proofAssignment = $proofAssignmentDao->getProofAssignmentByArticleId($articleId);

		if (!$proofAssignment->getDateLayoutEditorUnderway() && $proofAssignment->getDateLayoutEditorNotified()) {
			$proofAssignment->setDateLayoutEditorUnderway(Core::getCurrentDate());
		}

		$proofAssignmentDao->updateProofAssignment($proofAssignment);
	}
	
}

?>
