<?php

/**
 * FrontMatterFormSection.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package issue.form
 *
 * Form to create or edit an issue front matter section
 *
 * $Id$
 */

class FrontMatterSectionForm extends Form {

	var $frontSectionId;

	/**
	 * Constructor.
	 */
	function FrontMatterSectionForm($frontSectionId) {
		parent::Form('editor/issueManagement.tpl');

		$this->frontSectionId = $frontSectionId;
		$this->addCheck(new FormValidator(&$this, 'title', 'required', 'editor.issues.titleRequired'));
		$this->addCheck(new FormValidator(&$this, 'abbrev', 'required', 'editor.issues.abbrevRequired'));
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();

		$frontMatterSectionDao = &DAORegistry::getDAO('FrontMatterSectionDAO');
		$frontMatterSection = $frontMatterSectionDao->getFrontMatterSectionById($this->frontSectionId);

		parent::display();	
	}

	/**
	 * Validate the form
	 */
	function validate() {
		return parent::validate();
	}

	/**
	 * Initialize form data from current selected front matter section.
	 */
	function initData() {

		$frontMatterSectionDao = &DAORegistry::getDAO('FrontMatterSectionDAO');
		$frontMatterSection = $frontMatterSectionDao->getFrontMatterSectionById($this->frontSectionId);

		if (isset($frontMatterSection)) {
			$this->_data = array(
				'frontSectionId' => $frontMatterSection->getFrontSectionId(),
				'title' => $frontMatterSection->getTitle(),
				'abbrev' => $frontMatterSection->getAbbrev()
			);
		}
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array(
			'frontSectionId',
			'issueId',
			'title',
			'abbrev'
		));
		
	}
	
	/**
	 * Save issue settings.
	 */
	function execute() {

		$frontMatterSectionDao = &DAORegistry::getDAO('FrontMatterSectionDAO');

		if ($this->frontSectionId) {
			$frontMatterSection = $frontMatterSectionDao->getFrontMatterSectionById($this->frontSectionId);
		} else {
			$frontMatterSection = &new FrontMatterSection();
			$frontMatterSection->setSeq(0);
			$frontMatterSection->setIssueId(Request::getUserVar('issueId'));
		}

		$frontMatterSection->setTitle(Request::getUserVar('title'));
		$frontMatterSection->setAbbrev(Request::getUserVar('abbrev'));

		if ($this->frontSectionId) {
			$frontMatterSectionDao->updateFrontMatterSection($frontMatterSection);
		} else {
			$frontMatterSectionDao->insertFrontMatterSection($frontMatterSection);
		}
	}
	
}

?>
