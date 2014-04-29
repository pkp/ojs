<?php

/**
 * @file classes/author/form/submit/AuthorSubmitStep1Form.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthorSubmitStep1Form
 * @ingroup author_form_submit
 *
 * @brief Form for Step 1 of author article submission.
 */


import('classes.author.form.submit.AuthorSubmitForm');

class AuthorSubmitStep1Form extends AuthorSubmitForm {

	/**
	 * Constructor.
	 */
	function AuthorSubmitStep1Form(&$article, &$journal, $request) {
		parent::AuthorSubmitForm($article, 1, $journal, $request);

		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'sectionId', 'required', 'author.submit.form.sectionRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'sectionId', 'required', 'author.submit.form.sectionRequired', array(DAORegistry::getDAO('SectionDAO'), 'sectionExists'), array($journal->getId())));

		$supportedSubmissionLocales = $journal->getSetting('supportedSubmissionLocales');
		if (!is_array($supportedSubmissionLocales) || count($supportedSubmissionLocales) < 1) $supportedSubmissionLocales = array($journal->getPrimaryLocale());
		$this->addCheck(new FormValidatorInSet($this, 'locale', 'required', 'author.submit.form.localeRequired', $supportedSubmissionLocales));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$journal =& $this->request->getJournal();
		$user =& $this->request->getUser();

		$templateMgr =& TemplateManager::getManager();

		// Get sections for this journal
		$sectionDao =& DAORegistry::getDAO('SectionDAO');

		// If this user is a section editor or an editor, they are
		// allowed to submit to sections flagged as "editor-only" for
		// submissions. Otherwise, display only sections they are
		// allowed to submit to.
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$isEditor = $roleDao->userHasRole($journal->getId(), $user->getId(), ROLE_ID_EDITOR) || $roleDao->userHasRole($journal->getId(), $user->getId(), ROLE_ID_SECTION_EDITOR);
		$templateMgr->assign('sectionOptions', array('0' => __('author.submit.selectSection')) + $sectionDao->getSectionTitles($journal->getId(), !$isEditor));

		// Set up required Payment Related Information
		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($this->request);
		if ( $paymentManager->submissionEnabled() || $paymentManager->fastTrackEnabled() || $paymentManager->publicationEnabled()) {
			$templateMgr->assign('authorFees', true);
			$completedPaymentDao =& DAORegistry::getDAO('OJSCompletedPaymentDAO');
			$articleId = $this->articleId;

			if ($paymentManager->submissionEnabled()) {
				$templateMgr->assign_by_ref('submissionPayment', $completedPaymentDao->getSubmissionCompletedPayment ($journal->getId(), $articleId));
			}

			if ($paymentManager->fastTrackEnabled()) {
				$templateMgr->assign_by_ref('fastTrackPayment', $completedPaymentDao->getFastTrackCompletedPayment ($journal->getId(), $articleId));
			}
		}

		// Provide available submission languages. (Convert the array
		// of locale symbolic names xx_XX into an associative array
		// of symbolic names => readable names.)
		$supportedSubmissionLocales = $journal->getSetting('supportedSubmissionLocales');
		if (empty($supportedSubmissionLocales)) $supportedSubmissionLocales = array($journal->getPrimaryLocale());
		$templateMgr->assign(
			'supportedSubmissionLocaleNames',
			array_flip(array_intersect(
				array_flip(AppLocale::getAllLocales()),
				$supportedSubmissionLocales
			))
		);

		parent::display();
	}

	/**
	 * Initialize form data from current article.
	 */
	function initData() {
		if (isset($this->article)) {
			$this->_data = array(
				'sectionId' => $this->article->getSectionId(),
				'locale' => $this->article->getLocale(),
				'commentsToEditor' => $this->article->getCommentsToEditor()
			);
		} else {
			$journal =& $this->request->getJournal();
			$supportedSubmissionLocales = $journal->getSetting('supportedSubmissionLocales');
			// Try these locales in order until we find one that's
			// supported to use as a default.
			$fallbackLocales = array_keys($supportedSubmissionLocales);
			$tryLocales = array(
				$this->getFormLocale(), // Current form locale
				AppLocale::getLocale(), // Current UI locale
				$journal->getPrimaryLocale(), // Journal locale
				$supportedSubmissionLocales[array_shift($fallbackLocales)] // Fallback: first one on the list
			);
			$this->_data = array();
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
		$this->readUserVars(array('locale', 'submissionChecklist', 'copyrightNoticeAgree', 'sectionId', 'commentsToEditor'));
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
			$this->article->setLocale($this->getData('locale'));
			$this->article->setCommentsToEditor($this->getData('commentsToEditor'));
			if ($this->article->getSubmissionProgress() <= $this->step) {
				$this->article->stampStatusModified();
				$this->article->setSubmissionProgress($this->step + 1);
			}
			$articleDao->updateArticle($this->article);

		} else {
			// Insert new article
			$journal =& $this->request->getJournal();
			$user =& $this->request->getUser();

			$this->article = new Article();
			$this->article->setLocale($this->getData('locale'));
			$this->article->setUserId($user->getId());
			$this->article->setJournalId($journal->getId());
			$this->article->setSectionId($this->getData('sectionId'));
			$this->article->stampStatusModified();
			$this->article->setSubmissionProgress($this->step + 1);
			$this->article->setLanguage(String::substr($this->article->getLocale(), 0, 2));
			$this->article->setCommentsToEditor($this->getData('commentsToEditor'));
			$articleDao->insertArticle($this->article);
			$this->articleId = $this->article->getId();

			// Set user to initial author
			$authorDao =& DAORegistry::getDAO('AuthorDAO'); /* @var $authorDao AuthorDAO */
			$user =& $this->request->getUser();
			$author = new Author();
			$author->setSubmissionId($this->articleId);
			$author->setFirstName($user->getFirstName());
			$author->setMiddleName($user->getMiddleName());
			$author->setLastName($user->getLastName());
			$author->setAffiliation($user->getAffiliation(null), null);
			$author->setCountry($user->getCountry());
			$author->setEmail($user->getEmail());
			$author->setData('orcid', $user->getData('orcid'));
			$author->setUrl($user->getUrl());
			$author->setBiography($user->getBiography(null), null);
			$author->setPrimaryContact(1);
			$authorDao->insertAuthor($author);
		}

		return $this->articleId;
	}

}

?>
