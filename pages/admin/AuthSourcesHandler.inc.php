<?php

/**
 * @file AuthSourcesHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.admin
 * @class AuthSourcesHandler
 *
 * Handle requests for authentication source management in site administration. 
 *
 * $Id$
 */

import('plugins.AuthPlugin');
import('security.AuthSourceDAO');

class AuthSourcesHandler extends AdminHandler {

	/**
	 * Display a list of authentication sources.
	 */
	function auth() {
		parent::validate();
		parent::setupTemplate(true);

		$authDao = &DAORegistry::getDAO('AuthSourceDAO');
		$sources = &$authDao->getSources();

		$plugins = &PluginRegistry::loadCategory(AUTH_PLUGIN_CATEGORY);
		$pluginOptions = array();
		foreach ($plugins as $plugin) {
			$pluginOptions[$plugin->getName()] = $plugin->getDisplayName();
		}

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign_by_ref('sources', $sources);
		$templateMgr->assign('pluginOptions', $pluginOptions);
		$templateMgr->assign('helpTopicId', 'site.siteManagement');
		$templateMgr->display('admin/auth/sources.tpl');
	}

	/**
	 * Update the default authentication source.
	 */
	function updateAuthSources() {
		parent::validate();

		$authDao = &DAORegistry::getDAO('AuthSourceDAO');
		$authDao->setDefault((int) Request::getUserVar('defaultAuthId'));

		Request::redirect(null, null, 'auth');
	}

	/**
	 * Create an authentication source.
	 */
	function createAuthSource() {
		parent::validate();

		$auth = &new AuthSource();
		$auth->setPlugin(Request::getUserVar('plugin'));

		$authDao = &DAORegistry::getDAO('AuthSourceDAO');
		if ($authDao->insertSource($auth)) {
			Request::redirect(null, null, 'editAuthSource', $auth->getAuthId());
		} else {
			Request::redirect(null, null, 'auth');
		}
	}

	/**
	 * Display form to edit an authentication source.
	 */
	function editAuthSource($args) {
		parent::validate();
		parent::setupTemplate(true);

		import('security.form.AuthSourceSettingsForm');
		$form = &new AuthSourceSettingsForm((int)@$args[0]);
		$form->initData();
		$form->display();
	}

	/**
	 * Update an authentication source.
	 */
	function updateAuthSource($args) {
		parent::validate();

		import('security.form.AuthSourceSettingsForm');
		$form = &new AuthSourceSettingsForm((int)@$args[0]);
		$form->readInputData();
		$form->execute();
		Request::redirect(null, null, 'auth');
	}

	/**
	 * Delete an authentication source.
	 */
	function deleteAuthSource($args) {
		parent::validate();

		$authId = (int)@$args[0];
		$authDao = &DAORegistry::getDAO('AuthSourceDAO');
		$authDao->deleteSource($authId);
		Request::redirect(null, null, 'auth');
	}

}

?>
