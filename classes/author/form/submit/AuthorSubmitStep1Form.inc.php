<?php

/**
 * @file classes/author/form/submit/AuthorSubmitStep1Form.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthorSubmitStep1Form
 * @ingroup author_form_submit
 *
 * @brief Form for Step 1 of author article submission.
 */

// $Id$


import('classes.author.form.submit.AuthorSubmitForm');

class AuthorSubmitStep1Form extends AuthorSubmitForm {

	/**
	 * Constructor.
	 */
	function AuthorSubmitStep1Form($article = null) {
		parent::AuthorSubmitForm($article, 1);

		$journal =& Request::getJournal();

		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'sectionId', 'required', 'author.submit.form.sectionRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'sectionId', 'required', 'author.submit.form.sectionRequired', array(DAORegistry::getDAO('SectionDAO'), 'sectionExists'), array($journal->getId())));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$journal =& Request::getJournal();
		$user =& Request::getUser();

		$templateMgr =& TemplateManager::getManager();

		// Get sections for this journal
		$sectionDao =& DAORegistry::getDAO('SectionDAO');

		// If this user is a section editor or an editor, they are allowed
		// to submit to sections flagged as "editor-only" for submissions.
		// Otherwise, display only sections they are allowed to submit to.
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$isEditor = $roleDao->roleExists($journal->getId(), $user->getId(), ROLE_ID_EDITOR) || $roleDao->roleExists($journal->getId(), $user->getId(), ROLE_ID_SECTION_EDITOR);

		// Set up required Payment Related Information
		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager =& OJSPaymentManager::getManager();
		if ( $paymentManager->submissionEnabled() || $paymentManager->fastTrackEnabled() || $paymentManager->publicationEnabled()) {
			$templateMgr->assign('authorFees', true);
			$completedPaymentDAO =& DAORegistry::getDAO('OJSCompletedPaymentDAO');
			$articleId = $this->articleId;

			if ( $paymentManager->submissionEnabled() ) {
				$templateMgr->assign_by_ref('submissionPayment', $completedPaymentDAO->getSubmissionCompletedPayment ( $journal->getId(), $articleId ));
			}

			if ( $paymentManager->fastTrackEnabled()  ) {
				$templateMgr->assign_by_ref('fastTrackPayment', $completedPaymentDAO->getFastTrackCompletedPayment ( $journal->getId(), $articleId ));
			}
		}

		$templateMgr->assign('sectionOptions', array('0' => Locale::translate('author.submit.selectSection')) + $sectionDao->getSectionTitles($journal->getId(), !$isEditor));
		parent::display();
	}

	/**
	 * Initialize form data from current article.
	 */
	function initData() {
		if (isset($this->article)) {
			$this->_data = array(
				'sectionId' => $this->article->getSectionId(),
				'commentsToEditor' => $this->article->getCommentsToEditor()
			);
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('submissionChecklist', 'copyrightNoticeAgree', 'sectionId', 'commentsToEditor'));
	}

	/**
	 * Save changes to article.
	 * @return int the article ID
	 */
	function execute() {
		$articleDao =& DAORegistry::getDAO('ArticleDAO');

		if (isset($this->article)) {
			// Update existing article
			$this->article->setSectionId($this->getData('sectionId'));
			$this->article->setCommentsToEditor($this->getData('commentsToEditor'));
			if ($this->article->getSubmissionProgress() <= $this->step) {
				$this->article->stampStatusModified();
				$this->article->setSubmissionProgress($this->step + 1);
			}
			$articleDao->updateArticle($this->article);

		} else {
			// Insert new article
			$journal =& Request::getJournal();
			$user =& Request::getUser();

			$this->article = new Article();
			$this->article->setLocale(Locale::getLocale()); // FIXME in bug #5543
			$this->article->setUserId($user->getId());
			$this->article->setJournalId($journal->getId());
			$this->article->setSectionId($this->getData('sectionId'));
			$this->article->stampStatusModified();
			$this->article->setSubmissionProgress($this->step + 1);
			$this->article->setLanguage(String::substr($journal->getPrimaryLocale(), 0, 2));
			$this->article->setCommentsToEditor($this->getData('commentsToEditor'));

			// Set user to initial author
			$user =& Request::getUser();
			$author = new Author();
			$author->setFirstName($user->getFirstName());
			$author->setMiddleName($user->getMiddleName());
			$author->setLastName($user->getLastName());
			$author->setAffiliation($user->getAffiliation());
			$author->setCountry($user->getCountry());
			$author->setEmail($user->getEmail());
			$author->setUrl($user->getUrl());
			$author->setBiography($user->getBiography(null), null);
			$author->setPrimaryContact(1);
			$this->article->addAuthor($author);

			$articleDao->insertArticle($this->article);
			$this->articleId = $this->article->getArticleId();
		}

		return $this->articleId;
	}

}

?>
