<?php

/**
 * @file AuthSourcesHandler.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthSourcesHandler
 * @ingroup pages_admin
 *
 * @brief Handle requests for authentication source management in site administration. 
 */

// $Id$

import('classes.plugins.AuthPlugin');
import('lib.pkp.classes.security.AuthSourceDAO');
import('pages.admin.AdminHandler');

class AuthSourcesHandler extends AdminHandler {
	/**
	 * Constructor
	 **/
	function AuthSourcesHandler() {
		parent::AdminHandler();
	}

	/**
	 * Display a list of authentication sources.
	 */
	function auth() {
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
	 */
	function updateAuthSources() {
		$this->validate();

		$authDao =& DAORegistry::getDAO('AuthSourceDAO');
		$authDao->setDefault((int) Request::getUserVar('defaultAuthId'));

		Request::redirect(null, null, 'auth');
	}

	/**
	 * Create an authentication source.
	 */
	function createAuthSource() {
		$this->validate();

		$auth = new AuthSource();
		$auth->setPlugin(Request::getUserVar('plugin'));

		$authDao =& DAORegistry::getDAO('AuthSourceDAO');
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
		$this->validate();
		$this->setupTemplate(true);

		import('classes.security.form.AuthSourceSettingsForm');
		$form = new AuthSourceSettingsForm((int)@$args[0]);
		$form->initData();
		$form->display();
	}

	/**
	 * Update an authentication source.
	 */
	function updateAuthSource($args) {
		$this->validate();

		import('classes.security.form.AuthSourceSettingsForm');
		$form = new AuthSourceSettingsForm((int)@$args[0]);
		$form->readInputData();
		$form->execute();
		Request::redirect(null, null, 'auth');
	}

	/**
	 * Delete an authentication source.
	 */
	function deleteAuthSource($args) {
		$this->validate();

		$authId = (int)@$args[0];
		$authDao =& DAORegistry::getDAO('AuthSourceDAO');
		$authDao->deleteObject($authId);
		Request::redirect(null, null, 'auth');
	}
}

?>
