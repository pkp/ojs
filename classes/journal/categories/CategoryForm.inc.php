<?php

/**
 * @file classes/journal/category/CategoryForm.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CategoryForm
 * @ingroup manager_form
 * @see Category
 *
 * @brief Form for site admins to create/edit categories.
 */

import('lib.pkp.classes.form.Form');

class CategoryForm extends Form {
	/** @var groupId int the ID of the group being edited */
	var $category;

	/**
	 * Constructor
	 * @param group Category object; null to create new
	 */
	function CategoryForm($category = null) {
		parent::Form('admin/categories/categoryForm.tpl');

		// Category name is provided
		$this->addCheck(new FormValidatorLocale($this, 'name', 'required', 'admin.categories.nameRequired'));
		$this->addCheck(new FormValidatorPost($this));

		$this->category =& $category;
	}

	/**
	 * Get the list of localized field names for this object
	 * @return array
	 */
	function getLocaleFieldNames() {
		$categoryDao =& DAORegistry::getDAO('CategoryDAO');
		$categoryEntryDao =& $categoryDao->getEntryDAO();
		return $categoryEntryDao->getLocaleFieldNames();
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('category', $this->category);
		return parent::display();
	}

	/**
	 * Initialize form data from current group group.
	 */
	function initData() {
		if ($this->category != null) {
			$this->_data = array(
				'name' => $this->category->getName(null) // Localized
			);
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('name'));
	}

	/**
	 * Save group group.
	 */
	function execute() {
		$categoryDao =& DAORegistry::getDAO('CategoryDAO');
		$categoryEntryDao =& $categoryDao->getEntryDAO();
		$categoryControlledVocab =& $categoryDao->build();

		if (!isset($this->category)) {
			$this->category =& $categoryEntryDao->newDataObject();
		}

		$this->category->setName($this->getData('name'), null); // Localized
		$this->category->setControlledVocabId($categoryControlledVocab->getId());

		// Update or insert category
		if ($this->category->getId() != null) {
			$categoryEntryDao->updateObject($this->category);
		} else {
			$this->category->setSequence(REALLY_BIG_NUMBER);
			$categoryEntryDao->insertObject($this->category);
			$categoryEntryDao->resequence($categoryControlledVocab->getId());
		}
	}
}

?>
