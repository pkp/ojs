<?php

/**
 * @file plugins/generic/externalFeed/ExternalFeedSettingsForm.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ExternalFeedSettingsForm
 * @ingroup plugins_generic_externalFeed
 *
 * @brief Form for journal managers to modify External Feed plugin settings
 */

import('lib.pkp.classes.form.Form');

class ExternalFeedSettingsForm extends Form {

	/** @var $journalId int */
	var $journalId;

	/** @var $plugin object */
	var $plugin;

	/**
	 * Constructor
	 * @param $plugin object
	 * @param $journalId int
	 */
	function ExternalFeedSettingsForm(&$plugin, $journalId) {
		$this->journalId = $journalId;
		$this->plugin =& $plugin;

		parent::Form($plugin->getTemplatePath() . 'settingsForm.tpl');

		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$journalId = $this->journalId;
		$plugin =& $this->plugin;

		$this->_data = array(
			'externalFeedStyleSheet' => $plugin->getSetting($journalId, 'externalFeedStyleSheet')
		);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('externalFeedStyleSheet'));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$journalId = $this->journalId;
		$plugin =& $this->plugin;

		// Ensure upload file settings are reloaded when the form is displayed.
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('journalStyleSheet', $plugin->getSetting($journalId, 'externalFeedStyleSheet'));
		$templateMgr->assign('defaultStyleSheetUrl', Request::getBaseUrl() . '/' . $plugin->getDefaultStyleSheetFile());

		parent::display();
	}

	/**
	 * Uploads custom stylesheet.
	 */
	function uploadStyleSheet() {
		$journalId = $this->journalId;
		$plugin =& $this->plugin;
		$settingName = 'externalFeedStyleSheet';

		import('classes.file.PublicFileManager');
		$fileManager = new PublicFileManager();

		if ($fileManager->uploadedFileExists($settingName)) {
			$type = $fileManager->getUploadedFileType($settingName);
			if ($type != 'text/plain' && $type != 'text/css') {
				return false;
			}

			$uploadName = $plugin->getPluginPath() . '/' . $settingName . '.css';
			if($fileManager->uploadJournalFile($journalId, $settingName, $uploadName)) {			
				$value = array(
					'name' => $fileManager->getUploadedFileName($settingName),
					'uploadName' => $uploadName,
					'dateUploaded' => Core::getCurrentDate()
				);

				$plugin->updateSetting($journalId, $settingName, $value, 'object');
				return true;
			}
		}

		return false;
	}

	/**
	 * Deletes a custom stylesheet.
	 */
	function deleteStyleSheet() {
		$journalId = $this->journalId;
		$plugin =& $this->plugin;
		$settingName = 'externalFeedStyleSheet';

		$setting = $plugin->getSetting($journalId, $settingName);

		import('classes.file.PublicFileManager');
		$fileManager = new PublicFileManager();

		if ($fileManager->removeJournalFile($journalId, $setting['uploadName'])) {
			$plugin->updateSetting($journalId, $settingName, null);
			return true;
		} else {
			return false;
		}
	}
}

?>
