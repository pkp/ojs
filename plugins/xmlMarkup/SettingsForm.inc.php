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

	var $pdfxServerURLDefault = 'http://pkp-udev.lib.sfu.ca/';//HARDCODED DEFAULT!!!
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
	 * Initialize form data.
	 * .cslStyle holds the basic name (without .csl suffix) of the selected csl style 
	 * .cslStyleName holds the plain english name of the style
	 * .markupHostURL holds URL of pdfx server (e.g. DEVELOPMENT SERVER:  http://pkp-udev.lib.sfu.ca/ )
	 * .curlSupport indicates whether or not php curl has been installed
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
		$this->setData('markupHostPass', $plugin->getSetting($journalId, 'markupHostPass'));
		
		$this->setData('reviewVersion', $plugin->getSetting($journalId, 'reviewVersion'));
		
		//User assigned but should never change (view only).
		$this->setData('markupHostURL', $plugin->getSetting($journalId, 'markupHostURL'));
		
/*
		// Signals indicating plugin compatibility		
		$this->setData('curlSupport', function_exists('curl_init') ? "Installed": "Not Installed");
		$this->setData('zipSupport', extension_loaded('zlib') ? "Installed": "Not Installed");
*/
	}

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
		$plugin->updateSetting($journalId, 'markupHostUser', $this->getData('markupHostUser'));
		$plugin->updateSetting($journalId, 'markupHostPass', $this->getData('markupHostPass'));		
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
