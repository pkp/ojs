<?php

/**
 * @file AuthorSubmitStep5Form.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package author.form.submit
 * @class AuthorSubmitStep5Form
 *
 * Form for Step 5 of author article submission.
 *
 * $Id$
 */

import("author.form.submit.AuthorSubmitForm");

class AuthorSubmitStep5Form extends AuthorSubmitForm {

	/**
	 * Constructor.
	 */
	function AuthorSubmitStep5Form($article) {
		parent::AuthorSubmitForm($article, 5);
	}

	/**
	 * Display the form.
	 */
	function display() {
		$journal = &Request::getJournal();
		$user = &Request::getUser();		
		$templateMgr = &TemplateManager::getManager();

		// Get article file for this article
		$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
		$articleFiles =& $articleFileDao->getArticleFilesByArticle($this->articleId);

		$templateMgr->assign_by_ref('files', $articleFiles);
		$templateMgr->assign_by_ref('journal', Request::getJournal());

		// Set up required Payment Related Information
		import('payment.ojs.OJSPaymentManager');
		$paymentManager =& OJSPaymentManager::getManager();
		if ( $paymentManager->submissionEnabled() || $paymentManager->fastTrackEnabled() || $paymentManager->publicationEnabled()) {
			$templateMgr->assign('authorFees', true);
			$completedPaymentDAO =& DAORegistry::getDAO('OJSCompletedPaymentDAO');
			$articleId = $this->articleId;
			
			if ( $paymentManager->submissionEnabled() ) {
				$templateMgr->assign_by_ref('submissionPayment', $completedPaymentDAO->getSubmissionCompletedPayment ( $journal->getJournalId(), $articleId ));
				$templateMgr->assign('manualPayment', $journal->getSetting('paymentMethodPluginName') == 'ManualPayment');
			}
			
			if ( $paymentManager->fastTrackEnabled()  ) {
				$templateMgr->assign_by_ref('fastTrackPayment', $completedPaymentDAO->getFastTrackCompletedPayment ( $journal->getJournalId(), $articleId ));
			}	   
		}
		
		parent::display();
	}
	
	/**
	 * Initialize form data from current article.
	 */
	function initData() {
		if (isset($this->article)) {
			$this->_data = array(
				'commentsToEditor' => $this->article->getCommentsToEditor()
			);
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('qualifyForWaiver', 'commentsToEditor'));
	}	

	/**
	 * Validate the form
	 */
	function validate() {
		import('payment.ojs.OJSPaymentManager');
		$paymentManager =& OJSPaymentManager::getManager();
		if ( $paymentManager->submissionEnabled() ) {
			if ( !$this->isValid() ) return false;
	
			$journal =& Request::getJournal();
			$journalId = $journal->getJournalId();
			$articleId = $this->articleId;							
			$user =& Request::getUser();
			
			$completedPaymentDAO =& DAORegistry::getDAO('OJSCompletedPaymentDAO');
			if ( $completedPaymentDAO->hasPaidSubmission ( $journalId, $articleId )  ) {
				return true;		
			} elseif ( Request::getUserVar('qualifyForWaiver') && Request::getUserVar('commentsToEditor') != '') {  
				return true;
			} elseif ( Request::getUserVar('paymentSent') ) {
			} else {				
				$queuedPayment =& $paymentManager->createQueuedPayment($journalId, PAYMENT_TYPE_SUBMISSION, $user->getUserId(), $articleId, $journal->getSetting('submissionFee'));
				$queuedPaymentId = $paymentManager->queuePayment($queuedPayment);
		
				$paymentManager->displayPaymentForm($queuedPaymentId, $queuedPayment);
				exit;	
			}
		} else {
			return parent::validate();
		}	
	}
	
	/**
	 * Save changes to article.
	 */
	function execute() {
		$articleDao = &DAORegistry::getDAO('ArticleDAO');

		$journal = Request::getJournal();

		// Update article		
		$article = &$this->article;
		$article->setCommentsToEditor($this->getData('commentsToEditor'));
		$article->setDateSubmitted(Core::getCurrentDate());
		$article->setSubmissionProgress(0);
		$article->stampStatusModified();
		$articleDao->updateArticle($article);

		// Designate this as the review version by default.
		$authorSubmissionDao =& DAORegistry::getDAO('AuthorSubmissionDAO');
		$authorSubmission =& $authorSubmissionDao->getAuthorSubmission($article->getArticleId());
		AuthorAction::designateReviewVersion($authorSubmission, true);
		unset($authorSubmission);

		// Create additional submission mangement records
		$copyeditorSubmissionDao = &DAORegistry::getDAO('CopyeditorSubmissionDAO');
		$copyeditorSubmission = &new CopyeditorSubmission();
		$copyeditorSubmission->setArticleId($article->getArticleId());
		$copyeditorSubmission->setCopyeditorId(0);
		$copyeditorSubmissionDao->insertCopyeditorSubmission($copyeditorSubmission);

		$layoutDao = &DAORegistry::getDAO('LayoutAssignmentDAO');
		$layoutAssignment = &new LayoutAssignment();
		$layoutAssignment->setArticleId($article->getArticleId());
		$layoutAssignment->setEditorId(0);
		$layoutDao->insertLayoutAssignment($layoutAssignment);

		$proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
		$proofAssignment = &new ProofAssignment();
		$proofAssignment->setArticleId($article->getArticleId());
		$proofAssignment->setProofreaderId(0);
		$proofAssignmentDao->insertProofAssignment($proofAssignment);

		$sectionEditors = $this->assignEditors($article);

		$user = &Request::getUser();

		// Update search index
		import('search.ArticleSearchIndex');
		ArticleSearchIndex::indexArticleMetadata($article);
		ArticleSearchIndex::indexArticleFiles($article);

		// Send author notification email
		import('mail.ArticleMailTemplate');
		$mail = &new ArticleMailTemplate($article, 'SUBMISSION_ACK');
		$mail->setFrom($journal->getSetting('contactEmail'), $journal->getSetting('contactName'));
		if ($mail->isEnabled()) {
			$mail->addRecipient($user->getEmail(), $user->getFullName());
			// If necessary, BCC the acknowledgement to someone.
			if($journal->getSetting('copySubmissionAckPrimaryContact')) {
				$mail->addBcc(
					$journal->getSetting('contactEmail'),
					$journal->getSetting('contactName')
				);
			}
			if($journal->getSetting('copySubmissionAckSpecified')) {
				$copyAddress = $journal->getSetting('copySubmissionAckAddress');
				if (!empty($copyAddress)) $mail->addBcc($copyAddress);
			}

			// Also BCC automatically assigned section editors
			foreach ($sectionEditors as $sectionEditorEntry) {
				$sectionEditor =& $sectionEditorEntry['user'];
				$mail->addBcc($sectionEditor->getFullName(), $sectionEditor->getEmail());
				unset($sectionEditor);
			}

			$mail->assignParams(array(
				'authorName' => $user->getFullName(),
				'authorUsername' => $user->getUsername(),
				'editorialContactSignature' => $journal->getSetting('contactName') . "\n" . $journal->getJournalTitle(),
				'submissionUrl' => Request::url(null, 'author', 'submission', $article->getArticleId())
			));
			$mail->send();
		}

		import('article.log.ArticleLog');
		import('article.log.ArticleEventLogEntry');
		ArticleLog::logEvent($this->articleId, ARTICLE_LOG_ARTICLE_SUBMIT, ARTICLE_LOG_TYPE_AUTHOR, $user->getUserId(), 'log.author.submitted', array('submissionId' => $article->getArticleId(), 'authorName' => $user->getFullName()));

		return $this->articleId;
	}

}

?>
