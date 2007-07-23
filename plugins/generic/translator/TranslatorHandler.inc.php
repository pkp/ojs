<?php

/**
 * TranslatorHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * This handles requests for the translator plugin.
 *
 * $Id$
 */

require_once('TranslatorAction.inc.php');
require_once('EditableFile.inc.php');
require_once('EditableLocaleFile.inc.php');
require_once('EditableEmailFile.inc.php');

ini_set('display_errors', E_ALL); // FIXME until I improve error handling

class TranslatorHandler extends Handler {
	function index() {
		list($plugin) = TranslatorHandler::validate();
		TranslatorHandler::setupTemplate(false);

		$rangeInfo = Handler::getRangeInfo('locales');

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('locales', new ArrayItemIterator(Locale::getAllLocales(), $rangeInfo->getPage(), $rangeInfo->getCount()));
		$templateMgr->assign('masterLocale', MASTER_LOCALE);
		
		$templateMgr->display($plugin->getTemplatePath() . 'index.tpl');
	}

	function setupTemplate($subclass = true) {
		$templateMgr = &TemplateManager::getManager();
		$pageHierarchy = array(array(Request::url(null, 'user'), 'navigation.user'), array(Request::url(null, 'admin'), 'admin.siteAdmin'));
		if ($subclass) $pageHierarchy[] = array(Request::url(null, 'translate'), 'plugins.generic.translator.name');
		$templateMgr->assign('pageHierarchy', $pageHierarchy);
		$templateMgr->assign('helpTopicId', 'plugins.generic.TranslatorPlugin');
	}

	function edit($args) {
		list($plugin) = TranslatorHandler::validate();
		TranslatorHandler::setupTemplate();

		$locale = array_shift($args);
		$file = array_shift($args);

		if (!Locale::isLocaleValid($locale)) Request::redirect(null, null, 'index');
		$localeFiles = TranslatorAction::getLocaleFiles($locale);
		$miscFiles = TranslatorAction::getMiscLocaleFiles($locale);
		$emails = TranslatorAction::getEmailTemplates($locale);

		$templateMgr =& TemplateManager::getManager();

		$localeFilesRangeInfo = Handler::getRangeInfo('localeFiles');
		$miscFilesRangeInfo = Handler::getRangeInfo('miscFiles');
		$emailsRangeInfo = Handler::getRangeInfo('emails');

		$templateMgr->assign('localeFiles', new ArrayItemIterator($localeFiles, $localeFilesRangeInfo->getPage(), $localeFilesRangeInfo->getCount()));
		$templateMgr->assign('miscFiles', new ArrayItemIterator($miscFiles, $miscFilesRangeInfo->getPage(), $miscFilesRangeInfo->getCount()));
		$templateMgr->assign('emails', new ArrayItemIterator($emails, $emailsRangeInfo->getPage(), $emailsRangeInfo->getCount()));

		$templateMgr->assign('locale', $locale);
		$templateMgr->assign('masterLocale', MASTER_LOCALE);

		$templateMgr->display($plugin->getTemplatePath() . 'locale.tpl');
	}

	function check($args) {
		list($plugin) = TranslatorHandler::validate();
		TranslatorHandler::setupTemplate();

		$locale = array_shift($args);
		if (!Locale::isLocaleValid($locale)) Request::redirect(null, null, 'index');

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('locale', $locale);
		$templateMgr->assign('errors', Locale::testLocale($locale, MASTER_LOCALE));
		$templateMgr->assign('emailErrors', Locale::testEmails($locale, MASTER_LOCALE));
		$templateMgr->assign('localeFiles', TranslatorAction::getLocaleFiles($locale));
		$templateMgr->display($plugin->getTemplatePath() . 'errors.tpl');
	}

	/**
	 * Export the locale files to the browser as a tarball.
	 * Requires /bin/tar for operation.
	 */
	function export($args) {
		list($plugin) = TranslatorHandler::validate();
		TranslatorHandler::setupTemplate();

		$locale = array_shift($args);
		if (!Locale::isLocaleValid($locale)) Request::redirect(null, null, 'index');

		TranslatorAction::export($locale);
	}

	function saveLocaleChanges($args) {
		list($plugin) = TranslatorHandler::validate();
		TranslatorHandler::setupTemplate();
		
		$locale = array_shift($args);
		if (!Locale::isLocaleValid($locale)) Request::redirect(null, null, 'index');

		$localeFiles = TranslatorAction::getLocaleFiles($locale);

		$changesByFile = array();

		// Arrange the list of changes to save into an array by file.
		$stack = Request::getUserVar('stack');
		while (!empty($stack)) {
			$filename = array_shift($stack);
			$key = array_shift($stack);
			$value = array_shift($stack);
			if (in_array($filename, $localeFiles)) {
				$changesByFile[$filename][$key] = TranslatorHandler::correctCr($value);
			}
		}

		// Save the changes file by file.
		foreach ($changesByFile as $filename => $changes) {
			$file =& new EditableLocaleFile($locale, $filename);
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
				$file =& new EditableLocaleFile($locale, $filename);
				$file->delete($key);
				$file->write();
				unset($file);
			}
		}

		// Deal with email removals
		$deleteEmails = Request::getUserVar('deleteEmail');
		if (!empty($deleteEmails)) {
			$file =& new EditableEmailFile($locale, Locale::getEmailTemplateFilename($locale));
			foreach ($deleteEmails as $key) {
				$file->delete($key);
			}
			$file->write();
			unset($file);
		}

		Request::redirectUrl(Request::getUserVar('redirectUrl'));
	}

	function downloadLocaleFile($args) {
		list($plugin) = TranslatorHandler::validate();
		TranslatorHandler::setupTemplate();
		
		$locale = array_shift($args);
		if (!Locale::isLocaleValid($locale)) Request::redirect(null, null, 'index');

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
		list($plugin) = TranslatorHandler::validate();
		TranslatorHandler::setupTemplate();
		
		$locale = array_shift($args);
		if (!Locale::isLocaleValid($locale)) Request::redirect(null, null, 'index');

		$filename = urldecode(urldecode(array_shift($args)));
		if (!TranslatorAction::isLocaleFile($locale, $filename)) {
			Request::redirect(null, null, 'edit', $locale);
		}

		$templateMgr =& TemplateManager::getManager();


		$localeContentsRangeInfo = Handler::getRangeInfo('localeContents');
		$localeContents = EditableLocaleFile::load($filename);

		$templateMgr->assign('filename', $filename);
		$templateMgr->assign('locale', $locale);
		$templateMgr->assign_by_ref('localeContents', new ArrayItemIterator($localeContents, $localeContentsRangeInfo->getPage(), $localeContentsRangeInfo->getCount()));
		$templateMgr->assign('referenceLocaleContents', EditableLocaleFile::load(TranslatorAction::determineReferenceFilename($locale, $filename)));

		$templateMgr->display($plugin->getTemplatePath() . 'localeFile.tpl');
	}

	function editMiscFile($args) {
		list($plugin) = TranslatorHandler::validate();
		TranslatorHandler::setupTemplate();
		
		$locale = array_shift($args);
		if (!Locale::isLocaleValid($locale)) Request::redirect(null, null, 'index');

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
		list($plugin) = TranslatorHandler::validate();
		TranslatorHandler::setupTemplate();
		
		$locale = array_shift($args);
		if (!Locale::isLocaleValid($locale)) Request::redirect(null, null, 'index');

		$filename = urldecode(urldecode(array_shift($args)));
		if (!TranslatorAction::isLocaleFile($locale, $filename)) {
			Request::redirect(null, null, 'edit', $locale);
		}

		$changes = Request::getUserVar('changes');
		$file =& new EditableLocaleFile($locale, $filename);

		while (!empty($changes)) {
			$key = array_shift($changes);
			$value = TranslatorHandler::correctCr(array_shift($changes));
			if (!$file->update($key, $value)) {
				$file->insert($key, $value);
			}
		}
		$file->write();
		Request::redirectUrl(Request::getUserVar('redirectUrl'));
	}

	function deleteLocaleKey($args) {
		list($plugin) = TranslatorHandler::validate();
		TranslatorHandler::setupTemplate();
		
		$locale = array_shift($args);
		if (!Locale::isLocaleValid($locale)) Request::redirect(null, null, 'index');

		$filename = urldecode(urldecode(array_shift($args)));
		if (!TranslatorAction::isLocaleFile($locale, $filename)) {
			Request::redirect(null, null, 'edit', $locale);
		}

		$changes = Request::getUserVar('changes');
		$file =& new EditableLocaleFile($locale, $filename);

		if ($file->delete(array_shift($args))) $file->write();
		Request::redirect(null, null, 'editLocaleFile', array($locale, urlencode(urlencode($filename))));
	}

	function saveMiscFile($args) {
		list($plugin) = TranslatorHandler::validate();
		TranslatorHandler::setupTemplate();
		
		$locale = array_shift($args);
		if (!Locale::isLocaleValid($locale)) Request::redirect(null, null, 'index');

		$filename = urldecode(urldecode(array_shift($args)));
		if (!TranslatorAction::isLocaleFile($locale, $filename)) {
			Request::redirect(null, null, 'edit', $locale);
		}

		$fp = fopen($filename, 'w+'); // FIXME error handling
		if ($fp) {
			$contents = TranslatorHandler::correctCr(Request::getUserVar('translationContents'));
			fwrite ($fp, $contents);
			fclose($fp);
		}
		Request::redirect(null, null, 'edit', $locale);
	}

	function editEmail($args) {
		list($plugin) = TranslatorHandler::validate();
		TranslatorHandler::setupTemplate();
		
		$locale = array_shift($args);
		if (!Locale::isLocaleValid($locale)) Request::redirect(null, null, 'index');

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
		list($plugin) = TranslatorHandler::validate();
		TranslatorHandler::setupTemplate();
		
		$locale = array_shift($args);
		if (!Locale::isLocaleValid($locale)) Request::redirect(null, null, 'index');

		$filename = urldecode(urldecode(array_shift($args)));
		if (!TranslatorAction::isLocaleFile($locale, $filename)) {
			Request::redirect(null, null, 'edit', $locale);
		}

		import('file.FileManager');
		FileManager::copyFile(TranslatorAction::determineReferenceFilename($locale, $filename), $filename);
		Request::redirectUrl(Request::getUserVar('redirectUrl'));
	}

	function deleteEmail($args) {
		list($plugin) = TranslatorHandler::validate();
		TranslatorHandler::setupTemplate();
		
		$locale = array_shift($args);
		if (!Locale::isLocaleValid($locale)) Request::redirect(null, null, 'index');

		$emails = TranslatorAction::getEmailTemplates($locale);
		$referenceEmails = TranslatorAction::getEmailTemplates(MASTER_LOCALE);
		$emailKey = array_shift($args);

		if (!in_array($emailKey, array_keys($emails))) Request::redirect(null, null, 'index');

		$file =& new EditableEmailFile($locale, Locale::getEmailTemplateFilename($locale));

		$subject = Request::getUserVar('subject');
		$body = Request::getUserVar('body');
		$description = Request::getUserVar('description');

		if ($file->delete($emailKey)) $file->write();
		Request::redirect(null, null, 'edit', $locale, null, 'emails');
	}

	function saveEmail($args) {
		list($plugin) = TranslatorHandler::validate();
		TranslatorHandler::setupTemplate();
		
		$locale = array_shift($args);
		if (!Locale::isLocaleValid($locale)) Request::redirect(null, null, 'index');

		$emails = TranslatorAction::getEmailTemplates($locale);
		$referenceEmails = TranslatorAction::getEmailTemplates(MASTER_LOCALE);
		$emailKey = array_shift($args);

		if (!in_array($emailKey, array_keys($referenceEmails)) && !in_array($emailKey, array_keys($emails))) Request::redirect(null, null, 'index');

		$file =& new EditableEmailFile($locale, Locale::getEmailTemplateFilename($locale));

		$subject = TranslatorHandler::correctCr(Request::getUserVar('subject'));
		$body = TranslatorHandler::correctCr(Request::getUserVar('body'));
		$description = TranslatorHandler::correctCr(Request::getUserVar('description'));

		if (!$file->update($emailKey, $subject, $body, $description))
			$file->insert($emailKey, $subject, $body, $description);

		$file->write();
		if (Request::getUserVar('returnToCheck')==1) Request::redirect(null, null, 'check', $locale);
		else Request::redirect(null, null, 'edit', $locale);
	}

	function validate() {
		if (!Validation::isSiteAdmin()) {
			Validation::redirectLogin();
		}
		$plugin =& Registry::get('plugin');
		return array(&$plugin);
	}

	function correctCr($value) {
		return str_replace("\r\n", "\n", $value);
	}
}
?>
