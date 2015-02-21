<?php

/**
 * @defgroup security_form
 */

/**
 * @file classes/security/form/AuthSourceSettingsForm.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthSourceSettingsForm
 * @ingroup security_form
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
	function AuthSourceSettingsForm($authId) {
		parent::Form('admin/auth/sourceSettings.tpl');
		$this->addCheck(new FormValidatorPost($this));
		$this->authId = $authId;
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('authId', $this->authId);
		$templateMgr->assign('helpTopicId', 'site.siteManagement');

		if (isset($this->plugin)) {
			$this->plugin->addLocaleData();
			$templateMgr->assign('pluginTemplate', $this->plugin->getSettingsTemplate());
		}

		parent::display();
	}

	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		$authDao =& DAORegistry::getDAO('AuthSourceDAO');
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
		$authDao =& DAORegistry::getDAO('AuthSourceDAO');

		$auth = $authDao->newDataObject();
		$auth->setAuthId($this->authId);
		$auth->setTitle($this->getData('title'));
		$auth->setSettings($this->getData('settings'));

		$authDao->updateObject($auth);
	}
}

?>
