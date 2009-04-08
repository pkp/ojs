<?php

/**
 * @file CustomLocalePlugin.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CustomLocalePlugin
 *
 * @brief This plugin enables customization of locale strings.
 */

// $Id$


define('CUSTOM_LOCALE_DIR', 'customLocale');
import('classes.plugins.GenericPlugin');

class CustomLocalePlugin extends GenericPlugin {

	function register($category, $path) {
		if (parent::register($category, $path)) {
			$this->addLocaleData();

			if ($this->getEnabled()) {
				// Add custom locale data for already registered locale files.
				// This includes the main locale file and the locale files for all
				// plugins registered prior to and including this one. 
				$locale = Locale::getLocale();
				$localeFiles = Locale::getLocaleFiles($locale);
				$journal = Request::getJournal();
				$journalId = $journal->getJournalId();
				$publicFilesDir = Config::getVar('files', 'public_files_dir');
				$customLocalePathBase = $publicFilesDir . DIRECTORY_SEPARATOR . 'journals' . DIRECTORY_SEPARATOR . $journalId . DIRECTORY_SEPARATOR . CUSTOM_LOCALE_DIR . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR;

				import('file.FileManager');
				foreach ($localeFiles as $localeFile) {
					$customLocalePath = $customLocalePathBase . $localeFile->getFilename();
					if (FileManager::fileExists($customLocalePath)) {
						Locale::registerLocaleFile($locale, $customLocalePath, true);
					}
				}

				// Add custom locale data for all plugins registered after this one
				HookRegistry::register('Plugin::addLocaleData', array(&$this, 'addCustomLocalePlugin'));
			}

			return true;
		}
		return false;
	}

	function addCustomLocalePlugin($hookName, $args) {
		$locale =& $args[0];		
		$localeFilename =& $args[1];

		$journal = Request::getJournal();
		$journalId = $journal->getJournalId();
		$publicFilesDir = Config::getVar('files', 'public_files_dir');
		$customLocalePath = $publicFilesDir . DIRECTORY_SEPARATOR . 'journals' . DIRECTORY_SEPARATOR . $journalId . DIRECTORY_SEPARATOR . CUSTOM_LOCALE_DIR . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . $localeFilename;

		import('file.FileManager');
		if (FileManager::fileExists($customLocalePath)) {
			Locale::registerLocaleFile($locale, $customLocalePath, true);
		}

		return true;

	}

	function getName() {
		return 'CustomLocalePlugin';
	}

	function getDisplayName() {
		return Locale::translate('plugins.generic.customLocale.name');
	}

	function getDescription() {
		return Locale::translate('plugins.generic.customLocale.description');
	}

	function getEnabled() {
		$journal = &Request::getJournal();
		if (!$journal) return false;
		return $this->getSetting($journal->getJournalId(), 'enabled');
	}

	function setEnabled($enabled) {
		$journal = &Request::getJournal();
		if ($journal) {
			$this->updateSetting($journal->getJournalId(), 'enabled', $enabled ? true : false);
			return true;
		}
		return false;
	}

	function smartyPluginUrl($params, &$smarty) {
		$path = array($this->getCategory(), $this->getName());
		if (is_array($params['path'])) {
			$params['path'] = array_merge($path, $params['path']);
		} elseif (!empty($params['path'])) {
			$params['path'] = array_merge($path, array($params['path']));
		} else {
			$params['path'] = $path;
		}

		if (!empty($params['key'])) {
			$params['path'] = array_merge($params['path'], array($params['key']));
			unset($params['key']);
		}

		if (!empty($params['file'])) {
			$params['path'] = array_merge($params['path'], array($params['file']));
			unset($params['file']);
		}

		return $smarty->smartyUrl($params, $smarty);
	}

	function getManagementVerbs() {
		$isEnabled = $this->getEnabled();

		$verbs[] = array(
			($isEnabled?'disable':'enable'),
			Locale::translate($isEnabled?'manager.plugins.disable':'manager.plugins.enable')
		);

		if ($isEnabled) $verbs[] = array(
			'index',
			Locale::translate('plugins.generic.customLocale.customize')
		);

		return $verbs;
	}

	function manage($verb, $args) {
		$this->import('CustomLocaleHandler');
		$returner = true;

		switch ($verb) {
			case 'enable':
				$this->setEnabled(true);
				$returner = false;
				break;
			case 'disable':
				$this->setEnabled(false);
				$returner = false;
				break;
			case 'index':
				if ($this->getEnabled()) CustomLocaleHandler::index();
				break;
			case 'edit':
				if ($this->getEnabled()) CustomLocaleHandler::edit($args);
				break;
			case 'saveLocaleChanges':
				if ($this->getEnabled()) CustomLocaleHandler::saveLocaleChanges($args);
				break;
			case 'editLocaleFile':
				if ($this->getEnabled()) CustomLocaleHandler::editLocaleFile($args);
				break;
			case 'saveLocaleFile':
				if ($this->getEnabled()) CustomLocaleHandler::saveLocaleFile($args);
				break;
			default:
				if ($this->getEnabled()) CustomLocaleHandler::index();
				
		}
		return $returner;
	}
}

?>
