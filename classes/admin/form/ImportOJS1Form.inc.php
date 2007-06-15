<?php

/**
 * ImportOJS1Form.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package admin.form
 *
 * Form for site administrator to migrate data from an OJS 1.x system.
 *
 * $Id$
 */

import('site.ImportOJS1');
import('form.Form');

class ImportOJS1Form extends Form {

	/** @var $importer ImportOJS1 */
	var $importer;
	
	/**
	 * Constructor.
	 * @param $journalId omit for a new journal
	 */
	function ImportOJS1Form() {
		parent::Form('admin/importOJS1.tpl');
		$this->importer = &new ImportOJS1();

		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'journalPath', 'required', 'admin.journals.form.pathRequired'));
		$this->addCheck(new FormValidator($this, 'importPath', 'required', 'admin.journals.form.importPathRequired'));
		$this->addCheck(new FormValidatorPost($this));
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('importError', $this->importer->error());
		parent::display();
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('journalPath', 'importPath', 'options'));
	}
	
	/**
	 * Import content.
	 * @return boolean/int false or journal ID
	 */
	function execute() {
		$options = $this->getData('options');
		$journalId = $this->importer->import($this->getData('journalPath'), $this->getData('importPath'), is_array($options) ? $options : array());
		return $journalId;
	}

	function getConflicts() {
		return $this->importer->getConflicts();
	}
}

?>
