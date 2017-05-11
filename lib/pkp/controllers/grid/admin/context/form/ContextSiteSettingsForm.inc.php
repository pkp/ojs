<?php

/**
 * @file controllers/grid/admin/context/form/ContextSiteSettingsForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ContextSiteSettingsForm
 * @ingroup controllers_grid_admin_context_form
 *
 * @brief Form for site administrator to edit basic context settings.
 */

import('lib.pkp.classes.db.DBDataXMLParser');
import('lib.pkp.classes.form.Form');

class ContextSiteSettingsForm extends Form {

	/** The ID of the context being edited */
	var $contextId;

	/**
	 * Constructor.
	 * @param $contextId omit for a new context
	 */
	function __construct($contextId = null) {
		parent::__construct('admin/contextSettings.tpl');

		$this->contextId = isset($contextId) ? (int) $contextId : null;

		// Validation checks for this form
		$this->addCheck(new FormValidatorLocale($this, 'name', 'required', 'admin.contexts.form.titleRequired'));
		$this->addCheck(new FormValidator($this, 'path', 'required', 'admin.contexts.form.pathRequired'));
		$this->addCheck(new FormValidatorRegExp($this, 'path', 'required', 'admin.contexts.form.pathAlphaNumeric', '/^[a-z0-9]+([\-_][a-z0-9]+)*$/i'));
		$this->addCheck(new FormValidatorCustom($this, 'path', 'required', 'admin.contexts.form.pathExists', create_function('$path,$form,$contextDao', 'return !$contextDao->existsByPath($path) || ($form->getData(\'oldPath\') != null && $form->getData(\'oldPath\') == $path);'), array(&$this, Application::getContextDAO())));
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	/**
	 * Fetch the form.
	 * @param $request PKPRequest
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('contextId', $this->contextId);
		return parent::fetch($request);
	}

	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		if (isset($this->contextId)) {
			$contextDao = Application::getContextDAO();
			$context = $contextDao->getById($this->contextId);

			$this->setData('name', $context->getName(null));
			$this->setData('description', $context->getDescription(null));
			$this->setData('path', $context->getPath());
			$this->setData('enabled', $context->getEnabled());
		} else {
			$this->setData('enabled', 1);
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('name', 'description', 'path', 'enabled'));

		if ($this->contextId) {
			$contextDao = Application::getContextDAO();
			$context = $contextDao->getById($this->contextId);
			if ($context) $this->setData('oldPath', $context->getPath());
		}
	}

	/**
	 * Get a list of field names for which localized settings are used
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('name', 'description');
	}

	/**
	 * Initially populate the user groups and assignments when creating a new context.
	 * @param $contextId int
	 */
	function _loadDefaultUserGroups($contextId) {
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_DEFAULT, LOCALE_COMPONENT_PKP_DEFAULT);
		// Install default user groups
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$userGroupDao->installSettings($contextId, 'registry/userGroups.xml');
	}

	/**
	 * Make the site administrator the manager of the newly created context.
	 * @param $contextId int
	 */
	function _assignManagerGroup($contextId) {
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$sessionManager = SessionManager::getManager();
		$userSession = $sessionManager->getUserSession();
		if ($userSession->getUserId() != null && $userSession->getUserId() != 0 && !empty($contextId)) {
			// get the default site admin user group
			$managerUserGroup = $userGroupDao->getDefaultByRoleId($contextId, ROLE_ID_MANAGER);
			$userGroupDao->assignUserToGroup($userSession->getUserId(), $managerUserGroup->getId());
		}
	}
}

?>
