<?php

/**
 * @file classes/manager/form/setup/JournalSetupStep5Form.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JournalSetupStep5Form
 * @ingroup manager_form_setup
 *
 * @brief Form for Step 5 of journal setup.
 */

import('classes.manager.form.setup.JournalSetupForm');

class JournalSetupStep5Form extends JournalSetupForm {
	/**
	 * Constructor.
	 */
	function JournalSetupStep5Form() {
		parent::JournalSetupForm(
			5,
			array(
				'homeHeaderTitleType' => 'int',
				'homeHeaderTitle' => 'string',
				'pageHeaderTitleType' => 'int',
				'pageHeaderTitle' => 'string',
				'readerInformation' => 'string',
				'authorInformation' => 'string',
				'librarianInformation' => 'string',
				'journalPageHeader' => 'string',
				'journalPageFooter' => 'string',
				'displayCurrentIssue' => 'bool',
				'additionalHomeContent' => 'string',
				'description' => 'string',
				'navItems' => 'object',
				'itemsPerPage' => 'int',
				'numPageLinks' => 'int',
				'journalTheme' => 'string',
				'journalThumbnailAltText' => 'string',
				'homeHeaderTitleImageAltText' => 'string',
				'homeHeaderLogoImageAltText' => 'string',
				'homepageImageAltText' => 'string',
				'pageHeaderTitleImageAltText' => 'string',
				'pageHeaderLogoImageAltText' => 'string'
			)
		);
	}

	/**
	 * Get the list of field names for which localized settings are used.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('homeHeaderTitleType', 'homeHeaderTitle', 'pageHeaderTitleType', 'pageHeaderTitle', 'readerInformation', 'authorInformation', 'librarianInformation', 'journalPageHeader', 'journalPageFooter', 'homepageImage', 'journalFavicon', 'additionalHomeContent', 'description', 'navItems', 'homeHeaderTitleImageAltText', 'homeHeaderLogoImageAltText', 'journalThumbnailAltText', 'homepageImageAltText', 'pageHeaderTitleImageAltText', 'pageHeaderLogoImageAltText');

	}

	/**
	 * Display the form.
	 */
	function display($request, $dispatcher) {
		$journal =& $request->getJournal();

		$allThemes =& PluginRegistry::loadCategory('themes');
		$journalThemes = array();
		foreach ($allThemes as $key => $junk) {
			$plugin =& $allThemes[$key]; // by ref
			$journalThemes[basename($plugin->getPluginPath())] =& $plugin;
			unset($plugin);
		}

		$templateMgr =& TemplateManager::getManager();

		$templateMgr->assign(array(
			'homeHeaderTitleImage' => $journal->getSetting('homeHeaderTitleImage'),
			'homeHeaderLogoImage'=> $journal->getSetting('homeHeaderLogoImage'),
			'journalThumbnail'=> $journal->getSetting('journalThumbnail'),
			'pageHeaderTitleImage' => $journal->getSetting('pageHeaderTitleImage'),
			'pageHeaderLogoImage' => $journal->getSetting('pageHeaderLogoImage'),
			'homepageImage' => $journal->getSetting('homepageImage'),
			'journalStyleSheet' => $journal->getSetting('journalStyleSheet'),
			'readerInformation' => $journal->getSetting('readerInformation'),
			'authorInformation' => $journal->getSetting('authorInformation'),
			'librarianInformation' => $journal->getSetting('librarianInformation'),
			'journalThemes' => $journalThemes,
			'journalFavicon' => $journal->getSetting('journalFavicon')
		));

		// Make lists of the sidebar blocks available.
		$leftBlockPlugins = $disabledBlockPlugins = $rightBlockPlugins = array();
		$plugins =& PluginRegistry::loadCategory('blocks');
		foreach ($plugins as $key => $junk) {
			if (!$plugins[$key]->getEnabled() || $plugins[$key]->getBlockContext() == '') {
				if (count(array_intersect($plugins[$key]->getSupportedContexts(), array(BLOCK_CONTEXT_LEFT_SIDEBAR, BLOCK_CONTEXT_RIGHT_SIDEBAR))) > 0) $disabledBlockPlugins[] =& $plugins[$key];
			} else switch ($plugins[$key]->getBlockContext()) {
				case BLOCK_CONTEXT_LEFT_SIDEBAR:
					$leftBlockPlugins[] =& $plugins[$key];
					break;
				case BLOCK_CONTEXT_RIGHT_SIDEBAR:
					$rightBlockPlugins[] =& $plugins[$key];
					break;
			}
		}
		$templateMgr->assign(array(
			'disabledBlockPlugins' => &$disabledBlockPlugins,
			'leftBlockPlugins' => &$leftBlockPlugins,
			'rightBlockPlugins' => &$rightBlockPlugins
		));

		$templateMgr->setCacheability(CACHEABILITY_MUST_REVALIDATE);
		parent::display($request, $dispatcher);
	}

	/**
	 * Uploads a journal image.
	 * @param $settingName string setting key associated with the file
	 * @param $locale string
	 */
	function uploadImage($settingName, $locale) {
		$journal =& Request::getJournal();
		$settingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
		$faviconTypes = array('.ico', '.png', '.gif');

		import('classes.file.PublicFileManager');
		$fileManager = new PublicFileManager();
		if ($fileManager->uploadedFileExists($settingName)) {
			$type = $fileManager->getUploadedFileType($settingName);
			$extension = $fileManager->getImageExtension($type);
			if (!$extension) {
				return false;
			}
			if ($settingName == 'journalFavicon' && !in_array($extension, $faviconTypes)) {
				return false;
			}

			$uploadName = $settingName . '_' . $locale . $extension;
			if ($fileManager->uploadJournalFile($journal->getId(), $settingName, $uploadName)) {
				// Get image dimensions
				$filePath = $fileManager->getJournalFilesPath($journal->getId());
				list($width, $height) = getimagesize($filePath . '/' . $uploadName);

				$value = $journal->getSetting($settingName);
				$newImage = empty($value[$locale]);

				$value[$locale] = array(
					'name' => $fileManager->getUploadedFileName($settingName, $locale),
					'uploadName' => $uploadName,
					'width' => $width,
					'height' => $height,
					'mimeType' => $fileManager->getUploadedFileType($settingName),
					'dateUploaded' => Core::getCurrentDate()
				);

				$journal->updateSetting($settingName, $value, 'object', true);

				if ($newImage) {
					$altText = $journal->getSetting($settingName.'AltText');
					if (!empty($altText[$locale])) {
						$this->setData($settingName.'AltText', $altText);
					}
				}

				return true;
			}
		}

		return false;
	}

	/**
	 * Deletes a journal image.
	 * @param $settingName string setting key associated with the file
	 * @param $locale string
	 */
	function deleteImage($settingName, $locale = null) {
		$journal =& Request::getJournal();
		$settingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
		$setting = $settingsDao->getSetting($journal->getId(), $settingName);

		import('classes.file.PublicFileManager');
		$fileManager = new PublicFileManager();
		if ($fileManager->removeJournalFile($journal->getId(), $locale !== null ? $setting[$locale]['uploadName'] : $setting['uploadName'] )) {
			$returner = $settingsDao->deleteSetting($journal->getId(), $settingName, $locale);
			// Ensure page header is refreshed
			if ($returner) {
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->assign(array(
					'displayPageHeaderTitle' => $journal->getLocalizedPageHeaderTitle(),
					'displayPageHeaderLogo' => $journal->getLocalizedPageHeaderLogo()
				));
			}
			return $returner;
		} else {
			return false;
		}
	}

	/**
	 * Uploads journal custom stylesheet.
	 * @param $settingName string setting key associated with the file
	 */
	function uploadStyleSheet($settingName) {
		$journal =& Request::getJournal();
		$settingsDao =& DAORegistry::getDAO('JournalSettingsDAO');

		import('classes.file.PublicFileManager');
		$fileManager = new PublicFileManager();
		if ($fileManager->uploadedFileExists($settingName)) {
			$type = $fileManager->getUploadedFileType($settingName);
			if ($type != 'text/css') {
				return false;
			}

			$uploadName = $settingName . '.css';
			if($fileManager->uploadJournalFile($journal->getId(), $settingName, $uploadName)) {
				$value = array(
					'name' => $fileManager->getUploadedFileName($settingName),
					'uploadName' => $uploadName,
					'dateUploaded' => Core::getCurrentDate()
				);

				$settingsDao->updateSetting($journal->getId(), $settingName, $value, 'object');
				return true;
			}
		}

		return false;
	}

	function execute() {
		// Save the block plugin layout settings.
		$blockVars = array('blockSelectLeft', 'blockUnselected', 'blockSelectRight');
		foreach ($blockVars as $varName) {
			$$varName = array_map('urldecode', split(' ', Request::getUserVar($varName)));
		}

		$plugins =& PluginRegistry::loadCategory('blocks');
		foreach ($plugins as $key => $junk) {
			$plugin =& $plugins[$key]; // Ref hack
			$plugin->setEnabled(!in_array($plugin->getName(), $blockUnselected));
			if (in_array($plugin->getName(), $blockSelectLeft)) {
				$plugin->setBlockContext(BLOCK_CONTEXT_LEFT_SIDEBAR);
				$plugin->setSeq(array_search($key, $blockSelectLeft));
			}
			else if (in_array($plugin->getName(), $blockSelectRight)) {
				$plugin->setBlockContext(BLOCK_CONTEXT_RIGHT_SIDEBAR);
				$plugin->setSeq(array_search($key, $blockSelectRight));
			}
			unset($plugin);
		}

		return parent::execute();
	}
}

?>
