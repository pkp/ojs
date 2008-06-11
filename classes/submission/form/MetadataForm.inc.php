<?php

/**
 * @file MetadataForm.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission.form
 * @class MetadataForm
 *
 * Form to change metadata information for a submission.
 *
 * $Id$
 */

import('form.Form');

class MetadataForm extends Form {
	/** @var Article current article */
	var $article;

	/** @var boolean can edit metadata */
	var $canEdit;

	/** @var boolean can view authors */
	var $canViewAuthors;

	/**
	 * Constructor.
	 */
	function MetadataForm($article) {
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$copyAssignmentDao = &DAORegistry::getDAO('CopyAssignmentDAO');

		$user = &Request::getUser();
		$roleId = $roleDao->getRoleIdFromPath(Request::getRequestedPage());

		// If the user is an editor of this article, make the form editable.
		$this->canEdit = false;
		if ($roleId != null && ($roleId == ROLE_ID_EDITOR || $roleId == ROLE_ID_SECTION_EDITOR)) {
			$this->canEdit = true;
		}

		// If the user is an author and the article hasn't passed the Copyediting stage, make the form editable.
		if ($roleId == ROLE_ID_AUTHOR) {
			$copyAssignment = $copyAssignmentDao->getCopyAssignmentByArticleId($article->getArticleId());
			if ($article->getStatus() != STATUS_PUBLISHED && ($copyAssignment == null || $copyAssignment->getDateCompleted() == null)) {
				$this->canEdit = true;
			}
		}

		// Copy editors are also allowed to edit metadata, but only if they have
		// a current assignment to the article.
		if ($roleId != null && ($roleId == ROLE_ID_COPYEDITOR)) {
			$copyAssignment = $copyAssignmentDao->getCopyAssignmentByArticleId($article->getArticleId());
			if ($copyAssignment != null && $article->getStatus() != STATUS_PUBLISHED) {
				if ($copyAssignment->getDateNotified() != null && $copyAssignment->getDateFinalCompleted() == null) {
					$this->canEdit = true;
				}
			}
		}

		if ($this->canEdit) {
			parent::Form('submission/metadata/metadataEdit.tpl');
			$this->addCheck(new FormValidatorLocale($this, 'title', 'required', 'author.submit.form.titleRequired'));
		} else {
			parent::Form('submission/metadata/metadataView.tpl');
		}

		// If the user is a reviewer of this article, do not show authors.
		$this->canViewAuthors = true;
		if ($roleId != null && $roleId == ROLE_ID_REVIEWER) {
			$this->canViewAuthors = false;
		}

		$this->article = $article;

		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Initialize form data from current article.
	 */
	function initData() {
		if (isset($this->article)) {
			$article = &$this->article;
			$this->_data = array(
				'authors' => array(),
				'title' => $article->getTitle(null), // Localized
				'abstract' => $article->getAbstract(null), // Localized
				'coverPageAltText' => $article->getCoverPageAltText(null), // Localized
				'showCoverPage' => $article->getShowCoverPage(null), // Localized
				'originalFileName' => $article->getOriginalFileName(null), // Localized
				'fileName' => $article->getFileName(null), // Localized
				'width' => $article->getWidth(null), // Localized
				'height' => $article->getHeight(null), // Localized
				'discipline' => $article->getDiscipline(null), // Localized
				'subjectClass' => $article->getSubjectClass(null), // Localized
				'subject' => $article->getSubject(null), // Localized
				'coverageGeo' => $article->getCoverageGeo(null), // Localized
				'coverageChron' => $article->getCoverageChron(null), // Localized
				'coverageSample' => $article->getCoverageSample(null), // Localized
				'type' => $article->getType(null), // Localized
				'language' => $article->getLanguage(),
				'sponsor' => $article->getSponsor(null), // Localized
				'hideAuthor' => $article->getHideAuthor()
			);

			$authors = &$article->getAuthors();
			for ($i=0, $count=count($authors); $i < $count; $i++) {
				array_push(
					$this->_data['authors'],
					array(
						'authorId' => $authors[$i]->getAuthorId(),
						'firstName' => $authors[$i]->getFirstName(),
						'middleName' => $authors[$i]->getMiddleName(),
						'lastName' => $authors[$i]->getLastName(),
						'affiliation' => $authors[$i]->getAffiliation(),
						'country' => $authors[$i]->getCountry(),
						'countryLocalized' => $authors[$i]->getCountryLocalized(),
						'email' => $authors[$i]->getEmail(),
						'url' => $authors[$i]->getUrl(),
						'competingInterests' => $authors[$i]->getCompetingInterests(null), // Localized
						'biography' => $authors[$i]->getBiography(null) // Localized
					)
				);
				if ($authors[$i]->getPrimaryContact()) {
					$this->setData('primaryContact', $i);
				}
			}
		}
	}

	/**
	 * Get the field names for which data can be localized
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array(
			'title', 'abstract', 'coverPageAltText', 'showCoverPage', 'originalFileName', 'fileName', 'width', 'height',
			'discipline', 'subjectClass', 'subject', 'coverageGeo', 'coverageChron', 'coverageSample', 'type', 'sponsor'
		);
	}

	/**
	 * Display the form.
	 */
	function display() {
		$journal = &Request::getJournal();
		$settingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$sectionDao = &DAORegistry::getDAO('SectionDAO');

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('articleId', isset($this->article)?$this->article->getArticleId():null);
		$templateMgr->assign('journalSettings', $settingsDao->getJournalSettings($journal->getJournalId()));
		$templateMgr->assign('rolePath', Request::getRequestedPage());
		$templateMgr->assign('canViewAuthors', $this->canViewAuthors);

		$countryDao =& DAORegistry::getDAO('CountryDAO');
		$templateMgr->assign('countries', $countryDao->getCountries());

		$templateMgr->assign('helpTopicId','submission.indexingAndMetadata');
		if ($this->article) {
			$templateMgr->assign_by_ref('section', $sectionDao->getSection($this->article->getSectionId()));
		}

		if ($this->canEdit) {
			import('article.Article');
			$hideAuthorOptions = array(
				AUTHOR_TOC_DEFAULT => Locale::Translate('editor.article.hideTocAuthorDefault'),
				AUTHOR_TOC_HIDE => Locale::Translate('editor.article.hideTocAuthorHide'),
				AUTHOR_TOC_SHOW => Locale::Translate('editor.article.hideTocAuthorShow')
			);
			$templateMgr->assign('hideAuthorOptions', $hideAuthorOptions);
		}

		parent::display();
	}


	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(
			array(
				'articleId',
				'authors',
				'deletedAuthors',
				'primaryContact',
				'title',
				'abstract',
				'coverPageAltText',
				'showCoverPage',
				'originalFileName',
				'fileName',
				'width',
				'height',
				'discipline',
				'subjectClass',
				'subject',
				'coverageGeo',
				'coverageChron',
				'coverageSample',
				'type',
				'language',
				'sponsor',
				'hideAuthor'
			)
		);

		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$section = &$sectionDao->getSection($this->article->getSectionId());
		if (!$section->getAbstractsDisabled()) {
			$this->addCheck(new FormValidatorLocale($this, 'abstract', 'required', 'author.submit.form.abstractRequired'));
		}

	}

	/**
	 * Save changes to article.
	 * @return int the article ID
	 */
	function execute() {
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$authorDao = &DAORegistry::getDAO('AuthorDAO');
		$sectionDao = &DAORegistry::getDAO('SectionDAO');

		// Update article

		$article = &$this->article;
		$article->setTitle($this->getData('title'), null); // Localized

		$section = &$sectionDao->getSection($article->getSectionId());
		if (!$section->getAbstractsDisabled()) {
			$article->setAbstract($this->getData('abstract'), null); // Localized
		}

		import('file.PublicFileManager');
		$publicFileManager =& new PublicFileManager();
		if ($publicFileManager->uploadedFileExists('coverPage')) {
			$journal = Request::getJournal();
			$originalFileName = $publicFileManager->getUploadedFileName('coverPage');
			$newFileName = 'cover_article_' . $this->getData('articleId') . '_' . $this->getFormLocale() . '.' . $publicFileManager->getExtension($originalFileName);
			$publicFileManager->uploadJournalFile($journal->getJournalId(), 'coverPage', $newFileName);
			$article->setOriginalFileName($publicFileManager->truncateFileName($originalFileName, 127), $this->getFormLocale());
			$article->setFileName($newFileName, $this->getFormLocale());

			// Store the image dimensions.
			list($width, $height) = getimagesize($publicFileManager->getJournalFilesPath($journal->getJournalId()) . '/' . $newFileName);
			$article->setWidth($width, $this->getFormLocale());
			$article->setHeight($height, $this->getFormLocale());
		}

		$article->setCoverPageAltText($this->getData('coverPageAltText'), null); // Localized
		$showCoverPage = array_map(create_function('$arrayElement', 'return (int)$arrayElement;'), (array) $this->getData('showCoverPage'));
		foreach (array_keys($this->getData('coverPageAltText')) as $locale) {
			if (!array_key_exists($locale, $showCoverPage)) {
				$showCoverPage[$locale] = 0;
			}
		}
		$article->setShowCoverPage($showCoverPage, null); // Localized

		$article->setDiscipline($this->getData('discipline'), null); // Localized
		$article->setSubjectClass($this->getData('subjectClass'), null); // Localized
		$article->setSubject($this->getData('subject'), null); // Localized
		$article->setCoverageGeo($this->getData('coverageGeo'), null); // Localized
		$article->setCoverageChron($this->getData('coverageChron'), null); // Localized
		$article->setCoverageSample($this->getData('coverageSample'), null); // Localized
		$article->setType($this->getData('type'), null); // Localized
		$article->setLanguage($this->getData('language')); // Localized
		$article->setSponsor($this->getData('sponsor'), null); // Localized
		$article->setHideAuthor($this->getData('hideAuthor') ? $this->getData('hideAuthor') : 0);

		// Update authors
		$authors = $this->getData('authors');
		for ($i=0, $count=count($authors); $i < $count; $i++) {
			if ($authors[$i]['authorId'] > 0) {
				// Update an existing author
				$author = &$article->getAuthor($authors[$i]['authorId']);
				$isExistingAuthor = true;

			} else {
				// Create a new author
				$author = &new Author();
				$isExistingAuthor = false;
			}

			if ($author != null) {
				$author->setFirstName($authors[$i]['firstName']);
				$author->setMiddleName($authors[$i]['middleName']);
				$author->setLastName($authors[$i]['lastName']);
				$author->setAffiliation($authors[$i]['affiliation']);
				$author->setCountry($authors[$i]['country']);
				$author->setEmail($authors[$i]['email']);
				$author->setUrl($authors[$i]['url']);
				if (array_key_exists('competingInterests', $authors[$i])) {
					$author->setCompetingInterests($authors[$i]['competingInterests'], null); // Localized
				}
				$author->setBiography($authors[$i]['biography'], null); // Localized
				$author->setPrimaryContact($this->getData('primaryContact') == $i ? 1 : 0);
				$author->setSequence($authors[$i]['seq']);

				if ($isExistingAuthor == false) {
					$article->addAuthor($author);
				}
			}
		}

		// Remove deleted authors
		$deletedAuthors = explode(':', $this->getData('deletedAuthors'));
		for ($i=0, $count=count($deletedAuthors); $i < $count; $i++) {
			$article->removeAuthor($deletedAuthors[$i]);
		}

		// Save the article
		$articleDao->updateArticle($article);

		// Update search index
		import('search.ArticleSearchIndex');
		ArticleSearchIndex::indexArticleMetadata($article);

		return $article->getArticleId();
	}

	/**
	 * Determine whether or not the current user is allowed to edit metadata.
	 * @return boolean
	 */
	function getCanEdit() {
		return $this->canEdit;
	}
}

?>
