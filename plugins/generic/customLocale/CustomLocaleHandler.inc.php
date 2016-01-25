<?php

/**
 * @file plugins/generic/customLocale/CustomLocaleHandler.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CustomLocaleHandler
 * @ingroup plugins_generic_customLocale
 *
 * @brief This handles requests for the customLocale plugin.
 */

require_once('CustomLocalePlugin.inc.php');
require_once('CustomLocaleAction.inc.php');
import('classes.handler.Handler');

class CustomLocaleHandler extends Handler {
	/** Plugin associated with the request */
	var $plugin;
	
	/**
	 * Constructor
	 **/
	function CustomLocaleHandler($parentPluginName) {
		parent::Handler();

		$this->addCheck(new HandlerValidatorRoles($this, true, null, null, array(ROLE_ID_SITE_ADMIN, ROLE_ID_MANAGER)));

		$this->plugin = PluginRegistry::getPlugin('generic', $parentPluginName);
	}

	function index($args, $request) {
		$this->validate(null, $request);
		$plugin =& $this->plugin;
		$this->setupTemplate($request, $plugin);

		$journal = $request->getJournal();
		$rangeInfo = $this->getRangeInfo($request, 'locales');

		$templateMgr = TemplateManager::getManager($request);
		import('lib.pkp.classes.core.ArrayItemIterator');
		$templateMgr->assign('locales', new ArrayItemIterator($journal->getSupportedLocaleNames(), $rangeInfo->getPage(), $rangeInfo->getCount()));
		$templateMgr->assign('masterLocale', MASTER_LOCALE);

		$templateMgr->display($plugin->getTemplatePath() . 'index.tpl');
	}

	function edit($args, $request) {
		$this->validate(null, $request);
		$plugin =& $this->plugin;
		$this->setupTemplate($request, $plugin);

		$locale = array_shift($args);
		$file = array_shift($args);

		if (!AppLocale::isLocaleValid($locale)) {
			$path = array($plugin->getCategory(), $plugin->getName(), 'index');
			$request->redirect(null, null, null, $path);
		}
		$localeFiles = CustomLocaleAction::getLocaleFiles($locale);

		$templateMgr = TemplateManager::getManager($request);

		$localeFilesRangeInfo = $this->getRangeInfo($request, 'localeFiles');

		import('lib.pkp.classes.core.ArrayItemIterator');
		$templateMgr->assign('localeFiles', new ArrayItemIterator($localeFiles, $localeFilesRangeInfo->getPage(), $localeFilesRangeInfo->getCount()));

		$templateMgr->assign('locale', $locale);
		$templateMgr->assign('masterLocale', MASTER_LOCALE);

		$templateMgr->display($plugin->getTemplatePath() . 'locale.tpl');
	}

	function editLocaleFile($args, $request) {
		$this->validate(null, $request);
		$plugin =& $this->plugin;
		$this->setupTemplate($request, $plugin);

		$locale = array_shift($args);
		if (!AppLocale::isLocaleValid($locale)) {
			$path = array($plugin->getCategory(), $plugin->getName(), 'index');
			$request->redirect(null, null, null, $path);
		}

		$filename = urldecode(urldecode(array_shift($args)));
		if (!CustomLocaleAction::isLocaleFile($locale, $filename)) {
			$path = array($plugin->getCategory(), $plugin->getName(), 'edit', $locale);
			$request->redirect(null, null, null, $path);
		}

		$templateMgr = TemplateManager::getManager($request);

		import('lib.pkp.classes.file.FileManager');
		$fileManager = new FileManager();

		import('lib.pkp.classes.file.EditableLocaleFile');
		$journal = $request->getJournal();
		$journalId = $journal->getId();
		$publicFilesDir = Config::getVar('files', 'public_files_dir');
		$customLocaleDir = $publicFilesDir . DIRECTORY_SEPARATOR . 'journals' . DIRECTORY_SEPARATOR . $journalId . DIRECTORY_SEPARATOR . CUSTOM_LOCALE_DIR;
		$customLocalePath = $customLocaleDir . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . $filename;
		if ($fileManager->fileExists($customLocalePath)) {
			$localeContents = EditableLocaleFile::load($customLocalePath);
		} else {
			$localeContents = null;
		}

		$referenceLocaleContents = EditableLocaleFile::load($filename);
		$referenceLocaleContentsRangeInfo = $this->getRangeInfo($request, 'referenceLocaleContents');

		// Handle a search, if one was executed.
		$searchKey = $request->getUserVar('searchKey');
		$found = false;
		$index = 0;
		$pageIndex = 0;
		if (!empty($searchKey)) foreach ($referenceLocaleContents as $key => $value) {
			if ($index % $referenceLocaleContentsRangeInfo->getCount() == 0) $pageIndex++;
			if ($key == $searchKey) {
				$found = true;
				break;
			}
			$index++;
		}

		if ($found) {
			$referenceLocaleContentsRangeInfo->setPage($pageIndex);
			$templateMgr->assign('searchKey', $searchKey);
		}

		$templateMgr->assign('filename', $filename);
		$templateMgr->assign('locale', $locale);
		import('lib.pkp.classes.core.ArrayItemIterator');
		$templateMgr->assign('referenceLocaleContents', new ArrayItemIterator($referenceLocaleContents, $referenceLocaleContentsRangeInfo->getPage(), $referenceLocaleContentsRangeInfo->getCount()));
		$templateMgr->assign('localeContents', $localeContents);

		$templateMgr->display($plugin->getTemplatePath() . 'localeFile.tpl');
	}

	function saveLocaleFile($args, $request) {
		$this->validate(null, $request);
		$plugin =& $this->plugin;
		$this->setupTemplate($request, $plugin);

		$locale = array_shift($args);
		if (!AppLocale::isLocaleValid($locale)) {
			$path = array($plugin->getCategory(), $plugin->getName(), 'index');
			$request->redirect(null, null, null, $path);
		}

		$filename = urldecode(urldecode(array_shift($args)));
		if (!CustomLocaleAction::isLocaleFile($locale, $filename)) {
			$path = array($plugin->getCategory(), $plugin->getName(), 'edit', $locale);
			$request->redirect(null, null, null, $path);
		}

		$journal = $request->getJournal();
		$journalId = $journal->getId();
		$changes = $request->getUserVar('changes');
		$customFilesDir = Config::getVar('files', 'public_files_dir') . DIRECTORY_SEPARATOR . 'journals' . DIRECTORY_SEPARATOR . $journalId . DIRECTORY_SEPARATOR . CUSTOM_LOCALE_DIR . DIRECTORY_SEPARATOR . $locale;
		$customFilePath = $customFilesDir . DIRECTORY_SEPARATOR . $filename;

		// Create empty custom locale file if it doesn't exist
		import('lib.pkp.classes.file.FileManager');
		$fileManager = new FileManager();

		import('lib.pkp.classes.file.EditableLocaleFile');
		if (!$fileManager->fileExists($customFilePath)) {
			$numParentDirs = substr_count($customFilePath, DIRECTORY_SEPARATOR); 
			$parentDirs = '';
			for ($i=0; $i<$numParentDirs; $i++) {
				$parentDirs .= '..' . DIRECTORY_SEPARATOR;
			}

			$newFileContents = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
			$newFileContents .= '<!DOCTYPE locale SYSTEM "' . $parentDirs . 'lib' . DIRECTORY_SEPARATOR . 'pkp' . DIRECTORY_SEPARATOR . 'dtd' . DIRECTORY_SEPARATOR . 'locale.dtd' . '">' . "\n";
			$newFileContents .= '<locale name="' . $locale . '">' . "\n";
			$newFileContents .= '</locale>';
			$fileManager->writeFile($customFilePath, $newFileContents);
		}

		$file = new EditableLocaleFile($locale, $customFilePath);

		while (!empty($changes)) {
			$key = array_shift($changes);
			$value = $this->correctCr(array_shift($changes));
			if (!empty($value)) {
				if (!$file->update($key, $value)) {
					$file->insert($key, $value);
				}
			} else {
				$file->delete($key);
			}
		}
		$file->write();

		$request->redirectUrl($request->getUserVar('redirectUrl'));
	}

	function correctCr($value) {
		return str_replace("\r\n", "\n", $value);
	}

	function setupTemplate($request, $plugin) {
		parent::setupTemplate($request);
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->register_function('plugin_url', array($plugin, 'smartyPluginUrl'));
	}
}

?>
