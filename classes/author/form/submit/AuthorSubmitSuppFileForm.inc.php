<?php

/**
 * AuthorSubmitSuppFileForm.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package author.form.submit
 *
 * Supplementary file author submission form.
 *
 * $Id$
 */

import('form.Form');

class AuthorSubmitSuppFileForm extends Form {

	/** @var int the ID of the article */
	var $articleId;

	/** @var int the ID of the supplementary file */
	var $suppFileId;
	
	/** @var Article current article */
	var $article;
	
	/** @var SuppFile current file */
	var $suppFile;
	
	/**
	 * Constructor.
	 * @param $articleId int
	 * @param $suppFileId int (optional)
	 */
	function AuthorSubmitSuppFileForm($articleId, $suppFileId = null) {
		parent::Form('author/submit/suppFile.tpl');
		$this->articleId = $articleId;
		
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$this->article = &$articleDao->getArticle($articleId);
		
		if (isset($suppFileId) && !empty($suppFileId)) {
			$suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
			$this->suppFile = &$suppFileDao->getSuppFile($suppFileId, $articleId);
			if (isset($this->suppFile)) {
				$this->suppFileId = $suppFileId;
			}
		}
		
		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'title', 'required', 'author.submit.suppFile.form.titleRequired'));
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('sidebarTemplate', 'author/submit/submitSidebar.tpl');
		$templateMgr->assign('articleId', $this->articleId);
		$templateMgr->assign('suppFileId', $this->suppFileId);
		$templateMgr->assign('submitStep', 4);
		
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
		
		if (isset($this->article)) {
			$templateMgr->assign('submissionProgress', $this->article->getSubmissionProgress());
		}
		
		if (isset($this->suppFile)) {
			$templateMgr->assign('suppFile', $this->suppFile);
		}
		$templateMgr->assign('helpTopicId','submission.supplementaryFiles');		
		parent::display();
	}
	
	/**
	 * Initialize form data from current supplementary file (if applicable).
	 */
	function initData() {
		if (isset($this->suppFile)) {
			$suppFile = &$this->suppFile;
			$this->_data = array(
				'title' => $suppFile->getTitle(),
				'creator' => $suppFile->getCreator(),
				'subject' => $suppFile->getSubject(),
				'type' => $suppFile->getType(),
				'typeOther' => $suppFile->getTypeOther(),
				'description' => $suppFile->getDescription(),
				'publisher' => $suppFile->getPublisher(),
				'sponsor' => $suppFile->getSponsor(),
				'dateCreated' => $suppFile->getDateCreated(),
				'source' => $suppFile->getSource(),
				'language' => $suppFile->getLanguage(),
				'showReviewers' => $suppFile->getShowReviewers()
			);
			
		} else {
			$this->_data = array(
				'type' => ''
			);
		}
		
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
				'showReviewers'
			)
		);
	}
	
	/**
	 * Save changes to the supplementary file.
	 * @return int the supplementary file ID
	 */
	function execute() {
		import("file.ArticleFileManager");
		$articleFileManager = &new ArticleFileManager($this->articleId);
		$suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		
		$fileName = 'uploadSuppFile';
		
		// edit an existing supp file, otherwise create new supp file entry	
		if (isset($this->suppFile)) {
			$suppFile = &$this->suppFile;

			// Remove old file and upload new, if file is selected.
			if ($articleFileManager->uploadedFileExists($fileName)) {
				$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
				$suppFileId = $articleFileManager->uploadSuppFile($fileName, $suppFile->getFileId(), true);
				$suppFile->setFileId($suppFileId);
			}

			// Update existing supplementary file
			$this->setSuppFileData($suppFile);
			$suppFileDao->updateSuppFile($suppFile);
			
		} else {
			// Upload file, if file selected.
			if ($articleFileManager->uploadedFileExists($fileName)) {
				$fileId = $articleFileManager->uploadSuppFile($fileName);
			} else {
				$fileId = 0;
			}
			
			// Insert new supplementary file		
			$suppFile = &new SuppFile();
			$suppFile->setArticleId($this->articleId);
			$suppFile->setFileId($fileId);
			$this->setSuppFileData($suppFile);
			$suppFileDao->insertSuppFile($suppFile);
			$this->suppFileId = $suppFile->getSuppFileId();
		}
		
		return $this->suppFileId;
	}
	
	/**
	 * Assign form data to a SuppFile.
	 * @param $suppFile SuppFile
	 */
	function setSuppFileData(&$suppFile) {
		$suppFile->setTitle($this->getData('title'));
		$suppFile->setCreator($this->getData('creator'));
		$suppFile->setSubject($this->getData('subject'));
		$suppFile->setType($this->getData('type'));
		$suppFile->setTypeOther($this->getData('typeOther'));
		$suppFile->setDescription($this->getData('description'));
		$suppFile->setPublisher($this->getData('publisher'));
		$suppFile->setSponsor($this->getData('sponsor'));
		$suppFile->setDateCreated($this->getData('dateCreated') == '' ? Core::getCurrentDate() : $this->getData('dateCreated'));
		$suppFile->setSource($this->getData('source'));
		$suppFile->setLanguage($this->getData('language'));
		$suppFile->setShowReviewers($this->getData('showReviewers'));
	}
}

?>
