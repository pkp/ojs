<?php

/**
 * JournalSetupStep5Form.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form.setup
 *
 * Form for Step 5 of journal setup.
 *
 * $Id$
 */

import("manager.form.setup.JournalSetupForm");

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
				'homeHeaderTitleTypeAlt1' => 'int',
				'homeHeaderTitleAlt1' => 'string',
				'homeHeaderTitleTypeAlt2' => 'int',
				'homeHeaderTitleAlt2' => 'string',
				'pageHeaderTitleType' => 'int',
				'pageHeaderTitle' => 'string',
				'pageHeaderTitleTypeAlt1' => 'int',
				'pageHeaderTitleAlt1' => 'string',
				'pageHeaderTitleTypeAlt2' => 'int',
				'pageHeaderTitleAlt2' => 'string',
				'readerInformation' => 'string',
				'authorInformation' => 'string',
				'librarianInformation' => 'string',
				'journalPageHeader' => 'string',
				'journalPageFooter' => 'string',
				'displayCurrentIssue' => 'bool',
				'additionalHomeContent' => 'string',
				'journalDescription' => 'string',
				'navItems' => 'object'
			)
		);
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$journal = &Request::getJournal();

		// Ensure upload file settings are reloaded when the form is displayed.
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign(array(
			'homeHeaderTitleImage' => $journal->getSetting('homeHeaderTitleImage'),
			'homeHeaderLogoImage'=> $journal->getSetting('homeHeaderLogoImage'),
			'homeHeaderTitleImageAlt1' => $journal->getSetting('homeHeaderTitleImageAlt1'),
			'homeHeaderLogoImageAlt1'=> $journal->getSetting('homeHeaderLogoImageAlt1'),
			'homeHeaderTitleImageAlt2' => $journal->getSetting('homeHeaderTitleImageAlt2'),
			'homeHeaderLogoImageAlt2'=> $journal->getSetting('homeHeaderLogoImageAlt2'),
			'pageHeaderTitleImage' => $journal->getSetting('pageHeaderTitleImage'),
			'pageHeaderLogoImage' => $journal->getSetting('pageHeaderLogoImage'),
			'pageHeaderTitleImageAlt1' => $journal->getSetting('pageHeaderTitleImageAlt1'),
			'pageHeaderLogoImageAlt1' => $journal->getSetting('pageHeaderLogoImageAlt1'),
			'pageHeaderTitleImageAlt2' => $journal->getSetting('pageHeaderTitleImageAlt2'),
			'pageHeaderLogoImageAlt2' => $journal->getSetting('pageHeaderLogoImageAlt2'),
			'homepageImage' => $journal->getSetting('homepageImage'),
			'journalStyleSheet' => $journal->getSetting('journalStyleSheet'),
			'readerInformation' => $journal->getSetting('readerInformation'),
			'authorInformation' => $journal->getSetting('authorInformation'),
			'librarianInformation' => $journal->getSetting('librarianInformation')
		));
		
		parent::display();	   
	}
	
	/**
	 * Uploads a journal image.
	 * @param $settingName string setting key associated with the file
	 */
	function uploadImage($settingName) {
		$journal = &Request::getJournal();
		$settingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		
		import('file.PublicFileManager');
		$fileManager = &new PublicFileManager();
		if ($fileManager->uploadedFileExists($settingName)) {
			$type = $fileManager->getUploadedFileType($settingName);
			$extension = $fileManager->getImageExtension($type);
			if (!$extension) {
				return false;
			}
			
			$uploadName = $settingName . $extension;
			if ($fileManager->uploadJournalFile($journal->getJournalId(), $settingName, $uploadName)) {
				// Get image dimensions
				$filePath = $fileManager->getJournalFilesPath($journal->getJournalId());
				list($width, $height) = getimagesize($filePath . '/' . $settingName.$extension);
				
				$value = array(
					'name' => $fileManager->getUploadedFileName($settingName),
					'uploadName' => $uploadName,
					'width' => $width,
					'height' => $height,
					'dateUploaded' => Core::getCurrentDate()
				);
				
				return $settingsDao->updateSetting($journal->getJournalId(), $settingName, $value, 'object');
			}
		}
		
		return false;
	}

	/**
	 * Deletes a journal image.
	 * @param $settingName string setting key associated with the file
	 */
	function deleteImage($settingName) {
		$journal = &Request::getJournal();
		$settingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		$setting = $settingsDao->getSetting($journal->getJournalId(), $settingName);
		
		import('file.PublicFileManager');
		$fileManager = &new PublicFileManager();
	 	if ($fileManager->removeJournalFile($journal->getJournalId(), $setting['uploadName'])) {
			return $settingsDao->deleteSetting($journal->getJournalId(), $settingName);
		} else {
			return false;
		}
	}
	
	/**
	 * Uploads journal custom stylesheet.
	 * @param $settingName string setting key associated with the file
	 */
	function uploadStyleSheet($settingName) {
		$journal = &Request::getJournal();
		$settingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
	
		import('file.PublicFileManager');
		$fileManager = &new PublicFileManager();
		if ($fileManager->uploadedFileExists($settingName)) {
			$type = $fileManager->getUploadedFileType($settingName);
			if ($type != 'text/plain' && $type != 'text/css') {
				return false;
			}
	
			$uploadName = $settingName . '.css';
			if($fileManager->uploadJournalFile($journal->getJournalId(), $settingName, $uploadName)) {			
				$value = array(
					'name' => $fileManager->getUploadedFileName($settingName),
					'uploadName' => $uploadName,
					'dateUploaded' => date("Y-m-d g:i:s")
				);
				
				return $settingsDao->updateSetting($journal->getJournalId(), $settingName, $value, 'object');
			}
		}
		
		return false;
	}
	
}

?>
