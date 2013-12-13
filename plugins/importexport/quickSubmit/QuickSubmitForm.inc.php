<?php

/**
 * @file plugins/importexport/quickSubmit/QuickSubmitForm.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class QuickSubmitForm
 * @ingroup plugins_importexport_quickSubmit
 *
 * @brief Form for QuickSubmit one-page submission plugin
 */


import('lib.pkp.classes.form.Form');

class QuickSubmitForm extends Form {
	/** @var object */
	var $request;

	/**
	 * Constructor
	 * @param $plugin object
	 */
	function QuickSubmitForm(&$plugin, $request) {
		parent::Form($plugin->getTemplatePath() . 'index.tpl');

		$this->request = $request;
		$journal = $request->getJournal();

		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidator($this, 'sectionId', 'required', 'author.submit.form.sectionRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'tempFileId', 'required', 'plugins.importexport.quickSubmit.submissionRequired', create_function('$tempFileId', 'return $tempFileId > 0;')));
		$this->addCheck(new FormValidatorCustom($this, 'sectionId', 'required', 'author.submit.form.sectionRequired', array(DAORegistry::getDAO('SectionDAO'), 'sectionExists'), array($journal->getId())));
		$this->addCheck(new FormValidatorCustom($this, 'authors', 'required', 'author.submit.form.authorRequired', create_function('$authors', 'return count($authors) > 0;')));
		$this->addCheck(new FormValidatorCustom($this, 'destination', 'required', 'plugins.importexport.quickSubmit.issueRequired', create_function('$destination, $form', 'return $destination == \'queue\'? true : ($form->getData(\'issueId\') > 0);'), array($this)));
		$this->addCheck(new FormValidatorArray($this, 'authors', 'required', 'author.submit.form.authorRequiredFields', array('firstName', 'lastName')));
		$this->addCheck(new FormValidatorArrayCustom($this, 'authors', 'required', 'user.profile.form.emailRequired', create_function('$email, $regExp', 'return String::regexp_match($regExp, $email);'), array(ValidatorEmail::getRegexp()), false, array('email')));
		$this->addCheck(new FormValidatorArrayCustom($this, 'authors', 'required', 'user.profile.form.urlInvalid', create_function('$url, $regExp', 'return empty($url) ? true : String::regexp_match($regExp, $url);'), array(ValidatorUrl::getRegexp()), false, array('url')));
		$this->addCheck(new FormValidatorLocale($this, 'title', 'required', 'author.submit.form.titleRequired'));

	}

	/**
	 * Get the names of fields for which data should be localized
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('tempFileId', 'title', 'abstract', 'discipline', 'subjectClass', 'subject', 'coverageGeo', 'coverageChron', 'coverageSample', 'type', 'sponsor');
	}

	/**
	 * Display the form.
	 */
	function display() {
		$request = $this->request;
		$templateMgr = TemplateManager::getManager($request);
		$user = $request->getUser();
		$journal = $request->getJournal();
		$formLocale = $this->getFormLocale();

		$templateMgr->assign('journal', $journal);

		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$sections = $sectionDao->getByJournalId($journal->getId());
		$sectionTitles = $sectionAbstractsRequired = array();
		while ($section = $sections->next()) {
			$sectionTitles[$section->getId()] = $section->getLocalizedTitle();
			$sectionAbstractsRequired[(int) $section->getId()] = (int) (!$section->getAbstractsNotRequired());
			unset($section);
		}

		$templateMgr->assign('sectionOptions', array('0' => __('author.submit.selectSection')) + $sectionTitles);
		$templateMgr->assign('sectionAbstractsRequired', $sectionAbstractsRequired);

		$countryDao = DAORegistry::getDAO('CountryDAO');
		$templateMgr->assign('countries', $countryDao->getCountries());

		import('classes.issue.IssueAction');
		$issueAction = new IssueAction();
		$templateMgr->assign('issueOptions', $issueAction->getIssueOptions());

		import('lib.pkp.classes.file.TemporaryFileManager');
		$temporaryFileManager = new TemporaryFileManager();
		$tempFileId = $this->getData('tempFileId');
		if (isset($tempFileId[$formLocale]) && $tempFileId[$formLocale] > 0) {
			$submissionFile = $temporaryFileManager->getFile($tempFileId[$formLocale], $user->getId());
			$templateMgr->assign('submissionFile', $submissionFile);
		}

		if ($request->getUserVar('addAuthor') || $request->getUserVar('delAuthor')  || $request->getUserVar('moveAuthor')) {
			$templateMgr->assign('scrollToAuthor', true);
		}

		if ($request->getUserVar('destination') == 'queue' ) {
			$templateMgr->assign('publishToIssue', false);
		} else {
			$templateMgr->assign('issueNumber', $request->getUserVar('issueId'));
			$templateMgr->assign('publishToIssue', true);
		}

		$templateMgr->assign('enablePageNumber', $journal->getSetting('enablePageNumber'));

		parent::display();
	}


	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(
			array(
				'tempFileId',
				'destination',
				'issueId',
				'pages',
				'sectionId',
				'authors',
				'primaryContact',
				'title',
				'abstract',
				'discipline',
				'subjectClass',
				'subject',
				'coverageGeo',
				'coverageChron',
				'coverageSample',
				'type',
				'language',
				'sponsor',
				'citations',
				'title',
				'abstract'
			)
		);

		$this->readUserDateVars(array('datePublished'));

		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$section = $sectionDao->getById($this->getData('sectionId'));
		if ($section && !$section->getAbstractsNotRequired()) {
			$this->addCheck(new FormValidatorLocale($this, 'abstract', 'required', 'author.submit.form.abstractRequired'));
		}
	}

	/**
	 * Upload the submission file.
	 * @param $fileName string
	 * @return int TemporaryFile ID
	 */
	function uploadSubmissionFile($fileName) {
		import('lib.pkp.classes.file.TemporaryFileManager');
		$temporaryFileManager = new TemporaryFileManager();
		$request = $this->request;
		$user = $request->getUser();

		$temporaryFile = $temporaryFileManager->handleUpload($fileName, $user->getId());

		if ($temporaryFile) {
			return $temporaryFile->getId();
		} else {
			return false;
		}
	}

	/**
	 * Save settings.
	 */
	function execute() {
		$articleDao = DAORegistry::getDAO('ArticleDAO');
		$signoffDao = DAORegistry::getDAO('SignoffDAO');
		$sectionEditorSubmissionDao = DAORegistry::getDAO('SectionEditorSubmissionDAO');

		$application = PKPApplication::getApplication();
		$request = $this->request;
		$user = $request->getUser();
		$router = $request->getRouter();
		$journal = $router->getContext($request);

		$article = $articleDao->newDataObject();
		$article->setLocale($journal->getPrimaryLocale()); // FIXME in bug #5543
		$article->setUserId($user->getId());
		$article->setJournalId($journal->getId());
		$article->setSectionId($this->getData('sectionId'));
		$article->setLanguage($this->getData('language'));
		$article->setTitle($this->getData('title'), null); // Localized
		$article->setAbstract($this->getData('abstract'), null); // Localized
		$article->setDiscipline($this->getData('discipline'), null); // Localized
		$article->setSubjectClass($this->getData('subjectClass'), null); // Localized
		$article->setSubject($this->getData('subject'), null); // Localized
		$article->setCoverageGeo($this->getData('coverageGeo'), null); // Localized
		$article->setCoverageChron($this->getData('coverageChron'), null); // Localized
		$article->setCoverageSample($this->getData('coverageSample'), null); // Localized
		$article->setType($this->getData('type'), null); // Localized
		$article->setSponsor($this->getData('sponsor'), null); // Localized
		$article->setCitations($this->getData('citations'));
		$article->setPages($this->getData('pages'));

		// Set some default values so the ArticleDAO doesn't complain when adding this article
		$article->setDateSubmitted(Core::getCurrentDate());
		$article->setStatus(STATUS_PUBLISHED);
		$article->setSubmissionProgress(0);
		$article->stampStatusModified();
		$article->setCurrentRound(1);
		$article->setFastTracked(1);
		$article->setHideAuthor(0);
		$article->setCommentsStatus(0);

		// Insert the article to get it's ID
		$articleDao->insertObject($article);
		$articleId = $article->getId();

		// Add authors
		$authors = $this->getData('authors');
		for ($i=0, $count=count($authors); $i < $count; $i++) {
			if ($authors[$i]['authorId'] > 0) {
				// Update an existing author
				$author = $authorDao->getById($authors[$i]['authorId'], $articleId);
				$isExistingAuthor = true;
			} else {
				// Create a new author
				$author = new Author();
				$isExistingAuthor = false;
			}

			if ($author != null) {
				$author->setSubmissionId($articleId);
				$author->setFirstName($authors[$i]['firstName']);
				$author->setMiddleName($authors[$i]['middleName']);
				$author->setLastName($authors[$i]['lastName']);
				if (array_key_exists('affiliation', $authors[$i])) {
					$author->setAffiliation($authors[$i]['affiliation'], null);
				}
				$author->setCountry($authors[$i]['country']);
				$author->setEmail($authors[$i]['email']);
				$author->setUrl($authors[$i]['url']);
				if (array_key_exists('competingInterests', $authors[$i])) {
					$author->setCompetingInterests($authors[$i]['competingInterests'], null);
				}
				$author->setBiography($authors[$i]['biography'], null);
				$author->setPrimaryContact($this->getData('primaryContact') == $i ? 1 : 0);
				$author->setSequence($authors[$i]['seq']);

				if ($isExistingAuthor == false) {
					$authorDao = DAORegistry::getDAO('AuthorDAO'); /* @var $authorDao AuthorDAO */
					$authorDao->insertObject($author);
				}
			}
		}

		// Add the submission files as galleys
		import('lib.pkp.classes.file.TemporaryFileManager');
		import('classes.file.ArticleFileManager');
		$tempFileIds = $this->getData('tempFileId');
		$temporaryFileManager = new TemporaryFileManager();
		$articleFileManager = new ArticleFileManager($articleId);
		$designatedPrimary = false;
		foreach (array_keys($tempFileIds) as $locale) {
			$temporaryFile = $temporaryFileManager->getFile($tempFileIds[$locale], $user->getId());
			$fileId = null;
			if ($temporaryFile) {
				$fileId = $articleFileManager->temporaryFileToArticleFile($temporaryFile, SUBMISSION_FILE_SUBMISSION);
				$fileType = $temporaryFile->getFileType();

				if (strstr($fileType, 'html')) {
					import('classes.article.ArticleHTMLGalley');
					$galley = new ArticleHTMLGalley();
				} else {
					import('classes.article.ArticleGalley');
					$galley = new ArticleGalley();
				}
				$galley->setArticleId($articleId);
				$galley->setFileId($fileId);
				$galley->setLocale($locale);

				if ($galley->isHTMLGalley()) {
					$galley->setLabel('HTML');
				} else {
					if (strstr($fileType, 'pdf')) {
						$galley->setLabel('PDF');
					} else if (strstr($fileType, 'postscript')) {
						$galley->setLabel('Postscript');
					} else if (strstr($fileType, 'xml')) {
						$galley->setLabel('XML');
					} else {
						$galley->setLabel(__('common.untitled'));
					}
				}

				$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
				$galleyDao->insertObject($galley);

				if (!$designatedPrimary) {
					$article->setSubmissionFileId($fileId);
					if ($locale == $journal->getPrimaryLocale()) {
						// Used to make sure that *some* file
						// is designated Review Version, but
						// preferrably the primary locale.
						$designatedPrimary = true;
					}
				}
			}

			// Update file search index
			import('classes.search.ArticleSearchIndex');
			$articleSearchIndex = new ArticleSearchIndex();
			if (isset($galley)) {
				$articleSearchIndex->articleFileChanged(
					$galley->getArticleId(), SUBMISSION_SEARCH_GALLEY_FILE, $galley->getFileId()
				);
			}
			$articleSearchIndex->articleChangesFinished();
		}


		// Designate this as the review version by default.
		$authorSubmissionDao = DAORegistry::getDAO('AuthorSubmissionDAO');
		$authorSubmission =& $authorSubmissionDao->getAuthorSubmission($articleId);
		import('classes.submission.author.AuthorAction');
		AuthorAction::designateReviewVersion($authorSubmission, true);

		// Accept the submission
		$sectionEditorSubmission =& $sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		$articleFileManager = new ArticleFileManager($articleId);
		import('classes.submission.sectionEditor.SectionEditorAction');

		assert(false); // FIXME: $decisionLabels missing from call below.
		SectionEditorAction::recordDecision($this->request, $sectionEditorSubmission, SUBMISSION_EDITOR_DECISION_ACCEPT);

		import('classes.author.form.submit.AuthorSubmitForm');
		AuthorSubmitForm::assignEditors($article);

		$articleDao->updateObject($article);

		// Add to end of editing queue
		import('classes.submission.editor.EditorAction');
		if (isset($galley)) EditorAction::expediteSubmission($article, $this->request);

		if ($this->getData('destination') == "issue") {
			// Add to an existing issue
			$issueId = $this->getData('issueId');
			$this->scheduleForPublication($articleId, $issueId);
		}

		// Import the references list.
		$citationDao = DAORegistry::getDAO('CitationDAO');
		$rawCitationList = $article->getCitations();
		$citationDao->importCitations($request, ASSOC_TYPE_ARTICLE, $articleId, $rawCitationList);

		// Index article.
		import('classes.search.ArticleSearchIndex');
		$articleSearchIndex = new ArticleSearchIndex();
		$articleSearchIndex->articleMetadataChanged($article);
		$articleSearchIndex->articleChangesFinished();
	}

	/**
	 * Schedule an article for publication in a given issue
	 */
	function scheduleForPublication($articleId, $issueId) {
		$sectionEditorSubmissionDao = DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$issueDao = DAORegistry::getDAO('IssueDAO');

		$request = $this->request;
		$journal = $request->getJournal();
		$submission = $sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);
		$publishedArticle = $publishedArticleDao->getPublishedArticleByArticleId($articleId);
		$issue = $issueDao->getById($issueId, $journal->getId());

		if ($issue) {
			// Schedule against an issue.
			if ($publishedArticle) {
				$publishedArticle->setIssueId($issueId);
				$publishedArticleDao->updatePublishedArticle($publishedArticle);
			} else {
				$publishedArticle = $publishedArticleDao->newDataObject();
				$publishedArticle->setId($submission->getId());
				$publishedArticle->setIssueId($issueId);
				$publishedArticle->setDatePublished($this->getData('datePublished'));
				$publishedArticle->setSeq(REALLY_BIG_NUMBER);
				$publishedArticle->setAccessStatus(ARTICLE_ACCESS_ISSUE_DEFAULT);

				$publishedArticleDao->insertPublishedArticle($publishedArticle);

				// Resequence the articles.
				$publishedArticleDao->resequencePublishedArticles($submission->getSectionId(), $issueId);

				// If we're using custom section ordering, and if this is the first
				// article published in a section, make sure we enter a custom ordering
				// for it. (Default at the end of the list.)
				if ($sectionDao->customSectionOrderingExists($issueId)) {
					if ($sectionDao->getCustomSectionOrder($issueId, $submission->getSectionId()) === null) {
						$sectionDao->insertCustomSectionOrder($issueId, $submission->getSectionId(), REALLY_BIG_NUMBER);
						$sectionDao->resequenceCustomSectionOrders($issueId);
					}
				}
			}
		} else {
			if ($publishedArticle) {
				// This was published elsewhere; make sure we don't
				// mess up sequencing information.
				$publishedArticleDao->resequencePublishedArticles($submission->getSectionId(), $publishedArticle->getIssueId());
				$publishedArticleDao->deletePublishedArticleByArticleId($articleId);
			}
		}
		$submission->stampStatusModified();

		if ($issue && $issue->getPublished()) {
			$submission->setStatus(STATUS_PUBLISHED);
		} else {
			$submission->setStatus(STATUS_QUEUED);
		}

		$sectionEditorSubmissionDao->updateSectionEditorSubmission($submission);
	}
}

?>
