<?php

/**
 * @file controllers/tab/admin/categories/form/CategorySettingsForm.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CategorySettingsForm
 * @ingroup controllers_tab_settings_announcements_form
 *
 * @brief Form to edit announcement settings.
 */

import('lib.pkp.classes.form.Form');

class CategorySettingsForm extends Form {
	/**
	 * Constructor.
	 */
	function CategorySettingsForm() {
		parent::Form('controllers/tab/admin/categories/form/categorySettingsForm.tpl');
	}

	/**
	 * @copydoc Form::fetch()
	 */
	function fetch($request, $params = array()) {
		return parent::fetch($request);
	}

	/**
	 * @copydoc Form::initData()
	 */
	function initData($request) {
		$site = $request->getSite();
		$this->_data = array(
			'categoriesEnabled' => $site->getSetting('categoriesEnabled')
		);
	}

	/**
	 * @copydoc Form::readUserVars()
	 */
	function readInputData() {
		$this->readUserVars(array('categoriesEnabled', 'categories'));
	}

	/**
	 * @copydoc Form::execute()
	 */
	function execute($request) {
		$site = $request->getSite();
		$site->updateSetting('categoriesEnabled', (int) $this->getData('categoriesEnabled'));
		ListbuilderHandler::unpack($request, $this->getData('categories'));

	}

	/**
	 * @copydoc ListbuilderHandler::insertEntry()
	 */
	function insertEntry($request, $newRowId) {
		$categoryDao = DAORegistry::getDAO('CategoryDAO');
		$categoryEntryDao = $categoryDao->getEntryDao();
		$category = $categoryEntryDao->newDataObject();
		$category->setName($newRowId['name'], null);
		$category->setControlledVocabId($categoryDao->build()->getId());
		$categoryEntryDao->insertObject($category);
		return true;
	}

	/**
	 * @copydoc ListbuilderHandler::updateEntry()
	 */
	function updateEntry($request, $rowId, $newRowId) {
		$categoryDao = DAORegistry::getDAO('CategoryDAO');
		$categoryEntryDao = $categoryDao->getEntryDao();
		$category = $categoryEntryDao->getById($rowId, $categoryDao->build()->getId());
		$category->setName($newRowId['name'], null); // Localized
		$categoryEntryDao->updateObject($category);
		return true;
	}

	/**
	 * @copydoc ListbuilderHandler::deleteEntry()
	 */
	function deleteEntry($request, $rowId) {
		if ($rowId) {
			$categoryDao = DAORegistry::getDAO('CategoryDAO');
			$categoryEntryDao = $categoryDao->getEntryDao();
			$category = $categoryEntryDao->getById($rowId, $categoryDao->build()->getId());
			$categoryEntryDao->deleteObject($category);
		}
		return true;
	}

	/**
	 * Handle any additional form validation checks.
	 * (See SettingsTabHandler)
	 * @return boolean
	 */
	function addValidationChecks() {
		return true;
	}
}

?>
