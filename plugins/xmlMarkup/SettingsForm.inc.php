<?php

/**
 * @file plugins/generic/markup/SettingsForm.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SettingsForm
 * @ingroup plugins_generic_markup
 *
 * @brief Form for Document Markup gateway plugin settings
 */

import('lib.pkp.classes.form.Form');

class SettingsForm extends Form {

	/** @var $journalId int */
	var $journalId;

	/** @var $plugin object */
	var $plugin;

	var $pdfxServerURLDefault = 'http://pkp-udev.lib.sfu.ca/';// HARDCODED DEFAULT!!!
	var $cslStyleDefault = 'chicago-author-date.csl';
	var $cslStyleNameDefault = 'Chicago Manual of Style (author-date)';
	var $reviewVersionDefault = "yes";
	
	/**
	 * Constructor 
	 * @param $plugin object
	 * @param $journalId int
	 */
	function SettingsForm(&$plugin, $journalId) {
		$this->journalId = $journalId;
		$this->plugin =& $plugin;
		parent::Form($plugin->getTemplatePath() . 'settingsForm.tpl');
		$this->addCheck(new FormValidatorPost($this));
		
		// Every time settings form is displayed, ensure the plugins css/ subfolder's files are in place.
		$folderDefaultCss = dirname(__FILE__)."/css";
		$folderCss =  Config::getVar('files', 'files_dir') . '/journals/' . $journalId . '/css';
		@mkdir($folderCss); //Ensure folder is set up.
		$glob = glob($folderDefaultCss."/*.css");
		foreach ($glob as $g) {
			$file = $folderCss."/".basename($g);
			if (!file_exists($file)) copy($g, $file); //doesn't overwrite existing files
		}
		
	}

	/**
	 * Initialize plugin settings form data.
	 *
	 * @var cslStyle string holds the file name (including .csl suffix) of the selected csl style 
	 * @var cslStyleName string holds the plain english name of the above style
	 * @var cssFolder string is used to show links to the plugin's stylesheets for this journal
	 * @var cssHeaderImageName string holds name of banner image to appear at top of html and pdf articles.
	 * @var reviewVersion boolean indicates if a reviewer version of article (without author info) should be made
	 
	 * @var markupHostUser string (optional)
	 * @var markupHostPass string (optional) 	 
	 * @var markupHostURL string holds URL of document markup server (e.g. http://pkp-udev.lib.sfu.ca/ )
	 */
	function initData() {
		$journalId = $this->journalId;
		$plugin =& $this->plugin;

		// User must at least load settings page for plugin to work with defaults.
		if ($plugin->getSetting($journalId, 'cslStyle') == '') {
			$plugin->updateSetting($journalId, 'cslStyle', $this->cslStyleDefault);
			$plugin->updateSetting($journalId, 'cslStyleName', $this->cslStyleNameDefault);
		} 
		if ($plugin->getSetting($journalId, 'markupHostURL') == '') {
			$plugin->updateSetting($journalId, 'markupHostURL', $this->pdfxServerURLDefault );
		}
		/* How to get default value in here???
		if ($plugin->getSetting($journalId, 'reviewVersion') == null) {
			$plugin->updateSetting($journalId, 'reviewVersion', $this->reviewVersionDefault );
		}	
		*/

		$this->setData('cslStyle', $plugin->getSetting($journalId, 'cslStyle'));
		$this->setData('cslStyleName', $plugin->getSetting($journalId, 'cslStyleName'));
		
		$this->setData('cssFolder', Request::getJournal()->getUrl() . '/gateway/plugin/markup/css/');

		// This field has content only if header image actually exists in the right folder.
		$g = glob(Config::getVar('files', 'files_dir') . '/journals/' . $journalId . '/css/article_header.{jpg,png}',GLOB_BRACE);
		if (count($g)) {
			$this->setData('cssHeaderImageName', basename($g[0]));
		}
		
		$this->setData('markupHostUser', $plugin->getSetting($journalId, 'markupHostUser'));
		// Security note: Not sending markupHostPass to browser.
		
		$this->setData('reviewVersion', $plugin->getSetting($journalId, 'reviewVersion'));
		
		//User assigned but should never change (view only).
		$this->setData('markupHostURL', $plugin->getSetting($journalId, 'markupHostURL'));
		
	}

	
	/**
	 * Populate and display settings form.
	 *
	 * @var curlSupport indicates whether or not php curl has been installed
	 * @var zipSupport indicates whether or not zip library has been installed
	 */ 
	function display() {
		$templateMgr =& TemplateManager::getManager();
		// Signals indicating plugin compatibility		
		$templateMgr->assign('curlSupport', function_exists('curl_init') ? "Installed": "Not Installed");
		$templateMgr->assign('zipSupport', extension_loaded('zlib') ? "Installed": "Not Installed");
		
		//Plugin_Url not found????
		parent::display();
	}

	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('cslStyle','cslStyleName','markupHostURL','markupHostUser','markupHostPass','reviewVersion','cssHeaderImage'));
	}

	/**
	 * Save settings. 
	 */
	function execute() {
		$plugin =& $this->plugin;
		$journalId = $this->journalId;

		$plugin->updateSetting($journalId, 'cslStyle', $this->getData('cslStyle'));
		$plugin->updateSetting($journalId, 'cslStyleName', $this->getData('cslStyleName'));
		$plugin->updateSetting($journalId, 'markupHostURL', $this->getData('markupHostURL'));
		
		$markupHostUser = $this->getData('markupHostUser');
		$plugin->updateSetting($journalId, 'markupHostUser', $markupHostUser);

		if (strlen($markupHostUser) > 0) {
			$markupHostPass = $this->getData('markupHostPass');
			// Only update password if account exists and password exists.
			if (strlen($markupHostPass) > 0) {
				$plugin->updateSetting($journalId, 'markupHostPass', $markupHostPass);
			}
		}
		else {
			$plugin->updateSetting($journalId, 'markupHostPass','');
		}

		$plugin->updateSetting($journalId, 'reviewVersion', $this->getData('reviewVersion'));
		
		// Upload article header image if any given.
		if (isset($_FILES['cssHeaderImage'])) {

			import('classes.file.JournalFileManager');
			$journal =& Request::getJournal(); 
			$journalFileManager = new JournalFileManager($journal);		
			if ($journalFileManager->uploadedFileExists('cssHeaderImage') ) {
				// upload jpg or png
				$newFileName = $journalFileManager->getUploadedFileName('cssHeaderImage');
				if (preg_match("/(\.jpg|\.png)$/i", $newFileName))
					$journalFileManager->uploadFile('cssHeaderImage', "/css/article_header.png");
			}
		}
		
	}

}

?>
