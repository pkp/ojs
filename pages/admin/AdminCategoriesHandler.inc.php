<?php

/**
 * @file pages/admin/AdminCategoriesHandler.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AdminCategoriesHandler
 * @ingroup pages_admin
 *
 * @brief Handle requests for changing admin's category list. 
 *
 */

import('pages.admin.AdminHandler');

class AdminCategoriesHandler extends AdminHandler {
	/** @var $categoryControlledVocab object Category controlled vocab, if one is validated */
	var $categoryControlledVocab;

	/** @var $category object Category entry, if one is validated */
	var $category;

	/**
	 * Constructor
	 **/
	function AdminCategoriesHandler() {
		parent::AdminHandler();
	}

	/**
	 * Display a list of categories.
	 */
	function categories($args, &$request) {
		$this->validate();
		$this->setupTemplate($request);

		$rangeInfo =& Handler::getRangeInfo('categories');

		$categoryDao =& DAORegistry::getDAO('CategoryDAO');
		$categoryEntryDao =& $categoryDao->getEntryDAO();

		$categoriesArray =& $categoryDao->getCategories();
		import('lib.pkp.classes.core.ArrayItemIterator');
		$categories =& ArrayItemIterator::fromRangeInfo($categoriesArray, $rangeInfo);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->addJavaScript('lib/pkp/js/lib/jquery/plugins/jquery.tablednd.js');
		$templateMgr->addJavaScript('lib/pkp/js/functions/tablednd.js');
		$templateMgr->assign_by_ref('categories', $categories);

		$site =& $request->getSite();
		$templateMgr->assign('categoriesEnabled', $site->getSetting('categoriesEnabled'));

		$templateMgr->display('admin/categories/categories.tpl');
	}

	/**
	 * Delete a category.
	 * @param $args array first parameter is the ID of the category to delete
	 */
	function deleteCategory($args, &$request) {
		$categoryId = (int) array_shift($args);
		$this->validate($categoryId);

		$category =& $this->category;

		$categoryDao =& DAORegistry::getDAO('CategoryDAO');
		$categoryEntryDao =& $categoryDao->getEntryDAO();
		$categoryEntryDao->deleteObject($category);

		$categoryEntryDao->resequence($this->categoryControlledVocab->getId());

		$request->redirect(null, null, 'categories');
	}

	/**
	 * Change the sequence of a category.
	 */
	function moveCategory($args, &$request) {
		$categoryId = (int) $request->getUserVar('id');
		$this->validate($categoryId);

		$category =& $this->category;
		$categoryDao =& DAORegistry::getDAO('CategoryDAO');
		$categoryEntryDao =& $categoryDao->getEntryDAO();
		$direction = $request->getUserVar('d');

		if ($direction != null) {
			// moving with up or down arrow
			$category->setSequence($category->getSequence() + ($direction == 'u' ? -1.5 : 1.5));

		} else {
			// Dragging and dropping
			$prevId = Request::getUserVar('prevId');
			if ($prevId == null)
				$prevSeq = 0;
			else {
				$prevCategory =& $categoryEntryDao->getById($prevId, $this->categoryControlledVocab->getId());
				$prevSeq = $prevCategory->getSequence();
			}

			$category->setSequence($prevSeq + .5);
		}

		$categoryEntryDao->updateObject($category);
		$categoryEntryDao->resequence($this->categoryControlledVocab->getId());

		// Moving up or down with the arrows requires a page reload.
		// In the case of a drag and drop move, the display has been
		// updated on the client side, so no reload is necessary.
		if ($direction != null) {
			Request::redirect(null, null, 'categories');
		}
	}

	/**
	 * Display form to edit a category.
	 * @param $args array optional, first parameter is the ID of the category to edit
	 */
	function editCategory($args, &$request) {
		$categoryId = (int) array_shift($args);
		if (!$categoryId) $categoryId = null;

		$this->validate($categoryId);

		$this->setupTemplate($request, $this->category, true);
		import('classes.journal.categories.CategoryForm');

		$templateMgr =& TemplateManager::getManager();

		$templateMgr->assign('pageTitle',
			$this->category === null?
				'admin.categories.createTitle':
				'admin.categories.editTitle'
		);

		$categoryForm = new CategoryForm($this->category);
		if ($categoryForm->isLocaleResubmit()) {
			$categoryForm->readInputData();
		} else {
			$categoryForm->initData();
		}
		$categoryForm->display();
	}

	/**
	 * Display form to create new category.
	 */
	function createCategory($args, &$request) {
		$this->editCategory($args, $request);
	}

	/**
	 * Save changes to a category.
	 */
	function updateCategory($args, &$request) {
		$categoryId = $request->getUserVar('categoryId') === null? null : (int) Request::getUserVar('categoryId');
		if ($categoryId === null) {
			$this->validate();
			$category = null;
		} else {
			$categoryId = (int) $categoryId;
			$this->validate($categoryId);
			$category =& $this->category;
		}
		$this->setupTemplate($request, $category);

		import('classes.journal.categories.CategoryForm');

		$categoryForm = new CategoryForm($category);
		$categoryForm->readInputData();

		if ($categoryForm->validate()) {
			$categoryForm->execute();
			Request::redirect(null, null, 'categories');
		} else {

			$templateMgr =& TemplateManager::getManager();
			$templateMgr->append('pageHierarchy', array(Request::url(null, 'admin', 'categories'), 'admin.categories'));

			$templateMgr->assign('pageTitle',
				$category?
					'admin.categories.editTitle':
					'admin.categories.createTitle'
			);

			$categoryForm->display();
		}
	}

	function setCategoriesEnabled($args, &$request) {
		$this->validate();
		$categoriesEnabled = $request->getUserVar('categoriesEnabled')==1?true:false;
		$siteSettingsDao =& DAORegistry::getDAO('SiteSettingsDAO');
		$siteSettingsDao->updateSetting('categoriesEnabled', $categoriesEnabled);
		$request->redirect(null, null, 'categories');
	}

	function setupTemplate($request = null, $category = null, $subclass = false) {
		parent::setupTemplate(true);
		$templateMgr =& TemplateManager::getManager();
		if ($subclass) {
			$templateMgr->append('pageHierarchy', array($request->url(null, 'admin', 'categories'), 'admin.categories'));
		}
		if ($category) {
			$templateMgr->append('pageHierarchy', array($request->url(null, 'admin', 'editCategory', $category->getId()), $category->getLocalizedName(), true));
		}
	}

	/**
	 * Validate the request. If a category ID is supplied, the category object
	 * will be fetched and validated against. If,
	 * additionally, the user ID is supplied, the user and membership
	 * objects will be validated and fetched.
	 * @param $categoryId int optional
	 */
	function validate($categoryId = null) {
		parent::validate();

		$passedValidation = true;

		$categoryDao =& DAORegistry::getDAO('CategoryDAO');
		$this->categoryControlledVocab =& $categoryDao->build();

		if ($categoryId !== null) {
			$categoryEntryDao =& $categoryDao->getEntryDAO();

			$category =& $categoryEntryDao->getById($categoryId, $this->categoryControlledVocab->getId());
			if (!$category) $passedValidation = false;
			else $this->category =& $category;
		} else {
			$this->category = null;
		}

		if (!$passedValidation) Request::redirect(null, null, 'categories');
		return true;
	}
}

?>
