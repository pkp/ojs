<?php

/**
 * @file pages/admin/AuthSourcesHandler.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthSourcesHandler
 * @ingroup pages_admin
 *
 * @brief Handle requests for authentication source management in site administration. 
 */

import('classes.plugins.AuthPlugin');
import('lib.pkp.classes.security.AuthSourceDAO');
import('pages.admin.AdminHandler');

class AuthSourcesHandler extends AdminHandler {
	/**
	 * Constructor
	 */
	function AuthSourcesHandler() {
		parent::AdminHandler();
	}

	/**
	 * Display a list of authentication sources.
	 */
	function auth($args, &$request) {
		$this->validate();
		$this->setupTemplate(true);

		$authDao =& DAORegistry::getDAO('AuthSourceDAO');
		$sources =& $authDao->getSources();

		$plugins =& PluginRegistry::loadCategory(AUTH_PLUGIN_CATEGORY);
		$pluginOptions = array();
		foreach ($plugins as $plugin) {
			$pluginOptions[$plugin->getName()] = $plugin->getDisplayName();
		}

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('sources', $sources);
		$templateMgr->assign('pluginOptions', $pluginOptions);
		$templateMgr->assign('helpTopicId', 'site.siteManagement');
		$templateMgr->display('admin/auth/sources.tpl');
	}

	/**
	 * Update the default authentication source.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function updateAuthSources($args, &$request) {
		$this->validate();

		$authDao =& DAORegistry::getDAO('AuthSourceDAO');
		$authDao->setDefault((int) $request->getUserVar('defaultAuthId'));

		$request->redirect(null, null, 'auth');
	}

	/**
	 * Create an authentication source.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function createAuthSource($args, &$request) {
		$this->validate();

		$authDao =& DAORegistry::getDAO('AuthSourceDAO');
		$auth = $authDao->newDataObject();
		$auth->setPlugin($request->getUserVar('plugin'));

		if ($authDao->insertSource($auth)) {
			$request->redirect(null, null, 'editAuthSource', $auth->getAuthId());
		} else {
			$request->redirect(null, null, 'auth');
		}
	}

	/**
	 * Display form to edit an authentication source.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function editAuthSource($args, &$request) {
		$this->validate();
		$this->setupTemplate(true);

		import('classes.security.form.AuthSourceSettingsForm');
		$form = new AuthSourceSettingsForm((int)array_shift($args));
		$form->initData();
		$form->display();
	}

	/**
	 * Update an authentication source.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function updateAuthSource($args, &$request) {
		$this->validate();

		import('classes.security.form.AuthSourceSettingsForm');
		$form = new AuthSourceSettingsForm((int)array_shift($args));
		$form->readInputData();
		$form->execute();
		$request->redirect(null, null, 'auth');
	}

	/**
	 * Delete an authentication source.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function deleteAuthSource($args, &$request) {
		$this->validate();

		$authId = (int) array_shift($args);
		$authDao =& DAORegistry::getDAO('AuthSourceDAO');
		$authDao->deleteObject($authId);
		$request->redirect(null, null, 'auth');
	}
}

?>
