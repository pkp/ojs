<?php

/**
 * FrontMatterForm.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package issue.form
 *
 * Form to create or edit an issue front matter
 *
 * $Id$
 */

class FrontMatterForm extends Form {

	var $frontId;
	var $issueId;

	/**
	 * Constructor.
	 */
	function FrontMatterForm($frontId, $issueId) {
		parent::Form('editor/issueManagement.tpl');

		$this->frontId = $frontId;
		$this->issueId = $issueId;
		$this->addCheck(new FormValidator(&$this, 'frontSectionId', 'required', 'editor.issues.frontSectionRequired'));
		$this->addCheck(new FormValidator(&$this, 'title', 'required', 'editor.issues.titleRequired'));
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();

		$frontMatterSectionDao = &DAORegistry::getDAO('FrontMatterSectionDAO');
		$frontMatterSections = $frontMatterSectionDao->getFrontMatterSections($this->issueId);
		foreach($frontMatterSections as $frontMatterSection) {
			$frontSectionIdOptions[$frontMatterSection->getFrontSectionId()] = $frontMatterSection->getTitle();
		}

		if (isset($frontSectionIdOptions)) {
			$templateMgr->assign('frontSectionIdOptions',$frontSectionIdOptions);
		}

		$journal = Request::getJournal();
		$journalId = $journal->getJournalId();
		$templateMgr->assign('journalId', $journalId);

		parent::display();	
	}

	/**
	 * Validate the form
	 */
	function validate() {

		return parent::validate();
	}

	/**
	 * Initialize form data from current selected front matter.
	 */
	function initData() {
		$journal = Request::getJournal();
		$journalId = $journal->getJournalId();

		$frontMatterDao = &DAORegistry::getDAO('FrontMatterDAO');
		$frontMatter = $frontMatterDao->getFrontMatterById($this->frontId);

		if (isset($frontMatter)) {
			$this->_data = array(
				'frontId' => $frontMatter->getFrontId(),
				'frontSectionId' => $frontMatter->getFrontSectionId(),
				'fileName' => $frontMatter->getFileName(),
				'originalFileName' => $frontMatter->getOriginalFileName(),
				'title' => $frontMatter->getTitle(),
				'dateCreated' => $frontMatter->getDateCreated(),
				'dateModified' => $frontMatter->getDateModified(),
				'cover' => $frontMatter->getCover()
			);
		}
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array(
			'frontId',
			'issueId',
			'frontSectionId',
			'title',
			'cover'
		));
		
	}
	
	/**
	 * Save issue settings.
	 */
	function execute() {
		$frontMatterDao = &DAORegistry::getDAO('FrontMatterDAO');

		if ($this->frontId) {
			$frontMatter = $frontMatterDao->getFrontMatterById($this->frontId);
		} else {
			$frontMatter = &new FrontMatter();
			$frontMatter->setDateCreated(Core::getCurrentDate());
			$frontMatter->setFileName('dummy');
			$frontMatter->setOriginalFileName('dummy');
		}

		$issueId = Request::getUserVar('issueId');
		$frontMatter->setIssueId($issueId);
		$frontMatter->setTitle(Request::getUserVar('title'));
		$frontMatter->setDateModified(Core::getCurrentDate());
		$frontMatter->setFrontSectionId(Request::getUserVar('frontSectionId'));

		$cover = (int)Request::getUserVar('cover');
		if ($cover) {
			$frontMatterDao->removeCoverFromFrontMatter($issueId);
		}
		$frontMatter->setCover($cover);

		if ($this->frontId) {
			$frontMatterDao->updateFrontMatter($frontMatter);
		} else {
			$this->frontId = $frontMatterDao->insertFrontMatter($frontMatter);
			$frontMatter->setFrontId($this->frontId);
		}

		import('file.FrontMatterManager');
		$frontMatterManager = new FrontMatterManager($issueId);

		if ($frontMatterManager->uploadedFileExists('upload')) {
			$originalFileName = $frontMatterManager->getUploadedFileName('upload');
			$newFileName = $issueId . '-' . $this->frontId . '.' . $frontMatterManager->getExtension($originalFileName); 
			$frontMatterManager->uploadFile('upload',$newFileName);
			$frontMatter->setOriginalFileName($originalFileName);
			$frontMatter->setFileName($newFileName);
			$frontMatterDao->updateFrontMatter($frontMatter);
		}
	}
	
}

?>
