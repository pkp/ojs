<?php

/**
 * @file classes/author/form/submit/AuthorSubmitStep5Form.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthorSubmitStep5Form
 * @ingroup author_form_submit
 *
 * @brief Form for Step 5 of author article submission.
 */

import('classes.author.form.submit.AuthorSubmitForm');

class AuthorSubmitStep5Form extends AuthorSubmitForm {
	/**
	 * Constructor.
	 */
	function AuthorSubmitStep5Form(&$article, &$journal, $request) {
		parent::AuthorSubmitForm($article, 5, $journal, $request);

		$this->addCheck(new FormValidatorCustom($this, 'qualifyForWaiver', 'optional', 'author.submit.mustEnterWaiverReason', array(&$this, 'checkWaiverReason')));
	}

	/**
	 * Check that if the user choses a Waiver that they enter text in the comments to Editor
	 */
	function checkWaiverReason() {
		if ($this->request->getUserVar('qualifyForWaiver') == false ) return true;
		else return ($this->request->getUserVar('commentsToEditor') != '');
	}

	/**
	 * Display the form.
	 */
	function display() {
		$journal =& $this->request->getJournal();
		$user =& $this->request->getUser();
		$templateMgr =& TemplateManager::getManager();

		// Get article file for this article
		$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');
		$articleFiles =& $articleFileDao->getArticleFilesByArticle($this->articleId);

		$templateMgr->assign_by_ref('files', $articleFiles);
		$templateMgr->assign_by_ref('journal', $journal);

		// Set up required Payment Related Information
		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($this->request);
		if ( $paymentManager->submissionEnabled() || $paymentManager->fastTrackEnabled() || $paymentManager->publicationEnabled()) {
			$templateMgr->assign('authorFees', true);
			$completedPaymentDao =& DAORegistry::getDAO('OJSCompletedPaymentDAO');
			$articleId = $this->articleId;

			if ($paymentManager->submissionEnabled()) {
				$templateMgr->assign_by_ref('submissionPayment', $completedPaymentDao->getSubmissionCompletedPayment ($journal->getId(), $articleId));
				$templateMgr->assign('manualPayment', $journal->getSetting('paymentMethodPluginName') == 'ManualPayment');
			}

			if ($paymentManager->fastTrackEnabled()) {
				$templateMgr->assign_by_ref('fastTrackPayment', $completedPaymentDao->getFastTrackCompletedPayment ($journal->getId(), $articleId));
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
		$this->readUserVars(array('paymentSent', 'qualifyForWaiver', 'commentsToEditor'));
	}

	/**
	 * Validate the form
	 */
	function validate() {
		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($this->request);
		if ( $paymentManager->submissionEnabled() ) {
			if (!parent::validate()) return false;

			$journal =& $this->request->getJournal();
			$journalId = $journal->getId();
			$articleId = $this->articleId;
			$user =& $this->request->getUser();

			$completedPaymentDao =& DAORegistry::getDAO('OJSCompletedPaymentDAO');
			if ($completedPaymentDao->hasPaidSubmission($journalId, $articleId)) {
				return parent::validate();
			} elseif ($this->request->getUserVar('qualifyForWaiver') && $this->request->getUserVar('commentsToEditor') != '') {
				return parent::validate();
			} elseif ($this->request->getUserVar('paymentSent')) {
				return parent::validate();
			} else {
				$queuedPayment =& $paymentManager->createQueuedPayment($journalId, PAYMENT_TYPE_SUBMISSION, $user->getId(), $articleId, $journal->getSetting('submissionFee'));
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
		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');

		$journal =& $this->request->getJournal();
		$user =& $this->request->getUser();

		// Update article
		$article =& $this->article;

		if ($this->getData('commentsToEditor') != '') {
			$article->setCommentsToEditor($this->getData('commentsToEditor'));
		}

		$article->setDateSubmitted(Core::getCurrentDate());
		$article->setSubmissionProgress(0);
		$article->stampStatusModified();
		$articleDao->updateArticle($article);

		// Designate this as the review version by default.
		$authorSubmissionDao =& DAORegistry::getDAO('AuthorSubmissionDAO');
		$authorSubmission =& $authorSubmissionDao->getAuthorSubmission($article->getId());
		AuthorAction::designateReviewVersion($authorSubmission, true);
		unset($authorSubmission);

		$copyeditInitialSignoff = $signoffDao->build('SIGNOFF_COPYEDITING_INITIAL', ASSOC_TYPE_ARTICLE, $article->getId());
		$copyeditAuthorSignoff = $signoffDao->build('SIGNOFF_COPYEDITING_AUTHOR', ASSOC_TYPE_ARTICLE, $article->getId());
		$copyeditFinalSignoff = $signoffDao->build('SIGNOFF_COPYEDITING_FINAL', ASSOC_TYPE_ARTICLE, $article->getId());
		$copyeditInitialSignoff->setUserId(0);
		$copyeditAuthorSignoff->setUserId($user->getId());
		$copyeditFinalSignoff->setUserId(0);
		$signoffDao->updateObject($copyeditInitialSignoff);
		$signoffDao->updateObject($copyeditAuthorSignoff);
		$signoffDao->updateObject($copyeditFinalSignoff);

		$layoutSignoff = $signoffDao->build('SIGNOFF_LAYOUT', ASSOC_TYPE_ARTICLE, $article->getId());
		$layoutSignoff->setUserId(0);
		$signoffDao->updateObject($layoutSignoff);

		$proofAuthorSignoff = $signoffDao->build('SIGNOFF_PROOFREADING_AUTHOR', ASSOC_TYPE_ARTICLE, $article->getId());
		$proofProofreaderSignoff = $signoffDao->build('SIGNOFF_PROOFREADING_PROOFREADER', ASSOC_TYPE_ARTICLE, $article->getId());
		$proofLayoutEditorSignoff = $signoffDao->build('SIGNOFF_PROOFREADING_LAYOUT', ASSOC_TYPE_ARTICLE, $article->getId());
		$proofAuthorSignoff->setUserId($user->getId());
		$proofProofreaderSignoff->setUserId(0);
		$proofLayoutEditorSignoff->setUserId(0);
		$signoffDao->updateObject($proofAuthorSignoff);
		$signoffDao->updateObject($proofProofreaderSignoff);
		$signoffDao->updateObject($proofLayoutEditorSignoff);

		$sectionEditors = $this->assignEditors($article);

		// Send author notification email
		import('classes.mail.ArticleMailTemplate');
		$mail = new ArticleMailTemplate($article, 'SUBMISSION_ACK', null, null, null, false);
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
				$mail->addBcc($sectionEditor->getEmail(), $sectionEditor->getFullName());
				unset($sectionEditor);
			}

			$mail->assignParams(array(
				'authorName' => $user->getFullName(),
				'authorUsername' => $user->getUsername(),
				'editorialContactSignature' => $journal->getSetting('contactName') . "\n" . $journal->getLocalizedTitle(),
				'submissionUrl' => $this->request->url(null, 'author', 'submission', $article->getId())
			));
			$mail->send($this->request);
		}

		import('classes.article.log.ArticleLog');
		ArticleLog::logEvent($this->request, $article, ARTICLE_LOG_ARTICLE_SUBMIT, 'log.author.submitted', array('authorName' => $user->getFullName()));

		return $this->articleId;
	}

}

?>
