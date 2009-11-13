<?php

/**
 * @file plugins/generic/jquery/JQueryPlugin.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JQueryPlugin
 * @ingroup plugins_generic_jquery
 *
 * @brief Plugin to allow jQuery scripts to be added to OJS
 */

// $Id$


import('classes.plugins.GenericPlugin');

define('JQUERY_INSTALL_PATH', 'lib' . DIRECTORY_SEPARATOR . 'pkp' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'jquery');
define('JQUERY_JS_PATH', JQUERY_INSTALL_PATH . DIRECTORY_SEPARATOR . 'jquery-1.2.6.min.js');
define('JQUERY_SCRIPTS_DIR', 'plugins' . DIRECTORY_SEPARATOR . 'generic' . DIRECTORY_SEPARATOR . 'jquery' . DIRECTORY_SEPARATOR . 'scripts');

class JQueryPlugin extends GenericPlugin {
	/**
	 * Register the plugin, if enabled; note that this plugin
	 * runs under both Journal and Site contexts.
	 * @param $category string
	 * @param $path string
	 * @return boolean
	 */
	function register($category, $path) {
		if (parent::register($category, $path)) {
			$this->addLocaleData();
			if ($this->isJQueryInstalled() && $this->getEnabled()) {
				HookRegistry::register('TemplateManager::display',array(&$this, 'callback'));
			}
			return true;
		}
		return false;
	}

	/**
	 * Get the name of the settings file to be installed on new journal
	 * creation.
	 * @return string
	 */
	function getNewJournalPluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Get the name of the settings file to be installed site-wide when
	 * OJS is installed.
	 * @return string
	 */
	function getInstallSitePluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}
	
	/**
	 * Get the URL for the jQuery script
	 * @return string
	 */
	function getScriptPath() {
		return Request::getBaseUrl() . DIRECTORY_SEPARATOR . JQUERY_JS_PATH;
	}
	
	/**
	 * Given a $page and $op, return a list of scripts that should be loaded
	 * @param $page string The requested page
	 * @param $op string The requested operation
	 * @return array
	 */
	function getEnabledScripts($page, $op) {
		$scripts = array();
		switch ("$page/$op") {
			case 'editor/submissions':
				$scripts[] = 'submissionSearch.js';
				break;
			case 'editor/submission':
			case 'editor/submissionReview':
			case 'editor/submissionEditing':
			case 'editor/submissionHistory':
			case 'editor/submissionEventLog':
			case 'editor/submissionEmailLog':
			case 'editor/submissionNotes':
			case 'editor/viewMetadata':
			case 'editor/editComment':
				//$scripts[] = 'editorDivs.js';
				break;
			case 'manager/setup':
				//$scripts[] = 'dragLists.js';
				break;
			case 'admin/journals':
			case 'editor/backIssues':
			case 'manager/groupMembership':
			case 'manager/groups':
			case 'manager/reviewFormElements':
			case 'manager/reviewForms':
			case 'manager/sections':
			case 'manager/subscriptionTypes':
			case 'rtadmin/contexts':
			case 'rtadmin/searches':
			case 'subscriptionManager/subscriptionTypes':
				$scripts[] = 'jquery.tablednd_0_5.js';
				$scripts[] = 'tablednd.js';
				break;

		}
		return $scripts;
	}

	/**
	 * Hook callback function for TemplateManager::display
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function callback($hookName, $args) {
		$page = Request::getRequestedPage();
		$op = Request::getRequestedOp();
		if(!$scripts = JQueryPlugin::getEnabledScripts($page, $op)) return null;
		
		$templateManager =& $args[0];
		$additionalHeadData = $templateManager->get_template_vars('additionalHeadData');
		$baseUrl = $templateManager->get_template_vars('baseUrl');
		
		$jQueryScript = 
		'	<script language="javascript" type="text/javascript" src="' . $this->getScriptPath() . '"></script>'
		. "\n" . JQueryPlugin::addScripts($baseUrl, $scripts);

		$templateManager->assign('additionalHeadData', $additionalHeadData."\n".$jQueryScript);
	}
	
	/**
	 * Add scripts contained in scripts/ subdirectory to a string to be returned to callback func.
	 * @param baseUrl string
	 * @param scripts array All enabled scripts for this page
	 * @return string
	 */
	function addScripts($baseUrl, $scripts) {
		$scriptOpen = '	<script language="javascript" type="text/javascript" src="';
		$scriptClose = '"></script>';
		$returner = '';
		
		foreach ($scripts as $script) {
			if(file_exists(Core::getBaseDir() . DIRECTORY_SEPARATOR . JQUERY_SCRIPTS_DIR . DIRECTORY_SEPARATOR . $script)) {
				$returner .= $scriptOpen . $baseUrl . DIRECTORY_SEPARATOR . JQUERY_SCRIPTS_DIR . DIRECTORY_SEPARATOR . $script . $scriptClose . "\n";
			}
		}
		return $returner;
	}

	/**
	 * Get the symbolic name of this plugin
	 * @return string
	 */
	function getName() {
		return 'JQueryPlugin';
	}

	/**
	 * Get the display name of this plugin
	 * @return string
	 */
	function getDisplayName() {
		return Locale::translate('plugins.generic.jquery.name');
	}

	/**
	 * Get the description of this plugin
	 * @return string
	 */
	function getDescription() {
		if ($this->isJQueryInstalled()) return Locale::translate('plugins.generic.jquery.description');
		return Locale::translate('plugins.generic.jquery.descriptionDisabled', array('jQueryPath' => JQUERY_INSTALL_PATH));
	}

	/**
	 * Check whether or not the JQuery library is installed
	 * @return boolean
	 */
	function isJQueryInstalled() {
		return file_exists(JQUERY_JS_PATH);
	}

	/**
	 * Check whether or not this plugin is enabled
	 * @return boolean
	 */
	function getEnabled() {
		$journal =& Request::getJournal();
		$journalId = $journal?$journal->getJournalId():0;
		return $this->getSetting($journalId, 'enabled');
	}

	/**
	 * Get a list of available management verbs for this plugin
	 * @return array
	 */
	function getManagementVerbs() {
		$verbs = array();
		if ($this->isJQueryInstalled()) $verbs[] = array(
			($this->getEnabled()?'disable':'enable'),
			Locale::translate($this->getEnabled()?'manager.plugins.disable':'manager.plugins.enable')
		);
		return $verbs;
	}

	/**
	 * Execute a management verb on this plugin
	 * @param $verb string
	 * @param $args array
	 * @return boolean
	 */
	function manage($verb, $args, &$message) {
		$journal =& Request::getJournal();
		$journalId = $journal?$journal->getJournalId():0;
		switch ($verb) {
			case 'enable':
				$this->updateSetting($journalId, 'enabled', true);
				$message = Locale::translate('plugins.generic.jquery.enabled');
				break;
			case 'disable':
				$this->updateSetting($journalId, 'enabled', false);
				$message = Locale::translate('plugins.generic.jquery.disabled');
				break;
		}
		return false;
	}
}
?>
