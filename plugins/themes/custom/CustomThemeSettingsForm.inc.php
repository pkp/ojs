<?php

/**
 * @file plugins/themes/custom/CustomThemeSettingsForm.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CustomThemeSettingsForm
 * @ingroup plugins_generic_customTheme
 *
 * @brief Form for journal managers to modify custom theme plugin settings
 */

import('lib.pkp.classes.form.Form');

class CustomThemeSettingsForm extends Form {

	/** @var $journalId int */
	var $journalId;

	/** @var $plugin object */
	var $plugin;

	/**
	 * Constructor
	 * @param $plugin object
	 * @param $journalId int
	 */
	function CustomThemeSettingsForm(&$plugin, $journalId) {
		$this->journalId = $journalId;
		$this->plugin =& $plugin;

		parent::Form($plugin->getTemplatePath() . 'settingsForm.tpl');
	}

	/**
	 * Display the form
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$additionalHeadData = $templateMgr->get_template_vars('additionalHeadData');
		$additionalHeadData .= '<script type="text/javascript" src="' . Request::getBaseUrl() . '/plugins/themes/custom/picker.js"></script>' . "\n";
		$templateMgr->addStyleSheet(Request::getBaseUrl() . '/plugins/themes/custom/picker.css');
		$templateMgr->assign('additionalHeadData', $additionalHeadData);
		$stylesheetFilePluginLocation = $this->plugin->getPluginPath() . '/' . $this->plugin->getStylesheetFilename();
		if (!$this->_canUsePluginPath() || $this->plugin->getSetting($this->journalId, 'customThemePerJournal')) {
			if (!$this->_canUsePluginPath()) {
				$templateMgr->assign('disablePluginPath', true);
				$templateMgr->assign('stylesheetFilePluginLocation', $stylesheetFilePluginLocation);
			}
			import('classes.file.PublicFileManager');
			$fileManager = new PublicFileManager();
			$stylesheetFileLocation = $fileManager->getJournalFilesPath($this->journalId) . '/' . $this->plugin->getStylesheetFilename();
		} else {
			$stylesheetFileLocation = $stylesheetFilePluginLocation;
		}
		$templateMgr->assign('canSave', $this->_is_writable($stylesheetFileLocation));
		$templateMgr->assign('stylesheetFileLocation', $stylesheetFileLocation);

		return parent::display();
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$journalId = $this->journalId;
		$plugin =& $this->plugin;

		$this->_data = array(
			'customThemeHeaderColour' => $plugin->getSetting($journalId, 'customThemeHeaderColour'),
			'customThemeLinkColour' => $plugin->getSetting($journalId, 'customThemeLinkColour'),
			'customThemeBackgroundColour' => $plugin->getSetting($journalId, 'customThemeBackgroundColour'),
			'customThemeForegroundColour' => $plugin->getSetting($journalId, 'customThemeForegroundColour'),
			'customThemePerJournal' => $plugin->getSetting($journalId, 'customThemePerJournal'),
		);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('customThemeHeaderColour', 'customThemeLinkColour', 'customThemeBackgroundColour', 'customThemeForegroundColour', 'customThemePerJournal'));
	}

	/**
	 * Save settings. 
	 */
	function execute() {
		$plugin =& $this->plugin;
		$journalId = $this->journalId;
		$css = '';

		// Header and footer colours
		$customThemeHeaderColour = $this->getData('customThemeHeaderColour');
		$plugin->updateSetting($journalId, 'customThemeHeaderColour', $customThemeHeaderColour, 'string');
		$css .= "#header {background-color: $customThemeHeaderColour;}\n";
		$css .= "#footer {background-color: $customThemeHeaderColour;}\n";
		$css .= "table.listing tr.fastTracked {background-color: $customThemeHeaderColour;}\n";

		// Link colours
		$customThemeLinkColour = $this->getData('customThemeLinkColour');
		$plugin->updateSetting($journalId, 'customThemeLinkColour', $customThemeLinkColour, 'string');
		$css .= "a {color: $customThemeLinkColour;}\n";
		$css .= "a:link {color: $customThemeLinkColour;}\n";
		$css .= "a:active {color: $customThemeLinkColour;}\n";
		$css .= "a:visited {color: $customThemeLinkColour;}\n";
		$css .= "a:hover {color: $customThemeLinkColour;}\n";
		$css .= "input.defaultButton {color: $customThemeLinkColour;}\n";

		// Background colours
		$customThemeBackgroundColour = $this->getData('customThemeBackgroundColour');
		$plugin->updateSetting($journalId, 'customThemeBackgroundColour', $customThemeBackgroundColour, 'string');
		$css .= "body {background-color: $customThemeBackgroundColour;}\n";
		$css .= "input.defaultButton {background-color: $customThemeBackgroundColour;}\n";

		// Foreground colours
		$customThemeForegroundColour = $this->getData('customThemeForegroundColour');
		$plugin->updateSetting($journalId, 'customThemeForegroundColour', $customThemeForegroundColour, 'string');
		$css .= "body {color: $customThemeForegroundColour;}\n";
		$css .= "input.defaultButton {color: $customThemeForegroundColour;}\n";

		import('classes.file.PublicFileManager');
		$fileManager = new PublicFileManager();
		$customThemePerJournal = $this->getData('customThemePerJournal');
		if (!$customThemePerJournal && !$this->_canUsePluginPath()) {
			$customThemePerJournal = true;
		}
		$plugin->updateSetting($journalId, 'customThemePerJournal', $customThemePerJournal, 'boolean');
		if ($customThemePerJournal) {
			$fileManager->writeJournalFile($journalId, $this->plugin->getStylesheetFilename(), $css);
		} else {
			$fileManager->writeFile(dirname(__FILE__) . '/' . $this->plugin->getStylesheetFilename(), $css);
		}
	}
	
	/**
	 * Evaluate whether the plugin path is writable and available for use
	 */
	function _canUsePluginPath() {
		return is_writable($this->plugin->getPluginPath() . '/' . $this->plugin->getStylesheetFilename());
	}
	
	/**
	 * Evaluate whether a path is writable
	 * Check if the filename provided (or the parent directory, if the filename does not exist) can be written
	 */
	function _is_writable($filename) {
		if (is_writable($filename)) {
			return true;
		} elseif (!file_exists($filename) && is_writable(dirname($filename))) {
			return true;
		}
		return false;
	}
}

?>
