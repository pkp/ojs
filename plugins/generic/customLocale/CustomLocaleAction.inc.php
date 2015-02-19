<?php

/**
 * @file plugins/generic/customLocale/CustomLocaleAction.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CustomLocaleAction
 * @ingroup plugins_generic_customLocale
 *
 * @brief Perform various tasks related to customization of locale strings.
 */


class CustomLocaleAction {

	function getLocaleFiles($locale) {
		if (!AppLocale::isLocaleValid($locale)) return null;

		$localeFiles =& AppLocale::makeComponentMap($locale);
		$plugins =& PluginRegistry::loadAllPlugins();
		foreach (array_keys($plugins) as $key) {
			$plugin =& $plugins[$key];
			$localeFile = $plugin->getLocaleFilename($locale);
			if (!empty($localeFile)) {
				if (is_scalar($localeFile)) $localeFiles[] = $localeFile;
				if (is_array($localeFile)) $localeFiles = array_merge($localeFiles, $localeFile);
			}
			unset($plugin);
		}
		return $localeFiles;
	}

	function isLocaleFile($locale, $filename) {
		if (in_array($filename, CustomLocaleAction::getLocaleFiles($locale))) return true;
		return false;
	}

}
?>
