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

	function display() {
		$templateMgr =& TemplateManager::getManager();
		$additionalHeadData = $templateMgr->get_template_vars('additionalHeadData');
		$additionalHeadData .= '<script type="text/javascript" src="' . Request::getBaseUrl() . '/plugins/themes/custom/picker.js"></script>' . "\n";
		$templateMgr->addStyleSheet(Request::getBaseUrl() . '/plugins/themes/custom/picker.css');
		$templateMgr->assign('additionalHeadData', $additionalHeadData);
		$stylesheetFileLocation = $this->plugin->getPluginPath() . '/' . $this->plugin->getStylesheetFilename();
		$templateMgr->assign('canSave', is_writable($stylesheetFileLocation));
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
			'customThemeForegroundColour' => $plugin->getSetting($journalId, 'customThemeForegroundColour')
		);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('customThemeHeaderColour', 'customThemeLinkColour', 'customThemeBackgroundColour', 'customThemeForegroundColour'));
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

		import('lib.pkp.classes.file.FileManager');
		$fileManager = new FileManager();
		$fileManager->writeFile(dirname(__FILE__) . '/custom.css', $css);
	}
}

?>
