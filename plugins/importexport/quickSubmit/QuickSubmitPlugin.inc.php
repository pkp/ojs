<?php

/**
 * @file plugins/importexport/quickSubmit/QuickSubmitPlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class QuickSubmitPlugin
 * @ingroup plugins_importexport_quickSubmit
 *
 * @brief Quick Submit one-page submission plugin
 */


import('classes.plugins.ImportExportPlugin');

class QuickSubmitPlugin extends ImportExportPlugin {

	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'QuickSubmitPlugin';
	}

	function getDisplayName() {
		return __('plugins.importexport.quickSubmit.displayName');
	}

	function getDescription() {
		return __('plugins.importexport.quickSubmit.description');
	}

	function display(&$args, $request) {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));
		AppLocale::requireComponents(LOCALE_COMPONENT_OJS_AUTHOR, LOCALE_COMPONENT_OJS_EDITOR, LOCALE_COMPONENT_PKP_SUBMISSION);
		$this->setBreadcrumbs();

		if (array_shift($args) == 'saveSubmit') {
			$this->saveSubmit($args, $request);
		} else {
			$this->import('QuickSubmitForm');
			if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
				$form = new QuickSubmitForm($this, $request);
			} else {
				$form =& new QuickSubmitForm($this, $request);
			}
			if ($form->isLocaleResubmit()) {
				$form->readInputData();
			} else {
				$form->initData();
			}
			$form->display();
		}
	}

	/**
	 * Save the submitted form
	 * @param $args array
	 */
	function saveSubmit($args, $request) {
		$templateMgr =& TemplateManager::getManager();

		$this->import('QuickSubmitForm');
		if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
			$form = new QuickSubmitForm($this, $request);
		} else {
			$form =& new QuickSubmitForm($this, $request);
		}
		$form->readInputData();
		$formLocale = $form->getFormLocale();

		if ($request->getUserVar('addAuthor')) {
			$editData = true;
			$authors = $form->getData('authors');
			$authors[] = array();
			$form->setData('authors', $authors);
		} else if (($delAuthor = $request->getUserVar('delAuthor')) && count($delAuthor) == 1) {
			$editData = true;
			list($delAuthor) = array_keys($delAuthor);
			$delAuthor = (int) $delAuthor;
			$authors = $form->getData('authors');
			if (isset($authors[$delAuthor]['authorId']) && !empty($authors[$delAuthor]['authorId'])) {
				$deletedAuthors = explode(':', $form->getData('deletedAuthors'));
				array_push($deletedAuthors, $authors[$delAuthor]['authorId']);
				$form->setData('deletedAuthors', join(':', $deletedAuthors));
			}
			array_splice($authors, $delAuthor, 1);
			$form->setData('authors', $authors);

			if ($form->getData('primaryContact') == $delAuthor) {
				$form->setData('primaryContact', 0);
			}
		} else if ($request->getUserVar('moveAuthor')) {
			$editData = true;
			$moveAuthorDir = $request->getUserVar('moveAuthorDir');
			$moveAuthorDir = $moveAuthorDir == 'u' ? 'u' : 'd';
			$moveAuthorIndex = (int) $request->getUserVar('moveAuthorIndex');
			$authors = $form->getData('authors');

			if (!(($moveAuthorDir == 'u' && $moveAuthorIndex <= 0) || ($moveAuthorDir == 'd' && $moveAuthorIndex >= count($authors) - 1))) {
				$tmpAuthor = $authors[$moveAuthorIndex];
				$primaryContact = $form->getData('primaryContact');
				if ($moveAuthorDir == 'u') {
					$authors[$moveAuthorIndex] = $authors[$moveAuthorIndex - 1];
					$authors[$moveAuthorIndex - 1] = $tmpAuthor;
					if ($primaryContact == $moveAuthorIndex) {
						$form->setData('primaryContact', $moveAuthorIndex - 1);
					} else if ($primaryContact == ($moveAuthorIndex - 1)) {
						$form->setData('primaryContact', $moveAuthorIndex);
					}
				} else {
					$authors[$moveAuthorIndex] = $authors[$moveAuthorIndex + 1];
					$authors[$moveAuthorIndex + 1] = $tmpAuthor;
					if ($primaryContact == $moveAuthorIndex) {
						$form->setData('primaryContact', $moveAuthorIndex + 1);
					} else if ($primaryContact == ($moveAuthorIndex + 1)) {
						$form->setData('primaryContact', $moveAuthorIndex);
					}
				}
			}
			$form->setData('authors', $authors);
		} else if ($request->getUserVar('uploadSubmissionFile')) {
			$editData = true;
			$tempFileId = $form->getData('tempFileId');
			$tempFileId[$formLocale] = $form->uploadSubmissionFile('submissionFile');
			$form->setData('tempFileId', $tempFileId);
		}

		if ($request->getUserVar('createAnother') && $form->validate()) {
			$form->execute();
			$request->redirect(null, 'manager', 'importexport', array('plugin', $this->getName()));
		} else if (!isset($editData) && $form->validate()) {
			$form->execute();
			$templateMgr->display($this->getTemplatePath() . 'submitSuccess.tpl');
		} else {
			$form->display();
		}

	}

	/**
	 * Extend the {url ...} for smarty to support this plugin.
	 */
	function smartyPluginUrl($params, &$smarty) {
		$path = array('plugin',$this->getName());
		if (is_array($params['path'])) {
			$params['path'] = array_merge($path, $params['path']);
		} elseif (!empty($params['path'])) {
			$params['path'] = array_merge($path, array($params['path']));
		} else {
			$params['path'] = $path;
		}

		if (!empty($params['id'])) {
			$params['path'] = array_merge($params['path'], array($params['id']));
			unset($params['id']);
		}
		return $smarty->smartyUrl($params, $smarty);
	}

}

?>
