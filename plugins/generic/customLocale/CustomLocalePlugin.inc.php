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

				// Add custom locale data for all locale files registered after this plugin
				HookRegistry::register('PKPLocale::registerLocaleFile', array(&$this, 'addCustomLocale'));
			}

			return true;
		}
		return false;
	}

	function addCustomLocale($hookName, $args) {
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
		$journal =& Request::getJournal();
		if (!$journal) return false;
		return $this->getSetting($journal->getJournalId(), 'enabled');
	}

	function setEnabled($enabled) {
		$journal =& Request::getJournal();
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

 	/*
 	 * Execute a management verb on this plugin
 	 * @param $verb string
 	 * @param $args array
	 * @param $message string Location for the plugin to put a result msg
 	 * @return boolean
 	 */
	function manage($verb, $args, &$message) {
		$this->import('CustomLocaleHandler');
		$returner = true;

		switch ($verb) {
			case 'enable':
				$this->setEnabled(true);
				$message = Locale::translate('plugins.generic.customLocale.enabled');
				$returner = false;
				break;
			case 'disable':
				$this->setEnabled(false);
				$message = Locale::translate('plugins.generic.customLocale.disabled');
				$returner = false;
				break;
			case 'index':
				if ($this->getEnabled()) {
					$customLocaleHandler = new CustomLocaleHandler();
					$customLocaleHandler->index();
				}
				break;
			case 'edit':
				if ($this->getEnabled()) {
					$customLocaleHandler = new CustomLocaleHandler();
					$customLocaleHandler->edit($args);
				}
				break;
			case 'saveLocaleChanges':
				if ($this->getEnabled()) {
					$customLocaleHandler = new CustomLocaleHandler();
					$customLocaleHandler->saveLocaleChanges($args);
				}
				break;
			case 'editLocaleFile':
				if ($this->getEnabled()) {
					$customLocaleHandler = new CustomLocaleHandler();
					$customLocaleHandler->editLocaleFile($args);
				}
				break;
			case 'saveLocaleFile':
				if ($this->getEnabled()) {
					$customLocaleHandler = new CustomLocaleHandler();
					$customLocaleHandler->saveLocaleFile($args);
				}
				break;
			default:
				if ($this->getEnabled()) {
					$customLocaleHandler = new CustomLocaleHandler();
					$customLocaleHandler->index();
				}
				
		}
		return $returner;
	}
}

?>
