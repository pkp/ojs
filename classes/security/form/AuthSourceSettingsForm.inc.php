<?php

/**
 * @file classes/security/form/AuthSourceSettingsForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthSourceSettingsForm
 * @ingroup security
 * @see AuthSource, AuthSourceDAO
 *
 * @brief Form for editing authentication source settings.
 */

import('lib.pkp.classes.form.Form');

class AuthSourceSettingsForm extends Form {

	/** The ID of the source being edited */
	var $authId;

	/** The associated plugin */
	var $plugin;

	/**
	 * Constructor.
	 * @param $authId int
	 */
	function __construct($authId) {
		parent::__construct('admin/auth/sourceSettings.tpl');
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
		$this->authId = $authId;
	}

	/**
	 * @copydoc Form::display
	 */
	function display($request = null, $template = null) {
		$templateMgr = TemplateManager::getManager();
		$templateMgr->assign('authId', $this->authId);

		if (isset($this->plugin)) {
			$this->plugin->addLocaleData();
			$templateMgr->assign('pluginTemplate', $this->plugin->getSettingsTemplate());
		}

		parent::display($request, $template);
	}

	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		$authDao = DAORegistry::getDAO('AuthSourceDAO');
		$auth =& $authDao->getSource($this->authId);

		if ($auth != null) {
			$this->_data = array(
				'plugin' => $auth->getPlugin(),
				'title' => $auth->getTitle(),
				'settings' => $auth->getSettings()
			);
			$this->plugin =& $auth->getPluginClass();
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('title', 'settings'));
	}

	/**
	 * Save journal settings.
	 */
	function execute() {
		$authDao = DAORegistry::getDAO('AuthSourceDAO');

		$auth = $authDao->newDataObject();
		$auth->setAuthId($this->authId);
		$auth->setTitle($this->getData('title'));
		$auth->setSettings($this->getData('settings'));

		$authDao->updateObject($auth);
	}
}


