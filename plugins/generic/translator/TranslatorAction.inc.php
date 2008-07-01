<?php

/**
 * @file TranslatorAction.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TranslatorAction
 * @ingroup plugins_generic_translator
 *
 * @brief Perform various tasks related to translation.
 */

// $Id$


/** This command is used to execute tar. */
define('TAR_COMMAND', '/bin/tar cz');

class TranslatorAction {
	/**
	 * Export the locale files to the browser as a tarball.
	 * Requires /bin/tar for operation.
	 */
	function export($locale) {
		$command = TAR_COMMAND;
		$localeFilesList = TranslatorAction::getLocaleFiles($locale);
		$localeFilesList = array_merge($localeFilesList, TranslatorAction::getMiscLocaleFiles($locale));
		$localeFilesList[] = Locale::getEmailTemplateFilename($locale);

		// Include locale files (main file and plugin files)
		foreach ($localeFilesList as $file) {
			if (file_exists($file)) $command .= ' ' . escapeshellarg($file);
		}

		header('Content-Type: application/x-gtar');
		header("Content-Disposition: attachment; filename=\"$locale.tar.gz\"");
		header('Cache-Control: private'); // Workarounds for IE weirdness
		passthru($command);
	}

	function getLocaleFiles($locale) {
		if (!Locale::isLocaleValid($locale)) return null;

		$localeFiles = array(Locale::getMainLocaleFilename($locale));
		$plugins =& PluginRegistry::loadAllPlugins();
		foreach (array_keys($plugins) as $key) {
			$plugin =& $plugins[$key];
			$localeFile = $plugin->getLocaleFilename($locale);
			if (!empty($localeFile)) $localeFiles[] = $localeFile;
			unset($plugin);
		}
		return $localeFiles;
	}

	function getMiscLocaleFiles($locale) {
		$countryDao =& DAORegistry::getDAO('CountryDAO');
		$currencyDao =& DAORegistry::getDAO('CurrencyDAO');
		return array(
			$countryDao->getFilename($locale),
			$currencyDao->getCurrencyFilename($locale)
		);
	}

	function getEmailTemplates($locale) {
		$xmlParser =& new XMLParser();
		$emails = $xmlParser->parse(Locale::getEmailTemplateFilename($locale));
		$emailsTable =& $emails->getChildByName('table');

		$returner = array();

		for ($emailIndex = 0; ($email =& $emailsTable->getChildByName('row', $emailIndex)) !== null; $emailIndex++) {
			$fields = Locale::extractFields($email);
			$returner[$fields['email_key']]['subject'] = $fields['subject'];
			$returner[$fields['email_key']]['body'] = $fields['body'];
			$returner[$fields['email_key']]['description'] = isset($fields['description'])?$fields['description']:'';
		}
		return $returner;
	}

	function isLocaleFile($locale, $filename) {
		if (in_array($filename, TranslatorAction::getLocaleFiles($locale))) return true;
		if (in_array($filename, TranslatorAction::getMiscLocaleFiles($locale))) return true;
		if ($filename == Locale::getEmailTemplateFilename($locale)) return true;
		return false;
	}

	function determineReferenceFilename($locale, $filename) {
		// FIXME: This is ugly.
		return str_replace($locale, MASTER_LOCALE, $filename);
	}
}
?>
