<?php

/**
 * @file plugins/generic/translator/TranslatorAction.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TranslatorAction
 * @ingroup plugins_generic_translator
 *
 * @brief Perform various tasks related to translation.
 */


class TranslatorAction {
	/**
	 * Export the locale files to the browser as a tarball.
	 * Requires tar for operation (configured in config.inc.php).
	 * @param $locale string Locale code for exported locale
	 */
	function export($locale) {
		// Construct the tar command
		$tarBinary = Config::getVar('cli', 'tar');
		if (empty($tarBinary) || !file_exists($tarBinary)) {
			// We can use fatalError() here as we already have a user
			// friendly way of dealing with the missing tar on the
			// index page.
			fatalError('The tar binary must be configured in config.inc.php\'s cli section to use the export function of this plugin!');
		}
		$command = $tarBinary.' cz';

		$localeFilesList = TranslatorAction::getLocaleFiles($locale);
		$localeFilesList = array_merge($localeFilesList, TranslatorAction::getMiscLocaleFiles($locale));
		$emailTemplateDao = DAORegistry::getDAO('EmailTemplateDAO');
		$localeFilesList[] = $emailTemplateDao->getMainEmailTemplateDataFilename($locale);
		foreach (array_values(TranslatorAction::getEmailFileMap($locale)) as $emailFile) {
		}

		// Include locale files (main file and plugin files)
		foreach ($localeFilesList as $file) {
			if (file_exists($file)) $command .= ' ' . escapeshellarg($file);
		}

		header('Content-Type: application/x-gtar');
		header("Content-Disposition: attachment; filename=\"$locale.tar.gz\"");
		header('Cache-Control: private'); // Workarounds for IE weirdness
		passthru($command);
	}

	/**
	 * Get a list of locale files for the given locale code.
	 * @param $locale string Locale code
	 * @return array List of filenames
	 */
	function getLocaleFiles($locale) {
		if (!AppLocale::isLocaleValid($locale)) return null;

		$localeFiles = AppLocale::getFilenameComponentMap($locale);
		$pluginsLocaleFilesList = array();
		$plugins =& PluginRegistry::loadAllPlugins();
		foreach (array_keys($plugins) as $key) {
			$plugin =& $plugins[$key];
			$localeFile = $plugin->getLocaleFilename($locale);
			if (!empty($localeFile) && !in_array($localeFile, $pluginsLocaleFilesList)) {
				$pluginsLocaleFilesList[] = $localeFile;
				if (is_scalar($localeFile)) $localeFiles[] = $localeFile;
				if (is_array($localeFile)) $localeFiles = array_merge($localeFiles, $localeFile);
			}
			unset($plugin);
		}
		return $localeFiles;
	}

	/**
	 * Get a list of miscellaneous locale files for the specified locale.
	 * @param $locale string Locale code
	 * @return array List of locale files.
	 */
	function getMiscLocaleFiles($locale) {
		$countryDao = DAORegistry::getDAO('CountryDAO');
		$currencyDao = DAORegistry::getDAO('CurrencyDAO');
		return array(
			$countryDao->getFilename($locale),
			$currencyDao->getCurrencyFilename($locale)
		);
	}

	/**
	 * Get a map of email template descriptors to translated email template files.
	 * @param $locale string Locale code
	 * @return array Mapping of ('emailList.xml' => 'translatedEmails.xml')
	 */
	function getEmailFileMap($locale) {
		$emailTemplateDao = DAORegistry::getDAO('EmailTemplateDAO');
		$files = array($emailTemplateDao->getMainEmailTemplatesFilename() => $emailTemplateDao->getMainEmailTemplateDataFilename($locale));
		$categories = PluginRegistry::getCategories();
		foreach ($categories as $category) {
			$plugins =& PluginRegistry::loadCategory($category);
			if (!empty($plugins)) foreach (array_keys($plugins) as $name) {
				$plugin =& $plugins[$name];
				$templatesFile = $plugin->getInstallEmailTemplatesFile();
				if ($templatesFile) {
					$files[$templatesFile] = str_replace('{$installedLocale}', $locale, $plugin->getInstallEmailTemplateDataFile());
				}
				unset($plugin);
			}
			unset($plugins);
		}
		return $files;
	}
}

?>
