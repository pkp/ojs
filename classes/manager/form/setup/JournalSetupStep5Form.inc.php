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
	
	function JournalSetupStep5Form() {
		parent::JournalSetupForm(
			5,
			array(
				'headerTitleType' => 'int',
				'journalHeaderTitle' => 'string',
				'navItems' => 'object',
				'pageHeaderTitleType' => 'int',
				'pageHeaderTitle' => 'string',
				'alternateHeader' => 'string',
				'journalPageFooter' => 'string',
				'additionalContent' => 'string',
				'journalDescription' => 'string'
			)
		);
	}
	
	/**
	  * Sets this form uploaded data from journal settings.
	  * @param $settingName string
	 */
	function display() {
		$journal = &Request::getJournal();
		$array = array(
			'journalHeaderTitleImage' => $journal->getSetting('journalHeaderTitleImage'),
			'journalHeaderLogoImage'=> $journal->getSetting('journalHeaderLogoImage'),
			'pageHeaderTitleImage' => $journal->getSetting('pageHeaderTitleImage'),
			'pageHeaderLogoImage' => $journal->getSetting('pageHeaderLogoImage'),
			'homepageImage' => $journal->getSetting('homepageImage'),
			'journalStyleSheet' => $journal->getSetting('journalStyleSheet') 
			);
	
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign($array);
		
		parent::display();	   
	}
	
	/**
	  * Updates $settingName in jounal settings and uploads corresponding image to upload directory
	  * @param $settingName string in journal_settings name field, upload file name
	 */
	function uploadImage($settingName) {
		$journal = &Request::getJournal();
		$publicFileManager = new PublicFileManager();
		$settingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
	
		if ($publicFileManager->uploadedFileExists($settingName))
		{
			$type = $_FILES[$settingName]['type'];
			
			if ($type == 'image/gif') {
				$extension = '.gif';
			}
			else if ($type == 'image/jpeg' || 'image/pjpeg') {
				$extension = '.jpeg';
			}
			else if ($type == 'image/png' || $type == 'image/x-png') {
				$extension = '.png';
			}
			else {
				return;
			}
		
		$uploadName = $settingName.$extension;
		$publicFileManager->uploadJournalFile($journal->getJournalId(), $settingName, $uploadName);
		
		//get imageSize for height and width of image
		$filePath = $publicFileManager->getJournalFilesPath($journal->getJournalId());
		$imageSize = getimagesize($filePath.'/'.$settingName.$extension);
		
		$value = array('name' => $_FILES[$settingName]['name'],
					'uploadName' => $uploadName,
					'dateUploaded' => Core::getCurrentDate());
		$settingsDao->updateSetting($journal->getJournalId(),
						$settingName,
						$value,
						'object');
	}
	}

	/**
	  * Deletes $settingName in jounal settings and corresponding image in upload directory
	  * @param $settingName string in journal_settings name field
	 */
	function deleteImage($settingName) {
		$journal = &Request::getJournal();
		$publicFileManager = new PublicFileManager();
		$settingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		$setting = $settingsDao->getSetting($journal->getJournalId(), $settingName);
		
	 	$publicFileManager->removeJournalFile($journal->getJournalId(), $setting['uploadName']);
		$settingsDao->deleteSetting($journal->getJournalId(), $settingName);
	}
	
	/**
	  * Updates $settingName in jounal settings and uploads corresponding .css file to upload directory
	  * @param $settingName string in journal_settings name field, upload file name
	 */
	function uploadStyleSheet($settingName) {
	$journal = &Request::getJournal();
	$publicFileManager = new PublicFileManager();
	$settingsDao = &DAORegistry::getDAO('JournalSettingsDAO');

	if ($publicFileManager->uploadedFileExists($settingName)) {
		$name = $_FILES[$settingName]['name'];
		
		if (String::regexp_match('@.*.css@', "$name")) {
			$extension = '.css';
		}
		else {
			return;
		}

		$uploadName = $settingName.$extension;
		$publicFileManager->uploadJournalFile($journal->getJournalId(), $settingName, $uploadName);
		
		$value = array('name' => $_FILES[$settingName]['name'],
					'uploadName' => $uploadName,
					'dateUploaded' => date("Y-m-d g:i:s"));
		$settingsDao->updateSetting($journal->getJournalId(),
						$settingName,
						$value,
						'object');
	}
	}
}

?>