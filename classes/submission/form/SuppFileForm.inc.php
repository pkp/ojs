<?php

/**
 * @file classes/submission/form/SuppFileForm.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SuppFileForm
 * @ingroup submission_form
 *
 * @brief Supplementary file form.
 */

import('lib.pkp.classes.form.Form');

class SuppFileForm extends Form {
	/** @var int the ID of the supplementary file */
	var $suppFileId;

	/** @var Article current article */
	var $article;

	/** @var SuppFile current file */
	var $suppFile;

	/**
	 * Constructor.
	 * @param $article object
	 * @param $suppFileId int (optional)
	 */
	function SuppFileForm($article, $journal, $suppFileId = null) {
		$supportedSubmissionLocales = $journal->getSetting('supportedSubmissionLocales');
		if (empty($supportedSubmissionLocales)) $supportedSubmissionLocales = array($journal->getPrimaryLocale());

		parent::Form(
			'submission/suppFile/suppFile.tpl',
			true,
			$article->getLocale(),
			array_flip(array_intersect(
				array_flip(AppLocale::getAllLocales()),
				$supportedSubmissionLocales
			))
		);

		$this->article = $article;

		if (isset($suppFileId) && !empty($suppFileId)) {
			$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
			$this->suppFile =& $suppFileDao->getSuppFile($suppFileId, $article->getId());
			if (isset($this->suppFile)) {
				$this->suppFileId = $suppFileId;
			}
		}

		// Validation checks for this form
		$this->addCheck(new FormValidatorLocale($this, 'title', 'required', 'author.submit.suppFile.form.titleRequired'));
		$this->addCheck(new FormValidatorURL($this, 'remoteURL', 'optional', 'submission.layout.galleyRemoteURLValid'));
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
	 * Get the names of fields for which data should be localized
	 * @return array
	 */
	function getLocaleFieldNames() {
		$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
		return $suppFileDao->getLocaleFieldNames();
	}

	/**
	 * Display the form.
	 */
	function display() {
		$journal =& Request::getJournal();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('enablePublicSuppFileId', $journal->getSetting('enablePublicSuppFileId'));
		$templateMgr->assign('rolePath', Request::getRequestedPage());
		$templateMgr->assign('articleId', $this->article->getId());
		$templateMgr->assign('suppFileId', $this->suppFileId);

		$typeOptionsOutput = array(
			'author.submit.suppFile.researchInstrument',
			'author.submit.suppFile.researchMaterials',
			'author.submit.suppFile.researchResults',
			'author.submit.suppFile.transcripts',
			'author.submit.suppFile.dataAnalysis',
			'author.submit.suppFile.dataSet',
			'author.submit.suppFile.sourceText'
		);
		$typeOptionsValues = $typeOptionsOutput;
		array_push($typeOptionsOutput, 'common.other');
		array_push($typeOptionsValues, '');

		$templateMgr->assign('typeOptionsOutput', $typeOptionsOutput);
		$templateMgr->assign('typeOptionsValues', $typeOptionsValues);

		// Sometimes it's necessary to track the page we came from in
		// order to redirect back to the right place
		$templateMgr->assign('from', Request::getUserVar('from'));

		if (isset($this->article)) {
			$templateMgr->assign('submissionProgress', $this->article->getSubmissionProgress());
		}

		if (isset($this->suppFile)) {
			$templateMgr->assign_by_ref('suppFile', $this->suppFile);
		}
		$templateMgr->assign('helpTopicId','submission.supplementaryFiles');
		// consider public identifiers
		$pubIdPlugins =& PluginRegistry::loadCategory('pubIds', true);
		$templateMgr->assign('pubIdPlugins', $pubIdPlugins);

		AppLocale::requireComponents(LOCALE_COMPONENT_OJS_AUTHOR);

		parent::display();
	}

	/**
	 * Validate the form
	 */
	function validate() {
		$journal =& Request::getJournal();
		$journalDao =& DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */

		$publicSuppFileId = $this->getData('publicSuppFileId');
		if ($publicSuppFileId && $journalDao->anyPubIdExists($journal->getId(), 'publisher-id', $publicSuppFileId, ASSOC_TYPE_SUPP_FILE, $this->suppFileId)) {
			$this->addError('publicSuppFileId', __('editor.publicIdentificationExists', array('publicIdentifier' => $publicSuppFileId)));
			$this->addErrorField('publicSuppFileId');
		}

		// Verify additional fields from public identifer plug-ins.
		import('classes.plugins.PubIdPluginHelper');
		$pubIdPluginHelper = new PubIdPluginHelper();
		$pubIdPluginHelper->validate($journal->getId(), $this, $this->suppFile);

		return parent::validate();
	}

	/**
	 * Initialize form data from current supplementary file (if applicable).
	 */
	function initData() {
		if (isset($this->suppFile)) {
			$suppFile =& $this->suppFile;
			$this->_data = array(
				'title' => $suppFile->getTitle(null), // Localized
				'creator' => $suppFile->getCreator(null), // Localized
				'subject' => $suppFile->getSubject(null), // Localized
				'type' => $suppFile->getType(),
				'typeOther' => $suppFile->getTypeOther(null), // Localized
				'description' => $suppFile->getDescription(null), // Localized
				'publisher' => $suppFile->getPublisher(null), // Localized
				'sponsor' => $suppFile->getSponsor(null), // Localized
				'dateCreated' => $suppFile->getDateCreated(),
				'source' => $suppFile->getSource(null), // Localized
				'language' => $suppFile->getLanguage(),
				'showReviewers' => $suppFile->getShowReviewers()==1?1:0,
				'publicSuppFileId' => $suppFile->getPubId('publisher-id')
			);

		} else {
			$this->_data = array(
				'type' => '',
				'showReviewers' => 1
			);
		}
		// consider the additional field names from the public identifer plugins
		import('classes.plugins.PubIdPluginHelper');
		$pubIdPluginHelper = new PubIdPluginHelper();
		$pubIdPluginHelper->init($this, $suppFile);

		return parent::initData();
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(
			array(
				'title',
				'creator',
				'subject',
				'type',
				'typeOther',
				'description',
				'publisher',
				'sponsor',
				'dateCreated',
				'source',
				'language',
				'showReviewers',
				'publicSuppFileId',
				'remoteURL'
			)
		);
		// consider the additional field names from the public identifer plugins
		import('classes.plugins.PubIdPluginHelper');
		$pubIdPluginHelper = new PubIdPluginHelper();
		$pubIdPluginHelper->readInputData($this);

	}

	/**
	 * Save changes to the supplementary file.
	 * @return int the supplementary file ID
	 */
	function execute($fileName = null, $createRemote = false) {
		import('classes.file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($this->article->getId());
		$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');

		$fileName = isset($fileName) ? $fileName : 'uploadSuppFile';

		import('classes.search.ArticleSearchIndex');
		$articleSearchIndex = new ArticleSearchIndex();
		if (isset($this->suppFile)) {
			parent::execute();

			// Upload file, if file selected.
			if ($articleFileManager->uploadedFileExists($fileName)) {
				$fileId = $this->suppFile->getFileId();
				if ($fileId != 0) {
					$articleFileManager->uploadSuppFile($fileName, $fileId);
				} else {
					$fileId = $articleFileManager->uploadSuppFile($fileName);
					$this->suppFile->setFileId($fileId);
				}
				$articleSearchIndex->articleFileChanged($this->article->getId(), ARTICLE_SEARCH_SUPPLEMENTARY_FILE, $fileId);
			}

			// Update existing supplementary file
			$this->setSuppFileData($this->suppFile);
			if ($this->getData('remoteURL')) {
				$this->suppFile->setRemoteURL($this->getData('remoteURL'));
			}
			$suppFileDao->updateSuppFile($this->suppFile);

		} else {
			// Upload file, if file selected.
			if ($articleFileManager->uploadedFileExists($fileName)) {
				$fileId = $articleFileManager->uploadSuppFile($fileName);
				$articleSearchIndex->articleFileChanged($this->article->getId(), ARTICLE_SEARCH_SUPPLEMENTARY_FILE, $fileId);
			} else {
				$fileId = 0;
			}

			// Insert new supplementary file
			$this->suppFile = new SuppFile();
			$this->suppFile->setArticleId($this->article->getId());
			$this->suppFile->setFileId($fileId);

			if ($createRemote) {
				$this->suppFile->setRemoteURL(__('common.remoteURL'));
			}
			parent::execute();

			$this->setSuppFileData($this->suppFile);
			$suppFileDao->insertSuppFile($this->suppFile);
			$this->suppFileId = $this->suppFile->getId();
		}

		// Index updated metadata.
		$articleSearchIndex->suppFileMetadataChanged($this->suppFile);
		$articleSearchIndex->articleChangesFinished();

		// Stamp the article modification (for OAI)
		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$articleDao->updateArticle($this->article);

		return $this->suppFileId;
	}

	/**
	 * Assign form data to a SuppFile.
	 * @param $suppFile SuppFile
	 */
	function setSuppFileData(&$suppFile) {
		$suppFile->setTitle($this->getData('title'), null); // Localized
		$suppFile->setCreator($this->getData('creator'), null); // Localized
		$suppFile->setSubject($this->getData('subject'), null); // Localized
		$suppFile->setType($this->getData('type'));
		$suppFile->setTypeOther($this->getData('typeOther'), null); // Localized
		$suppFile->setDescription($this->getData('description'), null); // Localized
		$suppFile->setPublisher($this->getData('publisher'), null); // Localized
		$suppFile->setSponsor($this->getData('sponsor'), null); // Localized
		$suppFile->setDateCreated($this->getData('dateCreated') == '' ? Core::getCurrentDate() : $this->getData('dateCreated'));
		$suppFile->setSource($this->getData('source'), null); // Localized
		$suppFile->setLanguage($this->getData('language'));
		$suppFile->setShowReviewers($this->getData('showReviewers')==1?1:0);
		$suppFile->setStoredPubId('publisher-id', $this->getData('publicSuppFileId'));
		// consider the additional field names from the public identifer plugins
		import('classes.plugins.PubIdPluginHelper');
		$pubIdPluginHelper = new PubIdPluginHelper();
		$pubIdPluginHelper->execute($this, $suppFile);

	}
}

?>
