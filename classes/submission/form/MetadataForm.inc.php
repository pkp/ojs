<?php

/**
 * @file classes/submission/form/MetadataForm.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MetadataForm
 * @ingroup submission_form
 *
 * @brief Form to change metadata information for a submission.
 */


import('lib.pkp.classes.form.Form');

define('COVER_PAGE_IMAGE_NAME', 'coverPage');

class MetadataForm extends Form {
	/** @var Article current article */
	var $article;

	/** @var boolean can edit metadata */
	var $canEdit;

	/** @var boolean can view authors */
	var $canViewAuthors;

	/** @var boolean is an Editor, can edit all metadata */
	var $isEditor;

	/**
	 * Constructor.
	 */
	function MetadataForm($article, $journal) {
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');

		$user =& Request::getUser();
		$roleId = $roleDao->getRoleIdFromPath(Request::getRequestedPage());

		// If the user is an editor of this article, make the entire form editable.
		$this->canEdit = false;
		$this->isEditor = false;
		if ($roleId != null && ($roleId == ROLE_ID_EDITOR || $roleId == ROLE_ID_SECTION_EDITOR)) {
			$this->canEdit = true;
			$this->isEditor = true;
		}

		$copyeditInitialSignoff = $signoffDao->getBySymbolic('SIGNOFF_COPYEDITING_INITIAL', ASSOC_TYPE_ARTICLE, $article->getId());
		// If the user is an author and the article hasn't passed the Copyediting stage, make the form editable.
		if ($roleId == ROLE_ID_AUTHOR) {
			if ($article->getStatus() != STATUS_PUBLISHED && ($copyeditInitialSignoff == null || $copyeditInitialSignoff->getDateCompleted() == null)) {
				$this->canEdit = true;
			}
		}

		// Copy editors are also allowed to edit metadata, but only if they have
		// a current assignment to the article.
		if ($roleId != null && ($roleId == ROLE_ID_COPYEDITOR)) {
			$copyeditFinalSignoff = $signoffDao->build('SIGNOFF_COPYEDITING_FINAL', ASSOC_TYPE_ARTICLE, $article->getId());
			if ($copyeditFinalSignoff != null && $article->getStatus() != STATUS_PUBLISHED) {
				if ($copyeditInitialSignoff->getDateNotified() != null && $copyeditFinalSignoff->getDateCompleted() == null) {
					$this->canEdit = true;
				}
			}
		}

		if ($this->canEdit) {
			$supportedSubmissionLocales = $journal->getSetting('supportedSubmissionLocales');
			if (empty($supportedSubmissionLocales)) $supportedSubmissionLocales = array($journal->getPrimaryLocale());

			parent::Form(
				'submission/metadata/metadataEdit.tpl',
				true,
				$article->getLocale(),
				array_flip(array_intersect(
					array_flip(AppLocale::getAllLocales()),
					$supportedSubmissionLocales
				))
			);
			$this->addCheck(new FormValidatorLocale($this, 'title', 'required', 'author.submit.form.titleRequired', $this->getRequiredLocale()));
			$this->addCheck(new FormValidatorArray($this, 'authors', 'required', 'author.submit.form.authorRequiredFields', array('firstName', 'lastName')));
			$this->addCheck(new FormValidatorArrayCustom($this, 'authors', 'required', 'author.submit.form.authorRequiredFields', create_function('$email, $regExp', 'return String::regexp_match($regExp, $email);'), array(ValidatorEmail::getRegexp()), false, array('email')));
			$this->addCheck(new FormValidatorArrayCustom($this, 'authors', 'required', 'user.profile.form.urlInvalid', create_function('$url, $regExp', 'return empty($url) ? true : String::regexp_match($regExp, $url);'), array(ValidatorUrl::getRegexp()), false, array('url')));

			// Add ORCiD validation
			import('lib.pkp.classes.validation.ValidatorORCID');
			$this->addCheck(new FormValidatorArrayCustom($this, 'authors', 'required', 'user.profile.form.orcidInvalid', create_function('$orcid', '$validator = new ValidatorORCID(); return empty($orcid) ? true : $validator->isValid($orcid);'), array(), false, array('orcid')));

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
	 * Get the default form locale.
	 * @return string
	 */
	function getDefaultFormLocale() {
		if ($this->article) return $this->article->getLocale();
		return parent::getDefaultFormLocale();
	}

	/**
	 * Initialize form data from current article.
	 */
	function initData() {
		if (isset($this->article)) {
			$article =& $this->article;
			$this->_data = array(
				'authors' => array(),
				'title' => $article->getTitle(null), // Localized
				'abstract' => $article->getAbstract(null), // Localized
				'coverPageAltText' => $article->getCoverPageAltText(null), // Localized
				'showCoverPage' => $article->getShowCoverPage(null), // Localized
				'hideCoverPageToc' => $article->getHideCoverPageToc(null), // Localized
				'hideCoverPageAbstract' => $article->getHideCoverPageAbstract(null), // Localized
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
				'citations' => $article->getCitations(),
				'hideAuthor' => $article->getHideAuthor()
			);
			// consider the additional field names from the public identifer plugins
			import('classes.plugins.PubIdPluginHelper');
			$pubIdPluginHelper = new PubIdPluginHelper();
			$pubIdPluginHelper->init($this, $article);

			$authors =& $article->getAuthors();
			for ($i=0, $count=count($authors); $i < $count; $i++) {
				array_push(
					$this->_data['authors'],
					array(
						'authorId' => $authors[$i]->getId(),
						'firstName' => $authors[$i]->getFirstName(),
						'middleName' => $authors[$i]->getMiddleName(),
						'lastName' => $authors[$i]->getLastName(),
						'affiliation' => $authors[$i]->getAffiliation(null), // Localized
						'country' => $authors[$i]->getCountry(),
						'countryLocalized' => $authors[$i]->getCountryLocalized(),
						'email' => $authors[$i]->getEmail(),
						'orcid' => $authors[$i]->getData('orcid'),
						'url' => $authors[$i]->getUrl(),
						'competingInterests' => $authors[$i]->getCompetingInterests(null), // Localized
						'biography' => $authors[$i]->getBiography(null) // Localized
					)
				);
				if ($authors[$i]->getPrimaryContact()) {
					$this->setData('primaryContact', $i);
				}
			}
			if ($this->isEditor) {
				$this->setData('copyrightHolder', $article->getCopyrightHolder(null));
				$this->setData('copyrightYear', $article->getCopyrightYear());
				$this->setData('licenseURL', $article->getLicenseURL());
			}
		}
		return parent::initData();
	}

	/**
	 * Get the field names for which data can be localized
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array(
			'title', 'abstract', 'coverPageAltText', 'showCoverPage', 'hideCoverPageToc', 'hideCoverPageAbstract', 'originalFileName', 'fileName', 'width', 'height',
			'discipline', 'subjectClass', 'subject', 'coverageGeo', 'coverageChron', 'coverageSample', 'type', 'sponsor', 'citations',
			'copyrightHolder'
		);
	}

	/**
	 * Display the form.
	 */
	function display() {
		$journal =& Request::getJournal();
		$settingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$sectionDao =& DAORegistry::getDAO('SectionDAO');

		AppLocale::requireComponents(LOCALE_COMPONENT_OJS_EDITOR); // editor.cover.xxx locale keys; FIXME?

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('articleId', isset($this->article)?$this->article->getId():null);
		$templateMgr->assign('journalSettings', $settingsDao->getJournalSettings($journal->getId()));
		$templateMgr->assign('rolePath', Request::getRequestedPage());
		$templateMgr->assign('canViewAuthors', $this->canViewAuthors);

		$countryDao =& DAORegistry::getDAO('CountryDAO');
		$templateMgr->assign('countries', $countryDao->getCountries());

		$templateMgr->assign('helpTopicId','submission.indexingAndMetadata');
		if ($this->article) {
			$templateMgr->assign_by_ref('section', $sectionDao->getSection($this->article->getSectionId()));
		}

		if ($this->isEditor) {
			import('classes.article.Article');
			$hideAuthorOptions = array(
				AUTHOR_TOC_DEFAULT => AppLocale::Translate('editor.article.hideTocAuthorDefault'),
				AUTHOR_TOC_HIDE => AppLocale::Translate('editor.article.hideTocAuthorHide'),
				AUTHOR_TOC_SHOW => AppLocale::Translate('editor.article.hideTocAuthorShow')
			);
			$templateMgr->assign('hideAuthorOptions', $hideAuthorOptions);
			$templateMgr->assign('isEditor', true);
		}
		// consider public identifiers
		$pubIdPlugins =& PluginRegistry::loadCategory('pubIds', true);
		$templateMgr->assign('pubIdPlugins', $pubIdPlugins);
		$templateMgr->assign_by_ref('article', $this->article);

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
				'hideCoverPageToc',
				'hideCoverPageAbstract',
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
				'citations',
				'hideAuthor'
			)
		);
		if ($this->isEditor) {
			$this->readUserVars(array('copyrightHolder', 'copyrightYear', 'licenseURL'));
		}
		// consider the additional field names from the public identifer plugins
		import('classes.plugins.PubIdPluginHelper');
		$pubIdPluginHelper = new PubIdPluginHelper();
		$pubIdPluginHelper->readInputData($this);

		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$section =& $sectionDao->getSection($this->article->getSectionId());
		if (!$section->getAbstractsNotRequired()) {
			$this->addCheck(new FormValidatorLocale($this, 'abstract', 'required', 'author.submit.form.abstractRequired', $this->getRequiredLocale()));
		}

	}

	/**
	 * Check to ensure that the form is correctly validated.
	 */
	function validate() {
		// Verify that an image cover, if supplied, is actually an image.
		import('classes.file.PublicFileManager');
		$publicFileManager = new PublicFileManager();
		if ($publicFileManager->uploadedFileExists(COVER_PAGE_IMAGE_NAME)) {
			$type = $publicFileManager->getUploadedFileType(COVER_PAGE_IMAGE_NAME);
			$extension = $publicFileManager->getImageExtension($type);
			if (!$extension) {
				// Not a valid image.
				$this->addError('imageFile', __('submission.layout.imageInvalid'));
				return false;
			}
		}

		// Verify additional fields from public identifer plug-ins.
		$journal = Request::getJournal();
		import('classes.plugins.PubIdPluginHelper');
		$pubIdPluginHelper = new PubIdPluginHelper();
		$pubIdPluginHelper->validate($journal->getId(), $this, $this->article);

		// Fall back on parent validation
		return parent::validate();
	}


	/**
	 * Save changes to article.
	 * @param $request PKPRequest
	 * @return int the article ID
	 */
	function execute(&$request) {
		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$authorDao =& DAORegistry::getDAO('AuthorDAO');
		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$citationDao =& DAORegistry::getDAO('CitationDAO'); /* @var $citationDao CitationDAO */
		$article =& $this->article;

		// Retrieve the previous citation list for comparison.
		$previousRawCitationList = $article->getCitations();

		// Update article
		$article->setTitle($this->getData('title'), null); // Localized

		$section =& $sectionDao->getSection($article->getSectionId());
		$article->setAbstract($this->getData('abstract'), null); // Localized

		import('classes.file.PublicFileManager');
		$publicFileManager = new PublicFileManager();
		if ($publicFileManager->uploadedFileExists(COVER_PAGE_IMAGE_NAME)) {
			$journal = Request::getJournal();
			$originalFileName = $publicFileManager->getUploadedFileName(COVER_PAGE_IMAGE_NAME);
			$type = $publicFileManager->getUploadedFileType(COVER_PAGE_IMAGE_NAME);
			$newFileName = 'cover_article_' . $this->article->getId() . '_' . $this->getFormLocale() . $publicFileManager->getImageExtension($type);
			$publicFileManager->uploadJournalFile($journal->getId(), COVER_PAGE_IMAGE_NAME, $newFileName);
			$article->setOriginalFileName($publicFileManager->truncateFileName($originalFileName, 127), $this->getFormLocale());
			$article->setFileName($newFileName, $this->getFormLocale());

			// Store the image dimensions.
			list($width, $height) = getimagesize($publicFileManager->getJournalFilesPath($journal->getId()) . '/' . $newFileName);
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

		$hideCoverPageToc = array_map(create_function('$arrayElement', 'return (int)$arrayElement;'), (array) $this->getData('hideCoverPageToc'));
		foreach (array_keys($this->getData('coverPageAltText')) as $locale) {
			if (!array_key_exists($locale, $hideCoverPageToc)) {
				$hideCoverPageToc[$locale] = 0;
			}
		}
		$article->setHideCoverPageToc($hideCoverPageToc, null); // Localized

		$hideCoverPageAbstract = array_map(create_function('$arrayElement', 'return (int)$arrayElement;'), (array) $this->getData('hideCoverPageAbstract'));
		foreach (array_keys($this->getData('coverPageAltText')) as $locale) {
			if (!array_key_exists($locale, $hideCoverPageAbstract)) {
				$hideCoverPageAbstract[$locale] = 0;
			}
		}
		$article->setHideCoverPageAbstract($hideCoverPageAbstract, null); // Localized

		$article->setDiscipline($this->getData('discipline'), null); // Localized
		$article->setSubjectClass($this->getData('subjectClass'), null); // Localized
		$article->setSubject($this->getData('subject'), null); // Localized
		$article->setCoverageGeo($this->getData('coverageGeo'), null); // Localized
		$article->setCoverageChron($this->getData('coverageChron'), null); // Localized
		$article->setCoverageSample($this->getData('coverageSample'), null); // Localized
		$article->setType($this->getData('type'), null); // Localized
		$article->setLanguage($this->getData('language')); // Localized
		$article->setSponsor($this->getData('sponsor'), null); // Localized
		$article->setCitations($this->getData('citations'));
		if ($this->isEditor) {
			$article->setHideAuthor($this->getData('hideAuthor') ? $this->getData('hideAuthor') : 0);
		}
		// consider the additional field names from the public identifer plugins
		import('classes.plugins.PubIdPluginHelper');
		$pubIdPluginHelper = new PubIdPluginHelper();
		$pubIdPluginHelper->execute($this, $article);

		// Update authors
		$authors = $this->getData('authors');
		for ($i=0, $count=count($authors); $i < $count; $i++) {
			if ($authors[$i]['authorId'] > 0) {
				// Update an existing author
				$author =& $authorDao->getAuthor($authors[$i]['authorId'], $article->getId());
				$isExistingAuthor = true;

			} else {
				// Create a new author
				if (checkPhpVersion('5.0.0')) { // *5488* PHP4 Requires explicit instantiation-by-reference
					$author = new Author();
				} else {
					$author =& new Author();
				}
				$isExistingAuthor = false;
			}

			if ($author != null) {
				$author->setSubmissionId($article->getId());
				$author->setFirstName($authors[$i]['firstName']);
				$author->setMiddleName($authors[$i]['middleName']);
				$author->setLastName($authors[$i]['lastName']);
				$author->setAffiliation($authors[$i]['affiliation'], null); // Localized
				$author->setCountry($authors[$i]['country']);
				$author->setEmail($authors[$i]['email']);
				$author->setData('orcid', $authors[$i]['orcid']);
				$author->setUrl($authors[$i]['url']);
				if (array_key_exists('competingInterests', $authors[$i])) {
					$author->setCompetingInterests($authors[$i]['competingInterests'], null); // Localized
				}
				$author->setBiography($authors[$i]['biography'], null); // Localized
				$author->setPrimaryContact($this->getData('primaryContact') == $i ? 1 : 0);
				$author->setSequence($authors[$i]['seq']);

				HookRegistry::call('Submission::Form::MetadataForm::Execute', array(&$author, &$authors[$i]));

				if ($isExistingAuthor) {
					$authorDao->updateAuthor($author);
				} else {
					$authorDao->insertAuthor($author);
				}
				unset($author);
			}
		}

		// Remove deleted authors
		$deletedAuthors = preg_split('/:/', $this->getData('deletedAuthors'), -1, PREG_SPLIT_NO_EMPTY);
		for ($i=0, $count=count($deletedAuthors); $i < $count; $i++) {
			$authorDao->deleteAuthorById($deletedAuthors[$i], $article->getId());
		}

		if ($this->isEditor) {
			$article->setCopyrightHolder($this->getData('copyrightHolder'), null);
			$article->setCopyrightYear($this->getData('copyrightYear'));
			$article->setLicenseURL($this->getData('licenseURL'));
		}

		parent::execute();

		// Save the article
		$articleDao->updateArticle($article);

		// Update search index
		import('classes.search.ArticleSearchIndex');
		$articleSearchIndex = new ArticleSearchIndex();
		$articleSearchIndex->articleMetadataChanged($article);
		$articleSearchIndex->articleChangesFinished();

		// Update references list if it changed.
		$rawCitationList = $article->getCitations();
		if ($previousRawCitationList != $rawCitationList) {
			$citationDao->importCitations($request, ASSOC_TYPE_ARTICLE, $article->getId(), $rawCitationList);
		}

		return $article->getId();
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
