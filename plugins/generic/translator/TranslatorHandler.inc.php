<?php

/**
 * @file plugins/generic/translator/TranslatorHandler.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TranslatorHandler
 * @ingroup plugins_generic_translator
 *
 * @brief This handles requests for the translator plugin.
 */

require_once('TranslatorAction.inc.php');
import('classes.handler.Handler');

class TranslatorHandler extends Handler {
	var $plugin;

	/**
	 * Constructor
	 **/
	function TranslatorHandler() {
		parent::Handler();
		$this->addCheck(new HandlerValidatorRoles($this, true, null, null, array(ROLE_ID_SITE_ADMIN)));

		$plugin =& Registry::get('plugin');
		$this->plugin =& $plugin;
	}

	function getEmailTemplateFilename($locale) {
		return 'locale/' . $locale . '/emailTemplates.xml';
	}

	function index() {
		$this->validate();
		$plugin =& $this->plugin;
		$this->setupTemplate(false);

		$rangeInfo = Handler::getRangeInfo('locales');

		$templateMgr =& TemplateManager::getManager();
		import('lib.pkp.classes.core.ArrayItemIterator');
		$templateMgr->assign('locales', new ArrayItemIterator(AppLocale::getAllLocales(), $rangeInfo->getPage(), $rangeInfo->getCount()));
		$templateMgr->assign('masterLocale', MASTER_LOCALE);

		// Test whether the tar binary is available for the export to work
		$tarBinary = Config::getVar('cli', 'tar');
		$templateMgr->assign('tarAvailable', !empty($tarBinary) && file_exists($tarBinary));

		$templateMgr->display($plugin->getTemplatePath() . 'index.tpl');
	}

	function setupTemplate($subclass = true) {
		parent::setupTemplate();
		$templateMgr =& TemplateManager::getManager();
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_ADMIN, LOCALE_COMPONENT_PKP_MANAGER);
		$pageHierarchy = array(array(Request::url(null, 'user'), 'navigation.user'), array(Request::url(null, 'admin'), 'admin.siteAdmin'));
		if ($subclass) $pageHierarchy[] = array(Request::url(null, 'translate'), 'plugins.generic.translator.name');
		$templateMgr->assign('pageHierarchy', $pageHierarchy);
		$templateMgr->assign('helpTopicId', 'plugins.generic.TranslatorPlugin');
	}

	function edit($args) {
		$this->validate();
		$plugin =& $this->plugin;
		$this->setupTemplate();

		$locale = array_shift($args);
		$file = array_shift($args);

		if (!AppLocale::isLocaleValid($locale)) Request::redirect(null, null, 'index');
		$localeFiles = TranslatorAction::getLocaleFiles($locale);
		$miscFiles = TranslatorAction::getMiscLocaleFiles($locale);
		$emails = TranslatorAction::getEmailTemplates($locale);

		$templateMgr =& TemplateManager::getManager();

		$localeFilesRangeInfo = Handler::getRangeInfo('localeFiles');
		$miscFilesRangeInfo = Handler::getRangeInfo('miscFiles');
		$emailsRangeInfo = Handler::getRangeInfo('emails');

		import('lib.pkp.classes.core.ArrayItemIterator');
		$templateMgr->assign('localeFiles', new ArrayItemIterator($localeFiles, $localeFilesRangeInfo->getPage(), $localeFilesRangeInfo->getCount()));
		$templateMgr->assign('miscFiles', new ArrayItemIterator($miscFiles, $miscFilesRangeInfo->getPage(), $miscFilesRangeInfo->getCount()));
		$templateMgr->assign('emails', new ArrayItemIterator($emails, $emailsRangeInfo->getPage(), $emailsRangeInfo->getCount()));

		$templateMgr->assign('locale', $locale);
		$templateMgr->assign('masterLocale', MASTER_LOCALE);

		$templateMgr->display($plugin->getTemplatePath() . 'locale.tpl');
	}

	function check($args) {
		$this->validate();
		$plugin =& $this->plugin;
		$this->setupTemplate();

		$locale = array_shift($args);
		if (!AppLocale::isLocaleValid($locale)) Request::redirect(null, null, 'index');

		$localeFiles = TranslatorAction::getLocaleFiles($locale);
		$unwriteableFiles = array();
		foreach ($localeFiles as $localeFile) {
			$filename = Core::getBaseDir() . DIRECTORY_SEPARATOR . $localeFile;
			if (file_exists($filename) && !is_writeable($filename)) {
				$unwriteableFiles[] = $localeFile;
			}
		}

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('locale', $locale);
		$templateMgr->assign('errors', TranslatorAction::testLocale($locale, MASTER_LOCALE));
		$templateMgr->assign('emailErrors', TranslatorAction::testEmails($locale, MASTER_LOCALE));
		$templateMgr->assign('localeFiles', TranslatorAction::getLocaleFiles($locale));
		if(!empty($unwriteableFiles)) {
			$templateMgr->assign('error', true);
			$templateMgr->assign('unwriteableFiles', $unwriteableFiles);
		}
		$templateMgr->display($plugin->getTemplatePath() . 'errors.tpl');
	}

	/**
	 * Export the locale files to the browser as a tarball.
	 * Requires tar (configured in config.inc.php) for operation.
	 */
	function export($args) {
		$this->validate();
		$plugin =& $this->plugin;;
		$this->setupTemplate();

		$locale = array_shift($args);
		if (!AppLocale::isLocaleValid($locale)) Request::redirect(null, null, 'index');

		TranslatorAction::export($locale);
	}

	function saveLocaleChanges($args) {
		$this->validate();
		$plugin =& $this->plugin;
		$this->setupTemplate();

		$locale = array_shift($args);
		if (!AppLocale::isLocaleValid($locale)) Request::redirect(null, null, 'index');

		$localeFiles = TranslatorAction::getLocaleFiles($locale);

		$changesByFile = array();

		// Arrange the list of changes to save into an array by file.
		$stack = Request::getUserVar('stack');
		while (!empty($stack)) {
			$filename = array_shift($stack);
			$key = array_shift($stack);
			$value = array_shift($stack);
			if (in_array($filename, $localeFiles)) {
				$changesByFile[$filename][$key] = $this->correctCr($value);
			}
		}

		// Save the changes file by file.
		import('lib.pkp.classes.file.EditableLocaleFile');
		foreach ($changesByFile as $filename => $changes) {
			$file = new EditableLocaleFile($locale, $filename);
			foreach ($changes as $key => $value) {
				if (empty($value)) continue;
				if (!$file->update($key, $value)) {
					$file->insert($key, $value);
				}
			}
			$file->write();

			unset($nodes);
			unset($dom);
			unset($file);
		}

		// Deal with key removals
		$deleteKeys = Request::getUserVar('deleteKey');
		if (!empty($deleteKeys)) {
			if (!is_array($deleteKeys)) $deleteKeys = array($deleteKeys);
			foreach ($deleteKeys as $deleteKey) { // FIXME Optimize!
				list($filename, $key) = explode('/', $deleteKey, 2);
				$filename = urldecode(urldecode($filename));
				if (!in_array($filename, $localeFiles)) continue;
				$file = new EditableLocaleFile($locale, $filename);
				$file->delete($key);
				$file->write();
				unset($file);
			}
		}

		// Deal with email removals
		import('lib.pkp.classes.file.EditableEmailFile');
		$deleteEmails = Request::getUserVar('deleteEmail');
		if (!empty($deleteEmails)) {
			$file = new EditableEmailFile($locale, $this->getEmailTemplateFilename($locale));
			foreach ($deleteEmails as $key) {
				$file->delete($key);
			}
			$file->write();
			unset($file);
		}

		Request::redirectUrl(Request::getUserVar('redirectUrl'));
	}

	function downloadLocaleFile($args) {
		$this->validate();
		$plugin =& $this->plugin;
		$this->setupTemplate();

		$locale = array_shift($args);
		if (!AppLocale::isLocaleValid($locale)) Request::redirect(null, null, 'index');

		$filename = urldecode(urldecode(array_shift($args)));
		if (!TranslatorAction::isLocaleFile($locale, $filename)) {
			Request::redirect(null, null, 'edit', $locale);
		}

		header('Content-Type: application/xml');
		header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
		header('Cache-Control: private');
		readfile($filename);
	}

	function editLocaleFile($args) {
		$this->validate();
		$plugin =& $this->plugin;
		$this->setupTemplate();

		$locale = array_shift($args);
		if (!AppLocale::isLocaleValid($locale)) Request::redirect(null, null, 'index');

		$filename = urldecode(urldecode(array_shift($args)));
		if (!TranslatorAction::isLocaleFile($locale, $filename)) {
			Request::redirect(null, null, 'edit', $locale);
		}

		$templateMgr =& TemplateManager::getManager();
		if(!is_writeable(Core::getBaseDir() . DIRECTORY_SEPARATOR . $filename)) {
			$templateMgr->assign('error', true);
		}


		import('lib.pkp.classes.file.EditableLocaleFile');
		$localeContentsRangeInfo = Handler::getRangeInfo('localeContents');
		$localeContents = EditableLocaleFile::load($filename);

		// Handle a search, if one was executed.
		$searchKey = Request::getUserVar('searchKey');
		$found = false;
		$index = 0;
		$pageIndex = 0;
		if (!empty($searchKey)) foreach ($localeContents as $key => $value) {
			if ($index % $localeContentsRangeInfo->getCount() == 0) $pageIndex++;
			if ($key == $searchKey) {
				$found = true;
				break;
			}
			$index++;
		}

		if ($found) {
			$localeContentsRangeInfo->setPage($pageIndex);
			$templateMgr->assign('searchKey', $searchKey);
		}


		$templateMgr->assign('filename', $filename);
		$templateMgr->assign('locale', $locale);
		import('lib.pkp.classes.core.ArrayItemIterator');
		$templateMgr->assign_by_ref('localeContents', new ArrayItemIterator($localeContents, $localeContentsRangeInfo->getPage(), $localeContentsRangeInfo->getCount()));
		$templateMgr->assign('referenceLocaleContents', EditableLocaleFile::load(TranslatorAction::determineReferenceFilename($locale, $filename)));

		$templateMgr->display($plugin->getTemplatePath() . 'localeFile.tpl');
	}

	function editMiscFile($args) {
		$this->validate();
		$plugin =& $this->plugin;
		$this->setupTemplate();

		$locale = array_shift($args);
		if (!AppLocale::isLocaleValid($locale)) Request::redirect(null, null, 'index');

		$filename = urldecode(urldecode(array_shift($args)));
		if (!TranslatorAction::isLocaleFile($locale, $filename)) {
			Request::redirect(null, null, 'edit', $locale);
		}
		$referenceFilename = TranslatorAction::determineReferenceFilename($locale, $filename);
		$templateMgr =& TemplateManager::getManager();

		$templateMgr->assign('locale', $locale);
		$templateMgr->assign('filename', $filename);
		$templateMgr->assign('referenceContents', file_get_contents($referenceFilename));
		$templateMgr->assign('translationContents', file_exists($filename)?file_get_contents($filename):'');
		$templateMgr->display($plugin->getTemplatePath() . 'editMiscFile.tpl');
	}

	function saveLocaleFile($args) {
		$this->validate();
		$plugin =& $this->plugin;
		$this->setupTemplate();

		$locale = array_shift($args);
		if (!AppLocale::isLocaleValid($locale)) Request::redirect(null, null, 'index');

		$filename = urldecode(urldecode(array_shift($args)));
		if (!TranslatorAction::isLocaleFile($locale, $filename)) {
			Request::redirect(null, null, 'edit', $locale);
		}

		import('lib.pkp.classes.file.EditableLocaleFile');
		$changes = Request::getUserVar('changes');
		$file = new EditableLocaleFile($locale, $filename);

		while (!empty($changes)) {
			$key = array_shift($changes);
			$value = $this->correctCr(array_shift($changes));
			if (!$file->update($key, $value)) {
				$file->insert($key, $value);
			}
		}
		$file->write();
		Request::redirectUrl(Request::getUserVar('redirectUrl'));
	}

	function deleteLocaleKey($args) {
		$this->validate();
		$plugin =& $this->plugin;
		$this->setupTemplate();

		$locale = array_shift($args);
		if (!AppLocale::isLocaleValid($locale)) Request::redirect(null, null, 'index');

		$filename = urldecode(urldecode(array_shift($args)));
		if (!TranslatorAction::isLocaleFile($locale, $filename)) {
			Request::redirect(null, null, 'edit', $locale);
		}

		$changes = Request::getUserVar('changes');
		$file = new EditableLocaleFile($locale, $filename);

		if ($file->delete(array_shift($args))) $file->write();
		Request::redirect(null, null, 'editLocaleFile', array($locale, urlencode(urlencode($filename))));
	}

	function saveMiscFile($args) {
		$this->validate();
		$plugin =& $this->plugin;
		$this->setupTemplate();

		$locale = array_shift($args);
		if (!AppLocale::isLocaleValid($locale)) Request::redirect(null, null, 'index');

		$filename = urldecode(urldecode(array_shift($args)));
		if (!TranslatorAction::isLocaleFile($locale, $filename)) {
			Request::redirect(null, null, 'edit', $locale);
		}

		$fp = fopen($filename, 'w+'); // FIXME error handling
		if ($fp) {
			$contents = $this->correctCr(Request::getUserVar('translationContents'));
			fwrite ($fp, $contents);
			fclose($fp);
		}
		Request::redirect(null, null, 'edit', $locale);
	}

	function editEmail($args) {
		$this->validate();
		$plugin =& $this->plugin;
		$this->setupTemplate();

		$locale = array_shift($args);
		if (!AppLocale::isLocaleValid($locale)) Request::redirect(null, null, 'index');

		$emails = TranslatorAction::getEmailTemplates($locale);
		$referenceEmails = TranslatorAction::getEmailTemplates(MASTER_LOCALE);
		$emailKey = array_shift($args);

		if (!in_array($emailKey, array_keys($referenceEmails)) && !in_array($emailKey, array_keys($emails))) Request::redirect(null, null, 'index');

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('emailKey', $emailKey);
		$templateMgr->assign('locale', $locale);
		$templateMgr->assign('email', isset($emails[$emailKey])?$emails[$emailKey]:'');
		$templateMgr->assign('returnToCheck', Request::getUserVar('returnToCheck'));
		$templateMgr->assign('referenceEmail', isset($referenceEmails[$emailKey])?$referenceEmails[$emailKey]:'');
		$templateMgr->display($plugin->getTemplatePath() . 'editEmail.tpl');
	}

	function createFile($args) {
		$this->validate();
		$plugin =& $this->plugin;
		$this->setupTemplate();

		$locale = array_shift($args);
		if (!AppLocale::isLocaleValid($locale)) Request::redirect(null, null, 'index');

		$filename = urldecode(urldecode(array_shift($args)));
		if (!TranslatorAction::isLocaleFile($locale, $filename)) {
			Request::redirect(null, null, 'edit', $locale);
		}

		import('lib.pkp.classes.file.FileManager');
		$fileManager = new FileManager();
		$fileManager->copyFile(TranslatorAction::determineReferenceFilename($locale, $filename), $filename);
		Request::redirectUrl(Request::getUserVar('redirectUrl'));
	}

	function deleteEmail($args) {
		$this->validate();
		$plugin =& $this->plugin;
		$this->setupTemplate();

		$locale = array_shift($args);
		if (!AppLocale::isLocaleValid($locale)) Request::redirect(null, null, 'index');

		$emails = TranslatorAction::getEmailTemplates($locale);
		$referenceEmails = TranslatorAction::getEmailTemplates(MASTER_LOCALE);
		$emailKey = array_shift($args);

		if (!in_array($emailKey, array_keys($emails))) Request::redirect(null, null, 'index');

		import('lib.pkp.classes.file.EditableEmailFile');
		$file = new EditableEmailFile($locale, $this->getEmailTemplateFilename($locale));

		$subject = Request::getUserVar('subject');
		$body = Request::getUserVar('body');
		$description = Request::getUserVar('description');
		if ($file->delete($emailKey)) $file->write();
		Request::redirect(null, null, 'edit', $locale, null, 'emails');
	}

	function saveEmail($args) {
		$this->validate();
		$plugin =& $this->plugin;
		$this->setupTemplate();

		$locale = array_shift($args);
		if (!AppLocale::isLocaleValid($locale)) Request::redirect(null, null, 'index');

		$emails = TranslatorAction::getEmailTemplates($locale);
		$referenceEmails = TranslatorAction::getEmailTemplates(MASTER_LOCALE);
		$emailKey = array_shift($args);
		$targetFilename = str_replace(MASTER_LOCALE, $locale, $referenceEmails[$emailKey]['templateDataFile']); // FIXME: Ugly.

		if (!in_array($emailKey, array_keys($emails))) {
			// If it's not a reference or translation email, bail.
			if (!in_array($emailKey, array_keys($referenceEmails))) Request::redirect(null, null, 'index');

			// If it's a reference email but not a translated one,
			// create a blank file. FIXME: This is ugly.
			if (!file_exists($targetFilename)) {
				$dir = dirname($targetFilename);
				if (!file_exists($dir)) mkdir($dir);
				file_put_contents($targetFilename, '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE email_texts SYSTEM "../../../../../lib/pkp/dtd/emailTemplateData.dtd">
<!--
  * emailTemplateData.xml
  *
  * Copyright (c) 2003-2014 John Willinsky
  * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
  *
  * Localized email templates XML file.
  *
  -->
<email_texts locale="' . $locale . '">
</email_texts>');
			}
		}

		import('lib.pkp.classes.file.EditableEmailFile');
		$file = new EditableEmailFile($locale, $targetFilename);

		$subject = $this->correctCr(Request::getUserVar('subject'));
		$body = $this->correctCr(Request::getUserVar('body'));
		$description = $this->correctCr(Request::getUserVar('description'));

		if (!$file->update($emailKey, $subject, $body, $description))
			$file->insert($emailKey, $subject, $body, $description);

		$file->write();
		if (Request::getUserVar('returnToCheck')==1) Request::redirect(null, null, 'check', $locale);
		else Request::redirect(null, null, 'edit', $locale);
	}

	function correctCr($value) {
		return str_replace("\r\n", "\n", $value);
	}
}

?>
