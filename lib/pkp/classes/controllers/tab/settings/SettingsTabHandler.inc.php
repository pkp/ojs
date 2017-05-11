<?php

/**
 * @file classes/controllers/tab/settings/SettingsTabHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SettingsTabHandler
 * @ingroup classes_controllers_tab_settings
 *
 * @brief Handle AJAX operations for tabs on settings pages, under administration or management pages.
 */

// Import the base Handler.
import('classes.handler.Handler');
import('lib.pkp.classes.core.JSONMessage');

class SettingsTabHandler extends Handler {

	/** @var string */
	var $_currentTab;

	/** @var array */
	var $_pageTabs;


	/**
	 * Constructor
	 * @param $role string The role keys to be used in role assignment.
	 */
	function __construct($role) {
		parent::__construct();
		$this->addRoleAssignment(
			$role,
			array('saveFormData', 'showTab')
		);
	}

	//
	// Getters and Setters
	//
	/**
	 * Get the current tab name.
	 * @return string
	 */
	function getCurrentTab() {
		return $this->_currentTab;
	}

	/**
	 * Set the current tab name.
	 * @param $currentTab string
	 */
	function setCurrentTab($currentTab) {
		$this->_currentTab = $currentTab;
	}

	/**
	 * Get an array with current page tabs and its respective forms or templates.
	 * @return array
	 */
	function getPageTabs() {
		return $this->_pageTabs;
	}

	/**
	 * Set an array with current page tabs and its respective forms or templates.
	 * @param array
	 */
	function setPageTabs($pageTabs) {
		$this->_pageTabs = $pageTabs;
	}

	//
	// Extended methods from Handler
	//
	/**
	 * @see PKPHandler::initialize()
	 */
	function initialize($request) {
		$this->setCurrentTab($request->getUserVar('tab'));
	}

	//
	// Public handler methods
	//
	/**
	 * Show a tab.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function showTab($args, $request) {
		$this->setupTemplate($request);
		if ($this->_isValidTab()) {
			if ($this->_isTabTemplate()) {
				$this->setupTemplate($request, true);
				$templateMgr = TemplateManager::getManager($request);
				if ($this->_isManagementHandler()) {
					// Pass to template if we are in wizard mode.
					$templateMgr->assign('wizardMode', $this->getWizardMode());
				}
				$templateMgr->assign('canEdit', true);
				return $templateMgr->fetchJson($this->_getTabTemplate());
			} else {
				$tabForm = $this->getTabForm();
				$tabForm->initData($request);
				return new JSONMessage(true, $tabForm->fetch($request));
			}
		}
	}

	/**
	 * Handle forms data (save or edit).
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage JSON object
	 */
	function saveFormData($args, $request) {

		if ($this->_isValidTab()) {
			$tabForm = $this->getTabForm();

			// Try to save the form data.
			$tabForm->readInputData($request);
			$tabForm->addValidationChecks();
			if($tabForm->validate()) {
				$result = $tabForm->execute($request);
				if ($result !== false) {
					$notificationManager = new NotificationManager();
					$user = $request->getUser();
					$notificationManager->createTrivialNotification($user->getId());
				}
			} else {
				return new JSONMessage(true);
			}
		}
		return new JSONMessage();
	}

	/**
	 * Return an instance of the form based on the current tab.
	 * @return Form
	 */
	function getTabForm() {
		$currentTab = $this->getCurrentTab();
		$pageTabs = $this->getPageTabs();

		// Search for a form using the tab name.
		import($pageTabs[$currentTab]);
		$tabFormClassName = $this->_getFormClassName($pageTabs[$currentTab]);

		if ($this->_isManagementHandler()) {
			$tabForm = new $tabFormClassName($this->getWizardMode());
		} else {
			$tabForm = new $tabFormClassName();
		}

		assert(is_a($tabForm, 'Form'));

		return $tabForm;
	}


	//
	// Private helper methods.
	//
	/**
	 * Return the tab template file
	 * @return string
	 */
	function _getTabTemplate() {
		$currentTab = $this->getCurrentTab();
		$pageTabs = $this->getPageTabs();

		return $pageTabs[$currentTab];
	}

	/**
	 * Check if the current tab value exists in pageTabsAndForms array.
	 * @return boolean
	 */
	function _isValidTab() {
		if (array_key_exists($this->getCurrentTab(), $this->getPageTabs())) {
			return true;
		} else {
			assert(false);
			return false;
		}
	}

	/**
	 * Check if the tab use a template or not.
	 * @return boolean
	 */
	function _isTabTemplate() {
		$currentTab = $this->getCurrentTab();
		$pageTabs = $this->getPageTabs();

		return (strstr($pageTabs[$currentTab], '.tpl'));
	}

	/**
	 * Return the form class name based on the current tab name.
	 * @param $classPath string
	 * @return string
	 */
	function _getFormClassName($classPath) {
		$needle = '.form.';
		$formClassName = strstr($classPath, $needle);
		$formClassName = trim(str_replace($needle, ' ', $formClassName));
		return $formClassName;
	}

	/**
	 * Check if this handles management settings.
	 * @return boolean
	 */
	function _isManagementHandler() {
		return is_subclass_of($this, 'ManagerSettingsTabHandler');
	}
}

?>
